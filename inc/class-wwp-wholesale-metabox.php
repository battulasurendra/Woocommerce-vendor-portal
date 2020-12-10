<?php
if ( !defined('ABSPATH') ) {
	exit; // Exit if accessed directly
}
/**
 * Class to add product meta boxes
 */
if ( !class_exists('Wwp_Wholesale_Product_Metabox') ) {
	
	class Wwp_Wholesale_Product_Metabox {
		
		public function __construct() {
			$settings = get_option('wwp_vendor_portal_options', true);
			if ( isset($settings['wholesale_role']) && 'single' != $settings['wholesale_role'] ) { 
				add_action('add_meta_boxes', array($this, 'add_multiuser_role_metabox' ));
				add_action('wp_ajax_retrieve_wholesale_multiuser_pricing', array($this, 'retrieve_wholesale_multiuser_pricing' ));
				add_action('save_post_product', array($this, 'save_wholesale_metabox_data'), 10, 1);
			}
		}
		public function add_multiuser_role_metabox() {
			add_meta_box( 'wholesale-pricing-pro-multiuser', esc_html__('Wholesale Multi User Pricing', 'woocommerce-wholesale-pricing'), array($this, 'wholesale_multi_user_pricing_callback'), 'product', 'normal', 'high' 
			);
		}
		public function wholesale_multi_user_pricing_callback() {
			global $post, $product; 
			wp_nonce_field('wwp_wholesale_multi_user', 'wwp_wholesale_multi_user');
			?>
			<div class="" id="wholesale-multiuser-pricing">
				<input type="hidden" value="<?php esc_attr_e($post->ID); ?>" name="product_id">
				<div class="wholesale_loader"></div>
				<div class="wholesale_container"></div>
			</div>
			<?php
		}
		public function retrieve_wholesale_multiuser_pricing() {
			
			check_ajax_referer( 'wwp_wholesale_pricing', 'security' );
			
			if ( !isset($_POST['product_id'] ) && !is_numeric( $_POST['product_id'] ) && !isset( $_POST['ptype'] ) && empty( $_POST['ptype'] ) ) {
				die();
			}
			
			$product_id = wc_clean($_POST['product_id']);
			$ptype = wc_clean($_POST['ptype']);
			$data=get_post_meta($product_id, 'wholesale_multi_user_pricing', true);
			$wholesale_type = get_post_meta($product_id, '_wwp_wholesale_type', true);
			$wholesale_price = get_post_meta($product_id, '_wwp_wholesale_amount', true);
			$roles=get_terms(
					'wholesale_user_roles', array(
					'hide_empty' => false,
				) 
			);
			$rolehtml='';
			if ( 'variable' == $ptype ) {
				$rolehtml = $this->get_variable_product_wholesale( $product_id, $roles );
				$allowed_html = array(
					'input'	=> array(
								'name'  => array(),
								'value' => array(),
								'checked' => array(),
								'class' => array(),
								'id' => array(),
								'type' => array(),
								'readonly' => array()
								),
					'select'	=> array(
								'name'  => array(),
								'id' => array(),
								'class' => array(),
								'value' => array(),
								),
					'option'	=> array(
					'value' => array(),
					'selected' => array(),
			 
					),
					'a' => array(
						'class' => array(),
						'href'  => array(),
						'rel'   => array(),
						'title' => array(),
					),
					'abbr' => array(
						'title' => array(),
					),
					'b' => array(),
					'blockquote' => array(
						'cite'  => array(),
					),
					'cite' => array(
						'title' => array(),
					),
					'code' => array(),
					'del' => array(
						'datetime' => array(),
						'title' => array(),
					),
					'dd' => array(),
					'div' => array(
						'class' => array(),
						'title' => array(),
						'style' => array(),
					),
					'table' => array(
						'id' => array(),
						'class' => array(),
						'title' => array(),
						'style' => array(),
					),
					'tr' => array(),
					'th' => array(),
					'td' => array(),
					'dl' => array(),
					'dt' => array(),
					'em' => array(),
					'h1' => array(),
					'h2' => array(),
					'h3' => array(),
					'h4' => array(),
					'h5' => array(),
					'h6' => array(),
					'i' => array(),
					'img' => array(
						'alt'    => array(),
						'class'  => array(),
						'height' => array(),
						'src'    => array(),
						'width'  => array(),
					),
					'li' => array(
						'class' => array(),
					),
					'ol' => array(
						'class' => array(),
					),
					'p' => array(
						'class' => array(),
					),
					'q' => array(
						'cite' => array(),
						'title' => array(),
					),
					'span' => array(
						'class' => array(),
						'title' => array(),
						'style' => array(),
					),
					'strike' => array(),
					'strong' => array(),
					'ul' => array(
						'class' => array(),
					)
					);
					echo wp_kses($rolehtml, $allowed_html);
			} else {
				if ( !empty($roles) ) { 
					?>
					<table class="wholesale_pricing">
						<tr>
							<th><?php esc_html_e('Wholesale Role', 'woocommerce-wholesale-pricing'); ?></th>
							<th><?php esc_html_e('Enable for Role', 'woocommerce-wholesale-pricing'); ?></th>
							<th><?php esc_html_e('Discount Type', 'woocommerce-wholesale-pricing'); ?></th>
							<th><?php esc_html_e('Wholesale Price', 'woocommerce-wholesale-pricing'); ?></th>
							<th><?php esc_html_e('Min Quantity', 'woocommerce-wholesale-pricing'); ?></th>
						</tr>
						<?php 
						foreach ( $roles as $key => $role ) {
							$min=1;
							$price=$wholesale_price;
							$discount=$wholesale_type;
							if (isset($data[$role->term_id])) {
								$min=$data[$role->term_id]['min_quatity'];
								$price=$data[$role->term_id]['wholesale_price'];
								$discount=$data[$role->term_id]['discount_type'];
							} 
							?>
							<tr>
								<td>
									<span class="wwp-title"><?php esc_html_e($role->name); ?></span>
								</td>
								<td>
									<input type="checkbox" value="<?php esc_attr_e($role->slug); ?>" name="role_<?php esc_attr_e($role->term_id); ?>" <?php echo isset($data[$role->term_id]) ? 'checked' : ''; ?> >
								</td>
								<td>
									<select class="widefat" name="discount_type_<?php esc_attr_e($role->term_id); ?>" value="<?php esc_attr_e($wholesale_type); ?>">
										<option value="percent" <?php selected($discount, 'percent'); ?> > <?php esc_html_e('Percent', 'woocommerce-wholesale-pricing'); ?> </option>
										<option value="fixed"  <?php selected($discount, 'fixed'); ?> > <?php esc_html_e('Fixed', 'woocommerce-wholesale-pricing'); ?> </option>
									</select>
								</td>
								<td>
									<input class="widefat" type="text" name="wholesale_price_<?php esc_attr_e($role->term_id); ?>" value="<?php esc_attr_e($price); ?>">
								</td>
								<td>
									<input class="widefat" type="text" name="min_quatity_<?php esc_attr_e($role->term_id); ?>" value="<?php esc_attr_e($min); ?>">
								</td>
							</tr>
					<?php } ?>
					</table>
					<?php
				} else {
					esc_html_e('Please add Wholesale user roles first', 'woocommerce-wholesale-pricing');
				}
			}
			die();
		}
		public function get_variable_product_wholesale ( $product_id, $roles ) {
			if ( !empty($product_id) && !empty($roles) ) {
				$data=get_post_meta($product_id, 'wholesale_multi_user_pricing', true);
				$tickets = new WC_Product_Variable($product_id);
				$variables = $tickets->get_available_variations();
				ob_start();
				if ( !empty($variables) ) { 
					?>
					<table class="wholesale_pricing">
						<tr>
							<th><?php esc_html_e('Wholesale Role', 'woocommerce-wholesale-pricing'); ?></th> 
							<th><?php esc_html_e('Enable for Role', 'woocommerce-wholesale-pricing'); ?></th>
							<th><?php esc_html_e('Discount Type', 'woocommerce-wholesale-pricing'); ?></th> 
							<th><?php esc_html_e('Wholesale Price & Min Quantity', 'woocommerce-wholesale-pricing'); ?></th> 
						</tr>
							<?php 
							foreach ( $roles as $key => $role ) { 
								$discount='';
								if ( isset($data[$role->term_id]['discount_type']) ) {
									$discount=$data[$role->term_id]['discount_type'];
								} 
								?>
							<tr>
								<td>
									<span class="wwp-title"><?php esc_html_e($role->name); ?></span>
								</td>
								<td>
									<input type="checkbox" value="<?php esc_attr_e($role->slug); ?>" name="role_<?php esc_attr_e($role->term_id); ?>" <?php echo ( isset($data[$role->term_id]) ) ? 'checked' : ''; ?>>
								</td>
								<td>
									<select class="widefat" name="discount_type_<?php esc_attr_e($role->term_id); ?>" value="<?php esc_attr_e($wholesale_type); ?>" >
										<option value="percent" <?php selected($discount, 'percent'); ?> ><?php esc_html_e('Percent', 'woocommerce-wholesale-pricing'); ?></option>
										<option value="fixed" <?php selected($discount, 'fixed'); ?> ><?php esc_html_e('Fixed', 'woocommerce-wholesale-pricing'); ?></option>
									</select>
								</td>
								<td>     
									<div class="wwp-variable">
										<?php 
										foreach ( $variables as $key ) {
											$variation_id = $key['variation_id'];
											if ( isset($data[$role->term_id][$variation_id]) ) {
												$wholesale_price = $data[$role->term_id][$variation_id]['wholesaleprice'];
												$qty=$data[$role->term_id][$variation_id]['qty'];
											} else {
												$wholesale_price = get_post_meta($key['variation_id'], '_regular_price', true);
												$qty=1;
											}
											$regular_price = get_post_meta($key['variation_id'], '_regular_price', true);
											$wholesale_field_name = 'wholesaleprice_' . esc_attr($role->term_id) . '_' . esc_attr($key['variation_id']);
											$qty_field_name = 'qty_' . esc_attr($role->term_id) . '_' . esc_attr($key['variation_id']);
											$quanity_label = esc_html__('Qty: ', 'woocommerce-wholesale-pricing'); 
											?>
											<div class="variable-item">
												<span> # <?php esc_html_e($variation_id); ?></span>
												<input type="text" readonly name="reg-price" value="<?php esc_attr_e($regular_price); ?>"/>
												<label><?php esc_html_e('Wholesale Price', 'woocommerce-wholesale-pricing'); ?></label>
												<input type="text" name="<?php esc_attr_e($wholesale_field_name); ?>" value="<?php esc_attr_e($wholesale_price); ?>"/>
												<label>
													<?php esc_html_e($quanity_label, 'woocommerce-wholesale-pricing'); ?>
													<input type="number" class="qty" name="<?php esc_attr_e($qty_field_name); ?>" value="<?php esc_attr_e($qty); ?>"/>
												</label>
												<input type="hidden" name="product_type_<?php esc_attr_e($product_id); ?>" value="variable">
												<input type="hidden" name="prod_id_<?php esc_attr_e($product_id); ?>" value="<?php esc_attr_e($product_id); ?>">
											</div>
									<?php } ?>            
									</div>
								</td>
							</tr>
						<?php } ?>
					</table>
					<input type="hidden" name="wholesale_product_type" value="variable">
					<?php 
				} else { 
					esc_html_e('No variations found. Add variations before.', 'woocommerce-wholesale-pricing');
				}
			} else {
				esc_html_e('Wholesale Roles not found', 'woocommerce-wholesale-pricing');
			}
			return ob_get_clean();
		}
		public function save_wholesale_metabox_data ( $post_id ) {
			// Autosave
			if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) { 
				return;
			}
			// AJAX
			if (defined('DOING_AJAX') && DOING_AJAX) { 
				return;
			}
			if ( !isset($_POST['wwp_wholesale_multi_user']) || !wp_verify_nonce( wc_clean($_POST['wwp_wholesale_multi_user']), 'wwp_wholesale_multi_user') ) {
				return;
			}
			$roles=get_terms(
				'wholesale_user_roles', array(
					'hide_empty' => false,
				)
			);
			$data=array();
			if ( !empty($roles) ) {
				if ( isset($_POST['product-type'] ) && isset($_POST['wholesale_product_type']) && 'variable' == wc_clean($_POST['wholesale_product_type']) ) {
					$tickets = new WC_Product_Variable($post_id);
					$variables = $tickets->get_available_variations();
					if ( !empty($variables) ) {
						foreach ( $variables as $variable ) {
							$variation_id=$variable['variation_id'];
							$vary=array();
							foreach ( $roles as $key => $role ) {
								if ( !isset($_POST['role_' . $role->term_id]) ) {
									continue;
								} 
								if ( isset($_POST['role_' . $role->term_id]) ) {
									$data[$role->term_id]['slug'] = $role->slug;
									$vary[$role->term_id]['slug'] = $role->slug;
								}
								if ( isset($_POST['discount_type_' . $role->term_id]) ) {
									$data[$role->term_id]['discount_type']= wc_clean($_POST['discount_type_' . $role->term_id]);
									$vary[$role->term_id]['discount_type']= wc_clean($_POST['discount_type_' . $role->term_id]);
								}
								if ( isset( $_POST['wholesaleprice_' . $role->term_id . '_' . $variation_id]) ) {
									$data[$role->term_id][$variation_id]['wholesaleprice'] = is_numeric( wc_clean($_POST['wholesaleprice_' . $role->term_id . '_' . $variation_id]) ) ? wc_clean($_POST['wholesaleprice_' . $role->term_id . '_' . $variation_id]) : '';
									$vary[$role->term_id][$variation_id]['wholesaleprice']= is_numeric( wc_clean($_POST['wholesaleprice_' . $role->term_id . '_' . $variation_id]) ) ? wc_clean($_POST['wholesaleprice_' . $role->term_id . '_' . $variation_id]) : '';
								}
								if ( isset($_POST['qty_' . $role->term_id . '_' . $variation_id]) ) {
									$data[$role->term_id][$variation_id]['qty'] = is_numeric( wc_clean($_POST['qty_' . $role->term_id . '_' . $variation_id]) ) ? wc_clean($_POST['qty_' . $role->term_id . '_' . $variation_id]) : 1;
									$vary[$role->term_id][$variation_id]['qty'] = is_numeric( wc_clean($_POST['qty_' . $role->term_id . '_' . $variation_id]) ) ? wc_clean($_POST['qty_' . $role->term_id . '_' . $variation_id]) : 1;
								}
							}
							update_post_meta($variation_id, 'wholesale_multi_user_pricing', $vary);
						}
						update_post_meta($post_id, 'wholesale_multi_user_pricing', $data);
					}
				} else {
					foreach ( $roles as $key => $role ) {
						if ( !isset($_POST['role_' . $role->term_id]) ) {
							continue;
						} 
						if ( isset($_POST['role_' . $role->term_id]) ) {
							$data[$role->term_id]['slug']=$role->slug;
						}
						if ( isset($_POST['discount_type_' . $role->term_id]) ) {
							$data[$role->term_id]['discount_type'] = wc_clean($_POST['discount_type_' . $role->term_id]);
						}
						if ( isset($_POST['wholesale_price_' . $role->term_id]) ) {
							$data[$role->term_id]['wholesale_price'] = is_numeric( wc_clean($_POST['wholesale_price_' . $role->term_id]) ) ? wc_clean($_POST['wholesale_price_' . $role->term_id]) : '';
						}
						if ( isset($_POST['min_quatity_' . $role->term_id]) ) {
							$data[$role->term_id]['min_quatity'] = is_numeric( wc_clean($_POST['min_quatity_' . $role->term_id]) ) ? wc_clean($_POST['min_quatity_' . $role->term_id]) : 1;
						}
					}
				}
			}
			// version 1.3.0
			if ( isset($_POST['wholesale_product_visibility_multi']) ) {
				update_post_meta( $post_id, 'wholesale_product_visibility_multi', (array) wc_clean($_POST['wholesale_product_visibility_multi']) );
			} else {
				update_post_meta( $post_id, 'wholesale_product_visibility_multi', '' );
			} // ends version 1.3.0
			update_post_meta($post_id, 'wholesale_multi_user_pricing', $data);
			update_post_meta($post_id, 'wholesale_multi_user_pricing', $data);
		}
	}
	new Wwp_Wholesale_Product_Metabox();
}
