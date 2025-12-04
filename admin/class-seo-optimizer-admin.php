<?php
/**
 * SEO Optimizer Admin Interface
 *
 * Provides WordPress admin interface for the multi-pass SEO optimizer
 * with dashboard, controls, and reporting capabilities.
 *
 * @package AI_Content_Studio
 * @subpackage Admin
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class SEOOptimizerAdmin
 *
 * Manages admin interface for SEO optimizer
 */
class SEOOptimizerAdmin {
    
    /**
     * @var IntegrationCompatibilityLayer
     */
    private $integrationLayer;
    
    /**
     * @var string Admin page slug
     */
    private $pageSlug = 'acs-seo-optimizer';
    
    /**
     * @var array Admin capabilities
     */
    private $capabilities;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->capabilities = [
            'manage_optimizer' => 'manage_options',
            'view_reports' => 'edit_posts',
            'bulk_optimize' => 'manage_options'
        ];
        
        $this->initializeIntegrationLayer();
        $this->registerHooks();
    }
    
    /**
     * Initialize integration layer
     */
    private function initializeIntegrationLayer() {
        $config = [
            'enableOptimizer' => get_option('acs_optimizer_enabled', true),
            'autoOptimizeNewContent' => get_option('acs_auto_optimize', false),
            'bypassMode' => get_option('acs_bypass_mode', false),
            'supportedFormats' => get_option('acs_supported_formats', ['post', 'page', 'article']),
            'integrationMode' => get_option('acs_integration_mode', 'seamless'),
            'fallbackToOriginal' => get_option('acs_fallback_enabled', true),
            'preserveExistingWorkflow' => true,
            'logLevel' => get_option('acs_log_level', 'info')
        ];
        
        $this->integrationLayer = new IntegrationCompatibilityLayer($config);
    }
    
    /**
     * Register WordPress hooks
     * 
     * NOTE: Menu registration is disabled here - handled by ACS_Unified_Admin
     * to keep all plugin menus under a single "AI Content Studio" parent.
     */
    private function registerHooks() {
        // Admin menu - DISABLED: now handled by ACS_Unified_Admin
        // add_action('admin_menu', [$this, 'addAdminMenu']);
        
        // Admin scripts and styles
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
        
        // AJAX handlers
        add_action('wp_ajax_acs_manual_optimize', [$this, 'handleManualOptimization']);
        add_action('wp_ajax_acs_bulk_optimize', [$this, 'handleBulkOptimization']);
        add_action('wp_ajax_acs_get_optimization_status', [$this, 'getOptimizationStatus']);
        add_action('wp_ajax_acs_save_optimizer_settings', [$this, 'saveOptimizerSettings']);
        
        // Settings registration
        add_action('admin_init', [$this, 'registerSettings']);
        
        // Meta boxes
        add_action('add_meta_boxes', [$this, 'addMetaBoxes']);
        
        // Admin notices
        add_action('admin_notices', [$this, 'displayAdminNotices']);
    }
    
    /**
     * Add admin menu items
     */
    public function addAdminMenu() {
        // Main menu page
        add_menu_page(
            'SEO Optimizer',
            'SEO Optimizer',
            $this->capabilities['manage_optimizer'],
            $this->pageSlug,
            [$this, 'renderDashboard'],
            'dashicons-chart-line',
            30
        );
        
        // Dashboard submenu
        add_submenu_page(
            $this->pageSlug,
            'Dashboard',
            'Dashboard',
            $this->capabilities['view_reports'],
            $this->pageSlug,
            [$this, 'renderDashboard']
        );
        
        // Bulk optimization submenu
        add_submenu_page(
            $this->pageSlug,
            'Bulk Optimize',
            'Bulk Optimize',
            $this->capabilities['bulk_optimize'],
            $this->pageSlug . '-bulk',
            [$this, 'renderBulkOptimization']
        );
        
        // Reports submenu
        add_submenu_page(
            $this->pageSlug,
            'Reports',
            'Reports',
            $this->capabilities['view_reports'],
            $this->pageSlug . '-reports',
            [$this, 'renderReports']
        );
        
        // Settings submenu
        add_submenu_page(
            $this->pageSlug,
            'Settings',
            'Settings',
            $this->capabilities['manage_optimizer'],
            $this->pageSlug . '-settings',
            [$this, 'renderSettings']
        );
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueueAdminAssets($hook) {
        // Only load on our admin pages
        if (strpos($hook, $this->pageSlug) === false && $hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }
        
        // Enqueue styles
        wp_enqueue_style(
            'acs-seo-optimizer-admin',
            plugins_url('css/seo-optimizer-admin.css', dirname(__FILE__)),
            [],
            '1.0.0'
        );
        
        // Enqueue scripts
        wp_enqueue_script(
            'acs-seo-optimizer-admin',
            plugins_url('js/seo-optimizer-admin.js', dirname(__FILE__)),
            ['jquery'],
            '1.0.0',
            true
        );
        
        // Localize script
        wp_localize_script('acs-seo-optimizer-admin', 'acsOptimizer', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('acs_optimizer_nonce'),
            'strings' => [
                'optimizing' => __('Optimizing...', 'ai-content-studio'),
                'success' => __('Optimization completed successfully!', 'ai-content-studio'),
                'error' => __('Optimization failed. Please try again.', 'ai-content-studio'),
                'confirm_bulk' => __('Are you sure you want to optimize all selected posts?', 'ai-content-studio')
            ]
        ]);
    }
    
    /**
     * Register settings
     */
    public function registerSettings() {
        register_setting('acs_seo_optimizer', 'acs_optimizer_enabled');
        register_setting('acs_seo_optimizer', 'acs_auto_optimize');
        register_setting('acs_seo_optimizer', 'acs_bypass_mode');
        register_setting('acs_seo_optimizer', 'acs_integration_mode');
        register_setting('acs_seo_optimizer', 'acs_fallback_enabled');
        register_setting('acs_seo_optimizer', 'acs_log_level');
        register_setting('acs_seo_optimizer', 'acs_max_iterations');
        register_setting('acs_seo_optimizer', 'acs_target_score');
    }
    
    /**
     * Add meta boxes
     */
    public function addMetaBoxes() {
        $screens = ['post', 'page'];
        
        foreach ($screens as $screen) {
            add_meta_box(
                'acs-seo-optimization',
                'SEO Optimization',
                [$this, 'renderOptimizationMetaBox'],
                $screen,
                'side',
                'high'
            );
        }
    }
    
    /**
     * Render optimization meta box
     */
    public function renderOptimizationMetaBox($post) {
        $optimizationResult = get_post_meta($post->ID, '_acs_optimization_result', true);
        $lastOptimized = get_post_meta($post->ID, '_acs_optimization_timestamp', true);
        
        wp_nonce_field('acs_manual_optimization', 'acs_optimization_nonce');
        
        ?>
        <div class="acs-optimization-metabox">
            <?php if ($optimizationResult && isset($optimizationResult['optimizationSummary'])): 
                $summary = $optimizationResult['optimizationSummary'];
                $score = $summary['finalScore'] ?? 0;
                $compliant = $summary['complianceAchieved'] ?? false;
            ?>
                <div class="acs-score-display">
                    <div class="acs-score-circle <?php echo $compliant ? 'compliant' : 'needs-work'; ?>">
                        <span class="score-value"><?php echo number_format($score, 1); ?>%</span>
                    </div>
                    <div class="acs-score-label">
                        <?php if ($compliant): ?>
                            <span class="status-icon">✓</span>
                            <strong><?php _e('SEO Compliant', 'ai-content-studio'); ?></strong>
                        <?php else: ?>
                            <span class="status-icon">⚠</span>
                            <strong><?php _e('Needs Optimization', 'ai-content-studio'); ?></strong>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($lastOptimized): ?>
                    <p class="acs-last-optimized">
                        <small><?php _e('Last optimized:', 'ai-content-studio'); ?> 
                        <?php echo human_time_diff(strtotime($lastOptimized), current_time('timestamp')); ?> <?php _e('ago', 'ai-content-studio'); ?></small>
                    </p>
                <?php endif; ?>
                
                <div class="acs-optimization-details">
                    <p><strong><?php _e('Iterations:', 'ai-content-studio'); ?></strong> <?php echo $summary['iterationsUsed'] ?? 0; ?></p>
                    <p><strong><?php _e('Issues Fixed:', 'ai-content-studio'); ?></strong> <?php echo $summary['issuesFixed'] ?? 0; ?></p>
                </div>
            <?php else: ?>
                <p><?php _e('No optimization data available.', 'ai-content-studio'); ?></p>
            <?php endif; ?>
            
            <div class="acs-optimization-actions">
                <button type="button" class="button button-primary button-large" id="acs-manual-optimize" data-post-id="<?php echo $post->ID; ?>">
                    <?php _e('Optimize Now', 'ai-content-studio'); ?>
                </button>
            </div>
            
            <div id="acs-optimization-progress" style="display:none;">
                <div class="acs-progress-bar">
                    <div class="acs-progress-fill"></div>
                </div>
                <p class="acs-progress-text"></p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render dashboard page
     */
    public function renderDashboard() {
        $status = $this->integrationLayer->getIntegrationStatus();
        $recentOptimizations = $this->getRecentOptimizations(10);
        $stats = $this->getOptimizationStats();
        
        ?>
        <div class="wrap acs-optimizer-dashboard">
            <h1><?php _e('SEO Optimizer Dashboard', 'ai-content-studio'); ?></h1>
            
            <div class="acs-dashboard-grid">
                <!-- Status Card -->
                <div class="acs-card acs-status-card">
                    <h2><?php _e('Optimizer Status', 'ai-content-studio'); ?></h2>
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
                </div>
                
                <!-- Statistics Cards -->
                <div class="acs-card acs-stat-card">
                    <h3><?php _e('Total Optimizations', 'ai-content-studio'); ?></h3>
                    <div class="acs-stat-value"><?php echo number_format($stats['total_optimizations']); ?></div>
                </div>
                
                <div class="acs-card acs-stat-card">
                    <h3><?php _e('Average Score', 'ai-content-studio'); ?></h3>
                    <div class="acs-stat-value"><?php echo number_format($stats['average_score'], 1); ?>%</div>
                </div>
                
                <div class="acs-card acs-stat-card">
                    <h3><?php _e('Compliance Rate', 'ai-content-studio'); ?></h3>
                    <div class="acs-stat-value"><?php echo number_format($stats['compliance_rate'], 1); ?>%</div>
                </div>
            </div>
            
            <!-- Recent Optimizations -->
            <div class="acs-card acs-recent-optimizations">
                <h2><?php _e('Recent Optimizations', 'ai-content-studio'); ?></h2>
                <?php if (!empty($recentOptimizations)): ?>
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
                            <?php foreach ($recentOptimizations as $opt): ?>
                                <tr>
                                    <td>
                                        <strong>
                                            <a href="<?php echo get_edit_post_link($opt['post_id']); ?>">
                                                <?php echo esc_html($opt['post_title']); ?>
                                            </a>
                                        </strong>
                                    </td>
                                    <td><?php echo number_format($opt['score'], 1); ?>%</td>
                                    <td>
                                        <span class="acs-status-badge <?php echo $opt['compliant'] ? 'compliant' : 'needs-work'; ?>">
                                            <?php echo $opt['compliant'] ? __('Compliant', 'ai-content-studio') : __('Needs Work', 'ai-content-studio'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo human_time_diff(strtotime($opt['timestamp']), current_time('timestamp')); ?> <?php _e('ago', 'ai-content-studio'); ?></td>
                                    <td>
                                        <a href="<?php echo admin_url('admin.php?page=' . $this->pageSlug . '-reports&post_id=' . $opt['post_id']); ?>" class="button button-small">
                                            <?php _e('View Report', 'ai-content-studio'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p><?php _e('No optimizations yet.', 'ai-content-studio'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render bulk optimization page
     */
    public function renderBulkOptimization() {
        $posts = $this->getOptimizablePosts();
        
        ?>
        <div class="wrap acs-bulk-optimization">
            <h1><?php _e('Bulk Optimization', 'ai-content-studio'); ?></h1>
            
            <div class="acs-card">
                <p><?php _e('Select posts to optimize in bulk. This process may take several minutes depending on the number of posts.', 'ai-content-studio'); ?></p>
                
                <form id="acs-bulk-optimize-form">
                    <?php wp_nonce_field('acs_bulk_optimize', 'acs_bulk_nonce'); ?>
                    
                    <div class="acs-bulk-filters">
                        <label>
                            <input type="checkbox" id="acs-select-all"> <?php _e('Select All', 'ai-content-studio'); ?>
                        </label>
                        
                        <select name="post_type" id="acs-post-type-filter">
                            <option value=""><?php _e('All Post Types', 'ai-content-studio'); ?></option>
                            <option value="post"><?php _e('Posts', 'ai-content-studio'); ?></option>
                            <option value="page"><?php _e('Pages', 'ai-content-studio'); ?></option>
                        </select>
                        
                        <select name="optimization_status" id="acs-status-filter">
                            <option value=""><?php _e('All Statuses', 'ai-content-studio'); ?></option>
                            <option value="not_optimized"><?php _e('Not Optimized', 'ai-content-studio'); ?></option>
                            <option value="needs_work"><?php _e('Needs Work', 'ai-content-studio'); ?></option>
                        </select>
                    </div>
                    
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <td class="check-column"><input type="checkbox" id="select-all-posts"></td>
                                <th><?php _e('Post', 'ai-content-studio'); ?></th>
                                <th><?php _e('Type', 'ai-content-studio'); ?></th>
                                <th><?php _e('Current Score', 'ai-content-studio'); ?></th>
                                <th><?php _e('Status', 'ai-content-studio'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($posts as $post): ?>
                                <tr>
                                    <th class="check-column">
                                        <input type="checkbox" name="post_ids[]" value="<?php echo $post->ID; ?>" class="acs-post-checkbox">
                                    </th>
                                    <td>
                                        <strong><?php echo esc_html($post->post_title); ?></strong>
                                    </td>
                                    <td><?php echo ucfirst($post->post_type); ?></td>
                                    <td><?php echo $this->getPostScore($post->ID); ?></td>
                                    <td><?php echo $this->getPostOptimizationStatus($post->ID); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="acs-bulk-actions">
                        <button type="submit" class="button button-primary button-large" id="acs-start-bulk-optimize">
                            <?php _e('Start Bulk Optimization', 'ai-content-studio'); ?>
                        </button>
                        <span class="acs-selected-count">0 <?php _e('posts selected', 'ai-content-studio'); ?></span>
                    </div>
                </form>
                
                <div id="acs-bulk-progress" style="display:none;">
                    <h3><?php _e('Optimization Progress', 'ai-content-studio'); ?></h3>
                    <div class="acs-progress-bar">
                        <div class="acs-progress-fill"></div>
                    </div>
                    <p class="acs-progress-text"></p>
                    <div class="acs-progress-log"></div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render reports page
     */
    public function renderReports() {
        $postId = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
        
        if ($postId) {
            $this->renderSingleReport($postId);
        } else {
            $this->renderReportsList();
        }
    }
    
    /**
     * Render single optimization report
     */
    private function renderSingleReport($postId) {
        $post = get_post($postId);
        $result = get_post_meta($postId, '_acs_optimization_result', true);
        
        if (!$post || !$result) {
            echo '<div class="wrap"><p>' . __('Report not found.', 'ai-content-studio') . '</p></div>';
            return;
        }
        
        $summary = $result['optimizationSummary'] ?? [];
        
        ?>
        <div class="wrap acs-optimization-report">
            <h1><?php printf(__('Optimization Report: %s', 'ai-content-studio'), esc_html($post->post_title)); ?></h1>
            
            <div class="acs-report-header">
                <div class="acs-score-display-large">
                    <div class="acs-score-circle <?php echo ($summary['complianceAchieved'] ?? false) ? 'compliant' : 'needs-work'; ?>">
                        <span class="score-value"><?php echo number_format($summary['finalScore'] ?? 0, 1); ?>%</span>
                    </div>
                </div>
                
                <div class="acs-report-summary">
                    <h2><?php _e('Summary', 'ai-content-studio'); ?></h2>
                    <ul>
                        <li><strong><?php _e('Initial Score:', 'ai-content-studio'); ?></strong> <?php echo number_format($summary['initialScore'] ?? 0, 1); ?>%</li>
                        <li><strong><?php _e('Final Score:', 'ai-content-studio'); ?></strong> <?php echo number_format($summary['finalScore'] ?? 0, 1); ?>%</li>
                        <li><strong><?php _e('Improvement:', 'ai-content-studio'); ?></strong> +<?php echo number_format(($summary['finalScore'] ?? 0) - ($summary['initialScore'] ?? 0), 1); ?>%</li>
                        <li><strong><?php _e('Iterations:', 'ai-content-studio'); ?></strong> <?php echo $summary['iterationsUsed'] ?? 0; ?></li>
                        <li><strong><?php _e('Issues Fixed:', 'ai-content-studio'); ?></strong> <?php echo $summary['issuesFixed'] ?? 0; ?></li>
                        <li><strong><?php _e('Compliance:', 'ai-content-studio'); ?></strong> 
                            <?php echo ($summary['complianceAchieved'] ?? false) ? __('Achieved', 'ai-content-studio') : __('Not Achieved', 'ai-content-studio'); ?>
                        </li>
                    </ul>
                </div>
            </div>
            
            <?php if (isset($result['iterationHistory'])): ?>
                <div class="acs-card">
                    <h2><?php _e('Optimization History', 'ai-content-studio'); ?></h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Iteration', 'ai-content-studio'); ?></th>
                                <th><?php _e('Score', 'ai-content-studio'); ?></th>
                                <th><?php _e('Errors', 'ai-content-studio'); ?></th>
                                <th><?php _e('Warnings', 'ai-content-studio'); ?></th>
                                <th><?php _e('Corrections', 'ai-content-studio'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($result['iterationHistory'] as $iteration): ?>
                                <tr>
                                    <td><?php echo $iteration['iteration']; ?></td>
                                    <td><?php echo number_format($iteration['score'] ?? 0, 1); ?>%</td>
                                    <td><?php echo $iteration['errorCount'] ?? 0; ?></td>
                                    <td><?php echo $iteration['warningCount'] ?? 0; ?></td>
                                    <td><?php echo count($iteration['correctionsMade'] ?? []); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <div class="acs-report-actions">
                <a href="<?php echo get_edit_post_link($postId); ?>" class="button button-primary">
                    <?php _e('Edit Post', 'ai-content-studio'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=' . $this->pageSlug . '-reports'); ?>" class="button">
                    <?php _e('Back to Reports', 'ai-content-studio'); ?>
                </a>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render reports list
     */
    private function renderReportsList() {
        $reports = $this->getAllOptimizationReports();
        
        ?>
        <div class="wrap acs-reports-list">
            <h1><?php _e('Optimization Reports', 'ai-content-studio'); ?></h1>
            
            <div class="acs-card">
                <?php if (!empty($reports)): ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Post', 'ai-content-studio'); ?></th>
                                <th><?php _e('Score', 'ai-content-studio'); ?></th>
                                <th><?php _e('Improvement', 'ai-content-studio'); ?></th>
                                <th><?php _e('Status', 'ai-content-studio'); ?></th>
                                <th><?php _e('Date', 'ai-content-studio'); ?></th>
                                <th><?php _e('Actions', 'ai-content-studio'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reports as $report): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo esc_html($report['post_title']); ?></strong>
                                    </td>
                                    <td><?php echo number_format($report['final_score'], 1); ?>%</td>
                                    <td>+<?php echo number_format($report['improvement'], 1); ?>%</td>
                                    <td>
                                        <span class="acs-status-badge <?php echo $report['compliant'] ? 'compliant' : 'needs-work'; ?>">
                                            <?php echo $report['compliant'] ? __('Compliant', 'ai-content-studio') : __('Needs Work', 'ai-content-studio'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($report['timestamp'])); ?></td>
                                    <td>
                                        <a href="<?php echo admin_url('admin.php?page=' . $this->pageSlug . '-reports&post_id=' . $report['post_id']); ?>" class="button button-small">
                                            <?php _e('View Details', 'ai-content-studio'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p><?php _e('No optimization reports available.', 'ai-content-studio'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render settings page
     */
    public function renderSettings() {
        if (isset($_POST['submit']) && check_admin_referer('acs_optimizer_settings')) {
            $this->saveSettings();
            echo '<div class="notice notice-success"><p>' . __('Settings saved successfully.', 'ai-content-studio') . '</p></div>';
        }
        
        ?>
        <div class="wrap acs-settings">
            <h1><?php _e('SEO Optimizer Settings', 'ai-content-studio'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('acs_optimizer_settings'); ?>
                
                <div class="acs-card">
                    <h2><?php _e('General Settings', 'ai-content-studio'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Enable Optimizer', 'ai-content-studio'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="acs_optimizer_enabled" value="1" 
                                           <?php checked(get_option('acs_optimizer_enabled', true)); ?> />
                                    <?php _e('Enable automatic SEO optimization', 'ai-content-studio'); ?>
                                </label>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Integration Mode', 'ai-content-studio'); ?></th>
                            <td>
                                <select name="acs_integration_mode">
                                    <option value="seamless" <?php selected(get_option('acs_integration_mode', 'seamless'), 'seamless'); ?>>
                                        <?php _e('Seamless (Automatic)', 'ai-content-studio'); ?>
                                    </option>
                                    <option value="manual" <?php selected(get_option('acs_integration_mode'), 'manual'); ?>>
                                        <?php _e('Manual (On-Demand)', 'ai-content-studio'); ?>
                                    </option>
                                    <option value="disabled" <?php selected(get_option('acs_integration_mode'), 'disabled'); ?>>
                                        <?php _e('Disabled', 'ai-content-studio'); ?>
                                    </option>
                                </select>
                                <p class="description"><?php _e('Choose how the optimizer integrates with your workflow', 'ai-content-studio'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Auto-Optimize New Content', 'ai-content-studio'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="acs_auto_optimize" value="1" 
                                           <?php checked(get_option('acs_auto_optimize', false)); ?> />
                                    <?php _e('Automatically optimize new posts and pages on save', 'ai-content-studio'); ?>
                                </label>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Bypass Mode', 'ai-content-studio'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="acs_bypass_mode" value="1" 
                                           <?php checked(get_option('acs_bypass_mode', false)); ?> />
                                    <?php _e('Temporarily disable all optimization (bypass mode)', 'ai-content-studio'); ?>
                                </label>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Fallback to Original', 'ai-content-studio'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="acs_fallback_enabled" value="1" 
                                           <?php checked(get_option('acs_fallback_enabled', true)); ?> />
                                    <?php _e('Use original content if optimization fails', 'ai-content-studio'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="acs-card">
                    <h2><?php _e('Optimization Parameters', 'ai-content-studio'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Maximum Iterations', 'ai-content-studio'); ?></th>
                            <td>
                                <input type="number" name="acs_max_iterations" value="<?php echo esc_attr(get_option('acs_max_iterations', 3)); ?>" min="1" max="10" />
                                <p class="description"><?php _e('Maximum number of optimization passes (1-10)', 'ai-content-studio'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Target Compliance Score', 'ai-content-studio'); ?></th>
                            <td>
                                <input type="number" name="acs_target_score" value="<?php echo esc_attr(get_option('acs_target_score', 95)); ?>" min="50" max="100" step="5" />%
                                <p class="description"><?php _e('Target SEO compliance score (50-100%)', 'ai-content-studio'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Log Level', 'ai-content-studio'); ?></th>
                            <td>
                                <select name="acs_log_level">
                                    <option value="error" <?php selected(get_option('acs_log_level', 'info'), 'error'); ?>>
                                        <?php _e('Errors Only', 'ai-content-studio'); ?>
                                    </option>
                                    <option value="info" <?php selected(get_option('acs_log_level', 'info'), 'info'); ?>>
                                        <?php _e('Info', 'ai-content-studio'); ?>
                                    </option>
                                    <option value="debug" <?php selected(get_option('acs_log_level', 'info'), 'debug'); ?>>
                                        <?php _e('Debug (Verbose)', 'ai-content-studio'); ?>
                                    </option>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <?php submit_button(__('Save Settings', 'ai-content-studio')); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Save settings
     */
    private function saveSettings() {
        update_option('acs_optimizer_enabled', isset($_POST['acs_optimizer_enabled']));
        update_option('acs_auto_optimize', isset($_POST['acs_auto_optimize']));
        update_option('acs_bypass_mode', isset($_POST['acs_bypass_mode']));
        update_option('acs_fallback_enabled', isset($_POST['acs_fallback_enabled']));
        update_option('acs_integration_mode', sanitize_text_field($_POST['acs_integration_mode'] ?? 'seamless'));
        update_option('acs_max_iterations', absint($_POST['acs_max_iterations'] ?? 3));
        update_option('acs_target_score', absint($_POST['acs_target_score'] ?? 95));
        update_option('acs_log_level', sanitize_text_field($_POST['acs_log_level'] ?? 'info'));
        
        // Reinitialize integration layer with new settings
        $this->initializeIntegrationLayer();
    }
    
    /**
     * Handle manual optimization AJAX request
     */
    public function handleManualOptimization() {
        check_ajax_referer('acs_optimizer_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'ai-content-studio')]);
        }
        
        $postId = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        
        if (!$postId) {
            wp_send_json_error(['message' => __('Invalid post ID', 'ai-content-studio')]);
        }
        
        $post = get_post($postId);
        
        if (!$post) {
            wp_send_json_error(['message' => __('Post not found', 'ai-content-studio')]);
        }
        
        try {
            // Extract content
            $content = [
                'title' => $post->post_title,
                'content' => $post->post_content,
                'excerpt' => $post->post_excerpt,
                'meta_description' => get_post_meta($postId, '_yoast_wpseo_metadesc', true) ?: '',
                'type' => $post->post_type
            ];
            
            // Get focus keyword
            $focusKeyword = get_post_meta($postId, '_yoast_wpseo_focuskw', true) ?: '';
            
            // Run optimization
            $optimizer = $this->integrationLayer->getOptimizer();
            $result = $optimizer->optimizeContent($content, $focusKeyword, []);
            
            if ($result['success']) {
                // Update post
                wp_update_post([
                    'ID' => $postId,
                    'post_title' => $result['content']['title'],
                    'post_content' => $result['content']['content'],
                    'post_excerpt' => $result['content']['excerpt']
                ]);
                
                // Update meta description
                if (isset($result['content']['meta_description'])) {
                    update_post_meta($postId, '_yoast_wpseo_metadesc', $result['content']['meta_description']);
                }
                
                // Store optimization result
                update_post_meta($postId, '_acs_optimization_result', $result);
                update_post_meta($postId, '_acs_optimization_timestamp', current_time('mysql'));
                
                wp_send_json_success([
                    'message' => __('Optimization completed successfully!', 'ai-content-studio'),
                    'result' => $result
                ]);
            } else {
                wp_send_json_error([
                    'message' => $result['error'] ?? __('Optimization failed', 'ai-content-studio')
                ]);
            }
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Handle bulk optimization AJAX request
     */
    public function handleBulkOptimization() {
        check_ajax_referer('acs_optimizer_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'ai-content-studio')]);
        }
        
        $postIds = isset($_POST['post_ids']) ? array_map('intval', $_POST['post_ids']) : [];
        
        if (empty($postIds)) {
            wp_send_json_error(['message' => __('No posts selected', 'ai-content-studio')]);
        }
        
        $results = [];
        $optimizer = $this->integrationLayer->getOptimizer();
        
        foreach ($postIds as $postId) {
            $post = get_post($postId);
            
            if (!$post) {
                $results[] = [
                    'post_id' => $postId,
                    'success' => false,
                    'error' => __('Post not found', 'ai-content-studio')
                ];
                continue;
            }
            
            try {
                $content = [
                    'title' => $post->post_title,
                    'content' => $post->post_content,
                    'excerpt' => $post->post_excerpt,
                    'meta_description' => get_post_meta($postId, '_yoast_wpseo_metadesc', true) ?: '',
                    'type' => $post->post_type
                ];
                
                $focusKeyword = get_post_meta($postId, '_yoast_wpseo_focuskw', true) ?: '';
                $result = $optimizer->optimizeContent($content, $focusKeyword, []);
                
                if ($result['success']) {
                    wp_update_post([
                        'ID' => $postId,
                        'post_title' => $result['content']['title'],
                        'post_content' => $result['content']['content'],
                        'post_excerpt' => $result['content']['excerpt']
                    ]);
                    
                    if (isset($result['content']['meta_description'])) {
                        update_post_meta($postId, '_yoast_wpseo_metadesc', $result['content']['meta_description']);
                    }
                    
                    update_post_meta($postId, '_acs_optimization_result', $result);
                    update_post_meta($postId, '_acs_optimization_timestamp', current_time('mysql'));
                    
                    $results[] = [
                        'post_id' => $postId,
                        'post_title' => $post->post_title,
                        'success' => true,
                        'score' => $result['optimizationSummary']['finalScore'] ?? 0
                    ];
                } else {
                    $results[] = [
                        'post_id' => $postId,
                        'post_title' => $post->post_title,
                        'success' => false,
                        'error' => $result['error'] ?? __('Optimization failed', 'ai-content-studio')
                    ];
                }
            } catch (Exception $e) {
                $results[] = [
                    'post_id' => $postId,
                    'post_title' => $post->post_title,
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        wp_send_json_success(['results' => $results]);
    }
    
    /**
     * Get optimization status AJAX handler
     */
    public function getOptimizationStatus() {
        check_ajax_referer('acs_optimizer_nonce', 'nonce');
        
        $postId = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        
        if (!$postId) {
            wp_send_json_error(['message' => __('Invalid post ID', 'ai-content-studio')]);
        }
        
        $result = get_post_meta($postId, '_acs_optimization_result', true);
        $timestamp = get_post_meta($postId, '_acs_optimization_timestamp', true);
        
        wp_send_json_success([
            'result' => $result,
            'timestamp' => $timestamp
        ]);
    }
    
    /**
     * Save optimizer settings AJAX handler
     */
    public function saveOptimizerSettings() {
        check_ajax_referer('acs_optimizer_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'ai-content-studio')]);
        }
        
        $this->saveSettings();
        
        wp_send_json_success(['message' => __('Settings saved successfully', 'ai-content-studio')]);
    }
    
    /**
     * Display admin notices
     */
    public function displayAdminNotices() {
        $status = $this->integrationLayer->getIntegrationStatus();
        
        if ($status['bypass_mode']) {
            ?>
            <div class="notice notice-warning">
                <p><?php _e('SEO Optimizer is in bypass mode. All optimization is currently disabled.', 'ai-content-studio'); ?></p>
            </div>
            <?php
        }
        
        if (!$status['optimizer_enabled']) {
            ?>
            <div class="notice notice-info">
                <p><?php _e('SEO Optimizer is currently disabled.', 'ai-content-studio'); ?> 
                <a href="<?php echo admin_url('admin.php?page=' . $this->pageSlug . '-settings'); ?>"><?php _e('Enable it here', 'ai-content-studio'); ?></a></p>
            </div>
            <?php
        }
    }
    
    /**
     * Get recent optimizations
     */
    private function getRecentOptimizations($limit = 10) {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT p.ID as post_id, p.post_title, pm.meta_value as result, pm2.meta_value as timestamp
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_acs_optimization_result'
            INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_acs_optimization_timestamp'
            WHERE p.post_status = 'publish'
            ORDER BY pm2.meta_value DESC
            LIMIT %d
        ", $limit));
        
        $optimizations = [];
        foreach ($results as $row) {
            $result = maybe_unserialize($row->result);
            $summary = $result['optimizationSummary'] ?? [];
            
            $optimizations[] = [
                'post_id' => $row->post_id,
                'post_title' => $row->post_title,
                'score' => $summary['finalScore'] ?? 0,
                'compliant' => $summary['complianceAchieved'] ?? false,
                'timestamp' => $row->timestamp
            ];
        }
        
        return $optimizations;
    }
    
    /**
     * Get optimization statistics
     */
    private function getOptimizationStats() {
        global $wpdb;
        
        $results = $wpdb->get_results("
            SELECT pm.meta_value as result
            FROM {$wpdb->postmeta} pm
            WHERE pm.meta_key = '_acs_optimization_result'
        ");
        
        $totalOptimizations = count($results);
        $totalScore = 0;
        $compliantCount = 0;
        
        foreach ($results as $row) {
            $result = maybe_unserialize($row->result);
            $summary = $result['optimizationSummary'] ?? [];
            
            $totalScore += $summary['finalScore'] ?? 0;
            if ($summary['complianceAchieved'] ?? false) {
                $compliantCount++;
            }
        }
        
        return [
            'total_optimizations' => $totalOptimizations,
            'average_score' => $totalOptimizations > 0 ? $totalScore / $totalOptimizations : 0,
            'compliance_rate' => $totalOptimizations > 0 ? ($compliantCount / $totalOptimizations) * 100 : 0
        ];
    }
    
    /**
     * Get all optimization reports
     */
    private function getAllOptimizationReports() {
        global $wpdb;
        
        $results = $wpdb->get_results("
            SELECT p.ID as post_id, p.post_title, pm.meta_value as result, pm2.meta_value as timestamp
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_acs_optimization_result'
            INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_acs_optimization_timestamp'
            WHERE p.post_status = 'publish'
            ORDER BY pm2.meta_value DESC
        ");
        
        $reports = [];
        foreach ($results as $row) {
            $result = maybe_unserialize($row->result);
            $summary = $result['optimizationSummary'] ?? [];
            
            $reports[] = [
                'post_id' => $row->post_id,
                'post_title' => $row->post_title,
                'final_score' => $summary['finalScore'] ?? 0,
                'improvement' => ($summary['finalScore'] ?? 0) - ($summary['initialScore'] ?? 0),
                'compliant' => $summary['complianceAchieved'] ?? false,
                'timestamp' => $row->timestamp
            ];
        }
        
        return $reports;
    }
    
    /**
     * Get optimizable posts
     */
    private function getOptimizablePosts() {
        $args = [
            'post_type' => ['post', 'page'],
            'post_status' => 'publish',
            'posts_per_page' => 100,
            'orderby' => 'date',
            'order' => 'DESC'
        ];
        
        return get_posts($args);
    }
    
    /**
     * Get post score
     */
    private function getPostScore($postId) {
        $result = get_post_meta($postId, '_acs_optimization_result', true);
        
        if ($result && isset($result['optimizationSummary']['finalScore'])) {
            return number_format($result['optimizationSummary']['finalScore'], 1) . '%';
        }
        
        return __('Not optimized', 'ai-content-studio');
    }
    
    /**
     * Get post optimization status
     */
    private function getPostOptimizationStatus($postId) {
        $result = get_post_meta($postId, '_acs_optimization_result', true);
        
        if (!$result) {
            return '<span class="acs-status-badge not-optimized">' . __('Not Optimized', 'ai-content-studio') . '</span>';
        }
        
        $compliant = $result['optimizationSummary']['complianceAchieved'] ?? false;
        
        if ($compliant) {
            return '<span class="acs-status-badge compliant">' . __('Compliant', 'ai-content-studio') . '</span>';
        }
        
        return '<span class="acs-status-badge needs-work">' . __('Needs Work', 'ai-content-studio') . '</span>';
    }
    
    /**
     * Get integration layer
     */
    public function getIntegrationLayer() {
        return $this->integrationLayer;
    }
}
