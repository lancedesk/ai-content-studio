/**
 * SEO Optimizer Admin JavaScript
 *
 * Handles admin interface interactions for the multi-pass SEO optimizer
 *
 * @package AI_Content_Studio
 * @subpackage Admin
 */

(function($) {
    'use strict';
    
    /**
     * SEO Optimizer Admin Object
     */
    const ACSOptimizer = {
        
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.initializeDashboard();
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Manual optimization button
            $(document).on('click', '#acs-manual-optimize', this.handleManualOptimization.bind(this));
            
            // Bulk optimization
            $(document).on('submit', '#acs-bulk-optimize-form', this.handleBulkOptimization.bind(this));
            $(document).on('change', '#select-all-posts', this.toggleAllPosts.bind(this));
            $(document).on('change', '.acs-post-checkbox', this.updateSelectedCount.bind(this));
            
            // Settings save
            $(document).on('click', '#acs-save-settings', this.saveSettings.bind(this));
            
            // Filters
            $(document).on('change', '#acs-post-type-filter, #acs-status-filter', this.filterPosts.bind(this));
        },
        
        /**
         * Initialize dashboard
         */
        initializeDashboard: function() {
            this.updateSelectedCount();
            this.loadDashboardStats();
        },
        
        /**
         * Handle manual optimization
         */
        handleManualOptimization: function(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const postId = $button.data('post-id');
            
            if (!postId) {
                this.showNotice('error', acsOptimizer.strings.error);
                return;
            }
            
            // Disable button and show progress
            $button.prop('disabled', true).text(acsOptimizer.strings.optimizing);
            $('#acs-optimization-progress').show();
            this.updateProgress(0, 'Starting optimization...');
            
            // Send AJAX request
            $.ajax({
                url: acsOptimizer.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'acs_manual_optimize',
                    nonce: acsOptimizer.nonce,
                    post_id: postId
                },
                success: (response) => {
                    if (response.success) {
                        this.updateProgress(100, acsOptimizer.strings.success);
                        
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        this.showNotice('error', response.data.message || acsOptimizer.strings.error);
                        $button.prop('disabled', false).text('Optimize Now');
                        $('#acs-optimization-progress').hide();
                    }
                },
                error: (xhr, status, error) => {
                    this.showNotice('error', acsOptimizer.strings.error + ': ' + error);
                    $button.prop('disabled', false).text('Optimize Now');
                    $('#acs-optimization-progress').hide();
                }
            });
        },
        
        /**
         * Handle bulk optimization
         */
        handleBulkOptimization: function(e) {
            e.preventDefault();
            
            const $form = $(e.currentTarget);
            const postIds = [];
            
            $form.find('.acs-post-checkbox:checked').each(function() {
                postIds.push($(this).val());
            });
            
            if (postIds.length === 0) {
                this.showNotice('error', 'Please select at least one post to optimize.');
                return;
            }
            
            if (!confirm(acsOptimizer.strings.confirm_bulk)) {
                return;
            }
            
            // Show progress
            $('#acs-bulk-progress').show();
            $('#acs-start-bulk-optimize').prop('disabled', true);
            
            this.processBulkOptimization(postIds, 0);
        },
        
        /**
         * Process bulk optimization
         */
        processBulkOptimization: function(postIds, index) {
            if (index >= postIds.length) {
                this.updateProgress(100, 'Bulk optimization completed!');
                setTimeout(() => {
                    location.reload();
                }, 2000);
                return;
            }
            
            const progress = Math.round((index / postIds.length) * 100);
            this.updateProgress(progress, `Optimizing post ${index + 1} of ${postIds.length}...`);
            
            $.ajax({
                url: acsOptimizer.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'acs_manual_optimize',
                    nonce: acsOptimizer.nonce,
                    post_id: postIds[index]
                },
                success: (response) => {
                    const status = response.success ? '✓' : '✗';
                    const message = response.success ? 'Success' : (response.data.message || 'Failed');
                    
                    this.logBulkProgress(`${status} Post ID ${postIds[index]}: ${message}`);
                    
                    // Process next post
                    setTimeout(() => {
                        this.processBulkOptimization(postIds, index + 1);
                    }, 500);
                },
                error: (xhr, status, error) => {
                    this.logBulkProgress(`✗ Post ID ${postIds[index]}: Error - ${error}`);
                    
                    // Continue with next post
                    setTimeout(() => {
                        this.processBulkOptimization(postIds, index + 1);
                    }, 500);
                }
            });
        },
        
        /**
         * Toggle all posts
         */
        toggleAllPosts: function(e) {
            const checked = $(e.currentTarget).prop('checked');
            $('.acs-post-checkbox').prop('checked', checked);
            this.updateSelectedCount();
        },
        
        /**
         * Update selected count
         */
        updateSelectedCount: function() {
            const count = $('.acs-post-checkbox:checked').length;
            $('.acs-selected-count').text(count + ' posts selected');
        },
        
        /**
         * Filter posts
         */
        filterPosts: function() {
            const postType = $('#acs-post-type-filter').val();
            const status = $('#acs-status-filter').val();
            
            $('table tbody tr').each(function() {
                const $row = $(this);
                let show = true;
                
                if (postType && $row.data('post-type') !== postType) {
                    show = false;
                }
                
                if (status && $row.data('status') !== status) {
                    show = false;
                }
                
                $row.toggle(show);
            });
        },
        
        /**
         * Save settings
         */
        saveSettings: function(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            $button.prop('disabled', true).text('Saving...');
            
            $.ajax({
                url: acsOptimizer.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'acs_save_optimizer_settings',
                    nonce: acsOptimizer.nonce,
                    settings: $('#acs-settings-form').serialize()
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotice('success', response.data.message);
                    } else {
                        this.showNotice('error', response.data.message);
                    }
                    $button.prop('disabled', false).text('Save Settings');
                },
                error: (xhr, status, error) => {
                    this.showNotice('error', 'Error saving settings: ' + error);
                    $button.prop('disabled', false).text('Save Settings');
                }
            });
        },
        
        /**
         * Load dashboard stats
         */
        loadDashboardStats: function() {
            // Refresh dashboard stats if on dashboard page
            if ($('.acs-optimizer-dashboard').length === 0) {
                return;
            }
            
            // Could add real-time stats updates here
        },
        
        /**
         * Update progress bar
         */
        updateProgress: function(percent, message) {
            $('.acs-progress-fill').css('width', percent + '%');
            $('.acs-progress-text').text(message);
        },
        
        /**
         * Log bulk progress
         */
        logBulkProgress: function(message) {
            const $log = $('.acs-progress-log');
            $log.append('<div class="acs-log-entry">' + message + '</div>');
            $log.scrollTop($log[0].scrollHeight);
        },
        
        /**
         * Show notice
         */
        showNotice: function(type, message) {
            const $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            
            $('.wrap').prepend($notice);
            
            setTimeout(() => {
                $notice.fadeOut(() => {
                    $notice.remove();
                });
            }, 5000);
        }
    };
    
    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        ACSOptimizer.init();
    });
    
})(jQuery);
