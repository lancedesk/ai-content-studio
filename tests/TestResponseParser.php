<?php
/**
 * Property-based tests for ACS_Response_Parser
 *
 * @package ACS
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';
require_once dirname( __DIR__ ) . '/includes/class-acs-response-parser.php';

class TestResponseParser extends TestCase {

	/**
	 * Property 1: JSON Parsing Correctness
	 * Feature: content-generation-fixes, Property 1: JSON Parsing Correctness
	 * Validates: Requirements 1.1
	 *
	 * For any valid JSON string returned by an AI provider, parsing should produce
	 * a structured array with all expected fields accessible.
	 */
	public function test_property_json_parsing_correctness() {
		$parser = new ACS_Response_Parser();
		$iterations = 100;

		for ( $i = 0; $i < $iterations; $i++ ) {
			// Generate random valid JSON with required fields
			$data = $this->generate_valid_content_data();
			$json = json_encode( $data );

			$result = $parser->parse( $json );

			// Assert parsing succeeded
			$this->assertNotInstanceOf( 'WP_Error', $result, "Iteration {$i}: Valid JSON should parse successfully" );
			$this->assertIsArray( $result, "Iteration {$i}: Result should be an array" );

			// Assert all expected fields are accessible
			$this->assertArrayHasKey( 'title', $result, "Iteration {$i}: Should have title field" );
			$this->assertArrayHasKey( 'content', $result, "Iteration {$i}: Should have content field" );

			// Verify field values are preserved (after sanitization)
			$this->assertNotEmpty( $result['title'], "Iteration {$i}: Title should not be empty" );
			$this->assertNotEmpty( $result['content'], "Iteration {$i}: Content should not be empty" );
		}
	}

	/**
	 * Property 2: JSON Extraction from Mixed Content
	 * Feature: content-generation-fixes, Property 2: JSON Extraction from Mixed Content
	 * Validates: Requirements 5.1
	 *
	 * For any response containing JSON embedded in text, the extraction function
	 * should successfully isolate and parse the JSON portion.
	 */
	public function test_property_json_extraction_from_mixed_content() {
		$parser = new ACS_Response_Parser();
		$iterations = 100;

		for ( $i = 0; $i < $iterations; $i++ ) {
			// Generate random valid JSON
			$data = $this->generate_valid_content_data();
			$json = json_encode( $data );

			// Embed JSON in random text
			$prefix = $this->generate_random_text( rand( 10, 50 ) );
			$suffix = $this->generate_random_text( rand( 10, 50 ) );
			$mixed_content = $prefix . "\n" . $json . "\n" . $suffix;

			$result = $parser->parse( $mixed_content );

			// Assert parsing succeeded
			$this->assertNotInstanceOf( 'WP_Error', $result, "Iteration {$i}: Should extract JSON from mixed content" );
			$this->assertIsArray( $result, "Iteration {$i}: Result should be an array" );

			// Assert required fields are present
			$this->assertArrayHasKey( 'title', $result, "Iteration {$i}: Should have title field" );
			$this->assertArrayHasKey( 'content', $result, "Iteration {$i}: Should have content field" );
		}
	}

	/**
	 * Property 3: Title Sanitization
	 * Feature: content-generation-fixes, Property 3: Title Sanitization
	 * Validates: Requirements 1.4
	 *
	 * For any title field containing HTML tags or JSON syntax characters,
	 * sanitization should remove all such characters while preserving the text content.
	 */
	public function test_property_title_sanitization() {
		$parser = new ACS_Response_Parser();
		$iterations = 100;

		for ( $i = 0; $i < $iterations; $i++ ) {
			// Generate title with HTML tags and JSON syntax
			$clean_text = $this->generate_random_text( rand( 5, 15 ) );
			$dirty_title = $this->add_html_and_json_artifacts( $clean_text );

			$data = array(
				'title'   => $dirty_title,
				'content' => '<p>Test content</p>',
			);
			$json = json_encode( $data );

			$result = $parser->parse( $json );

			// Assert parsing succeeded
			$this->assertNotInstanceOf( 'WP_Error', $result, "Iteration {$i}: Should parse successfully" );

			// Assert title is sanitized
			$title = $result['title'];
			$this->assertStringNotContainsString( '<', $title, "Iteration {$i}: Title should not contain <" );
			$this->assertStringNotContainsString( '>', $title, "Iteration {$i}: Title should not contain >" );
			$this->assertStringNotContainsString( '{', $title, "Iteration {$i}: Title should not contain {" );
			$this->assertStringNotContainsString( '}', $title, "Iteration {$i}: Title should not contain }" );
			$this->assertStringNotContainsString( '[', $title, "Iteration {$i}: Title should not contain [" );
			$this->assertStringNotContainsString( ']', $title, "Iteration {$i}: Title should not contain ]" );
			$this->assertStringNotContainsString( '"', $title, "Iteration {$i}: Title should not contain \"" );
			$this->assertStringNotContainsString( '\\', $title, "Iteration {$i}: Title should not contain \\" );

			// Assert text content is preserved (at least partially)
			$this->assertNotEmpty( $title, "Iteration {$i}: Title should not be empty after sanitization" );
		}
	}

	/**
	 * Property 13: Partial JSON Handling
	 * Feature: content-generation-fixes, Property 13: Partial JSON Handling
	 * Validates: Requirements 5.4
	 *
	 * For any response containing valid JSON with missing optional fields,
	 * the system should extract present fields and apply defaults for missing ones.
	 */
	public function test_property_partial_json_handling() {
		$parser = new ACS_Response_Parser();
		$iterations = 100;

		for ( $i = 0; $i < $iterations; $i++ ) {
			// Generate JSON with only required fields and random optional fields
			$data = array(
				'title'   => $this->generate_random_text( rand( 5, 15 ) ),
				'content' => '<p>' . $this->generate_random_text( rand( 20, 100 ) ) . '</p>',
			);

			// Randomly add some optional fields
			$optional_fields = array( 'meta_description', 'focus_keyword', 'slug', 'excerpt', 'tags' );
			$num_optional = rand( 0, count( $optional_fields ) );
			$selected_optional = array_rand( array_flip( $optional_fields ), max( 1, $num_optional ) );
			if ( ! is_array( $selected_optional ) ) {
				$selected_optional = array( $selected_optional );
			}

			foreach ( $selected_optional as $field ) {
				if ( $field === 'tags' ) {
					$data[ $field ] = array( 'tag1', 'tag2' );
				} else {
					$data[ $field ] = $this->generate_random_text( rand( 5, 20 ) );
				}
			}

			$json = json_encode( $data );
			$result = $parser->parse( $json );

			// Assert parsing succeeded
			$this->assertNotInstanceOf( 'WP_Error', $result, "Iteration {$i}: Should parse partial JSON successfully" );
			$this->assertIsArray( $result, "Iteration {$i}: Result should be an array" );

			// Assert required fields are present
			$this->assertArrayHasKey( 'title', $result, "Iteration {$i}: Should have title field" );
			$this->assertArrayHasKey( 'content', $result, "Iteration {$i}: Should have content field" );

			// Assert defaults are applied for missing optional fields
			$this->assertArrayHasKey( 'meta_description', $result, "Iteration {$i}: Should have meta_description (default or provided)" );
			$this->assertArrayHasKey( 'focus_keyword', $result, "Iteration {$i}: Should have focus_keyword (default or provided)" );
			$this->assertArrayHasKey( 'slug', $result, "Iteration {$i}: Should have slug (default or provided)" );
			$this->assertArrayHasKey( 'excerpt', $result, "Iteration {$i}: Should have excerpt (default or provided)" );
			$this->assertArrayHasKey( 'tags', $result, "Iteration {$i}: Should have tags (default or provided)" );

			// Verify present fields are preserved
			foreach ( $selected_optional as $field ) {
				if ( isset( $data[ $field ] ) ) {
					$this->assertNotEmpty( $result[ $field ], "Iteration {$i}: Field {$field} should be preserved" );
				}
			}
		}
	}

	// ========== Helper Methods for Generating Test Data ==========

	/**
	 * Generate valid content data with all required fields.
	 */
	private function generate_valid_content_data() {
		return array(
			'title'              => $this->generate_random_text( rand( 5, 15 ) ),
			'content'            => '<p>' . $this->generate_random_text( rand( 50, 200 ) ) . '</p>',
			'meta_description'   => $this->generate_random_text( rand( 20, 50 ) ),
			'focus_keyword'      => $this->generate_random_text( rand( 2, 5 ) ),
			'slug'               => sanitize_title( $this->generate_random_text( rand( 3, 10 ) ) ),
			'excerpt'            => $this->generate_random_text( rand( 10, 30 ) ),
			'secondary_keywords' => array( $this->generate_random_text( 3 ), $this->generate_random_text( 3 ) ),
			'tags'               => array( $this->generate_random_text( 2 ), $this->generate_random_text( 2 ) ),
			'image_prompts'      => array(
				array(
					'prompt' => $this->generate_random_text( rand( 5, 15 ) ),
					'alt'    => $this->generate_random_text( rand( 5, 15 ) ),
				),
			),
			'internal_links'     => array(
				array(
					'url'    => 'https://example.com/' . sanitize_title( $this->generate_random_text( 3 ) ),
					'anchor' => $this->generate_random_text( rand( 3, 8 ) ),
				),
			),
		);
	}

	/**
	 * Generate random text with specified number of words.
	 */
	private function generate_random_text( $word_count ) {
		$words = array(
			'lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipiscing', 'elit',
			'sed', 'do', 'eiusmod', 'tempor', 'incididunt', 'ut', 'labore', 'et', 'dolore',
			'magna', 'aliqua', 'enim', 'ad', 'minim', 'veniam', 'quis', 'nostrud',
			'exercitation', 'ullamco', 'laboris', 'nisi', 'aliquip', 'ex', 'ea', 'commodo',
			'consequat', 'duis', 'aute', 'irure', 'in', 'reprehenderit', 'voluptate',
			'velit', 'esse', 'cillum', 'fugiat', 'nulla', 'pariatur', 'excepteur', 'sint',
			'occaecat', 'cupidatat', 'non', 'proident', 'sunt', 'culpa', 'qui', 'officia',
			'deserunt', 'mollit', 'anim', 'id', 'est', 'laborum',
		);

		$result = array();
		for ( $i = 0; $i < $word_count; $i++ ) {
			$result[] = $words[ array_rand( $words ) ];
		}

		return ucfirst( implode( ' ', $result ) );
	}

	/**
	 * Add HTML tags and JSON syntax characters to text.
	 */
	private function add_html_and_json_artifacts( $text ) {
		$artifacts = array(
			'<b>' . $text . '</b>',
			'<strong>' . $text . '</strong>',
			'<h1>' . $text . '</h1>',
			'{' . $text . '}',
			'[' . $text . ']',
			'"' . $text . '"',
			'\\' . $text,
			$text . ' <span>extra</span>',
			'{"title": "' . $text . '"}',
		);

		return $artifacts[ array_rand( $artifacts ) ];
	}
}
