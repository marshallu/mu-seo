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
				'key'      => 'group_mu_seo',
				'title'    => 'SEO',
				'fields'   => array(
					array(
						'key'               => 'field_mu_seo_title',
						'label'             => 'SEO Title',
						'name'              => 'mu_seo_title',
						'type'              => 'text',
						'instructions'      => 'Recommended length: 50–60 characters. Leave blank to use the default post title.',
						'required'          => 0,
						'maxlength'         => 100,
						'placeholder'       => '',
					),
					array(
						'key'               => 'field_mu_seo_description',
						'label'             => 'Meta Description',
						'name'              => 'mu_seo_description',
						'type'              => 'textarea',
						'instructions'      => 'Recommended length: 120–160 characters.',
						'required'          => 0,
						'maxlength'         => 320,
						'rows'              => 3,
						'new_lines'         => '',
						'placeholder'       => '',
					),
					array(
						'key'               => 'field_mu_seo_canonical',
						'label'             => 'Canonical URL',
						'name'              => 'mu_seo_canonical',
						'type'              => 'url',
						'instructions'      => 'Leave blank to use the default URL for this post.',
						'required'          => 0,
						'placeholder'       => '',
					),
				),
				'location' => $this->get_location_rules(),
				'position' => 'normal',
				'style'    => 'default',
				'label_placement'   => 'top',
				'instruction_placement' => 'label',
			)
		);
	}

	/**
	 * Build ACF location rules for all public post types.
	 *
	 * Each post type becomes its own OR group so the field group
	 * appears on every post type edit screen.
	 *
	 * @return array
	 */
	private function get_location_rules() {
		$post_types = get_post_types( array( 'public' => true ), 'names' );
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
