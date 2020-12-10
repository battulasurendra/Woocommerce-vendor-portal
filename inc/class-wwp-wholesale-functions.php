<?php
if ( !defined('ABSPATH') ) {
	exit; // Exit if accessed directly
}
/**
 * Class To Add Wholesale Functionality with WooCommerce
 */
if ( !class_exists('Wwp_Wholesale_Functions') ) {

	class Wwp_Wholesale_Functions {

		public function __construct () {
			add_filter('woocommerce_package_rates', array($this, 'wwp_apply_free_shipping_if_valid_coupon'), 100);
			add_action('init', array($this, 'wwp_exclude_tax'), 99, 1 );
			
			// enable to display tax id in billing address
			add_action( 'woocommerce_order_details_after_customer_details', array($this,'wwp_wholesale_after_customer_details'), 10, 1 ); 
		}
		
		public function wwp_wholesale_after_customer_details( $order ) { 
		
			$registrations = get_option('wwp_wholesale_registration_options');
			if ( isset($registrations['tax_id_display']) && 'yes' == $registrations['tax_id_display'] ) {
				$wholesaler_tax_id = esc_html__('Wholesaler Tax ID ', 'woocommerce-wholesale-pricing');
				echo wp_kses('<p><strong> ' . $wholesaler_tax_id . ':</strong> <br/>' . get_user_meta( $order->get_user_id(), 'wwp_wholesaler_tax_id', true ) . '</p>', shapeSpace_allowed_html());	
			}
		
		} 
		
		public function wwp_apply_free_shipping_if_valid_coupon ( $rates ) {
			global $woocommerce;
			$free = array();
			foreach ( $woocommerce->cart->applied_coupons as $coupon ) {
				$page = get_page_by_title($coupon, '', 'shop_coupon');
				$coupon = new WC_Coupon( $page->ID );
				if ( $coupon->get_free_shipping() ) {
					foreach ( $rates as $rate_id => $rate ) {
						if ( 'flat_rate' === $rate->method_id ) {
							$rate->label = 'Free Shipping';
							$rate->cost = 0.00;
							$free[ $rate_id ] = $rate;
							break;
						}
					}
				}
			}
			return !empty($free) ? $free : $rates;
		}
		public function wwp_exclude_tax( $post_data ) {
			if ( is_admin() ) {
				return;
			}
			global $woocommerce;
			$woocommerce->customer->set_is_vat_exempt( false );
			if ( is_user_logged_in() ) {
				$user_info = get_userdata(get_current_user_id());
				$user_role = implode( ', ', $user_info->roles );
				$wholesale_role = term_exists($user_role, 'wholesale_user_roles');
				if ( 0 !== $wholesale_role && null !== $wholesale_role ) {
					if ( is_array($wholesale_role) && isset($wholesale_role['term_id']) && 'yes' == get_term_meta($wholesale_role['term_id'], 'wwp_tax_exmept_wholesaler', true) ) { 
						$woocommerce->customer->set_is_vat_exempt( true );
						//add_filter('woocommerce_before_checkout_billing_form', array($this, 'wte_remove_tax'), 10, 2 );
						add_filter('woocommerce_product_get_tax_class', array($this, 'wte_remove_tax'), 10, 2 );
						add_action('woocommerce_before_cart_contents', array($this, 'wwp_exclude_tax'), 10, 2 );
						add_action('woocommerce_before_shipping_calculator', array($this, 'wwp_exclude_tax'), 10, 2 );
						add_filter('woocommerce_get_price_suffix', array($this, 'woocommerce_get_price_suffix' ), 99, 4 );
					}
				}
			}				
		}
		public function woocommerce_get_price_suffix( $html, $product, $price, $qty ) {
			return '';
		}
		public function wte_remove_tax( $tax_class, $product ) {
			return 'Zero Rate';
		}
	}
	new Wwp_Wholesale_Functions();
}
