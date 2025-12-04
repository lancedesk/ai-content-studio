<?php
/**
 * Unified Dashboard Template
 *
 * @package AI_Content_Studio
 * @subpackage Admin/Templates
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get dashboard data
$stats = $stats ?? [];
$recent_posts = $recent_posts ?? [];
$seo_stats = $seo_stats ?? [];

?>
<div class="wrap acs-admin-page" role="main">
    <!-- Skip link for keyboard navigation -->
    <a class="screen-reader-text skip-link" href="#main-content"><?php esc_html_e('Skip to main content', 'ai-content-studio'); ?></a>
    
    <header class="acs-page-header">
        <h1 id="main-heading"><?php esc_html_e('AI Content Studio Dashboard', 'ai-content-studio'); ?></h1>
    </header>
    
    <main id="main-content" aria-labelledby="main-heading">
    
    <?php
    // Render breadcrumbs
    if (isset($this->component_renderer)) {
        echo $this->component_renderer->render_breadcrumbs([
            ['label' => __('AI Content Studio', 'ai-content-studio'), 'url' => admin_url('admin.php?page=acs-dashboard')],
            ['label' => __('Dashboard', 'ai-content-studio')]
        ]);
    }
    ?>
    
    <!-- Statistics Cards -->
    <section class="acs-dashboard-section" aria-labelledby="stats-heading">
        <h2 id="stats-heading" class="screen-reader-text"><?php esc_html_e('Statistics Overview', 'ai-content-studio'); ?></h2>
        <div class="acs-dashboard-grid acs-dashboard-grid--4-col" role="group" aria-label="<?php esc_attr_e('Key statistics', 'ai-content-studio'); ?>">
        <?php
        // Total Posts Card with icon
        if (isset($this->component_renderer)) {
            echo $this->component_renderer->render_card([
                'variant' => 'stat',
                'content' => '<div class="acs-stat-icon"><span class="dashicons dashicons-edit-large"></span></div>
                             <div class="acs-stat-value">' . number_format($stats['total_posts'] ?? 0) . '</div>
                             <div class="acs-stat-label">' . esc_html__('Generated Posts', 'ai-content-studio') . '</div>'
            ]);
            
            // Total Optimizations Card with icon
            echo $this->component_renderer->render_card([
                'variant' => 'stat',
                'content' => '<div class="acs-stat-icon"><span class="dashicons dashicons-chart-line"></span></div>
                             <div class="acs-stat-value">' . number_format($stats['total_optimizations'] ?? 0) . '</div>
                             <div class="acs-stat-label">' . esc_html__('SEO Optimizations', 'ai-content-studio') . '</div>'
            ]);
            
            // Average SEO Score Card with visual indicator
            $score = $stats['avg_seo_score'] ?? 0;
            $score_color = $score >= 80 ? 'var(--acs-color-success)' : ($score >= 60 ? 'var(--acs-color-warning)' : 'var(--acs-color-error)');
            echo $this->component_renderer->render_card([
                'variant' => 'stat',
                'content' => '<div class="acs-stat-icon"><span class="dashicons dashicons-star-filled"></span></div>
                             <div class="acs-stat-value" style="color: ' . $score_color . '">' . number_format($score, 1) . '%</div>
                             <div class="acs-stat-label">' . esc_html__('Average SEO Score', 'ai-content-studio') . '</div>'
            ]);
            
            // Compliance Rate Card with visual indicator
            $compliance = $stats['compliance_rate'] ?? 0;
            $compliance_color = $compliance >= 80 ? 'var(--acs-color-success)' : ($compliance >= 60 ? 'var(--acs-color-warning)' : 'var(--acs-color-error)');
            echo $this->component_renderer->render_card([
                'variant' => 'stat',
                'content' => '<div class="acs-stat-icon"><span class="dashicons dashicons-yes-alt"></span></div>
                             <div class="acs-stat-value" style="color: ' . $compliance_color . '">' . number_format($compliance, 1) . '%</div>
                             <div class="acs-stat-label">' . esc_html__('Compliance Rate', 'ai-content-studio') . '</div>'
            ]);
        } else {
            // Fallback without component renderer
            ?>
            <div class="acs-card acs-card--stat">
                <div class="acs-card__content">
                    <div class="acs-stat-icon"><span class="dashicons dashicons-edit-large"></span></div>
                    <div class="acs-stat-value"><?php echo number_format($stats['total_posts'] ?? 0); ?></div>
                    <div class="acs-stat-label"><?php esc_html_e('Generated Posts', 'ai-content-studio'); ?></div>
                </div>
            </div>
            
            <div class="acs-card acs-card--stat">
                <div class="acs-card__content">
                    <div class="acs-stat-icon"><span class="dashicons dashicons-chart-line"></span></div>
                    <div class="acs-stat-value"><?php echo number_format($stats['total_optimizations'] ?? 0); ?></div>
                    <div class="acs-stat-label"><?php esc_html_e('SEO Optimizations', 'ai-content-studio'); ?></div>
                </div>
            </div>
            
            <div class="acs-card acs-card--stat">
                <div class="acs-card__content">
                    <?php
                    $score = $stats['avg_seo_score'] ?? 0;
                    $score_color = $score >= 80 ? 'var(--acs-color-success)' : ($score >= 60 ? 'var(--acs-color-warning)' : 'var(--acs-color-error)');
                    ?>
                    <div class="acs-stat-icon"><span class="dashicons dashicons-star-filled"></span></div>
                    <div class="acs-stat-value" style="color: <?php echo $score_color; ?>"><?php echo number_format($score, 1); ?>%</div>
                    <div class="acs-stat-label"><?php esc_html_e('Average SEO Score', 'ai-content-studio'); ?></div>
                </div>
            </div>
            
            <div class="acs-card acs-card--stat">
                <div class="acs-card__content">
                    <?php
                    $compliance = $stats['compliance_rate'] ?? 0;
                    $compliance_color = $compliance >= 80 ? 'var(--acs-color-success)' : ($compliance >= 60 ? 'var(--acs-color-warning)' : 'var(--acs-color-error)');
                    ?>
                    <div class="acs-stat-icon"><span class="dashicons dashicons-yes-alt"></span></div>
                    <div class="acs-stat-value" style="color: <?php echo $compliance_color; ?>"><?php echo number_format($compliance, 1); ?>%</div>
                    <div class="acs-stat-label"><?php esc_html_e('Compliance Rate', 'ai-content-studio'); ?></div>
                </div>
            </div>
            <?php
        }
        ?>
        </div>
    </section>
    
    <!-- Quick Actions and Recent Activity -->
    <section class="acs-dashboard-section" aria-labelledby="actions-activity-heading">
        <h2 id="actions-activity-heading" class="screen-reader-text"><?php esc_html_e('Quick Actions and Recent Activity', 'ai-content-studio'); ?></h2>
        <div class="acs-dashboard-grid acs-dashboard-grid--2-col">
        <!-- Quick Actions Card -->
        <article class="acs-card" role="region" aria-labelledby="quick-actions-heading">
            <div class="acs-card__header">
                <h3 id="quick-actions-heading" class="acs-card__title">
                    <span class="dashicons dashicons-admin-tools" aria-hidden="true" style="margin-right: var(--acs-spacing-xs);"></span>
                    <?php esc_html_e('Quick Actions', 'ai-content-studio'); ?>
                </h3>
            </div>
            <div class="acs-card__content">
                <div class="acs-quick-actions-grid">
                    <?php
                    $quick_actions = [
                        [
                            'text' => __('Generate New Content', 'ai-content-studio'),
                            'url' => admin_url('admin.php?page=acs-generate'),
                            'variant' => 'primary',
                            'icon' => 'edit',
                            'size' => 'large'
                        ],
                        [
                            'text' => __('SEO Optimizer', 'ai-content-studio'),
                            'url' => admin_url('admin.php?page=acs-seo-optimizer'),
                            'variant' => 'secondary',
                            'icon' => 'chart-line',
                            'size' => 'large'
                        ],
                        [
                            'text' => __('View Analytics', 'ai-content-studio'),
                            'url' => admin_url('admin.php?page=acs-analytics'),
                            'variant' => 'secondary',
                            'icon' => 'chart-bar',
                            'size' => 'large'
                        ],
                        [
                            'text' => __('Settings', 'ai-content-studio'),
                            'url' => admin_url('admin.php?page=acs-settings'),
                            'variant' => 'secondary',
                            'icon' => 'admin-settings',
                            'size' => 'large'
                        ]
                    ];
                    
                    if (isset($this->component_renderer)) {
                        foreach ($quick_actions as $action) {
                            echo $this->component_renderer->render_button($action);
                        }
                    } else {
                        // Fallback buttons
                        foreach ($quick_actions as $action) {
                            $class = 'button button-large';
                            if ($action['variant'] === 'primary') {
                                $class .= ' button-primary';
                            }
                            echo '<a href="' . esc_url($action['url']) . '" class="' . esc_attr($class) . '" style="width: 100%; justify-content: center;">';
                            if (!empty($action['icon'])) {
                                echo '<span class="dashicons dashicons-' . esc_attr($action['icon']) . '" style="margin-right: 8px;"></span>';
                            }
                            echo esc_html($action['text']);
                            echo '</a>';
                        }
                    }
                    ?>
                </div>
            </div>
        </article>
        
        <!-- Recent Posts Card -->
        <article class="acs-card" role="region" aria-labelledby="recent-posts-heading">
            <div class="acs-card__header">
                <h3 id="recent-posts-heading" class="acs-card__title">
                    <span class="dashicons dashicons-clock" aria-hidden="true" style="margin-right: var(--acs-spacing-xs);"></span>
                    <?php esc_html_e('Recent Generated Posts', 'ai-content-studio'); ?>
                </h3>
            </div>
            <div class="acs-card__content">
                <?php if (!empty($recent_posts)) : ?>
                    <ul class="acs-recent-posts-list" role="list" aria-label="<?php esc_attr_e('Recent generated posts', 'ai-content-studio'); ?>">
                        <?php foreach (array_slice($recent_posts, 0, 5) as $post) : ?>
                            <li class="acs-recent-post-item" role="listitem">
                                <div class="acs-recent-post-content">
                                    <div class="acs-recent-post-title">
                                        <a href="<?php echo esc_url(get_edit_post_link($post->ID)); ?>" 
                                           class="acs-recent-post-link"
                                           aria-describedby="post-meta-<?php echo esc_attr($post->ID); ?>">
                                            <?php echo esc_html($post->post_title); ?>
                                        </a>
                                    </div>
                                    <div id="post-meta-<?php echo esc_attr($post->ID); ?>" class="acs-recent-post-meta">
                                        <span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span>
                                        <time datetime="<?php echo esc_attr($post->post_date); ?>">
                                            <?php echo human_time_diff(strtotime($post->post_date), current_time('timestamp')); ?> <?php esc_html_e('ago', 'ai-content-studio'); ?>
                                        </time>
                                    </div>
                                </div>
                                <div class="acs-recent-post-status">
                                    <?php
                                    $status_colors = [
                                        'draft' => 'warning',
                                        'publish' => 'success',
                                        'private' => 'info'
                                    ];
                                    $status_color = $status_colors[$post->post_status] ?? 'default';
                                    
                                    if (isset($this->component_renderer)) {
                                        echo $this->component_renderer->render_badge([
                                            'text' => ucfirst($post->post_status),
                                            'variant' => $status_color,
                                            'size' => 'small'
                                        ]);
                                    } else {
                                        echo '<span class="acs-badge acs-badge--' . esc_attr($status_color) . ' acs-badge--small">';
                                        echo esc_html(ucfirst($post->post_status));
                                        echo '</span>';
                                    }
                                    ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="acs-card-footer-action">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=acs-generation-logs')); ?>" 
                           class="acs-button acs-button--secondary acs-button--small"
                           aria-label="<?php esc_attr_e('View all generation logs', 'ai-content-studio'); ?>">
                            <span class="dashicons dashicons-list-view" aria-hidden="true"></span>
                            <?php esc_html_e('View All Logs', 'ai-content-studio'); ?>
                        </a>
                    </div>
                <?php else : ?>
                    <div class="acs-empty-state" role="status" aria-live="polite">
                        <div class="acs-empty-state-icon" aria-hidden="true">
                            <span class="dashicons dashicons-edit-large"></span>
                        </div>
                        <p class="acs-empty-state-message">
                            <?php esc_html_e('No generated posts yet.', 'ai-content-studio'); ?>
                        </p>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=acs-generate')); ?>" 
                           class="acs-button acs-button--primary"
                           aria-label="<?php esc_attr_e('Create your first generated post', 'ai-content-studio'); ?>">
                            <span class="dashicons dashicons-plus-alt" aria-hidden="true"></span>
                            <?php esc_html_e('Create your first post!', 'ai-content-studio'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </article>
        </div>
    </section>
    
    <!-- System Status -->
    <section class="acs-dashboard-section" aria-labelledby="system-status-heading">
        <article class="acs-card" role="region" aria-labelledby="system-status-heading">
            <div class="acs-card__header">
                <h2 id="system-status-heading" class="acs-card__title">
                    <span class="dashicons dashicons-admin-generic" aria-hidden="true" style="margin-right: var(--acs-spacing-xs);"></span>
                    <?php esc_html_e('System Status', 'ai-content-studio'); ?>
                </h2>
            </div>
        <div class="acs-card__content">
            <div class="acs-system-status-grid">
                <?php
                // Check API configuration
                $settings = get_option('acs_settings', []);
                $api_configured = !empty($settings['providers']['groq']['api_key'] ?? '');
                
                // Check SEO optimizer status
                $seo_enabled = get_option('acs_optimizer_enabled', true);
                
                // Check recent activity
                $recent_activity = !empty($recent_posts);
                
                $status_items = [
                    [
                        'label' => __('API Configuration', 'ai-content-studio'),
                        'status' => $api_configured ? 'success' : 'warning',
                        'message' => $api_configured ? __('Configured', 'ai-content-studio') : __('Needs Setup', 'ai-content-studio'),
                        'icon' => 'admin-network'
                    ],
                    [
                        'label' => __('SEO Optimizer', 'ai-content-studio'),
                        'status' => $seo_enabled ? 'success' : 'info',
                        'message' => $seo_enabled ? __('Active', 'ai-content-studio') : __('Disabled', 'ai-content-studio'),
                        'icon' => 'chart-line'
                    ],
                    [
                        'label' => __('Recent Activity', 'ai-content-studio'),
                        'status' => $recent_activity ? 'success' : 'info',
                        'message' => $recent_activity ? __('Active', 'ai-content-studio') : __('No Recent Activity', 'ai-content-studio'),
                        'icon' => 'update'
                    ]
                ];
                
                foreach ($status_items as $item) :
                ?>
                    <div class="acs-system-status-item" role="status" aria-live="polite">
                        <div class="acs-system-status-icon" aria-hidden="true">
                            <span class="dashicons dashicons-<?php echo esc_attr($item['icon']); ?>"></span>
                        </div>
                        <div class="acs-system-status-content">
                            <div class="acs-system-status-label"><?php echo esc_html($item['label']); ?></div>
                            <div class="acs-system-status-value">
                                <?php
                                $indicator_class = 'acs-status-indicator';
                                if ($item['status'] === 'success') {
                                    $indicator_class .= ' active';
                                } else {
                                    $indicator_class .= ' inactive';
                                }
                                ?>
                                <span class="<?php echo esc_attr($indicator_class); ?>" 
                                      aria-label="<?php echo esc_attr(sprintf(__('%s status: %s', 'ai-content-studio'), $item['label'], $item['message'])); ?>">
                                    <span class="status-dot" aria-hidden="true"></span>
                                    <span><?php echo esc_html($item['message']); ?></span>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>
    </section>
    
    </main>
</div>

<!-- ARIA live region for dynamic updates -->
<div id="acs-live-region" class="screen-reader-text" aria-live="polite" aria-atomic="true"></div>