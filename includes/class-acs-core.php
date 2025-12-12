<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://lancedesk.com
 * @since      1.0.0
 *
 * @package    ACS
 * @subpackage ACS/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    ACS
 * @subpackage ACS/includes
 * @author     LanceDesk <support@lancedesk.com>
 */
class ACS_Core {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      ACS_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct() {
        if ( defined( 'ACS_VERSION' ) ) {
            $this->version = ACS_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'ai-content-studio';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Load essential files for basic functionality, plus AI providers.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        if (file_exists(ACS_PLUGIN_PATH . 'includes/class-acs-loader.php')) {
            require_once ACS_PLUGIN_PATH . 'includes/class-acs-loader.php';
            $this->loader = new ACS_Loader();
        }

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        if (file_exists(ACS_PLUGIN_PATH . 'includes/class-acs-i18n.php')) {
            require_once ACS_PLUGIN_PATH . 'includes/class-acs-i18n.php';
        }
        
        /**
         * Security and encryption utilities
         */
        if (file_exists(ACS_PLUGIN_PATH . 'security/class-acs-encryption.php')) {
            require_once ACS_PLUGIN_PATH . 'security/class-acs-encryption.php';
        }
        if (file_exists(ACS_PLUGIN_PATH . 'security/class-acs-sanitizer.php')) {
            require_once ACS_PLUGIN_PATH . 'security/class-acs-sanitizer.php';
        }
        if (file_exists(ACS_PLUGIN_PATH . 'security/class-acs-validator.php')) {
            require_once ACS_PLUGIN_PATH . 'security/class-acs-validator.php';
        }
        
        /**
         * Error handling system
         */
        if (file_exists(ACS_PLUGIN_PATH . 'includes/class-acs-error-handler.php')) {
            require_once ACS_PLUGIN_PATH . 'includes/class-acs-error-handler.php';
            if (class_exists('ACS_Error_Handler')) {
                ACS_Error_Handler::register_hooks();
            }
        }
        
        /**
         * Performance optimization
         */
        if (file_exists(ACS_PLUGIN_PATH . 'includes/class-acs-performance.php')) {
            require_once ACS_PLUGIN_PATH . 'includes/class-acs-performance.php';
            ACS_Performance::get_instance();
        }
        
        /**
         * AI Provider system
         */
        if (file_exists(ACS_PLUGIN_PATH . 'api/class-acs-ai-provider.php')) {
            require_once ACS_PLUGIN_PATH . 'api/class-acs-ai-provider.php';
        }
        if (file_exists(ACS_PLUGIN_PATH . 'api/providers/class-acs-groq.php')) {
            require_once ACS_PLUGIN_PATH . 'api/providers/class-acs-groq.php';
        }
        // Load REST controllers
        if ( file_exists( ACS_PLUGIN_PATH . 'includes/rest/class-acs-rest.php' ) ) {
            require_once ACS_PLUGIN_PATH . 'includes/rest/class-acs-rest.php';
        }
        // Load settings module if present
        if ( file_exists( ACS_PLUGIN_PATH . 'admin/class-acs-settings.php' ) ) {
            require_once ACS_PLUGIN_PATH . 'admin/class-acs-settings.php';
        }
        
        /**
         * Load the unified admin controller (modern UI)
         */
        if ( file_exists( ACS_PLUGIN_PATH . 'admin/class-acs-unified-admin.php' ) ) {
            require_once ACS_PLUGIN_PATH . 'admin/class-acs-unified-admin.php';
        }
        
        /**
         * Load the legacy admin class for backwards compatibility (AJAX handlers)
         */
        if ( file_exists( ACS_PLUGIN_PATH . 'admin/class-acs-admin.php' ) ) {
            require_once ACS_PLUGIN_PATH . 'admin/class-acs-admin.php';
        }

        // Backwards-compatible: also include a simple admin shim if present
        if ( file_exists( ACS_PLUGIN_PATH . 'admin/class-acs-admin-simple.php' ) ) {
            require_once ACS_PLUGIN_PATH . 'admin/class-acs-admin-simple.php';
        }
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the ACS_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {
        if (class_exists('ACS_i18n') && $this->loader) {
            $plugin_i18n = new ACS_i18n();
            $this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
        }
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        if (!$this->loader) return;
        
        // Use the UNIFIED Admin for menus and page rendering (modern UI)
        // The singleton pattern ensures hooks are only registered once
        if (class_exists('ACS_Unified_Admin')) {
            $unified_admin = ACS_Unified_Admin::get_instance($this->get_plugin_name(), $this->get_version());
            // Hooks are already registered in the singleton constructor
            // No need to add them again via the loader
        }
        
        // Use legacy ACS_Admin for AJAX handlers (backwards compatibility)
        if (class_exists('ACS_Admin')) {
            $plugin_admin = new ACS_Admin( $this->get_plugin_name(), $this->get_version() );
            
            // Don't register menu from old admin - unified admin handles that
            // $this->loader->add_action( 'admin_menu', $plugin_admin, 'add_admin_menu' );

            // Enqueue admin scripts and styles (for nonce and AJAX calls)
            $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
            $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

            // Initialize admin settings and other admin init hooks
            $this->loader->add_action( 'admin_init', $plugin_admin, 'admin_init' );

            // AJAX handlers for admin-facing AJAX calls
            $this->loader->add_action( 'wp_ajax_acs_generate_content', $plugin_admin, 'ajax_generate_content' );
            $this->loader->add_action( 'wp_ajax_acs_save_settings', $plugin_admin, 'ajax_save_settings' );
            $this->loader->add_action( 'wp_ajax_acs_test_api_connection', $plugin_admin, 'ajax_test_api_connection' );
            $this->loader->add_action( 'wp_ajax_acs_get_keyword_suggestions', $plugin_admin, 'ajax_get_keyword_suggestions' );
            $this->loader->add_action( 'wp_ajax_acs_create_post', $plugin_admin, 'ajax_create_post' );

            // Ensure administrator role has plugin capabilities so menu is visible
            if ( function_exists( 'get_role' ) ) {
                $admin_role = get_role( 'administrator' );
                if ( $admin_role ) {
                    $caps = array( 'acs_generate_content', 'acs_manage_settings', 'acs_view_analytics', 'acs_manage_seo' );
                    foreach ( $caps as $cap ) {
                        if ( ! $admin_role->has_cap( $cap ) ) {
                            $admin_role->add_cap( $cap );
                        }
                    }
                }
            }
        }

        // Register settings module hooks if class is available
        if ( class_exists( 'ACS_Settings' ) ) {
            $settings = new ACS_Settings( $this->get_plugin_name(), $this->get_version() );
            // Settings register their own admin_init and AJAX hooks
            $settings->register_hooks();
        }
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {
        if (!$this->loader) return;
        
        // Only register public hooks if public classes exist
        if (class_exists('ACS_Public')) {
            $plugin_public = new ACS_Public( $this->get_plugin_name(), $this->get_version() );
            $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
        }
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        if ($this->loader) {
            $this->loader->run();
        }
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    ACS_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }
}