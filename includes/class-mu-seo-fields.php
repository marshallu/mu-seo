<?php
/**
 * Registers ACF field groups for SEO fields.
 *
 * @package MU_SEO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * MU_SEO_Fields class.
 */
class MU_SEO_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'acf/init', array( $this, 'register_field_group' ) );
	}

	/**
	 * Register the SEO field group for all public post types.
	 */
	public function register_field_group() {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		acf_add_local_field_group(
			array(
				'key'                   => 'group_mu_seo',
				'title'                 => 'SEO',
				'fields'                => array(
					array(
						'key'   => 'field_mu_seo_tab_seo',
						'label' => 'SEO',
						'name'  => '',
						'type'  => 'tab',
					),
					array(
						'key'          => 'field_mu_seo_title',
						'label'        => 'SEO Title',
						'name'         => 'mu_seo_title',
						'type'         => 'text',
						'instructions' => 'Recommended length: 50–60 characters. Leave blank to use the default post title.',
						'required'     => 0,
						'maxlength'    => 100,
						'placeholder'  => '',
					),
					array(
						'key'          => 'field_mu_seo_description',
						'label'        => 'Meta Description',
						'name'         => 'mu_seo_description',
						'type'         => 'textarea',
						'instructions' => 'Recommended length: 120–160 characters.',
						'required'     => 0,
						'maxlength'    => 320,
						'rows'         => 3,
						'new_lines'    => '',
						'placeholder'  => '',
					),
					array(
						'key'          => 'field_mu_seo_canonical',
						'label'        => 'Canonical URL',
						'name'         => 'mu_seo_canonical',
						'type'         => 'url',
						'instructions' => 'The canonical URL tells search engines which version of this page is the "official" one. Use this field if the same content appears at multiple URLs (e.g. paginated pages, print views, or syndicated content) to avoid duplicate content penalties. Leave blank to use the default URL for this post.',
						'required'     => 0,
						'placeholder'  => '',
					),
					array(
						'key'           => 'field_mu_seo_robots',
						'label'         => 'Robots',
						'name'          => 'mu_seo_robots',
						'type'          => 'checkbox',
						'instructions'  => 'Override the default crawler directives for this page. Leave unchecked to allow normal indexing and link-following.',
						'required'      => 0,
						'choices'       => array(
							'noindex'  => 'noindex — prevent search engines from indexing this page',
							'nofollow' => 'nofollow — prevent search engines from following links on this page',
						),
						'layout'        => 'vertical',
						'toggle'        => 0,
						'return_format' => 'value',
					),
					array(
						'key'   => 'field_mu_seo_tab_social',
						'label' => 'Social / Open Graph',
						'name'  => '',
						'type'  => 'tab',
					),
					array(
						'key'           => 'field_mu_seo_og_image',
						'label'         => 'Social Image',
						'name'          => 'mu_seo_og_image',
						'type'          => 'image',
						'instructions'  => 'Used for og:image and Twitter card. Falls back to featured image, then the page hero image, then the site default.',
						'required'      => 0,
						'return_format' => 'id',
						'preview_size'  => 'medium',
						'library'       => 'all',
					),
					array(
						'key'           => 'field_mu_seo_og_type',
						'label'         => 'OG Type',
						'name'          => 'mu_seo_og_type',
						'type'          => 'select',
						'instructions'  => 'Use "article" for posts, news, and content pages. Use "website" for the homepage or section landing pages.',
						'required'      => 0,
						'choices'       => array(
							'article' => 'article',
							'website' => 'website',
						),
						'default_value' => 'article',
						'allow_null'    => 0,
						'multiple'      => 0,
						'ui'            => 0,
						'return_format' => 'value',
					),
					array(
						'key'           => 'field_mu_seo_twitter_card',
						'label'         => 'Twitter Card Style',
						'name'          => 'mu_seo_twitter_card',
						'type'          => 'select',
						'instructions'  => '"summary_large_image" displays a full-width image above the title — best for most content. "summary" shows a small thumbnail beside the text.',
						'required'      => 0,
						'choices'       => array(
							'summary_large_image' => 'summary_large_image',
							'summary'             => 'summary',
						),
						'default_value' => 'summary_large_image',
						'allow_null'    => 0,
						'multiple'      => 0,
						'ui'            => 0,
						'return_format' => 'value',
					),
				),
				'location'              => $this->get_location_rules(),
				'position'              => 'normal',
				'style'                 => 'default',
				'label_placement'       => 'top',
				'instruction_placement' => 'label',
			)
		);
	}

	/**
	 * Build ACF location rules for the enabled post types.
	 *
	 * Each post type becomes its own OR group so the field group
	 * appears on every post type edit screen.
	 *
	 * @return array
	 */
	private function get_location_rules() {
		$post_types = get_post_types( array( 'public' => true ), 'names' );

		/**
		 * Filter the post types that receive the SEO and Social field group.
		 *
		 * By default all public post types are included. Use this filter to
		 * add post types that have show_ui => true but public => false, or to
		 * remove post types that should not have SEO fields.
		 *
		 * @param string[] $post_types Array of post type slugs.
		 */
		$post_types = apply_filters( 'mu_seo_post_types', $post_types );
		$location   = array();

		foreach ( $post_types as $post_type ) {
			$location[] = array(
				array(
					'param'    => 'post_type',
					'operator' => '==',
					'value'    => $post_type,
				),
			);
		}

		return $location;
	}
}
