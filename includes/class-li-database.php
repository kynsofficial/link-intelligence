<?php
if (!defined('ABSPATH')) {
    exit;
}

class LI_Database {
    
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // SCAN HISTORY TABLE - Master table for all scans
        $table_scans = $wpdb->prefix . 'li_scans';
        $sql_scans = "CREATE TABLE $table_scans (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            scan_type varchar(50) NOT NULL,
            scan_config longtext NOT NULL,
            scan_config_hash varchar(64) NOT NULL,
            total_posts int(11) NOT NULL DEFAULT 0,
            issues_found int(11) NOT NULL DEFAULT 0,
            status varchar(20) NOT NULL DEFAULT 'running',
            started_at datetime DEFAULT CURRENT_TIMESTAMP,
            completed_at datetime NULL,
            PRIMARY KEY  (id),
            KEY scan_type (scan_type),
            KEY status (status),
            KEY scan_config_hash (scan_config_hash)
        ) $charset_collate;";
        dbDelta($sql_scans);
        
        // Issues table with scan_id
        $table_issues = $wpdb->prefix . 'li_issues';
        $sql_issues = "CREATE TABLE $table_issues (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            scan_id bigint(20) UNSIGNED NOT NULL,
            scan_type varchar(50) NOT NULL,
            post_id bigint(20) UNSIGNED NOT NULL,
            post_title text NOT NULL,
            post_type varchar(50) NOT NULL,
            anchor_text text NOT NULL,
            current_url text NOT NULL,
            destination_url text,
            redirect_type varchar(10),
            status_code int(3),
            issue_type varchar(50) NOT NULL,
            is_fixable tinyint(1) DEFAULT 0,
            status varchar(20) NOT NULL DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY scan_id (scan_id),
            KEY scan_type (scan_type),
            KEY post_id (post_id),
            KEY issue_type (issue_type),
            KEY status (status)
        ) $charset_collate;";
        dbDelta($sql_issues);
        
        // Ignored issues table with scan_id
        $table_ignored = $wpdb->prefix . 'li_ignored';
        $sql_ignored = "CREATE TABLE $table_ignored (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            issue_id bigint(20) UNSIGNED NOT NULL,
            scan_id bigint(20) UNSIGNED NOT NULL,
            post_id bigint(20) UNSIGNED NOT NULL,
            current_url text NOT NULL,
            reason text,
            ignored_by bigint(20) UNSIGNED NOT NULL,
            ignored_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY issue_id (issue_id),
            KEY scan_id (scan_id),
            KEY post_id (post_id)
        ) $charset_collate;";
        dbDelta($sql_ignored);
        
        // Fix log table
        $table_fixes = $wpdb->prefix . 'li_fixes';
        $sql_fixes = "CREATE TABLE $table_fixes (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id bigint(20) UNSIGNED NOT NULL,
            old_url text NOT NULL,
            new_url text NOT NULL,
            anchor_text text NOT NULL,
            fixed_by bigint(20) UNSIGNED NOT NULL,
            fixed_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY post_id (post_id)
        ) $charset_collate;";
        dbDelta($sql_fixes);
        
        // Intelligence data table with scan_id
        $table_intelligence = $wpdb->prefix . 'li_intelligence';
        $sql_intelligence = "CREATE TABLE $table_intelligence (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            scan_id bigint(20) UNSIGNED NOT NULL,
            metric_type varchar(50) NOT NULL,
            metric_key text NOT NULL,
            metric_value bigint(20) NOT NULL,
            post_id bigint(20) UNSIGNED,
            post_title text,
            additional_data longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY scan_id (scan_id),
            KEY metric_type (metric_type)
        ) $charset_collate;";
        dbDelta($sql_intelligence);
        
        // Settings table - FIXED VERSION
        $table_settings = $wpdb->prefix . 'li_settings';
        $sql_settings = "CREATE TABLE $table_settings (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            setting_key varchar(100) NOT NULL,
            setting_value longtext NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY setting_key (setting_key)
        ) $charset_collate;";
        dbDelta($sql_settings);
        
        // Scan state table - for active scan tracking
        $table_scan_state = $wpdb->prefix . 'li_scan_state';
        $sql_scan_state = "CREATE TABLE $table_scan_state (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            scan_id bigint(20) UNSIGNED NOT NULL,
            scan_type varchar(50) NOT NULL,
            config longtext NOT NULL,
            posts longtext NOT NULL,
            total int(11) NOT NULL,
            current int(11) NOT NULL DEFAULT 0,
            processed longtext,
            issues_found int(11) NOT NULL DEFAULT 0,
            status varchar(20) NOT NULL DEFAULT 'running',
            started_at datetime DEFAULT CURRENT_TIMESTAMP,
            completed_at datetime NULL,
            PRIMARY KEY  (id),
            KEY scan_id (scan_id),
            KEY status (status)
        ) $charset_collate;";
        dbDelta($sql_scan_state);
    }
    
    // SCAN HISTORY METHODS
    
    public static function create_scan($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'li_scans';
        
        // Generate config hash for comparison
        $config_hash = md5(json_encode($data['scan_config']));
        
        // Check if same scan config exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE scan_type = %s AND scan_config_hash = %s",
            $data['scan_type'],
            $config_hash
        ));
        
        // Delete old scan with same config (and all its data)
        if ($existing) {
            self::delete_scan($existing);
        }
        
        // Insert new scan
        $insert_data = array(
            'scan_type' => $data['scan_type'],
            'scan_config' => json_encode($data['scan_config']),
            'scan_config_hash' => $config_hash,
            'total_posts' => isset($data['total_posts']) ? $data['total_posts'] : 0,
            'issues_found' => isset($data['issues_found']) ? $data['issues_found'] : 0,
            'status' => 'running'
        );
        
        $wpdb->insert($table, $insert_data);
        return $wpdb->insert_id;
    }
    
    public static function update_scan($scan_id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'li_scans';
        
        $update_data = array();
        
        if (isset($data['total_posts'])) {
            $update_data['total_posts'] = $data['total_posts'];
        }
        if (isset($data['issues_found'])) {
            $update_data['issues_found'] = $data['issues_found'];
        }
        if (isset($data['status'])) {
            $update_data['status'] = $data['status'];
        }
        if (isset($data['completed_at'])) {
            $update_data['completed_at'] = $data['completed_at'];
        }
        
        return $wpdb->update($table, $update_data, array('id' => $scan_id));
    }
    
    public static function delete_scan($scan_id) {
        global $wpdb;
        
        // Delete all issues for this scan
        $wpdb->delete($wpdb->prefix . 'li_issues', array('scan_id' => $scan_id));
        
        // Delete all ignored issues for this scan
        $wpdb->delete($wpdb->prefix . 'li_ignored', array('scan_id' => $scan_id));
        
        // Delete all intelligence for this scan
        $wpdb->delete($wpdb->prefix . 'li_intelligence', array('scan_id' => $scan_id));
        
        // Delete scan state if exists
        $wpdb->delete($wpdb->prefix . 'li_scan_state', array('scan_id' => $scan_id));
        
        // Delete the scan itself
        return $wpdb->delete($wpdb->prefix . 'li_scans', array('id' => $scan_id));
    }
    
    public static function delete_all_scans() {
        global $wpdb;
        
        // Truncate all related tables
        $wpdb->query("TRUNCATE TABLE " . $wpdb->prefix . 'li_issues');
        $wpdb->query("TRUNCATE TABLE " . $wpdb->prefix . 'li_ignored');
        $wpdb->query("TRUNCATE TABLE " . $wpdb->prefix . 'li_intelligence');
        $wpdb->query("TRUNCATE TABLE " . $wpdb->prefix . 'li_scan_state');
        $wpdb->query("TRUNCATE TABLE " . $wpdb->prefix . 'li_scans');
        
        return true;
    }
    
    public static function get_scan($scan_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'li_scans';
        
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $scan_id), ARRAY_A);
    }
    
    public static function get_all_scans($page = 1, $per_page = 20) {
        global $wpdb;
        $table = $wpdb->prefix . 'li_scans';
        
        $offset = ($page - 1) * $per_page;
        
        $query = "SELECT * FROM $table ORDER BY started_at DESC LIMIT %d OFFSET %d";
        $results = $wpdb->get_results($wpdb->prepare($query, $per_page, $offset), ARRAY_A);
        
        // Ensure scan_config is always JSON, not PHP serialized
        foreach ($results as &$scan) {
            // Check if scan_config is PHP serialized (starts with 'a:' or 's:' or similar)
            if (isset($scan['scan_config']) && (strpos($scan['scan_config'], 'a:') === 0 || strpos($scan['scan_config'], 's:') === 0)) {
                // It's serialized PHP data, convert to JSON
                $unserialized = @unserialize($scan['scan_config']);
                if ($unserialized !== false) {
                    $scan['scan_config'] = json_encode($unserialized);
                } else {
                    $scan['scan_config'] = '{}';
                }
            }
            // If it's already JSON or empty, leave it as is
        }
        
        $total = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        
        return array(
            'scans' => $results,
            'total' => (int) $total,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total / $per_page)
        );
    }
    
    public static function get_scans_by_type($scan_type) {
        global $wpdb;
        $table = $wpdb->prefix . 'li_scans';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT id, scan_config, started_at, completed_at, issues_found, status 
             FROM $table 
             WHERE scan_type = %s AND status = 'completed'
             ORDER BY completed_at DESC",
            $scan_type
        ), ARRAY_A);
    }
    
    // SCAN STATE METHODS
    
    public static function create_scan_state($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'li_scan_state';
        
        // Delete any existing scan state first
        $wpdb->query("DELETE FROM $table");
        
        $insert_data = array(
            'scan_id' => $data['scan_id'],
            'scan_type' => $data['scan_type'],
            'config' => json_encode($data['config']),
            'posts' => json_encode($data['posts']),
            'total' => $data['total'],
            'current' => isset($data['current']) ? $data['current'] : 0,
            'processed' => json_encode(isset($data['processed']) ? $data['processed'] : array()),
            'issues_found' => isset($data['issues_found']) ? $data['issues_found'] : 0,
            'status' => isset($data['status']) ? $data['status'] : 'running'
        );
        
        $wpdb->insert($table, $insert_data);
        return $wpdb->insert_id;
    }
    
    public static function update_scan_state($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'li_scan_state';
        
        $update_data = array(
            'current' => $data['current'],
            'processed' => json_encode($data['processed']),
            'issues_found' => $data['issues_found'],
            'status' => isset($data['status']) ? $data['status'] : 'running'
        );
        
        if (isset($data['completed_at'])) {
            $update_data['completed_at'] = $data['completed_at'];
        }
        
        return $wpdb->update($table, $update_data, array('scan_id' => $data['scan_id']));
    }
    
    public static function get_scan_state() {
        global $wpdb;
        $table = $wpdb->prefix . 'li_scan_state';
        
        $result = $wpdb->get_row("SELECT * FROM $table LIMIT 1", ARRAY_A);
        
        if (!$result) {
            return null;
        }
        
        // Decode JSON fields
        $result['config'] = json_decode($result['config'], true);
        $result['posts'] = json_decode($result['posts'], true);
        $result['processed'] = json_decode($result['processed'], true);
        
        return $result;
    }
    
    public static function delete_scan_state() {
        global $wpdb;
        $table = $wpdb->prefix . 'li_scan_state';
        
        return $wpdb->query("DELETE FROM $table");
    }
    
    // SETTINGS METHODS
    
    public static function get_setting($key, $default = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'li_settings';
        
        $value = $wpdb->get_var($wpdb->prepare(
            "SELECT setting_value FROM $table WHERE setting_key = %s",
            $key
        ));
        
        if ($value === null) {
            return $default;
        }
        
        return maybe_unserialize($value);
    }
    
    public static function update_setting($key, $value) {
        global $wpdb;
        $table = $wpdb->prefix . 'li_settings';
        
        $serialized_value = maybe_serialize($value);
        
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE setting_key = %s",
            $key
        ));
        
        if ($existing) {
            return $wpdb->update(
                $table,
                array('setting_value' => $serialized_value),
                array('setting_key' => $key)
            );
        } else {
            return $wpdb->insert($table, array(
                'setting_key' => $key,
                'setting_value' => $serialized_value
            ));
        }
    }
    
    public static function get_all_settings() {
        global $wpdb;
        $table = $wpdb->prefix . 'li_settings';
        
        $results = $wpdb->get_results("SELECT setting_key, setting_value FROM $table", ARRAY_A);
        
        $settings = array();
        foreach ($results as $row) {
            $settings[$row['setting_key']] = maybe_unserialize($row['setting_value']);
        }
        
        return $settings;
    }
    
    // ISSUES METHODS
    
    public static function add_issue($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'li_issues';
        
        // Check if same issue already exists for this scan
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table 
             WHERE scan_id = %d 
             AND post_id = %d 
             AND current_url = %s 
             AND anchor_text = %s",
            $data['scan_id'],
            $data['post_id'],
            $data['current_url'],
            $data['anchor_text']
        ));
        
        if ($existing) {
            return $existing;
        }
        
        $wpdb->insert($table, $data);
        return $wpdb->insert_id;
    }
    
    public static function get_issues($scan_type, $page = 1, $per_page = 20, $filters = array()) {
        global $wpdb;
        $table_issues = $wpdb->prefix . 'li_issues';
        $table_ignored = $wpdb->prefix . 'li_ignored';
        $table_scans = $wpdb->prefix . 'li_scans';
        
        $offset = ($page - 1) * $per_page;
        
        // Build WHERE clause
        $where = array("i.scan_type = %s");
        $where_values = array($scan_type);
        
        // If scan_id specified in filters, use it; otherwise show ALL issues from ALL scans of this type
        if (!empty($filters['scan_id'])) {
            $where[] = "i.scan_id = %d";
            $where_values[] = $filters['scan_id'];
        }
        
        if (!empty($filters['issue_type'])) {
            $where[] = "i.issue_type = %s";
            $where_values[] = $filters['issue_type'];
        }
        
        if (!empty($filters['post_type'])) {
            $where[] = "i.post_type = %s";
            $where_values[] = $filters['post_type'];
        }
        
        if (!empty($filters['is_fixable'])) {
            $where[] = "i.is_fixable = %d";
            $where_values[] = $filters['is_fixable'];
        }
        
        $where_sql = implode(' AND ', $where);
        
        $query = "SELECT i.* FROM $table_issues i
                  LEFT JOIN $table_ignored ig ON i.id = ig.issue_id
                  WHERE $where_sql AND ig.id IS NULL
                  ORDER BY i.created_at DESC
                  LIMIT %d OFFSET %d";
        
        $where_values[] = $per_page;
        $where_values[] = $offset;
        
        $results = $wpdb->get_results($wpdb->prepare($query, $where_values), ARRAY_A);
        
        $count_query = "SELECT COUNT(*) FROM $table_issues i
                        LEFT JOIN $table_ignored ig ON i.id = ig.issue_id
                        WHERE $where_sql AND ig.id IS NULL";
        
        $total = $wpdb->get_var($wpdb->prepare($count_query, array_slice($where_values, 0, count($where_values) - 2)));
        
        return array(
            'issues' => $results,
            'total' => (int) $total,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total / $per_page)
        );
    }
    
    public static function get_fixable_issues($scan_type, $filters = array()) {
        global $wpdb;
        $table_issues = $wpdb->prefix . 'li_issues';
        $table_ignored = $wpdb->prefix . 'li_ignored';
        
        $where = array("i.scan_type = %s", "i.is_fixable = 1");
        $where_values = array($scan_type);
        
        if (!empty($filters['scan_id'])) {
            $where[] = "i.scan_id = %d";
            $where_values[] = $filters['scan_id'];
        }
        
        if (!empty($filters['issue_ids']) && is_array($filters['issue_ids'])) {
            $placeholders = implode(',', array_fill(0, count($filters['issue_ids']), '%d'));
            $where[] = "i.id IN ($placeholders)";
            $where_values = array_merge($where_values, $filters['issue_ids']);
        }
        
        $where_sql = implode(' AND ', $where);
        
        $query = "SELECT i.* FROM $table_issues i
                  LEFT JOIN $table_ignored ig ON i.id = ig.issue_id
                  WHERE $where_sql AND ig.id IS NULL
                  ORDER BY i.id ASC";
        
        return $wpdb->get_results($wpdb->prepare($query, $where_values), ARRAY_A);
    }
    
    public static function delete_issue($issue_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'li_issues';
        
        return $wpdb->delete($table, array('id' => $issue_id));
    }
    
    public static function mark_issue_as_fixed($issue_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'li_issues';
        
        return $wpdb->update(
            $table,
            array('status' => 'fixed'),
            array('id' => $issue_id),
            array('%s'),
            array('%d')
        );
    }
    
    public static function ignore_issue($issue_id, $reason = '') {
        global $wpdb;
        $table_issues = $wpdb->prefix . 'li_issues';
        $table_ignored = $wpdb->prefix . 'li_ignored';
        
        $issue = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_issues WHERE id = %d", $issue_id), ARRAY_A);
        
        if (!$issue) {
            return false;
        }
        
        return $wpdb->insert($table_ignored, array(
            'issue_id' => $issue_id,
            'scan_id' => $issue['scan_id'],
            'post_id' => $issue['post_id'],
            'current_url' => $issue['current_url'],
            'reason' => $reason,
            'ignored_by' => get_current_user_id()
        ));
    }
    
    public static function unignore_issue($issue_id) {
        global $wpdb;
        $table_ignored = $wpdb->prefix . 'li_ignored';
        
        return $wpdb->delete($table_ignored, array('issue_id' => $issue_id));
    }
    
    public static function get_ignored_issues($page = 1, $per_page = 20) {
        global $wpdb;
        $table_issues = $wpdb->prefix . 'li_issues';
        $table_ignored = $wpdb->prefix . 'li_ignored';
        
        $offset = ($page - 1) * $per_page;
        
        $query = "SELECT i.*, ig.reason, ig.ignored_at, ig.ignored_by 
                  FROM $table_issues i
                  INNER JOIN $table_ignored ig ON i.id = ig.issue_id
                  ORDER BY ig.ignored_at DESC
                  LIMIT %d OFFSET %d";
        
        $results = $wpdb->get_results($wpdb->prepare($query, $per_page, $offset), ARRAY_A);
        
        $total = $wpdb->get_var("SELECT COUNT(*) FROM $table_ignored");
        
        return array(
            'issues' => $results,
            'total' => (int) $total,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total / $per_page)
        );
    }
    
    public static function log_fix($post_id, $old_url, $new_url, $anchor_text) {
        global $wpdb;
        $table = $wpdb->prefix . 'li_fixes';
        
        return $wpdb->insert($table, array(
            'post_id' => $post_id,
            'old_url' => $old_url,
            'new_url' => $new_url,
            'anchor_text' => $anchor_text,
            'fixed_by' => get_current_user_id()
        ));
    }
    
    public static function add_intelligence($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'li_intelligence';
        
        return $wpdb->insert($table, $data);
    }
    
    public static function get_intelligence($metric_type, $page = 1, $per_page = 20) {
        global $wpdb;
        $table = $wpdb->prefix . 'li_intelligence';
        $table_scans = $wpdb->prefix . 'li_scans';
        
        $offset = ($page - 1) * $per_page;
        
        // Get the most recent completed intelligence scan
        $latest_scan_id = $wpdb->get_var(
            "SELECT id FROM $table_scans 
             WHERE scan_type = 'intelligence' AND status = 'completed'
             ORDER BY completed_at DESC 
             LIMIT 1"
        );
        
        if (!$latest_scan_id) {
            // No completed intelligence scan found
            return array(
                'data' => array(),
                'total' => 0,
                'page' => $page,
                'per_page' => $per_page,
                'total_pages' => 0
            );
        }
        
        $query = "SELECT * FROM $table 
                  WHERE metric_type = %s AND scan_id = %d
                  ORDER BY metric_value DESC 
                  LIMIT %d OFFSET %d";
        
        $results = $wpdb->get_results($wpdb->prepare($query, $metric_type, $latest_scan_id, $per_page, $offset), ARRAY_A);
        
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE metric_type = %s AND scan_id = %d", 
            $metric_type, 
            $latest_scan_id
        ));
        
        return array(
            'data' => $results,
            'total' => (int) $total,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total / $per_page)
        );
    }
    
    public static function delete_all_data() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'li_scans',
            $wpdb->prefix . 'li_issues',
            $wpdb->prefix . 'li_ignored',
            $wpdb->prefix . 'li_fixes',
            $wpdb->prefix . 'li_intelligence',
            $wpdb->prefix . 'li_settings',
            $wpdb->prefix . 'li_scan_state'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
        // Clean up any old wp_options data
        delete_option('li_settings');
        delete_option('li_scan_state');
    }
}