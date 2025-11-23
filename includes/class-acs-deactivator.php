<?php
/**
 * Fired during plugin deactivation
 *
 * @link       https://lancedesk.com
 * @since      1.0.0
 *
 * @package    ACS
 * @subpackage ACS/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    ACS
 * @subpackage ACS/includes
 * @author     LanceDesk <support@lancedesk.com>
 */
class ACS_Deactivator {

    /**
     * Deactivate the plugin.
     *
     * Clean up any temporary data and scheduled events.
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        
        // Clear scheduled events
        wp_clear_scheduled_hook( 'acs_process_content_queue' );
        wp_clear_scheduled_hook( 'acs_cleanup_logs' );
        wp_clear_scheduled_hook( 'acs_update_keyword_data' );
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Note: We don't remove capabilities or drop tables on deactivation
        // This preserves user data if they reactivate the plugin
    }
}