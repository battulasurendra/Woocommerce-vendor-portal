<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}
/**
 * Class To handle User Meta Fields
 */
if (!class_exists('Wwp_Wholesale_User_Fields')) {

	class Wwp_Wholesale_User_Fields {

		public function __construct () {
			add_action('show_user_profile', array($this, 'wwp_user_other_profile_fields' ));
			add_action('edit_user_profile', array($this, 'wwp_user_other_profile_fields'));
			add_action('personal_options_update', array($this, 'wwp_user_other_save_profile_fields'));
			add_action('edit_user_profile_update', array($this, 'wwp_user_other_save_profile_fields'));
		}
		public function wwp_user_other_profile_fields ( $user ) {
			$woo_tax_id =  get_the_author_meta('wwp_wholesaler_tax_id', $user->ID);
			$file_id =  get_the_author_meta('wwp_wholesaler_file_upload', $user->ID);
			$file = wp_get_attachment_url($file_id);
			$registrations = get_option('wwp_wholesale_registration_options');
			wp_nonce_field('wwp_wholesaler_nonce', 'wwp_wholesaler_nonce'); ?>
			<h3><?php esc_html_e('Others Fields', 'woocommerce-wholesale-pricing'); ?></h3>
			<table class="form-table">
				<tr>
					<th>
						<label for="birth-date-day"><?php esc_html_e('Wholesaler Tax ID', 'woocommerce-wholesale-pricing'); ?></label>
					</th>
					<td>
						<input type="text" name="wwp_wholesaler_tax_id" id="wwp_wholesaler_tax_id" value="<?php esc_attr_e($woo_tax_id); ?>">
					</td>
				</tr>
				<?php if ( !empty($file) ) { ?>
				<tr>
					<th>
						<label for="wwp_wholesaler_file_upload"><?php esc_html_e('Wholesaler File Upload', 'woocommerce-wholesale-pricing'); ?></label>
					</th>
					<td>
						<p><a href="<?php echo esc_url( admin_url( 'upload.php?item=' . $file_id ) ); ?>"><img src="<?php echo esc_url($file); ?>" width="100" height="100" class="wwp_wholesaler_file_upload"></a></p>
					</td>
				</tr>
					<?php 
				} 
				for ( $i=1; $i < 6; $i++ ) {
					if ( isset($registrations['custom_field_' . $i]) && !empty(get_the_author_meta('wwp_custom_field_' . $i, $user->ID)) ) { 
						$value = get_the_author_meta('wwp_custom_field_' . $i, $user->ID);
						?>
						<tr>
							<th>
								<label for="wwp_custom_field_<?php esc_attr_e($i); ?>"><?php echo !empty($registrations['woo_custom_field_' . $i]) ? esc_html($registrations['woo_custom_field_' . $i]) : esc_html__('Custom Field', 'woocommerce-wholesale-pricing'); ?></label>
							</th>
							<td>
							<?php if ('5' == $i ) { ?>
								<textarea rows="4" cols="120" name="wwp_custom_field_<?php esc_attr_e($i); ?>" id="wwp_custom_field_<?php esc_attr_e($i); ?>" ><?php esc_attr_e($value); ?></textarea>
							<?php } else { ?>
								<input type="text" name="wwp_custom_field_<?php esc_attr_e($i); ?>" id="wwp_custom_field_<?php esc_attr_e($i); ?>" value="<?php esc_attr_e($value); ?>">
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
						<label for="render_form_builder"><?php esc_html_e('Wholesaler Extra Fields Data', 'woocommerce-wholesale-pricing'); ?></label>
					</th>
					<td>
					<?php echo wp_kses_post( render_form_builder( 'get_user_meta', $user->ID ) ) ; ?>
					</td>
				</tr>
				<?php } ?>
			</table>
			<?php
		}
		public function wwp_user_other_save_profile_fields( $user_id ) {
			if ( !current_user_can( 'edit_user', $user_id ) ) {
				return false;
			}
			if ( !isset($_POST['wwp_wholesaler_nonce']) || !wp_verify_nonce( wc_clean($_POST['wwp_wholesaler_nonce']), 'wwp_wholesaler_nonce') ) {
				return;
			}
			if ( isset($_POST['wwp_wholesaler_tax_id']) ) {
				update_user_meta($user_id, 'wwp_wholesaler_tax_id', wc_clean($_POST['wwp_wholesaler_tax_id']));
			}
			
			if ( isset($_POST['wwp_form_data_json']) ) {
				// Form builder fields udate in user meta
				form_builder_update_user_meta( $user_id );
				$wwp_form_data_json = wwp_get_post_data( 'wwp_form_data_json' );
				update_user_meta($user_id, 'wwp_form_data_json', $wwp_form_data_json);
			}
			
			for ( $i=1; $i < 6; $i++ ) {
				if ( isset($_POST['wwp_custom_field_' . $i]) ) { 
					update_user_meta($user_id, 'wwp_custom_field_' . $i, wc_clean($_POST['wwp_custom_field_' . $i]));
				}
			}
		}
	}
	new Wwp_Wholesale_User_Fields();
}
