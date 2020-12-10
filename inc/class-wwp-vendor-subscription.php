<?php
if ( !defined('ABSPATH') ) {
	exit; // Exit if accessed directly
}
if ( !class_exists('Wwp_Wholesale_Subscription') ) {

	class Wwp_Wholesale_Subscription {
		public function __construct() { 
			//echo wp_get_post_parent_id(53); exit();
			//add_action('woocommerce_checkout_subscription_created', array($this, 'wwp_new_order'), 99, 3 );
			add_action('subscriptions_created_for_order', array($this, 'wwp_new_order'), 99, 1 );
			add_action('woocommerce_subscriptions_updated_users_role', array($this, 'wwp_role_changed'), 99, 3 );
			add_action('woocommerce_subscription_status_cancelled', array($this,'woocommerce_subscription_status_cancelled'), 10, 1);
			add_action('woocommerce_subscription_status_expired', array($this,'woocommerce_subscription_status_cancelled'), 10, 1);
		}
		
		public function woocommerce_subscription_status_cancelled ( $subscription ) {
			
			$user_id = get_post_meta($subscription->ID, '_customer_user', true);
			
			do_action('wwp_wholesale_woo_subscription_status_cancelled_before', $user_id, $subscription );
			
			$user = get_userdata($user_id);
			
			foreach ($user->roles as $role) {
				
				$term = get_term_by('slug', $role, 'wholesale_user_roles');
				
				if ( !empty($term->slug) ) {
					
					$user->remove_role( apply_filters( 'wwp_wholesale_subscription_cancelled_remove_wholesale_role', $term->slug )  );
					
					break;
					
				}
			}
			
			$user->add_role( apply_filters( 'wwp_wholesale_subscription_cancelled_set_default_role', get_option('default_role') ) );
			
			do_action('wwp_wholesale_woo_subscription_status_cancelled_after', $user_id, $subscription );
			
			
		}
		
		public function wwp_new_order( $order ) { 
			//$subscription, $recurring_cart
			
			$subscription = wcs_get_subscriptions_for_order($order->id);
			
			$items = $order->get_items();
			$user_id = $order->get_user_id();
			$user = get_user_by('id', $user_id);
			$settings=get_option('wwp_vendor_portal_options');
			$wholesale_subscription = !empty($settings['wholesale_subscription']) ? $settings['wholesale_subscription'] : '';
			if ( empty($settings['enable_subscription']) || ( !empty($settings['enable_subscription']) && 'no' == $settings['enable_subscription'] ) || empty($wholesale_subscription) ) {
				return;
			}
			if ( !empty( array_intersect($this->wwp_resitricted_roles(), $user->roles ) ) ) {
				return;
			}
			
			foreach ($items as $item) {
				$product = $item->get_product();
				if ( !empty($item->get_variation_id()) && 'product_variation' == get_post_type($item->get_variation_id()) && wp_get_post_parent_id($item->get_variation_id()) == $wholesale_subscription ) {
					$args = array(
						'hide_empty' => false, 
						'meta_query' => array(
							array(
							   'key'       => 'wwp_wholesaler_subscription',
							   'value'     => $item->get_variation_id(),
							   'compare'   => 'IN'
							)
						),
						'taxonomy'  => 'wholesale_user_roles',
					);
					$term = get_terms( $args );
					if ( !empty($term) && !empty($term[0]->slug) ) {
						foreach ($user->roles as $role) {
							$user->remove_role( $role );
						}
						$user->add_role( $term[0]->slug );
						do_action('wwp_wholesale_user_role_upgraded', $user_id, $term[0]->slug );
						break;
					}
				}
			}
		}
		public function wwp_resitricted_roles() {
			return array('editor', 'administrator', 'shop_manager');
		}
		public function wwp_role_changed( $role_new, $user, $role_old ) {
			
			foreach ($user->roles as $role) {
				$term = get_term_by('slug', $role, 'wholesale_user_roles');
				if ( !empty($term->slug) ) {
					$user->remove_role( $role_new );
					break;
				}
			}
		}
	}
	new Wwp_Wholesale_Subscription();
}
