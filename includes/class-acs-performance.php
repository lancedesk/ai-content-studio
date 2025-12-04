<?php
/**
 * Performance Optimization Class
 *
 * Handles asset optimization, caching, and performance improvements.
 *
 * @package    AI_Content_Studio
 * @subpackage Includes
 * @since      2.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ACS_Performance
 *
 * Provides performance optimizations including asset minification,
 * lazy loading, caching strategies, and database query optimization.
 */
class ACS_Performance {

	/**
	 * Singleton instance.
	 *
	 * @var ACS_Performance
	 */
	private static $instance = null;

	/**
	 * Cache group name.
	 *
	 * @var string
	 */
	const CACHE_GROUP = 'acs_cache';

	/**
	 * Transient prefix.
	 *
	 * @var string
	 */
	const TRANSIENT_PREFIX = 'acs_';

	/**
	 * Default cache expiration in seconds (1 hour).
	 *
	 * @var int
	 */
	const DEFAULT_CACHE_EXPIRATION = 3600;

	/**
	 * Get singleton instance.
	 *
	 * @return ACS_Performance
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		// Asset optimization.
		add_filter( 'script_loader_tag', array( $this, 'add_async_defer' ), 10, 3 );
		add_filter( 'style_loader_tag', array( $this, 'add_preload_hint' ), 10, 4 );

		// Database optimization.
		add_action( 'acs_daily_cleanup', array( $this, 'cleanup_expired_cache' ) );

		// Schedule cleanup if not already scheduled.
		if ( ! wp_next_scheduled( 'acs_daily_cleanup' ) ) {
			wp_schedule_event( time(), 'daily', 'acs_daily_cleanup' );
		}
	}

	// =========================================================================
	// Asset Optimization
	// =========================================================================

	/**
	 * Add async/defer attributes to scripts.
	 *
	 * @param string $tag    Script HTML tag.
	 * @param string $handle Script handle.
	 * @param string $src    Script source URL.
	 * @return string Modified script tag.
	 */
	public function add_async_defer( $tag, $handle, $src ) {
		// Only modify our plugin's scripts.
		if ( strpos( $handle, 'acs-' ) !== 0 ) {
			return $tag;
		}

		// Scripts that should load with defer (non-blocking).
		$defer_scripts = array(
			'acs-interactions',
			'acs-error-handler',
			'acs-form-validation',
		);

		if ( in_array( $handle, $defer_scripts, true ) ) {
			$tag = str_replace( ' src', ' defer src', $tag );
		}

		return $tag;
	}

	/**
	 * Add preload hints for critical styles.
	 *
	 * @param string $html   Link tag HTML.
	 * @param string $handle Style handle.
	 * @param string $href   Stylesheet URL.
	 * @param string $media  Media attribute.
	 * @return string Modified link tag.
	 */
	public function add_preload_hint( $html, $handle, $href, $media ) {
		// Add preload for main admin stylesheet.
		if ( 'acs-unified-admin' === $handle ) {
			$preload = sprintf(
				'<link rel="preload" href="%s" as="style">',
				esc_url( $href )
			);
			return $preload . $html;
		}

		return $html;
	}

	/**
	 * Get optimized script URL (minified if available).
	 *
	 * @param string $script_path Relative path to script.
	 * @return string Script URL.
	 */
	public function get_script_url( $script_path ) {
		$min_path = str_replace( '.js', '.min.js', $script_path );

		// Use minified version in production.
		if ( ! defined( 'SCRIPT_DEBUG' ) || ! SCRIPT_DEBUG ) {
			if ( file_exists( ACS_PLUGIN_PATH . $min_path ) ) {
				return ACS_PLUGIN_URL . $min_path;
			}
		}

		return ACS_PLUGIN_URL . $script_path;
	}

	/**
	 * Get optimized style URL (minified if available).
	 *
	 * @param string $style_path Relative path to stylesheet.
	 * @return string Style URL.
	 */
	public function get_style_url( $style_path ) {
		$min_path = str_replace( '.css', '.min.css', $style_path );

		// Use minified version in production.
		if ( ! defined( 'SCRIPT_DEBUG' ) || ! SCRIPT_DEBUG ) {
			if ( file_exists( ACS_PLUGIN_PATH . $min_path ) ) {
				return ACS_PLUGIN_URL . $min_path;
			}
		}

		return ACS_PLUGIN_URL . $style_path;
	}

	// =========================================================================
	// Caching Strategies
	// =========================================================================

	/**
	 * Get cached value or execute callback.
	 *
	 * @param string   $key        Cache key.
	 * @param callable $callback   Callback to generate value.
	 * @param int      $expiration Cache expiration in seconds.
	 * @return mixed Cached or generated value.
	 */
	public function remember( $key, $callback, $expiration = self::DEFAULT_CACHE_EXPIRATION ) {
		$cache_key = self::TRANSIENT_PREFIX . $key;
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		$value = call_user_func( $callback );

		if ( null !== $value && false !== $value ) {
			set_transient( $cache_key, $value, $expiration );
		}

		return $value;
	}

	/**
	 * Get cached value.
	 *
	 * @param string $key Cache key.
	 * @return mixed|false Cached value or false.
	 */
	public function get( $key ) {
		return get_transient( self::TRANSIENT_PREFIX . $key );
	}

	/**
	 * Set cached value.
	 *
	 * @param string $key        Cache key.
	 * @param mixed  $value      Value to cache.
	 * @param int    $expiration Cache expiration in seconds.
	 * @return bool Success.
	 */
	public function set( $key, $value, $expiration = self::DEFAULT_CACHE_EXPIRATION ) {
		return set_transient( self::TRANSIENT_PREFIX . $key, $value, $expiration );
	}

	/**
	 * Delete cached value.
	 *
	 * @param string $key Cache key.
	 * @return bool Success.
	 */
	public function delete( $key ) {
		return delete_transient( self::TRANSIENT_PREFIX . $key );
	}

	/**
	 * Clear all plugin cache.
	 *
	 * @return int Number of transients deleted.
	 */
	public function clear_all() {
		global $wpdb;

		$prefix  = '_transient_' . self::TRANSIENT_PREFIX;
		$results = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$prefix . '%',
				'_transient_timeout_' . self::TRANSIENT_PREFIX . '%'
			)
		);

		return $results;
	}

	/**
	 * Cleanup expired cache entries.
	 */
	public function cleanup_expired_cache() {
		global $wpdb;

		// Delete expired transients.
		$wpdb->query(
			"DELETE a, b FROM {$wpdb->options} a
			INNER JOIN {$wpdb->options} b ON b.option_name = CONCAT('_transient_timeout_', SUBSTRING(a.option_name, 12))
			WHERE a.option_name LIKE '_transient_acs_%'
			AND b.option_value < UNIX_TIMESTAMP()"
		);

		// Cleanup old error logs (keep 30 days).
		if ( class_exists( 'ACS_Error_Handler' ) ) {
			ACS_Error_Handler::get_instance()->cleanup_old_errors( 30 );
		}

		// Cleanup old analytics data (keep 90 days).
		if ( class_exists( 'ACS_Analytics' ) ) {
			ACS_Analytics::cleanup_old_data( 90 );
		}
	}

	// =========================================================================
	// Database Query Optimization
	// =========================================================================

	/**
	 * Get cached dashboard metrics.
	 *
	 * @return array Dashboard metrics.
	 */
	public function get_dashboard_metrics() {
		return $this->remember(
			'dashboard_metrics',
			function () {
				if ( ! class_exists( 'ACS_Analytics' ) ) {
					return array();
				}

				return array(
					'total_generations'   => ACS_Analytics::get_total_generations(),
					'generations_today'   => ACS_Analytics::get_generations_count( 'today' ),
					'generations_week'    => ACS_Analytics::get_generations_count( 'week' ),
					'generations_month'   => ACS_Analytics::get_generations_count( 'month' ),
					'avg_tokens'          => ACS_Analytics::get_average_tokens(),
					'total_cost'          => ACS_Analytics::get_total_cost(),
					'popular_content_types' => ACS_Analytics::get_popular_content_types( 5 ),
					'provider_usage'      => ACS_Analytics::get_provider_usage(),
				);
			},
			300 // 5 minutes.
		);
	}

	/**
	 * Get cached provider status.
	 *
	 * @return array Provider status data.
	 */
	public function get_provider_status() {
		return $this->remember(
			'provider_status',
			function () {
				$settings  = get_option( 'acs_settings', array() );
				$providers = isset( $settings['providers'] ) ? $settings['providers'] : array();
				$status    = array();

				foreach ( $providers as $provider => $config ) {
					$status[ $provider ] = array(
						'enabled'   => ! empty( $config['enabled'] ),
						'configured' => ! empty( $config['api_key'] ),
					);
				}

				return $status;
			},
			600 // 10 minutes.
		);
	}

	/**
	 * Invalidate dashboard cache.
	 */
	public function invalidate_dashboard_cache() {
		$this->delete( 'dashboard_metrics' );
		$this->delete( 'provider_status' );
	}

	// =========================================================================
	// Lazy Loading
	// =========================================================================

	/**
	 * Generate lazy loading placeholder.
	 *
	 * @param string $type    Component type (image, chart, table).
	 * @param array  $options Options for placeholder.
	 * @return string Placeholder HTML.
	 */
	public function get_lazy_placeholder( $type, $options = array() ) {
		$defaults = array(
			'width'  => '100%',
			'height' => '200px',
			'class'  => '',
		);
		$options  = wp_parse_args( $options, $defaults );

		switch ( $type ) {
			case 'image':
				return sprintf(
					'<div class="acs-lazy-image acs-skeleton__image %s" style="width:%s;height:%s;" data-lazy="true"></div>',
					esc_attr( $options['class'] ),
					esc_attr( $options['width'] ),
					esc_attr( $options['height'] )
				);

			case 'chart':
				return sprintf(
					'<div class="acs-lazy-chart acs-skeleton-chart %s" style="height:%s;" data-lazy="true">
						<div class="acs-skeleton-chart__bar"></div>
						<div class="acs-skeleton-chart__bar"></div>
						<div class="acs-skeleton-chart__bar"></div>
						<div class="acs-skeleton-chart__bar"></div>
						<div class="acs-skeleton-chart__bar"></div>
						<div class="acs-skeleton-chart__bar"></div>
						<div class="acs-skeleton-chart__bar"></div>
					</div>',
					esc_attr( $options['class'] ),
					esc_attr( $options['height'] )
				);

			case 'table':
				$rows = isset( $options['rows'] ) ? intval( $options['rows'] ) : 5;
				$html = '<div class="acs-lazy-table acs-skeleton-table ' . esc_attr( $options['class'] ) . '" data-lazy="true">';
				for ( $i = 0; $i < $rows; $i++ ) {
					$html .= '<div class="acs-skeleton-table__row">
						<div class="acs-skeleton-table__cell acs-skeleton-table__cell--small"><div class="acs-skeleton__line"></div></div>
						<div class="acs-skeleton-table__cell acs-skeleton-table__cell--large"><div class="acs-skeleton__line"></div></div>
						<div class="acs-skeleton-table__cell"><div class="acs-skeleton__line"></div></div>
					</div>';
				}
				$html .= '</div>';
				return $html;

			case 'stats':
				$count = isset( $options['count'] ) ? intval( $options['count'] ) : 4;
				$html  = '<div class="acs-lazy-stats acs-skeleton-stats ' . esc_attr( $options['class'] ) . '" data-lazy="true">';
				for ( $i = 0; $i < $count; $i++ ) {
					$html .= '<div class="acs-skeleton-stat">
						<div class="acs-skeleton-stat__value"></div>
						<div class="acs-skeleton-stat__label"></div>
					</div>';
				}
				$html .= '</div>';
				return $html;

			default:
				return '<div class="acs-skeleton ' . esc_attr( $options['class'] ) . '">
					<div class="acs-skeleton__line acs-skeleton__line--title"></div>
					<div class="acs-skeleton__line"></div>
					<div class="acs-skeleton__line"></div>
					<div class="acs-skeleton__line acs-skeleton__line--short"></div>
				</div>';
		}
	}

	// =========================================================================
	// Query Optimization Helpers
	// =========================================================================

	/**
	 * Optimize query for large datasets.
	 *
	 * @param string $query    SQL query.
	 * @param int    $limit    Maximum results.
	 * @param int    $offset   Offset for pagination.
	 * @return string Optimized query.
	 */
	public function optimize_query( $query, $limit = 100, $offset = 0 ) {
		// Add LIMIT if not present.
		if ( stripos( $query, 'LIMIT' ) === false ) {
			$query .= sprintf( ' LIMIT %d OFFSET %d', intval( $limit ), intval( $offset ) );
		}

		return $query;
	}

	/**
	 * Get batched results for large datasets.
	 *
	 * @param callable $query_callback Callback that accepts $limit and $offset.
	 * @param int      $batch_size     Number of items per batch.
	 * @param int      $max_items      Maximum total items to retrieve.
	 * @return array All results.
	 */
	public function get_batched_results( $query_callback, $batch_size = 100, $max_items = 1000 ) {
		$results = array();
		$offset  = 0;

		while ( $offset < $max_items ) {
			$batch = call_user_func( $query_callback, $batch_size, $offset );

			if ( empty( $batch ) ) {
				break;
			}

			$results = array_merge( $results, $batch );
			$offset += $batch_size;

			if ( count( $batch ) < $batch_size ) {
				break;
			}
		}

		return $results;
	}

	// =========================================================================
	// Performance Metrics
	// =========================================================================

	/**
	 * Start performance timer.
	 *
	 * @param string $label Timer label.
	 */
	public function start_timer( $label ) {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		$GLOBALS['acs_timers'][ $label ] = microtime( true );
	}

	/**
	 * End performance timer and log result.
	 *
	 * @param string $label Timer label.
	 * @return float|null Elapsed time in seconds.
	 */
	public function end_timer( $label ) {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return null;
		}

		if ( ! isset( $GLOBALS['acs_timers'][ $label ] ) ) {
			return null;
		}

		$elapsed = microtime( true ) - $GLOBALS['acs_timers'][ $label ];
		unset( $GLOBALS['acs_timers'][ $label ] );

		if ( defined( 'ACS_DEBUG_PERFORMANCE' ) && ACS_DEBUG_PERFORMANCE ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( sprintf( '[ACS Performance] %s: %.4f seconds', $label, $elapsed ) );
		}

		return $elapsed;
	}

	/**
	 * Get memory usage.
	 *
	 * @param bool $peak Get peak memory usage.
	 * @return string Formatted memory usage.
	 */
	public function get_memory_usage( $peak = false ) {
		$bytes = $peak ? memory_get_peak_usage( true ) : memory_get_usage( true );
		return size_format( $bytes );
	}
}
