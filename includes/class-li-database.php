<?php
if (!defined('ABSPATH')) {
    exit;
}

class LHCFWP_Database {
    
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // SCAN HISTORY TABLE - Master table for all scans
        $table_scans = $wpdb->prefix . 'lhcfwp_scans';
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
        $table_issues = $wpdb->prefix . 'lhcfwp_issues';
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
        $table_ignored = $wpdb->prefix . 'lhcfwp_ignored';
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
        $table_fixes = $wpdb->prefix . 'lhcfwp_fixes';
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
        $table_intelligence = $wpdb->prefix . 'lhcfwp_intelligence';
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
        $table_settings = $wpdb->prefix . 'lhcfwp_settings';
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
        $table_scan_state = $wpdb->prefix . 'lhcfwp_scan_state';
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
        
        // Redirects table
        $table_redirects = $wpdb->prefix . 'lhcfwp_redirects';
        $sql_redirects = "CREATE TABLE $table_redirects (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            source_urls longtext NOT NULL,
            destination_url text NOT NULL,
            redirect_type varchar(10) NOT NULL DEFAULT '301',
            status varchar(20) NOT NULL DEFAULT 'active',
            match_type varchar(20) NOT NULL DEFAULT 'exact',
            scheduled_activation datetime NULL,
            scheduled_deactivation datetime NULL,
            category varchar(100),
            created_by bigint(20) UNSIGNED NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY status (status),
            KEY match_type (match_type)
        ) $charset_collate;";
        dbDelta($sql_redirects);
    }
    
    // SCAN HISTORY METHODS
    
    public static function create_scan($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'lhcfwp_scans';
        
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
        
        // Handle overlapping post types from previous scans
        self::cleanup_overlapping_scan_data($data['scan_type'], $data['scan_config']);
        
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
        $table = $wpdb->prefix . 'lhcfwp_scans';
        
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
        $wpdb->delete($wpdb->prefix . 'lhcfwp_issues', array('scan_id' => $scan_id));
        
        // Delete all ignored issues for this scan
        $wpdb->delete($wpdb->prefix . 'lhcfwp_ignored', array('scan_id' => $scan_id));
        
        // Delete all intelligence for this scan
        $wpdb->delete($wpdb->prefix . 'lhcfwp_intelligence', array('scan_id' => $scan_id));
        
        // Delete scan state if exists
        $wpdb->delete($wpdb->prefix . 'lhcfwp_scan_state', array('scan_id' => $scan_id));
        
        // Delete the scan itself
        return $wpdb->delete($wpdb->prefix . 'lhcfwp_scans', array('id' => $scan_id));
    }
    
    /**
     * Cleanup overlapping post type data from previous scans
     * 
     * When starting a new scan with multiple post types, this removes
     * data for those specific post types from all previous scans of the same type.
     * 
     * Example: If previous scan was [Post, Page, Course] and new scan is [Product, Page, Course],
     * this will delete Page and Course data from the previous scan, keeping Post data intact.
     */
    private static function cleanup_overlapping_scan_data($scan_type, $scan_config) {
        global $wpdb;
        
        // Extract content types from new scan config
        if (!isset($scan_config['content_type'])) {
            return;
        }
        
        $new_content_types = is_array($scan_config['content_type']) 
            ? $scan_config['content_type'] 
            : array($scan_config['content_type']);
        
        if (empty($new_content_types)) {
            return;
        }
        
        // Get all previous scans of the same type
        $table_scans = $wpdb->prefix . 'lhcfwp_scans';
        $previous_scans = $wpdb->get_results($wpdb->prepare(
            "SELECT id, scan_config FROM $table_scans WHERE scan_type = %s",
            $scan_type
        ), ARRAY_A);
        
        if (empty($previous_scans)) {
            return;
        }
        
        // For each previous scan, check for overlapping post types
        foreach ($previous_scans as $prev_scan) {
            $prev_config = json_decode($prev_scan['scan_config'], true);
            
            if (!isset($prev_config['content_type'])) {
                continue;
            }
            
            $prev_content_types = is_array($prev_config['content_type']) 
                ? $prev_config['content_type'] 
                : array($prev_config['content_type']);
            
            // Find overlapping post types
            $overlapping_types = array_intersect($new_content_types, $prev_content_types);
            
            if (!empty($overlapping_types)) {
                // Delete issues for overlapping post types from this previous scan
                self::delete_issues_by_post_types($prev_scan['id'], $overlapping_types);
            }
        }
    }
    
    /**
     * Delete issues for specific post types from a scan
     */
    private static function delete_issues_by_post_types($scan_id, $post_types) {
        global $wpdb;
        
        if (empty($post_types)) {
            return;
        }
        
        $table_issues = $wpdb->prefix . 'lhcfwp_issues';
        $table_ignored = $wpdb->prefix . 'lhcfwp_ignored';
        $table_intelligence = $wpdb->prefix . 'lhcfwp_intelligence';
        
        // Build WHERE clause for post types
        $placeholders = implode(',', array_fill(0, count($post_types), '%s'));
        $where_values = array_merge(array($scan_id), $post_types);
        
        // Get issue IDs that match these post types
        $issue_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT id FROM $table_issues WHERE scan_id = %d AND post_type IN ($placeholders)",
            $where_values
        ));
        
        if (!empty($issue_ids)) {
            // Delete from ignored table first (foreign key constraint)
            $id_placeholders = implode(',', array_fill(0, count($issue_ids), '%d'));
            $wpdb->query($wpdb->prepare(
                "DELETE FROM $table_ignored WHERE issue_id IN ($id_placeholders)",
                $issue_ids
            ));
        }
        
        // Delete issues for these post types
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_issues WHERE scan_id = %d AND post_type IN ($placeholders)",
            $where_values
        ));
        
        // Delete intelligence data for these post types (if post_id is set)
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_intelligence WHERE scan_id = %d AND post_id IN (
                SELECT DISTINCT ID FROM {$wpdb->posts} WHERE post_type IN ($placeholders)
            )",
            $where_values
        ));
        
        // Update the scan's issue count
        $remaining_issues = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_issues WHERE scan_id = %d",
            $scan_id
        ));
        
        self::update_scan($scan_id, array(
            'issues_found' => (int) $remaining_issues
        ));
    }
    
    public static function delete_all_scans() {
        global $wpdb;
        
        // Truncate all related tables
        $wpdb->query("TRUNCATE TABLE " . $wpdb->prefix . 'lhcfwp_issues');
        $wpdb->query("TRUNCATE TABLE " . $wpdb->prefix . 'lhcfwp_ignored');
        $wpdb->query("TRUNCATE TABLE " . $wpdb->prefix . 'lhcfwp_intelligence');
        $wpdb->query("TRUNCATE TABLE " . $wpdb->prefix . 'lhcfwp_scan_state');
        $wpdb->query("TRUNCATE TABLE " . $wpdb->prefix . 'lhcfwp_scans');
        
        return true;
    }
    
    public static function get_scan($scan_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'lhcfwp_scans';
        
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $scan_id), ARRAY_A);
    }
    
    public static function get_all_scans($page = 1, $per_page = 20) {
        global $wpdb;
        $table = $wpdb->prefix . 'lhcfwp_scans';
        
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
        $table = $wpdb->prefix . 'lhcfwp_scans';
        
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
        $table = $wpdb->prefix . 'lhcfwp_scan_state';
        
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
        $table = $wpdb->prefix . 'lhcfwp_scan_state';
        
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
        $table = $wpdb->prefix . 'lhcfwp_scan_state';
        
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
        $table = $wpdb->prefix . 'lhcfwp_scan_state';
        
        return $wpdb->query("DELETE FROM $table");
    }
    
    // SETTINGS METHODS
    
    public static function get_setting($key, $default = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'lhcfwp_settings';
        
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
        $table = $wpdb->prefix . 'lhcfwp_settings';
        
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
        $table = $wpdb->prefix . 'lhcfwp_settings';
        
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
        $table = $wpdb->prefix . 'lhcfwp_issues';
        
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
        $table_issues = $wpdb->prefix . 'lhcfwp_issues';
        $table_ignored = $wpdb->prefix . 'lhcfwp_ignored';
        $table_scans = $wpdb->prefix . 'lhcfwp_scans';
        
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
        $table_issues = $wpdb->prefix . 'lhcfwp_issues';
        $table_ignored = $wpdb->prefix . 'lhcfwp_ignored';
        
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
        $table = $wpdb->prefix . 'lhcfwp_issues';
        
        return $wpdb->delete($table, array('id' => $issue_id));
    }
    
    public static function mark_issue_as_fixed($issue_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'lhcfwp_issues';
        
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
        $table_issues = $wpdb->prefix . 'lhcfwp_issues';
        $table_ignored = $wpdb->prefix . 'lhcfwp_ignored';
        
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
        $table_ignored = $wpdb->prefix . 'lhcfwp_ignored';
        
        return $wpdb->delete($table_ignored, array('issue_id' => $issue_id));
    }
    
    public static function get_ignored_issues($page = 1, $per_page = 20) {
        global $wpdb;
        $table_issues = $wpdb->prefix . 'lhcfwp_issues';
        $table_ignored = $wpdb->prefix . 'lhcfwp_ignored';
        
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
        $table = $wpdb->prefix . 'lhcfwp_fixes';
        
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
        $table = $wpdb->prefix . 'lhcfwp_intelligence';
        
        return $wpdb->insert($table, $data);
    }
    
    public static function get_intelligence($metric_type, $page = 1, $per_page = 20) {
        global $wpdb;
        $table = $wpdb->prefix . 'lhcfwp_intelligence';
        $table_scans = $wpdb->prefix . 'lhcfwp_scans';
        
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
            $wpdb->prefix . 'lhcfwp_scans',
            $wpdb->prefix . 'lhcfwp_issues',
            $wpdb->prefix . 'lhcfwp_ignored',
            $wpdb->prefix . 'lhcfwp_fixes',
            $wpdb->prefix . 'lhcfwp_intelligence',
            $wpdb->prefix . 'lhcfwp_settings',
            $wpdb->prefix . 'lhcfwp_scan_state',
            $wpdb->prefix . 'lhcfwp_redirects'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
        // Clean up any old wp_options data
        delete_option('lhcfwp_settings');
        delete_option('lhcfwp_scan_state');
    }
    
    // REDIRECT METHODS
    
    public static function add_redirect($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'lhcfwp_redirects';
        
        // Ensure table exists with correct schema
        self::ensure_redirects_table_schema();
        
        // Ensure source_urls is an array
        $source_urls = isset($data['source_urls']) && is_array($data['source_urls']) 
            ? $data['source_urls'] 
            : (isset($data['source_url']) ? array($data['source_url']) : array());
        
        if (empty($source_urls)) {
            self::$last_error = 'No source URLs provided';
            return false;
        }
        
        // Check for duplicate source URLs across all redirects
        $duplicates = self::check_duplicate_sources($source_urls);
        if (!empty($duplicates)) {
            self::$last_error = 'Source URL(s) already exist: ' . implode(', ', $duplicates);
            return false;
        }
        
        $insert_data = array(
            'source_urls' => json_encode($source_urls),
            'destination_url' => $data['destination_url'],
            'redirect_type' => isset($data['redirect_type']) ? $data['redirect_type'] : '301',
            'status' => isset($data['status']) ? $data['status'] : 'active',
            'match_type' => isset($data['match_type']) ? $data['match_type'] : 'exact',
            'category' => isset($data['category']) && !empty($data['category']) ? $data['category'] : null,
            'scheduled_activation' => isset($data['scheduled_activation']) && !empty($data['scheduled_activation']) ? $data['scheduled_activation'] : null,
            'scheduled_deactivation' => isset($data['scheduled_deactivation']) && !empty($data['scheduled_deactivation']) ? $data['scheduled_deactivation'] : null,
            'created_by' => get_current_user_id()
        );
        
        $result = $wpdb->insert($table, $insert_data);
        
        if ($result === false) {
            self::$last_error = $wpdb->last_error;
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    public static function check_duplicate_sources($source_urls, $exclude_id = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'lhcfwp_redirects';
        
        $existing_redirects = $wpdb->get_results("SELECT id, source_urls FROM $table", ARRAY_A);
        $duplicates = array();
        
        foreach ($existing_redirects as $redirect) {
            if ($exclude_id && $redirect['id'] == $exclude_id) {
                continue;
            }
            
            $existing_sources = json_decode($redirect['source_urls'], true);
            if (!is_array($existing_sources)) {
                continue;
            }
            
            foreach ($source_urls as $new_source) {
                foreach ($existing_sources as $existing_source) {
                    if (rtrim($new_source, '/') === rtrim($existing_source, '/')) {
                        $duplicates[] = $new_source;
                    }
                }
            }
        }
        
        return array_unique($duplicates);
    }
    
    private static function ensure_redirects_table_schema() {
        global $wpdb;
        $table = $wpdb->prefix . 'lhcfwp_redirects';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
        
        if (!$table_exists) {
            // Table doesn't exist, create it
            self::create_redirects_table();
            return;
        }
        
        // Table exists, check if it has the source_urls column
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table", ARRAY_A);
        $has_source_urls = false;
        
        foreach ($columns as $column) {
            if ($column['Field'] === 'source_urls') {
                $has_source_urls = true;
                break;
            }
        }
        
        // If source_urls column doesn't exist, drop and recreate the table
        if (!$has_source_urls) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
            self::create_redirects_table();
        }
    }
    
    private static function create_redirects_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $table_redirects = $wpdb->prefix . 'lhcfwp_redirects';
        $sql_redirects = "CREATE TABLE $table_redirects (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            source_urls longtext NOT NULL,
            destination_url text NOT NULL,
            redirect_type varchar(10) NOT NULL DEFAULT '301',
            status varchar(20) NOT NULL DEFAULT 'active',
            match_type varchar(20) NOT NULL DEFAULT 'exact',
            scheduled_activation datetime NULL,
            scheduled_deactivation datetime NULL,
            category varchar(100),
            created_by bigint(20) UNSIGNED NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY status (status),
            KEY match_type (match_type)
        ) $charset_collate;";
        dbDelta($sql_redirects);
    }
    
    private static $last_error = '';
    
    public static function get_last_error() {
        return self::$last_error;
    }
    
    public static function update_redirect($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'lhcfwp_redirects';
        
        // Ensure table exists with correct schema
        self::ensure_redirects_table_schema();
        
        $update_data = array();
        
        if (isset($data['source_urls'])) {
            // Check for duplicates excluding this redirect
            if (is_array($data['source_urls'])) {
                $duplicates = self::check_duplicate_sources($data['source_urls'], $id);
                if (!empty($duplicates)) {
                    self::$last_error = 'Source URL(s) already exist: ' . implode(', ', $duplicates);
                    return false;
                }
                $update_data['source_urls'] = json_encode($data['source_urls']);
            }
        }
        if (isset($data['destination_url'])) {
            $update_data['destination_url'] = $data['destination_url'];
        }
        if (isset($data['redirect_type'])) {
            $update_data['redirect_type'] = $data['redirect_type'];
        }
        if (isset($data['status'])) {
            $update_data['status'] = $data['status'];
        }
        if (isset($data['match_type'])) {
            $update_data['match_type'] = $data['match_type'];
        }
        if (isset($data['category'])) {
            $update_data['category'] = $data['category'];
        }
        if (isset($data['scheduled_activation'])) {
            $update_data['scheduled_activation'] = $data['scheduled_activation'];
        }
        if (isset($data['scheduled_deactivation'])) {
            $update_data['scheduled_deactivation'] = $data['scheduled_deactivation'];
        }
        
        return $wpdb->update($table, $update_data, array('id' => $id));
    }
    
    public static function get_redirects($page = 1, $per_page = 20, $filters = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'lhcfwp_redirects';
        
        // Ensure table exists with correct schema
        self::ensure_redirects_table_schema();
        
        $offset = ($page - 1) * $per_page;
        
        $where = array("1=1");
        $where_values = array();
        
        if (!empty($filters['status'])) {
            $where[] = "status = %s";
            $where_values[] = $filters['status'];
        }
        
        if (!empty($filters['category'])) {
            $where[] = "category = %s";
            $where_values[] = $filters['category'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = "(source_urls LIKE %s OR destination_url LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($filters['search']) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        $where_sql = implode(' AND ', $where);
        
        $query = "SELECT * FROM $table WHERE $where_sql ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $where_values[] = $per_page;
        $where_values[] = $offset;
        
        $results = $wpdb->get_results($wpdb->prepare($query, $where_values), ARRAY_A);
        
        $count_query = "SELECT COUNT(*) FROM $table WHERE $where_sql";
        $total = $wpdb->get_var(
            empty($where_values) || (count($where_values) == 2) ? 
            $count_query : 
            $wpdb->prepare($count_query, array_slice($where_values, 0, count($where_values) - 2))
        );
        
        return array(
            'redirects' => $results,
            'total' => (int) $total,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total / $per_page)
        );
    }
    
    public static function get_redirect($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'lhcfwp_redirects';
        
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id), ARRAY_A);
    }
    
    public static function delete_redirect($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'lhcfwp_redirects';
        
        return $wpdb->delete($table, array('id' => $id));
    }
    
    public static function get_active_redirects() {
        global $wpdb;
        $table = $wpdb->prefix . 'lhcfwp_redirects';
        
        // Ensure table exists with correct schema
        self::ensure_redirects_table_schema();
        
        $current_time = current_time('mysql');
        
        $query = "SELECT * FROM $table 
                  WHERE status = 'active'
                  AND (scheduled_activation IS NULL OR scheduled_activation <= %s)
                  AND (scheduled_deactivation IS NULL OR scheduled_deactivation > %s)
                  ORDER BY id ASC";
        
        $results = $wpdb->get_results($wpdb->prepare($query, $current_time, $current_time), ARRAY_A);
        
        // Expand source_urls for redirect matching
        $expanded = array();
        foreach ($results as $redirect) {
            $source_urls = json_decode($redirect['source_urls'], true);
            if (!is_array($source_urls)) {
                $source_urls = array($redirect['source_urls']);
            }
            
            foreach ($source_urls as $source_url) {
                $expanded[] = array(
                    'id' => $redirect['id'],
                    'source_url' => $source_url,
                    'destination_url' => $redirect['destination_url'],
                    'redirect_type' => $redirect['redirect_type'],
                    'match_type' => $redirect['match_type']
                );
            }
        }
        
        return $expanded;
    }
    
    public static function delete_redirects($ids) {
        global $wpdb;
        $table = $wpdb->prefix . 'lhcfwp_redirects';
        
        if (empty($ids) || !is_array($ids)) {
            return false;
        }
        
        $ids = array_map('intval', $ids);
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        
        return $wpdb->query($wpdb->prepare("DELETE FROM $table WHERE id IN ($placeholders)", $ids));
    }
    
    public static function clear_all_redirects() {
        global $wpdb;
        $table = $wpdb->prefix . 'lhcfwp_redirects';
        
        return $wpdb->query("TRUNCATE TABLE $table");
    }
}