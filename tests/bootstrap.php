<?php
// Lightweight bootstrap for plugin tests.
// NOTE: Full WP unit tests require the WP test suite; this bootstrap is minimal
// and intended for simple, isolated class tests that don't depend on WP core.

define( 'PHPUNIT_RUNNING', true );

// Define WordPress constants for testing
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__) . '/');
}

// Mock WordPress functions used by the plugin
if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file) {
        return dirname($file) . '/';
    }
}

if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($file) {
        return 'http://localhost/wp-content/plugins/' . basename(dirname($file)) . '/';
    }
}

if (!function_exists('wp_die')) {
    function wp_die($message = '', $title = '', $args = array()) {
        die($message);
    }
}

// Define plugin constants
if (!defined('ACS_PLUGIN_PATH')) {
    define('ACS_PLUGIN_PATH', dirname(__DIR__) . '/');
}

if (!defined('ACS_PLUGIN_URL')) {
    define('ACS_PLUGIN_URL', 'http://localhost/wp-content/plugins/ai-content-studio/');
}

if (!function_exists('wp_content_dir')) {
    function wp_content_dir() {
        return dirname(__DIR__) . '/';
    }
}

if (!function_exists('home_url')) {
    function home_url($path = '', $scheme = null) {
        return 'http://localhost/' . ltrim($path, '/');
    }
}

// Load simple test case for unit tests
require_once __DIR__ . '/SimpleTestCase.php';

require_once dirname( __DIR__ ) . '/generators/class-acs-content-generator.php';
require_once dirname( __DIR__ ) . '/seo/class-seo-validation-result.php';
require_once dirname( __DIR__ ) . '/seo/class-content-validation-metrics.php';
require_once dirname( __DIR__ ) . '/seo/class-seo-prompt-configuration.php';
require_once dirname( __DIR__ ) . '/seo/class-meta-description-validator.php';
require_once dirname( __DIR__ ) . '/seo/class-meta-description-corrector.php';
require_once dirname( __DIR__ ) . '/seo/class-keyword-density-calculator.php';
require_once dirname( __DIR__ ) . '/seo/class-keyword-density-optimizer.php';
require_once dirname( __DIR__ ) . '/seo/class-passive-voice-analyzer.php';
require_once dirname( __DIR__ ) . '/seo/class-sentence-length-analyzer.php';
require_once dirname( __DIR__ ) . '/seo/class-transition-word-analyzer.php';
require_once dirname( __DIR__ ) . '/seo/class-readability-corrector.php';
require_once dirname( __DIR__ ) . '/seo/class-title-uniqueness-validator.php';
require_once dirname( __DIR__ ) . '/seo/class-title-optimization-engine.php';
require_once dirname( __DIR__ ) . '/seo/class-image-prompt-generator.php';
require_once dirname( __DIR__ ) . '/seo/class-alt-text-accessibility-optimizer.php';
require_once dirname( __DIR__ ) . '/seo/class-seo-validation-cache.php';
require_once dirname( __DIR__ ) . '/seo/class-smart-retry-manager.php';
require_once dirname( __DIR__ ) . '/seo/class-seo-error-handler.php';
require_once dirname( __DIR__ ) . '/seo/class-seo-validation-pipeline.php';
require_once dirname( __DIR__ ) . '/seo/class-seo-issue-detector.php';
require_once dirname( __DIR__ ) . '/seo/class-correction-prompt-generator.php';
require_once dirname( __DIR__ ) . '/seo/class-multi-pass-seo-optimizer.php';
require_once dirname( __DIR__ ) . '/seo/class-integration-compatibility-layer.php';
require_once dirname( __DIR__ ) . '/admin/class-seo-optimizer-admin.php';

// Simple in-memory test doubles for minimal WP functions used by the generator.
// These are only active in the PHPUnit context to avoid requiring the full WP test suite.
global $acs_test_posts, $acs_test_post_meta;
$acs_test_posts = array();
$acs_test_post_meta = array();

if ( ! function_exists( 'get_post' ) ) {
	function get_post( $post_id ) {
		global $acs_test_posts;
		if ( isset( $acs_test_posts[ $post_id ] ) ) {
			return (object) $acs_test_posts[ $post_id ];
		}
		return null;
	}
}

// Minimal WP helper functions used by plugin code under test.
if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ) { return is_string( $str ) ? trim( strip_tags( $str ) ) : ''; }
}
if ( ! function_exists( 'sanitize_textarea_field' ) ) {
	function sanitize_textarea_field( $str ) { return is_string( $str ) ? trim( strip_tags( $str ) ) : ''; }
}
if ( ! function_exists( 'absint' ) ) {
	function absint( $v ) { return abs( intval( $v ) ); }
}
if ( ! function_exists( 'sanitize_file_name' ) ) {
	function sanitize_file_name( $name ) { return preg_replace( '/[^A-Za-z0-9_.-]/', '-', $name ); }
}
if ( ! function_exists( 'sanitize_title' ) ) {
	function sanitize_title( $title ) { return strtolower( preg_replace( '/[^A-Za-z0-9-]+/', '-', trim( $title ) ) ); }
}
if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	function wp_strip_all_tags( $text ) { return trim( strip_tags( $text ) ); }
}
if ( ! function_exists( 'wp_kses' ) ) {
	function wp_kses( $content, $allowed_tags = array() ) { return $content; }
}
if ( ! function_exists( 'wp_kses_post' ) ) {
	function wp_kses_post( $content ) { return $content; }
}
if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( $url ) { return filter_var( $url, FILTER_SANITIZE_URL ); }
}
if ( ! function_exists( 'wp_check_filetype' ) ) {
	function wp_check_filetype( $filename ) { $ext = pathinfo( $filename, PATHINFO_EXTENSION ); $types = array( 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp' ); return array( 'ext' => $ext, 'type' => $types[ strtolower( $ext ) ] ?? '' ); }
}

// Minimal WP_Error for tests
if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		public $errors = array();
		public function __construct( $code = '', $message = '' ) { if ( $code ) $this->errors[ $code ] = array( $message ); }
		public function get_error_message() { $first = reset( $this->errors ); return is_array( $first ) ? $first[0] : ''; }
	}
}
if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) { return ( $thing instanceof WP_Error ); }
}

// Minimal options store for tests
global $acs_test_options;
$acs_test_options = array();
if ( ! function_exists( 'get_option' ) ) {
	function get_option( $key, $default = false ) { global $acs_test_options; return isset( $acs_test_options[ $key ] ) ? $acs_test_options[ $key ] : $default; }
}
if ( ! function_exists( 'update_option' ) ) {
	function update_option( $key, $value ) { global $acs_test_options; $acs_test_options[ $key ] = $value; return true; }
}

// Provider behavior control for tests
global $acs_test_provider_behavior;
$acs_test_provider_behavior = array();

// Test provider classes used to simulate provider success/failure during generate()
if ( ! class_exists( 'ACS_Groq' ) ) {
	class ACS_Groq {
		public function __construct( $api_key = '' ) {}
		public function generate_content( $prompt, $options = array() ) {
			global $acs_test_provider_behavior;
			if ( isset( $acs_test_provider_behavior['groq'] ) && $acs_test_provider_behavior['groq'] === 'error' ) {
				return new WP_Error( 'prov_error', 'Simulated provider error' );
			}
			return array(
				'title' => 'Groq Generated',
				'content' => '<p>Groq content</p>',
				'meta_description' => 'meta',
				'slug' => 'groq-generated',
				'excerpt' => 'excerpt',
				'focus_keyword' => 'focus',
				'image_prompts' => array( array( 'alt' => 'focus image' ) ),
				'internal_links' => array( 'a', 'b' ),
			);
		}
	}
}
if ( ! class_exists( 'ACS_OpenAI' ) ) {
	class ACS_OpenAI {
		public function __construct( $api_key = '' ) {}
		public function generate_content( $prompt, $options = array() ) {
			global $acs_test_provider_behavior;
			if ( isset( $acs_test_provider_behavior['openai'] ) && $acs_test_provider_behavior['openai'] === 'error' ) {
				return new WP_Error( 'prov_error', 'Simulated provider error' );
			}
			return array(
				'title' => 'OpenAI Generated',
				'content' => '<p>OpenAI content</p>',
				'meta_description' => 'meta',
				'slug' => 'openai-generated',
				'excerpt' => 'excerpt',
				'focus_keyword' => 'focus',
				'image_prompts' => array( array( 'alt' => 'focus image' ) ),
				'internal_links' => array( 'a', 'b' ),
			);
		}
	}
}

if ( ! function_exists( 'get_post_meta' ) ) {
	function get_post_meta( $post_id, $key = '', $single = true ) {
		global $acs_test_post_meta;
		if ( isset( $acs_test_post_meta[ $post_id ] ) && array_key_exists( $key, $acs_test_post_meta[ $post_id ] ) ) {
			$val = $acs_test_post_meta[ $post_id ][ $key ];
			return $single ? $val : array( $val );
		}
		return $single ? '' : array();
	}
}

if ( ! function_exists( 'update_post_meta' ) ) {
	function update_post_meta( $post_id, $key, $value ) {
		global $acs_test_post_meta;
		if ( ! isset( $acs_test_post_meta[ $post_id ] ) ) {
			$acs_test_post_meta[ $post_id ] = array();
		}
		$acs_test_post_meta[ $post_id ][ $key ] = $value;
		return true;
	}
}

if ( ! function_exists( 'wp_update_post' ) ) {
	function wp_update_post( $postarr ) {
		global $acs_test_posts;
		$id = isset( $postarr['ID'] ) ? intval( $postarr['ID'] ) : 0;
		if ( $id <= 0 || ! isset( $acs_test_posts[ $id ] ) ) {
			return 0;
		}
		if ( isset( $postarr['post_title'] ) ) {
			$acs_test_posts[ $id ]['post_title'] = $postarr['post_title'];
		}
		if ( isset( $postarr['post_content'] ) ) {
			$acs_test_posts[ $id ]['post_content'] = $postarr['post_content'];
		}
		if ( isset( $postarr['post_excerpt'] ) ) {
			$acs_test_posts[ $id ]['post_excerpt'] = $postarr['post_excerpt'];
		}
		return $id;
	}
}

if ( ! function_exists( 'set_post_thumbnail' ) ) {
	function set_post_thumbnail( $post_id, $thumb_id ) {
		update_post_meta( $post_id, '_thumbnail_id', intval( $thumb_id ) );
		return true;
	}
}

if ( ! function_exists( 'get_the_title' ) ) {
	function get_the_title( $post_id ) {
		$p = get_post( $post_id );
		return $p ? $p->post_title : '';
	}
}

if ( ! function_exists( 'current_time' ) ) {
	function current_time( $type = 'mysql' ) {
		return date( 'Y-m-d H:i:s' );
	}
}

// Mock WordPress action/filter system for testing
global $wp_actions, $wp_filters;
$wp_actions = [];
$wp_filters = [];

if ( ! function_exists( 'add_action' ) ) {
	function add_action( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
		global $wp_actions;
		if (!isset($wp_actions[$tag])) {
			$wp_actions[$tag] = [];
		}
		$wp_actions[$tag][] = [
			'function' => $function_to_add,
			'priority' => $priority,
			'accepted_args' => $accepted_args
		];
		return true;
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
		global $wp_filters;
		if (!isset($wp_filters[$tag])) {
			$wp_filters[$tag] = [];
		}
		$wp_filters[$tag][] = [
			'function' => $function_to_add,
			'priority' => $priority,
			'accepted_args' => $accepted_args
		];
		return true;
	}
}

if ( ! function_exists( 'do_action' ) ) {
	function do_action( $tag, ...$args ) {
		global $wp_actions;
		if (isset($wp_actions[$tag])) {
			foreach ($wp_actions[$tag] as $action) {
				$function = $action['function'];
				if (is_callable($function)) {
					call_user_func_array($function, $args);
				} elseif (is_array($function) && count($function) === 2) {
					$object = $function[0];
					$method = $function[1];
					if (is_object($object) && method_exists($object, $method)) {
						call_user_func_array([$object, $method], $args);
					}
				}
			}
		}
		return true;
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $tag, $value, ...$args ) {
		global $wp_filters;
		if (isset($wp_filters[$tag])) {
			foreach ($wp_filters[$tag] as $filter) {
				$function = $filter['function'];
				if (is_callable($function)) {
					$value = call_user_func_array($function, array_merge([$value], $args));
				}
			}
		}
		return $value;
	}
}

if ( ! function_exists( 'remove_action' ) ) {
	function remove_action( $tag, $function_to_remove, $priority = 10 ) {
		global $wp_actions;
		if (isset($wp_actions[$tag])) {
			foreach ($wp_actions[$tag] as $key => $action) {
				if ($action['function'] === $function_to_remove && $action['priority'] === $priority) {
					unset($wp_actions[$tag][$key]);
					break;
				}
			}
		}
		return true;
	}
}

if ( ! function_exists( 'wp_is_post_autosave' ) ) {
	function wp_is_post_autosave( $post ) {
		return false;
	}
}

if ( ! function_exists( 'wp_is_post_revision' ) ) {
	function wp_is_post_revision( $post ) {
		return false;
	}
}

if ( ! function_exists( 'add_meta_box' ) ) {
	function add_meta_box( $id, $title, $callback, $screen = null, $context = 'advanced', $priority = 'default', $callback_args = null ) {
		return true;
	}
}

// Mock WordPress admin menu globals
global $menu, $submenu;
if (!isset($menu)) {
    $menu = [];
}
if (!isset($submenu)) {
    $submenu = [];
}

if ( ! function_exists( 'add_menu_page' ) ) {
	function add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function = '', $icon_url = '', $position = null ) {
		global $menu;
		$menu[] = [$menu_title, $capability, $menu_slug, $page_title, 'menu-top', '', $icon_url];
		return $menu_slug;
	}
}

if ( ! function_exists( 'add_submenu_page' ) ) {
	function add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function = '' ) {
		global $submenu;
		if (!isset($submenu[$parent_slug])) {
			$submenu[$parent_slug] = [];
		}
		$submenu[$parent_slug][] = [$menu_title, $capability, $menu_slug];
		return $menu_slug;
	}
}

// Mock WordPress user and capability functions
if ( ! function_exists( 'wp_get_current_user' ) ) {
	function wp_get_current_user() {
		static $user = null;
		if ($user === null) {
			$user = new MockWPUser();
		}
		return $user;
	}
}

if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( $capability ) {
		$user = wp_get_current_user();
		return $user->has_cap($capability);
	}
}

if ( ! function_exists( 'get_role' ) ) {
	function get_role( $role ) {
		return new MockWPRole($role);
	}
}

if ( ! function_exists( 'wp_generate_password' ) ) {
	function wp_generate_password( $length = 12, $special_chars = true, $extra_special_chars = false ) {
		return 'test_password_' . rand(1000, 9999);
	}
}

if ( ! function_exists( 'admin_url' ) ) {
	function admin_url( $path = '', $scheme = 'admin' ) {
		return 'http://localhost/wp-admin/' . ltrim($path, '/');
	}
}

if ( ! function_exists( 'human_time_diff' ) ) {
	function human_time_diff( $from, $to = '' ) {
		if ( empty( $to ) ) {
			$to = time();
		}
		$diff = (int) abs( $to - $from );
		if ( $diff < HOUR_IN_SECONDS ) {
			$mins = round( $diff / MINUTE_IN_SECONDS );
			return sprintf( '%d minutes', $mins );
		}
		return sprintf( '%d hours', round( $diff / HOUR_IN_SECONDS ) );
	}
}

if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}

if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
	define( 'MINUTE_IN_SECONDS', 60 );
}

// Mock WordPress user class
if ( ! class_exists( 'MockWPUser' ) ) {
	class MockWPUser {
		private $caps = [];
		private $roles = [];
		
		public function add_cap( $cap ) {
			$this->caps[$cap] = true;
		}
		
		public function remove_cap( $cap ) {
			unset($this->caps[$cap]);
		}
		
		public function has_cap( $cap ) {
			return isset($this->caps[$cap]) || in_array('administrator', $this->roles);
		}
		
		public function set_role( $role ) {
			$this->roles = [$role];
			if ($role === 'administrator') {
				$this->caps = [
					'acs_generate_content' => true,
					'acs_manage_settings' => true,
					'acs_view_analytics' => true,
					'acs_manage_seo' => true,
					'manage_options' => true,
					'edit_posts' => true
				];
			}
		}
	}
}

// Mock WordPress role class
if ( ! class_exists( 'MockWPRole' ) ) {
	class MockWPRole {
		private $name;
		private $caps = [];
		
		public function __construct( $name ) {
			$this->name = $name;
		}
		
		public function add_cap( $cap ) {
			$this->caps[$cap] = true;
		}
		
		public function has_cap( $cap ) {
			return isset($this->caps[$cap]);
		}
	}
}

if ( ! function_exists( 'settings_fields' ) ) {
	function settings_fields( $option_group ) {
		return true;
	}
}

if ( ! function_exists( 'do_settings_sections' ) ) {
	function do_settings_sections( $page ) {
		return true;
	}
}

if ( ! function_exists( 'submit_button' ) ) {
	function submit_button( $text = null, $type = 'primary', $name = 'submit', $wrap = true, $other_attributes = null ) {
		echo '<input type="submit" name="' . esc_attr( $name ) . '" value="' . esc_attr( $text ?: 'Save Changes' ) . '" class="button button-' . esc_attr( $type ) . '" />';
	}
}

if ( ! function_exists( 'checked' ) ) {
	function checked( $checked, $current = true, $echo = true ) {
		$result = ( $checked == $current ) ? ' checked="checked"' : '';
		if ( $echo ) {
			echo $result;
		}
		return $result;
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) {
		return $text;
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( $text, $domain = 'default' ) {
		return esc_html( __( $text, $domain ) );
	}
}

if ( ! function_exists( 'esc_attr__' ) ) {
	function esc_attr__( $text, $domain = 'default' ) {
		return esc_attr( __( $text, $domain ) );
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( $url ) {
		return htmlspecialchars( $url, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_html_e' ) ) {
	function esc_html_e( $text, $domain = 'default' ) {
		echo esc_html( __( $text, $domain ) );
	}
}

if ( ! function_exists( 'esc_attr_e' ) ) {
	function esc_attr_e( $text, $domain = 'default' ) {
		echo esc_attr( __( $text, $domain ) );
	}
}

if ( ! function_exists( 'esc_textarea' ) ) {
	function esc_textarea( $text ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'get_edit_post_link' ) ) {
	function get_edit_post_link( $post_id ) {
		return admin_url( 'post.php?post=' . $post_id . '&action=edit' );
	}
}

if ( ! function_exists( 'get_posts' ) ) {
	function get_posts( $args = [] ) {
		global $acs_test_posts;
		$posts = [];
		foreach ( $acs_test_posts as $post_data ) {
			$posts[] = (object) $post_data;
		}
		
		// Apply limit if specified
		if ( isset( $args['posts_per_page'] ) && $args['posts_per_page'] > 0 ) {
			$posts = array_slice( $posts, 0, $args['posts_per_page'] );
		}
		
		// Return IDs only if requested
		if ( isset( $args['fields'] ) && $args['fields'] === 'ids' ) {
			return array_map( function( $post ) {
				return $post->ID;
			}, $posts );
		}
		
		return $posts;
	}
}

if ( ! function_exists( 'register_setting' ) ) {
	function register_setting( $option_group, $option_name, $args = [] ) {
		return true;
	}
}

if ( ! function_exists( 'wp_create_nonce' ) ) {
	function wp_create_nonce( $action = -1 ) {
		return 'test_nonce_' . md5( $action );
	}
}

if ( ! function_exists( 'wp_parse_args' ) ) {
	function wp_parse_args( $args, $defaults = [] ) {
		if ( is_object( $args ) ) {
			$r = get_object_vars( $args );
		} elseif ( is_array( $args ) ) {
			$r = &$args;
		} else {
			parse_str( $args, $r );
		}
		
		if ( is_array( $defaults ) ) {
			return array_merge( $defaults, $r );
		}
		return $r;
	}
}

if ( ! function_exists( 'maybe_unserialize' ) ) {
	function maybe_unserialize( $original ) {
		if ( is_serialized( $original ) ) {
			return @unserialize( $original );
		}
		return $original;
	}
}

if ( ! function_exists( 'is_serialized' ) ) {
	function is_serialized( $data, $strict = true ) {
		if ( ! is_string( $data ) ) {
			return false;
		}
		$data = trim( $data );
		if ( 'N;' === $data ) {
			return true;
		}
		if ( strlen( $data ) < 4 ) {
			return false;
		}
		if ( ':' !== $data[1] ) {
			return false;
		}
		if ( $strict ) {
			$lastc = substr( $data, -1 );
			if ( ';' !== $lastc && '}' !== $lastc ) {
				return false;
			}
		} else {
			$semicolon = strpos( $data, ';' );
			$brace     = strpos( $data, '}' );
			if ( false === $semicolon && false === $brace ) {
				return false;
			}
			if ( false !== $semicolon && $semicolon < 3 ) {
				return false;
			}
			if ( false !== $brace && $brace < 4 ) {
				return false;
			}
		}
		$token = $data[0];
		switch ( $token ) {
			case 's':
				if ( $strict ) {
					if ( '"' !== substr( $data, -2, 1 ) ) {
						return false;
					}
				} elseif ( false === strpos( $data, '"' ) ) {
					return false;
				}
			case 'a':
			case 'O':
				return (bool) preg_match( "/^{$token}:[0-9]+:/s", $data );
			case 'b':
			case 'i':
			case 'd':
				$end = $strict ? '$' : '';
				return (bool) preg_match( "/^{$token}:[0-9.E+-]+;$end/", $data );
		}
		return false;
	}
}

// Mock WP_Post class
if ( ! class_exists( 'WP_Post' ) ) {
	class WP_Post {
		public $ID;
		public $post_title;
		public $post_content;
		public $post_excerpt;
		public $post_type;
		public $post_name;
		
		public function __construct( $data = [] ) {
			foreach ( $data as $key => $value ) {
				$this->$key = $value;
			}
		}
	}
}

// Mock WordPress cron functions
if ( ! function_exists( 'wp_next_scheduled' ) ) {
	function wp_next_scheduled( $hook, $args = array() ) {
		return false;
	}
}

if ( ! function_exists( 'wp_schedule_event' ) ) {
	function wp_schedule_event( $timestamp, $recurrence, $hook, $args = array() ) {
		return true;
	}
}

if ( ! function_exists( 'wp_clear_scheduled_hook' ) ) {
	function wp_clear_scheduled_hook( $hook, $args = array() ) {
		return true;
	}
}

// Mock WordPress transient functions
if ( ! function_exists( 'get_transient' ) ) {
	function get_transient( $transient ) {
		return false;
	}
}

if ( ! function_exists( 'set_transient' ) ) {
	function set_transient( $transient, $value, $expiration ) {
		return true;
	}
}

if ( ! function_exists( 'delete_transient' ) ) {
	function delete_transient( $transient ) {
		return true;
	}
}

if ( ! function_exists( 'get_current_user_id' ) ) {
	function get_current_user_id() { return 1; }
}

// Mock wpdb class for database operations
if ( ! class_exists( 'wpdb' ) ) {
	class wpdb {
		public $posts = 'wp_posts';
		public $options = 'wp_options';
		
		public function prepare( $query, ...$args ) {
			return vsprintf( str_replace( '%d', '%d', str_replace( '%s', "'%s'", $query ) ), $args );
		}
		
		public function get_results( $query, $output = OBJECT ) {
			// Mock some test data for title uniqueness testing
			$mock_posts = [
				[
					'post_id' => 1,
					'title' => 'How to Optimize SEO for WordPress',
					'post_date' => '2023-01-01 00:00:00'
				],
				[
					'post_id' => 2,
					'title' => 'Complete Guide to Content Marketing',
					'post_date' => '2023-02-01 00:00:00'
				],
				[
					'post_id' => 3,
					'title' => 'Best Blog Writing Tips for 2023',
					'post_date' => '2023-03-01 00:00:00'
				]
			];
			
			return $output === ARRAY_A ? $mock_posts : array_map(function($post) {
				return (object) $post;
			}, $mock_posts);
		}
		
		public function get_row( $query, $output = OBJECT ) {
			// Return mock cache size data
			return (object) [
				'entry_count' => 0,
				'total_size' => 0
			];
		}
		
		public function query( $query ) {
			return 0;
		}
	}
}

// Define constants if not defined
if ( ! defined( 'OBJECT' ) ) {
	define( 'OBJECT', 'OBJECT' );
}
if ( ! defined( 'ARRAY_A' ) ) {
	define( 'ARRAY_A', 'ARRAY_A' );
}

// Mock global $wpdb
global $wpdb;
if ( ! isset( $wpdb ) ) {
	$wpdb = new wpdb();
}

/**
 * Helper: create a test post in the in-memory store and return its ID.
 */
function acs_create_test_post( $args = array() ) {
	global $acs_test_posts, $acs_test_post_meta;
	$id = count( $acs_test_posts ) + 1;
	$defaults = array(
		'ID' => $id,
		'post_title' => $args['post_title'] ?? 'Test Post ' . $id,
		'post_content' => $args['post_content'] ?? 'Test content',
		'post_excerpt' => $args['post_excerpt'] ?? '',
		'post_name' => $args['post_name'] ?? 'test-post-' . $id,
	);
	$acs_test_posts[ $id ] = $defaults;
	$acs_test_post_meta[ $id ] = $args['meta'] ?? array();
	return $id;
}
