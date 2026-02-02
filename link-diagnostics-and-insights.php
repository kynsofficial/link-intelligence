<?php
/**
 * Link Diagnostics - Broken Links, Redirects, and Link Insights
 *
 * @package           LinkDiagnosticsAndInsights
 * @author            Ssu-Technology Limited
 * @copyright         2026 Ssu-Technology Limited
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Link Diagnostics – Broken Links, Redirects, and Link Insights
 * Plugin URI:        https://swiftspeed.org/
 * Description:       Monitor internal and external link health, detect broken links, redirects, and gain editorial link intelligence without automatic content changes.
 * Version:           1.0.0
 * Author:            Ssu-Technology Limited
 * Author URI:        https://swiftspeed.org
 * Text Domain:       link-diagnostic-and-insights
 * Domain Path:       /languages
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Requires PHP:      7.4
 * Requires at least: 5.6
 */

if (!defined('ABSPATH')) {
    exit;
}

define('LHCFWP_VERSION', '1.0.0');
define('LHCFWP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LHCFWP_PLUGIN_URL', plugin_dir_url(__FILE__));

class LHCFWP_Main {

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
    }

    private function includes() {
        require_once LHCFWP_PLUGIN_DIR . 'includes/class-li-database.php';
        require_once LHCFWP_PLUGIN_DIR . 'includes/class-li-scanner.php';
        require_once LHCFWP_PLUGIN_DIR . 'includes/class-li-internal-link-scanner.php';
        require_once LHCFWP_PLUGIN_DIR . 'includes/class-li-external-scanner.php';
        require_once LHCFWP_PLUGIN_DIR . 'includes/class-li-intelligence-scanner.php';
        require_once LHCFWP_PLUGIN_DIR . 'includes/class-li-fixer.php';
        require_once LHCFWP_PLUGIN_DIR . 'includes/class-li-ajax.php';
        require_once LHCFWP_PLUGIN_DIR . 'includes/class-li-redirect-handler.php';
        require_once LHCFWP_PLUGIN_DIR . 'admin/class-li-admin.php';
    }

    private function hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('plugins_loaded', array($this, 'init'));
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_action_links'));

        add_action('wp_ajax_lhcfwp_scan_continue', array('LHCFWP_Ajax', 'scan_continue'));
        add_action('wp_ajax_lhcfwp_scan_start', array('LHCFWP_Ajax', 'scan_start'));
        add_action('wp_ajax_lhcfwp_scan_cancel', array('LHCFWP_Ajax', 'scan_cancel'));
        add_action('wp_ajax_lhcfwp_check_scan_status', array('LHCFWP_Ajax', 'check_scan_status'));
        add_action('wp_ajax_lhcfwp_fix_link', array('LHCFWP_Ajax', 'fix_link'));
        add_action('wp_ajax_lhcfwp_bulk_fix_start', array('LHCFWP_Ajax', 'bulk_fix_start'));
        add_action('wp_ajax_lhcfwp_bulk_fix_continue', array('LHCFWP_Ajax', 'bulk_fix_continue'));
        add_action('wp_ajax_lhcfwp_bulk_fix_cancel', array('LHCFWP_Ajax', 'bulk_fix_cancel'));
        add_action('wp_ajax_lhcfwp_ignore_issue', array('LHCFWP_Ajax', 'ignore_issue'));
        add_action('wp_ajax_lhcfwp_get_issues', array('LHCFWP_Ajax', 'get_issues'));
        add_action('wp_ajax_lhcfwp_get_intelligence', array('LHCFWP_Ajax', 'get_intelligence'));
        add_action('wp_ajax_lhcfwp_get_post_titles', array('LHCFWP_Ajax', 'get_post_titles'));
        add_action('wp_ajax_lhcfwp_save_settings', array('LHCFWP_Ajax', 'save_settings'));
        add_action('wp_ajax_lhcfwp_get_settings', array('LHCFWP_Ajax', 'get_settings'));
        add_action('wp_ajax_lhcfwp_get_ignored', array('LHCFWP_Ajax', 'get_ignored'));
        add_action('wp_ajax_lhcfwp_unignore_issue', array('LHCFWP_Ajax', 'unignore_issue'));
        add_action('wp_ajax_lhcfwp_get_scan_history', array('LHCFWP_Ajax', 'get_scan_history'));
        add_action('wp_ajax_lhcfwp_delete_scan_history', array('LHCFWP_Ajax', 'delete_scan_history'));
        add_action('wp_ajax_lhcfwp_delete_all_scans', array('LHCFWP_Ajax', 'delete_all_scans'));
        add_action('wp_ajax_lhcfwp_add_redirect', array('LHCFWP_Ajax', 'add_redirect'));
        add_action('wp_ajax_lhcfwp_update_redirect', array('LHCFWP_Ajax', 'update_redirect'));
        add_action('wp_ajax_lhcfwp_delete_redirect', array('LHCFWP_Ajax', 'delete_redirect'));
        add_action('wp_ajax_lhcfwp_get_redirects', array('LHCFWP_Ajax', 'get_redirects'));
        add_action('wp_ajax_lhcfwp_delete_redirects', array('LHCFWP_Ajax', 'delete_redirects'));
        add_action('wp_ajax_lhcfwp_clear_all_redirects', array('LHCFWP_Ajax', 'clear_all_redirects'));
    }

    public function init() {
        if (is_admin()) {
            LHCFWP_Admin::instance();
        }
        
        // Initialize redirect handler
        LHCFWP_Redirect_Handler::init();
    }

    public function add_action_links($links) {
        $plugin_links = array(
            '<a href="' . admin_url('admin.php?page=link-diagnostics-and-insights') . '">' . __('Settings', 'link-diagnostic-and-insights') . '</a>',
        );
        return array_merge($plugin_links, $links);
    }

    public function activate() {
        LHCFWP_Database::create_tables();

        $default_settings = array(
            'allow_multiple_content_types' => false,
            'delete_on_uninstall' => false
        );

        foreach ($default_settings as $key => $value) {
            if (LHCFWP_Database::get_setting($key) === null) {
                LHCFWP_Database::update_setting($key, $value);
            }
        }

        $old_settings = get_option('lhcfwp_settings');
        if ($old_settings && is_array($old_settings)) {
            foreach ($old_settings as $key => $value) {
                LHCFWP_Database::update_setting($key, $value);
            }
            delete_option('lhcfwp_settings');
        }

        delete_option('lhcfwp_scan_state');
    }

    public function deactivate() {
        LHCFWP_Database::delete_scan_state();
    }
}

function lhcfwp_init() {
    return LHCFWP_Main::instance();
}

lhcfwp_init();
