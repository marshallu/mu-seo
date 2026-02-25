<?php
/**
 * Outputs JSON-LD schema markup in the document head.
 *
 * @package MU_SEO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * MU_SEO_Schema class.
 */
class MU_SEO_Schema {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_head', array( $this, 'output_schema' ), 2 );
	}

	/**
	 * Output JSON-LD schema for singular posts and pages.
	 */
	public function output_schema() {
		if ( ! is_singular() ) {
			return;
		}

		$post_id = get_the_ID();

		if ( ! $post_id ) {
			return;
		}

		if ( is_singular( 'post' ) ) {
			$schema = $this->build_article_schema( $post_id );
		} elseif ( is_page() ) {
			$schema = $this->build_webpage_schema( $post_id );
		} else {
			$schema = array();
		}

		/**
		 * Filter the JSON-LD schema object before output.
		 *
		 * Use this hook to add schema for custom post types, modify existing
		 * schema properties, or suppress output by returning an empty array.
		 *
		 * @param array  $schema    The schema array. Empty for unhandled post types.
		 * @param int    $post_id   The current post ID.
		 * @param string $post_type The current post type slug.
		 */
		$schema = apply_filters( 'mu_seo_schema', $schema, $post_id, get_post_type( $post_id ) );

		if ( empty( $schema ) ) {
			return;
		}

		$json = wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );

		if ( ! $json ) {
			return;
		}

		echo '<script type="application/ld+json">' . "\n" . $json . "\n" . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Build an Article schema object for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	private function build_article_schema( $post_id ) {
		$post = get_post( $post_id );

		if ( ! $post ) {
			return array();
		}

		$schema = array(
			'@context'      => 'https://schema.org',
			'@type'         => 'Article',
			'headline'      => $this->get_title( $post_id ),
			'description'   => $this->get_description( $post_id ),
			'url'           => $this->get_url( $post_id ),
			'datePublished' => get_the_date( 'c', $post ),
			'dateModified'  => get_the_modified_date( 'c', $post ),
			'author'        => array(
				'@type' => 'Person',
				'name'  => get_the_author_meta( 'display_name', (int) $post->post_author ),
			),
			'publisher'     => array(
				'@type' => 'Organization',
				'name'  => get_bloginfo( 'name' ),
			),
		);

		$image = $this->get_image_data( $post_id );

		if ( ! empty( $image['url'] ) ) {
			$schema['image'] = array(
				'@type'  => 'ImageObject',
				'url'    => $image['url'],
				'width'  => $image['width'],
				'height' => $image['height'],
			);
		}

		return $schema;
	}

	/**
	 * Build a WebPage schema object for a page.
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	private function build_webpage_schema( $post_id ) {
		$post = get_post( $post_id );

		if ( ! $post ) {
			return array();
		}

		$schema = array(
			'@context'      => 'https://schema.org',
			'@type'         => 'WebPage',
			'name'          => $this->get_title( $post_id ),
			'description'   => $this->get_description( $post_id ),
			'url'           => $this->get_url( $post_id ),
			'datePublished' => get_the_date( 'c', $post ),
			'dateModified'  => get_the_modified_date( 'c', $post ),
			'publisher'     => array(
				'@type' => 'Organization',
				'name'  => get_bloginfo( 'name' ),
			),
		);

		$image = $this->get_image_data( $post_id );

		if ( ! empty( $image['url'] ) ) {
			$schema['primaryImageOfPage'] = array(
				'@type'  => 'ImageObject',
				'url'    => $image['url'],
				'width'  => $image['width'],
				'height' => $image['height'],
			);
		}

		return $schema;
	}

	/**
	 * Get the title for the current post, falling back to the post title.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	private function get_title( $post_id ) {
		if ( function_exists( 'get_field' ) ) {
			$seo_title = sanitize_text_field( (string) get_field( 'mu_seo_title', $post_id ) );

			if ( $seo_title ) {
				return $seo_title;
			}
		}

		return get_the_title( $post_id );
	}

	/**
	 * Get the description for the current post, falling back to the excerpt.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	private function get_description( $post_id ) {
		if ( function_exists( 'get_field' ) ) {
			$seo_desc = sanitize_text_field( (string) get_field( 'mu_seo_description', $post_id ) );

			if ( $seo_desc ) {
				return $seo_desc;
			}
		}

		return wp_strip_all_tags( get_the_excerpt( $post_id ) );
	}

	/**
	 * Get the canonical URL for the current post, falling back to the permalink.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	private function get_url( $post_id ) {
		if ( function_exists( 'get_field' ) ) {
			$canonical = esc_url_raw( (string) get_field( 'mu_seo_canonical', $post_id ) );

			if ( $canonical ) {
				return $canonical;
			}
		}

		return (string) get_permalink( $post_id );
	}

	/**
	 * Resolve the image for the post via the standard fallback chain.
	 *
	 * Fallback order:
	 *   1. Post-level ACF og image override
	 *   2. Featured image
	 *   3. Hero block image (acf/hero)
	 *   4. Site default from options page
	 *
	 * @param int $post_id Post ID.
	 * @return array { url: string, width: int, height: int }
	 */
	private function get_image_data( $post_id ) {
		$empty = array(
			'url'    => '',
			'width'  => 0,
			'height' => 0,
		);

		if ( ! function_exists( 'get_field' ) ) {
			return $empty;
		}

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
			return $empty;
		}

		$src = wp_get_attachment_image_src( $image_id, 'large' );

		if ( ! $src ) {
			return $empty;
		}

		return array(
			'url'    => $src[0],
			'width'  => absint( $src[1] ),
			'height' => absint( $src[2] ),
		);
	}

	/**
	 * Parse the first acf/hero block in the post content and return its image ID.
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
					$image_id = 0;
					break;
			}

			return $image_id;
		}

		return 0;
	}
}
