<?php
/**
 * Input sanitization utilities
 *
 * @link       https://lancedesk.com
 * @since      1.0.0
 *
 * @package    ACS
 * @subpackage ACS/security
 */

/**
 * Handles sanitization of user inputs.
 *
 * @since      1.0.0
 * @package    ACS
 * @subpackage ACS/security
 * @author     LanceDesk <support@lancedesk.com>
 */
class ACS_Sanitizer {

    /**
     * Sanitize settings array.
     *
     * @since    1.0.0
     * @param    array    $input    The input array to sanitize.
     * @return   array              The sanitized array.
     */
    public static function sanitize_settings( $input ) {
        $sanitized = array();

        // General settings
        if ( isset( $input['general'] ) && is_array( $input['general'] ) ) {
            $sanitized['general'] = array(
                'site_niche' => isset( $input['general']['site_niche'] ) ? sanitize_text_field( $input['general']['site_niche'] ) : '',
                'brand_voice' => isset( $input['general']['brand_voice'] ) ? sanitize_text_field( $input['general']['brand_voice'] ) : '',
                'content_goals' => isset( $input['general']['content_goals'] ) && is_array( $input['general']['content_goals'] ) 
                    ? array_map( 'sanitize_text_field', $input['general']['content_goals'] ) : array(),
                'onboarding_completed' => isset( $input['general']['onboarding_completed'] ) ? (bool) $input['general']['onboarding_completed'] : false,
            );
        }

        // Provider settings
        if ( isset( $input['providers'] ) && is_array( $input['providers'] ) ) {
            $sanitized['providers'] = array(
                'primary' => isset( $input['providers']['primary'] ) ? sanitize_text_field( $input['providers']['primary'] ) : 'groq',
                'fallback' => isset( $input['providers']['fallback'] ) ? sanitize_text_field( $input['providers']['fallback'] ) : 'openai',
            );

            // Sanitize individual provider configs
            $providers = array( 'groq', 'openai', 'anthropic' );
            foreach ( $providers as $provider ) {
                if ( isset( $input['providers'][$provider] ) && is_array( $input['providers'][$provider] ) ) {
                    $sanitized['providers'][$provider] = array(
                        'api_key' => isset( $input['providers'][$provider]['api_key'] ) 
                            ? self::sanitize_api_key( $input['providers'][$provider]['api_key'] ) : '',
                        'model' => isset( $input['providers'][$provider]['model'] ) 
                            ? sanitize_text_field( $input['providers'][$provider]['model'] ) : '',
                        'enabled' => isset( $input['providers'][$provider]['enabled'] ) 
                            ? (bool) $input['providers'][$provider]['enabled'] : false,
                    );
                }
            }
            // Additional provider-level settings (content vs image provider)
            if ( isset( $input['default_provider'] ) ) {
                $sanitized['default_provider'] = sanitize_text_field( $input['default_provider'] );
            }
            if ( isset( $input['image_provider'] ) ) {
                $sanitized['image_provider'] = sanitize_text_field( $input['image_provider'] );
            }
            // Backup providers array (optional)
            if ( isset( $input['backup_providers'] ) && is_array( $input['backup_providers'] ) ) {
                $sanitized['backup_providers'] = array_map( 'sanitize_text_field', $input['backup_providers'] );
            }
        }

        // SEO settings
        if ( isset( $input['seo'] ) && is_array( $input['seo'] ) ) {
            $sanitized['seo'] = array(
                'detected_plugin' => isset( $input['seo']['detected_plugin'] ) ? sanitize_text_field( $input['seo']['detected_plugin'] ) : '',
                'auto_optimize' => isset( $input['seo']['auto_optimize'] ) ? (bool) $input['seo']['auto_optimize'] : true,
                'schema_enabled' => isset( $input['seo']['schema_enabled'] ) ? (bool) $input['seo']['schema_enabled'] : true,
                'auto_internal_links' => isset( $input['seo']['auto_internal_links'] ) ? (bool) $input['seo']['auto_internal_links'] : true,
                'target_readability' => isset( $input['seo']['target_readability'] ) ? sanitize_text_field( $input['seo']['target_readability'] ) : 'good',
            );
        }

        // Content settings
        if ( isset( $input['content'] ) && is_array( $input['content'] ) ) {
            $sanitized['content'] = array(
                'default_length' => isset( $input['content']['default_length'] ) ? absint( $input['content']['default_length'] ) : 1500,
                'tone' => isset( $input['content']['tone'] ) ? sanitize_text_field( $input['content']['tone'] ) : 'professional',
                'include_images' => isset( $input['content']['include_images'] ) ? (bool) $input['content']['include_images'] : true,
                'humanization_level' => isset( $input['content']['humanization_level'] ) ? sanitize_text_field( $input['content']['humanization_level'] ) : 'high',
                'auto_publish' => isset( $input['content']['auto_publish'] ) ? (bool) $input['content']['auto_publish'] : false,
                'require_review' => isset( $input['content']['require_review'] ) ? (bool) $input['content']['require_review'] : true,
            );
        }

        return $sanitized;
    }

    /**
     * Sanitize API key input.
     *
     * @since    1.0.0
     * @param    string    $api_key    The API key to sanitize.
     * @return   string                The sanitized API key.
     */
    public static function sanitize_api_key( $api_key ) {
        // Remove any whitespace and ensure it's a valid string
        $api_key = trim( sanitize_text_field( $api_key ) );
        
        // Basic validation - most API keys are alphanumeric with some special chars
        if ( ! empty( $api_key ) && ! preg_match( '/^[a-zA-Z0-9_\-\.]+$/', $api_key ) ) {
            return '';
        }

        return $api_key;
    }

    /**
     * Sanitize prompt input.
     *
     * @since    1.0.0
     * @param    array    $input    The prompt input array.
     * @return   array              The sanitized input.
     */
    public static function sanitize_prompt_input( $input ) {
        return array(
            'prompt' => isset( $input['prompt'] ) ? sanitize_textarea_field( $input['prompt'] ) : '',
            'keywords' => isset( $input['keywords'] ) && is_array( $input['keywords'] ) 
                ? array_map( 'sanitize_text_field', $input['keywords'] ) : array(),
            'tone' => isset( $input['tone'] ) ? sanitize_text_field( $input['tone'] ) : 'professional',
            'length' => isset( $input['length'] ) ? absint( $input['length'] ) : 1500,
            'include_images' => isset( $input['include_images'] ) ? (bool) $input['include_images'] : true,
            'publish_type' => isset( $input['publish_type'] ) ? sanitize_text_field( $input['publish_type'] ) : 'draft',
            'category' => isset( $input['category'] ) ? absint( $input['category'] ) : 0,
            'tags' => isset( $input['tags'] ) && is_array( $input['tags'] ) 
                ? array_map( 'sanitize_text_field', $input['tags'] ) : array(),
        );
    }

    /**
     * Sanitize keyword input.
     *
     * @since    1.0.0
     * @param    string    $keyword    The keyword to sanitize.
     * @return   string                The sanitized keyword.
     */
    public static function sanitize_keyword( $keyword ) {
        // Remove extra whitespace and convert to lowercase
        $keyword = trim( strtolower( sanitize_text_field( $keyword ) ) );
        
        // Remove any characters that aren't letters, numbers, spaces, or hyphens
        $keyword = preg_replace( '/[^a-z0-9\s\-]/', '', $keyword );
        
        // Replace multiple spaces with single space
        $keyword = preg_replace( '/\s+/', ' ', $keyword );
        
        return $keyword;
    }

    /**
     * Sanitize HTML content for posts.
     *
     * @since    1.0.0
     * @param    string    $content    The content to sanitize.
     * @return   string                The sanitized content.
     */
    public static function sanitize_post_content( $content ) {
        // Allow most HTML tags that are commonly used in posts
        $allowed_tags = array(
            'p' => array(),
            'br' => array(),
            'strong' => array(),
            'b' => array(),
            'em' => array(),
            'i' => array(),
            'u' => array(),
            'h1' => array(),
            'h2' => array(),
            'h3' => array(),
            'h4' => array(),
            'h5' => array(),
            'h6' => array(),
            'ul' => array(),
            'ol' => array(),
            'li' => array(),
            'a' => array(
                'href' => array(),
                'title' => array(),
                'target' => array(),
                'rel' => array(),
            ),
            'img' => array(
                'src' => array(),
                'alt' => array(),
                'title' => array(),
                'width' => array(),
                'height' => array(),
            ),
            'blockquote' => array(),
            'code' => array(),
            'pre' => array(),
            'div' => array(
                'class' => array(),
            ),
            'span' => array(
                'class' => array(),
            ),
        );

        return wp_kses( $content, $allowed_tags );
    }

    /**
     * Sanitize file upload.
     *
     * @since    1.0.0
     * @param    array    $file    The uploaded file array.
     * @return   array|WP_Error     The sanitized file array or error.
     */
    public static function sanitize_file_upload( $file ) {
        // Check if file was uploaded
        if ( ! isset( $file['tmp_name'] ) || empty( $file['tmp_name'] ) ) {
            return new WP_Error( 'no_file', __( 'No file was uploaded.', 'ai-content-studio' ) );
        }

        // Check file size (limit to 5MB)
        if ( $file['size'] > 5 * 1024 * 1024 ) {
            return new WP_Error( 'file_too_large', __( 'File is too large. Maximum size is 5MB.', 'ai-content-studio' ) );
        }

        // Check file type
        $allowed_types = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' );
        $file_type = wp_check_filetype( $file['name'] );
        
        if ( ! in_array( $file['type'], $allowed_types ) || ! in_array( $file_type['type'], $allowed_types ) ) {
            return new WP_Error( 'invalid_file_type', __( 'Invalid file type. Only JPEG, PNG, GIF, and WebP images are allowed.', 'ai-content-studio' ) );
        }

        // Sanitize filename
        $file['name'] = sanitize_file_name( $file['name'] );

        return $file;
    }
}