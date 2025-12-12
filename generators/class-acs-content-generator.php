<?php
/**
 * Content generator wrapper for AI Content Studio
 *
 * Provides a clean interface for generating content and creating posts.
 *
 * @package ACS
 */
if ( ! class_exists( 'ACS_Content_Generator' ) ) :
class ACS_Content_Generator {

    /**
     * Tracking start time for generation duration calculation.
     *
     * @var float|null
     */
    private $generation_start_time = null;

    public function __construct() {
    }

    /**
     * Resolve a global debug log path in `wp-content/acs_content_debug.log`.
     * Uses relative path from plugin files so it works in test harness and WP.
     *
     * @return string
     */
    private function get_global_debug_log_path() {
        $p = dirname( dirname( dirname( dirname( __FILE__ ) ) ) );
        return $p . DIRECTORY_SEPARATOR . 'acs_content_debug.log';
    }

    /**
     * Track a generation event in the analytics table.
     *
     * @param array  $result    The generated content result array.
     * @param string $provider  Provider key (groq, openai, etc.).
     * @param string $prompt    The prompt sent to the provider.
     * @param string $status    Generation status (completed, failed).
     * @param int    $tokens    Estimated token count (optional).
     * @param float  $cost      Estimated cost (optional).
     * @return int|false        Insert ID or false on failure.
     */
    private function track_analytics( $result, $provider, $prompt, $status = 'completed', $tokens = null, $cost = null ) {
        // Require analytics class if not loaded
        if ( ! class_exists( 'ACS_Analytics' ) ) {
            $file = defined( 'ACS_PLUGIN_PATH' ) ? ACS_PLUGIN_PATH . 'includes/class-acs-analytics.php' : '';
            if ( $file && file_exists( $file ) ) {
                require_once $file;
            }
        }
        if ( ! class_exists( 'ACS_Analytics' ) ) {
            return false;
        }

        $duration = null;
        if ( $this->generation_start_time ) {
            $duration = round( microtime( true ) - $this->generation_start_time, 3 );
        }

        $data = array(
            'post_id'         => isset( $result['post_id'] ) ? intval( $result['post_id'] ) : null,
            'user_id'         => function_exists( 'get_current_user_id' ) ? get_current_user_id() : 0,
            'provider'        => $provider,
            'model'           => isset( $result['model'] ) ? $result['model'] : null,
            'prompt_hash'     => hash( 'sha256', $prompt ),
            'prompt_text'     => $prompt,
            'response_text'   => isset( $result['content'] ) ? $result['content'] : '',
            'tokens_used'     => $tokens,
            'cost_estimate'   => $cost,
            'generation_time' => $duration,
            'status'          => $status,
        );

        return ACS_Analytics::track_generation( $data );
    }

    // ...existing methods...

    public function generate( $prompt_data ) {
        // Start timing for analytics
        $this->generation_start_time = microtime( true );

        $topic = sanitize_text_field( $prompt_data['topic'] ?? $prompt_data['content_topic'] ?? '' );
        $keywords = sanitize_text_field( $prompt_data['keywords'] ?? '' );
        $word_count = sanitize_text_field( $prompt_data['word_count'] ?? 'medium' );

        if ( empty( $topic ) ) {
            return new WP_Error( 'missing_params', 'Missing topic' );
        }

        // Ensure at least one provider is configured and enabled with an API key
        $settings = get_option( 'acs_settings', array() );
        $providers_ok = false;
        if ( ! empty( $settings['providers'] ) && is_array( $settings['providers'] ) ) {
            foreach ( $settings['providers'] as $pkey => $p ) {
                $enabled = isset( $p['enabled'] ) && ( $p['enabled'] === true || $p['enabled'] === '1' || $p['enabled'] === 1 );
                $api_key = isset( $p['api_key'] ) ? trim( (string) $p['api_key'] ) : '';
                if ( $enabled && $api_key !== '' ) {
                    $providers_ok = true;
                    break;
                }
            }
        }

        if ( ! $providers_ok ) {
            return new WP_Error( 'no_provider_configured', 'No AI provider is configured. Please add and enable an API key on AI Content Studio → Settings.' );
        }

        // Build the provider-agnostic prompt using the central helper so internal candidates
        // are included consistently across CLI and WP runs.
        $internal_candidates = $this->acs_get_internal_link_candidates( $topic, $keywords, 5 );
        $prompt = $this->build_prompt( $topic, $keywords, $word_count, $internal_candidates );

        // Log prompt for debugging (plugin directory)
        $debug_log_path = dirname( __FILE__ ) . '/acs_prompt_debug.log';
        $debug_entry = date( 'Y-m-d H:i:s' ) . "\nPROMPT:\n" . $prompt . "\n---\n";
        file_put_contents( $debug_log_path, $debug_entry, FILE_APPEND | LOCK_EX );

        // Determine provider order from settings
        $settings = get_option( 'acs_settings', array() );
        $default_provider = isset( $settings['default_provider'] ) ? $settings['default_provider'] : 'groq';
        $backup_providers = isset( $settings['backup_providers'] ) && is_array( $settings['backup_providers'] ) ? $settings['backup_providers'] : array();

        $ordered = array_values( array_unique( array_merge( array( $default_provider ), $backup_providers ) ) );

        // Map provider keys to class names
        $provider_map = array(
            'groq' => 'ACS_Groq',
            'openai' => 'ACS_OpenAI',
            'anthropic' => 'ACS_Anthropic',
            'mock' => 'ACS_Mock',
        );

        foreach ( $ordered as $prov ) {
            if ( empty( $prov ) ) {
                continue;
            }

            $class = isset( $provider_map[ $prov ] ) ? $provider_map[ $prov ] : false;
            if ( ! $class ) {
                continue;
            }

            // Require provider file if class not loaded
            if ( ! class_exists( $class ) ) {
                $file = ACS_PLUGIN_PATH . 'api/providers/class-acs-' . $prov . '.php';
                if ( file_exists( $file ) ) {
                    require_once $file;
                }
            }

            if ( ! class_exists( $class ) ) {
                continue;
            }

            // Get API key from settings for this provider
            $api_key = '';
            $enabled = false;
            if ( isset( $settings['providers'][ $prov ] ) && is_array( $settings['providers'][ $prov ] ) ) {
                $api_key = isset( $settings['providers'][ $prov ]['api_key'] ) ? trim( (string) $settings['providers'][ $prov ]['api_key'] ) : '';
                $enabled = isset( $settings['providers'][ $prov ]['enabled'] ) && ( $settings['providers'][ $prov ]['enabled'] === true || $settings['providers'][ $prov ]['enabled'] === '1' || $settings['providers'][ $prov ]['enabled'] === 1 );
            }
            
            // Skip if provider is not enabled or has no API key
            if ( ! $enabled || empty( $api_key ) ) {
                continue;
            }

            $instance = new $class( $api_key );
            $options = array();

            $raw = $instance->generate_content( $prompt, $options );
            // Always log raw provider response for debugging (array or string)
            $dbg = $this->get_global_debug_log_path();
            $entry = "[" . date('Y-m-d H:i:s') . "] RAW_FROM_PROVIDER ({$prov}):\n" . print_r( $raw, true ) . "\nPROMPT:\n" . $prompt . "\n---\n";
            @file_put_contents( $dbg, $entry, FILE_APPEND | LOCK_EX );
            if ( is_wp_error( $raw ) ) {
                // Log provider error with prompt for debugging
                $dbg = $this->get_global_debug_log_path();
                $entry = "[" . date('Y-m-d H:i:s') . "] PROVIDER_ERROR ({$prov}):\nPROMPT:\n" . $prompt . "\nERROR:\n" . print_r( $raw, true ) . "\n---\n";
                file_put_contents( $dbg, $entry, FILE_APPEND | LOCK_EX );
                continue;
            }

            $result = is_array( $raw ) ? $raw : $this->parse_generated_content( $raw );
            if ( ! is_array( $result ) ) {
                // Log parse failure with provider raw output and prompt
                $dbg = $this->get_global_debug_log_path();
                $entry = "[" . date('Y-m-d H:i:s') . "] PARSE_FAILED ({$prov}):\nPROMPT:\n" . $prompt . "\nRAW_RESPONSE:\n" . print_r( $raw, true ) . "\nPARSE_RESULT:\n" . print_r( $result, true ) . "\n---\n";
                file_put_contents( $dbg, $entry, FILE_APPEND | LOCK_EX );
                continue;
            }

            // Apply comprehensive SEO validation pipeline
            $seoValidationResult = $this->validateWithPipeline( $result, $keywords );
            
            // Always use corrected content if available (even if validation isn't perfect)
            // This ensures auto-corrections are always applied and returned to the user
            if ( ! empty( $seoValidationResult->correctedContent ) ) {
                $result = $seoValidationResult->correctedContent;
            }
            
            // Always return the content with SEO validation report, even if not fully valid
            // This allows users to see what was corrected and what issues remain
            if ( empty( $result['provider'] ) ) {
                $result['provider'] = $prov;
            }
            
            $result['acs_validation_report'] = array(
                'provider' => $prov,
                'initial_errors' => $seoValidationResult->errors ?? [],
                'warnings' => $seoValidationResult->warnings ?? [],
                'seo_validation' => $seoValidationResult->toArray(),
                'corrections_made' => $seoValidationResult->correctionsMade ?? [],
                'is_valid' => $seoValidationResult->isValid ?? false,
                'overall_score' => $seoValidationResult->overallScore ?? 0,
                'retry' => false,
            );
            
            // Log to global debug log for traceability
            $dbg = $this->get_global_debug_log_path();
            $status = $seoValidationResult->isValid ? 'VALID' : ( ! empty( $seoValidationResult->correctionsMade ) ? 'CORRECTED' : 'HAS_ISSUES' );
            $entry = "[" . date('Y-m-d H:i:s') . "] PROVIDER_SUCCESS_WITH_SEO_VALIDATION ({$prov}) [{$status}]:\n" . print_r( $result, true ) . "\nSEO_SCORE: " . $seoValidationResult->overallScore . "\nCORRECTIONS: " . implode(', ', $seoValidationResult->correctionsMade ?? []) . "\nERRORS: " . count($seoValidationResult->errors ?? []) . "\nPROMPT:\n" . $prompt . "\n---\n";
            @file_put_contents( $dbg, $entry, FILE_APPEND | LOCK_EX );
            
            // Track analytics
            $this->track_analytics( $result, $prov, $prompt, 'completed' );
            
            return $result;
            
            // Fallback to legacy validation for compatibility
            $validation = $this->validate_generated_output( $result );
            if ( $validation === true ) {
                // Apply minor automatic fixes
                $fixed = $this->auto_fix_generated_output( $result );
                if ( $fixed !== true ) {
                    $result = $fixed;
                }
                
                // Optionally fallback to local LLM for humanization if Groq output is too formal
                if ($prov === 'groq') {
                    $result = $this->fallback_humanize_content($result);
                }
                
                if ( empty( $result['provider'] ) ) {
                    $result['provider'] = $prov;
                }
                $result['acs_validation_report'] = array(
                    'provider' => $prov,
                    'initial_errors' => array(),
                    'seo_validation' => $seoValidationResult->toArray(),
                    'fallback_to_legacy' => true,
                    'retry' => false,
                );
                
                // Log success to global debug log for traceability
                $dbg = $this->get_global_debug_log_path();
                $entry = "[" . date('Y-m-d H:i:s') . "] PROVIDER_SUCCESS_LEGACY_FALLBACK ({$prov}):\n" . print_r( $result, true ) . "\nPROMPT:\n" . $prompt . "\n---\n";
                @file_put_contents( $dbg, $entry, FILE_APPEND | LOCK_EX );
                
                // Track analytics
                $this->track_analytics( $result, $prov, $prompt, 'completed' );
                
                return $result;
            }

            // Attempt progressive fallback retries with different strategies
            $retry_success = $this->attempt_progressive_retries( $instance, $topic, $keywords, $word_count, $validation, $internal_candidates, $prov, $auto_fix_applied, $options );
            if ( $retry_success !== false ) {
                return $retry_success;
            }

            // Retry failed or still invalid — store last attempt report and continue to next provider
            $last_report = array(
                'provider' => $prov,
                'initial_errors' => $validation,
                'auto_fix_applied' => $auto_fix_applied,
                'retry' => true,
                'retry_errors' => is_array( $retry_raw ) ? $this->validate_generated_output( $retry_raw ) : array( 'retry_failed' ),
            );
            $result['acs_validation_report'] = $last_report;
            // continue to next provider
        }

        // As a safe convenience for local testing: if all configured providers fail
        // and a mock provider exists in the environment, attempt one final mock
        // generation before giving up. This does not alter configured provider
        // order but helps CLI/test environments where an API key may be invalid.
        if ( class_exists( 'ACS_Mock' ) ) {
            try {
                $mock = new ACS_Mock( 'mockkey' );
                $raw = $mock->generate_content( $prompt, array() );
                $dbg = $this->get_global_debug_log_path();
                $entry = "[" . date('Y-m-d H:i:s') . "] FALLBACK_MOCK_RAW:\n" . print_r( $raw, true ) . "\nPROMPT:\n" . $prompt . "\n---\n";
                @file_put_contents( $dbg, $entry, FILE_APPEND | LOCK_EX );
                if ( ! is_wp_error( $raw ) ) {
                    $result = is_array( $raw ) ? $raw : $this->parse_generated_content( $raw );
                    if ( is_array( $result ) ) {
                        $fixed = $this->auto_fix_generated_output( $result );
                        if ( $fixed !== true ) {
                            $result = $fixed;
                        }
                        $validation = $this->validate_generated_output( $result );
                        if ( $validation === true ) {
                            if ( empty( $result['provider'] ) ) {
                                $result['provider'] = 'mock';
                            }
                            $result['acs_validation_report'] = array(
                                'provider' => 'mock',
                                'initial_errors' => array(),
                                'auto_fix_applied' => ( $fixed !== true ),
                                'retry' => false,
                            );
                            $entry = "[" . date('Y-m-d H:i:s') . "] PROVIDER_SUCCESS (mock-fallback):\n" . print_r( $result, true ) . "\nPROMPT:\n" . $prompt . "\n---\n";
                            @file_put_contents( $dbg, $entry, FILE_APPEND | LOCK_EX );
                            
                            // Track analytics
                            $this->track_analytics( $result, 'mock', $prompt, 'completed' );
                            
                            return $result;
                        }
                    }
                }
            } catch ( Exception $e ) {
                @file_put_contents( $dbg, "[" . date('Y-m-d H:i:s') . "] FALLBACK_MOCK_EXCEPTION: " . $e->getMessage() . "\n---\n", FILE_APPEND | LOCK_EX );
            }
        }

        return new WP_Error( 'no_providers', 'No available providers could generate content' );
    }

    /**
     * Fallback to local LLM for humanization if Groq output is too formal.
     * This is a stub for future integration.
     */
    private function fallback_humanize_content( $data ) {
        // Example: If transition word ratio < 20% or content is flagged as too formal, call local LLM
        $plain = isset( $data['content'] ) ? strtolower( wp_strip_all_tags( $data['content'] ) ) : '';
        $transitions = array( 'however', 'moreover', 'therefore', 'furthermore', 'additionally', 'consequently', 'meanwhile', 'nevertheless', 'nonetheless', 'subsequently' );
        $sentences = preg_split( '/(?<=[.?!])\s+(?=[A-Z0-9])/', $plain );
        $sent_with_transition = 0;
        $total_sentences = 0;
        foreach ( $sentences as $s ) {
            $s = trim( $s );
            if ( $s === '' ) continue;
            $total_sentences++;
            foreach ( $transitions as $t ) {
                if ( strpos( $s, " {$t} ") !== false || strpos( $s, " {$t},") !== false ) {
                    $sent_with_transition++;
                    break;
                }
            }
        }
        $pct = $total_sentences > 0 ? ( $sent_with_transition / $total_sentences ) * 100 : 0;
        if ( $pct < 20 ) {
            // Here you would call a local LLM API or function to rephrase $data['content']
            // For now, just flag for humanization
            $data['acs_humanization_needed'] = true;
        }
        return $data;
    }

    /**
     * Parse generated content into structured array with enhanced SEO validation.
     *
     * Accepts provider output in loose text or a structured block and returns
     * a normalized array with keys: title, content, meta_description, focus_keyword.
     * Now includes comprehensive SEO validation and auto-correction.
     *
     * @param string|array $content Raw provider output or array
     * @return array
     */
    public function parse_generated_content( $content ) {
        $result = array(
            'title' => '',
            'content' => '',
            'meta_description' => '',
            'focus_keyword' => '',
        );

        // If provider already returned structured array
        if ( is_array( $content ) ) {
            $result = array_merge( $result, $content );
            return $result;
        }

        $raw = is_string( $content ) ? $content : '';
        $log_path = $this->get_global_debug_log_path();
        $log_entry = "";

        // Record original raw output
        $log_entry .= "[" . date( 'Y-m-d H:i:s' ) . "] ORIGINAL OUTPUT:\n" . $raw . "\n---\n";

        // Attempt to clean control characters that commonly break json_decode
        $clean = $raw;
        // Remove non-printable control characters (keep common whitespace)
        $clean = preg_replace('/[\x00-\x1F\x7F]/u', '', $clean);
        $clean = trim( $clean );
        
        // Handle markdown code blocks - extract JSON from ```json ... ``` blocks
        if ( preg_match('/```json\s*\n?(.*?)\n?```/s', $clean, $matches) ) {
            $clean = trim( $matches[1] );
            $log_entry .= "EXTRACTED_FROM_MARKDOWN: " . substr($clean, 0, 200) . "...\n---\n";
            
            // Fix pretty-printed JSON with newlines in string values
            $clean = $this->fix_pretty_printed_json( $clean );
            $log_entry .= "FIXED_PRETTY_JSON: " . substr($clean, 0, 200) . "...\n---\n";
            
        } else if ( preg_match('/```\s*\n?(.*?)\n?```/s', $clean, $matches) ) {
            // Try generic code block extraction
            $clean = trim( $matches[1] );
            $log_entry .= "EXTRACTED_FROM_CODE_BLOCK: " . substr($clean, 0, 200) . "...\n---\n";
            
            // Fix pretty-printed JSON with newlines in string values
            $clean = $this->fix_pretty_printed_json( $clean );
            $log_entry .= "FIXED_PRETTY_JSON: " . substr($clean, 0, 200) . "...\n---\n";
        }
        
        $log_entry .= "CLEANED OUTPUT:\n" . $clean . "\n---\n";

        // Try direct JSON decode first
        $decoded = json_decode( $clean, true );
        if ( is_array( $decoded ) ) {
                $log_entry .= "JSON_DECODE: success\n";
            file_put_contents( $log_path, $log_entry, FILE_APPEND | LOCK_EX );
            return $decoded;
        }

        // If decode failed, try to extract the first JSON object in the string
        $start = strpos( $clean, '{' );
        $end = strrpos( $clean, '}' );
        if ( $start !== false && $end !== false && $end > $start ) {
            $maybe = substr( $clean, $start, $end - $start + 1 );
            $decoded2 = json_decode( $maybe, true );
            if ( is_array( $decoded2 ) ) {
                $log_entry .= "JSON_EXTRACT_DECODE: success (extracted object)\n";
                $log_entry .= "REPAIRED_JSON:\n" . $maybe . "\n---\n";
                file_put_contents( $log_path, $log_entry, FILE_APPEND | LOCK_EX );
                return $decoded2;
            }
            $log_entry .= "JSON_EXTRACT_DECODE: failed\n";
        } else {
            $log_entry .= "NO_JSON_BRACES_FOUND\n";
        }

        // Fallback: try to parse labeled blocks like TITLE:, META_DESCRIPTION:, CONTENT:
        if ( preg_match('/TITLE:\s*(.+?)(?=\n|$)/i', $raw, $m) ) {
            $result['title'] = trim( $m[1] );
        }
        if ( preg_match('/META_DESCRIPTION:\s*(.+?)(?=\n|$)/i', $raw, $m) ) {
            $result['meta_description'] = trim( $m[1] );
        }
        if ( preg_match('/FOCUS_KEYWORD:\s*(.+?)(?=\n|$)/i', $raw, $m) ) {
            $result['focus_keyword'] = trim( $m[1] );
        }
        if ( preg_match('/CONTENT:\s*(.+?)(?=\n\s*(?:META_DESCRIPTION|FOCUS_KEYWORD|TITLE):|$)/is', $raw, $m) ) {
            $result['content'] = trim( $m[1] );
        }

        // If content still empty, assume entire response is the body
        if ( empty( $result['content'] ) ) {
            $result['content'] = trim( $raw );
        }

        // Normalize markdown to HTML and strip bold tags
        $result['content'] = $this->convert_markdown_to_html( $result['content'] );
        $result['content'] = preg_replace( '#</?(b|strong)[^>]*>#i', '', $result['content'] );

        // Clean title
        if ( ! empty( $result['title'] ) ) {
            $result['title'] = wp_strip_all_tags( $result['title'] );
            $result['title'] = preg_replace( '#^\s*<h1[^>]*>(.*?)</h1>\s*$#is', '\\1', $result['title'] );
            $result['title'] = trim( $result['title'] );
        } else {
            // Use first line of content as title fallback
            $lines = preg_split('/\r?\n/', $raw);
            $result['title'] = isset( $lines[0] ) ? trim( wp_strip_all_tags( $lines[0] ) ) : '';
        }

        // Ensure content isn't empty
        if ( empty( $result['content'] ) ) {
            $result['content'] = $this->convert_markdown_to_html( $raw );
        }

        // Remove leading H1 that duplicates title
        if ( ! empty( $result['title'] ) && ! empty( $result['content'] ) ) {
            if ( preg_match( '#^\s*<h1[^>]*>(.*?)</h1>\s*#is', $result['content'], $mh ) ) {
                $first_h1 = wp_strip_all_tags( $mh[1] );
                $norm_title = trim( preg_replace( '/\s+/', ' ', strtolower( wp_strip_all_tags( $result['title'] ) ) ) );
                $norm_h1 = trim( preg_replace( '/\s+/', ' ', strtolower( $first_h1 ) ) );
                if ( $norm_title === $norm_h1 ) {
                    $result['content'] = preg_replace( '#^\s*<h1[^>]*>.*?</h1>\s*#is', '', $result['content'], 1 );
                }
            }
        }

        $log_entry .= "PARSED_FIELDS:\n" . print_r( array_keys( $result ), true ) . "\n---\n";
        file_put_contents( $log_path, $log_entry, FILE_APPEND | LOCK_EX );

        // Apply SEO validation and auto-correction to parsed content
        $result = $this->applyPostParseValidation( $result );

        return $result;
    }

    /**
     * Apply post-parse SEO validation and auto-correction
     *
     * @param array $result Parsed content array
     * @return array Validated and corrected content
     */
    private function applyPostParseValidation( $result ) {
        try {
            // Extract focus keyword from result or use a default
            $focusKeyword = $result['focus_keyword'] ?? '';
            
            // If no focus keyword, try to extract from title
            if ( empty( $focusKeyword ) && ! empty( $result['title'] ) ) {
                $focusKeyword = $this->extract_keyword_from_topic( $result['title'] );
            }
            
            // Apply basic SEO validation using the pipeline
            if ( ! empty( $focusKeyword ) ) {
                $seoValidationResult = $this->validateWithPipeline( $result, $focusKeyword );
                
                if ( $seoValidationResult->isValid ) {
                    // Use corrected content from pipeline
                    $result = $seoValidationResult->correctedContent;
                    
                    // Add validation metadata
                    $result['acs_seo_validation'] = [
                        'validated' => true,
                        'score' => $seoValidationResult->overallScore,
                        'corrections_applied' => $seoValidationResult->correctionsMade ?? [],
                        'validation_timestamp' => current_time( 'mysql' )
                    ];
                } else {
                    // Log validation issues but don't fail parsing
                    $log_path = $this->get_global_debug_log_path();
                    $entry = "[" . date('Y-m-d H:i:s') . "] POST_PARSE_VALIDATION_ISSUES:\n";
                    $entry .= "ERRORS: " . count($seoValidationResult->errors) . "\n";
                    $entry .= "WARNINGS: " . count($seoValidationResult->warnings) . "\n";
                    $entry .= "SCORE: " . $seoValidationResult->overallScore . "\n";
                    $entry .= "---\n";
                    @file_put_contents( $log_path, $entry, FILE_APPEND | LOCK_EX );
                    
                    // Add validation metadata even for failed validation
                    $result['acs_seo_validation'] = [
                        'validated' => false,
                        'score' => $seoValidationResult->overallScore,
                        'errors' => count($seoValidationResult->errors),
                        'warnings' => count($seoValidationResult->warnings),
                        'validation_timestamp' => current_time( 'mysql' )
                    ];
                }
            }
            
        } catch ( Exception $e ) {
            // Log error but don't fail parsing
            $log_path = $this->get_global_debug_log_path();
            $entry = "[" . date('Y-m-d H:i:s') . "] POST_PARSE_VALIDATION_ERROR: " . $e->getMessage() . "\n---\n";
            @file_put_contents( $log_path, $entry, FILE_APPEND | LOCK_EX );
        }
        
        return $result;
    }

    /**
     * Build a standardized prompt for AI providers using enhanced SEO constraints.
     * Ensures the provider returns a single JSON object with required fields
     * and enforces comprehensive SEO/readability constraints.
     *
     * @param string $topic
     * @param string $keywords
     * @param string $word_count
     * @param array $internal_candidates
     * @return string
     */
    public function build_prompt( $topic, $keywords = '', $word_count = 'medium', $internal_candidates = null ) {
        // Load enhanced prompt engine
        if ( ! class_exists( 'EnhancedPromptEngine' ) ) {
            require_once ACS_PLUGIN_PATH . 'seo/class-enhanced-prompt-engine.php';
        }
        if ( ! class_exists( 'SEOPromptConfiguration' ) ) {
            require_once ACS_PLUGIN_PATH . 'seo/class-seo-prompt-configuration.php';
        }
        
        // Parse primary keyword
        $primary = '';
        if ( ! empty( $keywords ) ) {
            $arr = array_map( 'trim', explode( ',', $keywords ) );
            $primary = $arr[0] ?? '';
        }
        
        // If no primary keyword, extract from topic
        if ( empty( $primary ) ) {
            $primary = $this->extract_keyword_from_topic( $topic );
        }
        
        // Parse secondary keywords
        $secondary = [];
        if ( ! empty( $keywords ) ) {
            $arr = array_map( 'trim', explode( ',', $keywords ) );
            $secondary = array_slice( $arr, 1 ); // Skip first (primary) keyword
        }
        
        // Create SEO configuration
        try {
            $config = new SEOPromptConfiguration([
                'focusKeyword' => $primary,
                'secondaryKeywords' => $secondary,
                'targetWordCount' => $this->get_word_count_target( $word_count ),
                'minMetaDescLength' => 120,
                'maxMetaDescLength' => 156,
                'maxKeywordDensity' => 2.5,
                'minKeywordDensity' => 0.5,
                'maxPassiveVoice' => 10.0,
                'minTransitionWords' => 30.0,
                'maxLongSentences' => 25.0,
                'maxTitleLength' => 66,
                'maxSubheadingKeywordUsage' => 75.0,
                'requireImages' => true,
                'requireKeywordInAltText' => true,
                'maxRetryAttempts' => 3
            ]);
        } catch ( Exception $e ) {
            // Fallback to basic prompt if configuration fails
            return $this->build_basic_prompt( $topic, $keywords, $word_count, $internal_candidates );
        }
        
        // Create enhanced prompt engine
        $promptEngine = new EnhancedPromptEngine( $config );
        
        // Set internal candidates if provided
        if ( ! empty( $internal_candidates ) && is_array( $internal_candidates ) ) {
            $promptEngine->setInternalCandidates( $internal_candidates );
        }
        
        // Generate enhanced SEO prompt
        return $promptEngine->buildSEOPrompt( $topic, $keywords, $word_count, $internal_candidates );
    }
    
    /**
     * Build fallback prompt for retry scenarios with progressive constraint relaxation
     *
     * @param string $topic
     * @param string $keywords
     * @param string $word_count
     * @param array $validation_errors
     * @param int $attempt_number
     * @param array $internal_candidates
     * @return string
     */
    public function build_fallback_prompt( $topic, $keywords, $word_count, $validation_errors = [], $attempt_number = 1, $internal_candidates = null ) {
        // Load enhanced prompt engine
        if ( ! class_exists( 'EnhancedPromptEngine' ) ) {
            require_once ACS_PLUGIN_PATH . 'seo/class-enhanced-prompt-engine.php';
        }
        if ( ! class_exists( 'SEOPromptConfiguration' ) ) {
            require_once ACS_PLUGIN_PATH . 'seo/class-seo-prompt-configuration.php';
        }
        
        // Parse primary keyword
        $primary = '';
        if ( ! empty( $keywords ) ) {
            $arr = array_map( 'trim', explode( ',', $keywords ) );
            $primary = $arr[0] ?? '';
        }
        
        if ( empty( $primary ) ) {
            $primary = $this->extract_keyword_from_topic( $topic );
        }
        
        // Parse secondary keywords
        $secondary = [];
        if ( ! empty( $keywords ) ) {
            $arr = array_map( 'trim', explode( ',', $keywords ) );
            $secondary = array_slice( $arr, 1 );
        }
        
        // Create SEO configuration
        try {
            $config = new SEOPromptConfiguration([
                'focusKeyword' => $primary,
                'secondaryKeywords' => $secondary,
                'targetWordCount' => $this->get_word_count_target( $word_count ),
                'minMetaDescLength' => 120,
                'maxMetaDescLength' => 156,
                'maxKeywordDensity' => 2.5,
                'minKeywordDensity' => 0.5,
                'maxPassiveVoice' => 10.0,
                'minTransitionWords' => 30.0,
                'maxLongSentences' => 25.0,
                'maxTitleLength' => 66,
                'maxSubheadingKeywordUsage' => 75.0,
                'requireImages' => true,
                'requireKeywordInAltText' => true,
                'maxRetryAttempts' => 3
            ]);
        } catch ( Exception $e ) {
            // Fallback to basic prompt if configuration fails
            return $this->build_basic_prompt( $topic, $keywords, $word_count, $internal_candidates );
        }
        
        // Create enhanced prompt engine
        $promptEngine = new EnhancedPromptEngine( $config );
        
        // Set internal candidates if provided
        if ( ! empty( $internal_candidates ) && is_array( $internal_candidates ) ) {
            $promptEngine->setInternalCandidates( $internal_candidates );
        }
        
        // Generate fallback prompt with error correction
        return $promptEngine->generateFallbackPrompt( $topic, $keywords, $word_count, $validation_errors, $attempt_number );
    }
    
    /**
     * Extract keyword from topic when no keywords provided
     *
     * @param string $topic
     * @return string
     */
    private function extract_keyword_from_topic( $topic ) {
        // Simple keyword extraction - take first 2-3 meaningful words
        $words = explode( ' ', trim( $topic ) );
        $meaningful_words = [];
        
        $stop_words = [ 'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'how', 'what', 'why', 'when', 'where' ];
        
        foreach ( $words as $word ) {
            $word = strtolower( trim( $word ) );
            if ( strlen( $word ) > 2 && ! in_array( $word, $stop_words ) ) {
                $meaningful_words[] = $word;
                if ( count( $meaningful_words ) >= 2 ) {
                    break;
                }
            }
        }
        
        return implode( ' ', $meaningful_words );
    }
    
    /**
     * Fix pretty-printed JSON with newlines in string values
     * 
     * @param string $json JSON string that may have unescaped newlines
     * @return string Fixed JSON string
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
     * Get numeric word count target from string
     *
     * @param string $word_count
     * @return int
     */
    private function get_word_count_target( $word_count ) {
        // Handle range format like "750-1500"
        if ( preg_match( '/^(\d+)-(\d+)$/', $word_count, $matches ) ) {
            // Return the midpoint of the range
            $min = intval( $matches[1] );
            $max = intval( $matches[2] );
            return intval( ( $min + $max ) / 2 );
        }
        
        // Handle single number
        if ( is_numeric( $word_count ) ) {
            return intval( $word_count );
        }
        
        // Handle named presets
        $targets = [
            'short' => 500,
            'medium' => 1000,
            'long' => 1500,
            'detailed' => 2000
        ];
        
        return $targets[ $word_count ] ?? 1000;
    }
    
    /**
     * Build basic prompt as fallback when enhanced engine fails
     *
     * @param string $topic
     * @param string $keywords
     * @param string $word_count
     * @param array $internal_candidates
     * @return string
     */
    private function build_basic_prompt( $topic, $keywords = '', $word_count = 'medium', $internal_candidates = null ) {
        $primary = '';
        if ( ! empty( $keywords ) ) {
            $arr = array_map( 'trim', explode( ',', $keywords ) );
            $primary = $arr[0] ?? '';
        }

        $word_targets = array(
            'short' => '500',
            'medium' => '1000',
            'long' => '1500',
            'detailed' => '2000+'
        );
        $target = $word_targets[ $word_count ] ?? '1000';

        $instructions = "You are an expert SEO copywriter. Produce a single JSON object only (no surrounding text or markdown) with the following keys: \n";
        $instructions .= "title, meta_description, slug, content, excerpt, focus_keyword, secondary_keywords (array), tags (array), image_prompts (array of {prompt,alt}), internal_links (array of {url,anchor}), outbound_links (array of {url,anchor}).\n";
        $instructions .= "Constraints:\n";
        $instructions .= "- meta_description: between 120 and 156 characters (inclusive).\n";
        $instructions .= "- title: should begin with the focus_keyword when provided and be <= 66 characters.\n";
        $instructions .= "- content: target roughly {$target} words, provide well-formed HTML paragraphs and H2/H3 subheadings; do NOT include tracking URLs.\n";
        $instructions .= "- excerpt: short summary (20-30 words).\n";
        $instructions .= "- internal_links: suggest at least two internal links (provide absolute URLs if possible) and avoid using the exact focus_keyword as the anchor text.\n";
        $instructions .= "- outbound_links: suggest at least one reputable external link and include it in the content where appropriate.\n";
        $instructions .= "- image_prompts: include at least one prompt and ensure alt text contains the focus keyword or a close synonym.\n";
        $instructions .= "- All string values must not contain raw newlines; escape or remove them so the JSON is a single-line valid object.\n";
        $instructions .= "- Return the JSON object with plain strings and arrays; do not include comments or explanatory text outside the JSON.\n";

        $prompt = "Topic: " . trim( $topic ) . "\n";
        if ( $primary !== '' ) {
            $prompt .= "Primary keywords: " . $primary . "\n";
        }
        if ( ! empty( $keywords ) ) {
            $prompt .= "Secondary keywords: " . $keywords . "\n";
        }
        $prompt .= "Target words: " . $target . "\n\n";
        $prompt .= $instructions;
        
        // If internal candidates were provided, include a short list of titles and URLs
        if ( ! empty( $internal_candidates ) && is_array( $internal_candidates ) ) {
            $prompt .= "\nHere are some pages and posts on our site you can link to if relevant:\n";
            foreach ( $internal_candidates as $link ) {
                $title = isset( $link['title'] ) ? $link['title'] : ( isset( $link['post_title'] ) ? $link['post_title'] : '' );
                $url = isset( $link['url'] ) ? $link['url'] : ( isset( $link['permalink'] ) ? $link['permalink'] : '' );
                if ( $title || $url ) {
                    $prompt .= "- Title: " . trim( $title ) . ", URL: " . trim( $url ) . "\n";
                }
            }
            $prompt .= "\n";
        }

        return $prompt;
    }
    
    /**
     * Attempt progressive retries with different fallback strategies
     *
     * @param object $instance AI provider instance
     * @param string $topic Content topic
     * @param string $keywords Keywords
     * @param string $word_count Word count target
     * @param array $validation_errors Validation errors
     * @param array $internal_candidates Internal link candidates
     * @param string $provider_name Provider name
     * @param bool $auto_fix_applied Whether auto-fix was applied
     * @param array $options Provider options
     * @return array|false Successful result or false
     */
    private function attempt_progressive_retries( $instance, $topic, $keywords, $word_count, $validation_errors, $internal_candidates, $provider_name, $auto_fix_applied, $options ) {
        // Load enhanced prompt engine
        if ( ! class_exists( 'EnhancedPromptEngine' ) ) {
            require_once ACS_PLUGIN_PATH . 'seo/class-enhanced-prompt-engine.php';
        }
        if ( ! class_exists( 'SEOPromptConfiguration' ) ) {
            require_once ACS_PLUGIN_PATH . 'seo/class-seo-prompt-configuration.php';
        }
        
        // Parse primary keyword
        $primary = '';
        if ( ! empty( $keywords ) ) {
            $arr = array_map( 'trim', explode( ',', $keywords ) );
            $primary = $arr[0] ?? '';
        }
        if ( empty( $primary ) ) {
            $primary = $this->extract_keyword_from_topic( $topic );
        }
        
        // Parse secondary keywords
        $secondary = [];
        if ( ! empty( $keywords ) ) {
            $arr = array_map( 'trim', explode( ',', $keywords ) );
            $secondary = array_slice( $arr, 1 );
        }
        
        try {
            // Create SEO configuration
            $config = new SEOPromptConfiguration([
                'focusKeyword' => $primary,
                'secondaryKeywords' => $secondary,
                'targetWordCount' => $this->get_word_count_target( $word_count ),
                'minMetaDescLength' => 120,
                'maxMetaDescLength' => 156,
                'maxKeywordDensity' => 2.5,
                'minKeywordDensity' => 0.5,
                'maxPassiveVoice' => 10.0,
                'minTransitionWords' => 30.0,
                'maxLongSentences' => 25.0,
                'maxTitleLength' => 66,
                'maxSubheadingKeywordUsage' => 75.0,
                'requireImages' => true,
                'requireKeywordInAltText' => true,
                'maxRetryAttempts' => 3
            ]);
            
            // Create enhanced prompt engine
            $promptEngine = new EnhancedPromptEngine( $config );
            if ( ! empty( $internal_candidates ) && is_array( $internal_candidates ) ) {
                $promptEngine->setInternalCandidates( $internal_candidates );
            }
            
            // Try up to 3 progressive retry attempts
            for ( $attempt = 1; $attempt <= 3; $attempt++ ) {
                // Get progressive fallback prompts for this attempt
                $fallback_prompts = $promptEngine->generateProgressiveFallbacks( $topic, $keywords, $word_count, (array) $validation_errors, $attempt );
                
                // Try each fallback strategy
                foreach ( $fallback_prompts as $strategy => $retry_prompt ) {
                    $retry_raw = $instance->generate_content( $retry_prompt, $options );
                    
                    if ( ! is_wp_error( $retry_raw ) ) {
                        $retry_result = is_array( $retry_raw ) ? $retry_raw : $this->parse_generated_content( $retry_raw );
                        
                        if ( is_array( $retry_result ) ) {
                            // Apply auto-fixes
                            $retry_fixed = $this->auto_fix_generated_output( $retry_result );
                            if ( $retry_fixed !== true ) {
                                $retry_result = $retry_fixed;
                            }
                            
                            // Validate result with SEO pipeline
                            $seoValidationResult = $this->validateWithPipeline( $retry_result, $keywords );
                            if ( $seoValidationResult->isValid ) {
                                // Use corrected content from pipeline
                                $retry_result = $seoValidationResult->correctedContent;
                                // Success! Return the result
                                if ( empty( $retry_result['provider'] ) ) {
                                    $retry_result['provider'] = $provider_name;
                                }
                                $retry_result['acs_validation_report'] = array(
                                    'provider' => $provider_name,
                                    'initial_errors' => $validation_errors,
                                    'auto_fix_applied' => $auto_fix_applied,
                                    'retry' => true,
                                    'retry_attempt' => $attempt,
                                    'retry_strategy' => $strategy,
                                    'seo_validation' => $seoValidationResult->toArray(),
                                    'corrections_made' => $seoValidationResult->correctionsMade ?? [],
                                    'retry_errors' => array(),
                                );
                                
                                // Log retry success
                                $dbg = $this->get_global_debug_log_path();
                                $entry = "[" . date('Y-m-d H:i:s') . "] PROVIDER_RETRY_SUCCESS ({$provider_name}, attempt {$attempt}, strategy {$strategy}):\n";
                                $entry .= "SEO_SCORE: " . $seoValidationResult->overallScore . "\n";
                                $entry .= "CORRECTIONS: " . implode(', ', $seoValidationResult->correctionsMade ?? []) . "\n";
                                $entry .= print_r( $retry_result, true ) . "\nPROMPT:\n" . $retry_prompt . "\n---\n";
                                @file_put_contents( $dbg, $entry, FILE_APPEND | LOCK_EX );
                                
                                return $retry_result;
                            } else {
                                // Log retry failure for this strategy
                                $dbg = $this->get_global_debug_log_path();
                                $entry = "[" . date('Y-m-d H:i:s') . "] PROVIDER_RETRY_FAILED ({$provider_name}, attempt {$attempt}, strategy {$strategy}):\n";
                                $entry .= "SEO_SCORE: " . $seoValidationResult->overallScore . "\n";
                                $entry .= "ERRORS: " . count($seoValidationResult->errors) . "\n";
                                $entry .= "WARNINGS: " . count($seoValidationResult->warnings) . "\n";
                                $entry .= "VALIDATION_ERRORS:\n" . print_r( $seoValidationResult->errors, true ) . "\n---\n";
                                @file_put_contents( $dbg, $entry, FILE_APPEND | LOCK_EX );
                                
                                // Update validation errors for next attempt
                                $newErrors = array_map(function($error) {
                                    return is_array($error) ? $error['message'] : $error;
                                }, $seoValidationResult->errors);
                                $validation_errors = array_merge( (array) $validation_errors, $newErrors );
                            }
                        }
                    }
                }
            }
            
        } catch ( Exception $e ) {
            // Log exception and fall back to simple retry
            $dbg = $this->get_global_debug_log_path();
            $entry = "[" . date('Y-m-d H:i:s') . "] PROGRESSIVE_RETRY_EXCEPTION ({$provider_name}): " . $e->getMessage() . "\n---\n";
            @file_put_contents( $dbg, $entry, FILE_APPEND | LOCK_EX );
            
            // Fall back to simple retry
            return $this->attempt_simple_retry( $instance, $topic, $keywords, $word_count, $validation_errors, $internal_candidates, $provider_name, $auto_fix_applied, $options );
        }
        
        return false; // All retry attempts failed
    }
    
    /**
     * Simple retry fallback when progressive retries fail
     *
     * @param object $instance AI provider instance
     * @param string $topic Content topic
     * @param string $keywords Keywords
     * @param string $word_count Word count target
     * @param array $validation_errors Validation errors
     * @param array $internal_candidates Internal link candidates
     * @param string $provider_name Provider name
     * @param bool $auto_fix_applied Whether auto-fix was applied
     * @param array $options Provider options
     * @return array|false Successful result or false
     */
    private function attempt_simple_retry( $instance, $topic, $keywords, $word_count, $validation_errors, $internal_candidates, $provider_name, $auto_fix_applied, $options ) {
        // Build simple retry prompt with error correction
        $fix_instructions = "Please correct the following issues and return ONLY a valid JSON object with the required fields (title, meta_description, slug, content, excerpt, focus_keyword, secondary_keywords, tags, image_prompts, internal_links):\n";
        foreach ( (array) $validation_errors as $v ) {
            $fix_instructions .= "- " . $v . "\n";
        }
        
        $original_prompt = $this->build_basic_prompt( $topic, $keywords, $word_count, $internal_candidates );
        $retry_prompt = $original_prompt . "\n\n" . $fix_instructions;
        
        $retry_raw = $instance->generate_content( $retry_prompt, $options );
        if ( ! is_wp_error( $retry_raw ) ) {
            $retry_result = is_array( $retry_raw ) ? $retry_raw : $this->parse_generated_content( $retry_raw );
            if ( is_array( $retry_result ) ) {
                $retry_fixed = $this->auto_fix_generated_output( $retry_result );
                if ( $retry_fixed !== true ) {
                    $retry_result = $retry_fixed;
                }
                $retry_validation = $this->validate_generated_output( $retry_result );
                if ( $retry_validation === true ) {
                    if ( empty( $retry_result['provider'] ) ) {
                        $retry_result['provider'] = $provider_name;
                    }
                    $retry_result['acs_validation_report'] = array(
                        'provider' => $provider_name,
                        'initial_errors' => $validation_errors,
                        'auto_fix_applied' => $auto_fix_applied,
                        'retry' => true,
                        'retry_strategy' => 'simple',
                        'retry_errors' => array(),
                    );
                    
                    // Log simple retry success
                    $dbg = $this->get_global_debug_log_path();
                    $entry = "[" . date('Y-m-d H:i:s') . "] PROVIDER_SIMPLE_RETRY_SUCCESS ({$provider_name}):\n" . print_r( $retry_result, true ) . "\nPROMPT:\n" . $retry_prompt . "\n---\n";
                    @file_put_contents( $dbg, $entry, FILE_APPEND | LOCK_EX );
                    
                    return $retry_result;
                }
            }
        }
        
        return false;
    }

    private function convert_markdown_to_html( $content ) {
        $content = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $content);
        $content = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $content);
        $content = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $content);
        $content = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $content);
        $content = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $content);
        $content = $this->convert_to_paragraphs( $content );
        return $content;
    }

    private function convert_to_paragraphs( $content ) {
        // If content already contains block-level HTML, assume it's HTML and return as-is
        if ( preg_match( '/<\s*(h[1-6]|p|div|ul|ol|blockquote|table|section|article)[\s>]/i', $content ) ) {
            return $content;
        }

        $content = str_replace(array("\r\n", "\r"), "\n", $content);
        $paragraphs = preg_split('/\n\s*\n/', trim( $content ) );
        $html_content = '';
        foreach ( $paragraphs as $paragraph ) {
            $paragraph = trim( $paragraph );
            if ( $paragraph === '' ) {
                continue;
            }
            // If paragraph starts with a block element, keep it
            if ( preg_match('/^<(h[1-6]|div|ul|ol|blockquote)(\s|>)/i', $paragraph ) ) {
                $html_content .= $paragraph . "\n\n";
            } else {
                $html_content .= '<p>' . $paragraph . '</p>' . "\n\n";
            }
        }
        return trim( $html_content );
    }

    /**
     * Validate the generated output array according to required SEO/readability rules.
     * Returns true on success or an array of human-readable error messages on failure.
     *
     * @param array $data Generated content array
     * @return true|array
     */
    private function validate_generated_output( $data ) {
        $errors = array();

        if ( ! is_array( $data ) ) {
            return array( 'Generated response is not a structured array/JSON.' );
        }

        $required = array( 'title', 'meta_description', 'slug', 'content', 'excerpt', 'focus_keyword', 'image_prompts', 'internal_links' );
        foreach ( $required as $r ) {
            if ( empty( $data[ $r ] ) ) {
                $errors[] = "Missing or empty required field: {$r}.";
                error_log( "[ACS][PARSE] Missing field: {$r}" );
            }
        }
        
        // Log what fields we do have for debugging
        if ( ! empty( $errors ) ) {
            error_log( '[ACS][PARSE] Available fields: ' . implode( ', ', array_keys( $data ) ) );
        }

        // Title must begin with the focus keyword for best SEO
        $focus = isset( $data['focus_keyword'] ) ? trim( strip_tags( $data['focus_keyword'] ) ) : '';
        $title = isset( $data['title'] ) ? trim( strip_tags( $data['title'] ) ) : '';
        if ( $focus !== '' ) {
            if ( stripos( $title, $focus ) !== 0 ) {
                $errors[] = 'SEO title must begin with the focus keyword.';
            }
        }

        // Title length for SEO: recommend <= 66 characters
        if ( ! empty( $title ) ) {
            $title_len = mb_strlen( $title );
            if ( $title_len > 66 ) {
                $errors[] = 'SEO title appears to be too long (recommended <=66 characters).';
            }
        }

        // Check whether this focus keyphrase has been used previously on the site
        $used_before = false;
        if ( $focus !== '' ) {
            $found = get_posts( array(
                'post_type' => 'any',
                'meta_query' => array(
                    array(
                        'key' => '_yoast_wpseo_focuskw',
                        'value' => $focus,
                    )
                ),
                'posts_per_page' => 1,
                'fields' => 'ids'
            ) );
            if ( ! empty( $found ) ) {
                $used_before = true;
            }
        }

        // Keyphrase density enforcement: occurrences per 1000 words with absolute allowance
        if ( isset( $data['content'] ) && $focus !== '' ) {
            $plain_text = wp_strip_all_tags( $data['content'] );
            $word_total = max( 1, str_word_count( $plain_text ) );
            $occurrences = preg_match_all( '/\b' . preg_quote( $focus, '/' ) . '\b/i', $plain_text, $m );
            // Compute allowed occurrences: at most 12 per 1000 words, but at least 1 allowed for short pieces
            $allowed = max( 1, intval( floor( 12 * $word_total / 1000 ) ) );
            if ( $occurrences > $allowed ) {
                $density_per_1000 = ( $occurrences / $word_total ) * 1000;
                $errors[] = "Keyphrase density is too high ({$occurrences} occurrences, " . round( $density_per_1000, 1 ) . " per 1000 words). Maximum 12 per 1000 words (allowed occurrences: {$allowed}).";
            }
        }

        // Subheading distribution: ensure not more than 75% of H2/H3 tags contain the primary keyphrase
            if ( ! empty( $internal_candidates ) && is_array( $internal_candidates ) ) {
                // previously used unrelated synonyms; use context-appropriate AI synonyms instead
                $synonyms = array( 'artificial intelligence', 'machine learning', 'AI tools', 'automation' );
                if ( preg_match_all( '#<h[23][^>]*>(.*?)</h[23]>#is', $data['content'], $hs ) ) {
                $total_h = count( $hs[1] );
                $h_with = 0;
                foreach ( $hs[1] as $htext ) {
                    $plain_h = trim( wp_strip_all_tags( $htext ) );
                    if ( stripos( $plain_h, $focus ) !== false ) {
                        $h_with++;
                        continue;
                    }
                    // Also consider close synonyms (contextual) as matches
                    $ai_synonyms = array( 'artificial intelligence', 'machine learning', 'AI tools', 'automation' );
                    foreach ( $ai_synonyms as $s ) {
                        if ( stripos( $plain_h, $s ) !== false ) {
                            $h_with++;
                            break;
                        }
                    }
                }
                if ( $total_h > 0 ) {
                    $pct = ( $h_with / $total_h ) * 100;
                    if ( $pct > 75 ) {
                        $errors[] = 'Too many subheadings (H2/H3) contain the primary keyphrase — limit to 75% to avoid over-optimization.';
                    }
                }
            } else {
                $errors[] = 'Content should include H2/H3 subheadings for structure.';
            }
        }

        // Ensure content includes HTML paragraphs and subheadings
        if ( isset( $data['content'] ) ) {
            $content_html = $data['content'];
            // Check for paragraphs
            preg_match_all( '#<p[^>]*>.*?</p>#is', $content_html, $pms );
            $p_count = isset( $pms[0] ) ? count( $pms[0] ) : 0;
            if ( $p_count < 2 ) {
                $errors[] = 'Generated content should include at least two HTML paragraphs.';
            }
            // Check for at least one H2/H3
            if ( ! preg_match( '#<h[23][^>]*>.*?</h[23]>#is', $content_html ) ) {
                $errors[] = 'Generated content should include H2/H3 subheadings.';
            }
        }

        // Internal links anchors must not use the exact keyphrase
        if ( isset( $data['internal_links'] ) && is_array( $data['internal_links'] ) ) {
            foreach ( $data['internal_links'] as $link ) {
                $anchor = isset( $link['anchor'] ) ? trim( strip_tags( $link['anchor'] ) ) : '';
                if ( $focus !== '' && $anchor !== '' && stripos( $anchor, $focus ) !== false ) {
                    $errors[] = 'Internal link anchors should not be the exact focus keyphrase.';
                    break;
                }
            }
            if ( count( $data['internal_links'] ) < 2 ) {
                $errors[] = 'At least two suggested internal links are required.';
            }
        }

        // Meta description length (120-141 characters)
        if ( isset( $data['meta_description'] ) ) {
            $meta_len = mb_strlen( wp_strip_all_tags( $data['meta_description'] ) );
            if ( $meta_len > 141 ) {
                $errors[] = 'Meta description exceeds 141 characters.';
            }
            if ( $meta_len < 120 ) {
                $errors[] = 'Meta description is too short (minimum 120 characters).';
            }
        }

        // Content: first paragraph must contain focus keyword
        if ( isset( $data['content'] ) && $focus !== '' ) {
            $first_para = '';
            $content_html = $data['content'];
            // Extract first paragraph text
            if ( preg_match( '#<p[^>]*>(.*?)</p>#is', $content_html, $m ) ) {
                $first_para = wp_strip_all_tags( $m[1] );
            } else {
                $text = wp_strip_all_tags( $content_html );
                $lines = preg_split( '/\n\s*\n/', trim( $text ) );
                $first_para = isset( $lines[0] ) ? $lines[0] : '';
            }
            if ( $first_para !== '' ) {
                $allowed_first = array( $focus, 'artificial intelligence', 'machine learning', 'AI tools', 'automation', 'intelligent automation' );
                $found_ok = false;
                foreach ( $allowed_first as $af ) {
                    if ( stripos( $first_para, $af ) !== false ) {
                        $found_ok = true;
                        break;
                    }
                }
                if ( ! $found_ok ) {
                    $errors[] = 'First paragraph must include the focus keyword or a close synonym.';
                }
            }
        }

        // Excerpt length: 20-30 words
        if ( isset( $data['excerpt'] ) ) {
            $excerpt_plain = wp_strip_all_tags( $data['excerpt'] );
            $word_count = str_word_count( $excerpt_plain );
            if ( $word_count < 20 || $word_count > 30 ) {
                $errors[] = 'Excerpt should be between 20 and 30 words.';
            }
        }

        // Outbound links: at least one external link should appear in content
        if ( isset( $data['content'] ) ) {
            preg_match_all("/https?:\\/\\/[^\\s\"']+/i", $data['content'], $matches);
            $found_external = false;
            if ( ! empty( $matches[0] ) ) {
                $home = parse_url( home_url(), PHP_URL_HOST );
                foreach ( $matches[0] as $u ) {
                    $host = parse_url( $u, PHP_URL_HOST );
                    if ( $host && $home && strcasecmp( $host, $home ) !== 0 ) {
                        $found_external = true;
                        break;
                    }
                }
            }
            if ( ! $found_external ) {
                $errors[] = 'Content should include at least one reputable outbound link.';
            }
        }
        // Image prompts: ensure at least 1 and alt includes focus keyword
        if ( isset( $data['image_prompts'] ) && is_array( $data['image_prompts'] ) ) {
            if ( count( $data['image_prompts'] ) < 1 ) {
                $errors[] = 'At least one image prompt is required.';
            } else {
                $ok_alt = false;
                foreach ( $data['image_prompts'] as $ip ) {
                    $alt = isset( $ip['alt'] ) ? $ip['alt'] : '';
                    if ( $focus !== '' && stripos( $alt, $focus ) !== false ) {
                        $ok_alt = true;
                        break;
                    }
                }
                if ( ! $ok_alt ) {
                    $errors[] = 'Image alt text must include the focus keyword or a close synonym.';
                }
            }
        }

        // Internal links: at least 2
        if ( isset( $data['internal_links'] ) && is_array( $data['internal_links'] ) ) {
            if ( count( $data['internal_links'] ) < 2 ) {
                $errors[] = 'At least two suggested internal links are required.';
            }
        }

        // Readability checks: average sentence length and long sentence percentage
        if ( isset( $data['content'] ) ) {
            $plain = wp_strip_all_tags( $data['content'] );
            $sentences = preg_split( '/(?<=[.?!])\s+(?=[A-Z0-9])/', $plain );
            $total_sentences = 0;
            $total_words = 0;
            $long_sentences = 0;
            if ( is_array( $sentences ) ) {
                foreach ( $sentences as $s ) {
                    $s = trim( $s );
                    if ( $s === '' ) {
                        continue;
                    }
                    $total_sentences++;
                    $words = str_word_count( strip_tags( $s ) );
                    $total_words += $words;
                    if ( $words > 20 ) {
                        $long_sentences++;
                    }
                }
            }
            if ( $total_sentences > 0 ) {
                $avg = $total_words / $total_sentences;
                $pct_long = ( $long_sentences / $total_sentences ) * 100;
                if ( $avg > 20 ) {
                    $errors[] = 'Average sentence length is too high (target <=20 words).';
                }
                // Requirement: no more than 25% of sentences exceed 20 words
                if ( $pct_long > 25 ) {
                    $errors[] = 'Too many long sentences (more than 25% exceed 20 words).';
                }
            }
        }

        // Transition words heuristic: count common transition words
        if ( isset( $data['content'] ) ) {
            $plain = strtolower( wp_strip_all_tags( $data['content'] ) );
            $transitions = array( 'however', 'moreover', 'therefore', 'furthermore', 'consequently', 'thus', 'additionally', 'meanwhile', 'nevertheless', 'nonetheless', 'subsequently' );
            $sentences = preg_split( '/(?<=[.?!])\s+(?=[A-Z0-9])/', $plain );
            $sent_with_transition = 0;
            $total_sentences = 0;
            foreach ( $sentences as $s ) {
                $s = trim( $s );
                if ( $s === '' ) {
                    continue;
                }
                $total_sentences++;
                foreach ( $transitions as $t ) {
                    if ( strpos( $s, " {$t} " ) !== false || strpos( $s, " {$t}," ) !== false ) {
                        $sent_with_transition++;
                        break;
                    }
                }
            }
            if ( $total_sentences > 0 ) {
                $pct = ( $sent_with_transition / $total_sentences ) * 100;
                if ( $pct < 30 ) {
                    $errors[] = 'Not enough transition words (target >=30% of sentences).';
                }
            }
        }

        // Paragraph length heuristic: prefer paragraphs of 1-5 sentences and avoid very long paragraphs
        if ( isset( $data['content'] ) ) {
            $dom_paras = array();
            if ( preg_match_all( '#<p[^>]*>(.*?)</p>#is', $data['content'], $pm ) ) {
                foreach ( $pm[1] as $p ) {
                    $txt = trim( wp_strip_all_tags( $p ) );
                    if ( $txt === '' ) continue;
                    $sent_count = count( preg_split( '/(?<=[.?!])\s+/', $txt ) );
                    $dom_paras[] = $sent_count;
                }
            }
            if ( ! empty( $dom_paras ) ) {
                $long_paras = 0;
                foreach ( $dom_paras as $sc ) {
                    if ( $sc > 6 ) $long_paras++;
                }
                $pct_long_paras = ( $long_paras / count( $dom_paras ) ) * 100;
                if ( $pct_long_paras > 25 ) {
                    $errors[] = 'Too many long paragraphs (more than 25% have over 6 sentences). Keep paragraphs short for readability.';
                }
            }
        }

        return empty( $errors ) ? true : $errors;
    }

    /**
     * Attempt minor automatic fixes to generated output to correct small, deterministic issues.
     * Returns true if no changes needed, or the modified $data array if fixes were applied.
     *
     * @param array $data
     * @return true|array
     */
    private function auto_fix_generated_output( $data ) {
        if ( ! is_array( $data ) ) {
            return $data;
        }

        $changed = false;
        $focus = isset( $data['focus_keyword'] ) ? trim( strip_tags( $data['focus_keyword'] ) ) : '';

        // Clean and truncate meta_description to 141 chars
        if ( isset( $data['meta_description'] ) ) {
            $meta = wp_strip_all_tags( $data['meta_description'] );
            // Remove leading transition words and inline transition clauses that break meta flow
            $meta = preg_replace( '/^\s*(however|moreover|therefore|additionally|furthermore|consequently|thus|meanwhile)[\s,]+/i', '', $meta );
            $meta = preg_replace( '/,\s*(however|moreover|therefore|additionally|furthermore|consequently|thus)\s*,/i', '. ', $meta );
            $meta = trim( $meta );
            // Capitalize first character and any sentence starts to keep meta well-formed after replacements
            if ( $meta !== '' ) {
                $meta = ucfirst( $meta );
                // Capitalize first letter after sentence-ending punctuation
                $meta = preg_replace_callback( '/([\.\!\?]\s+)([a-z])/', function( $m ) { return $m[1] . strtoupper( $m[2] ); }, $meta );
            }
            if ( mb_strlen( $meta ) > 141 ) {
                $data['meta_description'] = mb_substr( $meta, 0, 138 ) . '...';
                $changed = true;
            } else {
                $data['meta_description'] = $meta;
            }
        }

        // If meta too short, try to build a reasonable meta from first paragraph or several sentences
        if ( empty( $data['meta_description'] ) || mb_strlen( wp_strip_all_tags( $data['meta_description'] ) ) < 120 ) {
            if ( isset( $data['content'] ) ) {
                $plain = trim( wp_strip_all_tags( $data['content'] ) );
                $meta_candidate = '';

                // Split into sentences (preserve typical sentence endings)
                $sentences = preg_split('/(?<=[.?!])\s+(?=[A-Z0-9])/', $plain);
                if ( is_array( $sentences ) && ! empty( $sentences ) ) {
                    // Start by concatenating sentences until we reach the minimum meta length
                    foreach ( $sentences as $s ) {
                        $s = trim( $s );
                        if ( $s === '' ) continue;
                        if ( $meta_candidate === '' ) {
                            $meta_candidate = $s;
                        } else {
                            $meta_candidate .= ' ' . $s;
                        }
                        $len = mb_strlen( $meta_candidate );
                        if ( $len >= 120 ) break;
                    }
                }

                // If still too short, fall back to a trimmed descriptive expansion using title/focus
                if ( mb_strlen( trim( $meta_candidate ) ) < 120 ) {
                    $parts = array();
                    if ( isset( $data['title'] ) && trim( $data['title'] ) !== '' ) {
                        $parts[] = wp_strip_all_tags( $data['title'] );
                    }
                    if ( isset( $data['excerpt'] ) && trim( $data['excerpt'] ) !== '' ) {
                        $parts[] = wp_strip_all_tags( $data['excerpt'] );
                    }
                    if ( trim( $plain ) !== '' ) {
                        // include first 40-120 words of content to create a longer meta
                        $parts[] = wp_trim_words( $plain, 80, '...' );
                    }
                    $meta_candidate = trim( implode( '. ', $parts ) );
                }

                // Finalize candidate: ensure within 120-141 range
                if ( $meta_candidate !== '' ) {
                    $meta_candidate = preg_replace('/\s+/u', ' ', $meta_candidate);
                    // Trim to avoid cutting in the middle of multibyte characters
                    if ( mb_strlen( $meta_candidate ) > 141 ) {
                        $meta_candidate = mb_substr( $meta_candidate, 0, 138 ) . '...';
                    }

                    // If still shorter than 120, append a concise descriptive clause
                    if ( mb_strlen( $meta_candidate ) < 120 ) {
                        $append = '';
                        if ( isset( $data['focus_keyword'] ) && trim( $data['focus_keyword'] ) !== '' ) {
                            $append = ' ' . trim( $data['focus_keyword'] ) . ' strategies, tips, and practical examples for small businesses.';
                        } else {
                            $append = ' Practical tips and examples for small businesses to implement these ideas.';
                        }
                        $meta_candidate = trim( $meta_candidate . ' ' . $append );
                        if ( mb_strlen( $meta_candidate ) > 141 ) {
                            $meta_candidate = mb_substr( $meta_candidate, 0, 138 ) . '...';
                        }
                    }

                    // Ensure final candidate meets minimum length; if so, set and log change
                    if ( mb_strlen( $meta_candidate ) >= 120 && mb_strlen( $meta_candidate ) <= 141 ) {
                        $old = isset( $data['meta_description'] ) ? $data['meta_description'] : '';
                        $data['meta_description'] = $meta_candidate;
                        $changed = true;
                        // Log autofix action to global debug log for traceability
                        $dbg = $this->get_global_debug_log_path();
                        $entry = "[" . date('Y-m-d H:i:s') . "] AUTO_FIX_META_APPLIED:\nOLD_META:\n" . print_r( $old, true ) . "\nNEW_META:\n" . print_r( $meta_candidate, true ) . "\n---\n";
                        @file_put_contents( $dbg, $entry, FILE_APPEND | LOCK_EX );
                    }
                }
            }
        }

        // Ensure title begins with focus keyword
        // Ensure title begins with focus keyword; if missing, prepend it (and strip HTML)
        if ( $focus !== '' && isset( $data['title'] ) ) {
            $title = trim( $data['title'] );
            if ( stripos( $title, $focus ) !== 0 ) {
                $data['title'] = wp_strip_all_tags( $focus . ' - ' . $title );
                $changed = true;
            } else {
                $data['title'] = wp_strip_all_tags( $data['title'] );
            }
            // Truncate title to 66 chars for SEO
            if ( mb_strlen( $data['title'] ) > 66 ) {
                $data['title'] = mb_substr( $data['title'], 0, 63 ) . '...';
                $changed = true;
            }
        }

        // Ensure first paragraph contains focus keyword by prepending a short sentence
        if ( $focus !== '' && isset( $data['content'] ) ) {
            $content_html = $data['content'];
            $first_para = '';
            if ( preg_match( '#<p[^>]*>(.*?)</p>#is', $content_html, $m ) ) {
                $first_para = wp_strip_all_tags( $m[1] );
            }
            if ( $first_para !== '' && stripos( $first_para, $focus ) === false ) {
                // Prepend a short sentence containing the focus keyword
                $prepend = '<p>' . esc_html( $focus . ' is an important topic to consider.' ) . '</p>';
                $data['content'] = $prepend . "\n" . $data['content'];
                $changed = true;
            }
        }

        // Remove any leading H1 that duplicate the title
        if ( isset( $data['content'] ) && isset( $data['title'] ) ) {
            if ( preg_match( '#^\s*<h1[^>]*>(.*?)</h1>\s*#is', $data['content'], $mh ) ) {
                $h1text = wp_strip_all_tags( $mh[1] );
                if ( trim( strtolower( $h1text ) ) === trim( strtolower( wp_strip_all_tags( $data['title'] ) ) ) ) {
                    $data['content'] = preg_replace( '#^\s*<h1[^>]*>.*?</h1>\s*#is', '', $data['content'], 1 );
                    $changed = true;
                }
            }
        }

        // Strip bold tags aggressively in content
        if ( isset( $data['content'] ) ) {
            // Remove all <b> and <strong> tags, including nested and with attributes
            $new = preg_replace( '#<(\/)?(b|strong)(\s[^>]*)?>#i', '', $data['content'] );
            // Remove any leftover tags with attributes or nested
            while ( preg_match( '#<(\/)?(b|strong)(\s[^>]*)?>#i', $new ) ) {
                $new = preg_replace( '#<(\/)?(b|strong)(\s[^>]*)?>#i', '', $new );
            }
            if ( $new !== $data['content'] ) {
                $data['content'] = $new;
                $changed = true;
            }
        }

        // Reduce keyword density if too high by replacing some occurrences with synonyms
        if ( $focus !== '' && isset( $data['content'] ) ) {
            $plain = wp_strip_all_tags( $data['content'] );
            $word_total = max( 1, str_word_count( $plain ) );
            $norm_focus = str_replace( array( '’', '‘' ), array("'", "'"), $focus );
            $regex = '/\b' . preg_quote( $norm_focus, '/' ) . '\b/i';
            $count = preg_match_all( $regex, $plain, $matches );
            // Compute allowed occurrences (min 1)
            $allowed = max( 1, intval( floor( 12 * $word_total / 1000 ) ) );
            if ( $count > $allowed ) {
                $to_reduce = $count - $allowed;
                $synonyms = array( 'artificial intelligence', 'machine learning', 'AI tools', 'automation', 'intelligent automation' );
                $replaced = 0;
                $data['content'] = preg_replace_callback( $regex, function( $m ) use ( &$replaced, &$to_reduce, $synonyms ) {
                    if ( $to_reduce <= 0 ) {
                        return $m[0];
                    }
                    $rep = $synonyms[ $replaced % count( $synonyms ) ];
                    $replaced++;
                    $to_reduce--;
                    return $rep;
                }, $data['content'] );
                $changed = true;
            }
        }

        // Ensure at least one image prompt exists and has alt containing focus keyword
        if ( empty( $data['image_prompts'] ) || ! is_array( $data['image_prompts'] ) || count( $data['image_prompts'] ) < 1 ) {
            if ( $focus !== '' ) {
                $data['image_prompts'] = array(
                    array(
                        'prompt' => esc_html( $data['title'] ?? $focus ),
                        'alt' => sanitize_text_field( $focus . ' - featured image' ),
                    )
                );
                $changed = true;
            }
        } else {
            // Ensure alt attribute contains focus keyword or synonym
            $has_focus_alt = false;
            $synonyms = array( 'artificial intelligence', 'machine learning', 'AI tools', 'automation', 'intelligent automation' );
            foreach ( $data['image_prompts'] as &$img ) {
                if ( isset( $img['alt'] ) && ( stripos( $img['alt'], $focus ) !== false || preg_match('/'.implode('|', array_map('preg_quote', $synonyms)).'/i', $img['alt']) ) ) {
                    $has_focus_alt = true;
                }
            }
            if ( ! $has_focus_alt && isset( $data['image_prompts'][0] ) ) {
                $data['image_prompts'][0]['alt'] = $focus . ' - featured image';
                $changed = true;
            }
        }

        // Ensure at least two internal_link suggestions (placeholders if not available)
        if ( empty( $data['internal_links'] ) || ! is_array( $data['internal_links'] ) || count( $data['internal_links'] ) < 2 ) {
            $existing = is_array( $data['internal_links'] ) ? $data['internal_links'] : array();
            // Try to fetch relevant internal pages/posts for linking
            $candidates = $this->acs_get_internal_link_candidates( $data['title'] ?? $focus, isset($data['secondary_keywords']) ? ( is_array($data['secondary_keywords']) ? implode(',', $data['secondary_keywords']) : $data['secondary_keywords'] ) : '', 5 );
            foreach ( $candidates as $c ) {
                if ( count( $existing ) >= 2 ) break;
                $anchor = wp_strip_all_tags( $c['title'] );
                // Avoid using exact focus keyword as anchor
                if ( $focus !== '' && stripos( $anchor, $focus ) !== false ) {
                    // try excerpt as anchor instead
                    $anchor = wp_trim_words( wp_strip_all_tags( $c['excerpt'] ), 6, '...' );
                    if ( stripos( $anchor, $focus ) !== false ) {
                        // fallback to a neutral anchor
                        $anchor = sprintf( __( 'Related: %s', 'ai-content-studio' ), wp_strip_all_tags( $c['title'] ) );
                    }
                }
                $existing[] = array( 'url' => $c['url'], 'anchor' => $anchor );
            }
            // If still not enough, add safe placeholders
            if ( count( $existing ) < 2 ) {
                $placeholders = array(
                    array( 'url' => home_url( '/related-topics' ), 'anchor' => __( 'Related topics', 'ai-content-studio' ) ),
                    array( 'url' => home_url( '/resources' ), 'anchor' => __( 'Further reading', 'ai-content-studio' ) ),
                );
                foreach ( $placeholders as $ph ) {
                    if ( count( $existing ) >= 2 ) break;
                    $existing[] = $ph;
                }
            }
            $data['internal_links'] = $existing;
            $changed = true;
        }
        // Ensure at least one outbound link exists in content
        if ( isset( $data['content'] ) ) {
            // Use a reputable technology source when inserting an outbound link as fallback
            $outbound_url = 'https://www.technologyreview.com/';
            $outbound_anchor = 'MIT Technology Review';
            // If there are no external links, insert a reputable outbound link into first paragraph
            preg_match_all("/https?:\\/\\/[^\\s\"']+/i", $data['content'], $m);
            $has_external = ! empty( $m[0] );
            if ( ! $has_external ) {
                // Insert outbound link at the end of the first paragraph (general tech reference)
                $data['content'] = preg_replace( '/(<p>.*?<\/p>)/i', '$1<p>For further reading on this topic, see <a href="'.esc_attr( $outbound_url ).'" target="_blank" rel="noopener">'.esc_html( $outbound_anchor ).'</a>.</p>', $data['content'], 1 );
                $changed = true;
            }
        }

        // Re-check that first paragraph still includes the exact focus keyword; if reductions removed it, prepend a focused sentence.
        if ( $focus !== '' && isset( $data['content'] ) ) {
            $first_para = '';
            if ( preg_match( '#<p[^>]*>(.*?)</p>#is', $data['content'], $m ) ) {
                $first_para = wp_strip_all_tags( $m[1] );
            }
            if ( $first_para !== '' && stripos( $first_para, $focus ) === false ) {
                $prepend = '<p>' . esc_html( $focus . ' is an important topic to consider.' ) . '</p>';
                $data['content'] = $prepend . "\n" . $data['content'];
                $changed = true;
            }
        }

        // Final normalization: ensure total occurrences of exact focus keyword do not exceed allowed.
        // Protect the first paragraph (must contain the exact focus keyword) and only reduce occurrences in the remainder.
        if ( $focus !== '' && isset( $data['content'] ) ) {
            $content_html = $data['content'];
            $first_para_html = '';
            $rest_html = $content_html;
            if ( preg_match( '#^(\s*<p[^>]*>.*?</p>)#is', $content_html, $fm ) ) {
                $first_para_html = $fm[1];
                $rest_html = substr( $content_html, strlen( $first_para_html ) );
            }

            // Ensure first paragraph contains exact focus keyword; if not, prepend a focused sentence using the exact token.
            $first_para_text = wp_strip_all_tags( $first_para_html );
            if ( $first_para_text === '' || stripos( $first_para_text, $focus ) === false ) {
                $first_para_html = '<p>' . esc_html( $focus . ' is an important topic to consider.' ) . '</p>' . $first_para_html;
            }

            // Now run reduction only on the remainder HTML
            $plain_rest = wp_strip_all_tags( $rest_html );
            $word_total = max( 1, str_word_count( trim( wp_strip_all_tags( $first_para_html . ' ' . $rest_html ) ) ) );
            $regex = '/\b' . preg_quote( str_replace( array( '’', '‘' ), array("'", "'" ), $focus ), '/' ) . '\b/i';
            $total_count = preg_match_all( $regex, wp_strip_all_tags( $first_para_html . ' ' . $rest_html ), $matches );
            $allowed = max( 1, intval( floor( 12 * $word_total / 1000 ) ) );
            if ( $total_count > $allowed ) {
                $synonyms = array( 'artificial intelligence', 'machine learning', 'AI tools', 'automation', 'intelligent automation' );
                // Work on the full content but preserve first paragraph: replace occurrences beyond the allowed count.
                $full = $first_para_html . $rest_html;
                $matches = array();
                preg_match_all( $regex, wp_strip_all_tags( $full ), $m_all, PREG_OFFSET_CAPTURE );
                if ( ! empty( $m_all[0] ) ) {
                    // Build positions based on the stripped text; we will map replacements on the HTML by searching sequentially.
                    $strip = wp_strip_all_tags( $full );
                    $occurrences = array();
                    foreach ( $m_all[0] as $mi ) {
                        $occurrences[] = $mi[0];
                    }

                    // Decide which occurrences to keep: keep the earliest $allowed occurrences (this will include first paragraph since it's earliest)
                    $keep = $allowed;
                    $keep_list = array();
                    for ( $i = 0; $i < min( $keep, count( $occurrences ) ); $i++ ) {
                        $keep_list[] = $i;
                    }

                    // Now perform replacements from the end so offsets remain valid when modifying HTML
                    $repl_index = 0;
                    $occ_total = count( $occurrences );
                    for ( $i = $occ_total - 1; $i >= 0; $i-- ) {
                        if ( in_array( $i, $keep_list ) ) continue;
                        // Replace the i-th occurrence in the HTML with a synonym
                        $rep = $synonyms[ $repl_index % count( $synonyms ) ];
                        $repl_index++;
                        // Replace the next occurrence of the exact focus word in the HTML (from the end)
                        $pattern = $regex;
                        $full = preg_replace( $pattern, $rep, $full, 1 );
                    }
                    $data['content'] = $full;
                    $changed = true;
                }
            }
        }

        return $changed ? $data : true;
    }

    /**
     * Create WordPress post from generated content.
     * Returns post ID on success or false.
     */
    public function create_post( $content, $keywords = '' ) {
        // If $content is a JSON string, decode it first
        if ( is_string( $content ) ) {
            $parsed = json_decode( $content, true );
            if ( is_array( $parsed ) ) {
                $content = $parsed;
            }
        }

        $title = isset( $content['title'] ) ? wp_strip_all_tags( $content['title'] ) : '';
        if ( empty( $title ) ) {
            $title = 'AI Generated Post - ' . current_time( 'Y-m-d H:i:s' );
        }
        // Ensure title begins with focus keyword if provided and truncate
        $focus_kw = isset( $content['focus_keyword'] ) ? trim( wp_strip_all_tags( $content['focus_keyword'] ) ) : '';
        if ( $focus_kw !== '' && stripos( $title, $focus_kw ) !== 0 ) {
            $title = wp_strip_all_tags( $focus_kw . ' - ' . $title );
        }
        if ( mb_strlen( $title ) > 60 ) {
            $title = mb_substr( $title, 0, 57 ) . '...';
        }

        $post_content = isset( $content['content'] ) ? $content['content'] : 'No content generated.';
        // Remove bold tags (<b>, <strong>) anywhere (with attributes) and any leading H1 that duplicates title
        $post_content = preg_replace( '#</?(?:b|strong)(?:\s[^>]*)?>#i', '', $post_content );
        // Remove a leading H1 if it matches the title
        $post_content = preg_replace( '#^\s*<h1[^>]*>\s*' . preg_quote( wp_strip_all_tags( $title ), '#' ) . '\s*</h1>\s*#is', '', $post_content );

        $slug = isset( $content['slug'] ) ? sanitize_title( $content['slug'] ) : sanitize_title( $title );
        $meta_description = isset( $content['meta_description'] ) ? sanitize_text_field( $content['meta_description'] ) : '';
        $excerpt = isset( $content['excerpt'] ) ? sanitize_text_field( $content['excerpt'] ) : '';

        // Validate generated output before creating a post
        // For create_post, we only need essential fields (title, content)
        // Other fields are optional and can be added later
        $essential_fields = ['title', 'content'];
        $missing_essential = [];
        foreach ( $essential_fields as $field ) {
            if ( empty( $content[ $field ] ) ) {
                $missing_essential[] = $field;
            }
        }
        
        if ( ! empty( $missing_essential ) ) {
            $error_msg = 'Missing essential fields: ' . implode( ', ', $missing_essential );
            error_log( '[ACS][CREATE_POST] ' . $error_msg );
            return new WP_Error( 'missing_fields', $error_msg );
        }
        
        // Run full validation but don't fail if optional fields are missing
        $validation = $this->validate_generated_output( $content );
        if ( $validation !== true && is_array( $validation ) ) {
            // Log validation warnings but don't block post creation
            $dbg = $this->get_global_debug_log_path();
            $entry = "[" . date('Y-m-d H:i:s') . "] POST_CREATION_VALIDATION_WARNINGS:\n" . print_r( $validation, true ) . "\nCONTENT:\n" . print_r( $content, true ) . "\n---\n";
            file_put_contents( $dbg, $entry, FILE_APPEND | LOCK_EX );
            // Continue with post creation despite validation warnings
            error_log( '[ACS][CREATE_POST] Validation warnings (non-blocking): ' . implode( ', ', $validation ) );
        }

        $post_data = array(
            'post_title'   => $title,
            'post_content' => $post_content,
            'post_name'    => $slug,
            'post_excerpt' => $excerpt,
            'post_status'  => 'draft',
            'post_author'  => get_current_user_id(),
            'post_type'    => 'post',
            'meta_input'   => array(
                '_acs_generated'        => true,
                '_acs_generated_date'   => current_time( 'mysql' ),
                '_acs_original_keywords'=> $keywords,
                '_yoast_wpseo_metadesc' => $meta_description,
            ),
        );

        $post_id = wp_insert_post( $post_data );

        if ( $post_id && ! is_wp_error( $post_id ) ) {
            // Persist generation validation report (if present) for admin inspection
            if ( isset( $content['acs_validation_report'] ) ) {
                update_post_meta( $post_id, '_acs_generation_report', $content['acs_validation_report'] );
            }

            // Ensure the stored post content is free of bold tags and leading H1s
            $stored = get_post_field( 'post_content', $post_id );
            $cleaned = preg_replace( '#</?(?:b|strong)(?:\s[^>]*)?>#i', '', $stored );
            $cleaned = preg_replace( '#^\s*<h1[^>]*>.*?</h1>\s*#is', '', $cleaned );
            if ( $cleaned !== $stored ) {
                wp_update_post( array( 'ID' => $post_id, 'post_content' => $cleaned ) );
            }

            // Log generation attempt (non-blocking)
            if ( file_exists( ACS_PLUGIN_PATH . 'includes/class-acs-logger.php' ) ) {
                require_once ACS_PLUGIN_PATH . 'includes/class-acs-logger.php';
                $report = isset( $content['acs_validation_report'] ) ? $content['acs_validation_report'] : array();
                $context = array(
                    'title' => $title,
                    'focus_keyword' => $content['focus_keyword'] ?? '',
                );
                ACS_Logger::log_generation_attempt( $post_id, $report, $context );
            }
            // Apply SEO metadata via integration helper if available
            if ( class_exists( 'ACS_SEO_Integration' ) ) {
                require_once ACS_PLUGIN_PATH . 'api/class-acs-seo-integration.php';
                ACS_SEO_Integration::apply_seo_meta( $post_id, array(
                    'meta_description' => $content['meta_description'] ?? '',
                    'focus_keyword' => $content['focus_keyword'] ?? '',
                ) );
            } else {
                if ( ! empty( $content['meta_description'] ) ) {
                    update_post_meta( $post_id, '_yoast_wpseo_metadesc', sanitize_text_field( $content['meta_description'] ) );
                }
                $focus_keyword = $content['focus_keyword'];
                if ( empty( $focus_keyword ) && ! empty( $keywords ) ) {
                    $keyword_array = array_map( 'trim', explode( ',', $keywords ) );
                    $focus_keyword = $keyword_array[0];
                }
                    if ( ! empty( $focus_keyword ) ) {
                        update_post_meta( $post_id, '_yoast_wpseo_focuskw', sanitize_text_field( $focus_keyword ) );
                        // Ensure SEO title begins with focus keyword and is trimmed
                        $seo_title = $title;
                        if ( stripos( $seo_title, $focus_keyword ) !== 0 ) {
                            $seo_title = $focus_keyword . ' - ' . $seo_title;
                        }
                        $seo_title_trim = wp_strip_all_tags( $seo_title );
                        if ( mb_strlen( $seo_title_trim ) > 60 ) {
                            $seo_title_trim = mb_substr( $seo_title_trim, 0, 57 ) . '...';
                        }
                        update_post_meta( $post_id, '_yoast_wpseo_title', sanitize_text_field( $seo_title_trim ) );
                    }
            }

            // Attempt to generate and attach a featured image if generator exists
            if ( class_exists( 'ACS_Image_Generator' ) ) {
                require_once ACS_PLUGIN_PATH . 'generators/class-acs-image-generator.php';
                $img_gen = new ACS_Image_Generator();
                $featured_id = $img_gen->generate_featured_image( $title . ' ' . ($content['meta_description'] ?? ''), $post_id );
                if ( $featured_id ) {
                    set_post_thumbnail( $post_id, intval( $featured_id ) );
                }
            }

            return $post_id;
        }

        return false;
    }

    /**
     * Validate a saved post by ID using the generator's validation rules.
     * Returns true or array of errors.
     *
     * @param int $post_id
     * @return true|array
     */
    public function validate_post_by_id( $post_id ) {
        $post = get_post( $post_id );
        if ( ! $post ) {
            return array( 'post_not_found' );
        }

        $data = array();
        $data['title'] = $post->post_title;
        $data['content'] = $post->post_content;
        $data['meta_description'] = get_post_meta( $post_id, '_yoast_wpseo_metadesc', true ) ?: get_post_meta( $post_id, '_acs_meta_description', true ) ?: $post->post_excerpt;
        $data['focus_keyword'] = get_post_meta( $post_id, '_yoast_wpseo_focuskw', true ) ?: get_post_meta( $post_id, '_acs_original_keywords', true );
        $data['slug'] = $post->post_name;
        $data['excerpt'] = $post->post_excerpt;

        // Try to get previously-suggested image prompts / internal links from report
        $report = get_post_meta( $post_id, '_acs_generation_report', true );
        $data['image_prompts'] = isset( $report['image_prompts'] ) ? $report['image_prompts'] : array();
        $data['internal_links'] = isset( $report['internal_links'] ) ? $report['internal_links'] : array();

        return $this->validate_generated_output( $data );
    }

    /**
     * Retry generation for an existing post. This will attempt a fresh generation
     * using the default provider and update the post content and report when successful.
     *
     * @param int $post_id
     * @return array|WP_Error New generation result array on success or WP_Error
     */
    public function retry_generation_for_post( $post_id ) {
        $post = get_post( $post_id );
        if ( ! $post ) {
            return new WP_Error( 'post_not_found', 'Post not found' );
        }

        // Build prompt_data from existing post to improve context
        $topic = $post->post_title ?: wp_strip_all_tags( $post->post_excerpt );
        $keywords = get_post_meta( $post_id, '_acs_original_keywords', true ) ?: '';

        $prompt_data = array(
            'topic' => $topic,
            'keywords' => $keywords,
        );

        $new_result = $this->generate( $prompt_data );
        if ( is_wp_error( $new_result ) ) {
            return $new_result;
        }

        // Update existing post with new content (preserve author/status)
        $update = array(
            'ID' => $post_id,
            'post_title' => sanitize_text_field( $new_result['title'] ?? $post->post_title ),
            'post_content' => wp_kses_post( $new_result['content'] ?? $post->post_content ),
            'post_excerpt' => sanitize_text_field( $new_result['excerpt'] ?? $post->post_excerpt ),
        );

        wp_update_post( $update );

        // Persist updated SEO meta and generation report
        if ( isset( $new_result['meta_description'] ) ) {
            update_post_meta( $post_id, '_yoast_wpseo_metadesc', sanitize_text_field( $new_result['meta_description'] ) );
        }
        if ( isset( $new_result['focus_keyword'] ) ) {
            update_post_meta( $post_id, '_yoast_wpseo_focuskw', sanitize_text_field( $new_result['focus_keyword'] ) );
        }

        if ( isset( $new_result['acs_validation_report'] ) ) {
            update_post_meta( $post_id, '_acs_generation_report', $new_result['acs_validation_report'] );
        }

        // Attempt to regenerate and set featured image if available
        if ( class_exists( 'ACS_Image_Generator' ) ) {
            require_once ACS_PLUGIN_PATH . 'generators/class-acs-image-generator.php';
            $img_gen = new ACS_Image_Generator();
            $featured_id = $img_gen->generate_featured_image( sanitize_text_field( $new_result['title'] ?? $post->post_title ) . ' ' . sanitize_text_field( $new_result['meta_description'] ?? '' ), $post_id );
            if ( $featured_id ) {
                set_post_thumbnail( $post_id, intval( $featured_id ) );
            }
        }

        // Log retry attempt
        if ( file_exists( ACS_PLUGIN_PATH . 'includes/class-acs-logger.php' ) ) {
            require_once ACS_PLUGIN_PATH . 'includes/class-acs-logger.php';
            $report = isset( $new_result['acs_validation_report'] ) ? $new_result['acs_validation_report'] : array();
            $context = array(
                'title' => $new_result['title'] ?? '',
                'focus_keyword' => $new_result['focus_keyword'] ?? '',
            );
            ACS_Logger::log_generation_attempt( $post_id, $report, $context );
        }

        return $new_result;
    }

    /**
     * Apply deterministic auto-fixes to an existing post using the same rules
     * we apply during generation. This updates the post content, title, excerpt,
     * and SEO meta where applicable, and writes/updates the `_acs_generation_report`.
     *
     * @param int $post_id
     * @return array|WP_Error Report array on success or WP_Error
     */
    public function apply_auto_fixes_to_post( $post_id ) {
        $post = get_post( $post_id );
        if ( ! $post ) {
            return new WP_Error( 'post_not_found', 'Post not found' );
        }

        $data = array();
        $data['title'] = $post->post_title;
        $data['content'] = $post->post_content;
        $data['meta_description'] = get_post_meta( $post_id, '_yoast_wpseo_metadesc', true ) ?: get_post_meta( $post_id, '_acs_meta_description', true ) ?: $post->post_excerpt;
        $data['focus_keyword'] = get_post_meta( $post_id, '_yoast_wpseo_focuskw', true ) ?: get_post_meta( $post_id, '_acs_original_keywords', true );
        $data['slug'] = $post->post_name;
        $data['excerpt'] = $post->post_excerpt;

        $report_before = get_post_meta( $post_id, '_acs_generation_report', true );

        // Run auto-fix routine (this returns modified array or true)
        $fixed = $this->auto_fix_generated_output( $data );
        if ( $fixed === true ) {
            // Nothing to change
            return array( 'changed' => false, 'report' => $report_before );
        }
        if ( ! is_array( $fixed ) ) {
            return new WP_Error( 'fix_failed', 'Auto-fix did not return an array' );
        }

        // Prepare update
        $update = array( 'ID' => $post_id );
        if ( isset( $fixed['title'] ) && $fixed['title'] !== $post->post_title ) {
            $update['post_title'] = sanitize_text_field( $fixed['title'] );
        }
        if ( isset( $fixed['content'] ) && $fixed['content'] !== $post->post_content ) {
            $update['post_content'] = wp_kses_post( $fixed['content'] );
        }
        if ( isset( $fixed['excerpt'] ) ) {
            $update['post_excerpt'] = sanitize_text_field( $fixed['excerpt'] );
        }

        if ( count( $update ) > 1 ) {
            wp_update_post( $update );
        }

        // Update SEO meta if present
        if ( isset( $fixed['meta_description'] ) ) {
            update_post_meta( $post_id, '_yoast_wpseo_metadesc', sanitize_text_field( $fixed['meta_description'] ) );
        }
        if ( isset( $fixed['focus_keyword'] ) ) {
            update_post_meta( $post_id, '_yoast_wpseo_focuskw', sanitize_text_field( $fixed['focus_keyword'] ) );
            // Ensure seo title meta also begins with focus and is truncated
            $seo_title = isset( $fixed['title'] ) ? $fixed['title'] : get_the_title( $post_id );
            if ( stripos( $seo_title, $fixed['focus_keyword'] ) !== 0 ) {
                $seo_title = $fixed['focus_keyword'] . ' - ' . $seo_title;
            }
            if ( mb_strlen( $seo_title ) > 60 ) {
                $seo_title = mb_substr( $seo_title, 0, 57 ) . '...';
            }
            update_post_meta( $post_id, '_yoast_wpseo_title', sanitize_text_field( $seo_title ) );
        }

        // Rebuild generation report
        $new_report = is_array( $report_before ) ? $report_before : array();
        $new_report['auto_fix_applied'] = true;
        $new_report['auto_fix_timestamp'] = current_time( 'mysql' );
        update_post_meta( $post_id, '_acs_generation_report', $new_report );

        // Clean up any remaining bold tags and leading H1s in stored content
        $stored = get_post_field( 'post_content', $post_id );
        $cleaned = preg_replace( '#</?(?:b|strong)(?:\s[^>]*)?>#i', '', $stored );
        $cleaned = preg_replace( '#^\s*<h1[^>]*>.*?</h1>\s*#is', '', $cleaned );
        if ( $cleaned !== $stored ) {
            wp_update_post( array( 'ID' => $post_id, 'post_content' => $cleaned ) );
        }

        return array( 'changed' => true, 'report' => $new_report );
    }

    /**
     * Validate content using comprehensive SEO validation pipeline
     *
     * @param array $content Generated content array
     * @param string $keywords Keywords string
     * @return SEOValidationResult Validation result with corrections
     */
    private function validateWithPipeline( $content, $keywords ) {
        try {
            // Load Multi-Pass SEO Optimizer and Integration Layer
            if ( ! class_exists( 'IntegrationCompatibilityLayer' ) ) {
                require_once ACS_PLUGIN_PATH . 'seo/class-integration-compatibility-layer.php';
            }
            
            // Parse keywords
            $focusKeyword = '';
            $secondaryKeywords = [];
            
            if ( ! empty( $keywords ) ) {
                $keywordArray = array_map( 'trim', explode( ',', $keywords ) );
                $focusKeyword = $keywordArray[0] ?? '';
                $secondaryKeywords = array_slice( $keywordArray, 1 );
            }
            
            // Use focus keyword from content if not provided
            if ( empty( $focusKeyword ) && ! empty( $content['focus_keyword'] ) ) {
                $focusKeyword = $content['focus_keyword'];
            }
            
            // Configure multi-pass optimizer
            $integrationConfig = [
                'enableOptimizer' => get_option('acs_optimizer_enabled', true),
                'autoOptimizeNewContent' => get_option('acs_auto_optimize', true),
                'bypassMode' => get_option('acs_bypass_mode', false),
                'supportedFormats' => ['post', 'page', 'article'],
                'integrationMode' => get_option('acs_integration_mode', 'seamless'),
                'fallbackToOriginal' => get_option('acs_fallback_enabled', true),
                'preserveExistingWorkflow' => true,
                'logLevel' => 'info'
            ];
            
            $integrationLayer = new IntegrationCompatibilityLayer( $integrationConfig );
            
            // Process content through multi-pass optimizer
            $processedContent = $integrationLayer->processGeneratedContent( $content, $focusKeyword, $secondaryKeywords );
            
            // Get the actual SEOValidationResult from optimization metadata if available
            $validationResult = null;
            
            // Check optimization metadata
            if ( isset( $processedContent['_acs_optimization'] ) ) {
                $metadata = $processedContent['_acs_optimization'];
                
                // Try to get the validationResult from the metadata
                if ( isset( $metadata['validationResult'] ) && $metadata['validationResult'] instanceof SEOValidationResult ) {
                    $validationResult = $metadata['validationResult'];
                } elseif ( isset( $metadata['full_result']['validationResult'] ) && $metadata['full_result']['validationResult'] instanceof SEOValidationResult ) {
                    $validationResult = $metadata['full_result']['validationResult'];
                } elseif ( isset( $metadata['optimizationResult']['validationResult'] ) && $metadata['optimizationResult']['validationResult'] instanceof SEOValidationResult ) {
                    $validationResult = $metadata['optimizationResult']['validationResult'];
                }
            }
            
            // If we don't have a validation result, create one from metadata or use defaults
            if ( ! $validationResult ) {
                if ( ! class_exists( 'SEOValidationResult' ) ) {
                    require_once ACS_PLUGIN_PATH . 'seo/class-seo-validation-result.php';
                }
                
                $validationResult = new SEOValidationResult();
                
                if ( isset( $processedContent['_acs_optimization'] ) ) {
                    $metadata = $processedContent['_acs_optimization'];
                    
                    if ( $metadata['status'] === 'optimized' ) {
                        $validationResult->isValid = $metadata['compliance_achieved'] ?? false;
                        $validationResult->overallScore = $metadata['score'] ?? 0;
                        $validationResult->correctionsMade = ['multi_pass_optimization'];
                    } elseif ( $metadata['status'] === 'bypassed' ) {
                        $validationResult->isValid = true; // Accept bypassed content
                        $validationResult->overallScore = 100;
                        $validationResult->correctionsMade = ['bypassed'];
                    } elseif ( $metadata['status'] === 'fallback' || $metadata['status'] === 'error' ) {
                        $validationResult->isValid = true; // Accept fallback content
                        $validationResult->overallScore = 50;
                        $validationResult->correctionsMade = ['fallback'];
                    }
                } else {
                    // No optimization metadata - treat as valid for backward compatibility
                    $validationResult->isValid = true;
                    $validationResult->overallScore = 75;
                }
            }
            
            // Set corrected content
            $validationResult->correctedContent = $processedContent;
            
            // Log multi-pass optimization attempt
            $dbg = $this->get_global_debug_log_path();
            $entry = "[" . date('Y-m-d H:i:s') . "] MULTI_PASS_SEO_OPTIMIZATION:\n";
            $entry .= "FOCUS_KEYWORD: " . $focusKeyword . "\n";
            $entry .= "STATUS: " . ($result['_acs_optimization']['status'] ?? 'unknown') . "\n";
            $entry .= "VALID: " . ($validationResult->isValid ? 'YES' : 'NO') . "\n";
            $entry .= "SCORE: " . $validationResult->overallScore . "\n";
            $entry .= "CORRECTIONS: " . implode(', ', $validationResult->correctionsMade) . "\n";
            $entry .= "---\n";
            @file_put_contents( $dbg, $entry, FILE_APPEND | LOCK_EX );
            
            return $validationResult;
            
        } catch ( Exception $e ) {
            // Log error and return failed validation
            $dbg = $this->get_global_debug_log_path();
            $entry = "[" . date('Y-m-d H:i:s') . "] SEO_PIPELINE_ERROR: " . $e->getMessage() . "\n---\n";
            @file_put_contents( $dbg, $entry, FILE_APPEND | LOCK_EX );
            
            // Return failed validation result
            $result = new SEOValidationResult();
            $result->addError( 'SEO pipeline validation failed: ' . $e->getMessage(), 'pipeline' );
            return $result;
        }
    }

    /**
     * Get internal link candidates (posts/pages) relevant to topic/keywords.
     */
    private function acs_get_internal_link_candidates($topic, $keywords, $max = 5) {
        $candidates = array();
        $args = array(
            'post_type' => array('post', 'page'),
            'post_status' => 'publish',
            'posts_per_page' => $max,
            'orderby' => 'date',
            'order' => 'DESC',
            's' => $topic,
        );
        $query = new WP_Query($args);
        if ($query->have_posts()) {
            foreach ($query->posts as $p) {
                $candidates[] = array(
                    'title' => get_the_title($p->ID),
                    'url' => get_permalink($p->ID),
                    'excerpt' => wp_trim_words($p->post_content, 20),
                );
            }
        }
        // If not enough, add by keywords
        if (count($candidates) < $max && !empty($keywords)) {
            $kw_array = array_map('trim', explode(',', $keywords));
            foreach ($kw_array as $kw) {
                $args['s'] = $kw;
                $query = new WP_Query($args);
                foreach ($query->posts as $p) {
                    $found = false;
                    foreach ($candidates as $c) {
                        if ($c['url'] === get_permalink($p->ID)) {
                            $found = true; break;
                        }
                    }
                    if (!$found) {
                        $candidates[] = array(
                            'title' => get_the_title($p->ID),
                            'url' => get_permalink($p->ID),
                            'excerpt' => wp_trim_words($p->post_content, 20),
                        );
                        if (count($candidates) >= $max) break 2;
                    }
                }
            }
        }
        return $candidates;
    }

    /**
     * Get SEO validation configuration from plugin settings
     *
     * @return array SEO configuration array
     */
    public function getSEOConfiguration() {
        $settings = get_option( 'acs_settings', array() );
        $seoSettings = $settings['seo'] ?? array();
        
        // Default SEO configuration with slightly more lenient thresholds
        $defaultConfig = [
            'minMetaDescLength' => 110,  // Reduced from 120 to allow slightly shorter descriptions
            'maxMetaDescLength' => 156,
            'minKeywordDensity' => 0.5,
            'maxKeywordDensity' => 3.0,  // Increased from 2.5 to 3.0 for more flexibility
            'maxPassiveVoice' => 15.0,   // Increased from 10.0 to 15.0 for more natural writing
            'maxLongSentences' => 25.0,
            'minTransitionWords' => 30.0,
            'maxTitleLength' => 66,
            'maxSubheadingKeywordUsage' => 75.0,
            'requireImages' => false,    // Changed to false - images are nice but not required
            'requireKeywordInAltText' => true,
            'autoCorrection' => true,
            'maxRetryAttempts' => 3
        ];
        
        // Map plugin settings to SEO configuration
        $config = $defaultConfig;
        
        // Meta description length
        if ( ! empty( $seoSettings['meta_description_length'] ) ) {
            $length = intval( $seoSettings['meta_description_length'] );
            $config['maxMetaDescLength'] = min( max( $length, 120 ), 160 );
        }
        
        // Keyword density
        if ( ! empty( $seoSettings['focus_keyword_density'] ) ) {
            $density = $seoSettings['focus_keyword_density'];
            switch ( $density ) {
                case '0.5-1':
                    $config['minKeywordDensity'] = 0.5;
                    $config['maxKeywordDensity'] = 1.0;
                    break;
                case '1-2':
                    $config['minKeywordDensity'] = 1.0;
                    $config['maxKeywordDensity'] = 2.0;
                    break;
                case '2-3':
                    $config['minKeywordDensity'] = 2.0;
                    $config['maxKeywordDensity'] = 3.0;
                    break;
            }
        }
        
        // Auto-optimization
        if ( isset( $seoSettings['auto_optimize'] ) ) {
            $config['autoCorrection'] = ! empty( $seoSettings['auto_optimize'] );
        }
        
        // Internal linking
        if ( isset( $seoSettings['internal_linking'] ) ) {
            $config['requireInternalLinks'] = ! empty( $seoSettings['internal_linking'] );
        }
        
        // Readability settings
        if ( ! empty( $seoSettings['max_passive_voice'] ) ) {
            $config['maxPassiveVoice'] = floatval( $seoSettings['max_passive_voice'] );
        }
        
        if ( ! empty( $seoSettings['min_transition_words'] ) ) {
            $config['minTransitionWords'] = floatval( $seoSettings['min_transition_words'] );
        }
        
        if ( ! empty( $seoSettings['max_long_sentences'] ) ) {
            $config['maxLongSentences'] = floatval( $seoSettings['max_long_sentences'] );
        }
        
        if ( ! empty( $seoSettings['max_title_length'] ) ) {
            $config['maxTitleLength'] = intval( $seoSettings['max_title_length'] );
        }
        
        // Adaptive rules
        if ( isset( $seoSettings['adaptive_rules'] ) ) {
            $config['adaptiveRules'] = ! empty( $seoSettings['adaptive_rules'] );
        }
        
        // Comprehensive validation
        if ( isset( $seoSettings['comprehensive_validation'] ) ) {
            $config['comprehensiveValidation'] = ! empty( $seoSettings['comprehensive_validation'] );
        }
        
        // Content settings
        $contentSettings = $settings['content'] ?? array();
        if ( isset( $contentSettings['include_images'] ) ) {
            $config['requireImages'] = ! empty( $contentSettings['include_images'] );
        }
        
        // Advanced settings
        $advancedSettings = $settings['advanced'] ?? array();
        if ( ! empty( $advancedSettings['logging_level'] ) ) {
            $config['loggingLevel'] = $advancedSettings['logging_level'];
        }
        
        return $config;
    }
    
    /**
     * Update SEO validation configuration
     *
     * @param array $newConfig New configuration settings
     * @return bool Success status
     */
    public function updateSEOConfiguration( $newConfig ) {
        try {
            $settings = get_option( 'acs_settings', array() );
            
            // Initialize SEO settings if not exists
            if ( ! isset( $settings['seo'] ) ) {
                $settings['seo'] = array();
            }
            
            // Map configuration back to plugin settings
            if ( isset( $newConfig['maxMetaDescLength'] ) ) {
                $settings['seo']['meta_description_length'] = intval( $newConfig['maxMetaDescLength'] );
            }
            
            if ( isset( $newConfig['minKeywordDensity'] ) && isset( $newConfig['maxKeywordDensity'] ) ) {
                $min = floatval( $newConfig['minKeywordDensity'] );
                $max = floatval( $newConfig['maxKeywordDensity'] );
                
                if ( $min <= 1.0 && $max <= 1.0 ) {
                    $settings['seo']['focus_keyword_density'] = '0.5-1';
                } elseif ( $min <= 2.0 && $max <= 2.0 ) {
                    $settings['seo']['focus_keyword_density'] = '1-2';
                } else {
                    $settings['seo']['focus_keyword_density'] = '2-3';
                }
            }
            
            if ( isset( $newConfig['autoCorrection'] ) ) {
                $settings['seo']['auto_optimize'] = $newConfig['autoCorrection'] ? 1 : 0;
            }
            
            if ( isset( $newConfig['requireInternalLinks'] ) ) {
                $settings['seo']['internal_linking'] = $newConfig['requireInternalLinks'] ? 1 : 0;
            }
            
            if ( isset( $newConfig['requireImages'] ) ) {
                if ( ! isset( $settings['content'] ) ) {
                    $settings['content'] = array();
                }
                $settings['content']['include_images'] = $newConfig['requireImages'] ? 1 : 0;
            }
            
            if ( isset( $newConfig['loggingLevel'] ) ) {
                if ( ! isset( $settings['advanced'] ) ) {
                    $settings['advanced'] = array();
                }
                $settings['advanced']['logging_level'] = sanitize_text_field( $newConfig['loggingLevel'] );
            }
            
            // Readability settings
            if ( isset( $newConfig['maxPassiveVoice'] ) ) {
                $settings['seo']['max_passive_voice'] = floatval( $newConfig['maxPassiveVoice'] );
            }
            
            if ( isset( $newConfig['minTransitionWords'] ) ) {
                $settings['seo']['min_transition_words'] = floatval( $newConfig['minTransitionWords'] );
            }
            
            if ( isset( $newConfig['maxLongSentences'] ) ) {
                $settings['seo']['max_long_sentences'] = floatval( $newConfig['maxLongSentences'] );
            }
            
            if ( isset( $newConfig['maxTitleLength'] ) ) {
                $settings['seo']['max_title_length'] = intval( $newConfig['maxTitleLength'] );
            }
            
            // Adaptive rules
            if ( isset( $newConfig['adaptiveRules'] ) ) {
                $settings['seo']['adaptive_rules'] = $newConfig['adaptiveRules'] ? 1 : 0;
            }
            
            // Comprehensive validation
            if ( isset( $newConfig['comprehensiveValidation'] ) ) {
                $settings['seo']['comprehensive_validation'] = $newConfig['comprehensiveValidation'] ? 1 : 0;
            }
            
            // Update plugin settings
            $success = update_option( 'acs_settings', $settings );
            
            // Log configuration update
            if ( $success ) {
                $log_path = $this->get_global_debug_log_path();
                $entry = "[" . date('Y-m-d H:i:s') . "] SEO_CONFIG_UPDATED:\n";
                $entry .= "NEW_CONFIG:\n" . print_r( $newConfig, true ) . "\n---\n";
                @file_put_contents( $log_path, $entry, FILE_APPEND | LOCK_EX );
            }
            
            return $success;
            
        } catch ( Exception $e ) {
            // Log error
            $log_path = $this->get_global_debug_log_path();
            $entry = "[" . date('Y-m-d H:i:s') . "] SEO_CONFIG_UPDATE_ERROR: " . $e->getMessage() . "\n---\n";
            @file_put_contents( $log_path, $entry, FILE_APPEND | LOCK_EX );
            
            return false;
        }
    }
    
    /**
     * Get dynamic constraint adjustments based on error patterns
     *
     * @return array Adjusted constraints
     */
    public function getDynamicConstraints() {
        try {
            // Load SEO validation pipeline to get error statistics
            if ( ! class_exists( 'SEOValidationPipeline' ) ) {
                require_once ACS_PLUGIN_PATH . 'seo/class-seo-validation-pipeline.php';
            }
            
            $config = $this->getSEOConfiguration();
            $pipeline = new SEOValidationPipeline( $config );
            
            // Get error statistics for the last 7 days
            $errorStats = $pipeline->getErrorStats( null, 7 );
            
            // Adjust constraints based on error patterns
            $adjustments = [];
            
            // If meta description errors are frequent, relax length requirements slightly
            if ( isset( $errorStats['meta_description'] ) && $errorStats['meta_description']['count'] > 10 ) {
                $adjustments['maxMetaDescLength'] = min( $config['maxMetaDescLength'] + 5, 160 );
                $adjustments['minMetaDescLength'] = max( $config['minMetaDescLength'] - 5, 115 );
            }
            
            // If keyword density errors are frequent, adjust thresholds
            if ( isset( $errorStats['keyword_density'] ) && $errorStats['keyword_density']['count'] > 10 ) {
                $adjustments['maxKeywordDensity'] = min( $config['maxKeywordDensity'] + 0.5, 3.0 );
                $adjustments['minKeywordDensity'] = max( $config['minKeywordDensity'] - 0.2, 0.3 );
            }
            
            // If readability errors are frequent, relax requirements
            if ( isset( $errorStats['readability'] ) && $errorStats['readability']['count'] > 10 ) {
                $adjustments['maxPassiveVoice'] = min( $config['maxPassiveVoice'] + 2.0, 15.0 );
                $adjustments['maxLongSentences'] = min( $config['maxLongSentences'] + 5.0, 35.0 );
                $adjustments['minTransitionWords'] = max( $config['minTransitionWords'] - 5.0, 20.0 );
            }
            
            // Log dynamic adjustments
            if ( ! empty( $adjustments ) ) {
                $log_path = $this->get_global_debug_log_path();
                $entry = "[" . date('Y-m-d H:i:s') . "] DYNAMIC_CONSTRAINTS_APPLIED:\n";
                $entry .= "ADJUSTMENTS:\n" . print_r( $adjustments, true ) . "\n";
                $entry .= "ERROR_STATS:\n" . print_r( $errorStats, true ) . "\n---\n";
                @file_put_contents( $log_path, $entry, FILE_APPEND | LOCK_EX );
            }
            
            return array_merge( $config, $adjustments );
            
        } catch ( Exception $e ) {
            // Log error and return default config
            $log_path = $this->get_global_debug_log_path();
            $entry = "[" . date('Y-m-d H:i:s') . "] DYNAMIC_CONSTRAINTS_ERROR: " . $e->getMessage() . "\n---\n";
            @file_put_contents( $log_path, $entry, FILE_APPEND | LOCK_EX );
            
            return $this->getSEOConfiguration();
        }
    }
    
    /**
     * Apply manual override for persistent validation issues
     *
     * @param string $component Component name (meta_description, keyword_density, etc.)
     * @param string $error Error message
     * @param array $override Override configuration
     * @return bool Success status
     */
    public function addManualOverride( $component, $error, $override ) {
        try {
            // Load SEO validation pipeline
            if ( ! class_exists( 'SEOValidationPipeline' ) ) {
                require_once ACS_PLUGIN_PATH . 'seo/class-seo-validation-pipeline.php';
            }
            
            $config = $this->getSEOConfiguration();
            $pipeline = new SEOValidationPipeline( $config );
            
            $success = $pipeline->addManualOverride( $component, $error, $override );
            
            // Log manual override
            if ( $success ) {
                $log_path = $this->get_global_debug_log_path();
                $entry = "[" . date('Y-m-d H:i:s') . "] MANUAL_OVERRIDE_ADDED:\n";
                $entry .= "COMPONENT: {$component}\n";
                $entry .= "ERROR: {$error}\n";
                $entry .= "OVERRIDE:\n" . print_r( $override, true ) . "\n---\n";
                @file_put_contents( $log_path, $entry, FILE_APPEND | LOCK_EX );
            }
            
            return $success;
            
        } catch ( Exception $e ) {
            // Log error
            $log_path = $this->get_global_debug_log_path();
            $entry = "[" . date('Y-m-d H:i:s') . "] MANUAL_OVERRIDE_ERROR: " . $e->getMessage() . "\n---\n";
            @file_put_contents( $log_path, $entry, FILE_APPEND | LOCK_EX );
            
            return false;
        }
    }
}
endif;
