<?php
/**
 * Integration Test Runner
 *
 * Comprehensive test runner for WordPress integration tests including
 * Yoast SEO and RankMath compatibility verification.
 *
 * @package AI_Content_Studio
 * @subpackage Tests
 */

// Ensure we're running in WordPress environment
if (!defined('ABSPATH')) {
    // Try to load WordPress
    $wp_load_paths = [
        '../../../wp-load.php',
        '../../../../wp-load.php',
        '../../../../../wp-load.php'
    ];
    
    $wp_loaded = false;
    foreach ($wp_load_paths as $path) {
        if (file_exists(__DIR__ . '/' . $path)) {
            require_once __DIR__ . '/' . $path;
            $wp_loaded = true;
            break;
        }
    }
    
    if (!$wp_loaded) {
        die("Error: Could not load WordPress. Please run this script from within WordPress environment.\n");
    }
}

// Load test dependencies
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/TestWordPressIntegration.php';

/**
 * Integration Test Runner Class
 */
class IntegrationTestRunner {
    
    /**
     * @var array Test results
     */
    private $results = [];
    
    /**
     * @var float Start time
     */
    private $startTime;
    
    /**
     * @var array Environment info
     */
    private $environment;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->startTime = microtime(true);
        $this->environment = $this->collectEnvironmentInfo();
    }
    
    /**
     * Run all integration tests
     */
    public function runAllTests() {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "AI CONTENT STUDIO - INTEGRATION TEST SUITE\n";
        echo str_repeat("=", 80) . "\n";
        
        $this->displayEnvironmentInfo();
        
        echo "\nRunning Integration Tests...\n";
        echo str_repeat("-", 80) . "\n";
        
        // Initialize test suite
        $testSuite = new TestWordPressIntegration();
        $testSuite->setUp();
        
        // Run individual tests
        $tests = [
            'testWordPressCoreIntegration' => 'WordPress Core Integration',
            'testYoastSEOCompatibility' => 'Yoast SEO Compatibility',
            'testRankMathCompatibility' => 'RankMath Compatibility',
            'testContentWorkflowIntegration' => 'Content Workflow Integration',
            'testPerformanceUnderLoad' => 'Performance Under Load',
            'testErrorHandlingAndRecovery' => 'Error Handling and Recovery',
            'testDatabaseIntegration' => 'Database Integration',
            'testPluginHooks' => 'Plugin Hooks',
            'testGenerateTestReport' => 'Generate Test Report'
        ];
        
        foreach ($tests as $method => $description) {
            $this->runSingleTest($testSuite, $method, $description);
        }
        
        // Clean up
        $testSuite->tearDown();
        
        $this->displaySummary();
    }
    
    /**
     * Run a single test
     *
     * @param TestWordPressIntegration $testSuite Test suite instance
     * @param string $method Test method name
     * @param string $description Test description
     */
    private function runSingleTest($testSuite, $method, $description) {
        echo "\nRunning: {$description}\n";
        echo str_repeat("-", 40) . "\n";
        
        $startTime = microtime(true);
        $success = false;
        $error = null;
        
        try {
            $testSuite->$method();
            $success = true;
            $status = "PASSED";
        } catch (Exception $e) {
            $error = $e->getMessage();
            $status = "FAILED";
        } catch (PHPUnit\Framework\SkippedTestError $e) {
            $error = $e->getMessage();
            $status = "SKIPPED";
        }
        
        $executionTime = microtime(true) - $startTime;
        
        $this->results[$method] = [
            'description' => $description,
            'status' => $status,
            'success' => $success,
            'execution_time' => $executionTime,
            'error' => $error
        ];
        
        echo "\nResult: {$status}";
        if ($executionTime > 0.001) {
            echo " (Time: " . round($executionTime, 3) . "s)";
        }
        if ($error) {
            echo "\nError: {$error}";
        }
        echo "\n";
    }
    
    /**
     * Display environment information
     */
    private function displayEnvironmentInfo() {
        echo "\nEnvironment Information:\n";
        echo str_repeat("-", 40) . "\n";
        echo "WordPress Version: " . $this->environment['wp_version'] . "\n";
        echo "PHP Version: " . $this->environment['php_version'] . "\n";
        echo "Server: " . $this->environment['server'] . "\n";
        echo "Memory Limit: " . $this->environment['memory_limit'] . "\n";
        echo "Max Execution Time: " . $this->environment['max_execution_time'] . "s\n";
        echo "Active Plugins: " . count($this->environment['active_plugins']) . "\n";
        
        // Check for SEO plugins
        $seoPlugins = $this->detectSEOPlugins();
        if (!empty($seoPlugins)) {
            echo "SEO Plugins Detected: " . implode(', ', $seoPlugins) . "\n";
        } else {
            echo "SEO Plugins Detected: None\n";
        }
        
        echo "Theme: " . $this->environment['theme'] . "\n";
        echo "Multisite: " . ($this->environment['multisite'] ? 'Yes' : 'No') . "\n";
        echo "Debug Mode: " . ($this->environment['debug_mode'] ? 'Enabled' : 'Disabled') . "\n";
    }
    
    /**
     * Display test summary
     */
    private function displaySummary() {
        $totalTime = microtime(true) - $this->startTime;
        $totalTests = count($this->results);
        $passedTests = count(array_filter($this->results, function($result) {
            return $result['success'];
        }));
        $failedTests = count(array_filter($this->results, function($result) {
            return $result['status'] === 'FAILED';
        }));
        $skippedTests = count(array_filter($this->results, function($result) {
            return $result['status'] === 'SKIPPED';
        }));
        
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "TEST SUMMARY\n";
        echo str_repeat("=", 80) . "\n";
        
        echo "Total Tests: {$totalTests}\n";
        echo "Passed: {$passedTests}\n";
        echo "Failed: {$failedTests}\n";
        echo "Skipped: {$skippedTests}\n";
        echo "Total Execution Time: " . round($totalTime, 3) . "s\n";
        
        echo "\nDetailed Results:\n";
        echo str_repeat("-", 80) . "\n";
        
        foreach ($this->results as $method => $result) {
            $status = $result['status'];
            $time = round($result['execution_time'], 3);
            echo sprintf("%-40s %s (%ss)\n", $result['description'], $status, $time);
            
            if ($result['error'] && $result['status'] === 'FAILED') {
                echo "  Error: " . $result['error'] . "\n";
            }
        }
        
        // Overall result
        echo "\n" . str_repeat("=", 80) . "\n";
        if ($failedTests === 0) {
            echo "OVERALL RESULT: ALL TESTS PASSED ✓\n";
            $exitCode = 0;
        } else {
            echo "OVERALL RESULT: {$failedTests} TEST(S) FAILED ✗\n";
            $exitCode = 1;
        }
        echo str_repeat("=", 80) . "\n";
        
        // Save results to file
        $this->saveResultsToFile();
        
        // Exit with appropriate code for CI/CD
        if (php_sapi_name() === 'cli') {
            exit($exitCode);
        }
    }
    
    /**
     * Collect environment information
     *
     * @return array Environment info
     */
    private function collectEnvironmentInfo() {
        global $wp_version;
        
        return [
            'wp_version' => $wp_version ?? 'Unknown',
            'php_version' => PHP_VERSION,
            'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'active_plugins' => get_option('active_plugins', []),
            'theme' => wp_get_theme()->get('Name') ?? 'Unknown',
            'multisite' => is_multisite(),
            'debug_mode' => defined('WP_DEBUG') && WP_DEBUG,
            'timestamp' => current_time('mysql')
        ];
    }
    
    /**
     * Detect SEO plugins
     *
     * @return array List of detected SEO plugins
     */
    private function detectSEOPlugins() {
        $seoPlugins = [];
        $activePlugins = get_option('active_plugins', []);
        
        $knownSEOPlugins = [
            'wordpress-seo/wp-seo.php' => 'Yoast SEO',
            'seo-by-rankmath/rank-math.php' => 'RankMath',
            'all-in-one-seo-pack/all_in_one_seo_pack.php' => 'All in One SEO',
            'seopress/seopress.php' => 'SEOPress',
            'squirrly-seo/squirrly.php' => 'Squirrly SEO'
        ];
        
        foreach ($knownSEOPlugins as $plugin => $name) {
            if (in_array($plugin, $activePlugins)) {
                $seoPlugins[] = $name;
            }
        }
        
        return $seoPlugins;
    }
    
    /**
     * Save test results to file
     */
    private function saveResultsToFile() {
        $reportData = [
            'timestamp' => current_time('mysql'),
            'environment' => $this->environment,
            'seo_plugins' => $this->detectSEOPlugins(),
            'results' => $this->results,
            'summary' => [
                'total_tests' => count($this->results),
                'passed' => count(array_filter($this->results, function($r) { return $r['success']; })),
                'failed' => count(array_filter($this->results, function($r) { return $r['status'] === 'FAILED'; })),
                'skipped' => count(array_filter($this->results, function($r) { return $r['status'] === 'SKIPPED'; })),
                'total_time' => microtime(true) - $this->startTime
            ]
        ];
        
        $reportFile = ACS_PLUGIN_PATH . 'tests/integration_test_results.json';
        file_put_contents($reportFile, json_encode($reportData, JSON_PRETTY_PRINT));
        
        echo "\nDetailed results saved to: {$reportFile}\n";
        
        // Also create a simple HTML report
        $this->generateHTMLReport($reportData);
    }
    
    /**
     * Generate HTML report
     *
     * @param array $reportData Report data
     */
    private function generateHTMLReport($reportData) {
        $html = '<!DOCTYPE html>
<html>
<head>
    <title>AI Content Studio - Integration Test Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { background: #0073aa; color: white; padding: 20px; border-radius: 5px; }
        .summary { background: #f9f9f9; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .test-result { margin: 10px 0; padding: 10px; border-radius: 3px; }
        .passed { background: #d4edda; border-left: 4px solid #28a745; }
        .failed { background: #f8d7da; border-left: 4px solid #dc3545; }
        .skipped { background: #fff3cd; border-left: 4px solid #ffc107; }
        .environment { background: #e9ecef; padding: 15px; margin: 20px 0; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; }
    </style>
</head>
<body>
    <div class="header">
        <h1>AI Content Studio - Integration Test Report</h1>
        <p>Generated on: ' . $reportData['timestamp'] . '</p>
    </div>
    
    <div class="summary">
        <h2>Test Summary</h2>
        <p><strong>Total Tests:</strong> ' . $reportData['summary']['total_tests'] . '</p>
        <p><strong>Passed:</strong> ' . $reportData['summary']['passed'] . '</p>
        <p><strong>Failed:</strong> ' . $reportData['summary']['failed'] . '</p>
        <p><strong>Skipped:</strong> ' . $reportData['summary']['skipped'] . '</p>
        <p><strong>Total Time:</strong> ' . round($reportData['summary']['total_time'], 3) . 's</p>
    </div>
    
    <div class="environment">
        <h2>Environment Information</h2>
        <table>
            <tr><td><strong>WordPress Version:</strong></td><td>' . $reportData['environment']['wp_version'] . '</td></tr>
            <tr><td><strong>PHP Version:</strong></td><td>' . $reportData['environment']['php_version'] . '</td></tr>
            <tr><td><strong>Server:</strong></td><td>' . $reportData['environment']['server'] . '</td></tr>
            <tr><td><strong>Theme:</strong></td><td>' . $reportData['environment']['theme'] . '</td></tr>
            <tr><td><strong>SEO Plugins:</strong></td><td>' . (empty($reportData['seo_plugins']) ? 'None' : implode(', ', $reportData['seo_plugins'])) . '</td></tr>
            <tr><td><strong>Active Plugins:</strong></td><td>' . count($reportData['environment']['active_plugins']) . '</td></tr>
        </table>
    </div>
    
    <h2>Test Results</h2>';
        
        foreach ($reportData['results'] as $result) {
            $cssClass = strtolower($result['status']);
            $html .= '<div class="test-result ' . $cssClass . '">
                <h3>' . $result['description'] . '</h3>
                <p><strong>Status:</strong> ' . $result['status'] . '</p>
                <p><strong>Execution Time:</strong> ' . round($result['execution_time'], 3) . 's</p>';
            
            if ($result['error']) {
                $html .= '<p><strong>Error:</strong> ' . htmlspecialchars($result['error']) . '</p>';
            }
            
            $html .= '</div>';
        }
        
        $html .= '</body></html>';
        
        $htmlFile = ACS_PLUGIN_PATH . 'tests/integration_test_report.html';
        file_put_contents($htmlFile, $html);
        
        echo "HTML report saved to: {$htmlFile}\n";
    }
    
    /**
     * Run specific test category
     *
     * @param string $category Test category
     */
    public function runTestCategory($category) {
        $categories = [
            'core' => ['testWordPressCoreIntegration'],
            'seo' => ['testYoastSEOCompatibility', 'testRankMathCompatibility'],
            'workflow' => ['testContentWorkflowIntegration'],
            'performance' => ['testPerformanceUnderLoad'],
            'error' => ['testErrorHandlingAndRecovery'],
            'database' => ['testDatabaseIntegration'],
            'hooks' => ['testPluginHooks']
        ];
        
        if (!isset($categories[$category])) {
            echo "Unknown test category: {$category}\n";
            echo "Available categories: " . implode(', ', array_keys($categories)) . "\n";
            return;
        }
        
        echo "Running {$category} tests...\n";
        
        $testSuite = new TestWordPressIntegration();
        $testSuite->setUp();
        
        foreach ($categories[$category] as $method) {
            $this->runSingleTest($testSuite, $method, $method);
        }
        
        $testSuite->tearDown();
        $this->displaySummary();
    }
}

// Command line interface
if (php_sapi_name() === 'cli') {
    $category = $argv[1] ?? 'all';
    
    $runner = new IntegrationTestRunner();
    
    if ($category === 'all') {
        $runner->runAllTests();
    } else {
        $runner->runTestCategory($category);
    }
} else {
    // Web interface
    echo '<pre>';
    $runner = new IntegrationTestRunner();
    $runner->runAllTests();
    echo '</pre>';
}