<?php
/**
 * Unified Admin Interface for AI Content Studio
 *
 * Consolidates all ACS functionality under a single menu structure
 * with modern design patterns and responsive layouts.
 *
 * @package AI_Content_Studio
 * @subpackage Admin
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class ACS_Unified_Admin
 *
 * Manages the unified admin interface for all ACS functionality
 */
class ACS_Unified_Admin {
    
    /**
     * @var ACS_Unified_Admin Singleton instance
     */
    private static $instance = null;
    
    /**
     * @var string Plugin name
     */
    private $plugin_name;
    
    /**
     * @var string Plugin version
     */
    private $version;
    
    /**
     * @var string Main menu slug
     */
    private $main_menu_slug = 'acs-dashboard';
    
    /**
     * @var array Admin capabilities
     */
    private $capabilities;
    
    /**
     * @var ACS_Component_Renderer Component renderer instance
     */
    private $component_renderer;
    
    /**
     * @var bool Whether hooks have been registered
     */
    private $hooks_registered = false;
    
    /**
     * Get singleton instance
     *
     * @param string $plugin_name The plugin name (optional after first call)
     * @param string $version The plugin version (optional after first call)
     * @return ACS_Unified_Admin
     */
    public static function get_instance($plugin_name = 'ai-content-studio', $version = '1.0.1') {
        if (self::$instance === null) {
            self::$instance = new self($plugin_name, $version);
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     *
     * @param string $plugin_name The plugin name
     * @param string $version The plugin version
     */
    private function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        
        $this->capabilities = [
            'generate_content' => 'acs_generate_content',
            'manage_settings' => 'acs_manage_settings',
            'view_analytics' => 'acs_view_analytics',
            'manage_seo' => 'acs_manage_seo'
        ];
        
        $this->init_component_renderer();
        $this->register_hooks();
    }
    
    /**
     * Initialize component renderer
     */
    private function init_component_renderer() {
        if (!class_exists('ACS_Component_Renderer')) {
            require_once ACS_PLUGIN_PATH . 'admin/class-acs-component-renderer.php';
        }
        $this->component_renderer = new ACS_Component_Renderer();
    }
    
    /**
     * Register WordPress hooks (only once)
     */
    private function register_hooks() {
        // Prevent double registration
        if ($this->hooks_registered) {
            return;
        }
        $this->hooks_registered = true;
        
        // Admin menu - Note: May also be called from ACS_Core loader
        add_action('admin_menu', [$this, 'add_unified_menu']);
        
        // Admin assets
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        
        // Admin init
        add_action('admin_init', [$this, 'admin_init']);
        
        // AJAX handlers
        $this->register_ajax_handlers();
        
        // Meta boxes
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
    }
    
    /**
     * Register AJAX handlers
     */
    private function register_ajax_handlers() {
        $ajax_actions = [
            'acs_generate_content',
            'acs_save_settings', 
            'acs_test_api_connection',
            'acs_create_post',
            'acs_manual_optimize',
            'acs_bulk_optimize',
            'acs_revalidate_generation',
            'acs_retry_generation'
        ];
        
        foreach ($ajax_actions as $action) {
            add_action("wp_ajax_{$action}", [$this, 'handle_ajax_' . str_replace('acs_', '', $action)]);
        }
    }
    
    /**
     * Add unified admin menu structure
     */
    public function add_unified_menu() {
        $capability = $this->capabilities['generate_content'];
        
        // Check if user has any ACS capabilities before adding menu
        if (!current_user_can($capability)) {
            return;
        }
        
        // Main menu page - Dashboard
        add_menu_page(
            __('AI Content Studio', 'ai-content-studio'),
            __('AI Content Studio', 'ai-content-studio'),
            $capability,
            $this->main_menu_slug,
            [$this, 'render_dashboard'],
            'dashicons-edit',
            26
        );
        
        // First submenu MUST use same slug as parent to rename the auto-generated item
        // This replaces "AI Content Studio" with "Dashboard" in submenu
        add_submenu_page(
            $this->main_menu_slug,
            __('Dashboard', 'ai-content-studio'),
            __('Dashboard', 'ai-content-studio'),
            $capability,
            $this->main_menu_slug, // Same slug as parent = replaces auto-generated item
            [$this, 'render_dashboard']
        );
        
        // Generate Content submenu
        add_submenu_page(
            $this->main_menu_slug,
            __('Generate Content', 'ai-content-studio'),
            __('Generate', 'ai-content-studio'),
            $capability,
            'acs-generate',
            [$this, 'render_generate_page']
        );
        
        // SEO Optimizer submenu
        add_submenu_page(
            $this->main_menu_slug,
            __('SEO Optimizer', 'ai-content-studio'),
            __('SEO Optimizer', 'ai-content-studio'),
            $this->capabilities['manage_seo'],
            'acs-seo-optimizer',
            [$this, 'render_seo_optimizer']
        );
        
        // SEO Optimizer sub-pages (hidden from menu using options.php as parent)
        add_submenu_page(
            'options.php', // Hidden from menu - use non-null parent for PHP 8.3 compatibility
            __('Single Post Optimization', 'ai-content-studio'),
            __('Single Post', 'ai-content-studio'),
            $this->capabilities['manage_seo'],
            'acs-seo-single',
            [$this, 'render_seo_single']
        );
        
        add_submenu_page(
            'options.php', // Hidden from menu - use non-null parent for PHP 8.3 compatibility
            __('Bulk Optimization', 'ai-content-studio'),
            __('Bulk Optimize', 'ai-content-studio'),
            $this->capabilities['manage_seo'],
            'acs-seo-bulk',
            [$this, 'render_seo_bulk']
        );
        
        add_submenu_page(
            'options.php', // Hidden from menu - use non-null parent for PHP 8.3 compatibility
            __('Optimization Reports', 'ai-content-studio'),
            __('Reports', 'ai-content-studio'),
            $this->capabilities['view_analytics'],
            'acs-seo-reports',
            [$this, 'render_seo_reports']
        );
        
        // Analytics & Reports submenu
        add_submenu_page(
            $this->main_menu_slug,
            __('Analytics & Reports', 'ai-content-studio'),
            __('Analytics', 'ai-content-studio'),
            $this->capabilities['view_analytics'],
            'acs-analytics',
            [$this, 'render_analytics']
        );
        
        // Generation Logs submenu
        add_submenu_page(
            $this->main_menu_slug,
            __('Generation Logs', 'ai-content-studio'),
            __('Generation Logs', 'ai-content-studio'),
            $this->capabilities['view_analytics'],
            'acs-generation-logs',
            [$this, 'render_generation_logs']
        );
        
        // Settings submenu
        add_submenu_page(
            $this->main_menu_slug,
            __('Settings', 'ai-content-studio'),
            __('Settings', 'ai-content-studio'),
            $this->capabilities['manage_settings'],
            'acs-settings',
            [$this, 'render_settings']
        );
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on ACS admin pages
        if (strpos($hook, 'acs-') === false && strpos($hook, 'ai-content-studio') === false) {
            return;
        }
        
        // Enqueue WordPress admin dependencies first
        wp_enqueue_style('wp-admin');
        wp_enqueue_style('common');
        wp_enqueue_style('forms');
        wp_enqueue_style('admin-menu');
        wp_enqueue_style('dashboard');
        wp_enqueue_style('list-tables');
        wp_enqueue_style('edit');
        wp_enqueue_style('revisions');
        wp_enqueue_style('media');
        wp_enqueue_style('themes');
        wp_enqueue_style('about');
        wp_enqueue_style('nav-menus');
        wp_enqueue_style('widgets');
        wp_enqueue_style('site-icon');
        wp_enqueue_style('l10n');
        
        // Enqueue modern admin styles that extend WordPress styles
        wp_enqueue_style(
            'acs-unified-admin',
            ACS_PLUGIN_URL . 'admin/css/unified-admin.css',
            ['wp-admin', 'common', 'forms', 'dashboard'], // WordPress dependencies
            $this->version
        );
        
        // Enqueue WordPress admin JavaScript dependencies
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('postbox');
        wp_enqueue_script('dashboard');
        wp_enqueue_script('common');
        wp_enqueue_script('wp-lists');
        wp_enqueue_script('wp-util');
        
        // Enqueue admin JavaScript that extends WordPress functionality
        wp_enqueue_script(
            'acs-unified-admin',
            ACS_PLUGIN_URL . 'admin/js/unified-admin.js',
            ['jquery', 'postbox', 'dashboard', 'common', 'wp-util'], // WordPress dependencies
            $this->version,
            true
        );
        
        // Enqueue interactions module for animations, loading states, and keyboard shortcuts
        wp_enqueue_script(
            'acs-interactions',
            ACS_PLUGIN_URL . 'admin/js/modules/acs-interactions.js',
            ['jquery', 'acs-unified-admin'],
            $this->version,
            true
        );
        
        // Enqueue error handler module for comprehensive error handling
        wp_enqueue_script(
            'acs-error-handler',
            ACS_PLUGIN_URL . 'admin/js/modules/acs-error-handler.js',
            ['jquery', 'acs-unified-admin'],
            $this->version,
            true
        );
        
        // Enqueue form validation module for real-time form validation
        wp_enqueue_script(
            'acs-form-validation',
            ACS_PLUGIN_URL . 'admin/js/modules/acs-form-validation.js',
            ['jquery', 'acs-unified-admin'],
            $this->version,
            true
        );
        
        // Enqueue lazy loading module for performance
        wp_enqueue_script(
            'acs-lazy-load',
            ACS_PLUGIN_URL . 'admin/js/modules/acs-lazy-load.js',
            ['jquery', 'acs-unified-admin'],
            $this->version,
            true
        );
        
        // Get current page for active state
        $current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        
        // Enqueue page-specific assets (conditional loading for performance)
        $this->enqueue_page_specific_assets($current_page);
        
        // Localize script with WordPress-compatible data
        wp_localize_script('acs-unified-admin', 'acsAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('acs_admin_nonce'),
            'currentPage' => $current_page,
            'restUrl' => rest_url('wp/v2/'),
            'restNonce' => wp_create_nonce('wp_rest'),
            'logErrors' => defined('WP_DEBUG') && WP_DEBUG,
            'strings' => [
                'generating' => __('Generating content...', 'ai-content-studio'),
                'success' => __('Operation completed successfully!', 'ai-content-studio'),
                'error' => __('An error occurred. Please try again.', 'ai-content-studio'),
                'confirm' => __('Are you sure?', 'ai-content-studio'),
                'saved' => __('Settings saved.', 'ai-content-studio'),
                'loading' => __('Loading...', 'ai-content-studio'),
                'retry' => __('Retry', 'ai-content-studio'),
                'cancel' => __('Cancel', 'ai-content-studio'),
                'livePreview' => __('Live Preview', 'ai-content-studio'),
                'previewPlaceholder' => __('Fill in the form to see a preview of your content structure', 'ai-content-studio'),
                'analyzing' => __('Analyzing', 'ai-content-studio'),
                'optimizing' => __('Optimizing', 'ai-content-studio'),
                'finalizing' => __('Finalizing', 'ai-content-studio'),
                'required' => __('This field is required', 'ai-content-studio')
            ],
            'adminColors' => $this->get_admin_color_scheme()
        ]);
        
        // Add WordPress admin color scheme compatibility
        $this->add_admin_color_scheme_styles();
        
        // Add inline CSS for active menu state using WordPress patterns
        $this->add_active_menu_styles($current_page);
    }
    
    /**
     * Enqueue page-specific assets for conditional loading.
     *
     * @param string $current_page Current admin page slug.
     */
    private function enqueue_page_specific_assets($current_page) {
        switch ($current_page) {
            case 'acs-generate':
                // Content Generator specific styles
                wp_enqueue_style(
                    'acs-content-generator',
                    ACS_PLUGIN_URL . 'admin/css/content-generator.css',
                    ['acs-unified-admin'],
                    $this->version
                );
                
                // Content Generator specific JavaScript
                wp_enqueue_script(
                    'acs-content-generator',
                    ACS_PLUGIN_URL . 'admin/js/modules/acs-content-generator.js',
                    ['jquery', 'acs-unified-admin'],
                    $this->version,
                    true
                );
                break;
                
            case 'acs-analytics':
                // Chart.js for analytics (only load on analytics page)
                wp_enqueue_script(
                    'chartjs',
                    'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
                    [],
                    '4.4.1',
                    true
                );
                break;
                
            case 'acs-settings':
                // Settings page may need additional validation
                break;
                
            case 'acs-dashboard':
            default:
                // Dashboard-specific assets if needed
                break;
        }
    }
    
    /**
     * Add active menu styles for current page using WordPress native patterns
     */
    private function add_active_menu_styles($current_page) {
        if (empty($current_page) || strpos($current_page, 'acs-') !== 0) {
            return;
        }
        
        // Get WordPress admin color scheme
        $admin_colors = $this->get_admin_color_scheme();
        
        // Add inline style to highlight active menu using WordPress color scheme
        $custom_css = "
            /* WordPress native menu highlighting patterns */
            #adminmenu a[href*='page={$current_page}'] {
                font-weight: 600;
                position: relative;
            }
            #adminmenu .wp-submenu a[href*='page={$current_page}'] {
                color: {$admin_colors['highlight']};
                background-color: {$admin_colors['menu_current']};
            }
            #adminmenu .wp-submenu a[href*='page={$current_page}']::before {
                content: '';
                position: absolute;
                left: 0;
                top: 0;
                bottom: 0;
                width: 4px;
                background: {$admin_colors['highlight']};
                border-radius: 0 2px 2px 0;
            }
            /* WordPress admin notice compatibility */
            .acs-admin-page .notice {
                margin: 5px 15px 2px;
                padding: 1px 12px;
            }
            .acs-admin-page .notice-success {
                border-left-color: #00a32a;
            }
            .acs-admin-page .notice-error {
                border-left-color: #d63638;
            }
            .acs-admin-page .notice-warning {
                border-left-color: #dba617;
            }
            .acs-admin-page .notice-info {
                border-left-color: #72aee6;
            }
        ";
        wp_add_inline_style('acs-unified-admin', $custom_css);
    }
    
    /**
     * Get WordPress admin color scheme
     */
    private function get_admin_color_scheme() {
        global $_wp_admin_css_colors;
        
        $user_color_scheme = get_user_option('admin_color');
        if (empty($user_color_scheme) || !isset($_wp_admin_css_colors[$user_color_scheme])) {
            $user_color_scheme = 'fresh';
        }
        
        // Default WordPress 'fresh' color scheme
        $colors = [
            'highlight' => '#2271b1',
            'menu_current' => '#f0f6fc',
            'menu_submenu_current' => '#c7e8ca',
            'button_primary' => '#2271b1',
            'button_primary_hover' => '#135e96',
            'link' => '#2271b1',
            'link_hover' => '#135e96'
        ];
        
        // Override with user's selected color scheme if available
        if (isset($_wp_admin_css_colors[$user_color_scheme])) {
            $scheme = $_wp_admin_css_colors[$user_color_scheme];
            if (isset($scheme->colors)) {
                $colors['highlight'] = $scheme->colors[2] ?? $colors['highlight'];
                $colors['button_primary'] = $scheme->colors[3] ?? $colors['button_primary'];
            }
        }
        
        return $colors;
    }
    
    /**
     * Add WordPress admin color scheme compatibility styles
     */
    private function add_admin_color_scheme_styles() {
        $colors = $this->get_admin_color_scheme();
        
        $color_css = "
            /* WordPress admin color scheme integration */
            .acs-button--primary {
                background-color: {$colors['button_primary']};
                border-color: {$colors['button_primary']};
            }
            .acs-button--primary:hover {
                background-color: {$colors['button_primary_hover']};
                border-color: {$colors['button_primary_hover']};
            }
            .acs-admin-page a {
                color: {$colors['link']};
            }
            .acs-admin-page a:hover {
                color: {$colors['link_hover']};
            }
            .acs-progress__fill-circle {
                stroke: {$colors['highlight']};
            }
            .acs-badge--success {
                background-color: {$colors['menu_submenu_current']};
                color: #135e96;
            }
        ";
        
        wp_add_inline_style('acs-unified-admin', $color_css);
    }
    
    /**
     * Admin initialization
     */
    public function admin_init() {
        // Register settings using WordPress Settings API
        $this->register_settings();
        
        // Ensure admin capabilities
        $this->ensure_admin_capabilities();
        
        // Initialize WordPress admin notices
        $this->init_admin_notices();
        
        // Add WordPress form validation hooks
        $this->init_form_validation();
    }
    
    /**
     * Initialize WordPress admin notices system
     */
    private function init_admin_notices() {
        add_action('admin_notices', [$this, 'display_admin_notices']);
        add_action('network_admin_notices', [$this, 'display_admin_notices']);
        
        // Handle notice dismissal using WordPress patterns
        add_action('wp_ajax_acs_dismiss_notice', [$this, 'handle_dismiss_notice']);
    }
    
    /**
     * Initialize WordPress form validation patterns
     */
    private function init_form_validation() {
        // Add WordPress form validation hooks
        add_action('admin_post_acs_save_settings', [$this, 'handle_settings_form_submission']);
        add_action('admin_post_nopriv_acs_save_settings', [$this, 'handle_unauthorized_access']);
    }
    
    /**
     * Display admin notices using WordPress native patterns
     */
    public function display_admin_notices() {
        // Only show on ACS pages
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'acs-') === false) {
            return;
        }
        
        // Get stored notices
        $notices = get_transient('acs_admin_notices_' . get_current_user_id());
        if (!$notices || !is_array($notices)) {
            return;
        }
        
        foreach ($notices as $notice_id => $notice) {
            $this->render_wordpress_notice($notice, $notice_id);
        }
        
        // Clear notices after displaying
        delete_transient('acs_admin_notices_' . get_current_user_id());
    }
    
    /**
     * Render WordPress-style admin notice
     */
    private function render_wordpress_notice($notice, $notice_id) {
        $type = $notice['type'] ?? 'info';
        $message = $notice['message'] ?? '';
        $dismissible = $notice['dismissible'] ?? true;
        
        $classes = ['notice', 'notice-' . $type];
        if ($dismissible) {
            $classes[] = 'is-dismissible';
        }
        
        echo '<div class="' . esc_attr(implode(' ', $classes)) . '" data-notice-id="' . esc_attr($notice_id) . '">';
        echo '<p>' . wp_kses_post($message) . '</p>';
        if ($dismissible) {
            echo '<button type="button" class="notice-dismiss">';
            echo '<span class="screen-reader-text">' . esc_html__('Dismiss this notice.', 'ai-content-studio') . '</span>';
            echo '</button>';
        }
        echo '</div>';
    }
    
    /**
     * Add admin notice using WordPress patterns
     */
    public function add_admin_notice($message, $type = 'info', $dismissible = true) {
        $user_id = get_current_user_id();
        $notices = get_transient('acs_admin_notices_' . $user_id) ?: [];
        
        $notice_id = 'acs_notice_' . time() . '_' . wp_rand(1000, 9999);
        $notices[$notice_id] = [
            'message' => $message,
            'type' => $type,
            'dismissible' => $dismissible,
            'timestamp' => time()
        ];
        
        set_transient('acs_admin_notices_' . $user_id, $notices, 300); // 5 minutes
    }
    
    /**
     * Handle notice dismissal
     */
    public function handle_dismiss_notice() {
        check_ajax_referer('acs_admin_nonce', 'nonce');
        
        $notice_id = sanitize_text_field($_POST['notice_id'] ?? '');
        if (empty($notice_id)) {
            wp_die('Invalid notice ID');
        }
        
        // WordPress pattern: store dismissed notices in user meta
        $dismissed_notices = get_user_meta(get_current_user_id(), 'acs_dismissed_notices', true) ?: [];
        $dismissed_notices[] = $notice_id;
        update_user_meta(get_current_user_id(), 'acs_dismissed_notices', $dismissed_notices);
        
        wp_send_json_success();
    }
    
    /**
     * Handle settings form submission using WordPress patterns
     */
    public function handle_settings_form_submission() {
        // Verify nonce using WordPress security patterns
        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'acs_settings_nonce')) {
            wp_die(__('Security check failed. Please try again.', 'ai-content-studio'));
        }
        
        // Check user capabilities
        if (!current_user_can($this->capabilities['manage_settings'])) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'ai-content-studio'));
        }
        
        // Process form data using WordPress sanitization
        $settings = [];
        if (isset($_POST['acs_settings']) && is_array($_POST['acs_settings'])) {
            foreach ($_POST['acs_settings'] as $key => $value) {
                $settings[sanitize_key($key)] = $this->sanitize_setting_value($key, $value);
            }
        }
        
        // Save settings using WordPress Options API
        $result = update_option('acs_settings', $settings);
        
        // Add success/error notice
        if ($result) {
            $this->add_admin_notice(
                __('Settings saved successfully.', 'ai-content-studio'),
                'success'
            );
        } else {
            $this->add_admin_notice(
                __('Failed to save settings. Please try again.', 'ai-content-studio'),
                'error'
            );
        }
        
        // Redirect back to settings page
        wp_redirect(admin_url('admin.php?page=acs-settings'));
        exit;
    }
    
    /**
     * Handle unauthorized access attempts
     */
    public function handle_unauthorized_access() {
        wp_die(__('You must be logged in to perform this action.', 'ai-content-studio'));
    }
    
    /**
     * Sanitize setting value based on type
     */
    private function sanitize_setting_value($key, $value) {
        // Define sanitization rules for different setting types
        $sanitization_rules = [
            'api_key' => 'sanitize_text_field',
            'provider' => 'sanitize_text_field',
            'model' => 'sanitize_text_field',
            'temperature' => 'floatval',
            'max_tokens' => 'absint',
            'enable_seo' => 'rest_sanitize_boolean',
            'auto_publish' => 'rest_sanitize_boolean',
            'prompt_template' => 'wp_kses_post'
        ];
        
        $sanitize_function = $sanitization_rules[$key] ?? 'sanitize_text_field';
        
        if (function_exists($sanitize_function)) {
            return call_user_func($sanitize_function, $value);
        }
        
        return sanitize_text_field($value);
    }
    
    /**
     * Register settings
     */
    private function register_settings() {
        register_setting('acs_settings_group', 'acs_settings');
        register_setting('acs_seo_settings_group', 'acs_seo_settings');
    }
    
    /**
     * Ensure admin capabilities are set
     */
    private function ensure_admin_capabilities() {
        $admin_role = get_role('administrator');
        if ($admin_role) {
            foreach ($this->capabilities as $cap) {
                if (!$admin_role->has_cap($cap)) {
                    $admin_role->add_cap($cap);
                }
            }
        }
    }
    
    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        $screens = ['post', 'page'];
        
        foreach ($screens as $screen) {
            // Generation report meta box
            add_meta_box(
                'acs-generation-report',
                __('AI Generation Report', 'ai-content-studio'),
                [$this, 'render_generation_meta_box'],
                $screen,
                'side',
                'high'
            );
            
            // SEO optimization meta box
            add_meta_box(
                'acs-seo-optimization',
                __('SEO Optimization', 'ai-content-studio'),
                [$this, 'render_seo_meta_box'],
                $screen,
                'side',
                'high'
            );
        }
    }
    
    /**
     * Render dashboard page
     */
    public function render_dashboard() {
        $stats = $this->get_dashboard_stats();
        $recent_posts = $this->get_recent_generated_posts();
        $seo_stats = $this->get_seo_stats();
        
        // Add accessibility enhancements
        $this->add_accessibility_enhancements();
        
        include ACS_PLUGIN_PATH . 'admin/templates/dashboard.php';
    }
    
    /**
     * Add accessibility enhancements to admin pages
     */
    private function add_accessibility_enhancements() {
        // Add skip links and ARIA live regions
        add_action('admin_footer', [$this, 'add_accessibility_markup']);
        
        // Add page-specific ARIA labels
        add_filter('admin_body_class', [$this, 'add_accessibility_body_classes']);
        
        // Enhance form accessibility
        add_action('admin_print_footer_scripts', [$this, 'add_accessibility_scripts']);
    }
    
    /**
     * Add accessibility markup to admin footer
     */
    public function add_accessibility_markup() {
        // Only add on ACS pages
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'acs-') === false) {
            return;
        }
        
        echo '<div id="acs-live-region" class="screen-reader-text" aria-live="polite" aria-atomic="true"></div>';
        echo '<div id="acs-live-region-assertive" class="screen-reader-text" aria-live="assertive" aria-atomic="true"></div>';
        
        // Add skip links if not already present
        echo '<div class="skip-links">';
        echo '<a class="skip-link screen-reader-text" href="#main">' . esc_html__('Skip to main content', 'ai-content-studio') . '</a>';
        echo '<a class="skip-link screen-reader-text" href="#adminmenu">' . esc_html__('Skip to navigation', 'ai-content-studio') . '</a>';
        echo '</div>';
    }
    
    /**
     * Add accessibility-related body classes
     */
    public function add_accessibility_body_classes($classes) {
        $screen = get_current_screen();
        if ($screen && strpos($screen->id, 'acs-') !== false) {
            $classes .= ' acs-admin-page';
        }
        return $classes;
    }
    
    /**
     * Add accessibility enhancement scripts
     */
    public function add_accessibility_scripts() {
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'acs-') === false) {
            return;
        }
        
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Enhance form labels with required indicators
            $('input[required], textarea[required], select[required]').each(function() {
                var $input = $(this);
                var $label = $('label[for="' + $input.attr('id') + '"]');
                
                if ($label.length && !$label.find('.required').length) {
                    $label.append(' <span class="required" aria-label="<?php echo esc_attr__('required', 'ai-content-studio'); ?>">*</span>');
                }
            });
            
            // Add ARIA labels to buttons without text
            $('button:not([aria-label])').each(function() {
                var $button = $(this);
                var $icon = $button.find('.dashicons');
                var text = $button.text().trim();
                
                if (!text && $icon.length) {
                    var iconClass = $icon.attr('class');
                    var label = '';
                    
                    if (iconClass.includes('dashicons-edit')) {
                        label = '<?php echo esc_js__('Edit', 'ai-content-studio'); ?>';
                    } else if (iconClass.includes('dashicons-trash')) {
                        label = '<?php echo esc_js__('Delete', 'ai-content-studio'); ?>';
                    } else if (iconClass.includes('dashicons-visibility')) {
                        label = '<?php echo esc_js__('View', 'ai-content-studio'); ?>';
                    } else if (iconClass.includes('dashicons-admin-settings')) {
                        label = '<?php echo esc_js__('Settings', 'ai-content-studio'); ?>';
                    }
                    
                    if (label) {
                        $button.attr('aria-label', label);
                    }
                }
            });
            
            // Ensure proper heading hierarchy
            var headings = $('.acs-admin-page h1, .acs-admin-page h2, .acs-admin-page h3, .acs-admin-page h4, .acs-admin-page h5, .acs-admin-page h6');
            var currentLevel = 0;
            
            headings.each(function() {
                var $heading = $(this);
                var level = parseInt($heading.prop('tagName').charAt(1));
                
                if (currentLevel === 0) {
                    currentLevel = level;
                } else if (level > currentLevel + 1) {
                    console.warn('Heading hierarchy skip detected: h' + currentLevel + ' to h' + level);
                }
                
                currentLevel = level;
                
                if (!$heading.attr('aria-level')) {
                    $heading.attr('aria-level', level);
                }
            });
            
            // Add semantic roles
            if (!$('.acs-admin-page main, .acs-admin-page [role="main"]').length) {
                $('.acs-admin-page .wrap').attr('role', 'main').attr('id', 'main');
            }
            
            $('.acs-breadcrumbs').attr('role', 'navigation').attr('aria-label', '<?php echo esc_js__('Breadcrumb', 'ai-content-studio'); ?>');
            $('.acs-dashboard-section').attr('role', 'region');
            
            // Enhance table accessibility
            $('table:not([role])').attr('role', 'table');
            $('table th:not([scope])').attr('scope', 'col');
            
            // Add descriptions to form fields
            $('.acs-form-field').each(function() {
                var $field = $(this);
                var $input = $field.find('input, textarea, select');
                var $description = $field.find('.description, .help-text');
                
                if ($input.length && $description.length) {
                    var descId = 'desc-' + Math.random().toString(36).substr(2, 9);
                    $description.attr('id', descId);
                    $input.attr('aria-describedby', descId);
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render generate content page
     */
    public function render_generate_page() {
        // Use the enhanced content generator template
        include ACS_PLUGIN_PATH . 'admin/templates/content-generator.php';
    }
    
    /**
     * Render SEO optimizer main page
     */
    public function render_seo_optimizer() {
        include ACS_PLUGIN_PATH . 'admin/templates/seo-optimizer.php';
    }
    
    /**
     * Render SEO single post optimization
     */
    public function render_seo_single() {
        include ACS_PLUGIN_PATH . 'admin/templates/seo-single.php';
    }
    
    /**
     * Render SEO bulk optimization
     */
    public function render_seo_bulk() {
        include ACS_PLUGIN_PATH . 'admin/templates/seo-bulk.php';
    }
    
    /**
     * Render SEO reports
     */
    public function render_seo_reports() {
        include ACS_PLUGIN_PATH . 'admin/templates/seo-reports.php';
    }
    
    /**
     * Render analytics page
     */
    public function render_analytics() {
        // Use new data-driven analytics dashboard
        include ACS_PLUGIN_PATH . 'admin/templates/analytics-dashboard.php';
    }
    
    /**
     * Render generation logs page
     */
    public function render_generation_logs() {
        // Delegate to existing ACS_Admin if available for backward compatibility
        if (class_exists('ACS_Admin')) {
            $legacy_admin = new ACS_Admin($this->plugin_name, $this->version);
            $legacy_admin->display_generation_logs();
            return;
        }
        
        include ACS_PLUGIN_PATH . 'admin/templates/generation-logs.php';
    }
    
    /**
     * Render settings page
     */
    public function render_settings() {
        include ACS_PLUGIN_PATH . 'admin/templates/settings.php';
    }
    
    /**
     * Render generation report meta box
     */
    public function render_generation_meta_box($post) {
        // Delegate to existing ACS_Admin if available
        if (class_exists('ACS_Admin')) {
            $legacy_admin = new ACS_Admin($this->plugin_name, $this->version);
            $legacy_admin->render_generation_report_meta_box($post);
            return;
        }
        
        $report = get_post_meta($post->ID, '_acs_generation_report', true);
        include ACS_PLUGIN_PATH . 'admin/templates/meta-box-generation.php';
    }
    
    /**
     * Render SEO optimization meta box
     */
    public function render_seo_meta_box($post) {
        $optimization_result = get_post_meta($post->ID, '_acs_optimization_result', true);
        $last_optimized = get_post_meta($post->ID, '_acs_optimization_timestamp', true);
        
        wp_nonce_field('acs_seo_optimization', 'acs_seo_nonce');
        
        include ACS_PLUGIN_PATH . 'admin/templates/meta-box-seo.php';
    }
    
    /**
     * Get dashboard statistics
     */
    private function get_dashboard_stats() {
        return [
            'total_posts' => $this->get_generated_posts_count(),
            'total_optimizations' => $this->get_optimization_count(),
            'avg_seo_score' => $this->get_average_seo_score(),
            'compliance_rate' => $this->get_compliance_rate()
        ];
    }
    
    /**
     * Get recent generated posts
     */
    private function get_recent_generated_posts($limit = 5) {
        return get_posts([
            'post_type' => 'post',
            'posts_per_page' => $limit,
            'meta_key' => '_acs_generated',
            'meta_value' => true,
            'post_status' => ['draft', 'publish', 'private']
        ]);
    }
    
    /**
     * Get SEO statistics
     */
    private function get_seo_stats() {
        global $wpdb;
        
        $results = $wpdb->get_results("
            SELECT pm.meta_value as result
            FROM {$wpdb->postmeta} pm
            WHERE pm.meta_key = '_acs_optimization_result'
        ");
        
        $total = count($results);
        $compliant = 0;
        $total_score = 0;
        
        foreach ($results as $row) {
            $result = maybe_unserialize($row->result);
            $summary = $result['optimizationSummary'] ?? [];
            
            $total_score += $summary['finalScore'] ?? 0;
            if ($summary['complianceAchieved'] ?? false) {
                $compliant++;
            }
        }
        
        return [
            'total_optimizations' => $total,
            'average_score' => $total > 0 ? $total_score / $total : 0,
            'compliance_rate' => $total > 0 ? ($compliant / $total) * 100 : 0
        ];
    }
    
    /**
     * Get generated posts count
     */
    private function get_generated_posts_count() {
        $posts = get_posts([
            'post_type' => 'post',
            'posts_per_page' => -1,
            'meta_key' => '_acs_generated',
            'meta_value' => true,
            'post_status' => ['draft', 'publish', 'private'],
            'fields' => 'ids'
        ]);
        
        return count($posts);
    }
    
    /**
     * Get optimization count
     */
    private function get_optimization_count() {
        global $wpdb;
        
        return $wpdb->get_var("
            SELECT COUNT(*)
            FROM {$wpdb->postmeta}
            WHERE meta_key = '_acs_optimization_result'
        ");
    }
    
    /**
     * Get average SEO score
     */
    private function get_average_seo_score() {
        $stats = $this->get_seo_stats();
        return $stats['average_score'];
    }
    
    /**
     * Get compliance rate
     */
    private function get_compliance_rate() {
        $stats = $this->get_seo_stats();
        return $stats['compliance_rate'];
    }
    
    // AJAX Handlers (delegate to existing classes where possible)
    
    /**
     * Handle generate content AJAX
     */
    public function handle_ajax_generate_content() {
        if (class_exists('ACS_Admin')) {
            $legacy_admin = new ACS_Admin($this->plugin_name, $this->version);
            $legacy_admin->ajax_generate_content();
        }
    }
    
    /**
     * Handle save settings AJAX
     */
    public function handle_ajax_save_settings() {
        if (class_exists('ACS_Admin')) {
            $legacy_admin = new ACS_Admin($this->plugin_name, $this->version);
            $legacy_admin->ajax_save_settings();
        }
    }
    
    /**
     * Handle test API connection AJAX
     */
    public function handle_ajax_test_api_connection() {
        if (class_exists('ACS_Admin')) {
            $legacy_admin = new ACS_Admin($this->plugin_name, $this->version);
            $legacy_admin->ajax_test_api_connection();
        }
    }
    
    /**
     * Handle create post AJAX
     */
    public function handle_ajax_create_post() {
        if (class_exists('ACS_Admin')) {
            $legacy_admin = new ACS_Admin($this->plugin_name, $this->version);
            $legacy_admin->ajax_create_post();
        }
    }
    
    /**
     * Handle manual optimization AJAX
     */
    public function handle_ajax_manual_optimize() {
        if (class_exists('SEOOptimizerAdmin')) {
            global $acs_seo_admin;
            if ($acs_seo_admin) {
                $acs_seo_admin->handleManualOptimization();
            }
        }
    }
    
    /**
     * Handle bulk optimization AJAX
     */
    public function handle_ajax_bulk_optimize() {
        if (class_exists('SEOOptimizerAdmin')) {
            global $acs_seo_admin;
            if ($acs_seo_admin) {
                $acs_seo_admin->handleBulkOptimization();
            }
        }
    }
    
    /**
     * Handle revalidate generation AJAX
     */
    public function handle_ajax_revalidate_generation() {
        if (class_exists('ACS_Admin')) {
            $legacy_admin = new ACS_Admin($this->plugin_name, $this->version);
            $legacy_admin->ajax_revalidate_generation();
        }
    }
    
    /**
     * Handle retry generation AJAX
     */
    public function handle_ajax_retry_generation() {
        if (class_exists('ACS_Admin')) {
            $legacy_admin = new ACS_Admin($this->plugin_name, $this->version);
            $legacy_admin->ajax_retry_generation();
        }
    }
    
    /**
     * Get main menu slug
     */
    public function get_main_menu_slug() {
        return $this->main_menu_slug;
    }
    
    /**
     * Get plugin name
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }
    
    /**
     * Get version
     */
    public function get_version() {
        return $this->version;
    }
}