<?php
/**
 * Generator Loader Component
 *
 * Ensures proper class loading and method availability for the content generator.
 * Implements singleton pattern to maintain a single instance throughout the request lifecycle.
 *
 * @package ACS
 */

if ( ! defined( 'ABSPATH' ) ) {
	if ( ! defined( 'PHPUNIT_RUNNING' ) ) {
		exit;
	}
}

/**
 * Class ACS_Generator_Loader
 *
 * Handles loading and validation of the ACS_Content_Generator class.
 */
class ACS_Generator_Loader {

	/**
	 * Singleton instance
	 *
	 * @var ACS_Content_Generator|null
	 */
	private static $instance = null;

	/**
	 * Required methods that must exist in the generator class
	 *
	 * @var array
	 */
	private static $required_methods = array(
		'generate',
		'parse_generated_content',
		'create_post',
		'build_prompt',
	);

	/**
	 * Get singleton instance of content generator
	 *
	 * @return ACS_Content_Generator|WP_Error Generator instance or error
	 */
	public static function get_instance() {
		// Return existing instance if available
		if ( null !== self::$instance ) {
			return self::$instance;
		}

		// Verify class file exists
		$class_file = dirname( __DIR__ ) . '/generators/class-acs-content-generator.php';
		if ( ! self::verify_class_loaded( $class_file ) ) {
			$error_msg = sprintf(
				'Generator class file not found at: %s',
				$class_file
			);
			error_log( '[ACS][GENERATOR_LOADER] ' . $error_msg );
			return new WP_Error( 'class_file_not_found', $error_msg );
		}

		// Require the class file
		require_once $class_file;

		// Verify class is defined
		if ( ! class_exists( 'ACS_Content_Generator' ) ) {
			$error_msg = 'ACS_Content_Generator class not defined after requiring file';
			error_log( '[ACS][GENERATOR_LOADER] ' . $error_msg );
			return new WP_Error( 'class_not_defined', $error_msg );
		}

		// Verify required methods exist
		if ( ! self::verify_methods_exist( 'ACS_Content_Generator', self::$required_methods ) ) {
			$available_methods = get_class_methods( 'ACS_Content_Generator' );
			$error_msg = sprintf(
				'Required methods missing from ACS_Content_Generator. Required: %s. Available: %s',
				implode( ', ', self::$required_methods ),
				implode( ', ', $available_methods )
			);
			error_log( '[ACS][GENERATOR_LOADER] ' . $error_msg );
			return new WP_Error( 'methods_missing', $error_msg );
		}

		// Instantiate the generator
		try {
			self::$instance = new ACS_Content_Generator();
		} catch ( Exception $e ) {
			$error_msg = sprintf(
				'Failed to instantiate ACS_Content_Generator: %s',
				$e->getMessage()
			);
			error_log( '[ACS][GENERATOR_LOADER] ' . $error_msg );
			return new WP_Error( 'instantiation_failed', $error_msg );
		}

		// Log class info if debugging is enabled
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			self::log_class_info( $class_file );
		}

		return self::$instance;
	}

	/**
	 * Verify class file exists
	 *
	 * @param string $file_path Path to class file
	 * @return bool True if file exists
	 */
	private static function verify_class_loaded( $file_path ) {
		return file_exists( $file_path );
	}

	/**
	 * Verify required methods exist in class
	 *
	 * @param string $class_name Name of class to check
	 * @param array  $methods Array of required method names
	 * @return bool True if all methods exist
	 */
	private static function verify_methods_exist( $class_name, $methods ) {
		if ( ! class_exists( $class_name ) ) {
			return false;
		}

		foreach ( $methods as $method ) {
			if ( ! method_exists( $class_name, $method ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Log class information for debugging
	 *
	 * @param string $file_path Path to class file
	 * @return void
	 */
	private static function log_class_info( $file_path ) {
		$available_methods = class_exists( 'ACS_Content_Generator' )
			? get_class_methods( 'ACS_Content_Generator' )
			: array();

		$log_message = sprintf(
			'[ACS][GENERATOR_LOADER] Class loaded successfully. File: %s. Available methods: %s',
			$file_path,
			implode( ', ', $available_methods )
		);

		error_log( $log_message );
	}

	/**
	 * Reset the singleton instance (useful for testing)
	 *
	 * @return void
	 */
	public static function reset_instance() {
		self::$instance = null;
	}
}
