<?php
if (!defined('ABSPATH')) {
    exit;
}

abstract class LI_Scanner {
    
    protected $scan_type;
    protected $state;
    protected $scan_id;
    
    public function __construct($scan_type) {
        $this->scan_type = $scan_type;
    }
    
    public function start_scan($config) {
        // Get posts to scan (IDs only - lightweight)
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
        
        // Store state in wp_options (fast, cached by WordPress)
        $state = array(
            'scan_id' => $scan_id,
            'scan_type' => $this->scan_type,
            'config' => $config,
            'posts' => $posts,
            'total' => $total_posts,
            'current' => 0,
            'processed' => array(),
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
        
        if ($state['current'] >= $state['total']) {
            return $this->complete_scan($state);
        }
        
        // Get current post by ID (fast - direct lookup)
        $post_id = $state['posts'][$state['current']];
        $post = get_post($post_id);
        
        if (!$post) {
            // Skip invalid post
            $state['current']++;
            update_option('li_scan_state', $state, false);
            return $this->continue_scan();
        }
        
        // Process the post
        $result = $this->process_post($post, $state['config']);
        
        // Update state
        $state['current']++;
        $state['processed'][] = $post_id;
        
        if (!empty($result['issues_found'])) {
            $state['issues_found'] += $result['issues_found'];
        }
        
        $progress = round(($state['current'] / $state['total']) * 100);
        
        update_option('li_scan_state', $state, false);
        
        // Update scan history periodically (every 10 posts to reduce DB writes)
        if ($state['current'] % 10 === 0 || $state['current'] >= $state['total']) {
            LI_Database::update_scan($this->scan_id, array(
                'issues_found' => $state['issues_found']
            ));
        }
        
        return array(
            'success' => true,
            'continue' => true,
            'state' => $state,
            'progress' => $progress,
            'current_post' => isset($result['post_title']) ? $result['post_title'] : $post->post_title,
            'log' => $result['log'],
            'issues_found' => $result['issues_found']
        );
    }
    
    protected function complete_scan($state) {
        $state['status'] = 'completed';
        $state['completed_at'] = current_time('mysql');
        update_option('li_scan_state', $state, false);
        
        // Update scan history
        LI_Database::update_scan($this->scan_id, array(
            'status' => 'completed',
            'completed_at' => current_time('mysql'),
            'issues_found' => $state['issues_found']
        ));
        
        return array(
            'success' => true,
            'continue' => false,
            'completed' => true,
            'state' => $state,
            'progress' => 100
        );
    }
    
    public function cancel_scan() {
        $state = get_option('li_scan_state');
        
        if ($state && isset($state['scan_id'])) {
            LI_Database::update_scan($state['scan_id'], array(
                'status' => 'cancelled',
                'completed_at' => current_time('mysql')
            ));
        }
        
        delete_option('li_scan_state');
        
        return array(
            'success' => true,
            'message' => 'Scan cancelled'
        );
    }
    
    public function get_state() {
        return get_option('li_scan_state');
    }
    
    /**
     * Get all post IDs to scan (lightweight query)
     */
    protected function get_posts_to_scan($config) {
        $args = array(
            'post_type' => $config['content_type'],
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'orderby' => 'ID',
            'order' => 'ASC'
        );
        
        return get_posts($args);
    }
    
    protected function extract_links($content, $base_url = null) {
        if (empty($content)) {
            return array();
        }
        
        $links = array();
        $dom = new DOMDocument();
        
        // Suppress errors for malformed HTML
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        
        $anchor_tags = $dom->getElementsByTagName('a');
        
        foreach ($anchor_tags as $tag) {
            $href = $tag->getAttribute('href');
            $anchor_text = $tag->textContent;
            
            if (empty($href) || $href === '#') {
                continue;
            }
            
            // Skip mailto: and tel: links
            if (strpos($href, 'mailto:') === 0 || strpos($href, 'tel:') === 0) {
                continue;
            }
            
            // Skip javascript: links
            if (strpos($href, 'javascript:') === 0) {
                continue;
            }
            
            // Convert relative URLs to absolute
            if ($base_url && strpos($href, 'http') !== 0) {
                $href = $this->make_absolute_url($href, $base_url);
            }
            
            $links[] = array(
                'url' => $href,
                'anchor_text' => trim($anchor_text)
            );
        }
        
        return $links;
    }
    
    protected function make_absolute_url($relative_url, $base_url) {
        if (strpos($relative_url, '//') === 0) {
            return 'https:' . $relative_url;
        }
        
        if (strpos($relative_url, '/') === 0) {
            $parsed = wp_parse_url($base_url);
            return $parsed['scheme'] . '://' . $parsed['host'] . $relative_url;
        }
        
        return rtrim($base_url, '/') . '/' . ltrim($relative_url, '/');
    }
    
    protected function is_internal_url($url) {
        $site_url = get_site_url();
        $site_host = wp_parse_url($site_url, PHP_URL_HOST);
        $url_host = wp_parse_url($url, PHP_URL_HOST);
        
        return $url_host === $site_host;
    }
    
    /**
     * Check a single URL - simple and direct
     */
    protected function check_url($url, $follow_redirects = false) {
        $args = array(
            'timeout' => 10,
            'redirection' => $follow_redirects ? 5 : 0,
            'sslverify' => false,
            'headers' => array(
                'User-Agent' => 'Link Intelligence Scanner/1.0'
            )
        );
        
        $response = wp_remote_head($url, $args);
        
        if (is_wp_error($response)) {
            return array(
                'status_code' => 0,
                'error' => $response->get_error_message()
            );
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $headers = wp_remote_retrieve_headers($response);
        
        $result = array(
            'status_code' => $status_code,
            'headers' => $headers
        );
        
        // Check for redirect
        if (in_array($status_code, array(301, 302, 307, 308))) {
            $location = wp_remote_retrieve_header($response, 'location');
            if ($location) {
                $result['redirect_url'] = $location;
            }
        }
        
        return $result;
    }
    
    abstract protected function process_post($post, $config);
}