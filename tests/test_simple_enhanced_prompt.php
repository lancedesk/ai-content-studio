<?php
// Simple test for enhanced prompt engine with WordPress function stubs
echo "Starting enhanced prompt test...\n";

// WordPress function stubs
if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {
        return is_string($str) ? trim(strip_tags($str)) : $str;
    }
}

if (!function_exists('sanitize_title')) {
    function sanitize_title($title) {
        $title = preg_replace('/[^A-Za-z0-9-]+/', '-', strtolower($title));
        return trim($title, '-');
    }
}

if (!function_exists('wp_strip_all_tags')) {
    function wp_strip_all_tags($str) {
        return strip_tags($str);
    }
}

if (!defined('ABSPATH')) {
    define('ABSPATH', '/');
}

if (!defined('ACS_PLUGIN_PATH')) {
    define('ACS_PLUGIN_PATH', realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR);
}

echo "WordPress stubs loaded\n";
echo "ACS_PLUGIN_PATH: " . ACS_PLUGIN_PATH . "\n";

try {
    // Load required classes
    require_once ACS_PLUGIN_PATH . 'seo/class-seo-prompt-configuration.php';
    echo "SEO Configuration class loaded\n";
    
    require_once ACS_PLUGIN_PATH . 'seo/class-enhanced-prompt-engine.php';
    echo "Enhanced Prompt Engine class loaded\n";
    
    // Create SEO configuration
    $config = new SEOPromptConfiguration([
        'focusKeyword' => 'AI content generation',
        'targetWordCount' => 800
    ]);
    echo "SEO Configuration created\n";
    
    // Create enhanced prompt engine
    $promptEngine = new EnhancedPromptEngine($config);
    echo "Enhanced Prompt Engine created\n";
    
    // Generate a simple prompt
    $topic = 'Benefits of AI';
    $keywords = 'AI, benefits';
    $prompt = $promptEngine->buildSEOPrompt($topic, $keywords, 'medium');
    
    echo "Enhanced prompt generated successfully!\n";
    echo "Prompt length: " . strlen($prompt) . " characters\n";
    echo "First 200 characters: " . substr($prompt, 0, 200) . "...\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "Test completed.\n";
?>