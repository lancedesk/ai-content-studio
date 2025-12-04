<?php
/**
 * Property Test: Color Accessibility Compliance
 * 
 * **Feature: acs-admin-ui-redesign, Property 8: Color Accessibility Compliance**
 * **Validates: Requirements 2.5, 7.5**
 *
 * Tests that for any color usage in the interface, sufficient contrast ratios are maintained
 * and color is not the only means of conveying information according to WCAG 2.1 AA standards.
 *
 * @package AI_Content_Studio
 * @subpackage Tests
 */

require_once __DIR__ . '/bootstrap.php';

class ColorAccessibilityCompliancePropertyTest extends SimpleTestCase {
    
    private $admin;
    private $component_renderer;
    
    // WCAG 2.1 AA contrast ratio requirements
    const MIN_CONTRAST_NORMAL = 4.5;
    const MIN_CONTRAST_LARGE = 3.0;
    const LARGE_TEXT_SIZE = 18; // 18pt or 14pt bold
    
    protected function setUp(): void {
        parent::setUp();
        
        // Initialize admin and component renderer
        require_once ACS_PLUGIN_PATH . 'admin/class-acs-unified-admin.php';
        require_once ACS_PLUGIN_PATH . 'admin/class-acs-component-renderer.php';
        
        $this->admin = new ACS_Unified_Admin('ai-content-studio', '2.0.0');
        $this->component_renderer = new ACS_Component_Renderer();
        
        // Set up test user with admin capabilities
        $user = wp_get_current_user();
        $user->set_role('administrator');
    }
    
    /**
     * Property: Color Accessibility Compliance
     * 
     * For any color usage in the interface, sufficient contrast ratios should be maintained
     * and color should not be the only means of conveying information.
     */
    public function testColorAccessibilityCompliance() {
        $test_cases = $this->generateColorTestCases();
        
        foreach ($test_cases as $case) {
            $this->validateColorAccessibility($case);
        }
    }
    
    /**
     * Generate test cases for color accessibility
     */
    private function generateColorTestCases() {
        return [
            // Status indicators with different states
            [
                'type' => 'badge',
                'props' => [
                    'text' => 'Success',
                    'variant' => 'success',
                    'size' => 'small'
                ],
                'expected_colors' => ['background' => '#00a32a', 'text' => '#ffffff']
            ],
            [
                'type' => 'badge',
                'props' => [
                    'text' => 'Error',
                    'variant' => 'error',
                    'size' => 'small'
                ],
                'expected_colors' => ['background' => '#d63638', 'text' => '#ffffff']
            ],
            [
                'type' => 'badge',
                'props' => [
                    'text' => 'Warning',
                    'variant' => 'warning',
                    'size' => 'small'
                ],
                'expected_colors' => ['background' => '#dba617', 'text' => '#000000']
            ],
            // Buttons with different variants
            [
                'type' => 'button',
                'props' => [
                    'text' => 'Primary Action',
                    'variant' => 'primary',
                    'size' => 'medium'
                ],
                'expected_colors' => ['background' => '#2271b1', 'text' => '#ffffff']
            ],
            [
                'type' => 'button',
                'props' => [
                    'text' => 'Secondary Action',
                    'variant' => 'secondary',
                    'size' => 'medium'
                ],
                'expected_colors' => ['background' => '#f6f7f7', 'text' => '#2c3338']
            ],
            // Progress indicators with color coding
            [
                'type' => 'progress',
                'props' => [
                    'value' => 85,
                    'max' => 100,
                    'label' => 'SEO Score',
                    'color_variant' => 'success'
                ],
                'expected_colors' => ['fill' => '#00a32a', 'background' => '#f0f0f1']
            ],
            [
                'type' => 'progress',
                'props' => [
                    'value' => 45,
                    'max' => 100,
                    'label' => 'SEO Score',
                    'color_variant' => 'warning'
                ],
                'expected_colors' => ['fill' => '#dba617', 'background' => '#f0f0f1']
            ],
            // Links and interactive text
            [
                'type' => 'link',
                'props' => [
                    'text' => 'Edit Post',
                    'url' => '#',
                    'variant' => 'primary'
                ],
                'expected_colors' => ['text' => '#2271b1', 'background' => 'transparent']
            ],
            // Form validation states
            [
                'type' => 'input',
                'props' => [
                    'type' => 'text',
                    'name' => 'test_field',
                    'label' => 'Test Field',
                    'validation_state' => 'error',
                    'validation_message' => 'This field is required'
                ],
                'expected_colors' => ['border' => '#d63638', 'text' => '#d63638']
            ],
            [
                'type' => 'input',
                'props' => [
                    'type' => 'text',
                    'name' => 'test_field',
                    'label' => 'Test Field',
                    'validation_state' => 'success',
                    'validation_message' => 'Valid input'
                ],
                'expected_colors' => ['border' => '#00a32a', 'text' => '#00a32a']
            ]
        ];
    }
    
    /**
     * Validate color accessibility for a test case
     */
    private function validateColorAccessibility($case) {
        $html = $this->renderComponent($case);
        
        // Test contrast ratios
        $this->assertContrastRatioCompliance($html, $case);
        
        // Test that color is not the only indicator
        $this->assertColorNotOnlyIndicator($html, $case);
        
        // Test color blindness considerations
        $this->assertColorBlindnessSupport($html, $case);
        
        // Test focus and hover state contrast
        $this->assertInteractionStateContrast($html, $case);
    }
    
    /**
     * Render component based on test case
     */
    private function renderComponent($case) {
        switch ($case['type']) {
            case 'badge':
                return $this->component_renderer->render_badge($case['props']);
            case 'button':
                return $this->component_renderer->render_button($case['props']);
            case 'progress':
                return $this->component_renderer->render_progress($case['props']);
            case 'link':
                return $this->component_renderer->render_link($case['props']);
            case 'input':
                return $this->component_renderer->render_form_field($case['props']);
            default:
                return '';
        }
    }
    
    /**
     * Assert contrast ratio compliance (Requirements 2.5, 7.5)
     */
    private function assertContrastRatioCompliance($html, $case) {
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        
        // Extract color information from styles and classes
        $elements_with_color = $xpath->query('//*[@style or @class]');
        
        foreach ($elements_with_color as $element) {
            $computed_colors = $this->extractColorsFromElement($element);
            
            if (!empty($computed_colors['foreground']) && !empty($computed_colors['background'])) {
                $contrast_ratio = $this->calculateContrastRatio(
                    $computed_colors['foreground'],
                    $computed_colors['background']
                );
                
                $is_large_text = $this->isLargeText($element);
                $min_contrast = $is_large_text ? self::MIN_CONTRAST_LARGE : self::MIN_CONTRAST_NORMAL;
                
                $this->assertGreaterThanOrEqual(
                    $min_contrast,
                    $contrast_ratio,
                    sprintf(
                        "Contrast ratio %.2f does not meet WCAG AA requirement of %.1f for %s text",
                        $contrast_ratio,
                        $min_contrast,
                        $is_large_text ? 'large' : 'normal'
                    )
                );
            }
        }
    }
    
    /**
     * Assert that color is not the only indicator (Requirement 7.5)
     */
    private function assertColorNotOnlyIndicator($html, $case) {
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        
        // Status indicators should have additional visual cues
        if ($case['type'] === 'badge' || isset($case['props']['validation_state'])) {
            // Should have text content, icons, or other visual indicators
            $text_content = trim($dom->textContent);
            $icons = $xpath->query('//*[contains(@class, "dashicons") or contains(@class, "icon")]');
            $visual_indicators = $xpath->query('//*[contains(@class, "indicator") or contains(@class, "status")]');
            
            $this->assertTrue(
                !empty($text_content) || $icons->length > 0 || $visual_indicators->length > 0,
                "Status information should not rely solely on color - include text, icons, or other visual indicators"
            );
        }
        
        // Form validation should include text messages
        if (isset($case['props']['validation_state'])) {
            $validation_messages = $xpath->query('//*[contains(@class, "validation") or contains(@class, "error") or contains(@class, "success")]');
            $this->assertGreaterThan(
                0,
                $validation_messages->length,
                "Form validation should include text messages, not just color changes"
            );
        }
        
        // Progress indicators should have numerical values
        if ($case['type'] === 'progress') {
            $progress_text = $xpath->query('//*[contains(text(), "%") or contains(@aria-valuenow, "")]');
            $this->assertGreaterThan(
                0,
                $progress_text->length,
                "Progress indicators should include numerical values, not just color coding"
            );
        }
    }
    
    /**
     * Assert color blindness support
     */
    private function assertColorBlindnessSupport($html, $case) {
        // Test that different states are distinguishable without color
        if ($case['type'] === 'badge' && isset($case['props']['variant'])) {
            $dom = new DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new DOMXPath($dom);
            
            // Should have different text content or visual patterns for different variants
            $text_content = trim($dom->textContent);
            $class_names = $xpath->query('//*/@class');
            
            $has_distinguishing_features = false;
            
            // Check for variant-specific classes
            foreach ($class_names as $class_attr) {
                if (strpos($class_attr->value, $case['props']['variant']) !== false) {
                    $has_distinguishing_features = true;
                    break;
                }
            }
            
            // Check for meaningful text content
            if (!empty($text_content) && $text_content !== 'Status') {
                $has_distinguishing_features = true;
            }
            
            $this->assertTrue(
                $has_distinguishing_features,
                "Different status variants should be distinguishable without color (through text, patterns, or shapes)"
            );
        }
    }
    
    /**
     * Assert interaction state contrast
     */
    private function assertInteractionStateContrast($html, $case) {
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        
        // Interactive elements should maintain contrast in all states
        $interactive_elements = $xpath->query('//button | //a | //input');
        
        foreach ($interactive_elements as $element) {
            $style = $element->getAttribute('style');
            $class = $element->getAttribute('class');
            
            // Check that focus states are not removing outlines completely
            if (!empty($style)) {
                $this->assertStringNotContains(
                    'outline: none',
                    $style,
                    "Interactive elements should not completely remove focus indicators"
                );
                $this->assertStringNotContains(
                    'outline: 0',
                    $style,
                    "Interactive elements should not completely remove focus indicators"
                );
            }
            
            // Interactive elements should have hover/focus state classes
            if ($case['type'] === 'button' || $case['type'] === 'link') {
                $this->assertTrue(
                    strpos($class, 'button') !== false || 
                    strpos($class, 'link') !== false ||
                    $element->tagName === 'button' ||
                    $element->tagName === 'a',
                    "Interactive elements should use proper semantic markup or CSS classes"
                );
            }
        }
    }
    
    /**
     * Extract colors from element styles and classes
     */
    private function extractColorsFromElement($element) {
        $colors = ['foreground' => null, 'background' => null];
        
        // Extract from inline styles
        $style = $element->getAttribute('style');
        if (!empty($style)) {
            if (preg_match('/color:\s*([^;]+)/', $style, $matches)) {
                $colors['foreground'] = trim($matches[1]);
            }
            if (preg_match('/background-color:\s*([^;]+)/', $style, $matches)) {
                $colors['background'] = trim($matches[1]);
            }
        }
        
        // Extract from CSS classes (simplified mapping)
        $class = $element->getAttribute('class');
        if (!empty($class)) {
            $colors = array_merge($colors, $this->getColorsFromClasses($class));
        }
        
        return $colors;
    }
    
    /**
     * Get colors from CSS classes (simplified mapping for testing)
     */
    private function getColorsFromClasses($class_string) {
        $colors = ['foreground' => null, 'background' => null];
        
        // WordPress and ACS color mappings
        $color_mappings = [
            'button-primary' => ['background' => '#2271b1', 'foreground' => '#ffffff'],
            'button-secondary' => ['background' => '#f6f7f7', 'foreground' => '#2c3338'],
            'acs-badge--success' => ['background' => '#00a32a', 'foreground' => '#ffffff'],
            'acs-badge--error' => ['background' => '#d63638', 'foreground' => '#ffffff'],
            'acs-badge--warning' => ['background' => '#dba617', 'foreground' => '#000000'],
            'notice-success' => ['background' => '#00a32a', 'foreground' => '#ffffff'],
            'notice-error' => ['background' => '#d63638', 'foreground' => '#ffffff'],
            'notice-warning' => ['background' => '#dba617', 'foreground' => '#000000']
        ];
        
        foreach ($color_mappings as $class_name => $class_colors) {
            if (strpos($class_string, $class_name) !== false) {
                $colors = array_merge($colors, $class_colors);
                break;
            }
        }
        
        return $colors;
    }
    
    /**
     * Calculate contrast ratio between two colors
     */
    private function calculateContrastRatio($color1, $color2) {
        $luminance1 = $this->getRelativeLuminance($color1);
        $luminance2 = $this->getRelativeLuminance($color2);
        
        $lighter = max($luminance1, $luminance2);
        $darker = min($luminance1, $luminance2);
        
        return ($lighter + 0.05) / ($darker + 0.05);
    }
    
    /**
     * Get relative luminance of a color
     */
    private function getRelativeLuminance($color) {
        $rgb = $this->hexToRgb($color);
        if (!$rgb) {
            return 0.5; // Default middle luminance for unknown colors
        }
        
        $r = $this->linearizeColorComponent($rgb['r'] / 255);
        $g = $this->linearizeColorComponent($rgb['g'] / 255);
        $b = $this->linearizeColorComponent($rgb['b'] / 255);
        
        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }
    
    /**
     * Convert hex color to RGB
     */
    private function hexToRgb($hex) {
        $hex = ltrim($hex, '#');
        
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        
        if (strlen($hex) !== 6) {
            return null;
        }
        
        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2))
        ];
    }
    
    /**
     * Linearize color component for luminance calculation
     */
    private function linearizeColorComponent($component) {
        if ($component <= 0.03928) {
            return $component / 12.92;
        } else {
            return pow(($component + 0.055) / 1.055, 2.4);
        }
    }
    
    /**
     * Check if text is considered large (18pt+ or 14pt+ bold)
     */
    private function isLargeText($element) {
        $style = $element->getAttribute('style');
        $class = $element->getAttribute('class');
        
        // Check for large text indicators in styles or classes
        if (strpos($style, 'font-size') !== false) {
            if (preg_match('/font-size:\s*(\d+)pt/', $style, $matches)) {
                return intval($matches[1]) >= self::LARGE_TEXT_SIZE;
            }
        }
        
        // Check for large text classes
        $large_text_classes = ['large', 'h1', 'h2', 'h3', 'title', 'heading'];
        foreach ($large_text_classes as $large_class) {
            if (strpos($class, $large_class) !== false) {
                return true;
            }
        }
        
        return false;
    }
}

// Run the test
class ColorAccessibilityTestRunner {
    public function run() {
        try {
            $test = new ColorAccessibilityCompliancePropertyTest();
            
            // Use reflection to call protected setUp method
            $reflection = new ReflectionClass($test);
            $setUp = $reflection->getMethod('setUp');
            $setUp->setAccessible(true);
            $setUp->invoke($test);
            
            $test->testColorAccessibilityCompliance();
            echo "✓ Property Test: Color Accessibility Compliance - PASSED\n";
            return true;
        } catch (Exception $e) {
            echo "✗ Property Test: Color Accessibility Compliance - FAILED: " . $e->getMessage() . "\n";
            return false;
        }
    }
}

$runner = new ColorAccessibilityTestRunner();
$success = $runner->run();
if (!$success) {
    exit(1);
}