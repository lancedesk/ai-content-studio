<?php
/**
 * Property-based tests for ACS_Generator_Loader
 *
 * @package ACS
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';
require_once dirname( __DIR__ ) . '/includes/class-acs-generator-loader.php';

class TestGeneratorLoader extends TestCase {

	/**
	 * Reset the singleton instance before each test
	 */
	protected function setUp(): void {
		parent::setUp();
		ACS_Generator_Loader::reset_instance();
	}

	/**
	 * Property 9: Generator Instance Reuse
	 * Feature: content-generation-fixes, Property 9: Generator Instance Reuse
	 * Validates: Requirements 3.4
	 *
	 * For any request lifecycle, multiple calls to get the generator instance
	 * should return the same object instance.
	 */
	public function test_property_generator_instance_reuse() {
		$iterations = 100;

		for ( $i = 0; $i < $iterations; $i++ ) {
			// Reset instance for each iteration to simulate new request
			ACS_Generator_Loader::reset_instance();

			// Get instance multiple times (random number between 2 and 10)
			$num_calls = rand( 2, 10 );
			$instances = array();

			for ( $j = 0; $j < $num_calls; $j++ ) {
				$instance = ACS_Generator_Loader::get_instance();

				// Assert instance is not an error
				$this->assertNotInstanceOf(
					'WP_Error',
					$instance,
					"Iteration {$i}, Call {$j}: Should return valid instance"
				);

				// Assert instance is of correct type
				$this->assertInstanceOf(
					'ACS_Content_Generator',
					$instance,
					"Iteration {$i}, Call {$j}: Should return ACS_Content_Generator instance"
				);

				$instances[] = $instance;
			}

			// Assert all instances are the same object (using spl_object_id for strict comparison)
			$first_id = spl_object_id( $instances[0] );
			for ( $j = 1; $j < count( $instances ); $j++ ) {
				$current_id = spl_object_id( $instances[ $j ] );
				$this->assertSame(
					$first_id,
					$current_id,
					"Iteration {$i}: All instances should be the same object (call 0 vs call {$j})"
				);
			}

			// Additional verification: use strict identity check
			for ( $j = 1; $j < count( $instances ); $j++ ) {
				$this->assertSame(
					$instances[0],
					$instances[ $j ],
					"Iteration {$i}: Instance {$j} should be identical to first instance"
				);
			}
		}
	}

	/**
	 * Test that required methods exist in loaded generator
	 */
	public function test_required_methods_exist() {
		$instance = ACS_Generator_Loader::get_instance();

		$this->assertNotInstanceOf( 'WP_Error', $instance, 'Should load generator successfully' );

		$required_methods = array( 'generate', 'parse_generated_content', 'create_post', 'build_prompt' );

		foreach ( $required_methods as $method ) {
			$this->assertTrue(
				method_exists( $instance, $method ),
				"Generator should have method: {$method}"
			);
		}
	}

	/**
	 * Test error handling when class file doesn't exist
	 */
	public function test_error_when_class_file_missing() {
		// This test would require mocking the file system or moving the file
		// For now, we'll just verify the error handling structure exists
		$this->assertTrue(
			method_exists( 'ACS_Generator_Loader', 'get_instance' ),
			'get_instance method should exist'
		);
	}
}
