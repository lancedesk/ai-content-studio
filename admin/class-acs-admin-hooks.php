<?php
/**
 * Admin Hooks and Filters for Extensibility
 *
 * Provides WordPress hooks and filters for customizing the admin interface
 * following modular architecture patterns.
 *
 * @package AI_Content_Studio
 * @subpackage Admin
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class ACS_Admin_Hooks
 *
 * Manages hooks and filters for admin interface extensibility
 */
class ACS_Admin_Hooks {
    
    /**
     * Initialize hooks
     */
    public static function init() {
        // Admin menu hooks
        add_filter('acs_admin_menu_items', [__CLASS__, 'filter_menu_items'], 10, 1);
        add_filter('acs_admin_submenu_items', [__CLASS__, 'filter_submenu_items'], 10, 2);
        add_action('acs_before_admin_menu', [__CLASS__, 'before_admin_menu']);
        add_action('acs_after_admin_menu', [__CLASS__, 'after_admin_menu']);
        
        // Component rendering hooks
        add_filter('acs_component_card_props', [__CLASS__, 'filter_card_props'], 10, 2);
        add_filter('acs_component_table_props', [__CLASS__, 'filter_table_props'], 10, 2);
        add_filter('acs_component_button_props', [__CLASS__, 'filter_button_props'], 10, 2);
        add_action('acs_before_component_render', [__CLASS__, 'before_component_render'], 10, 2);
        add_action('acs_after_component_render', [__CLASS__, 'after_component_render'], 10, 2);
        
        // Page rendering hooks
        add_action('acs_before_page_render', [__CLASS__, 'before_page_render'], 10, 1);
        add_action('acs_after_page_render', [__CLASS__, 'after_page_render'], 10, 1);
        add_action('acs_page_header', [__CLASS__, 'page_header'], 10, 1);
        add_action('acs_page_footer', [__CLASS__, 'page_footer'], 10, 1);
        
        // Asset enqueuing hooks
        add_filter('acs_admin_styles', [__CLASS__, 'filter_admin_styles'], 10, 1);
        add_filter('acs_admin_scripts', [__CLASS__, 'filter_admin_scripts'], 10, 1);
        add_action('acs_enqueue_admin_assets', [__CLASS__, 'enqueue_admin_assets'], 10, 1);
        
        // Dashboard hooks
        add_filter('acs_dashboard_cards', [__CLASS__, 'filter_dashboard_cards'], 10, 1);
        add_filter('acs_dashboard_stats', [__CLASS__, 'filter_dashboard_stats'], 10, 1);
        add_action('acs_dashboard_before_cards', [__CLASS__, 'dashboard_before_cards']);
        add_action('acs_dashboard_after_cards', [__CLASS__, 'dashboard_after_cards']);
        
        // Settings hooks
        add_filter('acs_settings_sections', [__CLASS__, 'filter_settings_sections'], 10, 1);
        add_filter('acs_settings_fields', [__CLASS__, 'filter_settings_fields'], 10, 2);
        add_action('acs_settings_page_top', [__CLASS__, 'settings_page_top']);
        add_action('acs_settings_page_bottom', [__CLASS__, 'settings_page_bottom']);
    }
    
    /**
     * Filter admin menu items
     *
     * Allows modification of main menu items
     *
     * @param array $items Menu items
     * @return array Modified menu items
     */
    public static function filter_menu_items($items) {
        /**
         * Filter the admin menu items
         *
         * @param array $items Array of menu items
         * 
         * Example:
         * add_filter('acs_admin_menu_items', function($items) {
         *     $items[] = [
         *         'page_title' => 'Custom Page',
         *         'menu_title' => 'Custom',
         *         'capability' => 'manage_options',
         *         'menu_slug' => 'acs-custom',
         *         'callback' => 'my_custom_page_callback'
         *     ];
         *     return $items;
         * });
         */
        return apply_filters('acs_filter_admin_menu_items', $items);
    }
    
    /**
     * Filter admin submenu items
     *
     * Allows modification of submenu items for a specific parent
     *
     * @param array $items Submenu items
     * @param string $parent_slug Parent menu slug
     * @return array Modified submenu items
     */
    public static function filter_submenu_items($items, $parent_slug) {
        /**
         * Filter the admin submenu items
         *
         * @param array $items Array of submenu items
         * @param string $parent_slug Parent menu slug
         */
        return apply_filters('acs_filter_admin_submenu_items', $items, $parent_slug);
    }
    
    /**
     * Action before admin menu registration
     */
    public static function before_admin_menu() {
        /**
         * Fires before the admin menu is registered
         *
         * Use this to perform actions before menu registration
         */
        do_action('acs_action_before_admin_menu');
    }
    
    /**
     * Action after admin menu registration
     */
    public static function after_admin_menu() {
        /**
         * Fires after the admin menu is registered
         *
         * Use this to perform actions after menu registration
         */
        do_action('acs_action_after_admin_menu');
    }
    
    /**
     * Filter card component properties
     *
     * @param array $props Card properties
     * @param string $context Context where card is being rendered
     * @return array Modified properties
     */
    public static function filter_card_props($props, $context = '') {
        /**
         * Filter card component properties
         *
         * @param array $props Card properties
         * @param string $context Context identifier
         */
        return apply_filters('acs_filter_card_props', $props, $context);
    }
    
    /**
     * Filter table component properties
     *
     * @param array $props Table properties
     * @param string $context Context where table is being rendered
     * @return array Modified properties
     */
    public static function filter_table_props($props, $context = '') {
        /**
         * Filter table component properties
         *
         * @param array $props Table properties
         * @param string $context Context identifier
         */
        return apply_filters('acs_filter_table_props', $props, $context);
    }
    
    /**
     * Filter button component properties
     *
     * @param array $props Button properties
     * @param string $context Context where button is being rendered
     * @return array Modified properties
     */
    public static function filter_button_props($props, $context = '') {
        /**
         * Filter button component properties
         *
         * @param array $props Button properties
         * @param string $context Context identifier
         */
        return apply_filters('acs_filter_button_props', $props, $context);
    }
    
    /**
     * Action before component render
     *
     * @param string $component_type Type of component
     * @param array $props Component properties
     */
    public static function before_component_render($component_type, $props) {
        /**
         * Fires before a component is rendered
         *
         * @param string $component_type Component type (card, table, button, etc.)
         * @param array $props Component properties
         */
        do_action('acs_action_before_component_render', $component_type, $props);
    }
    
    /**
     * Action after component render
     *
     * @param string $component_type Type of component
     * @param string $output Rendered HTML output
     */
    public static function after_component_render($component_type, $output) {
        /**
         * Fires after a component is rendered
         *
         * @param string $component_type Component type
         * @param string $output Rendered HTML
         */
        do_action('acs_action_after_component_render', $component_type, $output);
    }
    
    /**
     * Action before page render
     *
     * @param string $page_slug Page slug
     */
    public static function before_page_render($page_slug) {
        /**
         * Fires before an admin page is rendered
         *
         * @param string $page_slug Page slug identifier
         */
        do_action('acs_action_before_page_render', $page_slug);
        do_action("acs_action_before_{$page_slug}_render");
    }
    
    /**
     * Action after page render
     *
     * @param string $page_slug Page slug
     */
    public static function after_page_render($page_slug) {
        /**
         * Fires after an admin page is rendered
         *
         * @param string $page_slug Page slug identifier
         */
        do_action('acs_action_after_page_render', $page_slug);
        do_action("acs_action_after_{$page_slug}_render");
    }
    
    /**
     * Action in page header
     *
     * @param string $page_slug Page slug
     */
    public static function page_header($page_slug) {
        /**
         * Fires in the page header area
         *
         * @param string $page_slug Page slug identifier
         */
        do_action('acs_action_page_header', $page_slug);
        do_action("acs_action_{$page_slug}_header");
    }
    
    /**
     * Action in page footer
     *
     * @param string $page_slug Page slug
     */
    public static function page_footer($page_slug) {
        /**
         * Fires in the page footer area
         *
         * @param string $page_slug Page slug identifier
         */
        do_action('acs_action_page_footer', $page_slug);
        do_action("acs_action_{$page_slug}_footer");
    }
    
    /**
     * Filter admin styles
     *
     * @param array $styles Array of style handles and paths
     * @return array Modified styles array
     */
    public static function filter_admin_styles($styles) {
        /**
         * Filter admin stylesheet handles
         *
         * @param array $styles Array of style handles
         * 
         * Example:
         * add_filter('acs_admin_styles', function($styles) {
         *     $styles['my-custom-style'] = [
         *         'src' => plugin_dir_url(__FILE__) . 'css/custom.css',
         *         'deps' => ['acs-admin-styles'],
         *         'version' => '1.0.0'
         *     ];
         *     return $styles;
         * });
         */
        return apply_filters('acs_filter_admin_styles', $styles);
    }
    
    /**
     * Filter admin scripts
     *
     * @param array $scripts Array of script handles and paths
     * @return array Modified scripts array
     */
    public static function filter_admin_scripts($scripts) {
        /**
         * Filter admin script handles
         *
         * @param array $scripts Array of script handles
         * 
         * Example:
         * add_filter('acs_admin_scripts', function($scripts) {
         *     $scripts['my-custom-script'] = [
         *         'src' => plugin_dir_url(__FILE__) . 'js/custom.js',
         *         'deps' => ['jquery', 'acs-admin-core'],
         *         'version' => '1.0.0',
         *         'in_footer' => true
         *     ];
         *     return $scripts;
         * });
         */
        return apply_filters('acs_filter_admin_scripts', $scripts);
    }
    
    /**
     * Action when enqueuing admin assets
     *
     * @param string $page_slug Current page slug
     */
    public static function enqueue_admin_assets($page_slug) {
        /**
         * Fires when admin assets are being enqueued
         *
         * @param string $page_slug Current page slug
         */
        do_action('acs_action_enqueue_admin_assets', $page_slug);
    }
    
    /**
     * Filter dashboard cards
     *
     * @param array $cards Array of dashboard card configurations
     * @return array Modified cards array
     */
    public static function filter_dashboard_cards($cards) {
        /**
         * Filter dashboard cards
         *
         * @param array $cards Array of card configurations
         * 
         * Example:
         * add_filter('acs_dashboard_cards', function($cards) {
         *     $cards[] = [
         *         'id' => 'custom-card',
         *         'title' => 'Custom Card',
         *         'content' => 'Custom content here',
         *         'variant' => 'stat',
         *         'order' => 10
         *     ];
         *     return $cards;
         * });
         */
        return apply_filters('acs_filter_dashboard_cards', $cards);
    }
    
    /**
     * Filter dashboard statistics
     *
     * @param array $stats Array of dashboard statistics
     * @return array Modified stats array
     */
    public static function filter_dashboard_stats($stats) {
        /**
         * Filter dashboard statistics
         *
         * @param array $stats Array of statistics
         */
        return apply_filters('acs_filter_dashboard_stats', $stats);
    }
    
    /**
     * Action before dashboard cards
     */
    public static function dashboard_before_cards() {
        /**
         * Fires before dashboard cards are rendered
         */
        do_action('acs_action_dashboard_before_cards');
    }
    
    /**
     * Action after dashboard cards
     */
    public static function dashboard_after_cards() {
        /**
         * Fires after dashboard cards are rendered
         */
        do_action('acs_action_dashboard_after_cards');
    }
    
    /**
     * Filter settings sections
     *
     * @param array $sections Array of settings sections
     * @return array Modified sections array
     */
    public static function filter_settings_sections($sections) {
        /**
         * Filter settings sections
         *
         * @param array $sections Array of section configurations
         * 
         * Example:
         * add_filter('acs_settings_sections', function($sections) {
         *     $sections[] = [
         *         'id' => 'custom-section',
         *         'title' => 'Custom Settings',
         *         'description' => 'Custom settings description'
         *     ];
         *     return $sections;
         * });
         */
        return apply_filters('acs_filter_settings_sections', $sections);
    }
    
    /**
     * Filter settings fields
     *
     * @param array $fields Array of settings fields
     * @param string $section_id Section identifier
     * @return array Modified fields array
     */
    public static function filter_settings_fields($fields, $section_id) {
        /**
         * Filter settings fields for a specific section
         *
         * @param array $fields Array of field configurations
         * @param string $section_id Section identifier
         */
        return apply_filters('acs_filter_settings_fields', $fields, $section_id);
    }
    
    /**
     * Action at top of settings page
     */
    public static function settings_page_top() {
        /**
         * Fires at the top of the settings page
         */
        do_action('acs_action_settings_page_top');
    }
    
    /**
     * Action at bottom of settings page
     */
    public static function settings_page_bottom() {
        /**
         * Fires at the bottom of the settings page
         */
        do_action('acs_action_settings_page_bottom');
    }
    
    /**
     * Register a custom admin page
     *
     * Helper method for third-party integrations
     *
     * @param array $page_config Page configuration
     * @return bool Success status
     */
    public static function register_custom_page($page_config) {
        $defaults = [
            'page_title' => '',
            'menu_title' => '',
            'capability' => 'manage_options',
            'menu_slug' => '',
            'callback' => null,
            'parent_slug' => 'ai-content-studio',
            'position' => null
        ];
        
        $config = wp_parse_args($page_config, $defaults);
        
        if (empty($config['menu_slug']) || !is_callable($config['callback'])) {
            return false;
        }
        
        add_action('admin_menu', function() use ($config) {
            add_submenu_page(
                $config['parent_slug'],
                $config['page_title'],
                $config['menu_title'],
                $config['capability'],
                $config['menu_slug'],
                $config['callback'],
                $config['position']
            );
        }, 20);
        
        return true;
    }
    
    /**
     * Register a custom dashboard widget
     *
     * Helper method for adding custom dashboard widgets
     *
     * @param array $widget_config Widget configuration
     * @return bool Success status
     */
    public static function register_dashboard_widget($widget_config) {
        $defaults = [
            'id' => '',
            'title' => '',
            'callback' => null,
            'order' => 10
        ];
        
        $config = wp_parse_args($widget_config, $defaults);
        
        if (empty($config['id']) || !is_callable($config['callback'])) {
            return false;
        }
        
        add_filter('acs_dashboard_cards', function($cards) use ($config) {
            $cards[] = [
                'id' => $config['id'],
                'title' => $config['title'],
                'content' => call_user_func($config['callback']),
                'order' => $config['order']
            ];
            return $cards;
        });
        
        return true;
    }
}

// Initialize hooks
ACS_Admin_Hooks::init();
