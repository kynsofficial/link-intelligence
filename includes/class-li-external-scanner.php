<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * External Link Scanner
 * 
 * Processes ONE URL per AJAX call for stability.
 * Checks external links for 404, 410, 5xx errors.
 */
class LHCFWP_External_Scanner extends LHCFWP_Scanner {
    
    public function __construct() {
        parent::__construct('external_errors');
    }
    
    public function start_scan($config) {
        // Get posts to scan (IDs only)
        $posts = $this->get_posts_to_scan($config);
        
        if (empty($posts)) {
            return array(
                'success' => false,
                'message' => 'No posts found to scan'
            );
        }
        
        $total_posts = count($posts);
        
        // Create scan history record
        $scan_id = LHCFWP_Database::create_scan(array(
            'scan_type' => $this->scan_type,
            'scan_config' => $config,
            'total_posts' => $total_posts,
            'issues_found' => 0
        ));
        
        $this->scan_id = $scan_id;
        
        // State structure for one-URL-at-a-time processing
        $state = array(
            'scan_id' => $scan_id,
            'scan_type' => $this->scan_type,
            'config' => $config,
            'posts' => $posts,
            'total_posts' => $total_posts,
            'current_post_index' => 0,
            'current_post_id' => null,
            'current_post_title' => '',
            'current_post_urls' => array(),
            'current_url_index' => 0,
            'url_check_cache' => array(),       // Cache check results to avoid re-checking same URLs
            'unique_issue_urls' => array(),     // Track unique issue URLs for accurate counting
            'total_urls_checked' => 0,
            'issues_found' => 0,
            'started_at' => current_time('mysql'),
            'status' => 'running'
        );
        
        update_option('lhcfwp_scan_state', $state, false);
        
        return array(
            'success' => true,
            'scan_id' => $scan_id,
            'state' => $state
        );
    }
    
    public function continue_scan() {
        $state = get_option('lhcfwp_scan_state');
        
        if (!$state || $state['status'] !== 'running') {
            return array(
                'success' => false,
                'message' => 'No active scan'
            );
        }
        
        $this->scan_id = $state['scan_id'];
        
        // Check if we need to load a new post's URLs
        if (empty($state['current_post_urls']) || $state['current_url_index'] >= count($state['current_post_urls'])) {
            $state = $this->load_next_post($state);
            
            if ($state['status'] === 'completed') {
                return $this->complete_scan($state);
            }
            
            // If still no URLs (post had no external links), continue to next
            if (empty($state['current_post_urls'])) {
                update_option('lhcfwp_scan_state', $state, false);
                return $this->continue_scan();
            }
        }
        
        // Process ONE URL
        $result = $this->process_one_url($state);
        $state = $result['state'];
        
        // Calculate progress
        $progress = 0;
        if ($state['total_posts'] > 0) {
            $post_progress = ($state['current_post_index'] / $state['total_posts']) * 100;
            $url_progress = 0;
            if (!empty($state['current_post_urls'])) {
                $url_progress = ($state['current_url_index'] / count($state['current_post_urls'])) * (100 / $state['total_posts']);
            }
            $progress = round($post_progress + $url_progress);
        }
        
        update_option('lhcfwp_scan_state', $state, false);
        
        // Update scan history periodically
        if ($state['total_urls_checked'] % 10 === 0) {
            LHCFWP_Database::update_scan($this->scan_id, array(
                'issues_found' => $state['issues_found']
            ));
        }
        
        return array(
            'success' => true,
            'continue' => true,
            'state' => array(
                'current' => $state['current_post_index'],
                'total' => $state['total_posts'],
                'issues_found' => $state['issues_found']
            ),
            'progress' => $progress,
            'current_post' => $state['current_post_title'],
            'log' => $result['log'],
            'issues_found' => $result['issues_found']
        );
    }
    
    /**
     * Load the next post and extract its external URLs
     */
    protected function load_next_post($state) {
        if ($state['current_post_id'] !== null) {
            $state['current_post_index']++;
        }
        
        if ($state['current_post_index'] >= $state['total_posts']) {
            $state['status'] = 'completed';
            return $state;
        }
        
        $post_id = $state['posts'][$state['current_post_index']];
        $post = get_post($post_id);
        
        if (!$post) {
            $state['current_post_index']++;
            return $this->load_next_post($state);
        }
        
        $base_url = get_site_url();
        $links = $this->extract_links($post->post_content, $base_url);
        
        // Filter to only external links (remove duplicates within same post only)
        $external_urls = array();
        $seen_in_post = array();
        foreach ($links as $link) {
            $url = $link['url'];
            
            // Only external links
            if ($this->is_internal_url($url)) {
                continue;
            }
            
            // Avoid duplicate entries within the SAME post
            if (isset($seen_in_post[$url])) {
                continue;
            }
            
            $seen_in_post[$url] = true;
            $external_urls[] = $link;
        }
        
        $state['current_post_id'] = $post_id;
        $state['current_post_title'] = $post->post_title;
        $state['current_post_type'] = $post->post_type;
        $state['current_post_urls'] = $external_urls;
        $state['current_url_index'] = 0;
        
        return $state;
    }
    
    /**
     * Process ONE external URL
     */
    protected function process_one_url($state) {
        $log = array();
        $issues_found = 0;
        
        $link = $state['current_post_urls'][$state['current_url_index']];
        $url = $link['url'];
        $anchor_text = $link['anchor_text'];
        
        $state['current_url_index']++;
        $state['total_urls_checked']++;
        
        // Check if we already checked this URL in this scan
        $use_cached_result = isset($state['url_check_cache'][$url]);
        
        if ($use_cached_result) {
            // Use cached result - no need to re-check
            $cached = $state['url_check_cache'][$url];
            $issue_found = $cached['issue_found'];
            $issue_type = $cached['issue_type'];
            $status_code = $cached['status_code'];
            
            $domain = wp_parse_url($url, PHP_URL_HOST);
            $log[] = "Processing: Using cached result for {$domain}";
            
        } else {
            // First time checking this URL - do actual check
            $issue_found = false;
            $issue_type = null;
            
            // Extract domain for log
            $domain = wp_parse_url($url, PHP_URL_HOST);
            $log[] = "Scanning: Checking external link at {$domain}...";
            
            // Check external URL via HTTP
            $http_result = $this->check_url($url, false);
            $status_code = $http_result['status_code'];
            
            if ($status_code === 404) {
                $issue_found = true;
                $issue_type = 'external_404';
                $log[] = "Problem found: 404 Not Found - {$url}";
                
            } elseif ($status_code === 410) {
                $issue_found = true;
                $issue_type = 'external_gone';
                $log[] = "Problem found: 410 Gone (permanently removed) - {$url}";
                
            } elseif ($status_code >= 500 && $status_code < 600) {
                $issue_found = true;
                $issue_type = 'external_server_error';
                $log[] = "Problem found: {$status_code} Server Error - {$url}";
                
            } elseif ($status_code === 0) {
                $issue_found = true;
                $issue_type = 'external_unreachable';
                $log[] = "Failed: Connection timeout or unreachable - {$url}";
                
            } elseif ($status_code >= 200 && $status_code < 300) {
                // Success
                $log[] = "Completed: ✓ External link OK ({$status_code}) - {$domain}";
                
            } elseif ($status_code >= 300 && $status_code < 400) {
                // Redirect (not necessarily a problem for external links)
                $log[] = "Completed: External redirect ({$status_code}) - {$domain}";
                
            } else {
                // Other status codes
                $log[] = "Processing: Received status {$status_code} from {$domain}";
            }
            
            // Cache the result for future occurrences of this URL
            $state['url_check_cache'][$url] = array(
                'issue_found' => $issue_found,
                'issue_type' => $issue_type,
                'status_code' => $status_code
            );
        }
        
        if ($issue_found) {
            // Add to database for THIS post
            LHCFWP_Database::add_issue(array(
                'scan_id' => $this->scan_id,
                'scan_type' => $this->scan_type,
                'post_id' => $state['current_post_id'],
                'post_title' => $state['current_post_title'],
                'post_type' => $state['current_post_type'],
                'anchor_text' => $anchor_text,
                'current_url' => $url,
                'destination_url' => null,
                'redirect_type' => null,
                'status_code' => $status_code,
                'issue_type' => $issue_type,
                'is_fixable' => 0
            ));
            
            // For external links, the issue identifier is just the URL (no destination)
            $issue_identifier = $url;
            
            // Only increment issues_found counter if this is a NEW unique issue
            if (!isset($state['unique_issue_urls'][$issue_identifier])) {
                $state['unique_issue_urls'][$issue_identifier] = true;
                $state['issues_found']++;
                $issues_found = 1;
            } else {
                // Duplicate issue - same broken URL in different post (but still added to database)
                $log[] = "Processing: Same issue found in another post (added to database)";
            }
        }
        
        return array(
            'state' => $state,
            'log' => $log,
            'issues_found' => $issues_found
        );
    }
    
    protected function complete_scan($state) {
        global $wpdb;
        $state['status'] = 'completed';
        $state['completed_at'] = current_time('mysql');
        update_option('lhcfwp_scan_state', $state, false);
        
        // Count ALL pending issues from database (not just unique ones)
        $issues_table = $wpdb->prefix . 'lhcfwp_issues';
        $actual_issues_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $issues_table WHERE scan_id = %d AND status = 'pending'",
            $this->scan_id
        ));
        
        LHCFWP_Database::update_scan($this->scan_id, array(
            'status' => 'completed',
            'completed_at' => current_time('mysql'),
            'issues_found' => $actual_issues_count
        ));
        
        return array(
            'success' => true,
            'continue' => false,
            'completed' => true,
            'state' => array(
                'current' => $state['total_posts'],
                'total' => $state['total_posts'],
                'issues_found' => $actual_issues_count
            ),
            'progress' => 100
        );
    }
    
    // Override parent - we handle process_post differently
    protected function process_post($post, $config) {
        return array('log' => array(), 'issues_found' => 0);
    }
}