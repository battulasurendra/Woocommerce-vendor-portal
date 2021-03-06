<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
/**
 * Class To handle Vendor Customer Requests
 */
if (!class_exists('AVP_Easy_Vendor_Requests')) {

    class AVP_Easy_Vendor_Requests
    {

        public function __construct()
        {
            add_action('init', array($this, 'register_requests_post_type'));
            add_filter('manage_avp_requests_posts_columns', array($this, 'register_avp_requests_columns'));
            add_action('manage_avp_requests_posts_custom_column', array($this, 'custom_columns_avp_requests'), 15, 2);
            add_action('admin_menu', array($this, 'register_menu_for_requests'));
            add_action('add_meta_boxes', array($this, 'register_add_meta_box_requests'));
            add_action('save_post_avp_requests', array($this, 'save_requests_meta'));
        }
        public function register_requests_post_type()
        {
            $labels = array(
                'name'              => esc_html_x('Requests', 'Post Type Name', 'woocommerce-vendor-portal'),
                'singular_name'     => esc_html_x('Request', 'Post Type Singular Name', 'woocommerce-vendor-portal'),
                'menu_name'         => esc_html__('Request', 'woocommerce-vendor-portal'),
                'name_admin_bar'    => esc_html__('Request', 'woocommerce-vendor-portal'),
                'add_new'           => esc_html__('Add New Request', 'woocommerce-vendor-portal'),
                'add_new_item'      => esc_html__('Add New Request', 'woocommerce-vendor-portal'),
                'new_item'          => esc_html__('New Request', 'woocommerce-vendor-portal'),
                'edit_item'         => esc_html__('Edit Request', 'woocommerce-vendor-portal'),
                'view_item'         => esc_html__('View Request', 'woocommerce-vendor-portal'),
                'all_items'         => esc_html__('Requests List', 'woocommerce-vendor-portal'),
                'search_items'      => esc_html__('Search Request', 'woocommerce-vendor-portal'),
                'not_found'         => esc_html__('No Request found.', 'woocommerce-vendor-portal'),
                'not_found_in_trash' => esc_html__('No Request found in Trash.', 'woocommerce-vendor-portal')
            );
            $args = array(
                'labels'            => $labels,
                'description'       => esc_html__('Description.', 'woocommerce-vendor-portal'),
                'public'            => false,
                'show_ui'           => true,
                'show_in_menu'      => false,
                'query_var'         => true,
                'rewrite'           => false,
                'capability_type'   => 'post',
                'has_archive'       => true,
                'hierarchical'      => false,
                'menu_position'     => null,
                'supports'          => array('thumbnail', 'title')
            );
            register_post_type('avp_requests', $args);
        }

        public function register_menu_for_requests()
        {
            $args = array(
                'posts_per_page'    => -1,
                'post_type'         => 'avp_requests',
                'post_status'       => 'publish',
                'meta_key'          => '_user_status',
                'meta_value'        => 'waiting'
            );
            $posts_query = new WP_Query($args);
            $the_count = $posts_query->post_count;
            if (0 != $the_count) {
                $the_count = '<span class="awaiting-mod">' . $the_count . '</span>';
            } else {
                $the_count = '';
            }
            // add_submenu_page('avp_vendor', esc_html__('Vendor User Requests', 'woocommerce-vendor-portal'), __('Requests ' . $the_count, 'woocommerce-vendor-portal'), 'manage_vendor_user_requests', 'edit.php?post_type=avp_requests');
        }

        public function register_avp_requests_columns($columns)
        {
            unset($columns['author']);
            $columns['user_role'] = esc_html__('Current Roles', 'woocommerce-vendor-portal');
            $columns['user_status'] = esc_html__('Upgrade Status', 'woocommerce-vendor-portal');
            return $columns;
        }

        public function custom_columns_avp_requests($column, $post_id)
        {
            switch ($column) {
                case 'user_status':
                    $status = get_post_meta($post_id, '_user_status', true);
                    if ('active' == $status) {
                        echo '<p class="approved text-success">' . esc_html__('Approved', 'woocommerce-vendor-portal') . '</p>';
                    } elseif ('waiting' == $status) {
                        echo '<p class="waiting text-warning">' . esc_html__('Waiting', 'woocommerce-vendor-portal') . '</p>';
                    } elseif ('rejected' == $status) {
                        echo '<p class="rejected text-danger">' . esc_html__('Rejected', 'woocommerce-vendor-portal') . '</p>';
                    }
                    break;
                case 'user_role':
                    $user_id = get_post_meta($post_id, '_user_id', true);
                    $user_info = get_userdata($user_id);
                    echo '<div style="text-transform: capitalize;">' . implode(', ', $user_info->roles) . '</div>';
                    break;
            }
        }
        public function register_add_meta_box_requests()
        {
            add_meta_box(
                'vendor-portal-pro-user-status',
                esc_html__('Vendor User Request Confirmation', 'woocommerce-vendor-portal'),
                array($this, 'vendor_user_confirmation'),
                'avp_requests',
                'normal',
                'high'
            );
        }
        
        public function vendor_user_confirmation()
        {
            global $post;
            $status = get_post_meta($post->ID, '_user_status', true);
            $user_id = get_post_meta($post->ID, '_user_id', true);
            $rejected_note = get_user_meta($user_id, 'rejected_note', true);
            wp_nonce_field('request_user_role_nonce', 'request_user_role_nonce'); ?>
            <div class="vendor_user_confirmation">
                <?php
                if (!empty($user_id)) {
                    $user_info = get_userdata($user_id);
                    if ($user_info) {
                ?>
                        <div class="user_info">
                            <table>
                                <tbody>
                                    <?php
                                    $wp_roles = wp_roles();
                                    if (!empty($user_info->roles[0])) {
                                        $wp_roles_user_info = $wp_roles->role_names[$user_info->roles[0]];
                                    }
                                    echo '<tr><th>' . esc_html__('User ID: ', 'woocommerce-vendor-portal') . '</th><td><a href="' . esc_url(admin_url("user-edit.php?user_id=$user_id")) . '">' . esc_html($user_info->ID) . '</a></td></tr>';
                                    echo '<tr><th>' . esc_html__('Username: ', 'woocommerce-vendor-portal') . '</th><td><a href="' . esc_url(admin_url("user-edit.php?user_id=$user_id")) . '">' . esc_html($user_info->user_login) . '</a></td></tr>';

                                    echo '<tr><th>' . esc_html__('Current user role: ', 'woocommerce-vendor-portal') . '</th><td><a href="' . esc_url(admin_url("user-edit.php?user_id=$user_id")) . '">' . esc_html($wp_roles_user_info) . '</a></td></tr>';

                                    echo '<tr><th></th><td><a href="' . esc_url(admin_url("user-edit.php?user_id=$user_id")) . '">' . esc_html__('More user details', 'woocommerce-vendor-portal') . '</a></td></tr>';

                                    echo '<tr class="user_role"><th>' . esc_html__('Vendor roles to be assign: ', 'woocommerce-vendor-portal') . '</th><td>' . esc_html('contractor') . '</td></tr>';
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        <hr>
                <?php
                    } else {
                        echo esc_html__('User does not exist', 'woocommerce-vendor-portal');
                    }
                } else {
                    echo esc_html__('User does not exist', 'woocommerce-vendor-portal');
                }
                ?>
                <table>
                    <tbody>
                        <tr>
                            <th>
                                <?php esc_html_e('Request status', 'woocommerce-vendor-portal'); ?>
                            </th>
                            <td>
                                <label>
                                    <input id="active" type="radio" name="user_status" value="active" <?php echo ('active' == $status) ? 'checked' : ''; ?>>
                                    <?php esc_html_e('Approve', 'woocommerce-vendor-portal'); ?>
                                </label>
                                <label>
                                    <input id="rejected" type="radio" name="user_status" value="rejected" <?php echo ('rejected' == $status) ? 'checked' : ''; ?>>
                                    <?php esc_html_e('Reject', 'woocommerce-vendor-portal'); ?>
                                </label>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div id="rejected_note" style="padding-bottom: 11px;padding-top: 11px;">
                <textarea name="rejected_note" rows="4" cols="120" placeholder="Reject Note"><?php echo esc_html_e($rejected_note, 'woocommerce-vendor-portal'); ?></textarea>
            </div>
            <input name="save" type="submit" class="button button-primary button-large" id="publish" value="<?php esc_html_e('Update', 'woocommerce-vendor-portal'); ?>">
            <style type="">
                .post-type-avp_requests .page-title-action {
					display: none;
				}
			</style>
<?php
        }
        public function save_requests_meta($post_id)
        {
            // Autosave
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return;
            }
            // AJAX
            if (defined('DOING_AJAX') && DOING_AJAX) {
                return;
            }
            if (!isset($_POST['request_user_role_nonce']) || !wp_verify_nonce(wc_clean($_POST['request_user_role_nonce']), 'request_user_role_nonce')) {
                return;
            }

            if (isset($_POST['user_status'])) {
                $status = wc_clean($_POST['user_status']);
                if (isset($_POST['user_role_set'])) {
                    $user_role_set = wc_clean($_POST['user_role_set']);
                } else {
                    $user_role_set = 'contractor';
                }
                if (isset($_POST['rejected_note'])) {
                    $rejected_note = wc_clean($_POST['rejected_note']);
                    update_post_meta($post_id, '_user_status', $status);
                }
                $term_list = wp_get_post_terms($post_id, 'vendor_user_roles', array('fields' => 'all'));

                $allterms = get_terms('vendor_user_roles', array('hide_empty' => false));

                $user_id = get_post_meta($post_id, '_user_id', true);
                update_user_meta($user_id, '_user_status', $status);
                update_user_meta($user_id, 'rejected_note', $rejected_note);

                $u = new WP_User($user_id);
                if ('active' == $status) {

                    $wp_roles = new WP_Roles();
                    // $names = $wp_roles->get_names();

                    // foreach ($names as $key => $value) {
                    //     $u->remove_role($key);
                    // }
                    $u->add_role($user_role_set);

                    foreach ($term_list as $term_remove) {

                        wp_remove_object_terms($post_id, $term_remove->slug, 'vendor_user_roles', true);
                    }

                    wp_set_object_terms($post_id, $user_role_set, 'vendor_user_roles', true);

                    if ('sent' != get_post_meta($post_id, '_approval_notification', true)) {
                        do_action('avp_vendor_user_request_approved', $user_id);
                        update_post_meta($post_id, '_approval_notification', 'sent');
                    }
                } else {
                    if ('rejected' == $status) {
                        foreach ($allterms as $key => $value) {
                            $u->remove_role($value->slug);
                        }
                        $u->add_role(get_option('default_role'));
                        do_action('avp_vendor_user_rejection_notification', $user_id);
                    }
                }
            }
        }
    }
    new AVP_Easy_Vendor_Requests();
}
