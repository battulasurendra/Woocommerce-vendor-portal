<?php
if ( !defined('ABSPATH') ) {
	exit; // Exit if accessed directly
}
if ( !class_exists('Avp_Vendor_Portal_Upgrade') ) {

	class Avp_Vendor_Portal_Upgrade {

		public $exclude_ids = array();
		
		public function __construct() {			
			add_action( 'init', array($this, 'avp_upgrade_add_rewrite' ));
            add_filter( 'query_vars', array($this, 'avp_upgrade_add_var'), 10 );
            add_filter( 'woocommerce_account_menu_items', array($this, 'avp_upgrade_add_menu_items' ));
            add_action( 'woocommerce_account_upgrade-account_endpoint', array($this, 'avp_upgrade_content' ));
            add_action( 'wp_head', array($this, 'wwp_li_icons' ));
            add_filter( 'wp_kses_allowed_html', array($this, 'filter_wp_kses_allowed_html'), 10, 1 );
		}
		
		public function avp_upgrade_add_rewrite() {
			global $wp_rewrite;
			add_rewrite_endpoint( 'upgrade-account', EP_ROOT | EP_PAGES  );	
			$wp_rewrite->flush_rules();
		}
		
		public function avp_upgrade_add_var( $vars ) {
			$vars[] = 'upgrade-account';
			return $vars;
		}
		
		public function avp_upgrade_add_menu_items( $items ) {
            $items['upgrade-account'] = esc_html__('Apply as contractor', 'woocommerce-vendor-portal');			
			return $items;
		}
		
		public function avp_upgrade_content() {
			$this->avp_account_content_callback();
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
		
		public function wwp_li_icons() {
			echo '<style>.woocommerce-MyAccount-navigation ul li.woocommerce-MyAccount-navigation-link--upgrade-account a::before {content: "\f1de";}</style>';	
		}
		
		public function avp_account_content_callback () {
			if ( is_user_logged_in() ) {				 
                $user_id = get_current_user_id();
                $user_info = get_userdata($user_id);
                $user_role = $user_info->roles;
                $check='';

				if ( !empty($user_role) ) {
					foreach ( $user_role as $key => $role ) {
						if ( term_exists($role, 'vendor_user_roles') ) {
							$check = 1;
							break;
						}
					}
				}
					
				if ( 'waiting' == get_user_meta($user_id, '_user_status', true) ) {
				
					$notice = apply_filters('avp_pending_msg', __('Your request for vendor is pending.', 'woocommerce-vendor-portal'));
					wc_print_notice(esc_html__($notice, 'woocommerce-vendor-portal'), 'success');
					
				} elseif ( 'rejected' == get_user_meta($user_id, '_user_status', true) ) {
					
					if ( ! isset( $_POST['avp_register_upgrade'] )  ) {
						wc_print_notice( __( 'Your upgrade request is rejected.', 'woocommerce-vendor-portal'), 'error' );
                        $rejected_note = get_user_meta( get_current_user_id(), 'rejected_note', true );
                        wc_print_notice( __( $rejected_note, 'woocommerce-vendor-portal'), 'error' );
					}
						
                    $this->avp_registration_insert($user_id);
                    if ( ! isset($_POST['avp_register_upgrade']) ) {
                        echo wp_kses_post($this->avp_vendor_registration_form());
                    }
					
					
				} elseif ( 'active' == get_user_meta($user_id, '_user_status', true) ) {
					
					wc_print_notice( __('Your account is upgraded to contractor.', 'woocommerce-vendor-portal'), 'success');
					
				} elseif ( !term_exists(get_user_meta($user_id, 'vendor_role_status', true), 'vendor_user_roles') ) {
				
					$this->avp_registration_insert($user_id);
				
				}
			
				if ( get_user_meta($user_id, '_user_status', true) ) {
					$check = 1;
                }
                
				if ( empty( $check ) ) {
					global $wp;
					// wc_print_notice( __('Apply here to upgrade your account.', 'woocommerce-vendor-portal'), 'notice' );
					echo wp_kses_post($this->avp_vendor_registration_form());
				}
			}
		}
		
		public function avp_registration_insert ( $user_id ) {
		
			if ( isset($_POST['avp_register_upgrade']) && !wp_verify_nonce( sanitize_text_field( $_POST['avp_register_upgrade'] ), 'avp_vendor_registrattion_nonce' ) ) { 
					
				if ( !is_wp_error($user_id) ) {
                    
					if (isset($_POST['first_name'])) {
                        $billing_first_name = wc_clean($_POST['first_name']);
                        update_user_meta($user_id, 'billing_first_name', $billing_first_name);
                    }
                    if (isset($_POST['last_name'])) {
                        $billing_last_name = wc_clean($_POST['last_name']);
                        update_user_meta($user_id, 'billing_last_name', $billing_last_name);
                    }
                    if (isset($_POST['billing_company'])) {
                        $billing_company = wc_clean($_POST['billing_company']);
                        update_user_meta($user_id, 'billing_company', $billing_company);
                    }
                    if (isset($_POST['billing_phone'])) {
                        $billing_phone = wc_clean($_POST['billing_phone']);
                        update_user_meta($user_id, 'billing_phone', $billing_phone);
                    }
                    if (isset($_POST['billing_address_1'])) {
                        $billing_address_1 = wc_clean($_POST['billing_address_1']);
                        update_user_meta($user_id, 'billing_address_1', $billing_address_1);
                    }
                    if (isset($_POST['billing_address_2'])) {
                        $billing_address_2 = wc_clean($_POST['billing_address_2']);
                        update_user_meta($user_id, 'billing_address_2', $billing_address_2);
                    }
                    if (isset($_POST['billing_city'])) {
                        $billing_city = wc_clean($_POST['billing_city']);
                        update_user_meta($user_id, 'billing_city', $billing_city);
                    }
                    if (isset($_POST['billing_country'])) {
                        $billing_country = wc_clean($_POST['billing_country']);
                        update_user_meta($user_id, 'billing_country', $billing_country);
                    }
                    if (isset($_POST['billing_state'])) {
                        $billing_state = wc_clean($_POST['billing_state']);
                        update_user_meta($user_id, 'billing_state', $billing_state);
                    }
                    if (isset($_POST['billing_postcode'])) {
                        $billing_postcode = wc_clean($_POST['billing_postcode']);
                        update_user_meta($user_id, 'billing_postcode', $billing_postcode);
                    }
                    if (isset($_POST['business_years'])) {
                        $custom_field = wc_clean($_POST['business_years']);
                        update_user_meta($user_id, 'business_years', $custom_field);
                    }
                    if (isset($_POST['employees_count'])) {
                        $custom_field = wc_clean($_POST['employees_count']);
                        update_user_meta($user_id, 'employees_count', $custom_field);
                    }
                    if (isset($_POST['business_website'])) {
                        $custom_field = wc_clean($_POST['business_website']);
                        update_user_meta($user_id, 'business_website', $custom_field);
                    }
                    
					$id = wp_insert_post(
						array(
						'post_type'     => 'avp_requests',
						'post_title'    => get_userdata(get_current_user_id())->data->user_nicename . ' - ' . get_current_user_id() . ' - Upgrade Request',
						'post_status'   => 'publish'
						)
                    );
                    
					if ( !is_wp_error($id) ) {
                        update_post_meta($id, '_user_id', $user_id);
                        update_post_meta($id, '_user_status', 'active');
                        update_user_meta($user_id, '_user_status', 'active');

                        $u = new WP_User($user_id);
                        $u->add_role('contractor');
    
                        wp_set_object_terms($id, 'contractor', 'vendor_user_roles', true);
                        do_action('avp_vendor_user_request_approved', $user_id);
                        do_action('avp_vendor_new_request_submitted', $user_id);
                        update_post_meta($id, '_approval_notification', 'sent');
                    }
                    
					//On success
					if ( !is_wp_error($user_id) ) {
                        wp_redirect( wc_get_page_permalink('my-account') );
                        $notice = apply_filters('avp_success_msg', esc_html__('Your account is upgraded as contractor.', 'woocommerce-vendor-portal'));
						wc_print_notice(esc_html__($notice, 'woocommerce-vendor-portal'), 'success');
					} else {
						$notice = apply_filters('avp_error_msg', esc_html__($user_id->get_error_message(), 'woocommerce-vendor-portal'));
						wc_print_notice(esc_html__($notice, 'woocommerce-vendor-portal'), 'error');
                    }
                    
					wp_safe_redirect( wp_get_referer() );
				}
			}
		}
		
		public function avp_vendor_registration_form() { 
			 
            global $woocommerce;
            
				 
            $user_id = get_current_user_id();
            $user_meta = get_user_meta($user_id);

            $fname              = isset($user_meta['first_name'][0]) ? wc_clean($user_meta['first_name'][0]) : '';
            $lname              = isset($user_meta['last_name'][0]) ? wc_clean($user_meta['last_name'][0]) : '';
            $company            = isset($user_meta['billing_company'][0]) ? wc_clean($user_meta['billing_company'][0]) : '';
            $billing_phone      = isset($user_meta['billing_phone'][0]) ? wc_clean($user_meta['billing_phone'][0]) : '';
            $addr1              = isset($user_meta['billing_address_1'][0]) ? wc_clean($user_meta['billing_address_1'][0]) : '';
            $billing_address_2  = isset($user_meta['billing_address_2'][0]) ? wc_clean($user_meta['billing_address_2'][0]) : '';
            $billing_city       = isset($user_meta['billing_city'][0]) ? wc_clean($user_meta['billing_city'][0]) : '';
            $billing_country    = isset($user_meta['billing_country'][0]) ? wc_clean($user_meta['billing_country'][0]) : wc_get_post_data_by_key('billing_country');
            $billing_state      = isset($user_meta['billing_state'][0]) ? wc_clean($user_meta['billing_state'][0]) : '';
            $billing_postcode   = isset($user_meta['billing_postcode'][0]) ? wc_clean($user_meta['billing_postcode'][0]) : '';
            $employees_count    = isset($user_meta['employees_count'][0]) ? wc_clean($user_meta['employees_count'][0]) : '';
            $business_years     = isset($user_meta['business_years'][0]) ? wc_clean($user_meta['business_years'][0]) : '';
            $business_website   = isset($user_meta['business_website'][0]) ? wc_clean($user_meta['business_website'][0]) : '';
            $employees_count    = isset($user_meta['employees_count'][0]) ? wc_clean($user_meta['employees_count'][0]) : '';
            $business_years     = isset($user_meta['business_years'][0]) ? wc_clean($user_meta['business_years'][0]) : '';
            $business_website   = isset($user_meta['business_website'][0]) ? wc_clean($user_meta['business_website'][0]) : '';
             
            $fname              = isset($_POST['first_name']) ? wc_clean($_POST['first_name']) : $fname;
            $lname              = isset($_POST['last_name']) ? wc_clean($_POST['last_name']) : $lname;
            $company            = isset($_POST['billing_company']) ? wc_clean($_POST['billing_company']) : $company;
            $billing_phone      = isset($_POST['billing_phone']) ? wc_clean($_POST['billing_phone']) : $billing_phone;
            $addr1              = isset($_POST['billing_address_1']) ? wc_clean($_POST['billing_address_1']) : $addr1;
            $billing_address_2  = isset($_POST['billing_address_2']) ? wc_clean($_POST['billing_address_2']) : $billing_address_2;
            $billing_city       = isset($_POST['billing_city']) ? wc_clean($_POST['billing_city']) : $billing_city;
            $billing_country    = isset($_POST['billing_country']) ? wc_clean($_POST['billing_country']) : wc_get_post_data_by_key('billing_country');
            $billing_state      = isset($_POST['billing_state']) ? wc_clean($_POST['billing_state']) : $billing_state;
            $billing_postcode   = isset($_POST['billing_postcode']) ? wc_clean($_POST['billing_postcode']) : $billing_postcode;
            $employees_count    = isset($_POST['employees_count']) ? wc_clean($_POST['employees_count']) : $employees_count;
            $business_years     = isset($_POST['business_years']) ? wc_clean($_POST['business_years']) : $business_years;
            $business_website   = isset($_POST['business_website']) ? wc_clean($_POST['business_website']) : $business_website;
            
			ob_start();
			?>
			
            <div class="avp_vendor_registration">
                <form method="post" action="" enctype="multipart/form-data" class="m-0">
                    <?php wp_nonce_field('avp_vendor_registrattion_nonce', 'avp_vendor_registrattion_nonce'); ?>
                    <div class="row">
                        <div class="col-12">
                            <h2 class="display-3 fw-5 my-2 pb-1 border-bottom"><?php esc_html_e('Business Information', 'woocommerce-vendor-portal'); ?></h2>
                        </div>
                        <div class="mb-3 col-md-6 col-12">
                            <label class="display-6 fw-5 text-gray-500 mb-1" for="first_name"><?php esc_html_e('First Name', 'woocommerce-vendor-portal'); ?><span class="required">*</span></label>
                            <input class="form-control" type="text" name="first_name" id="first_name" value="<?php esc_attr_e($fname); ?>" required>
                        </div>
                        <div class="mb-3 col-md-6 col-12">
                            <label class="display-6 fw-5 text-gray-500 mb-1" for="last_name"><?php esc_html_e('Last Name', 'woocommerce-vendor-portal'); ?><span class="required">*</span></label>
                            <input class="form-control" type="text" name="last_name" id="last_name" value="<?php esc_attr_e($lname); ?>" required>
                        </div>
                        <div class="mb-3 col-md-6 col-12">
                            <label class="display-6 fw-5 text-gray-500 mb-1" for="billing_company"><?php esc_html_e('Business Name', 'woocommerce-vendor-portal'); ?> <span class="required">*</span></label>
                            <input class="form-control" type="text" name="billing_company" id="billing_company" value="<?php esc_attr_e($company); ?>" required>
                        </div>
                        <div class="mb-3 col-md-6 col-12">
                            <label class="display-6 fw-5 text-gray-500 mb-1" for="billing_phone"><?php esc_html_e('Business Contact Number', 'woocommerce-vendor-portal'); ?> <span class="required">*</span></label>
                            <input class="form-control" type="text" name="billing_phone" id="billing_phone" value="<?php esc_attr_e($billing_phone); ?>" required>
                        </div>
                        <div class="mb-3 col-12">
                            <label class="display-6 fw-5 text-gray-500 mb-1" for="billing_address_1"><?php esc_html_e('Business Address line 1', 'woocommerce-vendor-portal'); ?> <span class="required">*</span></label>
                            <input class="form-control" type="text" name="billing_address_1" id="billing_address_1" value="<?php esc_attr_e($addr1); ?>" required>
                        </div>
                        <div class="mb-3 col-12">
                            <label class="display-6 fw-5 text-gray-500 mb-1" for="billing_address_2"><?php esc_html_e('Business Address line 2 (optional)', 'woocommerce-vendor-portal'); ?></label>
                            <input class="form-control" type="text" name="billing_address_2" id="billing_address_2" value="<?php esc_attr_e($billing_address_2); ?>">
                        </div>
                        <div class="mb-3 col-md-4 col-12">
                            <label class="display-6 fw-5 text-gray-500 mb-1" for="billing_city"><?php esc_html_e('City', 'woocommerce-vendor-portal'); ?><span class="required">*</span></label>
                            <input class="form-control" type="text" name="billing_city" id="billing_city" value="<?php esc_attr_e($billing_city); ?>" required>
                        </div>
                        <?php
                        woocommerce_form_field(
                            'billing_country',
                            array(
                                'label'       => __('Country / Region', 'woocommerce'),
                                'description' => '',
                                'class'       => array('js_field-country'),
                                'type'        => 'country',
                                'options'     => array('' => __('Select a country / region&hellip;', 'woocommerce')) + WC()->countries->get_allowed_countries(),
                            ),
                            $billing_country
                        );
                        ?>
                        <?php
                        woocommerce_form_field(
                            'billing_state',
                            array(
                                'type'        => 'state',
                                'label'       => __('State', 'woocommerce'),
                                'class'       => array('js_field-state'),
                                'required'    => true,
                            ),
                            $billing_state
                        );
                        ?>
                        <div class="mb-3 col-md-4 col-12">
                            <label class="display-6 fw-5 text-gray-500 mb-1" for="billing_postcode"><?php esc_html_e('ZIP', 'woocommerce-vendor-portal'); ?> <span class="required">*</span></label>
                            <input class="form-control" type="text" name="billing_postcode" id="billing_postcode" value="<?php esc_attr_e($billing_postcode); ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="mb-3 col-md-6 col-12">
                            <label class="display-6 fw-5 text-gray-500 mb-1" for="employees_count"><?php esc_html_e('No. of employees', 'woocommerce-vendor-portal'); ?><span class="required">*</span></label>
                            <input class="form-control" type="number" name="employees_count" id="employees_count" value="<?php esc_attr_e($employees_count); ?>" required>
                        </div>
                        <div class="mb-3 col-md-6 col-12">
                            <label class="display-6 fw-5 text-gray-500 mb-1" for="business_years"><?php esc_html_e('Years in business', 'woocommerce-vendor-portal'); ?><span class="required">*</span></label>
                            <input class="form-control" type="number" name="business_years" id="business_years" value="<?php esc_attr_e($business_years); ?>" required>
                        </div>
                        <div class="mb-3 col-md-6 col-12">
                            <label class="display-6 fw-5 text-gray-500 mb-1" for="business_website"><?php esc_html_e('Business Website (optional)', 'woocommerce-vendor-portal'); ?></label>
                            <input class="form-control" type="text" name="business_website" id="business_website" value="<?php esc_attr_e($business_website); ?>">
                        </div>
                    </div>

                    <div class="woocomerce-FormRow form-row col-12 mt-2">
                        <input class="btn w-10" type="submit" class="woocommerce-Button button" id="avp_register_upgrade" name="avp_register_upgrade" value="<?php esc_html_e('Apply', 'woocommerce-vendor-portal'); ?>">
                    </div>
                </form>
            </div>
			<?php 
			return ob_get_clean();
		}
	}
	new Avp_Vendor_Portal_Upgrade();
}
