<?php
/**
 * SEO Bulk Optimization Template
 *
 * @package AI_Content_Studio
 * @subpackage Admin/Templates
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get SEO optimizer admin instance
global $acs_seo_admin;
if (!$acs_seo_admin) {
    echo '<div class="wrap"><p>' . __('SEO Optimizer not available.', 'ai-content-studio') . '</p></div>';
    return;
}

// Get posts for bulk optimization
$post_types = get_post_types(['public' => true], 'objects');
$selected_post_type = isset($_GET['post_type']) ? sanitize_text_field($_GET['post_type']) : 'post';
$posts_per_page = 20;
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;

$posts_query = new WP_Query([
    'post_type' => $selected_post_type,
    'posts_per_page' => $posts_per_page,
    'paged' => $paged,
    'post_status' => ['publish', 'draft'],
    'orderby' => 'date',
    'order' => 'DESC'
]);

$posts = $posts_query->posts;
$total_posts = $posts_query->found_posts;
?>

<div class="wrap acs-seo-bulk">
    <div class="acs-page-header">
        <h1><?php _e('Bulk SEO Optimization', 'ai-content-studio'); ?></h1>
        <p class="acs-page-description">
            <?php _e('Optimize multiple posts and pages simultaneously for improved search engine rankings.', 'ai-content-studio'); ?>
        </p>
    </div>

    <!-- Navigation Tabs -->
    <nav class="acs-nav-tabs">
        <a href="<?php echo admin_url('admin.php?page=acs-seo-optimizer'); ?>" class="acs-nav-tab">
            <span class="dashicons dashicons-dashboard"></span>
            <?php _e('Dashboard', 'ai-content-studio'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=acs-seo-single'); ?>" class="acs-nav-tab">
            <span class="dashicons dashicons-edit"></span>
            <?php _e('Single Post', 'ai-content-studio'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=acs-seo-bulk'); ?>" class="acs-nav-tab active">
            <span class="dashicons dashicons-admin-tools"></span>
            <?php _e('Bulk Optimize', 'ai-content-studio'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=acs-seo-reports'); ?>" class="acs-nav-tab">
            <span class="dashicons dashicons-chart-line"></span>
            <?php _e('Reports', 'ai-content-studio'); ?>
        </a>
    </nav>

    <div class="acs-dashboard-grid">
        <!-- Filters Card -->
        <div class="acs-card acs-bulk-filters">
            <h2><?php _e('Filter Posts', 'ai-content-studio'); ?></h2>
            
            <form id="acs-bulk-filter-form" class="acs-form" method="get">
                <input type="hidden" name="page" value="acs-seo-bulk">
                
                <div class="acs-form-row">
                    <div class="acs-form-group">
                        <label for="post-type-filter"><?php _e('Post Type:', 'ai-content-studio'); ?></label>
                        <select id="post-type-filter" name="post_type" class="acs-select">
                            <?php foreach ($post_types as $post_type): ?>
                                <option value="<?php echo $post_type->name; ?>" <?php selected($selected_post_type, $post_type->name); ?>>
                                    <?php echo $post_type->label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="acs-form-actions">
                        <button type="submit" class="button button-secondary">
                            <?php _e('Filter', 'ai-content-studio'); ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Bulk Actions Card -->
        <div class="acs-card acs-bulk-actions">
            <h2><?php _e('Bulk Optimization Settings', 'ai-content-studio'); ?></h2>
            
            <form id="acs-bulk-optimization-form" class="acs-form">
                <?php wp_nonce_field('acs_bulk_optimization', 'acs_bulk_nonce'); ?>
                
                <div class="acs-form-group">
                    <label>
                        <input type="checkbox" name="optimize_title" value="1" checked>
                        <?php _e('Optimize Titles', 'ai-content-studio'); ?>
                    </label>
                </div>
                
                <div class="acs-form-group">
                    <label>
                        <input type="checkbox" name="optimize_content" value="1" checked>
                        <?php _e('Optimize Content', 'ai-content-studio'); ?>
                    </label>
                </div>
                
                <div class="acs-form-group">
                    <label>
                        <input type="checkbox" name="optimize_meta" value="1" checked>
                        <?php _e('Optimize Meta Descriptions', 'ai-content-studio'); ?>
                    </label>
                </div>
                
                <div class="acs-form-group">
                    <label for="batch-size"><?php _e('Batch Size:', 'ai-content-studio'); ?></label>
                    <select id="batch-size" name="batch_size" class="acs-select">
                        <option value="5">5 <?php _e('posts', 'ai-content-studio'); ?></option>
                        <option value="10" selected>10 <?php _e('posts', 'ai-content-studio'); ?></option>
                        <option value="20">20 <?php _e('posts', 'ai-content-studio'); ?></option>
                    </select>
                    <p class="description"><?php _e('Number of posts to optimize in each batch', 'ai-content-studio'); ?></p>
                </div>
            </form>
        </div>
    </div>

    <!-- Posts Selection -->
    <div class="acs-card acs-posts-selection">
        <div class="acs-card-header">
            <h2><?php _e('Select Posts to Optimize', 'ai-content-studio'); ?></h2>
            <div class="acs-bulk-controls">
                <button id="select-all-posts" class="button button-secondary">
                    <?php _e('Select All', 'ai-content-studio'); ?>
                </button>
                <button id="deselect-all-posts" class="button button-secondary">
                    <?php _e('Deselect All', 'ai-content-studio'); ?>
                </button>
                <button id="start-bulk-optimization" class="button button-primary" disabled>
                    <span class="dashicons dashicons-update"></span>
                    <?php _e('Start Bulk Optimization', 'ai-content-studio'); ?>
                </button>
            </div>
        </div>
        
        <?php if (!empty($posts)): ?>
            <div class="acs-posts-table">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <td class="manage-column column-cb check-column">
                                <input type="checkbox" id="cb-select-all">
                            </td>
                            <th class="manage-column"><?php _e('Title', 'ai-content-studio'); ?></th>
                            <th class="manage-column"><?php _e('Status', 'ai-content-studio'); ?></th>
                            <th class="manage-column"><?php _e('Last Optimized', 'ai-content-studio'); ?></th>
                            <th class="manage-column"><?php _e('SEO Score', 'ai-content-studio'); ?></th>
                            <th class="manage-column"><?php _e('Actions', 'ai-content-studio'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($posts as $post): 
                            $last_optimized = get_post_meta($post->ID, '_acs_optimization_timestamp', true);
                            $optimization_result = get_post_meta($post->ID, '_acs_optimization_result', true);
                            $seo_score = $optimization_result ? ($optimization_result['optimizationSummary']['finalScore'] ?? 0) : 0;
                        ?>
                            <tr>
                                <th class="check-column">
                                    <input type="checkbox" name="post_ids[]" value="<?php echo $post->ID; ?>" class="post-checkbox">
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
                                <td>
                                    <span class="acs-status-badge <?php echo $post->post_status; ?>">
                                        <?php echo ucfirst($post->post_status); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($last_optimized): ?>
                                        <?php echo human_time_diff(strtotime($last_optimized), current_time('timestamp')) . ' ' . __('ago', 'ai-content-studio'); ?>
                                    <?php else: ?>
                                        <span class="acs-text-muted"><?php _e('Never', 'ai-content-studio'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($seo_score > 0): ?>
                                        <span class="acs-score-badge score-<?php echo $seo_score >= 80 ? 'good' : ($seo_score >= 60 ? 'fair' : 'poor'); ?>">
                                            <?php echo number_format($seo_score, 1); ?>%
                                        </span>
                                    <?php else: ?>
                                        <span class="acs-text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=acs-seo-single&post_id=' . $post->ID); ?>" class="button button-small">
                                        <?php _e('Optimize', 'ai-content-studio'); ?>
                                    </a>
                                    <?php if ($optimization_result): ?>
                                        <a href="<?php echo admin_url('admin.php?page=acs-seo-reports&post_id=' . $post->ID); ?>" class="button button-small">
                                            <?php _e('Report', 'ai-content-studio'); ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($posts_query->max_num_pages > 1): ?>
                <div class="acs-pagination">
                    <?php
                    $pagination_args = [
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'current' => $paged,
                        'total' => $posts_query->max_num_pages,
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
                    <span class="dashicons dashicons-admin-tools"></span>
                </div>
                <h3><?php _e('No posts found', 'ai-content-studio'); ?></h3>
                <p><?php _e('No posts available for optimization with the current filters.', 'ai-content-studio'); ?></p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bulk Optimization Progress Modal -->
    <div id="acs-bulk-optimization-modal" class="acs-modal" style="display: none;">
        <div class="acs-modal-content acs-modal-large">
            <div class="acs-modal-header">
                <h3><?php _e('Bulk Optimization Progress', 'ai-content-studio'); ?></h3>
                <button type="button" class="acs-modal-close" id="close-bulk-modal">&times;</button>
            </div>
            <div class="acs-modal-body">
                <div class="acs-progress-container">
                    <div class="acs-progress-bar">
                        <div class="acs-progress-fill" style="width: 0%"></div>
                    </div>
                    <div class="acs-progress-text">
                        <span id="acs-progress-current">0</span> / <span id="acs-progress-total">0</span> <?php _e('posts optimized', 'ai-content-studio'); ?>
                    </div>
                </div>
                
                <div id="acs-bulk-status" class="acs-bulk-status">
                    <?php _e('Preparing bulk optimization...', 'ai-content-studio'); ?>
                </div>
                
                <div id="acs-bulk-results" class="acs-bulk-results" style="display: none;">
                    <h4><?php _e('Optimization Results', 'ai-content-studio'); ?></h4>
                    <div id="acs-results-list"></div>
                </div>
                
                <div class="acs-modal-actions">
                    <button type="button" id="pause-bulk-optimization" class="button button-secondary" style="display: none;">
                        <?php _e('Pause', 'ai-content-studio'); ?>
                    </button>
                    <button type="button" id="cancel-bulk-optimization" class="button button-secondary">
                        <?php _e('Cancel', 'ai-content-studio'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    let bulkOptimizationInProgress = false;
    let selectedPosts = [];
    
    // Handle post selection
    $('.post-checkbox').on('change', function() {
        updateBulkButton();
    });
    
    $('#cb-select-all').on('change', function() {
        $('.post-checkbox').prop('checked', this.checked);
        updateBulkButton();
    });
    
    $('#select-all-posts').on('click', function() {
        $('.post-checkbox').prop('checked', true);
        $('#cb-select-all').prop('checked', true);
        updateBulkButton();
    });
    
    $('#deselect-all-posts').on('click', function() {
        $('.post-checkbox').prop('checked', false);
        $('#cb-select-all').prop('checked', false);
        updateBulkButton();
    });
    
    function updateBulkButton() {
        const checkedBoxes = $('.post-checkbox:checked');
        $('#start-bulk-optimization').prop('disabled', checkedBoxes.length === 0);
        
        if (checkedBoxes.length > 0) {
            $('#start-bulk-optimization').text('<?php _e('Optimize', 'ai-content-studio'); ?> ' + checkedBoxes.length + ' <?php _e('Posts', 'ai-content-studio'); ?>');
        } else {
            $('#start-bulk-optimization').text('<?php _e('Start Bulk Optimization', 'ai-content-studio'); ?>');
        }
    }
    
    // Handle bulk optimization
    $('#start-bulk-optimization').on('click', function() {
        selectedPosts = $('.post-checkbox:checked').map(function() {
            return this.value;
        }).get();
        
        if (selectedPosts.length === 0) {
            alert('<?php _e('Please select at least one post to optimize.', 'ai-content-studio'); ?>');
            return;
        }
        
        startBulkOptimization();
    });
    
    function startBulkOptimization() {
        bulkOptimizationInProgress = true;
        
        // Show modal
        $('#acs-bulk-optimization-modal').show();
        $('#acs-progress-total').text(selectedPosts.length);
        $('#acs-progress-current').text(0);
        $('#pause-bulk-optimization').show();
        
        // Start optimization process
        optimizeNextBatch(0);
    }
    
    function optimizeNextBatch(startIndex) {
        if (!bulkOptimizationInProgress || startIndex >= selectedPosts.length) {
            completeBulkOptimization();
            return;
        }
        
        const batchSize = parseInt($('#batch-size').val()) || 10;
        const batch = selectedPosts.slice(startIndex, startIndex + batchSize);
        
        $('#acs-bulk-status').text('<?php _e('Optimizing posts', 'ai-content-studio'); ?> ' + (startIndex + 1) + '-' + Math.min(startIndex + batchSize, selectedPosts.length) + '...');
        
        $.ajax({
            url: acsAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'acs_bulk_optimize',
                nonce: acsAdmin.nonce,
                post_ids: batch,
                optimize_title: $('#acs-bulk-optimization-form input[name="optimize_title"]').is(':checked') ? 1 : 0,
                optimize_content: $('#acs-bulk-optimization-form input[name="optimize_content"]').is(':checked') ? 1 : 0,
                optimize_meta: $('#acs-bulk-optimization-form input[name="optimize_meta"]').is(':checked') ? 1 : 0
            },
            success: function(response) {
                if (response.success) {
                    updateProgress(startIndex + batch.length);
                    
                    // Add results to display
                    if (response.data && response.data.results) {
                        displayBatchResults(response.data.results);
                    }
                    
                    // Continue with next batch
                    setTimeout(function() {
                        optimizeNextBatch(startIndex + batchSize);
                    }, 1000);
                } else {
                    $('#acs-bulk-status').text('<?php _e('Error occurred during optimization. Please try again.', 'ai-content-studio'); ?>');
                    bulkOptimizationInProgress = false;
                }
            },
            error: function() {
                $('#acs-bulk-status').text('<?php _e('Network error occurred. Please try again.', 'ai-content-studio'); ?>');
                bulkOptimizationInProgress = false;
            }
        });
    }
    
    function updateProgress(completed) {
        const percentage = (completed / selectedPosts.length) * 100;
        $('.acs-progress-fill').css('width', percentage + '%');
        $('#acs-progress-current').text(completed);
    }
    
    function displayBatchResults(results) {
        $('#acs-bulk-results').show();
        
        results.forEach(function(result) {
            const resultHtml = '<div class="acs-result-item ' + (result.success ? 'success' : 'error') + '">' +
                '<strong>' + result.post_title + '</strong>: ' + result.message +
                '</div>';
            $('#acs-results-list').append(resultHtml);
        });
    }
    
    function completeBulkOptimization() {
        bulkOptimizationInProgress = false;
        $('#acs-bulk-status').text('<?php _e('Bulk optimization completed!', 'ai-content-studio'); ?>');
        $('#pause-bulk-optimization').hide();
        $('#cancel-bulk-optimization').text('<?php _e('Close', 'ai-content-studio'); ?>');
        
        // Refresh page after delay
        setTimeout(function() {
            location.reload();
        }, 3000);
    }
    
    // Handle modal controls
    $('#pause-bulk-optimization').on('click', function() {
        bulkOptimizationInProgress = false;
        $(this).hide();
        $('#acs-bulk-status').text('<?php _e('Optimization paused.', 'ai-content-studio'); ?>');
    });
    
    $('#cancel-bulk-optimization, #close-bulk-modal').on('click', function() {
        bulkOptimizationInProgress = false;
        $('#acs-bulk-optimization-modal').hide();
    });
    
    // Close modal on click outside
    $('#acs-bulk-optimization-modal').on('click', function(e) {
        if (e.target === this) {
            bulkOptimizationInProgress = false;
            $(this).hide();
        }
    });
});
</script>