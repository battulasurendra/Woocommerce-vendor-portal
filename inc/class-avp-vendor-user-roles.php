<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}
/**
 * Class Woo_Vendor_User_Roles
 */
if (!class_exists('AVP_Vendor_User_Roles')) {

	class AVP_Vendor_User_Roles {

		public function __construct () {
			add_role('vendor', esc_html__('vendor Role', 'woocommerce-vendor-portal'), array( 'read' => true, 'level_0' => true ));
			// add_action('init', array($this, 'register_taxonomy_for_users'));
			// add_action('created_vendor_user_roles', array($this, 'set_term_to_user_role'), 10, 2);
			// add_action('delete_vendor_user_roles', array($this, 'remove_term_and_user_role'), 10, 3);
			// add_action('edit_vendor_user_roles', array($this, 'edit_term_and_user_role'), 10, 2);
			add_action('wp_head', array($this, 'print_css_styles'));
			// add_action('vendor_user_roles_add_form_fields', array($this, 'avp_add_new_field'), 10);
			// add_action('vendor_user_roles_edit_form_fields', array($this, 'avp_edit_new_field'), 10, 1);
			// add_action('edited_vendor_user_roles', array($this, 'avp_save_new_field'), 10, 2 );
			// add_action('create_vendor_user_roles', array($this, 'avp_save_new_field'), 10, 2 );
		}
		public function print_css_styles() { ?>
			<style type="text/css">
				p.user_not_vendor {
					text-align: center;
				}
				p.user_not_vendor a {
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
				'label'                     => esc_html__('Vendor Roles', 'woocommerce-vendor-portal'),
				'name'                      => esc_html__('Vendor User Roles', 'woocommerce-vendor-portal'),
				'singular_name'             => esc_html__('Vendor Role', 'woocommerce-vendor-portal'),
				'search_items'              => esc_html__('Search User Roles', 'woocommerce-vendor-portal'),
				'popular_items'             => esc_html__('Popular User Roles', 'woocommerce-vendor-portal'),
				'all_items'                 => esc_html__('All User Roles', 'woocommerce-vendor-portal'),
				'parent_item'               => null,
				'parent_item_colon'         => null,
				'edit_item'                 => esc_html__('Edit User Role', 'woocommerce-vendor-portal'), 
				'update_item'               => esc_html__('Update User Role', 'woocommerce-vendor-portal'),
				'add_new_item'              => esc_html__('Add New User Role', 'woocommerce-vendor-portal'),
				'new_item_name'             => esc_html__('New User Role Name', 'woocommerce-vendor-portal'),
				'separate_items_with_commas'=> esc_html__('Separate topics with commas', 'woocommerce-vendor-portal'),
				'add_or_remove_items'       => esc_html__('Add or remove topics', 'woocommerce-vendor-portal'),
				'choose_from_most_used'     => esc_html__('Choose from the most used topics', 'woocommerce-vendor-portal'),
				'menu_name'                 => esc_html__('Vendor Roles', 'woocommerce-vendor-portal'),
			); 
			$args=array(
				'hierarchical'          => false,
				'labels'                => $labels,
				'show_ui'               => true,
				'show_admin_column'     => true,
				'update_count_callback' => '_update_post_term_count',
				'query_var'             => true,
			);
			register_taxonomy( 'vendor_user_roles', array( 'avp_requests' ), $args );
			$term = term_exists( 'vendor', 'vendor_user_roles' );
			if ( null === $term) {
				wp_insert_term( 'vendor', 'vendor_user_roles', array( 'slug' => 'vendor' ) );
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
				'manage_vendor_user_requests'
			);
            
            $capabilities = apply_filters( 'vendor_user_capabilities', $capabilities);
            
			foreach ( $capabilities as $cap ) {
				$wp_roles->add_cap( 'shop_manager', $cap );
				$wp_roles->add_cap( 'administrator', $cap );
			}
		}
		// public function set_term_to_user_role ( $term_id, $tt_id ) {
		// 	$term=get_term($term_id, 'vendor_user_roles');
		// 	if ( !wp_roles()->is_role($term->slug) ) {
		// 		add_role( $term->slug, $term->name . esc_html__(' - vendor role', 'woocommerce-vendor-portal'), array( 'read' => true, 'level_0' => true ) );
		// 	}
		// }
		// public function remove_term_and_user_role ( $term, $tt_id, $deleted_term ) {
		// 	$termObj = get_term( $deleted_term, 'vendor_user_roles' );
		// 	if ( wp_roles()->is_role( $termObj->slug ) ) {
		// 		remove_role( $termObj->slug );
		// 	}
		// }
		// public function edit_term_and_user_role ( $term_id, $tt_id ) {
		// 	if ( isset($_POST['avp_vendor_register_nonce']) || wp_verify_nonce( wc_clean($_POST['avp_vendor_register_nonce']), 'avp_vendor_register_nonce') ) {
		// 		echo esc_html__('Role updated', 'woocommerce-vendor-portal');
		// 	}
		// 	$termObj = get_term( $term_id, 'vendor_user_roles' );
		// 	$new_name = isset( $_POST['name'] ) ? wc_clean( $_POST['name'] ) : '';
		// 	$new_slug = isset( $_POST['slug'] ) ? wc_clean( $_POST['slug'] ) : '';
		// 	if ( $new_slug!=$termObj->slug ) {
		// 		if ( empty( $new_slug ) ) {
		// 			$new_slug = sanitize_title( $new_name );
		// 		}
		// 		if ( wp_roles()->is_role( $termObj->slug ) ) {
		// 			remove_role($termObj->slug);
		// 		}
		// 		if ( !wp_roles()->is_role( $new_slug ) ) {
		// 			add_role( $new_slug, $new_name . esc_html__(' - vendor role', 'woocommerce-vendor-portal'), array( 'read' => true, 'level_0' => true ) );
		// 		}
		// 		$args = array(
		// 			'role'    => $termObj->slug,
		// 		);
		// 		$users = get_users($args);
		// 		if ( !empty( $users ) ) {
		// 			foreach ( $users as $user ) {
		// 				$user = new WP_User( $user->ID );
		// 				// Remove current subscriber role
		// 				$user->remove_role( $termObj->slug );
		// 				$user->remove_cap( $termObj->slug );
		// 				// Upgrade to editor role
		// 				$user->add_role( $new_slug );
		// 				$user->add_cap( $new_slug );
		// 				wp_cache_delete( $user->ID, 'users' );
		// 			}
		// 		}
		// 	}
		// }
	}
	new AVP_Vendor_User_Roles();
}
