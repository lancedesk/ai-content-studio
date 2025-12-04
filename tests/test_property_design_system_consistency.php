<?php
/**
 * Property-Based Test: Design System Consistency
 * 
 * **Feature: acs-admin-ui-redesign, Property 3: Design System Consistency**
 * **Validates: Requirements 4.1, 4.2, 4.3, 4.4**
 * 
 * Tests that UI component instances conform to established design tokens
 * for colors, typography, spacing, and interaction patterns.
 *
 * @package AI_Content_Studio
 * @subpackage Tests
 */

class Test_Property_Design_System_Consistency extends WP_UnitTestCase {

    private $component_renderer;
    private $design_tokens;

    public function setUp(): void {
        parent::setUp();
        
        // Initialize component renderer
        require_once ACS_PLUGIN_PATH . 'admin/class-acs-component-renderer.php';
        $this->component_renderer = new ACS_Component_Renderer();
        
        // Define expected design tokens
        $this->design_tokens = [
            'colors' => [
                'primary' => '#0073aa',
                'primary-hover' => '#005a87',
                'success' => '#00a32a',
                'warning' => '#dba617',
                'error' => '#d63638',
                'text' => '#1d2327',
                'text-light' => '#646970',
                'background' => '#ffffff',
                'border' => '#c3c4c7'
            ],
            'spacing' => [
                'xs' => '0.25rem',
                'sm' => '0.5rem', 
                'md' => '1rem',
                'lg' => '1.5rem',
                'xl' => '2rem'
            ],
            'typography' => [
                'font-family' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto',
                'font-size-xs' => '0.75rem',
                'font-size-sm' => '0.875rem',
                'font-size-base' => '1rem',
                'font-size-lg' => '1.125rem'
            ],
            'border-radius' => [
                'sm' => '0.25rem',
                'md' => '0.375rem',
                'lg' => '0.5rem'
            ]
        ];
    }

    /**
     * Property Test: Card components maintain design system consistency
     * 
     * Tests that card components use consistent design tokens regardless
     * of variant, size, or content configuration.
     */
    public function test_card_design_system_consistency() {
        $iterations = 100;
        
        for ($i = 0; $i < $iterations; $i++) {
            // Generate random card configuration
            $card_props = $this->generate_random_card_props();
            
            // Render card component
            $html = $this->component_renderer->render_card($card_props);
            
            // Verify design system consistency
            $this->assert_design_system_compliance($html, 'card', $card_props);
        }
    }

    /**
     * Property Test: Button components maintain design system consistency
     * 
     * Tests that button components use consistent design tokens across
     * all variants, sizes, and states.
     */
    public function test_button_design_system_consistency() {
        $iterations = 100;
        
        for ($i = 0; $i < $iterations; $i++) {
            // Generate random button configuration
            $button_props = $this->generate_random_button_props();
            
            // Render button component
            $html = $this->component_renderer->render_button($button_props);
            
            // Verify design system consistency
            $this->assert_design_system_compliance($html, 'button', $button_props);
        }
    }

    /**
     * Property Test: Form components maintain design system consistency
     * 
     * Tests that form components use consistent design tokens across
     * all field types and validation states.
     */
    public function test_form_design_system_consistency() {
        $iterations = 100;
        
        for ($i = 0; $i < $iterations; $i++) {
            // Generate random form field configuration
            $field_props = $this->generate_random_form_field_props();
            
            // Render form field component
            $html = $this->component_renderer->render_form_field($field_props);
            
            // Verify design system consistency
            $this->assert_design_system_compliance($html, 'form-field', $field_props);
        }
    }

    /**
     * Property Test: Alert components maintain design system consistency
     * 
     * Tests that alert components use consistent design tokens across
     * all types and configurations.
     */
    public function test_alert_design_system_consistency() {
        $iterations = 100;
        
        for ($i = 0; $i < $iterations; $i++) {
            // Generate random alert configuration
            $alert_props = $this->generate_random_alert_props();
            
            // Render alert component
            $html = $this->component_renderer->render_alert($alert_props);
            
            // Verify design system consistency
            $this->assert_design_system_compliance($html, 'alert', $alert_props);
        }
    }

    /**
     * Property Test: Progress components maintain design system consistency
     * 
     * Tests that progress components use consistent design tokens across
     * linear and circular variants.
     */
    public function test_progress_design_system_consistency() {
        $iterations = 100;
        
        for ($i = 0; $i < $iterations; $i++) {
            // Generate random progress configuration
            $progress_props = $this->generate_random_progress_props();
            
            // Render progress component
            $html = $this->component_renderer->render_progress($progress_props);
            
            // Verify design system consistency
            $this->assert_design_system_compliance($html, 'progress', $progress_props);
        }
    }

    /**
     * Generate random card properties for testing
     */
    private function generate_random_card_props() {
        $variants = ['default', 'stat', 'action', 'status', 'activity'];
        $sizes = ['small', 'medium', 'large'];
        
        return [
            'title' => $this->generate_random_string(5, 20),
            'content' => $this->generate_random_string(10, 100),
            'variant' => $variants[array_rand($variants)],
            'size' => $sizes[array_rand($sizes)],
            'loading' => (bool) rand(0, 1),
            'actions' => rand(0, 1) ? $this->generate_random_actions() : []
        ];
    }

    /**
     * Generate random button properties for testing
     */
    private function generate_random_button_props() {
        $variants = ['primary', 'secondary', 'danger', 'ghost'];
        $sizes = ['small', 'medium', 'large'];
        
        return [
            'text' => $this->generate_random_string(3, 15),
            'variant' => $variants[array_rand($variants)],
            'size' => $sizes[array_rand($sizes)],
            'disabled' => (bool) rand(0, 1),
            'loading' => (bool) rand(0, 1),
            'icon' => rand(0, 1) ? 'edit' : ''
        ];
    }

    /**
     * Generate random form field properties for testing
     */
    private function generate_random_form_field_props() {
        $types = ['text', 'email', 'password', 'textarea', 'select', 'checkbox', 'radio'];
        $type = $types[array_rand($types)];
        
        $props = [
            'type' => $type,
            'name' => 'test_field_' . rand(1, 1000),
            'label' => $this->generate_random_string(5, 20),
            'value' => $this->generate_random_string(0, 50),
            'required' => (bool) rand(0, 1),
            'disabled' => (bool) rand(0, 1)
        ];
        
        if ($type === 'select' || $type === 'radio') {
            $props['options'] = $this->generate_random_options();
        }
        
        return $props;
    }

    /**
     * Generate random alert properties for testing
     */
    private function generate_random_alert_props() {
        $types = ['success', 'warning', 'error', 'info'];
        
        return [
            'message' => $this->generate_random_string(10, 100),
            'type' => $types[array_rand($types)],
            'dismissible' => (bool) rand(0, 1),
            'actions' => rand(0, 1) ? $this->generate_random_actions() : []
        ];
    }

    /**
     * Generate random progress properties for testing
     */
    private function generate_random_progress_props() {
        $variants = ['linear', 'circular'];
        $sizes = ['small', 'medium', 'large'];
        
        return [
            'value' => rand(0, 100),
            'max' => 100,
            'variant' => $variants[array_rand($variants)],
            'size' => $sizes[array_rand($sizes)],
            'label' => rand(0, 1) ? $this->generate_random_string(5, 20) : '',
            'show_percentage' => (bool) rand(0, 1)
        ];
    }

    /**
     * Generate random actions for components
     */
    private function generate_random_actions() {
        $count = rand(1, 3);
        $actions = [];
        
        for ($i = 0; $i < $count; $i++) {
            $actions[] = [
                'text' => $this->generate_random_string(3, 10),
                'variant' => ['primary', 'secondary'][array_rand(['primary', 'secondary'])]
            ];
        }
        
        return $actions;
    }

    /**
     * Generate random options for select/radio fields
     */
    private function generate_random_options() {
        $count = rand(2, 5);
        $options = [];
        
        for ($i = 0; $i < $count; $i++) {
            $key = 'option_' . $i;
            $options[$key] = $this->generate_random_string(3, 15);
        }
        
        return $options;
    }

    /**
     * Generate random string of specified length range
     */
    private function generate_random_string($min_length, $max_length) {
        $length = rand($min_length, $max_length);
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789 ';
        $string = '';
        
        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        return trim($string);
    }

    /**
     * Assert that rendered HTML complies with design system
     */
    private function assert_design_system_compliance($html, $component_type, $props) {
        // Verify component has proper CSS classes
        $this->assert_has_design_system_classes($html, $component_type, $props);
        
        // Verify no inline styles that override design tokens
        $this->assert_no_design_token_overrides($html);
        
        // Verify proper BEM methodology usage
        $this->assert_bem_methodology_compliance($html, $component_type);
        
        // Verify accessibility attributes are present
        $this->assert_accessibility_compliance($html, $component_type, $props);
    }

    /**
     * Assert component has proper CSS classes following design system
     */
    private function assert_has_design_system_classes($html, $component_type, $props) {
        // Base component class should be present
        $base_class = 'acs-' . str_replace('_', '-', $component_type);
        $this->assertStringContainsString($base_class, $html, 
            "Component should have base class: {$base_class}");
        
        // Variant classes should follow naming convention
        if (isset($props['variant'])) {
            $variant_class = $base_class . '--' . $props['variant'];
            $this->assertStringContainsString($variant_class, $html,
                "Component should have variant class: {$variant_class}");
        }
        
        // Size classes should follow naming convention
        if (isset($props['size'])) {
            $size_class = $base_class . '--' . $props['size'];
            $this->assertStringContainsString($size_class, $html,
                "Component should have size class: {$size_class}");
        }
        
        // State classes should follow naming convention
        if (isset($props['loading']) && $props['loading']) {
            $loading_class = $base_class . '--loading';
            $this->assertStringContainsString($loading_class, $html,
                "Component should have loading class: {$loading_class}");
        }
        
        if (isset($props['disabled']) && $props['disabled']) {
            $disabled_class = $base_class . '--disabled';
            $this->assertStringContainsString($disabled_class, $html,
                "Component should have disabled class: {$disabled_class}");
        }
    }

    /**
     * Assert no inline styles override design tokens
     */
    private function assert_no_design_token_overrides($html) {
        // Check for inline color overrides
        $color_pattern = '/style="[^"]*color\s*:\s*(?!var\(--acs-)[^;"]+/i';
        $this->assertDoesNotMatchRegularExpression($color_pattern, $html,
            'Components should not use inline colors that override design tokens');
        
        // Check for inline spacing overrides
        $spacing_pattern = '/style="[^"]*(?:margin|padding)\s*:\s*(?!var\(--acs-)[^;"]+/i';
        $this->assertDoesNotMatchRegularExpression($spacing_pattern, $html,
            'Components should not use inline spacing that overrides design tokens');
        
        // Check for inline font overrides
        $font_pattern = '/style="[^"]*font-[^:]*:\s*(?!var\(--acs-)[^;"]+/i';
        $this->assertDoesNotMatchRegularExpression($font_pattern, $html,
            'Components should not use inline fonts that override design tokens');
    }

    /**
     * Assert BEM methodology compliance
     */
    private function assert_bem_methodology_compliance($html, $component_type) {
        $base_class = 'acs-' . str_replace('_', '-', $component_type);
        
        // Check for proper element naming (block__element)
        $element_pattern = '/' . preg_quote($base_class, '/') . '__[a-z][a-z0-9-]*[a-z0-9]/';
        if (preg_match_all($element_pattern, $html, $matches)) {
            foreach ($matches[0] as $element_class) {
                // Element classes should not have double underscores
                $this->assertStringNotContainsString('__', substr($element_class, strpos($element_class, '__') + 2),
                    "BEM element class should not contain double underscores: {$element_class}");
            }
        }
        
        // Check for proper modifier naming (block--modifier or block__element--modifier)
        $modifier_pattern = '/' . preg_quote($base_class, '/') . '(?:__[a-z][a-z0-9-]*)?--[a-z][a-z0-9-]*[a-z0-9]/';
        if (preg_match_all($modifier_pattern, $html, $matches)) {
            foreach ($matches[0] as $modifier_class) {
                // Modifier classes should not have double dashes except for the modifier separator
                $parts = explode('--', $modifier_class);
                $this->assertCount(2, $parts,
                    "BEM modifier class should have exactly one modifier separator: {$modifier_class}");
            }
        }
    }

    /**
     * Assert accessibility compliance
     */
    private function assert_accessibility_compliance($html, $component_type, $props) {
        // Buttons should have proper attributes
        if ($component_type === 'button') {
            if (isset($props['disabled']) && $props['disabled']) {
                $this->assertStringContainsString('disabled=', $html,
                    'Disabled buttons should have disabled attribute');
            }
            
            // Buttons with only icons should have aria-label
            if (isset($props['icon']) && !empty($props['icon']) && empty($props['text'])) {
                $this->assertStringContainsString('aria-label=', $html,
                    'Icon-only buttons should have aria-label');
            }
        }
        
        // Form fields should have proper labels and attributes
        if ($component_type === 'form-field') {
            if (isset($props['required']) && $props['required']) {
                $this->assertStringContainsString('required=', $html,
                    'Required form fields should have required attribute');
            }
            
            if (!empty($props['label'])) {
                $this->assertStringContainsString('<label', $html,
                    'Form fields with labels should have label element');
                $this->assertStringContainsString('for=', $html,
                    'Form field labels should have for attribute');
            }
        }
        
        // Progress indicators should have ARIA attributes
        if ($component_type === 'progress') {
            $this->assertStringContainsString('role=', $html,
                'Progress indicators should have role attribute');
        }
        
        // Alerts should have proper ARIA attributes
        if ($component_type === 'alert') {
            $this->assertStringContainsString('role=', $html,
                'Alerts should have role attribute');
        }
    }
}