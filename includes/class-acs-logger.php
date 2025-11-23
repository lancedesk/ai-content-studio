<?php
/**
 * Simple logger for AI Content Studio generation attempts.
 *
 * Writes a JSON line to `logs/generation.log` and optionally stores recent
 * entries in a transient or option for quick access.
 *
 * @package ACS
 */
if ( ! class_exists( 'ACS_Logger' ) ) :
class ACS_Logger {

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
        $settings = get_option( 'acs_settings', array() );
        $enabled = true;
        if ( isset( $settings['advanced'] ) && is_array( $settings['advanced'] ) ) {
            if ( isset( $settings['advanced']['enable_logging'] ) ) {
                $enabled = (bool) $settings['advanced']['enable_logging'];
            }
        }
        if ( ! $enabled ) {
            return false;
        }

        $log_dir = ACS_PLUGIN_PATH . 'logs';
        if ( ! file_exists( $log_dir ) ) {
            wp_mkdir_p( $log_dir );
        }

        $log_file = trailingslashit( $log_dir ) . 'generation.log';

        $entry = array(
            'time' => current_time( 'mysql' ),
            'post_id' => intval( $post_id ),
            'report' => $report,
            'context' => $context,
            'user_id' => get_current_user_id(),
            'level' => isset( $context['level'] ) ? $context['level'] : 'info',
        );

        $line = wp_json_encode( $entry ) . PHP_EOL;

        try {
            // Rotate log if it exceeds max size (default 2MB)
            $max_size = defined( 'ACS_LOG_MAX_BYTES' ) ? ACS_LOG_MAX_BYTES : 2 * 1024 * 1024;
            if ( file_exists( $log_file ) && filesize( $log_file ) > $max_size ) {
                $rotated = trailingslashit( $log_dir ) . 'generation-' . time() . '.log';
                @rename( $log_file, $rotated );
            }

            file_put_contents( $log_file, $line, FILE_APPEND | LOCK_EX );
            // Keep a recent history in option for quick admin access (store last 20)
            $history = get_option( 'acs_generation_history', array() );
            array_unshift( $history, $entry );
            $history = array_slice( $history, 0, 20 );
            update_option( 'acs_generation_history', $history );
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
        $history = get_option( 'acs_generation_history', array() );
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
