<?php
/**
 * SEO Reports Template
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

// Get filter parameters
$post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
$post_type = isset($_GET['post_type']) ? sanitize_text_field($_GET['post_type']) : '';
$date_range = isset($_GET['date_range']) ? sanitize_text_field($_GET['date_range']) : '30';
$posts_per_page = 20;
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;

// Build query for optimization reports
$meta_query = [
    [
        'key' => '_acs_optimization_result',
        'compare' => 'EXISTS'
    ]
];

$date_query = [];
if ($date_range && $date_range !== 'all') {
    $date_query = [
        [
            'key' => '_acs_optimization_timestamp',
            'value' => date('Y-m-d H:i:s', strtotime('-' . $date_range . ' days')),
            'compare' => '>='
        ]
    ];
    $meta_query = array_merge($meta_query, $date_query);
}

$query_args = [
    'post_type' => $post_type ?: ['post', 'page'],
    'posts_per_page' => $post_id ? 1 : $posts_per_page,
    'paged' => $paged,
    'meta_query' => $meta_query,
    'orderby' => 'meta_value',
    'meta_key' => '_acs_optimization_timestamp',
    'order' => 'DESC'
];

if ($post_id) {
    $query_args['p'] = $post_id;
}

$reports_query = new WP_Query($query_args);
$reports = $reports_query->posts;

// Get summary statistics
$stats = $acs_seo_admin->getOptimizationStats();
?>

<div class="wrap acs-seo-reports">
    <div class="acs-page-header">
        <h1><?php _e('SEO Optimization Reports', 'ai-content-studio'); ?></h1>
        <p class="acs-page-description">
            <?php _e('View detailed reports and analytics for your SEO optimization activities.', 'ai-content-studio'); ?>
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
        <a href="<?php echo admin_url('admin.php?page=acs-seo-bulk'); ?>" class="acs-nav-tab">
            <span class="dashicons dashicons-admin-tools"></span>
            <?php _e('Bulk Optimize', 'ai-content-studio'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=acs-seo-reports'); ?>" class="acs-nav-tab active">
            <span class="dashicons dashicons-chart-line"></span>
            <?php _e('Reports', 'ai-content-studio'); ?>
        </a>
    </nav>

    <?php if (!$post_id): ?>
        <!-- Summary Statistics -->
        <div class="acs-dashboard-grid">
            <div class="acs-card acs-stat-card">
                <h2><?php _e('Total Optimizations', 'ai-content-studio'); ?></h2>
                <div class="acs-stat-value"><?php echo number_format($stats['total_optimizations']); ?></div>
            </div>

            <div class="acs-card acs-stat-card">
                <h2><?php _e('Average Score', 'ai-content-studio'); ?></h2>
                <div class="acs-stat-value"><?php echo number_format($stats['average_score'], 1); ?>%</div>
            </div>

            <div class="acs-card acs-stat-card">
                <h2><?php _e('Compliance Rate', 'ai-content-studio'); ?></h2>
                <div class="acs-stat-value"><?php echo number_format($stats['compliance_rate'], 1); ?>%</div>
            </div>

            <?php $improvement_rate = calculate_improvement_rate(); ?>
            <div class="acs-card acs-stat-card">
                <h2><?php _e('Improvement Rate', 'ai-content-studio'); ?></h2>
                <div class="acs-stat-value"><?php echo number_format($improvement_rate, 1); ?>%</div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="acs-card acs-reports-filters">
        <h2><?php _e('Filter Reports', 'ai-content-studio'); ?></h2>
        
        <form id="acs-reports-filter-form" class="acs-form" method="get">
            <input type="hidden" name="page" value="acs-seo-reports">
            
            <div class="acs-form-row">
                <div class="acs-form-group">
                    <label for="post-type-filter"><?php _e('Post Type:', 'ai-content-studio'); ?></label>
                    <select id="post-type-filter" name="post_type" class="acs-select">
                        <option value=""><?php _e('All Types', 'ai-content-studio'); ?></option>
                        <?php
                        $post_types = get_post_types(['public' => true], 'objects');
                        foreach ($post_types as $type):
                        ?>
                            <option value="<?php echo $type->name; ?>" <?php selected($post_type, $type->name); ?>>
                                <?php echo $type->label; ?>
                            </option>
                        <?php endforeach; ?>
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
                    <a href="<?php echo admin_url('admin.php?page=acs-seo-reports'); ?>" class="button button-secondary">
                        <?php _e('Clear', 'ai-content-studio'); ?>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Reports List -->
    <div class="acs-card acs-reports-list">
        <div class="acs-card-header">
            <h2><?php _e('Optimization Reports', 'ai-content-studio'); ?></h2>
            <?php if (!empty($reports)): ?>
                <div class="acs-export-actions">
                    <button id="export-reports-csv" class="button button-secondary">
                        <span class="dashicons dashicons-download"></span>
                        <?php _e('Export CSV', 'ai-content-studio'); ?>
                    </button>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($reports)): ?>
            <?php if ($post_id && count($reports) === 1): ?>
                <!-- Single Post Detailed Report -->
                <?php render_detailed_report($reports[0]); ?>
            <?php else: ?>
                <!-- Reports Table -->
                <div class="acs-reports-table">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th class="manage-column"><?php _e('Post', 'ai-content-studio'); ?></th>
                                <th class="manage-column"><?php _e('Type', 'ai-content-studio'); ?></th>
                                <th class="manage-column"><?php _e('SEO Score', 'ai-content-studio'); ?></th>
                                <th class="manage-column"><?php _e('Compliance', 'ai-content-studio'); ?></th>
                                <th class="manage-column"><?php _e('Optimized', 'ai-content-studio'); ?></th>
                                <th class="manage-column"><?php _e('Actions', 'ai-content-studio'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reports as $post): 
                                $optimization_result = get_post_meta($post->ID, '_acs_optimization_result', true);
                                $optimization_timestamp = get_post_meta($post->ID, '_acs_optimization_timestamp', true);
                                $summary = $optimization_result['optimizationSummary'] ?? [];
                                $score = $summary['finalScore'] ?? 0;
                                $compliant = $summary['complianceAchieved'] ?? false;
                            ?>
                                <tr>
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
                                        <span class="acs-score-badge score-<?php echo $score >= 80 ? 'good' : ($score >= 60 ? 'fair' : 'poor'); ?>">
                                            <?php echo number_format($score, 1); ?>%
                                        </span>
                                    </td>
                                    <td>
                                        <span class="acs-status-badge <?php echo $compliant ? 'compliant' : 'needs-work'; ?>">
                                            <?php echo $compliant ? __('Compliant', 'ai-content-studio') : __('Needs Work', 'ai-content-studio'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo human_time_diff(strtotime($optimization_timestamp), current_time('timestamp')) . ' ' . __('ago', 'ai-content-studio'); ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo admin_url('admin.php?page=acs-seo-reports&post_id=' . $post->ID); ?>" class="button button-small">
                                            <?php _e('View Report', 'ai-content-studio'); ?>
                                        </a>
                                        <a href="<?php echo admin_url('admin.php?page=acs-seo-single&post_id=' . $post->ID); ?>" class="button button-small">
                                            <?php _e('Re-optimize', 'ai-content-studio'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($reports_query->max_num_pages > 1): ?>
                    <div class="acs-pagination">
                        <?php
                        $pagination_args = [
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'current' => $paged,
                            'total' => $reports_query->max_num_pages,
                            'prev_text' => '&laquo; ' . __('Previous', 'ai-content-studio'),
                            'next_text' => __('Next', 'ai-content-studio') . ' &raquo;'
                        ];
                        echo paginate_links($pagination_args);
                        ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        <?php else: ?>
            <div class="acs-empty-state">
                <div class="acs-empty-icon">
                    <span class="dashicons dashicons-chart-line"></span>
                </div>
                <h3><?php _e('No reports found', 'ai-content-studio'); ?></h3>
                <p><?php _e('No optimization reports match your current filters.', 'ai-content-studio'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=acs-seo-single'); ?>" class="button button-primary">
                    <?php _e('Start Optimizing', 'ai-content-studio'); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Handle CSV export
    $('#export-reports-csv').on('click', function() {
        const params = new URLSearchParams(window.location.search);
        params.set('export', 'csv');
        params.set('action', 'acs_export_reports');
        
        const exportUrl = acsAdmin.ajaxUrl + '?' + params.toString();
        window.open(exportUrl, '_blank');
    });
});
</script>

<?php
/**
 * Render detailed report for single post
 */
function render_detailed_report($post) {
    $optimization_result = get_post_meta($post->ID, '_acs_optimization_result', true);
    $optimization_timestamp = get_post_meta($post->ID, '_acs_optimization_timestamp', true);
    
    if (!$optimization_result) {
        echo '<p>' . __('No optimization data available for this post.', 'ai-content-studio') . '</p>';
        return;
    }
    
    $summary = $optimization_result['optimizationSummary'] ?? [];
    $details = $optimization_result['optimizationDetails'] ?? [];
    ?>
    
    <div class="acs-detailed-report">
        <!-- Post Header -->
        <div class="acs-report-header">
            <h3><?php echo esc_html($post->post_title); ?></h3>
            <div class="acs-report-meta">
                <span class="acs-meta-item">
                    <strong><?php _e('Type:', 'ai-content-studio'); ?></strong>
                    <?php echo ucfirst($post->post_type); ?>
                </span>
                <span class="acs-meta-item">
                    <strong><?php _e('Optimized:', 'ai-content-studio'); ?></strong>
                    <?php echo human_time_diff(strtotime($optimization_timestamp), current_time('timestamp')) . ' ' . __('ago', 'ai-content-studio'); ?>
                </span>
                <span class="acs-meta-item">
                    <a href="<?php echo get_edit_post_link($post->ID); ?>" class="button button-secondary" target="_blank">
                        <?php _e('Edit Post', 'ai-content-studio'); ?>
                    </a>
                </span>
            </div>
        </div>
        
        <!-- Score Overview -->
        <div class="acs-score-overview">
            <div class="acs-score-circle-large">
                <span class="acs-score-value"><?php echo number_format($summary['finalScore'] ?? 0, 1); ?>%</span>
            </div>
            <div class="acs-score-details">
                <h4><?php _e('SEO Score', 'ai-content-studio'); ?></h4>
                <p class="acs-compliance-status <?php echo ($summary['complianceAchieved'] ?? false) ? 'compliant' : 'needs-work'; ?>">
                    <?php echo ($summary['complianceAchieved'] ?? false) ? __('Compliant', 'ai-content-studio') : __('Needs Work', 'ai-content-studio'); ?>
                </p>
                <?php if (!empty($summary['scoreBreakdown'])): ?>
                    <div class="acs-score-breakdown">
                        <?php foreach ($summary['scoreBreakdown'] as $category => $score): ?>
                            <div class="acs-score-item">
                                <span class="acs-score-label"><?php echo esc_html(ucwords(str_replace('_', ' ', $category))); ?>:</span>
                                <span class="acs-score-value"><?php echo number_format($score, 1); ?>%</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Improvements Made -->
        <?php if (!empty($summary['improvements'])): ?>
            <div class="acs-report-section">
                <h4><?php _e('Improvements Made', 'ai-content-studio'); ?></h4>
                <ul class="acs-improvements-list">
                    <?php foreach ($summary['improvements'] as $improvement): ?>
                        <li class="acs-improvement-item success">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php echo esc_html($improvement); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <!-- Recommendations -->
        <?php if (!empty($summary['recommendations'])): ?>
            <div class="acs-report-section">
                <h4><?php _e('Recommendations', 'ai-content-studio'); ?></h4>
                <ul class="acs-recommendations-list">
                    <?php foreach ($summary['recommendations'] as $recommendation): ?>
                        <li class="acs-recommendation-item">
                            <span class="dashicons dashicons-info"></span>
                            <?php echo esc_html($recommendation); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <!-- Detailed Analysis -->
        <?php if (!empty($details)): ?>
            <div class="acs-report-section">
                <h4><?php _e('Detailed Analysis', 'ai-content-studio'); ?></h4>
                <div class="acs-analysis-details">
                    <?php foreach ($details as $section => $data): ?>
                        <div class="acs-analysis-section">
                            <h5><?php echo esc_html(ucwords(str_replace('_', ' ', $section))); ?></h5>
                            <?php if (is_array($data)): ?>
                                <ul>
                                    <?php foreach ($data as $item): ?>
                                        <li><?php echo esc_html($item); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p><?php echo esc_html($data); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Actions -->
        <div class="acs-report-actions">
            <a href="<?php echo admin_url('admin.php?page=acs-seo-single&post_id=' . $post->ID); ?>" class="button button-primary">
                <span class="dashicons dashicons-update"></span>
                <?php _e('Re-optimize Post', 'ai-content-studio'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=acs-seo-reports'); ?>" class="button button-secondary">
                <?php _e('Back to Reports', 'ai-content-studio'); ?>
            </a>
        </div>
    </div>
    <?php
}

/**
 * Calculate improvement rate
 */
function calculate_improvement_rate() {
    global $wpdb;
    
    // Get posts that have been optimized multiple times
    $results = $wpdb->get_results("
        SELECT post_id, meta_value as result
        FROM {$wpdb->postmeta}
        WHERE meta_key = '_acs_optimization_result'
        ORDER BY post_id, meta_id
    ");
    
    $improvements = 0;
    $total_comparisons = 0;
    $post_scores = [];
    
    foreach ($results as $row) {
        $result = maybe_unserialize($row->result);
        $score = $result['optimizationSummary']['finalScore'] ?? 0;
        
        if (!isset($post_scores[$row->post_id])) {
            $post_scores[$row->post_id] = [];
        }
        $post_scores[$row->post_id][] = $score;
    }
    
    foreach ($post_scores as $scores) {
        if (count($scores) > 1) {
            for ($i = 1; $i < count($scores); $i++) {
                $total_comparisons++;
                if ($scores[$i] > $scores[$i-1]) {
                    $improvements++;
                }
            }
        }
    }
    
    return $total_comparisons > 0 ? ($improvements / $total_comparisons) * 100 : 0;
}
?>