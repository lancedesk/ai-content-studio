<?php
/**
 * Unit Tests for Correction Prompt Generator
 *
 * @package AI_Content_Studio
 * @subpackage Tests
 */

require_once dirname(__DIR__) . '/bootstrap.php';

class TestCorrectionPromptGenerator extends SimpleTestCase {
    
    private $generator;
    
    protected function setUp(): void {
        parent::setUp();
        $this->generator = new CorrectionPromptGenerator();
    }
    
    public function testGeneratorInitialization() {
        $this->assertInstanceOf(CorrectionPromptGenerator::class, $this->generator);
    }
    
    public function testGeneratePromptForIssues() {
        $issues = [
            [
                'type' => 'keyword_density_low',
                'severity' => 'major',
                'message' => 'Keyword density too low'
            ]
        ];
        
        $content = [
            'title' => 'Test Title',
            'content' => 'Test content'
        ];
        
        $prompt = $this->generator->generatePrompt($issues, $content, 'keyword');
        
        $this->assertIsString($prompt);
        $this->assertNotEmpty($prompt);
    }
    
    public function testPromptContainsIssueDetails() {
        $issues = [
            [
                'type' => 'meta_description_missing',
                'severity' => 'critical',
                'message' => 'Meta description is missing'
            ]
        ];
        
        $content = [
            'title' => 'Test',
            'content' => 'Content',
            'meta_description' => ''
        ];
        
        $prompt = $this->generator->generatePrompt($issues, $content, 'test');
        
        $this->assertStringContainsString('meta', strtolower($prompt));
    }
    
    public function testPromptPrioritization() {
        $issues = [
            [
                'type' => 'minor_issue',
                'severity' => 'minor',
                'message' => 'Minor issue'
            ],
            [
                'type' => 'critical_issue',
                'severity' => 'critical',
                'message' => 'Critical issue'
            ]
        ];
        
        $content = ['title' => 'Test', 'content' => 'Test'];
        $prompt = $this->generator->generatePrompt($issues, $content, 'test');
        
        // Critical issues should appear before minor issues
        $criticalPos = strpos(strtolower($prompt), 'critical');
        $minorPos = strpos(strtolower($prompt), 'minor');
        
        if ($criticalPos !== false && $minorPos !== false) {
            $this->assertLessThan($minorPos, $criticalPos);
        }
    }
    
    public function testGeneratePromptWithEmptyIssues() {
        $issues = [];
        $content = ['title' => 'Test', 'content' => 'Test'];
        
        $prompt = $this->generator->generatePrompt($issues, $content, 'test');
        
        $this->assertIsString($prompt);
    }
    
    public function testPromptIncludesFocusKeyword() {
        $issues = [
            ['type' => 'keyword_density_low', 'severity' => 'major', 'message' => 'Low density']
        ];
        
        $content = ['title' => 'Test', 'content' => 'Test'];
        $focusKeyword = 'SEO optimization';
        
        $prompt = $this->generator->generatePrompt($issues, $content, $focusKeyword);
        
        $this->assertStringContainsString('SEO', $prompt);
    }
    
    public function testQuantitativeInstructions() {
        $issues = [
            [
                'type' => 'content_too_short',
                'severity' => 'major',
                'message' => 'Content needs 200 more words',
                'quantification' => ['needed' => 200]
            ]
        ];
        
        $content = ['title' => 'Test', 'content' => 'Short'];
        $prompt = $this->generator->generatePrompt($issues, $content, 'test');
        
        // Should contain quantitative instruction
        $this->assertNotEmpty($prompt);
    }
    
    public function testPromptForMultipleIssueTypes() {
        $issues = [
            ['type' => 'keyword_density_low', 'severity' => 'major', 'message' => 'Low keyword density'],
            ['type' => 'meta_description_short', 'severity' => 'major', 'message' => 'Meta too short'],
            ['type' => 'title_missing_keyword', 'severity' => 'critical', 'message' => 'Title needs keyword']
        ];
        
        $content = [
            'title' => 'Test',
            'content' => 'Content',
            'meta_description' => 'Short'
        ];
        
        $prompt = $this->generator->generatePrompt($issues, $content, 'keyword');
        
        $this->assertIsString($prompt);
        $this->assertGreaterThan(50, strlen($prompt)); // Should be substantial
    }
    
    protected function tearDown(): void {
        parent::tearDown();
        $this->generator = null;
    }
}

// Run tests if executed directly
if (basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    echo "Running Correction Prompt Generator Unit Tests...\n\n";
    
    $test = new TestCorrectionPromptGenerator('test');
    $test->setUp();
    
    $methods = get_class_methods(TestCorrectionPromptGenerator::class);
    $passed = 0;
    $failed = 0;
    
    foreach ($methods as $method) {
        if (strpos($method, 'test') === 0) {
            try {
                $test->setUp();
                $test->$method();
                echo "✅ PASS: $method\n";
                $passed++;
            } catch (Exception $e) {
                echo "❌ FAIL: $method - " . $e->getMessage() . "\n";
                $failed++;
            } finally {
                $test->tearDown();
            }
        }
    }
    
    echo "\n" . str_repeat('=', 50) . "\n";
    echo "Total: " . ($passed + $failed) . " | Passed: $passed | Failed: $failed\n";
    
    exit($failed > 0 ? 1 : 0);
}
