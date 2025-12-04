<?php
/**
 * Unit Tests for Integration Compatibility Layer
 *
 * @package AI_Content_Studio
 * @subpackage Tests
 */

require_once dirname(__DIR__) . '/bootstrap.php';

class TestIntegrationCompatibilityLayer extends SimpleTestCase {
    
    private $integrationLayer;
    
    protected function setUp(): void {
        parent::setUp();
        
        $config = [
            'enableOptimizer' => true,
            'autoOptimizeNewContent' => false,
            'bypassMode' => false,
            'supportedFormats' => ['post', 'page'],
            'integrationMode' => 'seamless',
            'fallbackToOriginal' => true,
            'preserveExistingWorkflow' => true,
            'logLevel' => 'error'
        ];
        
        $this->integrationLayer = new IntegrationCompatibilityLayer($config);
    }
    
    public function testLayerInitialization() {
        $this->assertInstanceOf(IntegrationCompatibilityLayer::class, $this->integrationLayer);
    }
    
    public function testBypassMode() {
        $this->integrationLayer->updateConfig(['bypassMode' => true]);
        
        $content = [
            'title' => 'Original Title',
            'content' => 'Original content',
            'type' => 'post'
        ];
        
        $result = $this->integrationLayer->processGeneratedContent($content, 'test');
        
        $this->assertEquals('Original Title', $result['title']);
        $this->assertEquals('Original content', $result['content']);
        $this->assertArrayHasKey('_acs_optimization', $result);
        $this->assertEquals('bypassed', $result['_acs_optimization']['status']);
    }
    
    public function testSeamlessIntegration() {
        $this->integrationLayer->setIntegrationMode('seamless');
        
        $content = [
            'title' => 'Test Title',
            'content' => 'Test content',
            'type' => 'post'
        ];
        
        $result = $this->integrationLayer->processGeneratedContent($content, 'test');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('content', $result);
        $this->assertArrayHasKey('_acs_optimization', $result);
    }
    
    public function testManualMode() {
        $this->integrationLayer->setIntegrationMode('manual');
        
        $content = [
            'title' => 'Test Title',
            'content' => 'Test content',
            'type' => 'post'
        ];
        
        $result = $this->integrationLayer->processGeneratedContent($content, 'test');
        
        $this->assertArrayHasKey('_acs_optimization', $result);
        $this->assertEquals('manual_mode', $result['_acs_optimization']['status']);
    }
    
    public function testUnsupportedFormat() {
        $content = [
            'title' => 'Test',
            'content' => 'Test',
            'type' => 'custom_type'
        ];
        
        $result = $this->integrationLayer->processGeneratedContent($content, 'test');
        
        $this->assertArrayHasKey('_acs_optimization', $result);
        $this->assertEquals('unsupported_format', $result['_acs_optimization']['status']);
    }
    
    public function testEnableDisableOptimizer() {
        $this->integrationLayer->enableOptimizer();
        $this->assertTrue($this->integrationLayer->isOptimizerEnabled());
        
        $this->integrationLayer->disableOptimizer();
        $this->assertFalse($this->integrationLayer->isOptimizerEnabled());
    }
    
    public function testGetIntegrationStatus() {
        $status = $this->integrationLayer->getIntegrationStatus();
        
        $this->assertIsArray($status);
        $this->assertArrayHasKey('optimizer_enabled', $status);
        $this->assertArrayHasKey('bypass_mode', $status);
        $this->assertArrayHasKey('integration_mode', $status);
        $this->assertArrayHasKey('supported_formats', $status);
    }
    
    public function testConfigurationUpdate() {
        $newConfig = [
            'enableOptimizer' => false,
            'bypassMode' => true
        ];
        
        $this->integrationLayer->updateConfig($newConfig);
        $config = $this->integrationLayer->getConfig();
        
        $this->assertFalse($config['enableOptimizer']);
        $this->assertTrue($config['bypassMode']);
    }
    
    public function testSupportedFormats() {
        $formats = $this->integrationLayer->getSupportedFormats();
        
        $this->assertIsArray($formats);
        $this->assertContains('post', $formats);
        $this->assertContains('page', $formats);
    }
    
    public function testAddRemoveSupportedFormat() {
        $this->integrationLayer->addSupportedFormat('article');
        $formats = $this->integrationLayer->getSupportedFormats();
        $this->assertContains('article', $formats);
        
        $this->integrationLayer->removeSupportedFormat('article');
        $formats = $this->integrationLayer->getSupportedFormats();
        $this->assertNotContains('article', $formats);
    }
    
    public function testIntegrationCompatibilityTest() {
        $testContent = [
            'title' => 'Test',
            'content' => 'Test content',
            'type' => 'post'
        ];
        
        $results = $this->integrationLayer->testIntegrationCompatibility($testContent);
        
        $this->assertIsArray($results);
        $this->assertArrayHasKey('bypass_mode_test', $results);
        $this->assertArrayHasKey('format_support_test', $results);
        $this->assertArrayHasKey('workflow_preservation_test', $results);
    }
    
    protected function tearDown(): void {
        parent::tearDown();
        $this->integrationLayer = null;
    }
}

// Run tests if executed directly
if (basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    echo "Running Integration Compatibility Layer Unit Tests...\n\n";
    
    $test = new TestIntegrationCompatibilityLayer('test');
    $test->setUp();
    
    $methods = get_class_methods(TestIntegrationCompatibilityLayer::class);
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
