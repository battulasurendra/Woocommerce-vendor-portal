<?php
if ( !defined('ABSPATH') ) {
	exit; // Exit if accessed directly
}
/**
 * Class to handle backend functionality
 */
if ( !class_exists('AVP_Vendor_Portal_Backend') ) {

	class AVP_Vendor_Portal_Backend {
		
		public function __construct() {
			add_action('admin_menu', array($this, 'avp_register_custom_menu_page'));
			add_action('admin_enqueue_scripts', array($this, 'avp_admin_script_style'));
        }
		
		public function avp_register_custom_menu_page() {
			add_menu_page(
				esc_html__('Vendor Portal', 'woocommerce-vendor-portal'),
				esc_html__('Vendor Portal', 'woocommerce-vendor-portal'),
				'manage_vendor',
				'avp_vendor',
				array($this, 'avp_vendor_page_callback'),
				'dashicons-groups',
				59
			);
        }
        
		public function avp_vendor_page_callback() {
			
        }
        
		public function avp_admin_script_style() {
			
			wp_enqueue_script('avp-script', AVP_PLUGIN_URL . 'assets/js/admin-script.js', array('jquery'), '1.0' );
			wp_localize_script(
				'avp-script', 'avpscript', array(
				'ajaxurl'		=>	admin_url('admin-ajax.php'),
				'admin_url'		=>	admin_url(),
				'ajax_nonce'	=>	wp_create_nonce('avp_vendor_portal'),
				)
			);
			wp_enqueue_style('avp-style', AVP_PLUGIN_URL . 'assets/css/admin-style.css', array(), '1.0' );
			wp_enqueue_style('fontawesome', AVP_PLUGIN_URL . 'assets/css/font-awesome.min.css', array(), '1.0' );
		}
	}
	new AVP_Vendor_Portal_Backend();
}
