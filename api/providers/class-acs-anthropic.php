<?php
/**
 * Anthropic Claude provider implementation
 *
 * @link       https://lancedesk.com
 * @since      1.0.0
 *
 * @package    ACS
 * @subpackage ACS/api/providers
 */

/**
 * Anthropic Claude provider class.
 *
 * @since      1.0.0
 * @package    ACS
 * @subpackage ACS/api/providers
 * @author     LanceDesk <support@lancedesk.com>
 */
class ACS_Anthropic extends ACS_AI_Provider_Base {

    /**
     * Constructor.
     *
     * @since    1.0.0
     * @param    string    $api_key    The Anthropic API key.
     */
    public function __construct( $api_key = '' ) {
        parent::__construct( $api_key );
        $this->provider_name = 'anthropic';
        $this->api_base_url = 'https://api.anthropic.com/v1/';
    }

    /**
     * Authenticate with Anthropic API.
     *
     * @since    1.0.0
     * @param    string    $api_key    The API key.
     * @return   bool                  True if authentication successful.
     */
    public function authenticate( $api_key ) {
        $this->api_key = $api_key;
        
        // Test API key with a simple request
        $test_data = array(
            'model' => 'claude-3-5-sonnet-20241022',
            'max_tokens' => 10,
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => 'Hello'
                )
            )
        );
        
        $response = $this->make_request( 'messages', $test_data );
        
        return ! is_wp_error( $response );
    }

    /**
     * Generate content using Anthropic API.
     *
     * @since    1.0.0
     * @param    string    $prompt     The prompt text.
     * @param    array     $options    Additional options.
     * @return   array|WP_Error        The generated content or error.
     */
    public function generate_content( $prompt, $options = array() ) {
        $defaults = array(
            'model' => 'claude-3-5-sonnet-20241022',
            'max_tokens' => 2048,
            'temperature' => 0.7,
        );

        $options = wp_parse_args( $options, $defaults );

        $data = array(
            'model' => $options['model'],
            'max_tokens' => $options['max_tokens'],
            'temperature' => $options['temperature'],
            'system' => $this->get_system_prompt( $options ),
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $prompt,
                ),
            ),
        );

        $response = $this->make_request( 'messages', $data );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        // Extract content from response
        if ( ! isset( $response['content'][0]['text'] ) ) {
            return new WP_Error( 'invalid_response', __( 'Invalid response from Anthropic API.', 'ai-content-studio' ) );
        }

        $content = $response['content'][0]['text'];
        
        return $this->parse_generated_content( $content, $response );
    }

    /**
     * Generate an image (Anthropic doesn't support image generation).
     *
     * @since    1.0.0
     * @param    string    $prompt     The image prompt.
     * @param    array     $options    Additional options.
     * @return   WP_Error              Error indicating no image generation support.
     */
    public function generate_image( $prompt, $options = array() ) {
        return new WP_Error( 
            'not_supported', 
            __( 'Image generation is not supported by Anthropic Claude. Please configure an alternative provider for images.', 'ai-content-studio' ) 
        );
    }

    /**
     * Get available models for Anthropic.
     *
     * @since    1.0.0
     * @return   array    Array of model names.
     */
    public function get_models() {
        return array(
            'claude-3-5-sonnet-20241022' => __( 'Claude 3.5 Sonnet', 'ai-content-studio' ),
            'claude-3-opus-20240229' => __( 'Claude 3 Opus', 'ai-content-studio' ),
            'claude-3-sonnet-20240229' => __( 'Claude 3 Sonnet', 'ai-content-studio' ),
        );
    }

    /**
     * Calculate cost for Anthropic API usage.
     *
     * @since    1.0.0
     * @param    int       $tokens    Number of tokens.
     * @return   float               Cost in USD.
     */
    public function calculate_cost( $tokens ) {
        // Anthropic pricing (check current rates)
        $cost_per_token = array(
            'claude-3-5-sonnet-20241022' => 0.000003, // $3 per 1M input tokens
            'claude-3-opus-20240229' => 0.000015, // $15 per 1M input tokens
            'claude-3-sonnet-20240229' => 0.000003, // $3 per 1M input tokens
        );

        $model = 'claude-3-5-sonnet-20241022'; // Default model
        $settings = get_option( 'acs_settings', array() );
        
        if ( isset( $settings['providers']['anthropic']['model'] ) ) {
            $model = $settings['providers']['anthropic']['model'];
        }

        $rate = isset( $cost_per_token[$model] ) ? $cost_per_token[$model] : $cost_per_token['claude-3-5-sonnet-20241022'];
        
        return $tokens * $rate;
    }

    /**
     * Check Anthropic rate limit status.
     *
     * @since    1.0.0
     * @return   array    Rate limit information.
     */
    public function check_rate_limit() {
        return $this->rate_limit;
    }

    /**
     * Get request headers for Anthropic API.
     *
     * @since    1.0.0
     * @return   array    The request headers.
     */
    protected function get_request_headers() {
        return array(
            'x-api-key' => $this->api_key,
            'content-type' => 'application/json',
            'anthropic-version' => '2023-06-01',
            'User-Agent' => 'AI-Content-Studio/' . ACS_VERSION,
        );
    }

    /**
     * Get system prompt for content generation.
     *
     * @since    1.0.0
     * @param    array    $options    Generation options.
     * @return   string               The system prompt.
     */
    private function get_system_prompt( $options ) {
        $settings = get_option( 'acs_settings', array() );
        $general = isset( $settings['general'] ) ? $settings['general'] : array();
        $content_settings = isset( $settings['content'] ) ? $settings['content'] : array();

        $niche = isset( $general['site_niche'] ) ? $general['site_niche'] : 'general';
        $brand_voice = isset( $general['brand_voice'] ) ? $general['brand_voice'] : 'professional';
        $tone = isset( $content_settings['tone'] ) ? $content_settings['tone'] : 'professional';

        $prompt = "You are an expert content writer and SEO specialist for {$niche}. Voice: {$brand_voice}. Tone: {$tone}. ";
        $prompt .= "Produce high-quality, SEO-optimized content that reads as human-written.\n";
        $prompt .= "OUTPUT SPEC: Return ONLY a valid JSON object with these fields: \n";
        $prompt .= "  title (string) - SEO title, MUST begin with focus_keyword; keep <60 chars.\n";
        $prompt .= "  meta_description (string) - <=155 chars, include focus_keyword/synonym.\n";
        $prompt .= "  slug (string) - seo-friendly slug.\n";
        $prompt .= "  content (string) - full article HTML (use <h2>/<h3>, <p>, <ul>/<ol>); first paragraph must include focus_keyword.\n";
        $prompt .= "  excerpt (string) - <=150 chars.\n";
        $prompt .= "  focus_keyword (string).\n";
        $prompt .= "  secondary_keywords (array).\n";
        $prompt .= "  tags (array).\n";
        $prompt .= "  image_prompts (array of objects {prompt, alt}) - at least 1; alt must mention focus_keyword.\n";
        $prompt .= "  internal_links (array of objects {anchor_text, target}) - suggest at least 2.\n";
        $prompt .= "READABILITY & SEO CONSTRAINTS: Average sentence length <=20 words; max 15% sentences >25 words; use transition words in >=30% sentences; target focus_keyword density 1-2% and distribute evenly; include at least 1 image with alt text containing the focus keyword; suggest at least 2 internal links; do NOT add <b> or <strong> tags around keywords.\n";
        $prompt .= "Return only the JSON object and nothing else.";

        return $prompt;
    }

    /**
     * Parse generated content from Anthropic response.
     *
     * @since    1.0.0
     * @param    string    $content     The generated content.
     * @param    array     $response    The full API response.
     * @return   array                  Parsed content array.
     */
    private function parse_generated_content( $content, $response ) {
        // Try to parse JSON response
        $parsed = json_decode( $content, true );
        
        if ( json_last_error() === JSON_ERROR_NONE && is_array( $parsed ) ) {
            return array(
                'title' => isset( $parsed['title'] ) ? $parsed['title'] : '',
                'content' => isset( $parsed['content'] ) ? $parsed['content'] : '',
                'meta_description' => isset( $parsed['meta_description'] ) ? $parsed['meta_description'] : '',
                'slug' => isset( $parsed['slug'] ) ? $parsed['slug'] : '',
                'excerpt' => isset( $parsed['excerpt'] ) ? $parsed['excerpt'] : '',
                'focus_keyword' => isset( $parsed['focus_keyword'] ) ? $parsed['focus_keyword'] : '',
                'secondary_keywords' => isset( $parsed['secondary_keywords'] ) ? $parsed['secondary_keywords'] : array(),
                'tags' => isset( $parsed['tags'] ) ? $parsed['tags'] : array(),
                'image_prompt' => isset( $parsed['image_prompt'] ) ? $parsed['image_prompt'] : '',
                'usage' => isset( $response['usage'] ) ? $response['usage'] : array(),
                'model' => isset( $response['model'] ) ? $response['model'] : '',
                'provider' => 'anthropic',
            );
        }

        // Fallback for plain text response
        return array(
            'title' => $this->extract_title_from_content( $content ),
            'content' => $content,
            'meta_description' => wp_trim_words( strip_tags( $content ), 25 ),
            'slug' => sanitize_title( $this->extract_title_from_content( $content ) ),
            'excerpt' => wp_trim_words( strip_tags( $content ), 25 ),
            'focus_keyword' => '',
            'secondary_keywords' => array(),
            'tags' => array(),
            'image_prompt' => '',
            'usage' => isset( $response['usage'] ) ? $response['usage'] : array(),
            'model' => isset( $response['model'] ) ? $response['model'] : '',
            'provider' => 'anthropic',
        );
    }

    /**
     * Extract title from content.
     *
     * @since    1.0.0
     * @param    string    $content    The content.
     * @return   string                The extracted title.
     */
    private function extract_title_from_content( $content ) {
        if ( preg_match( '/<h1[^>]*>(.*?)<\/h1>/i', $content, $matches ) ) {
            return strip_tags( $matches[1] );
        }

        $lines = explode( "\n", strip_tags( $content ) );
        $first_line = trim( $lines[0] );
        
        if ( strlen( $first_line ) > 10 && strlen( $first_line ) < 100 ) {
            return $first_line;
        }

        return __( 'Generated Article', 'ai-content-studio' );
    }
}