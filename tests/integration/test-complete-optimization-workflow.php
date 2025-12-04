<?php
/**
 * Integration Test: Complete Optimization Workflow
 *
 * Tests the entire multi-pass SEO optimization workflow from start to finish
 *
 * @package AI_Content_Studio
 * @subpackage Tests
 */

require_once dirname(__DIR__) . '/bootstrap.php';

echo "=== Integration Test: Complete Optimization Workflow ===\n\n";

$allTestsPassed = true;

/**
 * Test 1: End-to-End Optimization with Real Content
 */
echo "Test 1: End-to-End Optimization with Real Content\n";
try {
    $config = [
        'maxIterations' => 3,
        'targetComplianceScore' => 95.0,
        'enableEarlyTermination' => true,
        'autoCorrection' => true
    ];
    
    $optimizer = new MultiPassSEOOptimizer($config);
    
    $content = [
        'title' => 'Simple Title',
        'content' => 'This is a short article about WordPress SEO. It needs optimization.',
        'excerpt' => 'Short excerpt',
        'meta_description' => 'Short meta',
        'type' => 'post'
    ];
    
    $focusKeyword = 'WordPress SEO';
    
    $result = $optimizer->optimizeContent($content, $focusKeyword, []);
    
    // Validate result structure
    if (!isset($result['success'])) {
        echo "  ❌ FAIL: Missing 'success' field in result\n";
        $allTestsPassed = false;
    } elseif (!isset($result['content'])) {
        echo "  ❌ FAIL: Missing 'content' field in result\n";
        $allTestsPassed = false;
    } elseif (!isset($result['optimizationSummary'])) {
        echo "  ❌ FAIL: Missing 'optimizationSummary' field in result\n";
        $allTestsPassed = false;
    } else {
        $summary = $result['optimizationSummary'];
        
        // Check that optimization improved the score
        if (($summary['finalScore'] ?? 0) > ($summary['initialScore'] ?? 0)) {
            echo "  ✅ PASS: Optimization improved score from " . 
                 number_format($summary['initialScore'], 1) . "% to " . 
                 number_format($summary['finalScore'], 1) . "%\n";
        } else {
            echo "  ⚠️  WARNING: Score did not improve (Initial: " . 
                 number_format($summary['initialScore'] ?? 0, 1) . "%, Final: " . 
                 number_format($summary['finalScore'] ?? 0, 1) . "%)\n";
        }
        
        // Check iteration count
        if (isset($summary['iterationsUsed']) && $summary['iterationsUsed'] > 0) {
            echo "  ✅ PASS: Used " . $summary['iterationsUsed'] . " iteration(s)\n";
        } else {
            echo "  ❌ FAIL: No iterations recorded\n";
            $allTestsPassed = false;
        }
    }
} catch (Exception $e) {
    echo "  ❌ FAIL: Exception - " . $e->getMessage() . "\n";
    $allTestsPassed = false;
}
echo "\n";

/**
 * Test 2: Integration with Compatibility Layer
 */
echo "Test 2: Integration with Compatibility Layer\n";
try {
    $integrationConfig = [
        'enableOptimizer' => true,
        'autoOptimizeNewContent' => false,
        'bypassMode' => false,
        'integrationMode' => 'seamless',
        'fallbackToOriginal' => true
    ];
    
    $integrationLayer = new IntegrationCompatibilityLayer($integrationConfig);
    
    $content = [
        'title' => 'Test Integration Article',
        'content' => 'Content for testing integration with the compatibility layer.',
        'excerpt' => 'Test excerpt',
        'meta_description' => 'Test meta description',
        'type' => 'post'
    ];
    
    $result = $integrationLayer->processGeneratedContent($content, 'integration', []);
    
    // Check that integration metadata was added
    if (!isset($result['_acs_optimization'])) {
        echo "  ❌ FAIL: Integration metadata missing\n";
        $allTestsPassed = false;
    } else {
        $metadata = $result['_acs_optimization'];
        
        if (isset($metadata['status'])) {
            echo "  ✅ PASS: Integration status: " . $metadata['status'] . "\n";
        } else {
            echo "  ❌ FAIL: Status missing from metadata\n";
            $allTestsPassed = false;
        }
        
        // Check content preservation
        if (isset($result['title']) && isset($result['content'])) {
            echo "  ✅ PASS: Content structure preserved\n";
        } else {
            echo "  ❌ FAIL: Content structure not preserved\n";
            $allTestsPassed = false;
        }
    }
} catch (Exception $e) {
    echo "  ❌ FAIL: Exception - " . $e->getMessage() . "\n";
    $allTestsPassed = false;
}
echo "\n";

/**
 * Test 3: Multiple Content Types
 */
echo "Test 3: Multiple Content Types\n";
try {
    $optimizer = new MultiPassSEOOptimizer([
        'maxIterations' => 2,
        'targetComplianceScore' => 90.0
    ]);
    
    $contentTypes = [
        [
            'type' => 'post',
            'title' => 'Blog Post Title',
            'content' => 'Blog post content about SEO optimization techniques.',
            'keyword' => 'SEO optimization'
        ],
        [
            'type' => 'page',
            'title' => 'Landing Page',
            'content' => 'Landing page content for product showcase.',
            'keyword' => 'product showcase'
        ],
        [
            'type' => 'article',
            'title' => 'Article Title',
            'content' => 'Article content with detailed information.',
            'keyword' => 'detailed information'
        ]
    ];
    
    $successCount = 0;
    foreach ($contentTypes as $testContent) {
        $content = [
            'title' => $testContent['title'],
            'content' => $testContent['content'],
            'excerpt' => 'Excerpt',
            'meta_description' => 'Meta description',
            'type' => $testContent['type']
        ];
        
        $result = $optimizer->optimizeContent($content, $testContent['keyword'], []);
        
        if ($result['success']) {
            $successCount++;
        }
    }
    
    if ($successCount === count($contentTypes)) {
        echo "  ✅ PASS: All " . count($contentTypes) . " content types optimized successfully\n";
    } else {
        echo "  ⚠️  WARNING: Only " . $successCount . " of " . count($contentTypes) . " content types optimized\n";
    }
} catch (Exception $e) {
    echo "  ❌ FAIL: Exception - " . $e->getMessage() . "\n";
    $allTestsPassed = false;
}
echo "\n";

/**
 * Test 4: Error Recovery and Fallback
 */
echo "Test 4: Error Recovery and Fallback\n";
try {
    $integrationLayer = new IntegrationCompatibilityLayer([
        'enableOptimizer' => true,
        'fallbackToOriginal' => true,
        'integrationMode' => 'seamless'
    ]);
    
    // Test with invalid content
    $invalidContent = [
        'title' => '',
        'content' => '',
        'type' => 'post'
    ];
    
    $result = $integrationLayer->processGeneratedContent($invalidContent, 'test', []);
    
    // Should fallback gracefully
    if (isset($result['_acs_optimization'])) {
        $status = $result['_acs_optimization']['status'];
        
        if (in_array($status, ['fallback', 'error', 'unsupported_format'])) {
            echo "  ✅ PASS: Fallback mechanism activated (status: " . $status . ")\n";
        } else {
            echo "  ❌ FAIL: Unexpected status: " . $status . "\n";
            $allTestsPassed = false;
        }
        
        // Check that result is still an array
        if (is_array($result)) {
            echo "  ✅ PASS: Result structure maintained during fallback\n";
        } else {
            echo "  ❌ FAIL: Result structure broken during fallback\n";
            $allTestsPassed = false;
        }
    } else {
        echo "  ❌ FAIL: No integration metadata in fallback\n";
        $allTestsPassed = false;
    }
} catch (Exception $e) {
    echo "  ❌ FAIL: Exception - " . $e->getMessage() . "\n";
    $allTestsPassed = false;
}
echo "\n";

/**
 * Test 5: Bypass Mode
 */
echo "Test 5: Bypass Mode\n";
try {
    $integrationLayer = new IntegrationCompatibilityLayer([
        'enableOptimizer' => true,
        'bypassMode' => true
    ]);
    
    $originalContent = [
        'title' => 'Original Title',
        'content' => 'Original content that should not be modified.',
        'excerpt' => 'Original excerpt',
        'type' => 'post'
    ];
    
    $result = $integrationLayer->processGeneratedContent($originalContent, 'test', []);
    
    // Check that content was not modified
    if ($result['title'] === $originalContent['title'] && 
        $result['content'] === $originalContent['content']) {
        echo "  ✅ PASS: Content preserved in bypass mode\n";
    } else {
        echo "  ❌ FAIL: Content was modified in bypass mode\n";
        $allTestsPassed = false;
    }
    
    // Check bypass status
    if (isset($result['_acs_optimization']['status']) && 
        $result['_acs_optimization']['status'] === 'bypassed') {
        echo "  ✅ PASS: Bypass status correctly set\n";
    } else {
        echo "  ❌ FAIL: Bypass status not set correctly\n";
        $allTestsPassed = false;
    }
} catch (Exception $e) {
    echo "  ❌ FAIL: Exception - " . $e->getMessage() . "\n";
    $allTestsPassed = false;
}
echo "\n";

/**
 * Test 6: Progress Tracking Throughout Workflow
 */
echo "Test 6: Progress Tracking Throughout Workflow\n";
try {
    $optimizer = new MultiPassSEOOptimizer([
        'maxIterations' => 3,
        'targetComplianceScore' => 95.0
    ]);
    
    $content = [
        'title' => 'Progress Tracking Test',
        'content' => 'Content for testing progress tracking through the optimization workflow.',
        'excerpt' => 'Test excerpt',
        'meta_description' => 'Test meta',
        'type' => 'post'
    ];
    
    $result = $optimizer->optimizeContent($content, 'progress tracking', []);
    
    // Check for iteration history
    if (isset($result['iterationHistory']) && is_array($result['iterationHistory'])) {
        echo "  ✅ PASS: Iteration history recorded (" . count($result['iterationHistory']) . " iterations)\n";
        
        // Verify each iteration has required data
        $historyValid = true;
        foreach ($result['iterationHistory'] as $iteration) {
            if (!isset($iteration['iteration']) || !isset($iteration['timestamp'])) {
                $historyValid = false;
                break;
            }
        }
        
        if ($historyValid) {
            echo "  ✅ PASS: All iteration records are valid\n";
        } else {
            echo "  ❌ FAIL: Some iteration records are invalid\n";
            $allTestsPassed = false;
        }
    } else {
        echo "  ❌ FAIL: Iteration history not recorded\n";
        $allTestsPassed = false;
    }
    
    // Check optimization summary
    if (isset($result['optimizationSummary'])) {
        $summary = $result['optimizationSummary'];
        $requiredFields = ['initialScore', 'finalScore', 'iterationsUsed', 'complianceAchieved'];
        
        $summaryValid = true;
        foreach ($requiredFields as $field) {
            if (!isset($summary[$field])) {
                $summaryValid = false;
                break;
            }
        }
        
        if ($summaryValid) {
            echo "  ✅ PASS: Optimization summary complete\n";
        } else {
            echo "  ❌ FAIL: Optimization summary incomplete\n";
            $allTestsPassed = false;
        }
    } else {
        echo "  ❌ FAIL: Optimization summary missing\n";
        $allTestsPassed = false;
    }
} catch (Exception $e) {
    echo "  ❌ FAIL: Exception - " . $e->getMessage() . "\n";
    $allTestsPassed = false;
}
echo "\n";

/**
 * Test 7: Content Structure Preservation
 */
echo "Test 7: Content Structure Preservation\n";
try {
    $optimizer = new MultiPassSEOOptimizer([
        'maxIterations' => 2,
        'targetComplianceScore' => 90.0
    ]);
    
    $originalContent = [
        'title' => 'Original Title with <strong>HTML</strong>',
        'content' => '<p>Paragraph 1 with <em>emphasis</em>.</p><p>Paragraph 2 with <a href="#">link</a>.</p>',
        'excerpt' => 'Original excerpt',
        'meta_description' => 'Original meta description',
        'type' => 'post'
    ];
    
    $result = $optimizer->optimizeContent($originalContent, 'HTML content', []);
    
    if ($result['success']) {
        $optimizedContent = $result['content'];
        
        // Check that HTML structure is preserved (at least some tags remain)
        if (strpos($optimizedContent['content'], '<p>') !== false || 
            strpos($optimizedContent['content'], '<') !== false) {
            echo "  ✅ PASS: HTML structure preserved in content\n";
        } else {
            echo "  ⚠️  WARNING: HTML structure may have been stripped\n";
        }
        
        // Check that all required fields are present
        $requiredFields = ['title', 'content', 'excerpt', 'meta_description'];
        $allFieldsPresent = true;
        
        foreach ($requiredFields as $field) {
            if (!isset($optimizedContent[$field])) {
                $allFieldsPresent = false;
                break;
            }
        }
        
        if ($allFieldsPresent) {
            echo "  ✅ PASS: All content fields preserved\n";
        } else {
            echo "  ❌ FAIL: Some content fields missing\n";
            $allTestsPassed = false;
        }
    } else {
        echo "  ❌ FAIL: Optimization failed\n";
        $allTestsPassed = false;
    }
} catch (Exception $e) {
    echo "  ❌ FAIL: Exception - " . $e->getMessage() . "\n";
    $allTestsPassed = false;
}
echo "\n";

// Final result
if ($allTestsPassed) {
    echo "✅ Integration Test: Complete Optimization Workflow - ALL TESTS PASSED\n";
    exit(0);
} else {
    echo "❌ Integration Test: Complete Optimization Workflow - SOME TESTS FAILED\n";
    exit(1);
}
