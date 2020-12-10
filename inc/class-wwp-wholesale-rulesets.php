<?php
if ( !defined('ABSPATH') ) {
	exit; // Exit if accessed directly
}
/**
 * Class To Add Wholesale Functionality with WooCommerce
 */
if ( !class_exists('Wwp_Wholesale_Rulesets') ) {

	class Wwp_Wholesale_Rulesets {

		public function __construct () {
			add_action('product_cat_add_form_fields', array($this, 'wwp_add_new_field'), 10);
			add_action('product_cat_edit_form_fields', array($this, 'wwp_edit_new_field'), 10, 1);
			add_action('edited_product_cat', array($this, 'wwp_save_new_field'), 10, 2 );
			add_action('create_product_cat', array($this, 'wwp_save_new_field'), 10, 2 );
		}
		public function wwp_add_new_field() {
			wp_nonce_field('wwp_wholeset_ruleset_nonce', 'wwp_wholeset_ruleset_nonce');
			$settings=get_option('wwp_vendor_portal_options', true);
			if (isset($settings['wholesale_role']) && 'multiple' == $settings['wholesale_role']) {
				$roles = get_terms(
					'wholesale_user_roles', array(
						'hide_empty' => false,
					) 
				);
				if ( !empty($roles) ) { 
					?>
				<!-- // version 1.3.0 -->
				<div class="form-field term-visibility-wrap">
					<label for="wholesale_product_visibility_multi"><?php esc_html_e('Hide Product for Wholesaler Roles', 'woocommerce-wholesale-pricing'); ?></label>
					<select name="wholesale_product_visibility_multi[]" id="wholesale_product_visibility_multi" class="widefat wc-enhanced-select" multiple>
					<?php
					foreach ( $roles as $key => $role ) {
						echo '<option value="' . esc_attr($role->slug) . '">' . esc_html($role->name) . '</option>';
					}
					?>
					</select>
					<p><?php esc_html_e('Select specific user roles to hide the products of this category.', 'woocommerce-wholesale-pricing'); ?></p>
				</div>
				<!-- // ends version 1.3.0 -->
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
							$price='';
							$discount='';
							?>
							<tr>
								<td>
									<span class="wwp-title"><?php esc_html_e($role->name); ?></span>
								</td>
								<td>
									<input type="checkbox" value="<?php esc_attr_e($role->slug); ?>" name="role_<?php esc_attr_e($role->term_id); ?>" <?php echo isset($data[$role->term_id]) ? 'checked' : ''; ?> >
								</td>
								<td>
									<select class="widefat" name="discount_type_<?php esc_attr_e($role->term_id); ?>" value="">
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
				}
			} else {
				?>
				<!-- // version 1.3.0 -->
				<div class="form-field term-visibility-wrap">
					<label for="_wwp_wholesale_product_visibility"><?php esc_html_e('Products Visibility', 'woocommerce-wholesale-pricing'); ?></label>
					<input type="checkbox" name="_wwp_wholesale_product_visibility" id="_wwp_wholesale_product_visibility" value="yes">
					<span><?php esc_html_e('Hide products of this category for wholesale customers.', 'woocommerce-wholesale-pricing'); ?></span>
				</div>
				<!-- // ends version 1.3.0 -->
				<div class="form-field term-wwp-wrap">
					<label for="_wwp_enable_wholesale_item"><?php esc_html_e('Enable Wholesale Prices', 'woocommerce-wholesale-pricing'); ?></label>
					<input type="checkbox" name="_wwp_enable_wholesale_item" id="_wwp_enable_wholesale_item" value="yes">
					<span><?php esc_html_e('Enable wholesale prices.', 'woocommerce-wholesale-pricing'); ?></span>
				</div>
				<div class="form-field term-discount-wrap">
					<label for="_wwp_wholesale_type"><?php esc_html_e('Wholesale Discount Type', 'woocommerce-wholesale-pricing'); ?></label>
					<select name="_wwp_wholesale_type" id="_wwp_wholesale_type"class="regular-text">
						<option value="fixed"><?php esc_html_e('Fixed Amount', 'woocommerce-wholesale-pricing'); ?></option>
						<option value="percent"><?php esc_html_e('Percentage', 'woocommerce-wholesale-pricing'); ?></option>
					</select>
					<p><?php esc_html_e('Price type for wholesale products.', 'woocommerce-wholesale-pricing'); ?></p>
				</div>
				<div class="form-field term-amount-wrap">
					<label for="_wwp_wholesale_amount"><?php esc_html_e('Enter Wholesale Amount', 'woocommerce-wholesale-pricing'); ?></label>
					<input type="text" name="_wwp_wholesale_amount" id="_wwp_wholesale_amount" value="" class="regular-text">
					<p><?php esc_html_e('Tax exempt for wholesale user role.', 'woocommerce-wholesale-pricing'); ?></p>
				</div>
				<div class="form-field term-qty-wrap">
					<label for="_wwp_wholesale_min_quantity"><?php esc_html_e('Minimum Quantity', 'woocommerce-wholesale-pricing'); ?></label>
					<input type="number" name="_wwp_wholesale_min_quantity" id="_wwp_wholesale_min_quantity" value="" class="regular-text">
					<p><?php esc_html_e('Tax exempt for wholesale user role.', 'woocommerce-wholesale-pricing'); ?></p>
				</div>
				<?php
			}
		}
		public function wwp_edit_new_field( $term ) {
			wp_nonce_field('wwp_wholeset_ruleset_nonce', 'wwp_wholeset_ruleset_nonce');
			$settings=get_option('wwp_vendor_portal_options', true);
			if (isset($settings['wholesale_role']) && 'multiple' == $settings['wholesale_role']) {
				$roles=get_terms(
					'wholesale_user_roles', array(
						'hide_empty' => false,
					) 
				);
				if ( !empty($roles) ) { 
				
					$data = get_term_meta( $term->term_id, 'wholesale_multi_user_pricing', true );
					// version 1.3.0
					$roles_selected = get_term_meta( $term->term_id, 'wholesale_product_visibility_multi', true );
					// ends version 1.3.0
					?>
				<!-- // version 1.3.0 -->
								<!-- // version 1.3.0 -->
				<tr class="form-field term-visibility-wrap">
					<th>
						<label for="wholesale_product_visibility_multi">
							<?php esc_html_e('Hide Product for Wholesaler Roles', 'woocommerce-wholesale-pricing'); ?>
						</label>
					</th>
					<td scope="row">
						<select name="wholesale_product_visibility_multi[]" id="wholesale_product_visibility_multi" class="regular-text wc-enhanced-select" multiple>
						<?php
						foreach ( $roles as $key => $role ) {
							$selected = ( !empty($roles_selected) && in_array($role->slug, $roles_selected) ) ? 'selected' : '';
							echo '<option value="' . esc_attr($role->slug) . '" ' . esc_attr($selected) . '>' . esc_html($role->name) . '</option>';
						}
						?>
						</select>
						<p><?php esc_html_e('Select specific user roles to hide the products of this category.', 'woocommerce-wholesale-pricing'); ?></p>
					</td>
				</tr>
				<!-- // ends version 1.3.0 -->
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
							$price='';
							$discount='';
							if ( isset($data[$role->term_id]) ) {
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
									<select class="widefat" name="discount_type_<?php esc_attr_e($role->term_id); ?>" value="">
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
				}
			} else {
				// version 1.3.0
				$visibility = get_term_meta($term->term_id, '_wwp_wholesale_product_visibility', true);
				// ends version 1.3.0
				$enable = get_term_meta($term->term_id, '_wwp_enable_wholesale_item', true);
				$amount = get_term_meta($term->term_id, '_wwp_wholesale_amount', true);
				$type = get_term_meta($term->term_id, '_wwp_wholesale_type', true);
				$qty = get_term_meta($term->term_id, '_wwp_wholesale_min_quantity', true);
				?>
			<!-- // version 1.3.0 -->
			<tr class="form-field term-visibility-wrap">
				<th>
					<label for="_wwp_wholesale_product_visibility">
						<?php esc_html_e('Hide Product for Wholesaler Roles', 'woocommerce-wholesale-pricing'); ?>
					</label>
				</th>
				<td scope="row">
					<input type="checkbox" name="_wwp_wholesale_product_visibility" id="_wwp_wholesale_product_visibility" value="yes" <?php checked('yes', $visibility); ?>>
					<span><?php esc_html_e('Hide products of this category for wholesale customers.', 'woocommerce-wholesale-pricing'); ?></span>
				</td>
			</tr>
			<!-- // ends version 1.3.0 -->
			<tr class="form-field term-wwp-wrap">
				<th>
					<label for="_wwp_enable_wholesale_item">
						<?php esc_html_e('Enable Wholesale Prices', 'woocommerce-wholesale-pricing'); ?>
					</label>
				</th>
				<td scope="row">
					<input type="checkbox" name="_wwp_enable_wholesale_item" id="_wwp_enable_wholesale_item" value="yes" <?php checked('yes', $enable); ?>>
					<span><?php esc_html_e('Enable wholesale prices.', 'woocommerce-wholesale-pricing'); ?></span>
				</td>
			</tr>
			<tr class="form-field term-discount-wrap">
				<th>
					<label for="_wwp_wholesale_type">
						<?php esc_html_e('Wholesale Discount Type', 'woocommerce-wholesale-pricing'); ?>
					</label>
				</th>
				<td scope="row">
					<select name="_wwp_wholesale_type" id="_wwp_wholesale_type" class="regular-text">
						<option value="fixed" <?php selected('fixed', $type); ?>><?php esc_html_e('Fixed Amount', 'woocommerce-wholesale-pricing'); ?></option>
						<option value="percent" <?php selected('percent', $type); ?>><?php esc_html_e('Percentage', 'woocommerce-wholesale-pricing'); ?></option>
					</select>
					<p><?php esc_html_e('Price type for wholesale products.', 'woocommerce-wholesale-pricing'); ?></p>
				</td>
			</tr>
			<tr class="form-field term-amount-wrap">
				<th>
					<label for="_wwp_wholesale_amount">
						<?php esc_html_e('Enter Wholesale Amount', 'woocommerce-wholesale-pricing'); ?>
					</label>
				</th>
				<td scope="row">
					<input type="text" name="_wwp_wholesale_amount" id="_wwp_wholesale_amount" value="<?php esc_attr_e($amount); ?>" class="regular-text">
					<p><?php esc_html_e('Enter wholesale amount.', 'woocommerce-wholesale-pricing'); ?></p>
				</td>
			</tr>
			<tr class="form-field term-qty-wrap">
				<th>
					<label for="_wwp_wholesale_min_quantity">
						<?php esc_html_e('Minimum Quantity', 'woocommerce-wholesale-pricing'); ?>
					</label>
				</th>
				<td scope="row">
					<input type="number" name="_wwp_wholesale_min_quantity" id="_wwp_wholesale_min_quantity" value="<?php esc_attr_e($qty); ?>" class="regular-text">
					<p><?php esc_html_e('Enter wholesale minimum quantity to apply discount.', 'woocommerce-wholesale-pricing'); ?></p>
				</td>
			</tr>
				<?php
			}
		}
		public function wwp_save_new_field( $term_id, $term ) {
			if ( !isset($_POST['wwp_wholeset_ruleset_nonce']) || !wp_verify_nonce( wc_clean($_POST['wwp_wholeset_ruleset_nonce']), 'wwp_wholeset_ruleset_nonce') ) {
				return;
			}
			$settings=get_option('wwp_vendor_portal_options', true);
			if (isset($settings['wholesale_role']) && 'multiple' == $settings['wholesale_role']) {
				$roles=get_terms(
				'wholesale_user_roles', array(
						'hide_empty' => false,
					)
				);
				$data=array();
				if ( !empty($roles) ) {
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
				update_term_meta($term_id, 'wholesale_multi_user_pricing', $data);
				// version 1.3.0
				if ( isset( $_POST['wholesale_product_visibility_multi'] ) ) {
					update_term_meta($term_id, 'wholesale_product_visibility_multi', (array) wc_clean($_POST['wholesale_product_visibility_multi']) );
				} else {
					update_term_meta($term_id, 'wholesale_product_visibility_multi', '' );
				}// ends version 1.3.0
			} else {
				// version 1.3.0
				if ( isset( $_POST['_wwp_wholesale_product_visibility'] ) ) {
					update_term_meta($term_id, '_wwp_wholesale_product_visibility', 'yes');
				} else {
					update_term_meta($term_id, '_wwp_wholesale_product_visibility', 'no');
				} // ends version 1.3.0
				if ( isset( $_POST['_wwp_enable_wholesale_item'] ) ) {
					update_term_meta($term_id, '_wwp_enable_wholesale_item', 'yes');
				} else {
					update_term_meta($term_id, '_wwp_enable_wholesale_item', 'no');
				}
				if ( isset( $_POST['_wwp_wholesale_type'] ) ) {
					update_term_meta($term_id, '_wwp_wholesale_type', wc_clean($_POST['_wwp_wholesale_type']) );
				} else {
					update_term_meta($term_id, '_wwp_wholesale_type', '');
				}
				if ( isset( $_POST['_wwp_wholesale_amount'] ) ) {
					update_term_meta($term_id, '_wwp_wholesale_amount', wc_clean($_POST['_wwp_wholesale_amount']) );
				} else {
					update_term_meta($term_id, '_wwp_wholesale_amount', '');
				}
				if ( isset( $_POST['_wwp_wholesale_min_quantity'] ) ) {
					update_term_meta($term_id, '_wwp_wholesale_min_quantity', wc_clean($_POST['_wwp_wholesale_min_quantity']) );
				} else {
					update_term_meta($term_id, '_wwp_wholesale_min_quantity', '');
				}
			}
		}
		
	}
	new Wwp_Wholesale_Rulesets();
}
