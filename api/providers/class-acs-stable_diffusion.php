<?php
/**
 * Stable Diffusion provider stub
 *
 * This is a minimal adapter. Integrate with your chosen Stable Diffusion
 * endpoint (local or hosted) and implement `generate_image()` to return
 * an array with at least a `url` key or a WP_Error on failure.
 *
 * @package ACS
 */
if ( ! class_exists( 'ACS_Stable_Diffusion' ) ) :
class ACS_Stable_Diffusion {
    protected $api_key = '';

    public function __construct( $api_key = '' ) {
        $this->api_key = $api_key;
    }

    public function generate_image( $prompt, $options = array() ) {
        // Placeholder implementation: return a WP_Error so caller will fallback
        return new WP_Error( 'not_implemented', __( 'Stable Diffusion image generation not configured.', 'ai-content-studio' ) );
    }
}
endif;
