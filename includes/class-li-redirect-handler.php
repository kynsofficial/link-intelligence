<?php
if (!defined('ABSPATH')) {
    exit;
}

class LHCFWP_Redirect_Handler {
    
    public static function init() {
        add_action('template_redirect', array(__CLASS__, 'handle_redirects'), 1);
    }
    
    public static function handle_redirects() {
        // Don't redirect admin pages
        if (is_admin()) {
            return;
        }
        
        // Get current URL
        $current_url = self::get_current_url();
        
        // Get active redirects
        $redirects = LHCFWP_Database::get_active_redirects();
        
        if (empty($redirects)) {
            return;
        }
        
        foreach ($redirects as $redirect) {
            $match = false;
            
            switch ($redirect['match_type']) {
                case 'exact':
                    $match = self::match_exact($current_url, $redirect['source_url']);
                    break;
                    
                case 'contains':
                    $match = self::match_contains($current_url, $redirect['source_url']);
                    break;
                    
                case 'starts_with':
                    $match = self::match_starts_with($current_url, $redirect['source_url']);
                    break;
                    
                case 'regex':
                    $match = self::match_regex($current_url, $redirect['source_url']);
                    break;
            }
            
            if ($match) {
                $redirect_type = $redirect['redirect_type'];
                $status_code = 301; // Default
                
                switch ($redirect_type) {
                    case '301':
                        $status_code = 301;
                        break;
                    case '302':
                        $status_code = 302;
                        break;
                    case '307':
                        $status_code = 307;
                        break;
                    case '308':
                        $status_code = 308;
                        break;
                }
                
                wp_redirect($redirect['destination_url'], $status_code);
                exit;
            }
        }
    }
    
    private static function get_current_url() {
        $protocol = is_ssl() ? 'https://' : 'http://';
        $host = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : '';
        $uri = isset($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : '';
        
        return $protocol . $host . $uri;
    }
    
    private static function match_exact($current_url, $source_url) {
        // Remove trailing slash for comparison
        $current = rtrim($current_url, '/');
        $source = rtrim($source_url, '/');
        
        return $current === $source;
    }
    
    private static function match_contains($current_url, $source_url) {
        return strpos($current_url, $source_url) !== false;
    }
    
    private static function match_starts_with($current_url, $source_url) {
        return strpos($current_url, $source_url) === 0;
    }
    
    private static function match_regex($current_url, $pattern) {
        return preg_match($pattern, $current_url);
    }
}
