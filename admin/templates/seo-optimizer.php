<?php
/**
 * SEO Optimizer Main Dashboard Template
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

$integration_layer = $acs_seo_admin->getIntegrationLayer();
$status = $integration_layer->getIntegrationStatus();
$recent_optimizations = $acs_seo_admin->getRecentOptimizations(10);
$stats = $acs_seo_admin->getOptimizationStats();
?>

<div class="wrap acs-seo-optimizer">
    <div class="acs-page-header">
        <h1><?php _e('SEO Optimizer', 'ai-content-studio'); ?></h1>
        <p class="acs-page-description">
            <?php _e('Optimize your content for better search engine rankings with AI-powered SEO analysis and corrections.', 'ai-content-studio'); ?>
        </p>
    </div>

    <!-- Navigation Tabs -->
    <nav class="acs-nav-tabs">
        <a href="<?php echo admin_url('admin.php?page=acs-seo-optimizer'); ?>" class="acs-nav-tab active">
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
        <a href="<?php echo admin_url('admin.php?page=acs-seo-reports'); ?>" class="acs-nav-tab">
            <span class="dashicons dashicons-chart-line"></span>
            <?php _e('Reports', 'ai-content-studio'); ?>
        </a>
    </nav>

    <div class="acs-dashboard-grid">
        <!-- Status Card -->
        <div class="acs-card acs-status-card">
            <h2><?php _e('Optimizer Status', 'ai-content-studio'); ?></h2>
            <?php echo render_status_content($status); ?>
        </div>

        <!-- Statistics Cards -->
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
    </div>

    <!-- Quick Actions -->
    <div class="acs-card acs-quick-actions">
        <h2><?php _e('Quick Actions', 'ai-content-studio'); ?></h2>
        <div class="acs-action-buttons">
            <a href="<?php echo admin_url('admin.php?page=acs-seo-single'); ?>" class="button button-primary button-large">
                <span class="dashicons dashicons-edit"></span>
                <?php _e('Optimize Single Post', 'ai-content-studio'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=acs-seo-bulk'); ?>" class="button button-secondary button-large">
                <span class="dashicons dashicons-admin-tools"></span>
                <?php _e('Bulk Optimize', 'ai-content-studio'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=acs-settings&tab=seo'); ?>" class="button button-secondary button-large">
                <span class="dashicons dashicons-admin-settings"></span>
                <?php _e('SEO Settings', 'ai-content-studio'); ?>
            </a>
        </div>
    </div>

    <!-- Recent Optimizations -->
    <div class="acs-card acs-recent-optimizations">
        <div class="acs-card-header">
            <h2><?php _e('Recent Optimizations', 'ai-content-studio'); ?></h2>
            <a href="<?php echo admin_url('admin.php?page=acs-seo-reports'); ?>" class="button button-secondary">
                <?php _e('View All Reports', 'ai-content-studio'); ?>
            </a>
        </div>
        
        <?php if (!empty($recent_optimizations)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Post', 'ai-content-studio'); ?></th>
                        <th><?php _e('Score', 'ai-content-studio'); ?></th>
                        <th><?php _e('Status', 'ai-content-studio'); ?></th>
                        <th><?php _e('Date', 'ai-content-studio'); ?></th>
                        <th><?php _e('Actions', 'ai-content-studio'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_optimizations as $opt): ?>
                        <tr>
                            <td>
                                <strong><a href="<?php echo get_edit_post_link($opt['post_id']); ?>"><?php echo esc_html($opt['post_title']); ?></a></strong>
                            </td>
                            <td><?php echo number_format($opt['score'], 1); ?>%</td>
                            <td>
                                <span class="acs-status-badge <?php echo $opt['compliant'] ? 'compliant' : 'needs-work'; ?>">
                                    <?php echo $opt['compliant'] ? __('Compliant', 'ai-content-studio') : __('Needs Work', 'ai-content-studio'); ?>
                                </span>
                            </td>
                            <td><?php echo human_time_diff(strtotime($opt['timestamp']), current_time('timestamp')) . ' ' . __('ago', 'ai-content-studio'); ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=acs-seo-reports&post_id=' . $opt['post_id']); ?>" class="button button-small">
                                    <?php _e('View Report', 'ai-content-studio'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="acs-empty-state">
                <div class="acs-empty-icon">
                    <span class="dashicons dashicons-chart-line"></span>
                </div>
                <h3><?php _e('No optimizations yet', 'ai-content-studio'); ?></h3>
                <p><?php _e('Start optimizing your content to see results here.', 'ai-content-studio'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=acs-seo-single'); ?>" class="button button-primary">
                    <?php _e('Optimize Your First Post', 'ai-content-studio'); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
/**
 * Render status content for status card
 */
function render_status_content($status) {
    ob_start();
    ?>
    <div class="acs-status-indicator <?php echo $status['optimizer_enabled'] ? 'active' : 'inactive'; ?>">
        <span class="status-dot"></span>
        <?php echo $status['optimizer_enabled'] ? __('Active', 'ai-content-studio') : __('Inactive', 'ai-content-studio'); ?>
    </div>
    <ul class="acs-status-list">
        <li>
            <strong><?php _e('Mode:', 'ai-content-studio'); ?></strong>
            <?php echo ucfirst($status['integration_mode']); ?>
        </li>
        <li>
            <strong><?php _e('Auto-Optimize:', 'ai-content-studio'); ?></strong>
            <?php echo $status['auto_optimize'] ? __('Enabled', 'ai-content-studio') : __('Disabled', 'ai-content-studio'); ?>
        </li>
        <li>
            <strong><?php _e('Bypass Mode:', 'ai-content-studio'); ?></strong>
            <?php echo $status['bypass_mode'] ? __('Yes', 'ai-content-studio') : __('No', 'ai-content-studio'); ?>
        </li>
    </ul>
    <?php
    return ob_get_clean();
}
?>