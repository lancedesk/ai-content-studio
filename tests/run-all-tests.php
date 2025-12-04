<?php
/**
 * Test Runner - Executes all unit and integration tests
 *
 * @package AI_Content_Studio
 * @subpackage Tests
 */

echo "=== AI Content Studio - Multi-Pass SEO Optimizer Test Suite ===\n\n";

$testFiles = [
    // Unit Tests
    'unit/test-multi-pass-optimizer.php',
    'unit/test-seo-issue-detector.php',
    'unit/test-correction-prompt-generator.php',
    'unit/test-integration-compatibility-layer.php',
    
    // Integration Tests
    'integration/test-complete-optimization-workflow.php',
    
    // Property Tests
    'test_property_1_comprehensive_issue_detection.php',
    'test_property_2_targeted_correction_prompts.php',
    'test_property_3_ai_integration_correction.php',
    'test_property_4_validation_improvement_measurement.php',
    'test_property_7_error_handling_fallback.php',
    'test_property_8_performance_tracking_reporting.php',
    'test_property_9_content_structure_preservation.php',
    'test_property_10_integration_compatibility.php'
];

$totalTests = 0;
$passedTests = 0;
$failedTests = 0;
$skippedTests = 0;

foreach ($testFiles as $testFile) {
    $fullPath = __DIR__ . '/' . $testFile;
    
    if (!file_exists($fullPath)) {
        echo "⚠️  SKIP: $testFile (file not found)\n";
        $skippedTests++;
        continue;
    }
    
    echo "\nRunning: $testFile\n";
    echo str_repeat('-', 70) . "\n";
    
    // Capture output
    ob_start();
    $exitCode = 0;
    
    try {
        include $fullPath;
    } catch (Exception $e) {
        echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
        $exitCode = 1;
    }
    
    $output = ob_get_clean();
    echo $output;
    
    // Determine if test passed based on output
    if ($exitCode === 0 && (strpos($output, 'ALL TESTS PASSED') !== false || strpos($output, '✅') !== false)) {
        $passedTests++;
        echo "✅ PASSED\n";
    } elseif (strpos($output, 'SKIP') !== false) {
        $skippedTests++;
    } else {
        $failedTests++;
        echo "❌ FAILED\n";
    }
    
    $totalTests++;
    echo "\n";
}

// Summary
echo "\n" . str_repeat('=', 70) . "\n";
echo "TEST SUMMARY\n";
echo str_repeat('=', 70) . "\n";
echo "Total Test Files: $totalTests\n";
echo "Passed: $passedTests\n";
echo "Failed: $failedTests\n";
echo "Skipped: $skippedTests\n";
echo "\n";

if ($failedTests === 0 && $passedTests > 0) {
    echo "✅ ALL TESTS PASSED!\n";
    exit(0);
} else {
    echo "❌ SOME TESTS FAILED OR WERE SKIPPED\n";
    exit(1);
}

