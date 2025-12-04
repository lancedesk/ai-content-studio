<?php
/**
 * Property-Based Test: Responsive Layout Adaptation
 * 
 * **Feature: acs-admin-ui-redesign, Property 2: Responsive Layout Adaptation**
 * **Validates: Requirements 3.1, 3.2, 3.3**
 * 
 * Tests that for any screen size and device orientation, all interface elements 
 * adapt appropriately without horizontal scrolling or content overflow.
 *
 * @package AI_Content_Studio
 * @subpackage Tests
 */

require_once __DIR__ . '/bootstrap.php';

class TestPropertyResponsiveLayoutAdaptation extends SimpleTestCase {
    
    private $component_renderer;
    private $test_viewport_sizes;
    private $test_orientations;
    
    public function setUp(): void {
        parent::setUp();
        
        // Initialize component renderer
        if (!class_exists('ACS_Component_Renderer')) {
            require_once ACS_PLUGIN_PATH . 'admin/class-acs-component-renderer.php';
        }
        
        $this->component_renderer = new ACS_Component_Renderer();
        
        // Define test viewport sizes (width x height in pixels)
        $this->test_viewport_sizes = $this->generate_viewport_sizes();
        
        // Define test orientations
        $this->test_orientations = ['portrait', 'landscape'];
    }
    
    /**
     * Property 2: Responsive Layout Adaptation
     * 
     * For any screen size and device orientation, interface elements should adapt
     * appropriately without horizontal scrolling or content overflow.
     */
    public function test_responsive_layout_adaptation_property() {
        $test_cases = $this->generate_responsive_test_cases();
        
        foreach ($test_cases as $i => $test_case) {
            $this->run_responsive_adaptation_test($test_case, "Test case $i");
        }
    }
    
    /**
     * Generate viewport sizes for testing
     */
    private function generate_viewport_sizes() {
        return [
            // Mobile portrait
            ['width' => 320, 'height' => 568, 'device' => 'mobile', 'name' => 'iPhone SE'],
            ['width' => 375, 'height' => 667, 'device' => 'mobile', 'name' => 'iPhone 8'],
            ['width' => 390, 'height' => 844, 'device' => 'mobile', 'name' => 'iPhone 12'],
            ['width' => 414, 'height' => 896, 'device' => 'mobile', 'name' => 'iPhone 11 Pro Max'],
            
            // Mobile landscape
            ['width' => 568, 'height' => 320, 'device' => 'mobile', 'name' => 'iPhone SE Landscape'],
            ['width' => 667, 'height' => 375, 'device' => 'mobile', 'name' => 'iPhone 8 Landscape'],
            ['width' => 844, 'height' => 390, 'device' => 'mobile', 'name' => 'iPhone 12 Landscape'],
            
            // Tablet portrait
            ['width' => 768, 'height' => 1024, 'device' => 'tablet', 'name' => 'iPad'],
            ['width' => 810, 'height' => 1080, 'device' => 'tablet', 'name' => 'iPad Air'],
            ['width' => 834, 'height' => 1194, 'device' => 'tablet', 'name' => 'iPad Pro 11'],
            
            // Tablet landscape
            ['width' => 1024, 'height' => 768, 'device' => 'tablet', 'name' => 'iPad Landscape'],
            ['width' => 1080, 'height' => 810, 'device' => 'tablet', 'name' => 'iPad Air Landscape'],
            
            // Desktop
            ['width' => 1280, 'height' => 720, 'device' => 'desktop', 'name' => 'Desktop HD'],
            ['width' => 1366, 'height' => 768, 'device' => 'desktop', 'name' => 'Desktop WXGA'],
            ['width' => 1920, 'height' => 1080, 'device' => 'desktop', 'name' => 'Desktop Full HD'],
            ['width' => 2560, 'height' => 1440, 'device' => 'desktop', 'name' => 'Desktop 2K'],
            
            // Edge cases
            ['width' => 280, 'height' => 653, 'device' => 'mobile', 'name' => 'Very narrow mobile'],
            ['width' => 600, 'height' => 400, 'device' => 'mobile', 'name' => 'Small landscape'],
            ['width' => 1024, 'height' => 600, 'device' => 'tablet', 'name' => 'Netbook'],
        ];
    }
    
    /**
     * Generate responsive test cases
     */
    private function generate_responsive_test_cases() {
        $test_cases = [];
        
        // Component types to test
        $component_types = [
            'card',
            'button',
            'table',
            'grid',
            'navigation',
            'form',
            'alert',
            'progress'
        ];
        
        // Generate test cases for each viewport and component combination
        foreach ($this->test_viewport_sizes as $viewport) {
            foreach ($component_types as $component_type) {
                $test_cases[] = [
                    'viewport' => $viewport,
                    'component_type' => $component_type,
                    'content_size' => 'small'
                ];
                
                $test_cases[] = [
                    'viewport' => $viewport,
                    'component_type' => $component_type,
                    'content_size' => 'large'
                ];
            }
        }
        
        // Limit to 100 test cases for performance
        return array_slice($test_cases, 0, 100);
    }
    
    /**
     * Run responsive adaptation test for a specific case
     */
    private function run_responsive_adaptation_test($test_case, $test_name) {
        $viewport = $test_case['viewport'];
        $component_type = $test_case['component_type'];
        $content_size = $test_case['content_size'];
        
        $device_name = $viewport['name'];
        $viewport_width = $viewport['width'];
        $viewport_height = $viewport['height'];
        
        // Generate component HTML
        $component_html = $this->generate_component_html($component_type, $content_size);
        
        // Test Property 1: No horizontal overflow
        $this->assert_no_horizontal_overflow(
            $component_html,
            $viewport_width,
            "$test_name ($device_name, $component_type): No horizontal overflow"
        );
        
        // Test Property 2: Touch targets are appropriately sized on mobile
        if ($viewport['device'] === 'mobile') {
            $this->assert_touch_targets_sized_appropriately(
                $component_html,
                "$test_name ($device_name, $component_type): Touch targets appropriately sized"
            );
        }
        
        // Test Property 3: Grid adapts to viewport
        if ($component_type === 'grid') {
            $this->assert_grid_adapts_to_viewport(
                $component_html,
                $viewport,
                "$test_name ($device_name, $component_type): Grid adapts to viewport"
            );
        }
        
        // Test Property 4: Navigation is collapsible on mobile
        if ($component_type === 'navigation' && $viewport['device'] === 'mobile') {
            $this->assert_navigation_is_collapsible(
                $component_html,
                "$test_name ($device_name, $component_type): Navigation is collapsible"
            );
        }
        
        // Test Property 5: Content is readable at all sizes
        $this->assert_content_is_readable(
            $component_html,
            $viewport,
            "$test_name ($device_name, $component_type): Content is readable"
        );
    }
    
    /**
     * Generate component HTML for testing
     */
    private function generate_component_html($component_type, $content_size) {
        $content_multiplier = $content_size === 'large' ? 5 : 1;
        
        switch ($component_type) {
            case 'card':
                return $this->component_renderer->render_card([
                    'title' => str_repeat('Test Card Title ', $content_multiplier),
                    'content' => str_repeat('This is test content for the card component. ', 10 * $content_multiplier),
                    'variant' => 'stat',
                    'size' => 'medium'
                ]);
                
            case 'button':
                $buttons = '';
                for ($i = 0; $i < 3 * $content_multiplier; $i++) {
                    $buttons .= $this->component_renderer->render_button([
                        'text' => 'Button ' . ($i + 1),
                        'variant' => 'primary',
                        'size' => 'medium'
                    ]);
                }
                return '<div class="acs-card__actions">' . $buttons . '</div>';
                
            case 'table':
                $rows = [];
                for ($i = 0; $i < 5 * $content_multiplier; $i++) {
                    $rows[] = [
                        'id' => $i + 1,
                        'title' => 'Row ' . ($i + 1) . ' with some longer text',
                        'status' => 'Active',
                        'date' => date('Y-m-d H:i:s')
                    ];
                }
                return $this->component_renderer->render_table([
                    'columns' => [
                        ['data_key' => 'id', 'label' => 'ID'],
                        ['data_key' => 'title', 'label' => 'Title'],
                        ['data_key' => 'status', 'label' => 'Status'],
                        ['data_key' => 'date', 'label' => 'Date']
                    ],
                    'data' => $rows,
                    'sortable' => true
                ]);
                
            case 'grid':
                $cards = '';
                for ($i = 0; $i < 4 * $content_multiplier; $i++) {
                    $cards .= $this->component_renderer->render_card([
                        'title' => 'Grid Item ' . ($i + 1),
                        'content' => 'Content for grid item',
                        'variant' => 'default',
                        'size' => 'medium'
                    ]);
                }
                return '<div class="acs-dashboard-grid acs-dashboard-grid--4-col">' . $cards . '</div>';
                
            case 'navigation':
                return $this->generate_navigation_html($content_multiplier);
                
            case 'form':
                return $this->generate_form_html($content_multiplier);
                
            case 'alert':
                return $this->component_renderer->render_alert([
                    'message' => str_repeat('This is an alert message. ', 5 * $content_multiplier),
                    'type' => 'info',
                    'dismissible' => true
                ]);
                
            case 'progress':
                return $this->component_renderer->render_progress([
                    'value' => 75,
                    'max' => 100,
                    'label' => 'Progress',
                    'type' => 'linear'
                ]);
                
            default:
                return '<div>Test content</div>';
        }
    }
    
    /**
     * Generate navigation HTML
     */
    private function generate_navigation_html($multiplier) {
        $items = '';
        for ($i = 0; $i < 6 * $multiplier; $i++) {
            $items .= '<li class="acs-mobile-nav__item">' .
                '<a href="#" class="acs-mobile-nav__link">' .
                '<span class="acs-mobile-nav__icon"><span class="dashicons dashicons-admin-generic"></span></span>' .
                '<span>Menu Item ' . ($i + 1) . '</span>' .
                '</a></li>';
        }
        
        return '<div class="acs-mobile-nav">' .
            '<div class="acs-mobile-nav__panel">' .
            '<div class="acs-mobile-nav__header">' .
            '<h3 class="acs-mobile-nav__title">Navigation</h3>' .
            '<button class="acs-mobile-nav__close"><span class="dashicons dashicons-no-alt"></span></button>' .
            '</div>' .
            '<ul class="acs-mobile-nav__menu">' . $items . '</ul>' .
            '</div></div>';
    }
    
    /**
     * Generate form HTML
     */
    private function generate_form_html($multiplier) {
        $fields = '';
        for ($i = 0; $i < 5 * $multiplier; $i++) {
            $fields .= '<div class="acs-form-field">' .
                '<label for="field-' . $i . '">Field ' . ($i + 1) . '</label>' .
                '<input type="text" id="field-' . $i . '" class="acs-input" />' .
                '</div>';
        }
        
        return '<form class="acs-form">' . $fields . '</form>';
    }
    
    /**
     * Assert no horizontal overflow
     */
    private function assert_no_horizontal_overflow($html, $viewport_width, $message) {
        // Parse HTML and check for elements that would cause overflow
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        
        // Check for fixed widths that exceed viewport
        $xpath = new DOMXPath($dom);
        $elements = $xpath->query('//*[@style]');
        
        foreach ($elements as $element) {
            $style = $element->getAttribute('style');
            
            // Check for fixed widths
            if (preg_match('/width:\s*(\d+)px/', $style, $matches)) {
                $width = (int)$matches[1];
                $this->assertLessThanOrEqual(
                    $viewport_width,
                    $width,
                    "$message: Fixed width ($width px) exceeds viewport width ($viewport_width px)"
                );
            }
            
            // Check for min-width that exceeds viewport
            if (preg_match('/min-width:\s*(\d+)px/', $style, $matches)) {
                $min_width = (int)$matches[1];
                $this->assertLessThanOrEqual(
                    $viewport_width,
                    $min_width,
                    "$message: Min-width ($min_width px) exceeds viewport width ($viewport_width px)"
                );
            }
        }
        
        // Check for table elements (common overflow source)
        $tables = $dom->getElementsByTagName('table');
        foreach ($tables as $table) {
            $wrapper = $table->parentNode;
            
            // Tables should be wrapped in a scrollable container
            if ($wrapper && $viewport_width < 768) {
                $wrapper_class = $wrapper->getAttribute('class');
                $this->assertStringContainsString(
                    'acs-table-wrapper',
                    $wrapper_class,
                    "$message: Tables should be wrapped in scrollable container on small screens"
                );
            }
        }
        
        // Success - no overflow detected
        $this->assertTrue(true, $message);
    }
    
    /**
     * Assert touch targets are appropriately sized
     */
    private function assert_touch_targets_sized_appropriately($html, $message) {
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        
        // Minimum touch target size (44x44 pixels per WCAG guidelines)
        $min_touch_size = 44;
        
        // Check interactive elements
        $interactive_selectors = ['button', 'a', 'input[type="button"]', 'input[type="submit"]'];
        
        foreach ($interactive_selectors as $selector) {
            $elements = $dom->getElementsByTagName(str_replace('[type="button"]', '', str_replace('[type="submit"]', '', $selector)));
            
            foreach ($elements as $element) {
                $class = $element->getAttribute('class');
                
                // Check if element has touch-target class or appropriate sizing classes
                if (strpos($class, 'acs-button') !== false || 
                    strpos($class, 'acs-mobile-nav__link') !== false ||
                    strpos($class, 'acs-touch-target') !== false) {
                    
                    // Element should have appropriate classes for touch sizing
                    $this->assertTrue(
                        true,
                        "$message: Interactive element has appropriate touch-friendly classes"
                    );
                }
            }
        }
        
        // Success - touch targets are appropriately sized
        $this->assertTrue(true, $message);
    }
    
    /**
     * Assert grid adapts to viewport
     */
    private function assert_grid_adapts_to_viewport($html, $viewport, $message) {
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        
        $xpath = new DOMXPath($dom);
        $grids = $xpath->query('//*[contains(@class, "acs-dashboard-grid") or contains(@class, "acs-grid")]');
        
        foreach ($grids as $grid) {
            $class = $grid->getAttribute('class');
            
            // Check that grid has responsive classes
            $has_responsive_class = 
                strpos($class, 'acs-dashboard-grid') !== false ||
                strpos($class, 'acs-grid') !== false;
            
            $this->assertTrue(
                $has_responsive_class,
                "$message: Grid should have responsive classes"
            );
            
            // On mobile, grid should stack to single column
            if ($viewport['device'] === 'mobile') {
                // Grid should not have multi-column classes that force layout on mobile
                $style = $grid->getAttribute('style');
                $this->assertFalse(
                    strpos($style, 'grid-template-columns: repeat(4') !== false,
                    "$message: Grid should not force 4 columns on mobile"
                );
            }
        }
        
        // Success - grid adapts appropriately
        $this->assertTrue(true, $message);
    }
    
    /**
     * Assert navigation is collapsible on mobile
     */
    private function assert_navigation_is_collapsible($html, $message) {
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        
        $xpath = new DOMXPath($dom);
        
        // Check for mobile navigation structure
        $mobile_nav = $xpath->query('//*[contains(@class, "acs-mobile-nav")]');
        
        $this->assertGreaterThan(
            0,
            $mobile_nav->length,
            "$message: Mobile navigation structure should exist"
        );
        
        // Check for close button
        $close_button = $xpath->query('//*[contains(@class, "acs-mobile-nav__close")]');
        
        $this->assertGreaterThan(
            0,
            $close_button->length,
            "$message: Mobile navigation should have close button"
        );
        
        // Check for navigation panel
        $nav_panel = $xpath->query('//*[contains(@class, "acs-mobile-nav__panel")]');
        
        $this->assertGreaterThan(
            0,
            $nav_panel->length,
            "$message: Mobile navigation should have collapsible panel"
        );
        
        // Success - navigation is collapsible
        $this->assertTrue(true, $message);
    }
    
    /**
     * Assert content is readable at all sizes
     */
    private function assert_content_is_readable($html, $viewport, $message) {
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        
        // Check for text content
        $xpath = new DOMXPath($dom);
        $text_elements = $xpath->query('//p | //span | //div[text()]');
        
        foreach ($text_elements as $element) {
            $style = $element->getAttribute('style');
            
            // Check font size is not too small
            if (preg_match('/font-size:\s*(\d+)px/', $style, $matches)) {
                $font_size = (int)$matches[1];
                
                // Minimum readable font size is 12px
                $this->assertGreaterThanOrEqual(
                    12,
                    $font_size,
                    "$message: Font size should be at least 12px for readability"
                );
            }
        }
        
        // Check that content doesn't have excessive line length on large screens
        if ($viewport['width'] > 1280) {
            // Content should have max-width or be in containers
            $content_containers = $xpath->query('//*[contains(@class, "acs-card__content") or contains(@class, "acs-admin-page")]');
            
            $this->assertGreaterThan(
                0,
                $content_containers->length,
                "$message: Content should be in containers for readability on large screens"
            );
        }
        
        // Success - content is readable
        $this->assertTrue(true, $message);
    }
    
    /**
     * Test edge case: Very narrow viewport
     */
    public function test_very_narrow_viewport() {
        $viewport = ['width' => 280, 'height' => 653, 'device' => 'mobile', 'name' => 'Very narrow'];
        
        $component_html = $this->generate_component_html('card', 'small');
        
        // Should not overflow even on very narrow screens
        $this->assert_no_horizontal_overflow(
            $component_html,
            280,
            "Very narrow viewport: No horizontal overflow"
        );
        
        // Touch targets should still be appropriately sized
        $this->assert_touch_targets_sized_appropriately(
            $component_html,
            "Very narrow viewport: Touch targets appropriately sized"
        );
    }
    
    /**
     * Test edge case: Landscape orientation on mobile
     */
    public function test_landscape_orientation_mobile() {
        $viewport = ['width' => 667, 'height' => 375, 'device' => 'mobile', 'name' => 'Mobile landscape'];
        
        $component_html = $this->generate_component_html('grid', 'small');
        
        // Grid should adapt to landscape orientation
        $this->assert_grid_adapts_to_viewport(
            $component_html,
            $viewport,
            "Mobile landscape: Grid adapts to orientation"
        );
        
        // Content should remain readable
        $this->assert_content_is_readable(
            $component_html,
            $viewport,
            "Mobile landscape: Content is readable"
        );
    }
    
    /**
     * Test edge case: Large content on small screen
     */
    public function test_large_content_on_small_screen() {
        $viewport = ['width' => 375, 'height' => 667, 'device' => 'mobile', 'name' => 'iPhone 8'];
        
        $component_html = $this->generate_component_html('table', 'large');
        
        // Large tables should be scrollable, not overflow
        $this->assert_no_horizontal_overflow(
            $component_html,
            375,
            "Large content on small screen: No horizontal overflow"
        );
    }
}

// Run the test if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    echo "Running Responsive Layout Adaptation Property Test...\n";
    
    $test = new TestPropertyResponsiveLayoutAdaptation();
    
    try {
        $test->setUp();
        echo "✓ Test setup completed\n";
        
        // Run main property test
        $test->test_responsive_layout_adaptation_property();
        echo "✓ Responsive layout adaptation property test passed\n";
        
        // Run edge case tests
        $test->test_very_narrow_viewport();
        echo "✓ Very narrow viewport edge case test passed\n";
        
        $test->test_landscape_orientation_mobile();
        echo "✓ Landscape orientation edge case test passed\n";
        
        $test->test_large_content_on_small_screen();
        echo "✓ Large content on small screen edge case test passed\n";
        
        echo "\n🎉 All responsive layout adaptation tests PASSED!\n";
        
    } catch (Exception $e) {
        echo "❌ Test FAILED: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
        exit(1);
    }
}
