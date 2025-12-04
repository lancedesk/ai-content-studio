<?php
/**
 * Admin Page Interface
 *
 * Standardized interface for all ACS admin pages to ensure consistent
 * implementation patterns and extensibility.
 *
 * @package AI_Content_Studio
 * @subpackage Admin/Interfaces
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Interface ACS_Admin_Page_Interface
 *
 * Defines the contract for all ACS admin pages
 */
interface ACS_Admin_Page_Interface {
    
    /**
     * Get the page slug
     *
     * @return string The unique page slug
     */
    public function get_page_slug();
    
    /**
     * Get the page title
     *
     * @return string The page title
     */
    public function get_page_title();
    
    /**
     * Get the required capability for this page
     *
     * @return string The WordPress capability required to access this page
     */
    public function get_required_capability();
    
    /**
     * Get the parent menu slug (if this is a submenu page)
     *
     * @return string|null The parent menu slug or null for top-level pages
     */
    public function get_parent_slug();
    
    /**
     * Get the menu title (displayed in the admin menu)
     *
     * @return string The menu title
     */
    public function get_menu_title();
    
    /**
     * Get the menu icon (for top-level pages)
     *
     * @return string The dashicon class or URL to icon
     */
    public function get_menu_icon();
    
    /**
     * Get the menu position (for top-level pages)
     *
     * @return int|null The menu position
     */
    public function get_menu_position();
    
    /**
     * Register the admin page with WordPress
     *
     * @return string|false The hook suffix or false on failure
     */
    public function register();
    
    /**
     * Render the page content
     *
     * @return void
     */
    public function render();
    
    /**
     * Enqueue page-specific assets
     *
     * @param string $hook_suffix The current admin page hook suffix
     * @return void
     */
    public function enqueue_assets($hook_suffix);
    
    /**
     * Handle AJAX requests for this page
     *
     * @return void
     */
    public function handle_ajax();
    
    /**
     * Get page-specific hooks and filters
     *
     * @return array Array of hooks and filters to register
     */
    public function get_hooks();
    
    /**
     * Initialize the page (called after WordPress init)
     *
     * @return void
     */
    public function init();
    
    /**
     * Get page configuration array
     *
     * @return array Configuration array with all page settings
     */
    public function get_config();
}

/**
 * Abstract Base Admin Page Class
 *
 * Provides common functionality for all ACS admin pages
 */
abstract class ACS_Base_Admin_Page implements ACS_Admin_Page_Interface {
    
    /**
     * @var string Page slug
     */
    protected $page_slug;
    
    /**
     * @var string Page title
     */
    protected $page_title;
    
    /**
     * @var string Required capability
     */
    protected $capability;
    
    /**
     * @var string|null Parent menu slug
     */
    protected $parent_slug;
    
    /**
     * @var string Menu title
     */
    protected $menu_title;
    
    /**
     * @var string Menu icon
     */
    protected $menu_icon = 'dashicons-admin-generic';
    
    /**
     * @var int|null Menu position
     */
    protected $menu_position;
    
    /**
     * @var ACS_Component_Renderer Component renderer instance
     */
    protected $renderer;
    
    /**
     * @var array Page configuration
     */
    protected $config = [];
    
    /**
     * Constructor
     *
     * @param array $config Page configuration
     */
    public function __construct($config = []) {
        $this->config = wp_parse_args($config, $this->get_default_config());
        
        $this->page_slug = $this->config['slug'];
        $this->page_title = $this->config['title'];
        $this->capability = $this->config['capability'];
        $this->parent_slug = $this->config['parent_slug'];
        $this->menu_title = $this->config['menu_title'] ?: $this->page_title;
        $this->menu_icon = $this->config['menu_icon'];
        $this->menu_position = $this->config['menu_position'];
        
        $this->init_renderer();
        $this->register_hooks();
    }
    
    /**
     * Get default configuration
     *
     * @return array Default configuration array
     */
    protected function get_default_config() {
        return [
            'slug' => 'acs-admin-page',
            'title' => __('ACS Admin Page', 'ai-content-studio'),
            'capability' => 'manage_options',
            'parent_slug' => null,
            'menu_title' => '',
            'menu_icon' => 'dashicons-admin-generic',
            'menu_position' => null,
            'show_in_menu' => true,
            'show_in_admin_bar' => false,
            'supports_sidebar' => false,
            'breadcrumbs' => [],
            'page_actions' => [],
            'assets' => [
                'css' => [],
                'js' => []
            ]
        ];
    }
    
    /**
     * Initialize component renderer
     */
    protected function init_renderer() {
        if (!class_exists('ACS_Component_Renderer')) {
            require_once ACS_PLUGIN_PATH . 'admin/class-acs-component-renderer.php';
        }
        $this->renderer = new ACS_Component_Renderer();
    }
    
    /**
     * Register WordPress hooks
     */
    protected function register_hooks() {
        // Register admin menu
        add_action('admin_menu', [$this, 'register']);
        
        // Enqueue assets
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        
        // Register page-specific hooks
        $hooks = $this->get_hooks();
        foreach ($hooks as $hook => $callback) {
            if (is_array($callback)) {
                add_action($hook, $callback[0], $callback[1] ?? 10, $callback[2] ?? 1);
            } else {
                add_action($hook, $callback);
            }
        }
        
        // Initialize after WordPress init
        add_action('init', [$this, 'init']);
    }
    
    /**
     * Get page slug
     */
    public function get_page_slug() {
        return $this->page_slug;
    }
    
    /**
     * Get page title
     */
    public function get_page_title() {
        return $this->page_title;
    }
    
    /**
     * Get required capability
     */
    public function get_required_capability() {
        return $this->capability;
    }
    
    /**
     * Get parent menu slug
     */
    public function get_parent_slug() {
        return $this->parent_slug;
    }
    
    /**
     * Get menu title
     */
    public function get_menu_title() {
        return $this->menu_title;
    }
    
    /**
     * Get menu icon
     */
    public function get_menu_icon() {
        return $this->menu_icon;
    }
    
    /**
     * Get menu position
     */
    public function get_menu_position() {
        return $this->menu_position;
    }
    
    /**
     * Register the admin page
     */
    public function register() {
        if (!$this->config['show_in_menu']) {
            return false;
        }
        
        if ($this->parent_slug) {
            // Submenu page
            return add_submenu_page(
                $this->parent_slug,
                $this->page_title,
                $this->menu_title,
                $this->capability,
                $this->page_slug,
                [$this, 'render']
            );
        } else {
            // Top-level menu page
            return add_menu_page(
                $this->page_title,
                $this->menu_title,
                $this->capability,
                $this->page_slug,
                [$this, 'render'],
                $this->menu_icon,
                $this->menu_position
            );
        }
    }
    
    /**
     * Render the page using the base template
     */
    public function render() {
        // Security check
        if (!current_user_can($this->capability)) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'ai-content-studio'));
        }
        
        // Set template variables
        $page_title = $this->page_title;
        $page_description = $this->get_page_description();
        $breadcrumbs = $this->get_breadcrumbs();
        $page_actions = $this->get_page_actions();
        $content_callback = [$this, 'render_content'];
        $sidebar_callback = $this->config['supports_sidebar'] ? [$this, 'render_sidebar'] : null;
        $page_slug = $this->page_slug;
        $page_capability = $this->capability;
        
        // Include the base template
        include ACS_PLUGIN_PATH . 'admin/templates/base-admin-page.php';
    }
    
    /**
     * Enqueue page-specific assets
     */
    public function enqueue_assets($hook_suffix) {
        // Only enqueue on this page
        if (!$this->is_current_page($hook_suffix)) {
            return;
        }
        
        // Enqueue CSS assets
        foreach ($this->config['assets']['css'] as $handle => $asset) {
            wp_enqueue_style(
                $handle,
                $asset['src'],
                $asset['deps'] ?? [],
                $asset['version'] ?? '1.0.0',
                $asset['media'] ?? 'all'
            );
        }
        
        // Enqueue JavaScript assets
        foreach ($this->config['assets']['js'] as $handle => $asset) {
            wp_enqueue_script(
                $handle,
                $asset['src'],
                $asset['deps'] ?? ['jquery'],
                $asset['version'] ?? '1.0.0',
                $asset['in_footer'] ?? true
            );
            
            // Localize script if data provided
            if (!empty($asset['localize'])) {
                wp_localize_script(
                    $handle,
                    $asset['localize']['object_name'],
                    $asset['localize']['data']
                );
            }
        }
    }
    
    /**
     * Check if current page is this admin page
     */
    protected function is_current_page($hook_suffix) {
        return strpos($hook_suffix, $this->page_slug) !== false;
    }
    
    /**
     * Default AJAX handler (override in child classes)
     */
    public function handle_ajax() {
        // Override in child classes
    }
    
    /**
     * Default hooks (override in child classes)
     */
    public function get_hooks() {
        return [];
    }
    
    /**
     * Default initialization (override in child classes)
     */
    public function init() {
        // Override in child classes
    }
    
    /**
     * Get page configuration
     */
    public function get_config() {
        return $this->config;
    }
    
    /**
     * Get page description (override in child classes)
     */
    protected function get_page_description() {
        return '';
    }
    
    /**
     * Get breadcrumbs (override in child classes)
     */
    protected function get_breadcrumbs() {
        return $this->config['breadcrumbs'];
    }
    
    /**
     * Get page actions (override in child classes)
     */
    protected function get_page_actions() {
        return $this->config['page_actions'];
    }
    
    /**
     * Render main content (must be implemented in child classes)
     */
    abstract public function render_content();
    
    /**
     * Render sidebar content (override in child classes if sidebar is supported)
     */
    public function render_sidebar() {
        if ($this->config['supports_sidebar']) {
            echo '<p>' . esc_html__('Sidebar content not implemented.', 'ai-content-studio') . '</p>';
        }
    }
    
    /**
     * Add admin notice
     */
    protected function add_notice($message, $type = 'info', $dismissible = true) {
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
     * Get current user's admin color scheme
     */
    protected function get_admin_color_scheme() {
        global $_wp_admin_css_colors;
        
        $user_color_scheme = get_user_option('admin_color');
        if (empty($user_color_scheme) || !isset($_wp_admin_css_colors[$user_color_scheme])) {
            $user_color_scheme = 'fresh';
        }
        
        return $user_color_scheme;
    }
    
    /**
     * Render a standardized form using the component renderer
     */
    protected function render_form($form_config) {
        if (!is_array($form_config) || empty($form_config['fields'])) {
            return '';
        }
        
        $form_html = '<form method="' . esc_attr($form_config['method'] ?? 'post') . '"';
        if (!empty($form_config['action'])) {
            $form_html .= ' action="' . esc_url($form_config['action']) . '"';
        }
        if (!empty($form_config['id'])) {
            $form_html .= ' id="' . esc_attr($form_config['id']) . '"';
        }
        if (!empty($form_config['class'])) {
            $form_html .= ' class="' . esc_attr($form_config['class']) . '"';
        }
        $form_html .= '>';
        
        // Add nonce field if specified
        if (!empty($form_config['nonce'])) {
            $form_html .= wp_nonce_field($form_config['nonce']['action'], $form_config['nonce']['name'], true, false);
        }
        
        // Render form groups
        if (!empty($form_config['groups'])) {
            foreach ($form_config['groups'] as $group) {
                $form_html .= $this->renderer->render_form_group($group);
            }
        } else {
            // Render individual fields
            foreach ($form_config['fields'] as $field) {
                $form_html .= $this->renderer->render_form_field($field);
            }
        }
        
        // Add submit button if specified
        if (!empty($form_config['submit'])) {
            $submit_config = wp_parse_args($form_config['submit'], [
                'text' => __('Save Changes', 'ai-content-studio'),
                'variant' => 'primary',
                'use_wp_classes' => true
            ]);
            $form_html .= '<p class="submit">' . $this->renderer->render_button($submit_config) . '</p>';
        }
        
        $form_html .= '</form>';
        
        return $form_html;
    }
}