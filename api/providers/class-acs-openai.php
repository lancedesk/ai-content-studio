<?php
/**
 * OpenAI provider implementation
 *
 * @link       https://lancedesk.com
 * @since      1.0.0
 *
 * @package    ACS
 * @subpackage ACS/api/providers
 */

/**
 * OpenAI provider class.
 *
 * @since      1.0.0
 * @package    ACS
 * @subpackage ACS/api/providers
 * @author     LanceDesk <support@lancedesk.com>
 */
class ACS_OpenAI extends ACS_AI_Provider_Base {

    /**
     * Constructor.
     *
     * @since    1.0.0
     * @param    string    $api_key    The OpenAI API key.
     */
    public function __construct( $api_key = '' ) {
        parent::__construct( $api_key );
        $this->provider_name = 'openai';
        $this->api_base_url = 'https://api.openai.com/v1/';
    }

    /**
     * Authenticate with OpenAI API.
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
     * Generate content using OpenAI API.
     *
     * @since    1.0.0
     * @param    string    $prompt     The prompt text.
     * @param    array     $options    Additional options.
     * @return   array|WP_Error        The generated content or error.
     */
    public function generate_content( $prompt, $options = array() ) {
        $defaults = array(
            'model' => 'gpt-4o-mini',
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
        );

        $response = $this->make_request( 'chat/completions', $data );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        // Extract content from response
        if ( ! isset( $response['choices'][0]['message']['content'] ) ) {
            return new WP_Error( 'invalid_response', __( 'Invalid response from OpenAI API.', 'ai-content-studio' ) );
        }

        $content = $response['choices'][0]['message']['content'];
        
        return $this->parse_generated_content( $content, $response );
    }

    /**
     * Generate an image using DALL-E.
     *
     * @since    1.0.0
     * @param    string    $prompt     The image prompt.
     * @param    array     $options    Additional options.
     * @return   array|WP_Error        The generated image data or error.
     */
    public function generate_image( $prompt, $options = array() ) {
        $defaults = array(
            'model' => 'dall-e-3',
            'size' => '1024x1024',
            'quality' => 'standard',
            'n' => 1,
        );

        $options = wp_parse_args( $options, $defaults );

        $data = array(
            'model' => $options['model'],
            'prompt' => $prompt,
            'size' => $options['size'],
            'quality' => $options['quality'],
            'n' => $options['n'],
        );

        $response = $this->make_request( 'images/generations', $data );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        if ( ! isset( $response['data'][0]['url'] ) ) {
            return new WP_Error( 'invalid_response', __( 'Invalid response from OpenAI image API.', 'ai-content-studio' ) );
        }

        return array(
            'url' => $response['data'][0]['url'],
            'revised_prompt' => isset( $response['data'][0]['revised_prompt'] ) ? $response['data'][0]['revised_prompt'] : $prompt,
            'provider' => 'openai',
            'model' => $options['model'],
        );
    }

    /**
     * Get available models for OpenAI.
     *
     * @since    1.0.0
     * @return   array    Array of model names.
     */
    public function get_models() {
        return array(
            'gpt-4o' => __( 'GPT-4o', 'ai-content-studio' ),
            'gpt-4o-mini' => __( 'GPT-4o Mini', 'ai-content-studio' ),
            'gpt-4-turbo' => __( 'GPT-4 Turbo', 'ai-content-studio' ),
            'gpt-3.5-turbo' => __( 'GPT-3.5 Turbo', 'ai-content-studio' ),
        );
    }

    /**
     * Calculate cost for OpenAI API usage.
     *
     * @since    1.0.0
     * @param    int       $tokens    Number of tokens.
     * @return   float               Cost in USD.
     */
    public function calculate_cost( $tokens ) {
        // OpenAI pricing (check current rates)
        $cost_per_token = array(
            'gpt-4o' => 0.000005, // $5 per 1M tokens (input)
            'gpt-4o-mini' => 0.00000015, // $0.15 per 1M tokens
            'gpt-4-turbo' => 0.00001, // $10 per 1M tokens
            'gpt-3.5-turbo' => 0.000001, // $1 per 1M tokens
        );

        $model = 'gpt-4o-mini'; // Default model
        $settings = get_option( 'acs_settings', array() );
        
        if ( isset( $settings['providers']['openai']['model'] ) ) {
            $model = $settings['providers']['openai']['model'];
        }

        $rate = isset( $cost_per_token[$model] ) ? $cost_per_token[$model] : $cost_per_token['gpt-4o-mini'];
        
        return $tokens * $rate;
    }

    /**
     * Check OpenAI rate limit status.
     *
     * @since    1.0.0
     * @return   array    Rate limit information.
     */
    public function check_rate_limit() {
        return $this->rate_limit;
    }

    /**
     * Get request headers for OpenAI API.
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
     * Update rate limit from OpenAI response headers.
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
            $this->rate_limit['reset_time'] = strtotime( $headers['x-ratelimit-reset-requests'] );
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
        $prompt .= "Writing voice: {$brand_voice}. Tone: {$tone}. ";

        if ( $humanization_level === 'high' ) {
            $prompt .= "Write naturally with varied sentence structure, short paragraphs, and concrete examples. Use contractions and active voice. ";
        } else {
            $prompt .= "Write in a clear, human tone and prefer active voice. ";
        }

        $prompt .= "STRICT REQUIREMENTS: ";
        $prompt .= "1) Output MUST be a valid JSON object (no surrounding text) with these fields:\n";
        $prompt .= "   - title: string (SEO title; MUST begin with the focus_keyword exactly)\n";
        $prompt .= "   - meta_description: string (<=155 characters; mention focus_keyword or synonym)\n";
        $prompt .= "   - slug: string (SEO-friendly slug)\n";
        $prompt .= "   - content: string (Full article HTML using <h2>/<h3> headings; the first paragraph MUST include the focus_keyword)\n";
        $prompt .= "   - excerpt: string (<=150 chars)\n";
        $prompt .= "   - focus_keyword: string (primary keyword)\n";
        $prompt .= "   - secondary_keywords: array of strings\n";
        $prompt .= "   - tags: array of strings\n";
        $prompt .= "   - image_prompts: array of objects [{prompt: string, alt: string}] (at least 1; preferred 2; each alt must include the focus_keyword or a close synonym)\n";
        $prompt .= "   - internal_links: array of objects [{anchor_text: string, target: string}] (provide at least 2 suggested internal links with anchor text)\n";

        $prompt .= "2) Readability: average sentence length should be <= 20 words; no more than 15% of sentences > 25 words. Use transition words in at least 30% of sentences (e.g., however, moreover, therefore). Avoid passive voice where possible.\n";
        $prompt .= "3) SEO density: target focus_keyword density 1-2% across the content; distribute the keyword evenly (first paragraph, some subheadings, and conclusion). Do NOT wrap keywords in <b> or <strong> tags.\n";
        $prompt .= "4) Images: include at least 1 suggested image prompt with an alt attribute including the focus keyword or a synonym. Provide image sizes (e.g., 1200x800) and composition suggestions.\n";
        $prompt .= "5) Internal links: suggest at least 2 internal links (anchor text + relative slug) to existing site-relevant pages.\n";
        $prompt .= "6) HTML: use clean semantic HTML for content (use <h2> and <h3>, paragraphs <p>, lists <ul>/<ol>). Do not include editor-only markup or Bold tags around keywords.\n";
        $prompt .= "7) Meta title: place the focus_keyword at the beginning of the title. Keep title length < 60 characters.\n";

        $prompt .= "RESPONSE FORMAT: Return only the JSON object. Do not prepend or append any explanation or markdown. Ensure all strings are properly escaped.\n";

        return $prompt;
    }

    /**
     * Parse generated content from OpenAI response.
     *
     * @since    1.0.0
     * @param    string    $content     The generated content.
     * @param    array     $response    The full API response.
     * @return   array                  Parsed content array.
     */
    private function parse_generated_content( $content, $response ) {
        // Try to extract JSON from the response
        $json_pattern = '/\{.*\}/s';
        if ( preg_match( $json_pattern, $content, $matches ) ) {
            $json_content = $matches[0];
            $parsed = json_decode( $json_content, true );
            
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
                    'provider' => 'openai',
                );
            }
        }

        // Fallback for plain text response
        return array(
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
            'provider' => 'openai',
        );
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

        // Look for markdown style heading
        if ( preg_match( '/^#\s+(.+)$/m', $content, $matches ) ) {
            return trim( $matches[1] );
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