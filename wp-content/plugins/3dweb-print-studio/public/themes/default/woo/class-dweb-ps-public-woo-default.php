<?php
/**
 * Default Woo theme integration.
 *
 * @package DWeb_PS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default WooCommerce theme integration.
 */
class DWeb_PS_Public_Woo_Default extends DWeb_PS_Public_Woo_Base {

	const THEME_NAME = 'default';

	/**
	 * Enqueues theme-specific styles when present.
	 *
	 * @return void
	 */
	protected function dweb_ps_load_extra_styles() {
		$style_path = plugin_dir_path( __FILE__ ) . 'woo.css';
		if ( ! file_exists( $style_path ) ) {
			return;
		}

		wp_enqueue_style(
			$this->plugin_name . '_woo_css',
			plugin_dir_url( __FILE__ ) . 'woo.css',
			array(),
			$this->version,
			'all'
		);
	}

	/**
	 * Enqueues theme-specific scripts when present.
	 *
	 * @return void
	 */
	protected function dweb_ps_load_extra_scripts() {
		$script_path = plugin_dir_path( __FILE__ ) . 'woo.js';
		if ( ! file_exists( $script_path ) ) {
			return;
		}

		wp_enqueue_script(
			$this->plugin_name . '_woo_js',
			plugin_dir_url( __FILE__ ) . 'woo.js',
			array(
				$this->plugin_name . '_public_core',
				$this->plugin_name . '_public_flexslider',
			),
			$this->version,
			true
		);
	}
}
