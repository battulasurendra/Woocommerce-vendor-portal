<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}
if ( !class_exists('WWP_Easy_Wholesale_Multiuser') ) {

	class WWP_Easy_Wholesale_Multiuser {
		
		public $exclude_ids = array();
		
		public function __construct() {
			
			if ( $this->is_wholesaler_user(get_current_user_id()) ) {
				add_filter('woocommerce_get_price_html', array($this, 'wwp_change_product_price_display'), 200, 2 );
				add_filter('woocommerce_cart_item_price', array($this, 'wwp_change_product_price_display'), 200, 2 );
	  
				add_filter('woocommerce_variable_sale_price_html', array($this, 'wwp_variable_price_format'), 200, 2);
				add_filter('woocommerce_variable_price_html', array($this, 'wwp_variable_price_format'), 200, 2);
				
				add_action('pre_get_posts', array($this, 'default_wholesaler_products_only'), 200, 1);
				add_action('woocommerce_shortcode_products_query', array($this, 'woocommerce_shortcode_products_query'), 200, 1);
				add_action('woocommerce_products_widget_query_args', array($this, 'woocommerce_shortcode_products_query'), 200, 1);
				
				add_filter('woocommerce_product_get_price', array($this, 'wwp_regular_price_change'), 200, 2);
				add_filter('woocommerce_product_get_regular_price', array($this, 'wwp_regular_price_change'), 200, 2);
				// Product variations (of a variable product)
				add_filter('woocommerce_product_variation_get_price', array($this,'wwp_regular_price_change') , 200, 2 );
				add_filter('woocommerce_product_variation_get_regular_price', array($this, 'wwp_regular_price_change'), 200, 2 ); 
				add_filter( 'woocommerce_available_payment_gateways', array($this, 'wwp_available_payment_gateways'), 200, 1 );
				add_filter('woocommerce_package_rates', array($this, 'wwp_restrict_shipping_wholesaler'), 100, 2);
				add_filter( 'woocommerce_add_to_cart_validation', array($this, 'add_the_date_validation'), 200, 5 );
				//add_filter( 'woocommerce_update_cart_validation', array($this, 'add_the_date_validation'), 200, 5 );
				
				add_action('woocommerce_before_calculate_totals', array($this, 'wwp_override_product_price_cart'), 200 );
				add_action('woocommerce_after_calculate_totals', array($this, 'wwp_override_price_filter_on'), 200 );
				
				add_filter( 'woocommerce_available_variation', array( $this , 'filter_woocommerce_available_variation' ), 200, 3 );
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
			if ( !empty( $cross_sells ) ) {
				return array_diff( $cross_sells, $this->exclude_ids );
			}
			return $cross_sells;
		}
		
		public function filter_woocommerce_available_variation( $variation_data, $instance, $variation ) { 
			global $product,$wpdb,$post;
			 
			if ( !$this->is_wholesaler_user(get_current_user_id()) ) {
				return $variation_data;
			}
			
			$role = $this->get_current_user_role_id();
			$product_id = wp_get_post_parent_id($variation_data['variation_id']);
			$data = get_post_meta($product_id, 'wholesale_multi_user_pricing', true);
			 
			if ( isset($data[$role][$variation_data['variation_id']]['qty']) ) {
				
				$min_quantity = (int) $data[$role][$variation_data['variation_id']]['qty'];
				
				if ( $min_quantity && 1 != $min_quantity ) {
					/* translators: %1$s is replaced with "string" */
					$variation_data['availability_html'] .= apply_filters( 'wwp_product_minimum_quantity_text', '<p style="font-size: 10px;">' . sprintf(esc_html__('Vendor price will only be applied to a minimum quantity of %1$s products', 'woocommerce-vendor-portal'), $min_quantity) . '</p>', $min_quantity );
					return $variation_data;  
				}
			
			}
			
			$terms = get_the_terms ( $product_id, 'product_cat' );
			if ( !is_wp_error($terms) && !empty($terms) ) {
				foreach ( $terms as $term ) {
					$data=get_term_meta($term->term_id, 'wholesale_multi_user_pricing', true);
					if ( isset($data[$role]) ) {
						
						$min_quantity = (int) $data[$role]['min_quatity'];
						if ( $min_quantity && 1 != $min_quantity ) {
							/* translators: %s: vendor price on minmum */
							$variation_data['availability_html'] .= apply_filters( 'wwp_product_minimum_quantity_text', '<p style="font-size: 10px;">' . sprintf(esc_html__('Vendor price will only be applied to a minimum quantity of %1$s products', 'woocommerce-vendor-portal'), $data[$role]['min_quatity']) . '</p>' , $data[$role]['min_quatity'] );
							return $variation_data;  	  
						
						}
					}
				}
			}
			
			$data=get_option('wholesale_multi_user_pricing');
			if ( isset($data[$role]) ) {
				
				$min_quantity = (int) $data[$role]['min_quatity'];
				if ( $min_quantity && 1 != $min_quantity ) {
					/* translators: %s: vendor price on minmum */
					$variation_data['availability_html'] .= apply_filters( 'wwp_product_minimum_quantity_text', '<p style="font-size: 10px;">' . sprintf(esc_html__('Vendor price will only be applied to a minimum quantity of %1$s products', 'woocommerce-vendor-portal'), $data[$role]['min_quatity']) . '</p>' , $data[$role]['min_quatity'] );
					return $variation_data; 

				}
			}
			return $variation_data;
		}
		
		public function add_the_date_validation( $passed, $product_id, $quantity, $variation_id = '', $variations = '' ) { 
		
			if ( $this->is_wholesale($product_id) ) {
					
				$settings = get_option('wwp_vendor_portal_options', true);	

				if ( 'yes' == $settings['wholesaler_allow_minimum_qty'] ) {	 
					$role = $this->get_current_user_role_id();
					$min_quantity = 1;
					if ( !empty($variation_id) ) {
						$data = get_post_meta($product_id, 'wholesale_multi_user_pricing', true);
						
						if (isset($data[$role][$variation_id]['qty'])) {
							$min_quantity = $data[$role][$variation_id]['qty'];
								
							if ( empty($min_quantity) || !isset($min_quantity) ) {  
								$min_quantity = 1;
							}
							if ($min_quantity > $quantity ) {
								 
								/* translators: %s: vendor price on minmum */
								wc_add_notice( __( apply_filters( 'wwp_product_minimum_quantity_text', sprintf(esc_html__('Vendor price will only be applied to a minimum quantity of %1$s products', 'woocommerce-vendor-portal'), $min_quantity) , $min_quantity ), 'woocommerce' ), 'error' );
								return  false; 
								 
							} else { 
								return  true;  
							}
						}
						
						$terms = get_the_terms ( $product_id, 'product_cat' );
						
						if ( !is_wp_error($terms) && !empty($terms) ) {
							foreach ( $terms as $term ) {
							
								$data=  get_term_meta($term->term_id, 'wholesale_multi_user_pricing', true);
								 
								if (isset($data[$role]['min_quatity'])) {
									$min_quantity = (int) $data[$role]['min_quatity'];
									
									if ( empty($min_quantity) || !isset($min_quantity) ) {  
										$min_quantity = 1;
									}
									
									if ($min_quantity > $quantity ) {
										
										/* translators: %s: vendor price on minmum */
										wc_add_notice( __( apply_filters( 'wwp_product_minimum_quantity_text', sprintf(esc_html__('Vendor price will only be applied to a minimum quantity of %1$s products', 'woocommerce-vendor-portal'), $min_quantity) , $min_quantity ), 'woocommerce' ), 'error' );
										return  false; 
									} else {
										return true;  
									}
								}
							}
						}	
						$data=get_option('wholesale_multi_user_pricing');
						if (isset($data[$role]['min_quatity'])) {
							$min_quantity = $data[$role]['min_quatity'];
						 
							if ( empty($min_quantity) || !isset($min_quantity) ) {  
								$min_quantity = 1;
							}
							if ($min_quantity > $quantity ) {
								/* translators: %s: vendor price on minmum */
								wc_add_notice( __( apply_filters( 'wwp_product_minimum_quantity_text', sprintf(esc_html__('Vendor price will only be applied to a minimum quantity of %1$s products', 'woocommerce-vendor-portal'), $min_quantity) , $min_quantity ), 'woocommerce' ), 'error' );
								return  false; 
								 
							} else { 
								return  true;  
							}
						}	
					} else {
						
						$data = get_post_meta($product_id, 'wholesale_multi_user_pricing', true);
						if ( isset($data[$role]['min_quatity']) ) {
							$min_quantity = $data[$role]['min_quatity'];
						 
							if ( empty($min_quantity) || !isset($min_quantity) ) {  
								$min_quantity = 1;
							}
							if ($min_quantity > $quantity ) {
								
								/* translators: %s: vendor price on minmum */
								wc_add_notice( __( apply_filters( 'wwp_product_minimum_quantity_text', sprintf(esc_html__('Vendor price will only be applied to a minimum quantity of %1$s products', 'woocommerce-vendor-portal'), $min_quantity) , $min_quantity ), 'woocommerce' ), 'error' );
								return  false; 
								 
							} else { 
								return  true;  
							}
						
						}
						
						$terms = get_the_terms ( $product_id, 'product_cat' );
						if ( !is_wp_error($terms) && !empty($terms) ) {
							foreach ( $terms as $term ) {
								$data=get_term_meta($term->term_id, 'wholesale_multi_user_pricing', true);
							 
								if (isset($data[$role]['min_quatity'])) {
									$min_quantity = $data[$role]['min_quatity'];
									 
									if ( empty($min_quantity) || !isset($min_quantity) ) {  
										$min_quantity = 1;
									}
									if ($min_quantity > $quantity ) {
										
										/* translators: %s: vendor price on minmum */
										wc_add_notice( __( apply_filters( 'wwp_product_minimum_quantity_text', sprintf(esc_html__('Vendor price will only be applied to a minimum quantity of %1$s products', 'woocommerce-vendor-portal'), $min_quantity) , $min_quantity ), 'woocommerce' ), 'error' );
										return  false; 
										 
									} else { 
										return  true;  
									}
								
								}
							}
						}
						
						$data=get_option('wholesale_multi_user_pricing');
						if (isset($data[$role]['min_quatity'])) {
							$min_quantity = $data[$role]['min_quatity'];
						 
							if ( empty($min_quantity) || !isset($min_quantity) ) {  
								$min_quantity = 1;
							}
							if ($min_quantity > $quantity ) {
								
								/* translators: %s: vendor price on minmum */
								wc_add_notice( __( apply_filters( 'wwp_product_minimum_quantity_text', sprintf(esc_html__('Vendor price will only be applied to a minimum quantity of %1$s products', 'woocommerce-vendor-portal'), $min_quantity) , $min_quantity ), 'woocommerce' ), 'error' );
								return  false; 
								 
							} else { 
								return  true;  
							}
						
						}
					}/// else end
				}
			}

			return  true; 
	
		}
		
		public function wwp_override_price_filter_on() {
		 
			add_filter( 'woocommerce_product_get_price', array($this,'wwp_regular_price_change'), 200, 2);
			add_filter('woocommerce_product_variation_get_price', array($this,'wwp_regular_price_change') , 200, 2 );
			add_filter('woocommerce_product_get_regular_price', array($this, 'wwp_regular_price_change'), 200, 2);
			add_filter('woocommerce_product_variation_get_regular_price', array($this, 'wwp_regular_price_change'), 200, 2 );
			add_filter('woocommerce_get_price_html', array($this, 'wwp_change_product_price_display'), 200 , 2 );
			add_filter('woocommerce_cart_item_price', array($this, 'wwp_change_product_price_display'), 200 , 2 );
		
		}
		
		public function wwp_available_payment_gateways( $available_gateways ) {
			global $woocommerce;
			$user_info = get_userdata(get_current_user_id());
			$user_roles = (array) $user_info->roles;
			if ( empty($user_roles) ) {
				return $available_gateways;
			}
			$term = get_term_by('slug', $user_roles[0], 'wholesale_user_roles');
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
			$term = get_term_by('slug', $user_roles[0], 'wholesale_user_roles');
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
			$wholesale_qty = '';
			wc_delete_product_transients($product->get_id());
			$product_id = $product->get_id();
			$qty='';
			if ( is_cart() || is_checkout() ) {
				foreach ( WC()->cart->get_cart() as $cart_item ) { 
					if ( $cart_item['product_id'] == $product_id ) {
						$qty =  $cart_item['quantity'];
						break; // stop the loop if product is found
					}
				}
			}
			
			$variation_id = $product_id;
			$role=$this->get_current_user_role_id();
			$data= get_post_meta($product_id, 'wholesale_multi_user_pricing', true);
			
			if ( isset($data[$role]) && 'product' == get_post_type($product_id) && isset($data[$role]['discount_type']) && isset($data[$role]['wholesale_price']) ) {
				
				$my = $data[$role];
				if ( isset($my['min_quatity']) && !empty($my['min_quatity']) ) {
					$wholesale_qty = $my['min_quatity'];
				}
				
					 
				if ( $this->quantity( $qty, $wholesale_qty ) ) {
					return $price;
				}
				
				$price=get_post_meta($product_id, '_regular_price', true); 
				
				return $this->change_price($price, $my['discount_type'], $my['wholesale_price']);
				
				
			} elseif ( 'product_variation' == get_post_type($product_id) ) { 
			
				$variation_id = $product_id;
				$product_id = wp_get_post_parent_id($product_id);
				
				$data=get_post_meta($product_id, 'wholesale_multi_user_pricing', true);
				
				if ( isset($data[$role][$variation_id]['wholesaleprice']) ) { 
					$my = $data[$role][$variation_id];
					if ( isset($my['min_quatity']) && !empty($my['min_quatity']) ) {
						$wholesale_qty = $my['min_quatity'];
					}
				 
					if (  $this->quantity( $qty, $wholesale_qty ) ) {
						return $price;
					}

					$price = $this->get_variable_wholesale_price($data[$role][$variation_id], $variation_id);

					return $price;
				}
			}
			
			$terms = get_the_terms ( $product_id, 'product_cat' );
			if ( !is_wp_error($terms) && !empty($terms) ) {
				foreach ( $terms as $term ) {
					$data=get_term_meta($term->term_id, 'wholesale_multi_user_pricing', true);
					
					
					
					if ( isset($data[$role]) && isset($data[$role]['discount_type']) && isset($data[$role]['wholesale_price']) ) {
						$my = $data[$role];
						if ( isset($my['min_quatity']) && !empty($my['min_quatity']) ) {
							$wholesale_qty = $my['min_quatity'];
						}
				 
						if (  $this->quantity( $qty, $wholesale_qty ) ) {
							return $price;
						}
						$price=get_post_meta($variation_id, '_regular_price', true); 
						return $this->change_price($price, $my['discount_type'], $my['wholesale_price']);
					}
				}
			}
			$data=get_option('wholesale_multi_user_pricing');
			if ( isset($data[$role]) && isset($data[$role]['discount_type']) && isset($data[$role]['wholesale_price']) ) {
				$my = $data[$role];
				if ( isset($my['min_quatity']) && !empty($my['min_quatity']) ) {
					$wholesale_qty = (int) $my['min_quatity'];
				}
				if (  $this->quantity( $qty, $wholesale_qty ) ) {
					return $price;
				}
				$price=get_post_meta($variation_id, '_regular_price', true);
				return $this->change_price( $price, $data[$role]['discount_type'], $data[$role]['wholesale_price']);
			}
			
			return $price;
		}
		
		public function quantity( $qty, $wholesale_qty ) { 
		
			if ( is_cart() || is_checkout() ) {
				return true;
			}
		
			if ( '' != $qty && $qty < $wholesale_qty ) {
				return false;
			}
		}
		
		public function wwp_variation_price( $price, $variation ) { 
			$variation_id = $variation->get_id();
			$product_id = wp_get_post_parent_id($product_id);
			$role = $this->get_current_user_role_id();
			if ( isset($data[$role][$variation_id]) ) {
				$price = $this->get_variable_wholesale_price($data[$role][$variation_id], $variation_id);
			}
			return $price;
		}
	 
		public function change_price( $price, $type, $amount ) { 
			
			if ( empty($price) || empty($amount) ) {
				return $price;   
			}
			if ( 'fixed' == $type ) {
				$price = $amount;
			} else {
				$price = $price * $amount / 100;
			}
			return $price;
		}
		public function wwp_change_product_price_display ( $price, $product ) {   
			global $woocommerce;
			if ( is_array($product) ) {
				$post_id = $product['product_id'];
				$product = wc_get_product($post_id);
			}
			$post_id = $product->get_id();
			// if ( is_cart() ) {
				// return $price;
			// }   
			if ( ( 'object' == gettype($product) ) && !$product->is_type('simple') ) { 
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
			$role=$this->get_current_user_role_id();
			$data=get_post_meta($post_id, 'wholesale_multi_user_pricing', true);
			if ( isset($data[$role]) ) { 
				$my = $data[$role];
				if ( isset($my['discount_type']) && !empty($my['discount_type']) && isset($my['wholesale_price']) && !empty($my['wholesale_price']) && isset($my['min_quatity']) && !empty($my['min_quatity']) ) {
					return $this->get_price($my['discount_type'], $my['wholesale_price'], $price, $my['min_quatity'], $product);
				} else {
					return $price;
				}   
			} 
			$terms = get_the_terms ( $post_id, 'product_cat' );
			if ( !is_wp_error($terms) && !empty($terms) ) {
				foreach ( $terms as $term ) {
					$data=get_term_meta($term->term_id, 'wholesale_multi_user_pricing', true);
				 
					if ( isset($data[$role]) ) {  
						$my = $data[$role];
						if ( isset($my['discount_type']) && !empty($my['discount_type']) && isset($my['wholesale_price']) && !empty($my['wholesale_price']) && isset($my['min_quatity']) && !empty($my['min_quatity']) ) {

							return $this->get_price($my['discount_type'], $my['wholesale_price'], $price, $my['min_quatity'], $product);
						
						} else {
							return $price;
						}   
					}
				}
			} 
			$data=get_option('wholesale_multi_user_pricing');
			if ( isset($data[$role]) ) {	
				$my = $data[$role];
				if ( isset($my['discount_type']) && !empty($my['discount_type']) && isset($my['wholesale_price']) && !empty($my['wholesale_price']) && isset($my['min_quatity']) && !empty($my['min_quatity']) ) {
					return $this->get_price($my['discount_type'], $my['wholesale_price'], $price, $my['min_quatity'], $product);
				} else {
					return $price;
				}   
			} 
			return $price;
		}
		public function get_price( $discountType = '', $Wprice, $r_price, $min, $product ) { 
			global $woocommerce;
			$r_price = get_post_meta( $product->get_id(), '_regular_price', true );
			$Wprice = $product->get_regular_price();
			if ( empty( $r_price ) || '0' == $r_price) {
				return add_filter('woocommerce_is_purchasable', '__return_false');
			}
			if ( '0' == $r_price ) {
				return $r_price;
			}
			
			// Tax display suffix function call 
			$tax_display_suffix = wwp_get_tax_price_display_suffix( $product->get_id() ) ;
			
			// Normal price inclide tax 
			$r_price = wwp_get_price_including_tax( $product, array('price' => $r_price ) );
			$Wprice  = wwp_get_price_including_tax( $product, array('price' => $Wprice ) );
			
			$saving_amount = ( $r_price - $Wprice ) ;
			$saving_amount = number_format( ( float ) $saving_amount, 2, '.', '' ); 
			$saving_percent = ( $r_price - $Wprice ) / $r_price * 100;
			$saving_percent = number_format( ( float ) $saving_percent, 2, '.', ''); 
			$settings = get_option('wwp_vendor_portal_options', true);
			$actual =( isset($settings['retailer_label']) && !empty($settings['retailer_label']) ) ? esc_html($settings['retailer_label']) : esc_html__('Actual', 'woocommerce-vendor-portal');
			$save =( isset($settings['save_label']) && !empty($settings['save_label']) ) ? esc_html($settings['save_label']) : esc_html__('Save', 'woocommerce-vendor-portal');
			$new =( isset($settings['wholesaler_label']) && !empty($settings['wholesaler_label']) ) ? esc_html($settings['wholesaler_label']) : esc_html__('New', 'woocommerce-vendor-portal');
			$html='';
			if ( !empty($Wprice) ) {
				$html .= do_action('wwp_before_pricing', $product  );
				$html .= '<div class="wwp-vendor-portal-details">';
				if ( 'yes' != $settings['retailer_disabled'] ) {
					$html .= '<p><span class="retailer-text">' . esc_html__($actual, 'woocommerce-vendor-portal') . '</span>: <s>' . wc_price($r_price) . ' ' . $tax_display_suffix . '</s></p>';
				}
				$html .= '<p><span class="price-text">' . esc_html__($new, 'woocommerce-vendor-portal') . '</span>: ' . wc_price($Wprice) . ' ' . $tax_display_suffix . '</p>';
				if ( 'yes' != $settings['save_price_disabled'] ) {
					$html .= '<p><b><span class="save-price-text">' . esc_html__($save, 'woocommerce-vendor-portal') . '</span>: ' . wc_price($saving_amount) . ' (' . round($saving_percent) . '%) </b></p>';
				}
				if ( $min > 1 ) {
					if ( $product->get_type() == 'simple') {
						/* translators: %s: vendor price on minmum */
						$html .= apply_filters( 'wwp_product_minimum_quantity_text', '<p style="font-size: 10px;">' . sprintf(esc_html__('Vendor price will only be applied to a minimum quantity of %1$s products', 'woocommerce-vendor-portal'), $min) . '</p>' , $min );
					}
				}
				$html .= '</div>';
				$html .= do_action('wwp_after_pricing' , $product );
			}
			return $html;
		}
		public function get_current_user_role_id() {
			if ( is_user_logged_in() ) {
				$user_info = get_userdata(get_current_user_id());
				$user_role = implode( ', ', $user_info->roles );
				$wholesale_role = term_exists($user_role, 'wholesale_user_roles');
				if ( 0 !== $wholesale_role && null !== $wholesale_role ) {
					if ( is_array($wholesale_role) && isset($wholesale_role['term_id']) ) {
						return $wholesale_role['term_id'];
					}
				}
			}
			return false;
		}
		public function wwp_override_product_price_cart ( $_cart ) {  
			
			remove_filter( 'woocommerce_product_get_price', array($this,'wwp_regular_price_change'), 200, 2);
			remove_filter('woocommerce_product_variation_get_price', array($this,'wwp_regular_price_change') , 200, 2 );
			remove_filter('woocommerce_product_get_regular_price', array($this, 'wwp_regular_price_change'), 200, 2);
			remove_filter('woocommerce_product_variation_get_regular_price', array($this, 'wwp_regular_price_change'), 200, 2 );
			remove_filter('woocommerce_get_price_html', array($this, 'wwp_change_product_price_display'), 200 , 2 );
			remove_filter('woocommerce_cart_item_price', array($this, 'wwp_change_product_price_display'), 200 , 2);
			
			global $woocommerce;
			$items = $woocommerce->cart->get_cart();
			$role=$this->get_current_user_role_id();
			
			foreach ( $_cart->cart_contents as $key => $item ) {
			
				$return_check = false;

				$variation_id = $item['variation_id'];
				
				if ( !empty($variation_id) ) {

					$data=get_post_meta($item['product_id'], 'wholesale_multi_user_pricing', true);
					if ( isset($data[$role]) && isset($data[$role][$variation_id]) && false === $return_check ) {                   
						// GET MIN QUANTITY
						$min_quantity=$data[$role][$variation_id]['qty'];
						if ( empty($min_quantity) || !isset($min_quantity) ) { // IF MIN QUANTITY NOT SET OR DOESN't EXIST ON DB
							$min_quantity = 1;
						}
						
						if ( $min_quantity <= $item['quantity'] ) {
							if ( !empty($this->get_variable_wholesale_price($data[$role][$variation_id], $variation_id)) ) {
								$item['data']->set_price($this->get_variable_wholesale_price($data[$role][$variation_id], $variation_id));
								$return_check = true;
							}
						} else {
							$item['data']->set_price(get_post_meta($item['variation_id'], '_regular_price', true));
							$return_check = true;			
						}
						if ( true === $return_check ) {
							continue;	
						}
					}
					$terms = get_the_terms ( $item['product_id'], 'product_cat' );
					if ( !is_wp_error($terms) && !empty($terms) ) {
						foreach ( $terms as $term ) {
							$data=get_term_meta($term->term_id, 'wholesale_multi_user_pricing', true);
							if ( isset($data[$role]) && isset($data[$role]['discount_type']) && isset($data[$role]['wholesale_price']) && false === $return_check ) {
								$my = $data[$role];
								if ( isset($my['min_quatity']) && !empty($my['min_quatity']) ) {
									$wholesale_qty = (int) $my['min_quatity'];
								}
								if ( $wholesale_qty <= $item['quantity'] ) {
									$item['data']->set_price($this->change_price(get_post_meta($item['variation_id'], '_regular_price', true), $my['discount_type'], $my['wholesale_price'] ));

									$return_check = true;		
								  
								} else {  
									$item['data']->set_price(get_post_meta($item['variation_id'], '_regular_price', true));
									$return_check = true;
								}
								
							}
						} 
						if ( true === $return_check ) {
							continue;	
						}
					}	
					$data=get_option('wholesale_multi_user_pricing');
					if ( isset($data[$role]) && isset($data[$role]['discount_type']) && isset($data[$role]['wholesale_price']) ) {
						$my = $data[$role];
						if ( isset($my['min_quatity']) && !empty($my['min_quatity']) ) {
							$wholesale_qty = $my['min_quatity'];
						}
						if ( $wholesale_qty <= $item['quantity'] ) {
						 
							$item['data']->set_price($this->change_price( get_post_meta($item['variation_id'], '_regular_price', true)  , $my['discount_type'], $my['wholesale_price']) );
						
						} else {
							$item['data']->set_price(get_post_meta($item['variation_id'], '_regular_price', true));
							$return_check = true;
						}
						if ( true === $return_check ) {
							continue;	
						}
						
					 
					}
				} else {
					
					$data=get_post_meta($item['product_id'], 'wholesale_multi_user_pricing', true);
				
					if ( isset($data[$role]) ) {
						$my=$data[$role]; 
						if ( isset($my['discount_type']) && !empty($my['discount_type']) && isset($my['wholesale_price']) && !empty($my['wholesale_price']) && isset($my['min_quatity']) && !empty($my['min_quatity']) && false ===  $return_check ) {
							if ( $my['min_quatity'] <= $item['quantity'] ) {
								$price= $this->get_wholesale_price_multi($my['discount_type'], $my['wholesale_price'], $item['product_id']);
								if ( !empty($price) ) {
									$item['data']->set_price($price);
									$return_check = true;
								}
							} else { 
								$item['data']->set_price(get_post_meta($item['product_id'], '_regular_price', true));
								$return_check = true;
							}
						}
					}
				
					$terms = get_the_terms ( $item['product_id'], 'product_cat' );
					if ( !is_wp_error($terms) && !empty($terms) ) {
						
						foreach ( $terms as $term ) {
							$data=get_term_meta($term->term_id, 'wholesale_multi_user_pricing', true);
							
							if ( isset($data[$role]) && isset($data[$role]['discount_type']) && isset($data[$role]['wholesale_price']) && false === $return_check ) {
								
								$my = $data[$role];
								if ( isset($my['min_quatity']) && !empty($my['min_quatity']) ) {
									$wholesale_qty = (int) $my['min_quatity'];
								}
								if ( $wholesale_qty <= $item['quantity'] ) {
								
									$item['data']->set_price($this->change_price(get_post_meta($item['product_id'], '_regular_price', true), $my['discount_type'], $my['wholesale_price']));	
									$return_check = true;		
								
								} else {  
								
									$item['data']->set_price(get_post_meta($item['product_id'], '_regular_price', true));
									$return_check = true;
								  
								}
							}
						}
						if ( true === $return_check ) {
							continue;	
						}
					}
					$data=get_option('wholesale_multi_user_pricing');
					if ( isset($data[$role]) && isset($data[$role]['discount_type']) && isset($data[$role]['wholesale_price']) ) {
						$my = $data[$role];
						if ( isset($my['min_quatity']) && !empty($my['min_quatity']) ) {
							$wholesale_qty = $my['min_quatity'];
						}
						if ( $wholesale_qty <= $item['quantity'] ) {
						 
							$item['data']->set_price($this->change_price(get_post_meta($item['product_id'], '_regular_price', true), $my['discount_type'], $my['wholesale_price']));
						
						} else {
								
							$item['data']->set_price(get_post_meta($item['product_id'], '_regular_price', true));
							$return_check = true;
						 
						}
						if ( true === $return_check ) {
							continue;	
						}
					}
				}
			} //ends foreach
			
		}
		public function is_wholesale ( $product_id ) {
			$role=$this->get_current_user_role_id();
			$data=get_post_meta($product_id, 'wholesale_multi_user_pricing', true);
			if ( isset($data[$role]) ) { 
				return true;
			} 
			$terms = get_the_terms ( $product_id, 'product_cat' );
			if ( !is_wp_error($terms) && !empty($terms) ) {
				foreach ( $terms as $term ) {
					$data=get_term_meta($term->term_id, 'wholesale_multi_user_pricing', true);
					if ( isset($data[$role]) && isset($data[$role]['discount_type']) && isset($data[$role]['wholesale_price']) ) {
						return true;
					}
				}
			}
			$data=get_option('wholesale_multi_user_pricing');
			if ( isset($data[$role]) && isset($data[$role]['discount_type']) && isset($data[$role]['wholesale_price']) ) {
				return true;
			}
			return false;
		}
		public function get_wholesale_price_multi ( $discount, $wprice, $post_id ) { 
			if ( 'fixed' == $discount ) {
				return $wprice;
			} else {
				$product_price = get_post_meta($post_id, '_regular_price', true);
				$product_price = ( isset($product_price) && is_numeric($product_price) ) ? $product_price : 0;
				$wholesale_price = $product_price * $wprice / 100;
				return $wholesale_price;
			}
		}
		public function get_variable_wholesale_price ( $arr, $variation_id ) { 
			if (empty($arr)) {
				return false;
			}
			
			$role = $this->get_current_user_role_id();
			$data=get_post_meta(wp_get_post_parent_id($variation_id), 'wholesale_multi_user_pricing', true);
			$variable_price = isset($arr['wholesaleprice']) ? $arr['wholesaleprice'] : 0;
			
			$wholesale_amount_type = $data[$role]['discount_type'];
			
			if ( 'fixed' === $wholesale_amount_type ) {
				return $variable_price;
			} else {
				$product_price = get_post_meta($variation_id, '_regular_price', true);
				
				$product_price=( isset($product_price) && is_numeric($product_price) ) ? $product_price : 0;
				$variable_price=( isset($variable_price) && is_numeric($variable_price) ) ? $variable_price : 0;
				$wholesale_price = $product_price * $variable_price / 100;
				return $wholesale_price;
			}
		}
		public function is_wholesaler_user ( $user_id ) {
			if ( !empty($user_id) ) {
				$user_info = get_userdata($user_id);
				
				$user_role = implode(', ', $user_info->roles);
				$allterms = get_terms('wholesale_user_roles', array('hide_empty' => false));
		 
				foreach ($allterms as $allterm_key => $allterm_value ) {
					if ( $user_role == $allterm_value->slug ) {
						return true;
					}
				}
				if ( 'default_wholesaler' == $user_role ) {
					return true;
				}
			}
			return false;
		}

		 
		public function wwp_variable_price_format( $price, $product ) { 
			global $woocommerce;
			$prod_id = $product->get_id();
			if ( !$this->is_wholesale($prod_id) ) {
				return $price;
			}
			$return_check = false;
			$product_variations = $product->get_children();
			
			$wholesale_product_variations = array();
			$original_variation_price = array();
			$role=$this->get_current_user_role_id();
			
			$data = get_post_meta($prod_id, 'wholesale_multi_user_pricing', true);
			if ( isset($data[$role]) && false === $return_check ) {
				
				foreach ( $product_variations as $product_variation ) { 
				
					if ( isset($data[$role]) && isset($data[$role][$product_variation]) ) {
						
						$_product = wc_get_product( $product_variation );
						
						$wholesale_product_variations[] = $_product->get_regular_price();
						$original_variation_price[] = get_post_meta($product_variation, '_regular_price', true);
						 
					}
				}
				
				 
				$return_check = true;
				 
			}
			
			$terms = get_the_terms ( $prod_id, 'product_cat' );
					
			if ( !is_wp_error($terms) && !empty($terms) ) {
				foreach ( $terms as $term ) {
				
					$data =  get_term_meta($term->term_id, 'wholesale_multi_user_pricing', true);
					if (isset($data[$role]['min_quatity']) && false === $return_check ) {
						$min_quantity = (int) $data[$role]['min_quatity'];
						 
						if ( empty($min_quantity) || !isset($min_quantity) ) {  
							$min_quantity = 1;
						}
						$wholesale_product_variations = array();
						$original_variation_price = array();
					
						foreach ( $product_variations as $product_variation ) {
						
							if ( isset($data[$role])) {
								
								$_product = wc_get_product( $product_variation ); 
								$wholesale_product_variations[] =  $this->get_wholesale_price_multi ( $data[$role]['discount_type'], $data[$role]['wholesale_price'], $product_variation ); 
								$original_variation_price[] =    get_post_meta($product_variation, '_regular_price', true);
							}
						}
						$return_check = true;
					}
				}
			}
			
			$data=get_option('wholesale_multi_user_pricing');
			
			if (isset($data[$role]['min_quatity']) && false === $return_check ) {
				$min_quantity = $data[$role]['min_quatity'];
			 
				if ( empty($min_quantity) || !isset($min_quantity) ) {  
					$min_quantity = 1;
				}
				$wholesale_product_variations = array();
				$original_variation_price = array();

				foreach ( $product_variations as $product_variation ) {
					$_product = wc_get_product( $product_variation ); 
					$wholesale_product_variations[] =   $this->get_wholesale_price_multi ( $data[$role]['discount_type'], $data[$role]['wholesale_price'], $product_variation ); 
					$original_variation_price[] =    get_post_meta($product_variation, '_regular_price', true);
				}
				 
				$return_check = true;
			}	

			sort( $wholesale_product_variations );
			sort( $original_variation_price );
			
			// orignal price add tax
			$original_variation_price[0] = wwp_get_price_including_tax( $product, array('price' => $original_variation_price[0] ) );
			$original_variation_price[count($original_variation_price) - 1] = wwp_get_price_including_tax( $product, array('price' => $original_variation_price[count($original_variation_price) - 1] ) );
			// vendor price add tax
			$wholesale_product_variations[0] = wwp_get_price_including_tax( $product, array('price' => $wholesale_product_variations[0] ) );

			$wholesale_product_variations[count($wholesale_product_variations) - 1] = wwp_get_price_including_tax( $product, array('price' => $wholesale_product_variations[count($wholesale_product_variations) - 1] ) );
			
			$min_wholesale_price = $wholesale_product_variations[0];
			$max_wholesale_price = $wholesale_product_variations[count($wholesale_product_variations) - 1];
			$min_original_variation_price = $original_variation_price[0];
			$max_original_variation_price = $original_variation_price[count($original_variation_price) - 1];
			
			$min_saving_amount = round( ( $min_original_variation_price - $min_wholesale_price ) );
			$min_saving_percent = ( $min_original_variation_price - $min_wholesale_price ) / $min_original_variation_price * 100;
			
			$max_saving_amount = round( ( $max_original_variation_price - $max_wholesale_price ) );
			$max_saving_percent = ( $max_original_variation_price - $max_wholesale_price ) / $max_original_variation_price * 100;
			
			$min_quantity = get_post_meta($prod_id, '_wwp_wholesale_min_quantity', true);
			$settings= get_option('wwp_vendor_portal_options', true);
			$actual = ( isset($settings['retailer_label']) && !empty($settings['retailer_label']) ) ? esc_html($settings['retailer_label']) : esc_html__('Actual', 'woocommerce-vendor-portal');
			$save = ( isset($settings['save_label']) && !empty($settings['save_label']) ) ? esc_html($settings['save_label']) : esc_html__('Save', 'woocommerce-vendor-portal');
			$new = ( isset($settings['wholesaler_label']) && !empty($settings['wholesaler_label']) ) ? esc_html($settings['wholesaler_label']) : esc_html__('New', 'woocommerce-vendor-portal');
			$html = '<div class="wwp-vendor-portal-details">';
			
			// var_dump(get_option('woocommerce_tax_display_shop'));
			
			
			//  var_dump( get_post_meta($prod_id, '_tax_status', true));
			//  taxable
			  
			// && 'incl' == get_option('woocommerce_tax_display_shop') 
			
			// this is condition for actual price value
			//if ( $woocommerce->customer->is_vat_exempt() ) {
				
				  $wcv_max_price = $max_original_variation_price;
				  $wcv_min_price = $min_original_variation_price;
			// } else {
				// $wcv_max_price = $product->get_variation_regular_price( 'max', true );
				// $wcv_min_price = $product->get_variation_regular_price( 'min', true );
			// }
			
			// Tax display suffix function call 
			$tax_display_suffix = wwp_get_tax_price_display_suffix( $prod_id ) ;
			
			if ( 'yes' != $settings['retailer_disabled'] ) {
				if ( $wcv_min_price == $wcv_max_price ) {
					$html .= '<p><span class="retailer-text">' . esc_html__($actual, 'woocommerce-vendor-portal') . '</span>: <s>' . wc_price($wcv_min_price) . ' ' . $tax_display_suffix . '</s></p>';
				} else {
					$html .= '<p><span class="retailer-text">' . esc_html__($actual, 'woocommerce-vendor-portal') . '</span>: <s>' . wc_price($wcv_min_price) . ' ' . $tax_display_suffix . ' - ' . wc_price($wcv_max_price) . ' ' . $tax_display_suffix . '</s></p>';
				}
			}
			
			if ( wc_price( $wholesale_product_variations[0] ) !== wc_price( $wholesale_product_variations[ count($wholesale_product_variations ) - 1 ] ) ) {
				$html .= '<p><b><span class="price-text">' . esc_html__($new, 'woocommerce-vendor-portal') . '</span>: ' . wc_price($wholesale_product_variations[0]) . ' ' . $tax_display_suffix . ' - ' . wc_price($wholesale_product_variations[count($wholesale_product_variations) - 1]) . ' ' . $tax_display_suffix . '</b></p>';
			} else {
				$html .= '<p><b><span class="price-text">' . esc_html__($new, 'woocommerce-vendor-portal') . '</span>: ' . wc_price($wholesale_product_variations[0]) . ' ' . $tax_display_suffix . '</b></p>';
			}
			
			if ( 'yes' != $settings['save_price_disabled'] ) {
				if ( round( $min_saving_percent ) !== round( $max_saving_percent ) ) {
					$html .= '<p><b><span class="save-price-text">' . esc_html__($save, 'woocommerce-vendor-portal') . '</span>:  (' . round($min_saving_percent) . '% - ' . round($max_saving_percent) . '%) </b></p>';
				} else {
					$html .= '<p><b><span class="save-price-text">' . esc_html__($save, 'woocommerce-vendor-portal') . '</span>:  (' . round($min_saving_percent) . '%) </b></p>';	
				}
			}
			
			if ($min_quantity > 1) {
				if ( $product->get_type() == 'simple') {
					/* translators: %s: vendor price on minmum */
					$html .= apply_filters( 'wwp_product_minimum_quantity_text', '<p style="font-size: 10px;">' . sprintf(esc_html__('Vendor price will only be applied to a minimum quantity of %1$s products', 'woocommerce-vendor-portal'), $min_quantity) . '</p>' , $min_quantity );
				}
			}
			$html .= '</div>';
			return $html;
		}
		
		public function woocommerce_shortcode_products_query( $q ) {  
			$settings=get_option('wwp_vendor_portal_options', true);
			$wholesaler_prod_only = ( isset($settings['wholesaler_prodcut_only']) && 'yes' ==$settings['wholesaler_prodcut_only'] ) ? 'yes' : 'no';
			 
			if ( 'yes' == $wholesaler_prod_only &&  !is_admin()  ) {
				
				if ( $this->is_wholesaler_user(get_current_user_id()) ) {
				
					$data = get_option('wholesale_multi_user_pricing');
					$role=$this->get_current_user_role_id();
					 
					if ( !isset ( $data[$role] ) ) {

						$cate =array();

						$categories = get_terms( array( 'taxonomy' => 'product_cat' ) );
						
						if ( is_array($categories) ) {
						
							foreach ( $categories as $category ) {
								
								$data =  get_term_meta($category->term_id, 'wholesale_multi_user_pricing', true);
							
								if ( isset ( $data[$role]['wholesale_price'] ) ) {
									
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
						'suppress_filters' => false,
						'post_status' => 'publish',
						'fields' => 'ids',
						) );
						foreach ( $all_ids as $id ) {
							
							$data = get_post_meta($id, 'wholesale_multi_user_pricing', true);
							
							if ( isset($data[$role]) ) { 
								$total_ids[] =	$id ;
							}
						
						}
						 
						if ( is_array($ids) ) {
							foreach ($ids  as $id) {
								$total_ids[] =	$id;
							}
						}
						unset( $q['post__in'] );
						if ( is_array($total_ids) ) { 
						
							$total_ids = array_unique(  $total_ids );
							$exclude_ids = array_diff($all_ids, $total_ids);
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
		
		public function default_wholesaler_products_only( $q ) { 
			$settings=get_option('wwp_vendor_portal_options', true);
			$wholesaler_prod_only = ( isset($settings['wholesaler_prodcut_only']) && 'yes' ==$settings['wholesaler_prodcut_only'] ) ? 'yes' : 'no';
			 
			if ( 'yes' == $wholesaler_prod_only && $q->is_main_query() && !is_admin()  ) {
				
				if ( $this->is_wholesaler_user(get_current_user_id()) ) {
					
					//Global price get
					$data = get_option('wholesale_multi_user_pricing');
					
					$role=$this->get_current_user_role_id();
					 
						
					if ( !isset ( $data[$role] ) ) {

						$cate =array();

						$categories = get_terms( array( 'taxonomy' => 'product_cat' ) );
						
						if ( is_array($categories) ) {
						
							foreach ( $categories as $category ) {
								
								$data =  get_term_meta($category->term_id, 'wholesale_multi_user_pricing', true);
							
								if ( isset ( $data[$role]['wholesale_price'] ) ) {
									
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
										'terms'     => $cate,
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
							
							$data = get_post_meta($id, 'wholesale_multi_user_pricing', true);
							
							if ( isset($data[$role]) ) {	
								$total_ids[] =	$id ;
							}
						
						}
						
						if ( is_array($ids) ) {
							foreach ($ids  as $id) {
								$total_ids[] =	$id;
							}
						}
						
						
						if ( is_array($total_ids) ) { 
						
							$total_ids = array_unique(  $total_ids );
							$exclude_ids = array_diff($all_ids, $total_ids);
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
	}
	new WWP_Easy_Wholesale_Multiuser();
}
