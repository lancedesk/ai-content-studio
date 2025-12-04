<?php
/**
 * Test Admin Interface Loading
 *
 * Quick test to verify the admin interface can load without errors
 */

// Load bootstrap for testing
require_once __DIR__ . '/tests/bootstrap.php';

echo "=== Admin Interface Loading Test ===\n\n";

try {
    echo "1. Loading SEO classes...\n";
    
    // Load all required SEO classes (same as admin-init.php)
    $seo_classes = [
        'seo/class-seo-validation-result.php',
        'seo/class-content-validation-metrics.php', 
        'seo/class-seo-prompt-configuration.php',
        'seo/class-meta-description-validator.php',
        'seo/class-meta-description-corrector.php',
        'seo/class-keyword-density-calculator.php',
        'seo/class-keyword-density-optimizer.php',
        'seo/class-passive-voice-analyzer.php',
        'seo/class-sentence-length-analyzer.php',
        'seo/class-transition-word-analyzer.php',
        'seo/class-readability-corrector.php',
        'seo/class-title-uniqueness-validator.php',
        'seo/class-title-optimization-engine.php',
        'seo/class-image-prompt-generator.php',
        'seo/class-alt-text-accessibility-optimizer.php',
        'seo/class-seo-validation-cache.php',
        'seo/class-smart-retry-manager.php',
        'seo/class-seo-error-handler.php',
        'seo/class-seo-validation-pipeline.php',
        'seo/class-seo-issue-detector.php',
        'seo/class-correction-prompt-generator.php',
        'seo/class-multi-pass-seo-optimizer.php',
        'seo/class-integration-compatibility-layer.php'
    ];
    
    $loaded_count = 0;
    foreach ($seo_classes as $class_file) {
        $file_path = __DIR__ . '/' . $class_file;
        if (file_exists($file_path)) {
            require_once $file_path;
            $loaded_count++;
        }
    }
    
    echo "   Loaded $loaded_count SEO classes\n";
    
    echo "2. Loading admin class...\n";
    require_once __DIR__ . '/admin/class-seo-optimizer-admin.php';
    echo "   Admin class loaded successfully\n";
    
    echo "3. Testing admin class instantiation...\n";
    $admin = new SEOOptimizerAdmin();
    echo "   Admin class instantiated successfully\n";
    
    echo "4. Testing integration layer access...\n";
    $integrationLayer = $admin->getIntegrationLayer();
    if ($integrationLayer) {
        echo "   Integration layer accessible\n";
        
        $status = $integrationLayer->getIntegrationStatus();
        echo "   Optimizer enabled: " . ($status['optimizer_enabled'] ? 'YES' : 'NO') . "\n";
        echo "   Integration mode: " . $status['integration_mode'] . "\n";
    } else {
        echo "   ⚠️  Integration layer not available\n";
    }
    
    echo "\n✅ SUCCESS: Admin interface can load without errors!\n";
    echo "\nYou should now be able to access the admin interface at:\n";
    echo "WordPress Admin > SEO Optimizer (in main menu)\n";
    echo "Or: WordPress Admin > Settings > SEO Optimizer\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";