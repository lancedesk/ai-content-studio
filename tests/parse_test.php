<?php
// Simple test harness for ACS_Content_Generator::parse_generated_content
// Stubs minimal WP functions used by the generator so we can run standalone.

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
    function wp_strip_all_tags( $str ) {
        return strip_tags( $str );
    }
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( $str ) {
        return is_string( $str ) ? trim( strip_tags( $str ) ) : $str;
    }
}

if ( ! function_exists( 'sanitize_title' ) ) {
    function sanitize_title( $title ) {
        $title = preg_replace('/[^A-Za-z0-9-]+/', '-', strtolower($title));
        return trim($title, '-');
    }
}

if ( ! defined( 'ACS_PLUGIN_PATH' ) ) {
    define( 'ACS_PLUGIN_PATH', __DIR__ . '/../' );
}

require_once __DIR__ . '/../generators/class-acs-content-generator.php';

$gen = new ACS_Content_Generator();

// Construct a noisy/broken provider output: control char + wrapper text + valid JSON object + trailing text
$inner = json_encode(array(
    'title' => 'AI for Small Businesses',
    'meta_description' => 'How AI content generation helps small businesses with SEO and automation.',
    'slug' => 'ai-for-small-businesses',
    'content' => '<p>AI helps small businesses scale content creation and improve SEO.</p>',
    'excerpt' => 'AI content generation helps small businesses automate content and improve SEO.',
    'focus_keyword' => 'AI',
    'secondary_keywords' => array('content generation', 'small business', 'SEO', 'automation'),
    'tags' => array('AI','small business'),
    'image_prompts' => array(array('prompt' => 'A small business owner using AI to create content', 'alt' => 'AI for small business - featured image')),
    'internal_links' => array(array('url' => 'https://example.com/related', 'anchor' => 'Related topics'), array('url' => 'https://example.com/resources', 'anchor' => 'Further reading'))
));

$broken = "Intro text before JSON." . chr(3) . " Some commentary\n" . $inner . "\nSome trailing commentary that the model sometimes adds.";

echo "TEST INPUT:\n" . $broken . "\n\n";

$result = $gen->parse_generated_content( $broken );

echo "PARSED RESULT:\n";
print_r( $result );

echo "\nCheck log file: " . __DIR__ . '/../generators/acs_content_debug.log' . "\n";

?>