<?php
if (!defined('ABSPATH')) {
    exit;
}

class LI_Admin {
    
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
            'Link Health',
            'Link Health',
            'manage_options',
            'link-intelligence',
            array($this, 'render_page'),
            'dashicons-admin-links',
            30
        );
    }
    
    public function enqueue_scripts($hook) {
        if ($hook !== 'toplevel_page_link-intelligence') {
            return;
        }
        
        // Enqueue Dashicons
        wp_enqueue_style('dashicons');
        
        // Main admin CSS
        wp_enqueue_style(
            'li-admin-css',
            LI_PLUGIN_URL . 'admin/css/admin.css',
            array('dashicons'),
            LI_VERSION
        );
        

        
        // Enqueue Core JS - handles initialization and navigation
        wp_enqueue_script(
            'li-admin-core',
            LI_PLUGIN_URL . 'admin/js/admin-core.js',
            array('jquery'),
            LI_VERSION,
            true
        );
        
        // Enqueue Scans JS - handles all scanning operations
        wp_enqueue_script(
            'li-admin-scans',
            LI_PLUGIN_URL . 'admin/js/admin-scans.js',
            array('jquery', 'li-admin-core'),
            LI_VERSION,
            true
        );
        
        // Enqueue Data Render JS - handles rendering (no dependencies on DataActions)
        wp_enqueue_script(
            'li-admin-data-render',
            LI_PLUGIN_URL . 'admin/js/admin-data-render.js',
            array('jquery', 'li-admin-core'),
            LI_VERSION,
            true
        );
        
        // Enqueue Data Actions JS - handles data loading and actions (depends on DataRender)
        wp_enqueue_script(
            'li-admin-data-actions',
            LI_PLUGIN_URL . 'admin/js/admin-data-actions.js',
            array('jquery', 'li-admin-core', 'li-admin-data-render'),
            LI_VERSION,
            true
        );
        
        // Localize for the core script
        wp_localize_script('li-admin-core', 'liAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('li_ajax_nonce'),
            'site_url' => get_site_url()
        ));
    }
    
    public function render_page() {
        // Get settings from database
        $settings = array(
            'allow_multiple_content_types' => LI_Database::get_setting('allow_multiple_content_types', false),
            'allow_multiple_redirect_types' => LI_Database::get_setting('allow_multiple_redirect_types', false),
            'delete_on_uninstall' => LI_Database::get_setting('delete_on_uninstall', false)
        );
        
        // Get scan state from database
        $scan_state = LI_Database::get_scan_state();
        
        $post_types = $this->get_post_types();
        
        include LI_PLUGIN_DIR . 'admin/views/main.php';
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