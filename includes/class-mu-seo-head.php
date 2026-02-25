<?php
/**
 * Outputs SEO tags in the document head.
 *
 * @package MU_SEO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * MU_SEO_Head class.
 */
class MU_SEO_Head {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'pre_get_document_title', array( $this, 'filter_document_title' ) );
		add_action( 'wp_head', array( $this, 'output_meta_tags' ) );
	}

	/**
	 * Override the document title when an SEO title is set.
	 *
	 * @param string $title The current document title.
	 * @return string
	 */
	public function filter_document_title( $title ) {
		if ( ! is_singular() ) {
			return $title;
		}

		$seo_title = $this->get_seo_title();

		if ( $seo_title ) {
			return $seo_title;
		}

		return $title;
	}

	/**
	 * Output meta description, robots, and canonical link tags.
	 */
	public function output_meta_tags() {
		if ( ! is_singular() ) {
			return;
		}

		$description = $this->get_seo_description();
		$robots      = $this->get_robots_directives();
		$canonical   = $this->get_canonical_url();

		if ( $description ) {
			printf(
				'<meta name="description" content="%s">' . "\n",
				esc_attr( $description )
			);
		}

		if ( $robots ) {
			printf(
				'<meta name="robots" content="%s">' . "\n",
				esc_attr( $robots )
			);
		}

		if ( $canonical ) {
			printf(
				'<link rel="canonical" href="%s">' . "\n",
				esc_url( $canonical )
			);
		}
	}

	/**
	 * Get the SEO title for the current post.
	 *
	 * @return string
	 */
	private function get_seo_title() {
		if ( ! function_exists( 'get_field' ) ) {
			return '';
		}

		return sanitize_text_field( (string) get_field( 'mu_seo_title' ) );
	}

	/**
	 * Get the meta description for the current post.
	 *
	 * @return string
	 */
	private function get_seo_description() {
		if ( ! function_exists( 'get_field' ) ) {
			return '';
		}

		return sanitize_text_field( (string) get_field( 'mu_seo_description' ) );
	}

	/**
	 * Get the robots meta content string for the current post.
	 *
	 * Returns a comma-separated list of checked directives (e.g. "noindex,nofollow"),
	 * or an empty string when no directives are set.
	 *
	 * @return string
	 */
	private function get_robots_directives() {
		if ( ! function_exists( 'get_field' ) ) {
			return '';
		}

		$directives = get_field( 'mu_seo_robots' );

		if ( empty( $directives ) || ! is_array( $directives ) ) {
			return '';
		}

		$allowed    = array( 'noindex', 'nofollow' );
		$directives = array_intersect( $directives, $allowed );

		return implode( ',', $directives );
	}

	/**
	 * Get the canonical URL for the current post.
	 *
	 * Falls back to the post's permalink when no override is set.
	 *
	 * @return string
	 */
	private function get_canonical_url() {
		if ( ! function_exists( 'get_field' ) ) {
			return esc_url( get_permalink() );
		}

		$override = esc_url_raw( (string) get_field( 'mu_seo_canonical' ) );

		return $override ? $override : esc_url( get_permalink() );
	}
}
