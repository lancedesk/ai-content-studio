<?php
// Simulate admin POST generation via CLI and dump prompt + provider logs.
// Minimal WP stubs (borrowed from generate_end_to_end.php)
if ( ! function_exists( 'wp_strip_all_tags' ) ) { function wp_strip_all_tags( $str ) { return strip_tags( $str ); } }
if ( ! function_exists( 'sanitize_text_field' ) ) { function sanitize_text_field( $str ) { return is_string( $str ) ? trim( strip_tags( $str ) ) : $str; } }
if ( ! function_exists( 'sanitize_textarea_field' ) ) { function sanitize_textarea_field( $s ) { return is_string($s) ? trim($s) : $s; } }
if ( ! function_exists( 'sanitize_title' ) ) { function sanitize_title( $title ) { $title = preg_replace('/[^A-Za-z0-9-]+/', '-', strtolower($title)); return trim($title, '-'); } }
if ( ! function_exists( 'home_url' ) ) { function home_url( $path = '/' ) { return 'https://example.org' . $path; } }
if ( ! function_exists( 'get_option' ) ) { function get_option( $key, $default = null ) { global $acs_test_options; if ( isset( $acs_test_options['acs_settings'] ) ) return $acs_test_options['acs_settings']; return $default; } }
if ( ! function_exists( 'get_posts' ) ) { function get_posts( $args = array() ) { return array(); } }
if ( ! function_exists( 'wp_trim_words' ) ) { function wp_trim_words( $text, $num_words = 55, $more = '...' ) { $words = preg_split('/\s+/', strip_tags($text)); if ( count($words) <= $num_words ) return implode(' ', $words); return implode(' ', array_slice($words,0,$num_words)) . $more; } }
if ( ! function_exists( 'wp_remote_request' ) ) {
    function wp_remote_request( $url, $args = array() ) {
        $method = isset( $args['method'] ) ? strtoupper( $args['method'] ) : 'POST';
        $headers = isset( $args['headers'] ) && is_array( $args['headers'] ) ? $args['headers'] : array();
        $body = isset( $args['body'] ) ? $args['body'] : null;
        $timeout = isset( $args['timeout'] ) ? intval( $args['timeout'] ) : 60;

        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_TIMEOUT, $timeout );
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );

        $curl_headers = array();
        foreach ( $headers as $k => $v ) {
            if ( is_int( $k ) ) { $curl_headers[] = $v; } else { $curl_headers[] = $k . ': ' . $v; }
        }

        if ( $method === 'POST' ) {
            curl_setopt( $ch, CURLOPT_POST, true );
            if ( $body !== null ) {
                curl_setopt( $ch, CURLOPT_POSTFIELDS, is_array( $body ) ? http_build_query( $body ) : $body );
            }
        } else {
            curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, $method );
            if ( $body !== null ) { curl_setopt( $ch, CURLOPT_POSTFIELDS, is_array( $body ) ? http_build_query( $body ) : $body ); }
        }

        if ( ! empty( $curl_headers ) ) { curl_setopt( $ch, CURLOPT_HTTPHEADER, $curl_headers ); }
        curl_setopt( $ch, CURLOPT_HEADER, true );
        $resp = curl_exec( $ch );
        if ( $resp === false ) { $err = curl_error( $ch ); curl_close( $ch ); return new WP_Error( 'http_request_failed', $err ); }
        $header_size = curl_getinfo( $ch, CURLINFO_HEADER_SIZE );
        $status = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
        $header_text = substr( $resp, 0, $header_size );
        $body = substr( $resp, $header_size );
        curl_close( $ch );
        $lines = preg_split('/\r?\n/', trim( $header_text ) );
        $parsed_headers = array();
        foreach ( $lines as $line ) { if ( strpos( $line, ':' ) !== false ) { list( $hk, $hv ) = explode( ':', $line, 2 ); $parsed_headers[ strtolower( trim( $hk ) ) ] = trim( $hv ); } }
        return array( 'headers' => $parsed_headers, 'body' => $body, 'response' => array( 'code' => $status ) );
    }
}
if ( ! function_exists( 'wp_remote_post' ) ) { function wp_remote_post( $url, $args = array() ) { return wp_remote_request( $url, array_merge( $args, array('method' => 'POST') ) ); } }
if ( ! function_exists( 'wp_remote_retrieve_response_code' ) ) { function wp_remote_retrieve_response_code( $response ) { return is_array( $response ) && isset( $response['response']['code'] ) ? $response['response']['code'] : 0; } }
if ( ! function_exists( 'wp_remote_retrieve_body' ) ) { function wp_remote_retrieve_body( $response ) { return is_array( $response ) && isset( $response['body'] ) ? $response['body'] : ''; } }
if ( ! class_exists( 'WP_Query' ) ) { class WP_Query { public $posts = array(); public function __construct( $args = array() ) { } public function have_posts() { return false; } } }
if ( ! class_exists( 'WP_Error' ) ) { class WP_Error { private $code; private $message; private $data; public function __construct( $code = '', $message = '', $data = array() ) { $this->code = $code; $this->message = $message; $this->data = $data; } public function get_error_message() { return $this->message; } } }
if ( ! function_exists( 'is_wp_error' ) ) { function is_wp_error( $val ) { return ( is_object( $val ) && $val instanceof WP_Error ); } }
if ( ! function_exists( 'esc_attr' ) ) { function esc_attr( $s ) { return is_scalar($s) ? htmlspecialchars( (string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ) : $s; } }
if ( ! function_exists( 'esc_html' ) ) { function esc_html( $s ) { return is_scalar($s) ? htmlspecialchars( (string)$s, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8' ) : $s; } }
if ( ! function_exists( '__' ) ) { function __( $str, $domain = null ) { return $str; } }
if ( ! function_exists( 'get_current_user_id' ) ) { function get_current_user_id() { return 1; } }
if ( ! defined( 'ACS_PLUGIN_PATH' ) ) define( 'ACS_PLUGIN_PATH', realpath( __DIR__ . '/..' ) . DIRECTORY_SEPARATOR );
if ( ! defined( 'WP_CONTENT_DIR' ) ) define( 'WP_CONTENT_DIR', dirname( __DIR__ ) );

// Load secrets if present
$groq_key = getenv('GROQ_KEY');
$secrets_file = realpath( __DIR__ . '/..' ) . DIRECTORY_SEPARATOR . 'secrets.php';
if ( (!$groq_key || strlen($groq_key) < 8) && file_exists( $secrets_file ) ) {
    $s = include $secrets_file;
    if ( is_array( $s ) && ! empty( $s['GROQ_KEY'] ) ) { $groq_key = trim( $s['GROQ_KEY'] ); }
}

// Provide settings for admin page
global $acs_test_options;
if ( $groq_key && strlen( $groq_key ) > 8 ) {
    $acs_test_options['acs_settings'] = array(
        'default_provider' => 'groq',
        'backup_providers' => array(),
        'providers' => array( 'groq' => array( 'api_key' => $groq_key, 'enabled' => true ) ),
    );
} else {
    $acs_test_options['acs_settings'] = array(
        'default_provider' => 'mock',
        'backup_providers' => array(),
        'providers' => array( 'mock' => array( 'api_key' => 'mockkey', 'enabled' => true ) ),
    );
}

require_once ACS_PLUGIN_PATH . 'admin/class-acs-admin.php';

// Instantiate admin and call private generate_full_content via Reflection
$admin = new ACS_Admin( 'ai-content-studio', '1.0.0' );
$ref = new ReflectionClass( $admin );
if ( $ref->hasMethod( 'generate_full_content' ) ) {
    $m = $ref->getMethod( 'generate_full_content' );
    $m->setAccessible( true );
    $api_key = $acs_test_options['acs_settings'][ $acs_test_options['acs_settings']['default_provider'] ?? 'groq' ]['api_key'] ?? '';
    $topic = 'The Benefits of AI Content Generation for Small Businesses';
    $keywords = 'AI, content generation, small business, SEO, automation';
    $word_count = 'medium';
    $result = $m->invoke( $admin, $api_key, $topic, $keywords, $word_count );
    echo "\n=== generate_full_content() returned: ===\n";
    var_export( $result );
} else {
    echo "Method generate_full_content not found\n";
}

// Print prompt debug and global content debug logs
$prompt_log = WP_CONTENT_DIR . '/acs_prompt_debug.log';
$content_log = WP_CONTENT_DIR . '/acs_content_debug.log';

echo "\n\n=== Prompt Debug Log (wp-content/acs_prompt_debug.log) ===\n";
if ( file_exists( $prompt_log ) ) { echo file_get_contents( $prompt_log ); } else { echo "(not found)\n"; }

echo "\n\n=== Content Debug Log (wp-content/acs_content_debug.log) ===\n";
if ( file_exists( $content_log ) ) { echo file_get_contents( $content_log ); } else { echo "(not found)\n"; }

?>