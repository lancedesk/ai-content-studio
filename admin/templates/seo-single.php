<?php
/**
 * SEO Single Post Optimization Template
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

// Get post ID from URL parameter
$post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
$selected_post = $post_id ? get_post($post_id) : null;

// Get recent optimization result if exists
$optimization_result = $post_id ? get_post_meta($post_id, '_acs_optimization_result', true) : null;
$last_optimized = $post_id ? get_post_meta($post_id, '_acs_optimization_timestamp', true) : null;

// Get all posts for selection dropdown
$posts = get_posts([
    'post_type' => ['post', 'page'],
    'posts_per_page' => -1,
    'post_status' => ['publish', 'draft', 'private'],
    'orderby' => 'date',
    'order' => 'DESC'
]);
?>

<div class="wrap acs-seo-single">
    <div class="acs-page-header">
        <h1><?php _e('Single Post Optimization', 'ai-content-studio'); ?></h1>
        <p class="acs-page-description">
            <?php _e('Optimize individual posts and pages for better search engine rankings with detailed SEO analysis.', 'ai-content-studio'); ?>
        </p>
    </div>

    <!-- Navigation Tabs -->
    <nav class="acs-nav-tabs">
        <a href="<?php echo admin_url('admin.php?page=acs-seo-optimizer'); ?>" class="acs-nav-tab">
            <span class="dashicons dashicons-dashboard"></span>
            <?php _e('Dashboard', 'ai-content-studio'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=acs-seo-single'); ?>" class="acs-nav-tab active">
            <span class="dashicons dashicons-edit"></span>
            <?php _e('Single Post', 'ai-content-studio'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=acs-seo-bulk'); ?>" class="acs-nav-tab">
            <span class="dashicons dashicons-admin-tools"></span>
            <?php _e('Bulk Optimize', 'ai-content-studio'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=acs-seo-reports'); ?>" class="acs-nav-tab">
            <span class="dashicons dashicons-chart-line"></span>
            <?php _e('Reports', 'ai-content-studio'); ?>
        </a>
    </nav>

    <div class="acs-dashboard-grid">
        <!-- Post Selection Card -->
        <div class="acs-card acs-post-selection">
            <h2><?php _e('Select Post to Optimize', 'ai-content-studio'); ?></h2>
            
            <form id="acs-post-selection-form" class="acs-form">
                <div class="acs-form-group">
                    <label for="post-selector"><?php _e('Choose Post or Page:', 'ai-content-studio'); ?></label>
                    <select id="post-selector" name="post_id" class="acs-select">
                        <option value=""><?php _e('Select a post...', 'ai-content-studio'); ?></option>
                        <?php foreach ($posts as $post): ?>
                            <option value="<?php echo $post->ID; ?>" <?php selected($post_id, $post->ID); ?>>
                                <?php echo esc_html($post->post_title); ?> 
                                (<?php echo ucfirst($post->post_type); ?> - <?php echo ucfirst($post->post_status); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="acs-form-actions">
                    <button type="submit" class="button button-primary">
                        <?php _e('Load Post', 'ai-content-studio'); ?>
                    </button>
                </div>
            </form>
        </div>

        <?php if ($selected_post): ?>
            <!-- Post Information Card -->
            <div class="acs-card acs-post-info-card">
                <h2><?php _e('Post Information', 'ai-content-studio'); ?></h2>
                <?php echo render_post_info_content($selected_post, $last_optimized); ?>
            </div>

            <!-- Optimization Actions Card -->
            <div class="acs-card acs-optimization-actions">
                <h2><?php _e('Optimization Actions', 'ai-content-studio'); ?></h2>
                
                <form id="acs-single-optimization-form" class="acs-form">
                    <?php wp_nonce_field('acs_single_optimization', 'acs_single_nonce'); ?>
                    <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                    
                    <div class="acs-form-group">
                        <label>
                            <input type="checkbox" name="optimize_title" value="1" checked>
                            <?php _e('Optimize Title', 'ai-content-studio'); ?>
                        </label>
                        <p class="description"><?php _e('Improve title for SEO and readability', 'ai-content-studio'); ?></p>
                    </div>
                    
                    <div class="acs-form-group">
                        <label>
                            <input type="checkbox" name="optimize_content" value="1" checked>
                            <?php _e('Optimize Content', 'ai-content-studio'); ?>
                        </label>
                        <p class="description"><?php _e('Enhance content structure and SEO elements', 'ai-content-studio'); ?></p>
                    </div>
                    
                    <div class="acs-form-group">
                        <label>
                            <input type="checkbox" name="optimize_meta" value="1" checked>
                            <?php _e('Optimize Meta Description', 'ai-content-studio'); ?>
                        </label>
                        <p class="description"><?php _e('Generate or improve meta description', 'ai-content-studio'); ?></p>
                    </div>
                    
                    <div class="acs-form-actions">
                        <button type="submit" class="button button-primary button-large" id="optimize-single-btn">
                            <span class="dashicons dashicons-update"></span>
                            <?php _e('Start Optimization', 'ai-content-studio'); ?>
                        </button>
                        
                        <?php if ($optimization_result): ?>
                            <a href="<?php echo admin_url('admin.php?page=acs-seo-reports&post_id=' . $post_id); ?>" class="button button-secondary">
                                <span class="dashicons dashicons-chart-line"></span>
                                <?php _e('View Report', 'ai-content-studio'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <?php if ($optimization_result): ?>
                <!-- Previous Optimization Results -->
                <div class="acs-card acs-optimization-results">
                    <div class="acs-card-header">
                        <h2><?php _e('Previous Optimization Results', 'ai-content-studio'); ?></h2>
                        <span class="acs-timestamp">
                            <?php echo human_time_diff(strtotime($last_optimized), current_time('timestamp')) . ' ' . __('ago', 'ai-content-studio'); ?>
                        </span>
                    </div>
                    
                    <?php echo render_optimization_summary($optimization_result); ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <!-- Empty State -->
            <div class="acs-card acs-empty-state">
                <div class="acs-empty-icon">
                    <span class="dashicons dashicons-edit"></span>
                </div>
                <h3><?php _e('No post selected', 'ai-content-studio'); ?></h3>
                <p><?php _e('Select a post from the dropdown above to begin optimization.', 'ai-content-studio'); ?></p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Optimization Progress Modal -->
    <div id="acs-optimization-modal" class="acs-modal" style="display: none;">
        <div class="acs-modal-content">
            <div class="acs-modal-header">
                <h3><?php _e('Optimizing Post...', 'ai-content-studio'); ?></h3>
            </div>
            <div class="acs-modal-body">
                <div class="acs-progress-bar">
                    <div class="acs-progress-fill" style="width: 0%"></div>
                </div>
                <p id="acs-optimization-status"><?php _e('Starting optimization...', 'ai-content-studio'); ?></p>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Handle post selection form
    $('#acs-post-selection-form').on('submit', function(e) {
        e.preventDefault();
        const postId = $('#post-selector').val();
        if (postId) {
            window.location.href = '<?php echo admin_url('admin.php?page=acs-seo-single'); ?>&post_id=' + postId;
        }
    });
    
    // Handle optimization form
    $('#acs-single-optimization-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'acs_manual_optimize');
        formData.append('nonce', acsAdmin.nonce);
        
        // Show progress modal
        $('#acs-optimization-modal').show();
        $('#optimize-single-btn').prop('disabled', true);
        
        // Start optimization
        $.ajax({
            url: acsAdmin.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#acs-optimization-status').text('<?php _e('Optimization completed successfully!', 'ai-content-studio'); ?>');
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    $('#acs-optimization-status').text(response.data || '<?php _e('Optimization failed. Please try again.', 'ai-content-studio'); ?>');
                }
            },
            error: function() {
                $('#acs-optimization-status').text('<?php _e('An error occurred. Please try again.', 'ai-content-studio'); ?>');
            },
            complete: function() {
                $('#optimize-single-btn').prop('disabled', false);
                setTimeout(function() {
                    $('#acs-optimization-modal').hide();
                }, 3000);
            }
        });
    });
    
    // Close modal on click outside
    $('#acs-optimization-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).hide();
        }
    });
});
</script>

<?php
/**
 * Render post information content
 */
function render_post_info_content($post, $last_optimized) {
    ob_start();
    ?>
    <div class="acs-post-info">
        <div class="acs-post-meta">
            <h3><?php echo esc_html($post->post_title); ?></h3>
            <div class="acs-meta-row">
                <strong><?php _e('Type:', 'ai-content-studio'); ?></strong>
                <?php echo ucfirst($post->post_type); ?>
            </div>
            <div class="acs-meta-row">
                <strong><?php _e('Status:', 'ai-content-studio'); ?></strong>
                <span class="acs-status-badge <?php echo $post->post_status; ?>">
                    <?php echo ucfirst($post->post_status); ?>
                </span>
            </div>
            <div class="acs-meta-row">
                <strong><?php _e('Word Count:', 'ai-content-studio'); ?></strong>
                <?php echo str_word_count(strip_tags($post->post_content)); ?> <?php _e('words', 'ai-content-studio'); ?>
            </div>
            <div class="acs-meta-row">
                <strong><?php _e('Last Modified:', 'ai-content-studio'); ?></strong>
                <?php echo human_time_diff(strtotime($post->post_modified), current_time('timestamp')) . ' ' . __('ago', 'ai-content-studio'); ?>
            </div>
            <?php if ($last_optimized): ?>
                <div class="acs-meta-row">
                    <strong><?php _e('Last Optimized:', 'ai-content-studio'); ?></strong>
                    <?php echo human_time_diff(strtotime($last_optimized), current_time('timestamp')) . ' ' . __('ago', 'ai-content-studio'); ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="acs-post-actions">
            <a href="<?php echo get_edit_post_link($post->ID); ?>" class="button button-secondary" target="_blank">
                <span class="dashicons dashicons-edit"></span>
                <?php _e('Edit Post', 'ai-content-studio'); ?>
            </a>
            <?php if ($post->post_status === 'publish'): ?>
                <a href="<?php echo get_permalink($post->ID); ?>" class="button button-secondary" target="_blank">
                    <span class="dashicons dashicons-external"></span>
                    <?php _e('View Post', 'ai-content-studio'); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render optimization summary
 */
function render_optimization_summary($optimization_result) {
    if (!$optimization_result || !isset($optimization_result['optimizationSummary'])) {
        return '<p>' . __('No optimization data available.', 'ai-content-studio') . '</p>';
    }
    
    $summary = $optimization_result['optimizationSummary'];
    
    ob_start();
    ?>
    <div class="acs-optimization-summary">
        <div class="acs-score-display">
            <div class="acs-score-circle">
                <span class="acs-score-value"><?php echo number_format($summary['finalScore'] ?? 0, 1); ?>%</span>
            </div>
            <div class="acs-score-info">
                <h4><?php _e('SEO Score', 'ai-content-studio'); ?></h4>
                <p class="acs-compliance-status <?php echo ($summary['complianceAchieved'] ?? false) ? 'compliant' : 'needs-work'; ?>">
                    <?php echo ($summary['complianceAchieved'] ?? false) ? __('Compliant', 'ai-content-studio') : __('Needs Work', 'ai-content-studio'); ?>
                </p>
            </div>
        </div>
        
        <?php if (!empty($summary['improvements'])): ?>
            <div class="acs-improvements">
                <h4><?php _e('Improvements Made', 'ai-content-studio'); ?></h4>
                <ul>
                    <?php foreach ($summary['improvements'] as $improvement): ?>
                        <li><?php echo esc_html($improvement); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($summary['recommendations'])): ?>
            <div class="acs-recommendations">
                <h4><?php _e('Recommendations', 'ai-content-studio'); ?></h4>
                <ul>
                    <?php foreach ($summary['recommendations'] as $recommendation): ?>
                        <li><?php echo esc_html($recommendation); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}