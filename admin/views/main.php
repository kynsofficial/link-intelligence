<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap li-wrap">

    <!-- Main Header - Full Width at Top -->
    <div class="li-main-header">
        <h1>Link Intelligence</h1>
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
                <a href="#" class="li-nav-item" data-section="redirects">
                    <span class="dashicons dashicons-migrate"></span>
                    <span>Redirects</span>
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
                        <h2 class="li-card-title">Link Intelligence Analysis</h2>
                        <button class="li-btn li-btn-primary li-start-scan" data-scan-type="intelligence">
                            <span class="dashicons dashicons-controls-play"></span> Start Analysis
                        </button>
                    </div>
                    <div class="li-card-body">
                        <div class="li-intelligence-controls">
                            <label for="intelligence-filter" style="margin-right: 12px;">View:</label>
                            <select id="intelligence-filter" class="li-intelligence-filter">
                                <option value="most_linked_internal">Most Linked Pages</option>
                                <option value="anchor_text">Common Anchor Texts</option>
                                <option value="external_domain">External Domains</option>
                                <option value="zero_inbound">Pages with Zero Inbound Links</option>
                            </select>
                        </div>

                        <div class="li-table-wrapper">
                            <table class="li-table">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Count</th>
                                        <th>Details</th>
                                    </tr>
                                </thead>
                                <tbody class="li-intelligence-table">
                                    <tr>
                                        <td colspan="4" class="li-text-center">
                                            <div class="li-empty-state">
                                                <div class="li-empty-icon"><span class="dashicons dashicons-chart-bar"></span></div>
                                                <div class="li-empty-title">No data yet</div>
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
                        <button class="li-btn li-btn-danger li-clear-all-scans">
                            <span class="dashicons dashicons-trash"></span> Clear All History
                        </button>
                    </div>
                    <div class="li-card-body">
                        <!-- Bulk Actions Bar for Scan History (Hidden by default) -->
                        <div class="li-bulk-actions-bar-history li-hidden">
                            <div class="li-bulk-actions-left">
                                <input type="checkbox" class="li-bulk-select-all-history" title="Select All">
                                <span class="li-selected-count-history">0 selected</span>
                            </div>
                            <div class="li-bulk-actions-right">
                                <button class="li-btn li-btn-danger li-btn-sm li-delete-selected-scans">
                                    <span class="dashicons dashicons-trash"></span> Delete Selected
                                </button>
                            </div>
                        </div>

                        <div class="li-table-wrapper">
                            <table class="li-table">
                                <thead>
                                    <tr>
                                        <th style="width: 40px;" class="li-table-checkbox"><input type="checkbox" class="li-select-all-history-header"></th>
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

            <!-- Redirects Section -->
            <div id="li-section-redirects" class="li-section" style="display: none;">
                <div class="li-card">
                    <div class="li-card-header">
                        <h2 class="li-card-title">URL Redirects</h2>
                        <div style="display: flex; gap: 10px;">
                            <button class="li-btn li-btn-danger li-btn-sm li-delete-selected-redirects" style="display: none;">
                                <span class="dashicons dashicons-trash"></span> Delete Selected
                            </button>
                            <button class="li-btn li-btn-danger li-btn-sm li-clear-all-redirects">
                                <span class="dashicons dashicons-dismiss"></span> Clear All
                            </button>
                            <button class="li-btn li-btn-primary li-add-redirect-btn">
                                <span class="dashicons dashicons-plus"></span> Add New
                            </button>
                        </div>
                    </div>
                    <div class="li-card-body">
                        <div class="li-table-wrapper">
                            <table class="li-table">
                                <thead>
                                    <tr>
                                        <th style="width: 40px;">
                                            <input type="checkbox" id="li-select-all-redirects" />
                                        </th>
                                        <th data-sort="source_url">Source URL(s)</th>
                                        <th data-sort="destination_url">Destination URL</th>
                                        <th data-sort="redirect_type">Type</th>
                                        <th data-sort="status">Status</th>
                                        <th data-sort="category">Category</th>
                                        <th data-sort="created_at">Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="li-redirects-table">
                                    <tr>
                                        <td colspan="8" class="li-text-center">
                                            <div class="li-empty-state">
                                                <div class="li-empty-icon"><span class="dashicons dashicons-migrate"></span></div>
                                                <div class="li-empty-title">No redirects configured</div>
                                                <div class="li-empty-text">Add redirects to manage your site's URL structure</div>
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

    <!-- Redirect Modal -->
    <div id="li-redirect-modal" class="li-redirect-modal">
        <div class="li-redirect-modal-content">
            <div class="li-redirect-modal-header">
                <h3 class="li-redirect-modal-title">Add Redirect</h3>
            </div>
            <div class="li-redirect-modal-body">
                <div class="li-form-group">
                    <label class="li-form-label">Source URL(s)</label>
                    <div id="li-source-urls-container">
                        <div class="li-source-url-row" style="display: flex; gap: 8px; margin-bottom: 8px;">
                            <input type="text" class="li-source-url-input" placeholder="https://example.com/old-page" style="flex: 1;">
                            <button type="button" class="li-btn li-btn-secondary li-btn-sm li-remove-source-url" style="display: none;">
                                <span class="dashicons dashicons-no"></span>
                            </button>
                        </div>
                    </div>
                    <button type="button" class="li-btn li-btn-secondary li-btn-sm li-add-source-url" style="margin-top: 8px;">
                        <span class="dashicons dashicons-plus"></span> Add Another Source URL
                    </button>
                </div>

                <div class="li-form-group">
                    <label class="li-form-label">Destination URL</label>
                    <input type="text" id="li-redirect-destination" placeholder="https://example.com/new-page" style="width: 100%;">
                </div>

                <div class="li-form-group">
                    <label class="li-form-label">Redirect Type</label>
                    <div class="li-radio-group">
                        <div class="li-radio-item">
                            <input type="radio" name="redirect_type" value="301" id="redirect_type_301" checked>
                            <label for="redirect_type_301">301 Permanent</label>
                        </div>
                        <div class="li-radio-item">
                            <input type="radio" name="redirect_type" value="302" id="redirect_type_302">
                            <label for="redirect_type_302">302 Temporary</label>
                        </div>
                        <div class="li-radio-item">
                            <input type="radio" name="redirect_type" value="307" id="redirect_type_307">
                            <label for="redirect_type_307">307 Temporary</label>
                        </div>
                        <div class="li-radio-item">
                            <input type="radio" name="redirect_type" value="308" id="redirect_type_308">
                            <label for="redirect_type_308">308 Permanent</label>
                        </div>
                    </div>
                </div>

                <div class="li-form-group">
                    <label class="li-form-label">Status</label>
                    <div class="li-radio-group">
                        <div class="li-radio-item">
                            <input type="radio" name="redirect_status" value="active" id="redirect_status_active" checked>
                            <label for="redirect_status_active">Activate</label>
                        </div>
                        <div class="li-radio-item">
                            <input type="radio" name="redirect_status" value="inactive" id="redirect_status_inactive">
                            <label for="redirect_status_inactive">Deactivate</label>
                        </div>
                    </div>
                </div>

                <div class="li-form-group">
                    <label class="li-form-label">Category (Optional)</label>
                    <div id="li-category-checkboxes" class="li-category-checkboxes" style="display: none; margin-bottom: 10px;">
                        <!-- Existing categories as checkboxes will be populated here -->
                    </div>
                    <input type="text" id="li-redirect-category" placeholder="Or enter a new category">
                </div>
            </div>
            <div class="li-redirect-modal-footer">
                <button type="button" class="li-btn li-btn-secondary li-redirect-modal-cancel">Cancel</button>
                <button type="button" class="li-btn li-btn-primary li-redirect-modal-save">Save Redirect</button>
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
                            <a href="https://wordpress.org/support/plugin/link-diagnostics-and-insights/reviews/#new-post" target="_blank" style="text-decoration: none; color: #2271b1;">Please write us a 5-star review on WordPress</a>
                        </li>
                        <li style="margin-bottom: 8px;">
                            <a href="https://x.com/kynsofficial" target="_blank" style="text-decoration: none; color: #2271b1;">Follow me on Twitter for support or questions</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

    </div>