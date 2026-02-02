<?php
if (!defined('ABSPATH')) {
    exit;
}

class LHCFWP_Admin {
    
    private static $instance = null;
    
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    public function add_menu() {
        add_menu_page(
            'Link Diagnostics',
            'Link Diagnostics',
            'manage_options',
            'link-diagnostics-and-insights',
            array($this, 'render_page'),
            'dashicons-admin-links',
            30
        );
    }
    
    public function enqueue_scripts($hook) {
        if ($hook !== 'toplevel_page_link-diagnostics-and-insights') {
            return;
        }
        
        // Enqueue Dashicons
        wp_enqueue_style('dashicons');
        
        // Main admin CSS
        wp_enqueue_style(
            'lhcfwp-admin-css',
            LHCFWP_PLUGIN_URL . 'admin/css/admin.css',
            array('dashicons'),
            LHCFWP_VERSION
        );
        

        
        // Enqueue Core JS - handles initialization and navigation
        wp_enqueue_script(
            'lhcfwp-admin-core',
            LHCFWP_PLUGIN_URL . 'admin/js/admin-core.js',
            array('jquery'),
            LHCFWP_VERSION,
            true
        );
        
        // Enqueue Scans JS - handles all scanning operations
        wp_enqueue_script(
            'lhcfwp-admin-scans',
            LHCFWP_PLUGIN_URL . 'admin/js/admin-scans.js',
            array('jquery', 'lhcfwp-admin-core'),
            LHCFWP_VERSION,
            true
        );
        
        // Enqueue Data Render JS - handles rendering (no dependencies on DataActions)
        wp_enqueue_script(
            'lhcfwp-admin-data-render',
            LHCFWP_PLUGIN_URL . 'admin/js/admin-data-render.js',
            array('jquery', 'lhcfwp-admin-core'),
            LHCFWP_VERSION,
            true
        );
        
        // Enqueue Data Actions JS - handles data loading and actions (depends on DataRender)
        wp_enqueue_script(
            'lhcfwp-admin-data-actions',
            LHCFWP_PLUGIN_URL . 'admin/js/admin-data-actions.js',
            array('jquery', 'lhcfwp-admin-core', 'lhcfwp-admin-data-render'),
            LHCFWP_VERSION,
            true
        );
        
        // Enqueue Redirects JS - handles redirect management
        wp_enqueue_script(
            'lhcfwp-admin-redirects',
            LHCFWP_PLUGIN_URL . 'admin/js/admin-redirects.js',
            array('jquery', 'lhcfwp-admin-core'),
            LHCFWP_VERSION,
            true
        );
        
        // Localize for the core script
        wp_localize_script('lhcfwp-admin-core', 'lhcfwpAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lhcfwp_ajax_nonce'),
            'site_url' => get_site_url()
        ));
    }
    
    public function render_page() {
        // Get settings from database
        $settings = array(
            'allow_multiple_content_types' => LHCFWP_Database::get_setting('allow_multiple_content_types', false),
            'allow_multiple_redirect_types' => LHCFWP_Database::get_setting('allow_multiple_redirect_types', false),
            'delete_on_uninstall' => LHCFWP_Database::get_setting('delete_on_uninstall', false)
        );
        
        // Get scan state from database
        $scan_state = LHCFWP_Database::get_scan_state();
        
        $post_types = $this->get_post_types();
        
        include LHCFWP_PLUGIN_DIR . 'admin/views/main.php';
    }
    
    private function get_post_types() {
        $post_types = get_post_types(array('public' => true), 'objects');
        $types = array();
        
        foreach ($post_types as $post_type) {
            if ($post_type->name !== 'attachment') {
                $types[] = array(
                    'name' => $post_type->name,
                    'label' => $post_type->label
                );
            }
        }
        
        return $types;
    }
}