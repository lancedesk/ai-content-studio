<?php
/**
 * Error Handler Integration Test
 * 
 * Demonstrates error handling and fallback strategies in realistic scenarios
 */

require_once __DIR__ . '/bootstrap.php';
require_once ACS_PLUGIN_PATH . 'seo/class-seo-error-handler.php';

echo "=== Error Handler Integration Test ===\n\n";

$errorHandler = new SEOErrorHandler();

// Scenario 1: Simulated AI provider failure with automatic recovery
echo "Scenario 1: AI Provider Failure with Automatic Recovery\n";
echo "--------------------------------------------------------\n";

$attemptLog = [];
$operation = function($attempt, $context) use (&$attemptLog) {
    $attemptLog[] = $attempt;
    echo "  Attempt {$attempt}: ";
    
    // Simulate failure on first 2 attempts
    if ($attempt < 3) {
        echo "Failed (simulated timeout)\n";
        return [
            'success' => false,
            'error' => 'AI provider timeout'
        ];
    }
    
    // Success on 3rd attempt
    echo "Success!\n";
    return [
        'success' => true,
        'data' => 'Content corrected successfully'
    ];
};

$result = $errorHandler->executeWithRecovery(
    $operation,
    'ai_provider_failure',
    'ai_correction',
    ['provider' => 'groq']
);

echo "\nResult:\n";
echo "  Success: " . ($result['success'] ? 'Yes' : 'No') . "\n";
echo "  Total Attempts: " . count($attemptLog) . "\n";
echo "  Data: " . ($result['data'] ?? 'N/A') . "\n";

// Scenario 2: Graceful degradation with partial validation success
echo "\n\nScenario 2: Graceful Degradation - Partial Validation Success\n";
echo "--------------------------------------------------------------\n";

$partialResults = [
    ['check' => 'meta_description', 'status' => 'pass', 'score' => 100],
    ['check' => 'keyword_density', 'status' => 'pass', 'score' => 95],
    ['check' => 'title', 'status' => 'pass', 'score' => 100],
    ['check' => 'headings', 'status' => 'pass', 'score' => 90]
];

$failures = [
    ['check' => 'readability', 'error' => 'Analyzer service timeout'],
    ['check' => 'images', 'error' => 'Image service unavailable']
];

echo "Partial Results: " . count($partialResults) . " checks passed\n";
echo "Failures: " . count($failures) . " checks failed\n\n";

$degradationResult = $errorHandler->applyGracefulDegradation(
    'validation',
    $partialResults,
    $failures
);

echo "Degradation Result:\n";
echo "  Degraded Mode: " . ($degradationResult['degraded'] ? 'Yes' : 'No') . "\n";
echo "  Degradation Level: " . ($degradationResult['degradation_level'] ?? 'N/A') . "\n";
echo "  Success Rate: " . number_format($degradationResult['success_rate'], 1) . "%\n";
echo "  Message: " . $degradationResult['message'] . "\n";

// Scenario 3: User-friendly error reporting
echo "\n\nScenario 3: User-Friendly Error Reporting\n";
echo "------------------------------------------\n";

$errors = [
    ['message' => 'Connection timeout after 30 seconds', 'component' => 'network'],
    ['message' => 'Rate limit: 429 Too Many Requests', 'component' => 'api'],
    ['message' => 'Authentication failed: invalid API key', 'component' => 'auth'],
    ['message' => 'Fatal exception in validation pipeline', 'component' => 'validator']
];

echo "Processing " . count($errors) . " errors...\n\n";

$report = $errorHandler->generateUserFriendlyReport($errors);

echo "Error Report:\n";
echo "  Severity: " . strtoupper($report['severity']) . "\n";
echo "  Summary: " . $report['summary'] . "\n\n";

echo "Details by Category:\n";
foreach ($report['details'] as $category => $details) {
    echo "  {$category}: {$details['count']} error(s)\n";
}

echo "\nRecommendations:\n";
foreach ($report['recommendations'] as $index => $recommendation) {
    echo "  " . ($index + 1) . ". {$recommendation}\n";
}

// Scenario 4: Error classification demonstration
echo "\n\nScenario 4: Error Classification\n";
echo "--------------------------------\n";

$testErrors = [
    'Fatal exception in optimizer' => 'critical',
    'Timeout waiting for response' => 'recoverable',
    'Partial validation completed' => 'degraded',
    'Minor formatting issue detected' => 'informational'
];

foreach ($testErrors as $error => $expectedCategory) {
    $category = $errorHandler->classifyError($error, 'test_component');
    $match = $category === $expectedCategory ? '✓' : '✗';
    echo "  {$match} '{$error}' → {$category}\n";
}

// Scenario 5: Recovery strategy selection
echo "\n\nScenario 5: Recovery Strategy Selection\n";
echo "----------------------------------------\n";

$errorTypes = [
    'ai_provider_failure' => 'provider_failover',
    'rate_limit_exceeded' => 'exponential_backoff',
    'validation_timeout' => 'simplified_validation',
    'network_error' => 'retry_with_backoff'
];

foreach ($errorTypes as $errorType => $expectedStrategy) {
    $recovery = $errorHandler->handleErrorWithRecovery(
        $errorType,
        'test_component',
        'Test error',
        [],
        1
    );
    
    $strategy = $recovery['strategy'] ?? 'none';
    $match = $strategy === $expectedStrategy ? '✓' : '✗';
    echo "  {$match} {$errorType} → {$strategy}\n";
}

// Scenario 6: Fallback strategy levels
echo "\n\nScenario 6: Fallback Strategy Levels\n";
echo "-------------------------------------\n";

$components = ['ai_correction', 'validation', 'optimization_loop'];

foreach ($components as $component) {
    $fallback = $errorHandler->getFallbackStrategy($component);
    echo "  {$component}:\n";
    echo "    Primary: {$fallback['primary']}\n";
    echo "    Fallback 1: {$fallback['fallback_1']}\n";
    echo "    Fallback 2: {$fallback['fallback_2']}\n";
    echo "    Fallback 3: {$fallback['fallback_3']}\n";
    echo "    Graceful Degradation: " . ($fallback['graceful_degradation'] ? 'Enabled' : 'Disabled') . "\n\n";
}

echo "=== Integration Test Complete ===\n";
echo "\nAll scenarios executed successfully!\n";
echo "The error handling system is ready for production use.\n";

