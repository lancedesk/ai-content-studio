<?php
/**
 * Input validation utilities
 *
 * @link       https://lancedesk.com
 * @since      1.0.0
 *
 * @package    ACS
 * @subpackage ACS/security
 */

/**
 * Handles validation of user inputs and permissions.
 *
 * @since      1.0.0
 * @package    ACS
 * @subpackage ACS/security
 * @author     LanceDesk <support@lancedesk.com>
 */
class ACS_Validator {

    /**
     * Validate user permissions for an action.
     *
     * @since    1.0.0
     * @param    string    $action    The action to validate.
     * @return   bool                 True if user has permission.
     */
    public static function validate_permission( $action ) {
        switch ( $action ) {
            case 'generate_content':
                return current_user_can( 'acs_generate_content' );
                
            case 'manage_settings':
                return current_user_can( 'acs_manage_settings' );
                
            case 'view_analytics':
                return current_user_can( 'acs_view_analytics' );
                
            case 'bulk_generate':
                return current_user_can( 'acs_bulk_generate' );
                
            default:
                return current_user_can( 'manage_options' );
        }
    }

    /**
     * Validate nonce for security.
     *
     * @since    1.0.0
     * @param    string    $nonce     The nonce to validate.
     * @param    string    $action    The action for the nonce.
     * @return   bool                 True if nonce is valid.
     */
    public static function validate_nonce( $nonce, $action ) {
        return wp_verify_nonce( $nonce, $action );
    }

    /**
     * Validate API key format.
     *
     * @since    1.0.0
     * @param    string    $api_key     The API key to validate.
     * @param    string    $provider    The provider name.
     * @return   bool                   True if API key format is valid.
     */
    public static function validate_api_key( $api_key, $provider ) {
        if ( empty( $api_key ) ) {
            return false;
        }

        switch ( $provider ) {
            case 'groq':
                // Groq API keys typically start with 'gsk_' and are 56 characters long
                return preg_match( '/^gsk_[a-zA-Z0-9]{52}$/', $api_key );
                
            case 'openai':
                // OpenAI API keys typically start with 'sk-' and are 51 characters long
                return preg_match( '/^sk-[a-zA-Z0-9]{48}$/', $api_key );
                
            case 'anthropic':
                // Anthropic API keys typically start with 'sk-ant-' and are variable length
                return preg_match( '/^sk-ant-[a-zA-Z0-9\-_]{20,}$/', $api_key );
                
            default:
                // Generic validation - at least 20 characters, alphanumeric with some symbols
                return strlen( $api_key ) >= 20 && preg_match( '/^[a-zA-Z0-9_\-\.]+$/', $api_key );
        }
    }

    /**
     * Validate email address.
     *
     * @since    1.0.0
     * @param    string    $email    The email to validate.
     * @return   bool                True if email is valid.
     */
    public static function validate_email( $email ) {
        return is_email( $email );
    }

    /**
     * Validate URL.
     *
     * @since    1.0.0
     * @param    string    $url    The URL to validate.
     * @return   bool              True if URL is valid.
     */
    public static function validate_url( $url ) {
        return filter_var( $url, FILTER_VALIDATE_URL ) !== false;
    }

    /**
     * Validate content length.
     *
     * @since    1.0.0
     * @param    int       $length    The content length.
     * @return   bool                 True if length is valid.
     */
    public static function validate_content_length( $length ) {
        return is_numeric( $length ) && $length >= 100 && $length <= 5000;
    }

    /**
     * Validate keyword.
     *
     * @since    1.0.0
     * @param    string    $keyword    The keyword to validate.
     * @return   bool                  True if keyword is valid.
     */
    public static function validate_keyword( $keyword ) {
        // Keywords should be 1-50 characters, letters, numbers, spaces, hyphens
        return ! empty( $keyword ) && 
               strlen( $keyword ) <= 50 && 
               preg_match( '/^[a-zA-Z0-9\s\-]+$/', $keyword );
    }

    /**
     * Validate tone setting.
     *
     * @since    1.0.0
     * @param    string    $tone    The tone to validate.
     * @return   bool               True if tone is valid.
     */
    public static function validate_tone( $tone ) {
        $valid_tones = array( 
            'professional', 'casual', 'friendly', 'authoritative', 
            'conversational', 'formal', 'humorous', 'inspiring' 
        );
        return in_array( $tone, $valid_tones );
    }

    /**
     * Validate provider name.
     *
     * @since    1.0.0
     * @param    string    $provider    The provider to validate.
     * @return   bool                   True if provider is valid.
     */
    public static function validate_provider( $provider ) {
        $valid_providers = array( 'groq', 'openai', 'anthropic', 'cohere', 'local' );
        return in_array( $provider, $valid_providers );
    }

    /**
     * Validate model name for a provider.
     *
     * @since    1.0.0
     * @param    string    $model       The model to validate.
     * @param    string    $provider    The provider name.
     * @return   bool                   True if model is valid for the provider.
     */
    public static function validate_model( $model, $provider ) {
        $valid_models = array(
            'groq' => array( 
                'llama-3.3-70b-versatile', 'llama-3.1-70b-versatile', 
                'mixtral-8x7b-32768', 'gemma-2-9b-it' 
            ),
            'openai' => array( 
                'gpt-4o', 'gpt-4o-mini', 'gpt-4-turbo', 'gpt-3.5-turbo' 
            ),
            'anthropic' => array( 
                'claude-3-5-sonnet-20241022', 'claude-3-opus-20240229', 
                'claude-3-sonnet-20240229' 
            ),
        );

        if ( ! isset( $valid_models[$provider] ) ) {
            return false;
        }

        return in_array( $model, $valid_models[$provider] );
    }

    /**
     * Validate POST request data.
     *
     * @since    1.0.0
     * @param    array     $required_fields    Array of required field names.
     * @param    array     $data               The POST data to validate.
     * @return   array|WP_Error                Validated data or error.
     */
    public static function validate_post_data( $required_fields, $data ) {
        $errors = array();

        // Check for required fields
        foreach ( $required_fields as $field ) {
            if ( ! isset( $data[$field] ) || empty( $data[$field] ) ) {
                $errors[] = sprintf( __( 'Missing required field: %s', 'ai-content-studio' ), $field );
            }
        }

        if ( ! empty( $errors ) ) {
            return new WP_Error( 'missing_fields', implode( ', ', $errors ) );
        }

        return $data;
    }

    /**
     * Validate AJAX request.
     *
     * @since    1.0.0
     * @param    string    $action    The AJAX action name.
     * @param    string    $nonce     The nonce value.
     * @return   bool|WP_Error        True if valid, error if not.
     */
    public static function validate_ajax_request( $action, $nonce ) {
        // Check nonce
        if ( ! self::validate_nonce( $nonce, 'acs_' . $action ) ) {
            return new WP_Error( 'invalid_nonce', __( 'Security check failed.', 'ai-content-studio' ) );
        }

        // Check if user is logged in
        if ( ! is_user_logged_in() ) {
            return new WP_Error( 'not_logged_in', __( 'You must be logged in to perform this action.', 'ai-content-studio' ) );
        }

        return true;
    }

    /**
     * Validate file upload.
     *
     * @since    1.0.0
     * @param    array     $file    The uploaded file data.
     * @return   bool|WP_Error      True if valid, error if not.
     */
    public static function validate_file_upload( $file ) {
        // Check if file exists
        if ( ! isset( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
            return new WP_Error( 'invalid_upload', __( 'Invalid file upload.', 'ai-content-studio' ) );
        }

        // Check file size (5MB limit)
        if ( $file['size'] > 5 * 1024 * 1024 ) {
            return new WP_Error( 'file_too_large', __( 'File size exceeds 5MB limit.', 'ai-content-studio' ) );
        }

        // Check MIME type
        $allowed_types = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' );
        $finfo = finfo_open( FILEINFO_MIME_TYPE );
        $mime_type = finfo_file( $finfo, $file['tmp_name'] );
        finfo_close( $finfo );

        if ( ! in_array( $mime_type, $allowed_types ) ) {
            return new WP_Error( 'invalid_file_type', __( 'Invalid file type. Only images are allowed.', 'ai-content-studio' ) );
        }

        return true;
    }
}