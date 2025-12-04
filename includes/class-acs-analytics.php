<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * ACS Analytics - minimal analytics backend to store generation events and provide helpers.
 */
class ACS_Analytics {

    /**
     * Create analytics tables. Call from activation hook.
     */
    public static function install() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();

        $gen_table = $wpdb->prefix . 'acs_generations';
        $events_table = $wpdb->prefix . 'acs_analytics_events';

        $sql = "
        CREATE TABLE {$gen_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            post_id bigint(20) unsigned DEFAULT NULL,
            user_id bigint(20) unsigned DEFAULT NULL,
            provider varchar(50) DEFAULT NULL,
            model varchar(100) DEFAULT NULL,
            prompt_hash varchar(64) DEFAULT NULL,
            prompt_text longtext,
            response_text longtext,
            tokens_used int(11) DEFAULT NULL,
            cost_estimate decimal(10,6) DEFAULT NULL,
            generation_time decimal(10,3) DEFAULT NULL,
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY post_id (post_id),
            KEY user_id (user_id),
            KEY provider (provider),
            KEY created_at (created_at)
        ) {$charset_collate};

        CREATE TABLE {$events_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            generation_id bigint(20) unsigned DEFAULT NULL,
            event_type varchar(50) DEFAULT NULL,
            payload longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY generation_id (generation_id),
            KEY event_type (event_type),
            KEY created_at (created_at)
        ) {$charset_collate};
        ";

        dbDelta( $sql );
    }

    /**
     * Track a generation record.
     * Expects array keys: post_id, user_id, provider, model, prompt_hash, prompt_text, response_text, tokens_used, cost_estimate, generation_time, status
     * Returns inserted generation ID on success, false on failure.
     */
    public static function track_generation( array $data = array() ) {
        global $wpdb;

        $table = $wpdb->prefix . 'acs_generations';

        $insert = array(
            'post_id' => isset( $data['post_id'] ) ? intval( $data['post_id'] ) : null,
            'user_id' => isset( $data['user_id'] ) ? intval( $data['user_id'] ) : null,
            'provider' => isset( $data['provider'] ) ? sanitize_text_field( $data['provider'] ) : null,
            'model' => isset( $data['model'] ) ? sanitize_text_field( $data['model'] ) : null,
            'prompt_hash' => isset( $data['prompt_hash'] ) ? sanitize_text_field( $data['prompt_hash'] ) : null,
            'prompt_text' => isset( $data['prompt_text'] ) ? wp_kses_post( $data['prompt_text'] ) : null,
            'response_text' => isset( $data['response_text'] ) ? wp_kses_post( $data['response_text'] ) : null,
            'tokens_used' => isset( $data['tokens_used'] ) ? intval( $data['tokens_used'] ) : null,
            'cost_estimate' => isset( $data['cost_estimate'] ) ? floatval( $data['cost_estimate'] ) : null,
            'generation_time' => isset( $data['generation_time'] ) ? floatval( $data['generation_time'] ) : null,
            'status' => isset( $data['status'] ) ? sanitize_text_field( $data['status'] ) : 'completed',
            'created_at' => current_time( 'mysql', 1 ),
        );

        $formats = array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%f', '%f', '%s', '%s' );

        $result = $wpdb->insert( $table, $insert );

        if ( false === $result ) {
            return false;
        }

        $id = $wpdb->insert_id;

        return $id;
    }

    /**
     * Record an event related to a generation (e.g., retry, validation_result)
     */
    public static function record_event( $generation_id, $event_type, $payload = '' ) {
        global $wpdb;

        $table = $wpdb->prefix . 'acs_analytics_events';

        $json_payload = is_string( $payload ) ? $payload : ( function_exists( 'wp_json_encode' ) ? wp_json_encode( $payload ) : json_encode( $payload ) );

        $insert = array(
            'generation_id' => intval( $generation_id ),
            'event_type' => sanitize_text_field( $event_type ),
            'payload' => $json_payload,
            'created_at' => function_exists( 'current_time' ) ? current_time( 'mysql', 1 ) : date( 'Y-m-d H:i:s' ),
        );

        $result = $wpdb->insert( $table, $insert );

        if ( false === $result ) {
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Get basic aggregated metrics.
     * $args: 'from' => 'YYYY-MM-DD', 'to' => 'YYYY-MM-DD', 'provider' => 'openai'|
     */
    public static function get_metrics( $args = array() ) {
        global $wpdb;

        $table = $wpdb->prefix . 'acs_generations';

        $where = array();
        $params = array();

        if ( ! empty( $args['from'] ) ) {
            $where[] = "created_at >= %s";
            $params[] = $args['from'] . ' 00:00:00';
        }
        if ( ! empty( $args['to'] ) ) {
            $where[] = "created_at <= %s";
            $params[] = $args['to'] . ' 23:59:59';
        }
        if ( ! empty( $args['provider'] ) ) {
            $where[] = "provider = %s";
            $params[] = sanitize_text_field( $args['provider'] );
        }

        $sql = "SELECT COUNT(*) as total, AVG(tokens_used) as avg_tokens, SUM(cost_estimate) as total_cost FROM {$table}";

        if ( ! empty( $where ) ) {
            $sql .= ' WHERE ' . implode( ' AND ', $where );
        }

        $prepared = $wpdb->prepare( $sql, $params );
        $row = $wpdb->get_row( $prepared, ARRAY_A );

        return $row;
    }

    /**
     * Get total number of generations.
     *
     * @return int Total generations count.
     */
    public static function get_total_generations() {
        global $wpdb;
        $table = $wpdb->prefix . 'acs_generations';
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
    }

    /**
     * Get generations count for a time period.
     *
     * @param string $period Period: 'today', 'week', 'month'.
     * @return int Generations count.
     */
    public static function get_generations_count( $period = 'today' ) {
        global $wpdb;
        $table = $wpdb->prefix . 'acs_generations';

        switch ( $period ) {
            case 'today':
                $date_condition = "DATE(created_at) = CURDATE()";
                break;
            case 'week':
                $date_condition = "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $date_condition = "created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
            default:
                $date_condition = "1=1";
        }

        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE {$date_condition}" );
    }

    /**
     * Get average tokens used.
     *
     * @return float Average tokens.
     */
    public static function get_average_tokens() {
        global $wpdb;
        $table = $wpdb->prefix . 'acs_generations';
        $result = $wpdb->get_var( "SELECT AVG(tokens_used) FROM {$table}" );
        return $result ? round( (float) $result, 2 ) : 0;
    }

    /**
     * Get total cost estimate.
     *
     * @return float Total cost.
     */
    public static function get_total_cost() {
        global $wpdb;
        $table = $wpdb->prefix . 'acs_generations';
        $result = $wpdb->get_var( "SELECT SUM(cost_estimate) FROM {$table}" );
        return $result ? round( (float) $result, 4 ) : 0;
    }

    /**
     * Get popular content types.
     *
     * @param int $limit Number of results.
     * @return array Content types with counts.
     */
    public static function get_popular_content_types( $limit = 5 ) {
        global $wpdb;
        $table = $wpdb->prefix . 'acs_generations';
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT content_type, COUNT(*) as count FROM {$table} GROUP BY content_type ORDER BY count DESC LIMIT %d",
                $limit
            ),
            ARRAY_A
        );
    }

    /**
     * Get provider usage statistics.
     *
     * @return array Provider usage data.
     */
    public static function get_provider_usage() {
        global $wpdb;
        $table = $wpdb->prefix . 'acs_generations';
        return $wpdb->get_results(
            "SELECT provider, COUNT(*) as count, SUM(tokens_used) as total_tokens, SUM(cost_estimate) as total_cost 
             FROM {$table} GROUP BY provider ORDER BY count DESC",
            ARRAY_A
        );
    }

    /**
     * Cleanup old analytics data.
     *
     * @param int $days_to_keep Number of days to keep data.
     * @return int Number of rows deleted.
     */
    public static function cleanup_old_data( $days_to_keep = 90 ) {
        global $wpdb;

        $gen_table = $wpdb->prefix . 'acs_generations';
        $event_table = $wpdb->prefix . 'acs_analytics_events';

        // Delete old events first (foreign key constraint).
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$event_table} WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $days_to_keep
            )
        );

        // Delete old generations.
        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$gen_table} WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $days_to_keep
            )
        );

        return $deleted;
    }

}
