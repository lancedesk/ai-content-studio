<?php
/**
 * Property-Based Test: Dashboard Information Architecture
 * 
 * **Feature: acs-admin-ui-redesign, Property 6: Dashboard Information Architecture**
 * **Validates: Requirements 6.1, 6.2, 6.3, 6.4**
 * 
 * Tests that for any dashboard view, the interface presents key metrics, recent activity,
 * and quick actions in a logical hierarchy that enables efficient workflow completion.
 *
 * @package AI_Content_Studio
 * @subpackage Tests
 */

require_once __DIR__ . '/bootstrap.php';

class TestPropertyDashboardInformationArchitecture extends SimpleTestCase {
    
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
     * Property 6: Dashboard Information Architecture
     * 
     * For any dashboard view, the interface should present key metrics, recent activity,
     * and quick actions in a logical hierarchy that enables efficient workflow completion.
     */
    public function test_dashboard_information_architecture_property() {
        $test_cases = $this->generate_dashboard_data_configurations();
        
        foreach ($test_cases as $i => $config) {
            $this->run_dashboard_architecture_test($config, "Test case $i");
        }
    }
    
    /**
     * Generate various dashboard data configurations for property testing
     */
    private function generate_dashboard_data_configurations() {
        $configurations = [];
        
        // Generate different data states
        $data_states = [
            // Empty state
            [
                'total_posts' => 0,
                'total_optimizations' => 0,
                'avg_seo_score' => 0,
                'compliance_rate' => 0,
                'recent_posts' => [],
                'api_configured' => false,
                'seo_enabled' => false
            ],
            // Minimal data
            [
                'total_posts' => 1,
                'total_optimizations' => 0,
                'avg_seo_score' => 0,
                'compliance_rate' => 0,
                'recent_posts' => [$this->create_mock_post(1)],
                'api_configured' => true,
                'seo_enabled' => false
            ],
            // Moderate data
            [
                'total_posts' => 25,
                'total_optimizations' => 15,
                'avg_seo_score' => 75.5,
                'compliance_rate' => 80.0,
                'recent_posts' => $this->create_mock_posts(5),
                'api_configured' => true,
                'seo_enabled' => true
            ],
            // High volume data
            [
                'total_posts' => 500,
                'total_optimizations' => 450,
                'avg_seo_score' => 92.3,
                'compliance_rate' => 95.5,
                'recent_posts' => $this->create_mock_posts(10),
                'api_configured' => true,
                'seo_enabled' => true
            ],
            // Edge case: High posts, low optimizations
            [
                'total_posts' => 100,
                'total_optimizations' => 5,
                'avg_seo_score' => 45.0,
                'compliance_rate' => 20.0,
                'recent_posts' => $this->create_mock_posts(8),
                'api_configured' => true,
                'seo_enabled' => true
            ],
        ];
        
        return $data_states;
    }
    
    /**
     * Create mock post for testing
     */
    private function create_mock_post($id) {
        return (object) [
            'ID' => $id,
            'post_title' => 'Test Post ' . $id,
            'post_status' => ['draft', 'publish', 'private'][array_rand(['draft', 'publish', 'private'])],
            'post_date' => date('Y-m-d H:i:s', strtotime('-' . rand(1, 30) . ' days'))
        ];
    }
    
    /**
     * Create multiple mock posts
     */
    private function create_mock_posts($count) {
        $posts = [];
        for ($i = 1; $i <= $count; $i++) {
            $posts[] = $this->create_mock_post($i);
        }
        return $posts;
    }
    
    /**
     * Run dashboard architecture test for a specific configuration
     */
    private function run_dashboard_architecture_test($config, $test_name) {
        // Setup test environment
        $this->setup_dashboard_environment($config);
        
        // Capture dashboard output
        ob_start();
        $stats = [
            'total_posts' => $config['total_posts'],
            'total_optimizations' => $config['total_optimizations'],
            'avg_seo_score' => $config['avg_seo_score'],
            'compliance_rate' => $config['compliance_rate']
        ];
        $recent_posts = $config['recent_posts'];
        $seo_stats = [];
        
        include ACS_PLUGIN_PATH . 'admin/templates/dashboard.php';
        $dashboard_html = ob_get_clean();
        
        // Test Property 6.1: Key metrics are displayed (Requirement 6.1)
        $this->assert_key_metrics_displayed($dashboard_html, $stats, $test_name);
        
        // Test Property 6.2: Data visualization is present (Requirement 6.2)
        $this->assert_data_visualization_present($dashboard_html, $stats, $test_name);
        
        // Test Property 6.3: Quick actions are accessible (Requirement 6.3)
        $this->assert_quick_actions_accessible($dashboard_html, $test_name);
        
        // Test Property 6.4: System status is displayed (Requirement 6.4)
        $this->assert_system_status_displayed($dashboard_html, $config, $test_name);
        
        // Test Property 6.5: Recent activity is shown
        $this->assert_recent_activity_shown($dashboard_html, $recent_posts, $test_name);
        
        // Test Property 6.6: Logical information hierarchy
        $this->assert_logical_information_hierarchy($dashboard_html, $test_name);
    }
    
    /**
     * Setup dashboard environment for testing
     */
    private function setup_dashboard_environment($config) {
        // Setup plugin state
        update_option('acs_optimizer_enabled', $config['seo_enabled']);
        
        $settings = get_option('acs_settings', []);
        if ($config['api_configured']) {
            $settings['providers']['groq']['api_key'] = 'test_key_' . wp_generate_password(20, false);
        } else {
            unset($settings['providers']['groq']['api_key']);
        }
        update_option('acs_settings', $settings);
    }
    
    /**
     * Assert key metrics are displayed (Requirement 6.1)
     */
    private function assert_key_metrics_displayed($html, $stats, $test_name) {
        // Test that all key metrics are present in the dashboard
        $required_metrics = [
            'total_posts' => 'Generated Posts',
            'total_optimizations' => 'SEO Optimizations',
            'avg_seo_score' => 'Average SEO Score',
            'compliance_rate' => 'Compliance Rate'
        ];
        
        foreach ($required_metrics as $metric_key => $metric_label) {
            $this->assertStringContainsString(
                $metric_label,
                $html,
                "$test_name: Dashboard should display '$metric_label' metric"
            );
            
            // Check that the metric value is displayed
            $metric_value = $stats[$metric_key];
            $formatted_value = number_format($metric_value, ($metric_key === 'total_posts' || $metric_key === 'total_optimizations') ? 0 : 1);
            
            $this->assertStringContainsString(
                $formatted_value,
                $html,
                "$test_name: Dashboard should display the value for '$metric_label' ($formatted_value)"
            );
        }
        
        // Test that metrics are in stat cards
        $this->assertStringContainsString(
            'acs-stat-value',
            $html,
            "$test_name: Metrics should be displayed in stat card format"
        );
        
        $this->assertStringContainsString(
            'acs-stat-label',
            $html,
            "$test_name: Metrics should have labels"
        );
    }
    
    /**
     * Assert data visualization is present (Requirement 6.2)
     */
    private function assert_data_visualization_present($html, $stats, $test_name) {
        // Test that data is presented using modern visualization patterns
        
        // Check for card-based layout
        $this->assertStringContainsString(
            'acs-card',
            $html,
            "$test_name: Dashboard should use card-based components"
        );
        
        // Check for stat cards specifically
        $this->assertStringContainsString(
            'acs-card--stat',
            $html,
            "$test_name: Dashboard should use stat card variant for metrics"
        );
        
        // Check for grid layout
        $this->assertStringContainsString(
            'acs-dashboard-grid',
            $html,
            "$test_name: Dashboard should use grid layout for organization"
        );
        
        // Check for percentage indicators (for SEO score and compliance rate)
        if ($stats['avg_seo_score'] > 0 || $stats['compliance_rate'] > 0) {
            $this->assertStringContainsString(
                '%',
                $html,
                "$test_name: Dashboard should display percentage indicators for scores"
            );
        }
    }
    
    /**
     * Assert quick actions are accessible (Requirement 6.3)
     */
    private function assert_quick_actions_accessible($html, $test_name) {
        // Test that quick action buttons are present and accessible
        
        $required_actions = [
            'Generate New Content' => 'acs-generate',
            'SEO Optimizer' => 'acs-seo-optimizer',
            'View Analytics' => 'acs-analytics',
            'Settings' => 'acs-settings'
        ];
        
        // Check for Quick Actions section
        $this->assertStringContainsString(
            'Quick Actions',
            $html,
            "$test_name: Dashboard should have a Quick Actions section"
        );
        
        foreach ($required_actions as $action_text => $action_slug) {
            $this->assertStringContainsString(
                $action_text,
                $html,
                "$test_name: Dashboard should have '$action_text' quick action"
            );
            
            $this->assertStringContainsString(
                $action_slug,
                $html,
                "$test_name: Quick action '$action_text' should link to correct page ($action_slug)"
            );
        }
        
        // Check that actions are in a card
        $quick_actions_section = $this->extract_section_between($html, 'Quick Actions', '</div></div>');
        if ($quick_actions_section) {
            $this->assertStringContainsString(
                'acs-card',
                $quick_actions_section,
                "$test_name: Quick actions should be in a card component"
            );
        }
    }
    
    /**
     * Assert system status is displayed (Requirement 6.4)
     */
    private function assert_system_status_displayed($html, $config, $test_name) {
        // Test that system status indicators are present
        
        $this->assertStringContainsString(
            'System Status',
            $html,
            "$test_name: Dashboard should have a System Status section"
        );
        
        // Check for status indicators
        $required_status_items = [
            'API Configuration',
            'SEO Optimizer'
        ];
        
        foreach ($required_status_items as $status_item) {
            $this->assertStringContainsString(
                $status_item,
                $html,
                "$test_name: System status should include '$status_item'"
            );
        }
        
        // Check for status indicator elements
        $this->assertStringContainsString(
            'acs-status-indicator',
            $html,
            "$test_name: System status should use status indicator components"
        );
        
        // Check that status reflects configuration
        if ($config['api_configured']) {
            $this->assertStringContainsString(
                'Configured',
                $html,
                "$test_name: System status should show API as configured when it is"
            );
        } else {
            $this->assertStringContainsString(
                'Needs Setup',
                $html,
                "$test_name: System status should show API needs setup when not configured"
            );
        }
        
        if ($config['seo_enabled']) {
            $this->assertStringContainsString(
                'Active',
                $html,
                "$test_name: System status should show SEO optimizer as active when enabled"
            );
        }
    }
    
    /**
     * Assert recent activity is shown
     */
    private function assert_recent_activity_shown($html, $recent_posts, $test_name) {
        // Test that recent activity is displayed
        
        $this->assertStringContainsString(
            'Recent Generated Posts',
            $html,
            "$test_name: Dashboard should have a Recent Generated Posts section"
        );
        
        if (!empty($recent_posts)) {
            // Check that recent posts are displayed
            foreach (array_slice($recent_posts, 0, 5) as $post) {
                $this->assertStringContainsString(
                    $post->post_title,
                    $html,
                    "$test_name: Dashboard should display recent post '{$post->post_title}'"
                );
                
                // Check for post status badge
                $this->assertStringContainsString(
                    ucfirst($post->post_status),
                    $html,
                    "$test_name: Dashboard should display post status for '{$post->post_title}'"
                );
            }
            
            // Check for "View All Logs" link
            $this->assertStringContainsString(
                'View All Logs',
                $html,
                "$test_name: Dashboard should have link to view all logs when posts exist"
            );
        } else {
            // Check for empty state message
            $this->assertStringContainsString(
                'No generated posts yet',
                $html,
                "$test_name: Dashboard should show empty state message when no posts exist"
            );
            
            // Check for call-to-action
            $this->assertStringContainsString(
                'Create your first post',
                $html,
                "$test_name: Dashboard should encourage user to create first post when empty"
            );
        }
    }
    
    /**
     * Assert logical information hierarchy
     */
    private function assert_logical_information_hierarchy($html, $test_name) {
        // Test that information is organized in a logical hierarchy
        
        // Extract section order by finding key headings
        $sections = [];
        $section_markers = [
            'AI Content Studio Dashboard' => 'title',
            'Generated Posts' => 'stats',
            'Quick Actions' => 'actions',
            'Recent Generated Posts' => 'activity',
            'System Status' => 'status'
        ];
        
        foreach ($section_markers as $marker => $section_name) {
            $position = strpos($html, $marker);
            if ($position !== false) {
                $sections[$section_name] = $position;
            }
        }
        
        // Test that title comes first
        if (isset($sections['title'])) {
            $this->assertLessThan(
                $sections['stats'] ?? PHP_INT_MAX,
                $sections['title'],
                "$test_name: Dashboard title should come before statistics"
            );
        }
        
        // Test that stats come before actions and activity
        if (isset($sections['stats'])) {
            $this->assertLessThan(
                $sections['actions'] ?? PHP_INT_MAX,
                $sections['stats'],
                "$test_name: Statistics should come before quick actions"
            );
            
            $this->assertLessThan(
                $sections['activity'] ?? PHP_INT_MAX,
                $sections['stats'],
                "$test_name: Statistics should come before recent activity"
            );
        }
        
        // Test that system status comes last
        if (isset($sections['status'])) {
            $this->assertGreaterThan(
                $sections['stats'] ?? 0,
                $sections['status'],
                "$test_name: System status should come after statistics"
            );
            
            $this->assertGreaterThan(
                $sections['actions'] ?? 0,
                $sections['status'],
                "$test_name: System status should come after quick actions"
            );
        }
        
        // Test that grid layouts are used for organization
        $grid_count = substr_count($html, 'acs-dashboard-grid');
        $this->assertGreaterThanOrEqual(
            2,
            $grid_count,
            "$test_name: Dashboard should use multiple grid layouts for organization"
        );
    }
    
    /**
     * Helper: Extract section between two markers
     */
    private function extract_section_between($html, $start_marker, $end_marker) {
        $start_pos = strpos($html, $start_marker);
        if ($start_pos === false) {
            return null;
        }
        
        $end_pos = strpos($html, $end_marker, $start_pos);
        if ($end_pos === false) {
            return null;
        }
        
        return substr($html, $start_pos, $end_pos - $start_pos);
    }
    
    /**
     * Test edge case: Dashboard with no data
     */
    public function test_dashboard_with_no_data() {
        $config = [
            'total_posts' => 0,
            'total_optimizations' => 0,
            'avg_seo_score' => 0,
            'compliance_rate' => 0,
            'recent_posts' => [],
            'api_configured' => false,
            'seo_enabled' => false
        ];
        
        $this->setup_dashboard_environment($config);
        
        ob_start();
        $stats = [
            'total_posts' => 0,
            'total_optimizations' => 0,
            'avg_seo_score' => 0,
            'compliance_rate' => 0
        ];
        $recent_posts = [];
        $seo_stats = [];
        
        include ACS_PLUGIN_PATH . 'admin/templates/dashboard.php';
        $dashboard_html = ob_get_clean();
        
        // Should still display all sections with zero values
        $this->assertStringContainsString('Generated Posts', $dashboard_html);
        $this->assertStringContainsString('Quick Actions', $dashboard_html);
        $this->assertStringContainsString('System Status', $dashboard_html);
        
        // Should show empty state for recent posts
        $this->assertStringContainsString('No generated posts yet', $dashboard_html);
    }
    
    /**
     * Test edge case: Dashboard with high volume data
     */
    public function test_dashboard_with_high_volume_data() {
        $config = [
            'total_posts' => 10000,
            'total_optimizations' => 9500,
            'avg_seo_score' => 98.7,
            'compliance_rate' => 99.2,
            'recent_posts' => $this->create_mock_posts(20),
            'api_configured' => true,
            'seo_enabled' => true
        ];
        
        $this->setup_dashboard_environment($config);
        
        ob_start();
        $stats = [
            'total_posts' => 10000,
            'total_optimizations' => 9500,
            'avg_seo_score' => 98.7,
            'compliance_rate' => 99.2
        ];
        $recent_posts = $config['recent_posts'];
        $seo_stats = [];
        
        include ACS_PLUGIN_PATH . 'admin/templates/dashboard.php';
        $dashboard_html = ob_get_clean();
        
        // Should properly format large numbers
        $this->assertStringContainsString('10,000', $dashboard_html);
        $this->assertStringContainsString('9,500', $dashboard_html);
        
        // Should limit recent posts display (max 5)
        $displayed_posts = 0;
        foreach ($recent_posts as $post) {
            if (strpos($dashboard_html, $post->post_title) !== false) {
                $displayed_posts++;
            }
        }
        $this->assertLessThanOrEqual(5, $displayed_posts, "Should display at most 5 recent posts");
    }
}

// Run the test if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    echo "Running Dashboard Information Architecture Property Test...\n";
    
    $test = new TestPropertyDashboardInformationArchitecture();
    
    try {
        $test->setUp();
        echo "✓ Test setup completed\n";
        
        // Run main property test
        $test->test_dashboard_information_architecture_property();
        echo "✓ Dashboard information architecture property test passed\n";
        
        // Run edge case tests
        $test->test_dashboard_with_no_data();
        echo "✓ No data edge case test passed\n";
        
        $test->test_dashboard_with_high_volume_data();
        echo "✓ High volume data edge case test passed\n";
        
        echo "\n🎉 All dashboard information architecture tests PASSED!\n";
        
    } catch (Exception $e) {
        echo "❌ Test FAILED: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
        exit(1);
    }
}
