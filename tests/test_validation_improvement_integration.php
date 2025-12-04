<?php
/**
 * Test Validation and Improvement Measurement Integration
 * 
 * Verifies that the ValidationImprovementTracker is properly integrated
 * into the MultiPassSEOOptimizer
 */

require_once __DIR__ . '/bootstrap.php';
require_once ACS_PLUGIN_PATH . 'seo/class-multi-pass-seo-optimizer.php';

echo "=== Validation and Improvement Measurement Integration Test ===\n\n";

$testPassed = true;

try {
    // Create optimizer instance
    $optimizer = new MultiPassSEOOptimizer([
        'maxIterations' => 2,
        'targetComplianceScore' => 100.0,
        'enableEarlyTermination' => true
    ]);
    
    echo "✓ MultiPassSEOOptimizer created successfully\n";
    
    // Verify improvement tracker is initialized
    $tracker = $optimizer->getImprovementTracker();
    if (!$tracker || !($tracker instanceof ValidationImprovementTracker)) {
        $testPassed = false;
        echo "✗ Improvement tracker not properly initialized\n";
    } else {
        echo "✓ Improvement tracker initialized\n";
    }
    
    // Test with sample content
    $content = [
        'title' => 'Test Article',
        'content' => '<p>This is test content about SEO optimization. SEO is important for websites.</p>',
        'meta_description' => 'Learn about SEO optimization techniques and best practices for improving website search engine rankings and visibility.'
    ];
    
    $focusKeyword = 'SEO';
    
    echo "\nRunning optimization with improvement tracking...\n";
    
    $result = $optimizer->optimizeContent($content, $focusKeyword, []);
    
    if (!isset($result['success'])) {
        $testPassed = false;
        echo "✗ Optimization result missing success indicator\n";
    } else {
        echo "✓ Optimization completed\n";
    }
    
    // Check if improvement analysis is available
    $improvementAnalysis = $optimizer->getImprovementAnalysis();
    
    if (!isset($improvementAnalysis['status']) || $improvementAnalysis['status'] !== 'available') {
        $testPassed = false;
        echo "✗ Improvement analysis not available\n";
    } else {
        echo "✓ Improvement analysis available\n";
        
        // Verify pass history
        if (isset($improvementAnalysis['passHistory']) && is_array($improvementAnalysis['passHistory'])) {
            echo "✓ Pass history tracked (" . count($improvementAnalysis['passHistory']) . " passes)\n";
        } else {
            $testPassed = false;
            echo "✗ Pass history not tracked\n";
        }
        
        // Verify cache stats
        if (isset($improvementAnalysis['cacheStats']) && is_array($improvementAnalysis['cacheStats'])) {
            echo "✓ Cache statistics available\n";
        } else {
            $testPassed = false;
            echo "✗ Cache statistics not available\n";
        }
    }
    
    // Check progress data for improvement details
    $progressData = $optimizer->getProgressData();
    if (isset($progressData['iterations']) && is_array($progressData['iterations'])) {
        $hasImprovementDetails = false;
        foreach ($progressData['iterations'] as $iteration) {
            if (isset($iteration['improvementDetails'])) {
                $hasImprovementDetails = true;
                break;
            }
        }
        
        if ($hasImprovementDetails) {
            echo "✓ Improvement details stored in progress data\n";
        } else {
            echo "⚠ No improvement details in progress data (may be expected for baseline)\n";
        }
    }
    
} catch (Exception $e) {
    $testPassed = false;
    echo "✗ Exception: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Integration Test Result ===\n";
echo $testPassed ? "✓ PASS - Integration successful\n" : "✗ FAIL - Integration issues detected\n";

echo "\n=== Test Complete ===\n";
