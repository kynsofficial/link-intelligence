<?php
if (!defined('ABSPATH')) {
    exit;
}

class LI_Ajax {
    
    public static function scan_start() {
        check_ajax_referer('li_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $scan_type = isset($_POST['scan_type']) ? sanitize_text_field(wp_unslash($_POST['scan_type'])) : '';
        $config = isset($_POST['config']) ? self::sanitize_config(wp_unslash($_POST['config'])) : array();
        
        $scanner = self::get_scanner($scan_type);
        
        if (!$scanner) {
            wp_send_json_error(array('message' => 'Invalid scan type'));
        }
        
        $result = $scanner->start_scan($config);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    public static function scan_continue() {
        check_ajax_referer('li_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        // Use wp_options for fast state retrieval
        $state = get_option('li_scan_state');
        
        if (!$state) {
            wp_send_json_error(array('message' => 'No active scan'));
        }
        
        $scanner = self::get_scanner($state['scan_type']);
        
        if (!$scanner) {
            wp_send_json_error(array('message' => 'Invalid scan type'));
        }
        
        $result = $scanner->continue_scan();
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    public static function scan_cancel() {
        check_ajax_referer('li_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $state = get_option('li_scan_state');
        
        if ($state && isset($state['scan_id'])) {
            // Mark scan as cancelled in the scans table
            LI_Database::update_scan($state['scan_id'], array(
                'status' => 'cancelled',
                'completed_at' => current_time('mysql')
            ));
        }
        
        // Delete the scan state
        delete_option('li_scan_state');
        
        wp_send_json_success(array('message' => 'Scan cancelled'));
    }
    
    public static function check_scan_status() {
        check_ajax_referer('li_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $state = get_option('li_scan_state');
        
        wp_send_json_success(array(
            'has_active_scan' => !empty($state) && $state['status'] === 'running',
            'state' => $state
        ));
    }
    
    public static function fix_link() {
        check_ajax_referer('li_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $issue_id = isset($_POST['issue_id']) ? intval($_POST['issue_id']) : 0;
        
        if (!$issue_id) {
            wp_send_json_error(array('message' => 'Invalid issue ID'));
        }
        
        $result = LI_Fixer::fix_link($issue_id);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    public static function ignore_issue() {
        check_ajax_referer('li_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $issue_id = isset($_POST['issue_id']) ? intval($_POST['issue_id']) : 0;
        $reason = isset($_POST['reason']) ? sanitize_textarea_field(wp_unslash($_POST['reason'])) : '';
        
        if (!$issue_id) {
            wp_send_json_error(array('message' => 'Invalid issue ID'));
        }
        
        $result = LI_Database::ignore_issue($issue_id, $reason);
        
        if ($result) {
            wp_send_json_success(array('message' => 'Issue ignored'));
        } else {
            wp_send_json_error(array('message' => 'Failed to ignore issue'));
        }
    }
    
    public static function unignore_issue() {
        check_ajax_referer('li_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $issue_id = isset($_POST['issue_id']) ? intval($_POST['issue_id']) : 0;
        
        if (!$issue_id) {
            wp_send_json_error(array('message' => 'Invalid issue ID'));
        }
        
        $result = LI_Database::unignore_issue($issue_id);
        
        if ($result) {
            wp_send_json_success(array('message' => 'Issue restored'));
        } else {
            wp_send_json_error(array('message' => 'Failed to restore issue'));
        }
    }
    
    public static function get_issues() {
        check_ajax_referer('li_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $scan_type = isset($_POST['scan_type']) ? sanitize_text_field(wp_unslash($_POST['scan_type'])) : '';
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 20;
        $filters = isset($_POST['filters']) ? array_map('sanitize_text_field', wp_unslash($_POST['filters'])) : array();
        
        $result = LI_Database::get_issues($scan_type, $page, $per_page, $filters);
        
        wp_send_json_success($result);
    }
    
    public static function get_ignored() {
        check_ajax_referer('li_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 20;
        
        $result = LI_Database::get_ignored_issues($page, $per_page);
        
        wp_send_json_success($result);
    }
    
    public static function get_intelligence() {
        check_ajax_referer('li_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $metric_type = isset($_POST['metric_type']) ? sanitize_text_field(wp_unslash($_POST['metric_type'])) : '';
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 20;
        
        $result = LI_Database::get_intelligence($metric_type, $page, $per_page);
        
        wp_send_json_success($result);
    }
    
    public static function get_post_titles() {
        check_ajax_referer('li_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $post_ids = isset($_POST['post_ids']) ? array_map('intval', $_POST['post_ids']) : array();
        
        if (empty($post_ids)) {
            wp_send_json_success(array('posts' => array()));
            return;
        }
        
        $posts = array();
        foreach ($post_ids as $post_id) {
            $post = get_post($post_id);
            if ($post) {
                $posts[$post_id] = array(
                    'id' => $post_id,
                    'title' => $post->post_title,
                    'type' => $post->post_type,
                    'edit_url' => get_edit_post_link($post_id, 'raw')
                );
            }
        }
        
        wp_send_json_success(array('posts' => $posts));
    }
    
    public static function save_settings() {
        check_ajax_referer('li_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        if (!isset($_POST['settings']) || !is_array($_POST['settings'])) {
            wp_send_json_error(array('message' => 'Invalid settings data'));
        }
        
        $allow_multiple_content = isset($_POST['settings']['allow_multiple_content_types']) && 
                                   $_POST['settings']['allow_multiple_content_types'] === 'true';
        $delete_on_uninstall = isset($_POST['settings']['delete_on_uninstall']) && 
                               $_POST['settings']['delete_on_uninstall'] === 'true';
        
        $results = array();
        $results['allow_multiple_content_types'] = LI_Database::update_setting('allow_multiple_content_types', $allow_multiple_content);
        $results['delete_on_uninstall'] = LI_Database::update_setting('delete_on_uninstall', $delete_on_uninstall);
        
        $failed = array();
        foreach ($results as $key => $result) {
            if ($result === false) {
                $failed[] = $key;
            }
        }
        
        if (!empty($failed)) {
            wp_send_json_error(array(
                'message' => 'Failed to save some settings: ' . implode(', ', $failed),
                'results' => $results
            ));
        }
        
        $verify = array(
            'allow_multiple_content_types' => LI_Database::get_setting('allow_multiple_content_types'),
            'delete_on_uninstall' => LI_Database::get_setting('delete_on_uninstall')
        );
        
        wp_send_json_success(array(
            'message' => 'Settings saved',
            'saved_values' => array(
                'allow_multiple_content_types' => $allow_multiple_content,
                'delete_on_uninstall' => $delete_on_uninstall
            ),
            'verified_values' => $verify,
            'results' => $results
        ));
    }
    
    public static function get_settings() {
        check_ajax_referer('li_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $settings = LI_Database::get_all_settings();
        
        if (!isset($settings['allow_multiple_content_types'])) {
            $settings['allow_multiple_content_types'] = false;
        }
        if (!isset($settings['delete_on_uninstall'])) {
            $settings['delete_on_uninstall'] = false;
        }
        
        wp_send_json_success(array('settings' => $settings));
    }
    
    public static function get_scan_history() {
        check_ajax_referer('li_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 20;
        
        $result = LI_Database::get_all_scans($page, $per_page);
        
        wp_send_json_success($result);
    }
    
    public static function delete_scan_history() {
        check_ajax_referer('li_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $scan_id = isset($_POST['scan_id']) ? intval($_POST['scan_id']) : 0;
        
        if (!$scan_id) {
            wp_send_json_error(array('message' => 'Invalid scan ID'));
        }
        
        $result = LI_Database::delete_scan($scan_id);
        
        if ($result) {
            wp_send_json_success(array('message' => 'Scan deleted successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete scan'));
        }
    }
    
    public static function delete_all_scans() {
        check_ajax_referer('li_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $result = LI_Database::delete_all_scans();
        
        if ($result) {
            wp_send_json_success(array('message' => 'All scans deleted successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete scans'));
        }
    }
    
    public static function bulk_fix_start() {
        check_ajax_referer('li_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $scan_type = isset($_POST['scan_type']) ? sanitize_text_field(wp_unslash($_POST['scan_type'])) : '';
        $issue_ids = isset($_POST['issue_ids']) ? array_map('intval', $_POST['issue_ids']) : array();
        
        if (empty($scan_type)) {
            wp_send_json_error(array('message' => 'Invalid scan type'));
        }
        
        $result = LI_Fixer::start_bulk_fix($scan_type, $issue_ids);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    public static function bulk_fix_continue() {
        check_ajax_referer('li_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $result = LI_Fixer::continue_bulk_fix();
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    public static function bulk_fix_cancel() {
        check_ajax_referer('li_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $result = LI_Fixer::cancel_bulk_fix();
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    private static function sanitize_config($config) {
        if (!is_array($config)) {
            return array();
        }
        
        $sanitized = array();
        
        foreach ($config as $key => $value) {
            $key = sanitize_text_field($key);
            
            if (is_array($value)) {
                // Handle nested arrays (e.g., content_type array)
                $sanitized[$key] = array_map('sanitize_text_field', $value);
            } else {
                $sanitized[$key] = sanitize_text_field($value);
            }
        }
        
        return $sanitized;
    }
    
    private static function get_scanner($scan_type) {
        switch ($scan_type) {
            case 'internal_links':
                return new LI_Internal_Link_Scanner();
            case 'external_errors':
                return new LI_External_Scanner();
            case 'intelligence':
                return new LI_Intelligence_Scanner();
            default:
                return null;
        }
    }
}