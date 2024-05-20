<?php
    defined('ABSPATH') || exit;

    // Display and manage organization types
    function cv_display_organization_types() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
    
    
        // Show organization types
        cv_show_organization_types();
    }

    
    // Function to show all organization types
    function cv_show_organization_types() {
        global $wpdb;
        $results = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "cmvine_OrganizationTypes");
    
       echo '<div class="wrap">';
       echo '<h1 class="wp-heading-inline">Organization Types</h1>';
        echo '<a href="?page=cv-add-organization-type" class="page-title-action">Add New</a>';
        echo '<hr class="wp-header-end">';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>ID</th><th>Type</th><th>Category</th></tr></thead>';
        echo '<tbody>';
        foreach ($results as $row) {
            echo '<tr>';
            echo '<td>' . esc_html($row->OrganizationTypesID) . '</td>';
            echo '<td>' . esc_html($row->OrganizationType) . '</td>';
            echo '<td>' . esc_html($row->OrganizationCategory) . '</td>';
            echo '<td><a href="' . wp_nonce_url(admin_url('admin-post.php?action=cv_delete_organization_type&id=' . $row->OrganizationTypesID), 'cv_delete_organization_type_' . $row->OrganizationTypesID) . '" class="button-link-delete">Delete</a></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        echo '</div>';
    }
    

   // *********Function to display the form for adding a new organization type ***********
function cv_add_organization_type_page() {
    global $wpdb;
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Check if the form has been submitted
    if (isset($_POST['submit']) && check_admin_referer('cv_add_organization_type_action', 'cv_organization_type_nonce')) {
        $type = sanitize_text_field($_POST['organizationType']);
        $category = sanitize_text_field($_POST['organizationCategory']);
        
        // Insert the new type into the database
        $wpdb->insert(
            $wpdb->prefix . 'cmvine_OrganizationTypes',
            array('OrganizationType' => $type, 'OrganizationCategory' => $category),
            array('%s', '%s')
        );

        echo '<div id="message" class="updated notice is-dismissible"><p>Organization type added.</p></div>';
    }

    // Display the form
    echo '<div class="wrap">';
    echo '<h1>Add New Organization Type</h1>';
    echo '<form method="post">';
    wp_nonce_field('cv_add_organization_type_action', 'cv_organization_type_nonce');
    echo '<table class="form-table">';
    echo '<tr><th scope="row">Organization Type</th><td><input type="text" name="organizationType" required /></td></tr>';
    echo '<tr><th scope="row">Organization Category</th><td><input type="text" name="organizationCategory" required /></td></tr>';
    echo '</table>';
    echo '<input type="submit" name="submit" class="button-primary" value="Add Type" />';
    echo '</form>';
    echo '</div>';
}
   // *********Function to end add the form for adding a new organization type ***********



    // ****************Delete the organization type ********************
// Handle the delete operation
add_action('admin_post_cv_delete_organization_type', 'cv_delete_organization_type');
function cv_delete_organization_type() {
    global $wpdb;

    if (!current_user_can('manage_options') || !isset($_GET['id']) || !wp_verify_nonce($_GET['_wpnonce'], 'cv_delete_organization_type_' . $_GET['id'])) {
        wp_die('You are not allowed to delete this item.');
    }

    $wpdb->delete(
        $wpdb->prefix . 'cmvine_OrganizationTypes',
        ['OrganizationTypesID' => intval($_GET['id'])],
        ['%d']
    );

    // Redirect back to the organization types page with a success message
    wp_redirect(add_query_arg('page', 'Organizations_Type', admin_url('admin.php')));
    exit;
}
// ****************end delete orgainization type ********************