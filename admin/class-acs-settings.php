<?php
/**
 * Settings management for AI Content Studio
 *
 * @package ACS
 */
class ACS_Settings {

    /**
     * Plugin name
     * @var string
     */
    private $plugin_name;

    /**
     * Version
     * @var string
     */
    private $version;

    public function __construct( $plugin_name = 'ai-content-studio', $version = '1.0.0' ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register hooks for settings
     */
    public function register_hooks() {
        add_action( 'admin_init', array( $this, 'admin_init' ) );
        add_action( 'wp_ajax_acs_save_settings', array( $this, 'ajax_save_settings' ) );
        add_action( 'wp_ajax_acs_test_api_connection', array( $this, 'ajax_test_api_connection' ) );
        add_action( 'wp_ajax_acs_get_keyword_suggestions', array( $this, 'ajax_get_keyword_suggestions' ) );
        add_action( 'wp_ajax_acs_export_settings', array( $this, 'ajax_export_settings' ) );
        add_action( 'wp_ajax_acs_import_settings', array( $this, 'ajax_import_settings' ) );
        add_action( 'wp_ajax_acs_reset_settings', array( $this, 'ajax_reset_settings' ) );
        add_action( 'wp_ajax_acs_log_client_error', array( $this, 'ajax_log_client_error' ) );
        add_action( 'wp_ajax_acs_get_dashboard_metrics', array( $this, 'ajax_get_dashboard_metrics' ) );
        add_action( 'wp_ajax_acs_clear_cache', array( $this, 'ajax_clear_cache' ) );
        // Show admin notice if essential provider keys are not configured
        add_action( 'admin_notices', array( $this, 'missing_api_key_notice' ) );
    }

    /**
     * Register settings and sections
     */
    public function admin_init() {
        register_setting( 'acs_settings_group', 'acs_settings', array( $this, 'sanitize_settings' ) );
        $this->add_settings_sections();
    }

    /**
     * Render settings page — this is called from the admin menu callback.
     */
    public function display_settings_page() {
        $settings = get_option( 'acs_settings', array() );

        // Handle form POST (non-AJAX fallback). Prefer structured `acs_settings` post data.
        if ( isset( $_POST['acs_settings'] ) && isset( $_POST['acs_settings_nonce'] ) && wp_verify_nonce( $_POST['acs_settings_nonce'], 'acs_settings_action' ) ) {
            $posted = is_array( $_POST['acs_settings'] ) ? $_POST['acs_settings'] : array();

            // Sanitize using shared sanitizer (if available)
            $sanitized = $this->sanitize_settings( $posted );

            // Merge with existing settings to avoid overwriting unrelated keys
            $merged = is_array( $settings ) ? array_replace_recursive( $settings, $sanitized ) : $sanitized;

            // Validate provider configuration: require at least one enabled provider with a non-empty API key
            if ( ! $this->providers_have_valid( $merged ) ) {
                echo '<div class="notice notice-error"><p>' . esc_html__( 'Error: Please configure at least one AI provider and API key before saving settings.', 'ai-content-studio' ) . '</p></div>';
            } else {
                update_option( 'acs_settings', $merged );

                // Role capability mappings if provided in the posted array
                if ( isset( $posted['role_caps'] ) && is_array( $posted['role_caps'] ) ) {
                    $this->apply_role_caps_mapping( $posted['role_caps'] );
                    update_option( 'acs_role_caps', $posted['role_caps'] );
                }

                echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved!', 'ai-content-studio' ) . '</p></div>';
                $settings = get_option( 'acs_settings', array() );
            }
        }

        // Backwards-compatible fallback: older templates that posted individual fields
        elseif ( isset( $_POST['submit'] ) && isset( $_POST['acs_settings_nonce'] ) && wp_verify_nonce( $_POST['acs_settings_nonce'], 'acs_settings_action' ) ) {
            $new_settings = array();

            if ( isset( $_POST['groq_api_key'] ) ) {
                $api_key = sanitize_text_field( $_POST['groq_api_key'] );
                $new_settings['providers']['groq']['api_key'] = $api_key;
                $new_settings['providers']['groq']['enabled'] = true;
            }

            // Role capability mappings
            if ( isset( $_POST['acs_role_caps'] ) && is_array( $_POST['acs_role_caps'] ) ) {
                $posted_caps = array();
                $allowed_caps = array( 'acs_generate_content', 'acs_manage_settings', 'acs_view_analytics' );

                foreach ( $_POST['acs_role_caps'] as $role_key => $caps ) {
                    $role_name = sanitize_key( $role_key );
                    if ( ! is_array( $caps ) ) {
                        continue;
                    }

                    $posted_caps[ $role_name ] = array();
                    foreach ( $caps as $cap => $val ) {
                        $cap = sanitize_text_field( $cap );
                        if ( in_array( $cap, $allowed_caps, true ) ) {
                            $posted_caps[ $role_name ][ $cap ] = true;
                        }
                    }
                }

                update_option( 'acs_role_caps', $posted_caps );
                $this->apply_role_caps_mapping( $posted_caps );
            }

            // Merge into existing settings instead of overwriting entirely
            $merged = is_array( $settings ) ? array_replace_recursive( $settings, $new_settings ) : $new_settings;
            update_option( 'acs_settings', $merged );
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved!', 'ai-content-studio' ) . '</p></div>';
            $settings = get_option( 'acs_settings', array() );
        }

        include ACS_PLUGIN_PATH . 'admin/templates/settings.php';
    }

    /**
     * AJAX handler for saving settings (JSON)
     */
    public function ajax_save_settings() {
        if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'acs_ajax_nonce' ) ) {
            wp_send_json_error( __( 'Security check failed.', 'ai-content-studio' ) );
        }

        if ( ! current_user_can( 'acs_manage_settings' ) ) {
            wp_send_json_error( __( 'You do not have permission to manage settings.', 'ai-content-studio' ) );
        }

        $settings = isset( $_POST['settings'] ) ? $_POST['settings'] : array();
        $sanitized = $this->sanitize_settings( $settings );

        // Ensure at least one provider enabled with API key before saving
        if ( ! $this->providers_have_valid( $sanitized ) ) {
            wp_send_json_error( __( 'Please configure at least one AI provider with a valid API key before saving.', 'ai-content-studio' ) );
        }

        update_option( 'acs_settings', $sanitized );

        // Return providers_ok flag so client-side can reactively enable generation UI
        $providers_ok = $this->providers_have_valid( $sanitized );
        wp_send_json_success( array( 'message' => __( 'Settings saved successfully.', 'ai-content-studio' ), 'providers_ok' => $providers_ok ) );
    }

    /**
     * AJAX handler for testing API connection
     */
    public function ajax_test_api_connection() {
        if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'acs_ajax_nonce' ) ) {
            wp_send_json_error( __( 'Security check failed.', 'ai-content-studio' ) );
        }

        if ( ! current_user_can( 'acs_manage_settings' ) ) {
            wp_send_json_error( __( 'You do not have permission to test API connections.', 'ai-content-studio' ) );
        }

        $provider = sanitize_text_field( $_POST['provider'] ?? '' );
        $api_key = sanitize_text_field( $_POST['api_key'] ?? '' );

        switch ( $provider ) {
            case 'groq':
                if ( class_exists( 'ACS_Groq' ) ) {
                    $instance = new ACS_Groq( $api_key );
                    $valid = method_exists( $instance, 'authenticate' ) ? $instance->authenticate( $api_key ) : false;
                } else {
                    $valid = false;
                }
                break;
            default:
                $valid = false;
        }

        if ( $valid ) {
            wp_send_json_success( __( 'API connection successful!', 'ai-content-studio' ) );
        }

        wp_send_json_error( __( 'API connection failed. Please check your API key.', 'ai-content-studio' ) );
    }

    /**
     * AJAX keyword suggestions wrapper
     */
    public function ajax_get_keyword_suggestions() {
        if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'acs_ajax_nonce' ) ) {
            wp_send_json_error( __( 'Security check failed.', 'ai-content-studio' ) );
        }

        $topic = sanitize_text_field( $_POST['topic'] ?? '' );
        if ( empty( $topic ) ) {
            wp_send_json_error( __( 'Please provide a topic.', 'ai-content-studio' ) );
        }

        if ( file_exists( ACS_PLUGIN_PATH . 'api/class-acs-keyword-research.php' ) ) {
            require_once ACS_PLUGIN_PATH . 'api/class-acs-keyword-research.php';
            $keyword_research = new ACS_Keyword_Research();
            $suggestions = $keyword_research->get_suggestions( $topic );
            if ( is_wp_error( $suggestions ) ) {
                wp_send_json_error( $suggestions->get_error_message() );
            } else {
                wp_send_json_success( $suggestions );
            }
        }

        wp_send_json_error( __( 'Keyword research unavailable.', 'ai-content-studio' ) );
    }

    /**
     * AJAX handler for exporting settings
     */
    public function ajax_export_settings() {
        if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'acs_ajax_nonce' ) ) {
            wp_send_json_error( __( 'Security check failed.', 'ai-content-studio' ) );
        }

        if ( ! current_user_can( 'acs_manage_settings' ) && ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'You do not have permission to export settings.', 'ai-content-studio' ) );
        }

        $settings = get_option( 'acs_settings', array() );
        
        // Remove sensitive API keys from export (optional - can be toggled)
        $export_data = array(
            'version'     => defined( 'ACS_VERSION' ) ? ACS_VERSION : '1.0.0',
            'exported_at' => gmdate( 'Y-m-d H:i:s' ),
            'settings'    => $settings,
        );

        wp_send_json_success( $export_data );
    }

    /**
     * AJAX handler for importing settings
     */
    public function ajax_import_settings() {
        if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'acs_ajax_nonce' ) ) {
            wp_send_json_error( __( 'Security check failed.', 'ai-content-studio' ) );
        }

        if ( ! current_user_can( 'acs_manage_settings' ) && ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'You do not have permission to import settings.', 'ai-content-studio' ) );
        }

        $import_json = isset( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : '';
        
        if ( empty( $import_json ) ) {
            wp_send_json_error( __( 'No settings data provided.', 'ai-content-studio' ) );
        }

        $import_data = json_decode( $import_json, true );
        
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            wp_send_json_error( __( 'Invalid JSON format.', 'ai-content-studio' ) );
        }

        // Extract settings from import data (support both formats)
        $settings = isset( $import_data['settings'] ) ? $import_data['settings'] : $import_data;
        
        if ( ! is_array( $settings ) ) {
            wp_send_json_error( __( 'Invalid settings format.', 'ai-content-studio' ) );
        }

        // Sanitize imported settings
        $sanitized = $this->sanitize_settings( $settings );
        
        // Merge with existing to preserve any keys not in import
        $existing = get_option( 'acs_settings', array() );
        $merged   = is_array( $existing ) ? array_replace_recursive( $existing, $sanitized ) : $sanitized;
        
        update_option( 'acs_settings', $merged );

        wp_send_json_success( __( 'Settings imported successfully.', 'ai-content-studio' ) );
    }

    /**
     * AJAX handler for resetting settings
     */
    public function ajax_reset_settings() {
        // Check for either nonce format
        $nonce = isset( $_POST['nonce'] ) ? $_POST['nonce'] : '';
        $valid_nonce = wp_verify_nonce( $nonce, 'acs_ajax_nonce' ) || wp_verify_nonce( $nonce, 'acs_nonce' );
        
        if ( ! $valid_nonce ) {
            wp_send_json_error( __( 'Security check failed.', 'ai-content-studio' ) );
        }

        if ( ! current_user_can( 'acs_manage_settings' ) && ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'You do not have permission to reset settings.', 'ai-content-studio' ) );
        }

        // Delete the settings option
        delete_option( 'acs_settings' );
        delete_option( 'acs_role_caps' );

        wp_send_json_success( __( 'Settings reset successfully.', 'ai-content-studio' ) );
    }

    /**
     * AJAX handler for logging client-side errors
     */
    public function ajax_log_client_error() {
        // Check nonce
        $nonce = isset( $_POST['nonce'] ) ? $_POST['nonce'] : '';
        $valid_nonce = wp_verify_nonce( $nonce, 'acs_admin_nonce' ) || wp_verify_nonce( $nonce, 'acs_ajax_nonce' );
        
        if ( ! $valid_nonce ) {
            wp_send_json_error( __( 'Security check failed.', 'ai-content-studio' ) );
        }

        // Must be a logged-in user
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( __( 'Not authenticated.', 'ai-content-studio' ) );
        }

        $code = isset( $_POST['code'] ) ? sanitize_text_field( $_POST['code'] ) : 'client_error';
        $message = isset( $_POST['message'] ) ? sanitize_text_field( $_POST['message'] ) : '';
        $data = isset( $_POST['data'] ) ? sanitize_text_field( $_POST['data'] ) : '';
        $url = isset( $_POST['url'] ) ? esc_url_raw( $_POST['url'] ) : '';
        $user_agent = isset( $_POST['userAgent'] ) ? sanitize_text_field( $_POST['userAgent'] ) : '';

        // Log using error handler if available
        if ( class_exists( 'ACS_Error_Handler' ) ) {
            $context = array(
                'message'    => $message,
                'client_url' => $url,
                'user_agent' => $user_agent,
                'data'       => $data,
            );
            ACS_Error_Handler::get_instance()->log_error( $code, $context, 'warning' );
        }

        wp_send_json_success();
    }

    /**
     * AJAX handler for getting cached dashboard metrics.
     */
    public function ajax_get_dashboard_metrics() {
        // Check nonce
        $nonce = isset( $_POST['nonce'] ) ? $_POST['nonce'] : '';
        $valid_nonce = wp_verify_nonce( $nonce, 'acs_admin_nonce' ) || wp_verify_nonce( $nonce, 'acs_ajax_nonce' );
        
        if ( ! $valid_nonce ) {
            wp_send_json_error( __( 'Security check failed.', 'ai-content-studio' ) );
        }

        if ( ! current_user_can( 'acs_view_analytics' ) && ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'ai-content-studio' ) );
        }

        // Use performance class for cached metrics
        if ( class_exists( 'ACS_Performance' ) ) {
            $metrics = ACS_Performance::get_instance()->get_dashboard_metrics();
            wp_send_json_success( $metrics );
        }

        // Fallback to direct query
        if ( class_exists( 'ACS_Analytics' ) ) {
            $metrics = array(
                'total_generations' => ACS_Analytics::get_total_generations(),
                'generations_today' => ACS_Analytics::get_generations_count( 'today' ),
                'generations_week'  => ACS_Analytics::get_generations_count( 'week' ),
            );
            wp_send_json_success( $metrics );
        }

        wp_send_json_error( __( 'Analytics not available.', 'ai-content-studio' ) );
    }

    /**
     * AJAX handler for clearing plugin cache.
     */
    public function ajax_clear_cache() {
        // Check nonce
        $nonce = isset( $_POST['nonce'] ) ? $_POST['nonce'] : '';
        $valid_nonce = wp_verify_nonce( $nonce, 'acs_admin_nonce' ) || wp_verify_nonce( $nonce, 'acs_ajax_nonce' );
        
        if ( ! $valid_nonce ) {
            wp_send_json_error( __( 'Security check failed.', 'ai-content-studio' ) );
        }

        if ( ! current_user_can( 'acs_manage_settings' ) && ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'ai-content-studio' ) );
        }

        if ( class_exists( 'ACS_Performance' ) ) {
            $deleted = ACS_Performance::get_instance()->clear_all();
            wp_send_json_success(
                sprintf(
                    /* translators: %d: number of cache entries cleared */
                    __( 'Cache cleared successfully. %d entries removed.', 'ai-content-studio' ),
                    $deleted
                )
            );
        }

        wp_send_json_error( __( 'Cache system not available.', 'ai-content-studio' ) );
    }

    /**
     * Sanitize settings (basic passthrough to shared sanitizer if available)
     */
    public function sanitize_settings( $input ) {
        if ( class_exists( 'ACS_Sanitizer' ) ) {
            return ACS_Sanitizer::sanitize_settings( $input );
        }

        return is_array( $input ) ? $input : array();
    }

    /**
     * Add settings sections (render callbacks live in admin templates)
     */
    private function add_settings_sections() {
        add_settings_section( 'acs_general_section', __( 'General Settings', 'ai-content-studio' ), array( $this, 'general_section_callback' ), 'acs-settings' );
        add_settings_section( 'acs_providers_section', __( 'AI Providers', 'ai-content-studio' ), array( $this, 'providers_section_callback' ), 'acs-settings' );
        add_settings_section( 'acs_seo_section', __( 'SEO Settings', 'ai-content-studio' ), array( $this, 'seo_section_callback' ), 'acs-settings' );
        add_settings_section( 'acs_content_section', __( 'Content Settings', 'ai-content-studio' ), array( $this, 'content_section_callback' ), 'acs-settings' );
    }

    public function general_section_callback() { echo '<p>' . __( 'Configure general plugin settings.', 'ai-content-studio' ) . '</p>'; }
    public function providers_section_callback() { echo '<p>' . __( 'Configure AI providers and API keys.', 'ai-content-studio' ) . '</p>'; }
    public function seo_section_callback() { echo '<p>' . __( 'Configure SEO optimization settings.', 'ai-content-studio' ) . '</p>'; }
    public function content_section_callback() { echo '<p>' . __( 'Configure content generation settings.', 'ai-content-studio' ) . '</p>'; }

    /**
     * Apply role capability mapping
     */
    private function apply_role_caps_mapping( $mapping ) {
        if ( empty( $mapping ) || ! is_array( $mapping ) ) {
            return;
        }

        $target_caps = array( 'acs_generate_content', 'acs_manage_settings', 'acs_view_analytics' );

        foreach ( $mapping as $role_key => $caps ) {
            $role_obj = get_role( $role_key );
            if ( ! $role_obj ) {
                continue;
            }

            foreach ( $target_caps as $cap ) {
                $role_obj->remove_cap( $cap );
            }

            if ( is_array( $caps ) ) {
                foreach ( $caps as $cap => $val ) {
                    if ( in_array( $cap, $target_caps, true ) ) {
                        $role_obj->add_cap( $cap );
                    }
                }
            }
        }

        $wp_roles = wp_roles();
        if ( $wp_roles && isset( $wp_roles->roles ) && is_array( $wp_roles->roles ) ) {
            foreach ( $wp_roles->roles as $role_key => $role_data ) {
                if ( ! isset( $mapping[ $role_key ] ) ) {
                    $r = get_role( $role_key );
                    if ( $r ) {
                        foreach ( $target_caps as $cap ) {
                            $r->remove_cap( $cap );
                        }
                    }
                }
            }
        }
    }

    /**
     * Show an admin notice when Groq API key is missing, prompting admins to configure it.
     */
    public function missing_api_key_notice() {
        if ( ! current_user_can( 'acs_manage_settings' ) && ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $settings = get_option( 'acs_settings', array() );
        $groq_key = $settings['providers']['groq']['api_key'] ?? '';

        if ( empty( $groq_key ) ) {
            $settings_url = esc_url( admin_url( 'admin.php?page=acs-settings' ) );
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p>' . sprintf( esc_html__( 'AI Content Studio: No Groq API key configured. %1$sConfigure API settings%2$s to enable content generation.', 'ai-content-studio' ), '<a href="' . $settings_url . '">', '</a>' ) . '</p>';
            echo '</div>';
        }
    }

    /**
     * Check whether provided settings contain at least one enabled provider with non-empty API key.
     *
     * @param array $settings
     * @return bool
     */
    private function providers_have_valid( $settings ) {
        if ( ! is_array( $settings ) ) {
            return false;
        }

        if ( empty( $settings['providers'] ) || ! is_array( $settings['providers'] ) ) {
            return false;
        }

        foreach ( $settings['providers'] as $prov_key => $prov ) {
            $enabled = isset( $prov['enabled'] ) && ( $prov['enabled'] === true || $prov['enabled'] === '1' || $prov['enabled'] === 1 );
            $api_key = isset( $prov['api_key'] ) ? trim( (string) $prov['api_key'] ) : '';
            if ( $enabled && $api_key !== '' ) {
                return true;
            }
        }

        return false;
    }
}
