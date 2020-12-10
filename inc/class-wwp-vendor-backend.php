<?php
if ( !defined('ABSPATH') ) {
	exit; // Exit if accessed directly
}
/**
 * Class to handle backend functionality
 */
if ( !class_exists('WWP_Vendor_Portal_Backend') ) {

	class WWP_Vendor_Portal_Backend {
		
		public function __construct() {
			add_action('admin_menu', array($this, 'wwp_register_custom_menu_page'));
			add_action('admin_init', array($this, 'wwp_request_options'));
			add_action('admin_enqueue_scripts', array($this, 'wwp_admin_script_style'));
			add_filter( 'anr_settings_fields', array($this, 'wwp_wholesale_recaptcha') );
			
			$settings = get_option('wwp_vendor_portal_options', true);
			add_filter('woocommerce_product_data_tabs', array($this, 'wwp_add_wholesale_product_data_tab'), 99, 1);
			add_action('admin_head', array($this, 'wcpp_custom_style'));
			add_filter('woocommerce_screen_ids', array($this, 'wwp_screen_ids' ), 10, 1);
			
			add_action( 'add_meta_boxes', array($this ,'wwp_register_meta_box'));

			add_action( 'save_post_shop_order', array($this , 'update_order_wwp_form_data_json_value') );

			if ( isset($settings['wholesale_role']) && 'single' != $settings['wholesale_role'] ) {
				add_action('woocommerce_product_data_panels', array($this, 'wwp_add_wholesale_product_data_fields_multi'));
				add_action('woocommerce_process_product_meta', array($this, 'wwp_woo_wholesale_fields_save_multi'), 99);
			} else {
				add_action('woocommerce_product_data_panels', array($this, 'wwp_add_wholesale_product_data_fields'));
				add_action('woocommerce_process_product_meta', array($this, 'wwp_woo_wholesale_fields_save'), 99);
				add_action('woocommerce_product_after_variable_attributes', array($this, 'wwp_variation_settings_fields'), 10, 3);
				add_action('woocommerce_save_product_variation', array($this, 'wwp_save_variation_settings_fields'), 10, 2);
			}
		}
		public function wwp_woo_wholesale_fields_save_multi( $post_id ) {
			if ( !isset($_POST['wwp_product_wholesale_nonce']) || !wp_verify_nonce( wc_clean($_POST['wwp_product_wholesale_nonce']), 'wwp_product_wholesale_nonce') ) {
				return;
			}
						
			//hide product for customer
			$_wwp_hide_for_customer = isset($_POST['_wwp_hide_for_customer']) ? wc_clean($_POST['_wwp_hide_for_customer']) : '';        
			update_post_meta($post_id, '_wwp_hide_for_customer', esc_attr($_wwp_hide_for_customer));
			
			//hide product for visitor
			$_wwp_hide_for_visitor = isset($_POST['_wwp_hide_for_visitor']) ? wc_clean($_POST['_wwp_hide_for_visitor']) : '';        
			update_post_meta($post_id, '_wwp_hide_for_visitor', esc_attr($_wwp_hide_for_visitor));
			
		}
		
		
		public function update_order_wwp_form_data_json_value( $post_id ) {
			$wwp_form_data_json = wwp_get_post_data('wwp_form_data_json');
			if ( isset($wwp_form_data_json) ) {
				$wwp_form_data_json = wc_clean($wwp_form_data_json);
				update_post_meta($post_id, 'wwp_form_data_json', $wwp_form_data_json);
			}
			
		}
		public function wwp_register_meta_box() {
			
			$registrations = get_option('wwp_wholesale_registration_options');
			if ( isset($registrations['display_fields_checkout']) && 'yes' == $registrations['display_fields_checkout'] ) {
				add_meta_box( 'wwp_form_builder', 
				esc_html__( 'Checkout Extra Fields Data', 'woocommerce-vendor-portal' ),
				array($this , 'wwp_meta_box_callback'),
				'shop_order', 
				'advanced',
				'high' );
			}
			
		}
		
		public function wwp_meta_box_callback( $order_id ) {
			 echo wp_kses_post( render_form_builder ( 'get_post_meta', $order_id->ID ) ); 
		}
		
		public function wwp_wholesale_recaptcha( $fields ) {
		 
			$add=array( 'wwp_wholesale_recaptcha' => 'Vendor Registration Form'  );
			$fields['enabled_forms']['options'] = array_merge($fields['enabled_forms']['options'], $add);
			return $fields;
		
		}
		
		public function wwp_request_options() {
			register_setting('wwp_wholesale_request_notifications', 'wwp_wholesale_admin_request_notification'); 
			register_setting('wwp_wholesale_request_notifications', 'wwp_wholesale_admin_request_subject'); 
			register_setting('wwp_wholesale_request_notifications', 'wwp_wholesale_admin_request_body'); 
			register_setting('wwp_wholesale_request_notifications', 'wwp_wholesale_request_approve_notification'); 
			register_setting('wwp_wholesale_request_notifications', 'wwp_wholesale_email_request_subject'); 
			register_setting('wwp_wholesale_request_notifications', 'wwp_wholesale_email_request_body'); 
			
			
			register_setting('wwp_wholesale_request_notifications', 'wwp_wholesale_user_registration_notification'); 
			register_setting('wwp_wholesale_request_notifications', 'wwp_wholesale_registration_notification_subject'); 
			register_setting('wwp_wholesale_request_notifications', 'wwp_wholesale_registration_notification_body');

			register_setting('wwp_wholesale_request_notifications', 'wwp_wholesale_user_rejection_notification'); 
			register_setting('wwp_wholesale_request_notifications', 'wwp_wholesale_rejection_notification_subject'); 
			register_setting('wwp_wholesale_request_notifications', 'wwp_wholesale_rejection_notification_body');
			
			
			// version 1.3.0 For Subscriptions User Role Update Notification  
			if ( in_array('woocommerce-subscriptions/woocommerce-subscriptions.php', apply_filters('active_plugins', get_option('active_plugins'))) ) {
				register_setting('wwp_wholesale_request_notifications', 'wwp_wholesale_subscription_role_notification'); 
				register_setting('wwp_wholesale_request_notifications', 'wwp_wholesale_subscription_role_subject'); 
				register_setting('wwp_wholesale_request_notifications', 'wwp_wholesale_subscription_role_body'); 
			}
			// ends version 1.3.0
			$this->upgrade_plugin_fixes();
		}
		
		public function upgrade_plugin_fixes() {
			$settings=get_option('wwp_vendor_portal_options', true);
			// if ( isset( $settings['enable_upgrade'] ) && 'yes' == $settings['enable_upgrade'] ) {
				// $settings['request_again_submit'] = 'yes';
				// $settings['enable_upgrade'] = 'no';
				// update_option('wwp_vendor_portal_options', $settings);
			// }
			
			if ( isset( $settings['upgrade_btn_label'] ) && !empty($settings['upgrade_btn_label'])  ) {
				$settings['upgrade_tab_text'] = $settings['upgrade_btn_label'];
				$settings['upgrade_btn_label'] = '';
				update_option('wwp_vendor_portal_options', $settings);
			}
		}
		
		public function wwp_register_custom_menu_page() {
			add_menu_page(
				esc_html__('Vendor Portal', 'woocommerce-vendor-portal'),
				esc_html__('Vendor Portal', 'woocommerce-vendor-portal'),
				'manage_vendor',
				'wwp_vendor',
				array($this, 'wwp_vendor_page_callback'),
				'dashicons-groups',
				59
			);
			add_submenu_page( 
				'wwp_vendor', 
				esc_html__('Vendor For WooCommerce', 'woocommerce-vendor-portal'), 
				esc_html__('Settings', 'woocommerce-vendor-portal'), 
				'manage_wholesale_settings', 
				'wwp_vendor',
				array($this, 'wwp_vendor_page_callback')
			);
			add_submenu_page( 
				'wwp_vendor', 
				esc_html__('User Roles', 'woocommerce-vendor-portal'), 
				esc_html__('User Roles', 'woocommerce-vendor-portal'), 
				'manage_wholesale_user_role', 'edit-tags.php?taxonomy=wholesale_user_roles'
			);
			
			add_submenu_page( 
				'wwp_vendor', 
				esc_html__('Notifications', 'woocommerce-vendor-portal'), 
				esc_html__('Notifications', 'woocommerce-vendor-portal'), 
				'manage_wholesale_notifications', 
				'wwp_wholesale_notifcations',
				array($this, 'wwp_wholesale_notifications_callback')
			);
		}
		public function wwp_vendor_page_callback() {
			$settings= !empty(get_option('wwp_vendor_portal_options')) ? get_option('wwp_vendor_portal_options', true) : array();
			if ( isset($_POST['save-wwp_vendor']) ) {
				if ( isset($_POST['wwp_vendor_register_nonce']) || wp_verify_nonce( wc_clean($_POST['wwp_vendor_register_nonce']), 'wwp_vendor_register_nonce') ) {
					$settings = isset($_POST['options']) ? wc_clean($_POST['options']) : '';
					$settings['enable_registration_page'] = isset($settings['enable_registration_page']) ? 'yes' : 'no';
					$settings['wholesaler_allow_minimum_qty'] = isset($settings['wholesaler_allow_minimum_qty']) ? 'yes' : 'no';
					$settings['wholesaler_prodcut_only'] = isset($settings['wholesaler_prodcut_only']) ? 'yes' : 'no';
					$settings['enable_upgrade'] = isset($settings['enable_upgrade']) ? 'yes' : 'no';
					$settings['disable_auto_role'] = isset($settings['disable_auto_role']) ? 'yes' : 'no';
					$settings['retailer_disabled'] = isset($settings['retailer_disabled']) ? 'yes' : 'no';
					$settings['save_price_disabled'] = isset($settings['save_price_disabled']) ? 'yes' : 'no';
					update_option('wwp_vendor_portal_options', $settings);
					if (isset($settings['wholesale_role']) && 'multiple' == $settings['wholesale_role']) {
						$roles=get_terms(
						'wholesale_user_roles', array(
								'hide_empty' => false,
							)
						);
						$data=array();
						if ( !empty($roles) ) {
							foreach ( $roles as $key => $role ) {
								if ( !isset($_POST['role_' . $role->term_id]) ) {
									continue;
								} 
								if ( isset($_POST['role_' . $role->term_id]) ) {
									$data[$role->term_id]['slug']=$role->slug;
								}
								if ( isset($_POST['discount_type_' . $role->term_id]) ) {
									$data[$role->term_id]['discount_type'] = wc_clean($_POST['discount_type_' . $role->term_id]);
								}
								if ( isset($_POST['wholesale_price_' . $role->term_id]) ) {
									$data[$role->term_id]['wholesale_price'] = is_numeric( wc_clean($_POST['wholesale_price_' . $role->term_id]) ) ? wc_clean($_POST['wholesale_price_' . $role->term_id]) : '';
								}
								if ( isset($_POST['min_quatity_' . $role->term_id]) ) {
									$data[$role->term_id]['min_quatity'] = is_numeric( wc_clean($_POST['min_quatity_' . $role->term_id]) ) ? wc_clean($_POST['min_quatity_' . $role->term_id]) : 1;
								}
							}
						}
						update_option('wholesale_multi_user_pricing', $data);
					} else {
						if ( isset($_POST['_wwp_enable_wholesale_item']) ) {
							update_option('_wwp_enable_wholesale_item', 'yes');
						} else {
							update_option('_wwp_enable_wholesale_item', 'no');
						}
						if ( isset($_POST['_wwp_wholesale_amount']) ) {
							update_option('_wwp_wholesale_amount', wc_clean($_POST['_wwp_wholesale_amount']) );
						} else {
							update_option('_wwp_wholesale_amount', '');
						}
						if ( isset($_POST['_wwp_wholesale_type']) ) {
							update_option('_wwp_wholesale_type', wc_clean($_POST['_wwp_wholesale_type']) );
						} else {
							update_option('_wwp_wholesale_type', '');
						}
						if ( isset($_POST['_wwp_wholesale_min_quantity']) ) {
							update_option('_wwp_wholesale_min_quantity', wc_clean($_POST['_wwp_wholesale_min_quantity']) );
						} else {
							update_option('_wwp_wholesale_min_quantity', '');
						}
					}
				}
			} 
			?>
			
			<form action="" method="post">
				<h2><?php esc_html_e('Vendor For WooCommerce', 'woocommerce-vendor-portal'); ?></h2><hr>
				<?php wp_nonce_field('wwp_vendor_register_nonce', 'wwp_vendor_register_nonce'); ?>
				<table class="form-table wwp-main-settings">
					<tbody>
						<tr scope="row">
							<th><label for=""><?php esc_html_e('Vendor Portal Mode', 'woocommerce-vendor-portal'); ?></label></th>
							<td>
								<p>
									<label for="single_wholesaler_role">
										<input id="single_wholesaler_role" type="radio" value="single" name="options[wholesale_role]" <?php echo ( isset($settings['wholesale_role']) && 'single' == $settings['wholesale_role'] ) ? 'checked' : ''; ?>>
										<?php esc_html_e(' Single Vendor Role', 'woocommerce-vendor-portal'); ?>
										<span data-tip="Default settings for single user role." class="data-tip-top"><span class="woocommerce-help-tip"></span></span>
										

									</label>
								</p>
								<p>
									<label for="multiple_wholesaler_role">
										<input id="multiple_wholesaler_role" type="radio" value="multiple" name="options[wholesale_role]" <?php echo ( isset($settings['wholesale_role']) && 'multiple' == $settings['wholesale_role'] ) ? 'checked' : ''; ?>>
										<?php esc_html_e(' Multiple Vendor Roles', 'woocommerce-vendor-portal'); ?>
										<span data-tip="Manage prices according to multiple wholesaler user roles." class="data-tip-top"><span class="woocommerce-help-tip"></span></span>
									</label>
								</p>
							</td>
						</tr>
						
						<tr scope="row" id="multiroledropdown">
						
						<th><label for="default_multipe_wholesale_roles"><?php esc_html_e('Default Multi Vendor Roles', 'woocommerce-vendor-portal'); ?></label></th>
							<td>
								<?php 
								$allterms = get_terms('wholesale_user_roles', array('hide_empty' => false));
								?>
								<select id="default_multipe_wholesale_roles" class="regular-text" name="options[default_multipe_wholesale_roles]" >
									<option value=""><?php esc_html_e('Select Vendor Role', 'woocommerce-vendor-portal'); ?></option>
									<?php  
									foreach ( $allterms as $allterm ) {
										 $selected='';
										if ( isset($settings['default_multipe_wholesale_roles']) && $settings['default_multipe_wholesale_roles']== $allterm->slug ) {
												$selected='selected';
										}
										?>
										<option value="<?php echo esc_attr($allterm->slug); ?>" <?php echo esc_html($selected); ?>><?php echo esc_html($allterm->name); ?></option>
									<?php } ?> 
								</select>     
								<span data-tip="Define the default wholesaler role for your user." class="data-tip-top"><span class="woocommerce-help-tip"></span></span>
							</td>
						</tr>
						
						<tr scope="row">
							<th>
								<label for=""><?php esc_html_e('Disable Auto Approval', 'woocommerce-vendor-portal'); ?></label>
							</th>
							<td>
								<label for="disable_auto_role">
									<input id="disable_auto_role" type="checkbox" value="yes" name="options[disable_auto_role]" <?php echo ( isset($settings['disable_auto_role']) && 'yes' == $settings['disable_auto_role'] ) ? 'checked' : ''; ?>>
									<?php esc_html_e(' Check this option to disable auto approval for vendor user role registration requests', 'woocommerce-vendor-portal'); ?>
								</label>
							</td>
						</tr>
						<tr scope="row">
							<th><label for=""><?php esc_html_e('Enable Registration Link', 'woocommerce-vendor-portal'); ?></label></th>
							<td>
								<label for="enable_registration_page">
									<input id="enable_registration_page" type="checkbox" value="yes" name="options[enable_registration_page]" <?php echo ( isset( $settings['enable_registration_page'] ) && 'yes' == $settings['enable_registration_page'] ) ? 'checked' : ''; ?>>
									<?php esc_html_e(' Enable vendor registration link on my account page (You must enable registration form on myaccount page to work this functionality)', 'woocommerce-vendor-portal'); ?>
								</label>
							</td>
						</tr>
						<tr scope="row">
							<th><label for="registration_page_for_wholesale"><?php esc_html_e('Registration Page', 'woocommerce-vendor-portal'); ?></label></th>
							<td>
								<?php 
								$args = array(
										'posts_per_page'   => -1,
										'post_type'        => 'page',
										'post_status'      => 'publish',
									);
								$pages = get_posts($args); 
								?>
								<select id="registration_page_for_wholesale" class="regular-text" name="options[registration_page]" >
									<option value=""><?php esc_html_e('Select Page', 'woocommerce-vendor-portal'); ?></option>
									<?php  
									foreach ( $pages as $page ) {
										$selected='';
										if ( isset($settings['registration_page']) && $settings['registration_page']== $page->ID ) {
											$selected='selected';
										}
										?>
										<option value="<?php echo esc_attr($page->ID); ?>" <?php echo esc_html($selected); ?>><?php echo esc_html($page->post_title); ?></option>
									<?php } ?> 
								</select>  
								<span data-tip="Select the page on which you want to display your vendor registration form." class="data-tip-top"><span class="woocommerce-help-tip"></span></span>								
							</td>
						</tr>
						<tr scope="row">
							<th><label for="registration_page_for_wholesale"><?php esc_html_e('Registration Page Redirect', 'woocommerce-vendor-portal'); ?></label></th>
							<td>
							<?php 
								$args = array(
										'posts_per_page'   => -1,
										'post_type'        => array('page','product'),
										'post_status'      => 'publish',
										
									);
								$pages = get_posts($args); 
								?>
								<select id="register_redirect" class="regular-text" name="options[register_redirect]" >
									<option value=""><?php esc_html_e('Select Page Or Product', 'woocommerce-vendor-portal'); ?></option>
									<?php  

									foreach ( $pages as $page ) {
										$selected='';
										if ( 'page' == $page->post_type ) {
											$post_name = $page->post_title . ' - Page';
										} else {
											$post_name = $page->post_title . ' - Product';
										}
										
										if ( isset($settings['register_redirect']) && $settings['register_redirect']== $page->ID ) {
											$selected='selected';
										}
										?>
										<option value="<?php echo esc_attr($page->ID); ?>" <?php echo esc_html($selected); ?>><?php echo esc_html($post_name); ?></option>
									<?php } ?> 
								</select>    
							<span data-tip="Please select a page or product to redirect after a successful registration." class="data-tip-top"><span class="woocommerce-help-tip"></span></span>
							</td>
						</tr>
						<tr scope="row">
							<th colspan="2"><h2><?php esc_html_e('Price Labels', 'woocommerce-vendor-portal'); ?></h2></th>
						</tr>
						<tr scope="row">
							<th><label for="retailer_label"><?php esc_html_e('Retailer Price Label', 'woocommerce-vendor-portal'); ?></label></th>
							<td><input type="text" class="regular-text" name="options[retailer_label]" id="retailer_label" value="<?php echo isset($settings['retailer_label']) ? esc_html($settings['retailer_label']) : ''; ?>"> 
							<input id="retailer_disabled" type="checkbox" value="yes" name="options[retailer_disabled]" <?php echo ( isset($settings['retailer_disabled']) && 'yes' == $settings['retailer_disabled'] ) ? 'checked' : ''; ?>><label for="retailer_disabled"><?php esc_html_e('Label Hide', 'woocommerce-vendor-portal'); ?></label>
							<span data-tip="Hide price Label for vendor user only." class="data-tip-top"><span class="woocommerce-help-tip"></span></span>
							</td>
						</tr>
						<tr scope="row">
							<th><label for="wholesaler_price_label"><?php esc_html_e('Wholesaler Price Label', 'woocommerce-vendor-portal'); ?></label></th>
							<td><input type="text" class="regular-text" name="options[wholesaler_label]" id="wholesaler_price_label" value="<?php echo isset($settings['wholesaler_label']) ? esc_html($settings['wholesaler_label']) : ''; ?>">
							</td>
						</tr>
						<tr scope="row">
							<th><label for="save_price_label"><?php esc_html_e('Save Price Label', 'woocommerce-vendor-portal'); ?></label></th>
							<td><input type="text" class="regular-text" name="options[save_label]" id="save_price_label" value="<?php echo isset($settings['save_label']) ? esc_html($settings['save_label']) : ''; ?>">
							<input id="save_price_disabled" type="checkbox" value="yes" name="options[save_price_disabled]" <?php echo ( isset($settings['save_price_disabled']) && 'yes' == $settings['save_price_disabled'] ) ? 'checked' : ''; ?>>
							<label for="save_price_disabled"><?php esc_html_e('Label Hide', 'woocommerce-vendor-portal'); ?></label>
							<span data-tip="Hide price Label for vendor user only." class="data-tip-top"><span class="woocommerce-help-tip"></span></span>
							</td>
						</tr>
						<!-----version 1.3.0 ---->
						<?php 
						if ( in_array('woocommerce-subscriptions/woocommerce-subscriptions.php', apply_filters('active_plugins', get_option('active_plugins'))) && isset($settings['wholesale_role']) ) {
							?>
							<tr scope="row">
								<th colspan="2"><h2><?php esc_html_e('Wholesaler Subscription', 'woocommerce-vendor-portal'); ?></h2></th>
							</tr>
							<tr scope="row">
								<th><label for=""><?php esc_html_e('Enable Vendor For Subscription', 'woocommerce-vendor-portal'); ?></label></th>
								<td>
									<label for="enable_subscription">
										<input id="enable_subscription" type="checkbox" value="yes" name="options[enable_subscription]" <?php echo ( isset($settings['enable_subscription']) && 'yes' == $settings['enable_subscription'] ) ? 'checked' : ''; ?>>
										<?php esc_html_e(' if checked Wholesaler role will be assigned on the respective vendor subscription.', 'woocommerce-vendor-portal'); ?>
									</label>
								</td>
							</tr>
							<?php
							$args = array(
									'post_type'     => array('product'),
									'post_status' => 'publish',
									'posts_per_page' => -1,
									'tax_query' => array(
										array(
											'taxonomy' => 'product_type',
											'field'    => 'slug',
											'terms'    => 'variable-subscription',
										)
									)
								);
							$products = get_posts($args);
							?>
							<tr scope="row">
								<th><label for=""><?php esc_html_e('Select Variable Subscription', 'woocommerce-vendor-portal'); ?></label></th>
								<td>
									<label for="wholesale_subscription">
										<select id="wholesale_subscription" name="options[wholesale_subscription]">
										<option disabled><?php esc_html_e('Select variable', 'woocommerce-vendor-portal'); ?></option> 
										<?php 
										if ( !empty($products) ) {
											foreach ( $products as $key => $product ) {
												$selected = ( isset($settings['wholesale_subscription']) && $product->ID == $settings['wholesale_subscription'] ) ? 'selected' : '';
												echo '<option value="' . esc_attr($product->ID) . '" ' . esc_attr($selected) . ' >' . esc_html($product->post_title) . '</option>';
											}
										}
										?>
										</select>
										<p><?php esc_html_e('Select the variable subscription product.', 'woocommerce-vendor-portal'); ?></p>
									</label>
								</td>
							</tr>
					<?php } ?>
						<!-----end version 1.3.0 ---->
						<tr scope="row">
							<th colspan="2"><h2><?php esc_html_e('Non-logged in user settings', 'woocommerce-vendor-portal'); ?></h2></th>
						</tr>
						<tr scope="row">
						<th><label for=""><?php esc_html_e('Hide price', 'woocommerce-vendor-portal'); ?></label></th>
							<td>
								<label for="price_hide">
									<input id="price_hide" type="checkbox" value="yes" name="options[price_hide]" <?php echo ( isset($settings['price_hide']) && 'yes' == $settings['price_hide'] ) ? 'checked' : ''; ?>>
									<?php 
										esc_html_e('Hide retail prices until user gets logged in', 'woocommerce-vendor-portal'); 
									?>
								</label>
							</td>
						</tr>
						<tr scope="row">
							<th><label for="display_link_text"><?php esc_html_e('Lable for login link', 'woocommerce-vendor-portal'); ?></label></th>
							<td><input type="text" class="regular-text" name="options[display_link_text]" id="display_link_text" value="<?php echo isset($settings['display_link_text']) ? esc_html($settings['display_link_text']) : ''; ?>">
							<span data-tip="This login link will appear on every product if Hide price option is checked" class="data-tip-top"><span class="woocommerce-help-tip"></span></span>
							</td>
						</tr>
						<!----restrict vendor user code----->
						<tr scope="row">
							<th colspan="2"><h2><?php esc_html_e('login Restriction', 'woocommerce-vendor-portal'); ?></h2></th>
						</tr>
						<tr scope="row">
							<th><label for=""><?php esc_html_e('Restrict vendor store access', 'woocommerce-vendor-portal'); ?></label></th>
							<td>
								<label for="wholesaler_login_restriction">
									<input id="wholesaler_login_restriction" type="checkbox" value="yes" name="options[wholesaler_login_restriction]" <?php echo ( isset($settings['wholesaler_login_restriction']) && 'yes' == $settings['wholesaler_login_restriction'] ) ? 'checked' : ''; ?>>
									<?php esc_html_e('Enabling this option will allow only approved vendor users to login.', 'woocommerce-vendor-portal'); ?>
								</label>
							</td>
						</tr>
						<tr scope="row">
							<th><label for="login_message_waiting_user"><?php esc_html_e('Custom message for pending request', 'woocommerce-vendor-portal'); ?></label></th>
							<?php 
							if ( empty($settings['login_message_waiting_user']) ) {
								$settings['login_message_waiting_user'] = __('You can not access this store, Your request status is in Pending');
							}
							?>
							<td><input type="text" class="regular-text" name="options[login_message_waiting_user]" id="login_message_waiting_user" value="<?php echo isset($settings['login_message_waiting_user']) ? esc_html($settings['login_message_waiting_user']) : ''; ?>">
							<span data-tip="Enter message to display for pending request" class="data-tip-top"><span class="woocommerce-help-tip"></span></span>
							</td>
						</tr>
						<tr scope="row">
							<th><label for="login_message_rejected_user"><?php esc_html_e('Custom message for rejected request', 'woocommerce-vendor-portal'); ?></label></th>
							<?php 
							if ( empty($settings['login_message_rejected_user']) ) {
								$settings['login_message_rejected_user'] = __('You can not access this store, Your request is Rejected by admin');
							}
							?>
							<td><input type="text" class="regular-text" name="options[login_message_rejected_user]" id="login_message_rejected_user" value="<?php echo isset($settings['login_message_rejected_user']) ? esc_html($settings['login_message_rejected_user']) : ''; ?>">
							<span data-tip="Enter message to display for rejected request" class="data-tip-top"><span class="woocommerce-help-tip"></span></span>
							</td>
						</tr>
						<!----restrict vendor user code end-->
						<tr scope="row">
							<th colspan="2"><h2><?php esc_html_e('Products Visibility', 'woocommerce-vendor-portal'); ?></h2></th>
						</tr>
						<tr scope="row">
							<th><label for=""><?php esc_html_e('Restrict vendor products visibility', 'woocommerce-vendor-portal'); ?></label></th>
							<td>
								<label for="wholesaler_prodcut_only">
									<input id="wholesaler_prodcut_only" type="checkbox" value="yes" name="options[wholesaler_prodcut_only]" <?php echo ( isset($settings['wholesaler_prodcut_only']) && 'yes' == $settings['wholesaler_prodcut_only'] ) ? 'checked' : ''; ?>>
									<?php esc_html_e('By enabling this option vendor only products will be visible to wholesaler user roles only.', 'woocommerce-vendor-portal'); ?>
								</label>
							</td>
						</tr>
						<tr scope="row">
							<th><label for=""><?php esc_html_e('Restrict vendor products globally from non-wholesaler customer.', 'woocommerce-vendor-portal'); ?></label></th>
							<td>
								<label for="non_wholesale_product_hide">
									<input id="non_wholesale_product_hide" type="checkbox" value="yes" name="options[non_wholesale_product_hide]" <?php echo ( isset($settings['non_wholesale_product_hide']) && 'yes' == $settings['non_wholesale_product_hide'] ) ? 'checked' : ''; ?>>
									<?php esc_html_e('Enable this option to hide vendor products from retailers and non-logged in user.', 'woocommerce-vendor-portal'); ?>
								</label>
							</td>
						</tr>
						<tr scope="row">
							<th><label for=""><?php esc_html_e('Enforce minimum quantity rules', 'woocommerce-vendor-portal'); ?></label></th>
							<td>
								<label for="wholesaler_allow_minimum_qty">
									<input id="wholesaler_allow_minimum_qty" type="checkbox" value="yes" name="options[wholesaler_allow_minimum_qty]" <?php echo ( isset($settings['wholesaler_allow_minimum_qty']) && 'yes' == $settings['wholesaler_allow_minimum_qty'] ) ? 'checked' : ''; ?>>
									<?php esc_html_e('Enforce the vendor customer to purchase with minimum quantity rules', 'woocommerce-vendor-portal'); ?>
								</label>
							</td>
						</tr>
						<tr scope="row">
							<th colspan="2"><h2><?php esc_html_e('Upgrade Customer', 'woocommerce-vendor-portal'); ?></h2></th>
						</tr>
						<tr scope="row">
							<th><label for=""><?php esc_html_e('Enable Upgrade Tab', 'woocommerce-vendor-portal'); ?></label></th>
							<td>
								<label for="enable_upgrade">
									<input id="enable_upgrade" type="checkbox" value="yes" name="options[enable_upgrade]" <?php echo ( isset($settings['enable_upgrade']) && 'yes' == $settings['enable_upgrade'] ) ? 'checked' : ''; ?>>
									<?php esc_html_e(' Enable vendor upgrade tab on my account page for non vendor users', 'woocommerce-vendor-portal'); ?>
								</label>
							</td>
						</tr>
						<tr scope="row">
							<th><label for=""><?php esc_html_e('Request Again Submit', 'woocommerce-vendor-portal'); ?></label></th>
							<td>
								<label for="request_again_submit">
									<input id="request_again_submit" type="checkbox" value="yes" name="options[request_again_submit]" <?php echo ( isset($settings['request_again_submit']) && 'yes' == $settings['request_again_submit'] ) ? 'checked' : ''; ?>>
									<?php esc_html_e('Ability to enable submitting request again after rejection.', 'woocommerce-vendor-portal'); ?>
								</label>
							</td>
						</tr>
						<tr scope="row">
							<th><label for=""><?php esc_html_e('Upgrade Tab Text', 'woocommerce-vendor-portal'); ?></label></th>
							<td>
								<label for="upgrade_tab_text">
									<input type="text" class="regular-text" name="options[upgrade_tab_text]" id="upgrade_tab_text" value="<?php echo isset($settings['upgrade_tab_text']) ? esc_html($settings['upgrade_tab_text']) : ''; ?>" Placeholder="Label for Upgrade to Wholesaler tab">
								</label>
								<span data-tip='Display any text you want on the "Upgrade to Wholesaler" tab.' class="data-tip-top"><span class="woocommerce-help-tip"></span></span>
							</td>
						</tr>
						
						
						<tr scope="row">
							<th colspan="2"><h2><?php esc_html_e('Additional CSS', 'woocommerce-vendor-portal'); ?></h2></th>
						</tr>
						
						<?php 
						if ( empty($settings['wholesale_css']) ) { 
							$settings['wholesale_css'] = '/* Enter Your Custom CSS Here */';
						}
						?>
						
						<tr scope="row">
							<th><label for=""><?php esc_html_e('Registration Page CSS', 'woocommerce-vendor-portal'); ?></label></th>
							<td>
								<label for="code_editor_page_css">
									<textarea id="code_editor_page_css" rows="5" name="options[wholesale_css]" class="widefat textarea"><?php echo wp_kses_post(wp_unslash( $settings['wholesale_css'] )); ?></textarea> 
									<p class="wwwp_help_text"><?php esc_html_e('Enter css without <style> tag.', 'woocommerce-vendor-portal'); ?></p>									
								</label>
							</td>
						</tr>
						
						
					</tbody>            
				</table>
				<?php
				if (isset($settings['wholesale_role']) && 'multiple' == $settings['wholesale_role']) {
					$roles=get_terms(
						'wholesale_user_roles', array(
							'hide_empty' => false,
						) 
					);
					if ( !empty($roles) ) { 
						$data = get_option( 'wholesale_multi_user_pricing' );
						?>
					<h2><?php esc_html_e('Vendor Portal Global Option', 'woocommerce-vendor-portal'); ?></h2>
					<table class="vendor_portal">
						<tr>
							<th><?php esc_html_e('Vendor Role', 'woocommerce-vendor-portal'); ?></th>
							<th><?php esc_html_e('Enable for Role', 'woocommerce-vendor-portal'); ?></th>
							<th><?php esc_html_e('Discount Type', 'woocommerce-vendor-portal'); ?></th>
							<th><?php esc_html_e('Vendor Price', 'woocommerce-vendor-portal'); ?></th>
							<th><?php esc_html_e('Min Quantity', 'woocommerce-vendor-portal'); ?></th>
						</tr>
							<?php 
							foreach ( $roles as $key => $role ) {
								$min=1;
								$price='';
								$discount='';
								 
								if (isset($data[$role->term_id])) {
									$min=$data[$role->term_id]['min_quatity'];
									$price=$data[$role->term_id]['wholesale_price'];
									$discount=$data[$role->term_id]['discount_type'];
								}
								?>
								<tr>
									<td>
										<span class="wwp-title"><?php esc_html_e($role->name); ?></span>
									</td>
									<td>
										<input type="checkbox" value="<?php esc_attr_e($role->slug); ?>" name="role_<?php esc_attr_e($role->term_id); ?>" <?php echo isset($data[$role->term_id]) ? 'checked' : ''; ?> >
									</td>
									<td>
										<select class="widefat" name="discount_type_<?php esc_attr_e($role->term_id); ?>" value="">
											<option value="percent" <?php selected($discount, 'percent'); ?> > <?php esc_html_e('Percent', 'woocommerce-vendor-portal'); ?> </option>
											<option value="fixed"  <?php selected($discount, 'fixed'); ?> > <?php esc_html_e('Fixed', 'woocommerce-vendor-portal'); ?> </option>
										</select>
									</td>
									<td>
										<input class="widefat" type="text" name="wholesale_price_<?php esc_attr_e($role->term_id); ?>" value="<?php esc_attr_e($price); ?>">
									</td>
									<td>
										<input class="widefat" type="text" name="min_quatity_<?php esc_attr_e($role->term_id); ?>" value="<?php esc_attr_e($min); ?>">
									</td>
								</tr>
						<?php } ?>
					</table>
						<?php
					}
				} else {
					$enable = get_option('_wwp_enable_wholesale_item');
					$amount = get_option('_wwp_wholesale_amount');
					$type = get_option('_wwp_wholesale_type');
					$qty = get_option('_wwp_wholesale_min_quantity');
					?>
					<h2><?php esc_html_e('Vendor Portal Global Option', 'woocommerce-vendor-portal'); ?></h2>
					<table class="form-table">
						<tr>
							<th>
								<label for="_wwp_enable_wholesale_item">
									<?php esc_html_e('Enable Vendor Prices', 'woocommerce-vendor-portal'); ?>
								</label>
							</th>
							<td scope="row">
								<input type="checkbox" name="_wwp_enable_wholesale_item" id="_wwp_enable_wholesale_item" value="yes" <?php checked('yes', $enable); ?>>
								<span><?php esc_html_e('Enable vendor prices.', 'woocommerce-vendor-portal'); ?></span>
							</td>
						</tr>
						<tr>
							<th>
								<label for="_wwp_wholesale_type">
									<?php esc_html_e('Vendor Discount Type', 'woocommerce-vendor-portal'); ?>
								</label>
							</th>
							<td scope="row">
								<select name="_wwp_wholesale_type" id="_wwp_wholesale_type" class="regular-text">
									<option value="fixed" <?php selected('fixed', $type); ?>><?php esc_html_e('Fixed Amount', 'woocommerce-vendor-portal'); ?></option>
									<option value="percent" <?php selected('percent', $type); ?>><?php esc_html_e('Percentage', 'woocommerce-vendor-portal'); ?></option>
								</select>
								<p><?php esc_html_e('Price type for vendor products.', 'woocommerce-vendor-portal'); ?></p>
							</td>
						</tr>
						<tr>
							<th>
								<label for="_wwp_wholesale_amount">
									<?php esc_html_e('Enter Vendor Amount', 'woocommerce-vendor-portal'); ?>
								</label>
							</th>
							<td scope="row">
								<input type="text" name="_wwp_wholesale_amount" id="_wwp_wholesale_amount" value="<?php esc_attr_e($amount); ?>" class="regular-text">
								<p><?php esc_html_e('Enter vendor amount.', 'woocommerce-vendor-portal'); ?></p>
							</td>
						</tr>
						<tr>
							<th>
								<label for="_wwp_wholesale_min_quantity">
									<?php esc_html_e('Minimum Quantity', 'woocommerce-vendor-portal'); ?>
								</label>
							</th>
							<td scope="row">
								<input type="number" name="_wwp_wholesale_min_quantity" id="_wwp_wholesale_min_quantity" value="<?php esc_attr_e($qty); ?>" class="regular-text">
								<p><?php esc_html_e('Enter vendor minimum quantity to apply discount.', 'woocommerce-vendor-portal'); ?></p>
							</td>
						</tr>
					</table>
					<?php
				}
				?>

				<p><button name="save-wwp_vendor" class="button-primary" type="submit" value="Save changes"><?php esc_html_e('Save changes', 'woocommerce-vendor-portal'); ?></button></p>
			</form>
			<?php
		}
		
		/**
		 * Initialize product vendor data tab
		 * 
		 * @since   1.0
		 * @version 1.0
		 */
		public function wwp_add_wholesale_product_data_tab( $product_data_tabs ) {
			$product_data_tabs['wwp-vendor-tab'] = array(
				'label' => esc_html__('Vendor', 'woocommerce-vendor-portal'),
				'target' => 'wwp_wholesale_product_data',
			);
			return $product_data_tabs;
		}
		/**
		 * Initialize product vendor data tab
		 * 
		 * @since   1.0
		 * @version 1.0
		 */
		public function wcpp_custom_style() {
			?>
			<style>
				.wwp-vendor-tab_tab a:before {
					font-family: Dashicons;
					content: "\f240" !important;
				}
			</style>
			<?php
		}
		/**
		 * Product vendor data tab multi users
		 * 
		 * @since   1.0
		 * @version 1.0
		 */
		public function wwp_add_wholesale_product_data_fields_multi() { 
			// version 1.3.0
			global $post;
			$product_id = $post->ID;
			$roles = array();
			$taxroles = get_terms(
					'wholesale_user_roles', array(
					'hide_empty' => false,
				)
			);
			if ( !empty($taxroles) ) {
				foreach ( $taxroles as $key => $role ) {
					$roles[$role->slug] = $role->name;
				}
			}
			?>
			<div id="wwp_wholesale_product_data" class="panel woocommerce_options_panel">
			<?php
			wp_nonce_field('wwp_product_wholesale_nonce', 'wwp_product_wholesale_nonce');
						
			woocommerce_wp_checkbox(
				array(
					'id'            => '_wwp_hide_for_customer',
					'wrapper_class' => '_wwp_hide_for_customer',
					'label'         => esc_html__('Hide Product', 'woocommerce-vendor-portal'),
					'description'   => esc_html__('Hide this product from users having customer role', 'woocommerce-vendor-portal')
				)
			);

			woocommerce_wp_checkbox(
				array(
					'id'            => '_wwp_hide_for_visitor',
					'wrapper_class' => '_wwp_hide_for_visitor',
					'label'         => esc_html__('Hide Product', 'woocommerce-vendor-portal'),
					'description'   => esc_html__('Hide this product from visitors', 'woocommerce-vendor-portal')
				)
			);
			
			
			$value = get_post_meta($product_id, 'wholesale_product_visibility_multi', true);
			woocommerce_wp_select(
				array(
				'id'				=> 'wholesale_product_visibility_multi[]',
				'label'				=> esc_html__( 'Hide Product for Wholesaler Roles', 'woocommerce-vendor-portal' ),
				'type'				=> 'select',
				'class'				=> 'wc-enhanced-select',
				'style'				=> 'min-width: 50%;',
				'desc_tip'			=> 'true',
				'description'		=> esc_html__( 'Choose specific user roles to hide the product.', 'woocommerce-vendor-portal' ),
				'options'			=> $roles,
				'value' 			=> $value,
				'custom_attributes'	=>	array(
									'multiple'	=>	'multiple'
								)
				)
			); // ends version 1.3.0
			?>
				<div id="wwp_wholesale_product_data" class="panel woocommerce_options_panel">
					<div id="variable_product_options" class=" wc-metaboxes-wrapper" style="display: block;">
						<div id="variable_product_options_inner">
							<div id="message" class="inline notice woocommerce-message">
								<p><?php echo sprintf('%1$s <strong>%2$s</strong> %3$s', esc_html__('For', 'woocommerce-vendor-portal'), esc_html__('Multi-user vendor roles', 'woocommerce-vendor-portal'), esc_html__('manage price from vendor metabox', 'woocommerce-vendor-portal')); ?></p>
								<p><a class="button-primary" id="vendor-portal-pro-multiuser-move"><?php esc_html_e('Move', 'woocommerce-vendor-portal'); ?></a></p>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php
		}
		/**
		 * Product vendor data tab single user 
		 * 
		 * @since   1.0
		 * @version 1.0
		 */
		public function wwp_add_wholesale_product_data_fields() {
			global $woocommerce, $post, $product; 
			?>
			<!-- id below must match target registered in above wwp_add_wholesale_product_data_tab function -->
			<div id="wwp_wholesale_product_data" class="panel woocommerce_options_panel">
				<?php
				wp_nonce_field('wwp_product_wholesale_nonce', 'wwp_product_wholesale_nonce');

				woocommerce_wp_checkbox(
					array(
						'id'            => '_wwp_enable_wholesale_item',
						'wrapper_class' => 'wwp_enable_wholesale_item',
						'label'         => esc_html__('Enable Vendor Item', 'woocommerce-vendor-portal'),
						'description'   => esc_html__('Add this item for vendor customers', 'woocommerce-vendor-portal')
					)
				);
				woocommerce_wp_select(
					array(
						'id'      => '_wwp_wholesale_type',
						'label'   => esc_html__('Vendor Type', 'woocommerce-vendor-portal'),
						'options' => array(
							'fixed'   => esc_html__('Fixed Amount', 'woocommerce-vendor-portal'),
							'percent' => esc_html__('Percent', 'woocommerce-vendor-portal'),
						)
					)
				);
				
				woocommerce_wp_checkbox(
					array(
						'id'            => '_wwp_hide_for_customer',
						'wrapper_class' => '_wwp_hide_for_customer',
						'label'         => esc_html__('Hide Product', 'woocommerce-vendor-portal'),
						'description'   => esc_html__('Hide this product from users having customer role', 'woocommerce-vendor-portal')
					)
				);
				
				woocommerce_wp_checkbox(
					array(
						'id'            => '_wwp_hide_for_visitor',
						'wrapper_class' => '_wwp_hide_for_visitor',
						'label'         => esc_html__('Hide Product', 'woocommerce-vendor-portal'),
						'description'   => esc_html__('Hide this product from visitors', 'woocommerce-vendor-portal')
					)
				);
				
				
				
				// version 1.3.0
				woocommerce_wp_checkbox(
					array(
						'id'            => '_wwp_wholesale_product_visibility',
						'wrapper_class' => 'wwp_wholesale_product_visibility',
						'label'         => esc_html__('Hide Product for Wholesaler Roles', 'woocommerce-vendor-portal'),
						'description'   => esc_html__('Hide this product for Vendor user', 'woocommerce-vendor-portal')
					)
				); // ends version 1.3.0
				echo '<div class="hide_if_variable">';
					woocommerce_wp_text_input(
						array(
							'id'          => '_wwp_wholesale_amount',
							'label'       => esc_html__('Enter Vendor Amount', 'woocommerce-vendor-portal'),
							'placeholder' => get_woocommerce_currency_symbol() . '15',
							'desc_tip'    => 'true',
							'description' => esc_html__('Enter Vendor Price (e.g 15)', 'woocommerce-vendor-portal')
						)
					);
					woocommerce_wp_text_input(
						array(
							'id'          => '_wwp_wholesale_min_quantity',
							'label'       => esc_html__('Minimum Quantity', 'woocommerce-vendor-portal'),
							'placeholder' => '1',
							'desc_tip'    => 'true',
							'description' => esc_html__('Minimum quantity to apply vendor price (default is 1)', 'woocommerce-vendor-portal'),
							'type'        => 'number',
							'custom_attributes' => array(
								'step'     => '1',
								'min'    => '1'
							)
						)
					);
				echo '</div>';
				echo '<div class="show_if_variable">';
				echo '<p>' . esc_html__('For Variable Product you can add vendor price from variations tab', 'woocommerce-vendor-portal') . '</p>';
				echo '</div>';
				?>
			</div>
			<?php
		}
		/**
		 * Save product meta fields
		 * 
		 * @param   $post_id to save product meta
		 * @since   1.0
		 * @version 1.0
		 */
		public function wwp_woo_wholesale_fields_save( $post_id ) {
			if ( !isset($_POST['wwp_product_wholesale_nonce']) || !wp_verify_nonce( wc_clean($_POST['wwp_product_wholesale_nonce']), 'wwp_product_wholesale_nonce') ) {
				return;
			}
			// Product Visibility // version 1.3.0
			if ( isset($_POST['_wwp_wholesale_product_visibility']) ) {
				update_post_meta($post_id, '_wwp_wholesale_product_visibility', 'yes');
			} else {
				update_post_meta($post_id, '_wwp_wholesale_product_visibility', 'no');
			} // ends version 1.3.0
			// Vendor Enable
			$woo_wholesale_enable = isset($_POST['_wwp_enable_wholesale_item']) ? wc_clean($_POST['_wwp_enable_wholesale_item']) : '';        
			update_post_meta($post_id, '_wwp_enable_wholesale_item', esc_attr($woo_wholesale_enable));
			// Vendor Type
			$woo_wholesale_type = isset($_POST['_wwp_wholesale_type']) ? wc_clean($_POST['_wwp_wholesale_type']) : '';
			if ( !empty($woo_wholesale_type) ) {
				update_post_meta($post_id, '_wwp_wholesale_type', esc_attr($woo_wholesale_type));
			}
			// Vendor Amount
			$woo_wholesale_amount = isset($_POST['_wwp_wholesale_amount']) ? wc_clean($_POST['_wwp_wholesale_amount']) : '';
			if ( !empty($woo_wholesale_amount) ) {
				update_post_meta($post_id, '_wwp_wholesale_amount', esc_attr($woo_wholesale_amount));
			}
			// Vendor Minimum Quantity
			$wwp_wholesale_min_quantity = isset($_POST['_wwp_wholesale_min_quantity']) ? wc_clean($_POST['_wwp_wholesale_min_quantity']) : '';
			if ( !empty($wwp_wholesale_min_quantity) ) {
				update_post_meta($post_id, '_wwp_wholesale_min_quantity', esc_attr($wwp_wholesale_min_quantity));
			}
			
			//hide product for customer
			$_wwp_hide_for_customer = isset($_POST['_wwp_hide_for_customer']) ? wc_clean($_POST['_wwp_hide_for_customer']) : '';        
			update_post_meta($post_id, '_wwp_hide_for_customer', esc_attr($_wwp_hide_for_customer));
			
			//hide product for visitor
			$_wwp_hide_for_visitor = isset($_POST['_wwp_hide_for_visitor']) ? wc_clean($_POST['_wwp_hide_for_visitor']) : '';        
			update_post_meta($post_id, '_wwp_hide_for_visitor', esc_attr($_wwp_hide_for_visitor));
			
		}
		/**
		 * Product variations settings single user 
		 * 
		 * @since   1.0
		 * @version 1.0
		 */
		public function wwp_variation_settings_fields ( $loop, $variation_data, $variation ) {
			wp_nonce_field('wwp_variation_wholesale_nonce', 'wwp_variation_wholesale_nonce');
			woocommerce_wp_text_input(
				array(
					'id'          => '_wwp_wholesale_amount[' . esc_attr($variation->ID) . ']',
					'label'       => esc_html__('Enter Vendor Price', 'woocommerce-vendor-portal'),
					'desc_tip'    => 'true',
					'description' => esc_html__('Enter Vendor Price Here (e.g 15)', 'woocommerce-vendor-portal'),
					'value'       => get_post_meta($variation->ID, '_wwp_wholesale_amount', true),
					'custom_attributes' => array(
						'step'     => 'any',
						'min'    => '0'
					)
				)
			);
			woocommerce_wp_text_input(
				array(
					'id'          => '_wwp_wholesale_min_quantity[' . esc_attr($variation->ID) . ']',
					'label'       => esc_html__('Minimum Quantity', 'woocommerce-vendor-portal'),
					'placeholder' => '1',
					'value'       =>  get_post_meta($variation->ID, '_wwp_wholesale_min_quantity', true),
					'desc_tip'    => 'true',
					'description' => esc_html__('Minimum quantity to apply vendor price (default is 1)', 'woocommerce-vendor-portal'),
					'type'              => 'number',
					'custom_attributes' => array(
						'step'     => '1',
						'min'    => '1'
					)
				)
			);
		}
		/**
		 * Save product variations settings single user 
		 * 
		 * @since   1.0
		 * @version 1.0
		 */
		public function wwp_save_variation_settings_fields ( $post_id ) {
			if ( !isset($_POST['wwp_variation_wholesale_nonce']) || !wp_verify_nonce( wc_clean($_POST['wwp_variation_wholesale_nonce']), 'wwp_variation_wholesale_nonce') ) {
				return;
			}
			$variable_wholesale = isset( $_POST['_wwp_wholesale_amount'][ $post_id ] ) ? wc_clean($_POST['_wwp_wholesale_amount'][ $post_id ]) : '';
			if ( !empty($variable_wholesale) ) {
				update_post_meta($post_id, '_wwp_wholesale_amount', esc_attr($variable_wholesale));
			}
			$wholesale_min_quantity = isset($_POST['_wwp_wholesale_min_quantity'][ $post_id ]) ? wc_clean($_POST['_wwp_wholesale_min_quantity'][ $post_id ]) : '';
			if ( !empty($wholesale_min_quantity) ) {
				update_post_meta($post_id, '_wwp_wholesale_min_quantity', esc_attr($wholesale_min_quantity));
			}
		}
		public function wwp_screen_ids ( $screen_ids ) {
			$custom = array('edit-wholesale_user_roles');
			$screen_ids = array_merge($custom, $screen_ids);
			return $screen_ids;
		}
		public function wwp_admin_script_style() {
			
			wp_enqueue_script('wwp-script', WWP_PLUGIN_URL . 'assets/js/admin-script.js', array('jquery'), '1.0' );
			wp_localize_script(
				'wwp-script', 'wwpscript', array(
				'ajaxurl'		=>	admin_url('admin-ajax.php'),
				'admin_url'		=>	admin_url(),
				'ajax_nonce'	=>	wp_create_nonce('wwp_vendor_portal'),
				)
			);
			wp_enqueue_style('wwp-style', WWP_PLUGIN_URL . 'assets/css/admin-style.css', array(), '1.0' );
			wp_enqueue_style('fontawesome', WWP_PLUGIN_URL . 'assets/css/font-awesome.min.css', array(), '1.0' );
			
			if ( isset( $_GET['page'] ) && ( 'wwp_vendor' == sanitize_text_field( $_GET['page'] ) || 'wwp-registration-setting' == sanitize_text_field( $_GET['page'] ) ) ) {  
				wp_enqueue_style('wwp-data-tip', WWP_PLUGIN_URL . 'assets/css/data-tip.min.css', array(), '1.0' );
			}
		}
	}
	new WWP_Vendor_Portal_Backend();
}