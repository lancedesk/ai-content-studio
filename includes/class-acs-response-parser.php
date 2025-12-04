<?php
/**
 * Response Parser for AI Content Studio
 *
 * Handles parsing and validation of AI provider responses.
 *
 * @package ACS
 */

if ( ! class_exists( 'ACS_Response_Parser' ) ) :

class ACS_Response_Parser {

	/**
	 * Parse AI provider response into structured array.
	 *
	 * Implements multi-stage parsing:
	 * 1. Try direct JSON decode
	 * 2. Try to extract JSON from mixed content
	 * 3. Try regex patterns for labeled fields
	 * 4. Validate structure
	 * 5. Sanitize all fields
	 * 6. Apply defaults for missing fields
	 *
	 * @param string $response Raw response from AI provider
	 * @return array|WP_Error Parsed content array or error
	 */
	public function parse( $response ) {
		if ( empty( $response ) || ! is_string( $response ) ) {
			error_log( '[ACS][JSON_PARSE] Invalid response: ' . ( empty( $response ) ? 'empty response' : 'non-string response' ) );
			return new WP_Error( 'invalid_response', 'Response must be a non-empty string' );
		}

		$data = null;

		// Stage 1: Try direct JSON decode
		$decoded = json_decode( $response, true );
		$json_error = json_last_error();
		
		if ( $json_error === JSON_ERROR_NONE && is_array( $decoded ) ) {
			$data = $decoded;
		} elseif ( $json_error !== JSON_ERROR_NONE ) {
			// Attempt to fix truncated JSON
			error_log( '[ACS][JSON_PARSE] JSON decode error: ' . json_last_error_msg() . '. Attempting to fix...' );
			$fixed_response = $this->attempt_json_fix( $response );
			if ( $fixed_response !== $response ) {
				$decoded = json_decode( $fixed_response, true );
				if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
					error_log( '[ACS][JSON_PARSE] Successfully repaired truncated JSON' );
					$data = $decoded;
				}
			}
		}

		// Stage 2: Try to extract JSON from mixed content
		if ( $data === null ) {
			$extracted_json = $this->extract_json_from_text( $response );
			if ( $extracted_json !== null ) {
				$decoded = json_decode( $extracted_json, true );
				if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
					$data = $decoded;
				}
			}
		}

		// Stage 3: Try regex patterns for labeled fields (fallback)
		if ( $data === null ) {
			$data = $this->parse_labeled_fields( $response );
		}

		// If still no data, return error
		if ( ! is_array( $data ) || empty( $data ) ) {
			error_log( '[ACS][JSON_PARSE] Failed to parse response. Raw response: ' . substr( $response, 0, 500 ) );
			return new WP_Error( 'parse_failed', 'Could not parse response into structured data', array( 'raw_response' => $response ) );
		}

		// Stage 4: Validate structure
		if ( ! $this->validate_structure( $data ) ) {
			error_log( '[ACS][JSON_PARSE] Response structure validation failed. Raw response: ' . substr( $response, 0, 500 ) );
			return new WP_Error( 'invalid_structure', 'Response missing required fields' );
		}

		// Stage 5: Sanitize all fields
		$data = $this->sanitize_fields( $data );

		// Stage 6: Apply defaults for missing optional fields
		$data = $this->apply_defaults( $data );

		return $data;
	}

	/**
	 * Attempt to fix truncated JSON by adding missing closing braces/brackets.
	 *
	 * @param string $json Potentially truncated JSON string
	 * @return string Fixed JSON or original if unfixable
	 */
	private function attempt_json_fix( $json ) {
		// First, try to fix pretty-printed JSON with newlines in string values
		// This is a common issue when AI returns formatted JSON
		$json = $this->fix_pretty_printed_json( $json );
		
		// Try decoding after pretty-print fix
		$test_decode = json_decode( $json, true );
		if ( json_last_error() === JSON_ERROR_NONE ) {
			error_log( '[ACS][JSON_PARSE] Successfully fixed pretty-printed JSON' );
			return $json;
		}
		
		// Clean up remaining control characters that might cause issues
		$json = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $json);
		
		// Try decoding after control character cleanup
		$test_decode = json_decode( $json, true );
		if ( json_last_error() === JSON_ERROR_NONE ) {
			error_log( '[ACS][JSON_PARSE] Successfully fixed control characters' );
			return $json;
		}
		
		// Count opening and closing braces/brackets
		$open_braces = substr_count( $json, '{' );
		$close_braces = substr_count( $json, '}' );
		$open_brackets = substr_count( $json, '[' );
		$close_brackets = substr_count( $json, ']' );
		
		// Check if we're inside a string value by looking at the last non-whitespace character
		$trimmed = rtrim( $json );
		$last_char = substr( $trimmed, -1 );
		
		// If the last character is not a closing brace/bracket/quote, we might be mid-string
		if ( ! in_array( $last_char, array( '}', ']', '"', '\'', ',' ) ) ) {
			// Check if we're in a string by counting unescaped quotes
			$in_string = false;
			$escape = false;
			for ( $i = 0; $i < strlen( $json ); $i++ ) {
				$char = $json[$i];
				if ( $escape ) {
					$escape = false;
					continue;
				}
				if ( $char === '\\' ) {
					$escape = true;
					continue;
				}
				if ( $char === '"' ) {
					$in_string = ! $in_string;
				}
			}
			
			// If we ended inside a string, close it
			if ( $in_string ) {
				$json .= '"';
			}
		}
		
		// Close any open arrays
		while ( $open_brackets > $close_brackets ) {
			$json .= ']';
			$close_brackets++;
		}
		
		// Close any open objects
		while ( $open_braces > $close_braces ) {
			$json .= '}';
			$close_braces++;
		}
		
		return $json;
	}

	/**
	 * Fix pretty-printed JSON that has newlines within string values.
	 * This handles cases where AI returns formatted JSON with literal newlines in strings.
	 *
	 * @param string $json Pretty-printed JSON string
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
	 * Extract JSON from text that may contain JSON embedded in other content.
	 *
	 * Looks for JSON objects or arrays within the text using various patterns.
	 *
	 * @param string $text Text that may contain JSON
	 * @return string|null Extracted JSON string or null if not found
	 */
	public function extract_json_from_text( $text ) {
		// Remove markdown code fences if present
		$text = preg_replace( '/```(?:json)?\s*(.*?)\s*```/s', '$1', $text );

		// Try to find JSON object pattern
		if ( preg_match( '/\{(?:[^{}]|(?R))*\}/s', $text, $matches ) ) {
			// Validate it's actually JSON
			$decoded = json_decode( $matches[0], true );
			if ( json_last_error() === JSON_ERROR_NONE ) {
				return $matches[0];
			}
		}

		// Try to find JSON array pattern
		if ( preg_match( '/\[(?:[^\[\]]|(?R))*\]/s', $text, $matches ) ) {
			$decoded = json_decode( $matches[0], true );
			if ( json_last_error() === JSON_ERROR_NONE ) {
				return $matches[0];
			}
		}

		return null;
	}

	/**
	 * Parse labeled fields using regex patterns (fallback method).
	 *
	 * @param string $content Raw content with labeled fields
	 * @return array Parsed data array
	 */
	private function parse_labeled_fields( $content ) {
		$result = array();

		// Try to parse common labeled blocks
		if ( preg_match( '/TITLE:\s*(.+?)(?=\n|$)/i', $content, $m ) ) {
			$result['title'] = trim( $m[1] );
		}
		if ( preg_match( '/META_DESCRIPTION:\s*(.+?)(?=\n|$)/i', $content, $m ) ) {
			$result['meta_description'] = trim( $m[1] );
		}
		if ( preg_match( '/FOCUS_KEYWORD:\s*(.+?)(?=\n|$)/i', $content, $m ) ) {
			$result['focus_keyword'] = trim( $m[1] );
		}
		if ( preg_match( '/CONTENT:\s*(.+?)(?=\n\s*(?:META_DESCRIPTION|FOCUS_KEYWORD|TITLE):|$)/is', $content, $m ) ) {
			$result['content'] = trim( $m[1] );
		}

		// If content still empty, use entire response as content
		if ( empty( $result['content'] ) ) {
			$result['content'] = trim( $content );
		}

		return $result;
	}

	/**
	 * Validate that the data structure contains required fields.
	 *
	 * @param array $data Data array to validate
	 * @return bool True if valid, false otherwise
	 */
	public function validate_structure( $data ) {
		if ( ! is_array( $data ) ) {
			return false;
		}

		// Required fields
		$required = array( 'title', 'content' );

		foreach ( $required as $field ) {
			if ( ! isset( $data[ $field ] ) || empty( $data[ $field ] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Sanitize all fields in the data array.
	 *
	 * @param array $data Data array to sanitize
	 * @return array Sanitized data array
	 */
	public function sanitize_fields( $data ) {
		if ( ! is_array( $data ) ) {
			return array();
		}

		$sanitized = array();

		// Sanitize title - strip all HTML and JSON artifacts
		if ( isset( $data['title'] ) ) {
			$title = $data['title'];
			// Strip HTML tags
			$title = wp_strip_all_tags( $title );
			// Remove JSON syntax characters
			$title = str_replace( array( '{', '}', '[', ']', '"', '\\' ), '', $title );
			// Clean up whitespace
			$title = trim( preg_replace( '/\s+/', ' ', $title ) );
			$sanitized['title'] = $title;
		}

		// Sanitize content - allow HTML but clean it
		if ( isset( $data['content'] ) ) {
			$sanitized['content'] = wp_kses_post( $data['content'] );
		}

		// Sanitize meta_description
		if ( isset( $data['meta_description'] ) ) {
			$sanitized['meta_description'] = sanitize_textarea_field( $data['meta_description'] );
		}

		// Sanitize focus_keyword
		if ( isset( $data['focus_keyword'] ) ) {
			$sanitized['focus_keyword'] = sanitize_text_field( $data['focus_keyword'] );
		}

		// Sanitize slug
		if ( isset( $data['slug'] ) ) {
			$sanitized['slug'] = sanitize_title( $data['slug'] );
		}

		// Sanitize excerpt
		if ( isset( $data['excerpt'] ) ) {
			$sanitized['excerpt'] = sanitize_textarea_field( $data['excerpt'] );
		}

		// Sanitize arrays
		if ( isset( $data['secondary_keywords'] ) ) {
			if ( is_array( $data['secondary_keywords'] ) ) {
				$sanitized['secondary_keywords'] = array_map( 'sanitize_text_field', $data['secondary_keywords'] );
			} elseif ( is_string( $data['secondary_keywords'] ) ) {
				$sanitized['secondary_keywords'] = array_map( 'trim', explode( ',', $data['secondary_keywords'] ) );
			}
		}

		if ( isset( $data['tags'] ) && is_array( $data['tags'] ) ) {
			$sanitized['tags'] = array_map( 'sanitize_text_field', $data['tags'] );
		}

		if ( isset( $data['image_prompts'] ) && is_array( $data['image_prompts'] ) ) {
			$sanitized['image_prompts'] = array();
			foreach ( $data['image_prompts'] as $prompt ) {
				if ( is_array( $prompt ) ) {
					$sanitized['image_prompts'][] = array(
						'prompt' => isset( $prompt['prompt'] ) ? sanitize_text_field( $prompt['prompt'] ) : '',
						'alt'    => isset( $prompt['alt'] ) ? sanitize_text_field( $prompt['alt'] ) : '',
					);
				}
			}
		}

		if ( isset( $data['internal_links'] ) && is_array( $data['internal_links'] ) ) {
			$sanitized['internal_links'] = array();
			foreach ( $data['internal_links'] as $link ) {
				if ( is_array( $link ) ) {
					$sanitized['internal_links'][] = array(
						'url'    => isset( $link['url'] ) ? esc_url_raw( $link['url'] ) : '',
						'anchor' => isset( $link['anchor'] ) ? sanitize_text_field( $link['anchor'] ) : '',
					);
				}
			}
		}

		// Pass through other fields that don't need special sanitization
		$passthrough = array( 'provider', 'acs_validation_report', 'acs_humanization_needed' );
		foreach ( $passthrough as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$sanitized[ $field ] = $data[ $field ];
			}
		}

		return $sanitized;
	}

	/**
	 * Apply default values for missing optional fields.
	 *
	 * @param array $data Data array
	 * @return array Data array with defaults applied
	 */
	public function apply_defaults( $data ) {
		if ( ! is_array( $data ) ) {
			return array();
		}

		// Apply defaults for optional fields
		$defaults = array(
			'meta_description'   => '',
			'focus_keyword'      => '',
			'slug'               => isset( $data['title'] ) ? sanitize_title( $data['title'] ) : '',
			'excerpt'            => '',
			'secondary_keywords' => array(),
			'tags'               => array(),
			'image_prompts'      => array(),
			'internal_links'     => array(),
		);

		foreach ( $defaults as $key => $default_value ) {
			if ( ! isset( $data[ $key ] ) || $data[ $key ] === '' || $data[ $key ] === array() ) {
				$data[ $key ] = $default_value;
			}
		}

		return $data;
	}
}

endif;
