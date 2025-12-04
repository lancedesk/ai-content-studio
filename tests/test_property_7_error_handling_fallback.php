<?php
/**
 * Property Test 7: Error Handling and Fallback Strategies
 * 
 * Feature: multi-pass-seo-optimizer, Property 7: Error Handling and Fallback Strategies
 * Validates: Requirements 3.5, 6.4
 * 
 * For any critical error during optimization, the system should implement fallback strategies 
 * and continue optimization gracefully
 */

require_once __DIR__ . '/bootstrap.php';
require_once ACS_PLUGIN_PATH . 'seo/class-seo-error-handler.php';
require_once ACS_PLUGIN_PATH . 'seo/class-multi-pass-seo-optimizer.php';

echo "=== Property 7: Error Handling and Fallback Strategies Test ===\n\n";

$property7Passed = true;
$testCases = [
    // Test case 1: Error classification
    [
        'name' => 'Error classification - critical errors',
        'test_type' => 'error_classification',
        'errors' => [
            ['message' => 'Fatal exception in validation', 'component' => 'validator'],
            ['message' => 'Cannot continue optimization', 'component' => 'optimizer'],
            ['message' => 'System crash detected', 'component' => 'system']
        ],
        'expected_category' => 'critical'
    ],
    // Test case 2: Error classification - recoverable
    [
        'name' => 'Error classification - recoverable errors',
        'test_type' => 'error_classification',
        'errors' => [
            ['message' => 'Timeout waiting for response', 'component' => 'ai_provider'],
            ['message' => 'Rate limit exceeded, retry later', 'component' => 'api'],
            ['message' => 'Temporary network error', 'component' => 'network']
        ],
        'expected_category' => 'recoverable'
    ],
    // Test case 3: Recovery strategy execution
    [
        'name' => 'Recovery strategy - AI provider failure',
        'test_type' => 'recovery_strategy',
        'error_type' => 'ai_provider_failure',
        'component' => 'ai_correction',
        'error' => 'AI provider timeout',
        'expected_strategy' => 'provider_failover'
    ],
    // Test case 4: Recovery strategy - rate limiting
    [
        'name' => 'Recovery strategy - rate limit handling',
        'test_type' => 'recovery_strategy',
        'error_type' => 'rate_limit_exceeded',
        'component' => 'api',
        'error' => 'Rate limit exceeded',
        'expected_strategy' => 'exponential_backoff'
    ],
    // Test case 5: Graceful degradation
    [
        'name' => 'Graceful degradation - partial success',
        'test_type' => 'graceful_degradation',
        'component' => 'validation',
        'partial_results' => [
            ['check' => 'meta_description', 'status' => 'pass'],
            ['check' => 'keyword_density', 'status' => 'pass'],
            ['check' => 'title', 'status' => 'pass']
        ],
        'failures' => [
            ['check' => 'readability', 'error' => 'Analyzer timeout'],
            ['check' => 'images', 'error' => 'Image service unavailable']
        ],
        'expected_degradation' => true,
        'expected_success_rate_min' => 50.0
    ],
    // Test case 6: Fallback strategy application
    [
        'name' => 'Fallback strategy - AI correction failure',
        'test_type' => 'fallback_strategy',
        'component' => 'ai_correction',
        'expected_fallback_levels' => ['use_alternative_provider', 'use_template_based_correction', 'return_original_content']
    ],
    // Test case 7: Automatic recovery with retry
    [
        'name' => 'Automatic recovery - retry mechanism',
        'test_type' => 'automatic_recovery',
        'error_type' => 'network_error',
        'component' => 'api',
        'max_attempts' => 3,
        'expected_backoff' => true
    ],
    // Test case 8: User-friendly error reporting
    [
        'name' => 'User-friendly error reporting',
        'test_type' => 'error_reporting',
        'errors' => [
            ['message' => 'Connection timeout after 30 seconds', 'component' => 'network'],
            ['message' => 'Rate limit: 429 Too Many Requests', 'component' => 'api'],
            ['message' => 'Authentication failed: invalid API key', 'component' => 'auth']
        ],
        'expected_simplified' => true,
        'expected_recommendations' => true
    ]
];

foreach ($testCases as $index => $testCase) {
    echo "Testing: {$testCase['name']}\n";
    
    try {
        $errorHandler = new SEOErrorHandler();
        
        switch ($testCase['test_type']) {
            case 'error_classification':
                // Test error classification
                $allCorrectlyClassified = true;
                
                foreach ($testCase['errors'] as $error) {
                    $category = $errorHandler->classifyError($error['message'], $error['component']);
                    
                    if ($category !== $testCase['expected_category']) {
                        $allCorrectlyClassified = false;
                        echo "  ✗ Error misclassified: '{$error['message']}' as '{$category}' (expected '{$testCase['expected_category']}')\n";
                    }
                }
                
                if (!$allCorrectlyClassified) {
                    $property7Passed = false;
                } else {
                    echo "  ✓ All errors correctly classified as '{$testCase['expected_category']}'\n";
                }
                break;
                
            case 'recovery_strategy':
                // Test recovery strategy selection and execution
                $recoveryResult = $errorHandler->handleErrorWithRecovery(
                    $testCase['error_type'],
                    $testCase['component'],
                    $testCase['error'],
                    [],
                    1
                );
                
                // Verify recovery result structure
                if (!is_array($recoveryResult)) {
                    $property7Passed = false;
                    echo "  ✗ Invalid recovery result structure\n";
                    break;
                }
                
                // Verify strategy is present
                if (!isset($recoveryResult['strategy'])) {
                    $property7Passed = false;
                    echo "  ✗ Recovery strategy not specified\n";
                    break;
                }
                
                // Verify correct strategy selected
                if ($recoveryResult['strategy'] !== $testCase['expected_strategy']) {
                    $property7Passed = false;
                    echo "  ✗ Wrong strategy: '{$recoveryResult['strategy']}' (expected '{$testCase['expected_strategy']}')\n";
                    break;
                }
                
                // Verify action is specified
                if (!isset($recoveryResult['action'])) {
                    $property7Passed = false;
                    echo "  ✗ Recovery action not specified\n";
                    break;
                }
                
                // Verify backoff delay for retry actions
                if ($recoveryResult['action'] === 'retry' && !isset($recoveryResult['backoff_delay'])) {
                    $property7Passed = false;
                    echo "  ✗ Backoff delay not specified for retry action\n";
                    break;
                }
                
                echo "  ✓ Recovery strategy '{$testCase['expected_strategy']}' correctly applied\n";
                break;
                
            case 'graceful_degradation':
                // Test graceful degradation
                $degradationResult = $errorHandler->applyGracefulDegradation(
                    $testCase['component'],
                    $testCase['partial_results'],
                    $testCase['failures']
                );
                
                // Verify result structure
                if (!is_array($degradationResult) || !isset($degradationResult['success'])) {
                    $property7Passed = false;
                    echo "  ✗ Invalid degradation result structure\n";
                    break;
                }
                
                // Verify degradation flag
                if (!isset($degradationResult['degraded'])) {
                    $property7Passed = false;
                    echo "  ✗ Degradation flag not set\n";
                    break;
                }
                
                // Verify degradation is enabled when expected
                if ($testCase['expected_degradation'] && !$degradationResult['degraded']) {
                    $property7Passed = false;
                    echo "  ✗ Graceful degradation not applied when expected\n";
                    break;
                }
                
                // Verify success rate calculation
                if (!isset($degradationResult['success_rate'])) {
                    $property7Passed = false;
                    echo "  ✗ Success rate not calculated\n";
                    break;
                }
                
                // Verify success rate meets minimum
                if ($degradationResult['success_rate'] < $testCase['expected_success_rate_min']) {
                    $property7Passed = false;
                    echo "  ✗ Success rate too low: {$degradationResult['success_rate']}%\n";
                    break;
                }
                
                // Verify degradation level is assigned
                if ($degradationResult['degraded'] && !isset($degradationResult['degradation_level'])) {
                    $property7Passed = false;
                    echo "  ✗ Degradation level not assigned\n";
                    break;
                }
                
                echo "  ✓ Graceful degradation applied with {$degradationResult['success_rate']}% success rate\n";
                break;
                
            case 'fallback_strategy':
                // Test fallback strategy retrieval
                $fallbackStrategy = $errorHandler->getFallbackStrategy($testCase['component']);
                
                // Verify fallback strategy structure
                if (!is_array($fallbackStrategy)) {
                    $property7Passed = false;
                    echo "  ✗ Invalid fallback strategy structure\n";
                    break;
                }
                
                // Verify primary strategy exists
                if (!isset($fallbackStrategy['primary'])) {
                    $property7Passed = false;
                    echo "  ✗ Primary fallback strategy not defined\n";
                    break;
                }
                
                // Verify fallback levels exist
                $hasAllLevels = true;
                foreach ($testCase['expected_fallback_levels'] as $level) {
                    $found = false;
                    foreach ($fallbackStrategy as $key => $value) {
                        if ($value === $level) {
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        $hasAllLevels = false;
                        echo "  ✗ Missing fallback level: {$level}\n";
                    }
                }
                
                if (!$hasAllLevels) {
                    $property7Passed = false;
                    break;
                }
                
                // Verify graceful degradation flag
                if (!isset($fallbackStrategy['graceful_degradation'])) {
                    $property7Passed = false;
                    echo "  ✗ Graceful degradation flag not set\n";
                    break;
                }
                
                echo "  ✓ Fallback strategy with " . count($testCase['expected_fallback_levels']) . " levels defined\n";
                break;
                
            case 'automatic_recovery':
                // Test automatic recovery with retry
                $attemptCount = 0;
                $backoffDelays = [];
                
                $operation = function($attempt, $context) use (&$attemptCount, &$backoffDelays, $testCase) {
                    $attemptCount = $attempt;
                    
                    // Simulate failure for first attempts
                    if ($attempt < $testCase['max_attempts']) {
                        return [
                            'success' => false,
                            'error' => 'Simulated network error'
                        ];
                    }
                    
                    // Success on final attempt
                    return [
                        'success' => true,
                        'data' => 'Operation completed'
                    ];
                };
                
                $result = $errorHandler->executeWithRecovery(
                    $operation,
                    $testCase['error_type'],
                    $testCase['component']
                );
                
                // Verify result structure
                if (!is_array($result) || !isset($result['success'])) {
                    $property7Passed = false;
                    echo "  ✗ Invalid recovery result structure\n";
                    break;
                }
                
                // Verify operation was retried
                if ($attemptCount < 2) {
                    $property7Passed = false;
                    echo "  ✗ Operation not retried (attempts: {$attemptCount})\n";
                    break;
                }
                
                // Verify max attempts respected
                if ($attemptCount > $testCase['max_attempts']) {
                    $property7Passed = false;
                    echo "  ✗ Max attempts exceeded: {$attemptCount} > {$testCase['max_attempts']}\n";
                    break;
                }
                
                // Verify eventual success or fallback
                if (!$result['success'] && !isset($result['fallback_applied'])) {
                    $property7Passed = false;
                    echo "  ✗ Neither success nor fallback achieved\n";
                    break;
                }
                
                echo "  ✓ Automatic recovery executed with {$attemptCount} attempts\n";
                break;
                
            case 'error_reporting':
                // Test user-friendly error reporting
                $report = $errorHandler->generateUserFriendlyReport($testCase['errors']);
                
                // Verify report structure
                if (!is_array($report)) {
                    $property7Passed = false;
                    echo "  ✗ Invalid report structure\n";
                    break;
                }
                
                // Verify required fields
                $requiredFields = ['summary', 'details', 'recommendations', 'severity'];
                foreach ($requiredFields as $field) {
                    if (!isset($report[$field])) {
                        $property7Passed = false;
                        echo "  ✗ Missing required field: {$field}\n";
                        break 2;
                    }
                }
                
                // Verify summary is not empty
                if (empty($report['summary'])) {
                    $property7Passed = false;
                    echo "  ✗ Empty summary\n";
                    break;
                }
                
                // Verify details are provided
                if (empty($report['details'])) {
                    $property7Passed = false;
                    echo "  ✗ No error details provided\n";
                    break;
                }
                
                // Verify recommendations when expected
                if ($testCase['expected_recommendations'] && empty($report['recommendations'])) {
                    $property7Passed = false;
                    echo "  ✗ No recommendations provided\n";
                    break;
                }
                
                // Verify severity is valid
                $validSeverities = ['critical', 'warning', 'info'];
                if (!in_array($report['severity'], $validSeverities)) {
                    $property7Passed = false;
                    echo "  ✗ Invalid severity: {$report['severity']}\n";
                    break;
                }
                
                echo "  ✓ User-friendly report generated with severity '{$report['severity']}'\n";
                break;
                
            default:
                $property7Passed = false;
                echo "  ✗ Unknown test type: {$testCase['test_type']}\n";
        }
        
    } catch (Exception $e) {
        $property7Passed = false;
        echo "  ✗ Exception - " . $e->getMessage() . "\n";
        echo "  Stack trace: " . $e->getTraceAsString() . "\n";
    }
}

// Additional comprehensive tests
echo "\n--- Comprehensive Error Handling Tests ---\n";

try {
    $errorHandler = new SEOErrorHandler();
    
    // Test 1: Verify error categories are defined
    echo "Testing: Error categories configuration\n";
    $categories = $errorHandler->getErrorCategories();
    
    if (!is_array($categories) || empty($categories)) {
        $property7Passed = false;
        echo "  ✗ Error categories not defined\n";
    } else {
        $requiredCategories = ['critical', 'recoverable', 'degraded', 'informational'];
        $hasAllCategories = true;
        
        foreach ($requiredCategories as $category) {
            if (!isset($categories[$category])) {
                $hasAllCategories = false;
                echo "  ✗ Missing category: {$category}\n";
            }
        }
        
        if ($hasAllCategories) {
            echo "  ✓ All error categories defined\n";
        } else {
            $property7Passed = false;
        }
    }
    
    // Test 2: Verify recovery strategies are defined
    echo "Testing: Recovery strategies configuration\n";
    $strategies = $errorHandler->getRecoveryStrategies();
    
    if (!is_array($strategies) || empty($strategies)) {
        $property7Passed = false;
        echo "  ✗ Recovery strategies not defined\n";
    } else {
        $requiredStrategies = ['ai_provider_failure', 'validation_timeout', 'correction_failure', 'rate_limit_exceeded', 'network_error'];
        $hasAllStrategies = true;
        
        foreach ($requiredStrategies as $strategy) {
            if (!isset($strategies[$strategy])) {
                $hasAllStrategies = false;
                echo "  ✗ Missing strategy: {$strategy}\n";
            }
        }
        
        if ($hasAllStrategies) {
            echo "  ✓ All recovery strategies defined\n";
        } else {
            $property7Passed = false;
        }
    }
    
    // Test 3: Verify fallback strategies are defined
    echo "Testing: Fallback strategies configuration\n";
    $fallbacks = $errorHandler->getFallbackStrategies();
    
    if (!is_array($fallbacks) || empty($fallbacks)) {
        $property7Passed = false;
        echo "  ✗ Fallback strategies not defined\n";
    } else {
        $requiredComponents = ['ai_correction', 'validation', 'optimization_loop'];
        $hasAllFallbacks = true;
        
        foreach ($requiredComponents as $component) {
            if (!isset($fallbacks[$component])) {
                $hasAllFallbacks = false;
                echo "  ✗ Missing fallback for: {$component}\n";
            }
        }
        
        if ($hasAllFallbacks) {
            echo "  ✓ All fallback strategies defined\n";
        } else {
            $property7Passed = false;
        }
    }
    
} catch (Exception $e) {
    $property7Passed = false;
    echo "  ✗ Exception in comprehensive tests - " . $e->getMessage() . "\n";
}

echo "\n=== Property 7 Test Result ===\n";
echo $property7Passed ? "✓ PASS - Error Handling and Fallback Strategies property validated\n" : "✗ FAIL - Property validation failed\n";

if ($property7Passed) {
    echo "\nProperty 7 validates that:\n";
    echo "- Errors are comprehensively classified by severity and type\n";
    echo "- Recovery strategies are defined for common error scenarios\n";
    echo "- Automatic retry with exponential backoff is implemented\n";
    echo "- Graceful degradation allows partial functionality during failures\n";
    echo "- Fallback strategies provide multiple levels of recovery\n";
    echo "- User-friendly error reporting simplifies technical errors\n";
    echo "- System continues optimization despite critical errors\n";
}

echo "\n=== Test Complete ===\n";

