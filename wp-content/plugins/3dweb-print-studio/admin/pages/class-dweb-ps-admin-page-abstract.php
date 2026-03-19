<?php
/**
 * Abstract admin page controller.
 *
 * @package DWeb_PS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base admin page controller.
 */
abstract class DWeb_PS_ADMIN_PAGE_ABSTRACT {

	const PATH = '';

	/**
	 * Template slug for the page partial.
	 *
	 * @var string
	 */
	protected string $template = '';

	/**
	 * Option field keys handled by this page.
	 *
	 * @var array
	 */
	protected array $fields = array();

	/**
	 * Checkbox field keys handled by this page.
	 *
	 * @var array
	 */
	protected array $check_boxes = array();

	/**
	 * Page title.
	 *
	 * @var string
	 */
	protected string $page_title = 'n.a';

	/**
	 * Menu title.
	 *
	 * @var string
	 */
	protected string $menu_title = 'n.a';

	/**
	 * Whether this page registers the main menu item.
	 *
	 * @var bool
	 */
	protected bool $is_main_menu = false;

	/**
	 * Additional AJAX hooks for the page.
	 *
	 * @var array
	 */
	protected array $custom_ajax_hooks = array();

	/**
	 * Normalizes a field value before saving.
	 *
	 * @param string $key   Field key.
	 * @param mixed  $value Submitted value.
	 * @return mixed
	 */
	protected function normalize_field_value( $key, $value ) {
		return $value;
	}

	/**
	 * Registers the page in the WordPress admin menu.
	 *
	 * @param string $main_slug Main plugin menu slug.
	 * @return void
	 */
	public function dweb_ps_load_menu_item( $main_slug ) {
		if ( $this->is_main_menu ) {
			add_menu_page(
				$this->page_title,
				$this->menu_title,
				'manage_options',
				$main_slug,
				array( $this, 'dweb_ps_init' ),
				plugin_dir_url( __FILE__ ) . '../img/sign-36-bw.svg'
			);
		} else {
			add_submenu_page(
				$main_slug,
				$this->page_title,
				$this->menu_title,
				'manage_options',
				$this::PATH,
				array( $this, 'dweb_ps_init' )
			);
		}
	}

	/**
	 * Renders the page template.
	 *
	 * @return void
	 */
	public function dweb_ps_init() {
		$template_path = sprintf( '%s/../partials/%s.php', __DIR__, $this->template );
		if ( file_exists( $template_path ) ) {
			require $template_path;
		}
	}

	/**
	 * Saves page settings via AJAX.
	 *
	 * @return void
	 */
	public function dweb_ps_save_settings() {
		$response = array();
		$this->dweb_ps_require_admin_ajax_request();

		// Add default values for unchecked checkboxes.
		foreach ( $this->check_boxes as $check_box ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification happens in dweb_ps_require_admin_ajax_request().
			if ( ! isset( $_POST[ $check_box ] ) ) {
				$_POST[ $check_box ] = 0;
			}
		}

		$field_map = array();
		foreach ( $this->fields as $field ) {
			$field_map[ sanitize_key( $field ) ] = $field;
		}

		$n_updated = 0;
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification happens in dweb_ps_require_admin_ajax_request().
		$n_expected = count( $_POST ) - 2;

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification happens in dweb_ps_require_admin_ajax_request().
		foreach ( $_POST as $raw_key => $raw_value ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification happens in dweb_ps_require_admin_ajax_request().
			$sanitized_key = sanitize_key( $raw_key );

			if ( in_array( $sanitized_key, array( 'action', '_ajax_nonce' ), true ) ) {
				continue;
			}

			$response[ $sanitized_key ] = array(
				'value' => '',
				'error' => '',
			);
			if ( isset( $field_map[ $sanitized_key ] ) ) {
				$key = $field_map[ $sanitized_key ];

				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification happens in dweb_ps_require_admin_ajax_request().
				if ( ! array_key_exists( $raw_key, $_POST ) ) {
					$response[ $sanitized_key ]['error'] = sprintf( 'field %s missing from request', $sanitized_key );
					continue;
				}

				if ( ! $this->dweb_ps_has_valid_option_prefix( $key ) ) {
					$response[ $sanitized_key ]['error'] = sprintf( 'option %s must use the dweb_ps prefix', $sanitized_key );
					continue;
				}

				$value     = sanitize_text_field( wp_unslash( $raw_value ) );
				$value     = $this->normalize_field_value( $key, $value );
				$old_value = get_option( $key, '' );
				if ( update_option( $key, $value ) || $value === $old_value ) {
					++$n_updated;
					$response[ $sanitized_key ]['value'] = $value;
				} else {
					$response[ $sanitized_key ]['error'] = sprintf( 'could not update %s', $sanitized_key );
				}
			} else {
				$response[ $sanitized_key ]['error'] = 'field not defined in controller';
			}
		}
		if ( $n_updated === $n_expected ) {
			wp_send_json_success( $response );
		} else {
			wp_send_json_error( $response );
		}
	}

	/**
	 * Registers AJAX hooks for the page.
	 *
	 * @return void
	 */
	public function dweb_ps_load_hooks() {
		add_action( 'wp_ajax_' . $this::PATH, array( $this, 'dweb_ps_save_settings' ) );
		foreach ( $this->custom_ajax_hooks as $method_name => $custom_ajax_hook ) {
			add_action( 'wp_ajax_' . $method_name, array( $this, $custom_ajax_hook ) );
		}
	}

	/**
	 * Checks whether the option key belongs to this plugin.
	 *
	 * @param string $key Option key.
	 * @return bool
	 */
	private function dweb_ps_has_valid_option_prefix( $key ) {
		return is_string( $key ) && strpos( strtolower( $key ), 'dweb_ps_' ) === 0;
	}

	/**
	 * Validates a privileged admin AJAX request.
	 *
	 * @param string $error_key     Error array key.
	 * @param string $error_message Error message.
	 * @return void
	 */
	protected function dweb_ps_require_admin_ajax_request( $error_key = 'error', $error_message = 'user does not have permission to manage options' ) {
		check_ajax_referer( DWeb_PS_Admin::AJAX_NONCE_ACTION, '_ajax_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					$error_key => $error_message,
				)
			);
		}
	}
}
