<?php
if (!defined('ABSPATH')) {
    exit;
}

class LHCFWP_Fixer {
    
    public static function fix_link($issue_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'lhcfwp_issues';
        
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
        LHCFWP_Database::log_fix($post->ID, $old_url, $new_url, $issue['anchor_text']);
        
        // Mark the issue as fixed instead of deleting it
        LHCFWP_Database::mark_issue_as_fixed($issue_id);
        
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
        $table = $wpdb->prefix . 'lhcfwp_issues';
        
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
        
        // Create lightweight state - track processed IDs instead of using offset
        $state = array(
            'scan_type' => $scan_type,
            'issue_ids' => $issue_ids, // Empty or specific IDs
            'processed_ids' => array(), // Track which IDs we've already processed
            'total' => (int)$total,
            'current' => 0,
            'fixed' => 0,
            'failed' => 0,
            'status' => 'running'
        );
        
        set_transient('lhcfwp_bulk_fix_state', $state, 3600);
        
        return array(
            'success' => true,
            'state' => $state,
            'total' => (int)$total
        );
    }
    
    public static function continue_bulk_fix() {
        $state = get_transient('lhcfwp_bulk_fix_state');
        
        if (!$state || $state['status'] !== 'running') {
            return array(
                'success' => false,
                'message' => 'No active bulk fix operation'
            );
        }
        
        if ($state['current'] >= $state['total']) {
            return self::complete_bulk_fix($state);
        }
        
        // Get the next issue to process, excluding already processed IDs
        $issue = self::get_next_fixable_issue($state['scan_type'], $state['issue_ids'], $state['processed_ids']);
        
        if (!$issue) {
            // No more issues found - complete
            return self::complete_bulk_fix($state);
        }
        
        // Fix the issue
        $result = self::fix_link($issue['id']);
        
        // Add to processed IDs to avoid reprocessing
        $state['processed_ids'][] = $issue['id'];
        
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
        set_transient('lhcfwp_bulk_fix_state', $state, 3600);
        
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
     * Get next fixable issue, excluding already processed IDs
     */
    private static function get_next_fixable_issue($scan_type, $issue_ids, $processed_ids) {
        global $wpdb;
        $table = $wpdb->prefix . 'lhcfwp_issues';
        
        if (!empty($issue_ids)) {
            // Get from specific issue IDs, excluding processed ones
            $available_ids = array_diff($issue_ids, $processed_ids);
            
            if (empty($available_ids)) {
                return null;
            }
            
            $placeholders = implode(',', array_fill(0, count($available_ids), '%d'));
            $issue = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table 
                 WHERE id IN ($placeholders) AND is_fixable = 1 AND status = 'pending'
                 ORDER BY id ASC
                 LIMIT 1",
                array_values($available_ids)
            ), ARRAY_A);
        } else {
            // Get from all fixable issues, excluding processed ones
            if (!empty($processed_ids)) {
                $exclude_placeholders = implode(',', array_fill(0, count($processed_ids), '%d'));
                $query_params = array_merge(array($scan_type), $processed_ids);
                $issue = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM $table 
                     WHERE scan_type = %s AND id NOT IN ($exclude_placeholders) AND is_fixable = 1 AND status = 'pending'
                     ORDER BY id ASC
                     LIMIT 1",
                    $query_params
                ), ARRAY_A);
            } else {
                $issue = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM $table 
                     WHERE scan_type = %s AND is_fixable = 1 AND status = 'pending'
                     ORDER BY id ASC
                     LIMIT 1",
                    $scan_type
                ), ARRAY_A);
            }
        }
        
        return $issue;
    }
    
    private static function complete_bulk_fix($state) {
        $state['status'] = 'completed';
        set_transient('lhcfwp_bulk_fix_state', $state, 3600);
        
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
        delete_transient('lhcfwp_bulk_fix_state');
        
        return array(
            'success' => true,
            'message' => 'Bulk fix cancelled'
        );
    }
    
    public static function get_bulk_fix_state() {
        return get_transient('lhcfwp_bulk_fix_state');
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