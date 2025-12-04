<?php
/**
 * Simple logger for AI Content Studio generation attempts.
 *
 * Writes a JSON line to a log file (default: wp-content/uploads/acs-logs/generation.log)
 * and optionally stores recent entries in an option for quick access.
 *
 * @package ACS
 */
if ( ! class_exists( 'ACS_Logger' ) ) :
class ACS_Logger {

    /**
     * Get the log directory path. Defaults to wp-content/uploads/acs-logs
     * but can be overridden via settings or constant ACS_LOG_DIR.
     *
     * @return string Absolute path to log directory.
     */
    public static function get_log_dir() {
        // Allow constant override for advanced users
        if ( defined( 'ACS_LOG_DIR' ) && ACS_LOG_DIR ) {
            return rtrim( ACS_LOG_DIR, '/\\' );
        }

        // Check if configured in settings
        $settings = function_exists( 'get_option' ) ? get_option( 'acs_settings', array() ) : array();
        if ( ! empty( $settings['advanced']['log_directory'] ) ) {
            $custom = sanitize_text_field( $settings['advanced']['log_directory'] );
            if ( $custom && is_dir( dirname( $custom ) ) ) {
                return rtrim( $custom, '/\\' );
            }
        }

        // Default: wp-content/uploads/acs-logs
        if ( function_exists( 'wp_upload_dir' ) ) {
            $upload = wp_upload_dir();
            $base = isset( $upload['basedir'] ) ? $upload['basedir'] : ( defined( 'WP_CONTENT_DIR' ) ? WP_CONTENT_DIR . '/uploads' : dirname( __DIR__ ) );
        } else {
            $base = defined( 'WP_CONTENT_DIR' ) ? WP_CONTENT_DIR . '/uploads' : dirname( dirname( __DIR__ ) );
        }

        return rtrim( $base, '/\\' ) . DIRECTORY_SEPARATOR . 'acs-logs';
    }

    /**
     * Log a generation attempt.
     *
     * @param int $post_id Post ID that was created (0 if not created).
     * @param array $report Validation/generation report or error details.
     * @param array $context Optional additional context (user, prompt, provider).
     * @return bool True on success
     */
    public static function log_generation_attempt( $post_id, $report = array(), $context = array() ) {
        // Respect settings toggle if present
        $settings = function_exists( 'get_option' ) ? get_option( 'acs_settings', array() ) : array();
        $enabled = true;
        if ( isset( $settings['advanced'] ) && is_array( $settings['advanced'] ) ) {
            if ( isset( $settings['advanced']['enable_logging'] ) ) {
                $enabled = (bool) $settings['advanced']['enable_logging'];
            }
        }
        if ( ! $enabled ) {
            return false;
        }

        $log_dir = self::get_log_dir();
        if ( ! file_exists( $log_dir ) ) {
            if ( function_exists( 'wp_mkdir_p' ) ) {
                wp_mkdir_p( $log_dir );
            } else {
                @mkdir( $log_dir, 0755, true );
            }
            // Add index.php for security
            @file_put_contents( $log_dir . '/index.php', '<?php // Silence is golden' );
        }

        $log_file = trailingslashit( $log_dir ) . 'generation.log';

        $entry = array(
            'time' => function_exists( 'current_time' ) ? current_time( 'mysql' ) : date( 'Y-m-d H:i:s' ),
            'post_id' => intval( $post_id ),
            'report' => $report,
            'context' => $context,
            'user_id' => function_exists( 'get_current_user_id' ) ? get_current_user_id() : 0,
            'level' => isset( $context['level'] ) ? $context['level'] : 'info',
        );

        $line = ( function_exists( 'wp_json_encode' ) ? wp_json_encode( $entry ) : json_encode( $entry ) ) . PHP_EOL;

        try {
            // Rotate log if it exceeds max size (default 2MB)
            $max_size = defined( 'ACS_LOG_MAX_BYTES' ) ? ACS_LOG_MAX_BYTES : 2 * 1024 * 1024;
            if ( file_exists( $log_file ) && filesize( $log_file ) > $max_size ) {
                $rotated = trailingslashit( $log_dir ) . 'generation-' . time() . '.log';
                @rename( $log_file, $rotated );
            }

            file_put_contents( $log_file, $line, FILE_APPEND | LOCK_EX );
            // Keep a recent history in option for quick admin access (store last 20)
            if ( function_exists( 'get_option' ) ) {
                $history = get_option( 'acs_generation_history', array() );
                array_unshift( $history, $entry );
                $history = array_slice( $history, 0, 20 );
                update_option( 'acs_generation_history', $history );
            }
            return true;
        } catch ( Exception $e ) {
            return false;
        }
    }

    /**
     * Export logs as array filtered by provider, date range or level.
     *
     * @param array $args ['provider' => '', 'since' => 'YYYY-mm-dd', 'until' => 'YYYY-mm-dd', 'level' => '']
     * @return array
     */
    public static function export_logs( $args = array() ) {
        $history = function_exists( 'get_option' ) ? get_option( 'acs_generation_history', array() ) : array();
        if ( ! is_array( $history ) ) {
            $history = array();
        }

        $provider = ! empty( $args['provider'] ) ? $args['provider'] : '';
        $level = ! empty( $args['level'] ) ? $args['level'] : '';
        $since = ! empty( $args['since'] ) ? strtotime( $args['since'] ) : 0;
        $until = ! empty( $args['until'] ) ? strtotime( $args['until'] . ' 23:59:59' ) : PHP_INT_MAX;

        $out = array();
        foreach ( $history as $entry ) {
            $ok = true;
            $entry_ts = isset( $entry['time'] ) ? strtotime( $entry['time'] ) : 0;
            if ( $provider && isset( $entry['report']['provider'] ) && $entry['report']['provider'] !== $provider ) {
                $ok = false;
            }
            if ( $level && isset( $entry['level'] ) && $entry['level'] !== $level ) {
                $ok = false;
            }
            if ( $entry_ts < $since || $entry_ts > $until ) {
                $ok = false;
            }
            if ( $ok ) {
                $out[] = $entry;
            }
        }

        return $out;
    }
}
endif;
