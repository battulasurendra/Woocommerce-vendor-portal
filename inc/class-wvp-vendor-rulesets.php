<?php
if ( !defined('ABSPATH') ) {
	exit; // Exit if accessed directly
}
/**
 * Class To Add Vendor Functionality with WooCommerce
 */
if ( !class_exists('Wvp_Vendor_Rulesets') ) {

	class Wvp_Vendor_Rulesets {

		public function __construct () {
			add_action('product_cat_add_form_fields', array($this, 'wvp_add_new_field'), 10);
			add_action('product_cat_edit_form_fields', array($this, 'wvp_edit_new_field'), 10, 1);
			add_action('edited_product_cat', array($this, 'wvp_save_new_field'), 10, 2 );
			add_action('create_product_cat', array($this, 'wvp_save_new_field'), 10, 2 );
		}
		public function wvp_add_new_field() {
			wp_nonce_field('wvp_wholeset_ruleset_nonce', 'wvp_wholeset_ruleset_nonce');
			$settings=get_option('wvp_vendor_portal_options', true);
			if (isset($settings['vendor_role']) && 'multiple' == $settings['vendor_role']) {
				$roles = get_terms(
					'vendor_user_roles', array(
						'hide_empty' => false,
					) 
				);
				if ( !empty($roles) ) { 
					?>
				<!-- // version 1.3.0 -->
				<div class="form-field term-visibility-wrap">
					<label for="vendor_product_visibility_multi"><?php esc_html_e('Hide Product for Contractor Roles', 'woocommerce-vendor-portal'); ?></label>
					<select name="vendor_product_visibility_multi[]" id="vendor_product_visibility_multi" class="widefat wc-enhanced-select" multiple>
					<?php
					foreach ( $roles as $key => $role ) {
						echo '<option value="' . esc_attr($role->slug) . '">' . esc_html($role->name) . '</option>';
					}
					?>
					</select>
					<p><?php esc_html_e('Select specific user roles to hide the products of this category.', 'woocommerce-vendor-portal'); ?></p>
				</div>
				<!-- // ends version 1.3.0 -->
				<table class="vendor_portal">
					<tr>
						<th><?php esc_html_e('Vendor Role', 'woocommerce-vendor-portal'); ?></th>
						<th><?php esc_html_e('Enable for Role', 'woocommerce-vendor-portal'); ?></th>
						<th><?php esc_html_e('Discount Type', 'woocommerce-vendor-portal'); ?></th>
						<th><?php esc_html_e('Vendor Price', 'woocommerce-vendor-portal'); ?></th>
						<th><?php esc_html_e('Min Quantity', 'woocommerce-vendor-portal'); ?></th>
					</tr>
						<?php 
						foreach ( $roles as $key => $role ) {
							$min=1;
							$price='';
							$discount='';
							?>
							<tr>
								<td>
									<span class="wvp-title"><?php esc_html_e($role->name); ?></span>
								</td>
								<td>
									<input type="checkbox" value="<?php esc_attr_e($role->slug); ?>" name="role_<?php esc_attr_e($role->term_id); ?>" <?php echo isset($data[$role->term_id]) ? 'checked' : ''; ?> >
								</td>
								<td>
									<select class="widefat" name="discount_type_<?php esc_attr_e($role->term_id); ?>" value="">
										<option value="percent" <?php selected($discount, 'percent'); ?> > <?php esc_html_e('Percent', 'woocommerce-vendor-portal'); ?> </option>
										<option value="fixed"  <?php selected($discount, 'fixed'); ?> > <?php esc_html_e('Fixed', 'woocommerce-vendor-portal'); ?> </option>
									</select>
								</td>
								<td>
									<input class="widefat" type="text" name="vendor_price_<?php esc_attr_e($role->term_id); ?>" value="<?php esc_attr_e($price); ?>">
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
					<label for="_wvp_vendor_product_visibility"><?php esc_html_e('Products Visibility', 'woocommerce-vendor-portal'); ?></label>
					<input type="checkbox" name="_wvp_vendor_product_visibility" id="_wvp_vendor_product_visibility" value="yes">
					<span><?php esc_html_e('Hide products of this category for vendor customers.', 'woocommerce-vendor-portal'); ?></span>
				</div>
				<!-- // ends version 1.3.0 -->
				<div class="form-field term-wvp-wrap">
					<label for="_wvp_enable_vendor_item"><?php esc_html_e('Enable Vendor Prices', 'woocommerce-vendor-portal'); ?></label>
					<input type="checkbox" name="_wvp_enable_vendor_item" id="_wvp_enable_vendor_item" value="yes">
					<span><?php esc_html_e('Enable vendor prices.', 'woocommerce-vendor-portal'); ?></span>
				</div>
				<div class="form-field term-discount-wrap">
					<label for="_wvp_vendor_type"><?php esc_html_e('Vendor Discount Type', 'woocommerce-vendor-portal'); ?></label>
					<select name="_wvp_vendor_type" id="_wvp_vendor_type"class="regular-text">
						<option value="fixed"><?php esc_html_e('Fixed Amount', 'woocommerce-vendor-portal'); ?></option>
						<option value="percent"><?php esc_html_e('Percentage', 'woocommerce-vendor-portal'); ?></option>
					</select>
					<p><?php esc_html_e('Price type for vendor products.', 'woocommerce-vendor-portal'); ?></p>
				</div>
				<div class="form-field term-amount-wrap">
					<label for="_wvp_vendor_amount"><?php esc_html_e('Enter Vendor Amount', 'woocommerce-vendor-portal'); ?></label>
					<input type="text" name="_wvp_vendor_amount" id="_wvp_vendor_amount" value="" class="regular-text">
					<p><?php esc_html_e('Tax exempt for vendor user role.', 'woocommerce-vendor-portal'); ?></p>
				</div>
				<div class="form-field term-qty-wrap">
					<label for="_wvp_vendor_min_quantity"><?php esc_html_e('Minimum Quantity', 'woocommerce-vendor-portal'); ?></label>
					<input type="number" name="_wvp_vendor_min_quantity" id="_wvp_vendor_min_quantity" value="" class="regular-text">
					<p><?php esc_html_e('Tax exempt for vendor user role.', 'woocommerce-vendor-portal'); ?></p>
				</div>
				<?php
			}
		}
		public function wvp_edit_new_field( $term ) {
			wp_nonce_field('wvp_wholeset_ruleset_nonce', 'wvp_wholeset_ruleset_nonce');
			$settings=get_option('wvp_vendor_portal_options', true);
			if (isset($settings['vendor_role']) && 'multiple' == $settings['vendor_role']) {
				$roles=get_terms(
					'vendor_user_roles', array(
						'hide_empty' => false,
					) 
				);
				if ( !empty($roles) ) { 
				
					$data = get_term_meta( $term->term_id, 'vendor_multi_user_pricing', true );
					// version 1.3.0
					$roles_selected = get_term_meta( $term->term_id, 'vendor_product_visibility_multi', true );
					// ends version 1.3.0
					?>
				<!-- // version 1.3.0 -->
								<!-- // version 1.3.0 -->
				<tr class="form-field term-visibility-wrap">
					<th>
						<label for="vendor_product_visibility_multi">
							<?php esc_html_e('Hide Product for Contractor Roles', 'woocommerce-vendor-portal'); ?>
						</label>
					</th>
					<td scope="row">
						<select name="vendor_product_visibility_multi[]" id="vendor_product_visibility_multi" class="regular-text wc-enhanced-select" multiple>
						<?php
						foreach ( $roles as $key => $role ) {
							$selected = ( !empty($roles_selected) && in_array($role->slug, $roles_selected) ) ? 'selected' : '';
							echo '<option value="' . esc_attr($role->slug) . '" ' . esc_attr($selected) . '>' . esc_html($role->name) . '</option>';
						}
						?>
						</select>
						<p><?php esc_html_e('Select specific user roles to hide the products of this category.', 'woocommerce-vendor-portal'); ?></p>
					</td>
				</tr>
				<!-- // ends version 1.3.0 -->
				<table class="vendor_portal">
					<tr>
						<th><?php esc_html_e('Vendor Role', 'woocommerce-vendor-portal'); ?></th>
						<th><?php esc_html_e('Enable for Role', 'woocommerce-vendor-portal'); ?></th>
						<th><?php esc_html_e('Discount Type', 'woocommerce-vendor-portal'); ?></th>
						<th><?php esc_html_e('Vendor Price', 'woocommerce-vendor-portal'); ?></th>
						<th><?php esc_html_e('Min Quantity', 'woocommerce-vendor-portal'); ?></th>
					</tr>
						<?php 
						foreach ( $roles as $key => $role ) {
							$min=1;
							$price='';
							$discount='';
							if ( isset($data[$role->term_id]) ) {
								$min=$data[$role->term_id]['min_quatity'];
								$price=$data[$role->term_id]['vendor_price'];
								$discount=$data[$role->term_id]['discount_type'];
							} 
							?>
							<tr>
								<td>
									<span class="wvp-title"><?php esc_html_e($role->name); ?></span>
								</td>
								<td>
									<input type="checkbox" value="<?php esc_attr_e($role->slug); ?>" name="role_<?php esc_attr_e($role->term_id); ?>" <?php echo isset($data[$role->term_id]) ? 'checked' : ''; ?> >
								</td>
								<td>
									<select class="widefat" name="discount_type_<?php esc_attr_e($role->term_id); ?>" value="">
										<option value="percent" <?php selected($discount, 'percent'); ?> > <?php esc_html_e('Percent', 'woocommerce-vendor-portal'); ?> </option>
										<option value="fixed"  <?php selected($discount, 'fixed'); ?> > <?php esc_html_e('Fixed', 'woocommerce-vendor-portal'); ?> </option>
									</select>
								</td>
								<td>
									<input class="widefat" type="text" name="vendor_price_<?php esc_attr_e($role->term_id); ?>" value="<?php esc_attr_e($price); ?>">
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
				$visibility = get_term_meta($term->term_id, '_wvp_vendor_product_visibility', true);
				// ends version 1.3.0
				$enable = get_term_meta($term->term_id, '_wvp_enable_vendor_item', true);
				$amount = get_term_meta($term->term_id, '_wvp_vendor_amount', true);
				$type = get_term_meta($term->term_id, '_wvp_vendor_type', true);
				$qty = get_term_meta($term->term_id, '_wvp_vendor_min_quantity', true);
				?>
			<!-- // version 1.3.0 -->
			<tr class="form-field term-visibility-wrap">
				<th>
					<label for="_wvp_vendor_product_visibility">
						<?php esc_html_e('Hide Product for Contractor Roles', 'woocommerce-vendor-portal'); ?>
					</label>
				</th>
				<td scope="row">
					<input type="checkbox" name="_wvp_vendor_product_visibility" id="_wvp_vendor_product_visibility" value="yes" <?php checked('yes', $visibility); ?>>
					<span><?php esc_html_e('Hide products of this category for vendor customers.', 'woocommerce-vendor-portal'); ?></span>
				</td>
			</tr>
			<!-- // ends version 1.3.0 -->
			<tr class="form-field term-wvp-wrap">
				<th>
					<label for="_wvp_enable_vendor_item">
						<?php esc_html_e('Enable Vendor Prices', 'woocommerce-vendor-portal'); ?>
					</label>
				</th>
				<td scope="row">
					<input type="checkbox" name="_wvp_enable_vendor_item" id="_wvp_enable_vendor_item" value="yes" <?php checked('yes', $enable); ?>>
					<span><?php esc_html_e('Enable vendor prices.', 'woocommerce-vendor-portal'); ?></span>
				</td>
			</tr>
			<tr class="form-field term-discount-wrap">
				<th>
					<label for="_wvp_vendor_type">
						<?php esc_html_e('Vendor Discount Type', 'woocommerce-vendor-portal'); ?>
					</label>
				</th>
				<td scope="row">
					<select name="_wvp_vendor_type" id="_wvp_vendor_type" class="regular-text">
						<option value="fixed" <?php selected('fixed', $type); ?>><?php esc_html_e('Fixed Amount', 'woocommerce-vendor-portal'); ?></option>
						<option value="percent" <?php selected('percent', $type); ?>><?php esc_html_e('Percentage', 'woocommerce-vendor-portal'); ?></option>
					</select>
					<p><?php esc_html_e('Price type for vendor products.', 'woocommerce-vendor-portal'); ?></p>
				</td>
			</tr>
			<tr class="form-field term-amount-wrap">
				<th>
					<label for="_wvp_vendor_amount">
						<?php esc_html_e('Enter Vendor Amount', 'woocommerce-vendor-portal'); ?>
					</label>
				</th>
				<td scope="row">
					<input type="text" name="_wvp_vendor_amount" id="_wvp_vendor_amount" value="<?php esc_attr_e($amount); ?>" class="regular-text">
					<p><?php esc_html_e('Enter vendor amount.', 'woocommerce-vendor-portal'); ?></p>
				</td>
			</tr>
			<tr class="form-field term-qty-wrap">
				<th>
					<label for="_wvp_vendor_min_quantity">
						<?php esc_html_e('Minimum Quantity', 'woocommerce-vendor-portal'); ?>
					</label>
				</th>
				<td scope="row">
					<input type="number" name="_wvp_vendor_min_quantity" id="_wvp_vendor_min_quantity" value="<?php esc_attr_e($qty); ?>" class="regular-text">
					<p><?php esc_html_e('Enter vendor minimum quantity to apply discount.', 'woocommerce-vendor-portal'); ?></p>
				</td>
			</tr>
				<?php
			}
		}
		public function wvp_save_new_field( $term_id, $term ) {
			if ( !isset($_POST['wvp_wholeset_ruleset_nonce']) || !wp_verify_nonce( wc_clean($_POST['wvp_wholeset_ruleset_nonce']), 'wvp_wholeset_ruleset_nonce') ) {
				return;
			}
			$settings=get_option('wvp_vendor_portal_options', true);
			if (isset($settings['vendor_role']) && 'multiple' == $settings['vendor_role']) {
				$roles=get_terms(
				'vendor_user_roles', array(
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
						if ( isset($_POST['vendor_price_' . $role->term_id]) ) {
							$data[$role->term_id]['vendor_price'] = is_numeric( wc_clean($_POST['vendor_price_' . $role->term_id]) ) ? wc_clean($_POST['vendor_price_' . $role->term_id]) : '';
						}
						if ( isset($_POST['min_quatity_' . $role->term_id]) ) {
							$data[$role->term_id]['min_quatity'] = is_numeric( wc_clean($_POST['min_quatity_' . $role->term_id]) ) ? wc_clean($_POST['min_quatity_' . $role->term_id]) : 1;
						}
					}
				}
				update_term_meta($term_id, 'vendor_multi_user_pricing', $data);
				// version 1.3.0
				if ( isset( $_POST['vendor_product_visibility_multi'] ) ) {
					update_term_meta($term_id, 'vendor_product_visibility_multi', (array) wc_clean($_POST['vendor_product_visibility_multi']) );
				} else {
					update_term_meta($term_id, 'vendor_product_visibility_multi', '' );
				}// ends version 1.3.0
			} else {
				// version 1.3.0
				if ( isset( $_POST['_wvp_vendor_product_visibility'] ) ) {
					update_term_meta($term_id, '_wvp_vendor_product_visibility', 'yes');
				} else {
					update_term_meta($term_id, '_wvp_vendor_product_visibility', 'no');
				} // ends version 1.3.0
				if ( isset( $_POST['_wvp_enable_vendor_item'] ) ) {
					update_term_meta($term_id, '_wvp_enable_vendor_item', 'yes');
				} else {
					update_term_meta($term_id, '_wvp_enable_vendor_item', 'no');
				}
				if ( isset( $_POST['_wvp_vendor_type'] ) ) {
					update_term_meta($term_id, '_wvp_vendor_type', wc_clean($_POST['_wvp_vendor_type']) );
				} else {
					update_term_meta($term_id, '_wvp_vendor_type', '');
				}
				if ( isset( $_POST['_wvp_vendor_amount'] ) ) {
					update_term_meta($term_id, '_wvp_vendor_amount', wc_clean($_POST['_wvp_vendor_amount']) );
				} else {
					update_term_meta($term_id, '_wvp_vendor_amount', '');
				}
				if ( isset( $_POST['_wvp_vendor_min_quantity'] ) ) {
					update_term_meta($term_id, '_wvp_vendor_min_quantity', wc_clean($_POST['_wvp_vendor_min_quantity']) );
				} else {
					update_term_meta($term_id, '_wvp_vendor_min_quantity', '');
				}
			}
		}
		
	}
	new Wvp_Vendor_Rulesets();
}
