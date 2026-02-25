<?php
/**
 * Main plugin class.
 *
 * @package MU_SEO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main MU_SEO class.
 */
class MU_SEO {

	/**
	 * Single instance of the class.
	 *
	 * @var MU_SEO
	 */
	private static $instance = null;

	/**
	 * Returns the single instance of MU_SEO.
	 *
	 * @return MU_SEO
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->includes();
		$this->init_hooks();
	}

	/**
	 * Load required files.
	 */
	private function includes() {
		require_once plugin_dir_path( __FILE__ ) . 'class-mu-seo-fields.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-mu-seo-head.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-mu-seo-options.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-mu-seo-social.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-mu-seo-schema.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-mu-seo-migrate.php';
	}

	/**
	 * Register hooks.
	 */
	private function init_hooks() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	/**
	 * Initialize plugin components.
	 */
	public function init() {
		new MU_SEO_Fields();
		new MU_SEO_Head();
		new MU_SEO_Options();
		new MU_SEO_Social();
		new MU_SEO_Schema();
		$migrate = new MU_SEO_Migrate();
		$migrate->register_cli();
		$migrate->register_admin_hooks();
	}
}
