<?php
// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;
$table_prefix = $wpdb->prefix;

// Names of the custom tables
$custom_tables = array(
    'cmvine_MembersOrganizations',
    'cmvine_Organizations',
    'cmvine_OrganizationTypes'
);

// Loop through and drop the custom tables
foreach ($custom_tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS {$table_prefix}{$table};");
}
