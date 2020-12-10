<?php
if (! defined('ABSPATH') ) {
	exit; // Exit if accessed directly
}
/**
 * Class Woo_Wholesale_Bulk_Price
 */
if ( !class_exists('WWP_Wholesale_Bulk_Price') ) {
	class WWP_Wholesale_Bulk_Price {
		public function __construct() {
			add_action('admin_menu', array($this, 'wwp_register_bulk_menu'), 99);
			add_action('wp_ajax_save_single_wholesale_product', array($this, 'save_single_wholesale_product_callback'));
		}
		/**
		 * Register sub menu page
		 * 
		 * @since   1.0
		 * @version 1.0
		 */
		public function wwp_register_bulk_menu() {
			add_submenu_page('wwp_vendor', esc_html__('Bulk Vendor Portal', 'woocommerce-wholesale-pricing'), esc_html__('Bulk Vendor Portal', 'woocommerce-wholesale-pricing'), 'manage_wholesale_bulk_ricing', 'wwp-bulk-wholesale-pricing', array($this,'wwp_bulk_wholesale_pricing_callback'));
		}
		/**
		 * Sub menu page callback
		 * 
		 * @since   1.0
		 * @version 1.0
		 */
		public function wwp_bulk_wholesale_pricing_callback() {
			echo '<h2>' . esc_html__('Bulk Vendor Portal', 'woocommerce-wholesale-pricing') . '</h2>';
			$this->wwp_update_products();
			echo '<form action="#" id="wwp_bulk_form" method="post">';
			wp_nonce_field('wwp_bulk_wholesale_nonce', 'wwp_bulk_wholesale_nonce');
			$this->wwp_bulk_update_options();
			$paged = ( isset($_GET['paged']) && wc_clean($_GET['paged']) ) ? wc_clean($_GET['paged']) : 1;
			$category = ( isset($_GET['category']) && wc_clean($_GET['category']) ) ? wc_clean($_GET['category']) : '';
			$taxonomy = '';
			if ( !empty($category) ) {
				$taxonomy = array(
					'taxonomy' => 'product_cat',
					'field'    => 'term_id',
					'terms'    => array( $category ),
				);
			}
			$post_per_page = 10;
			$offset = ( $post_per_page * $paged ) - $post_per_page;
			$args = array(
				'post_type' => 'product',
				'posts_per_page' => $post_per_page,
				'offset'        => $offset,
				'tax_query' => array(
					$taxonomy,
				)
			);
			$the_query = new WP_Query($args);
			if ($the_query->have_posts()) : 
				$settings=get_option('wwp_vendor_portal_options', true);
				$roles=get_terms(
					'wholesale_user_roles', array(
							'hide_empty' => false,
					) 
				);
				while ( $the_query->have_posts() ) {
					$the_query->the_post();
					$product_ID = get_the_ID();
					$this->wwp_product_tab_content($product_ID, $settings['wholesale_role'], $roles);
					$max_pages = $the_query->max_num_pages;
					$nextpage = $paged + 1;
				}
			endif;
			echo '</form>';
			$cat = ( isset($_GET['category']) && wc_clean($_GET['category']) ) ? '&category=' . wc_clean($_GET['category']) : '';
			if ($max_pages > $paged) {
				echo '<a href="' . esc_url(admin_url('admin.php?page=wwp-bulk-wholesale-pricing' . esc_attr($cat) . '&paged=' . esc_attr($nextpage))) . '">' . esc_html__('Next', 'woocommerce-wholesale-pricing') . ' >> ..</a>';
			}
			$prevpage = max( ( $paged - 1 ), 0 ); //max() will discard any negative value
			if ( 0 !== $prevpage ) {
				echo '<a href="' . esc_url( admin_url('admin.php?page=wwp-bulk-wholesale-pricing' . esc_attr($cat) . '&paged=' . esc_attr($prevpage)) ) . '">.. << ' . esc_html__('Pervious', 'woocommerce-wholesale-pricing') . '</a>';
			}
		}
		/**
		 * Register sub menu page
		 * 
		 * @since   1.0
		 * @version 1.0
		 */
		public function wwp_update_all_products( $prod_id, $price ) {
			if ( !isset($_POST['wwp_bulk_wholesale_nonce']) || !wp_verify_nonce( wc_clean($_POST['wwp_bulk_wholesale_nonce']), 'wwp_bulk_wholesale_nonce') ) {
				return;
			}
			if ( !empty($_POST['wholesale-price']) ) {
				$price = wc_clean($_POST['wholesale-price']);
			}
			if ( isset($_POST['wwp_wholesale_type']) ) {
				update_post_meta($prod_id, '_wwp_wholesale_type', wc_clean($_POST['wwp_wholesale_type']));
			}
			update_post_meta($prod_id, '_wwp_enable_wholesale_item', 'yes');
			update_post_meta($prod_id, '_wwp_wholesale_amount', $price);
		}
		public function wwp_update_variable_product( $prod_id, $variable_id, $price ) {
			if ( !isset($_POST['wwp_bulk_wholesale_nonce']) || !wp_verify_nonce( wc_clean($_POST['wwp_bulk_wholesale_nonce']), 'wwp_bulk_wholesale_nonce') ) {
				return;
			}
			if ( isset($_POST['wwp_wholesale_type']) ) { 
				update_post_meta($prod_id, '_wwp_wholesale_type', wc_clean($_POST['wwp_wholesale_type']));
			}
			update_post_meta($prod_id, '_wwp_enable_wholesale_item', 'yes');
			update_post_meta($variable_id, '_wwp_wholesale_amount', $price);
		}
		public function wwp_update_products() {
			if ( !isset($_POST['wwp_bulk_wholesale_nonce']) || !wp_verify_nonce( wc_clean($_POST['wwp_bulk_wholesale_nonce']), 'wwp_bulk_wholesale_nonce') ) {
				return;
			}
			$updated = -1;
			if ( isset($_POST['wwp_bulk_apply']) ) {
				if ( !empty($_POST['wwp_selected_item']) && is_array(wc_clean($_POST['wwp_selected_item'])) ) {
					foreach ( wc_clean($_POST['wwp_selected_item']) as $p_ids ) {
						if ( isset($_POST['product_type_' . esc_attr($p_ids)]) && 'simple' == wc_clean($_POST['product_type_' . esc_attr($p_ids)]) ) {
							$wholesale_pricing = isset( $_POST['wholesaleprice_' . $p_ids] ) ? wc_clean($_POST['wholesaleprice_' . $p_ids]) : '';
							$this->wwp_update_all_products($p_ids, $wholesale_pricing);
							$updated = 1;
						} elseif ( isset($_POST['product_type_' . esc_attr($p_ids)]) && 'variable' == wc_clean($_POST['product_type_' . esc_attr($p_ids)]) ) {
							$wholesale_variable_id = isset( $_POST['wholesale_variable_id_' . $p_ids] ) ? wc_clean($_POST['wholesale_variable_id_' . $p_ids]) : '';
							foreach ( $wholesale_variable_id as $var_id ) {
								$wholesale_pricing = isset( $_POST['wholesaleprice_' . $var_id] ) ? wc_clean($_POST['wholesaleprice_' . $var_id]) : '';
								if ( isset($_POST['wwp_wholesale_type']) ) {
									update_post_meta($p_ids, '_wwp_wholesale_type', wc_clean($_POST['wwp_wholesale_type']));
								}
								update_post_meta($p_ids, '_wwp_enable_wholesale_item', 'yes');
								$this->wwp_update_all_products($var_id, $wholesale_pricing);
								$updated = 1;
							}
						}
					}
				} else {
					$updated = 0;
				}
			}
			if ( 1 == $updated ) {
				echo wp_kses_post($this->wwp_bulk_notify('success'));
			} elseif ( 0 == $updated ) {
				echo wp_kses_post($this->wwp_bulk_notify('empty'));
			}
		}
		public function wwp_product_tab_content( $product_id, $role_type, $roles ) {
			$regular_price = get_post_meta($product_id, '_regular_price', true);
			$sale_price = get_post_meta($product_id, '_sale_price', true);
			$wholesale_type = get_post_meta($product_id, '_wwp_wholesale_type', true);
			$wholesale_price = get_post_meta($product_id, '_wwp_wholesale_amount', true);
			$_product = wc_get_product($product_id);
			$tickets = new WC_Product_Variable($product_id);
			$variables = $tickets->get_available_variations();
			$data=get_post_meta($product_id, 'wholesale_multi_user_pricing', true);
			$rolehtml='';
			if ( 'multiple' == $role_type && !empty($roles) ) {
				$rolehtml .= '<table class="wholesale_pricing"  cellpadding="10">';
				$rolehtml .= '<tr>';
				$rolehtml .= '<th>' . esc_html__('Wholesale Role', 'woocommerce-wholesale-pricing') . '</th>';
				$rolehtml .= '<th>' . esc_html__('Enable for Role', 'woocommerce-wholesale-pricing') . '</th>';
				$rolehtml .= '<th>' . esc_html__('Discount Type', 'woocommerce-wholesale-pricing') . '</th>';
				$rolehtml .= '<th>' . esc_html__('Wholesale Price', 'woocommerce-wholesale-pricing') . '</th>';
				$rolehtml .= '<th>' . esc_html__('Min Quantity', 'woocommerce-wholesale-pricing') . '</th>';
				$rolehtml .= '</tr>';
				foreach ( $roles as $key => $role ) {
						$min=1;
						$price=$wholesale_price;
						$discount=$wholesale_type;
					if ( isset($data[$role->term_id]) ) {
						$min=isset($data[$role->term_id]['min_quatity'])? $data[$role->term_id]['min_quatity'] : 1;
						$price=isset($data[$role->term_id]['wholesale_price']) ? $data[$role->term_id]['wholesale_price'] : '';
						$discount=isset($data[$role->term_id]['discount_type']) ? $data[$role->term_id]['discount_type'] : '';
					}
					$rolehtml .= '<tr>';
					$rolehtml .= '<td>' . esc_html($role->name) . '</td>';
					$rolehtml .= '<td><input type="checkbox" value="' . esc_attr($role->slug) . '" name="role_' . esc_attr($role->term_id) . '" ' . ( ( isset($data[$role->term_id]) ) ? 'checked' : '' ) . '></td>';
					$rolehtml .= '<td>
									<select class="widefat" name="discount_type_' . esc_attr($role->term_id) . '" value="' . esc_attr($wholesale_type) . '">
										<option value="percent" ' . ( ( 'percent' == $discount ) ? 'selected' : '' ) . '>' . esc_html__('Percent', 'woocommerce-wholesale-pricing') . '</option>
										<option value="fixed" ' . ( ( 'fixed' == $discount ) ? 'selected' : '' ) . '>' . esc_html__('Fixed', 'woocommerce-wholesale-pricing') . '</option>
									</select>
								</td>';
					if ( $_product->is_type('simple') ) {
						$rolehtml.='<td><input class="widefat" type="text" name="wholesale_price_' . esc_attr($role->term_id) . '" value="' . esc_attr($price) . '"> </td>';
					} else {
						$tickets = new WC_Product_Variable($product_id);
						$variables = $tickets->get_available_variations();
						foreach ( $variables as $keey ) {
							$wholesale_price = get_post_meta($keey['variation_id'], '_wwp_wholesale_amount', true);
							$rolehtml.='<td><input type="text" name="wholesaleprice_' . esc_attr($keey['variation_id']) . '" value="' . esc_attr($wholesale_price) . '"/></td>';
						}
					}
					$rolehtml .= '<td><input class="widefat" type="text" name="min_quatity_' . esc_attr($role->term_id) . '" value="' . esc_attr($min) . '"> </td>';
					$rolehtml .= '</tr>';
				}
				$rolehtml .= '</table>';
			}
			$featured_image = get_the_post_thumbnail_url($product_id, 'thumbnail');
			$html = '<div class="flip" id="' . esc_attr($product_id) . '"> <img src="' . esc_url($featured_image) . '" width="30" /> ';
			if ( 'single' == $role_type ) {
				$html.='<input type="checkbox" class="wwp_selected_item" name="wwp_selected_item[]" value="' . esc_attr($product_id) . '"/>';
			}
			$html .= '<span class="wwp-title">' . esc_html(get_the_title()) . '</span><span class="regular_price"><b>' . esc_html__('Regular Price: ', 'woocommerce-wholesale-pricing') . '</b> ' . esc_html($regular_price) . '</span> <span class="rotatingIcon"><i class="fa fa-arrow-down" aria-hidden="true"></i></span>';
			if ( '' != $sale_price ) {
				$html .= '<span class="sales_price">' . esc_html__('Sales Price: ', 'woocommerce-wholesale-pricing') . esc_html($sale_price) . ' </span>';   
			}
			$html .='</div>';
			if ( $_product->is_type('simple') ) {
				if ( 'multiple' == $role_type && !empty($roles) ) {
					$html.= '<div class="product_details" id="pannel-' . esc_attr($product_id) . '">';
						$html.='<div class="wwp-loader"></div>';
						$html.= $rolehtml;
						$html.= '<input type="hidden" name="product_type_' . esc_attr($product_id) . '" value="simple">';
						$html.= '<input type="hidden" name="prod_id_' . esc_attr($product_id) . '" value="' . esc_attr($product_id) . '">';
						$html.= '<div class="wwp-btn"><button data-id="' . esc_attr($product_id) . '" class="button button-primary" id="wholesale_pricing_bulk_update">' . esc_html__('Update', 'woocommerce-wholesale-pricing') . '</button></div>';
					$html.= '</div>';
				} else {
					$html.= '<div class="product_details" id="pannel-' . esc_attr($product_id) . '">';
						$html.= '<p>' . esc_html__('Product Type: Simple', 'woocommerce-wholesale-pricing') . '</p>';
						$html.= '<b>' . esc_html__('Wholesale Type: ', 'woocommerce-wholesale-pricing') . '</b> <input type="text" name="wholesale-type" readonly value="' . esc_attr($wholesale_type) . '">';
						$html.= '<b>' . esc_html__('Wholesale Price: ', 'woocommerce-wholesale-pricing') . '</b>';
						$html.= '<input type="text" name="wholesaleprice_' . esc_attr($product_id) . '" value="' . esc_attr($wholesale_price) . '">';
						$html.= '<input type="hidden" name="product_type_' . esc_attr($product_id) . '" value="simple">';
						$html.= '<input type="hidden" name="prod_id_' . esc_attr($product_id) . '" value="' . esc_attr($product_id) . '">';
					$html.= '</div>';
				}
			} else {
				$wholesale_type = get_post_meta($product_id, '_wwp_wholesale_type', true);
				if ( 'multiple' == $role_type && !empty($roles)) {
					$html .='<div class="product_details" id="pannel-' . esc_attr($product_id) . '">';
					//here we are stuck
					$tickets = new WC_Product_Variable($product_id);
					$variables = $tickets->get_available_variations();
					$html.='<div class="wwp-loader"></div>';
					if (!empty($variables)) {
						$html.='<table class="wholesale_pricing" cellpadding="10">';
						$html.='<input type="hidden" name="product_type_' . esc_attr($product_id) . '" value="variable">';
						$html.='<tr>'; 
						$html.='<th>' . esc_html__('Wholesale Role', 'woocommerce-wholesale-pricing') . '</th>'; 
						$html.='<th>' . esc_html__('Enable for Role', 'woocommerce-wholesale-pricing') . '</th>'; 
						$html.='<th>' . esc_html__('Discount Type', 'woocommerce-wholesale-pricing') . '</th>'; 
						$html.='<th>' . esc_html__('Wholesale Price & Min Quantity (Per Variation)', 'woocommerce-wholesale-pricing') . '</th>'; 
						$html.='</tr>'; 
						foreach ( $roles as $key => $role ) {
							$html .= '<tr>'; 
							$html .= '<td>'; 
							$html .= esc_html($role->name);
							$html .= '</td>'; 
							$html .= '<td><input type="checkbox" value="' . esc_attr($role->slug) . '" name="role_' . esc_attr($role->term_id) . '" ' . ( ( isset($data[$role->term_id]) ) ? 'checked' : '' ) . '></td>';
							$html.='<td><select class="widefat" name="discount_type_' . esc_attr($role->term_id) . '" value="' . esc_attr($wholesale_type) . '">
							<option value="percent" ' . ( ( 'percent' == $discount ) ? 'selected' : '' ) . '>' . esc_html__('Percent', 'woocommerce-wholesale-pricing') . '</option>
							<option value="fixed"  ' . ( ( 'fixed' == $discount ) ? 'selected' : '' ) . '>' . esc_html__('Fixed', 'woocommerce-wholesale-pricing') . '</option>
							</select></td>';
							$html .='<td>';     
							$html .= '<div class="wwp-variable">';
							$wholesale_price_pro_ids = 'wholesale_variable_id_' . esc_attr($product_id) . '[]';
							$wholesale_price_qty = esc_html__(' Qty: ', 'woocommerce-wholesale-pricing');
							$wholesale_prod_type = 'product_type_' . esc_attr($product_id);
							$wholesale_prod_id = 'prod_id_' . esc_attr($product_id);
							foreach ( $variables as $key ) {
								$variation_id = $key['variation_id'];
								$wholesale_price_var_name = 'wholesaleprice_' . esc_attr($role->term_id) . '_' . esc_attr($key['variation_id']);
								$wholesale_price_qty_name = 'qty_' . esc_attr($role->term_id) . '_' . esc_attr($key['variation_id']);
								if ( isset($data[$role->term_id][$variation_id]) ) {
									$wholesale_price=$data[$role->term_id][$variation_id]['wholesaleprice'];
									$qty=$data[$role->term_id][$variation_id]['qty'];
								} else {
									$sale_price = get_post_meta($key['variation_id'], '_sale_price', true);
									$wholesale_price = get_post_meta($key['variation_id'], '_wwp_wholesale_amount', true);
									$qty=1;
								}
								$regular_price = get_post_meta($key['variation_id'], '_regular_price', true);
								$html .= '<div class="variable-item"><span class="variation"> #' . esc_html($variation_id) . ' </span><label>' . esc_html__('Regular Price', 'woocommerce-wholesale-pricing') . '</label><input type="text" readonly name="reg-price" value="' . esc_attr($regular_price) . '"/> <label>' . esc_html__('Wholesale Price', 'woocommerce-wholesale-pricing') . '</label>';
								$html .= '<input type="text" name="' . esc_attr($wholesale_price_var_name) . '" value="' . esc_attr($wholesale_price) . '"/>';
								$html .= '<label>' . esc_html($wholesale_price_qty) . '<input type="number" name="' . esc_attr($wholesale_price_qty_name) . '" value="' . esc_attr($qty) . '"/></label>';
								$html .= '<input type="hidden" name="' . esc_attr($wholesale_price_pro_ids) . '" value="' . esc_attr($key['variation_id']) . '">';
								$html .= '<input type="hidden" name="' . esc_attr($wholesale_prod_type) . '" value="variable">';
								$html .= '<input type="hidden" name="' . esc_attr($wholesale_prod_id) . '" value="' . esc_attr($product_id) . '">
								</div>';
							}
							$html .= '</div>';
							$html .='</td>';
							$html .='</tr>';
						}
						$html .='</table>';
						$html .= '<div class="wwp-btn"><button data-id="' . esc_attr($product_id) . '" class="button button-primary" id="wholesale_pricing_bulk_update">' . esc_html__('Update', 'woocommerce-wholesale-pricing') . '</button></div>';
					} else {
						$html .= esc_html__('No variations found. Add variations before.', 'woocommerce-wholesale-pricing');
					}
					$html .= '</div>';
				} else {
					$html .= '<div class="product_details" id="pannel-' . esc_attr($product_id) . '">';
					$html .= '<p>' . esc_html__('Product Type: Variable') . '</p>';
					$html .= '<b>' . esc_html__('Wholesale Type:', 'woocommerce-wholesale-pricing') . ' </b> <select name="wholesale-type">';
					$html .= '<option ' . ( ( 'percent' == $wholesale_type ) ? 'selected' : '' ) . ' value="percent">' . esc_html__('Percent', 'woocommerce-wholesale-pricing') . '</option>';
					$html .= '<option ' . ( ( 'fixed' == $wholesale_type ) ? 'selected' : '' ) . ' value="fixed">' . esc_html__('Fixed', 'woocommerce-wholesale-pricing') . '</option>';    
					$html.= '</select>';    
					$tickets = new WC_Product_Variable($product_id);
					$variables = $tickets->get_available_variations();
					$wholesale_price_pro_ids = 'wholesale_variable_id_' . esc_attr($product_id) . '[]';
					$wholesale_prod_type = 'product_type_' . esc_attr($product_id);
					$wholesale_prod_id = 'prod_id_' . esc_attr($product_id);
					$html.= '<div class="wwp-variable">';
					foreach ( $variables as $key ) {
						$wholesale_price_var_id = 'wholesaleprice_' . esc_attr($key['variation_id']);
						$wholesale_price_qty_name = 'wholesaleprice_' . esc_attr($key['variation_id']);
						$regular_price = get_post_meta($key['variation_id'], '_regular_price', true);
						$sale_price = get_post_meta($key['variation_id'], '_sale_price', true);
						$wholesale_price = get_post_meta($key['variation_id'], '_wwp_wholesale_amount', true);
						$html .= '<div class="variable-item">' . ( isset($key['name']) ? esc_html($key['name']) : '' ) . ' <label>' . esc_html__('Regular Price', 'woocommerce-wholesale-pricing') . '</label>';
						$html .= '<input type="text" readonly name="reg-price" value="' . esc_attr($regular_price) . '"/>';
						$html .= '<label>' . esc_html__('Wholesale Price', 'woocommerce-wholesale-pricing') . '</label>';
						$html .= '<input type="text" name="' . esc_attr($wholesale_price_var_id) . '" value="' . esc_attr($wholesale_price) . '"/>';
						$html .= '<input type="hidden" name="' . esc_attr($wholesale_price_pro_ids) . '" value="' . esc_attr($key['variation_id']) . '">';
						$html .= '<input type="hidden" name="' . esc_attr($wholesale_prod_type) . '" value="variable">';
						$html .= '<input type="hidden" name="' . esc_attr($wholesale_prod_id) . '" value="' . esc_attr($product_id) . '">
						</div>';
					}
					$html .= '</div>';
					$html .= '</div>';
				}
			}
			echo wp_kses($html, $this->wwp_allowed_tags() );
		}
		private function wwp_allowed_tags() {
			$allowed_tags = array(
				'a' => array(
					'class' => array(),
					'href'  => array(),
					'rel'   => array(),
					'title' => array(),
				),
				'b' => array(),
				'del' => array(
					'datetime' => array(),
					'title' => array(),
				),
				'dd' => array(),
				'select' => array(
					'id' => array(),
					'class' => array(),
					'title' => array(),
					'style' => array(),
					'name' => array(),
					'disabled' => array(),
				),
				'table' => array(
					'id' => array(),
					'class' => array(),
					'style' => array(),
					'cellpadding' => array(),
				),
				'tr' => array(
					'id' => array(),
					'class' => array(),
					'style' => array(),
				),
				'td' => array(
					'id' => array(),
					'class' => array(),
					'style' => array(),
				),
				'th' => array(
					'id' => array(),
					'class' => array(),
					'style' => array(),
				),
				'button' => array(
					'id' => array(),
					'class' => array(),
					'type' => array(),
					'style' => array(),
					'value' => array(),
					'placeholder' => array(),
					'name' => array(),
					'data-id' => array(),
				),
				'input' => array(
					'id' => array(),
					'class' => array(),
					'type' => array(),
					'style' => array(),
					'value' => array(),
					'placeholder' => array(),
					'name' => array(),
					'data' => array(),
					'checked' => array(),
					'readonly' => array(),
					'disabled' => array(),
				),
				'option' => array(
					'selected' => array(),
					'value' => array(),
				),
				'div' => array(
					'id' => array(),
					'class' => array(),
					'title' => array(),
					'style' => array(),
				),
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
					'id' => array(),
					'class' => array(),
					'style' => array(),
				),
				'span' => array(
					'class' => array(),
					'id' => array(),
					'title' => array(),
					'style' => array(),
				),
				'label' => array(
					'for' => array(),
					'id' => array(),
					'class' => array(),
				),
				'strike' => array(),
				'strong' => array(),
				'ul' => array(
					'class' => array(),
				),
			);
			return $allowed_tags;
		}
		public function wwp_bulk_update_options() {
			ob_start(); 
			?>
			<div class="bulk-options">
				<div class="wwp_bulk_filter">
					<b><?php esc_html_e('Filter Products By Category', 'woocommerce-wholesale-pricing'); ?></b>
					<?php $this->wwp_get_product_category(); ?>
				</div>
				<?php $settings=get_option('wwp_vendor_portal_options', true); ?>
				<?php if ( isset($settings['wholesale_role']) && 'single'== $settings['wholesale_role'] ) { ?>      
					<div class="wwp_bulk_controls">
						<div class="wwp_bulk_field">
							<input type="checkbox" id="wwp_all_products" name="wwp_all_products"/>
							<label for="wwp_all_products"><?php esc_html_e('Select All', 'woocommerce-wholesale-pricing'); ?></label>
						</div>
						<div class="wwp_bulk_field">
							<b><?php esc_html_e('Wholesale Type', 'woocommerce-wholesale-pricing'); ?></b>
							<select name="wwp_wholesale_type">
								<option value="percent"><?php esc_html_e('Percent', 'woocommerce-wholesale-pricing'); ?></option>
								<option value="fixed"><?php esc_html_e('Fixed', 'woocommerce-wholesale-pricing'); ?></option>
							</select>
						</div>
						<div class="wwp_bulk_field">
							<b><?php esc_html_e('Enter Wholesale Price', 'woocommerce-wholesale-pricing'); ?></b>
							<input type="text" name="wholesale-price">
						</div>
						<div class="wwp_bulk_field">
							<input type="submit" class="button" name="wwp_bulk_apply" value="<?php esc_html_e('Update', 'woocommerce-wholesale-pricing'); ?>"/> 
						</div>
					</div>
					<?php
				} 
				?>
			</div>
			<?php
			echo wp_kses(ob_get_clean(), $this->wwp_allowed_tags() );
		}
		public function wwp_bulk_notify( $notify ) {
			$notify_msg = array();
			$notify_msg['error'] = esc_html__('Error, Something wrong while saving wholesale prices.', 'woocommerce-wholesale-pricing');
			$notify_msg['empty'] = '<b>' . esc_html__('Error!', 'woocommerce-wholesale-pricing') . '</b> ' . esc_html__('No Product Selected', 'woocommerce-wholesale-pricing');
			$notify_msg['success'] = esc_html__('Products have been updated successfully', 'woocommerce-wholesale-pricing');
			$html = '<div class="wwp_notify ' . esc_attr($notify) . '">' . wp_kses_post($notify_msg[$notify]) . '</div>';
			return $html;
		}
		public function wwp_extract_id( $extract_to ) {
			$iliminate_text = strstr($extract_to, '_');
			$product_id = str_replace('_', '', $iliminate_text);
			return $product_id;
		}
		public function wwp_get_product_category() {
			$terms = get_terms(
				array(
				'taxonomy' => 'product_cat',
				'hide_empty' => true,
				)
			);
			echo '<select name="wwp_prod_cat" class="wwp_prod_cat">';
			echo '<option value="">' . esc_html__('Select Category', 'woocommerce-wholesale-pricing') . '</option>';
			foreach ( $terms as $term ) {
				$selected = '';
				if ( isset($_GET['category']) && $_GET['category'] == $term->term_id ) {
					$selected = 'selected';
				} 
				echo '<option value="' . esc_attr__($term->term_id) . '" ' . esc_html($selected) . ' >' . esc_html__($term->name) . '</option>';
			}
			echo '</select>';
		}
		/**
		 * Ajax function to retrieve meta html
		 * 
		 * @since   1.0
		 * @version 1.0
		 */
		public function save_single_wholesale_product_callback() {
			
			check_ajax_referer( 'wwp_wholesale_pricing', 'security' );
			
			if ( !isset($_POST['product_id']) || !is_numeric($_POST['product_id']) ) {
				die();
			}
			$product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : '';
			
			if ( '' == $product_id ) {
				return;
			}
			$roles=get_terms(
				'wholesale_user_roles', array(
				'hide_empty' => false,
				)
			);
			$params = array();
			if ( isset($_POST['data']) ) {
				parse_str( wc_clean($_POST['data']), $params );
			}
			$data=array();
			$ptype = isset($params['product_type_' . $product_id]) ? $params['product_type_' . $product_id ] : '';
			if ( !empty($roles) ) {
				if ( 'variable' == $ptype ) {
					$tickets = new WC_Product_Variable($product_id);
					$variables = $tickets->get_available_variations();
					if ( !empty($variables) ) {
						foreach ( $variables as $variable ) {
							$variation_id=$variable['variation_id'];
							$vary=array();
							foreach ( $roles as $key => $role ) {
								if ( isset($role->term_id) && isset($params['role_' . $role->term_id]) ) {
									if ( isset($params['role_' . $role->term_id]) ) {
										$data[$role->term_id]['slug'] = $role->slug;
										$vary[$role->term_id]['slug'] = $role->slug;
									}
									if ( isset($params['discount_type_' . $role->term_id]) ) {
										$data[$role->term_id]['discount_type'] = $params['discount_type_' . $role->term_id];
										$vary[$role->term_id]['discount_type'] = $params['discount_type_' . $role->term_id];
									}
									if ( isset($params['wholesaleprice_' . $role->term_id . '_' . $variation_id]) ) {
										$data[$role->term_id][$variation_id]['wholesaleprice']=is_numeric($params['wholesaleprice_' . $role->term_id . '_' . $variation_id]) ? $params['wholesaleprice_' . $role->term_id . '_' . $variation_id] : '';
										$vary[$role->term_id][$variation_id]['wholesaleprice']=is_numeric($params['wholesaleprice_' . $role->term_id . '_' . $variation_id]) ? $params['wholesaleprice_' . $role->term_id . '_' . $variation_id] : '';
									}
									if ( isset($params['qty_' . $role->term_id . '_' . $variation_id]) ) {
										$data[$role->term_id][$variation_id]['qty']=is_numeric($params['qty_' . $role->term_id . '_' . $variation_id]) ? $params['qty_' . $role->term_id . '_' . $variation_id] : 1;
										$vary[$role->term_id][$variation_id]['qty']=is_numeric($params['qty_' . $role->term_id . '_' . $variation_id]) ? $params['qty_' . $role->term_id . '_' . $variation_id] : 1;
									}
								}
							}
							update_post_meta($variation_id, 'wholesale_multi_user_pricing', $vary);
						} 
					} 
				} else {
					foreach ( $roles as $key => $role ) {
						if ( !isset($params['role_' . esc_attr($role->term_id)]) ) {
							continue;
						} 
						if ( isset($params['role_' . esc_attr($role->term_id)]) ) {
							$data[$role->term_id]['slug'] = esc_attr($role->slug);
						}
						if ( isset($params['discount_type_' . $role->term_id]) ) {
							$data[$role->term_id]['discount_type']=$params['discount_type_' . esc_attr($role->term_id)];
						}
						if ( isset($params['wholesale_price_' . esc_attr($role->term_id)]) ) {
							$data[$role->term_id]['wholesale_price']=is_numeric($params['wholesale_price_' . esc_attr($role->term_id)]) ? $params['wholesale_price_' . esc_attr($role->term_id)] : '';
						}
						if ( isset($params['min_quatity_' . $role->term_id]) ) {
							$data[$role->term_id]['min_quatity'] = is_numeric($params['min_quatity_' . esc_attr($role->term_id)]) ? $params['min_quatity_' . esc_attr($role->term_id)] : 1;
						}
					}
				}
			}
			update_post_meta($product_id, 'wholesale_multi_user_pricing', $data);
			die();
		}
	}
	new WWP_Wholesale_Bulk_Price();
}
