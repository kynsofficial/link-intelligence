<?php
if (!defined('ABSPATH')) {
    exit;
}

class LI_Fixer {
    
    public static function fix_link($issue_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'li_issues';
        
        $issue = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM `{$table}` WHERE id = %d",
            $issue_id
        ), ARRAY_A);
        
        if (!$issue) {
            return array(
                'success' => false,
                'message' => 'Issue not found',
                'post_title' => '',
                'old_url' => ''
            );
        }
        
        if (!$issue['is_fixable']) {
            return array(
                'success' => false,
                'message' => 'This issue cannot be fixed automatically',
                'post_title' => $issue['post_title'],
                'old_url' => $issue['current_url']
            );
        }
        
        if (empty($issue['destination_url'])) {
            return array(
                'success' => false,
                'message' => 'No destination URL available',
                'post_title' => $issue['post_title'],
                'old_url' => $issue['current_url']
            );
        }
        
        // Get the post
        $post = get_post($issue['post_id']);
        if (!$post) {
            return array(
                'success' => false,
                'message' => 'Post not found',
                'post_title' => $issue['post_title'],
                'old_url' => $issue['current_url']
            );
        }
        
        // Replace the URL in content
        $old_url = $issue['current_url'];
        $new_url = $issue['destination_url'];
        $content = $post->post_content;
        
        // Find and replace the specific anchor tag
        $updated_content = self::replace_url_in_content($content, $old_url, $new_url, $issue['anchor_text']);
        
        if ($updated_content === $content) {
            return array(
                'success' => false,
                'message' => 'URL not found in content or already fixed',
                'post_title' => $issue['post_title'],
                'old_url' => $issue['current_url']
            );
        }
        
        // Update the post
        $result = wp_update_post(array(
            'ID' => $post->ID,
            'post_content' => $updated_content
        ), true);
        
        if (is_wp_error($result)) {
            return array(
                'success' => false,
                'message' => $result->get_error_message(),
                'post_title' => $issue['post_title'],
                'old_url' => $issue['current_url']
            );
        }
        
        // Log the fix
        LI_Database::log_fix($post->ID, $old_url, $new_url, $issue['anchor_text']);
        
        // Mark the issue as fixed instead of deleting it
        LI_Database::mark_issue_as_fixed($issue_id);
        
        return array(
            'success' => true,
            'message' => 'Link fixed successfully',
            'edit_url' => get_edit_post_link($post->ID, 'raw'),
            'post_title' => $issue['post_title'],
            'old_url' => $old_url
        );
    }
    
    public static function start_bulk_fix($scan_type, $issue_ids = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'li_issues';
        
        // Count fixable issues (lightweight query)
        if (!empty($issue_ids)) {
            // Specific issue IDs provided
            $placeholders = implode(',', array_fill(0, count($issue_ids), '%d'));
            $total = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM `{$table}` 
                 WHERE id IN ($placeholders) AND is_fixable = 1 AND status = 'pending'",
                $issue_ids
            ));
        } else {
            // All fixable issues for this scan type
            $total = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM `{$table}` 
                 WHERE scan_type = %s AND is_fixable = 1 AND status = 'pending'",
                $scan_type
            ));
        }
        
        if ($total === 0) {
            return array(
                'success' => false,
                'message' => 'No fixable issues found'
            );
        }
        
        // Create lightweight state - NO issues array
        $state = array(
            'scan_type' => $scan_type,
            'issue_ids' => $issue_ids, // Empty or specific IDs
            'total' => (int)$total,
            'current' => 0,
            'fixed' => 0,
            'failed' => 0,
            'status' => 'running'
        );
        
        set_transient('li_bulk_fix_state', $state, 3600);
        
        return array(
            'success' => true,
            'state' => $state,
            'total' => (int)$total
        );
    }
    
    public static function continue_bulk_fix() {
        $state = get_transient('li_bulk_fix_state');
        
        if (!$state || $state['status'] !== 'running') {
            return array(
                'success' => false,
                'message' => 'No active bulk fix operation'
            );
        }
        
        if ($state['current'] >= $state['total']) {
            return self::complete_bulk_fix($state);
        }
        
        // Get ONLY the next issue to process (one at a time)
        $issue = self::get_next_fixable_issue($state['scan_type'], $state['issue_ids'], $state['current']);
        
        if (!$issue) {
            // Skip and move to next
            $state['current']++;
            set_transient('li_bulk_fix_state', $state, 3600);
            
            if ($state['current'] < $state['total']) {
                return self::continue_bulk_fix();
            } else {
                return self::complete_bulk_fix($state);
            }
        }
        
        // Fix the issue
        $result = self::fix_link($issue['id']);
        
        // Free memory
        unset($issue);
        
        $log_entry = '';
        if ($result['success']) {
            $state['fixed']++;
            $log_entry = "Fixed: {$result['post_title']} - {$result['old_url']}";
        } else {
            $state['failed']++;
            $log_entry = "Failed: {$result['post_title']} - {$result['message']}";
        }
        
        $state['current']++;
        
        $progress = round(($state['current'] / $state['total']) * 100);
        
        // Update state
        set_transient('li_bulk_fix_state', $state, 3600);
        
        return array(
            'success' => true,
            'continue' => true,
            'state' => $state,
            'progress' => $progress,
            'current_item' => isset($result['post_title']) ? $result['post_title'] : 'Processing...',
            'log' => array($log_entry),
            'fixed' => $state['fixed'],
            'failed' => $state['failed']
        );
    }
    
    /**
     * Get next fixable issue using offset (one at a time)
     */
    private static function get_next_fixable_issue($scan_type, $issue_ids, $offset) {
        global $wpdb;
        $table = $wpdb->prefix . 'li_issues';
        
        if (!empty($issue_ids)) {
            // Get from specific issue IDs
            $placeholders = implode(',', array_fill(0, count($issue_ids), '%d'));
            $issue = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table 
                 WHERE id IN ($placeholders) AND is_fixable = 1 AND status = 'pending'
                 ORDER BY id ASC
                 LIMIT 1 OFFSET %d",
                array_merge($issue_ids, array($offset))
            ), ARRAY_A);
        } else {
            // Get from all fixable issues
            $issue = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table 
                 WHERE scan_type = %s AND is_fixable = 1 AND status = 'pending'
                 ORDER BY id ASC
                 LIMIT 1 OFFSET %d",
                $scan_type,
                $offset
            ), ARRAY_A);
        }
        
        return $issue;
    }
    
    private static function complete_bulk_fix($state) {
        $state['status'] = 'completed';
        set_transient('li_bulk_fix_state', $state, 3600);
        
        return array(
            'success' => true,
            'continue' => false,
            'completed' => true,
            'state' => $state,
            'progress' => 100,
            'fixed' => $state['fixed'],
            'failed' => $state['failed']
        );
    }
    
    public static function cancel_bulk_fix() {
        delete_transient('li_bulk_fix_state');
        
        return array(
            'success' => true,
            'message' => 'Bulk fix cancelled'
        );
    }
    
    public static function get_bulk_fix_state() {
        return get_transient('li_bulk_fix_state');
    }
    
    private static function replace_url_in_content($content, $old_url, $new_url, $anchor_text) {
        if (empty($content)) {
            return $content;
        }
        
        // Use DOMDocument for precise replacement
        libxml_use_internal_errors(true);
        
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        
        $anchor_tags = $dom->getElementsByTagName('a');
        $replaced = false;
        
        foreach ($anchor_tags as $tag) {
            $href = $tag->getAttribute('href');
            $text = trim($tag->textContent);
            
            // Match both URL and anchor text to ensure we're replacing the right link
            if ($href === $old_url && $text === $anchor_text) {
                $tag->setAttribute('href', $new_url);
                $replaced = true;
                break; // Only replace first occurrence
            }
        }
        
        if (!$replaced) {
            // Free memory
            unset($dom);
            unset($anchor_tags);
            libxml_clear_errors();
            return $content;
        }
        
        // Get the modified HTML
        $updated = $dom->saveHTML();
        
        // Remove XML declaration
        $updated = preg_replace('/^<\?xml[^>]+\?>\s*/i', '', $updated);
        
        // Free memory
        unset($dom);
        unset($anchor_tags);
        libxml_clear_errors();
        
        return $updated;
    }
}