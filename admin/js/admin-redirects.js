/**
 * Redirect Management JavaScript
 */

(function($) {
    'use strict';
    
    const RedirectManager = {
        currentPage: 1,
        editMode: false,
        editId: null,
        
        init: function() {
            this.bindEvents();
        },
        
        bindEvents: function() {
            const self = this;
            
            // Add redirect button
            $(document).on('click', '.li-add-redirect-btn', function(e) {
                e.preventDefault();
                self.openModal();
            });
            
            // Edit redirect button
            $(document).on('click', '.li-edit-redirect', function(e) {
                e.preventDefault();
                const id = $(this).data('redirect-id');
                self.openEditModal(id);
            });
            
            // Modal actions
            $(document).on('click', '.li-redirect-modal-cancel', function(e) {
                e.preventDefault();
                self.closeModal();
            });
            
            $(document).on('click', '.li-redirect-modal-save', function(e) {
                e.preventDefault();
                if (self.editMode) {
                    self.updateRedirect();
                } else {
                    self.saveRedirect();
                }
            });
            
            // Add/remove source URL rows
            $(document).on('click', '.li-add-source-url', function(e) {
                e.preventDefault();
                self.addSourceUrlRow();
            });
            
            $(document).on('click', '.li-remove-source-url', function(e) {
                e.preventDefault();
                $(this).closest('.li-source-url-row').remove();
                self.updateRemoveButtons();
            });
            
            // Delete redirect
            $(document).on('click', '.li-delete-redirect', function(e) {
                e.preventDefault();
                const id = $(this).data('redirect-id');
                self.deleteRedirect(id);
            });
            
            // Toggle redirect status
            $(document).on('click', '.li-toggle-redirect', function(e) {
                e.preventDefault();
                const id = $(this).data('redirect-id');
                const currentStatus = $(this).data('status');
                const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
                self.toggleStatus(id, newStatus);
            });
            
            // Select all checkboxes
            $(document).on('change', '#li-select-all-redirects', function() {
                $('.li-redirect-checkbox').prop('checked', $(this).prop('checked'));
                self.updateBulkDeleteButton();
            });
            
            // Individual checkbox
            $(document).on('change', '.li-redirect-checkbox', function() {
                self.updateBulkDeleteButton();
            });
            
            // Delete selected
            $(document).on('click', '.li-delete-selected-redirects', function(e) {
                e.preventDefault();
                self.deleteSelected();
            });
            
            // Clear all
            $(document).on('click', '.li-clear-all-redirects', function(e) {
                e.preventDefault();
                self.clearAll();
            });
            
            // Category checkbox change
            $(document).on('change', '.li-category-checkbox', function() {
                if ($(this).prop('checked')) {
                    $('#li-redirect-category').val($(this).val());
                } else if ($('#li-redirect-category').val() === $(this).val()) {
                    $('#li-redirect-category').val('');
                }
            });
            
            // Category input change
            $(document).on('input', '#li-redirect-category', function() {
                const val = $(this).val();
                $('.li-category-checkbox').each(function() {
                    $(this).prop('checked', $(this).val() === val);
                });
            });
            
            // Pagination
            $(document).on('click', '#li-section-redirects .li-pagination-btn', function() {
                const page = $(this).data('page');
                self.loadRedirects(page);
            });
            
            // Close modal when clicking outside
            $(document).on('click', '#li-redirect-modal', function(e) {
                if ($(e.target).is('#li-redirect-modal')) {
                    self.closeModal();
                }
            });
        },
        
        openModal: function(mode = 'add') {
            this.editMode = false;
            this.editId = null;
            $('.li-redirect-modal-title').text('Add Redirect');
            $('#li-redirect-modal').addClass('active');
            this.resetForm();
            this.loadCategories();
        },
        
        openEditModal: function(id) {
            const self = this;
            this.editMode = true;
            this.editId = id;
            $('.li-redirect-modal-title').text('Edit Redirect');
            
            $.ajax({
                url: lhcfwpAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'lhcfwp_get_redirects',
                    nonce: lhcfwpAjax.nonce,
                    page: 1,
                    per_page: 1000
                },
                success: function(response) {
                    if (response.success) {
                        const redirect = response.data.redirects.find(r => r.id == id);
                        if (redirect) {
                            self.populateEditForm(redirect);
                            $('#li-redirect-modal').addClass('active');
                        }
                    }
                }
            });
        },
        
        populateEditForm: function(redirect) {
            const sourceUrls = JSON.parse(redirect.source_urls || '[]');
            
            // Clear and populate source URLs
            $('#li-source-urls-container').empty();
            sourceUrls.forEach((url, index) => {
                const row = $(`
                    <div class="li-source-url-row" style="display: flex; gap: 8px; margin-bottom: 8px;">
                        <input type="text" class="li-source-url-input" placeholder="https://example.com/old-page" value="${this.escapeHtml(url)}" style="flex: 1;">
                        <button type="button" class="li-btn li-btn-secondary li-btn-sm li-remove-source-url" ${index === 0 && sourceUrls.length === 1 ? 'style="display: none;"' : ''}>
                            <span class="dashicons dashicons-no"></span>
                        </button>
                    </div>
                `);
                $('#li-source-urls-container').append(row);
            });
            
            this.updateRemoveButtons();
            
            $('#li-redirect-destination').val(redirect.destination_url);
            $('input[name="redirect_type"][value="' + redirect.redirect_type + '"]').prop('checked', true);
            $('input[name="redirect_status"][value="' + redirect.status + '"]').prop('checked', true);
            $('#li-redirect-category').val(redirect.category || '');
            
            this.loadCategories();
        },
        
        closeModal: function() {
            $('#li-redirect-modal').removeClass('active');
            this.editMode = false;
            this.editId = null;
        },
        
        resetForm: function() {
            $('#li-source-urls-container').html(`
                <div class="li-source-url-row" style="display: flex; gap: 8px; margin-bottom: 8px;">
                    <input type="text" class="li-source-url-input" placeholder="https://example.com/old-page" style="flex: 1;">
                    <button type="button" class="li-btn li-btn-secondary li-btn-sm li-remove-source-url" style="display: none;">
                        <span class="dashicons dashicons-no"></span>
                    </button>
                </div>
            `);
            
            $('#li-redirect-destination').val('');
            $('#li-redirect-category').val('');
            $('input[name="redirect_type"][value="301"]').prop('checked', true);
            $('input[name="redirect_status"][value="active"]').prop('checked', true);
        },
        
        addSourceUrlRow: function() {
            const newRow = $(`
                <div class="li-source-url-row" style="display: flex; gap: 8px; margin-bottom: 8px;">
                    <input type="text" class="li-source-url-input" placeholder="https://example.com/another-old-page" style="flex: 1;">
                    <button type="button" class="li-btn li-btn-secondary li-btn-sm li-remove-source-url">
                        <span class="dashicons dashicons-no"></span>
                    </button>
                </div>
            `);
            
            $('#li-source-urls-container').append(newRow);
            this.updateRemoveButtons();
        },
        
        updateRemoveButtons: function() {
            const rowCount = $('.li-source-url-row').length;
            if (rowCount > 1) {
                $('.li-remove-source-url').show();
            } else {
                $('.li-remove-source-url').hide();
            }
        },
        
        saveRedirect: function() {
            if (typeof lhcfwpAjax === 'undefined') {
                alert('Error: AJAX configuration not loaded. Please refresh the page.');
                return;
            }
            
            const self = this;
            const sourceUrls = [];
            $('.li-source-url-input').each(function() {
                const url = $(this).val().trim();
                if (url) {
                    sourceUrls.push(url);
                }
            });
            
            const destinationUrl = $('#li-redirect-destination').val().trim();
            const redirectType = $('input[name="redirect_type"]:checked').val();
            const status = $('input[name="redirect_status"]:checked').val();
            const category = $('#li-redirect-category').val().trim();
            
            if (sourceUrls.length === 0) {
                alert('Please enter at least one source URL');
                return;
            }
            
            if (!destinationUrl) {
                alert('Please enter a destination URL');
                return;
            }
            
            $.ajax({
                url: lhcfwpAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'lhcfwp_add_redirect',
                    nonce: lhcfwpAjax.nonce,
                    source_urls: sourceUrls,
                    destination_url: destinationUrl,
                    redirect_type: redirectType,
                    status: status,
                    category: category
                },
                success: function(response) {
                    if (response.success) {
                        self.closeModal();
                        self.loadRedirects();
                        alert(response.data.message);
                    } else {
                        alert('Error: ' + (response.data.message || 'Failed to add redirect'));
                    }
                },
                error: function() {
                    alert('An error occurred while saving the redirect');
                }
            });
        },
        
        updateRedirect: function() {
            const self = this;
            const sourceUrls = [];
            $('.li-source-url-input').each(function() {
                const url = $(this).val().trim();
                if (url) {
                    sourceUrls.push(url);
                }
            });
            
            const destinationUrl = $('#li-redirect-destination').val().trim();
            const redirectType = $('input[name="redirect_type"]:checked').val();
            const status = $('input[name="redirect_status"]:checked').val();
            const category = $('#li-redirect-category').val().trim();
            
            if (sourceUrls.length === 0) {
                alert('Please enter at least one source URL');
                return;
            }
            
            if (!destinationUrl) {
                alert('Please enter a destination URL');
                return;
            }
            
            $.ajax({
                url: lhcfwpAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'lhcfwp_update_redirect',
                    nonce: lhcfwpAjax.nonce,
                    id: this.editId,
                    source_urls: sourceUrls,
                    destination_url: destinationUrl,
                    redirect_type: redirectType,
                    status: status,
                    category: category
                },
                success: function(response) {
                    if (response.success) {
                        self.closeModal();
                        self.loadRedirects(self.currentPage);
                        alert(response.data.message);
                    } else {
                        alert('Error: ' + (response.data.message || 'Failed to update redirect'));
                    }
                },
                error: function() {
                    alert('An error occurred while updating the redirect');
                }
            });
        },
        
        loadCategories: function() {
            $.ajax({
                url: lhcfwpAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'lhcfwp_get_redirects',
                    nonce: lhcfwpAjax.nonce,
                    page: 1,
                    per_page: 1000
                },
                success: function(response) {
                    if (response.success && response.data.redirects) {
                        const categories = new Set();
                        response.data.redirects.forEach(redirect => {
                            if (redirect.category && redirect.category.trim() !== '') {
                                categories.add(redirect.category.trim());
                            }
                        });
                        
                        const container = $('#li-category-checkboxes');
                        container.empty();
                        
                        if (categories.size > 0) {
                            container.show();
                            categories.forEach(category => {
                                const checkbox = $(`
                                    <label style="display: inline-flex; align-items: center; margin-right: 15px; cursor: pointer;">
                                        <input type="checkbox" class="li-category-checkbox" value="${category}" style="margin-right: 5px;">
                                        <span>${category}</span>
                                    </label>
                                `);
                                container.append(checkbox);
                            });
                        } else {
                            container.hide();
                        }
                    }
                }
            });
        },
        
        loadRedirects: function(page = 1) {
            this.currentPage = page;
            
            $.ajax({
                url: lhcfwpAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'lhcfwp_get_redirects',
                    nonce: lhcfwpAjax.nonce,
                    page: page,
                    per_page: 20
                },
                success: (response) => {
                    if (response.success) {
                        this.renderRedirects(response.data);
                    }
                }
            });
        },
        
        renderRedirects: function(data) {
            const tbody = $('.li-redirects-table');
            tbody.empty();
            
            $('#li-select-all-redirects').prop('checked', false);
            
            if (!data.redirects || data.redirects.length === 0) {
                tbody.html(`
                    <tr>
                        <td colspan="8" class="li-text-center">
                            <div class="li-empty-state">
                                <div class="li-empty-icon"><span class="dashicons dashicons-migrate"></span></div>
                                <div class="li-empty-title">No redirects configured</div>
                                <div class="li-empty-text">Add redirects to manage your site's URL structure</div>
                            </div>
                        </td>
                    </tr>
                `);
                this.updateBulkDeleteButton();
                return;
            }
            
            data.redirects.forEach((redirect) => {
                const sourceUrls = JSON.parse(redirect.source_urls || '[]');
                const sourceDisplay = this.formatSourceUrls(sourceUrls);
                
                const statusBadge = redirect.status === 'active' 
                    ? '<span class="li-badge li-badge-success">Active</span>' 
                    : '<span class="li-badge li-badge-secondary">Inactive</span>';
                
                const typeBadge = `<span class="li-badge li-badge-info">${redirect.redirect_type}</span>`;
                
                const row = $(`
                    <tr>
                        <td><input type="checkbox" class="li-redirect-checkbox" data-redirect-id="${redirect.id}"></td>
                        <td>${sourceDisplay}</td>
                        <td>${this.truncateUrl(redirect.destination_url, 50)}</td>
                        <td>${typeBadge}</td>
                        <td>${statusBadge}</td>
                        <td>${this.escapeHtml(redirect.category || '-')}</td>
                        <td>${redirect.created_at}</td>
                        <td>
                            <div class="li-table-actions">
                                <button class="li-btn li-btn-secondary li-btn-sm li-edit-redirect" 
                                        data-redirect-id="${redirect.id}" title="Edit Redirect">
                                    <span class="dashicons dashicons-edit"></span> Edit
                                </button>
                                <button class="li-btn li-btn-secondary li-btn-sm li-toggle-redirect" 
                                        data-redirect-id="${redirect.id}" 
                                        data-status="${redirect.status}" 
                                        title="${redirect.status === 'active' ? 'Deactivate' : 'Activate'}">
                                    <span class="dashicons dashicons-controls-${redirect.status === 'active' ? 'pause' : 'play'}"></span> ${redirect.status === 'active' ? 'Deactivate' : 'Activate'}
                                </button>
                                <button class="li-btn li-btn-danger li-btn-sm li-delete-redirect" 
                                        data-redirect-id="${redirect.id}" title="Delete Redirect">
                                    <span class="dashicons dashicons-trash"></span> Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                `);
                
                tbody.append(row);
            });
            
            // Handle pagination
            if (data.total_pages > 1) {
                const paginationContainer = tbody.closest('.li-card-body').find('.li-pagination');
                paginationContainer.removeClass('li-hidden');
                this.renderPagination(paginationContainer, data.page, data.total_pages);
            }
            
            this.updateBulkDeleteButton();
        },
        
        formatSourceUrls: function(urls) {
            if (!urls || urls.length === 0) return '-';
            
            if (urls.length === 1) {
                return this.truncateUrl(urls[0], 50);
            }
            
            const truncated = this.truncateUrl(urls[0], 30);
            const count = urls.length - 1;
            const tooltip = urls.map(u => this.escapeHtml(u)).join('\n');
            
            return `<span title="${this.escapeHtml(tooltip)}" style="cursor: help;">${truncated} <span style="color: #666;">(+${count} more)</span></span>`;
        },
        
        truncateUrl: function(url, maxLength) {
            if (!url) return '';
            if (url.length <= maxLength) return this.escapeHtml(url);
            return this.escapeHtml(url.substring(0, maxLength) + '...');
        },
        
        renderPagination: function($container, currentPage, totalPages) {
            let html = '';
            
            if (currentPage > 1) {
                html += `<button class="li-pagination-btn" data-page="${currentPage - 1}">Previous</button>`;
            }
            
            html += `<span class="li-pagination-info">Page ${currentPage} of ${totalPages}</span>`;
            
            if (currentPage < totalPages) {
                html += `<button class="li-pagination-btn" data-page="${currentPage + 1}">Next</button>`;
            }
            
            $container.html(html);
        },
        
        deleteRedirect: function(id) {
            if (!confirm('Are you sure you want to delete this redirect?')) {
                return;
            }
            
            const self = this;
            
            $.ajax({
                url: lhcfwpAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'lhcfwp_delete_redirect',
                    nonce: lhcfwpAjax.nonce,
                    id: id
                },
                success: function(response) {
                    if (response.success) {
                        self.loadRedirects(self.currentPage);
                    } else {
                        alert(response.data.message || 'Failed to delete redirect');
                    }
                },
                error: function() {
                    alert('An error occurred while deleting the redirect');
                }
            });
        },
        
        deleteSelected: function() {
            const selectedIds = [];
            $('.li-redirect-checkbox:checked').each(function() {
                selectedIds.push($(this).data('redirect-id'));
            });
            
            if (selectedIds.length === 0) {
                alert('Please select at least one redirect to delete');
                return;
            }
            
            if (!confirm(`Are you sure you want to delete ${selectedIds.length} redirect(s)?`)) {
                return;
            }
            
            const self = this;
            
            $.ajax({
                url: lhcfwpAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'lhcfwp_delete_redirects',
                    nonce: lhcfwpAjax.nonce,
                    ids: selectedIds
                },
                success: function(response) {
                    if (response.success) {
                        self.loadRedirects(self.currentPage);
                        alert(response.data.message);
                    } else {
                        alert(response.data.message || 'Failed to delete redirects');
                    }
                },
                error: function() {
                    alert('An error occurred while deleting redirects');
                }
            });
        },
        
        clearAll: function() {
            if (!confirm('Are you sure you want to delete ALL redirects? This action cannot be undone!')) {
                return;
            }
            
            const self = this;
            
            $.ajax({
                url: lhcfwpAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'lhcfwp_clear_all_redirects',
                    nonce: lhcfwpAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.loadRedirects(1);
                        alert(response.data.message);
                    } else {
                        alert(response.data.message || 'Failed to clear redirects');
                    }
                },
                error: function() {
                    alert('An error occurred while clearing redirects');
                }
            });
        },
        
        toggleStatus: function(id, newStatus) {
            const self = this;
            
            $.ajax({
                url: lhcfwpAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'lhcfwp_update_redirect',
                    nonce: lhcfwpAjax.nonce,
                    id: id,
                    status: newStatus
                },
                success: function(response) {
                    if (response.success) {
                        self.loadRedirects(self.currentPage);
                    } else {
                        alert(response.data.message || 'Failed to update redirect');
                    }
                },
                error: function() {
                    alert('An error occurred while updating the redirect');
                }
            });
        },
        
        updateBulkDeleteButton: function() {
            const checkedCount = $('.li-redirect-checkbox:checked').length;
            if (checkedCount > 0) {
                $('.li-delete-selected-redirects').show();
            } else {
                $('.li-delete-selected-redirects').hide();
            }
        },
        
        escapeHtml: function(text) {
            if (!text) return '';
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
    
    // Initialize when document is ready
    $(document).ready(function() {
        RedirectManager.init();
        
        // Load redirects when section is shown via custom event
        $(document).on('li-load-redirects', function() {
            RedirectManager.loadRedirects();
        });
        
        // Load redirects on page load if redirects section is active
        const currentSection = localStorage.getItem('li_current_section');
        if (currentSection === 'redirects' && $('#li-section-redirects').is(':visible')) {
            RedirectManager.loadRedirects();
        }
    });
    
})(jQuery);
