<?php
if ( !defined('ABSPATH') ) {
	exit; // Exit if accessed directly
}
/**
 * Class to handle backend functionality
 */
if ( !class_exists('WVP_Vendor_Portal_Backend') ) {

	class WVP_Vendor_Portal_Backend {
		
		public function __construct() {
			add_action('admin_menu', array($this, 'wvp_register_custom_menu_page'));
			add_action('admin_enqueue_scripts', array($this, 'wvp_admin_script_style'));
			
			add_action('admin_head', array($this, 'wcpp_custom_style'));
        }
		
		public function wvp_register_custom_menu_page() {
			add_menu_page(
				esc_html__('Vendor Portal', 'woocommerce-vendor-portal'),
				esc_html__('Vendor Portal', 'woocommerce-vendor-portal'),
				'manage_vendor',
				'wvp_vendor',
				array($this, 'wvp_vendor_page_callback'),
				'dashicons-groups',
				59
			);
			add_submenu_page( 
				'wvp_vendor', 
				esc_html__('Vendor For WooCommerce', 'woocommerce-vendor-portal'), 
				esc_html__('Settings', 'woocommerce-vendor-portal'), 
				'manage_vendor_settings', 
				'wvp_vendor',
				array($this, 'wvp_vendor_page_callback')
			);
        }
        
		public function wvp_vendor_page_callback() {
			$settings= !empty(get_option('wvp_vendor_portal_options')) ? get_option('wvp_vendor_portal_options', true) : array();
			if ( isset($_POST['save-wvp_vendor']) ) {
				if ( isset($_POST['wvp_vendor_register_nonce']) || wp_verify_nonce( wc_clean($_POST['wvp_vendor_register_nonce']), 'wvp_vendor_register_nonce') ) {
					$settings = isset($_POST['options']) ? wc_clean($_POST['options']) : '';
					$settings['enable_registration_page'] = isset($settings['enable_registration_page']) ? 'yes' : 'no';
					$settings['contractor_allow_minimum_qty'] = isset($settings['contractor_allow_minimum_qty']) ? 'yes' : 'no';
					$settings['contractor_prodcut_only'] = isset($settings['contractor_prodcut_only']) ? 'yes' : 'no';
					$settings['enable_upgrade'] = isset($settings['enable_upgrade']) ? 'yes' : 'no';
					$settings['disable_auto_role'] = isset($settings['disable_auto_role']) ? 'yes' : 'no';
					$settings['retailer_disabled'] = isset($settings['retailer_disabled']) ? 'yes' : 'no';
					$settings['save_price_disabled'] = isset($settings['save_price_disabled']) ? 'yes' : 'no';
					update_option('wvp_vendor_portal_options', $settings);
					if (isset($settings['vendor_role']) && 'multiple' == $settings['vendor_role']) {
						$roles=get_terms(
						'vendor_user_roles', array(
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
								if ( isset($_POST['vendor_price_' . $role->term_id]) ) {
									$data[$role->term_id]['vendor_price'] = is_numeric( wc_clean($_POST['vendor_price_' . $role->term_id]) ) ? wc_clean($_POST['vendor_price_' . $role->term_id]) : '';
								}
								if ( isset($_POST['min_quatity_' . $role->term_id]) ) {
									$data[$role->term_id]['min_quatity'] = is_numeric( wc_clean($_POST['min_quatity_' . $role->term_id]) ) ? wc_clean($_POST['min_quatity_' . $role->term_id]) : 1;
								}
							}
						}
						update_option('vendor_multi_user_pricing', $data);
					} else {
						if ( isset($_POST['_wvp_enable_vendor_item']) ) {
							update_option('_wvp_enable_vendor_item', 'yes');
						} else {
							update_option('_wvp_enable_vendor_item', 'no');
						}
						if ( isset($_POST['_wvp_vendor_amount']) ) {
							update_option('_wvp_vendor_amount', wc_clean($_POST['_wvp_vendor_amount']) );
						} else {
							update_option('_wvp_vendor_amount', '');
						}
						if ( isset($_POST['_wvp_vendor_type']) ) {
							update_option('_wvp_vendor_type', wc_clean($_POST['_wvp_vendor_type']) );
						} else {
							update_option('_wvp_vendor_type', '');
						}
						if ( isset($_POST['_wvp_vendor_min_quantity']) ) {
							update_option('_wvp_vendor_min_quantity', wc_clean($_POST['_wvp_vendor_min_quantity']) );
						} else {
							update_option('_wvp_vendor_min_quantity', '');
						}
					}
				}
			} 
			?>
			
			<form action="" method="post">
				<h2><?php esc_html_e('Vendor For WooCommerce', 'woocommerce-vendor-portal'); ?></h2><hr>
				<?php wp_nonce_field('wvp_vendor_register_nonce', 'wvp_vendor_register_nonce'); ?>
				<table class="form-table wvp-main-settings">
					<tbody>
						<tr scope="row">
							<th><label for=""><?php esc_html_e('Vendor Portal Mode', 'woocommerce-vendor-portal'); ?></label></th>
							<td>
								<p>
									<label for="single_contractor_role">
										<input id="single_contractor_role" type="radio" value="single" name="options[vendor_role]" <?php echo ( isset($settings['vendor_role']) && 'single' == $settings['vendor_role'] ) ? 'checked' : ''; ?>>
										<?php esc_html_e(' Single Vendor Role', 'woocommerce-vendor-portal'); ?>
										<span data-tip="Default settings for single user role." class="data-tip-top"><span class="woocommerce-help-tip"></span></span>
										

									</label>
								</p>
								<p>
									<label for="multiple_contractor_role">
										<input id="multiple_contractor_role" type="radio" value="multiple" name="options[vendor_role]" <?php echo ( isset($settings['vendor_role']) && 'multiple' == $settings['vendor_role'] ) ? 'checked' : ''; ?>>
										<?php esc_html_e(' Multiple Vendor Roles', 'woocommerce-vendor-portal'); ?>
										<span data-tip="Manage prices according to multiple contractor user roles." class="data-tip-top"><span class="woocommerce-help-tip"></span></span>
									</label>
								</p>
							</td>
						</tr>
						
						<tr scope="row" id="multiroledropdown">
						
						<th><label for="default_multipe_vendor_roles"><?php esc_html_e('Default Multi Vendor Roles', 'woocommerce-vendor-portal'); ?></label></th>
							<td>
								<?php 
								$allterms = get_terms('vendor_user_roles', array('hide_empty' => false));
								?>
								<select id="default_multipe_vendor_roles" class="regular-text" name="options[default_multipe_vendor_roles]" >
									<option value=""><?php esc_html_e('Select Vendor Role', 'woocommerce-vendor-portal'); ?></option>
									<?php  
									foreach ( $allterms as $allterm ) {
										 $selected='';
										if ( isset($settings['default_multipe_vendor_roles']) && $settings['default_multipe_vendor_roles']== $allterm->slug ) {
												$selected='selected';
										}
										?>
										<option value="<?php echo esc_attr($allterm->slug); ?>" <?php echo esc_html($selected); ?>><?php echo esc_html($allterm->name); ?></option>
									<?php } ?> 
								</select>     
								<span data-tip="Define the default contractor role for your user." class="data-tip-top"><span class="woocommerce-help-tip"></span></span>
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
							<th><label for="registration_page_for_vendor"><?php esc_html_e('Registration Page', 'woocommerce-vendor-portal'); ?></label></th>
							<td>
								<?php 
								$args = array(
										'posts_per_page'   => -1,
										'post_type'        => 'page',
										'post_status'      => 'publish',
									);
								$pages = get_posts($args); 
								?>
								<select id="registration_page_for_vendor" class="regular-text" name="options[registration_page]" >
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
							<th><label for="registration_page_for_vendor"><?php esc_html_e('Registration Page Redirect', 'woocommerce-vendor-portal'); ?></label></th>
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
							<th><label for="contractor_price_label"><?php esc_html_e('Contractor Price Label', 'woocommerce-vendor-portal'); ?></label></th>
							<td><input type="text" class="regular-text" name="options[contractor_label]" id="contractor_price_label" value="<?php echo isset($settings['contractor_label']) ? esc_html($settings['contractor_label']) : ''; ?>">
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
						if ( in_array('woocommerce-subscriptions/woocommerce-subscriptions.php', apply_filters('active_plugins', get_option('active_plugins'))) && isset($settings['vendor_role']) ) {
							?>
							<tr scope="row">
								<th colspan="2"><h2><?php esc_html_e('Contractor Subscription', 'woocommerce-vendor-portal'); ?></h2></th>
							</tr>
							<tr scope="row">
								<th><label for=""><?php esc_html_e('Enable Vendor For Subscription', 'woocommerce-vendor-portal'); ?></label></th>
								<td>
									<label for="enable_subscription">
										<input id="enable_subscription" type="checkbox" value="yes" name="options[enable_subscription]" <?php echo ( isset($settings['enable_subscription']) && 'yes' == $settings['enable_subscription'] ) ? 'checked' : ''; ?>>
										<?php esc_html_e(' if checked Contractor role will be assigned on the respective vendor subscription.', 'woocommerce-vendor-portal'); ?>
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
									<label for="vendor_subscription">
										<select id="vendor_subscription" name="options[vendor_subscription]">
										<option disabled><?php esc_html_e('Select variable', 'woocommerce-vendor-portal'); ?></option> 
										<?php 
										if ( !empty($products) ) {
											foreach ( $products as $key => $product ) {
												$selected = ( isset($settings['vendor_subscription']) && $product->ID == $settings['vendor_subscription'] ) ? 'selected' : '';
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
								<label for="contractor_login_restriction">
									<input id="contractor_login_restriction" type="checkbox" value="yes" name="options[contractor_login_restriction]" <?php echo ( isset($settings['contractor_login_restriction']) && 'yes' == $settings['contractor_login_restriction'] ) ? 'checked' : ''; ?>>
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
								<label for="contractor_prodcut_only">
									<input id="contractor_prodcut_only" type="checkbox" value="yes" name="options[contractor_prodcut_only]" <?php echo ( isset($settings['contractor_prodcut_only']) && 'yes' == $settings['contractor_prodcut_only'] ) ? 'checked' : ''; ?>>
									<?php esc_html_e('By enabling this option vendor only products will be visible to contractor user roles only.', 'woocommerce-vendor-portal'); ?>
								</label>
							</td>
						</tr>
						<tr scope="row">
							<th><label for=""><?php esc_html_e('Restrict vendor products globally from non-contractor customer.', 'woocommerce-vendor-portal'); ?></label></th>
							<td>
								<label for="non_vendor_product_hide">
									<input id="non_vendor_product_hide" type="checkbox" value="yes" name="options[non_vendor_product_hide]" <?php echo ( isset($settings['non_vendor_product_hide']) && 'yes' == $settings['non_vendor_product_hide'] ) ? 'checked' : ''; ?>>
									<?php esc_html_e('Enable this option to hide vendor products from retailers and non-logged in user.', 'woocommerce-vendor-portal'); ?>
								</label>
							</td>
						</tr>
						<tr scope="row">
							<th><label for=""><?php esc_html_e('Enforce minimum quantity rules', 'woocommerce-vendor-portal'); ?></label></th>
							<td>
								<label for="contractor_allow_minimum_qty">
									<input id="contractor_allow_minimum_qty" type="checkbox" value="yes" name="options[contractor_allow_minimum_qty]" <?php echo ( isset($settings['contractor_allow_minimum_qty']) && 'yes' == $settings['contractor_allow_minimum_qty'] ) ? 'checked' : ''; ?>>
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
									<input type="text" class="regular-text" name="options[upgrade_tab_text]" id="upgrade_tab_text" value="<?php echo isset($settings['upgrade_tab_text']) ? esc_html($settings['upgrade_tab_text']) : ''; ?>" Placeholder="Label for Upgrade to Contractor tab">
								</label>
								<span data-tip='Display any text you want on the "Upgrade to Contractor" tab.' class="data-tip-top"><span class="woocommerce-help-tip"></span></span>
							</td>
						</tr>
						
						
						<tr scope="row">
							<th colspan="2"><h2><?php esc_html_e('Additional CSS', 'woocommerce-vendor-portal'); ?></h2></th>
						</tr>
						
						<?php 
						if ( empty($settings['vendor_css']) ) { 
							$settings['vendor_css'] = '/* Enter Your Custom CSS Here */';
						}
						?>
						
						<tr scope="row">
							<th><label for=""><?php esc_html_e('Registration Page CSS', 'woocommerce-vendor-portal'); ?></label></th>
							<td>
								<label for="code_editor_page_css">
									<textarea id="code_editor_page_css" rows="5" name="options[vendor_css]" class="widefat textarea"><?php echo wp_kses_post(wp_unslash( $settings['vendor_css'] )); ?></textarea> 
									<p class="wwvp_help_text"><?php esc_html_e('Enter css without <style> tag.', 'woocommerce-vendor-portal'); ?></p>									
								</label>
							</td>
						</tr>
						
						
					</tbody>            
				</table>
				<?php
				if (isset($settings['vendor_role']) && 'multiple' == $settings['vendor_role']) {
					$roles=get_terms(
						'vendor_user_roles', array(
							'hide_empty' => false,
						) 
					);
					if ( !empty($roles) ) { 
						$data = get_option( 'vendor_multi_user_pricing' );
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
									$price=$data[$role->term_id]['vendor_price'];
									$discount=$data[$role->term_id]['discount_type'];
								}
								?>
								<tr>
									<td>
										<span class="wvp-title"><?php esc_html_e($role->name); ?></span>
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
										<input class="widefat" type="text" name="vendor_price_<?php esc_attr_e($role->term_id); ?>" value="<?php esc_attr_e($price); ?>">
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
					$enable = get_option('_wvp_enable_vendor_item');
					$amount = get_option('_wvp_vendor_amount');
					$type = get_option('_wvp_vendor_type');
					$qty = get_option('_wvp_vendor_min_quantity');
					?>
					<h2><?php esc_html_e('Vendor Portal Global Option', 'woocommerce-vendor-portal'); ?></h2>
					<table class="form-table">
						<tr>
							<th>
								<label for="_wvp_enable_vendor_item">
									<?php esc_html_e('Enable Vendor Prices', 'woocommerce-vendor-portal'); ?>
								</label>
							</th>
							<td scope="row">
								<input type="checkbox" name="_wvp_enable_vendor_item" id="_wvp_enable_vendor_item" value="yes" <?php checked('yes', $enable); ?>>
								<span><?php esc_html_e('Enable vendor prices.', 'woocommerce-vendor-portal'); ?></span>
							</td>
						</tr>
						<tr>
							<th>
								<label for="_wvp_vendor_type">
									<?php esc_html_e('Vendor Discount Type', 'woocommerce-vendor-portal'); ?>
								</label>
							</th>
							<td scope="row">
								<select name="_wvp_vendor_type" id="_wvp_vendor_type" class="regular-text">
									<option value="fixed" <?php selected('fixed', $type); ?>><?php esc_html_e('Fixed Amount', 'woocommerce-vendor-portal'); ?></option>
									<option value="percent" <?php selected('percent', $type); ?>><?php esc_html_e('Percentage', 'woocommerce-vendor-portal'); ?></option>
								</select>
								<p><?php esc_html_e('Price type for vendor products.', 'woocommerce-vendor-portal'); ?></p>
							</td>
						</tr>
						<tr>
							<th>
								<label for="_wvp_vendor_amount">
									<?php esc_html_e('Enter Vendor Amount', 'woocommerce-vendor-portal'); ?>
								</label>
							</th>
							<td scope="row">
								<input type="text" name="_wvp_vendor_amount" id="_wvp_vendor_amount" value="<?php esc_attr_e($amount); ?>" class="regular-text">
								<p><?php esc_html_e('Enter vendor amount.', 'woocommerce-vendor-portal'); ?></p>
							</td>
						</tr>
						<tr>
							<th>
								<label for="_wvp_vendor_min_quantity">
									<?php esc_html_e('Minimum Quantity', 'woocommerce-vendor-portal'); ?>
								</label>
							</th>
							<td scope="row">
								<input type="number" name="_wvp_vendor_min_quantity" id="_wvp_vendor_min_quantity" value="<?php esc_attr_e($qty); ?>" class="regular-text">
								<p><?php esc_html_e('Enter vendor minimum quantity to apply discount.', 'woocommerce-vendor-portal'); ?></p>
							</td>
						</tr>
					</table>
					<?php
				}
				?>

				<p><button name="save-wvp_vendor" class="button-primary" type="submit" value="Save changes"><?php esc_html_e('Save changes', 'woocommerce-vendor-portal'); ?></button></p>
			</form>
			<?php
		}
		
		/**
		 * Initialize product vendor data tab
		 * 
		 * @since   1.0
		 * @version 1.0
		 */
		public function wvp_add_vendor_product_data_tab( $product_data_tabs ) {
			$product_data_tabs['wvp-vendor-tab'] = array(
				'label' => esc_html__('Vendor', 'woocommerce-vendor-portal'),
				'target' => 'wvp_vendor_product_data',
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
				.wvp-vendor-tab_tab a:before {
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
		public function wvp_add_vendor_product_data_fields_multi() { 
			// version 1.3.0
			global $post;
			$product_id = $post->ID;
			$roles = array();
			$taxroles = get_terms(
					'vendor_user_roles', array(
					'hide_empty' => false,
				)
			);
			if ( !empty($taxroles) ) {
				foreach ( $taxroles as $key => $role ) {
					$roles[$role->slug] = $role->name;
				}
			}
			?>
			<div id="wvp_vendor_product_data" class="panel woocommerce_options_panel">
			<?php
			wp_nonce_field('wvp_product_vendor_nonce', 'wvp_product_vendor_nonce');
						
			woocommerce_wp_checkbox(
				array(
					'id'            => '_wvp_hide_for_customer',
					'wrapper_class' => '_wvp_hide_for_customer',
					'label'         => esc_html__('Hide Product', 'woocommerce-vendor-portal'),
					'description'   => esc_html__('Hide this product from users having customer role', 'woocommerce-vendor-portal')
				)
			);

			woocommerce_wp_checkbox(
				array(
					'id'            => '_wvp_hide_for_visitor',
					'wrapper_class' => '_wvp_hide_for_visitor',
					'label'         => esc_html__('Hide Product', 'woocommerce-vendor-portal'),
					'description'   => esc_html__('Hide this product from visitors', 'woocommerce-vendor-portal')
				)
			);
			
			
			$value = get_post_meta($product_id, 'vendor_product_visibility_multi', true);
			woocommerce_wp_select(
				array(
				'id'				=> 'vendor_product_visibility_multi[]',
				'label'				=> esc_html__( 'Hide Product for Contractor Roles', 'woocommerce-vendor-portal' ),
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
				<div id="wvp_vendor_product_data" class="panel woocommerce_options_panel">
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
		public function wvp_add_vendor_product_data_fields() {
			global $woocommerce, $post, $product; 
			?>
			<!-- id below must match target registered in above wvp_add_vendor_product_data_tab function -->
			<div id="wvp_vendor_product_data" class="panel woocommerce_options_panel">
				<?php
				wp_nonce_field('wvp_product_vendor_nonce', 'wvp_product_vendor_nonce');

				woocommerce_wp_checkbox(
					array(
						'id'            => '_wvp_enable_vendor_item',
						'wrapper_class' => 'wvp_enable_vendor_item',
						'label'         => esc_html__('Enable Vendor Item', 'woocommerce-vendor-portal'),
						'description'   => esc_html__('Add this item for vendor customers', 'woocommerce-vendor-portal')
					)
				);
				woocommerce_wp_select(
					array(
						'id'      => '_wvp_vendor_type',
						'label'   => esc_html__('Vendor Type', 'woocommerce-vendor-portal'),
						'options' => array(
							'fixed'   => esc_html__('Fixed Amount', 'woocommerce-vendor-portal'),
							'percent' => esc_html__('Percent', 'woocommerce-vendor-portal'),
						)
					)
				);
				
				woocommerce_wp_checkbox(
					array(
						'id'            => '_wvp_hide_for_customer',
						'wrapper_class' => '_wvp_hide_for_customer',
						'label'         => esc_html__('Hide Product', 'woocommerce-vendor-portal'),
						'description'   => esc_html__('Hide this product from users having customer role', 'woocommerce-vendor-portal')
					)
				);
				
				woocommerce_wp_checkbox(
					array(
						'id'            => '_wvp_hide_for_visitor',
						'wrapper_class' => '_wvp_hide_for_visitor',
						'label'         => esc_html__('Hide Product', 'woocommerce-vendor-portal'),
						'description'   => esc_html__('Hide this product from visitors', 'woocommerce-vendor-portal')
					)
				);
				
				
				
				// version 1.3.0
				woocommerce_wp_checkbox(
					array(
						'id'            => '_wvp_vendor_product_visibility',
						'wrapper_class' => 'wvp_vendor_product_visibility',
						'label'         => esc_html__('Hide Product for Contractor Roles', 'woocommerce-vendor-portal'),
						'description'   => esc_html__('Hide this product for Vendor user', 'woocommerce-vendor-portal')
					)
				); // ends version 1.3.0
				echo '<div class="hide_if_variable">';
					woocommerce_wp_text_input(
						array(
							'id'          => '_wvp_vendor_amount',
							'label'       => esc_html__('Enter Vendor Amount', 'woocommerce-vendor-portal'),
							'placeholder' => get_woocommerce_currency_symbol() . '15',
							'desc_tip'    => 'true',
							'description' => esc_html__('Enter Vendor Price (e.g 15)', 'woocommerce-vendor-portal')
						)
					);
					woocommerce_wp_text_input(
						array(
							'id'          => '_wvp_vendor_min_quantity',
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
		public function wvp_woo_vendor_fields_save( $post_id ) {
			if ( !isset($_POST['wvp_product_vendor_nonce']) || !wp_verify_nonce( wc_clean($_POST['wvp_product_vendor_nonce']), 'wvp_product_vendor_nonce') ) {
				return;
			}
			// Product Visibility // version 1.3.0
			if ( isset($_POST['_wvp_vendor_product_visibility']) ) {
				update_post_meta($post_id, '_wvp_vendor_product_visibility', 'yes');
			} else {
				update_post_meta($post_id, '_wvp_vendor_product_visibility', 'no');
			} // ends version 1.3.0
			// Vendor Enable
			$woo_vendor_enable = isset($_POST['_wvp_enable_vendor_item']) ? wc_clean($_POST['_wvp_enable_vendor_item']) : '';        
			update_post_meta($post_id, '_wvp_enable_vendor_item', esc_attr($woo_vendor_enable));
			// Vendor Type
			$woo_vendor_type = isset($_POST['_wvp_vendor_type']) ? wc_clean($_POST['_wvp_vendor_type']) : '';
			if ( !empty($woo_vendor_type) ) {
				update_post_meta($post_id, '_wvp_vendor_type', esc_attr($woo_vendor_type));
			}
			// Vendor Amount
			$woo_vendor_amount = isset($_POST['_wvp_vendor_amount']) ? wc_clean($_POST['_wvp_vendor_amount']) : '';
			if ( !empty($woo_vendor_amount) ) {
				update_post_meta($post_id, '_wvp_vendor_amount', esc_attr($woo_vendor_amount));
			}
			// Vendor Minimum Quantity
			$wvp_vendor_min_quantity = isset($_POST['_wvp_vendor_min_quantity']) ? wc_clean($_POST['_wvp_vendor_min_quantity']) : '';
			if ( !empty($wvp_vendor_min_quantity) ) {
				update_post_meta($post_id, '_wvp_vendor_min_quantity', esc_attr($wvp_vendor_min_quantity));
			}
			
			//hide product for customer
			$_wvp_hide_for_customer = isset($_POST['_wvp_hide_for_customer']) ? wc_clean($_POST['_wvp_hide_for_customer']) : '';        
			update_post_meta($post_id, '_wvp_hide_for_customer', esc_attr($_wvp_hide_for_customer));
			
			//hide product for visitor
			$_wvp_hide_for_visitor = isset($_POST['_wvp_hide_for_visitor']) ? wc_clean($_POST['_wvp_hide_for_visitor']) : '';        
			update_post_meta($post_id, '_wvp_hide_for_visitor', esc_attr($_wvp_hide_for_visitor));
			
		}
		/**
		 * Product variations settings single user 
		 * 
		 * @since   1.0
		 * @version 1.0
		 */
		public function wvp_variation_settings_fields ( $loop, $variation_data, $variation ) {
			wp_nonce_field('wvp_variation_vendor_nonce', 'wvp_variation_vendor_nonce');
			woocommerce_wp_text_input(
				array(
					'id'          => '_wvp_vendor_amount[' . esc_attr($variation->ID) . ']',
					'label'       => esc_html__('Enter Vendor Price', 'woocommerce-vendor-portal'),
					'desc_tip'    => 'true',
					'description' => esc_html__('Enter Vendor Price Here (e.g 15)', 'woocommerce-vendor-portal'),
					'value'       => get_post_meta($variation->ID, '_wvp_vendor_amount', true),
					'custom_attributes' => array(
						'step'     => 'any',
						'min'    => '0'
					)
				)
			);
			woocommerce_wp_text_input(
				array(
					'id'          => '_wvp_vendor_min_quantity[' . esc_attr($variation->ID) . ']',
					'label'       => esc_html__('Minimum Quantity', 'woocommerce-vendor-portal'),
					'placeholder' => '1',
					'value'       =>  get_post_meta($variation->ID, '_wvp_vendor_min_quantity', true),
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
		public function wvp_save_variation_settings_fields ( $post_id ) {
			if ( !isset($_POST['wvp_variation_vendor_nonce']) || !wp_verify_nonce( wc_clean($_POST['wvp_variation_vendor_nonce']), 'wvp_variation_vendor_nonce') ) {
				return;
			}
			$variable_vendor = isset( $_POST['_wvp_vendor_amount'][ $post_id ] ) ? wc_clean($_POST['_wvp_vendor_amount'][ $post_id ]) : '';
			if ( !empty($variable_vendor) ) {
				update_post_meta($post_id, '_wvp_vendor_amount', esc_attr($variable_vendor));
			}
			$vendor_min_quantity = isset($_POST['_wvp_vendor_min_quantity'][ $post_id ]) ? wc_clean($_POST['_wvp_vendor_min_quantity'][ $post_id ]) : '';
			if ( !empty($vendor_min_quantity) ) {
				update_post_meta($post_id, '_wvp_vendor_min_quantity', esc_attr($vendor_min_quantity));
			}
        }
        
		public function wvp_admin_script_style() {
			
			wp_enqueue_script('wvp-script', WVP_PLUGIN_URL . 'assets/js/admin-script.js', array('jquery'), '1.0' );
			wp_localize_script(
				'wvp-script', 'wvpscript', array(
				'ajaxurl'		=>	admin_url('admin-ajax.php'),
				'admin_url'		=>	admin_url(),
				'ajax_nonce'	=>	wp_create_nonce('wvp_vendor_portal'),
				)
			);
			wp_enqueue_style('wvp-style', WVP_PLUGIN_URL . 'assets/css/admin-style.css', array(), '1.0' );
			wp_enqueue_style('fontawesome', WVP_PLUGIN_URL . 'assets/css/font-awesome.min.css', array(), '1.0' );
			
			if ( isset( $_GET['page'] ) && ( 'wvp_vendor' == sanitize_text_field( $_GET['page'] ) || 'wvp-registration-setting' == sanitize_text_field( $_GET['page'] ) ) ) {  
				wp_enqueue_style('wvp-data-tip', WVP_PLUGIN_URL . 'assets/css/data-tip.min.css', array(), '1.0' );
			}
		}
	}
	new WVP_Vendor_Portal_Backend();
}
