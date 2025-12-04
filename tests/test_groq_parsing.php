<?php
/**
 * Test Groq JSON Parsing Fix
 */

require_once __DIR__ . '/bootstrap.php';

// Mock the base class since we can't find it
if (!class_exists('ACS_AI_Provider_Base')) {
    class ACS_AI_Provider_Base {
        protected $api_key;
        protected $provider_name;
        protected $api_base_url;
        protected $rate_limit = array();
        
        public function __construct($api_key = '') {
            $this->api_key = $api_key;
        }
        
        protected function make_request($endpoint, $data, $method = 'POST') {
            return array(); // Mock response
        }
    }
}

require_once dirname(__DIR__) . '/api/providers/class-acs-groq.php';

echo "=== Testing Groq JSON Parsing Fix ===\n\n";

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

// Create a Groq instance
$groq = new ACS_Groq('test-key');

// Use reflection to access the private parse_generated_content method
$reflection = new ReflectionClass($groq);
$method = $reflection->getMethod('parse_generated_content');
$method->setAccessible(true);

// Mock response array
$mock_response = array(
    'model' => 'llama-3.3-70b-versatile',
    'usage' => array('total_tokens' => 100)
);

echo "Testing JSON parsing with markdown code blocks...\n";

try {
    $result = $method->invoke($groq, $sample_content, $mock_response);
    
    if (is_array($result)) {
        echo "✓ Successfully parsed JSON from markdown\n";
        echo "✓ Title: " . (isset($result['title']) ? $result['title'] : 'MISSING') . "\n";
        echo "✓ Meta Description: " . (isset($result['meta_description']) ? substr($result['meta_description'], 0, 50) . '...' : 'MISSING') . "\n";
        echo "✓ Content: " . (isset($result['content']) ? 'Present (' . strlen($result['content']) . ' chars)' : 'MISSING') . "\n";
        echo "✓ Focus Keyword: " . (isset($result['focus_keyword']) ? $result['focus_keyword'] : 'MISSING') . "\n";
        echo "✓ Image Prompts: " . (isset($result['image_prompts']) && is_array($result['image_prompts']) ? count($result['image_prompts']) . ' items' : 'MISSING') . "\n";
        echo "✓ Internal Links: " . (isset($result['internal_links']) && is_array($result['internal_links']) ? count($result['internal_links']) . ' items' : 'MISSING') . "\n";
        
        // Check for required fields
        $required_fields = array('title', 'content', 'meta_description');
        $missing_fields = array();
        foreach ($required_fields as $field) {
            if (empty($result[$field])) {
                $missing_fields[] = $field;
            }
        }
        
        if (empty($missing_fields)) {
            echo "✓ All required fields present\n";
        } else {
            echo "✗ Missing required fields: " . implode(', ', $missing_fields) . "\n";
        }
        
    } else {
        echo "✗ Failed to parse - result is not an array\n";
        echo "Result type: " . gettype($result) . "\n";
    }
    
} catch (Exception $e) {
    echo "✗ Exception during parsing: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";