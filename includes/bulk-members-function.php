<?php

defined('ABSPATH') || exit;

// Enqueue scripts
function enqueue_custom_scripts() {
    wp_enqueue_script('jquery');
    wp_enqueue_script('bulk-upload-script', plugin_dir_url(__FILE__) . 'bulk-upload.js', array('jquery'), null, true);
    wp_localize_script('bulk-upload-script', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
}
add_action('admin_enqueue_scripts', 'enqueue_custom_scripts');


// Function to add members in bulk
function MemberBulkUploadFunc() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    global $wpdb;
    $types = $wpdb->get_results("SELECT OrganizationID, OrganizationName FROM {$wpdb->prefix}cmvine_organizations");
    $Membership = $wpdb->get_results("SELECT ID, post_title FROM {$wpdb->prefix}posts WHERE post_type = 'memberpressproduct'");
    $webhookKey = $wpdb->get_results("SELECT option_value FROM {$wpdb->prefix}options WHERE option_name = 'mpdt_api_key'");
    $home_url = home_url();

    echo '<div class="wrap" style="margin-bottom:20px;">';
    echo '<h1>Bulk Add Members</h1>';
    echo '<form id="bulk-upload-form" method="post" enctype="multipart/form-data">';
    wp_nonce_field('cv_add_organization_action', 'cv_organization_nonce');
    echo '<table class="form-table">';
    echo '<tr><th scope="row">Organization Name</th><td><select name="organizationName" required id="organizationName">';
    foreach ($types as $type) {
        echo '<option value="' . esc_attr($type->OrganizationID) . '">' . esc_html($type->OrganizationName) . '</option>';
    }
    echo '</select></td></tr>';
    echo '<tr><th scope="row">Membership</th><td><select name="membershipName" required id="membershipName">';
    foreach ($Membership as $membershipdata) {
        echo '<option value="' . esc_attr($membershipdata->ID) . '">' . esc_html($membershipdata->post_title) . '</option>';
    }
    echo '</select></td></tr>';
    echo '<tr><th scope="row">CSV File</th><td><input type="file" name="csvFile" accept=".csv" required></td></tr>';
    echo '</table>';
    echo '<input type="submit" name="submit" class="button-primary" value="Bulk Upload" />';
    echo '</form>';
    echo '<div id="loading" style="display:none; margin-top:10px;">Loading...</div>';
    echo '<div id="log" style="margin-top:10px;"></div>';
    echo '</div>';

    echo "<script>
        jQuery(document).ready(function($) {
            $('#organizationName').select2();
            $('#membershipName').select2();
        });
    </script>";
}
add_action('admin_menu', function() {
    add_menu_page('Bulk Add Members', 'Bulk Add Members', 'manage_options', 'bulk-add-members', 'MemberBulkUploadFunc');
});

// AJAX handler for bulk upload
function handle_bulk_upload() {
    if (!current_user_can('manage_options') || !check_ajax_referer('cv_add_organization_action', 'nonce', false)) {
        wp_send_json_error('Unauthorized request.');
        return;
    }

    if (!isset($_FILES['csvFile']) || $_FILES['csvFile']['error'] !== UPLOAD_ERR_OK) {
        wp_send_json_error('File upload error.');
        return;
    }

    global $wpdb;
    $webhookKey = $wpdb->get_results("SELECT option_value FROM {$wpdb->prefix}options WHERE option_name = 'mpdt_api_key'");
    $handle = fopen($_FILES['csvFile']['tmp_name'], 'r');
    $headers = fgetcsv($handle);
    $headerIndex = array_flip($headers);
    $rowNumber = 1;
    $logs = [];
    $successCount = 0;
    $errorCount = 0;

    while (($data = fgetcsv($handle)) !== false) {
        $rowNumber++;
        $requiredFields = ['First_Name', 'Last_Name', 'Email', 'Password'];
        $isValid = true;
        foreach ($requiredFields as $field) {
            if (empty($data[$headerIndex[$field]])) {
                $isValid = false;
                break;
            }
        }

        if ($isValid) {
            $payload = json_encode([
                'email' => sanitize_email($data[$headerIndex['Email']]),
                'password' => sanitize_text_field($data[$headerIndex['Password']]),
                'username' => sanitize_user($data[$headerIndex['Email']]),
                'first_name' => sanitize_text_field($data[$headerIndex['First_Name']]),
                'last_name' => sanitize_text_field($data[$headerIndex['Last_Name']]),
                'send_welcome_email' => true,
                'transaction' => [
                    'membership' => sanitize_text_field($_POST['membershipName']),
                    'amount' => '0.00',
                    'total' => '0.00',
                    'tax_amount' => '0.00',
                    'tax_rate' => '0.000',
                    'trans_num' => 'mp-txn-' . uniqid(),
                    'status' => 'complete',
                    'gateway' => 'free',
                    'created_at' => date('c'),
                    'expires_at' => '0000-00-00 00:00:00'
                ]
            ]);

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
                    'MEMBERPRESS-API-KEY: ' . esc_attr($webhookKey[0]->option_value)
                ],
            ]);

            $response = curl_exec($curl);
            $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            if ($status != 200) {
                $responseArray = json_decode($response, true);
                $errorMessage = isset($responseArray['message']) ? $responseArray['message'] : 'Unknown error occurred.';
                $logs[] = "Error in row $rowNumber: " . esc_html($errorMessage);
                $errorCount++;
            } else {
                $responseArray = json_decode($response, true);
                $memberId = isset($responseArray['first_txn']['member']) ? $responseArray['first_txn']['member'] : 0;

                $wpdb->insert(
                    $wpdb->prefix . 'cmvine_membersorganizations',
                    [
                        'memberID' => $memberId,
                        'OrganizationID' => intval($_POST['organizationName']),
                        'MemberType' => sanitize_text_field($data[$headerIndex['MemberType']])
                    ],
                    ['%d', '%d', '%s']
                );

                $logs[] = "Success: Row $rowNumber processed successfully.";
                $successCount++;
            }

            curl_close($curl);
        } else {
            $logs[] = "Error: Missing data in CSV row $rowNumber. All required fields must be filled.";
            $errorCount++;
        }
    }

    fclose($handle);
    wp_send_json_success(['logs' => $logs, 'successCount' => $successCount, 'errorCount' => $errorCount]);
}
add_action('wp_ajax_bulk_upload', 'handle_bulk_upload');

// Function to clean up related data when a user is deleted
function cmvine_cleanup_user_data($user_id) {
    global $wpdb;
    $wpdb->delete(
        $wpdb->prefix . 'cmvine_membersorganizations',
        ['memberID' => $user_id],
        ['%d']
    );
}
add_action('delete_user', 'cmvine_cleanup_user_data');