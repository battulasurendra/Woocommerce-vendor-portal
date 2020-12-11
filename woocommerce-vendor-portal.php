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
if (!defined('AVP_PLUGIN_URL')) {
	define('AVP_PLUGIN_URL', plugin_dir_url(__FILE__));
}
if (!defined('AVP_PLUGIN_PATH')) {
	define('AVP_PLUGIN_PATH', plugin_dir_path(__FILE__));
}
if (!defined('AVP_PLUGIN_DIRECTORY_NAME')) {
	define('AVP_PLUGIN_DIRECTORY_NAME', dirname(__FILE__));
}
if (!class_exists('Avp_Vendor_Portal')) {

	class Avp_Vendor_Portal {

		public function __construct() {
			register_activation_hook( __FILE__, array( $this, 'vendor_register_activation_hook') );
			if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
				self::init();
			} else {
				add_action('admin_notices', array(__Class__, 'vendor_admin_notice_error'));
			}
        }
        
		public function vendor_register_activation_hook() {
			
			if ( get_option('avp_save_form') == false ) {

				$registrations = get_option('avp_vendor_registration_options');
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
				update_option('avp_save_form', $fields);
				update_option('avp_vendor_registration_options', $registrations);
			}
		}
		
		public static function init() {
			if (function_exists('load_plugin_textdomain')) {
				load_plugin_textdomain('woocommerce-vendor-portal', false, dirname(plugin_basename(__FILE__)) . '/languages/');
			}
			
			include_once AVP_PLUGIN_PATH . 'inc/class-avp-vendor-common.php';
			include_once AVP_PLUGIN_PATH . 'inc/class-avp-vendor-user-roles.php';
			if (is_admin()) {
				include_once AVP_PLUGIN_PATH . 'inc/class-avp-vendor-backend.php';
				include_once AVP_PLUGIN_PATH . 'inc/class-avp-vendor-requests.php';
			} else {
				include_once AVP_PLUGIN_PATH . '/inc/class-avp-vendor-upgrade.php';
				add_action('init', array(__Class__, 'include_vendor_functionality'));
            }
		}
        
        public static function include_vendor_functionality() {
			include_once AVP_PLUGIN_PATH . 'inc/class-avp-vendor-registration.php';
        }
        
		public static function vendor_admin_notice_error() {
			$class = 'notice notice-error';
			$message = esc_html__('The plugin Vendor For WooCommerce requires WooCommerce to be installed and activated, in order to work', 'woocommerce-vendor-portal');
			printf('<div class="%1$s"><p>%2$s</p></div>', esc_html($class), esc_html($message)); 
		}  
	}   
	new Avp_Vendor_Portal();
}
