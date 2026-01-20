<?php
/**
 * Link Health
 *
 * @package           LinkHealth
 * @author            Ssu-Technology Limited
 * @copyright         2026 Ssu-Technology Limited
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Link Health
 * Plugin URI:        https://swiftspeed.org/
 * Description:       Monitor internal and external link health, detect broken links, redirects, and gain editorial link intelligence without automatic content changes.
 * Version:           1.0.0
 * Author:            Ssu-Technology Limited
 * Author URI:        https://swiftspeed.org
 * Text Domain:       link-health
 * Domain Path:       /languages
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Requires PHP:      7.4
 * Requires at least: 5.6
 */

if (!defined('ABSPATH')) {
    exit;
}

define('LI_VERSION', '1.0.0');
define('LI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LI_PLUGIN_URL', plugin_dir_url(__FILE__));

class Link_Intelligence {
    
    private static $instance = null;
    
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->includes();
        $this->hooks();
        $this->load_textdomain();
    }
    
    private function load_textdomain() {
        load_plugin_textdomain('link-health', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    private function includes() {
        require_once LI_PLUGIN_DIR . 'includes/class-li-database.php';
        require_once LI_PLUGIN_DIR . 'includes/class-li-scanner.php';
        require_once LI_PLUGIN_DIR . 'includes/class-li-internal-link-scanner.php';
        require_once LI_PLUGIN_DIR . 'includes/class-li-external-scanner.php';
        require_once LI_PLUGIN_DIR . 'includes/class-li-intelligence-scanner.php';
        require_once LI_PLUGIN_DIR . 'includes/class-li-fixer.php';
        require_once LI_PLUGIN_DIR . 'includes/class-li-ajax.php';
        require_once LI_PLUGIN_DIR . 'admin/class-li-admin.php';
    }
    
    private function hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('plugins_loaded', array($this, 'init'));
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_action_links'));
        add_action('wp_ajax_li_scan_continue', array('LI_Ajax', 'scan_continue'));
        add_action('wp_ajax_li_scan_start', array('LI_Ajax', 'scan_start'));
        add_action('wp_ajax_li_scan_cancel', array('LI_Ajax', 'scan_cancel'));
        add_action('wp_ajax_li_check_scan_status', array('LI_Ajax', 'check_scan_status'));
        add_action('wp_ajax_li_fix_link', array('LI_Ajax', 'fix_link'));
        add_action('wp_ajax_li_bulk_fix_start', array('LI_Ajax', 'bulk_fix_start'));
        add_action('wp_ajax_li_bulk_fix_continue', array('LI_Ajax', 'bulk_fix_continue'));
        add_action('wp_ajax_li_bulk_fix_cancel', array('LI_Ajax', 'bulk_fix_cancel'));
        add_action('wp_ajax_li_ignore_issue', array('LI_Ajax', 'ignore_issue'));
        add_action('wp_ajax_li_get_issues', array('LI_Ajax', 'get_issues'));
        add_action('wp_ajax_li_get_intelligence', array('LI_Ajax', 'get_intelligence'));
        add_action('wp_ajax_li_get_post_titles', array('LI_Ajax', 'get_post_titles'));
        add_action('wp_ajax_li_save_settings', array('LI_Ajax', 'save_settings'));
        add_action('wp_ajax_li_get_settings', array('LI_Ajax', 'get_settings'));
        add_action('wp_ajax_li_get_ignored', array('LI_Ajax', 'get_ignored'));
        add_action('wp_ajax_li_unignore_issue', array('LI_Ajax', 'unignore_issue'));
        add_action('wp_ajax_li_get_scan_history', array('LI_Ajax', 'get_scan_history'));
        add_action('wp_ajax_li_delete_scan_history', array('LI_Ajax', 'delete_scan_history'));
        add_action('wp_ajax_li_delete_all_scans', array('LI_Ajax', 'delete_all_scans'));
    }
    
    public function init() {
        if (is_admin()) {
            LI_Admin::instance();
        }
    }
    
    public function add_action_links($links) {
        $plugin_links = array(
            '<a href="' . admin_url('admin.php?page=link-health') . '">' . __('Settings', 'link-health') . '</a>',
        );
        return array_merge($plugin_links, $links);
    }
    
    public function activate() {
        // Create all database tables
        LI_Database::create_tables();
        
        // Force initialize default settings
        $default_settings = array(
            'allow_multiple_content_types' => false,
            'delete_on_uninstall' => false
        );
        
        foreach ($default_settings as $key => $value) {
            if (LI_Database::get_setting($key) === null) {
                LI_Database::update_setting($key, $value);
            }
        }
        
        // Migrate old wp_options data if it exists
        $old_settings = get_option('li_settings');
        if ($old_settings && is_array($old_settings)) {
            foreach ($old_settings as $key => $value) {
                LI_Database::update_setting($key, $value);
            }
            delete_option('li_settings');
        }
        
        // Clean up any old scan state in wp_options
        delete_option('li_scan_state');
    }
    
    public function deactivate() {
        // Clean up any active scans
        LI_Database::delete_scan_state();
    }
}

function Link_Intelligence() {
    return Link_Intelligence::instance();
}

Link_Intelligence();