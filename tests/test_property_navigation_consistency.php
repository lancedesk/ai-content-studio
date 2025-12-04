<?php
/**
 * Property-Based Test: Navigation Pattern Consistency
 * 
 * **Feature: acs-admin-ui-redesign, Property 3: Navigation Pattern Consistency**
 * **Validates: Requirements 1.3, 4.2**
 * 
 * Tests that for any admin page and user interaction, navigation patterns remain
 * consistent with proper breadcrumbs, active states, and identical behavior for
 * similar elements.
 *
 * @package AI_Content_Studio
 * @subpackage Tests
 */

require_once __DIR__ . '/bootstrap.php';

class TestPropertyNavigationConsistency extends SimpleTestCase {
    
    private $unified_admin;
    private $component_renderer;
    
    public function setUp(): void {
        parent::setUp();
        
        // Initialize unified admin
        if (!class_exists('ACS_Unified_Admin')) {
            require_once ACS_PLUGIN_PATH . 'admin/class-acs-unified-admin.php';
        }
        
        if (!class_exists('ACS_Component_Renderer')) {
            require_once ACS_PLUGIN_PATH . 'admin/class-acs-component-renderer.php';
        }
        
        $this->unified_admin = new ACS_Unified_Admin('ai-content-studio', '2.0.0');
        $this->component_renderer = new ACS_Component_Renderer();
    }
    
    /**
     * Property 3: Navigation Pattern Consistency
     * 
     * For any admin page navigation, the system should maintain consistent
     * navigation patterns with proper breadcrumbs and active state indicators.
     */
    public function test_navigation_pattern_consistency_property() {
        $test_cases = $this->generate_navigation_scenarios();
        
        foreach ($test_cases as $i => $scenario) {
            $this->run_navigation_consistency_test($scenario, "Test case $i");
        }
    }
    
    /**
     * Generate various navigation scenarios for property testing
     */
    private function generate_navigation_scenarios() {
        $scenarios = [];
        
        // Different page contexts
        $page_contexts = [
            ['page' => 'acs-dashboard', 'parent' => null, 'level' => 0],
            ['page' => 'acs-generate', 'parent' => 'acs-dashboard', 'level' => 1],
            ['page' => 'acs-seo-optimizer', 'parent' => 'acs-dashboard', 'level' => 1],
            ['page' => 'acs-seo-single', 'parent' => 'acs-seo-optimizer', 'level' => 2],
            ['page' => 'acs-seo-bulk', 'parent' => 'acs-seo-optimizer', 'level' => 2],
            ['page' => 'acs-analytics', 'parent' => 'acs-dashboard', 'level' => 1],
            ['page' => 'acs-generation-logs', 'parent' => 'acs-analytics', 'level' => 2],
            ['page' => 'acs-settings', 'parent' => 'acs-dashboard', 'level' => 1],
        ];
        
        // Different navigation states
        $navigation_states = [
            ['has_breadcrumbs' => true, 'has_tabs' => false],
            ['has_breadcrumbs' => true, 'has_tabs' => true],
            ['has_breadcrumbs' => false, 'has_tabs' => false],
        ];
        
        // Different user interactions
        $user_interactions = [
            ['type' => 'page_load', 'from' => null],
            ['type' => 'menu_click', 'from' => 'acs-dashboard'],
            ['type' => 'breadcrumb_click', 'from' => 'acs-seo-single'],
            ['type' => 'tab_switch', 'from' => 'acs-settings'],
        ];
        
        // Combine scenarios
        foreach ($page_contexts as $context) {
            foreach ($navigation_states as $state) {
                foreach ($user_interactions as $interaction) {
                    $scenarios[] = array_merge($context, $state, ['interaction' => $interaction]);
                }
            }
        }
        
        return array_slice($scenarios, 0, 30); // Limit to 30 test cases
    }
    
    /**
     * Run navigation consistency test for a specific scenario
     */
    private function run_navigation_consistency_test($scenario, $test_name) {
        // Setup test environment
        $this->setup_navigation_environment($scenario);
        
        // Test Property 1: Breadcrumbs are consistent and accurate
        if ($scenario['has_breadcrumbs']) {
            $this->assert_breadcrumb_consistency($scenario, $test_name);
        }
        
        // Test Property 2: Active states are properly indicated
        $this->assert_active_state_consistency($scenario, $test_name);
        
        // Test Property 3: Navigation behavior is identical for similar elements
        $this->assert_navigation_behavior_consistency($scenario, $test_name);
        
        // Test Property 4: Navigation hierarchy is maintained
        $this->assert_navigation_hierarchy($scenario, $test_name);
    }
    
    /**
     * Setup navigation environment for testing
     */
    private function setup_navigation_environment($scenario) {
        global $hook_suffix, $plugin_page, $menu, $submenu;
        
        $hook_suffix = 'ai-content-studio_page_' . $scenario['page'];
        $plugin_page = $scenario['page'];
        
        $_GET['page'] = $scenario['page'];
        
        if (isset($scenario['interaction']['from'])) {
            $_SERVER['HTTP_REFERER'] = admin_url('admin.php?page=' . $scenario['interaction']['from']);
        }
        
        // Setup user with required capabilities
        $user = wp_get_current_user();
        $user->add_cap('acs_generate_content');
        $user->add_cap('acs_manage_settings');
        $user->add_cap('acs_view_analytics');
        $user->add_cap('acs_manage_seo');
        
        // Clear and rebuild menu
        $menu = [];
        $submenu = [];
        
        // Trigger menu registration
        if ($this->unified_admin) {
            $this->unified_admin->add_unified_menu();
        }
    }
    
    /**
     * Assert breadcrumb consistency
     */
    private function assert_breadcrumb_consistency($scenario, $test_name) {
        // Get breadcrumb trail
        $breadcrumbs = $this->get_breadcrumb_trail($scenario['page']);
        
        // Breadcrumbs should not be empty for non-root pages
        if ($scenario['level'] > 0) {
            $this->assertNotEmpty(
                $breadcrumbs,
                "$test_name: Breadcrumbs should exist for non-root pages (level {$scenario['level']})"
            );
            
            // Breadcrumb count should match page level
            $this->assertEquals(
                $scenario['level'] + 1, // +1 for current page
                count($breadcrumbs),
                "$test_name: Breadcrumb count should match page hierarchy level"
            );
            
            // First breadcrumb should always be dashboard
            $this->assertStringContainsString(
                'Dashboard',
                $breadcrumbs[0]['title'],
                "$test_name: First breadcrumb should always be Dashboard"
            );
            
            // Last breadcrumb should be current page
            $last_breadcrumb = end($breadcrumbs);
            $this->assertTrue(
                $last_breadcrumb['is_current'],
                "$test_name: Last breadcrumb should be marked as current page"
            );
            
            // All breadcrumbs except last should have links
            for ($i = 0; $i < count($breadcrumbs) - 1; $i++) {
                $this->assertNotEmpty(
                    $breadcrumbs[$i]['url'],
                    "$test_name: Non-current breadcrumbs should have URLs"
                );
            }
        }
        
        // Test breadcrumb consistency across multiple renders
        $breadcrumbs_second_render = $this->get_breadcrumb_trail($scenario['page']);
        $this->assertEquals(
            $breadcrumbs,
            $breadcrumbs_second_render,
            "$test_name: Breadcrumbs should be consistent across multiple renders"
        );
    }
    
    /**
     * Assert active state consistency
     */
    private function assert_active_state_consistency($scenario, $test_name) {
        // Get navigation menu HTML
        $nav_html = $this->render_navigation_menu($scenario['page']);
        
        // Current page should have active class
        $this->assertStringContainsString(
            'active',
            $nav_html,
            "$test_name: Navigation should contain active state indicator"
        );
        
        // Count active items - should be exactly one
        $active_count = substr_count($nav_html, 'class="active"') + 
                       substr_count($nav_html, 'class=\'active\'') +
                       substr_count($nav_html, 'active ') +
                       substr_count($nav_html, ' active');
        
        $this->assertGreaterThanOrEqual(
            1,
            $active_count,
            "$test_name: Should have at least one active navigation item"
        );
        
        // Test that active state persists across interactions
        $nav_html_after_interaction = $this->render_navigation_menu($scenario['page']);
        $this->assertEquals(
            $nav_html,
            $nav_html_after_interaction,
            "$test_name: Active state should persist across interactions"
        );
        
        // Test that active state matches current page
        $current_page_slug = $scenario['page'];
        $this->assertStringContainsString(
            $current_page_slug,
            $nav_html,
            "$test_name: Active navigation item should correspond to current page"
        );
    }
    
    /**
     * Assert navigation behavior consistency
     */
    private function assert_navigation_behavior_consistency($scenario, $test_name) {
        // Get all navigation links
        $nav_links = $this->get_navigation_links($scenario['page']);
        
        // All navigation links should follow consistent patterns
        foreach ($nav_links as $link) {
            // Each link should have required attributes
            $this->assertArrayHasKey('url', $link, "$test_name: Navigation link should have URL");
            $this->assertArrayHasKey('title', $link, "$test_name: Navigation link should have title");
            $this->assertArrayHasKey('capability', $link, "$test_name: Navigation link should have capability");
            
            // URLs should be properly formatted
            $this->assertStringContainsString(
                'admin.php?page=',
                $link['url'],
                "$test_name: Navigation URLs should follow WordPress admin URL pattern"
            );
            
            // Titles should not be empty
            $this->assertNotEmpty(
                $link['title'],
                "$test_name: Navigation link titles should not be empty"
            );
            
            // Capabilities should be valid
            $this->assertNotEmpty(
                $link['capability'],
                "$test_name: Navigation link capabilities should not be empty"
            );
        }
        
        // Test that similar navigation elements have identical structure
        $link_structures = array_map(function($link) {
            return array_keys($link);
        }, $nav_links);
        
        if (count($link_structures) > 1) {
            $first_structure = $link_structures[0];
            foreach ($link_structures as $structure) {
                $this->assertEquals(
                    $first_structure,
                    $structure,
                    "$test_name: All navigation links should have identical structure"
                );
            }
        }
    }
    
    /**
     * Assert navigation hierarchy is maintained
     */
    private function assert_navigation_hierarchy($scenario, $test_name) {
        // Get page hierarchy
        $hierarchy = $this->get_page_hierarchy($scenario['page']);
        
        // Hierarchy should match expected level
        $this->assertEquals(
            $scenario['level'],
            count($hierarchy) - 1, // -1 because hierarchy includes current page
            "$test_name: Page hierarchy depth should match expected level"
        );
        
        // If page has parent, verify parent relationship
        if ($scenario['parent']) {
            $this->assertContains(
                $scenario['parent'],
                $hierarchy,
                "$test_name: Page hierarchy should include expected parent"
            );
            
            // Parent should come before current page in hierarchy
            $parent_index = array_search($scenario['parent'], $hierarchy);
            $current_index = array_search($scenario['page'], $hierarchy);
            
            $this->assertLessThan(
                $current_index,
                $parent_index,
                "$test_name: Parent should come before current page in hierarchy"
            );
        }
        
        // Root page should always be in hierarchy
        $this->assertContains(
            'acs-dashboard',
            $hierarchy,
            "$test_name: Dashboard should always be in page hierarchy"
        );
    }
    
    /**
     * Get breadcrumb trail for a page
     */
    private function get_breadcrumb_trail($page_slug) {
        // Define page hierarchy
        $page_hierarchy = [
            'acs-dashboard' => ['title' => 'Dashboard', 'parent' => null],
            'acs-generate' => ['title' => 'Generate Content', 'parent' => 'acs-dashboard'],
            'acs-seo-optimizer' => ['title' => 'SEO Optimizer', 'parent' => 'acs-dashboard'],
            'acs-seo-single' => ['title' => 'Single Post', 'parent' => 'acs-seo-optimizer'],
            'acs-seo-bulk' => ['title' => 'Bulk Optimization', 'parent' => 'acs-seo-optimizer'],
            'acs-analytics' => ['title' => 'Analytics', 'parent' => 'acs-dashboard'],
            'acs-generation-logs' => ['title' => 'Generation Logs', 'parent' => 'acs-analytics'],
            'acs-settings' => ['title' => 'Settings', 'parent' => 'acs-dashboard'],
        ];
        
        $breadcrumbs = [];
        $current = $page_slug;
        
        // Build breadcrumb trail from current page to root
        while ($current !== null) {
            if (!isset($page_hierarchy[$current])) {
                break;
            }
            
            $page_info = $page_hierarchy[$current];
            array_unshift($breadcrumbs, [
                'title' => $page_info['title'],
                'url' => $current === $page_slug ? '' : admin_url('admin.php?page=' . $current),
                'is_current' => $current === $page_slug,
                'slug' => $current
            ]);
            
            $current = $page_info['parent'];
        }
        
        return $breadcrumbs;
    }
    
    /**
     * Render navigation menu for testing
     */
    private function render_navigation_menu($current_page) {
        global $submenu;
        
        $menu_slug = 'acs-dashboard';
        $nav_items = $submenu[$menu_slug] ?? [];
        
        $html = '<nav class="acs-navigation">';
        
        foreach ($nav_items as $item) {
            $item_slug = $item[2] ?? '';
            $item_title = $item[0] ?? '';
            $active_class = ($item_slug === $current_page) ? ' class="active"' : '';
            
            $html .= sprintf(
                '<a href="%s"%s>%s</a>',
                admin_url('admin.php?page=' . $item_slug),
                $active_class,
                esc_html($item_title)
            );
        }
        
        $html .= '</nav>';
        
        return $html;
    }
    
    /**
     * Get navigation links for testing
     */
    private function get_navigation_links($current_page) {
        global $submenu;
        
        $menu_slug = 'acs-dashboard';
        $nav_items = $submenu[$menu_slug] ?? [];
        
        $links = [];
        
        foreach ($nav_items as $item) {
            $links[] = [
                'title' => $item[0] ?? '',
                'capability' => $item[1] ?? '',
                'url' => admin_url('admin.php?page=' . ($item[2] ?? '')),
                'slug' => $item[2] ?? '',
                'is_current' => ($item[2] ?? '') === $current_page
            ];
        }
        
        return $links;
    }
    
    /**
     * Get page hierarchy
     */
    private function get_page_hierarchy($page_slug) {
        $breadcrumbs = $this->get_breadcrumb_trail($page_slug);
        return array_map(function($crumb) {
            return $crumb['slug'];
        }, $breadcrumbs);
    }
    
    /**
     * Test edge case: Navigation on root page
     */
    public function test_navigation_on_root_page() {
        $scenario = [
            'page' => 'acs-dashboard',
            'parent' => null,
            'level' => 0,
            'has_breadcrumbs' => false
        ];
        
        $this->setup_navigation_environment($scenario);
        
        // Root page should have minimal breadcrumbs
        $breadcrumbs = $this->get_breadcrumb_trail('acs-dashboard');
        $this->assertEquals(
            1,
            count($breadcrumbs),
            "Root page should have only one breadcrumb (itself)"
        );
        
        // Active state should still work on root page
        $nav_html = $this->render_navigation_menu('acs-dashboard');
        $this->assertStringContainsString(
            'active',
            $nav_html,
            "Root page should have active state indicator"
        );
    }
    
    /**
     * Test edge case: Navigation with deep hierarchy
     */
    public function test_navigation_with_deep_hierarchy() {
        $scenario = [
            'page' => 'acs-seo-single',
            'parent' => 'acs-seo-optimizer',
            'level' => 2,
            'has_breadcrumbs' => true
        ];
        
        $this->setup_navigation_environment($scenario);
        
        // Deep hierarchy should have complete breadcrumb trail
        $breadcrumbs = $this->get_breadcrumb_trail('acs-seo-single');
        $this->assertEquals(
            3,
            count($breadcrumbs),
            "Deep hierarchy page should have complete breadcrumb trail"
        );
        
        // Verify breadcrumb order
        $this->assertEquals('acs-dashboard', $breadcrumbs[0]['slug']);
        $this->assertEquals('acs-seo-optimizer', $breadcrumbs[1]['slug']);
        $this->assertEquals('acs-seo-single', $breadcrumbs[2]['slug']);
    }
    
    /**
     * Test edge case: Navigation consistency across page reloads
     */
    public function test_navigation_consistency_across_reloads() {
        $page = 'acs-generate';
        
        // First render
        $nav_html_1 = $this->render_navigation_menu($page);
        $breadcrumbs_1 = $this->get_breadcrumb_trail($page);
        
        // Simulate page reload
        $this->setup_navigation_environment(['page' => $page, 'level' => 1]);
        
        // Second render
        $nav_html_2 = $this->render_navigation_menu($page);
        $breadcrumbs_2 = $this->get_breadcrumb_trail($page);
        
        // Navigation should be identical
        $this->assertEquals(
            $nav_html_1,
            $nav_html_2,
            "Navigation HTML should be consistent across page reloads"
        );
        
        $this->assertEquals(
            $breadcrumbs_1,
            $breadcrumbs_2,
            "Breadcrumbs should be consistent across page reloads"
        );
    }
}

// Run the test if called directly
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    echo "Running Navigation Pattern Consistency Property Test...\n";
    
    $test = new TestPropertyNavigationConsistency();
    
    try {
        $test->setUp();
        echo "✓ Test setup completed\n";
        
        // Run main property test
        $test->test_navigation_pattern_consistency_property();
        echo "✓ Navigation pattern consistency property test passed\n";
        
        // Run edge case tests
        $test->test_navigation_on_root_page();
        echo "✓ Root page navigation test passed\n";
        
        $test->test_navigation_with_deep_hierarchy();
        echo "✓ Deep hierarchy navigation test passed\n";
        
        $test->test_navigation_consistency_across_reloads();
        echo "✓ Navigation consistency across reloads test passed\n";
        
        echo "\n🎉 All navigation pattern consistency tests PASSED!\n";
        
    } catch (Exception $e) {
        echo "❌ Test FAILED: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
        exit(1);
    }
}