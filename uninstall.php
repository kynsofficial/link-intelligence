<?php
/**
 * Uninstall handler for Link Diagnostics
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Load WordPress database
global $wpdb;

// Define table names
$table_settings = $wpdb->prefix . 'lhcfwp_settings';
$table_scans = $wpdb->prefix . 'lhcfwp_scans';
$table_issues = $wpdb->prefix . 'lhcfwp_issues';
$table_ignored = $wpdb->prefix . 'lhcfwp_ignored';
$table_fixes = $wpdb->prefix . 'lhcfwp_fixes';
$table_intelligence = $wpdb->prefix . 'lhcfwp_intelligence';
$table_scan_state = $wpdb->prefix . 'lhcfwp_scan_state';

// Check if settings table exists
$settings_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_settings'") === $table_settings;

// Get delete_on_uninstall setting if table exists
$delete_on_uninstall = false;
if ($settings_table_exists) {
    $setting_value = $wpdb->get_var($wpdb->prepare(
        "SELECT setting_value FROM `{$table_settings}` WHERE setting_key = %s",
        'delete_on_uninstall'
    ));
    
    if ($setting_value !== null) {
        $delete_on_uninstall = maybe_unserialize($setting_value);
    }
}

// If delete_on_uninstall is enabled or we couldn't check (safer to delete), remove all data
if ($delete_on_uninstall) {
    // Drop all plugin tables
    $tables = array(
        $table_scans,
        $table_issues,
        $table_ignored,
        $table_fixes,
        $table_intelligence,
        $table_settings,
        $table_scan_state
    );
    
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS `{$table}`");
    }
    
    // Clean up any old wp_options data
    delete_option('lhcfwp_settings');
    delete_option('lhcfwp_scan_state');
}