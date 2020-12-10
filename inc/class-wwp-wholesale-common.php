<?php
if ( !defined('ABSPATH') ) {
	exit; // Exit if accessed directly
}
if ( !class_exists('WWP_Wholesale_Pricing_Common') ) {

	class WWP_Wholesale_Pricing_Common {

		public function __construct() {
			// admin notification
			add_action('wwp_wholesale_new_request_submitted', array($this, 'wwp_wholesale_admin_request_notification'), 10, 1);
			
			// approved user request
			add_action('wwp_wholesale_user_request_approved', array($this, 'wwp_wholesale_user_request_approved_notification'), 10, 1);
			
			// reject request
			add_action('wwp_wholesale_user_rejection_notification', array($this, 'wwp_wholesale_user_rejection_notification'), 10, 1);
			
			// new user register
			add_action('wwp_wholesale_new_registered_request', array($this, 'wwp_wholesale_new_registered_request'), 10, 1);
			
			
			// version 1.3.0 subscription
			add_action('wwp_wholesale_user_role_upgraded', array($this, 'wwp_wholesale_subscription_role_upgraded'), 10, 2);
			// ends version 1.3.0
		}
		
		
		public function wwp_wholesale_user_rejection_notification ( $user_id ) {
			if ( 'yes' == get_option('wwp_wholesale_user_rejection_notification') ) {
				$subject = get_option('wwp_wholesale_rejection_notification_subject');
				$subject = !empty( $subject ) ? $subject : esc_html__('Request Rejected', 'woocommerce-wholesale-pricing');
				$subject = stripslashes(html_entity_decode($subject, ENT_QUOTES, 'UTF-8' ));
				$body = get_option('wwp_wholesale_rejection_notification_body');
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
		}
		
		
		public function wwp_wholesale_new_registered_request ( $user_id ) {
			if ( 'yes' == get_option('wwp_wholesale_user_registration_notification') ) {
				
				$subject = get_option('wwp_wholesale_registration_notification_subject');
				$subject = !empty( $subject ) ? $subject : esc_html__('New Request Received.', 'woocommerce-wholesale-pricing');
				$subject = stripslashes(html_entity_decode($subject, ENT_QUOTES, 'UTF-8' ));
				$body = get_option('wwp_wholesale_registration_notification_body');
				$user = get_user_by( 'ID', $user_id );
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
		
		
		
		public function wwp_wholesale_admin_request_notification ( $user_id ) {
			if ( 'yes' == get_option('wwp_wholesale_admin_request_notification') ) {
				$subject = get_option('wwp_wholesale_admin_request_subject');
				$subject = !empty( $subject ) ? $subject : esc_html__('New Requested Received.', 'woocommerce-wholesale-pricing');
				$subject = stripslashes(html_entity_decode($subject, ENT_QUOTES, 'UTF-8' ));
				$body = get_option('wwp_wholesale_admin_request_body');
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
					wp_mail( get_option('admin_email'), $subject, $body, $headers, '' );
				}
			}
		}
		public function wwp_wholesale_user_request_approved_notification ( $user_id ) {
			if ( 'yes' == get_option('wwp_wholesale_request_approve_notification') ) {
				$subject = get_option('wwp_wholesale_email_request_subject');
				$subject = !empty( $subject ) ? esc_html($subject) : esc_html__('Your Requested Approved.', 'woocommerce-wholesale-pricing');
				$subject = stripslashes(html_entity_decode($subject, ENT_QUOTES, 'UTF-8' ));
				$body = get_option('wwp_wholesale_email_request_body');
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
			// version 1.3.0
		public function wwp_wholesale_subscription_role_upgraded( $user_id, $role ) {
			if ( 'yes' == get_option('wwp_wholesale_subscription_role_notification') ) {
				$subject = get_option('wwp_wholesale_subscription_role_subject');
				$subject = !empty( $subject ) ? esc_html($subject) : esc_html__('Your Role Is Upgraded.', 'woocommerce-wholesale-pricing');
				$subject = stripslashes(html_entity_decode($subject, ENT_QUOTES, 'UTF-8' ));
				$body = get_option('wwp_wholesale_subscription_role_body');
				$user = get_user_by('ID', $user_id);
				if ( !is_wp_error($user) ) {
					$sendor = esc_html(get_option('blogname')) . ' <' . esc_html(get_option('admin_email')) . '>';
					$headers  = 'From: ' . $sendor . PHP_EOL;
					$headers .= 'MIME-Version: 1.0' . PHP_EOL; 
					$headers .= 'Content-Type: text/html; boundary=\"' . md5(time()) . '\"';
					$body=str_replace('{email}', $user->user_email, $body);
					$body=str_replace('{first_name}', $user->first_name, $body);
					$body=str_replace('{last_name}', $user->last_name, $body);
					$body=str_replace('{username}', $user->user_login, $body);
					$body=str_replace('{role}', $role, $body);
					$body=str_replace('{date}', gmdate( 'Y-m-d', strtotime( $user->user_registered ) ), $body);
					$body=str_replace('{time}', gmdate( 'H:i:s', strtotime( $user->user_registered ) ), $body);
					wp_mail($user->user_email, $subject, $body, $headers, '');
				}
			}
		}
		// ends version 1.3.0
	}
	new WWP_Wholesale_Pricing_Common();
}
