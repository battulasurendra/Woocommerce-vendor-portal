<?php
if ( !defined('ABSPATH') ) {
	exit; // Exit if accessed directly
}
if ( !class_exists('Wwp_Wholesale_Products_Visibility') ) {

	class Wwp_Wholesale_Products_Visibility {
		protected $excluded;
		public function __construct() {
			$this->excluded = array();
			add_action('init', array($this, 'wwp_get_excluded_products'));
			add_action('woocommerce_product_query', array($this, 'wwp_modiffy_query'), 10, 1 );
			add_filter('woocommerce_shortcode_products_query', array($this, 'wwp_modiffy_shortcode_query' ), 10, 1 );
			add_filter('woocommerce_is_purchasable', array($this, 'wwp_is_purchasable'), 20, 2 );
			add_action('template_redirect', array($this, 'wwp_redirect_products') );
			add_filter('woocommerce_coupons_enabled', array($this, 'wwp_coupons_enabled'), 10, 1 );
		}
		public function wwp_redirect_products () {
			global $post;
			if (wc_get_product( $post )) {
				$product = wc_get_product( $post );
			}
			if ( is_singular( 'product' ) && ! empty( $post ) && !empty($this->excluded) && in_array( $product->get_id(), $this->excluded ) ) {
				wp_redirect( home_url() );
				exit;
			}
		}
		public function wwp_is_purchasable ( $purchasable, $product ) {
			if ( !empty($this->excluded) && in_array($product->get_id(), $this->excluded) ) {
				return false;
			}
			return $purchasable;
		}
		public function wwp_coupons_enabled ( $enabled ) {
			if ( $this->wwp_coupons_disabled () ) {
				WC()->cart->remove_coupons();
				return false;
			}
			return $enabled;
		}			
		public function wwp_modiffy_shortcode_query ( $query_args ) {
			$excluded_products = $this->excluded;
			if ( !empty($excluded_products) ) {
				$post__not_in = isset($query_args['post__not_in']) ? (array) $query_args['post__not_in'] : array();
				$query_args['post__not_in'] = array_merge( $post__not_in, $excluded_products);
			}
			return $query_args;
		}
		public function wwp_modiffy_query( $query ) {
			if ( is_woocommerce() && $query->is_main_query() && isset($query->query_vars['wc_query']) ) {
				$excluded_products = $this->excluded;
				if ( !empty($excluded_products) ) {
					$post__not_in = (array) $query->get('post__not_in');
					$query->set('post__not_in', array_merge( $post__not_in, $excluded_products ) );
				}
			}
		}
		public function wwp_get_products_single() {
			$excluded_products_1 = array();
			$excluded_products_2 = array();
			$args = array(
				'post_type'      => array('product'),
				'posts_per_page' => -1,
				'fields'		 => 'ids',
				'meta_query' =>  array(
					array(
						'key'     => '_wwp_wholesale_product_visibility',
						'value'     => 'yes',
						'compare' => 'IN'
					)
				)
			);
			$excluded_products_1 = get_posts($args);
			$args = array(
				'hide_empty' => true,
				'fields'		 => 'ids',
				'posts_per_page' => -1,
				'meta_query' => array(
					array(
					   'key'       => '_wwp_wholesale_product_visibility',
					   'value'     => 'yes',
					   'compare'   => 'IN'
					)
				),
				'taxonomy'  => 'product_cat'
			);
			$terms = get_terms( $args );
			if ( !empty($terms) ) {
				$args = array(
					'post_type'      => array('product'),
					'posts_per_page' => -1,
					'fields'		 => 'ids',
					'tax_query' => array(
						array(
							'taxonomy' => 'product_cat',
							'terms' => $terms,
							'operator' => 'IN',
						)
					),
				);
				$excluded_products_2 = get_posts($args);
			}
			return array_merge( $excluded_products_1, $excluded_products_2 );
		}
		public function wwp_get_products_multi( $role ) {
			$excluded_products_1 = array();
			$excluded_products_2 = array();
			$args = array(
				'post_type'      => array('product'),
				'posts_per_page' => -1,
				'fields'		 => 'ids',
				'meta_query' =>  array(
					array(
						'key'     => 'wholesale_product_visibility_multi',
						'value'     => sprintf(':"%s";', $role),
						'compare' => 'LIKE'
					)
				)
			);
			$excluded_products_1 = get_posts($args);
			$args = array(
				'hide_empty' => true,
				'fields'		 => 'ids',
				'posts_per_page' => -1,
				'meta_query' => array(
					array(
					   'key'       => 'wholesale_product_visibility_multi',
					   'value'     => sprintf(':"%s";', $role),
					   'compare'   => 'LIKE'
					)
				),
				'taxonomy'  => 'product_cat'
			);
			$terms = get_terms( $args );
			if ( !empty($terms) ) {
				$args = array(
					'post_type'      => array('product'),
					'posts_per_page' => -1,
					'fields'		 => 'ids',
					'tax_query' => array(
						array(
							'taxonomy' => 'product_cat',
							'terms' => $terms,
							'operator' => 'IN',
						)
					),
				);
				$excluded_products_2 = get_posts($args);
			}
			return array_merge( $excluded_products_1, $excluded_products_2 );
		}
		public function wwp_coupons_disabled () {
			$user_role = $this->wwp_get_current_user_role();
			$wholesale_role = term_exists($user_role, 'wholesale_user_roles');
			if ( ! $wholesale_role ) {
				return false;
			}
			$settings = get_option('wwp_vendor_portal_options', true);
			if ( isset($settings['wholesale_role']) && 'multiple' == $settings['wholesale_role'] && 'yes' == get_term_meta($wholesale_role['term_id'], 'wwp_wholesale_disable_coupons', true) ) {
				return true;
			} elseif ( isset($settings['wholesale_role']) && 'single' == $settings['wholesale_role'] && 'yes' == get_term_meta($wholesale_role['term_id'], 'wwp_wholesale_disable_coupons', true) ) {
				return true;
			} else {
				return false;
			}
		}
		public function wwp_get_current_user_role() {
			if ( !is_user_logged_in() ) {
				return false;
			}
			$user = get_userdata(get_current_user_id());
			$user_role = array_shift($user->roles);
			return $user_role;
		}
		public function wwp_get_excluded_products() {
			if ( !is_user_logged_in() ) {
				return array();
			}
			$user_role = $this->wwp_get_current_user_role();
			if ( ! term_exists($user_role, 'wholesale_user_roles') ) {
				return array();
			}
			$settings = get_option('wwp_vendor_portal_options', true);
			if ( isset($settings['wholesale_role']) && 'multiple' == $settings['wholesale_role'] ) {
				$this->excluded = $this->wwp_get_products_multi( $user_role );
			} elseif ( isset($settings['wholesale_role']) && 'single' == $settings['wholesale_role'] ) {
				$this->excluded = $this->wwp_get_products_single();
			} else {
				$this->excluded = array();
			}
		}
	}
	new Wwp_Wholesale_Products_Visibility();
}
