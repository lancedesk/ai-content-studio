<?php
// Standalone prompt builder for testing — avoids loading admin stubs.
if ( ! function_exists( 'sanitize_text_field' ) ) { function sanitize_text_field( $str ) { return is_string( $str ) ? trim( strip_tags( $str ) ) : $str; } }
if ( ! function_exists( 'sanitize_title' ) ) { function sanitize_title( $title ) { $title = preg_replace('/[^A-Za-z0-9-]+/', '-', strtolower($title)); return trim($title, '-'); } }
if ( ! function_exists( 'wp_strip_all_tags' ) ) { function wp_strip_all_tags( $str ) { return strip_tags( $str ); } }
if ( ! defined( 'ACS_PLUGIN_PATH' ) ) define( 'ACS_PLUGIN_PATH', realpath( __DIR__ . '/..' ) . DIRECTORY_SEPARATOR );
require_once ACS_PLUGIN_PATH . 'generators/class-acs-content-generator.php';
$gen = new ACS_Content_Generator();
$topic = 'The Benefits of AI Content Generation for Small Businesses';
$keywords = 'AI, content generation, small business, SEO, automation';
$word_count = 'medium';
$test_internal = array(
    array('title'=>'About Our Services','url'=>'https://localhost/localserver/about-our-services','excerpt'=>'Learn about our services'),
    array('title'=>'AI Tools We Use','url'=>'https://localhost/localserver/ai-tools','excerpt'=>'Tools and integrations'),
);
$prompt = $gen->build_prompt( $topic, $keywords, $word_count, $test_internal );
$logpath = dirname( __DIR__ ) . '/acs_prompt_debug.log';
file_put_contents( $logpath, date('Y-m-d H:i:s') . "\nPROMPT:\n" . $prompt . "\n---\n", FILE_APPEND | LOCK_EX );
echo "\n=== Prompt written to: " . $logpath . " ===\n" . $prompt . "\n";
?>