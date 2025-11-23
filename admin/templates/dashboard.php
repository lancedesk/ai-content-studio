<?php
/**
 * Dashboard template for AI Content Studio
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get dashboard data
$stats = $this->get_dashboard_stats();
$recent_posts = $this->get_recent_posts();
?>

<div class="wrap acs-admin-page">
    <h1><?php esc_html_e('AI Content Studio Dashboard', 'ai-content-studio'); ?></h1>
    
    <!-- Dashboard Stats -->
    <div class="acs-dashboard-stats">
        <div class="acs-stat-box">
            <div class="acs-stat-icon">
                <span class="dashicons dashicons-edit-large"></span>
            </div>
            <div class="acs-stat-content">
                <div class="acs-stat-number"><?php echo esc_html($stats['total_generated']); ?></div>
                <div class="acs-stat-label"><?php esc_html_e('Total Generated', 'ai-content-studio'); ?></div>
            </div>
        </div>
        
        <div class="acs-stat-box">
            <div class="acs-stat-icon">
                <span class="dashicons dashicons-admin-post"></span>
            </div>
            <div class="acs-stat-content">
                <div class="acs-stat-number"><?php echo esc_html($stats['published_posts']); ?></div>
                <div class="acs-stat-label"><?php esc_html_e('Published Posts', 'ai-content-studio'); ?></div>
            </div>
        </div>
        
        <div class="acs-stat-box">
            <div class="acs-stat-icon">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <div class="acs-stat-content">
                <div class="acs-stat-number"><?php echo esc_html($stats['queue_count']); ?></div>
                <div class="acs-stat-label"><?php esc_html_e('In Queue', 'ai-content-studio'); ?></div>
            </div>
        </div>
        
        <div class="acs-stat-box">
            <div class="acs-stat-icon">
                <span class="dashicons dashicons-chart-line"></span>
            </div>
            <div class="acs-stat-content">
                <div class="acs-stat-number">$<?php echo esc_html(number_format($stats['total_cost'], 2)); ?></div>
                <div class="acs-stat-label"><?php esc_html_e('Total Cost', 'ai-content-studio'); ?></div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="acs-quick-actions">
        <h2><?php esc_html_e('Quick Actions', 'ai-content-studio'); ?></h2>
        <div class="acs-action-grid">
            <a href="<?php echo esc_url(admin_url('admin.php?page=acs-generator')); ?>" class="acs-action-card">
                <span class="dashicons dashicons-plus-alt2"></span>
                <h3><?php esc_html_e('Generate New Content', 'ai-content-studio'); ?></h3>
                <p><?php esc_html_e('Create AI-powered blog posts and articles', 'ai-content-studio'); ?></p>
            </a>
            
            <a href="<?php echo esc_url(admin_url('admin.php?page=acs-keywords')); ?>" class="acs-action-card">
                <span class="dashicons dashicons-search"></span>
                <h3><?php esc_html_e('Keyword Research', 'ai-content-studio'); ?></h3>
                <p><?php esc_html_e('Find trending keywords and topics', 'ai-content-studio'); ?></p>
            </a>
            
            <a href="<?php echo esc_url(admin_url('admin.php?page=acs-analytics')); ?>" class="acs-action-card">
                <span class="dashicons dashicons-chart-bar"></span>
                <h3><?php esc_html_e('Analytics', 'ai-content-studio'); ?></h3>
                <p><?php esc_html_e('View content performance metrics', 'ai-content-studio'); ?></p>
            </a>
            
            <a href="<?php echo esc_url(admin_url('admin.php?page=acs-settings')); ?>" class="acs-action-card">
                <span class="dashicons dashicons-admin-settings"></span>
                <h3><?php esc_html_e('Settings', 'ai-content-studio'); ?></h3>
                <p><?php esc_html_e('Configure AI providers and preferences', 'ai-content-studio'); ?></p>
            </a>
        </div>
    </div>
    
    <!-- Recent Activity -->
    <div class="acs-recent-activity">
        <div class="acs-content-grid">
            
            <!-- Recent Posts -->
            <div class="acs-grid-item">
                <h2><?php esc_html_e('Recent Generated Posts', 'ai-content-studio'); ?></h2>
                <div class="acs-recent-posts">
                    <?php if (!empty($recent_posts)) : ?>
                        <?php foreach ($recent_posts as $post) : ?>
                            <div class="acs-post-item">
                                <div class="acs-post-meta">
                                    <span class="acs-post-status <?php echo esc_attr($post->post_status); ?>">
                                        <?php echo esc_html(ucfirst($post->post_status)); ?>
                                    </span>
                                    <span class="acs-post-date">
                                        <?php echo esc_html(human_time_diff(strtotime($post->post_date), current_time('timestamp')) . ' ago'); ?>
                                    </span>
                                </div>
                                <h4 class="acs-post-title">
                                    <a href="<?php echo esc_url(get_edit_post_link($post->ID)); ?>">
                                        <?php echo esc_html($post->post_title); ?>
                                    </a>
                                </h4>
                                <div class="acs-post-actions">
                                    <a href="<?php echo esc_url(get_edit_post_link($post->ID)); ?>" class="button button-small">
                                        <?php esc_html_e('Edit', 'ai-content-studio'); ?>
                                    </a>
                                    <?php if ($post->post_status === 'publish') : ?>
                                        <a href="<?php echo esc_url(get_permalink($post->ID)); ?>" class="button button-small" target="_blank">
                                            <?php esc_html_e('View', 'ai-content-studio'); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <div class="acs-empty-state">
                            <p><?php esc_html_e('No posts generated yet.', 'ai-content-studio'); ?></p>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=acs-generator')); ?>" class="button button-primary">
                                <?php esc_html_e('Generate Your First Post', 'ai-content-studio'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Content Queue -->
            <div class="acs-grid-item">
                <h2><?php esc_html_e('Content Queue', 'ai-content-studio'); ?></h2>
                <div class="acs-queue-status">
                    <?php if ($stats['queue_count'] > 0) : ?>
                        <div class="acs-queue-item">
                            <span class="acs-queue-count"><?php echo esc_html($stats['queue_count']); ?></span>
                            <span class="acs-queue-label"><?php esc_html_e('items in queue', 'ai-content-studio'); ?></span>
                        </div>
                        <div class="acs-queue-actions">
                            <button class="button" id="acs-process-queue">
                                <?php esc_html_e('Process Queue', 'ai-content-studio'); ?>
                            </button>
                        </div>
                    <?php else : ?>
                        <div class="acs-empty-state">
                            <p><?php esc_html_e('No items in queue.', 'ai-content-studio'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
        </div>
    </div>
    
    <!-- System Status -->
    <div class="acs-system-status">
        <h2><?php esc_html_e('System Status', 'ai-content-studio'); ?></h2>
        <div class="acs-status-grid">
            
            <!-- API Status -->
            <div class="acs-status-item">
                <h3><?php esc_html_e('API Connections', 'ai-content-studio'); ?></h3>
                <div class="acs-api-status">
                    <?php
                    $providers = ACS_Core::get_instance()->get_ai_providers();
                    $settings = get_option('acs_settings', array());
                    
                    foreach ($providers as $provider_name => $provider) :
                        $api_key = isset($settings['providers'][$provider_name]['api_key']) ? $settings['providers'][$provider_name]['api_key'] : '';
                        $status_class = !empty($api_key) ? 'connected' : 'disconnected';
                        $status_text = !empty($api_key) ? __('Connected', 'ai-content-studio') : __('Not configured', 'ai-content-studio');
                    ?>
                        <div class="acs-provider-status-item">
                            <span class="acs-provider-name"><?php echo esc_html(ucfirst($provider_name)); ?></span>
                            <span class="acs-provider-status <?php echo esc_attr($status_class); ?>">
                                <?php echo esc_html($status_text); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- SEO Plugin Status -->
            <div class="acs-status-item">
                <h3><?php esc_html_e('SEO Integration', 'ai-content-studio'); ?></h3>
                <div class="acs-seo-status">
                    <?php
                    $seo_plugins = array(
                        'wordpress-seo/wp-seo.php' => 'Yoast SEO',
                        'seo-by-rank-math/rank-math.php' => 'Rank Math',
                        'seopress/seopress.php' => 'SEOPress',
                        'all-in-one-seo-pack/all_in_one_seo_pack.php' => 'All in One SEO'
                    );
                    
                    $active_seo = array();
                    foreach ($seo_plugins as $plugin_file => $plugin_name) {
                        if (is_plugin_active($plugin_file)) {
                            $active_seo[] = $plugin_name;
                        }
                    }
                    ?>
                    
                    <?php if (!empty($active_seo)) : ?>
                        <div class="acs-seo-active">
                            <span class="acs-status-icon success">✓</span>
                            <span><?php echo esc_html(implode(', ', $active_seo)); ?></span>
                        </div>
                    <?php else : ?>
                        <div class="acs-seo-inactive">
                            <span class="acs-status-icon warning">!</span>
                            <span><?php esc_html_e('No SEO plugin detected', 'ai-content-studio'); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
        </div>
    </div>
    
</div>

<script>
jQuery(document).ready(function($) {
    // Handle queue processing
    $('#acs-process-queue').on('click', function(e) {
        e.preventDefault();
        
        var button = $(this);
        button.prop('disabled', true).text('<?php esc_html_e('Processing...', 'ai-content-studio'); ?>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'acs_process_queue',
                nonce: '<?php echo wp_create_nonce('acs_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data || 'Error processing queue');
                }
            },
            error: function() {
                alert('Error processing queue');
            },
            complete: function() {
                button.prop('disabled', false).text('<?php esc_html_e('Process Queue', 'ai-content-studio'); ?>');
            }
        });
    });
});
</script>