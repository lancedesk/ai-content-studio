<?php
/**
 * Unsplash provider adapter (fallback)
 *
 * This adapter can be extended to call the Unsplash API. For now it performs
 * a simple search via Unsplash Source if no API key is configured. Returns an
 * array with a `url` key pointing to an image.
 *
 * @package ACS
 */
if ( ! class_exists( 'ACS_Unsplash' ) ) :
class ACS_Unsplash {
    protected $api_key = '';

    public function __construct( $api_key = '' ) {
        $this->api_key = $api_key;
    }

    public function generate_image( $prompt, $options = array() ) {
        // If an API key and proper implementation exist, use the official API.
        // Fallback: use Unsplash Source (no API key) to fetch a related image.
        $query = rawurlencode( substr( $prompt, 0, 60 ) );
        $url = "https://source.unsplash.com/featured/?{$query}";

        return array(
            'url' => $url,
            'provider' => 'unsplash',
        );
    }
}
endif;
