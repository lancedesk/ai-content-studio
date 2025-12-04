<?php
// End-to-end generation test using mock provider. Stubs minimal WP functions and classes.

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
    function wp_strip_all_tags( $str ) { return strip_tags( $str ); }
}
if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( $str ) { return is_string( $str ) ? trim( strip_tags( $str ) ) : $str; }
}
if ( ! function_exists( 'sanitize_title' ) ) {
    function sanitize_title( $title ) { $title = preg_replace('/[^A-Za-z0-9-]+/', '-', strtolower($title)); return trim($title, '-'); }
}
if ( ! function_exists( 'home_url' ) ) {
    function home_url( $path = '/' ) { return 'https://example.org' . $path; }
}
if ( ! function_exists( 'get_option' ) ) {
    function get_option( $key, $default = null ) {
        global $acs_test_options;
        if ( isset( $acs_test_options['acs_settings'] ) ) return $acs_test_options['acs_settings'];
        return $default;
    }
}
if ( ! function_exists( 'get_posts' ) ) {
    function get_posts( $args = array() ) { return array(); }
}
if ( ! function_exists( 'wp_trim_words' ) ) {
    function wp_trim_words( $text, $num_words = 55, $more = '...' ) { $words = preg_split('/\s+/', strip_tags($text)); if ( count($words) <= $num_words ) return implode(' ', $words); return implode(' ', array_slice($words,0,$num_words)) . $more; }
}
if ( ! function_exists( 'wp_parse_args' ) ) {
    function wp_parse_args( $args, $defaults = array() ) {
        if ( is_object( $args ) ) {
            $args = get_object_vars( $args );
        }
        if ( is_array( $args ) ) {
            return array_merge( $defaults, $args );
        }
        if ( is_string( $args ) ) {
            parse_str( $args, $parsed );
            return array_merge( $defaults, $parsed );
        }
        return $defaults;
    }
}
if ( ! function_exists( 'wp_json_encode' ) ) {
    function wp_json_encode( $data ) { return json_encode( $data ); }
}
if ( ! function_exists( 'wp_insert_post' ) ) { function wp_insert_post( $arr ) { return 123; } }
if ( ! function_exists( 'update_post_meta' ) ) { function update_post_meta( $id, $k, $v ) { return true; } }
if ( ! function_exists( 'get_current_user_id' ) ) { function get_current_user_id() { return 1; } }
if ( ! function_exists( 'current_time' ) ) { function current_time( $f = 'mysql' ) { return date('Y-m-d H:i:s'); } }
if ( ! function_exists( 'wp_remote_request' ) ) {
    function wp_remote_request( $url, $args = array() ) {
        // Minimal implementation using cURL to mimic WP HTTP API for testing
        $method = isset( $args['method'] ) ? strtoupper( $args['method'] ) : 'POST';
        $headers = isset( $args['headers'] ) && is_array( $args['headers'] ) ? $args['headers'] : array();
        $body = isset( $args['body'] ) ? $args['body'] : null;
        $timeout = isset( $args['timeout'] ) ? intval( $args['timeout'] ) : 60;

        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_TIMEOUT, $timeout );
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
        // Allow self-signed in local env; change as needed
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );

        $curl_headers = array();
        foreach ( $headers as $k => $v ) {
            if ( is_int( $k ) ) {
                $curl_headers[] = $v;
            } else {
                $curl_headers[] = $k . ': ' . $v;
            }
        }

        if ( $method === 'POST' ) {
            curl_setopt( $ch, CURLOPT_POST, true );
            if ( $body !== null ) {
                curl_setopt( $ch, CURLOPT_POSTFIELDS, is_array( $body ) ? http_build_query( $body ) : $body );
            }
        } else {
            curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, $method );
            if ( $body !== null ) {
                curl_setopt( $ch, CURLOPT_POSTFIELDS, is_array( $body ) ? http_build_query( $body ) : $body );
            }
        }

        if ( ! empty( $curl_headers ) ) {
            curl_setopt( $ch, CURLOPT_HTTPHEADER, $curl_headers );
        }

        // Capture headers
        curl_setopt( $ch, CURLOPT_HEADER, true );
        $resp = curl_exec( $ch );
        if ( $resp === false ) {
            $err = curl_error( $ch );
            curl_close( $ch );
            return new WP_Error( 'http_request_failed', $err );
        }

        $header_size = curl_getinfo( $ch, CURLINFO_HEADER_SIZE );
        $status = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
        $header_text = substr( $resp, 0, $header_size );
        $body = substr( $resp, $header_size );
        curl_close( $ch );

        // Parse headers into associative array (simple parse)
        $lines = preg_split('/\r?\n/', trim( $header_text ) );
        $parsed_headers = array();
        foreach ( $lines as $line ) {
            if ( strpos( $line, ':' ) !== false ) {
                list( $hk, $hv ) = explode( ':', $line, 2 );
                $parsed_headers[ strtolower( trim( $hk ) ) ] = trim( $hv );
            }
        }

        return array( 'headers' => $parsed_headers, 'body' => $body, 'response' => array( 'code' => $status ) );
    }
}

if ( ! function_exists( 'wp_remote_retrieve_response_code' ) ) {
    function wp_remote_retrieve_response_code( $response ) { return is_array( $response ) && isset( $response['response']['code'] ) ? $response['response']['code'] : 0; }
}
if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
    function wp_remote_retrieve_body( $response ) { return is_array( $response ) && isset( $response['body'] ) ? $response['body'] : ''; }
}
if ( ! function_exists( 'wp_remote_retrieve_headers' ) ) {
    function wp_remote_retrieve_headers( $response ) { return is_array( $response ) && isset( $response['headers'] ) ? $response['headers'] : array(); }
}
if ( ! class_exists( 'WP_Query' ) ) {
    class WP_Query { public $posts = array(); public function __construct( $args = array() ) { } public function have_posts() { return false; } }
}
if ( ! class_exists( 'WP_Error' ) ) {
    class WP_Error {
        private $code;
        private $message;
        private $data;
        public function __construct( $code = '', $message = '', $data = array() ) { $this->code = $code; $this->message = $message; $this->data = $data; }
        public function get_error_message() { return $this->message; }
        public function get_error_code() { return $this->code; }
        public function get_error_data() { return $this->data; }
    }
}
if ( ! function_exists( 'is_wp_error' ) ) {
    function is_wp_error( $val ) { return ( is_object( $val ) && $val instanceof WP_Error ); }
}
if ( ! function_exists( 'esc_attr' ) ) { function esc_attr( $s ) { return is_scalar($s) ? htmlspecialchars( (string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ) : $s; } }
if ( ! function_exists( 'esc_html' ) ) { function esc_html( $s ) { return is_scalar($s) ? htmlspecialchars( (string)$s, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8' ) : $s; } }
if ( ! function_exists( '__' ) ) { function __( $str, $domain = null ) { return $str; } }

require_once __DIR__ . '/../api/class-acs-ai-provider.php';
require_once __DIR__ . '/../generators/class-acs-content-generator.php';
require_once __DIR__ . '/../api/providers/class-acs-mock.php';

// Define plugin path constants for the test harness
if ( ! defined( 'ACS_PLUGIN_PATH' ) ) {
    define( 'ACS_PLUGIN_PATH', realpath( __DIR__ . '/..' ) . DIRECTORY_SEPARATOR );
}
if ( ! defined( 'ACS_PLUGIN_URL' ) ) {
    define( 'ACS_PLUGIN_URL', 'http://localhost/wp-content/plugins/ai-content-studio/' );
}
if ( ! defined( 'ACS_VERSION' ) ) {
    define( 'ACS_VERSION', '1.0.0' );
}

// Provide settings with mock provider configured
global $acs_test_options;

// Minimal $wpdb stub to prevent DB write errors in test harness
global $wpdb;
if ( ! isset( $wpdb ) || $wpdb === null ) {
    $wpdb = new class {
        public $prefix = 'wp_';
        public function insert( $table, $data, $format = null ) { return true; }
    };
}

// Use GROQ API key from environment variable `GROQ_KEY`.
// If not present (Windows shell may not have it), try loading a local secrets file
// at the plugin root: `secrets.php` (this file is gitignored by default).
$groq_key = getenv('GROQ_KEY');
if ( ! $groq_key || strlen( $groq_key ) < 8 ) {
    $secrets_file = realpath( __DIR__ . '/..' ) . DIRECTORY_SEPARATOR . 'secrets.php';
    if ( file_exists( $secrets_file ) ) {
        $s = include $secrets_file;
        if ( is_array( $s ) && ! empty( $s['GROQ_KEY'] ) ) {
            $groq_key = trim( $s['GROQ_KEY'] );
        } elseif ( defined( 'ACS_GROQ_KEY' ) && ACS_GROQ_KEY ) {
            $groq_key = ACS_GROQ_KEY;
        }
    }
}

if ( $groq_key && strlen( $groq_key ) > 8 ) {
    $acs_test_options['acs_settings'] = array(
        'default_provider' => 'groq',
        'backup_providers' => array(),
        'providers' => array(
            'groq' => array( 'api_key' => $groq_key, 'enabled' => true ),
        ),
    );
} else {
    $acs_test_options['acs_settings'] = array(
        'default_provider' => 'mock',
        'backup_providers' => array(),
        'providers' => array(
            'mock' => array( 'api_key' => 'mockkey', 'enabled' => true ),
        ),
    );
}

$gen = new ACS_Content_Generator();
// Debug: show settings seen by generator, but redact API keys when printing
$debug_settings = get_option('acs_settings', array());
function acs_redact_settings( $s ) {
    if ( is_array( $s ) ) {
        $out = array();
        foreach ( $s as $k => $v ) {
            if ( is_array( $v ) ) {
                $out[$k] = acs_redact_settings( $v );
            } else {
                $out[$k] = $v;
            }
        }
        // If this array looks like a provider config, redact api_key values
        if ( isset( $out['providers'] ) && is_array( $out['providers'] ) ) {
            foreach ( $out['providers'] as $pk => $pv ) {
                if ( is_array( $pv ) && isset( $pv['api_key'] ) ) {
                    $out['providers'][$pk]['api_key'] = '***REDACTED***';
                }
            }
        }
        return $out;
    }
    return $s;
}
echo "Using settings (redacted): \n"; print_r( acs_redact_settings( $debug_settings ) );

// Direct provider call + parse + validate to capture validation errors
$mock = new ACS_Mock( 'mockkey' );
$test_internal_candidates = array(
    array( 'title' => 'About Our Services', 'url' => 'https://example.org/about-our-services' ),
    array( 'title' => 'AI Tools We Use', 'url' => 'https://example.org/ai-tools' ),
);
$raw = $mock->generate_content( $gen->build_prompt( 'The Benefits of AI Content Generation for Small Businesses', 'AI,content generation,small business,SEO,automation', 'short', $test_internal_candidates ), array() );
echo "\n-- RAW FROM PROVIDER --\n" . $raw . "\n\n";
$parsed = $gen->parse_generated_content( $raw );
echo "\n-- PARSED --\n"; print_r( $parsed );
// Call private validate_generated_output via Reflection to inspect detailed errors
$reflection = new ReflectionClass( $gen );
$method = $reflection->getMethod( 'validate_generated_output' );
$method->setAccessible( true );
$validation = $method->invoke( $gen, $parsed );
echo "\n-- VALIDATION --\n";
if ( $validation === true ) { echo "Validation passed\n"; } else { print_r( $validation ); }

// Try auto-fix via reflection and validate again
$fix_method = $reflection->getMethod( 'auto_fix_generated_output' );
$fix_method->setAccessible( true );
$fixed = $fix_method->invoke( $gen, $parsed );
echo "\n-- AUTO-FIXED RESULT --\n"; print_r( $fixed );
$validation2 = $method->invoke( $gen, is_array($fixed) ? $fixed : $parsed );
echo "\n-- VALIDATION AFTER AUTO-FIX --\n"; if ( $validation2 === true ) { echo "Validation passed after auto-fix\n"; } else { print_r( $validation2 ); }

$result = $gen->generate( array( 'topic' => 'The Benefits of AI Content Generation for Small Businesses', 'keywords' => 'AI,content generation,small business,SEO,automation', 'word_count' => 'short' ) );

if ( is_wp_error( $result ) ) {
    echo "Generation failed: " . $result->get_error_message() . "\n";
} else {
    echo "Generation succeeded. Result keys:\n";
    print_r( array_keys( $result ) );
    echo "\nTitle: " . ( $result['title'] ?? '' ) . "\n";
    echo "Meta: " . ( $result['meta_description'] ?? '' ) . "\n";
    echo "Provider: " . ( $result['provider'] ?? '' ) . "\n";
    if ( isset( $result['acs_validation_report'] ) ) {
        echo "Validation report:\n"; print_r( $result['acs_validation_report'] );
    }
}

?>