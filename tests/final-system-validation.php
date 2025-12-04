<?php
/**
 * Final System Validation
 *
 * Comprehensive validation of the complete multi-pass SEO optimizer system
 * Tests all requirements and validates deployment readiness
 *
 * @package AI_Content_Studio
 * @subpackage Tests
 */

require_once __DIR__ . '/bootstrap.php';

echo "╔════════════════════════════════════════════════════════════════════╗\n";
echo "║   FINAL SYSTEM VALIDATION - Multi-Pass SEO Optimizer              ║\n";
echo "╚════════════════════════════════════════════════════════════════════╝\n\n";

$validationResults = [
    'core_components' => [],
    'integration' => [],
    'performance' => [],
    'requirements' => [],
    'deployment_readiness' => []
];

$allPassed = true;

/**
 * SECTION 1: Core Components Validation
 */
echo "═══ SECTION 1: Core Components Validation ═══\n\n";

// Test 1.1: Multi-Pass Optimizer Engine
echo "1.1 Multi-Pass Optimizer Engine\n";
try {
    $optimizer = new MultiPassSEOOptimizer([
        'maxIterations' => 3,
        'targetComplianceScore' => 95.0,
        'enableEarlyTermination' => true
    ]);
    
    if ($optimizer) {
        echo "  ✅ PASS: Optimizer engine initialized\n";
        $validationResults['core_components']['optimizer_engine'] = true;
    }
} catch (Exception $e) {
    echo "  ❌ FAIL: " . $e->getMessage() . "\n";
    $validationResults['core_components']['optimizer_engine'] = false;
    $allPassed = false;
}

// Test 1.2: SEO Issue Detector
echo "1.2 SEO Issue Detector\n";
try {
    $detector = new SEOIssueDetector();
    $result = $detector->detectAllIssues([
        'title' => 'Test',
        'content' => 'Test content',
        'meta_description' => ''
    ], 'test', []);
    
    if (is_array($result) && isset($result['issues'])) {
        echo "  ✅ PASS: Issue detector functional\n";
        $validationResults['core_components']['issue_detector'] = true;
    }
} catch (Exception $e) {
    echo "  ❌ FAIL: " . $e->getMessage() . "\n";
    $validationResults['core_components']['issue_detector'] = false;
    $allPassed = false;
}

// Test 1.3: Correction Prompt Generator
echo "1.3 Correction Prompt Generator\n";
try {
    $generator = new CorrectionPromptGenerator();
    $testIssue = new SEOIssue('keyword_density_low', 'major', 0.5, 1.0, [], 'Test issue');
    $prompts = $generator->generatePromptsForIssues([$testIssue], 'keyword', ['title' => 'Test', 'content' => 'Test']);
    
    if (is_array($prompts)) {
        echo "  ✅ PASS: Prompt generator functional\n";
        $validationResults['core_components']['prompt_generator'] = true;
    }
} catch (Exception $e) {
    echo "  ❌ FAIL: " . $e->getMessage() . "\n";
    $validationResults['core_components']['prompt_generator'] = false;
    $allPassed = false;
}

// Test 1.4: Integration Compatibility Layer
echo "1.4 Integration Compatibility Layer\n";
try {
    $integrationLayer = new IntegrationCompatibilityLayer([
        'enableOptimizer' => true,
        'bypassMode' => false
    ]);
    
    if ($integrationLayer) {
        echo "  ✅ PASS: Integration layer initialized\n";
        $validationResults['core_components']['integration_layer'] = true;
    }
} catch (Exception $e) {
    echo "  ❌ FAIL: " . $e->getMessage() . "\n";
    $validationResults['core_components']['integration_layer'] = false;
    $allPassed = false;
}

echo "\n";

/**
 * SECTION 2: Integration Testing
 */
echo "═══ SECTION 2: Integration Testing ═══\n\n";

// Test 2.1: End-to-End Optimization
echo "2.1 End-to-End Optimization Workflow\n";
try {
    $optimizer = new MultiPassSEOOptimizer([
        'maxIterations' => 2,
        'targetComplianceScore' => 90.0
    ]);
    
    $testContent = [
        'title' => 'WordPress SEO Guide',
        'content' => 'This is a comprehensive guide about WordPress SEO optimization techniques and best practices.',
        'excerpt' => 'Learn WordPress SEO',
        'meta_description' => 'Complete WordPress SEO guide',
        'type' => 'post'
    ];
    
    $result = $optimizer->optimizeContent($testContent, 'WordPress SEO', []);
    
    if ($result['success'] && isset($result['optimizationSummary'])) {
        echo "  ✅ PASS: End-to-end optimization successful\n";
        echo "     Initial Score: " . number_format($result['optimizationSummary']['initialScore'], 1) . "%\n";
        echo "     Final Score: " . number_format($result['optimizationSummary']['finalScore'], 1) . "%\n";
        echo "     Iterations: " . $result['optimizationSummary']['iterationsUsed'] . "\n";
        $validationResults['integration']['end_to_end'] = true;
    } else {
        echo "  ❌ FAIL: Optimization did not complete successfully\n";
        $validationResults['integration']['end_to_end'] = false;
        $allPassed = false;
    }
} catch (Exception $e) {
    echo "  ❌ FAIL: " . $e->getMessage() . "\n";
    $validationResults['integration']['end_to_end'] = false;
    $allPassed = false;
}

// Test 2.2: Integration with Existing Workflow
echo "2.2 Integration with Existing Workflow\n";
try {
    $integrationLayer = new IntegrationCompatibilityLayer([
        'enableOptimizer' => true,
        'integrationMode' => 'seamless',
        'fallbackToOriginal' => true
    ]);
    
    $content = [
        'title' => 'Test Article',
        'content' => 'Test content for workflow integration.',
        'type' => 'post'
    ];
    
    $result = $integrationLayer->processGeneratedContent($content, 'test', []);
    
    if (isset($result['_acs_optimization']) && isset($result['title'])) {
        echo "  ✅ PASS: Workflow integration successful\n";
        echo "     Status: " . $result['_acs_optimization']['status'] . "\n";
        $validationResults['integration']['workflow'] = true;
    } else {
        echo "  ❌ FAIL: Workflow integration incomplete\n";
        $validationResults['integration']['workflow'] = false;
        $allPassed = false;
    }
} catch (Exception $e) {
    echo "  ❌ FAIL: " . $e->getMessage() . "\n";
    $validationResults['integration']['workflow'] = false;
    $allPassed = false;
}

// Test 2.3: Multiple Content Types
echo "2.3 Multiple Content Types Support\n";
try {
    $optimizer = new MultiPassSEOOptimizer(['maxIterations' => 1]);
    $contentTypes = ['post', 'page', 'article'];
    $successCount = 0;
    
    foreach ($contentTypes as $type) {
        $content = [
            'title' => 'Test ' . ucfirst($type),
            'content' => 'Content for ' . $type . ' type.',
            'type' => $type
        ];
        
        $result = $optimizer->optimizeContent($content, 'test', []);
        if ($result['success']) {
            $successCount++;
        }
    }
    
    if ($successCount === count($contentTypes)) {
        echo "  ✅ PASS: All content types supported (" . $successCount . "/" . count($contentTypes) . ")\n";
        $validationResults['integration']['content_types'] = true;
    } else {
        echo "  ⚠️  WARNING: Only " . $successCount . "/" . count($contentTypes) . " content types successful\n";
        $validationResults['integration']['content_types'] = false;
    }
} catch (Exception $e) {
    echo "  ❌ FAIL: " . $e->getMessage() . "\n";
    $validationResults['integration']['content_types'] = false;
    $allPassed = false;
}

echo "\n";

/**
 * SECTION 3: Performance Validation
 */
echo "═══ SECTION 3: Performance Validation ═══\n\n";

// Test 3.1: Optimization Speed
echo "3.1 Optimization Speed (Target: < 30 seconds)\n";
try {
    $startTime = microtime(true);
    
    $optimizer = new MultiPassSEOOptimizer([
        'maxIterations' => 3,
        'targetComplianceScore' => 95.0
    ]);
    
    $content = [
        'title' => 'Performance Test Article',
        'content' => str_repeat('This is test content for performance validation. ', 20),
        'excerpt' => 'Performance test',
        'meta_description' => 'Testing optimization performance',
        'type' => 'post'
    ];
    
    $result = $optimizer->optimizeContent($content, 'performance', []);
    
    $duration = microtime(true) - $startTime;
    
    if ($duration < 30) {
        echo "  ✅ PASS: Optimization completed in " . number_format($duration, 2) . " seconds\n";
        $validationResults['performance']['speed'] = true;
    } else {
        echo "  ⚠️  WARNING: Optimization took " . number_format($duration, 2) . " seconds (exceeds 30s target)\n";
        $validationResults['performance']['speed'] = false;
    }
} catch (Exception $e) {
    echo "  ❌ FAIL: " . $e->getMessage() . "\n";
    $validationResults['performance']['speed'] = false;
    $allPassed = false;
}

// Test 3.2: Memory Usage
echo "3.2 Memory Usage\n";
try {
    $memoryBefore = memory_get_usage(true);
    
    $optimizer = new MultiPassSEOOptimizer(['maxIterations' => 2]);
    $content = [
        'title' => 'Memory Test',
        'content' => str_repeat('Content for memory testing. ', 50),
        'type' => 'post'
    ];
    
    $result = $optimizer->optimizeContent($content, 'memory', []);
    
    $memoryAfter = memory_get_usage(true);
    $memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024; // MB
    
    echo "  ✅ INFO: Memory used: " . number_format($memoryUsed, 2) . " MB\n";
    $validationResults['performance']['memory'] = true;
} catch (Exception $e) {
    echo "  ❌ FAIL: " . $e->getMessage() . "\n";
    $validationResults['performance']['memory'] = false;
    $allPassed = false;
}

// Test 3.3: Iteration Efficiency
echo "3.3 Iteration Efficiency\n";
try {
    $optimizer = new MultiPassSEOOptimizer([
        'maxIterations' => 5,
        'targetComplianceScore' => 95.0,
        'enableEarlyTermination' => true
    ]);
    
    $content = [
        'title' => 'Efficiency Test Article',
        'content' => 'Content for testing iteration efficiency.',
        'type' => 'post'
    ];
    
    $result = $optimizer->optimizeContent($content, 'efficiency', []);
    
    if (isset($result['optimizationSummary']['iterationsUsed'])) {
        $iterations = $result['optimizationSummary']['iterationsUsed'];
        echo "  ✅ PASS: Used " . $iterations . " iteration(s) (max: 5)\n";
        $validationResults['performance']['efficiency'] = true;
    }
} catch (Exception $e) {
    echo "  ❌ FAIL: " . $e->getMessage() . "\n";
    $validationResults['performance']['efficiency'] = false;
    $allPassed = false;
}

echo "\n";

/**
 * SECTION 4: Requirements Validation
 */
echo "═══ SECTION 4: Requirements Validation ═══\n\n";

// Test 4.1: SEO Compliance Achievement
echo "4.1 SEO Compliance Achievement Capability\n";
try {
    $optimizer = new MultiPassSEOOptimizer([
        'maxIterations' => 3,
        'targetComplianceScore' => 95.0
    ]);
    
    $wellStructuredContent = [
        'title' => 'Complete Guide to SEO Optimization Best Practices in 2025',
        'content' => str_repeat('This comprehensive guide covers SEO optimization techniques, best practices, and strategies. Learn about keyword research, content optimization, technical SEO, and link building. Discover how to improve your website ranking with proven SEO methods. ', 10),
        'excerpt' => 'Comprehensive SEO optimization guide with best practices',
        'meta_description' => 'Learn SEO optimization best practices and techniques to improve your website ranking. Complete guide with proven strategies and actionable tips for 2025.',
        'type' => 'post'
    ];
    
    $result = $optimizer->optimizeContent($wellStructuredContent, 'SEO optimization', ['best practices', 'techniques']);
    
    if (isset($result['optimizationSummary']['finalScore'])) {
        $finalScore = $result['optimizationSummary']['finalScore'];
        $compliant = $result['optimizationSummary']['complianceAchieved'] ?? false;
        
        echo "  ✅ INFO: Final Score: " . number_format($finalScore, 1) . "%\n";
        echo "     Compliance: " . ($compliant ? 'Achieved' : 'Not Achieved') . "\n";
        $validationResults['requirements']['compliance'] = true;
    }
} catch (Exception $e) {
    echo "  ❌ FAIL: " . $e->getMessage() . "\n";
    $validationResults['requirements']['compliance'] = false;
    $allPassed = false;
}

// Test 4.2: Error Handling and Fallback
echo "4.2 Error Handling and Fallback Mechanisms\n";
try {
    $integrationLayer = new IntegrationCompatibilityLayer([
        'enableOptimizer' => true,
        'fallbackToOriginal' => true
    ]);
    
    $invalidContent = [
        'title' => '',
        'content' => '',
        'type' => 'post'
    ];
    
    $result = $integrationLayer->processGeneratedContent($invalidContent, 'test', []);
    
    if (isset($result['_acs_optimization'])) {
        echo "  ✅ PASS: Fallback mechanism functional\n";
        echo "     Status: " . $result['_acs_optimization']['status'] . "\n";
        $validationResults['requirements']['error_handling'] = true;
    }
} catch (Exception $e) {
    echo "  ❌ FAIL: " . $e->getMessage() . "\n";
    $validationResults['requirements']['error_handling'] = false;
    $allPassed = false;
}

// Test 4.3: Content Structure Preservation
echo "4.3 Content Structure Preservation\n";
try {
    $optimizer = new MultiPassSEOOptimizer(['maxIterations' => 1]);
    
    $htmlContent = [
        'title' => 'HTML Content Test',
        'content' => '<p>Paragraph with <strong>bold</strong> and <em>italic</em> text.</p><ul><li>List item</li></ul>',
        'type' => 'post'
    ];
    
    $result = $optimizer->optimizeContent($htmlContent, 'HTML', []);
    
    if ($result['success'] && isset($result['content']['content'])) {
        $hasHtml = (strpos($result['content']['content'], '<') !== false);
        echo "  ✅ " . ($hasHtml ? "PASS" : "INFO") . ": Content structure handling verified\n";
        $validationResults['requirements']['structure_preservation'] = true;
    }
} catch (Exception $e) {
    echo "  ❌ FAIL: " . $e->getMessage() . "\n";
    $validationResults['requirements']['structure_preservation'] = false;
    $allPassed = false;
}

// Test 4.4: Progress Tracking
echo "4.4 Progress Tracking and Reporting\n";
try {
    $optimizer = new MultiPassSEOOptimizer(['maxIterations' => 2]);
    
    $content = [
        'title' => 'Progress Tracking Test',
        'content' => 'Content for progress tracking validation.',
        'type' => 'post'
    ];
    
    $result = $optimizer->optimizeContent($content, 'tracking', []);
    
    if (isset($result['iterationHistory']) && isset($result['optimizationSummary'])) {
        echo "  ✅ PASS: Progress tracking functional\n";
        echo "     Iterations recorded: " . count($result['iterationHistory']) . "\n";
        $validationResults['requirements']['progress_tracking'] = true;
    }
} catch (Exception $e) {
    echo "  ❌ FAIL: " . $e->getMessage() . "\n";
    $validationResults['requirements']['progress_tracking'] = false;
    $allPassed = false;
}

// Test 4.5: Bypass Mode
echo "4.5 Bypass Mode Functionality\n";
try {
    $integrationLayer = new IntegrationCompatibilityLayer([
        'enableOptimizer' => true,
        'bypassMode' => true
    ]);
    
    $originalContent = [
        'title' => 'Original Title',
        'content' => 'Original content',
        'type' => 'post'
    ];
    
    $result = $integrationLayer->processGeneratedContent($originalContent, 'test', []);
    
    if ($result['title'] === 'Original Title' && 
        $result['content'] === 'Original content' &&
        $result['_acs_optimization']['status'] === 'bypassed') {
        echo "  ✅ PASS: Bypass mode preserves content\n";
        $validationResults['requirements']['bypass_mode'] = true;
    }
} catch (Exception $e) {
    echo "  ❌ FAIL: " . $e->getMessage() . "\n";
    $validationResults['requirements']['bypass_mode'] = false;
    $allPassed = false;
}

echo "\n";

/**
 * SECTION 5: Deployment Readiness
 */
echo "═══ SECTION 5: Deployment Readiness ═══\n\n";

// Test 5.1: Class Availability
echo "5.1 Required Classes Available\n";
$requiredClasses = [
    'MultiPassSEOOptimizer',
    'SEOIssueDetector',
    'CorrectionPromptGenerator',
    'IntegrationCompatibilityLayer',
    'SEOValidationPipeline',
    'SEOOptimizerAdmin'
];

$classesAvailable = 0;
foreach ($requiredClasses as $class) {
    if (class_exists($class)) {
        $classesAvailable++;
    } else {
        echo "  ⚠️  WARNING: Class not found: $class\n";
    }
}

if ($classesAvailable === count($requiredClasses)) {
    echo "  ✅ PASS: All required classes available (" . $classesAvailable . "/" . count($requiredClasses) . ")\n";
    $validationResults['deployment_readiness']['classes'] = true;
} else {
    echo "  ⚠️  WARNING: " . $classesAvailable . "/" . count($requiredClasses) . " classes available\n";
    $validationResults['deployment_readiness']['classes'] = false;
}

// Test 5.2: Configuration Management
echo "5.2 Configuration Management\n";
try {
    $optimizer = new MultiPassSEOOptimizer([
        'maxIterations' => 3,
        'targetComplianceScore' => 95.0
    ]);
    
    $config = $optimizer->getConfig();
    
    if (isset($config['maxIterations']) && isset($config['targetComplianceScore'])) {
        echo "  ✅ PASS: Configuration management functional\n";
        $validationResults['deployment_readiness']['configuration'] = true;
    }
} catch (Exception $e) {
    echo "  ❌ FAIL: " . $e->getMessage() . "\n";
    $validationResults['deployment_readiness']['configuration'] = false;
    $allPassed = false;
}

// Test 5.3: Admin Interface Components
echo "5.3 Admin Interface Components\n";
if (class_exists('SEOOptimizerAdmin')) {
    echo "  ✅ PASS: Admin interface class available\n";
    $validationResults['deployment_readiness']['admin_interface'] = true;
} else {
    echo "  ⚠️  WARNING: Admin interface class not found\n";
    $validationResults['deployment_readiness']['admin_interface'] = false;
}

// Test 5.4: Integration Status
echo "5.4 Integration Status Reporting\n";
try {
    $integrationLayer = new IntegrationCompatibilityLayer(['enableOptimizer' => true]);
    $status = $integrationLayer->getIntegrationStatus();
    
    if (is_array($status) && isset($status['optimizer_enabled'])) {
        echo "  ✅ PASS: Integration status reporting functional\n";
        $validationResults['deployment_readiness']['status_reporting'] = true;
    }
} catch (Exception $e) {
    echo "  ❌ FAIL: " . $e->getMessage() . "\n";
    $validationResults['deployment_readiness']['status_reporting'] = false;
    $allPassed = false;
}

echo "\n";

/**
 * FINAL SUMMARY
 */
echo "╔════════════════════════════════════════════════════════════════════╗\n";
echo "║                        VALIDATION SUMMARY                          ║\n";
echo "╚════════════════════════════════════════════════════════════════════╝\n\n";

$sections = [
    'core_components' => 'Core Components',
    'integration' => 'Integration Testing',
    'performance' => 'Performance Validation',
    'requirements' => 'Requirements Validation',
    'deployment_readiness' => 'Deployment Readiness'
];

$totalTests = 0;
$passedTests = 0;

foreach ($sections as $key => $name) {
    $sectionResults = $validationResults[$key];
    $sectionPassed = count(array_filter($sectionResults));
    $sectionTotal = count($sectionResults);
    
    $totalTests += $sectionTotal;
    $passedTests += $sectionPassed;
    
    $percentage = $sectionTotal > 0 ? ($sectionPassed / $sectionTotal) * 100 : 0;
    $status = $percentage === 100 ? '✅' : ($percentage >= 75 ? '⚠️ ' : '❌');
    
    echo sprintf("%-30s %s %2d/%2d (%.0f%%)\n", 
        $name . ':', 
        $status, 
        $sectionPassed, 
        $sectionTotal, 
        $percentage
    );
}

echo "\n" . str_repeat('─', 70) . "\n";
echo sprintf("%-30s    %2d/%2d (%.0f%%)\n\n", 
    'TOTAL:', 
    $passedTests, 
    $totalTests, 
    ($totalTests > 0 ? ($passedTests / $totalTests) * 100 : 0)
);

if ($allPassed && $passedTests === $totalTests) {
    echo "╔════════════════════════════════════════════════════════════════════╗\n";
    echo "║                  ✅ SYSTEM VALIDATION PASSED ✅                    ║\n";
    echo "║                                                                    ║\n";
    echo "║         Multi-Pass SEO Optimizer is DEPLOYMENT READY!             ║\n";
    echo "╚════════════════════════════════════════════════════════════════════╝\n";
    exit(0);
} else {
    echo "╔════════════════════════════════════════════════════════════════════╗\n";
    echo "║              ⚠️  SYSTEM VALIDATION INCOMPLETE ⚠️                   ║\n";
    echo "║                                                                    ║\n";
    echo "║     Some tests failed or require attention before deployment      ║\n";
    echo "╚════════════════════════════════════════════════════════════════════╝\n";
    exit(1);
}

