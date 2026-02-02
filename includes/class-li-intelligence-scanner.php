<?php
if (!defined('ABSPATH')) {
    exit;
}

class LHCFWP_Intelligence_Scanner extends LHCFWP_Scanner {
    
    public function __construct() {
        parent::__construct('intelligence');
    }
    
    public function start_scan($config) {
        // Clear any previous intelligence data for this scan type
        global $wpdb;
        $table = esc_sql($wpdb->prefix . 'lhcfwp_intelligence');
        
        // Delete old intelligence data from previous scans
        $wpdb->query($wpdb->prepare(
            "DELETE FROM `{$table}` WHERE scan_id IN (
                SELECT id FROM {$wpdb->prefix}li_scans 
                WHERE scan_type = %s 
                AND status IN ('running', 'cancelled')
            )",
            'intelligence'
        ));
        
        return parent::start_scan($config);
    }
    
    protected function process_post($post, $config) {
        global $wpdb;
        $table = $wpdb->prefix . 'lhcfwp_intelligence';
        
        $log = array();
        
        $log[] = "Analyzing: {$post->post_title}";
        
        $content = $post->post_content;
        $base_url = get_site_url();
        
        $links = $this->extract_links($content, $base_url);
        
        if (empty($links)) {
            $log[] = "No links found in this post";
            return array(
                'log' => $log, 
                'issues_found' => 0,
                'post_title' => $post->post_title
            );
        }
        
        $link_count = count($links);
        $log[] = "Found {$link_count} link(s) to analyze";
        
        // Track what we've seen in THIS post to avoid duplicate processing
        $seen_anchors = array();
        $seen_internal = array();
        $seen_external = array();
        
        foreach ($links as $link) {
            $url = $link['url'];
            $anchor = $link['anchor_text'];
            
            if (empty($anchor)) {
                continue;
            }
            
            // Process anchor text (only if count >= 5)
            if (!isset($seen_anchors[$anchor])) {
                $this->update_anchor_text($anchor, $post->ID);
                $seen_anchors[$anchor] = true;
            }
            
            if ($this->is_internal_url($url)) {
                // Process internal link
                if (!isset($seen_internal[$url])) {
                    $this->update_internal_link($url, $anchor, $post->ID);
                    $seen_internal[$url] = true;
                } else {
                    // Still update anchor text count for this URL
                    $this->increment_anchor_for_url($url, $anchor, 'most_linked_internal');
                }
            } else {
                // Process external domain
                $domain = wp_parse_url($url, PHP_URL_HOST);
                if ($domain) {
                    if (!isset($seen_external[$domain])) {
                        $this->update_external_domain($domain, $anchor, $post->ID);
                        $seen_external[$domain] = true;
                    } else {
                        // Still update anchor text count for this domain
                        $this->increment_anchor_for_url($domain, $anchor, 'external_domain');
                    }
                }
            }
        }
        
        $log[] = "Analysis complete for: {$post->post_title}";
        
        return array(
            'log' => $log,
            'issues_found' => 0,
            'post_title' => $post->post_title
        );
    }
    
    private function update_anchor_text($anchor, $post_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'lhcfwp_intelligence';
        
        // Check if exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT metric_value, additional_data FROM `{$table}` 
             WHERE scan_id = %d AND metric_type = 'anchor_text' AND metric_key = %s",
            $this->scan_id,
            $anchor
        ), ARRAY_A);
        
        if ($existing) {
            $new_count = (int)$existing['metric_value'] + 1;
            $additional = json_decode($existing['additional_data'], true);
            $linking_posts = isset($additional['linking_posts']) ? $additional['linking_posts'] : array();
            
            if (!in_array($post_id, $linking_posts)) {
                $linking_posts[] = $post_id;
            }
            
            // Only keep if count >= 5
            if ($new_count >= 5) {
                $wpdb->update(
                    $table,
                    array(
                        'metric_value' => $new_count,
                        'additional_data' => json_encode(array('linking_posts' => $linking_posts))
                    ),
                    array(
                        'scan_id' => $this->scan_id,
                        'metric_type' => 'anchor_text',
                        'metric_key' => $anchor
                    )
                );
            }
        } else {
            // Insert new (will only show if it reaches count >= 5)
            $wpdb->insert($table, array(
                'scan_id' => $this->scan_id,
                'metric_type' => 'anchor_text',
                'metric_key' => $anchor,
                'metric_value' => 1,
                'post_id' => null,
                'post_title' => null,
                'additional_data' => json_encode(array('linking_posts' => array($post_id)))
            ));
        }
    }
    
    private function update_internal_link($url, $anchor, $post_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'lhcfwp_intelligence';
        
        // Check if exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT metric_value, additional_data FROM `{$table}` 
             WHERE scan_id = %d AND metric_type = 'most_linked_internal' AND metric_key = %s",
            $this->scan_id,
            $url
        ), ARRAY_A);
        
        if ($existing) {
            $new_count = (int)$existing['metric_value'] + 1;
            $additional = json_decode($existing['additional_data'], true);
            $linking_posts = isset($additional['linking_posts']) ? $additional['linking_posts'] : array();
            $anchor_texts = isset($additional['anchor_texts']) ? $additional['anchor_texts'] : array();
            
            if (!in_array($post_id, $linking_posts)) {
                $linking_posts[] = $post_id;
            }
            
            if (!isset($anchor_texts[$anchor])) {
                $anchor_texts[$anchor] = 0;
            }
            $anchor_texts[$anchor]++;
            
            $wpdb->update(
                $table,
                array(
                    'metric_value' => $new_count,
                    'additional_data' => json_encode(array(
                        'linking_posts' => $linking_posts,
                        'anchor_texts' => $anchor_texts
                    ))
                ),
                array(
                    'scan_id' => $this->scan_id,
                    'metric_type' => 'most_linked_internal',
                    'metric_key' => $url
                )
            );
        } else {
            $target_post_id = url_to_postid($url);
            $post_title = $target_post_id ? get_the_title($target_post_id) : $url;
            
            $wpdb->insert($table, array(
                'scan_id' => $this->scan_id,
                'metric_type' => 'most_linked_internal',
                'metric_key' => $url,
                'metric_value' => 1,
                'post_id' => $target_post_id ?: null,
                'post_title' => $post_title,
                'additional_data' => json_encode(array(
                    'linking_posts' => array($post_id),
                    'anchor_texts' => array($anchor => 1)
                ))
            ));
        }
    }
    
    private function update_external_domain($domain, $anchor, $post_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'lhcfwp_intelligence';
        
        // Check if exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT metric_value, additional_data FROM $table 
             WHERE scan_id = %d AND metric_type = 'external_domain' AND metric_key = %s",
            $this->scan_id,
            $domain
        ), ARRAY_A);
        
        if ($existing) {
            $new_count = (int)$existing['metric_value'] + 1;
            $additional = json_decode($existing['additional_data'], true);
            $linking_posts = isset($additional['linking_posts']) ? $additional['linking_posts'] : array();
            $anchor_texts = isset($additional['anchor_texts']) ? $additional['anchor_texts'] : array();
            
            if (!in_array($post_id, $linking_posts)) {
                $linking_posts[] = $post_id;
            }
            
            if (!isset($anchor_texts[$anchor])) {
                $anchor_texts[$anchor] = 0;
            }
            $anchor_texts[$anchor]++;
            
            $wpdb->update(
                $table,
                array(
                    'metric_value' => $new_count,
                    'additional_data' => json_encode(array(
                        'linking_posts' => $linking_posts,
                        'anchor_texts' => $anchor_texts
                    ))
                ),
                array(
                    'scan_id' => $this->scan_id,
                    'metric_type' => 'external_domain',
                    'metric_key' => $domain
                )
            );
        } else {
            $wpdb->insert($table, array(
                'scan_id' => $this->scan_id,
                'metric_type' => 'external_domain',
                'metric_key' => $domain,
                'metric_value' => 1,
                'post_id' => null,
                'post_title' => null,
                'additional_data' => json_encode(array(
                    'linking_posts' => array($post_id),
                    'anchor_texts' => array($anchor => 1)
                ))
            ));
        }
    }
    
    private function increment_anchor_for_url($key, $anchor, $metric_type) {
        global $wpdb;
        $table = $wpdb->prefix . 'lhcfwp_intelligence';
        
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT additional_data FROM $table 
             WHERE scan_id = %d AND metric_type = %s AND metric_key = %s",
            $this->scan_id,
            $metric_type,
            $key
        ), ARRAY_A);
        
        if ($existing) {
            $additional = json_decode($existing['additional_data'], true);
            $anchor_texts = isset($additional['anchor_texts']) ? $additional['anchor_texts'] : array();
            
            if (!isset($anchor_texts[$anchor])) {
                $anchor_texts[$anchor] = 0;
            }
            $anchor_texts[$anchor]++;
            
            $additional['anchor_texts'] = $anchor_texts;
            
            $wpdb->update(
                $table,
                array('additional_data' => json_encode($additional)),
                array(
                    'scan_id' => $this->scan_id,
                    'metric_type' => $metric_type,
                    'metric_key' => $key
                )
            );
        }
    }
    
    protected function complete_scan($state) {
        // Identify pages with zero inbound links
        $this->identify_zero_inbound_pages($state);
        
        return parent::complete_scan($state);
    }
    
    private function identify_zero_inbound_pages($state) {
        global $wpdb;
        $table = $wpdb->prefix . 'lhcfwp_intelligence';
        
        // Get all internal link post IDs from intelligence data
        $linked_posts = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT post_id FROM $table 
             WHERE scan_id = %d AND metric_type = 'most_linked_internal' AND post_id IS NOT NULL",
            $this->scan_id
        ));
        
        // Get all posts that were scanned
        $all_post_ids = isset($state['posts']) ? $state['posts'] : array();
        
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