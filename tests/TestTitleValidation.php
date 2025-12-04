<?php

/**
 * Test Title Validation Property
 * 
 * Property-based test for title validation before post creation
 */

require_once __DIR__ . '/bootstrap.php';

// Define plugin path constant
if (!defined('ACS_PLUGIN_PATH')) {
    define('ACS_PLUGIN_PATH', dirname(__DIR__) . '/');
}

/**
 * Property 4: Title Validation
 * Feature: content-generation-fixes, Property 4: Title Validation
 * 
 * Validates: Requirements 1.5
 *
 * For any parsed content array used for post creation, the title field should be non-empty and contain no JSON syntax characters
 */
class TestTitleValidation extends PHPUnit\Framework\TestCase {

    private $admin;

    protected function setUp(): void {
        // Mock WordPress functions
        if (!function_exists('wp_verify_nonce')) {
            function wp_verify_nonce($nonce, $action) { return true; }
        }
        if (!function_exists('current_user_can')) {
            function current_user_can($capability) { return true; }
        }
        if (!function_exists('wp_send_json_error')) {
            function wp_send_json_error($data) { throw new Exception('JSON Error: ' . $data); }
        }
        if (!function_exists('wp_send_json_success')) {
            function wp_send_json_success($data) { return $data; }
        }
        if (!function_exists('error_log')) {
            function error_log($message) { echo "LOG: $message\n"; }
        }
        if (!function_exists('get_current_user_id')) {
            function get_current_user_id() { return 1; }
        }
        if (!function_exists('wp_insert_post')) {
            function wp_insert_post($post_data) { 
                // Store the cleaned title for validation
                global $last_post_title;
                $last_post_title = isset($post_data['post_title']) ? $post_data['post_title'] : '';
                return 123; 
            }
        }
        if (!function_exists('is_wp_error')) {
            function is_wp_error($thing) { return $thing instanceof WP_Error; }
        }
        if (!function_exists('update_post_meta')) {
            function update_post_meta($post_id, $key, $value) { return true; }
        }
        if (!function_exists('sanitize_title')) {
            function sanitize_title($title) { return strtolower(str_replace(' ', '-', $title)); }
        }
        if (!function_exists('current_time')) {
            function current_time($type) { return ($type === 'mysql') ? date('Y-m-d H:i:s') : time(); }
        }
        if (!defined('WP_CONTENT_DIR')) {
            define('WP_CONTENT_DIR', sys_get_temp_dir());
        }

        // Include required classes
        require_once ACS_PLUGIN_PATH . 'includes/class-acs-response-parser.php';
        require_once ACS_PLUGIN_PATH . 'includes/class-acs-metadata-mapper.php';
        require_once ACS_PLUGIN_PATH . 'admin/class-acs-admin.php';

        $this->admin = new ACS_Admin('ai-content-studio', '1.0.0');
    }

    /**
     * Generate random content arrays with various title formats
     */
    private function generateContentArray() {
        $generators = [
            // Valid titles
            function() {
                return [
                    'title' => 'Valid Post Title ' . rand(1, 1000),
                    'content' => 'Some valid content',
                    'meta_description' => 'Valid meta description'
                ];
            },
            // Titles with JSON syntax characters (should be rejected)
            function() {
                $jsonChars = ['{', '}', '[', ']', '"'];
                $char = $jsonChars[array_rand($jsonChars)];
                return [
                    'title' => 'Invalid Title ' . $char . ' with JSON',
                    'content' => 'Some content',
                    'meta_description' => 'Meta description'
                ];
            },
            // Empty titles (should be rejected)
            function() {
                return [
                    'title' => '',
                    'content' => 'Some content',
                    'meta_description' => 'Meta description'
                ];
            },
            // Whitespace-only titles (should be rejected)
            function() {
                return [
                    'title' => '   ',
                    'content' => 'Some content',
                    'meta_description' => 'Meta description'
                ];
            }
        ];

        $generator = $generators[array_rand($generators)];
        return $generator();
    }

    /**
     * Property test: Title validation before post creation
     * 
     * Tests that titles are properly validated before creating posts
     */
    public function testTitleValidationProperty() {
        global $last_post_title;
        $iterations = 100;
        $validTitleCount = 0;
        $invalidTitleCount = 0;

        for ($i = 0; $i < $iterations; $i++) {
            $content = $this->generateContentArray();
            
            // Use reflection to access private method
            $reflection = new ReflectionClass($this->admin);
            $method = $reflection->getMethod('create_wordpress_post');
            $method->setAccessible(true);
            
            $result = $method->invoke($this->admin, $content, 'test,keywords');
            
            $originalTitle = $content['title'];
            $cleanedTitle = $last_post_title;
            
            $originalHasJsonSyntax = (strpos($originalTitle, '{') !== false || strpos($originalTitle, '}') !== false || 
                                     strpos($originalTitle, '[') !== false || strpos($originalTitle, ']') !== false ||
                                     strpos($originalTitle, '"') !== false);
            $originalIsEmpty = empty(trim($originalTitle));
            
            // The cleaned title should never have JSON syntax
            $cleanedHasJsonSyntax = (strpos($cleanedTitle, '{') !== false || strpos($cleanedTitle, '}') !== false || 
                                    strpos($cleanedTitle, '[') !== false || strpos($cleanedTitle, ']') !== false ||
                                    strpos($cleanedTitle, '"') !== false);
            
            $this->assertFalse($cleanedHasJsonSyntax, "Cleaned title should not have JSON syntax: " . $cleanedTitle);
            $this->assertNotEmpty(trim($cleanedTitle), "Cleaned title should not be empty");
            
            if ($originalHasJsonSyntax || $originalIsEmpty) {
                $invalidTitleCount++;
            } else {
                $validTitleCount++;
            }
            
            // Post should always be created successfully with cleaned title
            $this->assertNotFalse($result, "Post should be created with cleaned title");
        }

        // Ensure we tested both valid and invalid cases
        $this->assertGreaterThan(0, $validTitleCount, "Should have tested some valid titles");
        $this->assertGreaterThan(0, $invalidTitleCount, "Should have tested some invalid titles");
    }

    /**
     * Test specific edge cases for title validation
     */
    public function testTitleValidationEdgeCases() {
        global $last_post_title;
        
        $testCases = [
            // JSON-like strings that should be cleaned
            ['title' => '{"title": "Test"}', 'expected_cleaned' => 'title: Test'],
            ['title' => '[Test Title]', 'expected_cleaned' => 'Test Title'],
            ['title' => 'Title with "quotes"', 'expected_cleaned' => 'Title with quotes'],
            ['title' => '{Test}', 'expected_cleaned' => 'Test'],
            
            // Valid titles that should remain unchanged
            ['title' => 'Normal Title', 'expected_cleaned' => 'Normal Title'],
            ['title' => 'Title with (parentheses)', 'expected_cleaned' => 'Title with (parentheses)'],
            ['title' => 'Title with - dashes', 'expected_cleaned' => 'Title with - dashes'],
            ['title' => 'Title123 with numbers', 'expected_cleaned' => 'Title123 with numbers'],
            
            // Empty titles should get default
            ['title' => '', 'expected_pattern' => '/^AI Generated Post - \d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/'],
            ['title' => '   ', 'expected_pattern' => '/^AI Generated Post - \d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/'],
        ];

        foreach ($testCases as $testCase) {
            $content = [
                'title' => $testCase['title'],
                'content' => 'Test content',
                'meta_description' => 'Test description'
            ];

            // Use reflection to access private method
            $reflection = new ReflectionClass($this->admin);
            $method = $reflection->getMethod('create_wordpress_post');
            $method->setAccessible(true);
            
            $result = $method->invoke($this->admin, $content, 'test');
            
            // Post should always be created
            $this->assertNotFalse($result, "Post should be created for title: " . $testCase['title']);
            
            // Check cleaned title
            if (isset($testCase['expected_cleaned'])) {
                $this->assertEquals($testCase['expected_cleaned'], $last_post_title, 
                    "Title should be cleaned properly: " . $testCase['title']);
            } elseif (isset($testCase['expected_pattern'])) {
                $this->assertMatchesRegularExpression($testCase['expected_pattern'], $last_post_title,
                    "Title should match default pattern for empty title: " . $testCase['title']);
            }
            
            // Cleaned title should never have JSON syntax
            $this->assertFalse(
                strpos($last_post_title, '{') !== false || 
                strpos($last_post_title, '}') !== false ||
                strpos($last_post_title, '[') !== false || 
                strpos($last_post_title, ']') !== false ||
                strpos($last_post_title, '"') !== false,
                "Cleaned title should not have JSON syntax: " . $last_post_title
            );
        }
    }
}