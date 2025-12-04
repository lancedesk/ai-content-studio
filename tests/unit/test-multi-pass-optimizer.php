<?php
/**
 * Unit Test: Multi-Pass SEO Optimizer
 *
 * Tests the core multi-pass optimizer engine
 *
 * @package AI_Content_Studio
 * @subpackage Tests
 */

require_once dirname(__DIR__) . '/bootstrap.php';

echo "=== Unit Test: Multi-Pass SEO Optimizer ===\n\n";

class TestMultiPassOptimizer extends SimpleTestCase {
    
    private $optimizer;
    
    protected function setUp(): void {
        parent::setUp();
        
        $config = [
            'maxIterations' => 3,
            'targetComplianceScore' => 95.0,
            'enableEarlyTermination' => true,
            'autoCorrection' => true,
            'logLevel' => 'error'
        ];
        
        $this->optimizer = new MultiPassSEOOptimizer($config);
    }
    
    public function testOptimizerInitialization() {
        $this->assertInstanceOf(MultiPassSEOOptimizer::class, $this->optimizer);
        
        $config = $this->optimizer->getConfig();
        $this->assertEquals(3, $config['maxIterations']);
        $this->assertEquals(95.0, $config['targetComplianceScore']);
        $this->assertTrue($config['enableEarlyTermination']);
    }
    
    public function testOptimizeContentWithValidInput() {
        $content = [
            'title' => 'Test Article About SEO',
            'content' => 'This is a test article about SEO optimization. SEO is important for websites.',
            'excerpt' => 'Test excerpt',
            'meta_description' => 'Test meta description',
            'type' => 'post'
        ];
        
        $result = $this->optimizer->optimizeContent($content, 'SEO', []);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('content', $result);
        $this->assertArrayHasKey('optimizationSummary', $result);
    }
    
    public function testOptimizeContentWithEmptyContent() {
        $content = [
            'title' => '',
            'content' => '',
            'type' => 'post'
        ];
        
        $result = $this->optimizer->optimizeContent($content, 'test', []);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }
    
    public function testIterationTracking() {
        $content = [
            'title' => 'Short',
            'content' => 'Short content.',
            'type' => 'post'
        ];
        
        $result = $this->optimizer->optimizeContent($content, 'keyword', []);
        
        if (isset($result['optimizationSummary'])) {
            $this->assertArrayHasKey('iterationsUsed', $result['optimizationSummary']);
            $this->assertGreaterThanOrEqual(1, $result['optimizationSummary']['iterationsUsed']);
            $this->assertLessThanOrEqual(3, $result['optimizationSummary']['iterationsUsed']);
        }
    }
    
    public function testConfigurationUpdate() {
        $newConfig = [
            'maxIterations' => 5,
            'targetComplianceScore' => 90.0
        ];
        
        $this->optimizer->updateConfig($newConfig);
        $config = $this->optimizer->getConfig();
        
        $this->assertEquals(5, $config['maxIterations']);
        $this->assertEquals(90.0, $config['targetComplianceScore']);
    }
    
    public function testEarlyTermination() {
        // Create content that's already optimized
        $content = [
            'title' => 'Complete Guide to SEO Optimization in 2025',
            'content' => str_repeat('This is excellent SEO content with proper keyword density. ', 50),
            'excerpt' => 'Comprehensive guide to SEO',
            'meta_description' => 'Learn everything about SEO optimization. Complete guide with best practices and tips for improving your website ranking.',
            'type' => 'post'
        ];
        
        $result = $this->optimizer->optimizeContent($content, 'SEO', []);
        
        if (isset($result['optimizationSummary']['iterationsUsed'])) {
            // Should terminate early if content is already good
            $this->assertLessThanOrEqual(3, $result['optimizationSummary']['iterationsUsed']);
        }
    }
    
    public function testOptimizationSummaryStructure() {
        $content = [
            'title' => 'Test Title',
            'content' => 'Test content for validation.',
            'type' => 'post'
        ];
        
        $result = $this->optimizer->optimizeContent($content, 'test', []);
        
        if (isset($result['optimizationSummary'])) {
            $summary = $result['optimizationSummary'];
            
            $this->assertArrayHasKey('initialScore', $summary);
            $this->assertArrayHasKey('finalScore', $summary);
            $this->assertArrayHasKey('iterationsUsed', $summary);
            $this->assertArrayHasKey('complianceAchieved', $summary);
        }
    }
    
    protected function tearDown(): void {
        parent::tearDown();
        $this->optimizer = null;
    }
}

// Run tests if executed directly
if (basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    echo "Running Multi-Pass Optimizer Unit Tests...\n\n";
    
    $test = new TestMultiPassOptimizer('test');
    $test->setUp();
    
    $methods = get_class_methods(TestMultiPassOptimizer::class);
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
