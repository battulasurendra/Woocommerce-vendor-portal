<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}
/**
 * Class Woo_Wholesale_User_Roles
 */
if (!class_exists('WWP_Wholesale_User_Roles')) {

	class WWP_Wholesale_User_Roles {

		public function __construct () {
			add_role('default_wholesaler', esc_html__('Wholesaler - Wholesaler Role', 'woocommerce-wholesale-pricing'), array( 'read' => true, 'level_0' => true ));
			add_action('init', array($this, 'register_taxonomy_for_users'));
			add_action('created_wholesale_user_roles', array($this, 'set_term_to_user_role'), 10, 2);
			add_action('delete_wholesale_user_roles', array($this, 'remove_term_and_user_role'), 10, 3);
			add_action('edit_wholesale_user_roles', array($this, 'edit_term_and_user_role'), 10, 2);
			add_action('wp_head', array($this, 'print_css_styles'));
			add_action('wholesale_user_roles_add_form_fields', array($this, 'wwp_add_new_field'), 10);
			add_action('wholesale_user_roles_edit_form_fields', array($this, 'wwp_edit_new_field'), 10, 1);
			add_action('edited_wholesale_user_roles', array($this, 'wwp_save_new_field'), 10, 2 );
			add_action('create_wholesale_user_roles', array($this, 'wwp_save_new_field'), 10, 2 );
		}
		public function print_css_styles() { ?>
			<style type="text/css">
				p.user_not_wholesale {
					text-align: center;
				}
				p.user_not_wholesale a {
					text-decoration: none;
					border: 2px solid #333;
					color: #333;
					padding: 10px 60px;
				}
			</style>
			<?php
		}
		public function register_taxonomy_for_users() {  
			$capabilities = array();
			//global $wp_roles;
			$labels = array(
				'label'                     => esc_html__('Wholesale Roles', 'woocommerce-wholesale-pricing'),
				'name'                      => esc_html__('Wholesale User Roles', 'woocommerce-wholesale-pricing'),
				'singular_name'             => esc_html__('Wholesale Role', 'woocommerce-wholesale-pricing'),
				'search_items'              => esc_html__('Search User Roles', 'woocommerce-wholesale-pricing'),
				'popular_items'             => esc_html__('Popular User Roles', 'woocommerce-wholesale-pricing'),
				'all_items'                 => esc_html__('All User Roles', 'woocommerce-wholesale-pricing'),
				'parent_item'               => null,
				'parent_item_colon'         => null,
				'edit_item'                 => esc_html__('Edit User Role', 'woocommerce-wholesale-pricing'), 
				'update_item'               => esc_html__('Update User Role', 'woocommerce-wholesale-pricing'),
				'add_new_item'              => esc_html__('Add New User Role', 'woocommerce-wholesale-pricing'),
				'new_item_name'             => esc_html__('New User Role Name', 'woocommerce-wholesale-pricing'),
				'separate_items_with_commas'=> esc_html__('Separate topics with commas', 'woocommerce-wholesale-pricing'),
				'add_or_remove_items'       => esc_html__('Add or remove topics', 'woocommerce-wholesale-pricing'),
				'choose_from_most_used'     => esc_html__('Choose from the most used topics', 'woocommerce-wholesale-pricing'),
				'menu_name'                 => esc_html__('Wholesale Roles', 'woocommerce-wholesale-pricing'),
			); 
			$args=array(
				'hierarchical'          => false,
				'labels'                => $labels,
				'show_ui'               => true,
				'show_admin_column'     => true,
				'update_count_callback' => '_update_post_term_count',
				'query_var'             => true,
			);
			register_taxonomy( 'wholesale_user_roles', array( 'wwp_requests' ), $args );
			$term = term_exists( 'default_wholesaler', 'wholesale_user_roles' );
			if ( null === $term) {
				wp_insert_term( 'Wholesaler', 'wholesale_user_roles', array( 'slug' => 'default_wholesaler' ) );
			}
			
			// user capabilities add
			$wp_roles = wp_roles();
			if ( ! class_exists( 'WP_Roles' ) ) {
				return;
			}
			
			if ( ! isset( $wp_roles ) ) {
				$wp_roles = new WP_Roles();
			}

			$capabilities = array(
				'manage_vendor',
				'manage_wholesale_settings',
				'manage_wholesale_user_role',
				'manage_wholesale_notifications',
				'manage_wholesale_bulk_ricing',
				'manage_wholesale_registration_page',
				'manage_wholesale_user_requests'
			);
			$capabilities = apply_filters( 'wholesale_user_capabilities', $capabilities);
			foreach ( $capabilities as $cap ) {
				//$wp_roles->remove_cap( 'shop_manager', $cap );
				//$wp_roles->remove_cap( 'administrator', $cap );
				$wp_roles->add_cap( 'shop_manager', $cap );
				$wp_roles->add_cap( 'administrator', $cap );
			}
		}
		public function set_term_to_user_role ( $term_id, $tt_id ) {
			$term=get_term($term_id, 'wholesale_user_roles');
			if ( !wp_roles()->is_role($term->slug) ) {
				add_role( $term->slug, $term->name . esc_html__(' - Wholesaler role', 'woocommerce-wholesale-pricing'), array( 'read' => true, 'level_0' => true ) );
			}
		}
		public function remove_term_and_user_role ( $term, $tt_id, $deleted_term ) {
			$termObj = get_term( $deleted_term, 'wholesale_user_roles' );
			if ( wp_roles()->is_role( $termObj->slug ) ) {
				remove_role( $termObj->slug );
			}
		}
		public function edit_term_and_user_role ( $term_id, $tt_id ) {
			if ( isset($_POST['wwp_vendor_register_nonce']) || wp_verify_nonce( wc_clean($_POST['wwp_vendor_register_nonce']), 'wwp_vendor_register_nonce') ) {
				echo esc_html__('Role updated', 'woocommerce-wholesale-pricing');
			}
			$termObj = get_term( $term_id, 'wholesale_user_roles' );
			$new_name = isset( $_POST['name'] ) ? wc_clean( $_POST['name'] ) : '';
			$new_slug = isset( $_POST['slug'] ) ? wc_clean( $_POST['slug'] ) : '';
			if ( $new_slug!=$termObj->slug ) {
				if ( empty( $new_slug ) ) {
					$new_slug = sanitize_title( $new_name );
				}
				if ( wp_roles()->is_role( $termObj->slug ) ) {
					remove_role($termObj->slug);
				}
				if ( !wp_roles()->is_role( $new_slug ) ) {
					add_role( $new_slug, $new_name . esc_html__(' - Wholesaler role', 'woocommerce-wholesale-pricing'), array( 'read' => true, 'level_0' => true ) );
				}
				$args = array(
					'role'    => $termObj->slug,
				);
				$users = get_users($args);
				if ( !empty( $users ) ) {
					foreach ( $users as $user ) {
						$user = new WP_User( $user->ID );
						// Remove current subscriber role
						$user->remove_role( $termObj->slug );
						$user->remove_cap( $termObj->slug );
						// Upgrade to editor role
						$user->add_role( $new_slug );
						$user->add_cap( $new_slug );
						wp_cache_delete( $user->ID, 'users' );
					}
				}
			}
		}
		public function wwp_add_new_field() {
			wp_nonce_field('wwp_tax_exempt_nonce', 'wwp_tax_exempt_nonce');
			// version 1.3.0
			$settings=get_option('wwp_vendor_portal_options');
			$variable_subscription_id = !empty($settings['wholesale_subscription']) ? $settings['wholesale_subscription'] : '';
			// ends version 1.3.0
			?>
			<div class="form-field term-tax-wrap">
				<label for="wwp_tax_exmept_wholesaler"><?php esc_html_e('Tax Exempt', 'woocommerce-wholesale-pricing'); ?></label>
				<input type="checkbox" name="wwp_tax_exmept_wholesaler" id="wwp_tax_exmept_wholesaler" value="yes">
				<span><?php esc_html_e('Tax exempt for wholesale user role.', 'woocommerce-wholesale-pricing'); ?></span>
			</div>
			<!-- // version 1.3.0 -->
			<div class="form-field term-coupons-wrap">
				<label for="wwp_wholesale_disable_coupons"><?php esc_html_e('Disable Coupons', 'woocommerce-wholesale-pricing'); ?></label>
				<input type="checkbox" name="wwp_wholesale_disable_coupons" id="wwp_wholesale_disable_coupons" value="yes">
				<span><?php esc_html_e('Disable Coupons for wholesale user role.', 'woocommerce-wholesale-pricing'); ?></span>
			</div>
			<!-- // ends version 1.3.0 -->
			<div class="form-field term-gateways-wrap">
				<label for="wwp_restricted_pmethods_wholesaler"><?php esc_html_e('Disable Payment Methods', 'woocommerce-wholesale-pricing'); ?></label>
				<?php $available_gateways = WC()->payment_gateways->get_available_payment_gateways(); ?>
					<select name="wwp_restricted_pmethods_wholesaler[]" id="wwp_restricted_pmethods_wholesaler" class="regular-text wc-enhanced-select" multiple>
					<?php 
					if ( !empty($available_gateways) ) {
						foreach ( $available_gateways as $key => $method ) {
							echo '<option value="' . esc_attr($key) . '">' . esc_attr($method->title) . '</option>';
						}
					}
					?>
					</select>
					<p><?php esc_html_e('Select payment methods to restrict for wholesale users.', 'woocommerce-wholesale-pricing'); ?></p>
			</div>
			<div class="form-field term-shipping-wrap">
				<label for="wwp_restricted_smethods_wholesaler"><?php esc_html_e('Disable Shipping Methods', 'woocommerce-wholesale-pricing'); ?></label>
				<?php $shipping_methods = WC()->shipping->get_shipping_methods(); ?>
					<select name="wwp_restricted_smethods_wholesaler[]" id="wwp_restricted_smethods_wholesaler" class="regular-text wc-enhanced-select" multiple>
					<?php 
					if ( !empty($shipping_methods) ) {
						foreach ( $shipping_methods as $key => $method ) {
							echo '<option value="' . esc_attr($key) . '" >' . esc_attr($method->method_title) . '</option>';
						}
					}
					?>
					</select>
					<p><?php esc_html_e('Select shipping methods to restrict for wholesale users.', 'woocommerce-wholesale-pricing'); ?></p>
			</div>
			<?php
			// version 1.3.0
			
			if ( !empty($variable_subscription_id) && 'publish' == get_post_status($variable_subscription_id) ) {
				$product = wc_get_product($variable_subscription_id);
				$variations = $product->get_available_variations();
				$variations = $this->wwp_exclude_variations($variations);
				?>
				<div class="form-field term-subscription-wrap">
					<label for="wwp_wholesaler_subscription"><?php esc_html_e('Select Subscription Variation', 'woocommerce-wholesale-pricing'); ?></label>
					<select name="wwp_wholesaler_subscription" id="wwp_wholesaler_subscription">
						<option value=""><?php esc_html_e('Select Subscription Variation', 'woocommerce-wholesale-pricing'); ?></option>
							<?php foreach ( $variations as $key => $variation ) { ?> 
								<option value="<?php echo esc_attr($variation['variation_id']); ?>"><?php echo esc_attr(implode(',', $variation['attributes'])); ?></option>
							<?php }	?>						
						<?php
						// foreach ( $variations as $key => $variation ) {
							// echo wp_kses_post( '<option value="' . esc_attr($variation['variation_id']) . '">' . implode(',', $variation['attributes']) . '</option>' );
						// }
						?>
					</select>
					<p><?php esc_html_e('On the purchase of the selected variation the users will be assigned the this role.', 'woocommerce-wholesale-pricing'); ?></p>
				</div>
				<?php
			}
			// ends version 1.3.0
		}
		
		// version 1.3.0
		public function wwp_exclude_variations( $variations, $mine = '' ) {
			if ( !empty($variations) ) {
				$args = array(
					'hide_empty' => false,
					'fields'		 => 'ids',
					'posts_per_page' => -1,
					'meta_query' => array(
						array(
						   'key'       => 'wwp_wholesaler_subscription',
						   'compare'   => 'EXISTS'
						)
					),
					'taxonomy'  => 'wholesale_user_roles'
				);
				$terms = get_terms( $args );
				if ( !empty($terms) ) {
					foreach ( $terms as $term_id ) {
						$variation_id = get_term_meta($term_id, 'wwp_wholesaler_subscription', true);
						if ( $mine == $variation_id ) {
							continue;
						}
						$variations = array_filter( $variations, 
						function ( $element ) use ( $variation_id ) { 
							return ( $element['variation_id'] != $variation_id ); 
						} );
					}
				}
			}
			return $variations;
		}
		// ends version 1.3.0
		public function wwp_edit_new_field( $term ) {
			$term_id = $term->term_id;
			$tax=get_term_meta($term_id, 'wwp_tax_exmept_wholesaler', true);
			// version 1.3.0
			$coupons=get_term_meta($term_id, 'wwp_wholesale_disable_coupons', true);
			$settings=get_option('wwp_vendor_portal_options');
			$variable_subscription_id = !empty($settings['wholesale_subscription']) ? $settings['wholesale_subscription'] : '';
			$selected_variation = get_term_meta($term_id, 'wwp_wholesaler_subscription', true);
			// ends version 1.3.0
			wp_nonce_field('wwp_tax_exempt_nonce', 'wwp_tax_exempt_nonce');
			?>
			<tr class="form-field term-tax-wrap">
				<th>
					<label for="wwp_tax_exmept_wholesaler">
						<?php esc_html_e('Tax Exempt', 'woocommerce-wholesale-pricing'); ?>
					</label>
				</th>
				<td scope="row">
					<input type="checkbox" name="wwp_tax_exmept_wholesaler" value="yes" <?php checked('yes', $tax); ?>>
					<span><?php esc_html_e('Tax exempt for wholesale user role.', 'woocommerce-wholesale-pricing'); ?></span>
				</td>
			</tr>
			<!-- // version 1.3.0 -->
			<tr class="form-field term-coupons-wrap">
				<th>
					<label for="wwp_wholesale_disable_coupons">
						<?php esc_html_e('Disable Coupons', 'woocommerce-wholesale-pricing'); ?>
					</label>
				</th>
				<td scope="row">
					<input type="checkbox" name="wwp_wholesale_disable_coupons" value="yes" <?php checked('yes', $coupons); ?>>
					<span><?php esc_html_e('Disable Coupons for wholesale user role.', 'woocommerce-wholesale-pricing'); ?></span>
				</td>
			</tr>
			<!-- // ends version 1.3.0 -->
			<tr class="form-field term-gateways-wrap">
				<th><label for="wwp_restricted_pmethods_wholesaler">
					<?php esc_html_e('Disable Payment Methods', 'woocommerce-wholesale-pricing'); ?></label>
				</th>
				<td>
					<?php 
						$value=get_term_meta($term_id, 'wwp_restricted_pmethods_wholesaler', true); 
						$available_gateways = WC()->payment_gateways->get_available_payment_gateways(); 
					?>
					<select name="wwp_restricted_pmethods_wholesaler[]" id="wwp_restricted_pmethods_wholesaler" class="regular-text wc-enhanced-select" multiple>
					<?php 
					if ( !empty($available_gateways) ) {
						foreach ( $available_gateways as $key => $method ) {
							$selected='';
							if ( !empty($value) && in_array($key, $value) ) {
								$selected='selected="selected"';
							}
							echo '<option value="' . esc_attr($key) . '" ' . esc_attr($selected) . '>' . esc_attr($method->title) . '</option>';
						}
					}
					?>
					</select>
					<p><?php esc_html_e('Select payment methods to restrict for wholesale users.', 'woocommerce-wholesale-pricing'); ?></p>
				</td>
			</tr>
			<tr class="form-field term-shipping-wrap">
				<th><label for="wwp_restricted_smethods_wholesaler">
					<?php esc_html_e('Disable Shipping Methods', 'woocommerce-wholesale-pricing'); ?></label>
				</th>
				<td>
					<?php 
						$value=get_term_meta($term_id, 'wwp_restricted_smethods_wholesaler', true); 
						$shipping_methods = WC()->shipping->get_shipping_methods(); 
					?>
					<select name="wwp_restricted_smethods_wholesaler[]" id="wwp_restricted_smethods_wholesaler" class="regular-text wc-enhanced-select" multiple>
					<?php 
					if ( !empty($shipping_methods) ) {
						foreach ( $shipping_methods as $key => $method ) {
							$selected='';
							if ( !empty($value) && in_array($key, $value) ) {
								$selected='selected="selected"';
							}
							echo '<option value="' . esc_attr($key) . '" ' . esc_attr($selected) . '>' . esc_attr($method->method_title) . '</option>';
						}
					}
					?>
					</select>
					<p><?php esc_html_e('Select shipping methods to restrict for wholesale users.', 'woocommerce-wholesale-pricing'); ?></p>
				</td>
			</tr>

			<?php 
			// version 1.3.0
			if ( !empty($variable_subscription_id) && 'publish' == get_post_status($variable_subscription_id) ) {
				$product = wc_get_product($variable_subscription_id);
				$variations = $product->get_available_variations();
				$variations = $this->wwp_exclude_variations($variations, $selected_variation);
				?>
				<tr class="form-field term-subscription-wrap">
					<th><label for="wwp_wholesaler_subscription"><?php esc_html_e('Select Subscription Variation', 'woocommerce-wholesale-pricing'); ?></label></th>
					<td>
						<select name="wwp_wholesaler_subscription" id="wwp_wholesaler_subscription">
							<option value=""><?php esc_html_e('Select Subscription Variation', 'woocommerce-wholesale-pricing'); ?></option>
							<?php foreach ( $variations as $key => $variation ) { ?> 
								<option value="<?php echo esc_attr($variation['variation_id']); ?>" <?php echo selected($selected_variation, $variation['variation_id'], false); ?>><?php echo esc_attr(implode(',', $variation['attributes'])); ?></option>
							<?php }	?>
						</select>
						<p><?php esc_html_e('On the purchase of the selected variation the users will be assigned the this role.', 'woocommerce-wholesale-pricing'); ?></p>
					</td>
				</tr>
				<?php
			}
			// ends version 1.3.0
		}
		
		public function wwp_save_new_field( $term_id, $term ) {
			if ( !isset($_POST['wwp_tax_exempt_nonce']) || !wp_verify_nonce( wc_clean($_POST['wwp_tax_exempt_nonce']), 'wwp_tax_exempt_nonce') ) {
				return;
			}
			if ( isset( $_POST['wwp_tax_exmept_wholesaler'] ) ) {
				update_term_meta($term_id, 'wwp_tax_exmept_wholesaler', 'yes');
			} else {
				update_term_meta($term_id, 'wwp_tax_exmept_wholesaler', 'no');
			}
			// version 1.3.0 
			if ( isset( $_POST['wwp_wholesale_disable_coupons'] ) ) {
				update_term_meta($term_id, 'wwp_wholesale_disable_coupons', 'yes');
			} else {
				update_term_meta($term_id, 'wwp_wholesale_disable_coupons', 'no');
			}
			if ( isset( $_POST['wwp_wholesaler_subscription'] ) ) {
				update_term_meta($term_id, 'wwp_wholesaler_subscription', wc_clean($_POST['wwp_wholesaler_subscription']) );
			} else {
				update_term_meta($term_id, 'wwp_wholesaler_subscription', '');
			}
			// ends version 1.3.0 
			if ( isset( $_POST['wwp_restricted_pmethods_wholesaler'] ) ) {
				update_term_meta($term_id, 'wwp_restricted_pmethods_wholesaler', wc_clean($_POST['wwp_restricted_pmethods_wholesaler']) );
			} else {
				update_term_meta($term_id, 'wwp_restricted_pmethods_wholesaler', '');
			}
			if ( isset( $_POST['wwp_restricted_smethods_wholesaler'] ) ) {
				update_term_meta($term_id, 'wwp_restricted_smethods_wholesaler', wc_clean($_POST['wwp_restricted_smethods_wholesaler']) );
			} else {
				update_term_meta($term_id, 'wwp_restricted_smethods_wholesaler', '');
			}
		}
	}
	new WWP_Wholesale_User_Roles();
}
