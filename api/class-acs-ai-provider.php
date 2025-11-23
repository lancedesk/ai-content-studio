<?php
/**
 * AI Provider interface
 *
 * @link       https://lancedesk.com
 * @since      1.0.0
 *
 * @package    ACS
 * @subpackage ACS/api
 */

/**
 * Interface for AI providers.
 *
 * @since      1.0.0
 * @package    ACS
 * @subpackage ACS/api
 */
interface ACS_AI_Provider_Interface {

    /**
     * Authenticate with the provider.
     *
     * @since    1.0.0
     * @param    string    $api_key    The API key.
     * @return   bool                  True if authentication successful.
     */
    public function authenticate( $api_key );

    /**
     * Generate content using the provider.
     *
     * @since    1.0.0
     * @param    string    $prompt     The prompt text.
     * @param    array     $options    Additional options.
     * @return   array|WP_Error        The generated content or error.
     */
    public function generate_content( $prompt, $options = array() );

    /**
     * Generate an image using the provider.
     *
     * @since    1.0.0
     * @param    string    $prompt     The image prompt.
     * @param    array     $options    Additional options.
     * @return   array|WP_Error        The generated image data or error.
     */
    public function generate_image( $prompt, $options = array() );

    /**
     * Get available models for this provider.
     *
     * @since    1.0.0
     * @return   array    Array of model names.
     */
    public function get_models();

    /**
     * Calculate cost for token usage.
     *
     * @since    1.0.0
     * @param    int       $tokens    Number of tokens.
     * @return   float               Cost in USD.
     */
    public function calculate_cost( $tokens );

    /**
     * Check rate limit status.
     *
     * @since    1.0.0
     * @return   array    Rate limit information.
     */
    public function check_rate_limit();
}

/**
 * Base class for AI providers.
 *
 * @since      1.0.0
 * @package    ACS
 * @subpackage ACS/api
 * @author     LanceDesk <support@lancedesk.com>
 */
abstract class ACS_AI_Provider_Base implements ACS_AI_Provider_Interface {

    /**
     * The provider name.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $provider_name    The provider name.
     */
    protected $provider_name;

    /**
     * The API key.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $api_key    The API key.
     */
    protected $api_key;

    /**
     * The base API URL.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $api_base_url    The base API URL.
     */
    protected $api_base_url;

    /**
     * Rate limit information.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $rate_limit    Rate limit data.
     */
    protected $rate_limit;

    /**
     * Constructor.
     *
     * @since    1.0.0
     * @param    string    $api_key    The API key.
     */
    public function __construct( $api_key = '' ) {
        $this->api_key = $api_key;
        $this->rate_limit = array(
            'requests_per_minute' => 0,
            'requests_remaining' => 0,
            'reset_time' => 0,
        );
    }

    /**
     * Make HTTP request to API.
     *
     * @since    1.0.0
     * @param    string    $endpoint    The API endpoint.
     * @param    array     $data        The request data.
     * @param    string    $method      The HTTP method.
     * @return   array|WP_Error         The response or error.
     */
    protected function make_request( $endpoint, $data = array(), $method = 'POST' ) {
        $url = $this->api_base_url . $endpoint;
        
        $headers = $this->get_request_headers();
        
        $args = array(
            'method' => $method,
            'headers' => $headers,
            'timeout' => 60,
        );

        if ( $method === 'POST' && ! empty( $data ) ) {
            $args['body'] = wp_json_encode( $data );
        }

        $response = wp_remote_request( $url, $args );

        if ( is_wp_error( $response ) ) {
            $this->log_api_call( $endpoint, $data, $response );
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $decoded_body = json_decode( $body, true );

        // Update rate limit info from headers
        $this->update_rate_limit_from_headers( wp_remote_retrieve_headers( $response ) );

        if ( $status_code >= 400 ) {
            $error_message = isset( $decoded_body['error']['message'] ) ? 
                $decoded_body['error']['message'] : 
                sprintf( __( 'API request failed with status %d', 'ai-content-studio' ), $status_code );
            
            $error = new WP_Error( 'api_error', $error_message, array( 'status_code' => $status_code ) );
            $this->log_api_call( $endpoint, $data, $error );
            return $error;
        }

        $this->log_api_call( $endpoint, $data, $decoded_body );
        return $decoded_body;
    }

    /**
     * Get request headers for API calls.
     *
     * @since    1.0.0
     * @return   array    The request headers.
     */
    abstract protected function get_request_headers();

    /**
     * Update rate limit information from response headers.
     *
     * @since    1.0.0
     * @param    array    $headers    The response headers.
     */
    protected function update_rate_limit_from_headers( $headers ) {
        // Override in child classes based on provider's header format
    }

    /**
     * Log API call for monitoring.
     *
     * @since    1.0.0
     * @param    string    $endpoint    The API endpoint.
     * @param    array     $request     The request data.
     * @param    mixed     $response    The response data or error.
     */
    protected function log_api_call( $endpoint, $request, $response ) {
        global $wpdb;

        $status_code = 200;
        $error_message = '';
        $request_tokens = 0;
        $response_tokens = 0;
        $cost = 0;

        if ( is_wp_error( $response ) ) {
            $status_code = isset( $response->get_error_data()['status_code'] ) ? 
                $response->get_error_data()['status_code'] : 0;
            $error_message = $response->get_error_message();
        } else {
            // Extract token usage if available
            if ( isset( $response['usage'] ) ) {
                $request_tokens = isset( $response['usage']['prompt_tokens'] ) ? 
                    $response['usage']['prompt_tokens'] : 0;
                $response_tokens = isset( $response['usage']['completion_tokens'] ) ? 
                    $response['usage']['completion_tokens'] : 0;
                $cost = $this->calculate_cost( $request_tokens + $response_tokens );
            }
        }

        $wpdb->insert(
            $wpdb->prefix . 'acs_api_logs',
            array(
                'provider' => $this->provider_name,
                'endpoint' => $endpoint,
                'request_tokens' => $request_tokens,
                'response_tokens' => $response_tokens,
                'cost' => $cost,
                'status_code' => $status_code,
                'error_message' => $error_message,
                'created_at' => current_time( 'mysql' ),
            ),
            array( '%s', '%s', '%d', '%d', '%f', '%d', '%s', '%s' )
        );
    }

    /**
     * Get provider name.
     *
     * @since    1.0.0
     * @return   string    The provider name.
     */
    public function get_provider_name() {
        return $this->provider_name;
    }

    /**
     * Check if provider is properly configured.
     *
     * @since    1.0.0
     * @return   bool    True if configured.
     */
    public function is_configured() {
        return ! empty( $this->api_key );
    }
}