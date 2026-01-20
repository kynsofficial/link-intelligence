<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Internal Link Scanner
 * 
 * Processes ONE URL per AJAX call to prevent server overload.
 * Uses native WordPress checks first, HTTP only when needed for redirect detection.
 */
class LI_Internal_Link_Scanner extends LI_Scanner {
    
    public function __construct() {
        parent::__construct('internal_links');
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
        $scan_id = LI_Database::create_scan(array(
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
            'current_post_urls' => array(),    // URLs extracted from current post
            'current_url_index' => 0,
            'checked_urls' => array(),          // Already checked URLs (avoid duplicates)
            'unique_issue_urls' => array(),     // Track unique issue URLs for accurate counting
            'total_urls_checked' => 0,
            'issues_found' => 0,
            'started_at' => current_time('mysql'),
            'status' => 'running'
        );
        
        update_option('li_scan_state', $state, false);
        
        return array(
            'success' => true,
            'scan_id' => $scan_id,
            'state' => $state
        );
    }
    
    public function continue_scan() {
        $state = get_option('li_scan_state');
        
        if (!$state || $state['status'] !== 'running') {
            return array(
                'success' => false,
                'message' => 'No active scan'
            );
        }
        
        $this->scan_id = $state['scan_id'];
        
        // Check if we need to load a new post's URLs
        if (empty($state['current_post_urls']) || $state['current_url_index'] >= count($state['current_post_urls'])) {
            // Move to next post or finish
            $state = $this->load_next_post($state);
            
            if ($state['status'] === 'completed') {
                return $this->complete_scan($state);
            }
            
            // If still no URLs (post had no internal links), continue to next
            if (empty($state['current_post_urls'])) {
                update_option('li_scan_state', $state, false);
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
        
        update_option('li_scan_state', $state, false);
        
        // Update scan history periodically
        if ($state['total_urls_checked'] % 10 === 0) {
            LI_Database::update_scan($this->scan_id, array(
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
     * Load the next post and extract its internal URLs
     */
    protected function load_next_post($state) {
        // If we were processing a post, move to next
        if ($state['current_post_id'] !== null) {
            $state['current_post_index']++;
        }
        
        // Check if we've finished all posts
        if ($state['current_post_index'] >= $state['total_posts']) {
            $state['status'] = 'completed';
            return $state;
        }
        
        // Get next post
        $post_id = $state['posts'][$state['current_post_index']];
        $post = get_post($post_id);
        
        if (!$post) {
            // Skip invalid post, try next
            $state['current_post_index']++;
            return $this->load_next_post($state);
        }
        
        // Extract internal links from this post
        $base_url = get_site_url();
        $links = $this->extract_links($post->post_content, $base_url);
        
        // Filter to only internal links and remove already-checked URLs
        $internal_urls = array();
        foreach ($links as $link) {
            $url = $link['url'];
            
            if (!$this->is_internal_url($url)) {
                continue;
            }
            
            // Skip if already checked globally (same URL in different posts)
            if (isset($state['checked_urls'][$url])) {
                continue;
            }
            
            $internal_urls[] = $link;
        }
        
        // Update state
        $state['current_post_id'] = $post_id;
        $state['current_post_title'] = $post->post_title;
        $state['current_post_type'] = $post->post_type;
        $state['current_post_urls'] = $internal_urls;
        $state['current_url_index'] = 0;
        
        return $state;
    }
    
    /**
     * Process ONE URL and return result
     */
    protected function process_one_url($state) {
        $log = array();
        $issues_found = 0;
        
        $link = $state['current_post_urls'][$state['current_url_index']];
        $url = $link['url'];
        $anchor_text = $link['anchor_text'];
        
        // Mark URL as checked globally
        $state['checked_urls'][$url] = true;
        $state['current_url_index']++;
        $state['total_urls_checked']++;
        
        // Step 1: Native WordPress check (fast, no HTTP)
        $native_result = $this->check_url_native($url);
        
        $issue_found = false;
        $issue_type = null;
        $status_code = 200;
        $destination_url = null;
        $is_fixable = 0;
        
        if ($native_result['is_error']) {
            // Native check found 404
            $issue_found = true;
            $issue_type = $native_result['issue_type'];
            $status_code = 404;
            
            if ($issue_type === '404_trashed') {
                $log[] = "Problem found: Trashed post linked - {$url}";
            } elseif ($issue_type === '404_not_published') {
                $log[] = "Problem found: Unpublished post linked - {$url}";
            } else {
                $log[] = "Problem found: 404 Not Found (native check) - {$url}";
            }
            
        } elseif ($native_result['needs_http']) {
            // URL exists in WordPress - check for redirects via HTTP
            $log[] = "Scanning: Checking redirect status for {$url}";
            
            $http_result = $this->check_url($url, false);
            $status_code = $http_result['status_code'];
            
            if (in_array($status_code, array(301, 302, 307, 308))) {
                $issue_found = true;
                $issue_type = (string) $status_code;
                $destination_url = isset($http_result['redirect_url']) ? $http_result['redirect_url'] : null;
                $is_fixable = !empty($destination_url) ? 1 : 0;
                $log[] = "Found: {$status_code} Redirect detected → {$destination_url}";
                
            } elseif ($status_code === 404) {
                $issue_found = true;
                $issue_type = '404';
                $log[] = "Problem found: 404 Not Found (HTTP check) - {$url}";
                
            } elseif ($status_code === 410) {
                $issue_found = true;
                $issue_type = '410';
                $log[] = "Problem found: 410 Gone (permanently deleted) - {$url}";
                
            } elseif ($status_code >= 500) {
                $issue_found = true;
                $issue_type = 'server_error';
                $log[] = "Problem found: {$status_code} Server Error - {$url}";
                
            } elseif ($status_code === 0) {
                // Connection error - but we know the post exists
                // This might be a timeout, skip it rather than false positive
                $log[] = "Processing: Connection timeout (skipped) - {$url}";
            } else {
                // URL is OK
                $log[] = "Completed: ✓ Link verified OK - {$url}";
            }
        } else {
            // System URL, media file, or known OK URL - skipped
            $log[] = "Processing: System/Media URL (skipped) - {$url}";
        }
        
        // Save issue if found
        if ($issue_found) {
            // Always add to database (tracks all occurrences across posts)
            LI_Database::add_issue(array(
                'scan_id' => $this->scan_id,
                'scan_type' => $this->scan_type,
                'post_id' => $state['current_post_id'],
                'post_title' => $state['current_post_title'],
                'post_type' => $state['current_post_type'],
                'anchor_text' => $anchor_text,
                'current_url' => $url,
                'destination_url' => $destination_url,
                'redirect_type' => null,
                'status_code' => $status_code,
                'issue_type' => $issue_type,
                'is_fixable' => $is_fixable
            ));
            
            // Create unique identifier for this issue (current_url + destination_url combo)
            $issue_identifier = $url . '|' . ($destination_url ? $destination_url : '');
            
            // Only increment issues_found counter if this is a NEW unique issue
            if (!isset($state['unique_issue_urls'][$issue_identifier])) {
                $state['unique_issue_urls'][$issue_identifier] = true;
                $state['issues_found']++;
                $issues_found = 1;
            } else {
                // Duplicate issue - same problem in different post
                $log[] = "Processing: Duplicate issue (already counted) in another post";
            }
        }
        
        return array(
            'state' => $state,
            'log' => $log,
            'issues_found' => $issues_found
        );
    }
    
    /**
     * Native WordPress check - determines if HTTP is needed
     */
    protected function check_url_native($url) {
        $result = array(
            'is_error' => false,
            'issue_type' => null,
            'needs_http' => false
        );
        
        $path = wp_parse_url($url, PHP_URL_PATH);
        
        // Homepage always exists
        if (empty($path) || $path === '/') {
            $result['needs_http'] = true; // Check for redirect on homepage
            return $result;
        }
        
        // System URLs - skip
        if ($this->is_system_url($path)) {
            return $result;
        }
        
        // Media URLs - skip
        if ($this->is_media_url($path)) {
            return $result;
        }
        
        // Try to get post ID from URL
        $post_id = url_to_postid($url);
        
        if ($post_id > 0) {
            $post_status = get_post_status($post_id);
            
            if ($post_status === 'publish') {
                // Post exists and is published - need HTTP to check for redirects
                $result['needs_http'] = true;
                return $result;
                
            } elseif ($post_status === 'trash') {
                $result['is_error'] = true;
                $result['issue_type'] = '404_trashed';
                return $result;
                
            } elseif (in_array($post_status, array('draft', 'pending', 'private', 'future', 'auto-draft'))) {
                $result['is_error'] = true;
                $result['issue_type'] = '404_not_published';
                return $result;
                
            } elseif ($post_status === false) {
                $result['is_error'] = true;
                $result['issue_type'] = '404';
                return $result;
            }
            
            // Unknown status - check via HTTP
            $result['needs_http'] = true;
            return $result;
        }
        
        // URL doesn't resolve to a post
        // Check if it's a known WordPress URL type
        
        if ($this->is_likely_archive_url($path)) {
            $result['needs_http'] = true;
            return $result;
        }
        
        if ($this->check_taxonomy_exists($url)) {
            $result['needs_http'] = true;
            return $result;
        }
        
        if ($this->check_author_exists($url)) {
            $result['needs_http'] = true;
            return $result;
        }
        
        // Unknown URL - need HTTP to verify
        $result['needs_http'] = true;
        return $result;
    }
    
    protected function is_system_url($path) {
        $patterns = array('/wp-admin', '/wp-login', '/wp-content/', '/wp-includes/', '/wp-json/', '/feed/', '/xmlrpc.php');
        foreach ($patterns as $pattern) {
            if (strpos($path, $pattern) !== false) {
                return true;
            }
        }
        return false;
    }
    
    protected function is_media_url($path) {
        $extensions = array('jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip', 'mp3', 'mp4', 'css', 'js');
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return in_array($ext, $extensions);
    }
    
    protected function is_likely_archive_url($path) {
        if (preg_match('#^/\d{4}(/\d{2})?(/\d{2})?/?$#', $path)) return true;
        if (preg_match('#/page/\d+/?$#', $path)) return true;
        return false;
    }
    
    protected function check_taxonomy_exists($url) {
        $path = trim(wp_parse_url($url, PHP_URL_PATH), '/');
        $parts = explode('/', $path);
        if (empty($parts)) return false;
        
        $last_part = end($parts);
        if (get_category_by_slug($last_part)) return true;
        if (get_term_by('slug', $last_part, 'post_tag')) return true;
        
        return false;
    }
    
    protected function check_author_exists($url) {
        $path = trim(wp_parse_url($url, PHP_URL_PATH), '/');
        if (strpos($path, 'author/') === 0) {
            $author_slug = trim(str_replace('author/', '', $path), '/');
            if (get_user_by('slug', $author_slug)) return true;
        }
        return false;
    }
    
    protected function complete_scan($state) {
        $state['status'] = 'completed';
        $state['completed_at'] = current_time('mysql');
        update_option('li_scan_state', $state, false);
        
        LI_Database::update_scan($this->scan_id, array(
            'status' => 'completed',
            'completed_at' => current_time('mysql'),
            'issues_found' => $state['issues_found']
        ));
        
        return array(
            'success' => true,
            'continue' => false,
            'completed' => true,
            'state' => array(
                'current' => $state['total_posts'],
                'total' => $state['total_posts'],
                'issues_found' => $state['issues_found']
            ),
            'progress' => 100
        );
    }
    
    // Override parent - we handle process_post differently
    protected function process_post($post, $config) {
        // Not used - we process one URL at a time
        return array('log' => array(), 'issues_found' => 0);
    }
}