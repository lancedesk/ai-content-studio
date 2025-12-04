<?php
/**
 * Integration Tests for Complete SEO Validation Workflow
 *
 * Tests end-to-end content generation with SEO validation pipeline
 * to verify all validation steps work together correctly.
 *
 * @package AI_Content_Studio
 * @subpackage Tests
 */

require_once dirname(__DIR__) . '/seo/class-seo-validation-pipeline.php';
require_once dirname(__DIR__) . '/seo/class-enhanced-prompt-engine.php';

class TestSEOIntegrationWorkflow extends PHPUnit\Framework\TestCase {
    
    /**
     * @var SEOValidationPipeline
     */
    private $pipeline;
    
    /**
     * @var ACS_Content_Generator
     */
    private $generator;
    
    /**
     * @var EnhancedPromptEngine
     */
    private $promptEngine;
    
    /**
     * Set up test environment
     */
    protected function setUp(): void {
        parent::setUp();
        
        // Initialize pipeline with test configuration
        $this->pipeline = new SEOValidationPipeline([
            'minMetaDescLength' => 120,
            'maxMetaDescLength' => 156,
            'minKeywordDensity' => 0.5,
            'maxKeywordDensity' => 2.5,
            'maxPassiveVoice' => 10.0,
            'maxLongSentences' => 25.0,
            'minTransitionWords' => 30.0,
            'maxTitleLength' => 66,
            'maxSubheadingKeywordUsage' => 75.0,
            'requireImages' => true,
            'requireKeywordInAltText' => true,
            'autoCorrection' => true,
            'maxRetryAttempts' => 3
        ]);
        
        $this->generator = new ACS_Content_Generator();
        
        // Create SEO prompt configuration for the enhanced prompt engine
        $promptConfig = new SEOPromptConfiguration();
        $promptConfig->setFocusKeyword('test keyword');
        $promptConfig->setTargetWordCount(800);
        $this->promptEngine = new EnhancedPromptEngine($promptConfig);
        
        // Clear test data
        global $acs_test_posts, $acs_test_post_meta, $acs_test_options;
        $acs_test_posts = [];
        $acs_test_post_meta = [];
        $acs_test_options = [];
        
        // Set up test provider configuration
        $acs_test_options['acs_settings'] = [
            'providers' => [
                'mock' => [
                    'enabled' => true,
                    'api_key' => 'test-key'
                ]
            ],
            'default_provider' => 'mock'
        ];
    }
    
    /**
     * Test complete end-to-end content generation with SEO validation
     * Requirements: 6.1, 6.2, 6.4
     */
    public function testCompleteContentGenerationWorkflow() {
        // Arrange: Set up test content with various SEO issues
        $testContent = [
            'title' => 'This is a very long title that exceeds the maximum character limit for SEO optimization and should be corrected',
            'content' => '<p>This content was written in passive voice. The article was created by the author. Many sentences are being written passively.</p><p>This is a very long sentence that contains way too many words and should be split into shorter sentences for better readability and user experience according to SEO best practices.</p>',
            'meta_description' => 'Short meta', // Too short
            'slug' => 'test-slug',
            'excerpt' => 'Test excerpt',
            'focus_keyword' => 'SEO optimization',
            'image_prompts' => [], // Missing images
            'internal_links' => ['link1', 'link2']
        ];
        
        $focusKeyword = 'SEO optimization';
        $secondaryKeywords = ['content marketing', 'digital marketing'];
        
        // Act: Run complete validation and correction pipeline
        $result = $this->pipeline->validateAndCorrect($testContent, $focusKeyword, $secondaryKeywords);
        
        // Assert: Verify validation completed successfully
        $this->assertInstanceOf('SEOValidationResult', $result);
        $this->assertNotEmpty($result->correctedContent);
        
        // Verify corrections were made
        $this->assertNotEmpty($result->correctionsMade);
        $this->assertContains('meta_description', $result->correctionsMade);
        $this->assertContains('title', $result->correctionsMade);
        $this->assertContains('readability', $result->correctionsMade);
        $this->assertContains('images', $result->correctionsMade);
        
        // Verify corrected content meets SEO requirements
        $correctedContent = $result->correctedContent;
        
        // Meta description should be corrected
        $metaLength = strlen($correctedContent['meta_description']);
        $this->assertGreaterThanOrEqual(120, $metaLength);
        $this->assertLessThanOrEqual(156, $metaLength);
        $this->assertStringContainsString($focusKeyword, $correctedContent['meta_description']);
        
        // Title should be corrected
        $titleLength = strlen($correctedContent['title']);
        $this->assertLessThanOrEqual(66, $titleLength);
        $this->assertStringContainsString($focusKeyword, $correctedContent['title']);
        
        // Images should be added
        $this->assertNotEmpty($correctedContent['image_prompts']);
        $this->assertArrayHasKey('alt', $correctedContent['image_prompts'][0]);
        
        // Verify metrics are calculated
        $this->assertNotEmpty($result->metrics);
        $this->assertArrayHasKey('wordCount', $result->metrics);
        $this->assertArrayHasKey('keywordDensity', $result->metrics);
        
        // Verify overall score is calculated
        $this->assertIsFloat($result->overallScore);
        $this->assertGreaterThan(0, $result->overallScore);
    }
    
    /**
     * Test integration with enhanced prompt engine
     * Requirements: 6.1, 6.2
     */
    public function testPromptEngineIntegration() {
        // Arrange: Create SEO-enhanced prompt
        $config = [
            'targetWordCount' => 800,
            'focusKeyword' => 'content marketing',
            'secondaryKeywords' => ['digital marketing', 'SEO'],
            'minMetaDescLength' => 120,
            'maxMetaDescLength' => 156,
            'maxKeywordDensity' => 2.5,
            'maxPassiveVoice' => 10.0,
            'minTransitionWords' => 30.0
        ];
        
        // Act: Build enhanced prompt with SEO constraints
        $prompt = $this->promptEngine->buildSEOPrompt('content marketing strategies', $config);
        
        // Assert: Verify prompt contains SEO requirements
        $this->assertStringContainsString('meta description', $prompt);
        $this->assertStringContainsString('120-156 characters', $prompt);
        $this->assertStringContainsString('keyword density', $prompt);
        $this->assertStringContainsString('passive voice', $prompt);
        $this->assertStringContainsString('transition words', $prompt);
        $this->assertStringContainsString('content marketing', $prompt);
        
        // Verify fallback prompt generation
        $fallbackPrompt = $this->promptEngine->generateFallbackPrompts($config, ['meta_description_length']);
        $this->assertNotEmpty($fallbackPrompt);
        $this->assertStringContainsString('relaxed', $fallbackPrompt);
    }
    
    /**
     * Test validation pipeline with multiple retry attempts
     * Requirements: 6.2, 6.4
     */
    public function testValidationWithRetryMechanism() {
        // Arrange: Create content that requires multiple corrections
        $problematicContent = [
            'title' => 'Bad Title Without Keyword And Way Too Long For SEO Requirements',
            'content' => '<p>Content was written. Article was created. Everything was done passively. This sentence is extremely long and contains way too many words that make it difficult to read and understand for users and search engines alike.</p>',
            'meta_description' => 'Bad', // Way too short
            'slug' => 'test-slug',
            'excerpt' => 'Test excerpt',
            'focus_keyword' => 'test keyword',
            'image_prompts' => [],
            'internal_links' => []
        ];
        
        $focusKeyword = 'test keyword';
        
        // Act: Run validation with auto-correction enabled
        $result = $this->pipeline->validateAndCorrect($problematicContent, $focusKeyword);
        
        // Assert: Verify multiple corrections were applied
        $this->assertGreaterThan(2, count($result->correctionsMade));
        
        // Verify final validation passes
        $this->assertTrue($result->isValid || count($result->errors) < count($result->warnings));
        
        // Verify error handling logged issues appropriately
        $errorHandler = $this->pipeline->getErrorHandler();
        $this->assertInstanceOf('SEOErrorHandler', $errorHandler);
    }
    
    /**
     * Test complete workflow with real-world content scenario
     * Requirements: 6.1, 6.2, 6.4
     */
    public function testRealWorldContentScenario() {
        // Arrange: Create realistic blog post content
        $blogContent = [
            'title' => 'How to Improve Your Website SEO',
            'content' => '<h2>Introduction to SEO</h2><p>Search engine optimization is important for websites. Many websites are optimized by professionals. The process was developed over many years.</p><h2>SEO Techniques</h2><p>There are many techniques that can be used. Keywords should be included in content. Meta descriptions are written by content creators.</p><h2>Conclusion</h2><p>SEO is essential for online success. Websites are ranked by search engines based on various factors.</p>',
            'meta_description' => 'Learn about SEO techniques for your website and improve your search engine rankings with these proven strategies.',
            'slug' => 'improve-website-seo',
            'excerpt' => 'Comprehensive guide to SEO improvement',
            'focus_keyword' => 'website SEO',
            'image_prompts' => [
                ['alt' => 'SEO dashboard screenshot']
            ],
            'internal_links' => ['seo-basics', 'keyword-research']
        ];
        
        $focusKeyword = 'website SEO';
        $secondaryKeywords = ['search engine optimization', 'SEO techniques'];
        
        // Act: Process through complete validation pipeline
        $result = $this->pipeline->validateAndCorrect($blogContent, $focusKeyword, $secondaryKeywords);
        
        // Assert: Verify comprehensive validation
        $this->assertInstanceOf('SEOValidationResult', $result);
        
        // Check that passive voice was corrected
        $correctedContent = $result->correctedContent['content'];
        $passiveCount = substr_count(strtolower($correctedContent), ' was ') + 
                       substr_count(strtolower($correctedContent), ' were ') +
                       substr_count(strtolower($correctedContent), ' been ');
        $originalPassiveCount = substr_count(strtolower($blogContent['content']), ' was ') + 
                               substr_count(strtolower($blogContent['content']), ' were ') +
                               substr_count(strtolower($blogContent['content']), ' been ');
        
        // Passive voice should be reduced
        $this->assertLessThanOrEqual($originalPassiveCount, $passiveCount);
        
        // Verify keyword density is within acceptable range
        $contentText = strip_tags($correctedContent);
        $wordCount = str_word_count($contentText);
        $keywordCount = substr_count(strtolower($contentText), strtolower($focusKeyword));
        $density = ($keywordCount / $wordCount) * 100;
        
        $this->assertGreaterThanOrEqual(0.5, $density);
        $this->assertLessThanOrEqual(2.5, $density);
        
        // Verify meta description includes keyword
        $this->assertStringContainsString($focusKeyword, $result->correctedContent['meta_description']);
        
        // Verify images have proper alt text
        $this->assertNotEmpty($result->correctedContent['image_prompts']);
        foreach ($result->correctedContent['image_prompts'] as $image) {
            $this->assertArrayHasKey('alt', $image);
            $this->assertNotEmpty($image['alt']);
        }
    }
    
    /**
     * Test error handling in validation pipeline
     * Requirements: 6.2, 6.4
     */
    public function testValidationErrorHandling() {
        // Arrange: Create content that will cause validation errors
        $invalidContent = [
            'title' => '', // Empty title
            'content' => '', // Empty content
            'meta_description' => '',
            'slug' => '',
            'excerpt' => '',
            'focus_keyword' => '',
            'image_prompts' => [],
            'internal_links' => []
        ];
        
        $focusKeyword = 'test keyword';
        
        // Act: Run validation on invalid content
        $result = $this->pipeline->validateAndCorrect($invalidContent, $focusKeyword);
        
        // Assert: Verify errors are properly handled
        $this->assertInstanceOf('SEOValidationResult', $result);
        $this->assertFalse($result->isValid);
        $this->assertNotEmpty($result->errors);
        
        // Verify error categories are properly identified
        $errorCategories = array_keys($result->errors);
        $this->assertContains('meta_description', $errorCategories);
        $this->assertContains('title', $errorCategories);
        
        // Verify corrections were attempted even with invalid input
        $this->assertNotEmpty($result->correctionsMade);
    }
    
    /**
     * Test adaptive rule updates
     * Requirements: 6.4
     */
    public function testAdaptiveRuleUpdates() {
        // Arrange: Set up initial configuration
        $initialConfig = $this->pipeline->getConfig();
        
        // Act: Update adaptive rules
        $newRules = [
            'maxKeywordDensity' => 3.0, // Increase from 2.5
            'minTransitionWords' => 25.0 // Decrease from 30.0
        ];
        
        $success = $this->pipeline->updateAdaptiveRules($newRules);
        
        // Assert: Verify rules were updated
        $this->assertTrue($success);
        
        $updatedConfig = $this->pipeline->getConfig();
        $this->assertEquals(3.0, $updatedConfig['maxKeywordDensity']);
        $this->assertEquals(25.0, $updatedConfig['minTransitionWords']);
        
        // Verify other settings remain unchanged
        $this->assertEquals($initialConfig['minMetaDescLength'], $updatedConfig['minMetaDescLength']);
        $this->assertEquals($initialConfig['maxMetaDescLength'], $updatedConfig['maxMetaDescLength']);
    }
    
    /**
     * Test manual override functionality
     * Requirements: 6.2, 6.4
     */
    public function testManualOverrideFunctionality() {
        // Arrange: Create content with persistent validation issue
        $contentWithIssue = [
            'title' => 'Special Brand Name Title That Cannot Be Changed',
            'content' => '<p>This content discusses the Special Brand Name and its unique features.</p>',
            'meta_description' => 'Learn about Special Brand Name and its innovative solutions for modern businesses.',
            'slug' => 'special-brand-name',
            'excerpt' => 'Special Brand Name overview',
            'focus_keyword' => 'different keyword',
            'image_prompts' => [['alt' => 'Special Brand Name logo']],
            'internal_links' => []
        ];
        
        // Act: Add manual override for title keyword requirement
        $overrideSuccess = $this->pipeline->addManualOverride(
            'title',
            'Title should contain focus keyword: different keyword',
            ['skip_validation' => true, 'reason' => 'Brand name requirement']
        );
        
        // Run validation
        $result = $this->pipeline->validateAndCorrect($contentWithIssue, 'different keyword');
        
        // Assert: Verify override was applied
        $this->assertTrue($overrideSuccess);
        
        // Title should not be in corrections made due to override
        $this->assertNotContains('title', $result->correctionsMade);
        
        // But other validations should still work
        $this->assertNotEmpty($result->correctedContent);
    }
    
    /**
     * Test validation statistics and monitoring
     * Requirements: 6.4
     */
    public function testValidationStatistics() {
        // Arrange: Run multiple validations to generate statistics
        $testContents = [
            [
                'title' => 'First Test Article',
                'content' => '<p>Content for first article.</p>',
                'meta_description' => 'Short',
                'focus_keyword' => 'test'
            ],
            [
                'title' => 'Second Test Article With Very Long Title That Exceeds Limits',
                'content' => '<p>Content for second article.</p>',
                'meta_description' => 'This is a proper length meta description that should pass validation checks.',
                'focus_keyword' => 'test'
            ]
        ];
        
        // Act: Process multiple content pieces
        foreach ($testContents as $content) {
            $this->pipeline->validateAndCorrect($content, $content['focus_keyword']);
        }
        
        // Get error statistics
        $stats = $this->pipeline->getErrorStats();
        
        // Assert: Verify statistics are collected
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_validations', $stats);
        $this->assertArrayHasKey('error_counts', $stats);
        
        // Verify specific error types are tracked
        $this->assertGreaterThan(0, $stats['total_validations']);
    }
    
    /**
     * Clean up after tests
     */
    protected function tearDown(): void {
        // Clear test data
        global $acs_test_posts, $acs_test_post_meta, $acs_test_options;
        $acs_test_posts = [];
        $acs_test_post_meta = [];
        $acs_test_options = [];
        
        parent::tearDown();
    }
}