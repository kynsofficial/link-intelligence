<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap li-wrap">
    
    <!-- Main Header - Full Width at Top -->
    <div class="li-main-header">
        <h1>Link Health</h1>
        <p>Production-grade link analysis and intelligence engine</p>
    </div>

    <div class="li-container">
        <!-- Sidebar -->
        <div class="li-sidebar">
            <nav class="li-nav">
                <a href="#" class="li-nav-item active" data-section="internal-links">
                    <span class="dashicons dashicons-admin-links"></span>
                    <span>Internal Links</span>
                </a>
                <a href="#" class="li-nav-item" data-section="external-errors">
                    <span class="dashicons dashicons-admin-links"></span>
                    <span>External Errors</span>
                </a>
                <a href="#" class="li-nav-item" data-section="intelligence">
                    <span class="dashicons dashicons-chart-bar"></span>
                    <span>Intelligence</span>
                </a>
                <a href="#" class="li-nav-item" data-section="scan-history">
                    <span class="dashicons dashicons-backup"></span>
                    <span>Scan History</span>
                </a>
                <a href="#" class="li-nav-item" data-section="ignored">
                    <span class="dashicons dashicons-hidden"></span>
                    <span>Ignored Issues</span>
                </a>
                <a href="#" class="li-nav-item" data-section="settings">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <span>Settings</span>
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="li-main">
            
            <!-- Progress Container (Hidden by default) -->
            <div class="li-progress-container li-hidden">
                <div class="li-progress-header">
                    <h2 class="li-progress-title">Operation in Progress</h2>
                    <button class="li-btn li-btn-danger li-btn-sm li-cancel-operation">Cancel</button>
                </div>
                
                <div class="li-progress-info">
                    <span class="li-current-post">Initializing...</span>
                    <span class="li-progress-current">0 / 0</span>
                    <span class="li-progress-percentage">0%</span>
                </div>
                
                <div class="li-progress-bar-wrapper">
                    <div class="li-progress-bar" style="width: 0%;"></div>
                </div>
                
                <div class="li-execution-log-title">Execution Log</div>
                <div class="li-execution-log"></div>
            </div>

            <!-- Internal Links Section -->
            <div id="li-section-internal-links" class="li-section">
                <div class="li-card">
                    <div class="li-card-header">
                        <h2 class="li-card-title">Internal Link Issues</h2>
                        <button class="li-btn li-btn-primary li-start-scan" data-scan-type="internal_links">
                            <span class="dashicons dashicons-controls-play"></span> Start Scan
                        </button>
                    </div>
                    <div class="li-card-body">
                        <!-- Bulk Actions Bar (Hidden by default) -->
                        <div class="li-bulk-actions-bar li-hidden">
                            <div class="li-bulk-actions-left">
                                <input type="checkbox" class="li-bulk-select-all" title="Select All">
                                <span class="li-selected-count">0 selected</span>
                            </div>
                            <div class="li-bulk-actions-right">
                                <button class="li-btn li-btn-primary li-btn-sm li-bulk-fix-selected">
                                    <span class="dashicons dashicons-admin-tools"></span> Fix Selected
                                </button>
                                <button class="li-btn li-btn-primary li-btn-sm li-bulk-fix-all">
                                    <span class="dashicons dashicons-admin-tools"></span> Fix All Fixable
                                </button>
                            </div>
                        </div>
                        
                        <div class="li-table-wrapper">
                            <table class="li-table">
                                <thead>
                                    <tr>
                                        <th style="width: 40px;" class="li-table-checkbox"><input type="checkbox" class="li-select-all-header"></th>
                                        <th data-sort="post_title">Source</th>
                                        <th data-sort="post_type">Post Type</th>
                                        <th data-sort="anchor_text">Anchor Text</th>
                                        <th data-sort="current_url">Current URL</th>
                                        <th data-sort="destination_url">Destination</th>
                                        <th data-sort="status_code">Code</th>
                                        <th data-sort="issue_type">Type</th>
                                        <th data-sort="status">Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="li-issues-table">
                                    <tr>
                                        <td colspan="10" class="li-text-center">
                                            <div class="li-empty-state">
                                                <div class="li-empty-icon"><span class="dashicons dashicons-yes-alt"></span></div>
                                                <div class="li-empty-title">No issues found</div>
                                                <div class="li-empty-text">Run a scan to check for broken links and redirects</div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="li-pagination li-hidden"></div>
                    </div>
                </div>
            </div>

            <!-- External Errors Section -->
            <div id="li-section-external-errors" class="li-section" style="display: none;">
                <div class="li-card">
                    <div class="li-card-header">
                        <h2 class="li-card-title">External Links with Errors</h2>
                        <button class="li-btn li-btn-primary li-start-scan" data-scan-type="external_errors">
                            <span class="dashicons dashicons-controls-play"></span> Start Scan
                        </button>
                    </div>
                    <div class="li-card-body">
                        <div class="li-table-wrapper">
                            <table class="li-table">
                                <thead>
                                    <tr>
                                        <th data-sort="post_title">Source</th>
                                        <th data-sort="post_type">Post Type</th>
                                        <th data-sort="anchor_text">Anchor Text</th>
                                        <th data-sort="current_url">URL</th>
                                        <th data-sort="status_code">Code</th>
                                        <th data-sort="issue_type">Type</th>
                                        <th data-sort="status">Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="li-issues-table">
                                    <tr>
                                        <td colspan="8" class="li-text-center">
                                            <div class="li-empty-state">
                                                <div class="li-empty-icon"><span class="dashicons dashicons-yes-alt"></span></div>
                                                <div class="li-empty-title">No issues found</div>
                                                <div class="li-empty-text">Run a scan to check for external link errors</div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="li-pagination li-hidden"></div>
                    </div>
                </div>
            </div>

            <!-- Intelligence Section -->
            <div id="li-section-intelligence" class="li-section" style="display: none;">
                <div class="li-card">
                    <div class="li-card-header">
                        <h2 class="li-card-title">Link Intelligence</h2>
                        <button class="li-btn li-btn-primary li-start-scan" data-scan-type="intelligence">
                            <span class="dashicons dashicons-controls-play"></span> Start Analysis
                        </button>
                    </div>
                    <div class="li-card-body">
                        <div class="li-intelligence-tabs">
                            <button class="li-intelligence-tab active" data-metric="anchor_text">Most Used Anchor Text</button>
                            <button class="li-intelligence-tab" data-metric="most_linked_internal">Most Linked Internal Pages</button>
                            <button class="li-intelligence-tab" data-metric="external_domain">Most Linked External Domains</button>
                            <button class="li-intelligence-tab" data-metric="zero_inbound">Pages with Zero Inbound Links</button>
                        </div>
                        
                        <div class="li-table-wrapper">
                            <table class="li-table">
                                <thead class="li-intelligence-thead">
                                    <!-- Dynamic headers based on metric type -->
                                </thead>
                                <tbody class="li-intelligence-table">
                                    <tr>
                                        <td colspan="5" class="li-text-center">
                                            <div class="li-empty-state">
                                                <div class="li-empty-icon"><span class="dashicons dashicons-chart-bar"></span></div>
                                                <div class="li-empty-title">No analysis data</div>
                                                <div class="li-empty-text">Run an intelligence analysis to see insights</div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="li-pagination li-hidden"></div>
                    </div>
                </div>
            </div>

            <!-- Scan History Section -->
            <div id="li-section-scan-history" class="li-section" style="display: none;">
                <div class="li-card">
                    <div class="li-card-header">
                        <h2 class="li-card-title">Scan History</h2>
                        <button class="li-btn li-btn-danger li-delete-all-scans">
                            <span class="dashicons dashicons-trash"></span> Delete All History
                        </button>
                    </div>
                    <div class="li-card-body">
                        <div class="li-table-wrapper">
                            <table class="li-table">
                                <thead>
                                    <tr>
                                        <th data-sort="scan_type">Scan Type</th>
                                        <th data-sort="scan_config">Configuration</th>
                                        <th data-sort="total_posts">Total Scanned</th>
                                        <th data-sort="issues_found">Issues Found</th>
                                        <th data-sort="status">Status</th>
                                        <th data-sort="started_at">Started At</th>
                                        <th data-sort="completed_at">Completed At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="li-scan-history-table">
                                    <tr>
                                        <td colspan="9" class="li-text-center">
                                            <div class="li-empty-state">
                                                <div class="li-empty-icon"><span class="dashicons dashicons-backup"></span></div>
                                                <div class="li-empty-title">No scan history</div>
                                                <div class="li-empty-text">Run a scan to see history</div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="li-pagination li-hidden"></div>
                    </div>
                </div>
            </div>

            <!-- Ignored Issues Section -->
            <div id="li-section-ignored" class="li-section" style="display: none;">
                <div class="li-card">
                    <div class="li-card-header">
                        <h2 class="li-card-title">Ignored Issues</h2>
                    </div>
                    <div class="li-card-body">
                        <div class="li-table-wrapper">
                            <table class="li-table">
                                <thead>
                                    <tr>
                                        <th data-sort="post_title">Source</th>
                                        <th data-sort="post_type">Post Type</th>
                                        <th data-sort="current_url">URL</th>
                                        <th data-sort="issue_type">Type</th>
                                        <th data-sort="reason">Reason</th>
                                        <th data-sort="ignored_at">Ignored At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="li-ignored-table">
                                    <tr>
                                        <td colspan="7" class="li-text-center">
                                            <div class="li-empty-state">
                                                <div class="li-empty-icon"><span class="dashicons dashicons-hidden"></span></div>
                                                <div class="li-empty-title">No ignored issues</div>
                                                <div class="li-empty-text">Issues you ignore will appear here</div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="li-pagination li-hidden"></div>
                    </div>
                </div>
            </div>

            <!-- Settings Section -->
            <div id="li-section-settings" class="li-section" style="display: none;">
                <div class="li-card">
                    <div class="li-card-header">
                        <h2 class="li-card-title">Settings</h2>
                    </div>
                    <div class="li-card-body">
                        <form class="li-settings-form">
                            <div class="li-form-group">
                                <label class="li-form-label">
                                    <input type="checkbox" id="allow_multiple_content_types" <?php checked(isset($settings['allow_multiple_content_types']) ? $settings['allow_multiple_content_types'] : false); ?>>
                                    Allow multiple content types per scan
                                </label>
                                <p class="li-text-muted" style="margin-top: 8px; font-size: 13px;">
                                    <span class="dashicons dashicons-warning"></span>
                                    Warning: Enabling this may cause performance issues on shared hosting
                                </p>
                            </div>

                            <div class="li-form-group">
                                <label class="li-form-label">
                                    <input type="checkbox" id="delete_on_uninstall" <?php checked(isset($settings['delete_on_uninstall']) ? $settings['delete_on_uninstall'] : false); ?>>
                                    Delete all data on plugin uninstall
                                </label>
                                <p class="li-text-muted" style="margin-top: 8px; font-size: 13px;">
                                    This will permanently remove all scan data, issues, and settings when you uninstall the plugin
                                </p>
                            </div>

                            <div class="li-form-group">
                                <button type="button" class="li-btn li-btn-primary li-save-settings">Save Settings</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Scan Configuration Modal -->
    <div id="li-scan-modal" class="li-modal">
        <div class="li-modal-content">
            <div class="li-modal-header">
                <h3 class="li-modal-title">Configure Scan</h3>
            </div>
            <div class="li-modal-body">
                <div class="li-form-group">
                    <label class="li-form-label">Select Content Type(s)</label>
                    <div class="li-radio-group" id="content-type-group">
                        <?php foreach ($post_types as $type): ?>
                            <div class="li-radio-item">
                                <input type="<?php echo (isset($settings['allow_multiple_content_types']) && $settings['allow_multiple_content_types']) ? 'checkbox' : 'radio'; ?>" 
                                       name="content_type" 
                                       value="<?php echo esc_attr($type['name']); ?>" 
                                       id="content_type_<?php echo esc_attr($type['name']); ?>"
                                       class="content-type-input">
                                <label for="content_type_<?php echo esc_attr($type['name']); ?>"><?php echo esc_html($type['label']); ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="li-text-muted content-type-help" style="margin-top: 8px; font-size: 13px;">
                        <?php if (isset($settings['allow_multiple_content_types']) && $settings['allow_multiple_content_types']): ?>
                            Select one or more content types to scan.
                        <?php else: ?>
                            Select exactly one content type. Enable "Allow multiple content types per scan" in settings to select multiple.
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <div class="li-modal-footer">
                <button type="button" class="li-btn li-btn-secondary li-modal-cancel">Cancel</button>
                <button type="button" class="li-btn li-btn-primary li-modal-confirm">Start Scan</button>
            </div>
        </div>
    </div>

    <!-- Support This Plugin Section -->
    <div class="li-support-footer" style="margin-top: 40px; margin-bottom: 20px;">
        <div class="li-card">
            <div class="li-card-body" style="padding: 24px;">
                <h2 style="margin: 0 0 12px 0; font-size: 18px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                    <span style="color: #d63638;">❤️</span> Support This Plugin
                </h2>
                <p style="margin: 0 0 16px 0; color: #646970; font-size: 14px;">
                    Love this plugin 😍? I will appreciate your input in any or all of these:
                </p>
                <ul style="margin: 0; padding-left: 20px; list-style: disc; color: #2271b1;">
                    <li style="margin-bottom: 8px;">
                        <a href="https://buy.stripe.com/4gw4iJ3c676t1IQaEF" target="_blank" style="text-decoration: none; color: #2271b1;">Buy Me a Coffee</a>
                    </li>
                    <li style="margin-bottom: 8px;">
                        <a href="https://wordpress.org/support/plugin/link-intelligence/reviews/#new-post" target="_blank" style="text-decoration: none; color: #2271b1;">Please write us a 5-star review on WordPress</a>
                    </li>
                    <li style="margin-bottom: 8px;">
                        <a href="https://twitter.com/example" target="_blank" style="text-decoration: none; color: #2271b1;">Follow me on Twitter for support or questions</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

</div>