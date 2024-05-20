<?php
 defined('ABSPATH') || exit;

//****** / Function to show all organization *********
// Display and manage organization
function MemberOrganizationFunc(){
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }


    // Show organization
    cv_show_Members();
}

function cv_show_Members() {
    global $wpdb;
    $prefix = $wpdb->prefix;

    // Adjust the query to fetch MemberPress membership details
    $results = $wpdb->get_results("
    SELECT u.display_name, u.user_email, o.OrganizationName, mo.MemberType, mp.post_title AS product_name
    FROM {$prefix}cmvine_membersorganizations AS mo
    INNER JOIN {$prefix}users AS u ON mo.memberID = u.ID
    INNER JOIN {$prefix}cmvine_organizations AS o ON mo.OrganizationID = o.OrganizationID
    LEFT JOIN (
        SELECT mt.user_id, mt.product_id
        FROM {$prefix}mepr_transactions AS mt
        WHERE mt.status = 'complete'
        GROUP BY mt.user_id, mt.product_id
    ) AS mt ON mo.memberID = mt.user_id
    LEFT JOIN {$prefix}posts AS mp ON mt.product_id = mp.ID
    ");

    echo '<div class="wrap">';
    echo '<h1 class="wp-heading-inline">Members</h1>';
    echo '<a href="' . esc_url(admin_url('admin.php?page=cv-add-member')) . '" class="page-title-action">Add New</a>';
    echo '<hr class="wp-header-end">';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead>
            <tr>
                <th>Member Name</th>
                <th>Email</th>
                <th>Organization Name</th>
                <th>Member Type</th>
                <th>Membership Name</th>
            </tr>
        </thead>';
    echo '<tbody>';
    foreach ($results as $row) {
        echo '<tr>';
        echo '<td>' . esc_html($row->display_name) . '</td>';
        echo '<td>' . esc_html($row->user_email) . '</td>';
        echo '<td>' . esc_html($row->OrganizationName) . '</td>';
        echo '<td>' . esc_html($row->MemberType) . '</td>';
        echo '<td>' . esc_html($row->product_name ? $row->product_name : 'N/A') . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    echo '</div>';
}


//****** /End Function to show all organization *********
function cv_add_member_page() {
    global $wpdb;

    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    if (isset($_POST['submit']) && check_admin_referer('cv_add_member_action', 'cv_member_nonce')) {
        $first_name = sanitize_text_field($_POST['firstName']);
        $last_name = sanitize_text_field($_POST['lastName']);
        $email = sanitize_email($_POST['emailAddress']);
        $username = sanitize_text_field($_POST['username']);
        $password = sanitize_text_field($_POST['password']);
        $member_type = sanitize_text_field($_POST['memberType']); // This is now a text field
        $membership = intval($_POST['membership']); // This is now the select field for memberships
        $organization_id = intval($_POST['organizationID']);

        // Retrieve the webhook key
        $webhookKey = $wpdb->get_var("SELECT option_value FROM {$wpdb->prefix}options WHERE option_name = 'mpdt_api_key'");

        // Construct the API payload
        $payload = json_encode([
            'email' => $email,
            'password' => $password,
            'username' => $username,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'send_welcome_email' => true,
            'transaction' => [
                'membership' => $membership,
                'amount' => '0.00',
                'total' => '0.00',
                'tax_amount' => '0.00',
                'tax_rate' => '0.000',
                'trans_num' => 'mp-txn-' . uniqid(), // Generate a unique transaction ID
                'status' => 'complete',
                'gateway' => 'free',
                'created_at' => date('c'),
                'expires_at' => '0000-00-00 00:00:00'
            ]
        ]);

        // Make the API call
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => home_url('/wp-json/mp/v1/members'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'MEMBERPRESS-API-KEY: ' . $webhookKey,
            ],
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        // Decode the JSON response
        $response_data = json_decode($response, true);

        // Check for "error" in response
        if (!isset($response_data['error'])) {
            $user_id = $response_data['id'] ?? null;

            if ($user_id) {
                // Insert into custom table
                $wpdb->insert(
                    $wpdb->prefix . 'cmvine_membersorganizations',
                    array(
                        'memberID' => $user_id,
                        'OrganizationID' => $organization_id,
                        'MemberType' => $member_type
                    ),
                    array(
                        '%d',
                        '%d',
                        '%s'
                    )
                );

                echo '<div id="message" class="updated notice is-dismissible"><p>Member added successfully.</p></div>';
            } else {
                echo '<div id="message" class="error notice is-dismissible"><p>Failed to retrieve user ID from the response.</p></div>';
            }
        } else {
            $error_message = $response_data['error'] ?? 'Failed to add member.';
            echo '<div id="message" class="error notice is-dismissible"><p>' . esc_html($error_message) . '</p></div>';
        }
    }

    // Get MemberPress memberships
    $memberships = get_posts(array(
        'post_type' => 'memberpressproduct',
        'posts_per_page' => -1
    ));

    // Get organizations
    $organizations = $wpdb->get_results("SELECT OrganizationID, OrganizationName FROM {$wpdb->prefix}cmvine_organizations");

    // Display the form
    echo '<div class="wrap">';
    echo '<h1>Add New Member</h1>';
    echo '<form method="post">';
    wp_nonce_field('cv_add_member_action', 'cv_member_nonce');
    echo '<table class="form-table">';
    echo '<tr><th scope="row">First Name</th><td><input type="text" name="firstName" required /></td></tr>';
    echo '<tr><th scope="row">Last Name</th><td><input type="text" name="lastName" required /></td></tr>';
    echo '<tr><th scope="row">Email Address</th><td><input type="email" name="emailAddress" required /></td></tr>';
    echo '<tr><th scope="row">Username</th><td><input type="text" name="username" required /></td></tr>';
    echo '<tr><th scope="row">Password</th><td><input type="password" name="password" required /></td></tr>';
    echo '<tr><th scope="row">Member Type</th><td><input type="text" name="memberType" required /></td></tr>'; // This is the text field for member type
    echo '<tr><th scope="row">Membership</th><td><select name="membership" required>'; // This is the select field for memberships
    foreach ($memberships as $membership) {
        echo '<option value="' . esc_attr($membership->ID) . '">' . esc_html($membership->post_title) . '</option>';
    }
    echo '</select></td></tr>';
    echo '<tr><th scope="row">Organization</th><td><select name="organizationID" class="organization-select" required>';
    echo '<option value="">Select Organization</option>';
    foreach ($organizations as $organization) {
        echo '<option value="' . esc_attr($organization->OrganizationID) . '">' . esc_html($organization->OrganizationName) . '</option>';
    }
    echo '</select></td></tr>';
    echo '</table>';
    echo '<input type="submit" name="submit" class="button-primary" value="Add Member" />';
    echo '</form>';
    echo '</div>';

 echo '<script>
        jQuery(document).ready(function($) {
            $(".organization-select").select2({
                width: "350px",
                placeholder: "Search for an organization",
                allowClear: true
            });
        });
    </script>';
}