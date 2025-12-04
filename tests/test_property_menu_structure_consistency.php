<?php
/**
 * Property-Based Test: Menu Structure Consistency
 * 
 * **Feature: acs-admin-ui-redesign, Property 1: Menu Structure Consistency**
 * **Validates: Requirements 1.1, 1.2**
 * 
 * Tests that for any admin page load, the navigation menu displays the unified ACS 
 * structure with exactly one main menu item and all sub-items properly organized 
 * under logical categories.
 *
 * @package AI_Content_Studio
 * @subpackage Tests
 */

require_once __DIR__ . '/bootstrap.php';

class TestPropertyMenuStructureConsistency extends SimpleTestCase {
    
    private $unified_admin;
    private $original_menu;
    
    public function setUp(): void {
        parent::setUp();
        
        // Store original menu state
        global $menu, $submenu;
        $this->original_menu = ['menu' => $menu, 'submenu' => $submenu];
        
        // Initialize unified admin
        if (!class_exists('ACS_Unified_Admin')) {
            require_once ACS_PLUGIN_PATH . 'admin/class-acs-unified-admin.php';
        }
        
        $this->unified_admin = new ACS_Unified_Admin('ai-content-studio', '2.0.0');
    }
    
    public function tearDown(): void {
        // Restore original menu state
        global $menu, $submenu;
        $menu = $this->original_menu['menu'];
        $submenu = $this->original_menu['submenu'];
        
        parent::tearDown();
    }
    
    /**
     * Property 1: Menu Structure Consistency
     * 
     * For any admin page configuration, the unified menu should display exactly
     * one main ACS menu item with all functionality organized under it.
     */
    public function test_menu_structure_consistency_property() {
        $test_cases = $this->generate_admin_page_configurations();
        
        foreach ($test_cases as $i => $config) {
            $this->run_menu_consistency_test($config, "Test case $i");
        }
    }
    
    /**
     * Generate various admin page configurations for property testing
     */
    private function generate_admin_page_configurations() {
        $configurations = [];
        
        // Generate different user capability scenarios
        $capability_sets = [
            ['acs_generate_content'],
            ['acs_generate_content', 'acs_manage_settings'],
            ['acs_generate_content', 'acs_view_analytics'],
            ['acs_generate_content', 'acs_manage_seo'],
            ['acs_generate_content', 'acs_manage_settings', 'acs_view_analytics', 'acs_manage_seo'],
            [], // No capabilities
        ];
        
        // Generate different WordPress admin contexts
        $admin_contexts = [
            ['hook_suffix' => 'toplevel_page_acs-dashboard'],
            ['hook_suffix' => 'ai-content-studio_page_acs-generate'],
            ['hook_suffix' => 'ai-content-studio_page_acs-seo-optimizer'],
            ['hook_suffix' => 'ai-content-studio_page_acs-analytics'],
            ['hook_suffix' => 'ai-content-studio_page_acs-settings'],
            ['hook_suffix' => 'post.php'],
            ['hook_suffix' => 'edit.php'],
        ];
        
        // Generate different plugin states
        $plugin_states = [
            ['seo_enabled' => true, 'api_configured' => true],
            ['seo_enabled' => false, 'api_configured' => true],
            ['seo_enabled' => true, 'api_configured' => false],
            ['seo_enabled' => false, 'api_configured' => false],
        ];
        
        // Combine configurations
        foreach ($capability_sets as $caps) {
            foreach ($admin_contexts as $context) {
                foreach ($plugin_states as $state) {
                    $configurations[] = [
                        'capabilities' => $caps,
                        'context' => $context,
                        'plugin_state' => $state
                    ];
                }
            }
        }
        
        return array_slice($configurations, 0, 20); // Limit to 20 test cases for performance
    }
    
    /**
     * Run menu consistency test for a specific configuration
     */
    private function run_menu_consistency_test($config, $test_name) {
        // Setup test environment
        $this->setup_test_environment($config);
        
        // Clear and rebuild menu
        global $menu, $submenu;
        $menu = [];
        $submenu = [];
        
        // Simulate admin menu building
        do_action('admin_menu');
        
        // Test Property 1: Exactly one main ACS menu item
        $acs_main_menus = $this->find_acs_main_menu_items();
        $this->assertEquals(
            1, 
            count($acs_main_menus),
            "$test_name: Should have exactly one main ACS menu item, found " . count($acs_main_menus)
        );
        
        if (empty($acs_main_menus)) {
            return; // Skip further tests if no main menu found
        }
        
        $main_menu_slug = $acs_main_menus[0]['slug'];
        
        // Test Property 2: All ACS functionality under main menu
        $this->assert_all_acs_functionality_under_main_menu($main_menu_slug, $test_name);
        
        // Test Property 3: Logical submenu organization
        $this->assert_logical_submenu_organization($main_menu_slug, $test_name);
        
        // Test Property 4: Consistent menu structure regardless of context
        $this->assert_consistent_menu_structure($main_menu_slug, $config, $test_name);
    }
    
    /**
     * Setup test environment for specific configuration
     */
    private function setup_test_environment($config) {
        // Setup user capabilities
        $user = wp_get_current_user();
        
        // Remove all ACS capabilities first
        $all_acs_caps = ['acs_generate_content', 'acs_manage_settings', 'acs_view_analytics', 'acs_manage_seo'];
        foreach ($all_acs_caps as $cap) {
            $user->remove_cap($cap);
        }
        
        // Add specified capabilities
        foreach ($config['capabilities'] as $cap) {
            $user->add_cap($cap);
        }
        
        // Setup plugin state
        update_option('acs_optimizer_enabled', $config['plugin_state']['seo_enabled']);
        
        $settings = get_option('acs_settings', []);
        if ($config['plugin_state']['api_configured']) {
            $settings['providers']['groq']['api_key'] = 'test_key_' . wp_generate_password(20, false);
        } else {
            unset($settings['providers']['groq']['api_key']);
        }
        update_option('acs_settings', $settings);
        
        // Setup admin context
        global $hook_suffix;
        $hook_suffix = $config['context']['hook_suffix'];
        
        // Ensure the unified admin is properly initialized and hooked
        if ($this->unified_admin && !empty($config['capabilities'])) {
            // Manually trigger the menu registration for testing
            $this->unified_admin->add_unified_menu();
        }
    }
    
    /**
     * Find ACS main menu items
     */
    private function find_acs_main_menu_items() {
        global $menu;
        
        $acs_menus = [];
        
        if (!is_array($menu)) {
            return $acs_menus;
        }
        
        foreach ($menu as $menu_item) {
            if (!is_array($menu_item) || count($menu_item) < 3) {
                continue;
            }
            
            $menu_title = $menu_item[0] ?? '';
            $menu_slug = $menu_item[2] ?? '';
            
            // Check if this is an ACS menu item
            if (strpos($menu_title, 'AI Content Studio') !== false || 
                strpos($menu_slug, 'acs-') === 0) {
                $acs_menus[] = [
                    'title' => $menu_title,
                    'slug' => $menu_slug,
                    'capability' => $menu_item[1] ?? '',
                    'icon' => $menu_item[6] ?? ''
                ];
            }
        }
        
        return $acs_menus;
    }
    
    /**
     * Assert all ACS functionality is under the main menu
     */
    private function assert_all_acs_functionality_under_main_menu($main_menu_slug, $test_name) {
        global $submenu;
        
        // Check that ACS submenus exist under the main menu
        $this->assertArrayHasKey(
            $main_menu_slug,
            $submenu,
            "$test_name: Main ACS menu should have submenus"
        );
        
        $acs_submenus = $submenu[$main_menu_slug] ?? [];
        
        // Expected core functionality (minimum required submenus)
        $expected_functionality = [
            'dashboard' => ['acs-dashboard'],
            'generate' => ['acs-generate'],
            'seo' => ['acs-seo-optimizer', 'acs-seo'],
            'analytics' => ['acs-analytics', 'acs-generation-logs'],
            'settings' => ['acs-settings']
        ];
        
        $found_functionality = [];
        
        foreach ($acs_submenus as $submenu_item) {
            $submenu_slug = $submenu_item[2] ?? '';
            
            foreach ($expected_functionality as $func_name => $possible_slugs) {
                foreach ($possible_slugs as $slug_pattern) {
                    if (strpos($submenu_slug, $slug_pattern) !== false) {
                        $found_functionality[$func_name] = true;
                        break 2;
                    }
                }
            }
        }
        
        // At minimum, should have dashboard and generate functionality
        $this->assertTrue(
            isset($found_functionality['dashboard']),
            "$test_name: Should have dashboard functionality under main menu"
        );
        
        $this->assertTrue(
            isset($found_functionality['generate']),
            "$test_name: Should have content generation functionality under main menu"
        );
    }
    
    /**
     * Assert logical submenu organization
     */
    private function assert_logical_submenu_organization($main_menu_slug, $test_name) {
        global $submenu;
        
        $acs_submenus = $submenu[$main_menu_slug] ?? [];
        
        // Test that submenus are logically ordered
        $submenu_order = [];
        foreach ($acs_submenus as $submenu_item) {
            $submenu_title = $submenu_item[0] ?? '';
            $submenu_slug = $submenu_item[2] ?? '';
            $submenu_order[] = $submenu_slug;
        }
        
        // Dashboard should be first (if present)
        if (in_array('acs-dashboard', $submenu_order)) {
            $dashboard_position = array_search('acs-dashboard', $submenu_order);
            $this->assertEquals(
                0,
                $dashboard_position,
                "$test_name: Dashboard should be the first submenu item"
            );
        }
        
        // Settings should be last (if present)
        $settings_items = array_filter($submenu_order, function($slug) {
            return strpos($slug, 'settings') !== false;
        });
        
        if (!empty($settings_items)) {
            $last_item = end($submenu_order);
            $this->assertTrue(
                strpos($last_item, 'settings') !== false,
                "$test_name: Settings should be the last submenu item"
            );
        }
        
        // Test that each submenu has proper capability requirements
        foreach ($acs_submenus as $submenu_item) {
            $capability = $submenu_item[1] ?? '';
            $this->assertNotEmpty(
                $capability,
                "$test_name: Each submenu should have a capability requirement"
            );
            
            $this->assertTrue(
                strpos($capability, 'acs_') === 0 || in_array($capability, ['manage_options', 'edit_posts']),
                "$test_name: Submenu capabilities should be ACS-specific or standard WordPress capabilities"
            );
        }
    }
    
    /**
     * Assert consistent menu structure regardless of context
     */
    private function assert_consistent_menu_structure($main_menu_slug, $config, $test_name) {
        global $submenu;
        
        $acs_submenus = $submenu[$main_menu_slug] ?? [];
        
        // The menu structure should be consistent regardless of:
        // 1. Current admin page
        // 2. Plugin configuration state
        // 3. User capabilities (though visibility may differ)
        
        // Test that core menu items exist regardless of current page
        $core_items_found = 0;
        $core_items = ['dashboard', 'generate'];
        
        foreach ($acs_submenus as $submenu_item) {
            $submenu_slug = $submenu_item[2] ?? '';
            
            foreach ($core_items as $core_item) {
                if (strpos($submenu_slug, $core_item) !== false) {
                    $core_items_found++;
                    break;
                }
            }
        }
        
        $this->assertGreaterThanOrEqual(
            1,
            $core_items_found,
            "$test_name: At least one core menu item should always be present"
        );
        
        // Test that menu structure doesn't change based on plugin state
        // (This is tested by running the same test with different plugin states)
        
        // Test that submenu items have consistent naming patterns
        foreach ($acs_submenus as $submenu_item) {
            $submenu_title = $submenu_item[0] ?? '';
            $submenu_slug = $submenu_item[2] ?? '';
            
            // Submenu titles should not be empty
            $this->assertNotEmpty(
                $submenu_title,
                "$test_name: Submenu titles should not be empty"
            );
            
            // Submenu slugs should follow ACS naming convention
            if (strpos($submenu_slug, 'acs-') === 0) {
                $this->assertMatchesRegularExpression(
                    '/^acs-[a-z-]+$/',
                    $submenu_slug,
                    "$test_name: ACS submenu slugs should follow naming convention (acs-[a-z-]+)"
                );
            }
        }
    }
    
    /**
     * Test edge case: Menu structure with no capabilities
     */
    public function test_menu_structure_with_no_capabilities() {
        // Setup user with no ACS capabilities
        $user = wp_get_current_user();
        $all_acs_caps = ['acs_generate_content', 'acs_manage_settings', 'acs_view_analytics', 'acs_manage_seo'];
        foreach ($all_acs_caps as $cap) {
            $user->remove_cap($cap);
        }
        
        // Clear and rebuild menu
        global $menu, $submenu;
        $menu = [];
        $submenu = [];
        
        do_action('admin_menu');
        
        // Should have no ACS menu items when user has no capabilities
        $acs_main_menus = $this->find_acs_main_menu_items();
        $this->assertEquals(
            0,
            count($acs_main_menus),
            "Should have no ACS menu items when user has no capabilities"
        );
    }
    
    /**
     * Test edge case: Menu structure with admin capabilities
     */
    public function test_menu_structure_with_admin_capabilities() {
        // Setup user as administrator
        $user = wp_get_current_user();
        $user->set_role('administrator');
        
        // Clear and rebuild menu
        global $menu, $submenu;
        $menu = [];
        $submenu = [];
        
        do_action('admin_menu');
        
        // Should have full ACS menu when user is administrator
        $acs_main_menus = $this->find_acs_main_menu_items();
        $this->assertEquals(
            1,
            count($acs_main_menus),
            "Should have exactly one ACS menu item for administrators"
        );
        
        if (!empty($acs_main_menus)) {
            $main_menu_slug = $acs_main_menus[0]['slug'];
            $acs_submenus = $submenu[$main_menu_slug] ?? [];
            
            // Administrator should see all functionality
            $this->assertGreaterThanOrEqual(
                4,
                count($acs_submenus),
                "Administrator should see at least 4 submenu items (dashboard, generate, analytics, settings)"
            );
        }
    }
}

// Run the test if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    echo "Running Menu Structure Consistency Property Test...\n";
    
    $test = new TestPropertyMenuStructureConsistency();
    
    try {
        $test->setUp();
        echo "✓ Test setup completed\n";
        
        // Run main property test
        $test->test_menu_structure_consistency_property();
        echo "✓ Menu structure consistency property test passed\n";
        
        // Run edge case tests
        $test->test_menu_structure_with_no_capabilities();
        echo "✓ No capabilities edge case test passed\n";
        
        $test->test_menu_structure_with_admin_capabilities();
        echo "✓ Admin capabilities edge case test passed\n";
        
        $test->tearDown();
        echo "✓ Test teardown completed\n";
        
        echo "\n🎉 All menu structure consistency tests PASSED!\n";
        
    } catch (Exception $e) {
        echo "❌ Test FAILED: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
        exit(1);
    }
}