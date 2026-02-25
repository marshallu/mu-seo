<?php
/**
 * MU SEO
 *
 * This is a SEO for Marshall University's WordPress network.
 *
 * @package MU_SEO
 *
 * Plugin Name:  MU SEO
 * Plugin URI: https://www.marshall.edu
 * Description: This is a lean SEO plugin for Marshall University.
 * Version: 1.0
 * Author: Christopher McComas
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once plugin_dir_path( __FILE__ ) . 'includes/class-mu-seo.php';

/**
 * Returns the main instance of MU_SEO.
 *
 * @return MU_SEO
 */
function mu_seo() {
	return MU_SEO::instance();
}

mu_seo();
