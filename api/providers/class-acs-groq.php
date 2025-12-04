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
            'max_tokens' => 4096, // Increased from 2048 to allow longer content
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

        $prompt .= "STRICT REQUIREMENTS: Output a single valid, compact JSON object (no pretty-printing, no newlines in string values) with the fields: title, meta_description, slug, content, excerpt, focus_keyword, secondary_keywords (array), tags (array), image_prompts (array of {prompt, alt}), internal_links (array of {anchor_text, target}).\n";
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
        // Log raw response for debugging
        error_log( '[ACS][JSON_PARSE] Raw response length: ' . strlen( $content ) );
        error_log( '[ACS][JSON_PARSE] Raw response start: ' . substr( $content, 0, 100 ) );
        
        // Handle markdown code blocks - extract JSON from ```json ... ``` or ``` ... ``` blocks
        // Also handle cases where closing ``` might be missing
        if ( preg_match('/```json\s*\n?(.*?)(?:\n?```|$)/s', $content, $matches) ) {
            $content = trim( $matches[1] );
            error_log( '[ACS][JSON_PARSE] Extracted from ```json markdown' );
            // Fix pretty-printed JSON with newlines in string values
            $content = $this->fix_pretty_printed_json( $content );
            error_log( '[ACS][JSON_PARSE] Fixed pretty-printed JSON' );
        } else if ( preg_match('/```\s*\n?(.*?)(?:\n?```|$)/s', $content, $matches) ) {
            // Try generic code block extraction (handles both closed and unclosed blocks)
            $content = trim( $matches[1] );
            error_log( '[ACS][JSON_PARSE] Extracted from ``` code block' );
            // Fix pretty-printed JSON with newlines in string values
            $content = $this->fix_pretty_printed_json( $content );
            error_log( '[ACS][JSON_PARSE] Fixed pretty-printed JSON' );
        }
        
        // Try to parse JSON response
        $parsed = json_decode( $content, true );
        $json_error = json_last_error();
        
        // Check for truncated JSON
        if ( $json_error !== JSON_ERROR_NONE ) {
            error_log( '[ACS][JSON_PARSE] JSON decode error: ' . json_last_error_msg() );
            error_log( '[ACS][JSON_PARSE] Response structure validation failed. Raw response: ' . substr( $content, 0, 500 ) );
            
            // Attempt to fix truncated JSON by adding closing braces
            if ( $json_error === JSON_ERROR_SYNTAX || strpos( json_last_error_msg(), 'unexpected end' ) !== false ) {
                error_log( '[ACS][JSON_PARSE] Attempting to fix truncated JSON...' );
                $fixed_content = $this->attempt_json_fix( $content );
                if ( $fixed_content !== $content ) {
                    $parsed = json_decode( $fixed_content, true );
                    if ( json_last_error() === JSON_ERROR_NONE && is_array( $parsed ) ) {
                        error_log( '[ACS][JSON_PARSE] Successfully fixed truncated JSON' );
                    }
                }
            }
        }
        
        if ( json_last_error() === JSON_ERROR_NONE && is_array( $parsed ) ) {
            // Valid JSON response
            error_log( '[ACS][JSON_PARSE] Successfully parsed JSON with keys: ' . implode( ', ', array_keys( $parsed ) ) );
            $result = array(
                'title' => isset( $parsed['title'] ) ? $parsed['title'] : '',
                'content' => isset( $parsed['content'] ) ? $parsed['content'] : '',
                'meta_description' => isset( $parsed['meta_description'] ) ? $parsed['meta_description'] : '',
                'slug' => isset( $parsed['slug'] ) ? $parsed['slug'] : '',
                'excerpt' => isset( $parsed['excerpt'] ) ? $parsed['excerpt'] : '',
                'focus_keyword' => isset( $parsed['focus_keyword'] ) ? $parsed['focus_keyword'] : '',
                'secondary_keywords' => isset( $parsed['secondary_keywords'] ) ? $parsed['secondary_keywords'] : array(),
                'tags' => isset( $parsed['tags'] ) ? $parsed['tags'] : array(),
                'image_prompts' => isset( $parsed['image_prompts'] ) ? $parsed['image_prompts'] : ( isset( $parsed['image_prompt'] ) ? array( $parsed['image_prompt'] ) : array() ),
                'internal_links' => isset( $parsed['internal_links'] ) ? $parsed['internal_links'] : array(),
                'outbound_links' => isset( $parsed['outbound_links'] ) ? $parsed['outbound_links'] : array(),
                'usage' => isset( $response['usage'] ) ? $response['usage'] : array(),
                'model' => isset( $response['model'] ) ? $response['model'] : '',
                'provider' => 'groq',
            );
            
            // Validate required fields
            $required_fields = array( 'title', 'content', 'meta_description' );
            $missing_fields = array();
            foreach ( $required_fields as $field ) {
                if ( empty( $result[$field] ) ) {
                    $missing_fields[] = $field;
                }
            }
            
            if ( ! empty( $missing_fields ) ) {
                error_log( '[ACS][PARSE] Response missing required fields: ' . implode( ', ', $missing_fields ) );
            }
            
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
     * Attempt to fix truncated JSON by adding missing closing braces.
     *
     * @since    1.0.0
     * @param    string    $content    The potentially truncated JSON.
     * @return   string                The fixed JSON or original if unfixable.
     */
    private function attempt_json_fix( $content ) {
        // First, clean up control characters that might cause JSON parsing issues
        // Remove problematic control characters but preserve valid JSON whitespace (tab, newline, carriage return)
        $content = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $content);
        
        // Try decoding after control character cleanup
        $test_decode = json_decode( $content, true );
        if ( json_last_error() === JSON_ERROR_NONE ) {
            return $content;
        }
        
        // Count opening and closing braces/brackets
        $open_braces = substr_count( $content, '{' );
        $close_braces = substr_count( $content, '}' );
        $open_brackets = substr_count( $content, '[' );
        $close_brackets = substr_count( $content, ']' );
        
        // Check if we're inside a string value
        $in_string = false;
        $last_char = '';
        for ( $i = strlen( $content ) - 1; $i >= 0; $i-- ) {
            $char = $content[$i];
            if ( $char === '"' && $last_char !== '\\' ) {
                $in_string = true;
                break;
            }
            if ( ! ctype_space( $char ) ) {
                $last_char = $char;
            }
        }
        
        // If truncated inside a string, close it
        if ( $in_string ) {
            $content .= '"';
        }
        
        // Close any open arrays
        while ( $open_brackets > $close_brackets ) {
            $content .= ']';
            $close_brackets++;
        }
        
        // Close any open objects
        while ( $open_braces > $close_braces ) {
            $content .= '}';
            $close_braces++;
        }
        
        return $content;
    }

    /**
     * Fix pretty-printed JSON with newlines in string values.
     * 
     * @since    1.0.0
     * @param    string    $json    JSON string that may have unescaped newlines.
     * @return   string             Fixed JSON string.
     */
    private function fix_pretty_printed_json( $json ) {
        // Strategy: Parse character by character, tracking if we're inside a string
        // If inside a string, escape any unescaped newlines and tabs
        $result = '';
        $in_string = false;
        $escape_next = false;
        $len = strlen( $json );
        
        for ( $i = 0; $i < $len; $i++ ) {
            $char = $json[$i];
            
            if ( $escape_next ) {
                $result .= $char;
                $escape_next = false;
                continue;
            }
            
            if ( $char === '\\' ) {
                $result .= $char;
                $escape_next = true;
                continue;
            }
            
            if ( $char === '"' ) {
                $in_string = ! $in_string;
                $result .= $char;
                continue;
            }
            
            // If we're inside a string and encounter a newline or tab, escape it
            if ( $in_string ) {
                if ( $char === "\n" ) {
                    $result .= '\\n';
                    continue;
                }
                if ( $char === "\r" ) {
                    $result .= '\\r';
                    continue;
                }
                if ( $char === "\t" ) {
                    $result .= '\\t';
                    continue;
                }
            }
            
            $result .= $char;
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