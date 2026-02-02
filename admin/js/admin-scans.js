/**
 * Link Intelligence - Scans Module
 * Fast polling for seamless scanning experience
 */

(function($) {
    'use strict';
    
    window.LI_Admin = window.LI_Admin || {};
    
    const Scans = {
        activeInterval: null,
        currentScanType: null,
        currentOperation: null,
        isCompleting: false,
        scanning: false,
        
        init: function() {
            this.bindEvents();
        },
        
        disableScanButtons: function() {
            $('.li-start-scan').prop('disabled', true).addClass('li-disabled');
        },
        
        enableScanButtons: function() {
            $('.li-start-scan').prop('disabled', false).removeClass('li-disabled');
        },
        
        bindEvents: function() {
            $(document).on('click', '.li-start-scan', this.handleStartScan.bind(this));
            $('.li-modal-confirm').on('click', this.confirmScan.bind(this));
            $(document).on('click', '.li-cancel-operation', this.cancelOperation.bind(this));
        },
        
        handleStartScan: function(e) {
            const scanType = $(e.currentTarget).data('scan-type');
            this.currentScanType = scanType;
            LI_Admin.Core.showModal(scanType);
        },
        
        confirmScan: function() {
            const scanType = $('#li-scan-modal').data('scan-type');
            const config = this.getScanConfig();
            
            if (!this.validateConfig(config, scanType)) {
                return;
            }
            
            $('#li-scan-modal').removeClass('active');
            this.startScan(scanType, config);
        },
        
        getScanConfig: function() {
            const config = {};
            
            const contentTypes = [];
            $('input[name="content_type"]:checked').each(function() {
                contentTypes.push($(this).val());
            });
            config.content_type = contentTypes.length === 1 ? contentTypes[0] : contentTypes;
            
            return config;
        },
        
        validateConfig: function(config, scanType) {
            if (!config.content_type || (Array.isArray(config.content_type) && config.content_type.length === 0)) {
                LI_Admin.Core.showNotification('Please select at least one content type', 'error');
                return false;
            }
            
            return true;
        },
        
        startScan: function(scanType, config) {
            this.currentScanType = scanType;
            this.currentOperation = 'scan';
            this.isCompleting = false;
            this.scanning = true;
            this.disableScanButtons();
            
            $.ajax({
                url: lhcfwpAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'lhcfwp_scan_start',
                    nonce: lhcfwpAjax.nonce,
                    scan_type: scanType,
                    config: config
                },
                success: (response) => {
                    if (response.success) {
                        this.showProgressContainer();
                        this.continueScan();
                    } else {
                        this.enableScanButtons();
                        LI_Admin.Core.showNotification(response.data.message, 'error');
                    }
                },
                error: () => {
                    this.enableScanButtons();
                    LI_Admin.Core.showNotification('Failed to start scan', 'error');
                }
            });
        },
        
        startBulkFix: function(scanType, issueIds = []) {
            this.currentScanType = scanType;
            this.currentOperation = 'bulk_fix';
            this.isCompleting = false;
            this.scanning = true;
            this.disableScanButtons();
            
            $.ajax({
                url: lhcfwpAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'lhcfwp_bulk_fix_start',
                    nonce: lhcfwpAjax.nonce,
                    scan_type: scanType,
                    issue_ids: issueIds
                },
                success: (response) => {
                    if (response.success) {
                        if (response.data.total === 0) {
                            this.enableScanButtons();
                            LI_Admin.Core.showNotification('No fixable issues found', 'info');
                            return;
                        }
                        
                        this.showProgressContainer();
                        this.continueBulkFix();
                    } else {
                        this.enableScanButtons();
                        LI_Admin.Core.showNotification(response.data.message, 'error');
                    }
                },
                error: () => {
                    this.enableScanButtons();
                    LI_Admin.Core.showNotification('Failed to start bulk fix', 'error');
                }
            });
        },
        
        showProgressContainer: function() {
            $('.li-progress-container').removeClass('li-hidden');
            this.updateProgress(0, 'Initializing...', 0, 0);
            this.clearExecutionLog();
        },
        
        hideProgressContainer: function() {
            $('.li-progress-container').addClass('li-hidden');
        },
        
        continueScan: function() {
            if (!this.scanning) return;
            
            $.ajax({
                url: lhcfwpAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'lhcfwp_scan_continue',
                    nonce: lhcfwpAjax.nonce
                },
                success: (response) => {
                    if (response.success) {
                        if (response.data.completed) {
                            this.completeProcessing(response.data);
                        } else if (response.data.continue) {
                            this.updateProgress(
                                response.data.progress,
                                response.data.current_post || 'Processing...',
                                response.data.state.current,
                                response.data.state.total
                            );
                            
                            if (response.data.log) {
                                this.appendToExecutionLog(response.data.log);
                            }
                            
                            // Continue immediately - fast polling
                            setTimeout(() => {
                                this.continueScan();
                            }, 100);
                        }
                    } else {
                        this.enableScanButtons();
                        LI_Admin.Core.showNotification(response.data.message || 'Scan failed', 'error');
                        this.hideProgressContainer();
                        this.scanning = false;
                    }
                },
                error: () => {
                    this.enableScanButtons();
                    LI_Admin.Core.showNotification('An error occurred during scanning', 'error');
                    this.hideProgressContainer();
                    this.scanning = false;
                }
            });
        },
        
        continueBulkFix: function() {
            if (!this.scanning) return;
            
            $.ajax({
                url: lhcfwpAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'lhcfwp_bulk_fix_continue',
                    nonce: lhcfwpAjax.nonce
                },
                success: (response) => {
                    if (response.success) {
                        if (response.data.completed) {
                            this.completeProcessing(response.data);
                        } else if (response.data.continue) {
                            this.updateProgress(
                                response.data.progress,
                                response.data.current_item || 'Processing...',
                                response.data.state.current,
                                response.data.state.total
                            );
                            
                            if (response.data.log) {
                                this.appendToExecutionLog(response.data.log);
                            }
                            
                            // Continue immediately - fast polling
                            setTimeout(() => {
                                this.continueBulkFix();
                            }, 100);
                        }
                    } else {
                        this.enableScanButtons();
                        LI_Admin.Core.showNotification(response.data.message || 'Bulk fix failed', 'error');
                        this.hideProgressContainer();
                        this.scanning = false;
                    }
                },
                error: () => {
                    this.enableScanButtons();
                    LI_Admin.Core.showNotification('An error occurred during bulk fix', 'error');
                    this.hideProgressContainer();
                    this.scanning = false;
                }
            });
        },
        
        completeProcessing: function(data) {
            this.isCompleting = true;
            this.scanning = false;
            this.enableScanButtons();
            
            let message = '';
            
            if (this.currentOperation === 'scan') {
                if (this.currentScanType === 'intelligence') {
                    message = 'Intelligence analysis completed! Data gathered and ready to view.';
                } else {
                    const issueCount = data.state.issues_found || 0;
                    message = `Scan completed! Found ${issueCount} issue(s)`;
                }
            } else {
                message = `Bulk fix completed! Fixed: ${data.fixed}, Failed: ${data.failed}`;
            }
            
            this.updateProgress(100, message, data.state.total, data.state.total);
            
            LI_Admin.Core.showNotification(message, 'success');
            
            setTimeout(() => {
                this.hideProgressContainer();
                
                if (this.currentScanType) {
                    if (this.currentScanType === 'intelligence') {
                        $('.li-intelligence-filter').val('most_linked_internal');
                        LI_Admin.Data.loadIntelligence('most_linked_internal');
                    } else {
                        LI_Admin.Data.loadIssues(this.currentScanType);
                    }
                }
                
                this.isCompleting = false;
            }, 2000);
        },
        
        cancelOperation: function() {
            if (!confirm('Are you sure you want to cancel this operation?')) {
                return;
            }
            
            const action = this.currentOperation === 'scan' ? 'lhcfwp_scan_cancel' : 'lhcfwp_bulk_fix_cancel';
            
            $.ajax({
                url: lhcfwpAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: action,
                    nonce: lhcfwpAjax.nonce
                },
                success: () => {
                    this.scanning = false;
                    this.enableScanButtons();
                    this.hideProgressContainer();
                    LI_Admin.Core.showNotification('Operation cancelled', 'info');
                }
            });
        },
        
        resumeScan: function(state) {
            if (!state || state.status !== 'running') {
                return;
            }
            
            this.currentScanType = state.scan_type;
            this.currentOperation = 'scan';
            this.isCompleting = false;
            this.scanning = true;
            this.disableScanButtons();
            this.showProgressContainer();
            this.continueScan();
        },
        
        updateProgress: function(percentage, currentItem, current, total) {
            $('.li-progress-bar').css('width', percentage + '%');
            $('.li-progress-percentage').text(percentage + '%');
            $('.li-current-post').text(currentItem);
            $('.li-progress-current').text(`${current} / ${total}`);
            
            let title = 'Processing';
            if (this.currentOperation === 'scan') {
                if (this.currentScanType === 'intelligence') {
                    title = 'Intelligence Analysis in Progress';
                } else {
                    title = 'Scan in Progress';
                }
            } else {
                title = 'Bulk Fix in Progress';
            }
            $('.li-progress-title').text(title);
        },
        
        appendToExecutionLog: function(logLines) {
            const $log = $('.li-execution-log');
            
            if (Array.isArray(logLines)) {
                logLines.forEach(line => {
                    this.addLogLine(line, $log);
                });
            } else {
                this.addLogLine(logLines, $log);
            }
            
            $log.scrollTop($log[0].scrollHeight);
        },
        
        addLogLine: function(line, $log) {
            let className = 'li-log-line';
            
            if (line.includes('Scanning:') || line.includes('Analyzing:') || line.includes('Processing:')) {
                className += ' scanning';
            } else if (line.includes('Problem found:') || line.includes('Failed:')) {
                className += ' problem';
            } else if (line.includes('Completed') || line.includes('Fixed:') || line.includes('Found')) {
                className += ' completed';
            }
            
            $log.append(`<div class="${className}">${this.escapeHtml(line)}</div>`);
        },
        
        clearExecutionLog: function() {
            $('.li-execution-log').html('');
        },
        
        escapeHtml: function(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text).replace(/[&<>"']/g, m => map[m]);
        }
    };
    
    LI_Admin.Scans = Scans;
    
    $(document).ready(() => Scans.init());
    
})(jQuery);
