<?php
/**
 * Plugin Name: Acoustical Solutions - Vendor Portal
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
if (!defined('WVP_PLUGIN_URL')) {
	define('WVP_PLUGIN_URL', plugin_dir_url(__FILE__));
}
if (!defined('WVP_PLUGIN_PATH')) {
	define('WVP_PLUGIN_PATH', plugin_dir_path(__FILE__));
}
if (!defined('WVP_PLUGIN_DIRECTORY_NAME')) {
	define('WVP_PLUGIN_DIRECTORY_NAME', dirname(__FILE__));
}
if (!class_exists('Wvp_Vendor_Portal')) {

	class Wvp_Vendor_Portal {

		public function __construct() {
			register_activation_hook( __FILE__, array( $this, 'vendor_register_activation_hook') );
			if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
				self::init();
			} else {
				add_action('admin_notices', array(__Class__, 'vendor_admin_notice_error'));
			}
        }
        
		public function vendor_register_activation_hook() {
			
			if ( get_option('wvp_save_form') == false ) {

				$registrations = get_option('wvp_vendor_registration_options');
				$type = 'text';
				for ( $i=1; $i < 6; $i++ ) {

					if ( isset($registrations['custom_field_' . $i]) && 'yes' == $registrations['custom_field_' . $i] ) { 

						if ( isset($registrations['woo_custom_field_' . $i]) ) {
							$field_name =  $registrations['woo_custom_field_' . $i];
						} else {
							$field_name = esc_html__('Custom Field ' . $i, 'woocommerce-vendor-portal');
						}  

						if ( isset( $registrations['required_field_' . $i] ) && 'yes' == $registrations['required_field_' . $i] ) {
							$required = true;
						} else {
							$required = false;
						}  
						if ('5' == $i ) {
							$type = esc_html__( 'textarea', 'woocommerce-vendor-portal' );
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
				update_option('wvp_save_form', $fields);
				update_option('wvp_vendor_registration_options', $registrations);
			}
		}
		
		public static function init() {
			if (function_exists('load_plugin_textdomain')) {
				load_plugin_textdomain('woocommerce-vendor-portal', false, dirname(plugin_basename(__FILE__)) . '/languages/');
			}
			
			include_once WVP_PLUGIN_PATH . 'inc/class-wvp-vendor-general-functions.php';
			include_once WVP_PLUGIN_PATH . 'inc/class-wvp-vendor-common.php';
			include_once WVP_PLUGIN_PATH . 'inc/class-wvp-vendor-user-roles.php';
			if (is_admin()) {
				include_once WVP_PLUGIN_PATH . 'inc/class-wvp-vendor-backend.php';
				include_once WVP_PLUGIN_PATH . 'inc/class-wvp-vendor-metabox.php';
				include_once WVP_PLUGIN_PATH . 'inc/class-wvp-vendor-requests.php';
				include_once WVP_PLUGIN_PATH . 'inc/class-wvp-vendor-user-custom-fields.php';
				include_once WVP_PLUGIN_PATH . 'inc/class-wvp-vendor-registration-setting.php';
				include_once WVP_PLUGIN_PATH . 'inc/class-wvp-vendor-rulesets.php';
				$wvp_guide_skip = get_option('wvp_guide_skip');
				if (empty($wvp_guide_skip)) {
					include_once WVP_PLUGIN_PATH . 'inc/wvp-vendor-guide.php';
				}
				add_action('admin_notices', array(__Class__, 'setting_notice'));
			} else {
				include_once WVP_PLUGIN_PATH . '/inc/class-wvp-vendor-frontend.php';
				add_action('init', array(__Class__, 'include_vendor_functionality'));
            }
		}
        
        public static function include_vendor_functionality() {
			include_once WVP_PLUGIN_PATH . 'inc/class-wvp-vendor-registration.php';
        }
        
		public static function vendor_admin_notice_error() {
			$class = 'notice notice-error';
			$message = esc_html__('The plugin Vendor For WooCommerce requires WooCommerce to be installed and activated, in order to work', 'woocommerce-vendor-portal');
			printf('<div class="%1$s"><p>%2$s</p></div>', esc_html($class), esc_html($message)); 
		}
		public static function setting_notice() {
			$wvp_pricing_options = get_option('wvp_vendor_portal_options');
			if (empty($wvp_pricing_options)) { 
				$class = 'notice notice-warning is-dismissible';
				$heading = esc_html__('Thank You for installing Vendor Portal For WooCommerce', 'woocommerce-vendor-portal');
				$message = esc_html__('Your settings seems to be missing, You must save your settings to run the plugin, click here to ', 'woocommerce-vendor-portal');
				printf('<div class="%1$s"><h2>%2$s</h2><p> %3$s <a href="%4$s">%5$s</a></p></div>', esc_html($class), esc_html($heading), esc_html($message), esc_url(admin_url('admin.php?page=wvp_vendor')), esc_html__('Setup', 'woocommerce-vendor-portal'));
			}   
		}   
	}   
	new Wvp_Vendor_Portal();
}
