<?php
/**
 * Multi-Pass SEO Optimizer Property-Based Test Runner
 *
 * Tests the correctness properties for the Multi-Pass SEO Optimizer Engine.
 */

require_once __DIR__ . '/bootstrap.php';

echo "=== Multi-Pass SEO Optimizer Property Tests ===\n\n";

// Test Property 1: Comprehensive Issue Detection
echo "Testing Property 1: Comprehensive Issue Detection...\n";

/**
 * Feature: multi-pass-seo-optimizer, Property 1: Comprehensive Issue Detection
 * Validates: Requirements 1.1, 4.1, 4.2, 4.3, 4.4, 4.5
 * 
 * For any input content with SEO violations, the system should identify all violations 
 * with precise quantification and specific locations across all metrics
 */

$property1Passed = true;
$issueDetectionTestCases = [
    // Test case 1: Multiple keyword density violations
    [
        'title' => 'SEO SEO SEO SEO SEO Guide',
        'content' => '<p>SEO SEO SEO optimization SEO guide SEO tips SEO SEO best practices SEO SEO.</p>',
        'meta_description' => 'Short',
        'focus_keyword' => 'SEO',
        'expected_issues' => ['keyword_density_high', 'meta_description_short']
    ],
    // Test case 2: Passive voice and long sentences
    [
        'title' => 'Content Writing Guide',
        'content' => '<p>The content was written by the author and mistakes were made during the writing process which was then reviewed by editors who found that improvements were needed and these improvements were then implemented by the development team who worked on the content management system that was designed by the technical team.</p>',
        'meta_description' => 'This is a comprehensive guide about content writing that covers all the essential aspects of creating high-quality content for websites and blogs with detailed explanations.',
        'focus_keyword' => 'content',
        'expected_issues' => ['passive_voice_high', 'sentence_length_high', 'meta_description_long']
    ],
    // Test case 3: Missing keyword in title and meta
    [
        'title' => 'Ultimate Guide to Digital Marketing',
        'content' => '<p>This guide covers various aspects of online promotion and advertising strategies. The techniques discussed help businesses grow their online presence.</p>',
        'meta_description' => 'Complete guide covering digital marketing strategies, online advertising, and business growth techniques for modern companies.',
        'focus_keyword' => 'SEO',
        'expected_issues' => ['title_no_keyword', 'meta_description_no_keyword', 'keyword_density_low']
    ],
    // Test case 4: Image and alt text issues
    [
        'title' => 'SEO Image Optimization',
        'content' => '<p>Images are important for SEO optimization. <img src="test.jpg" alt="pic"> This content discusses image optimization techniques.</p>',
        'meta_description' => 'Learn about SEO image optimization techniques and best practices for improving website performance and search rankings.',
        'focus_keyword' => 'SEO',
        'expected_issues' => ['alt_text_no_keyword']
    ],
    // Test case 5: Subheading keyword overuse
    [
        'title' => 'SEO Optimization Guide',
        'content' => '<h2>SEO Basics</h2><p>Content about SEO.</p><h3>SEO Techniques</h3><p>More SEO content.</p><h2>SEO Tools</h2><p>SEO tool information.</p><h3>SEO Best Practices</h3><p>SEO guidelines.</p>',
        'meta_description' => 'Complete SEO optimization guide covering basics, techniques, tools, and best practices for improving search engine rankings.',
        'focus_keyword' => 'SEO',
        'expected_issues' => ['subheading_keyword_overuse']
    ]
];

foreach ($issueDetectionTestCases as $index => $testCase) {
    try {
        $content = [
            'title' => $testCase['title'],
            'content' => $testCase['content'],
            'meta_description' => $testCase['meta_description']
        ];
        
        $detector = new SEOIssueDetector();
        $result = $detector->detectAllIssues($content, $testCase['focus_keyword'], []);
        
        // Verify result structure
        if (!is_array($result) || !isset($result['issues']) || !isset($result['complianceScore'])) {
            $property1Passed = false;
            echo "  ✗ Test case {$index}: Invalid result structure\n";
            continue;
        }
        
        // Verify all expected issues are detected
        $detectedIssueTypes = array_map(function($issue) { return $issue->type; }, $result['issues']);
        $missingIssues = array_diff($testCase['expected_issues'], $detectedIssueTypes);
        
        if (!empty($missingIssues)) {
            $property1Passed = false;
            echo "  ✗ Test case {$index}: Missing expected issues: " . implode(', ', $missingIssues) . "\n";
            continue;
        }
        
        // Verify each detected issue has proper quantification
        foreach ($result['issues'] as $issue) {
            if (!is_object($issue) || !isset($issue->type) || !isset($issue->severity) || 
                !isset($issue->currentValue) || !isset($issue->targetValue)) {
                $property1Passed = false;
                echo "  ✗ Test case {$index}: Issue missing required properties\n";
                continue 2;
            }
            
            // Verify severity classification
            if (!in_array($issue->severity, ['critical', 'major', 'minor'])) {
                $property1Passed = false;
                echo "  ✗ Test case {$index}: Invalid severity level: {$issue->severity}\n";
                continue 2;
            }
            
            // Verify quantification makes sense
            if (!is_numeric($issue->currentValue) || !is_numeric($issue->targetValue)) {
                $property1Passed = false;
                echo "  ✗ Test case {$index}: Non-numeric quantification values\n";
                continue 2;
            }
            
            // Verify location tracking exists for applicable issues
            $locationRequiredTypes = ['keyword_density_high', 'keyword_density_low', 'passive_voice_high', 
                                    'sentence_length_high', 'meta_description_short', 'meta_description_long',
                                    'title_no_keyword', 'subheading_keyword_overuse', 'alt_text_no_keyword'];
            
            if (in_array($issue->type, $locationRequiredTypes) && empty($issue->locations)) {
                $property1Passed = false;
                echo "  ✗ Test case {$index}: Missing location data for {$issue->type}\n";
                continue 2;
            }
        }
        
        // Verify compliance score calculation
        if (!is_numeric($result['complianceScore']) || $result['complianceScore'] < 0 || $result['complianceScore'] > 100) {
            $property1Passed = false;
            echo "  ✗ Test case {$index}: Invalid compliance score: {$result['complianceScore']}\n";
            continue;
        }
        
        // Verify issue counts match
        $expectedCounts = [
            'totalIssues' => count($result['issues']),
            'criticalIssues' => count(array_filter($result['issues'], function($i) { return $i->severity === 'critical'; })),
            'majorIssues' => count(array_filter($result['issues'], function($i) { return $i->severity === 'major'; })),
            'minorIssues' => count(array_filter($result['issues'], function($i) { return $i->severity === 'minor'; }))
        ];
        
        foreach ($expectedCounts as $countType => $expectedCount) {
            if ($result[$countType] !== $expectedCount) {
                $property1Passed = false;
                echo "  ✗ Test case {$index}: Incorrect {$countType} count\n";
                continue 2;
            }
        }
        
        // Verify metrics are populated
        if (!isset($result['metrics']) || !is_array($result['metrics'])) {
            $property1Passed = false;
            echo "  ✗ Test case {$index}: Missing or invalid metrics\n";
            continue;
        }
        
        echo "  ✓ Test case {$index}: Detected " . count($result['issues']) . " issues with proper quantification and locations\n";
        
    } catch (Exception $e) {
        $property1Passed = false;
        echo "  ✗ Test case {$index}: Exception - " . $e->getMessage() . "\n";
    }
}

echo $property1Passed ? "✓ PASS\n" : "✗ FAIL\n";

// Test Property 5: Iterative Optimization Convergence
echo "Testing Property 5: Iterative Optimization Convergence...\n";

$property5Passed = true;
$testCases = [
    [
        'title' => 'A Very Long Title That Exceeds The Recommended Character Limit For SEO Optimization And Should Be Shortened',
        'content' => '<p>This content was written by someone and mistakes were made during the process which was analyzed by researchers who found that improvements were needed and these improvements were implemented by developers who worked on the system that was designed by experts. The content lacks proper keyword density and readability issues are present throughout the text.</p>',
        'meta_description' => 'Short desc', // Too short
        'focus_keyword' => 'SEO',
        'secondary_keywords' => ['optimization', 'guide']
    ]
];

foreach ($testCases as $index => $content) {
    try {
        $config = [
            'maxIterations' => 3,
            'targetComplianceScore' => 100.0,
            'enableEarlyTermination' => true,
            'stagnationThreshold' => 2,
            'minImprovementThreshold' => 1.0,
            'logLevel' => 'error'
        ];
        
        $optimizer = new MultiPassSEOOptimizer($config);
        
        $startTime = microtime(true);
        $result = $optimizer->optimizeContent($content, $content['focus_keyword'], $content['secondary_keywords']);
        $executionTime = microtime(true) - $startTime;
        
        // Check convergence properties
        if (!is_array($result) || !isset($result['progressData'])) {
            $property5Passed = false;
            echo "  ✗ Test case {$index}: Result structure invalid\n";
            continue;
        }
        
        $progressData = $result['progressData'];
        
        // Must terminate within reasonable time
        if ($executionTime >= 30) {
            $property5Passed = false;
            echo "  ✗ Test case {$index}: Took too long ({$executionTime}s)\n";
            continue;
        }
        
        // Must respect iteration limits
        if ($progressData['totalIterations'] > $config['maxIterations']) {
            $property5Passed = false;
            echo "  ✗ Test case {$index}: Exceeded max iterations\n";
            continue;
        }
        
        // Must have valid termination reason
        $validReasons = ['compliance_achieved', 'max_iterations_reached', 'stagnation_detected', 'insufficient_improvement', 'initial_compliance'];
        if (!in_array($progressData['terminationReason'], $validReasons)) {
            $property5Passed = false;
            echo "  ✗ Test case {$index}: Invalid termination reason\n";
            continue;
        }
        
        // Must show progress tracking
        if (empty($progressData['iterations'])) {
            $property5Passed = false;
            echo "  ✗ Test case {$index}: No progress tracking\n";
            continue;
        }
        
        echo "  ✓ Test case {$index}: Converged in {$progressData['totalIterations']} iterations ({$progressData['terminationReason']})\n";
        
    } catch (Exception $e) {
        $property5Passed = false;
        echo "  ✗ Test case {$index}: Exception - " . $e->getMessage() . "\n";
    }
}

echo $property5Passed ? "✓ PASS\n" : "✗ FAIL\n";

// Test Property 6: Loop Termination Guarantee
echo "Testing Property 6: Loop Termination Guarantee...\n";

$property6Passed = true;
$terminationTestCases = [
    // Already compliant content
    [
        'title' => "Best SEO Guide for Beginners",
        'content' => "<p>This comprehensive guide covers SEO optimization techniques. The content includes proper keyword density and readability. Transition words help improve flow. Additionally, the content maintains good structure. Furthermore, it provides valuable information. Moreover, the examples are clear and helpful.</p>",
        'meta_description' => "Complete SEO guide with step-by-step instructions, best practices, and expert tips for beginners and professionals.",
        'scenario' => 'compliant'
    ],
    // Problematic content
    [
        'title' => 'A Very Long Title That Exceeds The Recommended Character Limit For SEO Optimization And Should Be Shortened',
        'content' => '<p>This content was written by someone and mistakes were made during the process which was analyzed by researchers. The content lacks proper keyword density and readability issues are present throughout the text. Long sentences that exceed twenty words should be shortened for better readability and user experience.</p>',
        'meta_description' => 'Short',
        'scenario' => 'problematic'
    ]
];

foreach ($terminationTestCases as $index => $contentData) {
    try {
        $content = [
            'title' => $contentData['title'],
            'content' => $contentData['content'],
            'meta_description' => $contentData['meta_description'],
            'focus_keyword' => 'SEO',
            'secondary_keywords' => ['optimization', 'guide']
        ];
        
        $config = [
            'maxIterations' => 2,
            'targetComplianceScore' => 100.0,
            'enableEarlyTermination' => true,
            'stagnationThreshold' => 1,
            'minImprovementThreshold' => 1.0,
            'logLevel' => 'error'
        ];
        
        $optimizer = new MultiPassSEOOptimizer($config);
        
        $startTime = microtime(true);
        $result = $optimizer->optimizeContent($content, 'SEO', ['optimization', 'guide']);
        $executionTime = microtime(true) - $startTime;
        
        // Must always terminate
        if (!is_array($result)) {
            $property6Passed = false;
            echo "  ✗ {$contentData['scenario']}: Did not return result\n";
            continue;
        }
        
        // Must terminate within time limit
        if ($executionTime >= 30) {
            $property6Passed = false;
            echo "  ✗ {$contentData['scenario']}: Took too long ({$executionTime}s)\n";
            continue;
        }
        
        $progressData = $result['progressData'];
        
        // Must respect max iterations
        if ($progressData['totalIterations'] > $config['maxIterations']) {
            $property6Passed = false;
            echo "  ✗ {$contentData['scenario']}: Exceeded max iterations\n";
            continue;
        }
        
        // Must have valid termination reason
        $validReasons = ['compliance_achieved', 'max_iterations_reached', 'stagnation_detected', 'insufficient_improvement', 'initial_compliance', 'critical_error'];
        if (!in_array($progressData['terminationReason'], $validReasons)) {
            $property6Passed = false;
            echo "  ✗ {$contentData['scenario']}: Invalid termination reason: {$progressData['terminationReason']}\n";
            continue;
        }
        
        // Check specific termination logic
        if ($progressData['terminationReason'] === 'max_iterations_reached' && $progressData['totalIterations'] !== $config['maxIterations']) {
            $property6Passed = false;
            echo "  ✗ {$contentData['scenario']}: Max iterations termination inconsistent\n";
            continue;
        }
        
        if ($progressData['terminationReason'] === 'compliance_achieved' && $progressData['finalScore'] < 100.0) {
            $property6Passed = false;
            echo "  ✗ {$contentData['scenario']}: Compliance termination but score < 100%\n";
            continue;
        }
        
        // Must track all iterations
        $expectedIterationCount = $progressData['totalIterations'] + 1; // +1 for baseline
        if (count($progressData['iterations']) !== $expectedIterationCount) {
            $property6Passed = false;
            echo "  ✗ {$contentData['scenario']}: Iteration tracking inconsistent\n";
            continue;
        }
        
        echo "  ✓ {$contentData['scenario']}: Terminated correctly ({$progressData['terminationReason']}, {$progressData['totalIterations']} iterations)\n";
        
    } catch (Exception $e) {
        $property6Passed = false;
        echo "  ✗ {$contentData['scenario']}: Exception - " . $e->getMessage() . "\n";
    }
}

echo $property6Passed ? "✓ PASS\n" : "✗ FAIL\n";

// Test Property 7: Error Handling and Fallback Strategies
echo "Testing Property 7: Error Handling and Fallback Strategies...\n";

$property7Passed = true;

try {
    $errorHandler = new SEOErrorHandler();
    
    // Test error classification
    $testErrors = [
        ['message' => 'Fatal exception in validation', 'expected' => 'critical'],
        ['message' => 'Timeout waiting for response', 'expected' => 'recoverable'],
        ['message' => 'Partial validation completed', 'expected' => 'degraded']
    ];
    
    foreach ($testErrors as $testError) {
        $category = $errorHandler->classifyError($testError['message'], 'test_component');
        if ($category !== $testError['expected']) {
            $property7Passed = false;
            echo "  ✗ Error classification failed for: {$testError['message']}\n";
        }
    }
    
    // Test recovery strategy
    $recoveryResult = $errorHandler->handleErrorWithRecovery(
        'ai_provider_failure',
        'ai_correction',
        'Provider timeout',
        [],
        1
    );
    
    if (!isset($recoveryResult['strategy']) || $recoveryResult['strategy'] !== 'provider_failover') {
        $property7Passed = false;
        echo "  ✗ Recovery strategy not correctly applied\n";
    }
    
    // Test graceful degradation
    $partialResults = [
        ['check' => 'meta_description', 'status' => 'pass'],
        ['check' => 'keyword_density', 'status' => 'pass']
    ];
    $failures = [
        ['check' => 'readability', 'error' => 'Timeout']
    ];
    
    $degradationResult = $errorHandler->applyGracefulDegradation(
        'validation',
        $partialResults,
        $failures
    );
    
    if (!isset($degradationResult['degraded']) || !$degradationResult['degraded']) {
        $property7Passed = false;
        echo "  ✗ Graceful degradation not applied\n";
    }
    
    // Test fallback strategy
    $fallback = $errorHandler->getFallbackStrategy('ai_correction');
    if (!isset($fallback['primary']) || !isset($fallback['fallback_1'])) {
        $property7Passed = false;
        echo "  ✗ Fallback strategy not properly defined\n";
    }
    
    // Test user-friendly reporting
    $errors = [
        ['message' => 'Connection timeout', 'component' => 'network'],
        ['message' => 'Rate limit exceeded', 'component' => 'api']
    ];
    
    $report = $errorHandler->generateUserFriendlyReport($errors);
    if (!isset($report['summary']) || !isset($report['severity'])) {
        $property7Passed = false;
        echo "  ✗ User-friendly report not generated\n";
    }
    
    if ($property7Passed) {
        echo "  ✓ Error handling and fallback strategies validated\n";
    }
    
} catch (Exception $e) {
    $property7Passed = false;
    echo "  ✗ Exception - " . $e->getMessage() . "\n";
}

echo $property7Passed ? "✓ PASS\n" : "✗ FAIL\n";

// Summary
$totalTests = 4;
$passedTests = array_sum([$property1Passed, $property5Passed, $property6Passed, $property7Passed]);

echo "\n=== Multi-Pass Property Test Summary ===\n";
echo "Passed: {$passedTests}/{$totalTests}\n";
echo "Status: " . ($passedTests === $totalTests ? "ALL TESTS PASS" : "SOME TESTS FAILED") . "\n";

if ($passedTests === $totalTests) {
    echo "\n✓ All Multi-Pass SEO Optimizer properties are validated!\n";
    echo "The optimization engine meets all design requirements.\n";
} else {
    echo "\n✗ Some properties failed validation.\n";
    echo "Review the failed tests and fix any issues.\n";
}

echo "\n=== Multi-Pass Property Test Runner Complete ===\n";