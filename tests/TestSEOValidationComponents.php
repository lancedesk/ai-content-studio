<?php
/**
 * Unit Tests for Individual SEO Validation Components
 *
 * Tests individual validation components to ensure each works correctly
 * in isolation before integration testing.
 *
 * @package AI_Content_Studio
 * @subpackage Tests
 */

class TestSEOValidationComponents extends PHPUnit\Framework\TestCase {
    
    /**
     * Test meta description validator
     * Requirements: 1.1
     */
    public function testMetaDescriptionValidator() {
        $validator = new MetaDescriptionValidator();
        
        // Test valid length
        $validMeta = str_repeat('a', 140); // 140 characters
        $result = $validator->validateLength($validMeta);
        $this->assertTrue($result['isValid']);
        
        // Test too short
        $shortMeta = str_repeat('a', 100); // 100 characters
        $result = $validator->validateLength($shortMeta);
        $this->assertFalse($result['isValid']);
        $this->assertEquals('too_short', $result['type']);
        
        // Test too long
        $longMeta = str_repeat('a', 200); // 200 characters
        $result = $validator->validateLength($longMeta);
        $this->assertFalse($result['isValid']);
        $this->assertEquals('too_long', $result['type']);
        
        // Test keyword inclusion
        $metaWithKeyword = 'This meta description contains the SEO keyword for testing purposes.';
        $result = $validator->validateKeywordInclusion($metaWithKeyword, 'SEO keyword');
        $this->assertTrue($result['isValid']);
        
        $metaWithoutKeyword = 'This meta description does not contain the target phrase.';
        $result = $validator->validateKeywordInclusion($metaWithoutKeyword, 'SEO keyword');
        $this->assertFalse($result['isValid']);
        
        // Test case insensitive keyword matching
        $metaWithKeywordCaseInsensitive = 'This meta description contains the seo KEYWORD for testing.';
        $result = $validator->validateKeywordInclusion($metaWithKeywordCaseInsensitive, 'SEO keyword');
        $this->assertTrue($result['isValid']);
    }
    
    /**
     * Test meta description corrector
     * Requirements: 1.1
     */
    public function testMetaDescriptionCorrector() {
        $corrector = new MetaDescriptionCorrector();
        
        // Test auto-correction for short meta description
        $shortMeta = 'Short meta description.';
        $result = $corrector->autoCorrect($shortMeta, 'content marketing');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('corrected', $result);
        $this->assertArrayHasKey('corrections', $result);
        
        $corrected = $result['corrected'];
        $this->assertGreaterThanOrEqual(120, strlen($corrected));
        $this->assertLessThanOrEqual(156, strlen($corrected));
        $this->assertStringContainsString('content marketing', $corrected);
        
        // Test auto-correction for long meta description
        $longMeta = str_repeat('This is a very long meta description that exceeds the maximum character limit and needs to be trimmed down. ', 3);
        $result = $corrector->autoCorrect($longMeta, 'SEO optimization');
        
        $corrected = $result['corrected'];
        $this->assertLessThanOrEqual(156, strlen($corrected));
        $this->assertGreaterThanOrEqual(120, strlen($corrected));
        $this->assertStringContainsString('SEO optimization', $corrected);
        
        // Test keyword inclusion for meta without keyword
        $metaWithoutKeyword = 'This is a meta description that needs keyword inclusion for better performance and search engine visibility.';
        $result = $corrector->autoCorrect($metaWithoutKeyword, 'digital marketing');
        
        $corrected = $result['corrected'];
        $this->assertStringContainsString('digital marketing', $corrected);
        $this->assertNotEmpty($result['corrections']);
    }
    
    /**
     * Test keyword density calculator
     * Requirements: 2.1
     */
    public function testKeywordDensityCalculator() {
        $calculator = new Keyword_Density_Calculator();
        
        // Test basic density calculation
        $content = 'This is content about SEO optimization. SEO optimization is important for websites. Good SEO optimization helps rankings.';
        $result = $calculator->calculate_density($content, 'SEO optimization', []);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('overall_density', $result);
        $this->assertArrayHasKey('keyword_count', $result);
        $this->assertArrayHasKey('total_words', $result);
        
        // Verify calculation accuracy
        $expectedCount = 3; // "SEO optimization" appears 3 times
        $totalWords = str_word_count($content);
        $expectedDensity = ($expectedCount / $totalWords) * 100;
        
        $this->assertEquals($expectedCount, $result['keyword_count']);
        $this->assertEquals($totalWords, $result['total_words']);
        $this->assertEqualsWithDelta($expectedDensity, $result['overall_density'], 0.1);
        
        // Test with secondary keywords
        $contentWithSecondary = 'Content marketing and SEO optimization work together. Digital marketing includes SEO optimization strategies.';
        $result = $calculator->calculate_density($contentWithSecondary, 'SEO optimization', ['content marketing', 'digital marketing']);
        
        $this->assertArrayHasKey('secondary_densities', $result);
        $this->assertArrayHasKey('content marketing', $result['secondary_densities']);
        $this->assertArrayHasKey('digital marketing', $result['secondary_densities']);
    }
    
    /**
     * Test keyword density optimizer
     * Requirements: 2.1
     */
    public function testKeywordDensityOptimizer() {
        $optimizer = new Keyword_Density_Optimizer();
        
        // Test content with low keyword density
        $lowDensityContent = 'This is a long article about various topics. It discusses many different subjects without focusing on any particular theme. The content covers multiple areas of interest.';
        $result = $optimizer->optimize_content($lowDensityContent, 'SEO optimization', [], 1.5, 0.5, 2.5);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('content', $result);
        $this->assertArrayHasKey('density_after', $result);
        $this->assertArrayHasKey('changes_made', $result);
        
        // Verify keyword was added
        $this->assertStringContainsString('SEO optimization', $result['content']);
        $this->assertGreaterThan(0, count($result['changes_made']));
        
        // Test content with high keyword density
        $highDensityContent = 'SEO optimization is crucial. SEO optimization helps websites. SEO optimization improves rankings. SEO optimization drives traffic. SEO optimization is essential for SEO optimization success.';
        $result = $optimizer->optimize_content($highDensityContent, 'SEO optimization', [], 1.5, 0.5, 2.5);
        
        // Verify keyword density was reduced
        $finalDensity = $result['density_after'];
        $this->assertLessThanOrEqual(2.5, $finalDensity);
        $this->assertGreaterThanOrEqual(0.5, $finalDensity);
    }
    
    /**
     * Test passive voice analyzer
     * Requirements: 3.1
     */
    public function testPassiveVoiceAnalyzer() {
        $analyzer = new PassiveVoiceAnalyzer();
        
        // Test content with passive voice
        $passiveContent = 'The article was written by the author. The website was designed by professionals. The content was created yesterday.';
        $result = $analyzer->analyze($passiveContent);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('passive_sentences', $result);
        $this->assertArrayHasKey('total_sentences', $result);
        $this->assertArrayHasKey('passive_percentage', $result);
        
        // Should detect all 3 passive sentences
        $this->assertEquals(3, $result['passive_sentences']);
        $this->assertEquals(3, $result['total_sentences']);
        $this->assertEquals(100.0, $result['passive_percentage']);
        
        // Test content with active voice
        $activeContent = 'The author wrote the article. Professionals designed the website. We created the content yesterday.';
        $result = $analyzer->analyze($activeContent);
        
        $this->assertEquals(0, $result['passive_sentences']);
        $this->assertEquals(0.0, $result['passive_percentage']);
        
        // Test mixed content
        $mixedContent = 'The author wrote the article. The website was designed by professionals. We created great content.';
        $result = $analyzer->analyze($mixedContent);
        
        $this->assertEquals(1, $result['passive_sentences']);
        $this->assertEquals(3, $result['total_sentences']);
        $this->assertEqualsWithDelta(33.33, $result['passive_percentage'], 0.1);
    }
    
    /**
     * Test sentence length analyzer
     * Requirements: 3.1
     */
    public function testSentenceLengthAnalyzer() {
        $analyzer = new SentenceLengthAnalyzer();
        
        // Test content with long sentences
        $longSentenceContent = 'This is a very long sentence that contains many words and clauses that make it difficult to read and understand for most users who visit the website. Short sentence. Another extremely long sentence with multiple clauses, subclauses, and additional information that extends the length significantly beyond the recommended limit for optimal readability.';
        
        $result = $analyzer->analyze($longSentenceContent);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('long_sentences', $result);
        $this->assertArrayHasKey('total_sentences', $result);
        $this->assertArrayHasKey('long_sentence_percentage', $result);
        $this->assertArrayHasKey('average_length', $result);
        
        // Should detect 2 long sentences out of 3 total
        $this->assertEquals(2, $result['long_sentences']);
        $this->assertEquals(3, $result['total_sentences']);
        $this->assertEqualsWithDelta(66.67, $result['long_sentence_percentage'], 0.1);
        
        // Test content with short sentences
        $shortSentenceContent = 'Short sentence. Another short one. Very brief. Quick statement.';
        $result = $analyzer->analyze($shortSentenceContent);
        
        $this->assertEquals(0, $result['long_sentences']);
        $this->assertEquals(0.0, $result['long_sentence_percentage']);
        $this->assertLessThan(10, $result['average_length']);
    }
    
    /**
     * Test transition word analyzer
     * Requirements: 3.1
     */
    public function testTransitionWordAnalyzer() {
        $analyzer = new TransitionWordAnalyzer();
        
        // Test content with transition words
        $contentWithTransitions = 'First, we need to understand SEO. However, it can be complex. Therefore, we should start with basics. Additionally, practice is important.';
        $result = $analyzer->analyze($contentWithTransitions);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('sentences_with_transitions', $result);
        $this->assertArrayHasKey('total_sentences', $result);
        $this->assertArrayHasKey('transition_percentage', $result);
        $this->assertArrayHasKey('transition_words_found', $result);
        
        // Should detect transition words in all 4 sentences
        $this->assertEquals(4, $result['sentences_with_transitions']);
        $this->assertEquals(4, $result['total_sentences']);
        $this->assertEquals(100.0, $result['transition_percentage']);
        
        // Verify specific transition words were found
        $transitionWords = $result['transition_words_found'];
        $this->assertContains('first', array_map('strtolower', $transitionWords));
        $this->assertContains('however', array_map('strtolower', $transitionWords));
        $this->assertContains('therefore', array_map('strtolower', $transitionWords));
        $this->assertContains('additionally', array_map('strtolower', $transitionWords));
        
        // Test content without transition words
        $contentWithoutTransitions = 'SEO is important. Websites need optimization. Rankings matter for traffic. Users prefer fast sites.';
        $result = $analyzer->analyze($contentWithoutTransitions);
        
        $this->assertEquals(0, $result['sentences_with_transitions']);
        $this->assertEquals(0.0, $result['transition_percentage']);
        $this->assertEmpty($result['transition_words_found']);
    }
    
    /**
     * Test readability corrector
     * Requirements: 3.1
     */
    public function testReadabilityCorrector() {
        $corrector = new ReadabilityCorrector();
        
        // Test comprehensive readability correction
        $problematicContent = 'The article was written by the author. The website was designed by professionals. This is a very long sentence that contains many words and clauses that make it difficult to read and understand for most users who visit the website and want to consume the content quickly.';
        $result = $corrector->correctReadability($problematicContent);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('corrected_content', $result);
        $this->assertArrayHasKey('changes_made', $result);
        
        $corrected = $result['corrected_content'];
        
        // Should have fewer passive voice instances
        $originalPassiveCount = substr_count(strtolower($problematicContent), ' was ');
        $correctedPassiveCount = substr_count(strtolower($corrected), ' was ');
        $this->assertLessThanOrEqual($originalPassiveCount, $correctedPassiveCount);
        
        // Test individual correction methods
        $passiveContent = 'The article was written by the author. The website was designed by professionals.';
        $passiveResult = $corrector->correctPassiveVoice($passiveContent);
        
        $this->assertIsArray($passiveResult);
        $this->assertArrayHasKey('corrected_content', $passiveResult);
        
        // Test sentence length correction
        $longSentenceContent = 'This is a very long sentence that contains many words and clauses that make it difficult to read and understand for most users who visit the website and want to consume the content quickly.';
        $lengthResult = $corrector->correctSentenceLength($longSentenceContent);
        
        $this->assertIsArray($lengthResult);
        $this->assertArrayHasKey('corrected_content', $lengthResult);
        
        // Should be split into multiple sentences
        $originalSentenceCount = substr_count($longSentenceContent, '.');
        $correctedSentenceCount = substr_count($lengthResult['corrected_content'], '.');
        $this->assertGreaterThanOrEqual($originalSentenceCount, $correctedSentenceCount);
        
        // Test transition word addition
        $contentWithoutTransitions = 'SEO is important. Websites need optimization. Rankings matter for traffic.';
        $transitionResult = $corrector->addTransitionWords($contentWithoutTransitions);
        
        $this->assertIsArray($transitionResult);
        $this->assertArrayHasKey('corrected_content', $transitionResult);
    }
    
    /**
     * Test title uniqueness validator
     * Requirements: 4.1
     */
    public function testTitleUniquenessValidator() {
        $validator = new Title_Uniqueness_Validator();
        
        // Test unique title
        $uniqueTitle = 'Completely Unique Title That Has Never Been Used Before';
        $result = $validator->validate_title_uniqueness($uniqueTitle);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('is_unique', $result);
        $this->assertArrayHasKey('similarity_score', $result);
        $this->assertArrayHasKey('similar_titles', $result);
        
        // Should be unique since it's not in mock data
        $this->assertTrue($result['is_unique']);
        $this->assertLessThan(0.8, $result['similarity_score']);
        
        // Test similar title to existing mock data
        $similarTitle = 'How to Optimize SEO for WordPress Sites'; // Similar to mock data
        $result = $validator->validate_title_uniqueness($similarTitle);
        
        // Should detect similarity
        $this->assertFalse($result['is_unique']);
        $this->assertGreaterThan(0.8, $result['similarity_score']);
        $this->assertNotEmpty($result['similar_titles']);
    }
    
    /**
     * Test title optimization engine
     * Requirements: 4.1
     */
    public function testTitleOptimizationEngine() {
        $optimizer = new Title_Optimization_Engine();
        
        // Test optimized title generation
        $params = [
            'focus_keyword' => 'SEO optimization',
            'topic' => 'Website SEO strategies',
            'max_length' => 66,
            'content_context' => 'This article covers various SEO techniques for improving website rankings.'
        ];
        
        $result = $optimizer->generate_optimized_title($params);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('optimization_details', $result);
        
        $optimizedTitle = $result['title'];
        $this->assertLessThanOrEqual(66, strlen($optimizedTitle));
        $this->assertStringContainsString('SEO optimization', $optimizedTitle);
        
        // Test content variations
        $variationParams = [
            'base_topic' => 'Content marketing strategies',
            'focus_keyword' => 'content marketing',
            'variation_count' => 3
        ];
        
        $variations = $optimizer->generate_content_variations($variationParams);
        
        $this->assertIsArray($variations);
        $this->assertArrayHasKey('variations', $variations);
        $this->assertCount(3, $variations['variations']);
        
        // Each variation should be unique
        $titles = array_column($variations['variations'], 'title');
        $this->assertEquals(count($titles), count(array_unique($titles)));
        
        // Test max title length setting
        $this->assertTrue($optimizer->set_max_title_length(60));
        $this->assertEquals(60, $optimizer->get_max_title_length());
        
        // Test with invalid length
        $this->assertFalse($optimizer->set_max_title_length(0));
        $this->assertFalse($optimizer->set_max_title_length(300));
    }
    
    /**
     * Test image prompt generator
     * Requirements: 5.1
     */
    public function testImagePromptGenerator() {
        $generator = new ImagePromptGenerator();
        
        // Test basic image prompt generation
        $topic = 'SEO Optimization Strategies for 2024';
        $keyword = 'SEO optimization';
        $prompt = $generator->generateImagePrompt($topic, $keyword);
        
        $this->assertIsArray($prompt);
        $this->assertArrayHasKey('prompt', $prompt);
        $this->assertArrayHasKey('alt_text', $prompt);
        $this->assertArrayHasKey('context', $prompt);
        
        // Verify keyword inclusion in alt text
        $this->assertStringContainsString($keyword, $prompt['alt_text']);
        
        // Test multiple image generation
        $prompts = $generator->generateVariedImagePrompts($topic, $keyword, [], 3);
        
        $this->assertIsArray($prompts);
        $this->assertCount(3, $prompts);
        
        // Each prompt should be unique
        $altTexts = array_column($prompts, 'alt_text');
        $this->assertEquals(count($altTexts), count(array_unique($altTexts)));
        
        // All should contain keyword
        foreach ($prompts as $prompt) {
            $this->assertStringContainsString($keyword, $prompt['alt_text']);
        }
        
        // Test alt text generation
        $altText = $generator->generateAltText('SEO dashboard screenshot', $keyword);
        
        $this->assertIsString($altText);
        $this->assertStringContainsString($keyword, $altText);
        $this->assertLessThanOrEqual(125, strlen($altText)); // Standard alt text length limit
    }
    
    /**
     * Test alt text accessibility optimizer
     * Requirements: 5.1
     */
    public function testAltTextAccessibilityOptimizer() {
        $optimizer = new AltTextAccessibilityOptimizer();
        
        // Test basic alt text optimization
        $basicAlt = 'image';
        $result = $optimizer->optimizeAltText($basicAlt, 'content marketing');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('optimized_alt_text', $result);
        $this->assertArrayHasKey('accessibility_score', $result);
        
        $optimized = $result['optimized_alt_text'];
        $this->assertStringContainsString('content marketing', $optimized);
        $this->assertGreaterThan(strlen($basicAlt), strlen($optimized));
        
        // Test alt text that's already good
        $goodAlt = 'Content marketing strategy diagram showing customer journey';
        $result = $optimizer->optimizeAltText($goodAlt, 'content marketing');
        
        $optimized = $result['optimized_alt_text'];
        $this->assertStringContainsString('content marketing', $optimized);
        
        // Test accessibility validation
        $validationResult = $optimizer->validateAccessibility($optimized);
        
        $this->assertIsArray($validationResult);
        $this->assertArrayHasKey('is_accessible', $validationResult);
        $this->assertArrayHasKey('issues', $validationResult);
        $this->assertArrayHasKey('warnings', $validationResult);
        
        $this->assertTrue($validationResult['is_accessible']);
        $this->assertEmpty($validationResult['issues']);
        
        // Test inaccessible alt text
        $badAlt = 'img1.jpg';
        $validationResult = $optimizer->validateAccessibility($badAlt);
        
        $this->assertFalse($validationResult['is_accessible']);
        $this->assertNotEmpty($validationResult['issues']);
        
        // Test generating accessible variations
        $variations = $optimizer->generateAccessibleVariations('SEO dashboard', 'SEO optimization', [], 3);
        
        $this->assertIsArray($variations);
        $this->assertCount(3, $variations);
        
        // Each variation should contain the keyword
        foreach ($variations as $variation) {
            $this->assertArrayHasKey('alt_text', $variation);
            $this->assertStringContainsString('SEO optimization', $variation['alt_text']);
        }
    }
}