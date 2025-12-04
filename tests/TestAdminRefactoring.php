<?php

/**
 * Test Admin Class Refactoring
 * 
 * Verifies that the admin class properly uses the new components
 */

require_once __DIR__ . '/bootstrap.php';

// Define plugin path constant
if (!defined('ACS_PLUGIN_PATH')) {
    define('ACS_PLUGIN_PATH', dirname(__DIR__) . '/');
}

// Mock ACS_Sanitizer
if (!class_exists('ACS_Sanitizer')) {
    class ACS_Sanitizer {
        public static function sanitize_prompt_input($input) {
            return $input;
        }
    }
}

class TestAdminRefactoring extends PHPUnit\Framework\TestCase {

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
            function wp_insert_post($post_data) { return 123; }
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
        // Ensure temp directory exists
        if (!is_dir(WP_CONTENT_DIR)) {
            mkdir(WP_CONTENT_DIR, 0777, true);
        }

        // Include required classes
        require_once ACS_PLUGIN_PATH . 'includes/class-acs-response-parser.php';
        require_once ACS_PLUGIN_PATH . 'includes/class-acs-metadata-mapper.php';
        require_once ACS_PLUGIN_PATH . 'includes/class-acs-generator-loader.php';
        require_once ACS_PLUGIN_PATH . 'admin/class-acs-admin.php';

        $this->admin = new ACS_Admin('ai-content-studio', '1.0.0');
    }

    /**
     * Test that parse_generated_content uses Response Parser
     */
    public function testParseGeneratedContentUsesResponseParser() {
        $json_content = '{"title": "Test Title", "content": "Test content", "meta_description": "Test description"}';
        
        // Use reflection to access private method
        $reflection = new ReflectionClass($this->admin);
        $method = $reflection->getMethod('parse_generated_content');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->admin, $json_content);
        
        $this->assertIsArray($result);
        $this->assertEquals('Test Title', $result['title']);
        $this->assertStringContainsString('Test content', $result['content']);
        $this->assertEquals('Test description', $result['meta_description']);
    }

    /**
     * Test that parse_generated_content handles WP_Error properly
     */
    public function testParseGeneratedContentHandlesError() {
        $invalid_content = 'This is not valid JSON content';
        
        // Use reflection to access private method
        $reflection = new ReflectionClass($this->admin);
        $method = $reflection->getMethod('parse_generated_content');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->admin, $invalid_content);
        
        // Should return WP_Error for invalid content
        $this->assertInstanceOf('WP_Error', $result);
    }

    /**
     * Test that create_wordpress_post uses Metadata Mapper
     */
    public function testCreateWordpressPostUsesMetadataMapper() {
        $content = array(
            'title' => 'Test Post Title',
            'content' => 'Test post content',
            'meta_description' => 'Test meta description',
            'focus_keyword' => 'test keyword'
        );
        
        // Use reflection to access private method
        $reflection = new ReflectionClass($this->admin);
        $method = $reflection->getMethod('create_wordpress_post');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->admin, $content, 'test,keywords');
        
        // Should return a post ID (mocked as 123)
        $this->assertEquals(123, $result);
    }

    /**
     * Test that Generator Loader is used instead of direct instantiation
     */
    public function testGeneratorLoaderUsage() {
        // Mock $_POST data for AJAX request
        $_POST = array(
            'nonce' => 'test_nonce',
            'topic' => 'Test Topic',
            'keywords' => 'test,keywords',
            'word_count' => 'medium'
        );

        // Mock ACS_Sanitizer - will be defined outside class if needed

        // This should use Generator Loader instead of direct instantiation
        // We expect it to fail gracefully if generator is not available
        try {
            $this->admin->ajax_generate_content();
            $this->fail('Expected exception was not thrown');
        } catch (Exception $e) {
            // Should get JSON error about generator or provider not being available
            $this->assertTrue(
                strpos($e->getMessage(), 'Content generator not available') !== false ||
                strpos($e->getMessage(), 'No AI provider is configured') !== false
            );
        }
    }
}