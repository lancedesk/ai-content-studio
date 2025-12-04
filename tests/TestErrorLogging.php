<?php
/**
 * Test Error Logging Properties
 *
 * Tests error logging functionality throughout the content generation workflow.
 *
 * @package ACS
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';
require_once dirname( __DIR__ ) . '/includes/class-acs-response-parser.php';
require_once dirname( __DIR__ ) . '/includes/class-acs-generator-loader.php';
require_once dirname( __DIR__ ) . '/includes/class-acs-metadata-mapper.php';
require_once dirname( __DIR__ ) . '/admin/class-acs-admin.php';

class TestErrorLogging extends TestCase {

    private $original_debug_log;
    private $test_log_file;

    protected function setUp(): void {
        parent::setUp();
        
        // Set up test log file
        $this->test_log_file = dirname(__DIR__, 4) . '/test_debug.log';
        if (file_exists($this->test_log_file)) {
            unlink($this->test_log_file);
        }
        
        // Store original debug log setting
        $this->original_debug_log = ini_get('error_log');
        
        // Set error log to our test file
        ini_set('error_log', $this->test_log_file);
        
        // Enable debug logging
        if (!defined('WP_DEBUG_LOG')) {
            define('WP_DEBUG_LOG', true);
        }
    }

    protected function tearDown(): void {
        // Restore original debug log
        ini_set('error_log', $this->original_debug_log);
        
        // Clean up test log file
        if (file_exists($this->test_log_file)) {
            unlink($this->test_log_file);
        }
        
        parent::tearDown();
    }

    /**
     * Property 10: Error Logging on Generation Failure
     * **Feature: content-generation-fixes, Property 10: Error Logging on Generation Failure**
     * **Validates: Requirements 4.1**
     * 
     * For any content generation error, the system should write an error entry to the WordPress debug log
     */
    public function test_property_error_logging_on_generation_failure() {
        // Clear any existing log content
        if (file_exists($this->test_log_file)) {
            file_put_contents($this->test_log_file, '');
        }

        // Test with Response Parser - invalid JSON should trigger error logging
        
        $parser = new ACS_Response_Parser();
        
        // Generate various invalid responses that should trigger error logging
        $invalid_responses = [
            'invalid json {broken',
            '{"title": "Test", "content":}', // malformed JSON
            '', // empty response
            'plain text with no JSON structure',
            '{"incomplete": true', // incomplete JSON
        ];
        
        foreach ($invalid_responses as $invalid_response) {
            // Clear log before each test
            if (file_exists($this->test_log_file)) {
                file_put_contents($this->test_log_file, '');
            }
            
            $result = $parser->parse($invalid_response);
            
            // Should return WP_Error for invalid responses
            $this->assertInstanceOf('WP_Error', $result, 
                "Parser should return WP_Error for invalid response: " . substr($invalid_response, 0, 50));
            
            // Check that error was logged
            $this->assertTrue(file_exists($this->test_log_file), 
                "Debug log file should exist after parse error");
            
            $log_content = file_get_contents($this->test_log_file);
            $this->assertStringContainsString('[ACS][JSON_PARSE]', $log_content, 
                "Log should contain ACS JSON_PARSE error for response: " . substr($invalid_response, 0, 50));
        }
    }

    /**
     * Test Generator Loader error logging
     */
    public function test_generator_loader_error_logging() {
        // Clear log
        if (file_exists($this->test_log_file)) {
            file_put_contents($this->test_log_file, '');
        }

        // Reset singleton to force reload
        if (class_exists('ACS_Generator_Loader')) {
            ACS_Generator_Loader::reset_instance();
        }

        // Temporarily rename the generator file to simulate missing file
        $generator_file = dirname(__DIR__) . '/generators/class-acs-content-generator.php';
        $backup_file = $generator_file . '.backup';
        
        if (file_exists($generator_file)) {
            rename($generator_file, $backup_file);
        }



        $result = ACS_Generator_Loader::get_instance();
        
        // Should return WP_Error
        $this->assertInstanceOf('WP_Error', $result, 
            "Generator loader should return WP_Error when class file missing");
        
        // Check error logging
        $log_content = file_get_contents($this->test_log_file);
        $this->assertStringContainsString('[ACS][GENERATOR_LOADER]', $log_content, 
            "Log should contain ACS GENERATOR_LOADER error");

        // Restore the file
        if (file_exists($backup_file)) {
            rename($backup_file, $generator_file);
        }
        
        // Reset singleton
        ACS_Generator_Loader::reset_instance();
    }

    /**
     * Test Metadata Mapper error logging
     */
    public function test_metadata_mapper_error_logging() {
        // Clear log
        if (file_exists($this->test_log_file)) {
            file_put_contents($this->test_log_file, '');
        }
        
        $mapper = new ACS_Metadata_Mapper();
        
        // Test with invalid post ID - should trigger error logging
        $result = $mapper->map_to_post_meta(-1, ['meta_description' => 'test']);
        
        $this->assertFalse($result, "Mapper should return false for invalid post ID");
        
        // Check error logging
        $log_content = file_get_contents($this->test_log_file);
        $this->assertStringContainsString('[ACS][META_MAPPER]', $log_content, 
            "Log should contain ACS META_MAPPER error for invalid post ID");
    }

    /**
     * Test Admin class error logging integration
     */
    public function test_admin_error_logging_integration() {
        // Clear log
        if (file_exists($this->test_log_file)) {
            file_put_contents($this->test_log_file, '');
        }

        // Create admin instance
        $admin = new ACS_Admin('ai-content-studio', '1.0.0');
        
        // Test parse_generated_content with invalid content
        $reflection = new ReflectionClass($admin);
        $method = $reflection->getMethod('parse_generated_content');
        $method->setAccessible(true);
        
        $result = $method->invoke($admin, 'invalid json content {broken');
        
        // Should return WP_Error
        $this->assertInstanceOf('WP_Error', $result, 
            "Admin parse method should return WP_Error for invalid content");
        
        // Check that error was logged through the parser
        $log_content = file_get_contents($this->test_log_file);
        $this->assertStringContainsString('[ACS]', $log_content, 
            "Log should contain ACS error messages from admin class");
    }

    /**
     * Test log format consistency
     */
    public function test_log_format_consistency() {
        // Clear log
        if (file_exists($this->test_log_file)) {
            file_put_contents($this->test_log_file, '');
        }

        // Trigger various errors to test format consistency
        
        $parser = new ACS_Response_Parser();
        $parser->parse('invalid json');
        

        
        $mapper = new ACS_Metadata_Mapper();
        $mapper->map_to_post_meta(-1, []);
        
        // Check log format
        $log_content = file_get_contents($this->test_log_file);
        $log_lines = explode("\n", trim($log_content));
        
        foreach ($log_lines as $line) {
            if (!empty($line) && strpos($line, '[ACS]') !== false) {
                // Should follow format: [ACS][CATEGORY] Message: details
                $this->assertMatchesRegularExpression(
                    '/\[ACS\]\[[A-Z_]+\]/', 
                    $line, 
                    "Log line should follow [ACS][CATEGORY] format: " . $line
                );
            }
        }
    }
}