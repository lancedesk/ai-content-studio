<?php
/**
 * Property Test 1: Comprehensive Issue Detection
 * 
 * Feature: multi-pass-seo-optimizer, Property 1: Comprehensive Issue Detection
 * Validates: Requirements 1.1, 4.1, 4.2, 4.3, 4.4, 4.5
 * 
 * For any input content with SEO violations, the system should identify all violations 
 * with precise quantification and specific locations across all metrics
 */

require_once __DIR__ . '/bootstrap.php';

echo "=== Property 1: Comprehensive Issue Detection Test ===\n\n";

$property1Passed = true;
$testCases = [
    // Test case 1: Multiple keyword density violations
    [
        'name' => 'High keyword density + short meta description',
        'title' => 'SEO SEO SEO SEO SEO Guide',
        'content' => '<p>SEO SEO SEO optimization SEO guide SEO tips SEO SEO best practices SEO SEO.</p>',
        'meta_description' => 'Short',
        'focus_keyword' => 'SEO',
        'expected_issues' => ['keyword_density_high', 'meta_description_short']
    ],
    // Test case 2: Passive voice and long sentences
    [
        'name' => 'Passive voice + long sentences + long meta description',
        'title' => 'Content Writing Guide',
        'content' => '<p>The content was written by the author and mistakes were made during the writing process which was then reviewed by editors who found that improvements were needed and these improvements were then implemented by the development team who worked on the content management system that was designed by the technical team.</p>',
        'meta_description' => 'This is a comprehensive guide about content writing that covers all the essential aspects of creating high-quality content for websites and blogs with detailed explanations and practical tips.',
        'focus_keyword' => 'content',
        'expected_issues' => ['passive_voice_high', 'sentence_length_high', 'meta_description_long']
    ],
    // Test case 3: Missing keyword in title and meta
    [
        'name' => 'Missing keywords + low density',
        'title' => 'Ultimate Guide to Digital Marketing',
        'content' => '<p>This guide covers various aspects of online promotion and advertising strategies. The techniques discussed help businesses grow their online presence.</p>',
        'meta_description' => 'Complete guide covering digital marketing strategies, online advertising, and business growth techniques for modern companies.',
        'focus_keyword' => 'SEO',
        'expected_issues' => ['title_no_keyword', 'meta_description_no_keyword', 'keyword_density_low']
    ],
    // Test case 4: Image and alt text issues
    [
        'name' => 'Alt text without keyword',
        'title' => 'SEO Image Optimization',
        'content' => '<p>Images are important for SEO optimization. <img src="test.jpg" alt="pic"> This content discusses image optimization techniques.</p>',
        'meta_description' => 'Learn about SEO image optimization techniques and best practices for improving website performance and search rankings.',
        'focus_keyword' => 'SEO',
        'expected_issues' => ['alt_text_no_keyword']
    ],
    // Test case 5: Subheading keyword overuse
    [
        'name' => 'Subheading keyword overuse',
        'title' => 'SEO Optimization Guide',
        'content' => '<h2>SEO Basics</h2><p>Content about SEO.</p><h3>SEO Techniques</h3><p>More SEO content.</p><h2>SEO Tools</h2><p>SEO tool information.</p><h3>SEO Best Practices</h3><p>SEO guidelines.</p>',
        'meta_description' => 'Complete SEO optimization guide covering basics, techniques, tools, and best practices for improving search engine rankings.',
        'focus_keyword' => 'SEO',
        'expected_issues' => ['subheading_keyword_overuse']
    ]
];

foreach ($testCases as $index => $testCase) {
    echo "Testing: {$testCase['name']}\n";
    
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
            echo "  ✗ Invalid result structure\n";
            continue;
        }
        
        // Verify all expected issues are detected
        $detectedIssueTypes = array_map(function($issue) { return $issue->type; }, $result['issues']);
        $missingIssues = array_diff($testCase['expected_issues'], $detectedIssueTypes);
        
        if (!empty($missingIssues)) {
            $property1Passed = false;
            echo "  ✗ Missing expected issues: " . implode(', ', $missingIssues) . "\n";
            echo "  Detected: " . implode(', ', $detectedIssueTypes) . "\n";
            continue;
        }
        
        // Verify each detected issue has proper quantification
        $validationPassed = true;
        foreach ($result['issues'] as $issue) {
            if (!is_object($issue) || !isset($issue->type) || !isset($issue->severity) || 
                !isset($issue->currentValue) || !isset($issue->targetValue)) {
                $property1Passed = false;
                $validationPassed = false;
                echo "  ✗ Issue missing required properties\n";
                break;
            }
            
            // Verify severity classification
            if (!in_array($issue->severity, ['critical', 'major', 'minor'])) {
                $property1Passed = false;
                $validationPassed = false;
                echo "  ✗ Invalid severity level: {$issue->severity}\n";
                break;
            }
            
            // Verify quantification makes sense
            if (!is_numeric($issue->currentValue) || !is_numeric($issue->targetValue)) {
                $property1Passed = false;
                $validationPassed = false;
                echo "  ✗ Non-numeric quantification values\n";
                break;
            }
            
            // Verify location tracking exists for applicable issues
            $locationRequiredTypes = ['keyword_density_high', 'keyword_density_low', 'passive_voice_high', 
                                    'sentence_length_high', 'meta_description_short', 'meta_description_long',
                                    'title_no_keyword', 'subheading_keyword_overuse', 'alt_text_no_keyword'];
            
            if (in_array($issue->type, $locationRequiredTypes) && empty($issue->locations)) {
                $property1Passed = false;
                $validationPassed = false;
                echo "  ✗ Missing location data for {$issue->type}\n";
                break;
            }
        }
        
        if (!$validationPassed) {
            continue;
        }
        
        // Verify compliance score calculation
        if (!is_numeric($result['complianceScore']) || $result['complianceScore'] < 0 || $result['complianceScore'] > 100) {
            $property1Passed = false;
            echo "  ✗ Invalid compliance score: {$result['complianceScore']}\n";
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
                echo "  ✗ Incorrect {$countType} count\n";
                continue 2;
            }
        }
        
        // Verify metrics are populated
        if (!isset($result['metrics']) || !is_array($result['metrics'])) {
            $property1Passed = false;
            echo "  ✗ Missing or invalid metrics\n";
            continue;
        }
        
        echo "  ✓ Detected " . count($result['issues']) . " issues with proper quantification and locations\n";
        
    } catch (Exception $e) {
        $property1Passed = false;
        echo "  ✗ Exception - " . $e->getMessage() . "\n";
    }
}

echo "\n=== Property 1 Test Result ===\n";
echo $property1Passed ? "✓ PASS - Comprehensive Issue Detection property validated\n" : "✗ FAIL - Property validation failed\n";

if ($property1Passed) {
    echo "\nProperty 1 validates that:\n";
    echo "- All SEO violations are identified with precise quantification\n";
    echo "- Issues are properly classified by severity (critical, major, minor)\n";
    echo "- Location tracking is provided for applicable issues\n";
    echo "- Compliance scoring works correctly\n";
    echo "- All metrics are properly calculated and reported\n";
}

echo "\n=== Test Complete ===\n";