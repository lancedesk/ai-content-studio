<?php
/**
 * Minimal REST controller for AI Content Studio
 *
 * Registers a few endpoints to support modern admin UI usage.
 *
 * @package ACS
 */

defined( 'ABSPATH' ) || exit;

class ACS_REST {

    public static function register_routes() {
        register_rest_route( 'acs/v1', '/create-post', array(
            'methods'             => 'POST',
            'callback'            => array( __CLASS__, 'rest_create_post' ),
            'permission_callback' => array( __CLASS__, 'permissions_check' ),
        ) );

        register_rest_route( 'acs/v1', '/generate', array(
            'methods'             => 'POST',
            'callback'            => array( __CLASS__, 'rest_generate' ),
            'permission_callback' => array( __CLASS__, 'permissions_check' ),
        ) );

        register_rest_route( 'acs/v1', '/settings', array(
            'methods'             => 'POST',
            'callback'            => array( __CLASS__, 'rest_save_settings' ),
            'permission_callback' => array( __CLASS__, 'permissions_check' ),
        ) );

        // Analytics endpoints
        register_rest_route( 'acs/v1', '/analytics/summary', array(
            'methods'             => 'GET',
            'callback'            => array( __CLASS__, 'rest_analytics_summary' ),
            'permission_callback' => array( __CLASS__, 'analytics_permissions_check' ),
        ) );

        register_rest_route( 'acs/v1', '/analytics/generations', array(
            'methods'             => 'GET',
            'callback'            => array( __CLASS__, 'rest_analytics_generations' ),
            'permission_callback' => array( __CLASS__, 'analytics_permissions_check' ),
        ) );

        register_rest_route( 'acs/v1', '/analytics/chart-data', array(
            'methods'             => 'GET',
            'callback'            => array( __CLASS__, 'rest_analytics_chart_data' ),
            'permission_callback' => array( __CLASS__, 'analytics_permissions_check' ),
        ) );

        register_rest_route( 'acs/v1', '/analytics/export', array(
            'methods'             => 'GET',
            'callback'            => array( __CLASS__, 'rest_analytics_export' ),
            'permission_callback' => array( __CLASS__, 'analytics_permissions_check' ),
        ) );
    }

    public static function permissions_check( $request ) {
        // For now, only check user capability and logged-in status
        // WordPress REST API has built-in nonce validation via X-WP-Nonce header
        if ( ! is_user_logged_in() ) {
            error_log( '[ACS REST] User not logged in' );
            return false;
        }
        
        // Allow either generate or manage settings capability
        if ( ! current_user_can( 'acs_generate_content' ) && ! current_user_can( 'acs_manage_settings' ) ) {
            error_log( '[ACS REST] User lacks acs_generate_content or acs_manage_settings capability' );
            return false;
        }
        
        return true;
    }

    public static function analytics_permissions_check( $request ) {
        // For now, only check user capability and logged-in status
        if ( ! is_user_logged_in() ) {
            error_log( '[ACS REST Analytics] User not logged in' );
            return false;
        }
        
        if ( ! current_user_can( 'acs_view_analytics' ) && ! current_user_can( 'acs_manage_settings' ) ) {
            error_log( '[ACS REST Analytics] User lacks analytics or settings capabilities' );
            return false;
        }
        
        return true;
    }

    public static function rest_create_post( $request ) {
        $params = $request->get_json_params();
        // Support two modes: (1) direct create with title+content, or (2) generate+create via generator
        if ( ! empty( $params['generate'] ) && $params['generate'] ) {
            // Expect api_key + topic
            $api_key = sanitize_text_field( $params['api_key'] ?? '' );
            $topic = sanitize_text_field( $params['topic'] ?? '' );
            $keywords = sanitize_text_field( $params['keywords'] ?? '' );
            $word_count = sanitize_text_field( $params['word_count'] ?? 'medium' );
            $publish = ! empty( $params['publish'] );

            if ( empty( $api_key ) || empty( $topic ) ) {
                return new WP_Error( 'missing_params', 'Missing api_key or topic for generation', array( 'status' => 400 ) );
            }

            if ( file_exists( ACS_PLUGIN_PATH . 'generators/class-acs-content-generator.php' ) ) {
                require_once ACS_PLUGIN_PATH . 'generators/class-acs-content-generator.php';
                $generator = new ACS_Content_Generator();
                $result = $generator->generate( array(
                    'api_key' => $api_key,
                    'topic' => $topic,
                    'keywords' => $keywords,
                    'word_count' => $word_count,
                ) );

                if ( is_wp_error( $result ) ) {
                    return new WP_Error( 'generate_failed', $result->get_error_message(), array( 'status' => 500 ) );
                }

                // Create post from generated result
                $post_id = $generator->create_post( $result, $keywords );
                if ( $post_id ) {
                    if ( $publish ) {
                        wp_update_post( array( 'ID' => $post_id, 'post_status' => 'publish' ) );
                    }
                    return rest_ensure_response( array( 'post_id' => $post_id, 'permalink' => get_permalink( $post_id ) ) );
                }

                return new WP_Error( 'create_failed', 'Failed to create post from generated content', array( 'status' => 500 ) );
            }

            return new WP_Error( 'no_generator', 'Content generator not available', array( 'status' => 500 ) );
        }


        // Direct create mode
        // If title or content field is a JSON string, parse and map fields
        $raw_title = $params['title'] ?? '';
        $raw_content = $params['content'] ?? '';
        $parsed = null;
        if ( is_string( $raw_title ) && preg_match('/^\s*{/', $raw_title ) ) {
            $maybe_json = json_decode( $raw_title, true );
            if ( is_array( $maybe_json ) && isset( $maybe_json['title'] ) && isset( $maybe_json['content'] ) ) {
                $parsed = $maybe_json;
            }
        } elseif ( is_string( $raw_content ) && preg_match('/^\s*{/', $raw_content ) ) {
            $maybe_json = json_decode( $raw_content, true );
            if ( is_array( $maybe_json ) && isset( $maybe_json['title'] ) && isset( $maybe_json['content'] ) ) {
                $parsed = $maybe_json;
            }
        }

        if ( $parsed ) {
            $title = sanitize_text_field( $parsed['title'] );
            $content = wp_kses_post( $parsed['content'] );
            $meta_description = isset( $parsed['meta_description'] ) ? sanitize_text_field( $parsed['meta_description'] ) : '';
            $focus_keyword = isset( $parsed['focus_keyword'] ) ? sanitize_text_field( $parsed['focus_keyword'] ) : '';
            $slug = isset( $parsed['slug'] ) ? sanitize_title( $parsed['slug'] ) : sanitize_title( $parsed['title'] );
            $excerpt = isset( $parsed['excerpt'] ) ? sanitize_text_field( $parsed['excerpt'] ) : '';
        } else {
            if ( empty( $params['title'] ) || empty( $params['content'] ) ) {
                return new WP_Error( 'missing_data', 'Missing title or content', array( 'status' => 400 ) );
            }
            $title = sanitize_text_field( $params['title'] );
            $content = wp_kses_post( $params['content'] );
            $meta_description = isset( $params['meta_description'] ) ? sanitize_text_field( $params['meta_description'] ) : '';
            $focus_keyword = isset( $params['focus_keyword'] ) ? sanitize_text_field( $params['focus_keyword'] ) : '';
            $slug = isset( $params['slug'] ) ? sanitize_title( $params['slug'] ) : sanitize_title( $title );
            $excerpt = isset( $params['excerpt'] ) ? sanitize_text_field( $params['excerpt'] ) : '';
        }

        // Reuse creation helper if available in admin class
        if ( class_exists( 'ACS_Admin' ) ) {
            $admin = new ACS_Admin( 'ai-content-studio', ACS_VERSION );
            $result = $admin->create_wordpress_post( array(
                'title' => $title,
                'content' => $content,
                'meta_description' => $meta_description,
                'focus_keyword' => $focus_keyword,
                'slug' => $slug,
                'excerpt' => $excerpt,
            ), '' );

            if ( $result ) {
                return rest_ensure_response( array( 'post_id' => $result ) );
            }
        }

        return new WP_Error( 'create_failed', 'Failed to create post', array( 'status' => 500 ) );
    }

    public static function rest_generate( $request ) {
        $params = $request->get_json_params();
        $api_key = sanitize_text_field( $params['api_key'] ?? '' );
        $topic = sanitize_text_field( $params['topic'] ?? '' );
        $keywords = sanitize_text_field( $params['keywords'] ?? '' );
        $word_count = sanitize_text_field( $params['word_count'] ?? 'medium' );

        if ( empty( $api_key ) || empty( $topic ) ) {
            return new WP_Error( 'missing_data', 'Missing API key or topic', array( 'status' => 400 ) );
        }

        if ( file_exists( ACS_PLUGIN_PATH . 'generators/class-acs-content-generator.php' ) ) {
            require_once ACS_PLUGIN_PATH . 'generators/class-acs-content-generator.php';
            $generator = new ACS_Content_Generator();
            $result = $generator->generate( array(
                'api_key' => $api_key,
                'topic' => $topic,
                'keywords' => $keywords,
                'word_count' => $word_count,
            ) );

            if ( is_wp_error( $result ) ) {
                return new WP_Error( 'generate_failed', $result->get_error_message(), array( 'status' => 500 ) );
            }

            return rest_ensure_response( $result );
        }

        return new WP_Error( 'no_generator', 'Content generator not available', array( 'status' => 500 ) );
    }

    public static function rest_save_settings( $request ) {
        $params = $request->get_json_params();
        if ( ! isset( $params['settings'] ) ) {
            return new WP_Error( 'missing_settings', 'Missing settings payload', array( 'status' => 400 ) );
        }

        if ( class_exists( 'ACS_Settings' ) ) {
            $settings = new ACS_Settings();
            $sanitized = $settings->sanitize_settings( $params['settings'] );
            update_option( 'acs_settings', $sanitized );
            return rest_ensure_response( array( 'success' => true ) );
        }

        return new WP_Error( 'no_module', 'Settings module not available', array( 'status' => 500 ) );
    }
}

add_action( 'rest_api_init', array( 'ACS_REST', 'register_routes' ) );

/**
 * Analytics REST endpoint callbacks
 */
class ACS_REST_Analytics {

    /**
     * Get analytics summary (totals, averages).
     */
    public static function summary( $request ) {
        global $wpdb;

        $from = sanitize_text_field( $request->get_param( 'from' ) );
        $to = sanitize_text_field( $request->get_param( 'to' ) );
        $provider = sanitize_text_field( $request->get_param( 'provider' ) );

        // Require analytics class
        if ( ! class_exists( 'ACS_Analytics' ) ) {
            $file = defined( 'ACS_PLUGIN_PATH' ) ? ACS_PLUGIN_PATH . 'includes/class-acs-analytics.php' : '';
            if ( $file && file_exists( $file ) ) {
                require_once $file;
            }
        }

        if ( ! class_exists( 'ACS_Analytics' ) ) {
            return new WP_Error( 'analytics_unavailable', 'Analytics module not available', array( 'status' => 500 ) );
        }

        $args = array();
        if ( $from ) $args['from'] = $from;
        if ( $to ) $args['to'] = $to;
        if ( $provider ) $args['provider'] = $provider;

        $metrics = ACS_Analytics::get_metrics( $args );

        // Provider breakdown
        $table = $wpdb->prefix . 'acs_generations';
        $provider_sql = "SELECT provider, COUNT(*) as count, SUM(cost_estimate) as cost, AVG(generation_time) as avg_time FROM {$table}";
        $where = array();
        $params = array();
        if ( $from ) {
            $where[] = "created_at >= %s";
            $params[] = $from . ' 00:00:00';
        }
        if ( $to ) {
            $where[] = "created_at <= %s";
            $params[] = $to . ' 23:59:59';
        }
        if ( ! empty( $where ) ) {
            $provider_sql .= ' WHERE ' . implode( ' AND ', $where );
        }
        $provider_sql .= ' GROUP BY provider';

        if ( ! empty( $params ) ) {
            $provider_sql = $wpdb->prepare( $provider_sql, $params );
        }

        $providers = $wpdb->get_results( $provider_sql, ARRAY_A );

        return rest_ensure_response( array(
            'total_generations' => intval( $metrics['total'] ?? 0 ),
            'avg_tokens'        => round( floatval( $metrics['avg_tokens'] ?? 0 ), 1 ),
            'total_cost'        => round( floatval( $metrics['total_cost'] ?? 0 ), 4 ),
            'providers'         => $providers,
        ) );
    }

    /**
     * Get paginated list of generations.
     */
    public static function generations( $request ) {
        global $wpdb;

        $page = max( 1, intval( $request->get_param( 'page' ) ) );
        $per_page = max( 1, min( 100, intval( $request->get_param( 'per_page' ) ?: 20 ) ) );
        $offset = ( $page - 1 ) * $per_page;

        $from = sanitize_text_field( $request->get_param( 'from' ) );
        $to = sanitize_text_field( $request->get_param( 'to' ) );
        $provider = sanitize_text_field( $request->get_param( 'provider' ) );
        $status = sanitize_text_field( $request->get_param( 'status' ) );
        $search = sanitize_text_field( $request->get_param( 'search' ) );

        $table = $wpdb->prefix . 'acs_generations';

        $where = array();
        $params = array();

        if ( $from ) {
            $where[] = "created_at >= %s";
            $params[] = $from . ' 00:00:00';
        }
        if ( $to ) {
            $where[] = "created_at <= %s";
            $params[] = $to . ' 23:59:59';
        }
        if ( $provider ) {
            $where[] = "provider = %s";
            $params[] = $provider;
        }
        if ( $status ) {
            $where[] = "status = %s";
            $params[] = $status;
        }
        if ( $search ) {
            $where[] = "(prompt_text LIKE %s OR response_text LIKE %s)";
            $params[] = '%' . $wpdb->esc_like( $search ) . '%';
            $params[] = '%' . $wpdb->esc_like( $search ) . '%';
        }

        $where_sql = ! empty( $where ) ? 'WHERE ' . implode( ' AND ', $where ) : '';

        // Count total
        $count_sql = "SELECT COUNT(*) FROM {$table} {$where_sql}";
        if ( ! empty( $params ) ) {
            $total = $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) );
        } else {
            $total = $wpdb->get_var( $count_sql );
        }

        // Fetch rows
        $select_sql = "SELECT id, post_id, user_id, provider, model, tokens_used, cost_estimate, generation_time, status, created_at FROM {$table} {$where_sql} ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $params[] = $per_page;
        $params[] = $offset;

        $rows = $wpdb->get_results( $wpdb->prepare( $select_sql, $params ), ARRAY_A );

        return rest_ensure_response( array(
            'total'    => intval( $total ),
            'page'     => $page,
            'per_page' => $per_page,
            'pages'    => ceil( intval( $total ) / $per_page ),
            'data'     => $rows,
        ) );
    }

    /**
     * Get chart data (daily generation counts for last N days).
     */
    public static function chart_data( $request ) {
        global $wpdb;

        $days = max( 1, min( 90, intval( $request->get_param( 'days' ) ?: 7 ) ) );
        $provider = sanitize_text_field( $request->get_param( 'provider' ) );

        $table = $wpdb->prefix . 'acs_generations';

        $where = array( "created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)" );
        $params = array( $days );

        if ( $provider ) {
            $where[] = "provider = %s";
            $params[] = $provider;
        }

        $where_sql = 'WHERE ' . implode( ' AND ', $where );

        $sql = "SELECT DATE(created_at) as date, COUNT(*) as count, SUM(cost_estimate) as cost FROM {$table} {$where_sql} GROUP BY DATE(created_at) ORDER BY date ASC";

        $rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );

        // Fill in missing dates
        $labels = array();
        $counts = array();
        $costs = array();
        $date = new DateTime();
        $date->modify( "-{$days} days" );
        $indexed = array();
        foreach ( $rows as $r ) {
            $indexed[ $r['date'] ] = $r;
        }
        for ( $i = 0; $i <= $days; $i++ ) {
            $d = $date->format( 'Y-m-d' );
            $labels[] = $d;
            $counts[] = isset( $indexed[ $d ] ) ? intval( $indexed[ $d ]['count'] ) : 0;
            $costs[] = isset( $indexed[ $d ] ) ? round( floatval( $indexed[ $d ]['cost'] ), 4 ) : 0;
            $date->modify( '+1 day' );
        }

        return rest_ensure_response( array(
            'labels' => $labels,
            'generations' => $counts,
            'costs' => $costs,
        ) );
    }

    /**
     * Export generations as CSV or JSON.
     */
    public static function export( $request ) {
        global $wpdb;

        $format = sanitize_text_field( $request->get_param( 'format' ) ?: 'csv' );
        $from = sanitize_text_field( $request->get_param( 'from' ) );
        $to = sanitize_text_field( $request->get_param( 'to' ) );
        $provider = sanitize_text_field( $request->get_param( 'provider' ) );

        $table = $wpdb->prefix . 'acs_generations';

        $where = array();
        $params = array();

        if ( $from ) {
            $where[] = "created_at >= %s";
            $params[] = $from . ' 00:00:00';
        }
        if ( $to ) {
            $where[] = "created_at <= %s";
            $params[] = $to . ' 23:59:59';
        }
        if ( $provider ) {
            $where[] = "provider = %s";
            $params[] = $provider;
        }

        $where_sql = ! empty( $where ) ? 'WHERE ' . implode( ' AND ', $where ) : '';

        $sql = "SELECT id, post_id, user_id, provider, model, tokens_used, cost_estimate, generation_time, status, created_at FROM {$table} {$where_sql} ORDER BY created_at DESC LIMIT 5000";

        if ( ! empty( $params ) ) {
            $rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );
        } else {
            $rows = $wpdb->get_results( $sql, ARRAY_A );
        }

        if ( $format === 'json' ) {
            return rest_ensure_response( $rows );
        }

        // CSV
        $output = fopen( 'php://temp', 'r+' );
        if ( ! empty( $rows ) ) {
            fputcsv( $output, array_keys( $rows[0] ) );
            foreach ( $rows as $row ) {
                fputcsv( $output, $row );
            }
        }
        rewind( $output );
        $csv = stream_get_contents( $output );
        fclose( $output );

        $response = new WP_REST_Response( $csv );
        $response->header( 'Content-Type', 'text/csv; charset=utf-8' );
        $response->header( 'Content-Disposition', 'attachment; filename=acs-analytics-export.csv' );
        return $response;
    }
}

// Wire analytics endpoints to the static class
add_filter( 'rest_pre_dispatch', function( $result, $server, $request ) {
    $route = $request->get_route();
    if ( strpos( $route, '/acs/v1/analytics/' ) === 0 ) {
        $endpoint = str_replace( '/acs/v1/analytics/', '', $route );
        switch ( $endpoint ) {
            case 'summary':
                return ACS_REST_Analytics::summary( $request );
            case 'generations':
                return ACS_REST_Analytics::generations( $request );
            case 'chart-data':
                return ACS_REST_Analytics::chart_data( $request );
            case 'export':
                return ACS_REST_Analytics::export( $request );
        }
    }
    return $result;
}, 10, 3 );
