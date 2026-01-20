/**
 * Link Intelligence - Data Actions Module
 * Handles data loading, user actions, and events
 */

(function($) {
    'use strict';
    
    window.LI_Admin = window.LI_Admin || {};
    
    const DataActions = {
        currentPage: 1,
        currentScanType: null,
        currentMetricType: null,
        sortColumn: null,
        sortOrder: 'asc',
        selectedIssues: [],
        selectedScans: [],
        expandedRows: new Set(),
        postTitlesCache: {},
        
        init: function() {
            this.bindEvents();
        },
        
        bindEvents: function() {
            $(document).on('change', '.li-intelligence-filter', this.handleMetricChange.bind(this));
            $(document).on('click', '.li-pagination-btn', this.handlePagination.bind(this));
            $(document).on('click', '.li-table th[data-sort]', this.handleSort.bind(this));
            $(document).on('click', '.li-delete-scan', this.deleteScan.bind(this));
            $(document).on('click', '.li-clear-all-scans', this.clearAllScans.bind(this));
            $(document).on('change', '.li-issue-checkbox', this.handleIssueSelect.bind(this));
            $(document).on('change', '.li-select-all-header', this.handleSelectAll.bind(this));
            $(document).on('change', '.li-bulk-select-all', this.handleBulkSelectAll.bind(this));
            $(document).on('change', '.li-scan-checkbox', this.handleScanSelect.bind(this));
            $(document).on('change', '.li-select-all-history-header', this.handleSelectAllScans.bind(this));
            $(document).on('change', '.li-bulk-select-all-history', this.handleBulkSelectAllScans.bind(this));
            $(document).on('click', '.li-bulk-fix-selected', this.bulkFixSelected.bind(this));
            $(document).on('click', '.li-bulk-fix-all', this.bulkFixAll.bind(this));
            $(document).on('click', '.li-delete-selected-scans', this.deleteSelectedScans.bind(this));
            $(document).on('click', '.li-fix-link', this.fixLink.bind(this));
            $(document).on('click', '.li-ignore-link', this.ignoreLink.bind(this));
            $(document).on('click', '.li-unignore-link', this.unignoreLink.bind(this));
            $(document).on('click', '.li-expand-row', this.toggleRowExpansion.bind(this));
        },
        
        handleSort: function(e) {
            const $th = $(e.currentTarget);
            const column = $th.data('sort');
            
            // Toggle sort order if clicking the same column
            if (this.sortColumn === column) {
                this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
            } else {
                this.sortColumn = column;
                this.sortOrder = 'asc';
            }
            
            // Update visual indicators
            $('.li-table th[data-sort]').removeClass('sorted-asc sorted-desc');
            $th.addClass('sorted-' + this.sortOrder);
            
            // Reload data with sorting
            if (this.currentScanType) {
                this.loadIssues(this.currentScanType, 1);
            } else if (this.currentMetricType) {
                this.loadIntelligence(this.currentMetricType);
            }
        },
        
        handleIssueSelect: function(e) {
            const issueId = parseInt($(e.target).data('issue-id'));
            const isChecked = $(e.target).is(':checked');
            
            if (isChecked) {
                if (!this.selectedIssues.includes(issueId)) {
                    this.selectedIssues.push(issueId);
                }
            } else {
                this.selectedIssues = this.selectedIssues.filter(id => id !== issueId);
            }
            
            this.updateBulkActionsBar();
        },
        
        handleSelectAll: function(e) {
            const isChecked = $(e.target).is(':checked');
            $('.li-issue-checkbox').prop('checked', isChecked);
            
            if (isChecked) {
                this.selectedIssues = [];
                $('.li-issue-checkbox').each((i, el) => {
                    this.selectedIssues.push(parseInt($(el).data('issue-id')));
                });
            } else {
                this.selectedIssues = [];
            }
            
            this.updateBulkActionsBar();
        },
        
        handleBulkSelectAll: function(e) {
            const isChecked = $(e.target).is(':checked');
            $('.li-select-all-header').prop('checked', isChecked).trigger('change');
        },
        
        handleScanSelect: function(e) {
            const scanId = parseInt($(e.target).data('scan-id'));
            const isChecked = $(e.target).is(':checked');
            
            if (isChecked) {
                if (!this.selectedScans.includes(scanId)) {
                    this.selectedScans.push(scanId);
                }
            } else {
                this.selectedScans = this.selectedScans.filter(id => id !== scanId);
            }
            
            this.updateScanBulkActionsBar();
        },
        
        handleSelectAllScans: function(e) {
            const isChecked = $(e.target).is(':checked');
            $('.li-scan-checkbox').prop('checked', isChecked);
            
            if (isChecked) {
                this.selectedScans = [];
                $('.li-scan-checkbox').each((i, el) => {
                    this.selectedScans.push(parseInt($(el).data('scan-id')));
                });
            } else {
                this.selectedScans = [];
            }
            
            this.updateScanBulkActionsBar();
        },
        
        handleBulkSelectAllScans: function(e) {
            const isChecked = $(e.target).is(':checked');
            $('.li-select-all-history-header').prop('checked', isChecked).trigger('change');
        },
        
        updateBulkActionsBar: function() {
            const count = this.selectedIssues.length;
            $('.li-selected-count').text(`${count} selected`);
            
            if (count > 0) {
                $('.li-bulk-actions-bar').removeClass('li-hidden');
            } else {
                $('.li-bulk-actions-bar').addClass('li-hidden');
            }
        },
        
        updateScanBulkActionsBar: function() {
            const count = this.selectedScans.length;
            $('.li-selected-count-history').text(`${count} selected`);
            
            if (count > 0) {
                $('.li-bulk-actions-bar-history').removeClass('li-hidden');
            } else {
                $('.li-bulk-actions-bar-history').addClass('li-hidden');
            }
        },
        
        bulkFixSelected: function() {
            if (this.selectedIssues.length === 0) {
                LI_Admin.Core.showNotification('Please select issues to fix', 'error');
                return;
            }
            
            if (!confirm(`Fix ${this.selectedIssues.length} selected issue(s)?`)) {
                return;
            }
            
            LI_Admin.Scans.startBulkFix(this.currentScanType, this.selectedIssues);
        },
        
        bulkFixAll: function() {
            if (!confirm('Fix all fixable issues? This may take a while for large sites.')) {
                return;
            }
            
            LI_Admin.Scans.startBulkFix(this.currentScanType, []);
        },
        
        deleteSelectedScans: function() {
            if (this.selectedScans.length === 0) {
                LI_Admin.Core.showNotification('Please select scans to delete', 'error');
                return;
            }
            
            if (!confirm(`Delete ${this.selectedScans.length} selected scan(s) and all associated data?`)) {
                return;
            }
            
            let deleted = 0;
            const total = this.selectedScans.length;
            
            const deleteNext = () => {
                if (deleted >= total) {
                    LI_Admin.Core.showNotification(`Deleted ${deleted} scan(s) successfully`);
                    this.selectedScans = [];
                    this.updateScanBulkActionsBar();
                    this.loadScanHistory();
                    return;
                }
                
                const scanId = this.selectedScans[deleted];
                
                $.ajax({
                    url: liAjax.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'li_delete_scan_history',
                        nonce: liAjax.nonce,
                        scan_id: scanId
                    },
                    success: () => {
                        deleted++;
                        deleteNext();
                    },
                    error: () => {
                        LI_Admin.Core.showNotification(`Failed to delete scan ${scanId}`, 'error');
                        deleted++;
                        deleteNext();
                    }
                });
            };
            
            deleteNext();
        },
        
        handlePagination: function(e) {
            const page = parseInt($(e.currentTarget).data('page'));
            
            if (this.currentScanType) {
                this.loadIssues(this.currentScanType, page);
            } else if (this.currentMetricType) {
                this.loadIntelligence(this.currentMetricType, page);
            } else if ($('#li-section-scan-history').is(':visible')) {
                this.loadScanHistory(page);
            } else if ($('#li-section-ignored').is(':visible')) {
                this.loadIgnored(page);
            }
        },
        
        handleMetricChange: function(e) {
            const metricType = $(e.currentTarget).val();
            this.loadIntelligence(metricType);
        },
        
        loadIssues: function(scanType, page = 1) {
            this.currentPage = page;
            this.currentScanType = scanType;
            this.currentMetricType = null;
            
            const $table = $('.li-issues-table');
            
            const isInternalLinks = scanType === 'internal_links';
            const hasCheckbox = scanType !== 'external_errors';
            const colspanCount = isInternalLinks ? 8 : (hasCheckbox ? 7 : 6);
            
            $table.html(`<tr><td colspan="${colspanCount}" class="li-text-center">Loading...</td></tr>`);
            
            const filters = {};
            
            $.ajax({
                url: liAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'li_get_issues',
                    nonce: liAjax.nonce,
                    scan_type: scanType,
                    page: page,
                    per_page: 20,
                    filters: filters,
                    sort_column: this.sortColumn,
                    sort_order: this.sortOrder
                },
                success: (response) => {
                    if (response.success) {
                        LI_Admin.DataRender.renderIssues(response.data, $table, scanType);
                        LI_Admin.DataRender.renderPagination(response.data, $('.li-pagination'));
                        
                        this.selectedIssues = [];
                        this.updateBulkActionsBar();
                    }
                },
                error: () => {
                    $table.html(`<tr><td colspan="${colspanCount}" class="li-text-center">Failed to load issues</td></tr>`);
                }
            });
        },
        
        loadIntelligence: function(metricType, page = 1) {
            this.currentPage = page;
            this.currentMetricType = metricType;
            this.currentScanType = null;
            
            const $table = $('.li-intelligence-table');
            $table.html('<tr><td colspan="3" class="li-text-center">Loading...</td></tr>');
            
            $('.li-seo-insights').remove();
            
            $.ajax({
                url: liAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'li_get_intelligence',
                    nonce: liAjax.nonce,
                    metric_type: metricType,
                    page: page,
                    per_page: 20
                },
                success: (response) => {
                    if (response.success) {
                        LI_Admin.DataRender.renderIntelligence(response.data, $table, metricType, this);
                        LI_Admin.DataRender.renderPagination(response.data, $('.li-pagination'));
                    }
                },
                error: () => {
                    $table.html('<tr><td colspan="3" class="li-text-center">Failed to load intelligence data</td></tr>');
                }
            });
        },
        
        fetchPostTitles: function(postIds, callback) {
            if (!postIds || postIds.length === 0) {
                callback({});
                return;
            }
            
            const uncachedIds = postIds.filter(id => !this.postTitlesCache[id]);
            
            if (uncachedIds.length === 0) {
                const result = {};
                postIds.forEach(id => {
                    result[id] = this.postTitlesCache[id] || 'Unknown Post';
                });
                callback(result);
                return;
            }
            
            $.ajax({
                url: liAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'li_get_post_titles',
                    nonce: liAjax.nonce,
                    post_ids: uncachedIds
                },
                success: (response) => {
                    if (response.success) {
                        Object.assign(this.postTitlesCache, response.data.posts);
                        
                        const result = {};
                        postIds.forEach(id => {
                            result[id] = this.postTitlesCache[id] || 'Unknown Post';
                        });
                        callback(result);
                    } else {
                        callback({});
                    }
                },
                error: () => {
                    callback({});
                }
            });
        },
        
        loadScanHistory: function(page = 1) {
            console.log('loadScanHistory called with page:', page);
            
            this.currentPage = page;
            this.currentScanType = null;
            this.currentMetricType = null;
            
            const $table = $('.li-scan-history-table');
            console.log('Table element found:', $table.length);
            
            if ($table.length === 0) {
                console.error('Scan history table not found in DOM');
                return;
            }
            
            $table.html('<tr><td colspan="9" class="li-text-center">Loading...</td></tr>');
            
            $.ajax({
                url: liAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'li_get_scan_history',
                    nonce: liAjax.nonce,
                    page: page,
                    per_page: 20,
                    sort_column: this.sortColumn,
                    sort_order: this.sortOrder
                },
                success: (response) => {
                    console.log('Scan history response:', response);
                    if (response.success) {
                        LI_Admin.DataRender.renderScanHistory(response.data, $table);
                        LI_Admin.DataRender.renderPagination(response.data, $('.li-pagination'));
                        
                        this.selectedScans = [];
                        this.updateScanBulkActionsBar();
                    } else {
                        console.error('Scan history response failed:', response);
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Scan history AJAX error:', error, status, xhr);
                    $table.html('<tr><td colspan="9" class="li-text-center">Failed to load scan history</td></tr>');
                }
            });
        },
        
        loadIgnored: function(page = 1) {
            this.currentPage = page;
            this.currentScanType = null;
            this.currentMetricType = null;
            
            const $table = $('.li-ignored-table');
            $table.html('<tr><td colspan="6" class="li-text-center">Loading...</td></tr>');
            
            $.ajax({
                url: liAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'li_get_ignored',
                    nonce: liAjax.nonce,
                    page: page,
                    per_page: 20
                },
                success: (response) => {
                    if (response.success) {
                        LI_Admin.DataRender.renderIgnored(response.data, $table);
                        LI_Admin.DataRender.renderPagination(response.data, $('.li-pagination'));
                    }
                },
                error: () => {
                    $table.html('<tr><td colspan="6" class="li-text-center">Failed to load ignored issues</td></tr>');
                }
            });
        },
        
        fixLink: function(e) {
            e.preventDefault();
            const issueId = $(e.target).closest('button').data('issue-id');
            
            $.ajax({
                url: liAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'li_fix_link',
                    nonce: liAjax.nonce,
                    issue_id: issueId
                },
                success: (response) => {
                    if (response.success) {
                        LI_Admin.Core.showNotification('Link fixed successfully');
                        this.loadIssues(this.currentScanType, this.currentPage);
                    } else {
                        LI_Admin.Core.showNotification(response.data.message, 'error');
                    }
                },
                error: () => {
                    LI_Admin.Core.showNotification('Failed to fix link', 'error');
                }
            });
        },
        
        ignoreLink: function(e) {
            e.preventDefault();
            const issueId = $(e.target).closest('button').data('issue-id');
            
            const reason = prompt('Optional: Why are you ignoring this issue?');
            if (reason === null) {
                return;
            }
            
            $.ajax({
                url: liAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'li_ignore_issue',
                    nonce: liAjax.nonce,
                    issue_id: issueId,
                    reason: reason
                },
                success: (response) => {
                    if (response.success) {
                        LI_Admin.Core.showNotification('Issue ignored');
                        this.loadIssues(this.currentScanType, this.currentPage);
                    } else {
                        LI_Admin.Core.showNotification(response.data.message, 'error');
                    }
                },
                error: () => {
                    LI_Admin.Core.showNotification('Failed to ignore issue', 'error');
                }
            });
        },
        
        unignoreLink: function(e) {
            e.preventDefault();
            const issueId = $(e.target).closest('button').data('issue-id');
            
            $.ajax({
                url: liAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'li_unignore_issue',
                    nonce: liAjax.nonce,
                    issue_id: issueId
                },
                success: (response) => {
                    if (response.success) {
                        LI_Admin.Core.showNotification('Issue restored');
                        this.loadIgnored(this.currentPage);
                    } else {
                        LI_Admin.Core.showNotification(response.data.message, 'error');
                    }
                },
                error: () => {
                    LI_Admin.Core.showNotification('Failed to restore issue', 'error');
                }
            });
        },
        
        toggleRowExpansion: function(e) {
            const $btn = $(e.currentTarget);
            const rowId = $btn.data('row-id');
            const item = $btn.data('item');
            const metricType = $btn.data('metric-type');
            
            const $row = $(`.li-intel-row[data-row-id="${rowId}"]`);
            const $existingExpanded = $row.next('.li-expanded-details');
            
            if (this.expandedRows.has(rowId)) {
                this.expandedRows.delete(rowId);
                $existingExpanded.remove();
                $row.removeClass('li-row-expanded');
                $btn.find('.dashicons').removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
                $btn.html('<span class="dashicons dashicons-arrow-down-alt2"></span> More Details');
            } else {
                const additionalData = item.additional_data ? JSON.parse(item.additional_data) : null;
                const postIds = (additionalData && additionalData.linking_posts) ? additionalData.linking_posts : [];
                
                this.fetchPostTitles(postIds, (posts) => {
                    this.expandedRows.add(rowId);
                    const expandedHtml = LI_Admin.DataRender.renderExpandedRow(item, metricType, additionalData, posts);
                    $row.after(expandedHtml);
                    $row.addClass('li-row-expanded');
                    $btn.find('.dashicons').removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
                    $btn.html('<span class="dashicons dashicons-arrow-up-alt2"></span> Hide Details');
                });
            }
        },
        
        deleteScan: function(e) {
            e.preventDefault();
            const scanId = $(e.target).closest('button').data('scan-id');
            
            if (!confirm('Delete this scan and all associated data?')) {
                return;
            }
            
            $.ajax({
                url: liAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'li_delete_scan_history',
                    nonce: liAjax.nonce,
                    scan_id: scanId
                },
                success: (response) => {
                    if (response.success) {
                        LI_Admin.Core.showNotification('Scan deleted successfully');
                        this.loadScanHistory();
                    } else {
                        LI_Admin.Core.showNotification(response.data.message, 'error');
                    }
                },
                error: () => {
                    LI_Admin.Core.showNotification('Failed to delete scan', 'error');
                }
            });
        },
        
        clearAllScans: function(e) {
            e.preventDefault();
            
            if (!confirm('Delete ALL scan history and associated data? This cannot be undone!')) {
                return;
            }
            
            $.ajax({
                url: liAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'li_delete_all_scans',
                    nonce: liAjax.nonce
                },
                success: (response) => {
                    if (response.success) {
                        LI_Admin.Core.showNotification('All scans deleted successfully');
                        this.selectedScans = [];
                        this.updateScanBulkActionsBar();
                        this.loadScanHistory();
                    } else {
                        LI_Admin.Core.showNotification(response.data.message, 'error');
                    }
                },
                error: () => {
                    LI_Admin.Core.showNotification('Failed to delete scans', 'error');
                }
            });
        }
    };
    
    // Export DataActions immediately when script loads (not on document.ready)
    LI_Admin.DataActions = DataActions;
    LI_Admin.Data = DataActions;
    
    // Initialize only after document is ready
    $(document).ready(() => DataActions.init());
    
})(jQuery);