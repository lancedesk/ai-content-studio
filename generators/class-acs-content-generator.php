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

    public function __construct() {
    }

    /**
     * Generate content using the Groq API flow (extracted from admin logic).
     * @param array $prompt_data
     * @return array|WP_Error
     */
    public function generate( $prompt_data ) {
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

        // Build a provider-agnostic prompt
        $word_targets = array(
            'short' => '500',
            'medium' => '1000',
            'long' => '1500',
            'detailed' => '2000+'
        );
        $target_words = $word_targets[ $word_count ] ?? '1000';

        $primary_keyword = '';
        if ( ! empty( $keywords ) ) {
            $keyword_array = array_map( 'trim', explode( ',', $keywords ) );
            $primary_keyword = $keyword_array[0];
        }

        // Gather internal link candidates
        $internal_candidates = $this->acs_get_internal_link_candidates($topic, $keywords, 5);

        // Enhanced prompt for stricter SEO/readability compliance and humanization
        $prompt = "You are an expert SEO content writer for a family audience. Return ONLY a valid JSON object (no extra text, no code fences) with these fields: title, meta_description, slug, content, excerpt, focus_keyword, secondary_keywords (array or comma-separated), tags (array), image_prompts (array of {prompt,alt}), internal_links (array of {url,anchor}).\n\n";
        $prompt .= "Write a complete, SEO-optimized blog post about: {$topic}.\n";
        $prompt .= "Strict requirements (ALL must be followed):\n";
        $prompt .= "- Target length: approximately {$target_words} words.\n";
        $prompt .= "- Title: must begin with the exact focus keyphrase '{$primary_keyword}' (exact match), plain text, <= 60 characters.\n";
        $prompt .= "- Meta description: include the focus keyphrase, <= 155 characters, and engaging.\n";
        $prompt .= "- Content: HTML only (h2/h3, no h1), short paragraphs, varied sentence structure, conversational tone, personal anecdotes, and at least 30% of sentences with transition words (e.g., 'however', 'moreover', 'therefore', 'additionally', 'furthermore').\n";
        $prompt .= "- Do NOT use <b> or <strong> tags.\n";
        $prompt .= "- Keyphrase density: use the focus keyphrase naturally, max 8 times (max 1 if previously used), otherwise use synonyms.\n";
        $prompt .= "- Internal links: at least 2, anchors must be natural (not the exact keyphrase).\n";
        $prompt .= "- Images: at least one image prompt with alt text containing the keyphrase or synonym.\n";
        $prompt .= "- Readability: average sentence length <=20 words, <15% sentences >25 words, transition words in >=30% sentences.\n";
        $prompt .= "- Tone: professional, engaging, age-appropriate, active voice, human-like, avoid AI-detection patterns.\n";
        $prompt .= "- Structure: introduction with keyphrase, H2/H3 subheadings, short paragraphs, clear call to action in conclusion.\n";
        $prompt .= "- Output formatting: slug URL-safe, excerpt <=30 words.\n";
        $prompt .= "- JSON-only: Return ONLY valid JSON. If you cannot meet a rule, include an explicit 'errors' array in the JSON explaining which rules cannot be satisfied.\n";
        $prompt .= "If the output is too formal or robotic, rephrase to sound more human, using contractions, rhetorical questions, and conversational transitions.\n";
        if ( ! empty( $keywords ) ) {
            $prompt .= "Primary keyword: {$primary_keyword}. Secondary keywords: {$keywords}.\n";
        }
        $prompt .= "Strictly avoid adding any extra commentary outside the JSON object.\n";
        // Add internal link candidates to prompt
        if (!empty($internal_candidates)) {
            $prompt .= "\nHere are some pages and posts on our site you can link to if relevant:\n";
            foreach ($internal_candidates as $link) {
                $prompt .= "- Title: {$link['title']}, URL: {$link['url']}\n";
            }
        }

        // Log prompt for debugging (plugin directory)
        $debug_log_path = dirname(__FILE__) . '/acs_prompt_debug.log';
        $debug_entry = date('Y-m-d H:i:s') . "\nPROMPT:\n" . $prompt . "\n---\n";
        file_put_contents($debug_log_path, $debug_entry, FILE_APPEND | LOCK_EX);

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

                    // Validate output; if invalid, attempt one focused retry with fix instructions
                    // Attempt minor automatic fixes first
                    $fixed = $this->auto_fix_generated_output( $result );
                    if ( $fixed === true ) {
                        $auto_fix_applied = false;
                    } else {
                        $auto_fix_applied = true;
                        $result = $fixed;
                    }

                    // Optionally fallback to local LLM for humanization if Groq output is too formal
                    if ($prov === 'groq') {
                        $result = $this->fallback_humanize_content($result);
                    }

                    $validation = $this->validate_generated_output( $result );
                    if ( $validation !== true ) {
                        $fix_instructions = "Please correct the following issues and return ONLY a valid JSON object with the required fields (title, meta_description, slug, content, excerpt, focus_keyword, secondary_keywords, tags, image_prompts, internal_links):\n";
                        foreach ( (array) $validation as $v ) {
                            $fix_instructions .= "- " . $v . "\n";
                        }
                        $retry_prompt = $prompt . "\n\n" . $fix_instructions;
                        $retry_result = $instance->generate_content( $retry_prompt, $options );
                        if ( ! is_wp_error( $retry_result ) ) {
                            $retry_validation = $this->validate_generated_output( $retry_result );
                            if ( $retry_validation === true ) {
                                if ( empty( $retry_result['provider'] ) ) {
                                    $retry_result['provider'] = $prov;
                                }
                                $retry_result['acs_validation_report'] = array(
                                    'provider' => $prov,
                                    'initial_errors' => $validation,
                                    'auto_fix_applied' => $auto_fix_applied,
                                    'retry' => true,
                                    'retry_errors' => array(),
                                );
                                return $retry_result;
                            }
                        }
                        $last_report = array(
                            'provider' => $prov,
                            'initial_errors' => $validation,
                            'auto_fix_applied' => $auto_fix_applied,
                            'retry' => true,
                            'retry_errors' => is_array( $retry_result ) ? $this->validate_generated_output( $retry_result ) : array( 'retry_failed' ),
                        );
                        if ( is_array( $result ) ) {
                            $result['acs_validation_report'] = $last_report;
                        }
                    }

                    $result['acs_validation_report'] = array(
                        'provider' => $prov,
                        'initial_errors' => array(),
                        'auto_fix_applied' => $auto_fix_applied,
                        'retry' => false,
                    );

                    return $result;
                            $retry_result['provider'] = $prov;
                        }
                        // Add a validation report to the returned result
                        $retry_result['acs_validation_report'] = array(
                            'provider' => $prov,
                            'initial_errors' => $validation,
                            'auto_fix_applied' => $auto_fix_applied,
                            'retry' => true,
                            'retry_errors' => array(),
                        );
                        return $retry_result;
                    }
                }
                // Retry failed or still invalid — store last attempt report and continue to next provider
                $last_report = array(
                    'provider' => $prov,
                    'initial_errors' => $validation,
                    'auto_fix_applied' => $auto_fix_applied,
                    'retry' => true,
                    'retry_errors' => is_array( $retry_result ) ? $this->validate_generated_output( $retry_result ) : array( 'retry_failed' ),
                );
                // attach report to result so caller can surface it if desired
                if ( is_array( $result ) ) {
                    $result['acs_validation_report'] = $last_report;
                }
            }


            // Successful and validated: attach report and return
            $result['acs_validation_report'] = array(
                'provider' => $prov,
                'initial_errors' => array(),
                'auto_fix_applied' => $auto_fix_applied,
                'retry' => false,
            );

            return $result;
        }

        return new WP_Error( 'no_providers', 'No available providers could generate content' );
    }

    /**
     * Parse generated content into structured array.
     */
    public function parse_generated_content( $content ) {
        $result = array(
            'title' => '',
            'content' => '',
            'meta_description' => '',
            'focus_keyword' => ''
        );

        if ( preg_match('/TITLE:\s*(.+?)(?=\n|$)/i', $content, $m) ) {
            $result['title'] = trim( $m[1] );
        }
        if ( preg_match('/META_DESCRIPTION:\s*(.+?)(?=\n|$)/i', $content, $m) ) {
            $result['meta_description'] = trim( $m[1] );
        }
        if ( preg_match('/FOCUS_KEYWORD:\s*(.+?)(?=\n|$)/i', $content, $m) ) {
            $result['focus_keyword'] = trim( $m[1] );
        }
        if ( preg_match('/CONTENT:\s*(.+?)(?=\n\s*(?:META_DESCRIPTION|FOCUS_KEYWORD):|$)/is', $content, $m) ) {
            $result['content'] = trim( $m[1] );
        }

        $result['content'] = $this->convert_markdown_to_html( $result['content'] );

        // Remove any bold tags (<b>, <strong>) that providers may insert
        $result['content'] = preg_replace( '#</?(b|strong)[^>]*>#i', '', $result['content'] );

        // Clean title: strip any heading tags or HTML injected into title field
        if ( ! empty( $result['title'] ) ) {
            $result['title'] = wp_strip_all_tags( $result['title'] );
            // Remove accidental leading H1 text like "<h1>Title</h1>"
            $result['title'] = preg_replace( '#^\s*<h1[^>]*>(.*?)</h1>\s*$#is', '\\1', $result['title'] );
            $result['title'] = trim( $result['title'] );
        }

        if ( empty( $result['title'] ) ) {
            $lines = explode( "\n", $content );
            $result['title'] = trim( $lines[0] );
        }
        if ( empty( $result['content'] ) ) {
            $result['content'] = $this->convert_markdown_to_html( $content );
        }

        // If content starts with an H1 that matches the title, remove it to avoid duplicate H1s
        if ( ! empty( $result['title'] ) && ! empty( $result['content'] ) ) {
            $first_h1 = '';
            if ( preg_match( '#^\s*<h1[^>]*>(.*?)</h1>\s*#is', $result['content'], $mh ) ) {
                $first_h1 = wp_strip_all_tags( $mh[1] );
            }
            if ( $first_h1 !== '' ) {
                // Compare normalized variants
                $norm_title = trim( preg_replace( '/\s+/', ' ', strtolower( wp_strip_all_tags( $result['title'] ) ) ) );
                $norm_h1 = trim( preg_replace( '/\s+/', ' ', strtolower( $first_h1 ) ) );
                if ( $norm_title === $norm_h1 ) {
                    // Remove the first H1 block
                    $result['content'] = preg_replace( '#^\s*<h1[^>]*>.*?</h1>\s*#is', '', $result['content'], 1 );
                }
            }
        }

        return $result;
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
        $content = preg_replace('/<\/?p[^>]*>/', '', $content);
        $content = str_replace(array("\r\n", "\r"), "\n", $content);
        $paragraphs = preg_split('/\n\s*\n/', trim( $content ) );
        $html_content = '';
        foreach ( $paragraphs as $paragraph ) {
            $paragraph = trim( $paragraph );
            if ( ! empty( $paragraph ) ) {
                if ( preg_match('/^<\/(h[1-6]|div|ul|ol|blockquote)|^<(h[1-6]|div|ul|ol|blockquote)(\s|>)/i', $paragraph ) ) {
                    $html_content .= $paragraph . "\n\n";
                } else {
                    $html_content .= '<p>' . $paragraph . '</p>' . "\n\n";
                }
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
            }
        }

        // Title must begin with the focus keyword for best SEO
        $focus = isset( $data['focus_keyword'] ) ? trim( strip_tags( $data['focus_keyword'] ) ) : '';
        $title = isset( $data['title'] ) ? trim( strip_tags( $data['title'] ) ) : '';
        if ( $focus !== '' ) {
            if ( stripos( $title, $focus ) !== 0 ) {
                $errors[] = 'SEO title must begin with the focus keyword.';
            }
        }

        // Title length for SEO: recommend <= 60 characters
        if ( ! empty( $title ) ) {
            $title_len = mb_strlen( $title );
            if ( $title_len > 60 ) {
                $errors[] = 'SEO title appears to be too long (recommended <=60 characters).';
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
                        'compare' => '='
                    )
                ),
                'posts_per_page' => 1,
                'fields' => 'ids'
            ) );
            if ( ! empty( $found ) ) {
                $used_before = true;
            }
        }

        // Keyphrase density enforcement: different limits if used before
        if ( isset( $data['content'] ) && $focus !== '' ) {
            $plain_text = wp_strip_all_tags( $data['content'] );
            $occurrences = preg_match_all( '/\b' . preg_quote( $focus, '/' ) . '\b/i', $plain_text, $m );
            $max_allowed = $used_before ? 1 : 8;
            if ( $occurrences > $max_allowed ) {
                $errors[] = "Keyphrase appears too many times ({$occurrences}). Maximum allowed is {$max_allowed}.";
            }
        }

        // Subheading distribution: at least one H2/H3 should include keyphrase or a close synonym
        if ( isset( $data['content'] ) && $focus !== '' ) {
            $has_subheading_with_keyword = false;
            $synonyms = array( "children's entertainer", "kids' magician", 'party magician', 'children entertainer', 'family magician' );
            if ( preg_match_all( '#<h[23][^>]*>(.*?)</h[23]>#is', $data['content'], $hs ) ) {
                foreach ( $hs[1] as $htext ) {
                    $plain_h = trim( wp_strip_all_tags( $htext ) );
                    if ( stripos( $plain_h, $focus ) !== false ) {
                        $has_subheading_with_keyword = true;
                        break;
                    }
                    foreach ( $synonyms as $s ) {
                        if ( stripos( $plain_h, $s ) !== false ) {
                            $has_subheading_with_keyword = true;
                            break 2;
                        }
                    }
                }
            }
            if ( ! $has_subheading_with_keyword ) {
                $errors[] = 'At least one H2/H3 subheading should contain the focus keyphrase or a close synonym.';
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

        // Meta description length
        if ( isset( $data['meta_description'] ) ) {
            $meta_len = mb_strlen( wp_strip_all_tags( $data['meta_description'] ) );
            if ( $meta_len > 155 ) {
                $errors[] = 'Meta description exceeds 155 characters.';
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
            if ( $first_para !== '' && stripos( $first_para, $focus ) === false ) {
                $errors[] = 'First paragraph must include the focus keyword.';
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
                    if ( $words > 25 ) {
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
                if ( $pct_long > 15 ) {
                    $errors[] = 'Too many long sentences (more than 15% exceed 25 words).';
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

        // Truncate meta_description to 155 chars
        if ( isset( $data['meta_description'] ) ) {
            $meta = wp_strip_all_tags( $data['meta_description'] );
            if ( mb_strlen( $meta ) > 155 ) {
                $data['meta_description'] = mb_substr( $meta, 0, 152 ) . '...';
                $changed = true;
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
            // Truncate title to 60 chars for SEO
            if ( mb_strlen( $data['title'] ) > 60 ) {
                $data['title'] = mb_substr( $data['title'], 0, 57 ) . '...';
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
            $norm_focus = str_replace( array( '’', '‘' ), array("'", "'"), $focus );
            $regex = '/\b' . preg_quote( $norm_focus, '/' ) . '\b/i';
            $count = preg_match_all( $regex, $plain, $matches );
            // Determine max allowed based on prior usage
            $used_before = false;
            if ( $focus !== '' ) {
                $found = get_posts( array(
                    'post_type' => 'any',
                    'meta_query' => array(
                        array(
                            'key' => '_yoast_wpseo_focuskw',
                            'value' => $focus,
                            'compare' => '='
                        )
                    ),
                    'posts_per_page' => 1,
                    'fields' => 'ids'
                ) );
                if ( ! empty( $found ) ) {
                    $used_before = true;
                }
            }
            $max_allowed = $used_before ? 1 : 8;
            if ( $count > $max_allowed ) {
                $to_replace = $count - $max_allowed;
                $synonyms = array( "children's entertainer", "kids' magician", 'party magician', 'children entertainer', 'family magician' );
                $keep_first = $used_before ? 1 : 3;
                $idx = 0;
                $callback = function( $m ) use ( &$idx, $keep_first, &$to_replace, $synonyms ) {
                    $idx++;
                    if ( $idx <= $keep_first || $to_replace <= 0 ) {
                        return $m[0];
                    }
                    $rep = $synonyms[ $idx % count( $synonyms ) ];
                    $to_replace--;
                    return $rep;
                };
                $data['content'] = preg_replace_callback( $regex, $callback, $data['content'], $count );
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
            $synonyms = array( "children's entertainer", "kids' magician", 'party magician', 'children entertainer', 'family magician' );
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
            $anchors = array('birthday party ideas', 'kids entertainer tips', 'family entertainment', 'magic show packages');
            $urls = array(home_url('/birthday-party-ideas'), home_url('/kids-entertainer-tips'));
            $i = 0;
            while ( count( $existing ) < 2 ) {
                $anchor = $anchors[$i % count($anchors)];
                $url = $urls[$i % count($urls)];
                $existing[] = array( 'url' => $url, 'anchor' => $anchor );
                $i++;
            }
            $data['internal_links'] = $existing;
            $changed = true;
        }
        // Ensure at least one outbound link exists in content
        if ( isset( $data['content'] ) ) {
            $outbound_url = 'https://www.themagiccircle.co.uk/';
            $outbound_anchor = 'The Magic Circle';
            if ( strpos( $data['content'], $outbound_url ) === false ) {
                // Insert outbound link at the end of the first paragraph
                $data['content'] = preg_replace( '/(<p>.*?<\/p>)/i', '$1<p>For more on professional magicians, visit <a href="'.$outbound_url.'" target="_blank">'.$outbound_anchor.'</a>.</p>', $data['content'], 1 );
                $changed = true;
            }
        }
        // Improve sentence length and transition word ratio
        if ( isset( $data['content'] ) ) {
            $plain = wp_strip_all_tags( $data['content'] );
            $sentences = preg_split( '/(?<=[.?!])\s+(?=[A-Z0-9])/', $plain );
            $total_sentences = count($sentences);
            $long_sentences = 0;
            $transitions = array( 'However,', 'Moreover,', 'Therefore,', 'Additionally,', 'Furthermore,', 'Consequently,', 'Meanwhile,', 'Nevertheless,', 'Nonetheless,', 'Subsequently,' );
            $sent_with_transition = 0;
            foreach ($sentences as $idx => $s) {
                $words = str_word_count($s);
                if ($words > 25) {
                    // Try to split at comma or add a period
                    if (strpos($s, ',') !== false) {
                        $parts = explode(',', $s, 2);
                        $sentences[$idx] = trim($parts[0]) . '. ' . trim($parts[1]);
                    }
                    $long_sentences++;
                }
                foreach ($transitions as $t) {
                    if (stripos($s, strtolower($t)) !== false) {
                        $sent_with_transition++;
                        break;
                    }
                }
            }
            // If transition word ratio is low, prepend transitions to some sentences
            $pct = $total_sentences > 0 ? ($sent_with_transition / $total_sentences) * 100 : 0;
            if ($pct < 30) {
                $needed = ceil(0.3 * $total_sentences) - $sent_with_transition;
                for ($i = 0; $i < $total_sentences && $needed > 0; $i++) {
                    if (stripos($sentences[$i], ',') === false && stripos($sentences[$i], '.') !== false) {
                        $sentences[$i] = $transitions[$i % count($transitions)] . ' ' . $sentences[$i];
                        $needed--;
                    }
                }
            }
            // Rebuild content
            $new_content = '';
            foreach ($sentences as $s) {
                if (trim($s) !== '') {
                    $new_content .= '<p>' . trim($s) . '</p>';
                }
            }
            if ($new_content !== '' && $new_content !== $data['content']) {
                $data['content'] = $new_content;
                $changed = true;
            }
        }

        // Attempt to break up very long sentences (>30 words) by splitting at commas and improve transitions
        if ( isset( $data['content'] ) ) {
            $plain = wp_strip_all_tags( $data['content'] );
            $sentences = preg_split( '/(?<=[.?!])\s+(?=[A-Z0-9])/', $plain );
            $replacements = array();
            foreach ( $sentences as $s ) {
                $words = str_word_count( $s );
                if ( $words > 30 ) {
                    // try to split at the first comma
                    if ( strpos( $s, ',' ) !== false ) {
                        $parts = explode( ',', $s, 2 );
                        $replacements[] = trim( $parts[0] ) . '. ' . trim( $parts[1] );
                    }
                }
            }
            if ( ! empty( $replacements ) ) {
                // naive: replace long sentences occurrences in HTML content
                foreach ( $replacements as $r ) {
                    // replace the first occurrence of the original long chunk with the new shorter version
                    $data['content'] = preg_replace( '/(' . preg_quote( wp_kses_post( $r ), '/' ) . ')/', $r, $data['content'], 1 );
                }
                // also insert a transition sentence if transitions are low
                $plain2 = wp_strip_all_tags( $data['content'] );
                $transitions = array( 'Additionally,', 'Furthermore,', 'Moreover,', 'Consequently,' );
                if ( strpos( $plain2, $transitions[0] ) === false ) {
                    // insert after first paragraph
                    $data['content'] = preg_replace( '/<p>/', '<p>' . $transitions[0] . ' ', $data['content'], 1 );
                }
                $changed = true;
            }
            // Ensure transition words ratio: if still low, prepend transition words to some sentences
            $plain2 = wp_strip_all_tags( $data['content'] );
            $sentences2 = preg_split( '/(?<=[.?!])\s+(?=[A-Z0-9])/', $plain2 );
            $total_sentences = count( $sentences2 );
            $transitions = array( 'however', 'moreover', 'therefore', 'additionally', 'furthermore', 'consequently' );
            $sent_with_transition = 0;
            foreach ( $sentences2 as $s ) {
                foreach ( $transitions as $t ) {
                    if ( stripos( $s, $t ) !== false ) {
                        $sent_with_transition++;
                        break;
                    }
                }
            }
            $pct = $total_sentences > 0 ? ( $sent_with_transition / $total_sentences ) * 100 : 0;
            if ( $pct < 30 && $total_sentences > 0 ) {
                $needed = ceil( ( 0.3 * $total_sentences ) - $sent_with_transition );
                // Insert transition words at the start of the last N sentences
                $parts = preg_split( '/(\.|\?|!)/', $data['content'], -1, PREG_SPLIT_DELIM_CAPTURE );
                $i = 0;
                foreach ( $parts as $k => $p ) {
                    if ( $needed <= 0 ) {
                        break;
                    }
                    // find paragraph starts or sentence starts heuristically
                    if ( preg_match( '/^\s*<p>\s*/i', $p ) || preg_match( '/^\s*[A-Z]/', strip_tags( $p ) ) ) {
                        $insert = ucfirst( $transitions[ $i % count( $transitions ) ] ) . ', ';
                        $parts[$k] = preg_replace( '/^(\s*)(<p>\s*)?/i', "\\1\\2" . $insert, $p, 1 );
                        $needed--;
                        $i++;
                    }
                }
                $newcontent = implode( '', $parts );
                if ( $newcontent !== $data['content'] ) {
                    $data['content'] = $newcontent;
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
        $slug = sanitize_title( $title );

        $post_data = array(
            'post_title' => $title,
            'post_content' => $post_content,
            'post_name' => $slug,
            'post_status' => 'draft',
            'post_author' => get_current_user_id(),
            'post_type' => 'post',
            'meta_input' => array(
                '_acs_generated' => true,
                '_acs_generated_date' => current_time( 'mysql' ),
                '_acs_original_keywords' => $keywords,
            )
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
}
endif;
