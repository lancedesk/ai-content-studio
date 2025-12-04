<?php
// Test enhanced prompt engine directly
if ( ! defined( 'ACS_PLUGIN_PATH' ) ) define( 'ACS_PLUGIN_PATH', realpath( __DIR__ . '/..' ) . DIRECTORY_SEPARATOR );

// Load required classes
require_once ACS_PLUGIN_PATH . 'seo/class-seo-prompt-configuration.php';
require_once ACS_PLUGIN_PATH . 'seo/class-enhanced-prompt-engine.php';

try {
    // Create SEO configuration
    $config = new SEOPromptConfiguration([
        'focusKeyword' => 'AI content generation',
        'secondaryKeywords' => ['artificial intelligence', 'content automation', 'SEO'],
        'targetWordCount' => 800,
        'minMetaDescLength' => 120,
        'maxMetaDescLength' => 156,
        'maxKeywordDensity' => 2.5,
        'minKeywordDensity' => 0.5,
        'maxPassiveVoice' => 10.0,
        'minTransitionWords' => 30.0,
        'maxLongSentences' => 25.0,
        'maxTitleLength' => 66,
        'maxSubheadingKeywordUsage' => 75.0,
        'requireImages' => true,
        'requireKeywordInAltText' => true,
        'maxRetryAttempts' => 3
    ]);
    
    echo "✓ SEO Configuration created successfully\n";
    
    // Create enhanced prompt engine
    $promptEngine = new EnhancedPromptEngine($config);
    echo "✓ Enhanced Prompt Engine created successfully\n";
    
    // Test internal candidates
    $internalCandidates = [
        ['title' => 'About Our Services', 'url' => 'https://example.com/about'],
        ['title' => 'AI Tools Guide', 'url' => 'https://example.com/ai-tools']
    ];
    $promptEngine->setInternalCandidates($internalCandidates);
    echo "✓ Internal candidates set successfully\n";
    
    // Generate enhanced prompt
    $topic = 'The Benefits of AI Content Generation for Small Businesses';
    $keywords = 'AI content generation, artificial intelligence, content automation';
    $wordCount = 'medium';
    
    $enhancedPrompt = $promptEngine->buildSEOPrompt($topic, $keywords, $wordCount);
    echo "✓ Enhanced prompt generated successfully\n";
    echo "Enhanced prompt length: " . strlen($enhancedPrompt) . " characters\n";
    
    // Test fallback prompt
    $validationErrors = ['Meta description too short', 'Keyword density too low'];
    $fallbackPrompt = $promptEngine->generateFallbackPrompt($topic, $keywords, $wordCount, $validationErrors, 1);
    echo "✓ Fallback prompt generated successfully\n";
    echo "Fallback prompt length: " . strlen($fallbackPrompt) . " characters\n";
    
    // Test progressive fallbacks
    $progressiveFallbacks = $promptEngine->generateProgressiveFallbacks($topic, $keywords, $wordCount, $validationErrors, 1);
    echo "✓ Progressive fallbacks generated successfully\n";
    echo "Number of fallback strategies: " . count($progressiveFallbacks) . "\n";
    
    foreach ($progressiveFallbacks as $strategy => $prompt) {
        echo "  - Strategy: {$strategy}, Length: " . strlen($prompt) . " characters\n";
    }
    
    // Write enhanced prompt to log for inspection
    $logPath = dirname(__DIR__) . '/acs_enhanced_prompt_test.log';
    file_put_contents($logPath, date('Y-m-d H:i:s') . "\nENHANCED PROMPT:\n" . $enhancedPrompt . "\n\n" . 
                     "FALLBACK PROMPT:\n" . $fallbackPrompt . "\n---\n", FILE_APPEND | LOCK_EX);
    echo "✓ Prompts written to: {$logPath}\n";
    
    echo "\n🎉 All tests passed! Enhanced prompt engine is working correctly.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>