<?php
/**
 * Admin plugin bootstrap.
 *
 * @package DWeb_PS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin-facing plugin integration.
 */
class DWeb_PS_Admin {

	const REDIRECT_OPTION_NAME = 'dweb_ps_do_activation_redirect';
	const AJAX_NONCE_ACTION    = 'dweb_ps_save_settings';
	const PLUGIN_MENU_SLUG     = '3dweb-ps-main-settings';

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
	 * Registered admin pages.
	 *
	 * @var array
	 */
	private array $pages = array();

	/**
	 * Sets up the admin plugin integration.
	 *
	 * @param string $plugin_name Plugin handle.
	 * @param string $version     Plugin version.
	 */
	public function __construct( string $plugin_name, string $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->load_pages();
		$this->dweb_ps_load_hooks();
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function dweb_ps_admin_enqueue_styles() {
		wp_enqueue_style(
			$this->plugin_name,
			plugin_dir_url( __FILE__ ) . 'scss/3dweb-ps-admin.css',
			array(),
			$this->version,
			'all'
		);
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook Current admin hook suffix.
	 */
	public function dweb_ps_admin_enqueue_scripts( $hook ) {
		if ( ! did_action( 'wp_enqueue_media' ) ) {
			wp_enqueue_media();
		}
		wp_enqueue_script( '3dweb-ps-admin', plugin_dir_url( __FILE__ ) . 'js/admin.js', array( 'jquery' ), $this->version, true );

		wp_localize_script(
			'3dweb-ps-admin',
			'dwebPsAdmin',
			array(
				'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
				'security' => wp_create_nonce( self::AJAX_NONCE_ACTION ),
				'hook'     => $hook,
			)
		);
	}

	/**
	 * Registers page menu hooks.
	 *
	 * Registers the plugin admin menu pages.
	 */
	public function dweb_ps_load_page_menu() {
		foreach ( $this->pages as $page ) {
			$page->dweb_ps_load_menu_item( self::PLUGIN_MENU_SLUG );
		}
	}

	/**
	 * Registers page AJAX hooks.
	 *
	 * Loads hooks for the registered admin pages.
	 */
	private function dweb_ps_load_hooks() {
		foreach ( $this->pages as $page ) {
			$page->dweb_ps_load_hooks();
		}
	}

	/**
	 * Handles plugin activation redirect behavior.
	 */
	public function dweb_ps_load_startup() {
		if ( get_option( self::REDIRECT_OPTION_NAME, false ) ) {
			delete_option( self::REDIRECT_OPTION_NAME );
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Admin activation redirect flag is read-only and does not mutate state.
			if ( ! isset( $_GET['activate-multi'] ) ) {
				wp_safe_redirect( admin_url( 'admin.php?page=3dweb-ps-main-settings' ) );
				exit;
			}
		}
	}

	/**
	 * Handles plugin activation.
	 */
	public function dweb_ps_activation() {
		add_option( self::REDIRECT_OPTION_NAME, true );
	}

	/**
	 * Handles plugin deactivation.
	 */
	public function dweb_ps_de_activation() {
		delete_option( self::REDIRECT_OPTION_NAME );
	}

	/**
	 * Loads pages for the admin menu.
	 */
	private function load_pages() {
		$path = plugin_dir_path( __FILE__ ) . 'pages/';
		require_once $path . 'class-dweb-ps-admin-page-abstract.php';
		require_once $path . 'class-dweb-ps-admin-about.php';
		require_once $path . 'class-dweb-ps-admin-api.php';
		require_once $path . 'class-dweb-ps-admin-options.php';

		$this->pages = array(
			new DWeb_PS_ADMIN_ABOUT(),
			new DWeb_PS_ADMIN_API(),
			new DWeb_PS_ADMIN_OPTIONS(),
		);
	}
}
