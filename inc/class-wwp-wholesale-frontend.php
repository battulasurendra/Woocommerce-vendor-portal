<?php
if ( !defined('ABSPATH') ) {
	exit; // Exit if accessed directly
}
if ( !class_exists('Wwp_Wholesale_Pricing_Frontend') ) {

	class Wwp_Wholesale_Pricing_Frontend {

		public $exclude_ids = array();
		
		public function __construct() {
			// add_action('woocommerce_account_content', array($this, 'wwp_account_content_callback'));
			add_action('wp_enqueue_scripts', array($this, 'wwp_script_style'));
			add_action( 'init', array($this, 'wwp_hide_price_add_cart_not_logged_in'));
			add_filter( 'authenticate', array($this, 'authenticate'), 100, 2);
			
			// Form Builder show on order page v 1.4
			add_action( 'woocommerce_after_order_notes', array($this, 'action_woocommerce_after_order_notes'), 10, 1 ); 
			add_action('woocommerce_checkout_create_order', array($this, 'before_checkout_create_order'), 20, 2);
			
			// Restrict products from non-wholesaler users. v 1.4
			add_action('pre_get_posts', array($this, 'default_wholesaler_products_only'), 10, 1);
			
			// Checkout formbuilder fileds validation v 1.4
			add_action( 'woocommerce_after_checkout_validation', array($this, 'wwp_after_checkout_validation' ), 10, 2);
			
			
			$settings = get_option('wwp_vendor_portal_options', true);
			
			if ( isset( $settings['enable_upgrade'] ) && 'yes' == $settings['enable_upgrade'] ) { 
				add_action( 'init', array($this, 'wwp_upgrade_add_rewrite' ));
				add_filter( 'query_vars', array($this, 'wwp_upgrade_add_var'), 10 );
				add_filter( 'woocommerce_account_menu_items', array($this, 'wwp_upgrade_add_menu_items' ));
				add_action( 'woocommerce_account_upgrade-account_endpoint', array($this, 'wwp_upgrade_content' ));
				add_action( 'wp_head', array($this, 'wwp_li_icons' ));
				add_filter( 'wp_kses_allowed_html', array($this, 'filter_wp_kses_allowed_html'), 10, 1 );
			}
		}
		 
		public function wwp_after_checkout_validation( $fields, $errors ) {
			
			if ( isset( $_POST['wwp_form_data_json'] ) && wp_verify_nonce( wc_clean( $_POST['wwp_form_data_json'] ), 'wwp_form_data_json' ) ) {
				return;
			}
			$formData = stripslashes( wwp_get_post_data( 'wwp_form_data_json' ) );
			$formData = json_decode( $formData );		
			if ( !empty( $formData ) ) {
				if ( is_array ( $formData  ) ) {
					foreach ( $formData as $formData_key => $formData_value ) {
						if ( isset( $formData_value->required ) && true == $formData_value->required ) {
							if ( empty( $_POST[$formData_value->name] ) ) {
								$form_error = __( '<strong> ' . $formData_value->label . ' </strong> is a required field.', 'woocommerce-wholesale-pricing' );
								$errors->add( 'validation', $form_error  );
							}
						}
					}
				}	 
			}	
		}
		
		public function before_checkout_create_order( $order, $data ) {
			
			// Form builder fields udate in user meta
			form_builder_update_user_meta( 	get_current_user_id() );
			$wwp_form_data_json = wwp_get_post_data('wwp_form_data_json');
			$wwp_form_data_json = isset( $wwp_form_data_json ) ? wc_clean($wwp_form_data_json) : '';
			$order->update_meta_data( 'wwp_form_data_json', stripslashes($wwp_form_data_json) );
		}
		
		public function action_woocommerce_after_order_notes( $wccs_custom_checkout_field_pro  ) { 
		
			$registrations = get_option('wwp_wholesale_registration_options');
			if ( isset($registrations['display_fields_checkout']) && 'yes' == $registrations['display_fields_checkout'] ) {
				echo  wp_kses_post( render_form_builder( 'get_option', '') );
			}
		
		}
		
		public function default_wholesaler_products_only( $q ) { 
		
			$settings=get_option('wwp_vendor_portal_options', true);
			$data = '';
			$non_wholesale_product_hide = ( isset($settings['non_wholesale_product_hide']) && 'yes' == $settings['non_wholesale_product_hide'] ) ? 'yes' : 'no';
			
			if ( $q->is_main_query() && !is_user_logged_in() && !current_user_can('administrator') && !is_wholesaler_user( get_current_user_id() )) {
				
				$all_ids = get_posts( array(
						'post_type' => 'product',
						'numberposts' => -1,
						'post_status' => 'publish',
						'meta_query' => array(
												array(
												'key'   => '_wwp_hide_for_visitor',
												'value' => 'yes',
												)
										),
						'fields' => 'ids',
					));
				if ( !empty ( $all_ids ) ) {
					$this->exclude_ids = array_merge($this->exclude_ids, $all_ids );
					$this->wwp_extra_product_remove( $this->exclude_ids  );		
					$q->set('post__not_in', $this->exclude_ids );
				}		

			}
		
		
			if ( $q->is_main_query() && is_user_logged_in() && !is_wholesaler_user( get_current_user_id() )) {
			 
				$user = wp_get_current_user();
			
				if ( in_array( 'customer', (array) $user->roles ) ) {
				
					$all_ids = get_posts( array(
						'post_type' => 'product',
						'numberposts' => -1,
						'post_status' => 'publish',
						'meta_query' => array(
												array(
												'key'   => '_wwp_hide_for_customer',
												'value' => 'yes',
												)
										),
						'fields' => 'ids',
					));
					if ( !empty ( $all_ids ) ) {
						$this->exclude_ids = array_merge($this->exclude_ids, $all_ids );
						$this->wwp_extra_product_remove( $this->exclude_ids  );		
						$q->set('post__not_in', $this->exclude_ids );
					}		
				}
			}
			
			if ( 'yes' == $non_wholesale_product_hide && $q->is_main_query() && is_wholesaler_user( get_current_user_id() ) == false && !current_user_can('administrator') ) {
				
				//Global price get
				if ( 'single' == $settings['wholesale_role'] ) {
					if ( 'yes' != get_option('_wwp_enable_wholesale_item') ) {
						$data = 'no';
					}
				} else {
					  
					if ( empty(get_option('wholesale_multi_user_pricing')) ) {
						$data = 'no'; 
					} else {
						$data = '';
					}
				}

				$all_ids = get_posts( array(
					'post_type' => 'product',
					'numberposts' => -1,
					'post_status' => 'publish',
					'fields' => 'ids',
				) );
						 
				if ( 'no' == $data ) {
					
					if ( 'multiple' == $settings['wholesale_role'] ) {
						$total_ids = multi_wholesale_product_ids();
					} else {  
						$total_ids = single_wholesale_product_ids();
					}
					if ( is_array( $total_ids ) ) { 
					
						$exclude_ids = array_diff( $all_ids, $total_ids );
						$this->exclude_ids = array_merge($this->exclude_ids, $total_ids );
						$q->set('post__not_in', $this->exclude_ids );
						$this->wwp_extra_product_remove( $this->exclude_ids  );
					} else {
						$this->exclude_ids = array_merge($this->exclude_ids, $all_ids );
						$q->set('post__not_in', $this->exclude_ids );
						$this->wwp_extra_product_remove( $this->exclude_ids  );
					}
				} else {
					
						$this->exclude_ids = array_merge($this->exclude_ids, $all_ids );
						$q->set('post__not_in', $this->exclude_ids );
						$this->wwp_extra_product_remove( $this->exclude_ids  );
						
				}
			}

		}
		
		public function wwp_extra_product_remove( $all_ids  ) {
			add_filter('woocommerce_related_products', array($this, 'exclude_related_products'), 10, 3 );
			add_filter( 'woocommerce_shortcode_products_query', array($this, 'woocommerce_shortcode_products_query'), 99, 3 ); 
		}
		
		public function woocommerce_shortcode_products_query( $query_args, $attributes, $type ) { 
			unset( $query_args['post__in'] );
			$query_args['post__not_in']  = $this->exclude_ids;  
			return  $query_args;
		}	
		
		public function exclude_related_products( $related_posts, $product_id, $args ) {  
			return array_diff( $related_posts, $this->exclude_ids );
		}
		
		public function authenticate( $user, $username ) { 
				
			if ( !is_wp_error($user) ) { 
				$settings=get_option('wwp_vendor_portal_options', true);
				$auth_user=get_user_by('id', $user->data->ID);
				$user_role = $auth_user->roles;
				if ( !empty($settings['wholesaler_login_restriction']) && 'yes' == $settings['wholesaler_login_restriction']) {
					if ( !empty($user_role) ) {
						foreach ( $user_role as $key => $role ) {
							$args = array(
							'post_type'              => array( 'wwp_requests' ),
							'order'                  => 'ASC',
							'orderby'                => 'id',
							'fields' => 'ids',
							'meta_query' => array(
								array(
									'key' => '_user_id',
									'value' => $user->data->ID ,
								)
							)
							);
							$post_id = get_posts( $args );
							if ( isset ($post_id[0]) ) {
								$user_status = get_post_meta( $post_id[0], '_user_status', true ); 
								if ( 'waiting' == $user_status ) {	
									if ( empty ($settings['login_message_waiting_user']) ) {
										$settings['login_message_waiting_user'] = 'You can not access this store, Your request status is in Pending';
									}
									return new WP_Error( 'authentication_failed', __($settings['login_message_waiting_user'], 'woocommerce-wholesale-pricing') );
								} elseif ( 'rejected' == $user_status ) {
									if ( empty ($settings['login_message_rejected_user']) ) {
										$settings['login_message_rejected_user'] = 'You can not access this store, Your request is Rejected by admin';
									}
									return new WP_Error( 'authentication_failed', __($settings['login_message_rejected_user'], 'woocommerce-wholesale-pricing') );
								}
							}
						}
					}
				}
			}
			return $user;
		}
		
		public function filter_wp_kses_allowed_html( $allowedposttags ) { 
			if (is_account_page()) {
				$allowed_atts = array(
					'align'      => array(),
					'class'      => array(),
					'type'       => array(),
					'id'         => array(),
					'dir'        => array(),
					'lang'       => array(),
					'style'      => array(),
					'xml:lang'   => array(),
					'src'        => array(),
					'alt'        => array(),
					'href'       => array(),
					'rel'        => array(),
					'rev'        => array(),
					'target'     => array(),
					'novalidate' => array(),
					'type'       => array(),
					'value'      => array(),
					'name'       => array(),
					'tabindex'   => array(),
					'action'     => array(),
					'method'     => array(),
					'for'        => array(),
					'width'      => array(),
					'height'     => array(),
					'data'       => array(),
					'title'      => array(),
					'value'      => array(),
					'selected'	=> array(),
					'enctype'	=> array(),
					'disable'	=> array(),
					'disabled'	=> array(),
				);
				$allowedposttags['form']	= $allowed_atts;
				$allowedposttags['label']	= $allowed_atts;
				$allowedposttags['select']	= $allowed_atts;
				$allowedposttags['option']	= $allowed_atts;
				$allowedposttags['input']	= $allowed_atts;
				$allowedposttags['textarea']	= $allowed_atts;
				$allowedposttags['iframe']	= $allowed_atts;
				$allowedposttags['script']	= $allowed_atts;
				$allowedposttags['style']	= $allowed_atts;
				$allowedposttags['strong']	= $allowed_atts;
				$allowedposttags['small']	= $allowed_atts;
				$allowedposttags['table']	= $allowed_atts;
				$allowedposttags['span']	= $allowed_atts;
				$allowedposttags['abbr']	= $allowed_atts;
				$allowedposttags['code']	= $allowed_atts;
				$allowedposttags['pre']	= $allowed_atts;
				$allowedposttags['div']	= $allowed_atts;
				$allowedposttags['img']	= $allowed_atts;
				$allowedposttags['h1']	= $allowed_atts;
				$allowedposttags['h2']	= $allowed_atts;
				$allowedposttags['h3']	= $allowed_atts;
				$allowedposttags['h4']	= $allowed_atts;
				$allowedposttags['h5']	= $allowed_atts;
				$allowedposttags['h6']	= $allowed_atts;
				$allowedposttags['ol']	= $allowed_atts;
				$allowedposttags['ul']	= $allowed_atts;
				$allowedposttags['li']	= $allowed_atts;
				$allowedposttags['em']	= $allowed_atts;
				$allowedposttags['hr']	= $allowed_atts;
				$allowedposttags['br']	= $allowed_atts;
				$allowedposttags['tr']	= $allowed_atts;
				$allowedposttags['td']	= $allowed_atts;
				$allowedposttags['p']	= $allowed_atts;
				$allowedposttags['a']	= $allowed_atts;
				$allowedposttags['b']	= $allowed_atts;
				$allowedposttags['i']	= $allowed_atts;
			}
			return $allowedposttags;
		}
		
		public function wwp_hide_price_add_cart_not_logged_in() {
			
			$settings = get_option('wwp_vendor_portal_options', true);
			if ( ! is_user_logged_in() && isset($settings['price_hide']) && 'yes' == $settings['price_hide'] ) {  

				remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
				remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 10 );
				remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
				remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );   
				add_action( 'woocommerce_single_product_summary', array($this, 'wwp_removeretail_prices'), 10 );
				add_action( 'woocommerce_after_shop_loop_item', array($this, 'wwp_removeretail_prices'), 11 );
				add_filter( 'woocommerce_get_price_html', array($this, 'wwp_woocommerce_get_price_html'), 10, 2);
				add_filter( 'woocommerce_is_purchasable', array($this, 'filter_woocommerce_is_purchasable'), 10, 2 ); 
			}
		}
		
		public function filter_woocommerce_is_purchasable( $this_exists_publish, $instance ) { 
			return false; 
		} 

		public function wwp_removeretail_prices() {
			$settings = get_option('wwp_vendor_portal_options', true);
			
			if (isset( $settings['display_link_text']) && !empty($settings['display_link_text']) ) {
				$link_text = $settings['display_link_text'];
			} else {
				$link_text = esc_html__('Login to see price', 'woocommerce-wholesale-pricing');
			}
			
			echo '<a class="login-to-upgrade" href="' . esc_url(get_permalink(wc_get_page_id('myaccount'))) . '">' . esc_html__($link_text, 'woocommerce-wholesale-pricing') . '</a>';
		}
		
		public function wwp_woocommerce_get_price_html( $price, $product ) {
			if ( ( 'object' == gettype($product) ) && $product->is_type('simple') ) {
				return $this->wwp_removeretail_prices();
			} else {
				return '';
			}
			
		}
		
		public function wwp_li_icons() {
			echo '<style>.woocommerce-MyAccount-navigation ul li.woocommerce-MyAccount-navigation-link--upgrade-account a::before {content: "\f1de";}</style>';	
		}
		
		public function wwp_upgrade_add_rewrite() {
			global $wp_rewrite;
			add_rewrite_endpoint( 'upgrade-account', EP_ROOT | EP_PAGES  );	
			$wp_rewrite->flush_rules();
		}
		
		public function wwp_upgrade_add_var( $vars ) {
			$vars[] = 'upgrade-account';
			return $vars;
		}
		
		public function wwp_upgrade_add_menu_items( $items ) {
			$settings = get_option('wwp_vendor_portal_options', true);
			 
			if (isset($settings['upgrade_tab_text']) && !empty($settings['upgrade_tab_text'])) {
				$items['upgrade-account'] = $settings['upgrade_tab_text'];
			} else {
				$items['upgrade-account'] = esc_html__('Upgrade Account', 'woocommerce-wholesale-pricing');	
			}
			
			return $items;
		}
		
		public function wwp_upgrade_content() {
			$this->wwp_account_content_callback();
		}
		
		public function wwp_account_content_callback () {
			if ( is_user_logged_in() ) {
				$settings = get_option('wwp_vendor_portal_options', true);
				 
					$user_id = get_current_user_id();
					$user_info = get_userdata($user_id);
					$user_role = $user_info->roles;
					$check='';
				if ( !empty($user_role) ) {
					foreach ( $user_role as $key => $role ) {
						if ( term_exists($role, 'wholesale_user_roles') ) {
							$check = 1;
							break;
						}
					}
				}
					
				if ( 'waiting' == get_user_meta($user_id, '_user_status', true) ) {
				
					$notice = apply_filters('wwp_pending_msg', __('Your request for upgrade account is pending.', 'woocommerce-wholesale-pricing'));
					wc_print_notice(esc_html__($notice, 'woocommerce-wholesale-pricing'), 'success');
					
				} elseif ( 'rejected' == get_user_meta($user_id, '_user_status', true) ) {
					
					if ( isset( $_POST['wwp_register_upgrade'] ) && !wp_verify_nonce( sanitize_text_field( $_POST['wwp_register_upgrade'] ), 'wwp_wholesale_registrattion_nonce' ) ) {
						$post =	$_POST;
					}
					
					if ( ! isset( $_POST['wwp_register_upgrade'] )  ) {
						wc_print_notice( __( 'Your upgrade request is rejected.', 'woocommerce-wholesale-pricing'), 'error' );
						$rejected_note=get_user_meta( get_current_user_id(), 'rejected_note', true );
						echo '<p class="rejected_note">' . esc_html__($rejected_note, 'woocommerce-wholesale-pricing') . '</p>';
					}
					
					if ( isset( $settings['request_again_submit'] ) && 'yes' == $settings['request_again_submit'] ) {
						
						$this->wwp_registration_insert($user_id, $check, $settings);
						if ( ! isset($_POST['wwp_register_upgrade']) ) {
							echo wp_kses_post($this->wwp_registration_form());
						}
						
					}
					
					
				} elseif ( 'active' == get_user_meta($user_id, '_user_status', true) ) {
					
					wc_print_notice( __('Your request is approved.', 'woocommerce-wholesale-pricing'), 'success');
					
				} elseif ( !term_exists(get_user_meta($user_id, 'wholesale_role_status', true), 'wholesale_user_roles') ) {
				
					$this->wwp_registration_insert($user_id, $check, $settings);
				
				}
			
				if ( get_user_meta($user_id, '_user_status', true) ) {
					$check = 1;
				}
				if ( empty( $check ) ) {
					global $wp;
					wc_print_notice( __('Apply here to upgrade your account.', 'woocommerce-wholesale-pricing'), 'notice' );
					echo wp_kses_post($this->wwp_registration_form());
				}
			}
		}
		
		public function wwp_registration_insert ( $user_id, $check, $settings ) {
		
			if ( isset($_POST['wwp_register_upgrade']) && !wp_verify_nonce( sanitize_text_field( $_POST['wwp_register_upgrade'] ), 'wwp_wholesale_registrattion_nonce' ) ) { 
					
				if (isset($_POST['g-recaptcha-response'])) {
					$notice_recaptcha = apply_filters('wwp_recaptcha_error_msg', esc_html__('Robot verification failed, please try again.', 'woocommerce-wholesale-pricing'));
					
					if (empty($_POST['g-recaptcha-response'])) {
						wc_print_notice(esc_html__($notice_recaptcha, 'woocommerce-wholesale-pricing'), 'error');
						return;
					}
					
					$secret = get_option( 'anr_admin_options')['secret_key'];
					$verifyResponse = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . $secret . '&response=' . wc_clean($_POST['g-recaptcha-response']));
					$responseData = json_decode($verifyResponse);
					if (!$responseData->success) {
						wc_print_notice(esc_html__($notice_recaptcha, 'woocommerce-wholesale-pricing'), 'error');
						return;
					}
				}	
				if ( !is_wp_error($user_id) ) {
					// Form builder fields udate in user meta
					form_builder_update_user_meta( $user_id );
					if ( isset($_POST['wwp_wholesaler_fname']) ) {
						$billing_first_name = wc_clean($_POST['wwp_wholesaler_fname']);
						update_user_meta($user_id, 'billing_first_name', $billing_first_name);
					}
					if ( isset($_POST['wwp_wholesaler_lname']) ) {
						$billing_last_name = wc_clean($_POST['wwp_wholesaler_lname']);
						update_user_meta($user_id, 'billing_last_name', $billing_last_name);
					}
					if ( isset($_POST['wwp_wholesaler_company']) ) {
						$billing_company = wc_clean($_POST['wwp_wholesaler_company']);
						update_user_meta($user_id, 'billing_company', $billing_company);
					}
					if ( isset($_POST['wwp_wholesaler_address_line_1']) ) {
						$billing_address_1 = wc_clean($_POST['wwp_wholesaler_address_line_1']);
						update_user_meta($user_id, 'billing_address_1', $billing_address_1);
					}
					if ( isset($_POST['wwp_wholesaler_address_line_2']) ) {
						$billing_address_2 = wc_clean($_POST['wwp_wholesaler_address_line_2']);
						update_user_meta($user_id, 'billing_address_2', $billing_address_2);
					}
					if ( isset($_POST['wwp_wholesaler_city']) ) {
						$billing_city = wc_clean($_POST['wwp_wholesaler_city']);
						update_user_meta($user_id, 'billing_city', $billing_city);
					}
					if ( isset($_POST['wwp_wholesaler_state']) ) {
						$billing_state = wc_clean($_POST['wwp_wholesaler_state']);
						update_user_meta($user_id, 'billing_state', $billing_state);
					}
					if ( isset($_POST['wwp_wholesaler_post_code']) ) {
						$billing_postcode = wc_clean($_POST['wwp_wholesaler_post_code']);
						update_user_meta($user_id, 'billing_postcode', $billing_postcode);
					}
					if ( isset($_POST['billing_country']) ) {
						$billing_country = wc_clean($_POST['billing_country']);
						update_user_meta($user_id, 'billing_country', $billing_country);
					}
					if ( isset($_POST['wwp_wholesaler_phone']) ) {
						$billing_phone = wc_clean($_POST['wwp_wholesaler_phone']);
						update_user_meta($user_id, 'billing_phone', $billing_phone);
					}
					if ( isset($_POST['wwp_wholesaler_tax_id']) ) {
						$wwp_wholesaler_tax_id = wc_clean($_POST['wwp_wholesaler_tax_id']);
						update_user_meta($user_id, 'wwp_wholesaler_tax_id', $wwp_wholesaler_tax_id);
					}
					if ( isset($_POST['wwp_custom_field_1']) ) {
						$custom_field = wc_clean($_POST['wwp_custom_field_1']);
						update_user_meta($user_id, 'wwp_custom_field_1', $custom_field);
					}
					if ( isset($_POST['wwp_custom_field_2']) ) {
						$custom_field = wc_clean($_POST['wwp_custom_field_2']);
						update_user_meta($user_id, 'wwp_custom_field_2', $custom_field);
					}
					if ( isset($_POST['wwp_custom_field_3']) ) {
						$custom_field = wc_clean($_POST['wwp_custom_field_3']);
						update_user_meta($user_id, 'wwp_custom_field_3', $custom_field);
					}
					if ( isset($_POST['wwp_custom_field_4']) ) {
						$custom_field = wc_clean($_POST['wwp_custom_field_4']);
						update_user_meta($user_id, 'wwp_custom_field_4', $custom_field);
					}
					if ( isset($_POST['wwp_custom_field_5']) ) {
						$custom_field = wc_clean($_POST['wwp_custom_field_5']);
						update_user_meta($user_id, 'wwp_custom_field_5', $custom_field);
					}
					if ( isset($_POST['wwp_form_data_json']) ) {
						$wwp_form_data_json = wc_clean($_POST['wwp_form_data_json']);
						update_user_meta($user_id, 'wwp_form_data_json', $wwp_form_data_json);
					}
					if ( !empty($_FILES['wwp_wholesaler_file_upload']) ) {
						require_once ABSPATH . 'wp-admin/includes/file.php';
						require_once ABSPATH . 'wp-admin/includes/image.php';
						require_once ABSPATH . 'wp-admin/includes/media.php';
						$attach_id = media_handle_upload( 'wwp_wholesaler_file_upload', $user_id );
						update_user_meta( $user_id, 'wwp_wholesaler_file_upload', $attach_id);
					}
					if ( isset($_POST['wwp_wholesaler_copy_billing_address']) ) {
						$wwp_wholesaler_copy_billing_address = wc_clean($_POST['wwp_wholesaler_copy_billing_address']);
					}
					if ( isset($wwp_wholesaler_copy_billing_address) ) {
						if ( isset($_POST['wwp_wholesaler_fname']) ) {
							$billing_first_name = wc_clean($_POST['wwp_wholesaler_fname']);
							update_user_meta($user_id, 'shipping_first_name', $billing_first_name);
						}
						if ( isset($_POST['wwp_wholesaler_lname']) ) {
							$billing_last_name = wc_clean($_POST['wwp_wholesaler_lname']);
							update_user_meta($user_id, 'shipping_last_name', $billing_last_name);
						}
						if ( isset($_POST['wwp_wholesaler_company']) ) {
							$billing_company = wc_clean($_POST['wwp_wholesaler_company']);
							update_user_meta($user_id, 'shipping_company', $billing_company);
						}
						if ( isset($_POST['wwp_wholesaler_address_line_1']) ) {
							$billing_address_1 = wc_clean($_POST['wwp_wholesaler_address_line_1']);
							update_user_meta($user_id, 'shipping_address_1', $billing_address_1);
						}
						if ( isset($_POST['wwp_wholesaler_address_line_2']) ) {
							$billing_address_2 = wc_clean($_POST['wwp_wholesaler_address_line_2']);
							update_user_meta($user_id, 'shipping_address_2', $billing_address_2);
						}
						if ( isset($_POST['wwp_wholesaler_city']) ) {
							$billing_city = wc_clean($_POST['wwp_wholesaler_city']);
							update_user_meta($user_id, 'shipping_city', $billing_city);
						}
						if ( isset($_POST['wwp_wholesaler_state']) ) {
							$billing_state = wc_clean($_POST['wwp_wholesaler_state']);
							update_user_meta($user_id, 'shipping_state', $billing_state);
						}
						if ( isset($_POST['wwp_wholesaler_post_code']) ) {
							$billing_postcode = wc_clean($_POST['wwp_wholesaler_post_code']);
							update_user_meta($user_id, 'shipping_postcode', $billing_postcode);
						}
						if ( isset($_POST['billing_country']) ) {
							$shipping_country = wc_clean($_POST['billing_country']);
							update_user_meta($user_id, 'shipping_country', $shipping_country);
						}
					} else {
						if ( isset($_POST['wwp_wholesaler_shipping_fname']) ) {
							$shipping_first_name = wc_clean($_POST['wwp_wholesaler_shipping_fname']);
							update_user_meta($user_id, 'shipping_first_name', $shipping_first_name);
						}
						if ( isset($_POST['wwp_wholesaler_shipping_lname']) ) {
							$shipping_last_name = wc_clean($_POST['wwp_wholesaler_shipping_lname']);
							update_user_meta($user_id, 'shipping_last_name', $shipping_last_name);
						}
						if ( isset($_POST['wwp_wholesaler_shipping_company']) ) {
							$shipping_company = wc_clean($_POST['wwp_wholesaler_shipping_company']);
							update_user_meta($user_id, 'shipping_company', $shipping_company);
						}
						if ( isset($_POST['wwp_wholesaler_shipping_address_line_1']) ) {
							$shipping_address_1 = wc_clean($_POST['wwp_wholesaler_shipping_address_line_1']);
							update_user_meta($user_id, 'shipping_address_1', $shipping_address_1);
						}
						if ( isset($_POST['wwp_wholesaler_shipping_address_line_2']) ) {
							$shipping_address_2 = wc_clean($_POST['wwp_wholesaler_shipping_address_line_2']);
							update_user_meta($user_id, 'shipping_address_2', $shipping_address_2);
						}
						if ( isset($_POST['wwp_wholesaler_shipping_city']) ) {
							$shipping_city = wc_clean($_POST['wwp_wholesaler_shipping_city']);
							update_user_meta($user_id, 'shipping_city', $shipping_city);
						}
						if ( isset($_POST['wwp_wholesaler_shipping_state']) ) {
							$shipping_state = wc_clean($_POST['wwp_wholesaler_shipping_state']);
							update_user_meta($user_id, 'shipping_state', $shipping_state);
						}
						if ( isset($_POST['wwp_wholesaler_shipping_post_code']) ) {
							$shipping_postcode = wc_clean($_POST['wwp_wholesaler_shipping_post_code']);
							update_user_meta($user_id, 'shipping_postcode', $shipping_postcode);
						}
						if ( isset($_POST['shipping_country']) ) {
							$shipping_country = wc_clean($_POST['shipping_country']);
							update_user_meta($user_id, 'shipping_country', $shipping_country);
						}
					}
					$id = wp_insert_post(
						array(
						'post_type'     => 'wwp_requests',
						'post_title'    => get_userdata(get_current_user_id())->data->user_nicename . ' - ' . get_current_user_id() . ' - Upgrade Request',
						'post_status'   => 'publish'
						)
					);
					if ( !is_wp_error($id) ) {
						
						update_post_meta($id, '_user_id', $user_id);
						if ( 'no' == $settings['disable_auto_role'] ) {
							update_post_meta($id, '_user_status', 'active');
							update_user_meta($user_id, '_user_status', 'active');
							
							if ( isset($settings['wholesale_role']) && 'single' == $settings['wholesale_role'] ) {
									 
								wp_set_object_terms($id, 'default_wholesaler', 'wholesale_user_roles', true);
								 
							} else {
								
								if ( isset($settings['default_multipe_wholesale_roles']) ) {
									
									wp_set_object_terms($id, $settings['default_multipe_wholesale_roles'], 'wholesale_user_roles', true);
								} else {
									wp_set_object_terms($id, 'default_wholesaler', 'wholesale_user_roles', true);
								}
								
							}

							if ( !empty($role) ) {
								do_action('wwp_wholesale_user_request_approved', $user_id);
								update_post_meta($id, '_approval_notification', 'sent');
							}
						} else {
							update_post_meta($id, '_user_status', 'waiting');
							update_user_meta($user_id, '_user_status', 'waiting');
						}
						do_action('wwp_wholesale_new_request_submitted', $user_id);
					}
					//On success
					if ( !is_wp_error($user_id) ) {
						$notice = apply_filters('wwp_success_msg', esc_html__('Your request for upgrade account is submitted.', 'woocommerce-wholesale-pricing'));
						wc_print_notice(esc_html__($notice, 'woocommerce-wholesale-pricing'), 'success');
					} else {
						$notice = apply_filters('wwp_error_msg', esc_html__($user_id->get_error_message(), 'woocommerce-wholesale-pricing'));
						wc_print_notice(esc_html__($notice, 'woocommerce-wholesale-pricing'), 'error');
					}
					wp_safe_redirect( wp_get_referer() );
				}
				$check = 1;
			}
		}
		
		public function wwp_registration_form() { 
			 
			global $woocommerce;
			$countries_obj = new WC_Countries();
			$countries = $countries_obj->__get('countries');
			 
			 
			$username = '';
			$email = '';
			$fname = '';
			$lname = '';
			$company = '';
			$addr1 = '';
			$settings=get_option('wwp_vendor_portal_options', true);
			$registrations = get_option('wwp_wholesale_registration_options'); 
			ob_start();
			if ( isset($settings['wholesale_css']) ) {
				?>
			<style type="text/css">
				<?php echo wp_kses_post($settings['wholesale_css']); ?>
			</style>
				<?php 
			} 
			?>
			<div class="wwp_wholesaler_registration">
			
				<form method="post" action="" enctype="multipart/form-data">
					<?php wp_nonce_field('wwp_wholesale_registrattion_nonce', 'wwp_wholesale_registrattion_nonce'); ?>
					<?php 
					if ( empty($registrations) || ( isset($registrations['custommer_billing_address']) && 'yes' == $registrations['custommer_billing_address'] ) ) { 
						?>
						<h2><?php esc_html_e('Customer billing address', 'woocommerce-wholesale-pricing'); ?></h2>
						<?php 
						if ( isset($registrations['enable_billing_first_name']) && 'yes' == $registrations['enable_billing_first_name'] ) { 
							?>
							<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
								<label for="wwp_wholesaler_fname"> <?php echo !empty($registrations['billing_first_name']) ? esc_html($registrations['billing_first_name']) : esc_html__('First Name', 'woocommerce-wholesale-pricing'); ?> <span class="required">*</span></label>
								<input type="text" name="wwp_wholesaler_fname" id="wwp_wholesaler_fname" value="<?php esc_attr_e($fname); ?>" required>
							</p>
							<?php
						}
						if ( isset($registrations['enable_billing_last_name']) && 'yes' == $registrations['enable_billing_last_name'] ) { 
							?>
							<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
								<label for="wwp_wholesaler_lname"><?php echo !empty($registrations['billing_last_name']) ? esc_html($registrations['billing_last_name']) : esc_html__('Last Name', 'woocommerce-wholesale-pricing'); ?> <span class="required">*</span></label>
								<input type="text" name="wwp_wholesaler_lname" id="wwp_wholesaler_lname" value="<?php esc_attr_e($lname); ?>" required>
							</p>
							<?php
						}
						if ( isset($registrations['enable_billing_company']) && 'yes' == $registrations['enable_billing_company'] ) { 
							?>
							<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
								<label for="wwp_wholesaler_fname"><?php echo !empty($registrations['billing_company']) ? esc_html($registrations['billing_company']) : esc_html__('Company', 'woocommerce-wholesale-pricing'); ?> <span class="required">*</span></label>
								<input type="text" name="wwp_wholesaler_company" id="wwp_wholesaler_company" value="<?php esc_attr_e($company); ?>"  required>
							</p>
							<?php
						}
						if ( isset($registrations['enable_billing_address_1']) && 'yes' == $registrations['enable_billing_address_1'] ) { 
							?>
							<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
								<label for="wwp_wholesaler_address_line_1"><?php echo !empty($registrations['billing_address_1']) ? esc_html($registrations['billing_address_1']) : esc_html__('Address line 1', 'woocommerce-wholesale-pricing'); ?> <span class="required">*</span></label>
								<input type="text" name="wwp_wholesaler_address_line_1" id="wwp_wholesaler_address_line_1" value="<?php esc_attr_e($addr1); ?>" required>
							</p>
							<?php
						}
						if ( isset($registrations['enable_billing_address_2']) && 'yes' == $registrations['enable_billing_address_2'] ) { 
							?>
							<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
								<label for="wwp_wholesaler_address_line_2"><?php echo !empty($registrations['billing_address_2']) ? esc_html($registrations['billing_address_2']) : esc_html__('Address line 2', 'woocommerce-wholesale-pricing'); ?> <span class="required">*</span></label>
								<input type="text" name="wwp_wholesaler_address_line_2" id="wwp_wholesaler_address_line_2">
							</p>
							<?php
						}
						if ( isset($registrations['enable_billing_city']) && 'yes' == $registrations['enable_billing_city'] ) { 
							?>
							<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
								<label for="wwp_wholesaler_city"><?php echo !empty($registrations['billing_city']) ? esc_html($registrations['billing_city']) : esc_html__('City', 'woocommerce-wholesale-pricing'); ?><span class="required">*</span></label>
								<input type="text" name="wwp_wholesaler_city" id="wwp_wholesaler_city" required>
							</p>
							<?php
						}
						if ( isset($registrations['enable_billing_post_code']) && 'yes' == $registrations['enable_billing_post_code'] ) { 
							?>
							<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
								<label for="wwp_wholesaler_post_code"><?php echo !empty($registrations['billing_post_code']) ? esc_html($registrations['billing_post_code']) : esc_html__('Postcode / ZIP', 'woocommerce-wholesale-pricing'); ?> <span class="required">*</span></label>
								<input type="text" name="wwp_wholesaler_post_code" id="wwp_wholesaler_post_code" required>
							</p>
							<?php
						}
						if ( isset($registrations['enable_billing_country']) && 'yes' == $registrations['enable_billing_country'] ) { 
							?>
							<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
							<?php
								woocommerce_form_field(
									'billing_country', array(
									'type'       => 'select',
									'class'      => array( 'chzn-drop' ),
									'label'      => esc_html__('Select billing country', 'woocommerce-wholesale-pricing'),
									'placeholder'=> esc_html__('Enter something', 'woocommerce-wholesale-pricing'),
									'options'    => $countries
									)
								);
							?>
							</p>
							<?php
						}
						if ( isset($registrations['enable_billing_state']) && 'yes' == $registrations['enable_billing_state'] ) { 
							?>
							<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
								<label for="wwp_wholesaler_state"><?php echo !empty($registrations['billing_state']) ? esc_html($registrations['billing_state']) : esc_html__('State / County', 'woocommerce-wholesale-pricing'); ?> <span class="required">*</span></label>
								<input type="text" name="wwp_wholesaler_state" id="wwp_wholesaler_state" required>
								<span for="wwp_wholesaler_state"><?php esc_html_e('State / County or state code', 'woocommerce-wholesale-pricing'); ?></span>
							</p>
							<?php
						}
						if ( isset($registrations['enable_billing_phone']) && 'yes' == $registrations['enable_billing_phone'] ) { 
							?>
							<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
								<label for="wwp_wholesaler_phone"><?php echo !empty($registrations['billing_phone']) ? esc_html($registrations['billing_phone']) : esc_html__('Phone', 'woocommerce-wholesale-pricing'); ?> <span class="required">*</span></label>
								<input type="text" name="wwp_wholesaler_phone" id="wwp_wholesaler_phone" required>
							</p>
							<?php
						}
					}
					if ( empty($registrations) || ( isset($registrations['custommer_shipping_address']) && 'yes' == $registrations['custommer_shipping_address'] ) ) { 
						?>
						<h2><?php esc_html_e('Customer shipping address', 'woocommerce-wholesale-pricing'); ?></h2>
						<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
							<label for="wwp_wholesaler_copy_billing_address"><?php esc_html_e('Copy from billing address', 'woocommerce-wholesale-pricing'); ?></label>
							<input type="checkbox" name="wwp_wholesaler_copy_billing_address" id="wwp_wholesaler_copy_billing_address" value="yes" >
						</p>
						<div id="wholesaler_shipping_address"> 
							<?php 
							if ( isset($registrations['enable_shipping_first_name']) && 'yes' == $registrations['enable_shipping_first_name'] ) { 
								?>
								<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
									<label for="wwp_wholesaler_shipping_lname"><?php echo !empty($registrations['shipping_first_name']) ? esc_html($registrations['shipping_first_name']) : esc_html__('First Name', 'woocommerce-wholesale-pricing'); ?> <span class="required">*</span></label>
									<input type="text" name="wwp_wholesaler_shipping_fname" id="wwp_wholesaler_shipping_fname" >
								</p>
								<?php
							}
							if ( isset($registrations['enable_shipping_last_name']) && 'yes' == $registrations['enable_shipping_last_name'] ) { 
								?>
								<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
									<label for="wwp_wholesaler_shipping_fname"> <?php echo !empty($registrations['shipping_last_name']) ? esc_html($registrations['shipping_last_name']) : esc_html__('Last Name', 'woocommerce-wholesale-pricing'); ?> <span class="required">*</span> </label>
									<input type="text" name="wwp_wholesaler_shipping_lname" id="wwp_wholesaler_shipping_lname" >
								</p>
								<?php
							}
							if ( isset($registrations['enable_shipping_company']) && 'yes' == $registrations['enable_shipping_company'] ) { 
								?>
								<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
									<label for="wwp_wholesaler_shipping_company"><?php echo !empty($registrations['shipping_company']) ? esc_html($registrations['shipping_company']) : esc_html__('Company', 'woocommerce-wholesale-pricing'); ?> <span class="required">*</span></label>
									<input type="text" name="wwp_wholesaler_shipping_company" id="wwp_wholesaler_shipping_company" >
								</p>
								<?php
							}
							if ( isset($registrations['enable_shipping_address_1']) && 'yes' == $registrations['enable_shipping_address_1'] ) { 
								?>
								<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
									<label for="wwp_wholesaler_shipping_address_line_1"><?php echo !empty($registrations['shipping_address_1']) ? esc_html($registrations['shipping_address_1']) : esc_html__('Address line 1', 'woocommerce-wholesale-pricing'); ?> <span class="required">*</span></label>
									<input type="text" name="wwp_wholesaler_shipping_address_line_1" id="wwp_wholesaler_shipping_address_line_1" >
								</p>
								<?php
							}
							if ( isset($registrations['enable_shipping_address_2']) && 'yes' == $registrations['enable_shipping_address_2'] ) { 
								?>
								<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
									<label for="wwp_wholesaler_shipping_address_line_2"><?php echo !empty($registrations['shipping_address_2']) ? esc_html($registrations['shipping_address_2']) : esc_html__('Address line 2', 'woocommerce-wholesale-pricing'); ?> <span class="required">*</span></label>
									<input type="text" name="wwp_wholesaler_shipping_address_line_2" id="wwp_wholesaler_shipping_address_line_2" >
								</p>
								<?php 
							}
							if ( isset($registrations['enable_shipping_city']) && 'yes' == $registrations['enable_shipping_city'] ) { 
								?>
								<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
									<label for="wwp_wholesaler_shipping_city"><?php echo !empty($registrations['shipping_city']) ? esc_html($registrations['shipping_city']) : esc_html__('City', 'woocommerce-wholesale-pricing'); ?> <span class="required">*</span></label>
									<input type="text" name="wwp_wholesaler_shipping_city" id="wwp_wholesaler_shipping_city" >
								</p>
								<?php
							}
							if ( isset($registrations['enable_shipping_post_code']) && 'yes' == $registrations['enable_shipping_post_code'] ) { 
								?>
								<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
									<label for="wwp_wholesaler_shipping_post_code"><?php echo !empty($registrations['shipping_post_code']) ? esc_html($registrations['shipping_post_code']) : esc_html__('Postcode / ZIP', 'woocommerce-wholesale-pricing'); ?> <span class="required">*</span></label>
									<input type="text" name="wwp_wholesaler_shipping_post_code" id="wwp_wholesaler_shipping_post_code">
								</p>
								<?php
							}
							if ( isset($registrations['enable_shipping_country']) && 'yes' == $registrations['enable_shipping_country'] ) { 
								?>
								<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
								<?php
									woocommerce_form_field( 'shipping_country', 
									array(
										'type'       => 'select',
										'class'      => array('chzn-drop'),
										'label'      => esc_html__('Select shipping country', 'woocommerce-wholesale-pricing'),
										'placeholder'=> esc_html__('Enter something', 'woocommerce-wholesale-pricing'),
										'options'    => $countries
										)
									);
								?>
								</p>
								<?php
							}
							if ( isset($registrations['enable_shipping_state']) && 'yes' == $registrations['enable_shipping_state'] ) { 
								?>
								<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
									<label for="wwp_wholesaler_shipping_state"><?php echo !empty($registrations['shipping_state']) ? esc_html($registrations['shipping_state']) : esc_html__('State / County', 'woocommerce-wholesale-pricing'); ?> <span class="required">*</span></label>
									<input type="text" name="wwp_wholesaler_shipping_state" id="wwp_wholesaler_shipping_state">
								</p>
								<?php
							} 
							?>
						</div>
						<?php
					}
					if ( isset($registrations['enable_tex_id']) && 'yes' == $registrations['enable_tex_id'] ) { 
						$required = ( !empty($registrations['required_tex_id']) && 'yes' == $registrations['required_tex_id'] ) ? 'required' : '';
						?>
						<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
							<label for="wwp_wholesaler_tax_id">
								<?php echo !empty($registrations['woo_tax_id']) ? esc_html($registrations['woo_tax_id']) : esc_html__('Tax ID', 'woocommerce-wholesale-pricing'); ?>
								<?php
								if ( 'required' == $required ) {
									echo '<span class="required">*</span>';
								}
								?>
							</label>
							<input type="text" name="wwp_wholesaler_tax_id" id="wwp_wholesaler_tax_id" <?php esc_attr_e($required); ?>>
						</p>
						<?php
					}
					if ( isset($registrations['enable_file_upload']) && 'yes' == $registrations['enable_file_upload'] ) { 
						$required = ( !empty($registrations['required_file_upload']) && 'yes' == $registrations['required_file_upload'] ) ? 'required' : '';
						?>
						<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
							<label for="wwp_wholesaler_file_upload"><?php echo !empty($registrations['woo_file_upload']) ? esc_html($registrations['woo_file_upload']) : esc_html__('File Upload', 'woocommerce-wholesale-pricing'); ?>
							<?php
							if ( 'required' == $required ) { 
								echo '<span class="required">*</span>';
							} 
							?>
							</label>
							<input type="file" name="wwp_wholesaler_file_upload" id="wwp_wholesaler_file_upload" <?php esc_attr_e($required); ?> value="">
						</p>
						<?php
					}
					if ( isset($registrations['display_fields_myaccount']) && 'yes' == $registrations['display_fields_myaccount'] ) {
						echo wp_kses_post( render_form_builder ( 'get_option' , get_current_user_id() ) );
					}
					
					?>
					<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">                  
					<?php
					if ( isset(get_option( 'anr_admin_options')['enabled_forms']) ) {
						if (in_array('wwp_wholesale_recaptcha', get_option( 'anr_admin_options')['enabled_forms'])) {
							do_action( 'anr_captcha_form_field' );
						}
					}
					?>
					</p>
					<p class="woocomerce-FormRow form-row">                   
						<input type="submit" class="woocommerce-Button button" name="wwp_register_upgrade" value="<?php esc_html_e('Register', 'woocommerce-wholesale-pricing'); ?>">
					</p>
				</form>
			</div>
			<?php 
			return ob_get_clean();
		}
		 
		 
		public function wwp_script_style() {
			wp_enqueue_script( 'wwp-script', WWP_PLUGIN_URL . 'assets/js/script.js', array(), '1.0.0', true );
			wp_enqueue_style( 'wwp-wholesale', WWP_PLUGIN_URL . 'assets/css/wwp-css-script.css', array(), '1.1.0', false );
		}
	}
	new Wwp_Wholesale_Pricing_Frontend();
}
