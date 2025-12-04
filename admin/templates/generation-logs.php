<?php
/**
 * Generation Logs Template
 *
 * @package AI_Content_Studio
 * @subpackage Admin/Templates
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$date_range = isset($_GET['date_range']) ? sanitize_text_field($_GET['date_range']) : '30';
$posts_per_page = 20;
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;

// Build query for generation logs
$meta_query = [
    [
        'key' => '_acs_generated',
        'value' => true,
        'compare' => '='
    ]
];

if ($status_filter) {
    $meta_query[] = [
        'key' => '_acs_generation_status',
        'value' => $status_filter,
        'compare' => '='
    ];
}

$date_query = [];
if ($date_range && $date_range !== 'all') {
    $date_query = [
        [
            'after' => $date_range . ' days ago',
            'inclusive' => true
        ]
    ];
}

$query_args = [
    'post_type' => ['post', 'page'],
    'posts_per_page' => $posts_per_page,
    'paged' => $paged,
    'meta_query' => $meta_query,
    'date_query' => $date_query,
    'orderby' => 'date',
    'order' => 'DESC'
];

$logs_query = new WP_Query($query_args);
$logs = $logs_query->posts;

// Get summary statistics
$total_generated = wp_count_posts()->publish + wp_count_posts()->draft;
$success_rate = calculate_success_rate();
$avg_generation_time = get_average_generation_time();
?>

<div class="wrap acs-generation-logs">
    <div class="acs-page-header">
        <h1><?php _e('Generation Logs', 'ai-content-studio'); ?></h1>
        <p class="acs-page-description">
            <?php _e('View detailed logs and history of all content generation activities.', 'ai-content-studio'); ?>
        </p>
    </div>

    <!-- Summary Statistics -->
    <div class="acs-dashboard-grid">
        <div class="acs-card acs-stat-card">
            <h2><?php _e('Total Generated', 'ai-content-studio'); ?></h2>
            <div class="acs-stat-value"><?php echo number_format($total_generated); ?></div>
        </div>

        <div class="acs-card acs-stat-card">
            <h2><?php _e('Success Rate', 'ai-content-studio'); ?></h2>
            <div class="acs-stat-value"><?php echo number_format($success_rate, 1); ?>%</div>
        </div>

        <div class="acs-card acs-stat-card">
            <h2><?php _e('Avg. Generation Time', 'ai-content-studio'); ?></h2>
            <div class="acs-stat-value"><?php echo number_format($avg_generation_time, 1); ?>s</div>
        </div>

        <div class="acs-card acs-stat-card">
            <h2><?php _e('This Month', 'ai-content-studio'); ?></h2>
            <div class="acs-stat-value"><?php echo number_format(get_monthly_generation_count()); ?></div>
        </div>
    </div>

    <!-- Filters -->
    <div class="acs-card acs-logs-filters">
        <h2><?php _e('Filter Logs', 'ai-content-studio'); ?></h2>
        
        <form id="acs-logs-filter-form" class="acs-form" method="get">
            <input type="hidden" name="page" value="acs-generation-logs">
            
            <div class="acs-form-row">
                <div class="acs-form-group">
                    <label for="status-filter"><?php _e('Status:', 'ai-content-studio'); ?></label>
                    <select id="status-filter" name="status" class="acs-select">
                        <option value=""><?php _e('All Statuses', 'ai-content-studio'); ?></option>
                        <option value="success" <?php selected($status_filter, 'success'); ?>><?php _e('Success', 'ai-content-studio'); ?></option>
                        <option value="failed" <?php selected($status_filter, 'failed'); ?>><?php _e('Failed', 'ai-content-studio'); ?></option>
                        <option value="partial" <?php selected($status_filter, 'partial'); ?>><?php _e('Partial', 'ai-content-studio'); ?></option>
                    </select>
                </div>
                
                <div class="acs-form-group">
                    <label for="date-range-filter"><?php _e('Date Range:', 'ai-content-studio'); ?></label>
                    <select id="date-range-filter" name="date_range" class="acs-select">
                        <option value="7" <?php selected($date_range, '7'); ?>><?php _e('Last 7 days', 'ai-content-studio'); ?></option>
                        <option value="30" <?php selected($date_range, '30'); ?>><?php _e('Last 30 days', 'ai-content-studio'); ?></option>
                        <option value="90" <?php selected($date_range, '90'); ?>><?php _e('Last 90 days', 'ai-content-studio'); ?></option>
                        <option value="all" <?php selected($date_range, 'all'); ?>><?php _e('All time', 'ai-content-studio'); ?></option>
                    </select>
                </div>
                
                <div class="acs-form-actions">
                    <button type="submit" class="button button-secondary">
                        <?php _e('Filter', 'ai-content-studio'); ?>
                    </button>
                    <a href="<?php echo admin_url('admin.php?page=acs-generation-logs'); ?>" class="button button-secondary">
                        <?php _e('Clear', 'ai-content-studio'); ?>
                    </a>
                    <button type="button" id="export-logs-csv" class="button button-secondary">
                        <span class="dashicons dashicons-download"></span>
                        <?php _e('Export CSV', 'ai-content-studio'); ?>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Generation Logs List -->
    <div class="acs-card acs-logs-list">
        <div class="acs-card-header">
            <h2><?php _e('Generation History', 'ai-content-studio'); ?></h2>
            <div class="acs-bulk-actions">
                <button id="bulk-retry" class="button button-secondary" disabled>
                    <span class="dashicons dashicons-update"></span>
                    <?php _e('Retry Selected', 'ai-content-studio'); ?>
                </button>
                <button id="bulk-delete" class="button button-secondary" disabled>
                    <span class="dashicons dashicons-trash"></span>
                    <?php _e('Delete Selected', 'ai-content-studio'); ?>
                </button>
            </div>
        </div>
        
        <?php if (!empty($logs)): ?>
            <div class="acs-logs-table">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <td class="manage-column column-cb check-column">
                                <input type="checkbox" id="cb-select-all-logs">
                            </td>
                            <th class="manage-column"><?php _e('Content', 'ai-content-studio'); ?></th>
                            <th class="manage-column"><?php _e('Type', 'ai-content-studio'); ?></th>
                            <th class="manage-column"><?php _e('Status', 'ai-content-studio'); ?></th>
                            <th class="manage-column"><?php _e('Generated', 'ai-content-studio'); ?></th>
                            <th class="manage-column"><?php _e('Duration', 'ai-content-studio'); ?></th>
                            <th class="manage-column"><?php _e('Actions', 'ai-content-studio'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $post): 
                            $generation_report = get_post_meta($post->ID, '_acs_generation_report', true);
                            $generation_status = get_post_meta($post->ID, '_acs_generation_status', true) ?: 'success';
                            $generation_time = get_post_meta($post->ID, '_acs_generation_time', true);
                            $generation_duration = get_post_meta($post->ID, '_acs_generation_duration', true);
                        ?>
                            <tr>
                                <th class="check-column">
                                    <input type="checkbox" name="log_ids[]" value="<?php echo $post->ID; ?>" class="log-checkbox">
                                </th>
                                <td>
                                    <strong>
                                        <a href="<?php echo get_edit_post_link($post->ID); ?>" target="_blank">
                                            <?php echo esc_html($post->post_title ?: __('(No title)', 'ai-content-studio')); ?>
                                        </a>
                                    </strong>
                                    <div class="row-actions">
                                        <span class="edit">
                                            <a href="<?php echo get_edit_post_link($post->ID); ?>" target="_blank">
                                                <?php _e('Edit', 'ai-content-studio'); ?>
                                            </a>
                                        </span>
                                        <?php if ($post->post_status === 'publish'): ?>
                                            | <span class="view">
                                                <a href="<?php echo get_permalink($post->ID); ?>" target="_blank">
                                                    <?php _e('View', 'ai-content-studio'); ?>
                                                </a>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><?php echo ucfirst($post->post_type); ?></td>
                                <td>
                                    <span class="acs-status-badge <?php echo $generation_status; ?>">
                                        <?php echo ucfirst($generation_status); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($generation_time): ?>
                                        <?php echo human_time_diff(strtotime($generation_time), current_time('timestamp')) . ' ' . __('ago', 'ai-content-studio'); ?>
                                    <?php else: ?>
                                        <?php echo human_time_diff(strtotime($post->post_date), current_time('timestamp')) . ' ' . __('ago', 'ai-content-studio'); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($generation_duration): ?>
                                        <?php echo number_format($generation_duration, 1); ?>s
                                    <?php else: ?>
                                        <span class="acs-text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button type="button" class="button button-small view-report-btn" data-post-id="<?php echo $post->ID; ?>">
                                        <?php _e('View Report', 'ai-content-studio'); ?>
                                    </button>
                                    <?php if ($generation_status === 'failed'): ?>
                                        <button type="button" class="button button-small retry-generation-btn" data-post-id="<?php echo $post->ID; ?>">
                                            <?php _e('Retry', 'ai-content-studio'); ?>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($logs_query->max_num_pages > 1): ?>
                <div class="acs-pagination">
                    <?php
                    $pagination_args = [
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'current' => $paged,
                        'total' => $logs_query->max_num_pages,
                        'prev_text' => '&laquo; ' . __('Previous', 'ai-content-studio'),
                        'next_text' => __('Next', 'ai-content-studio') . ' &raquo;'
                    ];
                    echo paginate_links($pagination_args);
                    ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="acs-empty-state">
                <div class="acs-empty-icon">
                    <span class="dashicons dashicons-admin-page"></span>
                </div>
                <h3><?php _e('No generation logs found', 'ai-content-studio'); ?></h3>
                <p><?php _e('No content generation logs match your current filters.', 'ai-content-studio'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=acs-generate'); ?>" class="button button-primary">
                    <?php _e('Generate Content', 'ai-content-studio'); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Generation Report Modal -->
    <div id="acs-generation-report-modal" class="acs-modal" style="display: none;">
        <div class="acs-modal-content acs-modal-large">
            <div class="acs-modal-header">
                <h3><?php _e('Generation Report', 'ai-content-studio'); ?></h3>
                <button type="button" class="acs-modal-close">&times;</button>
            </div>
            <div class="acs-modal-body">
                <div id="acs-report-content">
                    <!-- Report content will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Handle log selection
    $('.log-checkbox').on('change', function() {
        updateBulkButtons();
    });
    
    $('#cb-select-all-logs').on('change', function() {
        $('.log-checkbox').prop('checked', this.checked);
        updateBulkButtons();
    });
    
    function updateBulkButtons() {
        const checkedBoxes = $('.log-checkbox:checked');
        const hasSelection = checkedBoxes.length > 0;
        
        $('#bulk-retry, #bulk-delete').prop('disabled', !hasSelection);
    }
    
    // Handle view report
    $('.view-report-btn').on('click', function() {
        const postId = $(this).data('post-id');
        loadGenerationReport(postId);
    });
    
    function loadGenerationReport(postId) {
        $('#acs-generation-report-modal').show();
        $('#acs-report-content').html('<div class="acs-loading">Loading report...</div>');
        
        $.ajax({
            url: acsAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'acs_get_generation_report',
                post_id: postId,
                nonce: acsAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#acs-report-content').html(response.data);
                } else {
                    $('#acs-report-content').html('<p>Error loading report: ' + (response.data || 'Unknown error') + '</p>');
                }
            },
            error: function() {
                $('#acs-report-content').html('<p>Network error occurred while loading report.</p>');
            }
        });
    }
    
    // Handle retry generation
    $('.retry-generation-btn').on('click', function() {
        const postId = $(this).data('post-id');
        const $button = $(this);
        
        if (confirm('<?php _e('Are you sure you want to retry generation for this post?', 'ai-content-studio'); ?>')) {
            $button.prop('disabled', true).text('<?php _e('Retrying...', 'ai-content-studio'); ?>');
            
            $.ajax({
                url: acsAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'acs_retry_generation',
                    post_id: postId,
                    nonce: acsAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + (response.data || 'Unknown error'));
                        $button.prop('disabled', false).text('<?php _e('Retry', 'ai-content-studio'); ?>');
                    }
                },
                error: function() {
                    alert('<?php _e('Network error occurred.', 'ai-content-studio'); ?>');
                    $button.prop('disabled', false).text('<?php _e('Retry', 'ai-content-studio'); ?>');
                }
            });
        }
    });
    
    // Handle bulk actions
    $('#bulk-retry').on('click', function() {
        const selectedIds = $('.log-checkbox:checked').map(function() {
            return this.value;
        }).get();
        
        if (selectedIds.length === 0) {
            alert('<?php _e('Please select at least one item.', 'ai-content-studio'); ?>');
            return;
        }
        
        if (confirm('<?php _e('Are you sure you want to retry generation for the selected posts?', 'ai-content-studio'); ?>')) {
            performBulkAction('retry', selectedIds);
        }
    });
    
    $('#bulk-delete').on('click', function() {
        const selectedIds = $('.log-checkbox:checked').map(function() {
            return this.value;
        }).get();
        
        if (selectedIds.length === 0) {
            alert('<?php _e('Please select at least one item.', 'ai-content-studio'); ?>');
            return;
        }
        
        if (confirm('<?php _e('Are you sure you want to delete the selected posts? This action cannot be undone.', 'ai-content-studio'); ?>')) {
            performBulkAction('delete', selectedIds);
        }
    });
    
    function performBulkAction(action, postIds) {
        $.ajax({
            url: acsAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'acs_bulk_log_action',
                bulk_action: action,
                post_ids: postIds,
                nonce: acsAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + (response.data || 'Unknown error'));
                }
            },
            error: function() {
                alert('<?php _e('Network error occurred.', 'ai-content-studio'); ?>');
            }
        });
    }
    
    // Handle CSV export
    $('#export-logs-csv').on('click', function() {
        const params = new URLSearchParams(window.location.search);
        params.set('export', 'csv');
        params.set('action', 'acs_export_logs');
        
        const exportUrl = acsAdmin.ajaxUrl + '?' + params.toString();
        window.open(exportUrl, '_blank');
    });
    
    // Close modal
    $('.acs-modal-close').on('click', function() {
        $(this).closest('.acs-modal').hide();
    });
    
    $('.acs-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).hide();
        }
    });
});
</script>

<?php
/**
 * Calculate success rate
 */
function calculate_success_rate() {
    global $wpdb;
    
    $total = $wpdb->get_var("
        SELECT COUNT(*)
        FROM {$wpdb->postmeta}
        WHERE meta_key = '_acs_generated'
        AND meta_value = '1'
    ");
    
    $successful = $wpdb->get_var("
        SELECT COUNT(*)
        FROM {$wpdb->postmeta} pm1
        INNER JOIN {$wpdb->postmeta} pm2 ON pm1.post_id = pm2.post_id
        WHERE pm1.meta_key = '_acs_generated'
        AND pm1.meta_value = '1'
        AND pm2.meta_key = '_acs_generation_status'
        AND pm2.meta_value = 'success'
    ");
    
    return $total > 0 ? ($successful / $total) * 100 : 0;
}

/**
 * Get average generation time
 */
function get_average_generation_time() {
    global $wpdb;
    
    $avg_time = $wpdb->get_var("
        SELECT AVG(CAST(meta_value AS DECIMAL(10,2)))
        FROM {$wpdb->postmeta}
        WHERE meta_key = '_acs_generation_duration'
        AND meta_value != ''
    ");
    
    return $avg_time ?: 0;
}

/**
 * Get monthly generation count
 */
function get_monthly_generation_count() {
    global $wpdb;
    
    $count = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(*)
        FROM {$wpdb->postmeta} pm
        INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
        WHERE pm.meta_key = '_acs_generated'
        AND pm.meta_value = '1'
        AND p.post_date >= %s
    ", date('Y-m-01')));
    
    return $count ?: 0;
}
?>