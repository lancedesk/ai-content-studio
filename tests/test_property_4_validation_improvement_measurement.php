<?php
/**
 * Property Test 4: Validation and Improvement Measurement
 * 
 * Feature: multi-pass-seo-optimizer, Property 4: Validation and Improvement Measurement
 * Validates: Requirements 1.4, 5.1, 5.2
 * 
 * For any corrected content, the system should re-validate all SEO metrics 
 * and accurately measure improvements
 */

require_once __DIR__ . '/bootstrap.php';
require_once ACS_PLUGIN_PATH . 'seo/class-validation-improvement-tracker.php';

echo "=== Property 4: Validation and Improvement Measurement Test ===\n\n";

$property4Passed = true;
$iterationCount = 100; // Run 100 iterations as specified

echo "Running {$iterationCount} property-based test iterations...\n\n";

for ($iteration = 1; $iteration <= $iterationCount; $iteration++) {
    // Generate random content with SEO issues
    $originalContent = generateRandomContentWithIssues();
    
    // Generate corrected version with improvements
    $correctedContent = generateImprovedContent($originalContent);
    
    $focusKeyword = $originalContent['focus_keyword'];
    
    try {
        $tracker = new ValidationImprovementTracker([
            'enableCaching' => true,
            'trackTrends' => true,
            'detailedMetrics' => true
        ]);
        
        // Test validation and improvement measurement
        $result = $tracker->validateAndMeasureImprovement(
            $originalContent,
            $correctedContent,
            $focusKeyword,
            [],
            1
        );
        
        // Property 1: Result must contain original and corrected validation data
        if (!isset($result['original']) || !isset($result['corrected'])) {
            $property4Passed = false;
            echo "Iteration {$iteration}: ✗ Missing original or corrected validation data\n";
            continue;
        }
        
        // Property 2: Both validations must have compliance scores
        if (!isset($result['original']['complianceScore']) || !isset($result['corrected']['complianceScore'])) {
            $property4Passed = false;
            echo "Iteration {$iteration}: ✗ Missing compliance scores\n";
            continue;
        }
        
        // Property 3: Compliance scores must be in valid range (0-100)
        if ($result['original']['complianceScore'] < 0 || $result['original']['complianceScore'] > 100 ||
            $result['corrected']['complianceScore'] < 0 || $result['corrected']['complianceScore'] > 100) {
            $property4Passed = false;
            echo "Iteration {$iteration}: ✗ Compliance scores out of valid range\n";
            continue;
        }
        
        // Property 4: Improvements must be calculated
        if (!isset($result['improvements'])) {
            $property4Passed = false;
            echo "Iteration {$iteration}: ✗ Missing improvements calculation\n";
            continue;
        }
        
        // Property 5: Score improvement must be calculated correctly
        $expectedScoreImprovement = $result['corrected']['complianceScore'] - $result['original']['complianceScore'];
        if (abs($result['improvements']['scoreImprovement'] - $expectedScoreImprovement) > 0.01) {
            $property4Passed = false;
            echo "Iteration {$iteration}: ✗ Incorrect score improvement calculation\n";
            continue;
        }
        
        // Property 6: Issues resolved must be calculated correctly
        $expectedIssuesResolved = $result['original']['totalIssues'] - $result['corrected']['totalIssues'];
        if ($result['improvements']['issuesResolved'] !== $expectedIssuesResolved) {
            $property4Passed = false;
            echo "Iteration {$iteration}: ✗ Incorrect issues resolved calculation\n";
            continue;
        }
        
        // Property 7: Improvement metrics must include resolved, new, and persistent issues
        if (!isset($result['improvements']['resolvedIssueTypes']) || 
            !isset($result['improvements']['newIssues']) ||
            !isset($result['improvements']['persistentIssues'])) {
            $property4Passed = false;
            echo "Iteration {$iteration}: ✗ Missing issue type tracking\n";
            continue;
        }
        
        // Property 8: Summary must indicate if improvement occurred
        if (!isset($result['summary']['improved'])) {
            $property4Passed = false;
            echo "Iteration {$iteration}: ✗ Missing improvement indicator\n";
            continue;
        }
        
        // Property 9: If score improved, summary should reflect that
        if ($result['improvements']['scoreImprovement'] > 0 && !$result['summary']['improved']) {
            $property4Passed = false;
            echo "Iteration {$iteration}: ✗ Summary doesn't reflect positive improvement\n";
            continue;
        }
        
        // Property 10: Metrics must be present for both original and corrected
        if (!isset($result['original']['metrics']) || !isset($result['corrected']['metrics'])) {
            $property4Passed = false;
            echo "Iteration {$iteration}: ✗ Missing metrics data\n";
            continue;
        }
        
        // Property 11: Test caching - validate same content twice
        $result2 = $tracker->validateAndMeasureImprovement(
            $originalContent,
            $correctedContent,
            $focusKeyword,
            [],
            2
        );
        
        // Scores should be identical when validating same content
        if (abs($result2['original']['complianceScore'] - $result['original']['complianceScore']) > 0.01) {
            $property4Passed = false;
            echo "Iteration {$iteration}: ✗ Caching not working correctly\n";
            continue;
        }
        
        // Property 12: Pass history should be tracked
        $passHistory = $tracker->getPassHistory();
        if (count($passHistory) !== 2) {
            $property4Passed = false;
            echo "Iteration {$iteration}: ✗ Pass history not tracked correctly\n";
            continue;
        }
        
        // Property 13: Trend analysis should work with multiple passes
        if (isset($result2['trends'])) {
            if (!isset($result2['trends']['status']) || !isset($result2['trends']['trendDirection'])) {
                $property4Passed = false;
                echo "Iteration {$iteration}: ✗ Trend analysis missing required fields\n";
                continue;
            }
        }
        
        // Property 14: Detailed metric improvements should be calculated
        if (isset($result['improvements']['metricImprovements'])) {
            foreach ($result['improvements']['metricImprovements'] as $metric => $data) {
                if (!isset($data['original']) || !isset($data['corrected']) || !isset($data['change'])) {
                    $property4Passed = false;
                    echo "Iteration {$iteration}: ✗ Metric improvement data incomplete for {$metric}\n";
                    continue 2;
                }
            }
        }
        
        if ($iteration % 10 === 0) {
            echo "Iteration {$iteration}: ✓ All properties validated\n";
        }
        
    } catch (Exception $e) {
        $property4Passed = false;
        echo "Iteration {$iteration}: ✗ Exception - " . $e->getMessage() . "\n";
    }
}

echo "\n=== Property 4 Test Result ===\n";
echo $property4Passed ? "✓ PASS - Validation and Improvement Measurement property validated\n" : "✗ FAIL - Property validation failed\n";

if ($property4Passed) {
    echo "\nProperty 4 validates that:\n";
    echo "- Content is re-validated after corrections\n";
    echo "- Compliance scores are calculated correctly for both original and corrected content\n";
    echo "- Improvements are accurately measured (score improvement, issues resolved)\n";
    echo "- Issue types are tracked (resolved, new, persistent)\n";
    echo "- Validation results are properly aggregated\n";
    echo "- Caching works correctly for performance optimization\n";
    echo "- Pass history is tracked across multiple passes\n";
    echo "- Trend analysis works with multiple passes\n";
    echo "- Detailed metric improvements are calculated\n";
}

echo "\n=== Test Complete ===\n";

/**
 * Generate random content with SEO issues
 *
 * @return array Content with issues
 */
function generateRandomContentWithIssues() {
    $keywords = ['SEO', 'marketing', 'content', 'optimization', 'digital', 'strategy'];
    $focusKeyword = $keywords[array_rand($keywords)];
    
    // Generate content with various issues
    $issueTypes = rand(1, 4);
    
    $title = 'Test Article ' . rand(1000, 9999);
    $content = '<p>This is test content. ';
    $metaDescription = 'Short';
    
    // Randomly add issues
    if ($issueTypes >= 1) {
        // Low keyword density
        $content .= 'This article discusses various topics without mentioning the main keyword much. ';
        $content .= 'We cover different aspects of the subject matter. ';
    }
    
    if ($issueTypes >= 2) {
        // Add passive voice
        $content .= 'The content was written by the team. Mistakes were made during the process. ';
        $content .= 'The article was reviewed by editors. ';
    }
    
    if ($issueTypes >= 3) {
        // Add long sentence
        $content .= 'This is a very long sentence that goes on and on and continues to add more words and phrases and clauses that make it difficult to read and understand because it just keeps going without proper breaks or pauses which is not good for readability. ';
    }
    
    $content .= '</p>';
    
    return [
        'title' => $title,
        'content' => $content,
        'meta_description' => $metaDescription,
        'focus_keyword' => $focusKeyword
    ];
}

/**
 * Generate improved version of content
 *
 * @param array $originalContent Original content
 * @return array Improved content
 */
function generateImprovedContent($originalContent) {
    $focusKeyword = $originalContent['focus_keyword'];
    
    // Improve title
    $title = $focusKeyword . ' Guide: ' . $originalContent['title'];
    
    // Improve content - add keyword, fix passive voice, shorten sentences
    $content = '<p>This article about ' . $focusKeyword . ' covers important topics. ';
    $content .= 'We discuss ' . $focusKeyword . ' strategies and best practices. ';
    $content .= 'The team wrote this content. We made improvements during the process. ';
    $content .= 'Editors reviewed the article. ';
    $content .= 'This sentence is shorter and easier to read. ';
    $content .= 'We focus on clarity and readability. ';
    $content .= 'Learn more about ' . $focusKeyword . ' optimization.</p>';
    
    // Improve meta description
    $metaDescription = 'Complete guide to ' . $focusKeyword . ' covering strategies, best practices, and optimization techniques for better results and improved performance.';
    
    return [
        'title' => $title,
        'content' => $content,
        'meta_description' => $metaDescription,
        'focus_keyword' => $focusKeyword
    ];
}
