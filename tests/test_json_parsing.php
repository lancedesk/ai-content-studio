<?php
/**
 * Test JSON Parsing Logic
 */

echo "=== Testing JSON Parsing Logic ===\n\n";

// Sample response content with markdown code blocks (like what we see in the debug log)
$sample_content = '```json
{
  "title": "AI Boosts Business",
  "meta_description": "Discover how AI content generation transforms small businesses, improving SEO and automation",
  "slug": "ai-content-generation-benefits",
  "content": "<p>Small businesses are leveraging the power of AI to streamline their content generation.</p>",
  "excerpt": "AI content generation transforms small businesses",
  "focus_keyword": "AI",
  "secondary_keywords": ["content generation", "small business", "SEO", "automation"],
  "tags": ["AI content generation", "small business marketing"],
  "image_prompts": [{"prompt": "A small business owner working on their laptop", "alt": "AI content generation for small businesses"}],
  "internal_links": [{"url": "http://example.com/page1", "anchor": "example link"}],
  "outbound_links": [{"url": "https://example.com", "anchor": "external link"}]
}
```';

echo "Original content length: " . strlen($sample_content) . "\n";
echo "Content preview: " . substr($sample_content, 0, 100) . "...\n\n";

// Test markdown extraction
echo "Testing markdown code block extraction...\n";

$clean = $sample_content;

// Handle markdown code blocks - extract JSON from ```json ... ``` blocks
if ( preg_match('/```json\s*\n?(.*?)\n?```/s', $clean, $matches) ) {
    $clean = trim( $matches[1] );
    echo "✓ Extracted from ```json``` block\n";
} else if ( preg_match('/```\s*\n?(.*?)\n?```/s', $clean, $matches) ) {
    // Try generic code block extraction
    $clean = trim( $matches[1] );
    echo "✓ Extracted from generic ``` block\n";
} else {
    echo "✗ No code blocks found\n";
}

echo "Cleaned content length: " . strlen($clean) . "\n";
echo "Cleaned content preview: " . substr($clean, 0, 100) . "...\n\n";

// Test JSON parsing
echo "Testing JSON parsing...\n";
$parsed = json_decode( $clean, true );
$json_error = json_last_error();

if ( $json_error === JSON_ERROR_NONE && is_array( $parsed ) ) {
    echo "✓ Successfully parsed JSON\n";
    echo "✓ Keys found: " . implode( ', ', array_keys( $parsed ) ) . "\n";
    
    // Check required fields
    $required_fields = array( 'title', 'content', 'meta_description' );
    $missing_fields = array();
    foreach ( $required_fields as $field ) {
        if ( empty( $parsed[$field] ) ) {
            $missing_fields[] = $field;
        }
    }
    
    if ( empty( $missing_fields ) ) {
        echo "✓ All required fields present\n";
    } else {
        echo "✗ Missing required fields: " . implode( ', ', $missing_fields ) . "\n";
    }
    
    // Test specific fields
    echo "\nField validation:\n";
    echo "- Title: " . (isset($parsed['title']) ? '"' . $parsed['title'] . '"' : 'MISSING') . "\n";
    echo "- Meta Description Length: " . (isset($parsed['meta_description']) ? strlen($parsed['meta_description']) . ' chars' : 'MISSING') . "\n";
    echo "- Focus Keyword: " . (isset($parsed['focus_keyword']) ? '"' . $parsed['focus_keyword'] . '"' : 'MISSING') . "\n";
    echo "- Image Prompts: " . (isset($parsed['image_prompts']) && is_array($parsed['image_prompts']) ? count($parsed['image_prompts']) . ' items' : 'MISSING') . "\n";
    echo "- Internal Links: " . (isset($parsed['internal_links']) && is_array($parsed['internal_links']) ? count($parsed['internal_links']) . ' items' : 'MISSING') . "\n";
    
} else {
    echo "✗ JSON parsing failed\n";
    echo "Error: " . json_last_error_msg() . "\n";
    echo "Error code: " . $json_error . "\n";
}

echo "\n=== Test Complete ===\n";