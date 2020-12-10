<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}
/**
 * Class To handle User Meta Fields
 */
if (!class_exists('Wvp_Vendor_User_Fields')) {

	class Wvp_Vendor_User_Fields {

		public function __construct () {
			add_action('show_user_profile', array($this, 'wvp_user_other_profile_fields' ));
			add_action('edit_user_profile', array($this, 'wvp_user_other_profile_fields'));
			add_action('personal_options_update', array($this, 'wvp_user_other_save_profile_fields'));
			add_action('edit_user_profile_update', array($this, 'wvp_user_other_save_profile_fields'));
		}
		public function wvp_user_other_profile_fields ( $user ) {
			$woo_tax_id =  get_the_author_meta('wvp_contractor_tax_id', $user->ID);
			$file_id =  get_the_author_meta('wvp_contractor_file_upload', $user->ID);
			$file = wp_get_attachment_url($file_id);
			$registrations = get_option('wvp_vendor_registration_options');
			wp_nonce_field('wvp_contractor_nonce', 'wvp_contractor_nonce'); ?>
			<h3><?php esc_html_e('Others Fields', 'woocommerce-vendor-portal'); ?></h3>
			<table class="form-table">
				<tr>
					<th>
						<label for="birth-date-day"><?php esc_html_e('Contractor Tax ID', 'woocommerce-vendor-portal'); ?></label>
					</th>
					<td>
						<input type="text" name="wvp_contractor_tax_id" id="wvp_contractor_tax_id" value="<?php esc_attr_e($woo_tax_id); ?>">
					</td>
				</tr>
				<?php if ( !empty($file) ) { ?>
				<tr>
					<th>
						<label for="wvp_contractor_file_upload"><?php esc_html_e('Contractor File Upload', 'woocommerce-vendor-portal'); ?></label>
					</th>
					<td>
						<p><a href="<?php echo esc_url( admin_url( 'upload.php?item=' . $file_id ) ); ?>"><img src="<?php echo esc_url($file); ?>" width="100" height="100" class="wvp_contractor_file_upload"></a></p>
					</td>
				</tr>
					<?php 
				} 
				for ( $i=1; $i < 6; $i++ ) {
					if ( isset($registrations['custom_field_' . $i]) && !empty(get_the_author_meta('wvp_custom_field_' . $i, $user->ID)) ) { 
						$value = get_the_author_meta('wvp_custom_field_' . $i, $user->ID);
						?>
						<tr>
							<th>
								<label for="wvp_custom_field_<?php esc_attr_e($i); ?>"><?php echo !empty($registrations['woo_custom_field_' . $i]) ? esc_html($registrations['woo_custom_field_' . $i]) : esc_html__('Custom Field', 'woocommerce-vendor-portal'); ?></label>
							</th>
							<td>
							<?php if ('5' == $i ) { ?>
								<textarea rows="4" cols="120" name="wvp_custom_field_<?php esc_attr_e($i); ?>" id="wvp_custom_field_<?php esc_attr_e($i); ?>" ><?php esc_attr_e($value); ?></textarea>
							<?php } else { ?>
								<input type="text" name="wvp_custom_field_<?php esc_attr_e($i); ?>" id="wvp_custom_field_<?php esc_attr_e($i); ?>" value="<?php esc_attr_e($value); ?>">
							<?php } ?>
							</td>
						</tr>
						<?php
					}
				} 
				?>
				<?php if ( ( isset( $registrations['display_fields_registration'] ) && 'yes' == $registrations['display_fields_registration'] ) || ( isset( $registrations['display_fields_myaccount'] ) && 'yes' == $registrations['display_fields_myaccount'] ) ) { ?>
				<tr>
					<th>
						<label for="render_form_builder"><?php esc_html_e('Contractor Extra Fields Data', 'woocommerce-vendor-portal'); ?></label>
					</th>
					<td>
					<?php echo wp_kses_post( render_form_builder( 'get_user_meta', $user->ID ) ) ; ?>
					</td>
				</tr>
				<?php } ?>
			</table>
			<?php
		}
		public function wvp_user_other_save_profile_fields( $user_id ) {
			if ( !current_user_can( 'edit_user', $user_id ) ) {
				return false;
			}
			if ( !isset($_POST['wvp_contractor_nonce']) || !wp_verify_nonce( wc_clean($_POST['wvp_contractor_nonce']), 'wvp_contractor_nonce') ) {
				return;
			}
			if ( isset($_POST['wvp_contractor_tax_id']) ) {
				update_user_meta($user_id, 'wvp_contractor_tax_id', wc_clean($_POST['wvp_contractor_tax_id']));
			}
			
			if ( isset($_POST['wvp_form_data_json']) ) {
				// Form builder fields udate in user meta
				form_builder_update_user_meta( $user_id );
				$wvp_form_data_json = wvp_get_post_data( 'wvp_form_data_json' );
				update_user_meta($user_id, 'wvp_form_data_json', $wvp_form_data_json);
			}
			
			for ( $i=1; $i < 6; $i++ ) {
				if ( isset($_POST['wvp_custom_field_' . $i]) ) { 
					update_user_meta($user_id, 'wvp_custom_field_' . $i, wc_clean($_POST['wvp_custom_field_' . $i]));
				}
			}
		}
	}
	new Wvp_Vendor_User_Fields();
}
