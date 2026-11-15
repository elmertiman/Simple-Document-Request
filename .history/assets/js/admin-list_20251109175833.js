(function($) {
    'use strict';
    
    let currentOrderBy = 'created_at';
    let currentOrder = 'DESC';
    let currentSearch = '';
    let currentStatus = '';
    let selectedRequests = [];
    
    $(document).ready(function() {
        // Load initial data
        loadRequests();
        
        // Select all checkbox
        $('#sdr-select-all').on('change', function() {
            const isChecked = $(this).is(':checked');
            $('.sdr-request-checkbox').prop('checked', isChecked);
            updateSelectedRequests();
        });
        
        // Individual checkbox
        $(document).on('change', '.sdr-request-checkbox', function() {
            updateSelectedRequests();
            
            // Update select all checkbox
            const totalCheckboxes = $('.sdr-request-checkbox').length;
            const checkedCheckboxes = $('.sdr-request-checkbox:checked').length;
            $('#sdr-select-all').prop('checked', totalCheckboxes === checkedCheckboxes && totalCheckboxes > 0);
        });
        
        // Bulk delete button
        $('#sdr-bulk-delete-btn').on('click', function() {
            if (selectedRequests.length === 0) {
                alert('Please select requests to delete');
                return;
            }
            
            const count = selectedRequests.length;
            const confirmMessage = count === 1 
                ? 'Are you sure you want to delete this request?' 
                : 'Are you sure you want to delete ' + count + ' requests?';
            
            if (!confirm(confirmMessage + ' This action cannot be undone.')) {
                return;
            }
            
            bulkDeleteRequests();
        });
        
        // Search functionality
        $('#sdr-search-btn').on('click', function() {
            currentSearch = $('#sdr-search-input').val().trim();
            loadRequests();
            
            if (currentSearch) {
                $('#sdr-clear-search-btn').show();
            }
        });
        
        // Clear search
        $('#sdr-clear-search-btn').on('click', function() {
            currentSearch = '';
            $('#sdr-search-input').val('');
            $(this).hide();
            loadRequests();
        });
        
        // Search on Enter key
        $('#sdr-search-input').on('keypress', function(e) {
            if (e.which === 13) {
                $('#sdr-search-btn').trigger('click');
            }
        });
        
        // Status filter
        $('#sdr-status-filter').on('change', function() {
            currentStatus = $(this).val();
            loadRequests();
        });
        
        // Sortable columns
        $(document).on('click', '.sdr-sortable', function() {
            const column = $(this).data('column');
            
            // Toggle order if same column
            if (currentOrderBy === column) {
                currentOrder = currentOrder === 'ASC' ? 'DESC' : 'ASC';
            } else {
                currentOrderBy = column;
                currentOrder = 'DESC';
            }
            
            // Update UI
            $('.sdr-sortable').removeClass('sorted asc desc');
            $(this).addClass('sorted ' + currentOrder.toLowerCase());
            
            // Update sort indicators
            $('.sdr-sort-indicator').text('');
            $(this).find('.sdr-sort-indicator').text(currentOrder === 'ASC' ? '▲' : '▼');
            
            loadRequests();
        });
        
        // Delete request
        $(document).on('click', '.sdr-delete-btn', function(e) {
            e.preventDefault();
            
            const requestId = $(this).data('request-id');
            const row = $(this).closest('tr');
            const deleteIcon = $(this).find('i');
            
            if (!confirm('Are you sure you want to delete this request? This action cannot be undone.')) {
                return;
            }
            
            // Disable button and show loading
            $(this).prop('disabled', true);
            deleteIcon.removeClass('fa-trash-alt').addClass('fa-spinner fa-spin');
            
            $.ajax({
                url: sdrAdminData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'sdr_delete_request',
                    nonce: sdrAdminData.nonce,
                    request_id: requestId
                },
                success: function(response) {
                    if (response.success) {
                        // Remove row with animation
                        row.fadeOut(300, function() {
                            $(this).remove();
                            
                            // Reload to update counts
                            loadRequests();
                        });
                    } else {
                        alert(response.data.message || 'Failed to delete request');
                        deleteIcon.removeClass('fa-spinner fa-spin').addClass('fa-trash-alt');
                        $(this).prop('disabled', false);
                    }
                },
                error: function() {
                    alert('An error occurred while deleting the request');
                    deleteIcon.removeClass('fa-spinner fa-spin').addClass('fa-trash-alt');
                    $(this).prop('disabled', false);
                }
            });
        });
    });
    
    function updateSelectedRequests() {
        selectedRequests = [];
        $('.sdr-request-checkbox:checked').each(function() {
            selectedRequests.push($(this).val());
        });
        
        // Update count and show/hide bulk actions
        $('#sdr-selected-count').text(selectedRequests.length);
        if (selectedRequests.length > 0) {
            $('.sdr-bulk-actions').slideDown(200);
        } else {
            $('.sdr-bulk-actions').slideUp(200);
        }
    }
    
    function bulkDeleteRequests() {
        const btn = $('#sdr-bulk-delete-btn');
        const icon = btn.find('i');
        const originalText = btn.html();
        
        // Disable button and show loading
        btn.prop('disabled', true);
        icon.removeClass('fa-trash-alt').addClass('fa-spinner fa-spin');
        
        $.ajax({
            url: sdrAdminData.ajaxurl,
            type: 'POST',
            data: {
                action: 'sdr_bulk_delete_requests',
                nonce: sdrAdminData.nonce,
                request_ids: selectedRequests
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    alert(response.data.message);
                    
                    // Reset selection
                    selectedRequests = [];
                    $('#sdr-select-all').prop('checked', false);
                    $('.sdr-bulk-actions').hide();
                    
                    // Reload table
                    loadRequests();
                } else {
                    alert(response.data.message || 'Failed to delete requests');
                }
                
                btn.prop('disabled', false).html(originalText);
            },
            error: function() {
                alert('An error occurred while deleting requests');
                btn.prop('disabled', false).html(originalText);
            }
        });
    }
    
    function loadRequests() {
        $('#sdr-loading').show();
        $('#sdr-requests-table').css('opacity', '0.5');
        $('.sdr-no-results').hide();
        
        $.ajax({
            url: sdrAdminData.ajaxurl,
            type: 'POST',
            data: {
                action: 'sdr_get_requests',
                nonce: sdrAdminData.nonce,
                search: currentSearch,
                orderby: currentOrderBy,
                order: currentOrder,
                status: currentStatus,
                limit: 100,
                offset: 0
            },
            success: function(response) {
                $('#sdr-loading').hide();
                $('#sdr-requests-table').css('opacity', '1');
                
                if (response.success) {
                    renderRequests(response.data.requests);
                    updateCounts(response.data.showing, response.data.total);
                } else {
                    showError(response.data.message || 'Failed to load requests');
                }
            },
            error: function() {
                $('#sdr-loading').hide();
                $('#sdr-requests-table').css('opacity', '1');
                showError('An error occurred while loading requests');
            }
        });
    }
    
    function renderRequests(requests) {
        const tbody = $('#sdr-requests-tbody');
        tbody.empty();
        
        // Reset selection
        selectedRequests = [];
        $('#sdr-select-all').prop('checked', false);
        $('.sdr-bulk-actions').hide();
        
        if (requests.length === 0) {
            tbody.append('<tr><td colspan="9" class="sdr-no-data">' + (currentSearch ? 'No requests found matching your search.' : 'No requests found.') + '</td></tr>');
            $('.sdr-no-results').show();
            return;
        }
        
        $.each(requests, function(index, request) {
            const row = $('<tr></tr>');
            
            // Checkbox
            const checkCell = $('<th class="check-column"></th>');
            const checkbox = $('<input type="checkbox" class="sdr-request-checkbox" value="' + request.id + '" />');
            checkCell.append(checkbox);
            row.append(checkCell);
            
            // ID
            row.append('<td>' + escapeHtml(request.id) + '</td>');
            
            // Product
            row.append('<td><a href="' + escapeHtml(request.product_edit_url) + '" target="_blank">' + escapeHtml(request.product_name) + '</a></td>');
            
            // Email
            row.append('<td>' + escapeHtml(request.email) + '</td>');
            
            // OTP Code
            row.append('<td><code>' + escapeHtml(request.otp_code) + '</code></td>');
            
            // Status
            const statusColor = request.otp_used ? 'green' : 'orange';
            row.append('<td><span class="sdr-status sdr-status-' + (request.otp_used ? 'verified' : 'pending') + '" style="color: ' + statusColor + ';">' + escapeHtml(request.status_label) + '</span></td>');
            
            // Created At
            row.append('<td>' + escapeHtml(request.created_at) + '</td>');
            
            // Verified At
            row.append('<td>' + (request.verified_at ? escapeHtml(request.verified_at) : '-') + '</td>');
            
            // Actions
            const actionsCell = $('<td class="sdr-actions"></td>');
            const deleteBtn = $('<button type="button" class="btn btn-default sdr-delete-btn" data-request-id="' + request.id + '" title="Delete"><i class="fas fa-trash-alt"></i></button>');
            actionsCell.append(deleteBtn);
            row.append(actionsCell);
            
            tbody.append(row);
        });
    }
    
    function updateCounts(showing, total) {
        $('#sdr-showing-count').text(showing);
        $('#sdr-total-count').text(total);
    }
    
    function showError(message) {
        const tbody = $('#sdr-requests-tbody');
        tbody.empty();
        tbody.append('<tr><td colspan="9" class="sdr-error">' + escapeHtml(message) + '</td></tr>');
    }
    
    function escapeHtml(text) {
        if (text === null || text === undefined) {
            return '';
        }
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
})(jQuery);

