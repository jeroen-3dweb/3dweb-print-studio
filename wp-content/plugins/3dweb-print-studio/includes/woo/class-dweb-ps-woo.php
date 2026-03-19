<?php
/**
 * WooCommerce integration bootstrap.
 *
 * @package DWeb_PS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce integration.
 */
class DWeb_PS_WOO {
	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	private string $version;

	/**
	 * Plugin handle.
	 *
	 * @var string
	 */
	private string $plugin_name;

	/**
	 * Hook loader instance.
	 *
	 * @var DWeb_PS_Loader
	 */
	private DWeb_PS_Loader $loader;

	/**
	 * Initializes the WooCommerce integration.
	 *
	 * @param string         $plugin_name Plugin handle.
	 * @param string         $version     Plugin version.
	 * @param DWeb_PS_Loader $loader      Hook loader.
	 */
	public function __construct( string $plugin_name, string $version, DWeb_PS_Loader $loader ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		$this->loader = $loader;
	}

	/**
	 * Load dependencies
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		require_once plugin_dir_path( __DIR__ ) . '/woo/class-dweb-ps-woo-metabox.php';
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		$meta_box = new DWeb_PS_WOO_METABOX( $this->plugin_name, $this->version );
		$this->loader->dweb_ps_add_action( 'add_meta_boxes', $meta_box, 'dweb_ps_add_boxes' );
		$this->loader->dweb_ps_add_action( 'save_post', $meta_box, 'dweb_ps_save_boxes' );
		$this->loader->dweb_ps_add_action( 'admin_enqueue_scripts', $meta_box, 'dweb_ps_enqueue_scripts' );
		$this->loader->dweb_ps_add_action( 'wp_ajax_dweb_ps_search_products', $meta_box, 'dweb_ps_search_products' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {
		$current_theme     = wp_get_theme();
		$plugin_public_woo = ( new DWeb_PS_Public_Theme_Factory( $this->plugin_name, $this->version ) )->dweb_ps_create_woo_theme_class( $current_theme->get( 'Name' ) );

		$this->loader->dweb_ps_add_filter( 'woocommerce_cart_item_thumbnail', $plugin_public_woo, 'dweb_ps_handle_change_cart_image', 1, 3 );
		$this->loader->dweb_ps_add_filter( 'woocommerce_store_api_cart_item_images', $plugin_public_woo, 'dweb_ps_handle_store_api_cart_item_images', 10, 3 );
		$this->loader->dweb_ps_add_filter( 'woocommerce_admin_order_item_thumbnail', $plugin_public_woo, 'dweb_ps_handle_admin_order_item_thumbnail', 10, 3 );
		$this->loader->dweb_ps_add_filter( 'woocommerce_order_item_display_meta_value', $plugin_public_woo, 'dweb_ps_handle_order_item_display_meta_value', 10, 3 );
		$this->loader->dweb_ps_add_filter( 'woocommerce_order_item_get_formatted_meta_data', $plugin_public_woo, 'dweb_ps_handle_order_item_formatted_meta_data', 10, 2 );
		$this->loader->dweb_ps_add_action( 'wp_ajax_3dweb_ps_download_design', $plugin_public_woo, 'dweb_ps_handle_admin_design_download', 10, 0 );
		$this->loader->dweb_ps_add_filter( 'woocommerce_before_add_to_cart_button', $plugin_public_woo, 'dweb_ps_handle_add_custom_hidden_field', 10, 0 );
		$this->loader->dweb_ps_add_filter( 'woocommerce_add_cart_item_data', $plugin_public_woo, 'dweb_ps_handle_add_to_cart_item', 10, 2 );
		$this->loader->dweb_ps_add_filter( 'woocommerce_add_to_cart_redirect', $plugin_public_woo, 'dweb_ps_handle_add_to_cart_redirect', 10, 1 );
		$this->loader->dweb_ps_add_filter( 'woocommerce_checkout_create_order_line_item', $plugin_public_woo, 'dweb_ps_handle_create_order_line_item', 10, 4 );
		$this->loader->dweb_ps_add_filter( 'woocommerce_get_item_data', $plugin_public_woo, 'dweb_ps_handle_get_item_data', 10, 2 );

		$this->loader->dweb_ps_add_filter( 'woocommerce_gallery_image_html_attachment_image_params', $plugin_public_woo, 'dweb_ps_handle_image_params', 999, 2 );
		$this->loader->dweb_ps_add_filter( 'wp_get_attachment_image_src', $plugin_public_woo, 'dweb_ps_handle_image_params_thumb', 999, 4 );

		$this->loader->dweb_ps_add_action( 'wp_enqueue_scripts', $plugin_public_woo, 'dweb_ps_enqueue_styles' );
		$this->loader->dweb_ps_add_action( 'wp_enqueue_scripts', $plugin_public_woo, 'dweb_ps_enqueue_scripts' );
	}


	/**
	 * Runs the WooCommerce integration.
	 *
	 * @since 1.0.0
	 */
	public function dweb_ps_run() {
		$this->load_dependencies();
		$this->define_public_hooks();
		$this->define_admin_hooks();
	}

	/**
	 * Checks whether WooCommerce is active.
	 *
	 * @return bool
	 */
	public static function dweb_ps_woocommerce_is_active() {
		$active_plugins = (array) get_option( 'active_plugins', array() );
		if ( is_multisite() ) {
			$network_active = array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) );
			$active_plugins = array_merge( $active_plugins, $network_active );
		}

		return in_array( 'woocommerce/woocommerce.php', $active_plugins, true );
	}
}
