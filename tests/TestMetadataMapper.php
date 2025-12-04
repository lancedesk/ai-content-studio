<?php
/**
 * Property-based tests for ACS_Metadata_Mapper
 *
 * @package ACS
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';
require_once dirname( __DIR__ ) . '/includes/class-acs-metadata-mapper.php';

class TestMetadataMapper extends TestCase {

/**
 * Property 5: Meta Description Persistence
 * Feature: content-generation-fixes, Property 5: Meta Description Persistence
 * Validates: Requirements 2.1
 *
 * For any post created with generated content containing a meta_description field,
 * the value should be saved to the _yoast_wpseo_metadesc post meta field.
 */
public function test_property_meta_description_persistence() {
$mapper = new ACS_Metadata_Mapper();
$iterations = 100;

for ( $i = 0; $i < $iterations; $i++ ) {
// Create a test post
$post_id = acs_create_test_post();

// Generate random meta description
$meta_description = $this->generate_random_text( rand( 10, 50 ) );

// Create content array with meta_description
$content = array(
'title'            => $this->generate_random_text( rand( 5, 15 ) ),
'content'          => '<p>' . $this->generate_random_text( rand( 20, 100 ) ) . '</p>',
'meta_description' => $meta_description,
);

// Map to post meta
$result = $mapper->map_to_post_meta( $post_id, $content );

// Assert mapping succeeded
$this->assertTrue( $result, "Iteration {$i}: Mapping should succeed" );

// Assert meta_description was saved to Yoast field
$saved_meta = get_post_meta( $post_id, '_yoast_wpseo_metadesc', true );
$this->assertNotEmpty( $saved_meta, "Iteration {$i}: Meta description should be saved" );

// Verify the saved value matches (accounting for truncation and sanitization)
$expected = $mapper->truncate_meta_description( $meta_description );
$expected = $mapper->sanitize_meta_value( 'meta_description', $expected );
$this->assertEquals( $expected, $saved_meta, "Iteration {$i}: Saved meta description should match expected value" );
}
}

/**
 * Property 6: Focus Keyword Persistence
 * Feature: content-generation-fixes, Property 6: Focus Keyword Persistence
 * Validates: Requirements 2.2
 *
 * For any post created with generated content containing a focus_keyword field,
 * the value should be saved to the _yoast_wpseo_focuskw post meta field.
 */
public function test_property_focus_keyword_persistence() {
$mapper = new ACS_Metadata_Mapper();
$iterations = 100;

for ( $i = 0; $i < $iterations; $i++ ) {
// Create a test post
$post_id = acs_create_test_post();

// Generate random focus keyword
$focus_keyword = $this->generate_random_text( rand( 1, 5 ) );

// Create content array with focus_keyword
$content = array(
'title'         => $this->generate_random_text( rand( 5, 15 ) ),
'content'       => '<p>' . $this->generate_random_text( rand( 20, 100 ) ) . '</p>',
'focus_keyword' => $focus_keyword,
);

// Map to post meta
$result = $mapper->map_to_post_meta( $post_id, $content );

// Assert mapping succeeded
$this->assertTrue( $result, "Iteration {$i}: Mapping should succeed" );

// Assert focus_keyword was saved to Yoast field
$saved_keyword = get_post_meta( $post_id, '_yoast_wpseo_focuskw', true );
$this->assertNotEmpty( $saved_keyword, "Iteration {$i}: Focus keyword should be saved" );

// Verify the saved value matches (accounting for sanitization)
$expected = $mapper->sanitize_meta_value( 'focus_keyword', $focus_keyword );
$this->assertEquals( $expected, $saved_keyword, "Iteration {$i}: Saved focus keyword should match expected value" );
}
}

/**
 * Property 7: SEO Title Persistence
 * Feature: content-generation-fixes, Property 7: SEO Title Persistence
 * Validates: Requirements 2.3
 *
 * For any post created with generated content containing a title field,
 * the value should be saved to the _yoast_wpseo_title post meta field.
 */
public function test_property_seo_title_persistence() {
$mapper = new ACS_Metadata_Mapper();
$iterations = 100;

for ( $i = 0; $i < $iterations; $i++ ) {
// Create a test post
$post_id = acs_create_test_post();

// Generate random title
$title = $this->generate_random_text( rand( 5, 15 ) );

// Create content array with title
$content = array(
'title'   => $title,
'content' => '<p>' . $this->generate_random_text( rand( 20, 100 ) ) . '</p>',
);

// Map to post meta
$result = $mapper->map_to_post_meta( $post_id, $content );

// Assert mapping succeeded
$this->assertTrue( $result, "Iteration {$i}: Mapping should succeed" );

// Assert title was saved to Yoast SEO title field
$saved_title = get_post_meta( $post_id, '_yoast_wpseo_title', true );
$this->assertNotEmpty( $saved_title, "Iteration {$i}: SEO title should be saved" );

// Verify the saved value matches (accounting for sanitization)
$expected = $mapper->sanitize_meta_value( 'title', $title );
$this->assertEquals( $expected, $saved_title, "Iteration {$i}: Saved SEO title should match expected value" );
}
}

/**
 * Property 8: Metadata Sanitization
 * Feature: content-generation-fixes, Property 8: Metadata Sanitization
 * Validates: Requirements 2.5
 *
 * For any SEO metadata value being saved to post meta,
 * the value should be sanitized using appropriate WordPress sanitization functions.
 */
public function test_property_metadata_sanitization() {
$mapper = new ACS_Metadata_Mapper();
$iterations = 100;

for ( $i = 0; $i < $iterations; $i++ ) {
// Create a test post
$post_id = acs_create_test_post();

// Generate metadata with potentially unsafe characters
$dirty_title = $this->add_unsafe_characters( $this->generate_random_text( rand( 5, 15 ) ) );
$dirty_meta_desc = $this->add_unsafe_characters( $this->generate_random_text( rand( 10, 50 ) ) );
$dirty_keyword = $this->add_unsafe_characters( $this->generate_random_text( rand( 2, 5 ) ) );

// Create content array with dirty data
$content = array(
'title'            => $dirty_title,
'content'          => '<p>Content</p>',
'meta_description' => $dirty_meta_desc,
'focus_keyword'    => $dirty_keyword,
);

// Map to post meta
$result = $mapper->map_to_post_meta( $post_id, $content );

// Assert mapping succeeded
$this->assertTrue( $result, "Iteration {$i}: Mapping should succeed" );

// Assert all saved values are sanitized (no HTML tags)
$saved_title = get_post_meta( $post_id, '_yoast_wpseo_title', true );
$saved_meta = get_post_meta( $post_id, '_yoast_wpseo_metadesc', true );
$saved_keyword = get_post_meta( $post_id, '_yoast_wpseo_focuskw', true );

$this->assertStringNotContainsString( '<script>', $saved_title, "Iteration {$i}: Title should not contain script tags" );
$this->assertStringNotContainsString( '<script>', $saved_meta, "Iteration {$i}: Meta description should not contain script tags" );
$this->assertStringNotContainsString( '<script>', $saved_keyword, "Iteration {$i}: Focus keyword should not contain script tags" );

$this->assertStringNotContainsString( '<', $saved_title, "Iteration {$i}: Title should not contain HTML tags" );
$this->assertStringNotContainsString( '<', $saved_keyword, "Iteration {$i}: Focus keyword should not contain HTML tags" );

// Meta description uses sanitize_textarea_field which also strips tags
$this->assertStringNotContainsString( '<', $saved_meta, "Iteration {$i}: Meta description should not contain HTML tags" );
}
}

// ========== Helper Methods for Generating Test Data ==========

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
 * Add unsafe characters to text for sanitization testing.
 */
private function add_unsafe_characters( $text ) {
$unsafe_additions = array(
'<script>alert("xss")</script>' . $text,
$text . '<img src=x onerror=alert(1)>',
'<b>' . $text . '</b>',
'<strong>' . $text . '</strong>',
$text . ' <span>extra</span>',
'<div>' . $text . '</div>',
);

return $unsafe_additions[ array_rand( $unsafe_additions ) ];
}
}
