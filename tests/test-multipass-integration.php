<?php
/**
 * Test Multi-Pass SEO Optimizer Integration
 *
 * Quick test to verify the multi-pass optimizer is working with content generation
 */

// Load bootstrap for testing
require_once __DIR__ . '/tests/bootstrap.php';

echo "=== Multi-Pass SEO Optimizer Integration Test ===\n\n";

// Test content (similar to what your generator produces)
$testContent = [
    'title' => 'AI Boosts Business',
    'meta_description' => 'Discover how AI content generation transforms small businesses with automation and SEO',
    'slug' => 'ai-content-generation-benefits',
    'content' => '<p>Artificial intelligence, or AI, is revolutionizing the way small businesses approach content generation. By leveraging AI, companies can automate tasks, improve SEO, and enhance their overall marketing strategy.</p><h2>The Benefits of AI Content Generation</h2><p>One of the primary advantages of AI content generation is its ability to save time and increase efficiency.</p>',
    'excerpt' => 'Discover the benefits of AI content generation for small businesses',
    'focus_keyword' => 'AI',
    'secondary_keywords' => ['content generation', 'small business', 'SEO', 'automation'],
    'type' => 'post'
];

try {
    // Load integration layer
    require_once __DIR__ . '/seo/class-integration-compatibility-layer.php';
    
    // Configure integration layer
    $config = [
        'enableOptimizer' => true,
        'autoOptimizeNewContent' => true,
        'bypassMode' => false,
        'supportedFormats' => ['post', 'page', 'article'],
        'integrationMode' => 'seamless',
        'fallbackToOriginal' => true,
        'preserveExistingWorkflow' => true,
        'logLevel' => 'info'
    ];
    
    $integrationLayer = new IntegrationCompatibilityLayer($config);
    
    echo "1. Testing Multi-Pass Optimization Integration...\n";
    
    // Process content through multi-pass optimizer
    $result = $integrationLayer->processGeneratedContent($testContent, 'AI', ['content generation', 'small business']);
    
    echo "2. Optimization Result:\n";
    
    if (isset($result['_acs_optimization'])) {
        $metadata = $result['_acs_optimization'];
        echo "   Status: " . $metadata['status'] . "\n";
        
        if (isset($metadata['score'])) {
            echo "   Score: " . number_format($metadata['score'], 1) . "%\n";
        }
        
        if (isset($metadata['iterations'])) {
            echo "   Iterations: " . $metadata['iterations'] . "\n";
        }
        
        if (isset($metadata['compliance_achieved'])) {
            echo "   Compliance: " . ($metadata['compliance_achieved'] ? 'YES' : 'NO') . "\n";
        }
        
        echo "\n3. Content Changes:\n";
        echo "   Original Title: " . $testContent['title'] . "\n";
        echo "   Optimized Title: " . $result['title'] . "\n";
        
        echo "   Original Meta: " . $testContent['meta_description'] . "\n";
        echo "   Optimized Meta: " . $result['meta_description'] . "\n";
        
        echo "\n✅ SUCCESS: Multi-pass optimizer is working!\n";
        
    } else {
        echo "❌ ISSUE: No optimization metadata found\n";
        echo "This suggests the multi-pass optimizer is not running.\n";
    }
    
    echo "\n4. Integration Status:\n";
    $status = $integrationLayer->getIntegrationStatus();
    echo "   Optimizer Enabled: " . ($status['optimizer_enabled'] ? 'YES' : 'NO') . "\n";
    echo "   Integration Mode: " . $status['integration_mode'] . "\n";
    echo "   Bypass Mode: " . ($status['bypass_mode'] ? 'YES' : 'NO') . "\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";