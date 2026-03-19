<?php
/**
 * Public plugin bootstrap.
 *
 * @package DWeb_PS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Public-facing plugin integration.
 */
class DWeb_PS_Public {
	/**
	 * Plugin handle.
	 *
	 * @var string
	 */
	private string $plugin_name;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	private string $version;

	/**
	 * Sets up the public plugin integration.
	 *
	 * @param string $plugin_name Plugin handle.
	 * @param string $version     Plugin version.
	 */
	public function __construct( string $plugin_name, string $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		$this->load_theme_factory();
	}

	/**
	 * Loads the theme factory dependency.
	 */
	private function load_theme_factory() {
		require_once plugin_dir_path( __DIR__ ) . '/public/themes/class-dweb-ps-public-theme-factory.php';
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function dweb_ps_enqueue_styles() {
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function dweb_ps_enqueue_scripts() {
		// Intentionally left empty: public scripts are enqueued by the active Woo theme integration.
	}
}
