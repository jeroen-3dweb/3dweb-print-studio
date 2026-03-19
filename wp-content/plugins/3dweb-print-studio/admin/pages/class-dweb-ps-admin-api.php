<?php
/**
 * API settings admin page.
 *
 * @package DWeb_PS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * API settings page controller.
 */
class DWeb_PS_ADMIN_API extends DWeb_PS_ADMIN_PAGE_ABSTRACT {

	const PATH = '3dweb-ps-api-settings';

	/**
	 * Page title.
	 *
	 * @var string
	 */
	protected string $page_title = 'API Settings';

	/**
	 * Menu title.
	 *
	 * @var string
	 */
	protected string $menu_title = 'API Settings';

	/**
	 * Template slug.
	 *
	 * @var string
	 */
	protected string $template = '3dweb-ps-admin-display-api';

	/**
	 * Additional AJAX hooks.
	 *
	 * @var array
	 */
	protected array $custom_ajax_hooks = array(
		'dweb_ps-check-auth' => 'dweb_ps_ajax_check_auth',
	);

	const TOKEN                     = 'dweb_ps_token';
	const CONFIGURATOR_HOST         = 'dweb_ps_configurator_host';
	const CONFIGURATOR_HOST_VERSION = 'dweb_ps_configurator_host_version';

	/**
	 * Supported option fields.
	 *
	 * @var array
	 */
	protected array $fields = array(
		self::TOKEN,
		self::CONFIGURATOR_HOST,
		self::CONFIGURATOR_HOST_VERSION,
	);

	/**
	 * Normalizes API field values before saving.
	 *
	 * @param string $key   Field key.
	 * @param mixed  $value Submitted value.
	 * @return mixed
	 */
	protected function normalize_field_value( $key, $value ) {
		if ( self::CONFIGURATOR_HOST === $key && '' === $value ) {
			return DWeb_PS_API::DEFAULT_CONFIGURATOR_HOST;
		}
		if ( self::CONFIGURATOR_HOST_VERSION === $key && '' === $value ) {
			return DWeb_PS_API::DEFAULT_API_VERSION;
		}

		return $value;
	}

	/**
	 * AJAX handler to test credentials against /check-auth endpoint
	 */
	public function dweb_ps_ajax_check_auth() {
		$this->dweb_ps_require_admin_ajax_request(
			'message',
			__( 'You do not have permission to perform this action.', '3dweb-print-studio' )
		);

		// Use current form values first (even when not saved), fallback to options.
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verification happens in dweb_ps_require_admin_ajax_request().
		$token = isset( $_POST[ self::TOKEN ] )
			? sanitize_text_field( wp_unslash( $_POST[ self::TOKEN ] ) )
			: get_option( self::TOKEN, '' );
		$host  = isset( $_POST[ self::CONFIGURATOR_HOST ] )
			? esc_url_raw( wp_unslash( $_POST[ self::CONFIGURATOR_HOST ] ) )
			: get_option( self::CONFIGURATOR_HOST, DWeb_PS_API::DEFAULT_CONFIGURATOR_HOST );
		$ver   = isset( $_POST[ self::CONFIGURATOR_HOST_VERSION ] )
			? sanitize_key( wp_unslash( $_POST[ self::CONFIGURATOR_HOST_VERSION ] ) )
			: get_option( self::CONFIGURATOR_HOST_VERSION, DWeb_PS_API::DEFAULT_API_VERSION );
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( empty( $host ) ) {
			$host = DWeb_PS_API::DEFAULT_CONFIGURATOR_HOST;
		}
		if ( empty( $ver ) ) {
			$ver = DWeb_PS_API::DEFAULT_API_VERSION;
		}
		if ( ! in_array( $ver, array( DWeb_PS_API::DEFAULT_API_VERSION ), true ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid API version.', '3dweb-print-studio' ),
				)
			);
		}

		$missing = array();
		if ( empty( $token ) ) {
			$missing[] = 'Token';
		}
		if ( ! empty( $missing ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Please fill in the following fields first:', '3dweb-print-studio' ) . ' ' . implode( ', ', $missing ) . '.',
				)
			);
		}

		$api    = ( new DWeb_PS_API() )->dweb_ps_with_runtime_config(
			array(
				'token'   => $token,
				'host'    => $host,
				'version' => $ver,
			)
		);
		$result = $api->dweb_ps_perform_get( 'check-auth' );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Authentication failed:', '3dweb-print-studio' ) . ' ' . $result->get_error_message(),
					'data'    => $result->get_error_data(),
				)
			);
		}

		if ( false === $result ) {
			wp_send_json_error(
				array(
					'message' => __( 'Authentication failed or the server returned an error.', '3dweb-print-studio' ),
					'data'    => $result,
				)
			);
		}

		// Test if the response data contains an error.
		if ( isset( $result['error'] ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Authentication failed:', '3dweb-print-studio' ) . ' ' . $result['error'],
					'data'    => $result,
				)
			);
		}

		if ( null === $result ) {
			wp_send_json_error(
				array(
					'message' => __( 'Authentication failed: Unknown error', '3dweb-print-studio' ),
					'data'    => $result,
				)
			);
		}

		wp_send_json_success( $result );
	}
}
