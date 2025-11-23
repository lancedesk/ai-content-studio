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
