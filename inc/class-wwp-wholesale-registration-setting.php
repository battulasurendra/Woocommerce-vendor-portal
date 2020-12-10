<?php
if (! defined('ABSPATH') ) {
	exit; // Exit if accessed directly
}
/**
 * Class Woo_Wholesale_Registration_Page_Setting
 */
if ( !class_exists('Wwp_Wholesale_Registration_Page_Setting') ) {

	class Wwp_Wholesale_Registration_Page_Setting {
		
		public function __construct() {
			add_action('admin_menu', array($this, 'wwp_registration_page_setting' ));
			// enable to display tax id in billing address
			add_action( 'woocommerce_admin_order_data_after_billing_address', array($this, 'wwp_tax_display_admin_order_meta'), 10, 1 );
			add_action('admin_init', array($this, 'enqueue_front_scripts'));
			
			add_action( 'wp_ajax_wwp_save_form', array($this, 'ajax_call_wwp_save_form') );
			add_action( 'wp_ajax_nopriv_wwp_save_form', array($this, 'ajax_call_wwp_save_form') );
		}
		
		public function ajax_call_wwp_save_form() {
			 
			if (wwp_get_post_data('formData')) {
				update_option( 'wwp_save_form', stripslashes(wwp_get_post_data('formData')) );
				die('save');
			}
		}
		
		public function enqueue_front_scripts() { 
		 
			//wp_enqueue_script( 'wwp_jquery', plugin_dir_url( __DIR__ ) . 'assets/js/formbuilder/jquery.min.js', array(), '1.0.0'  );
			wp_enqueue_script( 'wwp_jquery-ui', plugin_dir_url( __DIR__ ) . 'assets/js/formbuilder/jquery-ui.min.js', array(), '1.0.0'  );
			wp_enqueue_script( 'wwp_formbuilder', plugin_dir_url( __DIR__ ) . 'assets/js/formbuilder/form-builder.min.js', array(), '1.0.0' );
			wp_enqueue_script( 'wwp_formrender', plugin_dir_url( __DIR__ ) . 'assets/js/formbuilder/form-render.min.js', array(), '1.0.0' );
			 
		}
		
		public function wwp_tax_display_admin_order_meta( $order ) {
			
			$registrations = get_option('wwp_wholesale_registration_options');
			if ( isset($registrations['tax_id_display']) && 'yes' == $registrations['tax_id_display'] ) {
				$wholesaler_tax_id = esc_html__('Wholesaler Tax ID ', 'woocommerce-wholesale-pricing');
				echo wp_kses('<p><strong> ' . $wholesaler_tax_id . ':</strong> <br/>' . get_user_meta( $order->get_user_id(), 'wwp_wholesaler_tax_id', true ) . '</p>', shapeSpace_allowed_html());
			}
		}
		
		public function wwp_registration_page_setting() {
			add_submenu_page('wwp_vendor', esc_html__('Registration Page', 'woocommerce-wholesale-pricing'), esc_html__('Registration Setting', 'woocommerce-wholesale-pricing'), 'manage_wholesale_registration_page', 'wwp-registration-setting', array($this,'wwp_wholesale_registration_page_callback'));
		}
		
		public function wwp_wholesale_registration_page_callback() {
			$registrations = get_option('wwp_wholesale_registration_options');
			if ( isset($_POST['save_wwp_registration_setting']) ) {
				if ( isset($_POST['wwp_wholesale_settings_nonce']) || wp_verify_nonce( wc_clean($_POST['wwp_wholesale_settings_nonce']), 'wwp_wholesale_settings_nonce') ) {
					$registrations = isset($_POST['registrations']) ? wc_clean($_POST['registrations']) : '';
					update_option('wwp_wholesale_registration_options', $registrations);
				}
			} 
			?><div id="screen_fix"></div>
			<div id="registration_form_settings">
			
			<nav class="wholesale-tab-link nav-tab-wrapper wp-clearfix">
				<a href="<?php echo esc_html_e(wholesale_tab_link('')); ?>" class="nav-tab <?php echo esc_html_e(wholesale_tab_active('')); ?>" data-tab="wholesale-general-settings">
					<?php esc_html_e('General Settings', 'woocommerce-wholesale-pricing'); ?>
				</a>
				<a href="<?php echo esc_html_e(wholesale_tab_link('default-fields')); ?>" class="nav-tab <?php echo esc_html_e(wholesale_tab_active('default-fields')); ?>" data-tab="wholesale-default-settings">
					<?php esc_html_e('Default Fields', 'woocommerce-wholesale-pricing'); ?>
				</a>
				<a href="<?php echo esc_html_e(wholesale_tab_link('extra-fields')); ?>" class="nav-tab <?php echo esc_html_e(wholesale_tab_active('extra-fields')); ?>" data-tab="wholesale-extra-settings">
					<?php esc_html_e('Extra Fields', 'woocommerce-wholesale-pricing'); ?>
				</a>
			</nav>
			
			<?php if (wholesale_load_form_builder()) { ?>
				<form action="" method="post">
					<?php wp_nonce_field('wwp_wholesale_settings_nonce', 'wwp_wholesale_settings_nonce'); ?>
					
					
					<table class="form-table" style="display: <?php echo esc_html_e(wholesale_content_tab_active('')); ?>">
						<tbody>
							<tr scope="row">
								<th><h4><label for=""><?php esc_html_e('Enable Billing Address form Default Fields', 'woocommerce-wholesale-pricing'); ?></label></h4></th>
								<td>
									<p>
										<label for="custommer_billing_address" class="switch">
										<?php
											$checked = '';
										if ( !isset($registrations) || empty($registrations) ) {
											$checked = 'checked';
										} else if ( isset( $registrations['custommer_billing_address'] ) && 'yes' == $registrations['custommer_billing_address'] ) {
											$checked = 'checked';
										} else {
											$checked = '';
										} 
										?>
											<input id="custommer_billing_address" type="checkbox"  value="yes" name="registrations[custommer_billing_address]" <?php echo esc_html($checked); ?> >
											<span class="slider round"></span>
										</label>
										<span data-tip="Enabling this option will allow default WooCommerce billing address field to appear on the front-end form." class="data-tip-top"><span class="woocommerce-help-tip"></span></span>
									</p>
								</td>
							</tr>
						</tbody>
					</table>
					<table class="form-table"  style="display: <?php echo esc_html_e(wholesale_content_tab_active('')); ?>">
						<tbody>
							<tr scope="row">
								<th><h4><label><?php esc_html_e('Enable Shipping Address form Default Fields', 'woocommerce-wholesale-pricing'); ?></label></h4></th>
								<td>
									<p>
										<?php
										$checked = '';
										if ( !isset($registrations) || empty($registrations) ) {
											$checked = 'checked';
										} elseif ( isset( $registrations['custommer_shipping_address'] ) && 'yes' == $registrations['custommer_shipping_address'] ) {
											$checked = 'checked';
										} else {
											$checked = '';
										} 
										?>
										<label for="custommer_shipping_address" class="switch">
											<input id="custommer_shipping_address" type="checkbox" value="yes" name="registrations[custommer_shipping_address]" <?php echo esc_html($checked); ?>>
											<span class="slider round"></span>
										</label>
										<span data-tip="Enabling this option will allow default WooCommerce shipping address field to appear on the front-end form." class="data-tip-top"><span class="woocommerce-help-tip"></span></span>
									</p>
								</td>
							</tr>
						</tbody>
					</table>

					<table class="form-table"  style="display: <?php echo esc_html_e(wholesale_content_tab_active('')); ?>">
						<tbody>
							<tr scope="row">
								<th><h4><label><?php esc_html_e('Display Extra Fields on Registration', 'woocommerce-wholesale-pricing'); ?></label></h4></th>
								<td>
									<p>
										<?php
										$checked = '';
										if ( !isset($registrations) || empty($registrations) ) {
											$checked = 'checked';
										} elseif ( isset($registrations['display_fields_registration']) && 'yes' == $registrations['display_fields_registration'] ) {
											$checked = 'checked';
										} else {
											$checked = '';
										} 
										?>
										<label for="display_fields_registration" class="switch">
											<input id="display_fields_registration" type="checkbox" value="yes" name="registrations[display_fields_registration]" <?php echo esc_html($checked); ?>>
											<span class="slider round"></span>
										</label>
										<span data-tip="Enable this option to allow the dynamic form builder to display extra fields on the registration page." class="data-tip-top"><span class="woocommerce-help-tip"></span></span>
									</p>
								</td>
							</tr>
						</tbody>
					</table>
					
					<table class="form-table"  style="display: <?php echo esc_html_e(wholesale_content_tab_active('')); ?>">
						<tbody>
							<tr scope="row">
								<th><h4><label><?php esc_html_e('Display Extra Fields on  My account', 'woocommerce-wholesale-pricing'); ?></label></h4></th>
								<td>
									<p>
										<?php
										$checked = '';
										if ( !isset($registrations) || empty($registrations) ) {
											$checked = 'checked';
										} elseif ( isset($registrations['display_fields_myaccount']) && 'yes' == $registrations['display_fields_myaccount'] ) {
											$checked = 'checked';
										} else {
											$checked = '';
										} 
										?>
										<label for="display_fields_myaccount" class="switch">
											<input id="display_fields_myaccount" type="checkbox" value="yes" name="registrations[display_fields_myaccount]" <?php echo esc_html($checked); ?>>
											<span class="slider round"></span>
										</label>
										<span data-tip="Enable this option to allow the dynamic form builder to display extra fields on the my account page." class="data-tip-top"><span class="woocommerce-help-tip"></span></span>
									</p>
								</td>
							</tr>
						</tbody>
					</table>
					 
					<table class="form-table"  style="display: <?php echo esc_html_e(wholesale_content_tab_active('')); ?>">
						<tbody>
							<tr scope="row">
								<th><h4><label><?php esc_html_e('Display Extra Fields on Checkout', 'woocommerce-wholesale-pricing'); ?></label></h4></th>
								<td>
									<p>
										<?php
										$checked = '';
										if ( !isset($registrations) || empty($registrations) ) {
											$checked = 'checked';
										} elseif ( isset($registrations['display_fields_checkout']) && 'yes' == $registrations['display_fields_checkout'] ) {
											$checked = 'checked';
										} else {
											$checked = '';
										} 
										?>
										<label for="display_fields_checkout" class="switch">
											<input id="display_fields_checkout" type="checkbox" value="yes" name="registrations[display_fields_checkout]" <?php echo esc_html($checked); ?>>
											<span class="slider round"></span>
										</label>
										<span data-tip="Enable this option to allow the dynamic form builder to display extra fields on the checkout page." class="data-tip-top"><span class="woocommerce-help-tip"></span></span>
									</p>
								</td>
							</tr>
						</tbody>
					</table> 
					
					<div id="billing_address_fields" style="display:<?php echo esc_html_e(wholesale_content_tab_active('default-fields')); ?>">
					<h3><label for=""><?php esc_html_e('Billing Address form Fields', 'woocommerce-wholesale-pricing'); ?></label></h3>
						<table class="form-table">
							<tbody>
								<tr scope="row">
									<th><label for=""><?php esc_html_e('Billing First Name', 'woocommerce-wholesale-pricing'); ?></label></th>
									<td>
										<p>
											<label for="billing_first_name">
												<input type="checkbox" id="enable_billing_first_name" name="registrations[enable_billing_first_name]" value="yes" <?php echo ( isset($registrations['enable_billing_first_name']) && 'yes' == $registrations['enable_billing_first_name'] ) ? 'checked' : ''; ?>>
												<?php esc_html_e('Enable', 'woocommerce-wholesale-pricing'); ?>
												<input type="text" id="billing_first_name" placeholder="Custom label" name="registrations[billing_first_name]" value="<?php echo isset( $registrations['billing_first_name'] ) ? esc_attr($registrations['billing_first_name']) : ''; ?>" >
												<?php esc_html_e(' [ default label : "First Name" ]', 'woocommerce-wholesale-pricing'); ?>
											</label>
										</p>
									</td>
								</tr>
								<tr scope="row">
									<th><label for=""><?php esc_html_e('Billing Last Name', 'woocommerce-wholesale-pricing'); ?></label></th>
									<td>
										<p>
											<label for="billing_last_name">
												<input type="checkbox" id="enable_billing_last_name" name="registrations[enable_billing_last_name]" value="yes" <?php echo ( isset($registrations['enable_billing_last_name']) && 'yes' == $registrations['enable_billing_last_name'] ) ? 'checked' : ''; ?>>
												<?php esc_html_e('Enable', 'woocommerce-wholesale-pricing'); ?>
												<input type="text" id="billing_last_name" placeholder="Custom label" name="registrations[billing_last_name]" value="<?php echo isset($registrations['billing_last_name']) ? esc_attr($registrations['billing_last_name']) : ''; ?>">
												<?php esc_html_e(' [ default label : "Last Name" ] ', 'woocommerce-wholesale-pricing'); ?>
											</label>
										</p>
									</td>
								</tr>
								<tr scope="row" >
									<th><label for=""><?php esc_html_e('Company', 'woocommerce-wholesale-pricing'); ?></label></th>
									<td>
										<p>
											<label for="billing_company">
												<input type="checkbox" id="enable_billing_company" name="registrations[enable_billing_company]" value="yes" <?php echo ( isset($registrations['enable_billing_company']) && 'yes' == $registrations['enable_billing_company'] ) ? 'checked' : ''; ?>>
												<?php esc_html_e('Enable', 'woocommerce-wholesale-pricing'); ?>
												<input type="text" id="billing_company" placeholder="Custom label" name="registrations[billing_company]" value="<?php echo isset( $registrations['billing_company'] ) ? esc_attr($registrations['billing_company']) : ''; ?>" >
												<?php esc_html_e(' [ default label : "Company" ] ', 'woocommerce-wholesale-pricing'); ?>
											</label>
										</p>
									</td>
								</tr>
								<tr scope="row" >
									<th><label for=""><?php esc_html_e('Address line 1 ', 'woocommerce-wholesale-pricing'); ?></label></th>
									<td>
										<p>
											<label for="billing_address_1">
												<input type="checkbox" id="enable_billing_address_1" name="registrations[enable_billing_address_1]" value="yes"  <?php echo ( isset($registrations['enable_billing_address_1']) && 'yes' == $registrations['enable_billing_address_1'] ) ? 'checked' : ''; ?>>
												<?php esc_html_e('Enable', 'woocommerce-wholesale-pricing'); ?>
												<input type="text" id="billing_address_1" placeholder="Custom label" name="registrations[billing_address_1]" value="<?php echo isset($registrations['billing_address_1']) ? esc_attr($registrations['billing_address_1']) : ''; ?>">
												<?php esc_html_e(' [ default label : "Address line 1" ] ', 'woocommerce-wholesale-pricing'); ?>
											</label>
										</p>
									</td>
								</tr>
								<tr scope="row" >
									<th><label for=""><?php esc_html_e('Address line 2 ', 'woocommerce-wholesale-pricing'); ?></label></th>
									<td>
										<p>
											<label for="billing_address_2">
												<input type="checkbox" id="enable_billing_address_2" name="registrations[enable_billing_address_2]" value="yes" <?php echo ( isset($registrations['enable_billing_address_2']) && 'yes' == $registrations['enable_billing_address_2'] ) ? 'checked' : ''; ?>>
												<?php esc_html_e('Enable', 'woocommerce-wholesale-pricing'); ?>
												<input type="text" id="billing_address_2" placeholder="Custom label" name="registrations[billing_address_2]" value="<?php echo isset($registrations['billing_address_2']) ? esc_attr($registrations['billing_address_2']) : ''; ?>" >
												<?php esc_html_e('  [ default label : "Address line 2" ]', 'woocommerce-wholesale-pricing'); ?>
											</label>
										</p>
									</td>
								</tr>
								<tr scope="row" >
									<th><label for=""><?php esc_html_e('City ', 'woocommerce-wholesale-pricing'); ?></label></th>
									<td>
										<p>
											<label for="billing_city">
												<input type="checkbox" id="enable_billing_city" name="registrations[enable_billing_city]" value="yes" <?php echo ( isset($registrations['enable_billing_city']) && 'yes' == $registrations['enable_billing_city'] ) ? 'checked' : ''; ?>>
												<?php esc_html_e('Enable', 'woocommerce-wholesale-pricing'); ?>
												<input type="text" id="billing_city" placeholder="Custom label" name="registrations[billing_city]" value="<?php echo isset($registrations['billing_city']) ? esc_attr($registrations['billing_city']) : ''; ?>"  >
												<?php esc_html_e(' [ default label : "City" ] ', 'woocommerce-wholesale-pricing'); ?>
											</label>
										</p>
									</td>
								</tr>
								<tr scope="row" >
									<th><label for=""><?php esc_html_e('Postcode / ZIP ', 'woocommerce-wholesale-pricing'); ?></label></th>
									<td>
										<p>
											<label for="billing_post_code">
												<input type="checkbox" id="enable_billing_post_code" name="registrations[enable_billing_post_code]" value="yes" <?php echo ( isset($registrations['enable_billing_post_code']) && 'yes' == $registrations['enable_billing_post_code'] ) ? 'checked' : ''; ?>>
												<?php esc_html_e('Enable', 'woocommerce-wholesale-pricing'); ?>
												<input type="text" id="billing_post_code"  placeholder="Custom label" name="registrations[billing_post_code]" value="<?php echo isset($registrations['billing_post_code']) ? esc_attr($registrations['billing_post_code']) : ''; ?>"  >
												<?php esc_html_e(' [ default label : "Postcode / ZIP" ]', 'woocommerce-wholesale-pricing'); ?>
											</label>
										</p>
									</td>
								</tr>
								<tr scope="row" >
									<th><label for=""><?php esc_html_e('Countries ', 'woocommerce-wholesale-pricing'); ?></label></th>
									<td>
										<p>
											<label for="billing_countries">
												<input type="checkbox" id="enable_billing_country" name="registrations[enable_billing_country]" value="yes" <?php echo ( isset($registrations['enable_billing_country']) && 'yes' == $registrations['enable_billing_country'] ) ? 'checked' : ''; ?>>
												<?php esc_html_e('Enable', 'woocommerce-wholesale-pricing'); ?>
												<input type="text" id="billing_countries" placeholder="Custom label" name="registrations[billing_countries]" value="<?php echo isset($registrations['billing_countries']) ? esc_attr($registrations['billing_countries']) : ''; ?>" >
												<?php esc_html_e(' [ default label : "Countries" ] ', 'woocommerce-wholesale-pricing'); ?>
											</label>
										</p>
									</td>
								</tr>
								<tr scope="row" >
									<th><label for=""><?php esc_html_e('States ', 'woocommerce-wholesale-pricing'); ?></label></th>
									<td>
										<p>
											<label for="billing_countries">
												<input type="checkbox" id="enable_billing_state" name="registrations[enable_billing_state]" value="yes" <?php echo ( isset($registrations['enable_billing_state']) && 'yes' == $registrations['enable_billing_state'] ) ? 'checked' : ''; ?>>
												<?php esc_html_e('Enable', 'woocommerce-wholesale-pricing'); ?>
												<input type="text" id="billing_state"  placeholder="Custom label" name="registrations[billing_state]" value="<?php echo isset($registrations['billing_state']) ? esc_attr($registrations['billing_state']) : ''; ?>" >
												<?php esc_html_e(' [ default label : "States" ]', 'woocommerce-wholesale-pricing'); ?>
											</label>
										</p>
									</td>
								</tr>
								<tr scope="row" >
									<th><label><?php esc_html_e('Phone ', 'woocommerce-wholesale-pricing'); ?></label></th>
									<td>
										<p>
											<label for="billing_phone">
												<input type="checkbox" id="enable_billing_phone" name="registrations[enable_billing_phone]" value="yes" <?php echo ( isset($registrations['enable_billing_phone']) && 'yes' == $registrations['enable_billing_phone'] ) ? 'checked' : ''; ?>>
												<?php esc_html_e('Enable', 'woocommerce-wholesale-pricing'); ?>
												<input type="text" id="billing_phone" placeholder="Custom label" name="registrations[billing_phone]" value="<?php echo isset($registrations['billing_phone']) ? esc_attr($registrations['billing_phone']) : ''; ?>">
												<?php esc_html_e(' [ default label : "Phone" ] ', 'woocommerce-wholesale-pricing'); ?>
											</label>
										</p>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
					
					<div id="shipping_address_fields" style="display:<?php echo esc_html_e(wholesale_content_tab_active('default-fields')); ?>;">
					<h3><label><?php esc_html_e('Shipping Address form Fields', 'woocommerce-wholesale-pricing'); ?></label></h3>
						<table class="form-table">
							<tbody>
								<tr scope="row">
									<th><label for=""><?php esc_html_e('Shipping First Name', 'woocommerce-wholesale-pricing'); ?></label></th>
									<td>
										<p>
											<label for="shipping_first_name">
												<input type="checkbox" id="enable_shipping_first_name" name="registrations[enable_shipping_first_name]" value="yes" <?php echo ( isset($registrations['enable_shipping_first_name']) && 'yes' == $registrations['enable_shipping_first_name'] ) ? 'checked' : ''; ?>>
												<?php esc_html_e('Enable', 'woocommerce-wholesale-pricing'); ?>
												<input type="text" id="shipping_first_name" placeholder="Custom label" name="registrations[shipping_first_name]" value="<?php echo isset($registrations['shipping_first_name']) ? esc_attr($registrations['shipping_first_name']) : ''; ?>">
												<?php esc_html_e('[ default label : "First Name" ]  ', 'woocommerce-wholesale-pricing'); ?>

											</label>
										</p>
									</td>
								</tr>
								<tr scope="row">
									<th><label for=""><?php esc_html_e('Shipping Last Name', 'woocommerce-wholesale-pricing'); ?></label></th>
									<td>
										<p>
											<label for="shipping_last_name">
												<input type="checkbox" id="enable_shipping_last_name" name="registrations[enable_shipping_last_name]" value="yes" <?php echo ( isset($registrations['enable_shipping_last_name']) && 'yes' == $registrations['enable_shipping_last_name'] ) ? 'checked' : ''; ?>>
												<?php esc_html_e('Enable', 'woocommerce-wholesale-pricing'); ?>
												<input type="text" id="shipping_last_name" placeholder="Custom label" name="registrations[shipping_last_name]" value="<?php echo isset($registrations['shipping_last_name']) ? esc_attr($registrations['shipping_last_name']) : ''; ?>">
												<?php esc_html_e(' [ default label : "Last Name" ] ', 'woocommerce-wholesale-pricing'); ?>
											</label>
										</p>
									</td>
								</tr>
								<tr scope="row" >
									<th><label for=""><?php esc_html_e('Company', 'woocommerce-wholesale-pricing'); ?></label></th>
									<td>
										<p>
											<label for="billing_company">
												<input type="checkbox" id="enable_shipping_company" name="registrations[enable_shipping_company]" value="yes" <?php echo ( isset($registrations['enable_shipping_company']) && 'yes' == $registrations['enable_shipping_company'] ) ? 'checked' : ''; ?>>
												<?php esc_html_e('Enable', 'woocommerce-wholesale-pricing'); ?>
												<input type="text" id="shipping_company" placeholder="Custom label" name="registrations[shipping_company]" value="<?php echo isset($registrations['shipping_company']) ? esc_attr($registrations['shipping_company']) : ''; ?>">
												<?php esc_html_e(' [ default label : "Company" ] ', 'woocommerce-wholesale-pricing'); ?>

											</label>
										</p>
									</td>
								</tr>
								<tr scope="row" >
									<th><label for=""><?php esc_html_e('Address line 1 ', 'woocommerce-wholesale-pricing'); ?></label></th>
									<td>
										<p>
											<label for="shipping_address_1">
												<input type="checkbox" id="enable_shipping_address_1" name="registrations[enable_shipping_address_1]" value="yes" value="yes" <?php echo ( isset($registrations['enable_shipping_address_1']) && 'yes' == $registrations['enable_shipping_address_1'] ) ? 'checked' : ''; ?>>
												<?php esc_html_e('Enable', 'woocommerce-wholesale-pricing'); ?>
												<input type="text" id="shipping_address_1" placeholder="Custom label" name="registrations[shipping_address_1]" value="<?php echo isset($registrations['shipping_address_1']) ? esc_attr($registrations['shipping_address_1']) : ''; ?>">
												<?php esc_html_e('[ default label : "Address line 1" ]  ', 'woocommerce-wholesale-pricing'); ?>

											</label>
										</p>
									</td>
								</tr>
								<tr scope="row" >
									<th><label for=""><?php esc_html_e('Address line 2 ', 'woocommerce-wholesale-pricing'); ?></label></th>
									<td>
										<p>
											<label for="shipping_address_2">
												<input type="checkbox" id="enable_shipping_address_2" name="registrations[enable_shipping_address_2]" value="yes" <?php echo ( isset($registrations['enable_shipping_address_2']) && 'yes' == $registrations['enable_shipping_address_2'] ) ? 'checked' : ''; ?>>
												<?php esc_html_e('Enable', 'woocommerce-wholesale-pricing'); ?>
												<input type="text" id="shipping_address_2" placeholder="Custom label" name="registrations[shipping_address_2]" value="<?php echo isset($registrations['shipping_address_2']) ? esc_attr($registrations['shipping_address_2']) : ''; ?>">
												<?php esc_html_e(' [ default label : "Address line 2" ]', 'woocommerce-wholesale-pricing'); ?>
											</label>
										</p>
									</td>
								</tr>
								<tr scope="row" >
									<th><label for=""><?php esc_html_e('City ', 'woocommerce-wholesale-pricing'); ?></label></th>
									<td>
										<p>
											<label for="shipping_city">
												<input type="checkbox" id="enable_shipping_city" name="registrations[enable_shipping_city]" value="yes" <?php echo ( isset($registrations['enable_shipping_city']) && 'yes' == $registrations['enable_shipping_city'] ) ? 'checked' : ''; ?>>
												<?php esc_html_e('Enable', 'woocommerce-wholesale-pricing'); ?>
												<input type="text" id="shipping_city" placeholder="Custom label" name="registrations[shipping_city]" value="<?php echo isset($registrations['shipping_city']) ? esc_attr($registrations['shipping_city']) : ''; ?>">
												<?php esc_html_e(' [ default label : "City" ]', 'woocommerce-wholesale-pricing'); ?>
											</label>
										</p>
									</td>
								</tr>
								<tr scope="row" >
									<th><label for=""><?php esc_html_e('Postcode / ZIP ', 'woocommerce-wholesale-pricing'); ?></label></th>
									<td>
										<p>
											<label for="shipping_post_code">
												<input type="checkbox" id="enable_shipping_post_code" name="registrations[enable_shipping_post_code]" value="yes" <?php echo ( isset($registrations['enable_shipping_post_code']) && 'yes' == $registrations['enable_shipping_post_code'] ) ? 'checked' : ''; ?>>
												<?php esc_html_e('Enable', 'woocommerce-wholesale-pricing'); ?>
												<input type="text" id="shipping_post_code" placeholder="Custom label" name="registrations[shipping_post_code]" value="<?php echo isset($registrations['shipping_post_code']) ? esc_attr($registrations['shipping_post_code']) : ''; ?>">
												<?php esc_html_e(' [ default label : "Postcode / ZIP" ]', 'woocommerce-wholesale-pricing'); ?>
											</label>
										</p>
									</td>
								</tr>
								<tr scope="row" >
									<th><label for=""><?php esc_html_e('Countries ', 'woocommerce-wholesale-pricing'); ?></label></th>
									<td>
										<p>
											<label for="shipping_countries">
												<input type="checkbox" id="enable_shipping_country" name="registrations[enable_shipping_country]" value="yes" <?php echo ( isset($registrations['enable_shipping_country']) && 'yes' == $registrations['enable_shipping_country'] ) ? 'checked' : ''; ?>>
												<?php esc_html_e('Enable', 'woocommerce-wholesale-pricing'); ?>
												<input type="text" id="shipping_countries" placeholder="Custom label" name="registrations[shipping_countries]" value="<?php echo isset($registrations['shipping_countries']) ? esc_attr($registrations['shipping_countries']) : ''; ?>">
												<?php esc_html_e('[ default label : "Countries" ] ', 'woocommerce-wholesale-pricing'); ?>
											</label>
										</p>
									</td>
								</tr>
								<tr scope="row">
									<th><label for=""><?php esc_html_e('States ', 'woocommerce-wholesale-pricing'); ?></label></th>
									<td>
										<p>
											<label for="billing_countries">
												<input type="checkbox" id="enable_shipping_state" name="registrations[enable_shipping_state]" value="yes" <?php echo ( isset($registrations['enable_shipping_state']) && 'yes' == $registrations['enable_shipping_state'] ) ? 'checked' : ''; ?>>
												<?php esc_html_e('Enable', 'woocommerce-wholesale-pricing'); ?>
												<input type="text" id="shipping_state" placeholder="Custom label" name="registrations[shipping_state]" value="<?php echo isset($registrations['shipping_state']) ? esc_attr($registrations['shipping_state']) : ''; ?>">
												<?php esc_html_e(' [ default label : "States" ] ', 'woocommerce-wholesale-pricing'); ?>
											</label>
										</p>
									</td>
								</tr>
							</tbody>
						</table>
					</div>

					<div id="custom_other_fields" style="display:<?php echo esc_html_e(wholesale_content_tab_active('default-fields')); ?>;">
						<table class="form-table">
							<tbody>
								<tr scope="row">
									<th><label for=""><?php esc_html_e('Tax ID ', 'woocommerce-wholesale-pricing'); ?></label>
									<span data-tip="Enable this option to create the 'Tax ID' label text." class="data-tip-right"><span class="woocommerce-help-tip"></span></span>
									</th>
									<td>
										<p>
											<label for="woo_tax_id">
												<input type="checkbox" id="enable_tex_id" name="registrations[enable_tex_id]" value="yes" <?php echo ( isset($registrations['enable_tex_id']) && 'yes' == $registrations['enable_tex_id'] ) ? 'checked' : ''; ?>>
												<?php esc_html_e('Enable', 'woocommerce-wholesale-pricing'); ?>
												<input type="text" id="woo_tax_id"  placeholder="Custom label" name="registrations[woo_tax_id]" value="<?php echo isset($registrations['woo_tax_id']) ? esc_attr($registrations['woo_tax_id']) : ''; ?>">
												<?php esc_html_e('[ default label : "Tax ID" ]', 'woocommerce-wholesale-pricing'); ?>
												<input type="checkbox" id="required_tex_id" name="registrations[required_tex_id]" value="yes" <?php echo ( isset($registrations['required_tex_id']) && 'yes' == $registrations['required_tex_id'] ) ? 'checked' : ''; ?>>
												<?php esc_html_e('Required', 'woocommerce-wholesale-pricing'); ?>
											</label>
										</p>
									</td>
								</tr>
								<tr scope="row">
									<th><label for=""><?php esc_html_e('File Upload ', 'woocommerce-wholesale-pricing'); ?></label>
									<span data-tip="Enable this option to create the 'File Upload' label text." class="data-tip-right"><span class="woocommerce-help-tip"></span></span>
									</th>
									<td>
										<p>
											<label for="woo_file_upload">
												<input type="checkbox" id="enable_file_upload" name="registrations[enable_file_upload]" value="yes" <?php echo ( isset($registrations['enable_file_upload']) && 'yes' == $registrations['enable_file_upload'] ) ? 'checked' : ''; ?>>
												<?php esc_html_e('Enable', 'woocommerce-wholesale-pricing'); ?>
												<input type="text" id="woo_file_upload"  placeholder="Custom label" name="registrations[woo_file_upload]" value="<?php echo isset($registrations['woo_file_upload']) ? esc_attr($registrations['woo_file_upload']) : ''; ?>">
												<?php esc_html_e('[ default label : "File Upload" ]', 'woocommerce-wholesale-pricing'); ?>
												<input type="checkbox" id="required_file_upload" name="registrations[required_file_upload]" value="yes" <?php echo ( isset($registrations['required_file_upload']) && 'yes' == $registrations['required_file_upload'] ) ? 'checked' : ''; ?>>
												<?php esc_html_e('Required', 'woocommerce-wholesale-pricing'); ?>
											</label>
										</p>
									</td>
								</tr>
								<tr scope="row">
									<th><label for=""><?php esc_html_e('Tax ID Display', 'woocommerce-wholesale-pricing'); ?></label>
									<span data-tip="Enable this option to display the Tax ID in the billing address." class="data-tip-right"><span class="woocommerce-help-tip"></span></span>
									</th>
									<td>
										<p>
											<label for="tax_id_display">
												<input type="checkbox" id="tax_id_display" name="registrations[tax_id_display]" value="yes" <?php echo ( isset($registrations['tax_id_display']) && 'yes' == $registrations['tax_id_display'] ) ? 'checked' : ''; ?>>
												<?php esc_html_e('Enable to display tax id in billing address', 'woocommerce-wholesale-pricing'); ?>
											</label>
										</p>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
					
					<p><button name="save_wwp_registration_setting" class="button-primary" type="submit" value="Save changes"><?php esc_html_e('Save changes', 'woocommerce-wholesale-pricing'); ?></button></p>
				</form>
				<?php 
			} else {
				include_once WWP_PLUGIN_PATH . 'inc/class-wwp-wholesale-form-builder.php'; 
			}
			?>
			
			
			</div>
			<div class="map_shortcode_callback">
				<p> <?php esc_html_e('Copy following shortcode, and paste in page where you would like to display wholesaler registration form.', 'woocommerce-wholesale-pricing'); ?></p>
				<div class="map_shortcode_copy"  onclick="copytoclipboard()"><span class="dashicons dashicons-admin-page"></span><label><?php esc_html_e('Copy', 'woocommerce-wholesale-pricing'); ?></label></div>
				<p> <input type="text" onfocus="this.select();" value="[wwp_registration_form]" readonly="readonly" name="shortcode" class="large-text code"> </p>
			</div>
			
			<?php
		}
	}
	new Wwp_Wholesale_Registration_Page_Setting();
}
