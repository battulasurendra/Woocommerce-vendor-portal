<?php
if ( !defined('ABSPATH') ) {
	exit; // Exit if accessed directly
}
if ( !class_exists('AVP_Vendor_Portal_Common') ) {

	class AVP_Vendor_Portal_Common {

		public function __construct() {
            add_action('avp_vendor_new_request_submitted', array($this, 'avp_vendor_admin_request_notification'), 10, 1);
			
			add_action('avp_vendor_user_request_approved', array($this, 'avp_vendor_user_request_approved_notification'), 10, 1);
			add_action('avp_vendor_user_rejection_notification', array($this, 'avp_vendor_user_rejection_notification'), 10, 1);
			add_action('avp_vendor_user_role_upgraded', array($this, 'avp_vendor_user_request_approved_notification'), 10, 2);
			
        }
        
        public function avp_vendor_admin_request_notification ( $user_id ) {
            $subject = esc_html__('New vendor registration', 'woocommerce-vendor-portal');
            $subject = stripslashes(html_entity_decode($subject, ENT_QUOTES, 'UTF-8' ));
            $user = get_user_by('ID', $user_id );
            $body = 'Another user {first_name} {last_name}, with {email} has registered as vendor.';
            if ( !is_wp_error($user) ) {
                $sendor = esc_html(get_option('blogname')) . ' <' . esc_html(get_option('admin_email')) . '>';
                $headers  = 'From: ' . $sendor . PHP_EOL;
                $headers .= 'MIME-Version: 1.0' . PHP_EOL; 
                $headers .= 'Content-Type: text/html; charset=UTF-8';
                $body=str_replace('{email}', $user->user_email, $body);
                $body=str_replace('{first_name}', $user->first_name, $body);
                $body=str_replace('{last_name}', $user->last_name, $body);
                $body=str_replace('{username}', $user->user_login, $body);
                $body=str_replace('{date}', gmdate( 'Y-m-d', strtotime( $user->user_registered ) ), $body);
                $body=str_replace('{time}', gmdate( 'H:i:s', strtotime( $user->user_registered ) ), $body);
                wp_mail( get_option('admin_email'), $subject, $body, $headers, '' );
            }
		}
		
		
		public function avp_vendor_user_rejection_notification ( $user_id ) {
            $subject = esc_html__('Request Rejected', 'woocommerce-vendor-portal');
            $subject = stripslashes(html_entity_decode($subject, ENT_QUOTES, 'UTF-8' ));
            $body = 'Hello {first_name} {last_name}, your request for vendor is rejected.';
            $user = get_user_by('ID', $user_id );
            if ( !is_wp_error($user) ) {
                $sendor = esc_html(get_option('blogname')) . ' <' . esc_html(get_option('admin_email')) . '>';
                $headers  = 'From: ' . $sendor . PHP_EOL;
                $headers .= 'MIME-Version: 1.0' . PHP_EOL; 
                $headers .= 'Content-Type: text/html; charset=UTF-8';
                $body=str_replace('{email}', $user->user_email, $body);
                $body=str_replace('{first_name}', $user->first_name, $body);
                $body=str_replace('{last_name}', $user->last_name, $body);
                $body=str_replace('{username}', $user->user_login, $body);
                $body=str_replace('{date}', gmdate( 'Y-m-d', strtotime( $user->user_registered ) ), $body);
                $body=str_replace('{time}', gmdate( 'H:i:s', strtotime( $user->user_registered ) ), $body);
                wp_mail($user->user_email, $subject, $body, $headers, '');
            }
		}
		
		public function avp_vendor_user_request_approved_notification ( $user_id ) {
            $subject = esc_html__('Your Requested Approved.', 'woocommerce-vendor-portal');
            $subject = stripslashes(html_entity_decode($subject, ENT_QUOTES, 'UTF-8' ));
            $body = 'Hello {first_name} {last_name}, your request for vendor is approved. Your account is upgraded.';
            $user = get_user_by('ID', $user_id);
            if ( !is_wp_error($user) ) {
                $sendor = esc_html(get_option('blogname')) . ' <' . esc_html(get_option('admin_email')) . '>';
                $headers  = 'From: ' . $sendor . PHP_EOL;
                $headers .= 'MIME-Version: 1.0' . PHP_EOL; 
                $headers .= 'Content-Type: text/html; charset=UTF-8';
                $body=str_replace('{email}', $user->user_email, $body);
                $body=str_replace('{first_name}', $user->first_name, $body);
                $body=str_replace('{last_name}', $user->last_name, $body);
                $body=str_replace('{username}', $user->user_login, $body);
                $body=str_replace('{date}', gmdate( 'Y-m-d', strtotime( $user->user_registered ) ), $body);
                $body=str_replace('{time}', gmdate( 'H:i:s', strtotime( $user->user_registered ) ), $body);				
                wp_mail($user->user_email, $subject, $body, $headers, '');
            }
		}
	}
	new AVP_Vendor_Portal_Common();
}
