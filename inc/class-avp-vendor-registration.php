<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class Woo_Vendor_Registration
 */
if (!class_exists('Avp_Vendor_Portal_Registration')) {

    class Avp_Vendor_Portal_Registration
    {

        public function __construct()
        {

            add_shortcode('vendor_registration_form', array($this, 'avp_vendor_registration_form'));
            $this->errors = array();
            $this->registratio_process();
        }

        public function registratio_process()
        {

            if (isset($_POST['avp_register'])) {
                if (isset($_POST['avp_vendor_registrattion_nonce']) || wp_verify_nonce(wc_clean($_POST['avp_vendor_registrattion_nonce']), 'avp_vendor_registrattion_nonce')) {
                    $this->errors = $this->avp_register_vendor();
                }
            }
        }

        public function avp_vendor_registration_form()
        {
            if (!is_admin() && is_user_logged_in()) {
                return esc_html__('You are already registered!', 'woocommerce-vendor-portal');
            }
            
            $errors = array();
            ob_start();
            
            if (isset($_POST['avp_register'])) {
                if (isset($_POST['avp_vendor_registrattion_nonce']) || wp_verify_nonce(wc_clean($_POST['avp_vendor_registrattion_nonce']), 'avp_vendor_registrattion_nonce')) {
                    $errors = $this->errors;
                }
            }

            if (!empty($errors)) {
                echo '<ul class="bg-danger border border-danger display-6 mt-2 notice-danger rounded text-white" role="alert">';
                foreach ($errors as $key => $error) {
                    echo '<li class="m-1">' . wp_kses_post($error) . '</li>';
                }
                echo '</ul>';
            }
            
            $email              = isset($_POST['user_email']) ? wc_clean($_POST['user_email']) : '';
            $fname              = isset($_POST['first_name']) ? wc_clean($_POST['first_name']) : '';
            $lname              = isset($_POST['last_name']) ? wc_clean($_POST['last_name']) : '';
            $company            = isset($_POST['billing_company']) ? wc_clean($_POST['billing_company']) : '';
            $billing_phone      = isset($_POST['billing_phone']) ? wc_clean($_POST['billing_phone']) : '';
            $addr1              = isset($_POST['billing_address_1']) ? wc_clean($_POST['billing_address_1']) : '';
            $billing_address_2  = isset($_POST['billing_address_2']) ? wc_clean($_POST['billing_address_2']) : '';
            $billing_city       = isset($_POST['billing_city']) ? wc_clean($_POST['billing_city']) : '';
            $billing_country    = isset($_POST['billing_country']) ? wc_clean($_POST['billing_country']) : wc_get_post_data_by_key('billing_country');
            $billing_state      = isset($_POST['billing_state']) ? wc_clean($_POST['billing_state']) : '';
            $billing_postcode   = isset($_POST['billing_postcode']) ? wc_clean($_POST['billing_postcode']) : '';

            $employees_count    = isset($_POST['employees_count']) ? wc_clean($_POST['employees_count']) : '';
            $business_years     = isset($_POST['business_years']) ? wc_clean($_POST['business_years']) : '';
            $business_website   = isset($_POST['business_website']) ? wc_clean($_POST['business_website']) : '';

            if ('yes' == get_option('avp_notice_register')) {
                $notice = apply_filters('avp_success_msg', esc_html__('You are Registered Successfully', 'woocommerce-vendor-portal'));
                wc_print_notice(esc_html__($notice, 'woocommerce-vendor-portal'), 'success');
                delete_option('avp_notice_register');
            }
            ?>

            <div class="avp_vendor_registration">
                <form method="post" action="" enctype="multipart/form-data">
                    <?php
                    wp_nonce_field('avp_vendor_registrattion_nonce', 'avp_vendor_registrattion_nonce');
                    ?>
                    <div class="row">
                        <div class="col-12">
                            <h2 class="display-3 fw-5 mt-4 mb-2 pb-1 border-bottom"><?php esc_html_e('Account information', 'woocommerce-vendor-portal'); ?></h2>
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
                            <label class="display-6 fw-5 text-gray-500 mb-1" for="user_email"><?php esc_html_e('Email Address', 'woocommerce-vendor-portal'); ?><span class="required">*</span></label>
                            <input class="form-control" type="email" name="user_email" id="user_email" value="<?php esc_attr_e($email); ?>" required>
                        </div>
                        <div class="mb-3 col-md-6 col-12">
                            <label class="display-6 fw-5 text-gray-500 mb-1" for="user_pass"><?php esc_html_e('Password', 'woocommerce-vendor-portal'); ?><span class="required">*</span></label>
                            <input class="form-control" type="password" name="user_pass" id="user_pass" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <h2 class="display-3 fw-5 mt-4 mb-2 pb-1 border-bottom"><?php esc_html_e('Business Information', 'woocommerce-vendor-portal'); ?></h2>
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
                        <div class="mb-3 col-md-4 col-12">
                            <label class="display-6 fw-5 text-gray-500 mb-1" for="employees_count"><?php esc_html_e('No. of employees', 'woocommerce-vendor-portal'); ?><span class="required">*</span></label>
                            <input class="form-control" type="number" name="employees_count" id="employees_count" value="<?php esc_attr_e($employees_count); ?>" required>
                        </div>
                        <div class="mb-3 col-md-4 col-12">
                            <label class="display-6 fw-5 text-gray-500 mb-1" for="business_years"><?php esc_html_e('Years in business', 'woocommerce-vendor-portal'); ?><span class="required">*</span></label>
                            <input class="form-control" type="number" name="business_years" id="business_years" value="<?php esc_attr_e($business_years); ?>" required>
                        </div>
                        <div class="mb-3 col-md-4 col-12">
                            <label class="display-6 fw-5 text-gray-500 mb-1" for="business_website"><?php esc_html_e('Business Website (optional)', 'woocommerce-vendor-portal'); ?></label>
                            <input class="form-control" type="text" name="business_website" id="business_website" value="<?php esc_attr_e($business_website); ?>">
                        </div>
                        <div class="woocomerce-FormRow col-12 mt-2">
                            <input class="btn w-10" type="submit" class="woocommerce-Button button" id="register" name="avp_register" value="<?php esc_html_e('Apply', 'woocommerce-vendor-portal'); ?>">
                        </div>
                    </div>
                </form>
            </div>
<?php
            return ob_get_clean();
        }
        public function avp_register_vendor()
        {

            if (!isset($_POST['avp_vendor_registrattion_nonce']) || !wp_verify_nonce(wc_clean($_POST['avp_vendor_registrattion_nonce']), 'avp_vendor_registrattion_nonce')) {
                return;
            }

            $errors = array();
           
            $email = isset($_POST['user_email']) ? wc_clean($_POST['user_email']) : '';
            $first_name = isset($_POST['first_name']) ? wc_clean($_POST['first_name']) : '';
            $last_name = isset($_POST['last_name']) ? wc_clean($_POST['last_name']) : '';
			$username = wc_create_new_customer_username( $email, array('first_name' => $first_name, 'last_name'  => $last_name));

            $userdata = array(
                'first_name'    => $first_name,
                'last_name'     => $last_name,
                'user_email'    => $email,
                'user_login'    => $username,
                'user_pass'     => isset($_POST['user_pass']) ? wc_clean($_POST['user_pass']) : '',
                'role'          => 'customer',
            );

            $user_id = wp_insert_user($userdata);

            if (!is_wp_error($user_id)) {
                wc_set_customer_auth_cookie( $user_id );

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
                        'post_title'    => !empty($username) ? wc_clean($username) . ' - ' . esc_attr($user_id) : $email,
                        'post_status'   => 'publish'
                    )
                );

                if (!is_wp_error($id)) {
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

                wp_redirect(wc_get_account_endpoint_url('dashboard'));
                //On success
                if (!is_wp_error($user_id)) {
                    $notice = apply_filters('avp_success_msg', esc_html__('You are Registered Successfully', 'woocommerce-vendor-portal'));
                    wc_add_notice(esc_html__($notice, 'woocommerce-vendor-portal'), 'success');
                    $_POST = array();
                } else {
                    $notice = apply_filters('avp_error_msg', esc_html__($user_id->get_error_message(), 'woocommerce-vendor-portal'));
                    wc_add_notice(esc_html__($notice, 'woocommerce-vendor-portal'), 'error');
                }
                exit;
            } else {
                $errors[] = $user_id->get_error_message();
            }
            return $errors;
        }
    }
    new Avp_Vendor_Portal_Registration();
}
