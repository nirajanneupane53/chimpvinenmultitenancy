<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

function cv_multitenancy_setup_database() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();
    $table_prefix = $wpdb->prefix;

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // SQL to create your tables
    $sql = array();

    $sql[] = "CREATE TABLE IF NOT EXISTS `{$table_prefix}cmvine_OrganizationTypes` (
              `OrganizationTypesID` int(11) NOT NULL AUTO_INCREMENT,
              `OrganizationType` varchar(255) NOT NULL,
              `OrganizationCategory` varchar(255) NOT NULL,
              PRIMARY KEY (`OrganizationTypesID`)
            ) $charset_collate;";

    $sql[] = "CREATE TABLE IF NOT EXISTS `{$table_prefix}cmvine_Organizations` (
              `OrganizationID` int(11) NOT NULL AUTO_INCREMENT,
              `OrganizationName` varchar(255) NOT NULL,
              `OrganizationEmail` varchar(255) NOT NULL,
              `OrganizationTelephone` varchar(255) NOT NULL,
              `OrganizationTypesID` int(11) NOT NULL,
              `OrganizationCountry` varchar(255)NOT NULL,
              `OrganizationState` varchar(255) NOT NULL,
              `OrganizationCity` varchar(255) NOT NULL,
              PRIMARY KEY (`OrganizationID`),
              FOREIGN KEY (`OrganizationTypesID`) REFERENCES `{$table_prefix}cmvine_OrganizationTypes` (`OrganizationTypesID`) ON DELETE CASCADE
            ) $charset_collate;";

    $sql[] = "CREATE TABLE IF NOT EXISTS `{$table_prefix}cmvine_MembersOrganizations` (
              `MembersOrganizationsID` int(11) NOT NULL AUTO_INCREMENT,
              `memberID` bigint(20) UNSIGNED NOT NULL,
              `OrganizationID` int(11) NOT NULL,
              PRIMARY KEY (`MembersOrganizationsID`),
              `MemberType` varchar(255) NOT NULL,
              FOREIGN KEY (`memberID`) REFERENCES `{$wpdb->base_prefix}users` (`ID`) ON DELETE CASCADE,
              FOREIGN KEY (`OrganizationID`) REFERENCES `{$table_prefix}cmvine_Organizations` (`OrganizationID`) ON DELETE CASCADE
            ) $charset_collate;";

    foreach ($sql as $query) {
        dbDelta($query);
    }
}
