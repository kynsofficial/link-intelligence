/**
 * Link Intelligence - Core Admin JavaScript
 * Handles initialization, navigation, and UI interactions
 */

(function($) {
    'use strict';
    
    window.LI_Admin = window.LI_Admin || {};
    
    const Core = {
        currentSection: 'internal-links',
        
        init: function() {
            this.restoreLastSection();
            this.bindEvents();
            this.checkActiveScan();
        },
        
        bindEvents: function() {
            $('.li-nav-item').on('click', this.handleNavigation.bind(this));
            
            $('.li-modal-cancel, .li-modal').on('click', this.closeModal.bind(this));
            $('.li-modal-content').on('click', function(e) {
                e.stopPropagation();
            });
            
            $('.li-save-settings').on('click', this.saveSettings.bind(this));
        },
        
        handleNavigation: function(e) {
            e.preventDefault();
            const section = $(e.currentTarget).data('section');
            this.switchSection(section);
        },
        
        switchSection: function(section) {
            this.currentSection = section;
            localStorage.setItem('li_current_section', section);
            
            $('.li-nav-item').removeClass('active');
            $(`.li-nav-item[data-section="${section}"]`).addClass('active');
            
            $('.li-section').hide();
            $(`#li-section-${section}`).show();
            
            // Remove SEO insights when navigating away from intelligence
            if (section !== 'intelligence') {
                $('.li-seo-insights').remove();
            }
            
            switch(section) {
                case 'internal-links':
                    LI_Admin.Data.loadIssues('internal_links');
                    break;
                case 'external-errors':
                    LI_Admin.Data.loadIssues('external_errors');
                    break;
                case 'intelligence':
                    LI_Admin.Data.loadIntelligence('most_linked_internal');
                    break;
                case 'scan-history':
                    LI_Admin.Data.loadScanHistory();
                    break;
                case 'ignored':
                    LI_Admin.Data.loadIgnored();
                    break;
                case 'settings':
                    this.loadSettings();
                    break;
            }
        },
        
        restoreLastSection: function() {
            const lastSection = localStorage.getItem('li_current_section');
            if (lastSection) {
                this.switchSection(lastSection);
            }
        },
        
        checkActiveScan: function() {
            $.ajax({
                url: liAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'li_check_scan_status',
                    nonce: liAjax.nonce
                },
                success: (response) => {
                    if (response.success && response.data.has_active_scan) {
                        LI_Admin.Scans.resumeScan(response.data.state);
                    }
                }
            });
        },
        
        showModal: function(scanType) {
            const $modal = $('#li-scan-modal');
            $modal.data('scan-type', scanType);
            
            // No need to show/hide redirect types section anymore
            // All scans just show content type selection
            
            $('input[name="content_type"]').prop('checked', false);
            
            $modal.addClass('active');
        },
        
        closeModal: function(e) {
            if ($(e.target).is('.li-modal') || $(e.target).is('.li-modal-cancel')) {
                $('#li-scan-modal').removeClass('active');
            }
        },
        
        showNotification: function(message, type = 'success') {
            const $notification = $('<div>')
                .addClass(`li-notification ${type}`)
                .text(message)
                .appendTo('body');
            
            setTimeout(() => {
                $notification.addClass('show');
            }, 10);
            
            setTimeout(() => {
                $notification.removeClass('show');
                setTimeout(() => $notification.remove(), 300);
            }, 3000);
        },
        
        loadSettings: function() {
            $.ajax({
                url: liAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'li_get_settings',
                    nonce: liAjax.nonce
                },
                success: (response) => {
                    if (response.success) {
                        const settings = response.data.settings;
                        $('#allow_multiple_content_types').prop('checked', settings.allow_multiple_content_types);
                        $('#delete_on_uninstall').prop('checked', settings.delete_on_uninstall);
                    }
                }
            });
        },
        
        saveSettings: function() {
            const settings = {
                allow_multiple_content_types: $('#allow_multiple_content_types').is(':checked') ? 'true' : 'false',
                delete_on_uninstall: $('#delete_on_uninstall').is(':checked') ? 'true' : 'false'
            };
            
            $.ajax({
                url: liAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'li_save_settings',
                    nonce: liAjax.nonce,
                    settings: settings
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotification('Settings saved successfully');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        this.showNotification('Failed to save settings', 'error');
                    }
                }
            });
        }
    };
    
    LI_Admin.Core = Core;
    
    $(document).ready(() => Core.init());
    
})(jQuery);