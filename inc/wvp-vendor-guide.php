<?php
if (! defined('ABSPATH') ) {
	exit; // Exit if accessed directly
}
/**
 * Guide Pointer 
 * 
 * @since   1.0
 * @version 1.0
 */
function wvp_get_content_in_wp_pointer() {
	if ( isset($_GET['wvp_skip']) ) {
		update_option('wvp_guide_skip', 1);
	}
	$user_role = '<h3>' . esc_html__('Select Pricing Mode', 'woocommerce-vendor-portal') . '</h3>';
	$user_role .= '<p>' . esc_html__('System will work with Single Contractor Mode or Multi Contractor Mode.', 'woocommerce-vendor-portal') . '</p>';
	$setting = '<h3>' . esc_html__('Make Your Settings.', 'woocommerce-vendor-portal') . '</h3>';
	$setting .= '<p>' . esc_html__('Before using the plugin you must save your settings.', 'woocommerce-vendor-portal') . '</p>';
	$select_mode  = '<h3>' . esc_html__('Select Mode.', 'woocommerce-vendor-portal') . '</h3>';
	$select_mode .= '<p>' . esc_html__('Before using the plugin you must save your settings.', 'woocommerce-vendor-portal') . '</p>';
	$pricing_labels  = esc_html__('Pricing Labels', 'woocommerce-vendor-portal');
	$pricing_labels .= '<p>' . esc_html__('Set labels for Retail, Vendor and Save Price.', 'woocommerce-vendor-portal') . '</p>';
	$registration_page  = '<h3>' . esc_html__('Select Registration Page', 'woocommerce-vendor-portal') . '</h3>';
	$registration_page .= '<p>' . esc_html__('Select the page where you would like to place the shortcode to display registration form.', 'woocommerce-vendor-portal') . '</p>';
	$add_user_role  = '<h3>' . esc_html__('Add Contractor Role', 'woocommerce-vendor-portal') . '</h3>';
	$add_user_role .= '<p>' . esc_html__('You can rename the default user role or add another role', 'woocommerce-vendor-portal') . '</p>';
	?>
	<style>
		.wp-pointer-buttons span {
			color: gray;
			float: right;
			padding: 5px 15px;
		}
		.wp-pointer-buttons a {
			margin: 0px 3px !important;
		}
	</style>
	<script type="text/javascript">
	//<![CDATA[
	jQuery(document).ready( function($) {
		jQuery('table.form-table.wvp-main-settings tr').css('cssText','opacity:0.5');
		<?php if ( isset($_GET['page']) && 'wvp_vendor' == $_GET['page'] ) { ?>
			jQuery('table.form-table.wvp-main-settings tr').css('cssText','opacity:0.2');
			jQuery('table.form-table.wvp-main-settings tr:nth-child(1)').css('cssText','opacity:1');
			var pointer_elem;
			$('table.form-table.wvp-main-settings tr:nth-child(1) td p:nth-child(2) label').pointer({
				content: '<?php echo wp_kses_post($user_role); ?>',
				position: {
					edge: 'left',
					align: 'center',
					offset: '-25 0'
				},
				buttons: function (event, t) {
				button = jQuery ('<a id="pointer-close" class="button-primary next">NEXT</a>');
				button.bind ('click.pointer', function () {
					//t.element.pointer ('close');
					t.element.pointer ('close');
					registration_page();
				});
				return button;
			},
			close: function() {
					setUserSetting( 'p1', '1' );
				}
			}).pointer('open');
			jQuery('a.next').after('<a id="pointer-skip" class="button-secondary skip">SKIP</a>');
			jQuery('a.skip').after('<span>1 OF 4</span>');
			close_pointer('table.form-table.wvp-main-settings tr:nth-child(1) td p:nth-child(2) label');
			function registration_page() {
				jQuery('table.form-table.wvp-main-settings tr').css('cssText','opacity:0.2');
				jQuery('table.form-table.wvp-main-settings tr:nth-child(4)').css('cssText','opacity:1');
				$('table.form-table.wvp-main-settings tr:nth-child(4) select').pointer({
				content: '<?php echo wp_kses_post($registration_page); ?>',
				position: {
					edge: 'left',
					align: 'center',
					offset: '-25 0'
				},
				buttons: function (event, t) {
				button = jQuery ('<a id="pointer-close" class="button-primary next">NEXT</a>');
				button.bind ('click.pointer', function () {
						t.element.pointer ('close');
						pricing_labels();
				});
					return button;
				},
				close: function() {
					setUserSetting( 'p1', '1' );
				}
				}).pointer('open');
				jQuery('a.next').after('<a id="pointer-skip" class="button-secondary skip">SKIP</a>');
				jQuery('a.skip').after('<span>2 OF 4</span>');
				close_pointer('table.form-table.wvp-main-settings tr:nth-child(4) select');
			}
			function pricing_labels() {
				jQuery('table.form-table.wvp-main-settings tr').css('cssText','opacity:0.2');
				jQuery('table.form-table.wvp-main-settings tr:nth-child(8)').css('cssText','opacity:1');
				jQuery('table.form-table.wvp-main-settings tr:nth-child(9)').css('cssText','opacity:1');
				jQuery('table.form-table.wvp-main-settings tr:nth-child(10)').css('cssText','opacity:1');
				$('table.form-table.wvp-main-settings tr:nth-child(8) input').pointer({
				content: '<?php echo '<h3>' . wp_kses_post($pricing_labels) . '</h3>'; ?>',
				position: {
					edge: 'left',
					align: 'center',
					offset: '-25 0'
				},
				buttons: function (event, t) {
				button = jQuery ('<a id="pointer-close" class="button-primary next">NEXT</a>');
				button.bind ('click.pointer', function () {
					t.element.pointer ('close');
					user_role();
				});
					return button;
				},
				close: function() {
					setUserSetting( 'p1', '1' );
				}
				}).pointer('open');
				jQuery('a.next').after('<a id="pointer-skip" class="button-secondary skip">SKIP</a>');
				jQuery('a.skip').after('<span>3 OF 4</span>');
				close_pointer('table.form-table.wvp-main-settings tr:nth-child(8) input');
			}
			function user_role() {
				jQuery('table.form-table.wvp-main-settings tr').css('cssText','opacity:0.2');
				$('li#toplevel_page_wvp_vendor ul li:nth-child(3)').pointer({
				content: '<?php echo wp_kses_post($add_user_role); ?>',
				position: {
					edge: 'left',
					align: 'center',
					offset: '-25 0'
				},
				buttons: function (event, t) {
				button = jQuery ('<a id="pointer-close" href="<?php echo esc_url(admin_url('edit-tags.php?taxonomy=vendor_user_roles&wvp_skip=1')); ?>" class="button-primary next">Add User Role</a>');
				button.bind ('click.pointer', function () {
				jQuery('table.form-table tr').css('cssText','opacity:1');
					close_pointer();
				});
					return button;
				},
				close: function() {
					setUserSetting( 'p1', '1' );
				}
				}).pointer('open');
				jQuery('a.next').after('<a id="pointer-skip" class="button-secondary skip">SKIP</a>');
				jQuery('a.skip').after('<span>4 OF 4</span>');
				close_pointer('li#toplevel_page_wvp_vendor ul li:nth-child(3)');
			}
		<?php } ?>
		function close_pointer(elem) {
			jQuery('a.skip').click(function() {
				jQuery('table.form-table.wvp-main-settings tr').css('cssText','opacity:1');
				jQuery(elem).pointer('close');
				window.open("<?php echo esc_url(admin_url('admin.php?page=wvp_vendor&wvp_skip=1')); ?>", "_self");
			});
		}
	});
	</script>
	<?php
}
/**
 * Set guide pointer
 * 
 * @since   1.0
 * @version 1.0
 */
function wvp_enqueue_wp_pointer( $hook_suffix ) {
	$enqueue = false;
	$admin_bar = get_user_setting('p1', 0); // check settings on user
	// check if admin bar is active and default filter for wp pointer is true
	if (! $admin_bar && apply_filters('show_wp_pointer_admin_bar', true) ) {
		$enqueue = true;
		add_action('admin_print_footer_scripts', 'wvp_get_content_in_wp_pointer');
	}
	// in true, include the scripts
	if ($enqueue ) {
		wp_enqueue_style('wp-pointer');
		wp_enqueue_script('wp-pointer');
		wp_enqueue_script('utils'); // for user settings
	}
}
add_action('admin_enqueue_scripts', 'wvp_enqueue_wp_pointer');
register_activation_hook(__FILE__, 'wvp_setting_notice_activation_hook');
/**
 * Set settings transient
 * 
 * @since   1.0
 * @version 1.0
 */
function wvp_setting_notice_activation_hook() {
	set_transient('wvp-setting-notice', true, 5);
}
