<?php
/**
 * Analytics & Reports Template
 *
 * @package AI_Content_Studio
 * @subpackage Admin/Templates
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get analytics data
$generation_stats = get_generation_analytics();
$seo_stats = get_seo_analytics();
$performance_stats = get_performance_analytics();
$date_range = isset($_GET['date_range']) ? sanitize_text_field($_GET['date_range']) : '30';
?>

<div class="wrap acs-analytics">
    <div class="acs-page-header">
        <h1><?php _e('Analytics & Reports', 'ai-content-studio'); ?></h1>
        <p class="acs-page-description">
            <?php _e('Comprehensive analytics and performance insights for your AI Content Studio activities.', 'ai-content-studio'); ?>
        </p>
    </div>

    <!-- Date Range Filter -->
    <div class="acs-card acs-analytics-filters">
        <form id="acs-analytics-filter-form" class="acs-form" method="get">
            <input type="hidden" name="page" value="acs-analytics">
            
            <div class="acs-form-row">
                <div class="acs-form-group">
                    <label for="date-range-filter"><?php _e('Date Range:', 'ai-content-studio'); ?></label>
                    <select id="date-range-filter" name="date_range" class="acs-select">
                        <option value="7" <?php selected($date_range, '7'); ?>><?php _e('Last 7 days', 'ai-content-studio'); ?></option>
                        <option value="30" <?php selected($date_range, '30'); ?>><?php _e('Last 30 days', 'ai-content-studio'); ?></option>
                        <option value="90" <?php selected($date_range, '90'); ?>><?php _e('Last 90 days', 'ai-content-studio'); ?></option>
                        <option value="365" <?php selected($date_range, '365'); ?>><?php _e('Last year', 'ai-content-studio'); ?></option>
                        <option value="all" <?php selected($date_range, 'all'); ?>><?php _e('All time', 'ai-content-studio'); ?></option>
                    </select>
                </div>
                
                <div class="acs-form-actions">
                    <button type="submit" class="button button-secondary">
                        <?php _e('Update', 'ai-content-studio'); ?>
                    </button>
                    <button type="button" id="export-analytics" class="button button-secondary">
                        <span class="dashicons dashicons-download"></span>
                        <?php _e('Export Report', 'ai-content-studio'); ?>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Overview Statistics -->
    <div class="acs-dashboard-grid">
        <div class="acs-card acs-stat-card">
            <h2><?php _e('Content Generated', 'ai-content-studio'); ?></h2>
            <div class="acs-stat-value"><?php echo number_format($generation_stats['total_generated']); ?></div>
            <div class="acs-stat-change <?php echo $generation_stats['change_direction']; ?>">
                <?php echo $generation_stats['change_percentage']; ?>% <?php echo $generation_stats['change_direction']; ?>
            </div>
        </div>

        <div class="acs-card acs-stat-card">
            <h2><?php _e('SEO Optimizations', 'ai-content-studio'); ?></h2>
            <div class="acs-stat-value"><?php echo number_format($seo_stats['total_optimizations']); ?></div>
            <div class="acs-stat-change <?php echo $seo_stats['change_direction']; ?>">
                <?php echo $seo_stats['change_percentage']; ?>% <?php echo $seo_stats['change_direction']; ?>
            </div>
        </div>

        <div class="acs-card acs-stat-card">
            <h2><?php _e('Average SEO Score', 'ai-content-studio'); ?></h2>
            <div class="acs-stat-value"><?php echo number_format($seo_stats['average_score'], 1); ?>%</div>
            <div class="acs-stat-change <?php echo $seo_stats['score_change_direction']; ?>">
                <?php echo $seo_stats['score_change']; ?>% <?php echo $seo_stats['score_change_direction']; ?>
            </div>
        </div>

        <div class="acs-card acs-stat-card">
            <h2><?php _e('Success Rate', 'ai-content-studio'); ?></h2>
            <div class="acs-stat-value"><?php echo number_format($generation_stats['success_rate'], 1); ?>%</div>
            <div class="acs-stat-change <?php echo $generation_stats['success_change_direction']; ?>">
                <?php echo $generation_stats['success_change']; ?>% <?php echo $generation_stats['success_change_direction']; ?>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="acs-analytics-charts">
        <!-- Generation Trends Chart -->
        <div class="acs-card acs-chart-card">
            <div class="acs-card-header">
                <h2><?php _e('Content Generation Trends', 'ai-content-studio'); ?></h2>
                <div class="acs-chart-controls">
                    <button type="button" class="button button-small" data-chart="generation" data-period="daily">
                        <?php _e('Daily', 'ai-content-studio'); ?>
                    </button>
                    <button type="button" class="button button-small button-primary" data-chart="generation" data-period="weekly">
                        <?php _e('Weekly', 'ai-content-studio'); ?>
                    </button>
                    <button type="button" class="button button-small" data-chart="generation" data-period="monthly">
                        <?php _e('Monthly', 'ai-content-studio'); ?>
                    </button>
                </div>
            </div>
            <div class="acs-chart-container">
                <canvas id="generation-trends-chart" width="400" height="200"></canvas>
            </div>
        </div>

        <!-- SEO Performance Chart -->
        <div class="acs-card acs-chart-card">
            <div class="acs-card-header">
                <h2><?php _e('SEO Performance Trends', 'ai-content-studio'); ?></h2>
                <div class="acs-chart-controls">
                    <button type="button" class="button button-small" data-chart="seo" data-period="daily">
                        <?php _e('Daily', 'ai-content-studio'); ?>
                    </button>
                    <button type="button" class="button button-small button-primary" data-chart="seo" data-period="weekly">
                        <?php _e('Weekly', 'ai-content-studio'); ?>
                    </button>
                    <button type="button" class="button button-small" data-chart="seo" data-period="monthly">
                        <?php _e('Monthly', 'ai-content-studio'); ?>
                    </button>
                </div>
            </div>
            <div class="acs-chart-container">
                <canvas id="seo-performance-chart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>

    <!-- Detailed Analytics -->
    <div class="acs-analytics-details">
        <!-- Content Types Breakdown -->
        <div class="acs-card acs-breakdown-card">
            <h2><?php _e('Content Types Breakdown', 'ai-content-studio'); ?></h2>
            <div class="acs-breakdown-chart">
                <canvas id="content-types-chart" width="300" height="300"></canvas>
            </div>
            <div class="acs-breakdown-legend">
                <?php foreach ($generation_stats['content_types'] as $type => $count): ?>
                    <div class="acs-legend-item">
                        <span class="acs-legend-color" style="background-color: <?php echo get_chart_color($type); ?>"></span>
                        <span class="acs-legend-label"><?php echo ucfirst($type); ?></span>
                        <span class="acs-legend-value"><?php echo number_format($count); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Performance Metrics -->
        <div class="acs-card acs-performance-card">
            <h2><?php _e('Performance Metrics', 'ai-content-studio'); ?></h2>
            <div class="acs-metrics-grid">
                <div class="acs-metric-item">
                    <h4><?php _e('Average Generation Time', 'ai-content-studio'); ?></h4>
                    <div class="acs-metric-value"><?php echo number_format($performance_stats['avg_generation_time'], 1); ?>s</div>
                </div>
                <div class="acs-metric-item">
                    <h4><?php _e('Average Word Count', 'ai-content-studio'); ?></h4>
                    <div class="acs-metric-value"><?php echo number_format($performance_stats['avg_word_count']); ?></div>
                </div>
                <div class="acs-metric-item">
                    <h4><?php _e('API Usage', 'ai-content-studio'); ?></h4>
                    <div class="acs-metric-value"><?php echo number_format($performance_stats['api_calls']); ?></div>
                </div>
                <div class="acs-metric-item">
                    <h4><?php _e('Error Rate', 'ai-content-studio'); ?></h4>
                    <div class="acs-metric-value"><?php echo number_format($performance_stats['error_rate'], 2); ?>%</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="acs-card acs-recent-activity">
        <div class="acs-card-header">
            <h2><?php _e('Recent Activity', 'ai-content-studio'); ?></h2>
            <a href="<?php echo admin_url('admin.php?page=acs-generation-logs'); ?>" class="button button-secondary">
                <?php _e('View All Logs', 'ai-content-studio'); ?>
            </a>
        </div>
        
        <?php
        $recent_activities = get_recent_activities(10);
        if (!empty($recent_activities)):
        ?>
            <div class="acs-activity-timeline">
                <?php foreach ($recent_activities as $activity): ?>
                    <div class="acs-activity-item">
                        <div class="acs-activity-icon <?php echo $activity['type']; ?>">
                            <span class="dashicons dashicons-<?php echo get_activity_icon($activity['type']); ?>"></span>
                        </div>
                        <div class="acs-activity-content">
                            <div class="acs-activity-title"><?php echo esc_html($activity['title']); ?></div>
                            <div class="acs-activity-description"><?php echo esc_html($activity['description']); ?></div>
                            <div class="acs-activity-time"><?php echo human_time_diff(strtotime($activity['timestamp']), current_time('timestamp')) . ' ' . __('ago', 'ai-content-studio'); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="acs-empty-state">
                <div class="acs-empty-icon">
                    <span class="dashicons dashicons-chart-line"></span>
                </div>
                <h3><?php _e('No recent activity', 'ai-content-studio'); ?></h3>
                <p><?php _e('Start generating content to see activity here.', 'ai-content-studio'); ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Initialize charts
    initializeCharts();
    
    // Handle chart period changes
    $('.acs-chart-controls button').on('click', function() {
        const $button = $(this);
        const chart = $button.data('chart');
        const period = $button.data('period');
        
        // Update button states
        $button.siblings().removeClass('button-primary');
        $button.addClass('button-primary');
        
        // Update chart
        updateChart(chart, period);
    });
    
    // Handle export
    $('#export-analytics').on('click', function() {
        const dateRange = $('#date-range-filter').val();
        const exportUrl = acsAdmin.ajaxUrl + '?action=acs_export_analytics&date_range=' + dateRange + '&nonce=' + acsAdmin.nonce;
        window.open(exportUrl, '_blank');
    });
});

function initializeCharts() {
    // Initialize generation trends chart
    const generationCtx = document.getElementById('generation-trends-chart').getContext('2d');
    window.generationChart = new Chart(generationCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($generation_stats['chart_labels']); ?>,
            datasets: [{
                label: '<?php _e('Generated Posts', 'ai-content-studio'); ?>',
                data: <?php echo json_encode($generation_stats['chart_data']); ?>,
                borderColor: '#2271b1',
                backgroundColor: 'rgba(34, 113, 177, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    // Initialize SEO performance chart
    const seoCtx = document.getElementById('seo-performance-chart').getContext('2d');
    window.seoChart = new Chart(seoCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($seo_stats['chart_labels']); ?>,
            datasets: [{
                label: '<?php _e('Average SEO Score', 'ai-content-studio'); ?>',
                data: <?php echo json_encode($seo_stats['chart_data']); ?>,
                borderColor: '#00a32a',
                backgroundColor: 'rgba(0, 163, 42, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });
    
    // Initialize content types pie chart
    const contentTypesCtx = document.getElementById('content-types-chart').getContext('2d');
    window.contentTypesChart = new Chart(contentTypesCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_keys($generation_stats['content_types'])); ?>,
            datasets: [{
                data: <?php echo json_encode(array_values($generation_stats['content_types'])); ?>,
                backgroundColor: [
                    '#2271b1',
                    '#00a32a',
                    '#dba617',
                    '#d63638',
                    '#8c8f94'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
}

function updateChart(chartType, period) {
    // AJAX call to get updated chart data
    $.ajax({
        url: acsAdmin.ajaxUrl,
        type: 'POST',
        data: {
            action: 'acs_get_chart_data',
            chart_type: chartType,
            period: period,
            nonce: acsAdmin.nonce
        },
        success: function(response) {
            if (response.success) {
                const chart = chartType === 'generation' ? window.generationChart : window.seoChart;
                chart.data.labels = response.data.labels;
                chart.data.datasets[0].data = response.data.data;
                chart.update();
            }
        }
    });
}
</script>

<?php
/**
 * Get generation analytics data
 */
function get_generation_analytics() {
    global $wpdb;
    
    // Mock data for now - replace with actual database queries
    return [
        'total_generated' => 150,
        'change_percentage' => 12.5,
        'change_direction' => 'up',
        'success_rate' => 94.2,
        'success_change' => 2.1,
        'success_change_direction' => 'up',
        'content_types' => [
            'blog_post' => 85,
            'product_description' => 35,
            'social_media' => 20,
            'email' => 10
        ],
        'chart_labels' => ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
        'chart_data' => [25, 35, 45, 45]
    ];
}

/**
 * Get SEO analytics data
 */
function get_seo_analytics() {
    global $wpdb;
    
    // Mock data for now - replace with actual database queries
    return [
        'total_optimizations' => 120,
        'change_percentage' => 8.3,
        'change_direction' => 'up',
        'average_score' => 78.5,
        'score_change' => 3.2,
        'score_change_direction' => 'up',
        'chart_labels' => ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
        'chart_data' => [72, 75, 77, 78.5]
    ];
}

/**
 * Get performance analytics data
 */
function get_performance_analytics() {
    global $wpdb;
    
    // Mock data for now - replace with actual database queries
    return [
        'avg_generation_time' => 12.3,
        'avg_word_count' => 850,
        'api_calls' => 1250,
        'error_rate' => 2.1
    ];
}

/**
 * Get recent activities
 */
function get_recent_activities($limit = 10) {
    global $wpdb;
    
    // Mock data for now - replace with actual database queries
    return [
        [
            'type' => 'generation',
            'title' => 'Content Generated',
            'description' => 'Blog post "How to Optimize Your Website" was generated successfully',
            'timestamp' => date('Y-m-d H:i:s', strtotime('-2 hours'))
        ],
        [
            'type' => 'optimization',
            'title' => 'SEO Optimization',
            'description' => 'Post "Digital Marketing Tips" was optimized with score 85%',
            'timestamp' => date('Y-m-d H:i:s', strtotime('-4 hours'))
        ],
        [
            'type' => 'error',
            'title' => 'Generation Failed',
            'description' => 'Content generation failed due to API rate limit',
            'timestamp' => date('Y-m-d H:i:s', strtotime('-6 hours'))
        ]
    ];
}

/**
 * Get chart color for content type
 */
function get_chart_color($type) {
    $colors = [
        'blog_post' => '#2271b1',
        'product_description' => '#00a32a',
        'social_media' => '#dba617',
        'email' => '#d63638',
        'default' => '#8c8f94'
    ];
    
    return $colors[$type] ?? $colors['default'];
}

/**
 * Get activity icon
 */
function get_activity_icon($type) {
    $icons = [
        'generation' => 'edit',
        'optimization' => 'chart-line',
        'error' => 'warning',
        'default' => 'admin-generic'
    ];
    
    return $icons[$type] ?? $icons['default'];
}
?>