<?php
/**
 * Groq AI provider implementation
 *
 * @link       https://lancedesk.com
 * @since      1.0.0
 *
 * @package    ACS
 * @subpackage ACS/api/providers
 */

/**
 * Groq AI provider class.
 *
 * @since      1.0.0
 * @package    ACS
 * @subpackage ACS/api/providers
 * @author     LanceDesk <support@lancedesk.com>
 */
class ACS_Groq extends ACS_AI_Provider_Base {

    /**
     * Constructor.
     *
     * @since    1.0.0
     * @param    string    $api_key    The Groq API key.
     */
    public function __construct( $api_key = '' ) {
        parent::__construct( $api_key );
        $this->provider_name = 'groq';
        $this->api_base_url = 'https://api.groq.com/openai/v1/';
    }

    /**
     * Authenticate with Groq API.
     *
     * @since    1.0.0
     * @param    string    $api_key    The API key.
     * @return   bool                  True if authentication successful.
     */
    public function authenticate( $api_key ) {
        $this->api_key = $api_key;
        
        // Test API key with a simple request
        $response = $this->make_request( 'models', array(), 'GET' );
        
        return ! is_wp_error( $response );
    }

    /**
     * Generate content using Groq API.
     *
     * @since    1.0.0
     * @param    string    $prompt     The prompt text.
     * @param    array     $options    Additional options.
     * @return   array|WP_Error        The generated content or error.
     */
    public function generate_content( $prompt, $options = array() ) {
        $defaults = array(
            'model' => 'llama-3.3-70b-versatile',
            'max_tokens' => 2048,
            'temperature' => 0.7,
            'top_p' => 1,
            'frequency_penalty' => 0,
            'presence_penalty' => 0,
        );

        $options = wp_parse_args( $options, $defaults );

        $messages = array(
            array(
                'role' => 'system',
                'content' => $this->get_system_prompt( $options ),
            ),
            array(
                'role' => 'user',
                'content' => $prompt,
            ),
        );

        $data = array(
            'model' => $options['model'],
            'messages' => $messages,
            'max_tokens' => $options['max_tokens'],
            'temperature' => $options['temperature'],
            'top_p' => $options['top_p'],
            'frequency_penalty' => $options['frequency_penalty'],
            'presence_penalty' => $options['presence_penalty'],
            'stream' => false,
        );

        $response = $this->make_request( 'chat/completions', $data );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        // Extract content from response
        if ( ! isset( $response['choices'][0]['message']['content'] ) ) {
            return new WP_Error( 'invalid_response', __( 'Invalid response from Groq API.', 'ai-content-studio' ) );
        }

        $content = $response['choices'][0]['message']['content'];
        
        // Parse structured content
        return $this->parse_generated_content( $content, $response );
    }

    /**
     * Generate an image (Groq doesn't support image generation, so return error).
     *
     * @since    1.0.0
     * @param    string    $prompt     The image prompt.
     * @param    array     $options    Additional options.
     * @return   WP_Error              Error indicating no image generation support.
     */
    public function generate_image( $prompt, $options = array() ) {
        return new WP_Error( 
            'not_supported', 
            __( 'Image generation is not supported by Groq. Please configure an alternative provider for images.', 'ai-content-studio' ) 
        );
    }

    /**
     * Get available models for Groq.
     *
     * @since    1.0.0
     * @return   array    Array of model names.
     */
    public function get_models() {
        return array(
            'llama-3.3-70b-versatile' => __( 'Llama 3.3 70B Versatile', 'ai-content-studio' ),
            'llama-3.1-70b-versatile' => __( 'Llama 3.1 70B Versatile', 'ai-content-studio' ),
            'mixtral-8x7b-32768' => __( 'Mixtral 8x7B', 'ai-content-studio' ),
            'gemma-2-9b-it' => __( 'Gemma 2 9B IT', 'ai-content-studio' ),
        );
    }

    /**
     * Calculate cost for Groq API usage.
     *
     * @since    1.0.0
     * @param    int       $tokens    Number of tokens.
     * @return   float               Cost in USD.
     */
    public function calculate_cost( $tokens ) {
        // Groq pricing (approximate, check current rates)
        $cost_per_token = array(
            'llama-3.3-70b-versatile' => 0.00000059, // $0.59 per 1M tokens
            'llama-3.1-70b-versatile' => 0.00000059,
            'mixtral-8x7b-32768' => 0.00000024, // $0.24 per 1M tokens
            'gemma-2-9b-it' => 0.00000020, // $0.20 per 1M tokens
        );

        $model = 'llama-3.3-70b-versatile'; // Default model
        $settings = get_option( 'acs_settings', array() );
        
        if ( isset( $settings['providers']['groq']['model'] ) ) {
            $model = $settings['providers']['groq']['model'];
        }

        $rate = isset( $cost_per_token[$model] ) ? $cost_per_token[$model] : $cost_per_token['llama-3.3-70b-versatile'];
        
        return $tokens * $rate;
    }

    /**
     * Check Groq rate limit status.
     *
     * @since    1.0.0
     * @return   array    Rate limit information.
     */
    public function check_rate_limit() {
        return $this->rate_limit;
    }

    /**
     * Get request headers for Groq API.
     *
     * @since    1.0.0
     * @return   array    The request headers.
     */
    protected function get_request_headers() {
        return array(
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json',
            'User-Agent' => 'AI-Content-Studio/' . ACS_VERSION,
        );
    }

    /**
     * Update rate limit from Groq response headers.
     *
     * @since    1.0.0
     * @param    array    $headers    The response headers.
     */
    protected function update_rate_limit_from_headers( $headers ) {
        if ( isset( $headers['x-ratelimit-limit-requests'] ) ) {
            $this->rate_limit['requests_per_minute'] = (int) $headers['x-ratelimit-limit-requests'];
        }
        
        if ( isset( $headers['x-ratelimit-remaining-requests'] ) ) {
            $this->rate_limit['requests_remaining'] = (int) $headers['x-ratelimit-remaining-requests'];
        }
        
        if ( isset( $headers['x-ratelimit-reset-requests'] ) ) {
            $this->rate_limit['reset_time'] = time() + (int) $headers['x-ratelimit-reset-requests'];
        }
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
        $humanization_level = isset( $content_settings['humanization_level'] ) ? $content_settings['humanization_level'] : 'high';

        $prompt = "You are an expert content writer and SEO specialist for {$niche} topics. ";
        $prompt .= "Voice: {$brand_voice}. Tone: {$tone}. ";

        if ( $humanization_level === 'high' ) {
            $prompt .= "Write naturally, prefer active voice, use short paragraphs and concrete examples. ";
        } else {
            $prompt .= "Write clearly and humanly. ";
        }

        $prompt .= "STRICT REQUIREMENTS: Output a single valid JSON object with the fields: title, meta_description, slug, content, excerpt, focus_keyword, secondary_keywords (array), tags (array), image_prompts (array of {prompt, alt}), internal_links (array of {anchor_text, target}).\n";
        $prompt .= "Readability: average sentence length <=20 words; no more than 15% sentences >25 words; use transition words in >=30% of sentences; avoid passive voice.\n";
        $prompt .= "SEO: focus_keyword must appear at the start of the title, in the first paragraph, and be distributed across the content (1-2% density). Provide at least 1 image prompt with alt text including the focus keyword; provide at least 2 suggested internal links. Do NOT wrap keywords in <b> or <strong> tags.\n";
        $prompt .= "HTML rules: Use semantic HTML (<h2>/<h3>, <p>, <ul>/<ol>). Return only JSON.\n";

        return $prompt;
    }

    /**
     * Parse generated content from Groq response.
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
            // Valid JSON response
            $result = array(
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
                'provider' => 'groq',
            );
        } else {
            // Fallback for plain text response
            $result = array(
                'title' => $this->extract_title_from_content( $content ),
                'content' => $content,
                'meta_description' => $this->generate_meta_description( $content ),
                'slug' => sanitize_title( $this->extract_title_from_content( $content ) ),
                'excerpt' => wp_trim_words( strip_tags( $content ), 25 ),
                'focus_keyword' => '',
                'secondary_keywords' => array(),
                'tags' => array(),
                'image_prompt' => '',
                'usage' => isset( $response['usage'] ) ? $response['usage'] : array(),
                'model' => isset( $response['model'] ) ? $response['model'] : '',
                'provider' => 'groq',
            );
        }

        return $result;
    }

    /**
     * Extract title from content if not provided.
     *
     * @since    1.0.0
     * @param    string    $content    The content.
     * @return   string                The extracted title.
     */
    private function extract_title_from_content( $content ) {
        // Look for H1 tag
        if ( preg_match( '/<h1[^>]*>(.*?)<\/h1>/i', $content, $matches ) ) {
            return strip_tags( $matches[1] );
        }

        // Look for first line as title
        $lines = explode( "\n", strip_tags( $content ) );
        $first_line = trim( $lines[0] );
        
        if ( strlen( $first_line ) > 10 && strlen( $first_line ) < 100 ) {
            return $first_line;
        }

        return __( 'Generated Article', 'ai-content-studio' );
    }

    /**
     * Generate meta description from content.
     *
     * @since    1.0.0
     * @param    string    $content    The content.
     * @return   string                The meta description.
     */
    private function generate_meta_description( $content ) {
        $clean_content = wp_strip_all_tags( $content );
        $description = wp_trim_words( $clean_content, 25 );
        
        if ( strlen( $description ) > 155 ) {
            $description = substr( $description, 0, 155 ) . '...';
        }

        return $description;
    }
}