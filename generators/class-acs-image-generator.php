<?php
/**
 * Image generator stub for AI Content Studio
 *
 * This is a minimal placeholder. Real implementations should call an image
 * provider (Stable Diffusion, DALL·E, Groq Vision) and return an attachment ID.
 *
 * @package ACS
 */
if ( ! class_exists( 'ACS_Image_Generator' ) ) :
class ACS_Image_Generator {

    /**
     * Generate a featured image based on prompt and attach to media library.
     * @param string $prompt
     * @param int $post_id Optional post to attach
     * @return int|WP_Error Attachment ID on success, WP_Error or false on failure
     */
    public function generate_featured_image( $prompt, $post_id = 0 ) {
        if ( empty( $prompt ) ) {
            return new WP_Error( 'no_prompt', 'No image prompt provided' );
        }

        $settings = get_option( 'acs_settings', array() );
        $image_provider = isset( $settings['image_provider'] ) ? $settings['image_provider'] : ( $settings['default_provider'] ?? 'openai' );
        $backup_providers = isset( $settings['backup_providers'] ) && is_array( $settings['backup_providers'] ) ? $settings['backup_providers'] : array();

        $ordered = array_values( array_unique( array_merge( array( $image_provider ), $backup_providers ) ) );

        $provider_map = array(
            'openai' => 'ACS_OpenAI',
            'stable_diffusion' => 'ACS_Stable_Diffusion',
            'unsplash' => 'ACS_Unsplash',
        );

        foreach ( $ordered as $prov ) {
            if ( empty( $prov ) ) {
                continue;
            }

            $class = isset( $provider_map[ $prov ] ) ? $provider_map[ $prov ] : false;
            if ( ! $class ) {
                continue;
            }

            if ( ! class_exists( $class ) ) {
                $file = ACS_PLUGIN_PATH . 'api/providers/class-acs-' . $prov . '.php';
                if ( file_exists( $file ) ) {
                    require_once $file;
                }
            }

            if ( ! class_exists( $class ) ) {
                continue;
            }

            $prov_api_key = '';
            if ( isset( $settings['providers'][ $prov ]['api_key'] ) ) {
                $prov_api_key = sanitize_text_field( $settings['providers'][ $prov ]['api_key'] );
            }

            // Fallback to provider-specific keys in top-level settings
            if ( empty( $prov_api_key ) && isset( $settings['providers'][ $prov ] ) && is_string( $settings['providers'][ $prov ] ) ) {
                $prov_api_key = sanitize_text_field( $settings['providers'][ $prov ] );
            }

            $instance = new $class( $prov_api_key );
            if ( ! method_exists( $instance, 'generate_image' ) ) {
                continue;
            }

            $image_result = $instance->generate_image( $prompt, array( 'size' => '1024x1024', 'n' => 1 ) );

            if ( is_wp_error( $image_result ) ) {
                // Try next provider
                continue;
            }

            $image_url = $image_result['url'] ?? '';
            if ( empty( $image_url ) ) {
                continue;
            }

            $response = wp_remote_get( $image_url );
            if ( is_wp_error( $response ) ) {
                continue;
            }

            $code = wp_remote_retrieve_response_code( $response );
            if ( $code !== 200 ) {
                continue;
            }

            $body = wp_remote_retrieve_body( $response );
            if ( empty( $body ) ) {
                continue;
            }

            if ( ! function_exists( 'wp_upload_bits' ) ) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
            }

            $upload_dir = wp_upload_dir();
            $ext = pathinfo( parse_url( $image_url, PHP_URL_PATH ), PATHINFO_EXTENSION ) ?: 'png';
            $filename = sanitize_file_name( 'acs_image_' . time() . '.' . $ext );
            $file_path = trailingslashit( $upload_dir['path'] ) . $filename;

            $saved = file_put_contents( $file_path, $body );
            if ( $saved === false ) {
                continue;
            }

            $filetype = wp_check_filetype( $filename, null );

            if ( ! function_exists( 'wp_insert_attachment' ) ) {
                require_once ABSPATH . 'wp-admin/includes/image.php';
                require_once ABSPATH . 'wp-admin/includes/file.php';
                require_once ABSPATH . 'wp-admin/includes/media.php';
            }

            $attachment = array(
                'post_mime_type' => $filetype['type'] ?? 'image/png',
                'post_title' => sanitize_text_field( wp_strip_all_tags( $prompt ) ),
                'post_content' => '',
                'post_status' => 'inherit'
            );

            $attach_id = wp_insert_attachment( $attachment, $file_path, $post_id );
            if ( is_wp_error( $attach_id ) || ! $attach_id ) {
                continue;
            }

            $meta = wp_generate_attachment_metadata( $attach_id, $file_path );
            wp_update_attachment_metadata( $attach_id, $meta );

            return $attach_id;
        }

        return new WP_Error( 'no_provider', 'No image provider could generate an image' );
    }
}
endif;
