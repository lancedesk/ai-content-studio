<?php
use PHPUnit\Framework\TestCase;
use Eris\Generator;

require_once __DIR__ . '/bootstrap.php';

/**
 * Property-based tests for SEO validation framework
 * 
 * **Feature: content-generation-fixes**
 */
class TestSEOValidationProperties extends TestCase {
    use Eris\TestTrait;

    /**
     * **Feature: content-generation-fixes, Property 13: Comprehensive SEO validation**
     * **Validates: Requirements 6.1, 6.2, 6.4**
     * 
     * For any completed content generation, all Yoast SEO and RankMath requirements 
     * should be verified, issues should be automatically corrected, and compliant 
     * content should be marked for publication
     */
    public function test_comprehensive_seo_validation_consistency() {
        $this->minimumEvaluationRatio(0.01);
        
        $this->forAll(
            Generator\choose(0, 5), // error count
            Generator\choose(0, 3)  // warning count
        )
        ->then(function($errorCount, $warningCount) {
            // Create validation result
            $result = new SEOValidationResult();
            
            // Add errors and warnings
            for ($i = 0; $i < $errorCount; $i++) {
                $result->addError("Test error {$i}", "component{$i}");
            }
            
            for ($i = 0; $i < $warningCount; $i++) {
                $result->addWarning("Test warning {$i}", "component{$i}");
            }
            
            $result->calculateScore();
            
            // Property: Score should always be between 0 and 100
            $this->assertGreaterThanOrEqual(0, $result->overallScore);
            $this->assertLessThanOrEqual(100, $result->overallScore);
            
            // Property: Issue count should match errors + warnings
            $expectedIssueCount = $errorCount + $warningCount;
            $this->assertEquals($expectedIssueCount, $result->getIssueCount());
            
            // Property: Score should decrease with more issues
            $expectedScore = max(0, 100 - ($errorCount * 20) - ($warningCount * 5));
            $this->assertEquals($expectedScore, $result->overallScore);
        });
    }
    
    /**
     * **Feature: content-generation-fixes, Property 1: Meta description compliance**
     * **Validates: Requirements 1.1, 1.2**
     * 
     * For any generated content, the meta description should be between 120-156 characters 
     * and contain the focus keyword or close synonym
     */
    public function test_meta_description_compliance() {
        $this->minimumEvaluationRatio(0.01);
        
        $this->forAll(
            Generator\elements(['seo', 'wordpress', 'content', 'marketing', 'blog']), // focus keyword
            Generator\seq(Generator\elements(['optimization', 'guide', 'tips', 'tutorial', 'best practices'])), // synonyms
            Generator\string() // meta description input
        )
        ->then(function($focusKeyword, $synonyms, $metaDescription) {
            // Create validator and corrector
            $validator = new MetaDescriptionValidator(120, 156, true);
            $corrector = new MetaDescriptionCorrector(120, 156);
            
            // Auto-correct the meta description
            $result = $corrector->autoCorrect($metaDescription, $focusKeyword, $synonyms);
            
            // Property 1: Corrected description should be within length bounds (120-156 chars)
            $correctedLength = strlen($result['corrected']);
            $this->assertGreaterThanOrEqual(120, $correctedLength, 
                "Meta description must be at least 120 characters, got {$correctedLength}");
            $this->assertLessThanOrEqual(156, $correctedLength, 
                "Meta description must be at most 156 characters, got {$correctedLength}");
            
            // Property 2: Corrected description should contain the focus keyword or synonym
            $lowerCorrected = strtolower($result['corrected']);
            $lowerKeyword = strtolower($focusKeyword);
            $hasKeyword = strpos($lowerCorrected, $lowerKeyword) !== false;
            
            $hasSynonym = false;
            foreach ($synonyms as $synonym) {
                if (strpos($lowerCorrected, strtolower($synonym)) !== false) {
                    $hasSynonym = true;
                    break;
                }
            }
            
            $this->assertTrue($hasKeyword || $hasSynonym, 
                "Meta description must contain focus keyword '{$focusKeyword}' or a synonym");
            
            // Property 3: Validation should pass for corrected description
            $validation = $validator->validate($result['corrected'], $focusKeyword, $synonyms);
            $this->assertTrue($validation->isValid, 
                "Corrected meta description should pass validation");
            
            // Property 4: Character count should match actual length
            $this->assertEquals($correctedLength, $result['characterCount'], 
                "Character count should match actual length");
        });
    }
    
    /**
     * **Feature: content-generation-fixes, Property 9: Retry mechanism effectiveness**
     * **Validates: Requirements 1.3, 1.4, 3.5, 4.4, 4.5**
     * 
     * For any validation failure (meta description, readability, uniqueness), the system 
     * should retry generation until compliance is achieved or fallback rules are applied
     */
    public function test_retry_mechanism_effectiveness() {
        $this->minimumEvaluationRatio(0.01);
        
        $this->forAll(
            Generator\elements(['seo', 'wordpress', 'content', 'marketing', 'blog']), // focus keyword
            Generator\seq(Generator\elements(['optimization', 'guide', 'tips', 'tutorial', 'best practices'])), // synonyms
            Generator\oneOf(
                Generator\constant('short'),  // too short descriptions
                Generator\constant('This is a very long description that exceeds the normal length requirements for meta descriptions and should trigger the retry mechanism to correct it properly'), // too long descriptions
                Generator\constant('')               // empty descriptions
            ),
            Generator\choose(1, 5) // max attempts
        )
        ->then(function($focusKeyword, $synonyms, $problematicDescription, $maxAttempts) {
            $corrector = new MetaDescriptionCorrector(120, 156);
            
            // Test retry mechanism with problematic input
            $result = $corrector->correctWithRetry($problematicDescription, $focusKeyword, $synonyms, $maxAttempts);
            
            // Property 1: Retry mechanism should always return a result
            $this->assertIsArray($result, "Retry mechanism must return a result array");
            $this->assertArrayHasKey('success', $result, "Result must indicate success status");
            $this->assertArrayHasKey('metaDescription', $result, "Result must contain final meta description");
            $this->assertArrayHasKey('attempts', $result, "Result must track number of attempts");
            
            // Property 2: Final meta description should always be compliant
            $validator = new MetaDescriptionValidator(120, 156, true);
            $finalValidation = $validator->validate($result['metaDescription'], $focusKeyword, $synonyms);
            $this->assertTrue($finalValidation->isValid, 
                "Final meta description must be compliant after retry mechanism");
            
            // Property 3: Number of attempts should not exceed maximum
            $this->assertLessThanOrEqual($maxAttempts, $result['attempts'], 
                "Number of attempts should not exceed maximum allowed");
            $this->assertGreaterThan(0, $result['attempts'], 
                "At least one attempt should be made");
            
            // Property 4: If success is false, fallback should be used
            if (!$result['success']) {
                $this->assertArrayHasKey('usedFallback', $result, 
                    "Failed attempts should indicate fallback usage");
                $this->assertTrue($result['usedFallback'], 
                    "Fallback should be used when all attempts fail");
                
                // Property 5: Fallback should always be compliant
                $this->assertTrue($finalValidation->isValid, 
                    "Fallback meta description must be compliant");
            }
            
            // Property 6: Attempt details should be tracked
            $this->assertArrayHasKey('attemptDetails', $result, 
                "Retry mechanism should track attempt details");
            $this->assertIsArray($result['attemptDetails'], 
                "Attempt details should be an array");
            $this->assertCount($result['attempts'], $result['attemptDetails'], 
                "Number of attempt details should match number of attempts");
            
            // Property 7: Final meta description should meet length requirements
            $finalLength = strlen($result['metaDescription']);
            $this->assertGreaterThanOrEqual(120, $finalLength, 
                "Final meta description must be at least 120 characters");
            $this->assertLessThanOrEqual(156, $finalLength, 
                "Final meta description must be at most 156 characters");
            
            // Property 8: Final meta description should contain keyword or synonym
            $lowerDescription = strtolower($result['metaDescription']);
            $lowerKeyword = strtolower($focusKeyword);
            $hasKeyword = strpos($lowerDescription, $lowerKeyword) !== false;
            
            $hasSynonym = false;
            foreach ($synonyms as $synonym) {
                if (strpos($lowerDescription, strtolower($synonym)) !== false) {
                    $hasSynonym = true;
                    break;
                }
            }
            
            $this->assertTrue($hasKeyword || $hasSynonym, 
                "Final meta description must contain focus keyword or synonym");
        });
    }
    
    /**
     * **Feature: content-generation-fixes, Property 2: Keyword density optimization**
     * **Validates: Requirements 2.1, 2.2, 2.3**
     * 
     * For any generated content, keyword density should remain between 0.5-2.5% of total 
     * word count, and the system should automatically adjust density when outside this range
     */
    public function test_keyword_density_optimization() {
        $this->minimumEvaluationRatio(0.01);
        
        $this->forAll(
            Generator\elements(['seo', 'wordpress', 'content', 'marketing', 'blog']), // focus keyword
            Generator\seq(Generator\elements(['optimization', 'guide', 'tips', 'tutorial', 'best practices'])), // synonyms
            Generator\oneOf(
                // Generate content with various keyword densities
                $this->generateContentWithDensity(0.1), // too low density
                $this->generateContentWithDensity(3.5), // too high density
                $this->generateContentWithDensity(1.5)  // optimal density
            ),
            Generator\elements([0.5, 1.0, 1.5, 2.0, 2.5]) // target density
        )
        ->then(function($focusKeyword, $synonyms, $content, $targetDensity) {
            $optimizer = new Keyword_Density_Optimizer();
            $calculator = new Keyword_Density_Calculator();
            
            // Calculate initial density
            $initialMetrics = $calculator->calculate_density($content, $focusKeyword, $synonyms);
            
            // Optimize the content
            $result = $optimizer->optimize_content($content, $focusKeyword, $synonyms, $targetDensity, 0.5, 2.5);
            
            // Calculate final density
            $finalMetrics = $calculator->calculate_density($result['content'], $focusKeyword, $synonyms);
            
            // Property 1: Final density should be within acceptable range (0.5-2.5%)
            $this->assertGreaterThanOrEqual(0.5, $finalMetrics['overall_density'], 
                "Final keyword density must be at least 0.5%, got {$finalMetrics['overall_density']}%");
            $this->assertLessThanOrEqual(2.5, $finalMetrics['overall_density'], 
                "Final keyword density must be at most 2.5%, got {$finalMetrics['overall_density']}%");
            
            // Property 2: If initial density was outside range, optimization should occur
            if ($initialMetrics['overall_density'] < 0.5 || $initialMetrics['overall_density'] > 2.5) {
                $this->assertTrue($result['optimized'], 
                    "Content with density {$initialMetrics['overall_density']}% should be optimized");
                $this->assertNotEmpty($result['changes_made'], 
                    "Optimization should record changes made");
            }
            
            // Property 3: Optimization should move density closer to target
            if ($result['optimized']) {
                $initialDistance = abs($initialMetrics['overall_density'] - $targetDensity);
                $finalDistance = abs($finalMetrics['overall_density'] - $targetDensity);
                $this->assertLessThanOrEqual($initialDistance, $finalDistance, 
                    "Optimization should move density closer to target");
            }
            
            // Property 4: Content structure should remain valid HTML
            $this->assertNotEmpty($result['content'], "Optimized content should not be empty");
            
            // Property 5: Word count should not decrease significantly (max 10% reduction)
            $initialWords = $initialMetrics['total_words'];
            $finalWords = $finalMetrics['total_words'];
            if ($initialWords > 0) {
                $wordReduction = ($initialWords - $finalWords) / $initialWords;
                $this->assertLessThanOrEqual(0.1, $wordReduction, 
                    "Word count should not decrease by more than 10%");
            }
            
            // Property 6: Keyword count should change appropriately
            if ($initialMetrics['overall_density'] < 0.5) {
                // Should increase keyword count
                $this->assertGreaterThanOrEqual($initialMetrics['keyword_count'], $finalMetrics['keyword_count'], 
                    "Low density content should have keyword count increased");
            } elseif ($initialMetrics['overall_density'] > 2.5) {
                // Should decrease keyword count
                $this->assertLessThanOrEqual($initialMetrics['keyword_count'], $finalMetrics['keyword_count'], 
                    "High density content should have keyword count decreased");
            }
            
            // Property 7: Final metrics should be consistent with content
            $verificationMetrics = $calculator->calculate_density($result['content'], $focusKeyword, $synonyms);
            $this->assertEquals($finalMetrics['overall_density'], $verificationMetrics['overall_density'], 
                "Final metrics should be consistent with actual content");
        });
    }
    
    /**
     * **Feature: content-generation-fixes, Property 3: Keyword density calculation inclusivity**
     * **Validates: Requirements 2.4**
     * 
     * For any content analysis, keyword density calculation should include synonyms 
     * and related terms in addition to the exact keyword
     */
    public function test_keyword_calculation_inclusivity() {
        $this->minimumEvaluationRatio(0.01);
        
        $this->forAll(
            Generator\elements(['seo', 'wordpress', 'content', 'marketing', 'blog']), // focus keyword
            Generator\seq(Generator\elements(['optimization', 'guide', 'tips', 'tutorial', 'best practices'])), // synonyms
            Generator\choose(1, 5), // number of focus keyword instances
            Generator\choose(1, 5)  // number of synonym instances
        )
        ->then(function($focusKeyword, $synonyms, $focusCount, $synonymCount) {
            $calculator = new Keyword_Density_Calculator();
            
            // Create content with both focus keyword and synonyms
            $content = "<p>This is a comprehensive article. ";
            
            // Add focus keyword instances
            for ($i = 0; $i < $focusCount; $i++) {
                $content .= "The {$focusKeyword} method is effective. ";
            }
            
            // Add synonym instances
            $synonymsToUse = array_slice($synonyms, 0, min(count($synonyms), 3)); // Use up to 3 synonyms
            foreach ($synonymsToUse as $index => $synonym) {
                $instancesForThisSynonym = $index < $synonymCount ? 1 : 0;
                for ($j = 0; $j < $instancesForThisSynonym; $j++) {
                    $content .= "This {$synonym} approach works well. ";
                }
            }
            
            // Add filler content
            $content .= "Additional content to provide context and increase word count for proper density calculation. ";
            $content .= "More text to ensure we have enough words for meaningful density percentages. ";
            $content .= "</p>";
            
            // Calculate density with and without synonyms
            $densityWithSynonyms = $calculator->calculate_density($content, $focusKeyword, $synonyms);
            $densityWithoutSynonyms = $calculator->calculate_density($content, $focusKeyword, []);
            
            // Property 1: Density with synonyms should be higher than or equal to density without synonyms
            $this->assertGreaterThanOrEqual($densityWithoutSynonyms['overall_density'], 
                $densityWithSynonyms['overall_density'], 
                "Density with synonyms should be >= density without synonyms");
            
            // Property 2: Keyword count with synonyms should include all instances
            $expectedMinKeywordCount = $focusCount; // At minimum, should count focus keyword instances
            $this->assertGreaterThanOrEqual($expectedMinKeywordCount, $densityWithSynonyms['keyword_count'], 
                "Keyword count should include at least the focus keyword instances");
            
            // Property 3: If synonyms are present in content, density should increase
            $synonymsInContent = 0;
            $contentLower = strtolower($content);
            foreach ($synonyms as $synonym) {
                if (strpos($contentLower, strtolower($synonym)) !== false) {
                    $synonymsInContent++;
                }
            }
            
            if ($synonymsInContent > 0) {
                $this->assertGreater($densityWithSynonyms['keyword_count'], $densityWithoutSynonyms['keyword_count'], 
                    "When synonyms are present, keyword count should be higher with synonym inclusion");
            }
            
            // Property 4: Density calculation should be consistent
            $recalculatedDensity = $calculator->calculate_density($content, $focusKeyword, $synonyms);
            $this->assertEquals($densityWithSynonyms['overall_density'], $recalculatedDensity['overall_density'], 
                "Density calculation should be consistent across multiple calls");
            
            // Property 5: Total word count should remain the same regardless of synonym inclusion
            $this->assertEquals($densityWithoutSynonyms['total_words'], $densityWithSynonyms['total_words'], 
                "Total word count should not change based on synonym inclusion");
            
            // Property 6: Density should be calculated correctly
            if ($densityWithSynonyms['total_words'] > 0) {
                $expectedDensity = ($densityWithSynonyms['keyword_count'] / $densityWithSynonyms['total_words']) * 100;
                $this->assertEquals(round($expectedDensity, 2), $densityWithSynonyms['overall_density'], 
                    "Density should be calculated as (keyword_count / total_words) * 100");
            }
            
            // Property 7: Empty synonyms array should not cause errors
            $densityEmptySynonyms = $calculator->calculate_density($content, $focusKeyword, []);
            $this->assertIsArray($densityEmptySynonyms, "Empty synonyms array should not cause errors");
            $this->assertArrayHasKey('overall_density', $densityEmptySynonyms, 
                "Result should contain overall_density key");
            
            // Property 8: Case insensitive matching should work
            $upperCaseSynonyms = array_map('strtoupper', $synonyms);
            $densityUpperCase = $calculator->calculate_density($content, strtoupper($focusKeyword), $upperCaseSynonyms);
            $this->assertEquals($densityWithSynonyms['keyword_count'], $densityUpperCase['keyword_count'], 
                "Keyword matching should be case insensitive");
        });
    }
    
    /**
     * **Feature: content-generation-fixes, Property 4: Subheading keyword optimization limits**
     * **Validates: Requirements 2.5**
     * 
     * For any generated content, when keyword usage in subheadings exceeds 75%, 
     * the system should rewrite subheadings to reduce optimization
     */
    public function test_subheading_optimization_limits() {
        $this->minimumEvaluationRatio(0.01);
        
        $this->forAll(
            Generator\elements(['seo', 'wordpress', 'content', 'marketing', 'blog']), // focus keyword
            Generator\seq(Generator\elements(['optimization', 'guide', 'tips', 'tutorial', 'best practices'])), // synonyms
            Generator\choose(3, 8), // number of subheadings
            Generator\choose(80, 100) // percentage of subheadings with keywords (over 75%)
        )
        ->then(function($focusKeyword, $synonyms, $totalSubheadings, $keywordPercentage) {
            $calculator = new Keyword_Density_Calculator();
            $optimizer = new Keyword_Density_Optimizer();
            
            // Create content with excessive keyword usage in subheadings
            $keywordSubheadings = ceil(($keywordPercentage / 100) * $totalSubheadings);
            $content = "<p>Introduction paragraph with some content.</p>";
            
            // Add subheadings with keywords
            for ($i = 1; $i <= $keywordSubheadings; $i++) {
                $content .= "<h2>Best {$focusKeyword} Practices for Section {$i}</h2>";
                $content .= "<p>Content for section {$i} with relevant information.</p>";
            }
            
            // Add subheadings without keywords
            for ($i = $keywordSubheadings + 1; $i <= $totalSubheadings; $i++) {
                $content .= "<h2>Additional Information for Section {$i}</h2>";
                $content .= "<p>Content for section {$i} with relevant information.</p>";
            }
            
            // Calculate initial subheading density
            $initialMetrics = $calculator->calculate_density($content, $focusKeyword, $synonyms);
            
            // Optimize the content
            $result = $optimizer->optimize_content($content, $focusKeyword, $synonyms, 1.5, 0.5, 2.5);
            
            // Calculate final subheading density
            $finalMetrics = $calculator->calculate_density($result['content'], $focusKeyword, $synonyms);
            
            // Property 1: Initial subheading density should be over 75%
            $this->assertGreater($initialMetrics['subheading_density'], 75.0, 
                "Initial subheading density should be over 75% for this test");
            
            // Property 2: Final subheading density should be reduced
            $this->assertLessThan($initialMetrics['subheading_density'], $finalMetrics['subheading_density'], 
                "Subheading density should be reduced when initially over 75%");
            
            // Property 3: Optimization should occur when subheading density > 75%
            if ($initialMetrics['subheading_density'] > 75.0) {
                $this->assertTrue($result['optimized'], 
                    "Content with subheading density > 75% should be optimized");
                
                // Check if subheading optimization was mentioned in changes
                $subheadingOptimized = false;
                foreach ($result['changes_made'] as $change) {
                    if (strpos(strtolower($change), 'subheading') !== false) {
                        $subheadingOptimized = true;
                        break;
                    }
                }
                $this->assertTrue($subheadingOptimized, 
                    "Changes should mention subheading optimization");
            }
            
            // Property 4: Content structure should remain valid
            $this->assertNotEmpty($result['content'], "Optimized content should not be empty");
            
            // Property 5: Number of subheadings should remain the same
            preg_match_all('/<h[1-6][^>]*>.*?<\/h[1-6]>/i', $content, $initialHeadings);
            preg_match_all('/<h[1-6][^>]*>.*?<\/h[1-6]>/i', $result['content'], $finalHeadings);
            $this->assertEquals(count($initialHeadings[0]), count($finalHeadings[0]), 
                "Number of subheadings should remain the same after optimization");
            
            // Property 6: Some subheadings should no longer contain keywords
            if ($result['optimized'] && $initialMetrics['subheading_density'] > 75.0) {
                $this->assertLessThan($initialMetrics['subheading_keyword_count'], 
                    $finalMetrics['subheading_keyword_count'], 
                    "Number of subheadings with keywords should be reduced");
            }
            
            // Property 7: Validation should pass for subheading density
            $validation = $calculator->validate_subheading_density($finalMetrics['subheading_density']);
            $this->assertFalse($validation['is_excessive'], 
                "Final subheading density should not be excessive");
            
            // Property 8: Content should still be readable and meaningful
            $this->assertGreaterThan(0, strlen(strip_tags($result['content'])), 
                "Optimized content should still contain text");
            
            // Property 9: Keyword density calculation should be consistent
            $verificationMetrics = $calculator->calculate_density($result['content'], $focusKeyword, $synonyms);
            $this->assertEquals($finalMetrics['subheading_density'], $verificationMetrics['subheading_density'], 
                "Subheading density calculation should be consistent");
        });
    }
    
    /**
     * **Feature: content-generation-fixes, Property 5: Readability compliance**
     * **Validates: Requirements 3.1, 3.2, 3.3**
     * 
     * For any generated content, passive voice usage should remain below 10%, 
     * no more than 25% of sentences should exceed 20 words, and at least 30% 
     * of sentences should contain transition words
     */
    public function test_readability_compliance() {
        $this->minimumEvaluationRatio(0.01);
        
        $this->forAll(
            Generator\oneOf(
                // Generate content with various readability issues
                $this->generateContentWithPassiveVoice(15), // high passive voice
                $this->generateContentWithLongSentences(35), // high long sentences
                $this->generateContentWithFewTransitions(10), // low transitions
                $this->generateReadableContent() // good readability
            )
        )
        ->then(function($content) {
            $passiveAnalyzer = new PassiveVoiceAnalyzer(10.0);
            $lengthAnalyzer = new SentenceLengthAnalyzer(20, 25.0);
            $transitionAnalyzer = new TransitionWordAnalyzer(30.0);
            $corrector = new ReadabilityCorrector();
            
            // Analyze initial readability
            $initialPassive = $passiveAnalyzer->analyze($content);
            $initialLength = $lengthAnalyzer->analyze($content);
            $initialTransitions = $transitionAnalyzer->analyze($content);
            
            // Apply readability corrections
            $correctionResult = $corrector->correctReadability($content);
            $correctedContent = $correctionResult['corrected_content'];
            
            // Analyze final readability
            $finalPassive = $passiveAnalyzer->analyze($correctedContent);
            $finalLength = $lengthAnalyzer->analyze($correctedContent);
            $finalTransitions = $transitionAnalyzer->analyze($correctedContent);
            
            // Property 1: Final passive voice should be <= 10%
            $this->assertLessThanOrEqual(10.0, $finalPassive['passivePercentage'], 
                "Final passive voice percentage must be <= 10%, got {$finalPassive['passivePercentage']}%");
            
            // Property 2: Final long sentence percentage should be <= 25%
            $this->assertLessThanOrEqual(25.0, $finalLength['longSentencePercentage'], 
                "Final long sentence percentage must be <= 25%, got {$finalLength['longSentencePercentage']}%");
            
            // Property 3: Final transition word percentage should be >= 30%
            $this->assertGreaterThanOrEqual(30.0, $finalTransitions['transitionPercentage'], 
                "Final transition word percentage must be >= 30%, got {$finalTransitions['transitionPercentage']}%");
            
            // Property 4: All readability metrics should be compliant
            $this->assertTrue($finalPassive['isCompliant'], 
                "Final content should be compliant for passive voice");
            $this->assertTrue($finalLength['isCompliant'], 
                "Final content should be compliant for sentence length");
            $this->assertTrue($finalTransitions['isCompliant'], 
                "Final content should be compliant for transition words");
            
            // Property 5: If initial content was non-compliant, corrections should be made
            $wasNonCompliant = !$initialPassive['isCompliant'] || 
                              !$initialLength['isCompliant'] || 
                              !$initialTransitions['isCompliant'];
            
            if ($wasNonCompliant) {
                $this->assertNotEmpty($correctionResult['changes_made'], 
                    "Non-compliant content should have corrections applied");
                $this->assertGreaterThan(0, $correctionResult['iterations'], 
                    "Non-compliant content should require at least one iteration");
            }
            
            // Property 6: Corrected content should not be empty
            $this->assertNotEmpty($correctedContent, 
                "Corrected content should not be empty");
            $this->assertGreaterThan(0, strlen(strip_tags($correctedContent)), 
                "Corrected content should contain text");
            
            // Property 7: Readability improvements should be tracked
            if (!empty($correctionResult['changes_made'])) {
                foreach ($correctionResult['changes_made'] as $iteration) {
                    $this->assertArrayHasKey('iteration', $iteration, 
                        "Each change iteration should be numbered");
                    $this->assertArrayHasKey('changes', $iteration, 
                        "Each iteration should track specific changes");
                }
            }
            
            // Property 8: Final analysis should show compliance
            $finalAnalysis = $correctionResult['final_analysis'];
            $this->assertArrayHasKey('passive_voice', $finalAnalysis, 
                "Final analysis should include passive voice metrics");
            $this->assertArrayHasKey('sentence_length', $finalAnalysis, 
                "Final analysis should include sentence length metrics");
            $this->assertArrayHasKey('transitions', $finalAnalysis, 
                "Final analysis should include transition word metrics");
            
            // Property 9: Readability score should be acceptable
            $readabilityScore = $corrector->getReadabilityScore($correctedContent);
            $this->assertGreaterThanOrEqual(80, $readabilityScore['overall_score'], 
                "Final readability score should be at least 80");
            $this->assertTrue($readabilityScore['is_compliant'], 
                "Final content should be marked as readability compliant");
            
            // Property 10: Content structure should remain valid
            if (strpos($content, '<') !== false) {
                // If original had HTML, corrected should too
                $this->assertNotEquals($correctedContent, strip_tags($correctedContent), 
                    "HTML structure should be preserved");
            }
            
            // Property 11: Word count should not decrease significantly
            $originalWords = str_word_count(strip_tags($content));
            $correctedWords = str_word_count(strip_tags($correctedContent));
            if ($originalWords > 0) {
                $wordReduction = ($originalWords - $correctedWords) / $originalWords;
                $this->assertLessThanOrEqual(0.2, $wordReduction, 
                    "Word count should not decrease by more than 20%");
            }
        });
    }

    /**
     * **Feature: content-generation-fixes, Property 6: Automatic readability correction**
     * **Validates: Requirements 3.4**
     * 
     * For any content with readability issues, the system should automatically 
     * rewrite problematic sentences to meet standards
     */
    public function test_automatic_readability_correction() {
        $this->minimumEvaluationRatio(0.01);
        
        $this->forAll(
            Generator\oneOf(
                // Generate content with specific readability problems
                Generator\constant("The report was written by the team and mistakes were made during the process which was analyzed by researchers who found that improvements were needed and these improvements were implemented by developers who worked on the system that was designed by experts."), // Long passive sentence
                Generator\constant("The data was analyzed. Results were obtained. The study was conducted. Findings were reported. Conclusions were drawn."), // Multiple passive sentences
                Generator\constant("This sentence has no transitions and this sentence also lacks transitions and this sentence continues without proper flow and this sentence maintains the same pattern."), // No transitions
                Generator\constant("This is a very long sentence that contains many words and clauses and subclauses and additional information that makes it extremely difficult to read and understand because it goes on and on without proper breaks or pauses which negatively impacts readability scores.") // Very long sentence
            )
        )
        ->then(function($problematicContent) {
            $corrector = new ReadabilityCorrector();
            
            // Analyze initial readability issues
            $initialAnalysis = $corrector->analyzeReadability($problematicContent);
            $initialCompliant = $corrector->isReadabilityCompliant($problematicContent);
            
            // Apply automatic corrections
            $correctionResult = $corrector->correctReadability($problematicContent, [
                'fix_passive_voice' => true,
                'split_long_sentences' => true,
                'add_transitions' => true,
                'max_iterations' => 3
            ]);
            
            $correctedContent = $correctionResult['corrected_content'];
            $finalAnalysis = $corrector->analyzeReadability($correctedContent);
            $finalCompliant = $corrector->isReadabilityCompliant($correctedContent);
            
            // Property 1: Correction should always return valid content
            $this->assertIsString($correctedContent, "Corrected content should be a string");
            $this->assertNotEmpty($correctedContent, "Corrected content should not be empty");
            
            // Property 2: If initial content was non-compliant, corrections should improve readability
            if (!$initialCompliant) {
                $this->assertNotEmpty($correctionResult['changes_made'], 
                    "Non-compliant content should have corrections applied");
                
                // At least one readability metric should improve
                $passiveImproved = $finalAnalysis['passive_voice']['passivePercentage'] <= 
                                  $initialAnalysis['passive_voice']['passivePercentage'];
                $lengthImproved = $finalAnalysis['sentence_length']['longSentencePercentage'] <= 
                                 $initialAnalysis['sentence_length']['longSentencePercentage'];
                $transitionsImproved = $finalAnalysis['transitions']['transitionPercentage'] >= 
                                      $initialAnalysis['transitions']['transitionPercentage'];
                
                $this->assertTrue($passiveImproved || $lengthImproved || $transitionsImproved,
                    "At least one readability metric should improve after correction");
            }
            
            // Property 3: Final content should meet readability standards
            $this->assertLessThanOrEqual(10.0, $finalAnalysis['passive_voice']['passivePercentage'],
                "Final passive voice should be <= 10%");
            $this->assertLessThanOrEqual(25.0, $finalAnalysis['sentence_length']['longSentencePercentage'],
                "Final long sentence percentage should be <= 25%");
            $this->assertGreaterThanOrEqual(30.0, $finalAnalysis['transitions']['transitionPercentage'],
                "Final transition percentage should be >= 30%");
            
            // Property 4: Correction process should be tracked
            $this->assertArrayHasKey('original_content', $correctionResult,
                "Result should track original content");
            $this->assertArrayHasKey('corrected_content', $correctionResult,
                "Result should contain corrected content");
            $this->assertArrayHasKey('changes_made', $correctionResult,
                "Result should track changes made");
            $this->assertArrayHasKey('iterations', $correctionResult,
                "Result should track number of iterations");
            $this->assertArrayHasKey('final_analysis', $correctionResult,
                "Result should include final analysis");
            
            // Property 5: Changes should be categorized properly
            if (!empty($correctionResult['changes_made'])) {
                foreach ($correctionResult['changes_made'] as $iteration) {
                    $this->assertArrayHasKey('iteration', $iteration,
                        "Each change should be numbered");
                    $this->assertArrayHasKey('changes', $iteration,
                        "Each iteration should list specific changes");
                    
                    if (isset($iteration['changes']['passive_voice'])) {
                        $this->assertIsArray($iteration['changes']['passive_voice'],
                            "Passive voice changes should be tracked as array");
                    }
                    if (isset($iteration['changes']['sentence_length'])) {
                        $this->assertIsArray($iteration['changes']['sentence_length'],
                            "Sentence length changes should be tracked as array");
                    }
                    if (isset($iteration['changes']['transitions'])) {
                        $this->assertIsArray($iteration['changes']['transitions'],
                            "Transition changes should be tracked as array");
                    }
                }
            }
            
            // Property 6: Iterations should not exceed maximum
            $this->assertLessThanOrEqual(3, $correctionResult['iterations'],
                "Number of iterations should not exceed maximum");
            $this->assertGreaterThanOrEqual(0, $correctionResult['iterations'],
                "Number of iterations should be non-negative");
            
            // Property 7: Content meaning should be preserved
            $originalWords = str_word_count(strip_tags($problematicContent));
            $correctedWords = str_word_count(strip_tags($correctedContent));
            
            if ($originalWords > 0) {
                $wordChangeRatio = abs($correctedWords - $originalWords) / $originalWords;
                $this->assertLessThanOrEqual(0.5, $wordChangeRatio,
                    "Word count should not change by more than 50%");
            }
            
            // Property 8: Specific correction types should work as expected
            if (strpos($problematicContent, 'was written by') !== false) {
                // Should attempt passive voice correction
                $hasPassiveCorrection = false;
                foreach ($correctionResult['changes_made'] as $iteration) {
                    if (isset($iteration['changes']['passive_voice'])) {
                        $hasPassiveCorrection = true;
                        break;
                    }
                }
                $this->assertTrue($hasPassiveCorrection,
                    "Content with 'was written by' should trigger passive voice correction");
            }
            
            // Property 9: Final readability score should be acceptable
            $finalScore = $corrector->getReadabilityScore($correctedContent);
            $this->assertGreaterThanOrEqual(70, $finalScore['overall_score'],
                "Final readability score should be at least 70");
            
            // Property 10: Correction should be idempotent (running again shouldn't change much)
            $secondCorrection = $corrector->correctReadability($correctedContent);
            $secondCorrectedContent = $secondCorrection['corrected_content'];
            
            // Should have fewer or no changes on second run
            $secondChangeCount = count($secondCorrection['changes_made']);
            $firstChangeCount = count($correctionResult['changes_made']);
            $this->assertLessThanOrEqual($firstChangeCount, $secondChangeCount,
                "Second correction should make fewer or equal changes");
            
            // Property 11: All analyzers should work consistently
            $passiveAnalyzer = new PassiveVoiceAnalyzer();
            $lengthAnalyzer = new SentenceLengthAnalyzer();
            $transitionAnalyzer = new TransitionWordAnalyzer();
            
            $directPassive = $passiveAnalyzer->analyze($correctedContent);
            $directLength = $lengthAnalyzer->analyze($correctedContent);
            $directTransitions = $transitionAnalyzer->analyze($correctedContent);
            
            $this->assertEquals($finalAnalysis['passive_voice']['passivePercentage'],
                $directPassive['passivePercentage'], "Passive voice analysis should be consistent");
            $this->assertEquals($finalAnalysis['sentence_length']['longSentencePercentage'],
                $directLength['longSentencePercentage'], "Sentence length analysis should be consistent");
            $this->assertEquals($finalAnalysis['transitions']['transitionPercentage'],
                $directTransitions['transitionPercentage'], "Transition analysis should be consistent");
        });
    }

    /**
     * **Feature: content-generation-fixes, Property 7: Title uniqueness and compliance**
     * **Validates: Requirements 4.1, 4.2**
     * 
     * For any generated title, it should be unique from previous titles, 
     * remain under 66 characters, and include the focus keyword
     */
    public function test_title_uniqueness_and_compliance() {
        $this->minimumEvaluationRatio(0.01);
        
        $this->forAll(
            Generator\elements(['seo', 'wordpress', 'content', 'marketing', 'blog']), // focus keyword
            Generator\elements(['how_to', 'listicle', 'comparison', 'benefits', 'problem_solution', 'beginner']), // content type
            Generator\elements(['beginners', 'professionals', 'experts', 'business owners']), // target audience
            Generator\choose(1, 10) // max attempts
        )
        ->then(function($focusKeyword, $contentType, $targetAudience, $maxAttempts) {
            $titleEngine = new Title_Optimization_Engine();
            $uniquenessValidator = new Title_Uniqueness_Validator();
            
            // Generate optimized title
            $params = [
                'focus_keyword' => $focusKeyword,
                'content_type' => $contentType,
                'target_audience' => $targetAudience,
                'max_attempts' => $maxAttempts
            ];
            
            $result = $titleEngine->generate_optimized_title($params);
            
            // Property 1: Title generation should always return a result
            $this->assertIsArray($result, "Title generation must return an array result");
            $this->assertArrayHasKey('title', $result, "Result must contain a title");
            $this->assertArrayHasKey('success', $result, "Result must indicate success status");
            $this->assertArrayHasKey('attempts', $result, "Result must track number of attempts");
            
            // Property 2: Generated title should not be empty
            $this->assertNotEmpty($result['title'], "Generated title should not be empty");
            $this->assertIsString($result['title'], "Generated title should be a string");
            
            // Property 3: Title should be under 66 characters (SEO requirement)
            $titleLength = strlen($result['title']);
            $this->assertLessThanOrEqual(66, $titleLength, 
                "Title must be under 66 characters for SEO, got {$titleLength} characters");
            
            // Property 4: Title should contain the focus keyword
            $titleLower = strtolower($result['title']);
            $keywordLower = strtolower($focusKeyword);
            $this->assertStringContainsString($keywordLower, $titleLower, 
                "Title must contain the focus keyword '{$focusKeyword}'");
            
            // Property 5: Title should pass basic requirements validation
            $requirementsResult = $uniquenessValidator->validate_title_requirements($result['title'], $focusKeyword);
            $this->assertTrue($requirementsResult['is_valid'], 
                "Generated title should meet basic requirements: " . implode(', ', $requirementsResult['errors']));
            
            // Property 6: Number of attempts should not exceed maximum
            $this->assertLessThanOrEqual($maxAttempts, $result['attempts'], 
                "Number of attempts should not exceed maximum allowed");
            $this->assertGreaterThan(0, $result['attempts'], 
                "At least one attempt should be made");
            
            // Property 7: Character count should be tracked accurately
            $this->assertArrayHasKey('character_count', $result, 
                "Result should track character count");
            $this->assertEquals($titleLength, $result['character_count'], 
                "Character count should match actual title length");
            
            // Property 8: Title should be meaningful and readable
            $this->assertGreaterThan(10, $titleLength, 
                "Title should be at least 10 characters for meaningfulness");
            
            // Property 9: Title should not contain excessive punctuation
            $punctuationCount = preg_match_all('/[^\w\s]/', $result['title']);
            $this->assertLessThan($titleLength * 0.2, $punctuationCount, 
                "Title should not contain excessive punctuation");
            
            // Property 10: If successful, title should be unique
            if ($result['success']) {
                $uniquenessResult = $uniquenessValidator->validate_title_uniqueness($result['title']);
                $this->assertTrue($uniquenessResult['is_unique'], 
                    "Successful title generation should produce unique titles");
            }
            
            // Property 11: All attempts should be tracked
            if (isset($result['all_attempts'])) {
                $this->assertIsArray($result['all_attempts'], 
                    "All attempts should be tracked as an array");
                $this->assertCount($result['attempts'], $result['all_attempts'], 
                    "Number of tracked attempts should match attempt count");
                
                // Each attempt should have required fields
                foreach ($result['all_attempts'] as $attempt) {
                    $this->assertArrayHasKey('title', $attempt, 
                        "Each attempt should have a title");
                    $this->assertArrayHasKey('character_count', $attempt, 
                        "Each attempt should track character count");
                    $this->assertArrayHasKey('is_unique', $attempt, 
                        "Each attempt should check uniqueness");
                    $this->assertArrayHasKey('meets_requirements', $attempt, 
                        "Each attempt should validate requirements");
                }
            }
            
            // Property 12: Title optimization should preserve keyword prominence
            $keywordPosition = strpos($titleLower, $keywordLower);
            $this->assertNotFalse($keywordPosition, "Keyword should be found in title");
            
            // Keyword should appear in first half of title for better SEO
            $titleMidpoint = $titleLength / 2;
            $this->assertLessThan($titleMidpoint, $keywordPosition + strlen($keywordLower), 
                "Keyword should appear in first half of title for better SEO");
            
            // Property 13: Title should match content type expectations
            $titleWords = explode(' ', strtolower($result['title']));
            switch ($contentType) {
                case 'how_to':
                    $hasHowToIndicator = array_intersect($titleWords, ['how', 'guide', 'step', 'tutorial']) !== [];
                    $this->assertTrue($hasHowToIndicator, 
                        "How-to titles should contain instructional indicators");
                    break;
                case 'listicle':
                    $hasNumber = preg_match('/\d+/', $result['title']);
                    $this->assertTrue($hasNumber, 
                        "Listicle titles should contain numbers");
                    break;
                case 'comparison':
                    $hasComparisonIndicator = array_intersect($titleWords, ['vs', 'versus', 'compare', 'comparison', 'best']) !== [];
                    $this->assertTrue($hasComparisonIndicator, 
                        "Comparison titles should contain comparison indicators");
                    break;
            }
            
            // Property 14: Title should be appropriate for target audience
            if (!empty($targetAudience) && $targetAudience !== 'Everyone') {
                // Title should either contain audience reference or be appropriate for audience
                $audienceLower = strtolower($targetAudience);
                $titleContainsAudience = strpos($titleLower, $audienceLower) !== false;
                $titleContainsBeginner = strpos($titleLower, 'beginner') !== false || 
                                        strpos($titleLower, 'start') !== false ||
                                        strpos($titleLower, 'basic') !== false;
                
                if ($audienceLower === 'beginners') {
                    $this->assertTrue($titleContainsAudience || $titleContainsBeginner, 
                        "Beginner-targeted titles should indicate beginner level");
                }
            }
            
            // Property 15: Title similarity calculation should work correctly
            $similarity = $uniquenessValidator->calculate_title_similarity($result['title'], $result['title']);
            $this->assertEquals(1.0, $similarity, 
                "Title similarity with itself should be 1.0");
            
            // Property 16: Title should not be overly repetitive
            $words = explode(' ', $result['title']);
            $uniqueWords = array_unique(array_map('strtolower', $words));
            $repetitionRatio = count($words) > 0 ? count($uniqueWords) / count($words) : 1;
            $this->assertGreaterThan(0.6, $repetitionRatio, 
                "Title should not be overly repetitive (at least 60% unique words)");
        });
    }

    /**
     * **Feature: content-generation-fixes, Property 8: Content variation for similar topics**
     * **Validates: Requirements 4.3**
     * 
     * For any set of content with similar topics, the system should vary approach, 
     * structure, and focus points to avoid duplication
     */
    public function test_content_variation_for_similar_topics() {
        $this->minimumEvaluationRatio(0.01);
        
        $this->forAll(
            Generator\elements(['seo', 'wordpress', 'content', 'marketing', 'blog']), // base topic
            Generator\elements(['beginners', 'professionals', 'experts', 'business owners']), // target audience
            Generator\seq(Generator\elements(['how_to', 'listicle', 'comparison', 'benefits', 'problem_solution', 'beginner'])), // content types
            Generator\choose(2, 5) // number of variations to generate
        )
        ->then(function($baseTopic, $targetAudience, $contentTypes, $variationCount) {
            $titleEngine = new Title_Optimization_Engine();
            
            // Generate multiple content variations for the same topic
            $params = [
                'base_topic' => $baseTopic,
                'focus_keyword' => $baseTopic,
                'target_audience' => $targetAudience,
                'content_types' => array_slice($contentTypes, 0, $variationCount)
            ];
            
            $variationResult = $titleEngine->generate_content_variations($params);
            
            // Property 1: Variation generation should always return a result
            $this->assertIsArray($variationResult, "Content variation must return an array result");
            $this->assertArrayHasKey('success', $variationResult, "Result must indicate success status");
            $this->assertArrayHasKey('variations', $variationResult, "Result must contain variations");
            $this->assertArrayHasKey('total_variations', $variationResult, "Result must track total variations");
            
            // Property 2: Should generate at least one variation
            $this->assertGreaterThan(0, $variationResult['total_variations'], 
                "Should generate at least one content variation");
            $this->assertNotEmpty($variationResult['variations'], 
                "Variations array should not be empty");
            
            // Property 3: Each variation should have required fields
            foreach ($variationResult['variations'] as $index => $variation) {
                $this->assertArrayHasKey('content_type', $variation, 
                    "Variation {$index} must have content_type");
                $this->assertArrayHasKey('title', $variation, 
                    "Variation {$index} must have title");
                $this->assertArrayHasKey('angle', $variation, 
                    "Variation {$index} must have angle");
                $this->assertArrayHasKey('focus_points', $variation, 
                    "Variation {$index} must have focus_points");
                $this->assertArrayHasKey('target_audience', $variation, 
                    "Variation {$index} must have target_audience");
                
                // Property 4: Each variation should have a valid title
                $this->assertNotEmpty($variation['title'], 
                    "Variation {$index} title should not be empty");
                $this->assertIsString($variation['title'], 
                    "Variation {$index} title should be a string");
                $this->assertLessThanOrEqual(66, strlen($variation['title']), 
                    "Variation {$index} title should be under 66 characters");
                
                // Property 5: Title should contain the focus keyword
                $titleLower = strtolower($variation['title']);
                $keywordLower = strtolower($baseTopic);
                $this->assertStringContainsString($keywordLower, $titleLower, 
                    "Variation {$index} title must contain the focus keyword '{$baseTopic}'");
                
                // Property 6: Each variation should have a unique content angle
                $this->assertNotEmpty($variation['angle'], 
                    "Variation {$index} should have a content angle");
                $this->assertIsString($variation['angle'], 
                    "Variation {$index} angle should be a string");
                
                // Property 7: Focus points should be an array with meaningful content
                $this->assertIsArray($variation['focus_points'], 
                    "Variation {$index} focus_points should be an array");
                $this->assertNotEmpty($variation['focus_points'], 
                    "Variation {$index} should have focus points");
                
                foreach ($variation['focus_points'] as $pointIndex => $point) {
                    $this->assertNotEmpty($point, 
                        "Variation {$index} focus point {$pointIndex} should not be empty");
                    $this->assertIsString($point, 
                        "Variation {$index} focus point {$pointIndex} should be a string");
                }
            }
            
            // Property 8: Variations should have different content types when multiple are requested
            if (count($variationResult['variations']) > 1) {
                $contentTypes = array_column($variationResult['variations'], 'content_type');
                $uniqueContentTypes = array_unique($contentTypes);
                $this->assertGreaterThan(1, count($uniqueContentTypes), 
                    "Multiple variations should have different content types");
            }
            
            // Property 9: Variations should have different titles
            if (count($variationResult['variations']) > 1) {
                $titles = array_column($variationResult['variations'], 'title');
                $uniqueTitles = array_unique($titles);
                $this->assertEquals(count($titles), count($uniqueTitles), 
                    "All variation titles should be unique");
            }
            
            // Property 10: Variations should have different angles
            if (count($variationResult['variations']) > 1) {
                $angles = array_column($variationResult['variations'], 'angle');
                $uniqueAngles = array_unique($angles);
                $this->assertGreaterThan(1, count($uniqueAngles), 
                    "Variations should have different content angles");
            }
            
            // Property 11: Focus points should vary between variations
            if (count($variationResult['variations']) > 1) {
                $allFocusPoints = [];
                foreach ($variationResult['variations'] as $variation) {
                    $allFocusPoints = array_merge($allFocusPoints, $variation['focus_points']);
                }
                $uniqueFocusPoints = array_unique($allFocusPoints);
                
                // Should have more unique focus points than just one variation would provide
                $singleVariationPoints = count($variationResult['variations'][0]['focus_points']);
                $this->assertGreaterThan($singleVariationPoints, count($uniqueFocusPoints), 
                    "Variations should provide diverse focus points");
            }
            
            // Property 12: Content type should match expected patterns
            foreach ($variationResult['variations'] as $variation) {
                $contentType = $variation['content_type'];
                $title = strtolower($variation['title']);
                
                switch ($contentType) {
                    case 'how_to':
                        $hasHowToPattern = strpos($title, 'how') !== false || 
                                          strpos($title, 'guide') !== false || 
                                          strpos($title, 'step') !== false;
                        $this->assertTrue($hasHowToPattern, 
                            "How-to variation should have appropriate title pattern");
                        break;
                        
                    case 'listicle':
                        $hasNumberPattern = preg_match('/\d+/', $title);
                        $this->assertTrue($hasNumberPattern, 
                            "Listicle variation should contain numbers in title");
                        break;
                        
                    case 'comparison':
                        $hasComparisonPattern = strpos($title, 'vs') !== false || 
                                               strpos($title, 'compar') !== false || 
                                               strpos($title, 'best') !== false;
                        $this->assertTrue($hasComparisonPattern, 
                            "Comparison variation should have comparison indicators");
                        break;
                        
                    case 'benefits':
                        $hasBenefitPattern = strpos($title, 'benefit') !== false || 
                                            strpos($title, 'advantage') !== false || 
                                            strpos($title, 'why') !== false;
                        $this->assertTrue($hasBenefitPattern, 
                            "Benefits variation should emphasize value");
                        break;
                }
            }
            
            // Property 13: Target audience should be consistent or appropriately varied
            foreach ($variationResult['variations'] as $variation) {
                $this->assertNotEmpty($variation['target_audience'], 
                    "Each variation should have a target audience");
                
                // Should either match the input audience or be a reasonable default
                $validAudiences = [$targetAudience, 'Practitioners', 'Professionals', 
                                  'Decision makers', 'Business owners', 'Problem solvers', 
                                  'Newcomers', 'General audience'];
                $this->assertContains($variation['target_audience'], $validAudiences, 
                    "Target audience should be valid: " . $variation['target_audience']);
            }
            
            // Property 14: Variation structure should prevent duplication
            $variationSignatures = [];
            foreach ($variationResult['variations'] as $variation) {
                // Create a signature based on content type, angle, and key focus points
                $signature = $variation['content_type'] . '|' . 
                           substr($variation['angle'], 0, 20) . '|' . 
                           implode(',', array_slice($variation['focus_points'], 0, 2));
                $variationSignatures[] = $signature;
            }
            
            $uniqueSignatures = array_unique($variationSignatures);
            $this->assertEquals(count($variationSignatures), count($uniqueSignatures), 
                "Each variation should have a unique approach signature");
            
            // Property 15: Focus points should be relevant to content type
            foreach ($variationResult['variations'] as $variation) {
                $contentType = $variation['content_type'];
                $focusPoints = $variation['focus_points'];
                
                switch ($contentType) {
                    case 'how_to':
                        $hasImplementationFocus = false;
                        foreach ($focusPoints as $point) {
                            if (strpos(strtolower($point), 'step') !== false || 
                                strpos(strtolower($point), 'implement') !== false || 
                                strpos(strtolower($point), 'practice') !== false) {
                                $hasImplementationFocus = true;
                                break;
                            }
                        }
                        $this->assertTrue($hasImplementationFocus, 
                            "How-to variations should have implementation-focused points");
                        break;
                        
                    case 'comparison':
                        $hasAnalysisFocus = false;
                        foreach ($focusPoints as $point) {
                            if (strpos(strtolower($point), 'analy') !== false || 
                                strpos(strtolower($point), 'pros') !== false || 
                                strpos(strtolower($point), 'feature') !== false) {
                                $hasAnalysisFocus = true;
                                break;
                            }
                        }
                        $this->assertTrue($hasAnalysisFocus, 
                            "Comparison variations should have analysis-focused points");
                        break;
                }
            }
            
            // Property 16: Successful variation generation should be marked as such
            if ($variationResult['success']) {
                $this->assertGreaterThan(0, count($variationResult['variations']), 
                    "Successful variation generation should produce variations");
            }
            
            // Property 17: Total variations count should match actual variations
            $this->assertEquals(count($variationResult['variations']), 
                $variationResult['total_variations'], 
                "Total variations count should match actual variations array length");
        });
    }

    /**
     * **Feature: content-generation-fixes, Property 10: Image and alt text requirements**
     * **Validates: Requirements 5.1, 5.2**
     * 
     * For any generated content, it should include at least one image prompt with 
     * descriptive alt text that contains the focus keyword or synonym
     */
    public function test_image_and_alt_text_requirements() {
        $this->minimumEvaluationRatio(0.01);
        
        $this->forAll(
            Generator\elements(['seo', 'wordpress', 'content', 'marketing', 'blog', 'technology', 'business']), // focus keyword
            Generator\seq(Generator\elements(['optimization', 'guide', 'tips', 'tutorial', 'best practices', 'strategy', 'solution'])), // synonyms
            Generator\elements(['how to', 'ultimate guide to', 'best practices for', 'introduction to', 'advanced techniques for']), // topic prefix
            Generator\elements(['beginners', 'professionals', 'experts', 'business owners', 'developers']) // target audience
        )
        ->then(function($focusKeyword, $synonyms, $topicPrefix, $targetAudience) {
            $imageGenerator = new ImagePromptGenerator();
            
            // Create topic from prefix and keyword
            $topic = $topicPrefix . ' ' . $focusKeyword . ' for ' . $targetAudience;
            
            // Generate image prompt
            $result = $imageGenerator->generateImagePrompt($topic, $focusKeyword, $synonyms);
            
            // Property 1: Image prompt generation should always return required fields
            $this->assertIsArray($result, "Image prompt generation must return an array");
            $this->assertArrayHasKey('prompt', $result, "Result must contain image prompt");
            $this->assertArrayHasKey('alt_text', $result, "Result must contain alt text");
            $this->assertArrayHasKey('context', $result, "Result must contain context information");
            $this->assertArrayHasKey('focus_keyword', $result, "Result must track focus keyword");
            $this->assertArrayHasKey('metadata', $result, "Result must contain metadata");
            
            // Property 2: Image prompt should not be empty and should be descriptive
            $this->assertNotEmpty($result['prompt'], "Image prompt should not be empty");
            $this->assertIsString($result['prompt'], "Image prompt should be a string");
            $this->assertGreaterThan(20, strlen($result['prompt']), 
                "Image prompt should be descriptive (at least 20 characters)");
            
            // Property 3: Alt text should meet accessibility requirements
            $this->assertNotEmpty($result['alt_text'], "Alt text should not be empty");
            $this->assertIsString($result['alt_text'], "Alt text should be a string");
            
            $altTextLength = strlen($result['alt_text']);
            $this->assertGreaterThanOrEqual(10, $altTextLength, 
                "Alt text must be at least 10 characters for meaningfulness");
            $this->assertLessThanOrEqual(125, $altTextLength, 
                "Alt text should not exceed 125 characters for screen reader compatibility");
            
            // Property 4: Alt text should contain focus keyword or synonym
            $altTextLower = strtolower($result['alt_text']);
            $focusKeywordLower = strtolower($focusKeyword);
            $hasKeyword = strpos($altTextLower, $focusKeywordLower) !== false;
            
            $hasSynonym = false;
            foreach ($synonyms as $synonym) {
                if (strpos($altTextLower, strtolower($synonym)) !== false) {
                    $hasSynonym = true;
                    break;
                }
            }
            
            $this->assertTrue($hasKeyword || $hasSynonym, 
                "Alt text must contain focus keyword '{$focusKeyword}' or a synonym");
            
            // Property 5: Image prompt should be relevant to topic
            $promptLower = strtolower($result['prompt']);
            $this->assertStringContainsString($focusKeywordLower, $promptLower, 
                "Image prompt should contain the focus keyword for relevance");
            
            // Property 6: Context should provide meaningful information
            $this->assertIsArray($result['context'], "Context should be an array");
            $this->assertArrayHasKey('subject', $result['context'], "Context should define subject");
            $this->assertArrayHasKey('style', $result['context'], "Context should define style");
            $this->assertNotEmpty($result['context']['subject'], "Context subject should not be empty");
            $this->assertNotEmpty($result['context']['style'], "Context style should not be empty");
            
            // Property 7: Metadata should contain required information
            $this->assertIsArray($result['metadata'], "Metadata should be an array");
            $this->assertArrayHasKey('topic', $result['metadata'], "Metadata should track topic");
            $this->assertArrayHasKey('style', $result['metadata'], "Metadata should track style");
            $this->assertArrayHasKey('composition', $result['metadata'], "Metadata should track composition");
            
            // Property 8: Focus keyword should be preserved correctly
            $this->assertEquals($focusKeyword, $result['focus_keyword'], 
                "Focus keyword should be preserved in result");
            
            // Property 9: Secondary keywords should be preserved
            $this->assertEquals($synonyms, $result['secondary_keywords'], 
                "Secondary keywords should be preserved in result");
            
            // Property 10: Image prompt should not contain screen reader unfriendly phrases
            $unfriendlyPhrases = ['image of', 'picture of', 'photo of', 'graphic of'];
            foreach ($unfriendlyPhrases as $phrase) {
                $this->assertStringNotContainsString($phrase, $altTextLower, 
                    "Alt text should not contain screen reader unfriendly phrase: '{$phrase}'");
            }
            
            // Property 11: Alt text should be properly formatted
            $this->assertEquals(ucfirst(trim($result['alt_text'])), $result['alt_text'], 
                "Alt text should be properly capitalized and trimmed");
            
            // Property 12: Image prompt should include quality indicators
            $qualityIndicators = ['professional', 'high quality', 'detailed', 'clear'];
            $hasQualityIndicator = false;
            foreach ($qualityIndicators as $indicator) {
                if (strpos($promptLower, $indicator) !== false) {
                    $hasQualityIndicator = true;
                    break;
                }
            }
            $this->assertTrue($hasQualityIndicator, 
                "Image prompt should include quality indicators for better results");
            
            // Property 13: Validation should pass for generated data
            $isValid = $imageGenerator->validateImagePromptData($result);
            $this->assertTrue($isValid, "Generated image prompt data should pass validation");
            
            // Property 14: Alt text should not be overly repetitive
            $altWords = explode(' ', $result['alt_text']);
            $uniqueAltWords = array_unique(array_map('strtolower', $altWords));
            if (count($altWords) > 3) {
                $repetitionRatio = count($uniqueAltWords) / count($altWords);
                $this->assertGreaterThan(0.6, $repetitionRatio, 
                    "Alt text should not be overly repetitive");
            }
            
            // Property 15: Image prompt should be suitable for AI image generation
            $this->assertLessThan(500, strlen($result['prompt']), 
                "Image prompt should not be excessively long for AI processing");
            
            // Property 16: Context should match expected patterns
            $validStyles = ['professional', 'modern', 'clean', 'minimalist', 'vibrant', 'corporate', 'creative', 'technical'];
            $this->assertContains($result['context']['style'], $validStyles, 
                "Context style should be from valid options");
            
            // Property 17: Alt text should be meaningful for screen readers
            $meaningfulWords = ['showing', 'featuring', 'displaying', 'depicting', 'professional', 'modern', 'workspace'];
            $hasMeaningfulContent = false;
            foreach ($meaningfulWords as $word) {
                if (strpos($altTextLower, $word) !== false) {
                    $hasMeaningfulContent = true;
                    break;
                }
            }
            $this->assertTrue($hasMeaningfulContent, 
                "Alt text should contain meaningful descriptive words for screen readers");
        });
    }

    /**
     * **Feature: content-generation-fixes, Property 11: Image prompt relevance and variation**
     * **Validates: Requirements 5.3, 5.4**
     * 
     * For any generated image prompts, they should accurately describe content relevant 
     * to the topic, and multiple images should have varied alt text while maintaining 
     * keyword relevance
     */
    public function test_image_prompt_relevance_and_variation() {
        $this->minimumEvaluationRatio(0.01);
        
        $this->forAll(
            Generator\elements(['seo', 'wordpress', 'content', 'marketing', 'blog', 'technology', 'business', 'design']), // focus keyword
            Generator\seq(Generator\elements(['optimization', 'guide', 'tips', 'tutorial', 'best practices', 'strategy', 'solution', 'tools'])), // synonyms
            Generator\elements(['comprehensive guide to', 'best practices for', 'introduction to', 'advanced techniques for', 'complete overview of']), // topic prefix
            Generator\choose(2, 5) // number of variations to generate
        )
        ->then(function($focusKeyword, $synonyms, $topicPrefix, $variationCount) {
            $imageGenerator = new ImagePromptGenerator();
            
            // Create topic from prefix and keyword
            $topic = $topicPrefix . ' ' . $focusKeyword;
            
            // Generate multiple varied image prompts
            $variations = $imageGenerator->generateVariedImagePrompts($topic, $focusKeyword, $synonyms, $variationCount);
            
            // Property 1: Should generate requested number of variations
            $this->assertIsArray($variations, "Variations should be returned as an array");
            $this->assertCount($variationCount, $variations, 
                "Should generate exactly {$variationCount} variations");
            
            // Property 2: Each variation should have required fields
            foreach ($variations as $index => $variation) {
                $this->assertArrayHasKey('prompt', $variation, 
                    "Variation {$index} must have image prompt");
                $this->assertArrayHasKey('alt_text', $variation, 
                    "Variation {$index} must have alt text");
                $this->assertArrayHasKey('context', $variation, 
                    "Variation {$index} must have context");
                $this->assertArrayHasKey('variation_index', $variation, 
                    "Variation {$index} must track variation index");
                $this->assertArrayHasKey('metadata', $variation, 
                    "Variation {$index} must have metadata");
                
                // Property 3: Variation index should match array index
                $this->assertEquals($index, $variation['variation_index'], 
                    "Variation index should match array position");
            }
            
            // Property 4: All variations should be relevant to the topic
            foreach ($variations as $index => $variation) {
                $promptLower = strtolower($variation['prompt']);
                $focusKeywordLower = strtolower($focusKeyword);
                
                $this->assertStringContainsString($focusKeywordLower, $promptLower, 
                    "Variation {$index} prompt should contain focus keyword for relevance");
                
                // Property 5: Prompt should accurately describe visual content
                $descriptiveWords = ['showing', 'featuring', 'displaying', 'depicting', 'professional', 'modern'];
                $hasDescriptiveContent = false;
                foreach ($descriptiveWords as $word) {
                    if (strpos($promptLower, $word) !== false) {
                        $hasDescriptiveContent = true;
                        break;
                    }
                }
                $this->assertTrue($hasDescriptiveContent, 
                    "Variation {$index} should contain descriptive visual language");
            }
            
            // Property 6: Variations should have different prompts
            if (count($variations) > 1) {
                $prompts = array_column($variations, 'prompt');
                $uniquePrompts = array_unique($prompts);
                $this->assertEquals(count($prompts), count($uniquePrompts), 
                    "All image prompts should be unique");
            }
            
            // Property 7: Alt text should vary while maintaining keyword relevance
            if (count($variations) > 1) {
                $altTexts = array_column($variations, 'alt_text');
                $uniqueAltTexts = array_unique($altTexts);
                $this->assertEquals(count($altTexts), count($uniqueAltTexts), 
                    "All alt texts should be unique");
                
                // Each alt text should still contain keyword or synonym
                foreach ($variations as $index => $variation) {
                    $altTextLower = strtolower($variation['alt_text']);
                    $focusKeywordLower = strtolower($focusKeyword);
                    $hasKeyword = strpos($altTextLower, $focusKeywordLower) !== false;
                    
                    $hasSynonym = false;
                    foreach ($synonyms as $synonym) {
                        if (strpos($altTextLower, strtolower($synonym)) !== false) {
                            $hasSynonym = true;
                            break;
                        }
                    }
                    
                    $this->assertTrue($hasKeyword || $hasSynonym, 
                        "Variation {$index} alt text must maintain keyword relevance");
                }
            }
            
            // Property 8: Context should vary between variations
            if (count($variations) > 1) {
                $contexts = array_column($variations, 'context');
                $contextSignatures = [];
                
                foreach ($contexts as $context) {
                    $signature = ($context['style'] ?? '') . '|' . 
                                ($context['composition'] ?? '') . '|' . 
                                ($context['lighting'] ?? '');
                    $contextSignatures[] = $signature;
                }
                
                $uniqueContexts = array_unique($contextSignatures);
                $this->assertGreaterThan(1, count($uniqueContexts), 
                    "Variations should have different visual contexts");
            }
            
            // Property 9: Each variation should meet individual quality standards
            foreach ($variations as $index => $variation) {
                // Alt text length requirements
                $altTextLength = strlen($variation['alt_text']);
                $this->assertGreaterThanOrEqual(10, $altTextLength, 
                    "Variation {$index} alt text must be at least 10 characters");
                $this->assertLessThanOrEqual(125, $altTextLength, 
                    "Variation {$index} alt text must not exceed 125 characters");
                
                // Prompt quality requirements
                $this->assertGreaterThan(20, strlen($variation['prompt']), 
                    "Variation {$index} prompt should be descriptive");
                
                // Validation should pass
                $isValid = $imageGenerator->validateImagePromptData($variation);
                $this->assertTrue($isValid, 
                    "Variation {$index} should pass validation");
            }
            
            // Property 10: Metadata should reflect variation differences
            if (count($variations) > 1) {
                $styles = array_column(array_column($variations, 'metadata'), 'style');
                $compositions = array_column(array_column($variations, 'metadata'), 'composition');
                $lightings = array_column(array_column($variations, 'metadata'), 'lighting');
                
                // At least one aspect should vary
                $styleVariation = count(array_unique($styles)) > 1;
                $compositionVariation = count(array_unique($compositions)) > 1;
                $lightingVariation = count(array_unique($lightings)) > 1;
                
                $this->assertTrue($styleVariation || $compositionVariation || $lightingVariation, 
                    "Variations should differ in style, composition, or lighting");
            }
            
            // Property 11: Topic relevance should be maintained across all variations
            foreach ($variations as $index => $variation) {
                $context = $variation['context'];
                $this->assertArrayHasKey('topic', $context, 
                    "Variation {$index} context should track topic");
                $this->assertArrayHasKey('keywords', $context, 
                    "Variation {$index} context should track keywords");
                
                // Keywords should include focus keyword
                $contextKeywords = array_map('strtolower', $context['keywords']);
                $this->assertContains(strtolower($focusKeyword), $contextKeywords, 
                    "Variation {$index} context should include focus keyword");
            }
            
            // Property 12: Visual diversity should be appropriate for content type
            $subjectVariations = [];
            foreach ($variations as $variation) {
                $subject = $variation['context']['subject'] ?? '';
                $subjectVariations[] = $subject;
            }
            
            if (count($variations) > 2) {
                $uniqueSubjects = array_unique($subjectVariations);
                $this->assertGreaterThan(1, count($uniqueSubjects), 
                    "Multiple variations should explore different visual subjects");
            }
            
            // Property 13: Alt text should avoid screen reader unfriendly phrases
            foreach ($variations as $index => $variation) {
                $altTextLower = strtolower($variation['alt_text']);
                $unfriendlyPhrases = ['image of', 'picture of', 'photo of', 'graphic of'];
                
                foreach ($unfriendlyPhrases as $phrase) {
                    $this->assertStringNotContainsString($phrase, $altTextLower, 
                        "Variation {$index} alt text should not contain '{$phrase}'");
                }
            }
            
            // Property 14: Prompts should be suitable for AI image generation
            foreach ($variations as $index => $variation) {
                $prompt = $variation['prompt'];
                
                // Should not be too long for AI processing
                $this->assertLessThan(500, strlen($prompt), 
                    "Variation {$index} prompt should not be excessively long");
                
                // Should contain visual descriptors
                $visualDescriptors = ['professional', 'modern', 'clean', 'detailed', 'high quality'];
                $hasVisualDescriptor = false;
                $promptLower = strtolower($prompt);
                
                foreach ($visualDescriptors as $descriptor) {
                    if (strpos($promptLower, $descriptor) !== false) {
                        $hasVisualDescriptor = true;
                        break;
                    }
                }
                
                $this->assertTrue($hasVisualDescriptor, 
                    "Variation {$index} prompt should contain visual quality descriptors");
            }
            
            // Property 15: Variation quality should be consistent
            $qualityScores = [];
            foreach ($variations as $variation) {
                // Calculate a simple quality score based on length and keyword presence
                $promptScore = min(100, strlen($variation['prompt']) * 2);
                $altTextScore = min(100, strlen($variation['alt_text']) * 4);
                $keywordScore = strpos(strtolower($variation['alt_text']), strtolower($focusKeyword)) !== false ? 50 : 0;
                
                $qualityScore = ($promptScore + $altTextScore + $keywordScore) / 3;
                $qualityScores[] = $qualityScore;
            }
            
            // All variations should meet minimum quality threshold
            foreach ($qualityScores as $index => $score) {
                $this->assertGreaterThan(60, $score, 
                    "Variation {$index} should meet minimum quality threshold");
            }
            
            // Property 16: Context information should be complete
            foreach ($variations as $index => $variation) {
                $context = $variation['context'];
                $requiredContextFields = ['subject', 'style', 'composition', 'lighting', 'background'];
                
                foreach ($requiredContextFields as $field) {
                    $this->assertArrayHasKey($field, $context, 
                        "Variation {$index} context should have {$field}");
                    $this->assertNotEmpty($context[$field], 
                        "Variation {$index} context {$field} should not be empty");
                }
            }
            
            // Property 17: Semantic similarity should be balanced
            if (count($variations) > 1) {
                // Calculate semantic overlap between variations
                $allWords = [];
                foreach ($variations as $variation) {
                    $words = str_word_count(strtolower($variation['alt_text']), 1);
                    $allWords = array_merge($allWords, $words);
                }
                
                $uniqueWords = array_unique($allWords);
                $totalWords = count($allWords);
                
                if ($totalWords > 0) {
                    $diversityRatio = count($uniqueWords) / $totalWords;
                    $this->assertGreaterThan(0.4, $diversityRatio, 
                        "Variations should maintain good semantic diversity");
                }
            }
        });
    }

    /**
     * **Feature: content-generation-fixes, Property 12: Alt text accessibility**
     * **Validates: Requirements 5.5**
     * 
     * For any generated alt text, it should provide meaningful descriptions suitable 
     * for screen readers while including relevant keywords
     */
    public function test_alt_text_accessibility() {
        $this->minimumEvaluationRatio(0.01);
        
        $this->forAll(
            Generator\elements(['seo', 'wordpress', 'content', 'marketing', 'blog', 'technology', 'business', 'design']), // focus keyword
            Generator\seq(Generator\elements(['optimization', 'guide', 'tips', 'tutorial', 'best practices', 'strategy', 'solution', 'tools'])), // synonyms
            Generator\oneOf(
                // Generate various types of problematic alt text for optimization
                Generator\constant("image of seo optimization"), // Contains "image of"
                Generator\constant("pic showing wordpress"), // Contains "pic"
                Generator\constant("photo of content marketing strategy for business professionals"), // Too long
                Generator\constant("seo"), // Too short
                Generator\constant("Professional workspace featuring modern technology setup with advanced SEO optimization tools and comprehensive content marketing strategies"), // Very long
                Generator\constant("A professional image showing SEO optimization techniques"), // Contains unfriendly phrase
                Generator\elements(['Professional workspace', 'Modern office environment', 'Technology setup', 'Business meeting']) // Good base descriptions
            ),
            Generator\choose(2, 4) // number of variations to generate
        )
        ->then(function($focusKeyword, $synonyms, $baseAltText, $variationCount) {
            $altTextOptimizer = new AltTextAccessibilityOptimizer();
            
            // Test single alt text optimization
            $optimizationResult = $altTextOptimizer->optimizeAltText($baseAltText, $focusKeyword, $synonyms);
            
            // Property 1: Optimization should always return required fields
            $this->assertIsArray($optimizationResult, "Optimization must return an array");
            $this->assertArrayHasKey('optimized_text', $optimizationResult, "Result must contain optimized text");
            $this->assertArrayHasKey('original_text', $optimizationResult, "Result must track original text");
            $this->assertArrayHasKey('accessibility_score', $optimizationResult, "Result must include accessibility score");
            $this->assertArrayHasKey('length', $optimizationResult, "Result must track length");
            $this->assertArrayHasKey('has_keyword', $optimizationResult, "Result must indicate keyword presence");
            $this->assertArrayHasKey('screen_reader_friendly', $optimizationResult, "Result must indicate screen reader friendliness");
            $this->assertArrayHasKey('improvements_made', $optimizationResult, "Result must track improvements");
            
            // Property 2: Optimized text should meet accessibility length requirements
            $optimizedLength = $optimizationResult['length'];
            $this->assertGreaterThanOrEqual(15, $optimizedLength, 
                "Optimized alt text must be at least 15 characters for meaningfulness");
            $this->assertLessThanOrEqual(100, $optimizedLength, 
                "Optimized alt text should not exceed 100 characters for optimal screen reader experience");
            
            // Property 3: Optimized text should contain keyword or synonym
            $this->assertTrue($optimizationResult['has_keyword'], 
                "Optimized alt text must contain focus keyword or synonym");
            
            // Property 4: Accessibility score should be reasonable
            $accessibilityScore = $optimizationResult['accessibility_score'];
            $this->assertGreaterThanOrEqual(0, $accessibilityScore, 
                "Accessibility score should be non-negative");
            $this->assertLessThanOrEqual(100, $accessibilityScore, 
                "Accessibility score should not exceed 100");
            
            // Property 5: High-scoring alt text should be marked as screen reader friendly
            if ($accessibilityScore >= 80) {
                $this->assertTrue($optimizationResult['screen_reader_friendly'], 
                    "Alt text with score >= 80 should be marked as screen reader friendly");
            }
            
            // Property 6: Optimized text should not contain unfriendly phrases
            $optimizedLower = strtolower($optimizationResult['optimized_text']);
            $unfriendlyPhrases = ['image of', 'picture of', 'photo of', 'graphic of', 'pic of'];
            
            foreach ($unfriendlyPhrases as $phrase) {
                $this->assertStringNotContainsString($phrase, $optimizedLower, 
                    "Optimized alt text should not contain screen reader unfriendly phrase: '{$phrase}'");
            }
            
            // Property 7: Original text should be preserved in result
            $this->assertEquals($baseAltText, $optimizationResult['original_text'], 
                "Original text should be preserved in result");
            
            // Property 8: Length tracking should be accurate
            $this->assertEquals(strlen($optimizationResult['optimized_text']), $optimizationResult['length'], 
                "Length tracking should match actual optimized text length");
            
            // Test accessibility validation
            $validationResult = $altTextOptimizer->validateAccessibility($optimizationResult['optimized_text']);
            
            // Property 9: Validation should return comprehensive results
            $this->assertIsArray($validationResult, "Validation must return an array");
            $this->assertArrayHasKey('is_accessible', $validationResult, "Validation must indicate accessibility");
            $this->assertArrayHasKey('accessibility_score', $validationResult, "Validation must include score");
            $this->assertArrayHasKey('issues', $validationResult, "Validation must list issues");
            $this->assertArrayHasKey('warnings', $validationResult, "Validation must list warnings");
            $this->assertArrayHasKey('suggestions', $validationResult, "Validation must provide suggestions");
            $this->assertArrayHasKey('readability_level', $validationResult, "Validation must assess readability");
            
            // Property 10: Well-optimized text should pass accessibility validation
            if ($optimizationResult['accessibility_score'] >= 80) {
                $this->assertTrue($validationResult['is_accessible'], 
                    "Well-optimized alt text should pass accessibility validation");
                $this->assertEmpty($validationResult['issues'], 
                    "Well-optimized alt text should have no accessibility issues");
            }
            
            // Property 11: Validation score should be consistent with optimization score
            $scoreDifference = abs($validationResult['accessibility_score'] - $optimizationResult['accessibility_score']);
            $this->assertLessThan(10, $scoreDifference, 
                "Validation and optimization scores should be consistent");
            
            // Test multiple variations generation
            $baseDescription = "Professional workspace with technology";
            $variations = $altTextOptimizer->generateAccessibleVariations($baseDescription, $focusKeyword, $synonyms, $variationCount);
            
            // Property 12: Should generate requested number of variations
            $this->assertIsArray($variations, "Variations should be returned as an array");
            $this->assertCount($variationCount, $variations, 
                "Should generate exactly {$variationCount} variations");
            
            // Property 13: Each variation should meet accessibility standards
            foreach ($variations as $index => $variation) {
                $this->assertArrayHasKey('optimized_text', $variation, 
                    "Variation {$index} must have optimized text");
                $this->assertArrayHasKey('accessibility_score', $variation, 
                    "Variation {$index} must have accessibility score");
                $this->assertArrayHasKey('screen_reader_friendly', $variation, 
                    "Variation {$index} must indicate screen reader friendliness");
                $this->assertArrayHasKey('structure_type', $variation, 
                    "Variation {$index} must indicate structure type");
                $this->assertArrayHasKey('uniqueness_score', $variation, 
                    "Variation {$index} must have uniqueness score");
                
                // Each variation should meet basic accessibility requirements
                $variationLength = strlen($variation['optimized_text']);
                $this->assertGreaterThanOrEqual(15, $variationLength, 
                    "Variation {$index} must meet minimum length requirement");
                $this->assertLessThanOrEqual(100, $variationLength, 
                    "Variation {$index} must not exceed maximum length");
                
                // Should contain keyword
                $this->assertTrue($variation['has_keyword'], 
                    "Variation {$index} must contain focus keyword or synonym");
                
                // Should have reasonable accessibility score
                $this->assertGreaterThanOrEqual(70, $variation['accessibility_score'], 
                    "Variation {$index} should have good accessibility score");
            }
            
            // Property 14: Variations should be unique
            if (count($variations) > 1) {
                $texts = array_column($variations, 'optimized_text');
                $uniqueTexts = array_unique($texts);
                $this->assertEquals(count($texts), count($uniqueTexts), 
                    "All variation texts should be unique");
            }
            
            // Property 15: Variations should have different structure types
            if (count($variations) > 1) {
                $structureTypes = array_column($variations, 'structure_type');
                $uniqueStructures = array_unique($structureTypes);
                $this->assertGreaterThan(1, count($uniqueStructures), 
                    "Variations should use different structure types");
            }
            
            // Property 16: Variations should be sorted by accessibility score (highest first)
            if (count($variations) > 1) {
                $scores = array_column($variations, 'accessibility_score');
                $sortedScores = $scores;
                rsort($sortedScores);
                $this->assertEquals($sortedScores, $scores, 
                    "Variations should be sorted by accessibility score (highest first)");
            }
            
            // Property 17: Uniqueness scores should be meaningful
            foreach ($variations as $index => $variation) {
                $uniquenessScore = $variation['uniqueness_score'];
                $this->assertGreaterThanOrEqual(0, $uniquenessScore, 
                    "Variation {$index} uniqueness score should be non-negative");
                $this->assertLessThanOrEqual(100, $uniquenessScore, 
                    "Variation {$index} uniqueness score should not exceed 100");
            }
            
            // Property 18: All variations should avoid screen reader unfriendly content
            foreach ($variations as $index => $variation) {
                $variationLower = strtolower($variation['optimized_text']);
                
                foreach ($unfriendlyPhrases as $phrase) {
                    $this->assertStringNotContainsString($phrase, $variationLower, 
                        "Variation {$index} should not contain unfriendly phrase: '{$phrase}'");
                }
            }
            
            // Property 19: Readability levels should be appropriate
            foreach ($variations as $index => $variation) {
                $validationResult = $altTextOptimizer->validateAccessibility($variation['optimized_text']);
                $readabilityLevel = $validationResult['readability_level'];
                
                $acceptableReadabilityLevels = ['Very Easy', 'Easy', 'Moderate'];
                $this->assertContains($readabilityLevel, $acceptableReadabilityLevels, 
                    "Variation {$index} should have appropriate readability level, got: {$readabilityLevel}");
            }
            
            // Property 20: Optimization should be idempotent for already good alt text
            $goodAltText = "Professional workspace featuring modern technology and SEO optimization tools";
            $firstOptimization = $altTextOptimizer->optimizeAltText($goodAltText, $focusKeyword, $synonyms);
            $secondOptimization = $altTextOptimizer->optimizeAltText($firstOptimization['optimized_text'], $focusKeyword, $synonyms);
            
            // Second optimization should make minimal changes
            $this->assertLessThanOrEqual(count($firstOptimization['improvements_made']), 
                count($secondOptimization['improvements_made']), 
                "Second optimization should make fewer or equal improvements");
            
            // Property 21: Meaningful descriptions should be preserved and enhanced
            $meaningfulWords = ['professional', 'modern', 'workspace', 'technology', 'showing', 'featuring'];
            foreach ($variations as $index => $variation) {
                $variationLower = strtolower($variation['optimized_text']);
                $hasMeaningfulContent = false;
                
                foreach ($meaningfulWords as $word) {
                    if (strpos($variationLower, $word) !== false) {
                        $hasMeaningfulContent = true;
                        break;
                    }
                }
                
                $this->assertTrue($hasMeaningfulContent, 
                    "Variation {$index} should contain meaningful descriptive content");
            }
            
            // Property 22: Keyword integration should be natural
            foreach ($variations as $index => $variation) {
                $text = $variation['optimized_text'];
                $keywordCount = substr_count(strtolower($text), strtolower($focusKeyword));
                
                // Should not have excessive keyword repetition
                $this->assertLessThanOrEqual(2, $keywordCount, 
                    "Variation {$index} should not have excessive keyword repetition");
                
                // Should have at least one keyword occurrence
                $this->assertGreaterThanOrEqual(1, $keywordCount, 
                    "Variation {$index} should contain the focus keyword");
            }
            
            // Property 23: Structure types should be appropriate
            $validStructureTypes = ['descriptive_action', 'contextual_scene', 'focused_detail', 'environmental'];
            foreach ($variations as $index => $variation) {
                $this->assertContains($variation['structure_type'], $validStructureTypes, 
                    "Variation {$index} should use a valid structure type");
            }
        });
    }

    /**
     * Simple test to verify the property test framework is working
     */
    public function test_simple_validation() {
        $result = new SEOValidationResult();
        $result->addError("Test error", "test");
        $result->calculateScore();
        
        $this->assertEquals(80, $result->overallScore);
        $this->assertEquals(1, $result->getIssueCount());
    }
    
    /**
     * Helper method to generate content with specific keyword density
     */
    private function generateContentWithDensity($targetDensity) {
        return Generator\bind(
            Generator\elements(['seo', 'wordpress', 'content', 'marketing', 'blog']),
            function($keyword) use ($targetDensity) {
                return Generator\map(
                    function($baseWords) use ($keyword, $targetDensity) {
                        $totalWords = 200; // Base word count
                        $keywordCount = max(1, round(($targetDensity / 100) * $totalWords));
                        
                        // Create content with specified keyword density
                        $content = "<p>This is a comprehensive guide about various topics. ";
                        
                        // Add keywords based on target density
                        for ($i = 0; $i < $keywordCount; $i++) {
                            $content .= "The {$keyword} approach is important. ";
                        }
                        
                        // Fill with filler content to reach target word count
                        $fillerWords = $totalWords - $keywordCount * 3 - 10; // Approximate
                        for ($i = 0; $i < $fillerWords; $i++) {
                            $content .= "word ";
                        }
                        
                        $content .= "</p>";
                        
                        return $content;
                    },
                    Generator\choose(1, 10)
                );
            }
        );
    }
    
    /**
     * Helper method to generate content with high passive voice percentage
     */
    private function generateContentWithPassiveVoice($targetPercentage) {
        return Generator\map(
            function($sentences) use ($targetPercentage) {
                $totalSentences = 10;
                $passiveSentences = ceil(($targetPercentage / 100) * $totalSentences);
                
                $content = "<p>";
                
                // Add passive voice sentences
                $passiveTemplates = [
                    "The report was written by the team.",
                    "Mistakes were made during the process.",
                    "The data was analyzed by researchers.",
                    "Results were obtained through testing.",
                    "The website was designed by experts.",
                    "Content was created by writers.",
                    "The study was conducted by scientists.",
                    "Improvements were implemented by developers."
                ];
                
                for ($i = 0; $i < $passiveSentences; $i++) {
                    $content .= $passiveTemplates[$i % count($passiveTemplates)] . " ";
                }
                
                // Add active voice sentences
                $activeTemplates = [
                    "The team writes comprehensive reports.",
                    "Developers implement new features regularly.",
                    "Researchers analyze complex data sets.",
                    "Writers create engaging content daily.",
                    "Scientists conduct thorough studies.",
                    "Experts design user-friendly websites."
                ];
                
                for ($i = $passiveSentences; $i < $totalSentences; $i++) {
                    $content .= $activeTemplates[($i - $passiveSentences) % count($activeTemplates)] . " ";
                }
                
                $content .= "</p>";
                return $content;
            },
            Generator\choose(1, 5)
        );
    }
    
    /**
     * Helper method to generate content with high percentage of long sentences
     */
    private function generateContentWithLongSentences($targetPercentage) {
        return Generator\map(
            function($sentences) use ($targetPercentage) {
                $totalSentences = 8;
                $longSentences = ceil(($targetPercentage / 100) * $totalSentences);
                
                $content = "<p>";
                
                // Add long sentences (over 20 words)
                for ($i = 0; $i < $longSentences; $i++) {
                    $content .= "This is a very long sentence that contains many words and clauses, making it difficult to read and understand, which negatively impacts the overall readability score of the content and should be shortened for better user experience. ";
                }
                
                // Add normal length sentences
                for ($i = $longSentences; $i < $totalSentences; $i++) {
                    $content .= "This is a normal sentence with good length. ";
                }
                
                $content .= "</p>";
                return $content;
            },
            Generator\choose(1, 5)
        );
    }
    
    /**
     * Helper method to generate content with few transition words
     */
    private function generateContentWithFewTransitions($targetPercentage) {
        return Generator\map(
            function($sentences) use ($targetPercentage) {
                $totalSentences = 10;
                $transitionSentences = ceil(($targetPercentage / 100) * $totalSentences);
                
                $content = "<p>";
                
                // Add sentences with transitions
                $transitionTemplates = [
                    "Furthermore, this approach works well.",
                    "However, there are some limitations.",
                    "Additionally, we should consider alternatives."
                ];
                
                for ($i = 0; $i < $transitionSentences; $i++) {
                    $content .= $transitionTemplates[$i % count($transitionTemplates)] . " ";
                }
                
                // Add sentences without transitions
                for ($i = $transitionSentences; $i < $totalSentences; $i++) {
                    $content .= "This sentence has no transition words. ";
                }
                
                $content .= "</p>";
                return $content;
            },
            Generator\choose(1, 5)
        );
    }
    
    /**
     * Helper method to generate content with good readability
     */
    private function generateReadableContent() {
        return Generator\constant(
            "<p>This content demonstrates good readability practices. Furthermore, it uses appropriate transition words. " .
            "The sentences are well-structured and easy to read. Additionally, passive voice is minimized throughout. " .
            "Moreover, sentence length remains within optimal ranges. Therefore, this content should pass all readability tests. " .
            "However, we still need sufficient content for proper analysis. Consequently, we add more sentences here. " .
            "Finally, this ensures comprehensive testing of the readability system.</p>"
        );
    }

    /**
     * **Feature: content-generation-fixes, Property 14: Error handling and logging**
     * **Validates: Requirements 6.3**
     * 
     * For any persistent validation failures, the system should log specific errors 
     * and provide manual override options
     */
    public function test_error_handling_and_logging() {
        $this->minimumEvaluationRatio(0.01);
        
        $this->forAll(
            Generator\elements(['meta_description', 'keyword_density', 'readability', 'title', 'images']), // component
            Generator\elements(['too_short', 'too_long', 'missing_keyword', 'excessive_density', 'poor_readability']), // error type
            Generator\elements(['error', 'warning', 'info']), // severity
            Generator\choose(1, 10) // error frequency
        )
        ->then(function($component, $errorType, $severity, $errorFrequency) {
            $errorHandler = new SEOErrorHandler();
            
            // Generate error message based on type
            $errorMessages = [
                'too_short' => 'Content is too short for requirements',
                'too_long' => 'Content exceeds maximum length',
                'missing_keyword' => 'Required keyword not found',
                'excessive_density' => 'Keyword density exceeds maximum threshold',
                'poor_readability' => 'Content fails readability standards'
            ];
            $errorMessage = $errorMessages[$errorType];
            
            // Create context data
            $context = [
                'content_title' => 'Test Content Title',
                'focus_keyword' => 'test keyword',
                'validation_step' => 'automated_validation',
                'timestamp' => current_time('mysql')
            ];
            
            // Property 1: Error logging should always return log entry
            $logEntry = $errorHandler->logValidationFailure($component, $errorMessage, $context, $severity);
            $this->assertIsArray($logEntry, "Error logging must return an array");
            $this->assertArrayHasKey('timestamp', $logEntry, "Log entry must have timestamp");
            $this->assertArrayHasKey('component', $logEntry, "Log entry must track component");
            $this->assertArrayHasKey('error', $logEntry, "Log entry must contain error message");
            $this->assertArrayHasKey('severity', $logEntry, "Log entry must track severity");
            $this->assertArrayHasKey('context', $logEntry, "Log entry must preserve context");
            
            // Property 2: Log entry should preserve all input data
            $this->assertEquals($component, $logEntry['component'], "Component should be preserved");
            $this->assertEquals($errorMessage, $logEntry['error'], "Error message should be preserved");
            $this->assertEquals($severity, $logEntry['severity'], "Severity should be preserved");
            $this->assertEquals($context, $logEntry['context'], "Context should be preserved");
            
            // Property 3: Timestamp should be valid and recent
            $logTimestamp = strtotime($logEntry['timestamp']);
            $currentTimestamp = time();
            $this->assertLessThanOrEqual($currentTimestamp, $logTimestamp, "Log timestamp should not be in future");
            $this->assertGreaterThan($currentTimestamp - 60, $logTimestamp, "Log timestamp should be recent (within 60 seconds)");
            
            // Property 4: Session and user tracking should work
            $this->assertArrayHasKey('session_id', $logEntry, "Log entry should track session");
            $this->assertArrayHasKey('user_id', $logEntry, "Log entry should track user");
            $this->assertNotEmpty($logEntry['session_id'], "Session ID should not be empty");
            
            // Simulate multiple occurrences of the same error
            for ($i = 1; $i < $errorFrequency; $i++) {
                $errorHandler->logValidationFailure($component, $errorMessage, $context, $severity);
            }
            
            // Property 5: Error statistics should be updated correctly
            $stats = $errorHandler->getErrorStats($component);
            $this->assertIsArray($stats, "Error stats should be an array");
            
            if (!empty($stats)) {
                $errorKey = array_keys($stats)[0]; // Get first error key
                $errorStat = $stats[$errorKey];
                
                $this->assertArrayHasKey('component', $errorStat, "Error stat should track component");
                $this->assertArrayHasKey('error', $errorStat, "Error stat should track error message");
                $this->assertArrayHasKey('count', $errorStat, "Error stat should track occurrence count");
                $this->assertArrayHasKey('first_occurrence', $errorStat, "Error stat should track first occurrence");
                $this->assertArrayHasKey('last_occurrence', $errorStat, "Error stat should track last occurrence");
                
                // Property 6: Error count should match logged occurrences
                $this->assertEquals($errorFrequency, $errorStat['count'], 
                    "Error count should match number of logged occurrences");
                
                // Property 7: Component and error should match
                $this->assertEquals($component, $errorStat['component'], 
                    "Stat component should match logged component");
                $this->assertEquals($errorMessage, $errorStat['error'], 
                    "Stat error should match logged error");
            }
            
            // Property 8: Manual override functionality should work
            $overrideConfig = [
                'skip_validation' => true,
                'reason' => 'Business requirement override',
                'approved_by' => 'admin_user'
            ];
            
            $overrideResult = $errorHandler->addManualOverride($component, $errorMessage, $overrideConfig);
            $this->assertTrue($overrideResult, "Manual override addition should succeed");
            
            // Property 9: Manual override should be retrievable
            $retrievedOverride = $errorHandler->getManualOverride($component, $errorMessage);
            $this->assertNotFalse($retrievedOverride, "Manual override should be retrievable");
            $this->assertEquals($overrideConfig, $retrievedOverride, "Retrieved override should match original");
            
            // Property 10: Override should be logged
            $recentErrors = $errorHandler->getRecentErrors(10, 'manual_override');
            $this->assertNotEmpty($recentErrors, "Override creation should be logged");
            
            $overrideLog = $recentErrors[0];
            $this->assertEquals('manual_override', $overrideLog['component'], "Override log should have correct component");
            $this->assertStringContainsString('Override created', $overrideLog['error'], "Override log should indicate creation");
            
            // Property 11: Override removal should work
            $removalResult = $errorHandler->removeManualOverride($component, $errorMessage);
            $this->assertTrue($removalResult, "Manual override removal should succeed");
            
            $removedOverride = $errorHandler->getManualOverride($component, $errorMessage);
            $this->assertFalse($removedOverride, "Removed override should not be retrievable");
            
            // Property 12: Recent errors should be retrievable
            $recentErrors = $errorHandler->getRecentErrors(50, $component);
            $this->assertIsArray($recentErrors, "Recent errors should be an array");
            
            if (!empty($recentErrors)) {
                $recentError = $recentErrors[0];
                $this->assertArrayHasKey('timestamp', $recentError, "Recent error should have timestamp");
                $this->assertArrayHasKey('component', $recentError, "Recent error should have component");
                $this->assertArrayHasKey('error', $recentError, "Recent error should have error message");
                $this->assertArrayHasKey('severity', $recentError, "Recent error should have severity");
            }
            
            // Property 13: Log export should work
            $exportedLogs = $errorHandler->exportLogs('json', 1);
            $this->assertNotFalse($exportedLogs, "Log export should succeed");
            $this->assertIsString($exportedLogs, "Exported logs should be a string");
            
            $decodedLogs = json_decode($exportedLogs, true);
            $this->assertIsArray($decodedLogs, "JSON export should be valid JSON");
            
            // Property 14: CSV export should work
            $csvExport = $errorHandler->exportLogs('csv', 1);
            $this->assertNotFalse($csvExport, "CSV export should succeed");
            $this->assertIsString($csvExport, "CSV export should be a string");
            $this->assertStringContainsString('Timestamp,Component,Error,Severity', $csvExport, 
                "CSV should contain proper headers");
            
            // Property 15: Error frequency should trigger adaptive suggestions for high-frequency errors
            if ($errorFrequency >= 10) {
                // Simulate high-frequency error scenario
                for ($i = 0; $i < 10; $i++) {
                    $errorHandler->logValidationFailure($component, $errorMessage, $context, 'error');
                }
                
                // Check if adaptive suggestions were logged
                $adaptiveLogs = $errorHandler->getRecentErrors(20, 'adaptive_suggestion');
                $hasAdaptiveSuggestion = false;
                
                foreach ($adaptiveLogs as $log) {
                    if (strpos($log['error'], 'Rule adaptation suggested') !== false) {
                        $hasAdaptiveSuggestion = true;
                        break;
                    }
                }
                
                $this->assertTrue($hasAdaptiveSuggestion, 
                    "High-frequency errors should trigger adaptive rule suggestions");
            }
            
            // Property 16: Log file should be created and accessible
            $logFile = wp_content_dir() . '/acs_seo_errors.log';
            $this->assertFileExists($logFile, "Error log file should be created");
            $this->assertFileIsReadable($logFile, "Error log file should be readable");
            
            // Property 17: Log entries should be properly formatted JSON
            $logContents = file_get_contents($logFile);
            $logLines = explode("\n", trim($logContents));
            
            foreach ($logLines as $line) {
                if (!empty($line)) {
                    $decodedLine = json_decode($line, true);
                    $this->assertIsArray($decodedLine, "Each log line should be valid JSON");
                    $this->assertArrayHasKey('timestamp', $decodedLine, "Each log entry should have timestamp");
                    $this->assertArrayHasKey('component', $decodedLine, "Each log entry should have component");
                    $this->assertArrayHasKey('error', $decodedLine, "Each log entry should have error");
                }
            }
            
            // Property 18: Error handler should handle edge cases gracefully
            $emptyContextResult = $errorHandler->logValidationFailure($component, $errorMessage, [], $severity);
            $this->assertIsArray($emptyContextResult, "Empty context should not cause errors");
            
            $nullContextResult = $errorHandler->logValidationFailure($component, $errorMessage, null, $severity);
            $this->assertIsArray($nullContextResult, "Null context should not cause errors");
            
            // Property 19: Severity levels should be preserved correctly
            $validSeverities = ['error', 'warning', 'info'];
            $this->assertContains($severity, $validSeverities, "Severity should be valid");
            $this->assertEquals($severity, $logEntry['severity'], "Severity should be preserved in log entry");
            
            // Property 20: Context data should be preserved as-is
            if (!empty($context)) {
                foreach ($context as $key => $value) {
                    $this->assertArrayHasKey($key, $logEntry['context'], 
                        "Context key '{$key}' should be preserved");
                    $this->assertEquals($value, $logEntry['context'][$key], 
                        "Context value for '{$key}' should be preserved");
                }
            }
        });
    }

    /**
     * **Feature: content-generation-fixes, Property 15: Adaptive validation rules**
     * **Validates: Requirements 6.5**
     * 
     * For any updates to validation rules, the system should automatically adapt 
     * without manual intervention
     */
    public function test_adaptive_validation_rules() {
        $this->minimumEvaluationRatio(0.01);
        
        $this->forAll(
            Generator\elements(['minMetaDescLength', 'maxMetaDescLength', 'minKeywordDensity', 'maxKeywordDensity', 'maxPassiveVoice']), // rule name
            Generator\oneOf(
                Generator\choose(100, 140), // for meta desc min
                Generator\choose(150, 200), // for meta desc max
                Generator\choose(0.1, 1.0), // for keyword density min
                Generator\choose(2.0, 5.0), // for keyword density max
                Generator\choose(5.0, 20.0) // for passive voice max
            ), // new rule value
            Generator\choose(1, 5) // number of rule updates
        )
        ->then(function($ruleName, $newValue, $updateCount) {
            $errorHandler = new SEOErrorHandler();
            $pipeline = new SEOValidationPipeline();
            
            // Get initial rules
            $initialRules = $errorHandler->getAdaptiveRules();
            $this->assertIsArray($initialRules, "Initial rules should be an array");
            $this->assertArrayHasKey($ruleName, $initialRules, "Rule '{$ruleName}' should exist in initial rules");
            
            // Property 1: Rule updates should be applied correctly
            $newRules = [$ruleName => $newValue];
            $updateResult = $errorHandler->updateAdaptiveRules($newRules);
            $this->assertTrue($updateResult, "Rule update should succeed");
            
            // Property 2: Updated rules should be retrievable
            $updatedRules = $errorHandler->getAdaptiveRules();
            $this->assertIsArray($updatedRules, "Updated rules should be an array");
            $this->assertArrayHasKey($ruleName, $updatedRules, "Updated rules should contain the modified rule");
            $this->assertEquals($newValue, $updatedRules[$ruleName], "Rule value should be updated correctly");
            
            // Property 3: Other rules should remain unchanged
            foreach ($initialRules as $key => $value) {
                if ($key !== $ruleName) {
                    $this->assertEquals($value, $updatedRules[$key], 
                        "Unmodified rule '{$key}' should remain unchanged");
                }
            }
            
            // Property 4: Pipeline should use updated rules
            $pipelineConfig = $pipeline->getConfig();
            $this->assertIsArray($pipelineConfig, "Pipeline config should be an array");
            
            // Update pipeline with new rules
            $pipeline->updateAdaptiveRules($newRules);
            $newPipelineConfig = $pipeline->getConfig();
            $this->assertEquals($newValue, $newPipelineConfig[$ruleName], 
                "Pipeline should use updated rule value");
            
            // Property 5: Multiple rule updates should work
            $multipleRules = [];
            $ruleNames = ['minMetaDescLength', 'maxKeywordDensity', 'maxPassiveVoice'];
            $ruleValues = [110, 3.0, 15.0];
            
            for ($i = 0; $i < min($updateCount, count($ruleNames)); $i++) {
                $multipleRules[$ruleNames[$i]] = $ruleValues[$i];
            }
            
            $multiUpdateResult = $errorHandler->updateAdaptiveRules($multipleRules);
            $this->assertTrue($multiUpdateResult, "Multiple rule updates should succeed");
            
            $finalRules = $errorHandler->getAdaptiveRules();
            foreach ($multipleRules as $key => $value) {
                $this->assertEquals($value, $finalRules[$key], 
                    "Multiple update rule '{$key}' should be applied correctly");
            }
            
            // Property 6: Rule updates should be logged
            $recentErrors = $errorHandler->getRecentErrors(10, 'adaptive_rules');
            $this->assertNotEmpty($recentErrors, "Rule updates should be logged");
            
            $ruleUpdateLog = null;
            foreach ($recentErrors as $log) {
                if (strpos($log['error'], 'Rules updated') !== false) {
                    $ruleUpdateLog = $log;
                    break;
                }
            }
            
            $this->assertNotNull($ruleUpdateLog, "Rule update should be logged");
            $this->assertEquals('adaptive_rules', $ruleUpdateLog['component'], 
                "Rule update log should have correct component");
            $this->assertArrayHasKey('context', $ruleUpdateLog, 
                "Rule update log should contain context");
            
            // Property 7: Rule validation should work with new values
            $testContent = [
                'title' => 'Test SEO Content',
                'meta_description' => str_repeat('a', 120), // Minimum length
                'content' => '<p>This is test content for validation.</p>',
                'focus_keyword' => 'test'
            ];
            
            // Test with updated rules
            $validationResult = $pipeline->validateAndCorrect($testContent, 'test', []);
            $this->assertInstanceOf('SEOValidationResult', $validationResult, 
                "Validation should work with updated rules");
            
            // Property 8: Rule persistence should work
            $newErrorHandler = new SEOErrorHandler();
            $persistedRules = $newErrorHandler->getAdaptiveRules();
            
            foreach ($multipleRules as $key => $value) {
                $this->assertEquals($value, $persistedRules[$key], 
                    "Rule '{$key}' should persist across handler instances");
            }
            
            // Property 9: Invalid rule updates should be handled gracefully
            $invalidRules = [
                'nonexistent_rule' => 100,
                'invalid_value' => 'not_a_number'
            ];
            
            $invalidUpdateResult = $errorHandler->updateAdaptiveRules($invalidRules);
            $this->assertTrue($invalidUpdateResult, "Invalid rule updates should not cause errors");
            
            $rulesAfterInvalid = $errorHandler->getAdaptiveRules();
            $this->assertArrayHasKey('nonexistent_rule', $rulesAfterInvalid, 
                "New rules should be added even if they don't exist initially");
            
            // Property 10: Rule boundaries should be respected
            $boundaryRules = [
                'minMetaDescLength' => 50,  // Very low
                'maxMetaDescLength' => 300, // Very high
                'minKeywordDensity' => 0.0, // Zero
                'maxKeywordDensity' => 10.0 // Very high
            ];
            
            $boundaryUpdateResult = $errorHandler->updateAdaptiveRules($boundaryRules);
            $this->assertTrue($boundaryUpdateResult, "Boundary rule updates should succeed");
            
            $boundaryRulesResult = $errorHandler->getAdaptiveRules();
            foreach ($boundaryRules as $key => $value) {
                $this->assertEquals($value, $boundaryRulesResult[$key], 
                    "Boundary rule '{$key}' should be applied");
            }
            
            // Property 11: Rule consistency should be maintained
            $consistentRules = $errorHandler->getAdaptiveRules();
            
            // Meta description rules should be consistent
            if (isset($consistentRules['minMetaDescLength']) && isset($consistentRules['maxMetaDescLength'])) {
                $this->assertLessThanOrEqual($consistentRules['maxMetaDescLength'], 
                    $consistentRules['minMetaDescLength'] + 100, 
                    "Meta description min/max should be reasonably related");
            }
            
            // Keyword density rules should be consistent
            if (isset($consistentRules['minKeywordDensity']) && isset($consistentRules['maxKeywordDensity'])) {
                $this->assertLessThan($consistentRules['maxKeywordDensity'], 
                    $consistentRules['minKeywordDensity'], 
                    "Keyword density min should be less than max");
            }
            
            // Property 12: Rule export and import should work
            $exportedRules = $errorHandler->getAdaptiveRules();
            $this->assertIsArray($exportedRules, "Exported rules should be an array");
            $this->assertNotEmpty($exportedRules, "Exported rules should not be empty");
            
            // Create new handler and import rules
            $importHandler = new SEOErrorHandler();
            $importResult = $importHandler->updateAdaptiveRules($exportedRules);
            $this->assertTrue($importResult, "Rule import should succeed");
            
            $importedRules = $importHandler->getAdaptiveRules();
            foreach ($exportedRules as $key => $value) {
                $this->assertEquals($value, $importedRules[$key], 
                    "Imported rule '{$key}' should match exported value");
            }
            
            // Property 13: Rule versioning should be tracked
            $ruleHistory = $errorHandler->getRecentErrors(20, 'adaptive_rules');
            $updateCount = 0;
            
            foreach ($ruleHistory as $log) {
                if (strpos($log['error'], 'Rules updated') !== false) {
                    $updateCount++;
                }
            }
            
            $this->assertGreaterThan(0, $updateCount, 
                "Rule update history should be tracked");
            
            // Property 14: Automatic adaptation should work
            // Simulate frequent errors that should trigger adaptation
            $frequentError = 'Meta description too short';
            for ($i = 0; $i < 15; $i++) {
                $errorHandler->logValidationFailure('meta_description', $frequentError, [
                    'current_length' => 115,
                    'required_length' => 120
                ], 'error');
            }
            
            // Check if adaptation was suggested
            $adaptiveLogs = $errorHandler->getRecentErrors(30, 'adaptive_suggestion');
            $hasAdaptation = false;
            
            foreach ($adaptiveLogs as $log) {
                if (strpos($log['error'], 'Rule adaptation suggested') !== false) {
                    $hasAdaptation = true;
                    break;
                }
            }
            
            $this->assertTrue($hasAdaptation, 
                "Frequent errors should trigger automatic adaptation suggestions");
            
            // Property 15: Rule validation should prevent invalid configurations
            $currentRules = $errorHandler->getAdaptiveRules();
            
            // All numeric rules should be positive
            foreach ($currentRules as $key => $value) {
                if (is_numeric($value)) {
                    $this->assertGreaterThanOrEqual(0, $value, 
                        "Rule '{$key}' should be non-negative");
                }
            }
            
            // Property 16: Rule updates should not break existing functionality
            $testPipeline = new SEOValidationPipeline($currentRules);
            $testResult = $testPipeline->validateAndCorrect($testContent, 'test', []);
            
            $this->assertInstanceOf('SEOValidationResult', $testResult, 
                "Pipeline should work with any valid rule configuration");
            $this->assertIsArray($testResult->toArray(), 
                "Validation result should be serializable");
            
            // Property 17: Rule changes should be atomic
            $atomicRules = [
                'minMetaDescLength' => 125,
                'maxMetaDescLength' => 155,
                'minKeywordDensity' => 0.8,
                'maxKeywordDensity' => 2.2
            ];
            
            $atomicResult = $errorHandler->updateAdaptiveRules($atomicRules);
            $this->assertTrue($atomicResult, "Atomic rule update should succeed");
            
            $atomicFinalRules = $errorHandler->getAdaptiveRules();
            foreach ($atomicRules as $key => $value) {
                $this->assertEquals($value, $atomicFinalRules[$key], 
                    "All atomic rules should be applied together");
            }
            
            // Property 18: Default rules should always be available
            $defaultRules = [
                'minMetaDescLength', 'maxMetaDescLength', 'minKeywordDensity', 
                'maxKeywordDensity', 'maxPassiveVoice', 'maxLongSentences', 
                'minTransitionWords', 'maxTitleLength', 'maxSubheadingKeywordUsage'
            ];
            
            $finalRulesCheck = $errorHandler->getAdaptiveRules();
            foreach ($defaultRules as $requiredRule) {
                $this->assertArrayHasKey($requiredRule, $finalRulesCheck, 
                    "Default rule '{$requiredRule}' should always be available");
            }
            
            // Property 19: Rule updates should be reversible
            $preRevertRules = $errorHandler->getAdaptiveRules();
            $revertRules = [
                'minMetaDescLength' => 120,
                'maxMetaDescLength' => 156,
                'minKeywordDensity' => 0.5,
                'maxKeywordDensity' => 2.5
            ];
            
            $revertResult = $errorHandler->updateAdaptiveRules($revertRules);
            $this->assertTrue($revertResult, "Rule reversion should succeed");
            
            $revertedRules = $errorHandler->getAdaptiveRules();
            foreach ($revertRules as $key => $value) {
                $this->assertEquals($value, $revertedRules[$key], 
                    "Reverted rule '{$key}' should match expected value");
            }
            
            // Property 20: Rule system should be thread-safe (basic check)
            $concurrentHandler1 = new SEOErrorHandler();
            $concurrentHandler2 = new SEOErrorHandler();
            
            $concurrent1Rules = ['maxPassiveVoice' => 12.0];
            $concurrent2Rules = ['maxLongSentences' => 30.0];
            
            $concurrent1Result = $concurrentHandler1->updateAdaptiveRules($concurrent1Rules);
            $concurrent2Result = $concurrentHandler2->updateAdaptiveRules($concurrent2Rules);
            
            $this->assertTrue($concurrent1Result, "Concurrent update 1 should succeed");
            $this->assertTrue($concurrent2Result, "Concurrent update 2 should succeed");
            
            // Both updates should be reflected
            $finalConcurrentRules = $errorHandler->getAdaptiveRules();
            $this->assertEquals(12.0, $finalConcurrentRules['maxPassiveVoice'], 
                "Concurrent update 1 should be applied");
            $this->assertEquals(30.0, $finalConcurrentRules['maxLongSentences'], 
                "Concurrent update 2 should be applied");
        });
    }
    
    /**
     * **Feature: multi-pass-seo-optimizer, Property 5: Iterative Optimization Convergence**
     * **Validates: Requirements 1.5, 3.2**
     * 
     * For any content with remaining issues, the system should continue optimization cycles 
     * until 100% compliance is achieved or maximum iterations reached
     */
    public function test_iterative_optimization_convergence() {
        $this->minimumEvaluationRatio(0.01);
        
        $this->forAll(
            Generator\elements(['seo', 'wordpress', 'content', 'marketing', 'blog']), // focus keyword
            Generator\seq(Generator\elements(['optimization', 'guide', 'tips', 'tutorial', 'best practices'])), // synonyms
            Generator\choose(1, 5), // max iterations
            Generator\choose(50, 95) // initial score (not perfect)
        )
        ->then(function($focusKeyword, $synonyms, $maxIterations, $initialScore) {
            // Create test content with known issues
            $content = [
                'title' => 'Test Title',
                'content' => '<p>This is test content that needs optimization. ' . str_repeat('More content here. ', 50) . '</p>',
                'meta_description' => 'Short description' // Too short, will need correction
            ];
            
            // Create optimizer with test configuration
            $config = [
                'maxIterations' => $maxIterations,
                'targetComplianceScore' => 100.0,
                'enableEarlyTermination' => true,
                'minImprovementThreshold' => 1.0,
                'stagnationThreshold' => 2,
                'autoCorrection' => true
            ];
            
            $optimizer = new MultiPassSEOOptimizer($config);
            
            // Run optimization
            $result = $optimizer->optimizeContent($content, $focusKeyword, $synonyms);
            
            // Property 1: Optimization should always return a result
            $this->assertIsArray($result, "Optimization must return a result array");
            $this->assertArrayHasKey('success', $result, "Result must indicate success status");
            $this->assertArrayHasKey('content', $result, "Result must contain optimized content");
            $this->assertArrayHasKey('validationResult', $result, "Result must contain validation result");
            $this->assertArrayHasKey('progressData', $result, "Result must contain progress data");
            
            // Property 2: Progress data should track iterations correctly
            $progressData = $result['progressData'];
            $this->assertArrayHasKey('totalIterations', $progressData, "Progress must track total iterations");
            $this->assertArrayHasKey('iterations', $progressData, "Progress must track iteration details");
            $this->assertLessThanOrEqual($maxIterations, $progressData['totalIterations'], 
                "Total iterations should not exceed maximum allowed");
            $this->assertGreaterThan(0, $progressData['totalIterations'], 
                "At least one iteration should be performed");
            
            // Property 3: Final score should be better than or equal to initial score
            if (!empty($progressData['iterations'])) {
                $initialIterationScore = $progressData['iterations'][0]['score'];
                $finalScore = $progressData['finalScore'];
                $this->assertGreaterThanOrEqual($initialIterationScore, $finalScore, 
                    "Final score should be better than or equal to initial score");
            }
            
            // Property 4: If compliance achieved, score should be 100%
            if ($progressData['complianceAchieved']) {
                $this->assertEquals(100.0, $progressData['finalScore'], 
                    "When compliance is achieved, final score should be 100%");
                $this->assertEquals('compliance_achieved', $progressData['terminationReason'], 
                    "Termination reason should indicate compliance achieved");
            }
            
            // Property 5: Termination reason should be valid
            $validReasons = ['compliance_achieved', 'max_iterations_reached', 'stagnation_detected', 'insufficient_improvement', 'initial_compliance', 'critical_error'];
            $this->assertContains($progressData['terminationReason'], $validReasons, 
                "Termination reason must be one of the valid reasons");
            
            // Property 6: Performance metrics should be tracked
            $this->assertArrayHasKey('performanceMetrics', $progressData, 
                "Progress data must include performance metrics");
            $performanceMetrics = $progressData['performanceMetrics'];
            $this->assertArrayHasKey('totalDuration', $performanceMetrics, 
                "Performance metrics must include total duration");
            $this->assertArrayHasKey('finalComplianceScore', $performanceMetrics, 
                "Performance metrics must include final compliance score");
            
            // Property 7: Iteration data should be consistent
            foreach ($progressData['iterations'] as $iteration) {
                $this->assertArrayHasKey('iteration', $iteration, "Each iteration must have iteration number");
                $this->assertArrayHasKey('score', $iteration, "Each iteration must have score");
                $this->assertArrayHasKey('timestamp', $iteration, "Each iteration must have timestamp");
                $this->assertGreaterThanOrEqual(0, $iteration['score'], "Iteration score must be >= 0");
                $this->assertLessThanOrEqual(100, $iteration['score'], "Iteration score must be <= 100");
            }
            
            // Property 8: Content structure should be preserved
            $finalContent = $result['content'];
            $this->assertIsArray($finalContent, "Final content must be an array");
            $this->assertArrayHasKey('title', $finalContent, "Final content must have title");
            $this->assertArrayHasKey('content', $finalContent, "Final content must have content");
            $this->assertArrayHasKey('meta_description', $finalContent, "Final content must have meta description");
            
            // Property 9: Validation result should be consistent with final score
            $validationResult = $result['validationResult'];
            $this->assertEquals($progressData['finalScore'], $validationResult->overallScore, 
                "Validation result score should match progress data final score");
        });
    }
    
    /**
     * **Feature: multi-pass-seo-optimizer, Property 6: Loop Termination Guarantee**
     * **Validates: Requirements 3.1, 3.2, 3.3, 3.4**
     * 
     * For any optimization process, the loop manager should guarantee termination within 
     * maximum iterations, upon achieving compliance, or when no improvement is detected
     */
    public function test_loop_termination_guarantee() {
        $this->minimumEvaluationRatio(0.01);
        
        $this->forAll(
            Generator\elements(['seo', 'wordpress', 'content', 'marketing', 'blog']), // focus keyword
            Generator\choose(1, 3), // max iterations (small for testing)
            Generator\choose(1, 3), // stagnation threshold
            Generator\elements([true, false]), // enable early termination
            Generator\choose(0, 5) // min improvement threshold
        )
        ->then(function($focusKeyword, $maxIterations, $stagnationThreshold, $enableEarlyTermination, $minImprovement) {
            // Create test content
            $content = [
                'title' => 'Test Title for SEO Optimization',
                'content' => '<p>This is comprehensive test content for SEO optimization testing. ' . 
                           str_repeat('Additional content to meet word count requirements. ', 30) . '</p>',
                'meta_description' => 'This is a test meta description that should be optimized for SEO compliance and keyword inclusion.'
            ];
            
            // Create optimizer with termination test configuration
            $config = [
                'maxIterations' => $maxIterations,
                'targetComplianceScore' => 100.0,
                'enableEarlyTermination' => $enableEarlyTermination,
                'stagnationThreshold' => $stagnationThreshold,
                'minImprovementThreshold' => $minImprovement,
                'autoCorrection' => true,
                'logLevel' => 'error' // Reduce logging for tests
            ];
            
            $optimizer = new MultiPassSEOOptimizer($config);
            
            // Record start time
            $startTime = microtime(true);
            
            // Run optimization
            $result = $optimizer->optimizeContent($content, $focusKeyword, []);
            
            // Record end time
            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            
            // Property 1: Optimization must terminate within reasonable time (30 seconds max)
            $this->assertLessThan(30, $executionTime, 
                "Optimization must terminate within 30 seconds, took {$executionTime} seconds");
            
            // Property 2: Total iterations must not exceed maximum
            $progressData = $result['progressData'];
            $this->assertLessThanOrEqual($maxIterations, $progressData['totalIterations'], 
                "Total iterations ({$progressData['totalIterations']}) must not exceed maximum ({$maxIterations})");
            
            // Property 3: Termination reason must be valid and consistent
            $terminationReason = $progressData['terminationReason'];
            $validReasons = ['compliance_achieved', 'max_iterations_reached', 'stagnation_detected', 'insufficient_improvement', 'initial_compliance', 'critical_error'];
            $this->assertContains($terminationReason, $validReasons, 
                "Termination reason '{$terminationReason}' must be valid");
            
            // Property 4: If max iterations reached, should be exactly at max
            if ($terminationReason === 'max_iterations_reached') {
                $this->assertEquals($maxIterations, $progressData['totalIterations'], 
                    "When max iterations reached, total iterations should equal max iterations");
            }
            
            // Property 5: If compliance achieved, score should be 100%
            if ($terminationReason === 'compliance_achieved') {
                $this->assertEquals(100.0, $progressData['finalScore'], 
                    "When compliance achieved, final score should be 100%");
                $this->assertTrue($progressData['complianceAchieved'], 
                    "Compliance achieved flag should be true");
            }
            
            // Property 6: If stagnation detected, should respect stagnation threshold
            if ($terminationReason === 'stagnation_detected' && $enableEarlyTermination) {
                // Should have stopped before max iterations due to stagnation
                $this->assertLessThan($maxIterations, $progressData['totalIterations'], 
                    "Stagnation termination should occur before max iterations");
            }
            
            // Property 7: Optimization should always return valid content
            $this->assertIsArray($result['content'], "Result must contain valid content array");
            $this->assertNotEmpty($result['content']['title'], "Content must have non-empty title");
            $this->assertNotEmpty($result['content']['content'], "Content must have non-empty content");
            $this->assertNotEmpty($result['content']['meta_description'], "Content must have non-empty meta description");
            
            // Property 8: Progress tracking should be complete
            // Note: iterations include baseline (iteration 0) plus optimization iterations
            $expectedIterationCount = $progressData['totalIterations'] + 1; // +1 for baseline
            $this->assertCount($expectedIterationCount, $progressData['iterations'], 
                "Number of iteration records should include baseline plus optimization iterations");
            
            // Property 9: Performance metrics should be reasonable
            $performanceMetrics = $progressData['performanceMetrics'];
            $this->assertGreaterThan(0, $performanceMetrics['totalDuration'], 
                "Total duration should be greater than 0");
            $this->assertGreaterThanOrEqual(0, $performanceMetrics['finalComplianceScore'], 
                "Final compliance score should be >= 0");
            $this->assertLessThanOrEqual(100, $performanceMetrics['finalComplianceScore'], 
                "Final compliance score should be <= 100");
            
            // Property 10: Error handling should be robust
            $this->assertIsArray($result['errorLog'], "Error log should be an array");
            
            // Property 11: Configuration should be preserved
            $this->assertArrayHasKey('config', $result, "Result should include configuration");
            $resultConfig = $result['config'];
            $this->assertEquals($maxIterations, $resultConfig['maxIterations'], 
                "Configuration should preserve max iterations");
            $this->assertEquals($enableEarlyTermination, $resultConfig['enableEarlyTermination'], 
                "Configuration should preserve early termination setting");
        });
    }

    /**
     * **Feature: multi-pass-seo-optimizer, Property 5: Iterative Optimization Convergence**
     * **Validates: Requirements 1.5, 3.2**
     * 
     * For any content with remaining issues, the system should continue optimization cycles 
     * until 100% compliance is achieved or maximum iterations reached
     */
    public function test_iterative_optimization_convergence() {
        $this->minimumEvaluationRatio(0.01);
        
        $this->forAll(
            Generator\elements(['seo', 'wordpress', 'content', 'marketing', 'blog']), // focus keyword
            Generator\seq(Generator\elements(['optimization', 'guide', 'tips', 'tutorial', 'best practices'])), // synonyms
            Generator\choose(2, 5), // max iterations
            Generator\elements([true, false]), // enable early termination
            Generator\choose(1, 3) // stagnation threshold
        )
        ->then(function($focusKeyword, $synonyms, $maxIterations, $enableEarlyTermination, $stagnationThreshold) {
            // Create content with known SEO issues
            $problematicContent = [
                'title' => 'A Very Long Title That Exceeds The Recommended Character Limit For SEO Optimization And Should Be Shortened',
                'content' => '<p>This content was written by someone and mistakes were made during the process which was analyzed by researchers who found that improvements were needed and these improvements were implemented by developers who worked on the system that was designed by experts. The content lacks proper keyword density and readability issues are present throughout the text.</p>',
                'meta_description' => 'Short desc', // Too short
                'focus_keyword' => $focusKeyword,
                'secondary_keywords' => $synonyms
            ];
            
            // Configure optimizer for convergence testing
            $config = [
                'maxIterations' => $maxIterations,
                'targetComplianceScore' => 100.0,
                'enableEarlyTermination' => $enableEarlyTermination,
                'stagnationThreshold' => $stagnationThreshold,
                'minImprovementThreshold' => 1.0,
                'logLevel' => 'error' // Reduce noise in tests
            ];
            
            $optimizer = new MultiPassSEOOptimizer($config);
            
            // Run optimization
            $startTime = microtime(true);
            $result = $optimizer->optimizeContent($problematicContent, $focusKeyword, $synonyms);
            $executionTime = microtime(true) - $startTime;
            
            // Property 1: Optimization should always converge (terminate)
            $this->assertIsArray($result, "Optimization must return a result array");
            $this->assertArrayHasKey('success', $result, "Result must indicate success status");
            $this->assertArrayHasKey('progressData', $result, "Result must contain progress data");
            $this->assertLessThan(30, $executionTime, 
                "Optimization must converge within 30 seconds, took {$executionTime} seconds");
            
            // Property 2: Convergence should respect iteration limits
            $progressData = $result['progressData'];
            $this->assertLessThanOrEqual($maxIterations, $progressData['totalIterations'], 
                "Total iterations ({$progressData['totalIterations']}) must not exceed maximum ({$maxIterations})");
            
            // Property 3: Convergence should show measurable progress
            if ($progressData['totalIterations'] > 0) {
                $iterations = $progressData['iterations'];
                $this->assertGreaterThan(1, count($iterations), 
                    "Should have baseline plus at least one optimization iteration");
                
                // Check that we have baseline (iteration 0) and optimization iterations
                $baselineIteration = $iterations[0];
                $this->assertEquals(0, $baselineIteration['iteration'], 
                    "First iteration should be baseline (iteration 0)");
                $this->assertEquals('baseline', $baselineIteration['type'], 
                    "First iteration should be marked as baseline");
                
                // If we have optimization iterations, they should show progress tracking
                if (count($iterations) > 1) {
                    for ($i = 1; $i < count($iterations); $i++) {
                        $iteration = $iterations[$i];
                        $this->assertEquals($i, $iteration['iteration'], 
                            "Iteration {$i} should be numbered correctly");
                        $this->assertEquals('optimization', $iteration['type'], 
                            "Optimization iterations should be marked as 'optimization'");
                        $this->assertArrayHasKey('score', $iteration, 
                            "Each iteration should track score");
                        $this->assertArrayHasKey('improvements', $iteration, 
                            "Each iteration should track improvements");
                    }
                }
            }
            
            // Property 4: Final score should be better than or equal to initial score
            if (!empty($progressData['iterations'])) {
                $initialScore = $progressData['iterations'][0]['score'];
                $finalScore = $progressData['finalScore'];
                $this->assertGreaterThanOrEqual($initialScore, $finalScore, 
                    "Final score ({$finalScore}) should be >= initial score ({$initialScore})");
            }
            
            // Property 5: Convergence criteria should be respected
            $terminationReason = $progressData['terminationReason'];
            $validReasons = ['compliance_achieved', 'max_iterations_reached', 'stagnation_detected', 'insufficient_improvement', 'initial_compliance'];
            $this->assertContains($terminationReason, $validReasons, 
                "Termination reason '{$terminationReason}' must be valid");
            
            // Property 6: If compliance achieved, should have 100% score
            if ($terminationReason === 'compliance_achieved') {
                $this->assertEquals(100.0, $progressData['finalScore'], 
                    "Compliance achieved should result in 100% score");
                $this->assertTrue($progressData['complianceAchieved'], 
                    "Compliance achieved flag should be true");
            }
            
            // Property 7: Early termination should work when enabled
            if ($enableEarlyTermination && $terminationReason === 'stagnation_detected') {
                $this->assertLessThan($maxIterations, $progressData['totalIterations'], 
                    "Early termination should stop before max iterations");
            }
            
            // Property 8: Progress should be monotonic or show stagnation
            if (count($progressData['iterations']) > 2) {
                $scores = array_column($progressData['iterations'], 'score');
                $improvements = [];
                for ($i = 1; $i < count($scores); $i++) {
                    $improvements[] = $scores[$i] - $scores[$i-1];
                }
                
                // Either we should see overall improvement or detect stagnation
                $totalImprovement = end($scores) - reset($scores);
                $hasStagnation = $terminationReason === 'stagnation_detected' || $terminationReason === 'insufficient_improvement';
                
                $this->assertTrue($totalImprovement >= 0 || $hasStagnation, 
                    "Should show improvement or properly detect stagnation");
            }
            
            // Property 9: Content should be preserved and valid
            $this->assertIsArray($result['content'], "Result must contain valid content");
            $this->assertNotEmpty($result['content']['title'], "Content must have title");
            $this->assertNotEmpty($result['content']['content'], "Content must have content");
            $this->assertNotEmpty($result['content']['meta_description'], "Content must have meta description");
            
            // Property 10: Performance metrics should be tracked
            $performanceMetrics = $progressData['performanceMetrics'];
            $this->assertArrayHasKey('totalDuration', $performanceMetrics, 
                "Should track total duration");
            $this->assertArrayHasKey('finalComplianceScore', $performanceMetrics, 
                "Should track final compliance score");
            $this->assertGreaterThan(0, $performanceMetrics['totalDuration'], 
                "Total duration should be positive");
            $this->assertEquals($progressData['finalScore'], $performanceMetrics['finalComplianceScore'], 
                "Performance metrics should match progress data");
        });
    }

    /**
     * **Feature: multi-pass-seo-optimizer, Property 6: Loop Termination Guarantee**
     * **Validates: Requirements 3.1, 3.2, 3.3, 3.4**
     * 
     * For any optimization process, the loop manager should guarantee termination within 
     * maximum iterations, upon achieving compliance, or when no improvement is detected
     */
    public function test_loop_termination_guarantee() {
        $this->minimumEvaluationRatio(0.01);
        
        $this->forAll(
            Generator\elements(['seo', 'wordpress', 'content', 'marketing', 'blog']), // focus keyword
            Generator\seq(Generator\elements(['optimization', 'guide', 'tips', 'tutorial', 'best practices'])), // synonyms
            Generator\choose(1, 5), // max iterations
            Generator\elements([true, false]), // enable early termination
            Generator\choose(1, 3), // stagnation threshold
            Generator\choose(0, 5) // min improvement threshold (0-5%)
        )
        ->then(function($focusKeyword, $synonyms, $maxIterations, $enableEarlyTermination, $stagnationThreshold, $minImprovement) {
            // Create various content scenarios to test termination
            $contentScenarios = [
                // Already compliant content (should terminate immediately)
                [
                    'title' => "Best {$focusKeyword} Guide for Beginners",
                    'content' => "<p>This comprehensive guide covers {$focusKeyword} optimization techniques. The content includes proper keyword density and readability. Transition words help improve flow. Additionally, the content maintains good structure. Furthermore, it provides valuable information. Moreover, the examples are clear and helpful.</p>",
                    'meta_description' => "Complete {$focusKeyword} guide with step-by-step instructions, best practices, and expert tips for beginners and professionals.",
                    'scenario' => 'compliant'
                ],
                // Problematic content (should iterate)
                [
                    'title' => 'A Very Long Title That Exceeds The Recommended Character Limit For SEO Optimization And Should Be Shortened',
                    'content' => '<p>This content was written by someone and mistakes were made during the process which was analyzed by researchers. The content lacks proper keyword density and readability issues are present throughout the text. Long sentences that exceed twenty words should be shortened for better readability and user experience.</p>',
                    'meta_description' => 'Short',
                    'scenario' => 'problematic'
                ],
                // Moderately problematic content
                [
                    'title' => "Good {$focusKeyword} Tips",
                    'content' => "<p>This article discusses {$focusKeyword} strategies. The content was created by experts and improvements were suggested by analysts. Some sentences are too long and contain multiple clauses that make them difficult to read and understand properly.</p>",
                    'meta_description' => "Learn about {$focusKeyword} with these helpful tips and strategies for better results.",
                    'scenario' => 'moderate'
                ]
            ];
            
            foreach ($contentScenarios as $contentData) {
                $content = [
                    'title' => $contentData['title'],
                    'content' => $contentData['content'],
                    'meta_description' => $contentData['meta_description'],
                    'focus_keyword' => $focusKeyword,
                    'secondary_keywords' => $synonyms
                ];
                
                // Configure optimizer with termination settings
                $config = [
                    'maxIterations' => $maxIterations,
                    'targetComplianceScore' => 100.0,
                    'enableEarlyTermination' => $enableEarlyTermination,
                    'stagnationThreshold' => $stagnationThreshold,
                    'minImprovementThreshold' => $minImprovement,
                    'logLevel' => 'error'
                ];
                
                $optimizer = new MultiPassSEOOptimizer($config);
                
                // Run optimization with timeout protection
                $startTime = microtime(true);
                $result = $optimizer->optimizeContent($content, $focusKeyword, $synonyms);
                $executionTime = microtime(true) - $startTime;
                
                // Property 1: Loop must always terminate
                $this->assertIsArray($result, "Loop must terminate and return result for {$contentData['scenario']} content");
                $this->assertLessThan(30, $executionTime, 
                    "Loop must terminate within 30 seconds for {$contentData['scenario']} content, took {$executionTime} seconds");
                
                // Property 2: Termination must respect maximum iterations
                $progressData = $result['progressData'];
                $this->assertLessThanOrEqual($maxIterations, $progressData['totalIterations'], 
                    "Must not exceed max iterations ({$maxIterations}) for {$contentData['scenario']} content, got {$progressData['totalIterations']}");
                
                // Property 3: Termination reason must be valid and consistent
                $terminationReason = $progressData['terminationReason'];
                $validReasons = ['compliance_achieved', 'max_iterations_reached', 'stagnation_detected', 'insufficient_improvement', 'initial_compliance', 'critical_error'];
                $this->assertContains($terminationReason, $validReasons, 
                    "Termination reason '{$terminationReason}' must be valid for {$contentData['scenario']} content");
                
                // Property 4: Max iterations termination should be exact
                if ($terminationReason === 'max_iterations_reached') {
                    $this->assertEquals($maxIterations, $progressData['totalIterations'], 
                        "Max iterations termination should use exactly {$maxIterations} iterations for {$contentData['scenario']} content");
                }
                
                // Property 5: Compliance termination should achieve 100%
                if ($terminationReason === 'compliance_achieved') {
                    $this->assertEquals(100.0, $progressData['finalScore'], 
                        "Compliance termination should achieve 100% score for {$contentData['scenario']} content");
                    $this->assertTrue($progressData['complianceAchieved'], 
                        "Compliance flag should be true for {$contentData['scenario']} content");
                }
                
                // Property 6: Early termination should work when enabled
                if ($enableEarlyTermination && in_array($terminationReason, ['stagnation_detected', 'insufficient_improvement'])) {
                    $this->assertLessThan($maxIterations, $progressData['totalIterations'], 
                        "Early termination should stop before max iterations for {$contentData['scenario']} content");
                }
                
                // Property 7: Stagnation detection should be accurate
                if ($terminationReason === 'stagnation_detected' && count($progressData['iterations']) > $stagnationThreshold + 1) {
                    // Check that recent iterations show stagnation
                    $recentScores = array_slice(array_column($progressData['iterations'], 'score'), -($stagnationThreshold + 1));
                    $improvements = [];
                    for ($i = 1; $i < count($recentScores); $i++) {
                        $improvements[] = $recentScores[$i] - $recentScores[$i-1];
                    }
                    
                    // Should have minimal or no improvements in recent iterations
                    $significantImprovements = array_filter($improvements, function($imp) use ($minImprovement) {
                        return $imp >= $minImprovement;
                    });
                    
                    $this->assertLessThanOrEqual(1, count($significantImprovements), 
                        "Stagnation detection should identify lack of significant improvements for {$contentData['scenario']} content");
                }
                
                // Property 8: Initial compliance should be detected correctly
                if ($terminationReason === 'initial_compliance') {
                    $this->assertEquals(0, $progressData['totalIterations'], 
                        "Initial compliance should result in 0 optimization iterations for {$contentData['scenario']} content");
                    $this->assertGreaterThanOrEqual(100.0, $progressData['finalScore'], 
                        "Initial compliance should have high score for {$contentData['scenario']} content");
                }
                
                // Property 9: Progress tracking should be complete
                $iterations = $progressData['iterations'];
                $this->assertNotEmpty($iterations, "Should track at least baseline iteration for {$contentData['scenario']} content");
                
                // Should have baseline + optimization iterations
                $expectedIterationCount = $progressData['totalIterations'] + 1; // +1 for baseline
                $this->assertCount($expectedIterationCount, $iterations, 
                    "Should track all iterations including baseline for {$contentData['scenario']} content");
                
                // Property 10: Error handling should not cause infinite loops
                $this->assertIsArray($result['errorLog'], "Error log should be tracked for {$contentData['scenario']} content");
                
                // If there are errors, they shouldn't prevent termination
                if (!empty($result['errorLog'])) {
                    $criticalErrors = array_filter($result['errorLog'], function($error) {
                        return $error['level'] === 'error';
                    });
                    
                    // Even with errors, should terminate gracefully
                    $this->assertNotEquals('infinite_loop', $terminationReason, 
                        "Should not get stuck in infinite loop even with errors for {$contentData['scenario']} content");
                }
                
                // Property 11: Configuration should be respected
                $resultConfig = $result['config'];
                $this->assertEquals($maxIterations, $resultConfig['maxIterations'], 
                    "Configuration should be preserved for {$contentData['scenario']} content");
                $this->assertEquals($enableEarlyTermination, $resultConfig['enableEarlyTermination'], 
                    "Early termination setting should be preserved for {$contentData['scenario']} content");
                $this->assertEquals($stagnationThreshold, $resultConfig['stagnationThreshold'], 
                    "Stagnation threshold should be preserved for {$contentData['scenario']} content");
                
                // Property 12: Performance metrics should be reasonable
                $performanceMetrics = $progressData['performanceMetrics'];
                $this->assertGreaterThan(0, $performanceMetrics['totalDuration'], 
                    "Should track positive duration for {$contentData['scenario']} content");
                $this->assertGreaterThanOrEqual(0, $performanceMetrics['finalComplianceScore'], 
                    "Final compliance score should be >= 0 for {$contentData['scenario']} content");
                $this->assertLessThanOrEqual(100, $performanceMetrics['finalComplianceScore'], 
                    "Final compliance score should be <= 100 for {$contentData['scenario']} content");
            }
        });
    }
}
