<?php
/**
 * SEO integration helper (stub)
 *
 * Provides a minimal interface to map generated content to popular SEO plugins.
 * Real implementations should detect Yoast/RankMath/SEOPress and set meta accordingly.
 *
 * @package ACS
 */
if ( ! class_exists( 'ACS_SEO_Integration' ) ) :
class ACS_SEO_Integration {

    /**
     * Map generated SEO fields into a post after creation.
     * @param int $post_id
     * @param array $generated Content array with keys: meta_description, focus_keyword
     */
    public static function apply_seo_meta( $post_id, $generated = array() ) {
        if ( empty( $post_id ) || empty( $generated ) ) {
            return;
        }

        // Yoast example
        if ( function_exists( 'update_post_meta' ) ) {
            if ( ! empty( $generated['meta_description'] ) ) {
                update_post_meta( $post_id, '_yoast_wpseo_metadesc', sanitize_text_field( $generated['meta_description'] ) );
            }
            if ( ! empty( $generated['focus_keyword'] ) ) {
                update_post_meta( $post_id, '_yoast_wpseo_focuskw', sanitize_text_field( $generated['focus_keyword'] ) );
            }
        }

        // Best-effort: support other SEO plugins by writing common meta keys.
        // Rank Math (best-effort keys)
        if ( defined( 'RANK_MATH_VERSION' ) || class_exists( 'RankMath\Product' ) || class_exists( 'RankMath' ) ) {
            if ( ! empty( $generated['meta_description'] ) ) {
                update_post_meta( $post_id, 'rank_math_description', sanitize_text_field( $generated['meta_description'] ) );
            }
            if ( ! empty( $generated['focus_keyword'] ) ) {
                update_post_meta( $post_id, 'rank_math_focus_keyword', sanitize_text_field( $generated['focus_keyword'] ) );
            }
        }

        // SEOPress (best-effort keys)
        if ( defined( 'SEOPRESS_VERSION' ) || class_exists( 'SEOPress' ) ) {
            if ( ! empty( $generated['meta_description'] ) ) {
                update_post_meta( $post_id, 'seopress_titles_metadesc', sanitize_text_field( $generated['meta_description'] ) );
            }
            if ( ! empty( $generated['focus_keyword'] ) ) {
                update_post_meta( $post_id, 'seopress_titles_title', sanitize_text_field( $generated['focus_keyword'] ) );
            }
        }
    }
}
endif;
