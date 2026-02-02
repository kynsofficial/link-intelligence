<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * INTELLIGENCE SCANNER V2 - SIMPLE & VERIFIABLE
 * 
 * Strategy: Store raw link data, aggregate on retrieval
 * 
 * Database structure per row:
 * - For each SOURCE post that contains links, we store ONE row per UNIQUE (target_url, anchor_text) combination
 * - metric_type: 'link_occurrence'
 * - metric_key: target URL or domain
 * - metric_value: count of this anchor in this source post
 * - post_id: SOURCE post ID
 * - post_title: SOURCE post title  
 * - additional_data: {
 *     'anchor_text': the actual anchor text,
 *     'is_internal': true/false,
 *     'target_post_id': ID of target post (for internal links),
 *     'target_post_title': title of target post
 *   }
 */
class LHCFWP_Intelligence_Scanner extends LHCFWP_Scanner {
    
    public function __construct() {
        parent::__construct('intelligence');
    }
    
    public function start_scan($config) {
        global $wpdb;
        $table = $wpdb->prefix . 'lhcfwp_intelligence';
        
        // Delete ALL previous intelligence data (not just incomplete scans)
        // Intelligence is a snapshot - old data has no value and causes bloat
        $wpdb->query($wpdb->prepare(
            "DELETE FROM `{$table}` WHERE scan_id IN (
                SELECT id FROM {$wpdb->prefix}lhcfwp_scans 
                WHERE scan_type = %s
            )",
            'intelligence'
        ));
        
        return parent::start_scan($config);
    }
    
    protected function process_post($post, $config) {
        global $wpdb;
        $table = $wpdb->prefix . 'lhcfwp_intelligence';
        
        $log = array();
        $log[] = "Scanning: {$post->post_title} (ID: {$post->ID})";
        
        // Extract all links from this post
        $content = $post->post_content;
        $base_url = get_site_url();
        $links = $this->extract_links($content, $base_url);
        
        if (empty($links)) {
            $log[] = "No links found";
            return array(
                'log' => $log,
                'issues_found' => 0,
                'post_title' => $post->post_title
            );
        }
        
        $log[] = "Found " . count($links) . " link(s)";
        
        // Count occurrences: target_url => anchor_text => count
        $link_data = array();
        
        foreach ($links as $link) {
            $url = $link['url'];
            $anchor = trim($link['anchor_text']);
            
            if (empty($anchor)) {
                continue;
            }
            
            // Normalize URL (remove fragments, trailing slashes)
            $url = preg_replace('/#.*$/', '', $url);
            $url = rtrim($url, '/');
            
            if (!isset($link_data[$url])) {
                $link_data[$url] = array();
            }
            
            if (!isset($link_data[$url][$anchor])) {
                $link_data[$url][$anchor] = 0;
            }
            
            $link_data[$url][$anchor]++;
        }
        
        // Store each unique (URL, anchor) combination for this source post
        $stored_count = 0;
        foreach ($link_data as $target_url => $anchors) {
            $is_internal = $this->is_internal_url($target_url);
            $target_post_id = null;
            $target_post_title = null;
            
            if ($is_internal) {
                $target_post_id = url_to_postid($target_url);
                if ($target_post_id) {
                    $target_post_title = get_the_title($target_post_id);
                }
            }
            
            foreach ($anchors as $anchor_text => $count) {
                $wpdb->insert($table, array(
                    'scan_id' => $this->scan_id,
                    'metric_type' => 'link_occurrence',
                    'metric_key' => $target_url,
                    'metric_value' => $count,
                    'post_id' => $post->ID,  // SOURCE post ID
                    'post_title' => $post->post_title,  // SOURCE post title
                    'additional_data' => json_encode(array(
                        'anchor_text' => $anchor_text,
                        'is_internal' => $is_internal,
                        'target_post_id' => $target_post_id,
                        'target_post_title' => $target_post_title
                    ))
                ));
                $stored_count++;
            }
        }
        
        $log[] = "Stored {$stored_count} link occurrence(s)";
        
        return array(
            'log' => $log,
            'issues_found' => 0,
            'post_title' => $post->post_title
        );
    }
    
    protected function complete_scan($state) {
        // Aggregate the raw data into metrics
        $this->aggregate_most_linked_internal();
        $this->aggregate_common_anchors();
        $this->aggregate_external_domains();
        $this->identify_zero_inbound_pages($state);
        
        return parent::complete_scan($state);
    }
    
    /**
     * Aggregate: Which internal pages are linked to the most?
     */
    private function aggregate_most_linked_internal() {
        global $wpdb;
        $table = $wpdb->prefix . 'lhcfwp_intelligence';
        
        // Get all internal link occurrences
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT metric_key as target_url, post_id as source_post_id, post_title as source_post_title, 
                    metric_value as count, additional_data
             FROM `{$table}`
             WHERE scan_id = %d AND metric_type = 'link_occurrence'",
            $this->scan_id
        ), ARRAY_A);
        
        // Group by target URL
        $targets = array();
        
        foreach ($results as $row) {
            $data = json_decode($row['additional_data'], true);
            
            // Skip external links
            if (empty($data['is_internal'])) {
                continue;
            }
            
            $target_url = $row['target_url'];
            $source_post_id = $row['source_post_id'];
            $anchor = $data['anchor_text'];
            $count = (int)$row['count'];
            
            if (!isset($targets[$target_url])) {
                $targets[$target_url] = array(
                    'total_links' => 0,
                    'linking_posts' => array(),
                    'post_anchors' => array(),
                    'anchor_texts' => array(),
                    'target_post_id' => $data['target_post_id'],
                    'target_post_title' => $data['target_post_title']
                );
            }
            
            $targets[$target_url]['total_links'] += $count;
            
            if (!in_array($source_post_id, $targets[$target_url]['linking_posts'])) {
                $targets[$target_url]['linking_posts'][] = $source_post_id;
            }
            
            if (!isset($targets[$target_url]['post_anchors'][$source_post_id])) {
                $targets[$target_url]['post_anchors'][$source_post_id] = array();
            }
            $targets[$target_url]['post_anchors'][$source_post_id][$anchor] = $count;
            
            if (!isset($targets[$target_url]['anchor_texts'][$anchor])) {
                $targets[$target_url]['anchor_texts'][$anchor] = 0;
            }
            $targets[$target_url]['anchor_texts'][$anchor] += $count;
        }
        
        // Store aggregated data
        foreach ($targets as $target_url => $data) {
            $wpdb->insert($table, array(
                'scan_id' => $this->scan_id,
                'metric_type' => 'most_linked_internal',
                'metric_key' => $target_url,
                'metric_value' => $data['total_links'],
                'post_id' => $data['target_post_id'],
                'post_title' => $data['target_post_title'] ?: $target_url,
                'additional_data' => json_encode(array(
                    'linking_posts' => $data['linking_posts'],
                    'post_anchors' => $data['post_anchors'],
                    'anchor_texts' => $data['anchor_texts']
                ))
            ));
        }
    }
    
    /**
     * Aggregate: Which anchor texts are used most frequently?
     */
    private function aggregate_common_anchors() {
        global $wpdb;
        $table = $wpdb->prefix . 'lhcfwp_intelligence';
        
        // Get all link occurrences
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT post_id as source_post_id, metric_value as count, additional_data
             FROM `{$table}`
             WHERE scan_id = %d AND metric_type = 'link_occurrence'",
            $this->scan_id
        ), ARRAY_A);
        
        // Count anchor text usage
        $anchors = array();
        
        foreach ($results as $row) {
            $data = json_decode($row['additional_data'], true);
            $anchor = $data['anchor_text'];
            $source_post_id = $row['source_post_id'];
            $count = (int)$row['count'];
            
            if (!isset($anchors[$anchor])) {
                $anchors[$anchor] = array(
                    'total_count' => 0,
                    'linking_posts' => array()
                );
            }
            
            $anchors[$anchor]['total_count'] += $count;
            
            if (!in_array($source_post_id, $anchors[$anchor]['linking_posts'])) {
                $anchors[$anchor]['linking_posts'][] = $source_post_id;
            }
        }
        
        // Store anchors that appear 5+ times
        foreach ($anchors as $anchor => $data) {
            if ($data['total_count'] >= 5) {
                $wpdb->insert($table, array(
                    'scan_id' => $this->scan_id,
                    'metric_type' => 'anchor_text',
                    'metric_key' => $anchor,
                    'metric_value' => $data['total_count'],
                    'post_id' => null,
                    'post_title' => null,
                    'additional_data' => json_encode(array(
                        'linking_posts' => $data['linking_posts']
                    ))
                ));
            }
        }
    }
    
    /**
     * Aggregate: Which external domains are linked to the most?
     */
    private function aggregate_external_domains() {
        global $wpdb;
        $table = $wpdb->prefix . 'lhcfwp_intelligence';
        
        // Get all external link occurrences
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT metric_key as target_url, post_id as source_post_id, 
                    metric_value as count, additional_data
             FROM `{$table}`
             WHERE scan_id = %d AND metric_type = 'link_occurrence'",
            $this->scan_id
        ), ARRAY_A);
        
        // Group by domain
        $domains = array();
        
        foreach ($results as $row) {
            $data = json_decode($row['additional_data'], true);
            
            // Skip internal links
            if (!empty($data['is_internal'])) {
                continue;
            }
            
            $domain = wp_parse_url($row['target_url'], PHP_URL_HOST);
            if (!$domain) {
                continue;
            }
            
            $source_post_id = $row['source_post_id'];
            $anchor = $data['anchor_text'];
            $count = (int)$row['count'];
            
            if (!isset($domains[$domain])) {
                $domains[$domain] = array(
                    'total_links' => 0,
                    'linking_posts' => array(),
                    'post_anchors' => array(),
                    'anchor_texts' => array()
                );
            }
            
            $domains[$domain]['total_links'] += $count;
            
            if (!in_array($source_post_id, $domains[$domain]['linking_posts'])) {
                $domains[$domain]['linking_posts'][] = $source_post_id;
            }
            
            if (!isset($domains[$domain]['post_anchors'][$source_post_id])) {
                $domains[$domain]['post_anchors'][$source_post_id] = array();
            }
            if (!isset($domains[$domain]['post_anchors'][$source_post_id][$anchor])) {
                $domains[$domain]['post_anchors'][$source_post_id][$anchor] = 0;
            }
            $domains[$domain]['post_anchors'][$source_post_id][$anchor] += $count;
            
            if (!isset($domains[$domain]['anchor_texts'][$anchor])) {
                $domains[$domain]['anchor_texts'][$anchor] = 0;
            }
            $domains[$domain]['anchor_texts'][$anchor] += $count;
        }
        
        // Store aggregated data
        foreach ($domains as $domain => $data) {
            $wpdb->insert($table, array(
                'scan_id' => $this->scan_id,
                'metric_type' => 'external_domain',
                'metric_key' => $domain,
                'metric_value' => $data['total_links'],
                'post_id' => null,
                'post_title' => null,
                'additional_data' => json_encode(array(
                    'linking_posts' => $data['linking_posts'],
                    'post_anchors' => $data['post_anchors'],
                    'anchor_texts' => $data['anchor_texts']
                ))
            ));
        }
    }
    
    /**
     * Identify pages with zero inbound internal links
     */
    private function identify_zero_inbound_pages($state) {
        global $wpdb;
        $table = $wpdb->prefix . 'lhcfwp_intelligence';
        
        // Get all target post IDs that have inbound links
        $linked_posts = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT post_id FROM `{$table}` 
             WHERE scan_id = %d AND metric_type = 'most_linked_internal' AND post_id IS NOT NULL",
            $this->scan_id
        ));
        
        // Get all posts that were scanned
        $all_post_ids = isset($state['posts']) ? $state['posts'] : array();
        
        // Find orphans
        foreach ($all_post_ids as $post_id) {
            if (!in_array($post_id, $linked_posts)) {
                $post = get_post($post_id);
                if ($post) {
                    $wpdb->insert($table, array(
                        'scan_id' => $this->scan_id,
                        'metric_type' => 'zero_inbound',
                        'metric_key' => get_permalink($post_id),
                        'metric_value' => 0,
                        'post_id' => $post_id,
                        'post_title' => $post->post_title,
                        'additional_data' => json_encode(array(
                            'post_type' => $post->post_type,
                            'post_date' => $post->post_date
                        ))
                    ));
                }
            }
        }
    }
}
