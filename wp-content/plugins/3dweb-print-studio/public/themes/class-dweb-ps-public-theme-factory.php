<?php
/**
 * Public theme factory.
 *
 * @package DWeb_PS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolves the active WooCommerce theme integration.
 */
class DWeb_PS_Public_Theme_Factory {
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
	 * Sets up the theme factory.
	 *
	 * @param string $plugin_name Plugin handle.
	 * @param string $version     Plugin version.
	 */
	public function __construct( string $plugin_name, string $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		$this->dweb_ps_load_base_woo_file();
	}

	/**
	 * Loads a theme-specific Woo integration class file.
	 *
	 * @param string $theme Theme name.
	 * @return bool
	 */
	private function load_woo_theme( $theme ) {
		$theme            = strtolower( $theme );
		$theme_class_file = __DIR__ . '/' . $theme . '/woo/class-dweb-ps-public-woo-' . $theme . '.php';

		if ( file_exists( $theme_class_file ) ) {
			require_once $theme_class_file;
			return true;
		}
		return false;
	}

	/**
	 * Creates the active theme Woo integration instance.
	 *
	 * @param string $theme Theme name.
	 * @return object
	 */
	public function dweb_ps_create_woo_theme_class( $theme ) {

		if ( $this->load_woo_theme( $theme ) ) {
			$class_name = 'DWeb_PS_Public_Woo_' . ucfirst( strtolower( $theme ) );
			if ( class_exists( $class_name ) ) {
				return new $class_name( $this->plugin_name, $this->version );
			}
		}

		$this->load_woo_theme( 'default' );
		return new DWeb_PS_Public_Woo_Default( $this->plugin_name, $this->version );
	}

	/**
	 * Loads the shared Woo base class.
	 *
	 * @return void
	 */
	private function dweb_ps_load_base_woo_file() {
		require_once __DIR__ . '/class-dweb-ps-public-woo-base.php';
	}
}
