<?php
/**
 * Registers the ACF options page and site-wide SEO settings fields.
 *
 * @package MU_SEO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * MU_SEO_Options class.
 */
class MU_SEO_Options {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'acf/init', array( $this, 'register_options_page' ) );
		add_action( 'acf/init', array( $this, 'register_field_group' ) );
	}

	/**
	 * Register the SEO Settings options sub-page under Settings.
	 */
	public function register_options_page() {
		if ( ! function_exists( 'acf_add_options_sub_page' ) ) {
			return;
		}

		acf_add_options_sub_page(
			array(
				'page_title'  => 'SEO Settings',
				'menu_title'  => 'SEO Settings',
				'menu_slug'   => 'mu-seo-settings',
				'parent_slug' => 'options-general.php',
				'capability'  => 'manage_options',
			)
		);
	}

	/**
	 * Register the ACF field group for the options page.
	 */
	public function register_field_group() {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		acf_add_local_field_group(
			array(
				'key'                   => 'group_mu_seo_options',
				'title'                 => 'SEO Settings',
				'fields'                => array(
					array(
						'key'          => 'field_mu_seo_twitter_handle',
						'label'        => 'Twitter / X Handle',
						'name'         => 'mu_seo_twitter_handle',
						'type'         => 'text',
						'instructions' => 'Include the @ symbol, e.g. @MarshallU',
						'required'     => 0,
						'placeholder'  => '@MarshallU',
					),
					array(
						'key'           => 'field_mu_seo_default_og_image',
						'label'         => 'Default Social Image',
						'name'          => 'mu_seo_default_og_image',
						'type'          => 'image',
						'instructions'  => 'Fallback image used when a post has no featured image or hero image.',
						'required'      => 0,
						'return_format' => 'id',
						'preview_size'  => 'medium',
						'library'       => 'all',
					),
				),
				'location'              => array(
					array(
						array(
							'param'    => 'options_page',
							'operator' => '==',
							'value'    => 'mu-seo-settings',
						),
					),
				),
				'position'              => 'normal',
				'style'                 => 'default',
				'label_placement'       => 'top',
				'instruction_placement' => 'label',
			)
		);
	}
}
