<?php
/**
 * Plugin Name: Chimpvine Multitenancy
 * Description: A plugin to manage multi-tenancy through organizational structures.
 * Version: 1.0.0
 * Author: Chimpvine
 * Author URI: https://site.chimpvine.com/
 * License: GPL-2.0+
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Include the database handler file
require_once plugin_dir_path(__FILE__) . 'includes/db-activation-handler.php';

//Organaization Type File
include 'includes/organization-type-function.php';

//Member Organaization File
include 'includes/member-organization-function.php';

//Organaization File
include 'includes/organizations-function.php';

//Bulk members Upload
include 'includes/bulk-members-function.php';
// Activation Hook
function cv_multitenancy_activate() {
    cv_multitenancy_setup_database();
    add_mt_subscriber_role() ;
}
register_activation_hook(__FILE__, 'cv_multitenancy_activate');


function cv_menu(){
}

function cv_multitenancy_menus(){
    add_menu_page('Chimpvine Multitenancy', 'CV MT', 'manage_options', 'CV-MT', 'cv_menu',"", 6);
    add_submenu_page('CV-MT', 'Organization', 'Organization', 'manage_options', 'CV-MT', 'Organization_func');
    add_submenu_page('CV-MT', 'Organization Type', 'Organization Type', 'manage_options', 'Organizations_Type', 'Organization_type_func');
    add_submenu_page(null, 'Add Organization Type', '', 'manage_options', 'cv-add-organization-type', 'cv_add_organization_type_page');
    add_submenu_page(null, 'Add Organization ', '', 'manage_options', 'cv-add-organization', 'cv_add_organization_page');
    add_submenu_page('CV-MT', 'Members Organization', 'Members Organization', 'manage_options', 'Members_Organizations', 'MemberOrganization_func');
    add_submenu_page('CV-MT', 'Bulk Members', 'Bulk Members', 'manage_options', 'bulk_add_members', 'MemberBulkUploadFunc');
    add_submenu_page(null, 'Add Members', '', 'manage_options', 'cv-add-member', 'cv_add_member_page');
}
add_action('admin_menu', 'cv_multitenancy_menus');
function Organization_type_func(){
    
    cv_display_organization_types();   
}
function MemberOrganization_func(){
    
    MemberOrganizationFunc();

}    
function Organization_func(){
     
 OrganizationFunc();
}   

// Add role with read capability if it doesn't already exist
function add_mt_subscriber_role() {
    // Check if the role already exists
    $role = get_role('mt_subscriber');

    // If the role doesn't exist
    if (null === $role) {
        // Get the subscriber role
        $subscriber = get_role('subscriber');

        // If the subscriber role exists
        if (null !== $subscriber) {
            // Clone the subscriber role
            $mt_subscriber = clone $subscriber;

            // Add additional capabilities
            $mt_subscriber->add_cap('read');

            // Add the new role
            add_role('mt_subscriber', __('MT Subscriber'), $mt_subscriber->capabilities);
        }
    }
}
