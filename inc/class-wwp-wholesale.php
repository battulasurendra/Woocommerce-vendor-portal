<?php
if (! defined('ABSPATH') ) {
	exit; // Exit if accessed directly
}
/**
 * Class To Add Wholesale Functionality with WooCommerce
 */
if (!class_exists('WWP_Easy_Wholesale')) {
	class WWP_Easy_Wholesale {
		
		public $exclude_ids = array();
		
		public function __construct() {
			
			if ( $this->is_wholesaler_user(get_current_user_id()) ) {
				add_filter('woocommerce_get_price_html', array($this, 'wwp_change_product_price_display'), 200, 2 );
				add_filter('woocommerce_cart_item_price', array($this, 'wwp_change_product_price_display'), 200, 2 );
			
				add_action('wp_ajax_wwp_variation', array($this, 'wwp_variation_change_callback'));
				add_action('wp_ajax_nopriv_wwp_variation', array($this, 'wwp_variation_change_callback'));
				add_filter('woocommerce_variable_sale_price_html', array($this, 'wwp_variable_price_format'), 200, 2);
				add_filter('woocommerce_variable_price_html', array($this, 'wwp_variable_price_format'), 200, 2);
				
				add_action('pre_get_posts', array($this, 'wwp_default_wholesaler_products_only'));
				add_action('woocommerce_shortcode_products_query', array($this, 'woocommerce_shortcode_products_query'), 200, 1);
				add_action('woocommerce_products_widget_query_args', array($this, 'woocommerce_shortcode_products_query'), 200, 1);
				add_action('init', array($this, 'wwp_default_settings'));
				add_filter('woocommerce_product_get_price', array($this, 'wwp_regular_price_change'), 200, 2);
				add_filter('woocommerce_product_get_regular_price', array($this, 'wwp_regular_price_change'), 200, 2);
				
				add_filter('woocommerce_product_variation_get_price', array($this, 'wwp_variation_price_change') , 200, 2 );
				add_filter('woocommerce_product_variation_get_regular_price', array($this, 'wwp_variation_price_change'), 200, 2 );
				add_filter( 'woocommerce_available_payment_gateways', array($this, 'wwp_available_payment_gateways'), 10, 1 );
				add_filter('woocommerce_package_rates', array($this, 'wwp_restrict_shipping_wholesaler'), 200, 2);
				add_filter( 'woocommerce_add_to_cart_validation', array($this, 'add_the_qty_validation'), 200, 5 );
				 
				 
				add_action('woocommerce_before_calculate_totals', array($this, 'wwp_override_product_price_cart'), 200 );
				add_action('woocommerce_after_calculate_totals', array($this, 'wwp_override_price_filter_on'), 200 ); 
				
				add_filter( 'woocommerce_available_variation', array( $this, 'filter_woocommerce_available_variation' ), 200, 3 );
				add_filter( 'woocommerce_cart_crosssell_ids', array( $this , 'remove_non_wholesale_product_crosssell' ), 10, 2 );
				
				// cart display html fixed
				add_filter( 'woocommerce_before_cart', array( $this , 'woocommerce_before_cart' ), 200);
				// cart display html fixed
				add_filter( 'woocommerce_after_cart_table', array( $this , 'woocommerce_after_cart_table' ), 200);
			 
			}
		}
		
		public function woocommerce_before_cart() {
			remove_filter('woocommerce_get_price_html', array($this, 'wwp_change_product_price_display'), 200, 2 );
			remove_filter('woocommerce_variable_sale_price_html', array($this, 'wwp_variable_price_format'), 200, 2);
			remove_filter('woocommerce_variable_price_html', array($this, 'wwp_variable_price_format'), 200, 2);
			remove_filter('woocommerce_cart_item_price', array($this, 'wwp_change_product_price_display'), 200, 2 );
		}
		
		public function woocommerce_after_cart_table() {
			add_filter('woocommerce_get_price_html', array($this, 'wwp_change_product_price_display'), 200, 2 );
			add_filter('woocommerce_variable_sale_price_html', array($this, 'wwp_variable_price_format'), 200, 2);
			add_filter('woocommerce_variable_price_html', array($this, 'wwp_variable_price_format'), 200, 2);
			add_filter('woocommerce_cart_item_price', array($this, 'wwp_change_product_price_display'), 200, 2 );
		}
		
		public function remove_non_wholesale_product_crosssell( $cross_sells, $cart ) {
			if (!empty( $cross_sells )) {
				return array_diff( $cross_sells, $this->exclude_ids );
			}
			return $cross_sells;
		}
		
		public function filter_woocommerce_available_variation( $variation_get_max_purchase_quantity, $instance, $variation ) { 
			if ( !$this->is_wholesaler_user(get_current_user_id()) ) {
				return $variation_get_max_purchase_quantity;
			}
			global $product,$wpdb,$post;
			$post_id = wp_get_post_parent_id( $variation_get_max_purchase_quantity['variation_id'] );
			
			 
			$data = get_post_meta(wp_get_post_parent_id($variation_get_max_purchase_quantity['variation_id']), '_wwp_enable_wholesale_item', true);
			
			if ( 'yes' == $data ) {
				$min_quantity = get_post_meta($variation_get_max_purchase_quantity['variation_id'], '_wwp_wholesale_min_quantity', true);
				
				//	$variation_get_max_purchase_quantity['price_html'] = '<span class="price"><ins><span class="woocommerce-Price-amount amount">' . wc_price($variation_get_max_purchase_quantity['display_regular_price'] ) . ' </span></ins></span>';
				 
				if ( $min_quantity && 1 != $min_quantity ) {
					/* translators: %1$s is replaced with "string" */
					$variation_get_max_purchase_quantity['availability_html'] .= apply_filters( 'wwp_product_minimum_quantity_text', '<p style="font-size: 10px;">' . sprintf(esc_html__('Wholesale price will only be applied to a minimum quantity of %1$s products', 'woocommerce-wholesale-pricing'), $min_quantity) . '</p>' , $min_quantity );
					return $variation_get_max_purchase_quantity; 
				}
			 
			}
			
			$terms = get_the_terms ( $post_id, 'product_cat' );
			if ( !is_wp_error($terms) && !empty($terms) ) {
				foreach ( $terms as $term ) {
				 
					$data=get_term_meta($term->term_id, '_wwp_enable_wholesale_item', true);
					if ( 'yes' == $data ) {
					 
						$min_quantity = get_term_meta($term->term_id, '_wwp_wholesale_min_quantity', true);
						//$variation_get_max_purchase_quantity['price_html'] = '<span class="price"><ins><span class="woocommerce-Price-amount amount"> ' . wc_price($variation_get_max_purchase_quantity['display_regular_price'] ) . ' </span></ins></span>';
						if ( $min_quantity && 1 != $min_quantity ) {
							/* translators: %s: minimum quanity to apply wholesale */
							$variation_get_max_purchase_quantity['availability_html'].= apply_filters( 'wwp_product_minimum_quantity_text', '<p style="font-size: 10px;">' . sprintf(esc_html__('Wholesale price will only be applied to a minimum quantity of %1$s products', 'woocommerce-wholesale-pricing'), $min_quantity) . '</p>' , $min_quantity );
							return $variation_get_max_purchase_quantity; 	  
						
						}
					}
				}
			}
			
			$data=get_option('_wwp_enable_wholesale_item');
			
			if (  'yes' == $data  ) {
				
				$min_quantity = (int) get_option('_wwp_wholesale_min_quantity');
				//$variation_get_max_purchase_quantity['price_html'] = '<span class="price"><ins><span class="woocommerce-Price-amount amount"> ' . wc_price($variation_get_max_purchase_quantity['display_regular_price'] ) . ' </span></ins></span>';
				if ( $min_quantity && 1 != $min_quantity ) {
					 /* translators: %s: minimum quanity to apply wholesale */
					$variation_get_max_purchase_quantity['availability_html'] .= apply_filters( 'wwp_product_minimum_quantity_text', '<p style="font-size: 10px;">' . sprintf(esc_html__('Wholesale price will only be applied to a minimum quantity of %1$s products', 'woocommerce-wholesale-pricing'), $min_quantity ) . '</p>' , $min_quantity  );
					return $variation_get_max_purchase_quantity; 

				}
			}
			
			return $variation_get_max_purchase_quantity; 	
		}
		
		public function wwp_override_price_filter_on() {
		 
			add_filter( 'woocommerce_product_get_price', array($this,'wwp_regular_price_change'), 200, 2 );
			add_filter( 'woocommerce_product_variation_get_price', array($this,'wwp_variation_price_change') , 200, 2 );
			add_filter( 'woocommerce_get_price_html', array($this, 'wwp_change_product_price_display'), 200 , 2 );
			add_filter( 'woocommerce_cart_item_price', array($this, 'wwp_change_product_price_display'), 200 , 2 );
			add_filter( 'woocommerce_product_get_regular_price', array($this, 'wwp_regular_price_change'), 200, 2 );
			add_filter( 'woocommerce_product_variation_get_regular_price', array($this, 'wwp_variation_price_change'), 200, 2 );
		
		}
		
		public function add_the_qty_validation( $passed, $product_id, $quantity, $variation_id = '', $variations = '' ) { 
			
			if ( $this->is_wholesale($product_id) ) {
					
				$settings = get_option('wwp_vendor_portal_options', true);	
				if ( 'yes' == $settings['wholesaler_allow_minimum_qty'] ) {
						
					if ( !empty($variation_id) ) {
						$enable_wholesale_item = get_post_meta($product_id, '_wwp_enable_wholesale_item', true);
						if ( 'yes' == $enable_wholesale_item ) {
						
							$min_quantity = get_post_meta($variation_id, '_wwp_wholesale_min_quantity', true);
							if ( empty($min_quantity) || !isset($min_quantity) ) {
								$min_quantity = 1;
							}
							if ($min_quantity > $quantity ) {
								/* translators: %s: minimum quanity to apply wholesale */
								wc_add_notice( __( apply_filters( 'wwp_product_minimum_quantity_text', sprintf(esc_html__('Wholesale price will only be applied to a minimum quantity of %1$s products', 'woocommerce-wholesale-pricing'), $min_quantity) , $min_quantity ), 'woocommerce' ), 'error' );
								return  false; 
								 
							} else { 
								return  true;  
							}
							
						} 
					} else {
						
						$enable_wholesale_item = get_post_meta($product_id, '_wwp_enable_wholesale_item', true);
						if ( 'yes' == $enable_wholesale_item ) {
							$min_quantity = (int) get_post_meta($product_id, '_wwp_wholesale_min_quantity', true);
							if ( empty($min_quantity) || !isset($min_quantity) ) {  
								$min_quantity = 1;
							}
							
							if ($min_quantity > $quantity ) {
								/* translators: %s: minimum quanity to apply wholesale */	 
								wc_add_notice( __( apply_filters( 'wwp_product_minimum_quantity_text', sprintf(esc_html__('Wholesale price will only be applied to a minimum quantity of %1$s products', 'woocommerce-wholesale-pricing'), $min_quantity) , $min_quantity ), 'woocommerce' ), 'error' );
								return  false; 
									 
							} else { 
								return  true;  
							}
						}
					}
					
					$terms = get_the_terms ( $product_id, 'product_cat' );
					
					if ( !is_wp_error($terms) && !empty($terms) ) {
						foreach ( $terms as $term ) {
						
							if ( 'yes' == get_term_meta($term->term_id, '_wwp_enable_wholesale_item', true) ) {
								
								$min_quantity = get_term_meta($term->term_id, '_wwp_wholesale_min_quantity', true) ;
								 
								if ( empty($min_quantity) || !isset($min_quantity) ) {  
									$min_quantity = 1;
								}
								if ($min_quantity > $quantity ) {
									 
									/* translators: %s: minimum quanity to apply wholesale */	  
									wc_add_notice( __( apply_filters( 'wwp_product_minimum_quantity_text', sprintf(esc_html__('Wholesale price will only be applied to a minimum quantity of %1$s products', 'woocommerce-wholesale-pricing'), $min_quantity) , $min_quantity ), 'woocommerce' ), 'error' );
									return  false; 
									 
								} else { 
									return  true;
								}
							
							}
						}
					}	
					
					$enable_wholesale_item = get_option('_wwp_enable_wholesale_item');
					
					if ( 'yes' == $enable_wholesale_item ) {
					
						$min_quantity = get_option('_wwp_wholesale_min_quantity') ;
					 
						if ( empty($min_quantity) || !isset($min_quantity) ) {
							$min_quantity = 1;
						}
						if ($min_quantity > $quantity ) {
							/* translators: %s: minimum quanity to apply wholesale */
							wc_add_notice( __( apply_filters( 'wwp_product_minimum_quantity_text', sprintf(esc_html__('Wholesale price will only be applied to a minimum quantity of %1$s products', 'woocommerce-wholesale-pricing'), $min_quantity) , $min_quantity ), 'woocommerce' ), 'error' );
							return  false; 
							 
						} else { 
							return  true;  
						}
					}	
				}
			}

			return $passed;
		
		}

		public function wwp_available_payment_gateways( $available_gateways ) {
			global $woocommerce;
			$user_info = get_userdata(get_current_user_id());
			$user_roles = (array) $user_info->roles;
			if ( empty($user_roles) ) {
				return $available_gateways;
			}
			if ( !in_array('default_wholesaler', $user_roles) ) {
				return $available_gateways;
			}
			$term = get_term_by('slug', 'default_wholesaler', 'wholesale_user_roles');
			if ( !empty($term) && !is_wp_error($term) ) {
				$restricted = get_term_meta($term->term_id, 'wwp_restricted_pmethods_wholesaler', true); 
				
				if ( !empty($restricted) && !empty($available_gateways) ) {
					foreach ( $restricted as $restrict ) {
						unset($available_gateways[$restrict]);
					}
				}
			}
			return $available_gateways;
		}
		public function wwp_restrict_shipping_wholesaler ( $rates, $package ) {
			if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
				return;
			}
			$user_info = get_userdata(get_current_user_id());
			$user_roles = (array) $user_info->roles;
			if ( empty($user_roles) ) {
				return $rates;
			}
			if ( !in_array('default_wholesaler', $user_roles) ) {
				return $available_gateways;
			}
			$term = get_term_by('slug', 'default_wholesaler', 'wholesale_user_roles');
			if ( !empty($term) && !is_wp_error($term) ) {
				$restricted = get_term_meta($term->term_id, 'wwp_restricted_smethods_wholesaler', true); 
				if ( !empty($restricted) && !empty($rates) ) {
					//$rates = array_diff_key($rates, $restricted);
					foreach ( $rates as $rate_key => $rate ) {
						if ( in_array ($rate->method_id , $restricted) ) {
							unset($rates[$rate_key]); // Remove shipping method
						}
					}
				}
			}
			return $rates;
		}
		public function wwp_regular_price_change( $price, $product ) { 
			wc_delete_product_transients($product->get_id());
			$qty='';
			if ( is_cart() || is_checkout() ) {
				foreach ( WC()->cart->get_cart() as $cart_item ) { 
					if ( $cart_item['product_id'] == $product->get_id() ) {
						$qty =  $cart_item['quantity'];
						break; // stop the loop if product is found
					}
				}
			}
			 
			if ( !$this->is_wholesale($product->get_id()) ) {
				return $price;
			}
			
			if ( is_cart() || is_checkout() ) {
				return $price;
			}
			
			
			$wholesale_qty = $this->get_wholesale_qty( $product->get_id() );
			if ( '' != $qty && $qty < $wholesale_qty ) {
				return $price;
			}
			if ( 'product' == get_post_type($product->get_id()) ) {
				$price = $this->get_wholesale_price($product->get_id());
			}
			
			
			return $price;
		}
		public function wwp_variation_price_change( $price, $variation ) { 
			global $woocommerce;
			$variation_id = $variation->get_id();
			$product_id = wp_get_post_parent_id($variation_id);
			$qty='';
			if ( is_cart() || is_checkout() ) {
				foreach ( WC()->cart->get_cart() as $cart_item ) { 
					if ( $cart_item['variation_id'] == $variation_id ) {
						$qty =  $cart_item['quantity'];
						break; // stop the loop if product is found
					}
				}
			}
			if ( is_cart() || is_checkout() ) {
				return $price;
			}
			
			$wholesale_qty = get_post_meta($variation_id, '_wwp_wholesale_min_quantity', true);
			if ( '' != $qty && $qty <= $wholesale_qty ) {
				return $price;
			}
			if ($this->get_variable_wholesale_price ( $variation_id, $product_id )) {
				$price = $this->get_variable_wholesale_price ( $variation_id, $product_id );
			}
			
			return $price;
		}
		public function wwp_change_product_price_display( $price, $product ) { 
			global $woocommerce;
			if ( is_array($product) ) {
				
				$post_id = $product['product_id'];
				$product = wc_get_product($post_id);
				
			}
			
			$post_id = $product->get_id();
			
			// if ( is_cart()) {
				// return $price;
			// }
			
			if ( ( !empty($product) && 'object' == gettype($product) ) && !$product->is_type('simple') ) {
				if ( 'product_variation' == get_post_type( $product->get_id() ) ) {
					if ( $woocommerce->customer->is_vat_exempt() ) { 
						return wc_price($product->get_regular_price());
					} else {
						return $price;
					}
				} else {
						return $price;
				}
			}
			 
			if ( !$this->is_wholesale($post_id) ) {
				return $price;
			} 
			
			$original_price = get_post_meta( $post_id, '_regular_price', true );
			
			$enable_wholesale = get_post_meta($post_id, '_wwp_enable_wholesale_item', true);
			$min_quantity = get_post_meta( $post_id, '_wwp_wholesale_min_quantity', true);
			if ( empty( $enable_wholesale ) ) {
				$terms = get_the_terms ( $post_id, 'product_cat' );
			
				if ( !is_wp_error($terms) && !empty($terms) ) {
					foreach ( $terms as $term ) { 
						
						if ( 'yes' == get_term_meta( $term->term_id, '_wwp_enable_wholesale_item', true ) ) {
							$enable_wholesale = 'yes';
							$min_quantity =  get_term_meta($term->term_id , '_wwp_wholesale_min_quantity', true ) ;	 
						}
					}
				}
			}
			
			if ( empty($enable_wholesale) && 'yes' == get_option('_wwp_enable_wholesale_item') ) {
				$enable_wholesale = 'yes';
				$min_quantity = get_option('_wwp_wholesale_min_quantity') ;
			}
			
			if ( empty($enable_wholesale) ) {
				return $price;
			}
			
			$r_price = get_post_meta( $post_id, '_regular_price', true );
			$wholesale_price = $product->get_regular_price();
			
			if ( !is_numeric($wholesale_price) || !is_numeric($r_price) ) {
				return $price;
			}
			
			if ( '0' == $r_price ) {
				return $price;
			}
			
			
			// if ( false == $woocommerce->customer->is_vat_exempt() ) {
				// $r_price = wc_get_price_including_tax( $product, array('price' => $r_price ) );
				// $original_price = wc_get_price_including_tax( $product, array('price' => $original_price ) );
				// $wholesale_price = wc_get_price_including_tax( $product, array('price' => $wholesale_price ) );
			// }
			
			 
			// Tax display suffix function call 
			$tax_display_suffix = wwp_get_tax_price_display_suffix( $post_id );
			
			$r_price 			 = wwp_get_price_including_tax( $product, array('price' => $r_price ) );
			$original_price  	 = wwp_get_price_including_tax( $product, array('price' => $original_price ) );
			$wholesale_price	 = wwp_get_price_including_tax( $product, array('price' => $wholesale_price ) );
			
			

			$saving_amount = ( $r_price - $wholesale_price );
			$saving_amount = number_format( ( float ) $saving_amount, 2, '.', '' );
			$saving_percent = ( $r_price - $wholesale_price ) / $r_price * 100;
			$saving_percent = number_format( ( float ) $saving_percent, 2, '.', '' );
			
			$html = '';
			$settings = get_option('wwp_vendor_portal_options', true);
			 
			$actual = ( isset( $settings['retailer_label'] ) && !empty( $settings['retailer_label'] ) ) ? $settings['retailer_label'] : esc_html__('Actual', 'woocommerce-wholesale-pricing');
			$save = ( isset( $settings['save_label'] ) && !empty( $settings['save_label'] ) ) ? $settings['save_label'] : esc_html__('Save', 'woocommerce-wholesale-pricing');
			$new = ( isset( $settings['wholesaler_label'] ) && !empty( $settings['wholesaler_label']) ) ? $settings['wholesaler_label'] : esc_html__('New', 'woocommerce-wholesale-pricing');
			if ( !empty($wholesale_price) ) {
				$html = do_action('wwp_before_pricing');
				$html .= '<div class="wwp-wholesale-pricing-details">';
				
				if ( 'yes' != $settings['retailer_disabled'] ) {
					$html .= '<p><span class="retailer-text">' . esc_html__($actual, 'woocommerce-wholesale-pricing') . '</span>: <s>' . wc_price( $original_price ) . ' ' . $tax_display_suffix . '</s></p>';
				}
				
				$html .= '<p><span class="price-text">' . esc_html__($new, 'woocommerce-wholesale-pricing') . '</span>: ' . wc_price( $wholesale_price ) . ' ' . $tax_display_suffix . '</p>';
				
				if ( 'yes' != $settings['save_price_disabled'] ) {
					$html .= '<p><b><span class="save-price-text">' . esc_html__($save, 'woocommerce-wholesale-pricing') . '</span>: ' . wc_price( $saving_amount ) . ' (' . round($saving_percent) . '%)</b></p>';
				}
				
				if ( $min_quantity > 1 ) {
					if ( $product->get_type() == 'simple') {
						/* translators: %s: minimum quanity to apply wholesale */
						$html .= apply_filters( 'wwp_product_minimum_quantity_text', '<p style="font-size: 10px;">' . sprintf(esc_html__('Wholesale price will only be applied to a minimum quantity of %1$s products', 'woocommerce-wholesale-pricing'), $min_quantity) . '</p>' , $min_quantity );
					}
				}
				$html .= '</div>';
				$html .= do_action('wwp_after_pricing');
			}
			return $html;
		}
		public function wwp_override_product_price_cart( $_cart ) {
			global $woocommerce;
			$items = $woocommerce->cart->get_cart();
			
			
			remove_filter( 'woocommerce_product_get_price', array($this,'wwp_regular_price_change'), 200, 2);
			remove_filter('woocommerce_product_variation_get_price', array($this,'wwp_variation_price_change') , 200, 2 );
			remove_filter('woocommerce_get_price_html', array($this, 'wwp_change_product_price_display'), 200 );
			remove_filter('woocommerce_cart_item_price', array($this, 'wwp_change_product_price_display'), 200 );
			remove_filter('woocommerce_product_get_regular_price', array($this, 'wwp_regular_price_change'), 200, 2);
			remove_filter('woocommerce_product_variation_get_regular_price', array($this, 'wwp_variation_price_change'), 200, 2 );
			
			foreach ( $_cart->cart_contents as $item ) {
				if ( $this->is_wholesale($item['product_id']) ) {
					
					$return_check = false;
					$variation_id = $item['variation_id'];
					
					if ( !empty($variation_id) ) {
 

						if ( 'yes' == get_post_meta( $item['product_id'], '_wwp_enable_wholesale_item', true ) ) {
							
							$min_quantity = get_post_meta( $variation_id, '_wwp_wholesale_min_quantity', true );
							
						} else {
							
							$min_quantity = $this->get_wholesale_qty($item['product_id']);
						}

						if ( empty($min_quantity) || !isset($min_quantity) ) {  
							$min_quantity = 1;
						}
					
						if ( $min_quantity <= $item['quantity'] ) {
								
							if ( !empty( $this->get_variable_wholesale_price( $variation_id, $item['product_id']) ) ) {
								$item['data']->set_price($this->get_variable_wholesale_price($variation_id, $item['product_id']));
							}
							
						} else {
								$item['data']->set_price(get_post_meta($item['variation_id'], '_regular_price', true));
						}
						 
					} else {
						
						if ( 'yes' == get_post_meta( $item['product_id'], '_wwp_enable_wholesale_item', true ) ) {
							
							$min_quantity = get_post_meta( $item['product_id'], '_wwp_wholesale_min_quantity', true );
							
						} else {
							
							$min_quantity = $this->get_wholesale_qty($item['product_id']);
							
						}
						
						if ( empty($min_quantity) || !isset($min_quantity) ) { // IF MIN QUANTITY NOT SET OR DOESN't EXIST ON DB
							$min_quantity = 1;
						}
						if ( $min_quantity <= $item['quantity'] ) {
							if ( !empty($this->get_wholesale_price($item['product_id'])) ) {
								$item['data']->set_price($this->get_wholesale_price($item['product_id']));
							}
						} else {
								$item['data']->set_price(get_post_meta($item['product_id'], '_regular_price', true));
						}
					
					
					
					}
				}
			}
		}
		
		public function is_wholesale ( $product_id ) {
			$enable_wholesale = get_post_meta($product_id, '_wwp_enable_wholesale_item', true);
			if ( !empty($enable_wholesale) ) {
				return true;
			}
			$terms = get_the_terms ( $product_id, 'product_cat' );
			if ( !is_wp_error($terms) && !empty($terms) ) {
				foreach ( $terms as $term ) {
					$cat_id = $term->term_id;
					if ( 'yes' == get_term_meta($term->term_id, '_wwp_enable_wholesale_item', true) ) {
						return true;
					}
				}
			}
			if ( 'yes' == get_option('_wwp_enable_wholesale_item') ) {
				return true;
			}
			return false;
		}

		public function get_wholesale_qty ( $product_id ) {
			$quanity = '';
			$enable_wholesale = get_post_meta($product_id, '_wwp_enable_wholesale_item', true);
			if ( !empty($enable_wholesale) ) {
				$quanity = get_post_meta($product_id, '_wwp_wholesale_min_quantity', true);
			}
			if ( '' == $quanity ) {
				$terms = get_the_terms ( $product_id, 'product_cat' );
				foreach ( $terms as $term ) {
					if ( 'yes' == get_term_meta($term->term_id, '_wwp_enable_wholesale_item', true) ) {
						$quanity = get_term_meta($term->term_id, '_wwp_wholesale_min_quantity', true);
						break;
					}
				}
			}
			if ( '' == $quanity ) {
				if ( 'yes' == get_option('_wwp_enable_wholesale_item') ) {
					$quanity = get_option('_wwp_wholesale_min_quantity');
				}
			}
			return $quanity;
		}
		public function get_wholesale_price ( $product_id ) {  
			$wholesale_price = '';
			$wholesale_amount_type = '';
			$enable_wholesale = get_post_meta($product_id, '_wwp_enable_wholesale_item', true);
			if ( !empty($enable_wholesale) ) {
				$wholesale_price = get_post_meta($product_id, '_wwp_wholesale_amount', true);
				$wholesale_amount_type = get_post_meta($product_id, '_wwp_wholesale_type', true);
			}
			if ( empty($wholesale_price) && empty($wholesale_amount_type) ) {
				$terms = get_the_terms ( $product_id, 'product_cat' );
				foreach ( $terms as $term ) {
					if ( 'yes' == get_term_meta($term->term_id, '_wwp_enable_wholesale_item', true) ) {
						$wholesale_price = get_term_meta($term->term_id, '_wwp_wholesale_amount', true);
						$wholesale_amount_type = get_term_meta($term->term_id, '_wwp_wholesale_type', true);
						break;
					}
				}
			}
			if ( empty($wholesale_price) && empty($wholesale_amount_type) ) {
				if ( 'yes' == get_option('_wwp_enable_wholesale_item') ) {
					$wholesale_price = get_option('_wwp_wholesale_amount');
					$wholesale_amount_type = get_option('_wwp_wholesale_type');
				}
			}
			if ( !empty($wholesale_price) && !empty($wholesale_amount_type) ) {
				if ( 'fixed' == $wholesale_amount_type ) {
					return $wholesale_price;
				} else {
					$product_price = get_post_meta($product_id, '_regular_price', true);
					if ( empty($wholesale_price) ||  '' == $wholesale_price ) { 
						$wholesale_price = 0;
					}
					$product_price = ( isset($product_price) && is_numeric($product_price) ) ? $product_price : 0;
					$wholesale_price = $product_price * $wholesale_price / 100;
					return $wholesale_price;
				}
			}
		}
		public function get_variable_wholesale_price ( $variation_id, $product_id = '' ) { 
			
			if ( empty($product_id) ) {
				$product_id = get_the_ID();
			}
			if ( 'yes' == get_post_meta($product_id, '_wwp_enable_wholesale_item', true) ) {
				$wholesale_price = get_post_meta($variation_id, '_wwp_wholesale_amount', true);
				$wholesale_amount_type = get_post_meta($product_id, '_wwp_wholesale_type', true);
			}
			if ( empty($wholesale_price) && empty($wholesale_amount_type) ) {
				$terms = get_the_terms ( $product_id, 'product_cat' ); 
				foreach ( $terms as $term ) {
					if ( 'yes' == get_term_meta($term->term_id, '_wwp_enable_wholesale_item', true) ) {
						$wholesale_price = get_term_meta($term->term_id, '_wwp_wholesale_amount', true);
						$wholesale_amount_type = get_term_meta($term->term_id, '_wwp_wholesale_type', true);
						break;
					}
				}
			}
			
			if ( empty($wholesale_price) && empty($wholesale_amount_type) ) {
				if ( 'yes' == get_option('_wwp_enable_wholesale_item') ) {
					$wholesale_price = get_option('_wwp_wholesale_amount');
					$wholesale_amount_type = get_option('_wwp_wholesale_type');
				}
			}
			
			if ( !empty($wholesale_price) && !empty($wholesale_amount_type) ) { 
				if ( 'fixed' == $wholesale_amount_type ) { 
					return $wholesale_price;
				} else {
					$product_price = get_post_meta($variation_id, '_regular_price', true);
					$product_price= ( isset($product_price) && is_numeric($product_price) ) ? $product_price : 0;
					$wholesale_price= ( isset($wholesale_price) && is_numeric($wholesale_price) ) ? $wholesale_price : 0;
					$wholesale_price = $product_price * $wholesale_price / 100;
					return $wholesale_price;
				}
			}
		}
		public function is_wholesaler_user ( $user_id) { 
			if ( !empty($user_id) ) {
				$user_info = get_userdata($user_id);
			 
				$user_role = implode(', ', $user_info->roles);
				
				$allterms = get_terms('wholesale_user_roles', array('hide_empty' => false));
				
				if ( 'default_wholesaler' == $user_role ) {
					return true;
				}
				foreach ( $allterms as $allterm_key => $allterm_value ) {
					if ( $user_role == $allterm_value->slug ) {
						return true;
					}
				}
			}
			return false;
		}
		
		public function wwp_variation_change_callback () {
			if ( !isset($_POST['wwp_variation_nonce']) || !wp_verify_nonce( wc_clean($_POST['wwp_variation_nonce']), 'wwp_variation_nonce') ) {
				return;
			}
			$variation_id = isset( $_POST['variation_id'] ) ? absint( $_POST['variation_id'] ) : '';
			$variation_price = isset( $_POST['variation_price'] ) ? wc_clean( $_POST['variation_price'] ) : '';
			$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : '';
 
			$variable_wholesale_price = $this->get_variable_wholesale_price($variation_id, $product_id);
			$html = '<s>' . esc_html($variation_price) . '</s>';
			$html .= '<span class="price"><span class="woocommerce-Price-amount amount">' . wc_price($variable_wholesale_price) . '</span></span>';
			$min_quantity = get_post_meta($variation_id, '_wwp_wholesale_min_quantity', true);
			if ( $min_quantity > 1 ) {
				if ( $product->get_type() == 'simple') {
					/* translators: %s: minimum quanity to apply wholesale */
					$html .= apply_filters( 'wwp_product_minimum_quantity_text', '<p style="font-size: 10px;">' . sprintf(esc_html__('Wholesale price will only be applied to a minimum quantity of %1$s products', 'woocommerce-wholesale-pricing'), $min_quantity) . '</p>' , $min_quantity );
				}
			}
			echo wp_kses_post($html);
			die();
		}	
		public function wwp_variable_price_format( $price, $product ) { 
			global $woocommerce;
			$prod_id = $product->get_id();
			$return_check = false;
			$product_variations = $product->get_children();
			$wholesale_product_variations = array();
			$original_variation_price = array();
			
			$min_quantity = get_post_meta( $prod_id, '_wwp_wholesale_min_quantity', true);
			
			foreach ( $product_variations as $product_variation ) {
				
				if ( empty( $this->get_variable_wholesale_price($product_variation, $prod_id) ) ) {
					return$price;
				}
				$wholesale_product_variations[] =  $this->get_variable_wholesale_price($product_variation, $prod_id);
				$original_variation_price[] = get_post_meta($product_variation, '_regular_price', true);
			}
			sort($wholesale_product_variations);
			sort($original_variation_price);
			
			// orignal price add tax
			$original_variation_price[0] = wwp_get_price_including_tax( $product, array('price' => $original_variation_price[0] ) );
			$original_variation_price[count($original_variation_price) - 1] = wwp_get_price_including_tax( $product, array('price' => $original_variation_price[count($original_variation_price) - 1] ) );
			
			// wholesale price add tax
			$wholesale_product_variations[0] = wwp_get_price_including_tax( $product, array('price' => $wholesale_product_variations[0] ) );

			$wholesale_product_variations[count($wholesale_product_variations) - 1] = wwp_get_price_including_tax( $product, array('price' => $wholesale_product_variations[count($wholesale_product_variations) - 1] ) );
			
			$min_wholesale_price = $wholesale_product_variations[0];
			$max_wholesale_price = $wholesale_product_variations[count($wholesale_product_variations) - 1];
			$min_original_variation_price = $original_variation_price[0];
			$max_original_variation_price = $original_variation_price[count($original_variation_price) - 1];
			if ( empty( $min_wholesale_price ) || '0' === $min_wholesale_price ) {
				return add_filter( 'woocommerce_is_purchasable', '__return_false');
			}
			$min_saving_amount = round ( ( $min_original_variation_price - $min_wholesale_price ) );
			$min_saving_percent = ( $min_original_variation_price - $min_wholesale_price ) / $min_original_variation_price * 100;
			$max_saving_amount = round ( ( $max_original_variation_price - $max_wholesale_price ) );
			$max_saving_percent = ( $max_original_variation_price - $max_wholesale_price ) / $max_original_variation_price * 100;
			
			$settings = get_option('wwp_vendor_portal_options', true);
			$actual = ( isset( $settings['retailer_label'] ) && !empty( $settings['retailer_label']) ) ? esc_html( $settings['retailer_label'] ) : esc_html__('Actual', 'woocommerce-wholesale-pricing');
			$save= ( isset( $settings['save_label'] ) && !empty( $settings['save_label']) ) ? esc_html($settings['save_label']) : esc_html__('Save', 'woocommerce-wholesale-pricing');
			$new= ( isset( $settings['wholesaler_label'] ) && !empty( $settings['wholesaler_label']) ) ? esc_html($settings['wholesaler_label']) : esc_html__('New', 'woocommerce-wholesale-pricing');
			$html = '<div class="wwp-wholesale-pricing-details">';
			
			if ( $woocommerce->customer->is_vat_exempt() ) {
			
				$wcv_max_price = $max_original_variation_price;
				$wcv_min_price = $min_original_variation_price;
			
			} else {
				 
				$wcv_max_price = $product->get_variation_regular_price( 'max', true );
				$wcv_min_price = $product->get_variation_regular_price( 'min', true );
			}
			
			// Tax display suffix function call 
			$tax_display_suffix = wwp_get_tax_price_display_suffix( $prod_id );
			
			if ( 'yes' != $settings['retailer_disabled'] ) {
				if ( $wcv_min_price == $wcv_max_price ) {
					$html .= '<p><span class="retailer-text">' . esc_html__($actual, 'woocommerce-wholesale-pricing') . '</span>: <s>' . wc_price($wcv_min_price) . ' ' . $tax_display_suffix . '</s></p>';
				} else {
					$html .= '<p><span class="retailer-text">' . esc_html__($actual, 'woocommerce-wholesale-pricing') . '</span>: <s>' . wc_price($wcv_min_price) . ' ' . $tax_display_suffix . ' - ' . wc_price($wcv_max_price) . ' ' . $tax_display_suffix . '</s></p>';
				}
			}
			//	$html .= '<p><span class="retailer-text">' . esc_html( $actual, 'woocommerce-wholesale-pricing' ) . '</span>: <s>' . $price . '</s></p>';
			if ( wc_price( $wholesale_product_variations[0] ) !== wc_price( $wholesale_product_variations[ count($wholesale_product_variations) - 1 ] ) ) {
				$html .= '<p><b><span class="price-text">' . esc_html__($new, 'woocommerce-wholesale-pricing') . '</span>: ' . wc_price($wholesale_product_variations[0]) . ' ' . $tax_display_suffix . ' - ' . wc_price($wholesale_product_variations[count($wholesale_product_variations) - 1]) . ' ' . $tax_display_suffix . '</b></p>';
			} else {
				$html .= '<p><b><span class="price-text">' . esc_html__($new, 'woocommerce-wholesale-pricing') . '</span>: ' . wc_price($wholesale_product_variations[0]) . ' ' . $tax_display_suffix . '</b></p>';
			}
			
			if ( 'yes' != $settings['save_price_disabled'] ) {
				if ( round( $min_saving_percent ) !== round( $max_saving_percent ) ) {
					$html .= '<p><b><span class="save-price-text">' . esc_html__($save, 'woocommerce-wholesale-pricing') . '</span>:  (' . round($min_saving_percent) . '% - ' . round($max_saving_percent) . '%) </b></p>';
				} else {
					$html .= '<p><b><span class="save-price-text">' . esc_html__($save, 'woocommerce-wholesale-pricing') . '</span>:  (' . round($min_saving_percent) . '%) </b></p>';	
				}
			}
			
			if ( $min_quantity > 1 ) {
				if ( $product->get_type() == 'simple') {
					/* translators: %s: minimum quanity to apply wholesale */
					$html .= apply_filters( 'wwp_product_minimum_quantity_text', '<p style="font-size: 10px;">' . sprintf(esc_html__('Wholesale price will only be applied to a minimum quantity of %1$s products', 'woocommerce-wholesale-pricing'), $min_quantity) . '</p>' , $min_quantity );
				}
			}
			$html .= '</div>';
			return $html;
		}
		
		 
		public function woocommerce_shortcode_products_query( $q ) {  
			$settings=get_option('wwp_vendor_portal_options', true);
			$wholesaler_prod_only = ( isset($settings['wholesaler_prodcut_only']) && 'yes' ==$settings['wholesaler_prodcut_only'] ) ? 'yes' : 'no';
			 
			if ( 'yes' == $wholesaler_prod_only  && !is_admin()  ) {
				
				if ( $this->is_wholesaler_user(get_current_user_id()) ) {
					
					if ( 'yes' != get_option('_wwp_enable_wholesale_item') ) {
					
						$cate =array();

						$categories = get_terms( array( 'taxonomy' => 'product_cat' ) );
						
						if ( is_array( $categories ) ) {
						
							foreach ( $categories as $category ) {
								 
								if ( 'yes' == get_term_meta($category->term_id, '_wwp_enable_wholesale_item', true) ) {
									$cate[] = $category->term_id;
								}
							}
							$args = array(
								'post_type'		=> 'product',
								 'fields' => 'ids',
								'tax_query'		=> array(
									array(
										'taxonomy'	=> 'product_cat',
										'field'    => 'term_id',
										'terms'     =>  $cate,
										'operator' => 'IN',
										
									)
								),
							);
							
							$ids = get_posts( $args );
							if ( is_array($ids) ) {
								foreach ($ids  as $id) {
										$total_ids[] =	$id ;
								}
								
							}
						}
						
						$all_ids = get_posts( array(
						'post_type' => 'product',
						'numberposts' => -1,
						'post_status' => 'publish',
						'fields' => 'ids',
						) );
						foreach ( $all_ids as $id ) {
							
							if ( 'yes' == get_post_meta($id, '_wwp_enable_wholesale_item', true)) {	
								$total_ids[] =	$id ;
							}
						}
						unset( $q['post__in'] );
						if ( is_array( $total_ids ) ) {
								$total_ids = 	array_unique(  $total_ids );
								$exclude_ids =	array_diff($all_ids, $total_ids);
								$post__not_in = isset($q['post__not_in']) ? (array) $q['post__not_in'] : array();
								$q['post__not_in'] = array_merge( $post__not_in, $exclude_ids);
						} else {
							$q['post__not_in'] = $all_ids ;
						}
					} 
				}
			}
			return $q;
		}
		
		public function wwp_default_wholesaler_products_only( $q ) {
			
			$settings=get_option('wwp_vendor_portal_options', true);
			
			$wholesaler_prod_only = ( isset($settings['wholesaler_prodcut_only']) && 'yes' == $settings['wholesaler_prodcut_only'] ) ? 'yes' : 'no';
		
			if ( 'yes' == $wholesaler_prod_only && $q->is_main_query() && !is_admin() ) {
				
				if ( $this->is_wholesaler_user(get_current_user_id()) ) {
					
					if ( 'yes' != get_option('_wwp_enable_wholesale_item') ) {
					
						$cate =array();

						$categories = get_terms( array( 'taxonomy' => 'product_cat' ) );
						
						if ( is_array( $categories ) ) {
						
							foreach ( $categories as $category ) {
								 
								if ( 'yes' == get_term_meta($category->term_id, '_wwp_enable_wholesale_item', true) ) {
									$cate[] = $category->term_id;
								}
							}
							$args = array(
								'post_type'		=> 'product',
								'numberposts' => -1,
								'fields' => 'ids',
								'tax_query'		=> array(
									array(
										'taxonomy'	=> 'product_cat',
										'field'    => 'term_id',
										'terms'     =>  $cate,
										'operator' => 'IN',
										
									)
								),
							);
							
							$ids = get_posts( $args );
							if ( is_array($ids) ) {
								foreach ($ids  as $id) {
										$total_ids[] =	$id ;
								}
								
							}
						}
						
						$all_ids = get_posts( array(
						'post_type' => 'product',
						'numberposts' => -1,
						'post_status' => 'publish',
						'fields' => 'ids',
						) );
						foreach ( $all_ids as $id ) {
							
							if ( 'yes' == get_post_meta($id, '_wwp_enable_wholesale_item', true)) {	
								$total_ids[] =	$id ;
							}
						}
						
						if ( is_array( $total_ids ) ) { 
						
							$total_ids = 	array_unique(  $total_ids );
							$exclude_ids =	array_diff($all_ids, $total_ids);
							$q->set('post__not_in', $exclude_ids );
							$this->exclude_ids = $exclude_ids;
							add_filter('woocommerce_related_products', array($this, 'exclude_related_products'), 10, 3 );
						} else {
							$q->set('post__not_in', $all_ids  );
						}
					 
					} 
				}
			}
		}

		public function exclude_related_products( $related_posts, $product_id, $args ) {
			 return array_diff( $related_posts, $this->exclude_ids );
		}

		public function wwp_default_settings () {
			if ( empty(get_option('wc_settings_tab_wholesale_retailer_label', true)) ) {
				update_option('wc_settings_tab_wholesale_retailer_label', esc_html__('RRP', 'woocommerce-wholesale-pricing'));
			}
			if ( empty(get_option('wc_settings_tab_wholesale_wholesaler_price_label', true)) ) {
				update_option('wc_settings_tab_wholesale_wholesaler_price_label', esc_html__('Your Price', 'woocommerce-wholesale-pricing'));
			}
			if ( empty(get_option('wc_settings_tab_wholesale_wholesaler_save_price_label', true)) ) {
				update_option('wc_settings_tab_wholesale_wholesaler_save_price_label', esc_html__('You Save', 'woocommerce-wholesale-pricing'));
			}
		}
	}
	new WWP_Easy_Wholesale();
}
