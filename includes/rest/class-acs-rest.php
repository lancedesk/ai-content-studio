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
    }

    public static function permissions_check( $request ) {
        return current_user_can( 'acs_manage_settings' );
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
        if ( empty( $params['title'] ) || empty( $params['content'] ) ) {
            return new WP_Error( 'missing_data', 'Missing title or content', array( 'status' => 400 ) );
        }

        $title = sanitize_text_field( $params['title'] );
        $content = wp_kses_post( $params['content'] );
        $meta_description = isset( $params['meta_description'] ) ? sanitize_text_field( $params['meta_description'] ) : '';
        $focus_keyword = isset( $params['focus_keyword'] ) ? sanitize_text_field( $params['focus_keyword'] ) : '';

        // Reuse creation helper if available in admin class
        if ( class_exists( 'ACS_Admin' ) ) {
            $admin = new ACS_Admin( 'ai-content-studio', ACS_VERSION );
            $result = $admin->create_wordpress_post( array(
                'title' => $title,
                'content' => $content,
                'meta_description' => $meta_description,
                'focus_keyword' => $focus_keyword,
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
