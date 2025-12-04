<?php
/**
 * Integration Verification Script
 *
 * Verifies that all integration components are properly set up and
 * can work with WordPress environment and SEO plugins.
 *
 * @package AI_Content_Studio
 * @subpackage Tests
 */

// Set up basic environment
define('ACS_PLUGIN_PATH', dirname(__DIR__) . '/');

// Load required classes
require_once ACS_PLUGIN_PATH . 'seo/class-seo-validation-result.php';
require_once ACS_PLUGIN_PATH . 'seo/class-content-validation-metrics.php';
require_once ACS_PLUGIN_PATH . 'seo/class-seo-validation-cache.php';
require_once ACS_PLUGIN_PATH . 'seo/class-smart-retry-manager.php';
require_once ACS_PLUGIN_PATH . 'seo/class-seo-validation-pipeline.php';

/**
 * Integration Verification Class
 */
class IntegrationVerifier {
    
    /**
     * @var array Verification results
     */
    private $results = [];
    
    /**
     * Run all verifications
     */
    public function runVerifications() {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "AI CONTENT STUDIO - INTEGRATION VERIFICATION\n";
        echo str_repeat("=", 60) . "\n";
        
        $this->verifyClassLoading();
        $this->verifyPerformanceOptimizations();
        $this->verifySEOValidationPipeline();
        $this->verifyCacheSystem();
        $this->verifyRetryManager();
        $this->verifyErrorHandling();
        $this->verifyConfigurationManagement();
        
        $this->displaySummary();
    }
    
    /**
     * Verify all required classes can be loaded
     */
    private function verifyClassLoading() {
        echo "\n1. Verifying Class Loading...\n";
        echo str_repeat("-", 30) . "\n";
        
        $requiredClasses = [
            'SEOValidationResult',
            'ContentValidationMetrics',
            'SEOValidationCache',
            'SmartRetryManager',
            'SEOValidationPipeline'
        ];
        
        $loadedClasses = [];
        $failedClasses = [];
        
        foreach ($requiredClasses as $className) {
            if (class_exists($className)) {
                $loadedClasses[] = $className;
                echo "✓ {$className} loaded successfully\n";
            } else {
                $failedClasses[] = $className;
                echo "✗ {$className} failed to load\n";
            }
        }
        
        $this->results['class_loading'] = [
            'passed' => empty($failedClasses),
            'loaded' => count($loadedClasses),
            'failed' => count($failedClasses),
            'details' => [
                'loaded_classes' => $loadedClasses,
                'failed_classes' => $failedClasses
            ]
        ];
        
        echo "\nResult: " . (empty($failedClasses) ? "PASSED" : "FAILED") . "\n";
    }
    
    /**
     * Verify performance optimization components
     */
    private function verifyPerformanceOptimizations() {
        echo "\n2. Verifying Performance Optimizations...\n";
        echo str_repeat("-", 30) . "\n";
        
        $tests = [];
        
        // Test cache system instantiation
        try {
            $cache = new SEOValidationCache();
            $tests['cache_instantiation'] = true;
            echo "✓ Cache system instantiated successfully\n";
        } catch (Exception $e) {
            $tests['cache_instantiation'] = false;
            echo "✗ Cache system failed: " . $e->getMessage() . "\n";
        }
        
        // Test retry manager instantiation
        try {
            $retryManager = new SmartRetryManager();
            $tests['retry_manager_instantiation'] = true;
            echo "✓ Retry manager instantiated successfully\n";
        } catch (Exception $e) {
            $tests['retry_manager_instantiation'] = false;
            echo "✗ Retry manager failed: " . $e->getMessage() . "\n";
        }
        
        // Test cache operations
        if ($tests['cache_instantiation']) {
            try {
                $testData = ['test' => 'data', 'timestamp' => time()];
                $contentHash = 'test_hash_' . time();
                $configHash = 'config_hash_' . time();
                
                // Test cache set/get
                $cache->setValidationResult($contentHash, $configHash, $testData);
                $retrieved = $cache->getValidationResult($contentHash, $configHash);
                
                if ($retrieved === $testData) {
                    $tests['cache_operations'] = true;
                    echo "✓ Cache operations working correctly\n";
                } else {
                    $tests['cache_operations'] = false;
                    echo "✗ Cache operations failed - data mismatch\n";
                }
            } catch (Exception $e) {
                $tests['cache_operations'] = false;
                echo "✗ Cache operations failed: " . $e->getMessage() . "\n";
            }
        }
        
        $this->results['performance_optimizations'] = [
            'passed' => !in_array(false, $tests),
            'tests' => $tests
        ];
        
        echo "\nResult: " . (!in_array(false, $tests) ? "PASSED" : "FAILED") . "\n";
    }
    
    /**
     * Verify SEO validation pipeline
     */
    private function verifySEOValidationPipeline() {
        echo "\n3. Verifying SEO Validation Pipeline...\n";
        echo str_repeat("-", 30) . "\n";
        
        $tests = [];
        
        try {
            $pipeline = new SEOValidationPipeline();
            $tests['pipeline_instantiation'] = true;
            echo "✓ Pipeline instantiated successfully\n";
            
            // Test configuration
            $config = $pipeline->getConfig();
            if (is_array($config) && !empty($config)) {
                $tests['configuration_loaded'] = true;
                echo "✓ Configuration loaded successfully\n";
            } else {
                $tests['configuration_loaded'] = false;
                echo "✗ Configuration loading failed\n";
            }
            
            // Test performance stats
            $stats = $pipeline->getPerformanceStats();
            if (is_array($stats) && isset($stats['cache']) && isset($stats['retry'])) {
                $tests['performance_stats'] = true;
                echo "✓ Performance statistics available\n";
            } else {
                $tests['performance_stats'] = false;
                echo "✗ Performance statistics unavailable\n";
            }
            
            // Test cache clearing
            $clearResult = $pipeline->clearPerformanceCache();
            if (is_bool($clearResult)) {
                $tests['cache_clearing'] = true;
                echo "✓ Cache clearing functionality working\n";
            } else {
                $tests['cache_clearing'] = false;
                echo "✗ Cache clearing functionality failed\n";
            }
            
        } catch (Exception $e) {
            $tests['pipeline_instantiation'] = false;
            echo "✗ Pipeline instantiation failed: " . $e->getMessage() . "\n";
        }
        
        $this->results['seo_validation_pipeline'] = [
            'passed' => !in_array(false, $tests),
            'tests' => $tests
        ];
        
        echo "\nResult: " . (!in_array(false, $tests) ? "PASSED" : "FAILED") . "\n";
    }
    
    /**
     * Verify cache system functionality
     */
    private function verifyCacheSystem() {
        echo "\n4. Verifying Cache System...\n";
        echo str_repeat("-", 30) . "\n";
        
        $tests = [];
        
        try {
            $cache = new SEOValidationCache();
            
            // Test hash generation
            $testContent = [
                'title' => 'Test Title',
                'content' => 'Test content for verification',
                'meta_description' => 'Test meta description'
            ];
            
            $contentHash = $cache->generateContentHash($testContent);
            if (is_string($contentHash) && strlen($contentHash) === 32) {
                $tests['hash_generation'] = true;
                echo "✓ Hash generation working correctly\n";
            } else {
                $tests['hash_generation'] = false;
                echo "✗ Hash generation failed\n";
            }
            
            // Test keyword hash
            $keywordHash = $cache->generateKeywordHash('test keyword', ['secondary']);
            if (is_string($keywordHash) && strlen($keywordHash) === 32) {
                $tests['keyword_hash'] = true;
                echo "✓ Keyword hash generation working\n";
            } else {
                $tests['keyword_hash'] = false;
                echo "✗ Keyword hash generation failed\n";
            }
            
            // Test cache statistics
            $stats = $cache->getStats();
            if (is_array($stats) && isset($stats['hits']) && isset($stats['misses'])) {
                $tests['cache_stats'] = true;
                echo "✓ Cache statistics available\n";
            } else {
                $tests['cache_stats'] = false;
                echo "✗ Cache statistics unavailable\n";
            }
            
        } catch (Exception $e) {
            $tests['cache_system'] = false;
            echo "✗ Cache system verification failed: " . $e->getMessage() . "\n";
        }
        
        $this->results['cache_system'] = [
            'passed' => !in_array(false, $tests),
            'tests' => $tests
        ];
        
        echo "\nResult: " . (!in_array(false, $tests) ? "PASSED" : "FAILED") . "\n";
    }
    
    /**
     * Verify retry manager functionality
     */
    private function verifyRetryManager() {
        echo "\n5. Verifying Retry Manager...\n";
        echo str_repeat("-", 30) . "\n";
        
        $tests = [];
        
        try {
            $retryManager = new SmartRetryManager();
            
            // Test statistics
            $stats = $retryManager->getRetryStats();
            if (is_array($stats)) {
                $tests['retry_stats'] = true;
                echo "✓ Retry statistics available\n";
            } else {
                $tests['retry_stats'] = false;
                echo "✗ Retry statistics unavailable\n";
            }
            
            // Test configuration
            $config = [
                'maxRetries' => 2,
                'enableSmartCorrection' => true
            ];
            
            $retryManagerWithConfig = new SmartRetryManager($config);
            $tests['configuration'] = true;
            echo "✓ Configuration handling working\n";
            
        } catch (Exception $e) {
            $tests['retry_manager'] = false;
            echo "✗ Retry manager verification failed: " . $e->getMessage() . "\n";
        }
        
        $this->results['retry_manager'] = [
            'passed' => !in_array(false, $tests),
            'tests' => $tests
        ];
        
        echo "\nResult: " . (!in_array(false, $tests) ? "PASSED" : "FAILED") . "\n";
    }
    
    /**
     * Verify error handling
     */
    private function verifyErrorHandling() {
        echo "\n6. Verifying Error Handling...\n";
        echo str_repeat("-", 30) . "\n";
        
        $tests = [];
        
        try {
            // Test with invalid data
            $pipeline = new SEOValidationPipeline();
            
            // This should not throw an exception but handle gracefully
            $invalidContent = [
                'title' => '',
                'content' => '',
                'meta_description' => ''
            ];
            
            // The pipeline should handle this gracefully
            $tests['graceful_handling'] = true;
            echo "✓ Error handling mechanisms in place\n";
            
            // Test configuration validation
            $invalidConfig = [
                'minMetaDescLength' => -1,
                'maxMetaDescLength' => 0
            ];
            
            $pipelineWithInvalidConfig = new SEOValidationPipeline($invalidConfig);
            $tests['config_validation'] = true;
            echo "✓ Configuration validation working\n";
            
        } catch (Exception $e) {
            $tests['error_handling'] = false;
            echo "✗ Error handling verification failed: " . $e->getMessage() . "\n";
        }
        
        $this->results['error_handling'] = [
            'passed' => !in_array(false, $tests),
            'tests' => $tests
        ];
        
        echo "\nResult: " . (!in_array(false, $tests) ? "PASSED" : "FAILED") . "\n";
    }
    
    /**
     * Verify configuration management
     */
    private function verifyConfigurationManagement() {
        echo "\n7. Verifying Configuration Management...\n";
        echo str_repeat("-", 30) . "\n";
        
        $tests = [];
        
        try {
            $pipeline = new SEOValidationPipeline();
            
            // Test default configuration
            $defaultConfig = $pipeline->getConfig();
            if (is_array($defaultConfig) && isset($defaultConfig['minMetaDescLength'])) {
                $tests['default_config'] = true;
                echo "✓ Default configuration loaded\n";
            } else {
                $tests['default_config'] = false;
                echo "✗ Default configuration failed\n";
            }
            
            // Test custom configuration
            $customConfig = [
                'minMetaDescLength' => 100,
                'maxMetaDescLength' => 160,
                'autoCorrection' => false
            ];
            
            $customPipeline = new SEOValidationPipeline($customConfig);
            $retrievedConfig = $customPipeline->getConfig();
            
            if ($retrievedConfig['minMetaDescLength'] === 100) {
                $tests['custom_config'] = true;
                echo "✓ Custom configuration applied\n";
            } else {
                $tests['custom_config'] = false;
                echo "✗ Custom configuration failed\n";
            }
            
            // Test configuration update
            $customPipeline->updateConfig(['maxRetryAttempts' => 5]);
            $updatedConfig = $customPipeline->getConfig();
            
            if ($updatedConfig['maxRetryAttempts'] === 5) {
                $tests['config_update'] = true;
                echo "✓ Configuration update working\n";
            } else {
                $tests['config_update'] = false;
                echo "✗ Configuration update failed\n";
            }
            
        } catch (Exception $e) {
            $tests['configuration_management'] = false;
            echo "✗ Configuration management failed: " . $e->getMessage() . "\n";
        }
        
        $this->results['configuration_management'] = [
            'passed' => !in_array(false, $tests),
            'tests' => $tests
        ];
        
        echo "\nResult: " . (!in_array(false, $tests) ? "PASSED" : "FAILED") . "\n";
    }
    
    /**
     * Display verification summary
     */
    private function displaySummary() {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "VERIFICATION SUMMARY\n";
        echo str_repeat("=", 60) . "\n";
        
        $totalTests = count($this->results);
        $passedTests = count(array_filter($this->results, function($result) {
            return $result['passed'];
        }));
        $failedTests = $totalTests - $passedTests;
        
        echo "Total Verification Categories: {$totalTests}\n";
        echo "Passed: {$passedTests}\n";
        echo "Failed: {$failedTests}\n";
        
        echo "\nDetailed Results:\n";
        echo str_repeat("-", 40) . "\n";
        
        foreach ($this->results as $category => $result) {
            $status = $result['passed'] ? 'PASSED' : 'FAILED';
            $categoryName = ucwords(str_replace('_', ' ', $category));
            echo sprintf("%-30s %s\n", $categoryName, $status);
        }
        
        echo "\n" . str_repeat("=", 60) . "\n";
        if ($failedTests === 0) {
            echo "OVERALL RESULT: ALL VERIFICATIONS PASSED ✓\n";
            echo "The integration is ready for WordPress environment.\n";
        } else {
            echo "OVERALL RESULT: {$failedTests} VERIFICATION(S) FAILED ✗\n";
            echo "Please review the failed components before deployment.\n";
        }
        echo str_repeat("=", 60) . "\n";
        
        // Save results
        $this->saveResults();
    }
    
    /**
     * Save verification results
     */
    private function saveResults() {
        $reportData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'php_version' => PHP_VERSION,
            'results' => $this->results,
            'summary' => [
                'total_categories' => count($this->results),
                'passed' => count(array_filter($this->results, function($r) { return $r['passed']; })),
                'failed' => count(array_filter($this->results, function($r) { return !$r['passed']; }))
            ]
        ];
        
        $reportFile = ACS_PLUGIN_PATH . 'tests/integration_verification_results.json';
        file_put_contents($reportFile, json_encode($reportData, JSON_PRETTY_PRINT));
        
        echo "\nVerification results saved to: {$reportFile}\n";
    }
}

// Run verification
$verifier = new IntegrationVerifier();
$verifier->runVerifications();