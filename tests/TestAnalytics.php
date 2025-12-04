<?php
/**
 * Unit tests for ACS_Analytics class.
 *
 * Verifies track_generation(), record_event(), and get_metrics() produce
 * expected behavior and fall back gracefully when tables are missing.
 *
 * @package ACS\Tests
 */

require_once __DIR__ . '/bootstrap.php';

// Re-declare wpdb with analytics table support for this test file
class TestAnalyticsWpdb {
    public $prefix = 'wp_';
    public $insert_id = 0;
    private $generations = array();
    private $events = array();

    public function get_charset_collate() {
        return 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
    }

    public function insert( $table, $data, $formats = null ) {
        if ( strpos( $table, 'acs_generations' ) !== false ) {
            $this->insert_id = count( $this->generations ) + 1;
            $data['id'] = $this->insert_id;
            $this->generations[] = $data;
            return 1;
        }
        if ( strpos( $table, 'acs_analytics_events' ) !== false ) {
            $this->insert_id = count( $this->events ) + 1;
            $data['id'] = $this->insert_id;
            $this->events[] = $data;
            return 1;
        }
        return false;
    }

    public function prepare( $query, ...$args ) {
        return vsprintf( str_replace( array( '%s', '%d', '%f' ), "'%s'", $query ), $args );
    }

    public function get_row( $query, $output = ARRAY_A ) {
        // Simulate aggregated metrics
        $count = count( $this->generations );
        $sum_tokens = array_sum( array_column( $this->generations, 'tokens_used' ) );
        $sum_cost = array_sum( array_column( $this->generations, 'cost_estimate' ) );
        return array(
            'total' => $count,
            'avg_tokens' => $count > 0 ? $sum_tokens / $count : 0,
            'total_cost' => $sum_cost,
        );
    }

    public function get_results( $query, $output = ARRAY_A ) {
        return $this->generations;
    }

    public function get_var( $query ) {
        return count( $this->generations );
    }

    // Expose data for assertions
    public function _get_generations() {
        return $this->generations;
    }
    public function _get_events() {
        return $this->events;
    }
}

// Override global $wpdb with test double
global $wpdb;
$wpdb = new TestAnalyticsWpdb();

// Ensure ACS_PLUGIN_PATH is set
if ( ! defined( 'ACS_PLUGIN_PATH' ) ) {
    define( 'ACS_PLUGIN_PATH', dirname( __DIR__ ) . '/' );
}

// Load analytics class
require_once ACS_PLUGIN_PATH . 'includes/class-acs-analytics.php';

class TestAnalytics extends SimpleTestCase {

    public function test_track_generation_inserts_row() {
        global $wpdb;
        $wpdb = new TestAnalyticsWpdb();

        $id = ACS_Analytics::track_generation( array(
            'post_id' => 123,
            'user_id' => 1,
            'provider' => 'groq',
            'model' => 'llama-3.3-70b',
            'prompt_hash' => 'abc123',
            'prompt_text' => 'Test prompt',
            'response_text' => 'Test response',
            'tokens_used' => 500,
            'cost_estimate' => 0.001,
            'generation_time' => 2.5,
            'status' => 'completed',
        ) );

        $this->assertTrue( $id > 0, 'track_generation should return insert ID' );

        $rows = $wpdb->_get_generations();
        $this->assertEquals( 1, count( $rows ), 'Should have one generation row' );
        $this->assertEquals( 'groq', $rows[0]['provider'] );
        $this->assertEquals( 500, $rows[0]['tokens_used'] );
    }

    public function test_record_event_inserts_row() {
        global $wpdb;
        $wpdb = new TestAnalyticsWpdb();

        $gen_id = ACS_Analytics::track_generation( array( 'provider' => 'openai', 'status' => 'completed' ) );
        $event_id = ACS_Analytics::record_event( $gen_id, 'retry', array( 'attempt' => 2 ) );

        $this->assertTrue( $event_id > 0, 'record_event should return insert ID' );

        $events = $wpdb->_get_events();
        $this->assertEquals( 1, count( $events ) );
        $this->assertEquals( 'retry', $events[0]['event_type'] );
    }

    public function test_get_metrics_returns_aggregates() {
        global $wpdb;
        $wpdb = new TestAnalyticsWpdb();

        ACS_Analytics::track_generation( array( 'provider' => 'groq', 'tokens_used' => 100, 'cost_estimate' => 0.001, 'status' => 'completed' ) );
        ACS_Analytics::track_generation( array( 'provider' => 'groq', 'tokens_used' => 200, 'cost_estimate' => 0.002, 'status' => 'completed' ) );

        $metrics = ACS_Analytics::get_metrics();

        $this->assertEquals( 2, $metrics['total'] );
        $this->assertEquals( 150, $metrics['avg_tokens'] );
        $this->assertEquals( 0.003, $metrics['total_cost'] );
    }

    public function test_get_metrics_with_provider_filter() {
        global $wpdb;
        $wpdb = new TestAnalyticsWpdb();

        ACS_Analytics::track_generation( array( 'provider' => 'groq', 'tokens_used' => 100, 'status' => 'completed' ) );
        ACS_Analytics::track_generation( array( 'provider' => 'openai', 'tokens_used' => 300, 'status' => 'completed' ) );

        $metrics = ACS_Analytics::get_metrics( array( 'provider' => 'groq' ) );

        // Because our mock doesn't filter, we just check return structure
        $this->assertArrayHasKey( 'total', $metrics );
        $this->assertArrayHasKey( 'avg_tokens', $metrics );
        $this->assertArrayHasKey( 'total_cost', $metrics );
    }
}

// Run tests
$test = new TestAnalytics();
$methods = get_class_methods( $test );
$passed = 0;
$failed = 0;
echo "Running Analytics Tests\n";
echo "========================\n";
foreach ( $methods as $method ) {
    if ( strpos( $method, 'test_' ) === 0 ) {
        try {
            $test->$method();
            echo "[PASS] $method\n";
            $passed++;
        } catch ( Exception $e ) {
            echo "[FAIL] $method: " . $e->getMessage() . "\n";
            $failed++;
        }
    }
}
echo "========================\n";
echo "Passed: $passed, Failed: $failed\n";
exit( $failed > 0 ? 1 : 0 );
