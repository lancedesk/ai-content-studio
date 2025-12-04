<?php
/**
 * Property Test 2: Targeted Correction Prompt Generation
 * 
 * Feature: multi-pass-seo-optimizer, Property 2: Targeted Correction Prompt Generation
 * Validates: Requirements 1.2, 2.1, 2.2, 2.3, 2.4, 2.5
 * 
 * For any detected SEO issue, the correction prompt generator should create specific, 
 * actionable instructions with exact metrics and expected outcomes
 */

require_once __DIR__ . '/bootstrap.php';

echo "=== Property 2: Targeted Correction Prompt Generation Test ===\n\n";

$property2Passed = true;
$testCases = [
    // Test case 1: Keyword density high - should specify exact reduction targets
    [
        'name' => 'Keyword density high',
        'issue' => new SEOIssue(
            'keyword_density_high',
            'critical',
            5.2,
            2.5,
            [
                ['position' => 10, 'length' => 3, 'context' => 'SEO optimization SEO guide SEO tips'],
                ['position' => 50, 'length' => 3, 'context' => 'best SEO practices SEO techniques']
            ],
            'Keyword density too high (5.2%, maximum 2.5%)',
            9,
            3.0
        ),
        'focus_keyword' => 'SEO',
        'expected_properties' => [
            'has_quantitative_target' => true,
            'has_specific_locations' => true,
            'has_expected_changes' => true,
            'prompt_contains_keyword' => true,
            'prompt_contains_current_value' => true,
            'prompt_contains_target_value' => true
        ]
    ],
    
    // Test case 2: Meta description short - should specify character adjustment
    [
        'name' => 'Meta description short',
        'issue' => new SEOIssue(
            'meta_description_short',
            'critical',
            45,
            120,
            [['position' => 0, 'length' => 45, 'text' => 'Short description about SEO']],
            'Meta description too short (45 chars, minimum 120)',
            10,
            3.0
        ),
        'focus_keyword' => 'SEO',
        'content' => ['meta_description' => 'Short description about SEO'],
        'expected_properties' => [
            'has_quantitative_target' => true,
            'prompt_contains_current_value' => true,
            'prompt_contains_target_value' => true,
            'prompt_contains_difference' => true,
            'prompt_contains_keyword' => true,
            'prompt_contains_current_text' => true
        ]
    ],
    
    // Test case 3: Passive voice high - should identify specific sentences
    [
        'name' => 'Passive voice high',
        'issue' => new SEOIssue(
            'passive_voice_high',
            'major',
            25.5,
            10.0,
            [
                ['sentence_index' => 0, 'sentence' => 'The content was written by the author.', 'patterns' => ['was written'], 'confidence' => 0.9],
                ['sentence_index' => 2, 'sentence' => 'Mistakes were made during the process.', 'patterns' => ['were made'], 'confidence' => 0.85]
            ],
            'Too much passive voice (25.5%, maximum 10.0%)',
            5,
            2.0
        ),
        'focus_keyword' => 'content',
        'expected_properties' => [
            'has_quantitative_target' => true,
            'has_specific_locations' => true,
            'prompt_contains_sentences' => true,
            'prompt_contains_count' => true,
            'prompt_contains_current_value' => true,
            'prompt_contains_target_value' => true
        ]
    ],
    
    // Test case 4: Sentence length high - should target specific long sentences
    [
        'name' => 'Sentence length high',
        'issue' => new SEOIssue(
            'sentence_length_high',
            'minor',
            35.0,
            25.0,
            [
                ['sentence' => 'This is a very long sentence that exceeds the recommended word count and should be split into multiple shorter sentences for better readability.', 'word_count' => 25, 'position' => 0],
                ['sentence' => 'Another long sentence that contains too many words and makes it difficult for readers to follow the main point being communicated.', 'word_count' => 22, 'position' => 1]
            ],
            'Too many long sentences (35.0%, maximum 25.0%)',
            3,
            1.0
        ),
        'focus_keyword' => 'content',
        'expected_properties' => [
            'has_quantitative_target' => true,
            'has_specific_locations' => true,
            'prompt_contains_sentences' => true,
            'prompt_contains_count' => true
        ]
    ],
    
    // Test case 5: Heading keyword overuse - should specify which headings
    [
        'name' => 'Heading keyword overuse',
        'issue' => new SEOIssue(
            'subheading_keyword_overuse',
            'minor',
            85.0,
            75.0,
            [
                ['heading' => 'SEO Basics', 'position' => 100, 'full_tag' => '<h2>SEO Basics</h2>'],
                ['heading' => 'SEO Techniques', 'position' => 200, 'full_tag' => '<h3>SEO Techniques</h3>'],
                ['heading' => 'SEO Tools', 'position' => 300, 'full_tag' => '<h2>SEO Tools</h2>']
            ],
            'Too many subheadings contain keyword (85.0%, maximum 75.0%)',
            4,
            1.0
        ),
        'focus_keyword' => 'SEO',
        'expected_properties' => [
            'has_quantitative_target' => true,
            'has_specific_locations' => true,
            'prompt_contains_headings' => true,
            'prompt_contains_count' => true,
            'prompt_contains_keyword' => true
        ]
    ],
    
    // Test case 6: Meta description long - should specify character reduction
    [
        'name' => 'Meta description long',
        'issue' => new SEOIssue(
            'meta_description_long',
            'major',
            180,
            156,
            [['position' => 156, 'length' => 24, 'text' => ' with extra information']],
            'Meta description too long (180 chars, maximum 156)',
            7,
            2.0
        ),
        'focus_keyword' => 'SEO',
        'content' => ['meta_description' => 'This is a comprehensive SEO guide that covers all aspects of search engine optimization including keyword research, on-page optimization, and link building with extra information'],
        'expected_properties' => [
            'has_quantitative_target' => true,
            'prompt_contains_current_value' => true,
            'prompt_contains_target_value' => true,
            'prompt_contains_difference' => true,
            'prompt_contains_keyword' => true
        ]
    ],
    
    // Test case 7: Title too long - should specify character reduction
    [
        'name' => 'Title too long',
        'issue' => new SEOIssue(
            'title_too_long',
            'major',
            85,
            66,
            [['position' => 66, 'length' => 19, 'text' => ' And More Details']],
            'Title too long (85 chars, maximum 66)',
            7,
            2.0
        ),
        'focus_keyword' => 'SEO',
        'content' => ['title' => 'Complete SEO Optimization Guide For Beginners And Advanced Users And More Details'],
        'expected_properties' => [
            'has_quantitative_target' => true,
            'prompt_contains_current_value' => true,
            'prompt_contains_target_value' => true,
            'prompt_contains_difference' => true,
            'prompt_contains_current_text' => true
        ]
    ],
    
    // Test case 8: Keyword density low - should specify increase targets
    [
        'name' => 'Keyword density low',
        'issue' => new SEOIssue(
            'keyword_density_low',
            'major',
            0.2,
            0.5,
            [['position' => 0, 'length' => 0, 'context' => 'Content without enough keywords', 'note' => 'Keyword not found in content']],
            'Keyword density too low (0.2%, minimum 0.5%)',
            8,
            2.0
        ),
        'focus_keyword' => 'SEO',
        'expected_properties' => [
            'has_quantitative_target' => true,
            'prompt_contains_current_value' => true,
            'prompt_contains_target_value' => true,
            'prompt_contains_count' => true,
            'prompt_contains_keyword' => true
        ]
    ]
];

foreach ($testCases as $index => $testCase) {
    echo "Testing: {$testCase['name']}\n";
    
    try {
        $generator = new CorrectionPromptGenerator();
        $content = $testCase['content'] ?? [];
        
        $prompt = $generator->generatePromptForIssue(
            $testCase['issue'],
            $testCase['focus_keyword'],
            $content
        );
        
        // Verify prompt was generated
        if ($prompt === null) {
            $property2Passed = false;
            echo "  ✗ No prompt generated for issue type: {$testCase['issue']->type}\n";
            continue;
        }
        
        // Verify prompt is a CorrectionPrompt object
        if (!($prompt instanceof CorrectionPrompt)) {
            $property2Passed = false;
            echo "  ✗ Invalid prompt type returned\n";
            continue;
        }
        
        // Verify required properties exist
        if (empty($prompt->issueType) || empty($prompt->promptText)) {
            $property2Passed = false;
            echo "  ✗ Missing required prompt properties\n";
            continue;
        }
        
        // Verify issue type matches
        if ($prompt->issueType !== $testCase['issue']->type) {
            $property2Passed = false;
            echo "  ✗ Issue type mismatch\n";
            continue;
        }
        
        // Verify priority is set correctly
        if (!is_int($prompt->priority) || $prompt->priority < 1 || $prompt->priority > 10) {
            $property2Passed = false;
            echo "  ✗ Invalid priority value: {$prompt->priority}\n";
            continue;
        }
        
        // Test expected properties
        $validationPassed = true;
        foreach ($testCase['expected_properties'] as $property => $expected) {
            $result = false;
            
            switch ($property) {
                case 'has_quantitative_target':
                    $result = $prompt->quantitativeTarget !== null && is_array($prompt->quantitativeTarget);
                    break;
                    
                case 'has_specific_locations':
                    $result = !empty($prompt->targetLocations);
                    break;
                    
                case 'has_expected_changes':
                    $result = !empty($prompt->expectedChanges) && isset($prompt->expectedChanges['metric']);
                    break;
                    
                case 'prompt_contains_keyword':
                    $result = stripos($prompt->promptText, $testCase['focus_keyword']) !== false;
                    break;
                    
                case 'prompt_contains_current_value':
                    $result = stripos($prompt->promptText, (string)$testCase['issue']->currentValue) !== false ||
                              stripos($prompt->promptText, number_format($testCase['issue']->currentValue, 1)) !== false;
                    break;
                    
                case 'prompt_contains_target_value':
                    $result = stripos($prompt->promptText, (string)$testCase['issue']->targetValue) !== false ||
                              stripos($prompt->promptText, number_format($testCase['issue']->targetValue, 1)) !== false;
                    break;
                    
                case 'prompt_contains_difference':
                    $diff = abs($testCase['issue']->currentValue - $testCase['issue']->targetValue);
                    $result = stripos($prompt->promptText, (string)(int)$diff) !== false;
                    break;
                    
                case 'prompt_contains_count':
                    // Check if prompt contains a count/number
                    $result = preg_match('/\d+/', $prompt->promptText) === 1;
                    break;
                    
                case 'prompt_contains_sentences':
                    $result = stripos($prompt->promptText, 'sentence') !== false;
                    break;
                    
                case 'prompt_contains_headings':
                    $result = stripos($prompt->promptText, 'heading') !== false;
                    break;
                    
                case 'prompt_contains_current_text':
                    if (isset($content['meta_description'])) {
                        $result = stripos($prompt->promptText, substr($content['meta_description'], 0, 20)) !== false;
                    } elseif (isset($content['title'])) {
                        $result = stripos($prompt->promptText, substr($content['title'], 0, 20)) !== false;
                    }
                    break;
            }
            
            if ($expected && !$result) {
                $property2Passed = false;
                $validationPassed = false;
                echo "  ✗ Expected property not found: {$property}\n";
                break;
            }
        }
        
        if (!$validationPassed) {
            continue;
        }
        
        // Verify quantitative target structure if present
        if ($prompt->quantitativeTarget !== null) {
            $requiredKeys = ['current', 'target', 'difference', 'count', 'action'];
            foreach ($requiredKeys as $key) {
                if (!isset($prompt->quantitativeTarget[$key])) {
                    $property2Passed = false;
                    echo "  ✗ Missing quantitative target key: {$key}\n";
                    continue 2;
                }
            }
            
            // Verify action is valid
            if (!in_array($prompt->quantitativeTarget['action'], ['reduce', 'increase'])) {
                $property2Passed = false;
                echo "  ✗ Invalid quantitative action: {$prompt->quantitativeTarget['action']}\n";
                continue;
            }
            
            // Verify count is positive
            if ($prompt->quantitativeTarget['count'] < 1) {
                $property2Passed = false;
                echo "  ✗ Invalid change count: {$prompt->quantitativeTarget['count']}\n";
                continue;
            }
        }
        
        // Verify expected changes structure
        if (!empty($prompt->expectedChanges)) {
            if (!isset($prompt->expectedChanges['metric']) || 
                !isset($prompt->expectedChanges['currentValue']) || 
                !isset($prompt->expectedChanges['targetValue'])) {
                $property2Passed = false;
                echo "  ✗ Invalid expected changes structure\n";
                continue;
            }
        }
        
        // Verify target locations structure if present
        if (!empty($prompt->targetLocations)) {
            foreach ($prompt->targetLocations as $location) {
                if (!is_array($location)) {
                    $property2Passed = false;
                    echo "  ✗ Invalid target location structure\n";
                    continue 2;
                }
            }
        }
        
        echo "  ✓ Generated targeted prompt with quantitative targets and specific instructions\n";
        echo "    Priority: {$prompt->priority}, Changes: {$prompt->quantitativeTarget['count']}, Action: {$prompt->quantitativeTarget['action']}\n";
        
    } catch (Exception $e) {
        $property2Passed = false;
        echo "  ✗ Exception - " . $e->getMessage() . "\n";
    }
}

// Test priority-based ordering
echo "\nTesting priority-based ordering...\n";
try {
    $generator = new CorrectionPromptGenerator();
    
    // Create multiple issues with different severities
    $issues = [
        new SEOIssue('keyword_density_low', 'major', 0.2, 0.5, [], 'Low density', 8, 2.0),
        new SEOIssue('meta_description_short', 'critical', 45, 120, [], 'Short meta', 10, 3.0),
        new SEOIssue('sentence_length_high', 'minor', 35.0, 25.0, [], 'Long sentences', 3, 1.0),
        new SEOIssue('title_no_keyword', 'critical', 0, 1, [], 'No keyword in title', 9, 3.0)
    ];
    
    $prompts = $generator->generatePromptsForIssues($issues, 'SEO', []);
    
    // Verify prompts are sorted by priority
    if (count($prompts) !== count($issues)) {
        $property2Passed = false;
        echo "  ✗ Not all prompts generated\n";
    } else {
        $previousPriority = 11; // Start higher than max
        $sortedCorrectly = true;
        
        foreach ($prompts as $prompt) {
            if ($prompt->priority > $previousPriority) {
                $sortedCorrectly = false;
                break;
            }
            $previousPriority = $prompt->priority;
        }
        
        if (!$sortedCorrectly) {
            $property2Passed = false;
            echo "  ✗ Prompts not sorted by priority correctly\n";
        } else {
            echo "  ✓ Prompts correctly sorted by priority (highest first)\n";
        }
    }
    
} catch (Exception $e) {
    $property2Passed = false;
    echo "  ✗ Exception in priority ordering - " . $e->getMessage() . "\n";
}

echo "\n=== Property 2 Test Result ===\n";
echo $property2Passed ? "✓ PASS - Targeted Correction Prompt Generation property validated\n" : "✗ FAIL - Property validation failed\n";

if ($property2Passed) {
    echo "\nProperty 2 validates that:\n";
    echo "- Correction prompts contain specific, actionable instructions\n";
    echo "- Quantitative targets are calculated with exact metrics\n";
    echo "- Prompts include current values, target values, and differences\n";
    echo "- Specific locations and context are provided where applicable\n";
    echo "- Expected changes are clearly defined\n";
    echo "- Prompts are prioritized correctly based on severity\n";
    echo "- All issue types generate appropriate correction prompts\n";
}

echo "\n=== Test Complete ===\n";
