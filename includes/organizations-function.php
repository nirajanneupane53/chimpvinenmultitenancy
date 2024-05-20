<?php


defined('ABSPATH') || exit;

function enqueue_select2() {
    wp_enqueue_script('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', array('jquery'), '4.0.13', true);
    wp_enqueue_style('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css', array(), '4.0.13');
}
add_action('admin_enqueue_scripts', 'enqueue_select2');


//**************** */ Function to show all organization ************************
// Display and manage organization
function OrganizationFunc(){
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }


    // Show organization
    cv_show_organization();
}

function cv_show_organization() {
    global $wpdb;
    $results = $wpdb->get_results("
        SELECT o.*, ot.OrganizationType
        FROM {$wpdb->prefix}cmvine_organizations AS o
        INNER JOIN {$wpdb->prefix}cmvine_organizationtypes AS ot
        ON o.OrganizationTypesID = ot.OrganizationTypesID
    ");

    echo '<div class="wrap">';
    echo '<h1 class="wp-heading-inline">Organization</h1>';
    echo '<a href="?page=cv-add-organization" class="page-title-action">Add New</a>';
    echo '<hr class="wp-header-end">';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead>
            <tr>
                <th>ID</th>
                <th>Organization Name</th>
                <th>Email</th>
                <th>Telephone</th>
                <th>Organization Type</th>
                <th>Organization Country</th>
                <th>Organization State</th>
                <th>Organization City</th>
            </tr>
        </thead>';
    echo '<tbody>';
    foreach ($results as $row) {
        echo '<tr>';
        echo '<td>' . esc_html($row->OrganizationID) . '</td>';
        echo '<td>' . esc_html($row->OrganizationName) . '</td>';
        echo '<td>' . esc_html($row->OrganizationEmail) . '</td>';
        echo '<td>' . esc_html($row->OrganizationTelephone) . '</td>';
        echo '<td>' . esc_html($row->OrganizationType) . '</td>'; // Display OrganizationType instead of OrganizationTypesID
        echo '<td>' . esc_html($row->OrganizationCountry) . '</td>';
        echo '<td>' . esc_html($row->OrganizationState) . '</td>';
        echo '<td>' . esc_html($row->OrganizationCity) . '</td>';
        echo '<td><a href="' . wp_nonce_url(admin_url('admin-post.php?action=cv_delete_organization&id=' . $row->OrganizationID), 'cv_delete_organization_type_' . $row->OrganizationID) . '" class="button-link-delete">Delete</a></td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    echo '</div>';
}
//**************** */End Function to show all organization ************************




// *********Function to display the form for adding a new organization  ***********
function cv_add_organization_page() {
    global $wpdb;
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Check if the form has been submitted
    if (isset($_POST['submit']) && check_admin_referer('cv_add_organization_action', 'cv_organization_nonce')) {
        $type = sanitize_text_field($_POST['organizationName']);
        $category = intval($_POST['organizationType']); // Convert to integer for security
        
        // Sanitize email and telephone inputs
        $email = sanitize_email($_POST['organizationEmail']);
        $telephone = sanitize_text_field($_POST['organizationTelephone']);

        // Insert the new organization into the database
        $wpdb->insert(
            $wpdb->prefix . 'cmvine_organizations',
            array(
                'OrganizationName' => $type, 
                'OrganizationTypesID' => $category, 
                'OrganizationCountry' => sanitize_text_field($_POST['organizationCountry']),
                'OrganizationState' => sanitize_text_field($_POST['organizationState']),
                'OrganizationCity' => sanitize_text_field($_POST['organizationCity']),
                'OrganizationEmail' => $email,
                'OrganizationTelephone' => $telephone
            ),
            array('%s','%d','%s','%s','%s','%s','%s')
        );

        echo '<div id="message" class="updated notice is-dismissible"><p>Organization is added.</p></div>';
    }

    // Retrieve organization types
    $types = $wpdb->get_results("SELECT OrganizationTypesID, OrganizationType FROM {$wpdb->prefix}cmvine_organizationtypes");

    // Display the form
    echo '<div class="wrap">';
    echo '<h1>Add New Organization</h1>';
    echo '<form method="post">';
    wp_nonce_field('cv_add_organization_action', 'cv_organization_nonce');
    echo '<table class="form-table">';
    echo '<tr><th scope="row">Organization Name</th><td><input type="text" name="organizationName" required /></td></tr>';
    echo '<tr><th scope="row">Organization Type</th><td><select name="organizationType" required id="organizationType">';
    foreach ($types as $type) {
        echo '<option value="' . $type->OrganizationTypesID . '">' . $type->OrganizationType . '</option>';
    }
    echo '</select></td></tr>';
    echo '<tr><th scope="row">Country</th><td><input type="text" name="organizationCountry" required /></td></tr>';
    echo '<tr><th scope="row">State</th><td><input type="text" name="organizationState" required /></td></tr>';
    echo '<tr><th scope="row">City</th><td><input type="text" name="organizationCity" required /></td></tr>';
    echo '<tr><th scope="row">Email</th><td><input type="email" name="organizationEmail" required /></td></tr>';
    echo '<tr><th scope="row">Telephone</th><td><input type="text" name="organizationTelephone" required /></td></tr>';
    echo '</table>';
    echo '<input type="submit" name="submit" class="button-primary" value="Add Organization" />';
    echo '</form>';
    echo '</div>';
    
    // Initialize Select2 for the organization type dropdown
    echo "<script>
        jQuery(document).ready(function($) {
            $('#organizationType').select2();
        });
    </script>";
}


   // *********Function to end add the form for adding a new organization type ***********