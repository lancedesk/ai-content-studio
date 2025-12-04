<?php
/**
 * Debug Validation Pipeline Issues
 *
 * This test reproduces the exact content that's failing in production
 * to understand why the validation pipeline isn't correcting the issues.
 */

require_once __DIR__ . '/bootstrap.php';

echo "=== Validation Pipeline Debug Test ===\n\n";

// Reproduce the exact problematic content from the debug log
$problematic_content = [
    'title' => 'AI',
    'meta_description' => 'Discover how AI content generation boosts small business efficiency and SEO',
    'slug' => 'ai-content-generation-benefits',
    'content' => '<p>Small businesses are increasingly turning to AI for content generation, and it\'s easy to see why. AI offers a range of benefits, from automating tedious tasks to improving search engine optimization (SEO). By leveraging AI, small businesses can free up more time to focus on what really matters: growing their business and engaging with customers. Moreover, AI-generated content can help small businesses establish a strong online presence, driving more traffic and sales.</p><h2>The Power of Automation</h2><p>One of the most significant advantages of AI content generation is its ability to automate repetitive tasks. For instance, AI can generate high-quality product descriptions, social media posts, and even entire blog articles. This not only saves time but also reduces the risk of human error. Additionally, AI can analyze data and provide valuable insights, helping small businesses refine their content strategy and improve their overall marketing efforts.</p><h3>Enhancing SEO</h3><p>AI-generated content can also enhance a small business\'s SEO. By analyzing keywords, trends, and user behavior, AI can create content that is optimized for search engines. This can lead to higher search engine rankings, driving more organic traffic to a small business\'s website. Furthermore, AI can help small businesses create content that is tailored to their target audience, increasing engagement and conversion rates.</p><h2>The Benefits of AI Content Generation</h2><p>So, what are the key benefits of AI content generation for small businesses? Some of the most significant advantages include:</p><ul><li>Increased efficiency and productivity</li><li>Improved SEO and search engine rankings</li><li>Enhanced customer engagement and conversion rates</li><li>Reduced costs and improved ROI</li></ul><p>However, it\'s essential to note that AI content generation is not a replacement for human creativity and judgment. Small businesses should use AI as a tool to augment their content strategy, rather than relying solely on automated content. By striking the right balance between human and artificial intelligence, small businesses can unlock the full potential of AI content generation.</p><h3>Getting Started with AI Content Generation</h3><p>If you\'re interested in leveraging AI content generation for your small business, there are several steps you can take. First, research different AI content generation tools and platforms to find the one that best fits your needs. Additionally, consider the following tips:</p><ul><li>Start small and experiment with different types of content</li><li>Monitor and analyze the performance of your AI-generated content</li><li>Refine your content strategy based on the insights and data you receive</li></ul><p>Therefore, by embracing AI content generation, small businesses can revolutionize their content strategy and stay ahead of the competition. To learn more about the benefits of AI content generation, check out our article on <a href=\'http://localhost/localserver/index.php/2025/11/26/revolutionizing-small-business-marketing-the-power-of-ai-content-generation/\'>revolutionizing small business marketing with AI</a>. You can also explore our guide on <a href=\'http://localhost/localserver/index.php/2025/11/26/revolutionize-your-content-strategy-with-ai-the-benefits-of-ai-content-generation-for-small-businesses/\'>revolutionizing your content strategy with AI</a>.</p><p>For more information on AI content generation and its applications, visit <a href=\'https://www.contentmarketinginstitute.com/\'>Content Marketing Institute</a>.</p>',
    'excerpt' => 'AI content generation boosts small business efficiency and SEO',
    'focus_keyword' => 'AI',
    'secondary_keywords' => ['content generation', 'small business', 'SEO', 'automation'],
    'tags' => ['AI content generation', 'small business marketing', 'SEO optimization', 'content strategy'],
    'image_prompts' => [['prompt' => 'AI-generated content on a computer screen', 'alt' => 'AI content generation for small businesses']],
    'internal_links' => [['url' => 'http://localhost/localserver/index.php/2025/11/26/revolutionizing-small-business-marketing-the-power-of-ai-content-generation/', 'anchor' => 'revolutionizing small business marketing']],
    'outbound_links' => [['url' => 'https://www.contentmarketinginstitute.com/', 'anchor' => 'Content Marketing Institute']]
];

echo "Original Content Issues:\n";
echo "- Title: '" . $problematic_content['title'] . "' (" . strlen($problematic_content['title']) . " chars)\n";
echo "- Meta Description: '" . $problematic_content['meta_description'] . "' (" . strlen($problematic_content['meta_description']) . " chars)\n";
echo "- Focus Keyword: '" . $problematic_content['focus_keyword'] . "'\n\n";

// Test individual validators first
echo "Testing Individual Validators:\n";

// 1. Meta Description Validator
$metaValidator = new MetaDescriptionValidator();
$metaResult = $metaValidator->validateLength($problematic_content['meta_description']);
echo "1. Meta Description Validator:\n";
echo "   - Length: " . strlen($problematic_content['meta_description']) . " chars\n";
echo "   - Valid: " . ($metaResult['isValid'] ? 'YES' : 'NO') . "\n";
echo "   - Required: 120-156 chars\n\n";

// 2. Keyword Density Calculator
$keywordCalc = new Keyword_Density_Calculator();
$densityResult = $keywordCalc->calculate_density($problematic_content['content'], $problematic_content['focus_keyword']);
echo "2. Keyword Density Calculator:\n";
echo "   - Density: " . ($densityResult['overall_density'] ?? 'N/A') . "%\n";
echo "   - Occurrences: " . ($densityResult['total_occurrences'] ?? 'N/A') . "\n";
echo "   - Word Count: " . ($densityResult['word_count'] ?? 'N/A') . "\n";
echo "   - Valid Range: 0.5% - 2.5%\n\n";

// 3. Passive Voice Analyzer
$passiveAnalyzer = new PassiveVoiceAnalyzer();
$passiveResult = $passiveAnalyzer->analyze($problematic_content['content']);
echo "3. Passive Voice Analyzer:\n";
echo "   - Passive Voice: " . ($passiveResult['passivePercentage'] ?? 'N/A') . "%\n";
echo "   - Max Allowed: 10%\n";
echo "   - Valid: " . (($passiveResult['passivePercentage'] ?? 0) <= 10 ? 'YES' : 'NO') . "\n\n";

// Test the full validation pipeline
echo "Testing Full Validation Pipeline:\n";

try {
    $pipeline = new SEOValidationPipeline();
    
    echo "Pipeline initialized successfully.\n";
    echo "Configuration:\n";
    $config = $pipeline->getConfig();
    echo "- Auto Correction: " . ($config['autoCorrection'] ? 'ENABLED' : 'DISABLED') . "\n";
    echo "- Min Meta Desc: " . $config['minMetaDescLength'] . " chars\n";
    echo "- Max Meta Desc: " . $config['maxMetaDescLength'] . " chars\n";
    echo "- Max Keyword Density: " . $config['maxKeywordDensity'] . "%\n";
    echo "- Max Passive Voice: " . $config['maxPassiveVoice'] . "%\n\n";
    
    echo "Running validation and correction...\n";
    $result = $pipeline->validateAndCorrect(
        $problematic_content, 
        $problematic_content['focus_keyword'], 
        $problematic_content['secondary_keywords']
    );
    
    echo "Validation Results:\n";
    echo "- Valid: " . ($result->isValid ? 'YES' : 'NO') . "\n";
    echo "- Overall Score: " . ($result->overallScore ?? 'N/A') . "\n";
    echo "- Errors: " . count($result->errors) . "\n";
    echo "- Warnings: " . count($result->warnings) . "\n";
    echo "- Corrections Made: " . implode(', ', $result->correctionsMade ?? []) . "\n\n";
    
    if (!empty($result->errors)) {
        echo "Errors Found:\n";
        foreach ($result->errors as $error) {
            echo "- " . $error . "\n";
        }
        echo "\n";
    }
    
    if (!empty($result->warnings)) {
        echo "Warnings Found:\n";
        foreach ($result->warnings as $warning) {
            echo "- " . $warning . "\n";
        }
        echo "\n";
    }
    
    if (isset($result->correctedContent)) {
        echo "Corrected Content:\n";
        echo "- Title: '" . ($result->correctedContent['title'] ?? 'N/A') . "' (" . strlen($result->correctedContent['title'] ?? '') . " chars)\n";
        echo "- Meta Description: '" . ($result->correctedContent['meta_description'] ?? 'N/A') . "' (" . strlen($result->correctedContent['meta_description'] ?? '') . " chars)\n";
        echo "- Focus Keyword: '" . ($result->correctedContent['focus_keyword'] ?? 'N/A') . "'\n";
    }
    
} catch (Exception $e) {
    echo "Pipeline Error: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Debug Test Complete ===\n";