<?php
/**
 * Plugin Name: Vendor Portal
 * Description: This plugin helps to manage vendor requests.
 * Author: Pichkaari Design Studio Pvt Ltd.
 * Developer: Surendra
 * Version: 1.0.0
 * WC requires at least: 3.0
 * WC tested up to: 4.7.1
 */

if (!defined('ABSPATH')) {
	exit();
}
if (!defined('WWP_PLUGIN_URL')) {
	define('WWP_PLUGIN_URL', plugin_dir_url(__FILE__));
}
if (!defined('WWP_PLUGIN_PATH')) {
	define('WWP_PLUGIN_PATH', plugin_dir_path(__FILE__));
}
if (!defined('WWP_PLUGIN_DIRECTORY_NAME')) {
	define('WWP_PLUGIN_DIRECTORY_NAME', dirname(__FILE__));
}
if (!class_exists('Wwp_Wholesale_Pricing')) {

	class Wwp_Wholesale_Pricing {

		public function __construct() {
			register_activation_hook( __FILE__, array( $this, 'wholesale_register_activation_hook') );
			if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
				self::init();
			} else {
				add_action('admin_notices', array(__Class__, 'wholesale_admin_notice_error'));
			}
		}
		public function wholesale_register_activation_hook() {
			
			if ( get_option('wwp_save_form') == false ) {

				$registrations = get_option('wwp_wholesale_registration_options');
				$type = 'text';
				for ( $i=1; $i < 6; $i++ ) {

					if ( isset($registrations['custom_field_' . $i]) && 'yes' == $registrations['custom_field_' . $i] ) { 

						if ( isset($registrations['woo_custom_field_' . $i]) ) {
							$field_name =  $registrations['woo_custom_field_' . $i];
						} else {
							$field_name = esc_html__('Custom Field ' . $i, 'woocommerce-wholesale-pricing');
						}  

						if ( isset( $registrations['required_field_' . $i] ) && 'yes' == $registrations['required_field_' . $i] ) {
							$required = true;
						} else {
							$required = false;
						}  
						if ('5' == $i ) {
							$type = esc_html__( 'textarea', 'woocommerce-wholesale-pricing' );
						}  

							$fields[] = array(
								'type'		=> $type, 
								'required'	=> $required, 
								'label'		=> $field_name,
								'className'	=> 'form-control',
								'name'		=> 'text-159670154749' . $i,
								'value'		=> '',
								'subtype'	=> 'text'
							);
					}
				}
				$fields = json_encode( $fields ) ;
				$registrations['display_fields_registration'] = 'yes';
				update_option('wwp_save_form', $fields);
				update_option('wwp_wholesale_registration_options', $registrations);
			}
		}
		
		public static function init() {
			if (function_exists('load_plugin_textdomain')) {
				load_plugin_textdomain('woocommerce-wholesale-pricing', false, dirname(plugin_basename(__FILE__)) . '/languages/');
			}
			
			include_once WWP_PLUGIN_PATH . 'inc/class-wwp-wholesale-general-functions.php';
			include_once WWP_PLUGIN_PATH . 'inc/class-wwp-wholesale-common.php';
			include_once WWP_PLUGIN_PATH . 'inc/class-wwp-wholesale-user-roles.php';
			if (is_admin()) {
				include_once WWP_PLUGIN_PATH . 'inc/class-wwp-wholesale-backend.php';
				include_once WWP_PLUGIN_PATH . 'inc/class-wwp-wholesale-metabox.php';
				include_once WWP_PLUGIN_PATH . 'inc/class-wwp-wholesale-requests.php';
				include_once WWP_PLUGIN_PATH . 'inc/class-wwp-wholesale-user-custom-fields.php';
				include_once WWP_PLUGIN_PATH . 'inc/class-wwp-wholesale-bulk-price.php';
				include_once WWP_PLUGIN_PATH . 'inc/class-wwp-wholesale-registration-setting.php';
				include_once WWP_PLUGIN_PATH . 'inc/class-wwp-wholesale-rulesets.php';
				$wwp_guide_skip = get_option('wwp_guide_skip');
				if (empty($wwp_guide_skip)) {
					include_once WWP_PLUGIN_PATH . 'inc/wwp-wholesale-guide.php';
				}
				add_action('admin_notices', array(__Class__, 'setting_notice'));
			} else {
				include_once WWP_PLUGIN_PATH . '/inc/class-wwp-wholesale-frontend.php';
				add_action('init', array(__Class__, 'include_wholesale_functionality'));
				// version 1.3.0
				include_once WWP_PLUGIN_PATH . 'inc/class-wwp-products-visibility.php';
				// ends version 1.3.0
			}
			// version 1.3.0
			if ( in_array('woocommerce-subscriptions/woocommerce-subscriptions.php', apply_filters('active_plugins', get_option('active_plugins'))) ) {
					include_once WWP_PLUGIN_PATH . 'inc/class-wwp-wholesale-subscription.php';
			}
			// ends version 1.3.0
		}
		public static function include_wholesale_functionality() {
			if (is_user_logged_in()) {
				$user_info = get_userdata(get_current_user_id());
				$user_role = implode(', ', $user_info->roles);
				$wholesale_role = term_exists($user_role, 'wholesale_user_roles');
				if ( ( 0 !== $wholesale_role && null !== $wholesale_role ) || is_admin() ) {
					$settings=get_option('wwp_vendor_portal_options', true);
					if (isset($settings['wholesale_role']) && 'single' != $settings['wholesale_role']) {
						include_once WWP_PLUGIN_PATH . 'inc/class-wwp-wholesale-multiuser.php';
					} else {
						include_once WWP_PLUGIN_PATH . 'inc/class-wwp-wholesale.php';
					}
					include_once WWP_PLUGIN_PATH . 'inc/class-wwp-wholesale-functions.php';
				}
			}
			include_once WWP_PLUGIN_PATH . 'inc/class-wwp-wholesale-registration.php';
		}
		public static function wholesale_admin_notice_error() {
			$class = 'notice notice-error';
			$message = esc_html__('The plugin Wholesale For WooCommerce requires WooCommerce to be installed and activated, in order to work', 'woocommerce-wholesale-pricing');
			printf('<div class="%1$s"><p>%2$s</p></div>', esc_html($class), esc_html($message)); 
		}
		public static function setting_notice() {
			$wwp_pricing_options = get_option('wwp_vendor_portal_options');
			if (empty($wwp_pricing_options)) { 
				$class = 'notice notice-warning is-dismissible';
				$heading = esc_html__('Thank You for installing Wholesale For WooCommerce', 'woocommerce-wholesale-pricing');
				$message = esc_html__('Your settings seems to be missing, You must save your settings to run the plugin, click here to ', 'woocommerce-wholesale-pricing');
				printf('<div class="%1$s"><h2>%2$s</h2><p> %3$s <a href="%4$s">%5$s</a></p></div>', esc_html($class), esc_html($heading), esc_html($message), esc_url(admin_url('admin.php?page=wwp_vendor')), esc_html__('Setup', 'woocommerce-wholesale-pricing'));
			}   
		}   
	}   
	new Wwp_Wholesale_Pricing();
}
