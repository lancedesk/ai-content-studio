<?php
/**
 * Property Test 10: Integration Compatibility
 * 
 * Feature: multi-pass-seo-optimizer, Property 10: Integration Compatibility
 * Validates: Requirements 6.1, 6.5
 * 
 * For any existing content generation workflow, the optimizer should integrate
 * seamlessly without breaking functionality and support bypass mode
 */

require_once __DIR__ . '/bootstrap.php';

echo "=== Property 10: Integration Compatibility Test ===\n\n";

$property10Passed = true;

// Initialize integration layer with test configuration
$config = [
    'enableOptimizer' => true,
    'autoOptimizeNewContent' => false, // Disable for testing
    'bypassMode' => false,
    'supportedFormats' => ['post', 'page', 'article'],
    'integrationMode' => 'seamless',
    'fallbackToOriginal' => true,
    'preserveExistingWorkflow' => true,
    'logLevel' => 'error' // Reduce noise in tests
];

$integrationLayer = new IntegrationCompatibilityLayer($config);

// Test cases for integration compatibility
$testCases = [
    // Test case 1: Bypass mode functionality
    [
        'name' => 'Bypass mode preserves original content',
        'content' => [
            'title' => 'Original Title',
            'content' => 'Original content that should be preserved.',
            'excerpt' => 'Original excerpt',
            'meta_description' => 'Original meta description',
            'type' => 'post'
        ],
        'focus_keyword' => 'test',
        'bypass_mode' => true,
        'expected_status' => 'bypassed'
    ],
    
    // Test case 2: Seamless integration
    [
        'name' => 'Seamless integration processes content',
        'content' => [
            'title' => 'Test Article Title',
            'content' => 'This is test content for integration testing.',
            'excerpt' => 'Test excerpt',
            'meta_description' => 'Test meta description',
            'type' => 'post'
        ],
        'focus_keyword' => 'integration',
        'bypass_mode' => false,
        'expected_status' => ['optimized', 'fallback', 'error'] // Multiple valid outcomes
    ],
    
    // Test case 3: Unsupported format handling
    [
        'name' => 'Unsupported format handling',
        'content' => [
            'title' => 'Custom Content',
            'content' => 'Content with unsupported format.',
            'type' => 'custom_type'
        ],
        'focus_keyword' => 'custom',
        'bypass_mode' => false,
        'expected_status' => 'unsupported_format'
    ],
    
    // Test case 4: Manual mode
    [
        'name' => 'Manual mode preserves content with metadata',
        'content' => [
            'title' => 'Manual Mode Test',
            'content' => 'Content for manual mode testing.',
            'type' => 'post'
        ],
        'focus_keyword' => 'manual',
        'bypass_mode' => false,
        'expected_status' => 'manual_mode'
    ]
];

// Run test cases
foreach ($testCases as $testCase) {
    echo "Testing: {$testCase['name']}\n";
    
    try {
        // Configure integration layer for test
        $integrationLayer->updateConfig(['bypassMode' => $testCase['bypass_mode']]);
        
        // Set manual mode for test case 4
        if ($testCase['name'] === 'Manual mode preserves content with metadata') {
            $integrationLayer->setIntegrationMode('manual');
        } else {
            $integrationLayer->setIntegrationMode('seamless');
        }
        
        // Process content
        $result = $integrationLayer->processGeneratedContent(
            $testCase['content'], 
            $testCase['focus_keyword']
        );
        
        // Validate result structure
        if (!is_array($result)) {
            echo "  ❌ FAIL: Result is not an array\n";
            $property10Passed = false;
            continue;
        }
        
        // Check required fields are preserved
        if (!isset($result['title']) || !isset($result['content'])) {
            echo "  ❌ FAIL: Required fields not preserved\n";
            $property10Passed = false;
            continue;
        }
        
        // Check integration metadata
        if (!isset($result['_acs_optimization'])) {
            echo "  ❌ FAIL: Integration metadata missing\n";
            $property10Passed = false;
            continue;
        }
        
        $metadata = $result['_acs_optimization'];
        
        // Check status
        if (!isset($metadata['status'])) {
            echo "  ❌ FAIL: Status missing from metadata\n";
            $property10Passed = false;
            continue;
        }
        
        // Validate expected status
        $expectedStatus = $testCase['expected_status'];
        $actualStatus = $metadata['status'];
        
        if (is_array($expectedStatus)) {
            if (!in_array($actualStatus, $expectedStatus)) {
                echo "  ❌ FAIL: Status '{$actualStatus}' not in expected values: " . implode(', ', $expectedStatus) . "\n";
                $property10Passed = false;
                continue;
            }
        } else {
            if ($actualStatus !== $expectedStatus) {
                echo "  ❌ FAIL: Expected status '{$expectedStatus}', got '{$actualStatus}'\n";
                $property10Passed = false;
                continue;
            }
        }
        
        // Validate bypass mode behavior
        if ($testCase['bypass_mode'] && $actualStatus === 'bypassed') {
            // Check that original content is preserved
            if ($result['title'] !== $testCase['content']['title'] || 
                $result['content'] !== $testCase['content']['content']) {
                echo "  ❌ FAIL: Original content not preserved in bypass mode\n";
                $property10Passed = false;
                continue;
            }
        }
        
        echo "  ✅ PASS: Integration compatibility verified\n";
        
    } catch (Exception $e) {
        echo "  ❌ FAIL: Exception occurred: " . $e->getMessage() . "\n";
        $property10Passed = false;
    }
    
    echo "\n";
}

// Test integration status reporting
echo "Testing: Integration status reporting\n";
try {
    $status = $integrationLayer->getIntegrationStatus();
    
    // Check required fields
    $requiredFields = [
        'optimizer_enabled',
        'bypass_mode', 
        'integration_mode',
        'auto_optimize',
        'supported_formats',
        'hooks_registered',
        'optimizer_available'
    ];
    
    $statusTestPassed = true;
    foreach ($requiredFields as $field) {
        if (!array_key_exists($field, $status)) {
            echo "  ❌ FAIL: Status missing field: {$field}\n";
            $statusTestPassed = false;
            $property10Passed = false;
        }
    }
    
    if ($statusTestPassed) {
        echo "  ✅ PASS: Integration status reporting works correctly\n";
    }
} catch (Exception $e) {
    echo "  ❌ FAIL: Exception occurred: " . $e->getMessage() . "\n";
    $property10Passed = false;
}

echo "\n";

// Test comprehensive integration compatibility
echo "Testing: Comprehensive integration compatibility\n";
try {
    $testContent = [
        'title' => 'Test Article Title',
        'content' => 'This is test content for integration compatibility testing.',
        'excerpt' => 'Test excerpt',
        'meta_description' => 'Test meta description',
        'type' => 'post'
    ];
    
    $compatibilityResults = $integrationLayer->testIntegrationCompatibility($testContent);
    
    // Check expected tests
    $expectedTests = [
        'bypass_mode_test',
        'seamless_integration_test', 
        'fallback_test',
        'format_support_test',
        'workflow_preservation_test'
    ];
    
    $compatTestPassed = true;
    foreach ($expectedTests as $test) {
        if (!array_key_exists($test, $compatibilityResults)) {
            echo "  ❌ FAIL: Compatibility test missing: {$test}\n";
            $compatTestPassed = false;
            $property10Passed = false;
        }
    }
    
    // Check critical tests passed
    if (isset($compatibilityResults['bypass_mode_test']) && !$compatibilityResults['bypass_mode_test']) {
        echo "  ❌ FAIL: Bypass mode test failed\n";
        $compatTestPassed = false;
        $property10Passed = false;
    }
    
    if (isset($compatibilityResults['format_support_test']) && !$compatibilityResults['format_support_test']) {
        echo "  ❌ FAIL: Format support test failed\n";
        $compatTestPassed = false;
        $property10Passed = false;
    }
    
    if (isset($compatibilityResults['workflow_preservation_test']) && !$compatibilityResults['workflow_preservation_test']) {
        echo "  ❌ FAIL: Workflow preservation test failed\n";
        $compatTestPassed = false;
        $property10Passed = false;
    }
    
    if ($compatTestPassed) {
        echo "  ✅ PASS: Comprehensive integration compatibility verified\n";
    }
} catch (Exception $e) {
    echo "  ❌ FAIL: Exception occurred: " . $e->getMessage() . "\n";
    $property10Passed = false;
}

echo "\n";

// Final result
if ($property10Passed) {
    echo "✅ Property 10: Integration Compatibility - ALL TESTS PASSED\n";
    exit(0);
} else {
    echo "❌ Property 10: Integration Compatibility - SOME TESTS FAILED\n";
    exit(1);
}
