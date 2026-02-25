<?php
/**
 * Outputs Open Graph and Twitter Card meta tags in the document head.
 *
 * @package MU_SEO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * MU_SEO_Social class.
 */
class MU_SEO_Social {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_head', array( $this, 'output_social_tags' ), 1 );
	}

	/**
	 * Output OG and Twitter Card tags for singular pages.
	 */
	public function output_social_tags() {
		if ( ! is_singular() ) {
			return;
		}

		if ( ! function_exists( 'get_field' ) ) {
			return;
		}

		$post_id = get_the_ID();

		if ( ! $post_id ) {
			return;
		}

		$title       = $this->get_title( $post_id );
		$description = $this->get_description( $post_id );
		$url         = $this->get_url( $post_id );
		$type        = $this->get_og_type();
		$site_name   = get_bloginfo( 'name' );
		$image_data  = $this->get_image_data( $post_id );
		$card_type   = $this->get_twitter_card_type();
		$tw_handle   = $this->get_twitter_handle();

		// OG tags.
		printf( '<meta property="og:type" content="%s">' . "\n", esc_attr( $type ) );
		printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( $title ) );
		printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( $description ) );
		printf( '<meta property="og:url" content="%s">' . "\n", esc_url( $url ) );
		printf( '<meta property="og:site_name" content="%s">' . "\n", esc_attr( $site_name ) );

		if ( ! empty( $image_data['url'] ) ) {
			printf( '<meta property="og:image" content="%s">' . "\n", esc_url( $image_data['url'] ) );
			printf( '<meta property="og:image:width" content="%s">' . "\n", esc_attr( (string) $image_data['width'] ) );
			printf( '<meta property="og:image:height" content="%s">' . "\n", esc_attr( (string) $image_data['height'] ) );
			printf( '<meta property="og:image:alt" content="%s">' . "\n", esc_attr( $image_data['alt'] ) );
		}

		// Twitter Card tags.
		printf( '<meta name="twitter:card" content="%s">' . "\n", esc_attr( $card_type ) );

		if ( $tw_handle ) {
			printf( '<meta name="twitter:site" content="%s">' . "\n", esc_attr( $tw_handle ) );
		}

		printf( '<meta name="twitter:title" content="%s">' . "\n", esc_attr( $title ) );
		printf( '<meta name="twitter:description" content="%s">' . "\n", esc_attr( $description ) );

		if ( ! empty( $image_data['url'] ) ) {
			printf( '<meta name="twitter:image" content="%s">' . "\n", esc_url( $image_data['url'] ) );
		}
	}

	/**
	 * Get the OG title for the current post.
	 *
	 * Falls back to the post title.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	private function get_title( $post_id ) {
		$seo_title = sanitize_text_field( (string) get_field( 'mu_seo_title', $post_id ) );

		if ( $seo_title ) {
			return $seo_title;
		}

		return get_the_title( $post_id );
	}

	/**
	 * Get the OG description for the current post.
	 *
	 * Falls back to the post excerpt.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	private function get_description( $post_id ) {
		$seo_desc = sanitize_text_field( (string) get_field( 'mu_seo_description', $post_id ) );

		if ( $seo_desc ) {
			return $seo_desc;
		}

		return wp_strip_all_tags( get_the_excerpt( $post_id ) );
	}

	/**
	 * Get the canonical URL for the current post.
	 *
	 * Falls back to the permalink.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	private function get_url( $post_id ) {
		$canonical = esc_url_raw( (string) get_field( 'mu_seo_canonical', $post_id ) );

		if ( $canonical ) {
			return $canonical;
		}

		return (string) get_permalink( $post_id );
	}

	/**
	 * Get the OG type for the current post.
	 *
	 * @return string
	 */
	private function get_og_type() {
		$type = sanitize_key( (string) get_field( 'mu_seo_og_type' ) );

		if ( in_array( $type, array( 'article', 'website' ), true ) ) {
			return $type;
		}

		$default = is_singular( 'post' ) ? 'article' : 'website';

		/**
		 * Filters the default og:type for the current post.
		 *
		 * @param string $default  The default type ('article' or 'website').
		 * @param int    $post_id  The current post ID.
		 */
		return sanitize_key(
			(string) apply_filters( 'mu_seo_og_type', $default, get_the_ID() )
		);
	}

	/**
	 * Get the Twitter Card type for the current post.
	 *
	 * @return string
	 */
	private function get_twitter_card_type() {
		$card = sanitize_key( (string) get_field( 'mu_seo_twitter_card' ) );

		if ( in_array( $card, array( 'summary_large_image', 'summary' ), true ) ) {
			return $card;
		}

		return 'summary_large_image';
	}

	/**
	 * Get the site Twitter handle from the options page.
	 *
	 * @return string
	 */
	private function get_twitter_handle() {
		return sanitize_text_field( (string) get_field( 'mu_seo_twitter_handle', 'option' ) );
	}

	/**
	 * Resolve the social image via the fallback chain and return its data.
	 *
	 * Fallback order:
	 *   1. Post-level ACF og image override
	 *   2. Featured image
	 *   3. Hero block image (acf/hero)
	 *   4. Site default from options page
	 *
	 * @param int $post_id Post ID.
	 * @return array { url: string, width: int, height: int, alt: string }
	 */
	private function get_image_data( $post_id ) {
		$image_id = 0;

		// 1. Post-level ACF override.
		$acf_image = get_field( 'mu_seo_og_image', $post_id );
		if ( $acf_image ) {
			$image_id = absint( $acf_image );
		}

		// 2. Featured image.
		if ( ! $image_id ) {
			$thumbnail_id = get_post_thumbnail_id( $post_id );
			if ( $thumbnail_id ) {
				$image_id = absint( $thumbnail_id );
			}
		}

		// 3. Hero block image.
		if ( ! $image_id ) {
			$image_id = $this->get_hero_block_image( $post_id );
		}

		// 4. Site default.
		if ( ! $image_id ) {
			$default = get_field( 'mu_seo_default_og_image', 'option' );
			if ( $default ) {
				$image_id = absint( $default );
			}
		}

		if ( ! $image_id ) {
			return array(
				'url'    => '',
				'width'  => 0,
				'height' => 0,
				'alt'    => '',
			);
		}

		$src = wp_get_attachment_image_src( $image_id, 'large' );

		if ( ! $src ) {
			return array(
				'url'    => '',
				'width'  => 0,
				'height' => 0,
				'alt'    => '',
			);
		}

		$alt = sanitize_text_field(
			(string) get_post_meta( $image_id, '_wp_attachment_image_alt', true )
		);

		return array(
			'url'    => $src[0],
			'width'  => absint( $src[1] ),
			'height' => absint( $src[2] ),
			'alt'    => $alt,
		);
	}

	/**
	 * Parse the first acf/hero block in the post content and return its image ID.
	 *
	 * Supports hero_type values: static, random, video, videourl, none, color.
	 *
	 * @param int $post_id Post ID.
	 * @return int Image attachment ID, or 0 if not found.
	 */
	private function get_hero_block_image( $post_id ) {
		$post_content = get_post_field( 'post_content', $post_id );

		if ( ! $post_content || ! has_blocks( $post_content ) ) {
			return 0;
		}

		$blocks = parse_blocks( $post_content );

		foreach ( $blocks as $block ) {
			if ( 'acf/hero' !== $block['blockName'] ) {
				continue;
			}

			$data      = isset( $block['attrs']['data'] ) ? $block['attrs']['data'] : array();
			$hero_type = isset( $data['hero_type'] ) ? (string) $data['hero_type'] : '';

			switch ( $hero_type ) {
				case 'static':
					$image_id = isset( $data['hero_image_image'] ) ? absint( $data['hero_image_image'] ) : 0;
					break;

				case 'random':
					$image_id = isset( $data['hero_images_0_image'] ) ? absint( $data['hero_images_0_image'] ) : 0;
					break;

				case 'video':
				case 'videourl':
					$image_id = isset( $data['video_video_thumbnail'] ) ? absint( $data['video_video_thumbnail'] ) : 0;
					break;

				default:
					// 'none', 'color', or unknown — no image.
					$image_id = 0;
					break;
			}

			return $image_id;
		}

		return 0;
	}
}
