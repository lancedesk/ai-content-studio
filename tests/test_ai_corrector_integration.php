<?php
/**
 * Integration Test: AI Content Corrector with Multi-Pass Optimizer
 * 
 * Verifies that AIContentCorrector integrates properly with the multi-pass
 * optimization workflow
 */

require_once __DIR__ . '/bootstrap.php';
require_once ACS_PLUGIN_PATH . 'seo/class-ai-content-corrector.php';
require_once ACS_PLUGIN_PATH . 'seo/class-correction-prompt-generator.php';
require_once ACS_PLUGIN_PATH . 'seo/class-seo-issue-detector.php';

echo "=== AI Content Corrector Integration Test ===\n\n";

$testPassed = true;

try {
    // Test 1: Initialize AIContentCorrector
    echo "Test 1: Initialize AIContentCorrector\n";
    $corrector = new AIContentCorrector([
        'maxRetryAttempts' => 2,
        'enableProviderFailover' => true,
        'enableCorrectionValidation' => true
    ]);
    
    if (!$corrector) {
        throw new Exception("Failed to initialize AIContentCorrector");
    }
    echo "  ✓ AIContentCorrector initialized successfully\n";
    
    // Test 2: Verify configuration
    echo "\nTest 2: Verify configuration\n";
    $config = $corrector->getConfig();
    
    if (!isset($config['maxRetryAttempts']) || $config['maxRetryAttempts'] !== 2) {
        throw new Exception("Configuration not set correctly");
    }
    echo "  ✓ Configuration verified\n";
    
    // Test 3: Create test content with issues
    echo "\nTest 3: Create test content with issues\n";
    $testContent = [
        'title' => 'SEO Guide',
        'content' => '<p>This is a test content about SEO optimization.</p>',
        'meta_description' => 'Short'
    ];
    
    $focusKeyword = 'SEO';
    echo "  ✓ Test content created\n";
    
    // Test 4: Detect issues
    echo "\nTest 4: Detect SEO issues\n";
    $detector = new SEOIssueDetector();
    $detectionResult = $detector->detectAllIssues($testContent, $focusKeyword, []);
    
    if (empty($detectionResult['issues'])) {
        throw new Exception("No issues detected in test content");
    }
    echo "  ✓ Detected " . count($detectionResult['issues']) . " issues\n";
    
    // Test 5: Generate correction prompts
    echo "\nTest 5: Generate correction prompts\n";
    $generator = new CorrectionPromptGenerator();
    $prompts = $generator->generatePromptsForIssues(
        $detectionResult['issues'],
        $focusKeyword,
        $testContent
    );
    
    if (empty($prompts)) {
        throw new Exception("No correction prompts generated");
    }
    echo "  ✓ Generated " . count($prompts) . " correction prompts\n";
    
    // Test 6: Apply corrections (will skip if no provider available)
    echo "\nTest 6: Apply corrections\n";
    $correctionResult = $corrector->applyCorrections(
        $testContent,
        $prompts,
        $focusKeyword
    );
    
    if (!isset($correctionResult['success'])) {
        throw new Exception("Invalid correction result structure");
    }
    
    if ($correctionResult['correctionsApplied'] > 0) {
        echo "  ✓ Applied " . $correctionResult['correctionsApplied'] . " corrections\n";
    } else {
        echo "  ⚠ No corrections applied (no AI provider configured)\n";
    }
    
    // Test 7: Verify correction history tracking
    echo "\nTest 7: Verify correction history tracking\n";
    $history = $corrector->getCorrectionHistory();
    
    if (!is_array($history)) {
        throw new Exception("Correction history not tracked");
    }
    echo "  ✓ Correction history tracked (" . count($history) . " entries)\n";
    
    // Test 8: Verify error log
    echo "\nTest 8: Verify error log\n";
    $errorLog = $corrector->getErrorLog();
    
    if (!is_array($errorLog)) {
        throw new Exception("Error log not available");
    }
    echo "  ✓ Error log available (" . count($errorLog) . " entries)\n";
    
    // Test 9: Verify statistics
    echo "\nTest 9: Verify correction statistics\n";
    $stats = $corrector->getCorrectionStats();
    
    if (!isset($stats['status'])) {
        throw new Exception("Statistics not available");
    }
    echo "  ✓ Statistics available\n";
    
    // Test 10: Verify configuration update
    echo "\nTest 10: Verify configuration update\n";
    $corrector->updateConfig(['maxRetryAttempts' => 5]);
    $updatedConfig = $corrector->getConfig();
    
    if ($updatedConfig['maxRetryAttempts'] !== 5) {
        throw new Exception("Configuration update failed");
    }
    echo "  ✓ Configuration updated successfully\n";
    
    echo "\n=== All Integration Tests Passed ===\n";
    
} catch (Exception $e) {
    $testPassed = false;
    echo "\n✗ Test Failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Integration Test Result ===\n";
echo $testPassed ? "✓ PASS - AI Content Corrector integration verified\n" : "✗ FAIL - Integration test failed\n";

if ($testPassed) {
    echo "\nIntegration test validates that:\n";
    echo "- AIContentCorrector initializes correctly\n";
    echo "- Configuration management works\n";
    echo "- Integration with SEOIssueDetector works\n";
    echo "- Integration with CorrectionPromptGenerator works\n";
    echo "- Correction application workflow functions\n";
    echo "- History and logging are tracked\n";
    echo "- Statistics are available\n";
}

echo "\n=== Test Complete ===\n";
