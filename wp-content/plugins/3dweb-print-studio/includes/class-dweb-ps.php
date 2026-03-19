<?php
/**
 * Core plugin bootstrap.
 *
 * @package DWeb_PS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class.
 */
class DWeb_PS {
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
	 * Initializes the plugin.
	 *
	 * @param string $version Plugin version.
	 */
	public function __construct( string $version ) {
		$this->version = $version;

		$this->plugin_name = '3dweb-print-studio';

		$this->load_loader();

		$this->load_dependencies();
		$this->define_public_hooks();
		$this->define_admin_hooks();

		$this->load_plugin_hooks();
	}

	/**
	 * Load dependencies
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		// Load public.
		require_once plugin_dir_path( __DIR__ ) . 'public/class-dweb-ps-public.php';

		// Load admin.
		require_once plugin_dir_path( __DIR__ ) . 'admin/class-dweb-ps-admin.php';

		// Plugins.
		require_once plugin_dir_path( __DIR__ ) . 'includes/woo/class-dweb-ps-woo.php'; // Woo.
		require_once plugin_dir_path( __DIR__ ) . 'includes/api/class-dweb-ps-api.php'; // API Configurator.
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		$plugin_admin = new DWeb_PS_Admin( $this->plugin_name, $this->version );

		$this->loader->dweb_ps_add_action( 'admin_enqueue_scripts', $plugin_admin, 'dweb_ps_admin_enqueue_styles' );
		$this->loader->dweb_ps_add_action( 'admin_enqueue_scripts', $plugin_admin, 'dweb_ps_admin_enqueue_scripts' );

		$this->loader->dweb_ps_add_action( 'admin_menu', $plugin_admin, 'dweb_ps_load_page_menu' );

		register_activation_hook( DWEB_PS_MAIN_URL, array( $plugin_admin, 'dweb_ps_activation' ) );
		register_deactivation_hook( DWEB_PS_MAIN_URL, array( $plugin_admin, 'dweb_ps_de_activation' ) );

		$this->loader->dweb_ps_add_action( 'admin_init', $plugin_admin, 'dweb_ps_load_startup' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {
		$plugin_public = new DWeb_PS_Public( $this->plugin_name, $this->version );

		$this->loader->dweb_ps_add_action( 'wp_enqueue_scripts', $plugin_public, 'dweb_ps_enqueue_styles' );
		$this->loader->dweb_ps_add_action( 'wp_enqueue_scripts', $plugin_public, 'dweb_ps_enqueue_scripts' );
	}

	/**
	 * Runs the plugin.
	 *
	 * @since 1.0.0
	 */
	public function dweb_ps_run() {
		$this->loader->dweb_ps_run();
	}

	/**
	 * Runs plugin-specific hooks.
	 */
	private function load_plugin_hooks() {
		if ( DWeb_PS_WOO::dweb_ps_woocommerce_is_active() ) {
			( new DWeb_PS_WOO( $this->plugin_name, $this->version, $this->loader ) )->dweb_ps_run();
		}
	}

	/**
	 * Loads the hook loader dependency.
	 */
	private function load_loader() {
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-dweb-ps-loader.php';
		$this->loader = new DWeb_PS_Loader();
	}
}
