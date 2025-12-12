<?php
/**
 * Error Handler Class
 *
 * Centralized error handling, user feedback, and retry mechanisms for AI Content Studio.
 *
 * @package    AI_Content_Studio
 * @subpackage Includes
 * @since      2.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ACS_Error_Handler
 *
 * Provides centralized error handling with user-friendly messages,
 * retry mechanisms, and detailed logging for debugging.
 */
class ACS_Error_Handler {

	/**
	 * Singleton instance for backwards compatibility with instance-style calls.
	 *
	 * @var ACS_Error_Handler|null
	 */
	private static $instance = null;

	/**
	 * Error codes and their user-friendly messages
	 *
	 * @var array
	 */
	private static $error_messages = array(
		// API Errors
		'api_connection_failed'    => 'Unable to connect to the AI service. Please check your internet connection and try again.',
		'api_authentication_failed' => 'API authentication failed. Please verify your API key in Settings.',
		'api_rate_limited'         => 'API rate limit reached. Please wait a moment and try again.',
		'api_quota_exceeded'       => 'API quota exceeded. Please check your subscription or upgrade your plan.',
		'api_timeout'              => 'The AI service took too long to respond. Please try again with a shorter prompt.',
		'api_server_error'         => 'The AI service is experiencing issues. Please try again later.',
		'api_invalid_response'     => 'Received an invalid response from the AI service. Please try again.',

		// Content Errors
		'content_too_long'         => 'The generated content exceeds the maximum length. Try reducing the word count.',
		'content_empty'            => 'No content was generated. Please try a different prompt or topic.',
		'content_blocked'          => 'The content was blocked due to policy restrictions. Please modify your prompt.',
		'content_parse_failed'     => 'Failed to parse the generated content. Please try again.',

		// Validation Errors
		'invalid_topic'            => 'Please enter a valid topic for content generation.',
		'invalid_keyword'          => 'Please enter a valid focus keyword.',
		'missing_required_field'   => 'Please fill in all required fields.',
		'invalid_word_count'       => 'Please select a valid word count range.',
		'invalid_provider'         => 'The selected AI provider is not available. Please choose another.',

		// Permission Errors
		'permission_denied'        => 'You do not have permission to perform this action.',
		'not_logged_in'            => 'You must be logged in to perform this action.',
		'nonce_failed'             => 'Security verification failed. Please refresh the page and try again.',

		// File Errors
		'file_upload_failed'       => 'File upload failed. Please try again.',
		'file_too_large'           => 'The file is too large. Maximum allowed size is 5MB.',
		'invalid_file_type'        => 'Invalid file type. Please upload a supported format.',

		// Database Errors
		'database_error'           => 'A database error occurred. Please try again or contact support.',
		'save_failed'              => 'Failed to save the content. Please try again.',

		// SEO Errors
		'seo_analysis_failed'      => 'SEO analysis could not be completed. Please try again.',
		'seo_optimization_failed'  => 'Content optimization failed. Please try again.',

		// Generic Errors
		'unknown_error'            => 'An unexpected error occurred. Please try again or contact support.',
		'operation_failed'         => 'The operation could not be completed. Please try again.',
	);

	/**
	 * Errors that can be retried
	 *
	 * @var array
	 */
	private static $retryable_errors = array(
		'api_connection_failed',
		'api_rate_limited',
		'api_timeout',
		'api_server_error',
		'database_error',
		'save_failed',
	);

	/**
	 * Maximum retry attempts
	 *
	 * @var int
	 */
	private static $max_retries = 3;

	/**
	 * Retry delay in seconds (exponential backoff base)
	 *
	 * @var int
	 */
	private static $retry_delay = 1;

	/**
	 * Create a standardized error response
	 *
	 * @param string $code    Error code.
	 * @param string $message Optional custom message (overrides default).
	 * @param array  $data    Additional error data.
	 * @return WP_Error
	 */
	public static function create_error( $code, $message = '', $data = array() ) {
		// Support calling with a context array as the second parameter
		if ( is_array( $message ) && empty( $data ) ) {
			$context = $message;
			$message = isset( $context['message'] ) ? $context['message'] : '';
			$data = $context;
		}

		if ( empty( $message ) ) {
			$message = self::get_user_message( $code );
		}

		$error_data = array_merge( array(
			'code'       => $code,
			'retryable'  => self::is_retryable( $code ),
			'timestamp'  => current_time( 'mysql' ),
		), $data );

		// Log the error (accepts either message or context array)
		self::log_error( $code, $message, $error_data );

		return new WP_Error( $code, $message, $error_data );
	}

	/**
	 * Get user-friendly message for an error code
	 *
	 * @param string $code Error code.
	 * @return string
	 */
	public static function get_user_message( $code ) {
		if ( isset( self::$error_messages[ $code ] ) ) {
			return __( self::$error_messages[ $code ], 'ai-content-studio' );
		}
		return __( self::$error_messages['unknown_error'], 'ai-content-studio' );
	}

	/**
	 * Check if an error is retryable
	 *
	 * @param string $code Error code.
	 * @return bool
	 */
	public static function is_retryable( $code ) {
		return in_array( $code, self::$retryable_errors, true );
	}

	/**
	 * Get maximum retry attempts
	 *
	 * @return int
	 */
	public static function get_max_retries() {
		return apply_filters( 'acs_error_max_retries', self::$max_retries );
	}

	/**
	 * Calculate retry delay with exponential backoff
	 *
	 * @param int $attempt Current attempt number (1-based).
	 * @return int Delay in seconds.
	 */
	public static function get_retry_delay( $attempt ) {
		$delay = self::$retry_delay * pow( 2, $attempt - 1 );
		return apply_filters( 'acs_error_retry_delay', min( $delay, 30 ), $attempt );
	}

	/**
	 * Log an error
	 *
	 * @param string $code    Error code.
	 * @param string $message Error message.
	 * @param array  $data    Additional error data.
	 */
	public static function log_error( $code, $message, $data = array() ) {
		// Support call pattern where $message may actually be a context array.
		$level = 'error';
		if ( is_array( $message ) ) {
			$context = $message;
			$message = isset( $context['message'] ) ? $context['message'] : self::get_user_message( $code );
			// If $data is a string, it's likely the level (e.g., 'warning')
			if ( is_string( $data ) ) {
				$level = $data;
				$data = $context;
			} elseif ( is_array( $data ) && ! empty( $data ) ) {
				$data = array_merge( $context, $data );
			} else {
				$data = $context;
			}
		} elseif ( is_string( $data ) ) {
			// If second param is a message and third is a string, interpret third as level
			$level = $data;
			$data = array();
		} else {
			$level = isset( $data['level'] ) ? $data['level'] : 'error';
		}

		if ( class_exists( 'ACS_Logger' ) ) {
			ACS_Logger::error( sprintf( '[%s] %s', $code, $message ), $data );
		}

		// Also log to WordPress debug log if enabled
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			error_log( sprintf( '[ACS Error] [%s] %s - %s', $code, $message, wp_json_encode( $data ) ) );
		}

		// Persist error to database if table exists
		global $wpdb;
		$table_name = $wpdb->prefix . 'acs_error_logs';
		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );
		if ( $exists === $table_name ) {
			$wpdb->insert(
				$table_name,
				array(
					'error_code' => sanitize_text_field( $code ),
					'message'    => sanitize_textarea_field( $message ),
					'data'       => wp_json_encode( $data ),
						'level'      => sanitize_text_field( $level ),
					'created_at' => current_time( 'mysql' ),
				),
				array( '%s', '%s', '%s', '%s', '%s' )
			);
		}
	}

	/**
	 * Handle AJAX error response
	 *
	 * @param WP_Error|string $error       Error object or code.
	 * @param string          $message     Optional custom message.
	 * @param array           $extra_data  Additional data to include.
	 */
	public static function ajax_error( $error, $message = '', $extra_data = array() ) {
		if ( is_wp_error( $error ) ) {
			$code    = $error->get_error_code();
			$message = $error->get_error_message();
			$data    = $error->get_error_data();
		} else {
			$code = $error;
			$data = array();
		}

		if ( empty( $message ) ) {
			$message = self::get_user_message( $code );
		}

		$response = array_merge( array(
			'code'        => $code,
			'message'     => $message,
			'retryable'   => self::is_retryable( $code ),
			'retry_delay' => self::is_retryable( $code ) ? self::get_retry_delay( 1 ) : 0,
			'max_retries' => self::is_retryable( $code ) ? self::get_max_retries() : 0,
		), $extra_data, (array) $data );

		wp_send_json_error( $response );
	}

	/**
	 * Handle REST API error response
	 *
	 * @param WP_Error|string $error   Error object or code.
	 * @param int             $status  HTTP status code.
	 * @return WP_REST_Response
	 */
	public static function rest_error( $error, $status = 400 ) {
		if ( is_wp_error( $error ) ) {
			$code    = $error->get_error_code();
			$message = $error->get_error_message();
			$data    = $error->get_error_data();
		} else {
			$code    = $error;
			$message = self::get_user_message( $code );
			$data    = array();
		}

		$response_data = array(
			'code'        => $code,
			'message'     => $message,
			'retryable'   => self::is_retryable( $code ),
			'retry_delay' => self::is_retryable( $code ) ? self::get_retry_delay( 1 ) : 0,
			'data'        => $data,
		);

		return new WP_REST_Response( $response_data, $status );
	}

	/**
	 * Map API HTTP status codes to error codes
	 *
	 * @param int    $status_code HTTP status code.
	 * @param string $provider    Provider name.
	 * @return string Error code.
	 */
	public static function map_http_error( $status_code, $provider = '' ) {
		$mapping = array(
			400 => 'api_invalid_response',
			401 => 'api_authentication_failed',
			403 => 'permission_denied',
			404 => 'api_invalid_response',
			429 => 'api_rate_limited',
			500 => 'api_server_error',
			502 => 'api_server_error',
			503 => 'api_server_error',
			504 => 'api_timeout',
		);

		return isset( $mapping[ $status_code ] ) ? $mapping[ $status_code ] : 'unknown_error';
	}

	/**
	 * Parse API error response
	 *
	 * @param mixed  $response API response.
	 * @param string $provider Provider name.
	 * @return WP_Error|null Error if detected, null otherwise.
	 */
	public static function parse_api_error( $response, $provider = '' ) {
		if ( is_wp_error( $response ) ) {
			$code = $response->get_error_code();
			
			// Map WordPress HTTP error codes
			if ( strpos( $code, 'http_request_failed' ) !== false ) {
				return self::create_error( 'api_connection_failed', '', array( 'provider' => $provider ) );
			}
			
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		
		if ( $status_code >= 400 ) {
			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body, true );
			
			$error_code = self::map_http_error( $status_code, $provider );
			$error_message = '';

			// Try to extract error message from response
			if ( is_array( $data ) ) {
				if ( isset( $data['error']['message'] ) ) {
				    $error_message = $data['error']['message'];
				} elseif ( isset( $data['message'] ) ) {
				    $error_message = $data['message'];
				} elseif ( isset( $data['error'] ) && is_string( $data['error'] ) ) {
				    $error_message = $data['error'];
				}
			}

			return self::create_error( $error_code, $error_message, array(
				'provider'    => $provider,
				'status_code' => $status_code,
				'response'    => $data,
			) );
		}

		return null;
	}

	/**
	 * Execute operation with retry logic
	 *
	 * @param callable $operation   Operation to execute.
	 * @param array    $args        Arguments to pass to operation.
	 * @param int      $max_retries Maximum retry attempts.
	 * @return mixed Operation result or WP_Error.
	 */
	public static function with_retry( $operation, $args = array(), $max_retries = null ) {
		if ( $max_retries === null ) {
			$max_retries = self::get_max_retries();
		}

		$attempt = 0;
		$last_error = null;

		while ( $attempt <= $max_retries ) {
			$attempt++;

			try {
				$result = call_user_func_array( $operation, $args );

				if ( ! is_wp_error( $result ) ) {
				    return $result;
				}

				$last_error = $result;
				$error_code = $result->get_error_code();

				// Only retry if error is retryable
				if ( ! self::is_retryable( $error_code ) ) {
				    return $result;
				}

				// Don't retry on last attempt
				if ( $attempt <= $max_retries ) {
				    $delay = self::get_retry_delay( $attempt );
				    self::log_error( 'retry_scheduled', sprintf( 'Retrying operation (attempt %d/%d) after %ds', $attempt, $max_retries, $delay ), array(
				        'error_code' => $error_code,
				    ) );
				    sleep( $delay );
				}

			} catch ( Exception $e ) {
				$last_error = self::create_error( 'unknown_error', $e->getMessage(), array(
				    'exception' => get_class( $e ),
				    'trace'     => $e->getTraceAsString(),
				) );

				if ( $attempt > $max_retries ) {
				    return $last_error;
				}

				$delay = self::get_retry_delay( $attempt );
				sleep( $delay );
			}
		}

		return $last_error ?: self::create_error( 'operation_failed' );
	}

	/**
	 * Format error for display in admin notices
	 *
	 * @param WP_Error $error       Error object.
	 * @param bool     $dismissible Whether notice is dismissible.
	 * @return string HTML notice.
	 */
	public static function format_admin_notice( $error, $dismissible = true ) {
		$message = $error->get_error_message();
		$code    = $error->get_error_code();
		$data    = $error->get_error_data();

		$class = 'notice notice-error';
		if ( $dismissible ) {
			$class .= ' is-dismissible';
		}

		$html = sprintf( '<div class="%s">', esc_attr( $class ) );
		$html .= sprintf( '<p><strong>%s</strong></p>', esc_html( $message ) );

		// Add retry button if error is retryable
		if ( self::is_retryable( $code ) && ! empty( $data['retry_action'] ) ) {
			$html .= sprintf(
				'<p><button type="button" class="button acs-retry-btn" data-action="%s" data-nonce="%s">%s</button></p>',
				esc_attr( $data['retry_action'] ),
				wp_create_nonce( 'acs_retry_action' ),
				esc_html__( 'Retry', 'ai-content-studio' )
			);
		}

		// Add support link for persistent errors
		if ( ! empty( $data['show_support'] ) ) {
			$html .= sprintf(
				'<p><a href="%s" target="_blank">%s</a></p>',
				esc_url( 'https://support.example.com/acs-help' ),
				esc_html__( 'Get Help', 'ai-content-studio' )
			);
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Get error statistics
	 *
	 * @param string $period Period to get stats for (day, week, month).
	 * @return array Error statistics.
	 */
	public static function get_error_stats( $period = 'day' ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'acs_analytics_events';
		
		// Check if table exists
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) !== $table_name ) {
			return array();
		}

		$intervals = array(
			'day'   => '1 DAY',
			'week'  => '7 DAY',
			'month' => '30 DAY',
		);

		$interval = isset( $intervals[ $period ] ) ? $intervals[ $period ] : '1 DAY';

		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT 
				JSON_UNQUOTE(JSON_EXTRACT(payload, '$.error_code')) as error_code,
				COUNT(*) as count
			FROM {$table_name}
			WHERE event_type = 'error'
			AND created_at > DATE_SUB(NOW(), INTERVAL %s)
			GROUP BY error_code
			ORDER BY count DESC
			LIMIT 10",
			$interval
		), ARRAY_A );

		return $results ?: array();
	}

	/**
	 * Register error handler hooks
	 */
	public static function register_hooks() {
		// Register shutdown handler for fatal errors
		register_shutdown_function( array( __CLASS__, 'handle_shutdown' ) );

		// Set custom error handler for non-fatal errors
		set_error_handler( array( __CLASS__, 'handle_error' ), E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED );
	}

	/**
	 * Return singleton instance for compatibility with code that calls get_instance().
	 *
	 * @return ACS_Error_Handler
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor used for singleton pattern.
	 */
	private function __construct() {
		// No initialization required for now.
	}

	/**
	 * Create the database table for error logs.
	 *
	 * @return void
	 */
	public function create_db_table() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'acs_error_logs';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			error_code VARCHAR(100) NOT NULL,
			message TEXT NOT NULL,
			data LONGTEXT NULL,
			level VARCHAR(20) DEFAULT 'error',
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id)
		) {$charset_collate};";

		if ( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}
		dbDelta( $sql );
	}

	/**
	 * Cleanup old error logs older than the supplied number of days.
	 *
	 * @param int $days Number of days to keep.
	 * @return int Number of rows deleted
	 */
	public function cleanup_old_errors( $days = 30 ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'acs_error_logs';

		// Ensure table exists
		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );
		if ( $exists !== $table_name ) {
			return 0;
		}

		$result = $wpdb->query( $wpdb->prepare( "DELETE FROM {$table_name} WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)", $days ) );
		return $result;
	}

	/**
	 * Return an array-compatible AJAX error response for a given WP_Error or string code.
	 *
	 * @param WP_Error|string $error Error object or code.
	 * @return array
	 */
	public function get_ajax_error_response( $error ) {
		if ( is_wp_error( $error ) ) {
			$code = $error->get_error_code();
			$message = $error->get_error_message();
			$data = $error->get_error_data();
		} else {
			$code = $error;
			$message = self::get_user_message( $code );
			$data = array();
		}

		return array(
			'code' => $code,
			'message' => $message,
			'retryable' => self::is_retryable( $code ),
			'retry_delay' => self::is_retryable( $code ) ? self::get_retry_delay( 1 ) : 0,
			'max_retries' => self::is_retryable( $code ) ? self::get_max_retries() : 0,
			'data' => (array) $data,
		);
	}

	/**
	 * Handle PHP shutdown (for fatal errors)
	 */
	public static function handle_shutdown() {
		$error = error_get_last();

		if ( $error !== null && in_array( $error['type'], array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR ), true ) ) {
			// Only log if it's our plugin's error
			if ( strpos( $error['file'], 'ai-content-studio' ) !== false ) {
				self::log_error( 'fatal_error', $error['message'], array(
				    'file' => $error['file'],
				    'line' => $error['line'],
				    'type' => $error['type'],
				) );
			}
		}
	}

	/**
	 * Custom error handler
	 *
	 * @param int    $errno   Error level.
	 * @param string $errstr  Error message.
	 * @param string $errfile File where error occurred.
	 * @param int    $errline Line where error occurred.
	 * @return bool
	 */
	public static function handle_error( $errno, $errstr, $errfile, $errline ) {
		// Only handle our plugin's errors
		if ( strpos( $errfile, 'ai-content-studio' ) === false ) {
			return false;
		}

		$error_types = array(
			E_WARNING         => 'warning',
			E_USER_WARNING    => 'warning',
			E_USER_NOTICE     => 'notice',
			E_USER_ERROR      => 'error',
			E_RECOVERABLE_ERROR => 'error',
		);

		$type = isset( $error_types[ $errno ] ) ? $error_types[ $errno ] : 'unknown';

		self::log_error( 'php_' . $type, $errstr, array(
			'file'  => $errfile,
			'line'  => $errline,
			'errno' => $errno,
		) );

		// Don't execute PHP's internal error handler
		return false;
	}
}
