<?php
/**
 * Fired during plugin activation
 *
 * @link       https://lancedesk.com
 * @since      1.0.0
 *
 * @package    ACS
 * @subpackage ACS/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    ACS
 * @subpackage ACS/includes
 * @author     LanceDesk <support@lancedesk.com>
 */
class ACS_Activator {

    /**
     * Activate the plugin.
     *
     * Simple activation - just set basic options.
     *
     * @since    1.0.0
     */
    public static function activate() {
        
        // Create analytics tables
        require_once ACS_PLUGIN_PATH . 'includes/class-acs-analytics.php';
        ACS_Analytics::install();
        
        // Create error handler table
        require_once ACS_PLUGIN_PATH . 'includes/class-acs-error-handler.php';
        ACS_Error_Handler::get_instance()->create_db_table();
        
        // Create basic default options
        self::create_default_options();
        
        // Set activation flag for onboarding
        update_option( 'acs_activation_redirect', true );
        
        // Ensure required capabilities exist for administrator
        // If there's a saved mapping, apply it; otherwise default to admin-only
        $saved = get_option( 'acs_role_caps', false );
        if ( $saved && is_array( $saved ) ) {
            self::apply_role_caps_mapping( $saved );
        } else {
            self::add_capabilities_to_roles();
        }
        
        // Set plugin version
        update_option( 'acs_version', '1.0.0' );
    }
    
    /**
     * Create default plugin options.
     *
     * @since    1.0.0
     */
    private static function create_default_options() {
        $default_settings = array(
            'providers' => array(
                'groq' => array(
                    'api_key' => '',
                    'enabled' => true,
                ),
            ),
            'general' => array(
                'onboarding_completed' => false,
            ),
        );
        
        // Only create settings if they don't exist
        if (!get_option('acs_settings')) {
            update_option( 'acs_settings', $default_settings );
        }
    }

    /**
     * Add required capabilities to appropriate roles (administrator by default).
     *
     * @since 1.0.0
     */
    private static function add_capabilities_to_roles() {
        $caps = array(
            'acs_generate_content',
            'acs_manage_settings',
            'acs_view_analytics',
        );

        // Grant capabilities to the administrator role
        $admin_role = get_role( 'administrator' );
        if ( $admin_role ) {
            foreach ( $caps as $cap ) {
                if ( ! $admin_role->has_cap( $cap ) ) {
                    $admin_role->add_cap( $cap );
                }
            }
        }
    }

    /**
     * Apply a stored role->capability mapping during activation.
     *
     * @param array $mapping Role keyed mapping of capabilities
     */
    private static function apply_role_caps_mapping( $mapping ) {
        if ( empty( $mapping ) || ! is_array( $mapping ) ) {
            return;
        }

        foreach ( $mapping as $role_key => $caps ) {
            $role_obj = get_role( $role_key );
            if ( ! $role_obj ) {
                continue;
            }

            if ( is_array( $caps ) ) {
                foreach ( $caps as $cap => $val ) {
                    $cap = sanitize_text_field( $cap );
                    if ( ! $role_obj->has_cap( $cap ) ) {
                        $role_obj->add_cap( $cap );
                    }
                }
            }
        }
    }
}