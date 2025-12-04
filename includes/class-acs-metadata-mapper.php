<?php
/**
 * Metadata Mapper for AI Content Studio
 *
 * Handles mapping of generated content to SEO plugin metadata fields.
 *
 * @package ACS
 */

if ( ! class_exists( 'ACS_Metadata_Mapper' ) ) :

class ACS_Metadata_Mapper {

	/**
	 * Map generated content to SEO plugin meta fields.
	 *
	 * Detects the active SEO plugin and maps content fields to the appropriate
	 * post meta keys for that plugin.
	 *
	 * @param int   $post_id WordPress post ID
	 * @param array $content Generated content array
	 * @return bool Success status
	 */
	public function map_to_post_meta( $post_id, $content ) {
		if ( ! is_numeric( $post_id ) || $post_id <= 0 ) {
			error_log( '[ACS][META_MAPPER] Invalid post ID: ' . $post_id );
			return false;
		}

		if ( ! is_array( $content ) ) {
			error_log( '[ACS][META_MAPPER] Content must be an array' );
			return false;
		}

		// Detect active SEO plugin
		$seo_plugin = $this->detect_seo_plugin();

		// Get meta keys for the detected plugin
		$meta_keys = $this->get_meta_keys( $seo_plugin );

		$success = true;

		// Map meta_description
		if ( isset( $content['meta_description'] ) && ! empty( $content['meta_description'] ) ) {
			$meta_desc = $this->truncate_meta_description( $content['meta_description'] );
			$meta_desc = $this->sanitize_meta_value( 'meta_description', $meta_desc );
			
			if ( isset( $meta_keys['meta_description'] ) ) {
				$result = update_post_meta( $post_id, $meta_keys['meta_description'], $meta_desc );
				if ( ! $result ) {
					error_log( '[ACS][META_MAPPER] Failed to save meta_description for post ' . $post_id );
					$success = false;
				}
			}
		}

		// Map focus_keyword
		if ( isset( $content['focus_keyword'] ) && ! empty( $content['focus_keyword'] ) ) {
			$focus_kw = $this->sanitize_meta_value( 'focus_keyword', $content['focus_keyword'] );
			
			if ( isset( $meta_keys['focus_keyword'] ) ) {
				$result = update_post_meta( $post_id, $meta_keys['focus_keyword'], $focus_kw );
				if ( ! $result ) {
					error_log( '[ACS][META_MAPPER] Failed to save focus_keyword for post ' . $post_id );
					$success = false;
				}
			}
		}

		// Map SEO title
		if ( isset( $content['title'] ) && ! empty( $content['title'] ) ) {
			$seo_title = $this->sanitize_meta_value( 'title', $content['title'] );
			
			if ( isset( $meta_keys['title'] ) ) {
				$result = update_post_meta( $post_id, $meta_keys['title'], $seo_title );
				if ( ! $result ) {
					error_log( '[ACS][META_MAPPER] Failed to save SEO title for post ' . $post_id );
					$success = false;
				}
			}
		}

		return $success;
	}

	/**
	 * Detect which SEO plugin is active.
	 *
	 * Checks for common SEO plugins in order of popularity.
	 *
	 * @return string Plugin identifier ('yoast', 'rankmath', 'seopress', or 'none')
	 */
	public function detect_seo_plugin() {
		// Check for Yoast SEO
		if ( defined( 'WPSEO_VERSION' ) || class_exists( 'WPSEO_Options' ) ) {
			return 'yoast';
		}

		// Check for Rank Math
		if ( defined( 'RANK_MATH_VERSION' ) || class_exists( 'RankMath' ) ) {
			return 'rankmath';
		}

		// Check for SEOPress
		if ( defined( 'SEOPRESS_VERSION' ) || class_exists( 'SEOPress' ) ) {
			return 'seopress';
		}

		// Default to Yoast format if no plugin detected
		return 'yoast';
	}

	/**
	 * Get meta keys for a specific SEO plugin.
	 *
	 * Returns an array mapping field names to post meta keys.
	 *
	 * @param string $plugin Plugin identifier
	 * @return array Meta key mappings
	 */
	public function get_meta_keys( $plugin ) {
		$mappings = array(
			'yoast' => array(
				'title'            => '_yoast_wpseo_title',
				'meta_description' => '_yoast_wpseo_metadesc',
				'focus_keyword'    => '_yoast_wpseo_focuskw',
				'canonical'        => '_yoast_wpseo_canonical',
			),
			'rankmath' => array(
				'title'            => 'rank_math_title',
				'meta_description' => 'rank_math_description',
				'focus_keyword'    => 'rank_math_focus_keyword',
			),
			'seopress' => array(
				'title'            => '_seopress_titles_title',
				'meta_description' => '_seopress_titles_desc',
				'focus_keyword'    => '_seopress_analysis_target_kw',
			),
		);

		return isset( $mappings[ $plugin ] ) ? $mappings[ $plugin ] : $mappings['yoast'];
	}

	/**
	 * Sanitize a meta value based on its type.
	 *
	 * @param string $key   Meta key identifier
	 * @param mixed  $value Value to sanitize
	 * @return mixed Sanitized value
	 */
	public function sanitize_meta_value( $key, $value ) {
		if ( ! is_string( $value ) ) {
			return '';
		}

		switch ( $key ) {
			case 'title':
			case 'focus_keyword':
				return sanitize_text_field( $value );

			case 'meta_description':
				return sanitize_textarea_field( $value );

			default:
				return sanitize_text_field( $value );
		}
	}

	/**
	 * Truncate meta description to 155 characters.
	 *
	 * Ensures meta descriptions don't exceed the recommended length for search engines.
	 *
	 * @param string $description Meta description text
	 * @return string Truncated description
	 */
	public function truncate_meta_description( $description ) {
		if ( ! is_string( $description ) ) {
			return '';
		}

		$max_length = 155;

		if ( mb_strlen( $description ) <= $max_length ) {
			return $description;
		}

		// Truncate and add ellipsis
		$truncated = mb_substr( $description, 0, $max_length - 3 );
		
		// Try to break at last word boundary
		$last_space = mb_strrpos( $truncated, ' ' );
		if ( $last_space !== false && $last_space > $max_length * 0.8 ) {
			$truncated = mb_substr( $truncated, 0, $last_space );
		}

		return $truncated . '...';
	}
}

endif;
