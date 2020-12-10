<?php
if ( !defined('ABSPATH') ) {
	exit; // Exit if accessed directly
}
if ( ! function_exists( 'shapeSpace_allowed_html' ) ) :
	function shapeSpace_allowed_html() {
	
		$allowed_atts = array(
		'align'      => array(),
		'class'      => array(),
		'type'       => array(),
		'id'         => array(),
		'dir'        => array(),
		'lang'       => array(),
		'style'      => array(),
		'xml:lang'   => array(),
		'src'        => array(),
		'alt'        => array(),
		'href'       => array(),
		'rel'        => array(),
		'rev'        => array(),
		'target'     => array(),
		'novalidate' => array(),
		'type'       => array(),
		'value'      => array(),
		'name'       => array(),
		'tabindex'   => array(),
		'action'     => array(),
		'method'     => array(),
		'for'        => array(),
		'width'      => array(),
		'height'     => array(),
		'data'       => array(),
		'title'      => array(),
		'value'      => array(),
		'selected'	=> array(),
		'enctype'	=> array(),
		'disable'	=> array(),
		'disabled'	=> array(),
		);
		$allowedposttags['form']	= $allowed_atts;
		$allowedposttags['label']	= $allowed_atts;
		$allowedposttags['select']	= $allowed_atts;
		$allowedposttags['option']	= $allowed_atts;
		$allowedposttags['input']	= $allowed_atts;
		$allowedposttags['textarea']	= $allowed_atts;
		$allowedposttags['iframe']	= $allowed_atts;
		$allowedposttags['script']	= $allowed_atts;
		$allowedposttags['style']	= $allowed_atts;
		$allowedposttags['strong']	= $allowed_atts;
		$allowedposttags['small']	= $allowed_atts;
		$allowedposttags['table']	= $allowed_atts;
		$allowedposttags['span']	= $allowed_atts;
		$allowedposttags['abbr']	= $allowed_atts;
		$allowedposttags['code']	= $allowed_atts;
		$allowedposttags['pre']	= $allowed_atts;
		$allowedposttags['div']	= $allowed_atts;
		$allowedposttags['img']	= $allowed_atts;
		$allowedposttags['h1']	= $allowed_atts;
		$allowedposttags['h2']	= $allowed_atts;
		$allowedposttags['h3']	= $allowed_atts;
		$allowedposttags['h4']	= $allowed_atts;
		$allowedposttags['h5']	= $allowed_atts;
		$allowedposttags['h6']	= $allowed_atts;
		$allowedposttags['ol']	= $allowed_atts;
		$allowedposttags['ul']	= $allowed_atts;
		$allowedposttags['li']	= $allowed_atts;
		$allowedposttags['em']	= $allowed_atts;
		$allowedposttags['hr']	= $allowed_atts;
		$allowedposttags['br']	= $allowed_atts;
		$allowedposttags['tr']	= $allowed_atts;
		$allowedposttags['td']	= $allowed_atts;
		$allowedposttags['p']	= $allowed_atts;
		$allowedposttags['a']	= $allowed_atts;
		$allowedposttags['b']	= $allowed_atts;
		$allowedposttags['i']	= $allowed_atts;
		return $allowedposttags;
	}
endif;

if ( ! function_exists( 'wvp_get_post_data' ) ) :
	function wvp_get_post_data( $name ) { 
		if ( isset($_POST['wvp_vendor_registrattion_nonce']) || wp_verify_nonce( wc_clean($_POST['wvp_vendor_registrattion_nonce']), 'wvp_vendor_registrattion_nonce') ) {
			$post = $_POST;
		}
		$post = $_POST;
		if ( isset( $post[$name] ) ) { 
			return apply_filters( 'wvp_get_post_data', wp_kses_post( $post[$name] ) ); 
		}
	}
endif;

if ( ! function_exists( 'vendor_tab_link' ) ) :
	function vendor_tab_link( $tab = '' ) {
		
		if (!empty($tab)) {
			return admin_url( 'admin.php?page=wvp-registration-setting&tab=' ) . $tab;
		} else {
			return admin_url( 'admin.php?page=wvp-registration-setting' );
		}
	}
endif;

if ( ! function_exists( 'vendor_tab_active' ) ) :
	function vendor_tab_active( $active_tab = '' ) {
		$getdata = '';
		if (isset($_GET['tab'])) {
			$getdata = sanitize_text_field($_GET['tab']);
		}
		
		if ( $getdata == $active_tab ) {
			return 'nav-tab-active';
		} 
	}
endif;

if ( ! function_exists( 'vendor_content_tab_active' ) ) :
	function vendor_content_tab_active( $active_tab = '' ) {
		$getdata = '';		
		if (isset($_GET['tab'])) {
			$getdata = sanitize_text_field($_GET['tab']);
		}
		
		if ( $getdata == $active_tab ) {
			return 'bolck';
		} else {
			return 'none';
		}
	}
endif;

if ( ! function_exists( 'vendor_load_form_builder' ) ) :
	function vendor_load_form_builder( $active_tab = '' ) {
		$tab = '';
		if (isset($_GET['tab'])) {
			$tab = sanitize_text_field($_GET['tab']);
		}
		
		if ( 'extra-fields' != $tab ) { 
			return true;
		} else {
			return false;
		}
	}
endif;


if ( ! function_exists( 'is_contractor_user' ) ) :
	function is_contractor_user ( $user_id ) {
		if ( !empty($user_id) ) {
			$user_info = get_userdata($user_id);
			
			$user_role = implode(', ', $user_info->roles);
			$allterms = get_terms('vendor_user_roles', array('hide_empty' => false));
	 
			foreach ($allterms as $allterm_key => $allterm_value ) {
				if ( $user_role == $allterm_value->slug ) {
					return true;
				}
			}
			if ( 'default_contractor' == $user_role ) {
				return true;
			}
		}
		return false;
	}
endif;



if ( ! function_exists( 'multi_vendor_product_ids' ) ) :
	function multi_vendor_product_ids() {
		$cate = array();
		$total_ids = array();

		$categories = get_terms( array( 'taxonomy' => 'product_cat' ) );
		
		if ( is_array($categories) ) {
		
			foreach ( $categories as $category ) {
				
				$data = get_term_meta($category->term_id, 'vendor_multi_user_pricing', true);
					
				if ( !empty($data) ) {	
					foreach ( $data as $key => $value) {
						
						if ( isset ( $data[$key]['vendor_price'] ) ) {
							$cate[] = $category->term_id;
						} 
					}	
				}	
			} 
			
			$cate = array_unique($cate);
			
			$args = array(
				'post_type'		=> 'product',
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
			
			$data = get_post_meta($id, 'vendor_multi_user_pricing', true);
			if ( !empty($data) ) {
				foreach ( $data as $key => $value ) {
					if ( isset($data[$key]) ) {	
						$total_ids[] =	$id ;
					}
				}
			}
		}
		
		if ( is_array($ids) ) {
			foreach ($ids  as $id) {
				$total_ids[] =	$id;
			}
		}
		return array_unique(  $total_ids ) ;
	 
	}
endif;

if ( ! function_exists( 'single_vendor_product_ids' ) ) :
	function single_vendor_product_ids() {
		$cate = array();
		$total_ids = array();

		$categories = get_terms( array( 'taxonomy' => 'product_cat' ) );
		
		if ( is_array($categories) ) {
		
			foreach ( $categories as $category ) {
				 
				if ( 'yes' == get_term_meta($category->term_id, '_wvp_enable_vendor_item', true) ) {
					$cate[] = $category->term_id;
				}
		
			} 
			
			$cate = array_unique($cate);
			$args = array(
				'post_type'		=> 'product',
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
						$total_ids[] =	$id;
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
			
			if ( 'yes' == get_post_meta($id, '_wvp_enable_vendor_item', true)) {	
				$total_ids[] =	$id ;
			}
		}
		
		if ( is_array($ids) ) {
			foreach ($ids  as $id) {
				$total_ids[] =	$id;
			}
		}
		return array_unique(  $total_ids ) ;
	 
	}
endif;

if ( ! function_exists( 'refresh_structure_form' ) ) :
	function refresh_structure_form( $formData ) {
		$formData = json_decode( $formData );
		if ( is_array ( $formData  ) ) {
			foreach ( $formData as $formData_key => $formData_value ) {
				if ( isset( $formData_value->userData ) ) {
					if ( isset( $formData_value->required ) ) {
						$formData[$formData_key]->required = false;
					}
					if ( isset( $formData_value->values ) ) {
						foreach ( $formData_value->values as $formData_value_key => $formData_value_val ) {
							$formData[$formData_key]->values[$formData_value_key]->selected = false;
						}
					}
				}
			}
		}
		return json_encode($formData);
	}
endif;

if ( ! function_exists( 'wvp_render_characters_remove' ) ) :
	function wvp_render_characters_remove( $formData ) {
		$formData = refresh_structure_form( $formData );
		$formData = str_replace( "'", '&#39;', $formData ); 
		return apply_filters( 'wvp_render_characters_remove', $formData );
	}
endif;

if ( ! function_exists( 'render_form_builder' ) ) :
	function render_form_builder ( $callbach_data_form, $user_id = '' ) { 
		?>
		<div id="container-wrap">
			<div class="render-wrap"></div>
			<input type="hidden" name="wvp_form_data_json" id="wvp_form_data_json"  value="">
		</div>
		<script>
			jQuery( document ).ready(function($) {
				<?php if ( 'get_option' == $callbach_data_form ) { ?>
			 
					formData = <?php echo wp_kses_post(wvp_render_characters_remove(get_option('wvp_save_form'))); ?>;
				 
					<?php } elseif ( 'get_post_meta' == $callbach_data_form ) { ?>
					 
					formData = <?php echo wp_kses_post(wvp_render_characters_remove(get_post_meta( $user_id , 'wvp_form_data_json', true ))); ?>;

					<?php } else { ?>
				
						<?php if ( !empty($user_id) &&  !empty(get_user_meta( $user_id , 'wvp_form_data_json', true )) ) { ?> 
						
						formData = <?php echo wp_kses_post(wvp_render_characters_remove(get_user_meta( $user_id , 'wvp_form_data_json', true ))); ?>;
							
						<?php } else { ?>
							formData = <?php echo wp_kses_post(wvp_render_characters_remove(get_option('wvp_save_form'))); ?>;
						<?php } ?>
				
					<?php } ?>
					
			wvp_filter_css = "<?php echo wp_kses_post( registration_form_class(' woocommerce-form-row woocommerce-form-row--wide form-row-wide wvp_form_css_row ') ); ?>";		
			// Remove all br tags	
			//formData = formData.replace(/\//g,'');
			//formData = formData.replace(/<br >/g,'');
			//formData = formData.replace(/(\r\n|\n|\r)/gm," ");
			//console.log(formData);
			render_wrap = jQuery('.render-wrap').formRender({
				formData,
				layoutTemplates: {
					default: function( field, label, help, data ) {
						
						if ( data.type == 'checkbox-group' || data.type == 'radio-group') {
							//return $('<div/>').append(label, field, help);
							  return $('<p/>').addClass(wvp_filter_css).append(label, field, help);
						 } else {
							 
							 return $('<p/>').addClass(wvp_filter_css +" form-row").append(label, field, help);
						 }
					}
				},
			});
			jQuery("input,textarea").keyup(function() { 
				wvp_set_json_to_hidden_field(); 
			});
			jQuery("select,input,textarea").change(function() { 
				wvp_set_json_to_hidden_field(); 
			});
			jQuery("ul.formbuilder-autocomplete-list li").click(function(){ 
				wvp_set_json_to_hidden_field();
			});
			jQuery("input,textarea").on("input paste", function() { 
				wvp_set_json_to_hidden_field();
			});
			
			function wvp_set_json_to_hidden_field() { 
				jQuery('#wvp_form_data_json').val(window.JSON.stringify(jQuery(render_wrap).formRender("userData")));
				 console.log(render_wrap.userData);
				return true;
			}
			
			wvp_set_json_to_hidden_field();
			
			<?php if ( !is_admin() ) { ?>
			jQuery('.formBuilder-injected-style').remove();
			<?php } ?>
			
			});
		</script>
		<?php 
	}
endif;

if ( ! function_exists( 'wvp_vendor_css' ) ) : 
	function wvp_vendor_css( $settings ) { 
		if ( isset($settings['vendor_css']) ) { 
			?>
			<style type="text/css">
			<?php echo wp_kses_post(apply_filters( 'wvp_vendor_registration_form_css', $settings['vendor_css'] )); ?>
			</style>
			<?php 
		} 
	} 
endif;

if ( ! function_exists( 'registration_form_class' ) ) : 
	function registration_form_class( $css ) { 
		return apply_filters( 'registration_form_class', $css );
	}
endif;

if ( ! function_exists( 'wvp_elements' ) ) : 
	function wvp_elements( $elements ) { 
		echo wp_kses_post( apply_filters( 'wvp_vendor_registration_form_elements', $elements ) );
	}
endif;

if ( ! function_exists( 'form_builder_update_user_meta' ) ) : 
	function form_builder_update_user_meta ( $user_id ) { 
		if ( isset($_POST['wvp_vendor_registrattion_nonce']) || wp_verify_nonce( wc_clean($_POST['wvp_vendor_registrattion_nonce']), 'wvp_vendor_registrattion_nonce') ) {
			return;
		}
		if (isset($_POST['wvp_form_data_json']) && !empty($_POST['wvp_form_data_json'])) {
			
			$wvp_form_data_json = json_decode( stripslashes( wvp_get_post_data( 'wvp_form_data_json' ) ), true );
			
			foreach ( $wvp_form_data_json as $formdata_key => $meta_value ) {
				
				$meta_key = 'form_builder_' . str_replace(' ', '_', $meta_value['label']);
				update_user_meta( $user_id, $meta_key, $meta_value );
				
			}
			
		}
	}
endif;

if ( ! function_exists( 'wvp_get_tax_price_display_suffix' ) ) : 
	function wvp_get_tax_price_display_suffix( $product_id ) { 
		global $woocommerce;
		$product = wc_get_product( $product_id );
		
		$tax_display_suffix = '';

		if ($woocommerce->customer->is_vat_exempt() == false && get_option('woocommerce_price_display_suffix') && 'taxable' == get_post_meta($product_id, '_tax_status', true)) {
			$tax_display_suffix = get_option('woocommerce_price_display_suffix');
			$tax_display_suffix = '<small class="woocommerce-price-suffix">' . $tax_display_suffix . '</small>';
		}
		
		$price_r = wc_get_price_including_tax( $product, array('price' => 1 ) );
		
		if ( 1 == $price_r ) {
			$tax_display_suffix = '';
		}
		
		return apply_filters( 'wvp_get_tax_price_display_suffix', $tax_display_suffix );
	}
endif;

if ( ! function_exists( 'wvp_get_price_including_tax' ) ) : 
	function wvp_get_price_including_tax( $product, $args = array() ) { 
		global $woocommerce;

		if ($woocommerce->customer->is_vat_exempt() == false && 'taxable' == get_post_meta($product->get_id(), '_tax_status', true)) {
			if ( ( is_product() || is_product_category() || is_shop() ) && 'excl' == get_option('woocommerce_tax_display_shop') ) { 
				$price = $args['price'];
			} else {
				$price = wc_get_price_including_tax( $product, array('price' => $args['price'] ) );
			}
		} else {
			$price = $args['price'];
		}
		
		return apply_filters( 'wvp_get_price_including_tax', $price , $product );
	}
endif;

