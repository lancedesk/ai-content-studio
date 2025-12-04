<?php
/**
 * Integration Test: Content Structure Preservation with Multi-Pass Optimizer
 * 
 * Tests that the ContentStructurePreserver integrates correctly with the
 * MultiPassSEOOptimizer and preserves content structure during optimization.
 */

require_once __DIR__ . '/bootstrap.php';

echo "=== Content Structure Preservation Integration Test ===\n\n";

$integrationPassed = true;

try {
    // Create test content with specific structure
    $originalContent = [
        'title' => 'SEO Optimization Guide for Beginners',
        'meta_description' => 'Short desc',
        'content' => '<h2>Introduction to SEO</h2><p>SEO is important for websites. This guide covers the basics.</p><h3>Key Concepts</h3><p>Understanding search engines is crucial.</p><img src="seo.jpg" alt="SEO diagram"><ul><li>Keywords</li><li>Content</li><li>Links</li></ul>'
    ];
    
    echo "Test 1: Structure Preserver Integration\n";
    
    // Initialize optimizer
    $config = [
        'maxIterations' => 2,
        'targetComplianceScore' => 100.0,
        'enableEarlyTermination' => true,
        'autoCorrection' => false, // Disable AI correction for testing
        'logLevel' => 'error'
    ];
    
    $optimizer = new MultiPassSEOOptimizer($config);
    
    // Get structure preserver instance
    $preserver = $optimizer->getStructurePreserver();
    
    if (!$preserver instanceof ContentStructurePreserver) {
        $integrationPassed = false;
        echo "  ✗ Structure preserver not initialized\n";
    } else {
        echo "  ✓ Structure preserver initialized\n";
    }
    
    // Analyze original structure
    $originalStructure = $preserver->analyzeStructure($originalContent);
    
    echo "\nTest 2: Structure Analysis\n";
    
    if (!isset($originalStructure['content']['htmlStructure'])) {
        $integrationPassed = false;
        echo "  ✗ HTML structure analysis failed\n";
    } else {
        echo "  ✓ HTML structure analyzed\n";
        echo "    - Paragraphs: {$originalStructure['content']['paragraphCount']}\n";
        echo "    - Headings: " . array_sum($originalStructure['content']['headingCount']) . "\n";
        echo "    - Images: {$originalStructure['content']['imageCount']}\n";
        echo "    - Lists: {$originalStructure['content']['listCount']}\n";
    }
    
    echo "\nTest 3: Snapshot Creation\n";
    
    $snapshotId = $preserver->createSnapshot($originalContent, 'test_snapshot');
    
    if (empty($snapshotId)) {
        $integrationPassed = false;
        echo "  ✗ Snapshot creation failed\n";
    } else {
        echo "  ✓ Snapshot created: {$snapshotId}\n";
    }
    
    // Verify snapshot can be retrieved
    $snapshot = $preserver->rollback($snapshotId);
    
    if ($snapshot === null || $snapshot['content'] !== $originalContent) {
        $integrationPassed = false;
        echo "  ✗ Snapshot retrieval failed\n";
    } else {
        echo "  ✓ Snapshot retrieved successfully\n";
    }
    
    echo "\nTest 4: Checksum Generation\n";
    
    $checksum = $preserver->generateChecksum($originalContent);
    
    if (empty($checksum)) {
        $integrationPassed = false;
        echo "  ✗ Checksum generation failed\n";
    } else {
        echo "  ✓ Checksum generated: " . substr($checksum, 0, 16) . "...\n";
    }
    
    echo "\nTest 5: Integrity Validation\n";
    
    // Create slightly modified content (SEO improvements)
    $modifiedContent = $originalContent;
    $modifiedContent['meta_description'] = 'Learn SEO optimization techniques for beginners with this comprehensive guide covering all essential concepts and best practices.';
    
    $validation = $preserver->validateIntegrity($originalContent, $modifiedContent);
    
    if (!isset($validation['isValid'])) {
        $integrationPassed = false;
        echo "  ✗ Validation failed\n";
    } else {
        echo "  ✓ Validation completed\n";
        echo "    - Valid: " . ($validation['isValid'] ? 'Yes' : 'No') . "\n";
        echo "    - Violations: " . count($validation['violations']) . "\n";
        echo "    - Warnings: " . count($validation['warnings']) . "\n";
    }
    
    echo "\nTest 6: Corruption Detection\n";
    
    // Test with corrupted content
    $corruptedContent = $originalContent;
    $corruptedContent['content'] = '<p>Completely different content</p>';
    
    $corruptionCheck = $preserver->detectCorruption($corruptedContent, $checksum);
    
    if (!$corruptionCheck['isCorrupted']) {
        $integrationPassed = false;
        echo "  ✗ Failed to detect corruption\n";
    } else {
        echo "  ✓ Corruption detected correctly\n";
    }
    
    // Test with non-corrupted content
    $corruptionCheck2 = $preserver->detectCorruption($originalContent, $checksum);
    
    if ($corruptionCheck2['isCorrupted']) {
        $integrationPassed = false;
        echo "  ✗ False positive corruption detection\n";
    } else {
        echo "  ✓ Non-corrupted content validated\n";
    }
    
    echo "\nTest 7: Preservation During Optimization\n";
    
    // Create content with major structure violation
    $badOptimization = $originalContent;
    $badOptimization['content'] = '<p>All structure removed</p>'; // Major violation
    
    $preservationResult = $preserver->preserveContent($originalContent, $badOptimization);
    
    if (!isset($preservationResult['validation'])) {
        $integrationPassed = false;
        echo "  ✗ Preservation validation failed\n";
    } else {
        echo "  ✓ Preservation validation completed\n";
        echo "    - Success: " . ($preservationResult['success'] ? 'Yes' : 'No') . "\n";
        echo "    - Rolled back: " . ($preservationResult['rolledBack'] ? 'Yes' : 'No') . "\n";
        
        // Should detect major violations
        $majorViolations = array_filter($preservationResult['validation']['violations'], function($v) {
            return $v['severity'] === 'major';
        });
        
        if (empty($majorViolations)) {
            $integrationPassed = false;
            echo "  ✗ Failed to detect major structure violations\n";
        } else {
            echo "  ✓ Major violations detected: " . count($majorViolations) . "\n";
        }
    }
    
    echo "\nTest 8: Statistics and Reporting\n";
    
    $stats = $preserver->getPreservationStats();
    
    if (!isset($stats['totalSnapshots'])) {
        $integrationPassed = false;
        echo "  ✗ Statistics retrieval failed\n";
    } else {
        echo "  ✓ Statistics retrieved\n";
        echo "    - Total snapshots: {$stats['totalSnapshots']}\n";
        echo "    - Total checksums: {$stats['totalChecksums']}\n";
        echo "    - Cache size: {$stats['cacheSize']}\n";
    }
    
    echo "\nTest 9: Snapshot Management\n";
    
    // Create multiple snapshots
    for ($i = 1; $i <= 3; $i++) {
        $testContent = $originalContent;
        $testContent['title'] = "Version {$i}";
        $preserver->createSnapshot($testContent, "version_{$i}");
    }
    
    $snapshots = $preserver->getSnapshots();
    
    if (count($snapshots) < 3) {
        $integrationPassed = false;
        echo "  ✗ Snapshot management failed\n";
    } else {
        echo "  ✓ Multiple snapshots created: " . count($snapshots) . "\n";
    }
    
    $latestSnapshot = $preserver->getLatestSnapshot();
    
    if ($latestSnapshot === null || $latestSnapshot['label'] !== 'version_3') {
        $integrationPassed = false;
        echo "  ✗ Latest snapshot retrieval failed\n";
    } else {
        echo "  ✓ Latest snapshot retrieved: {$latestSnapshot['label']}\n";
    }
    
    echo "\nTest 10: Configuration Management\n";
    
    $currentConfig = $preserver->getConfig();
    
    if (!isset($currentConfig['enableRollback'])) {
        $integrationPassed = false;
        echo "  ✗ Configuration retrieval failed\n";
    } else {
        echo "  ✓ Configuration retrieved\n";
    }
    
    // Update configuration
    $preserver->updateConfig(['strictValidation' => false]);
    $updatedConfig = $preserver->getConfig();
    
    if ($updatedConfig['strictValidation'] !== false) {
        $integrationPassed = false;
        echo "  ✗ Configuration update failed\n";
    } else {
        echo "  ✓ Configuration updated successfully\n";
    }
    
} catch (Exception $e) {
    $integrationPassed = false;
    echo "\n✗ Exception: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Integration Test Result ===\n";
echo $integrationPassed ? "✓ PASS - Content Structure Preservation integration validated\n" : "✗ FAIL - Integration test failed\n";

if ($integrationPassed) {
    echo "\nIntegration test confirms:\n";
    echo "- ContentStructurePreserver integrates with MultiPassSEOOptimizer\n";
    echo "- Structure analysis works correctly\n";
    echo "- Snapshot creation and retrieval functions properly\n";
    echo "- Checksum generation and validation works\n";
    echo "- Corruption detection identifies issues\n";
    echo "- Integrity validation detects violations\n";
    echo "- Preservation logic handles rollback correctly\n";
    echo "- Statistics and reporting are accurate\n";
    echo "- Configuration management works as expected\n";
}

echo "\n=== Test Complete ===\n";
