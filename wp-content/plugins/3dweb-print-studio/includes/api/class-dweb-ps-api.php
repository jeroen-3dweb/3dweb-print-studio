<?php
/**
 * Remote API client.
 *
 * @package DWeb_PS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles communication with the 3DWeb API.
 */
class DWeb_PS_API {

	const DEFAULT_CONFIGURATOR_HOST = 'https://api.3dweb.io';
	const DEFAULT_API_VERSION       = 'v1';

	/**
	 * Plugin version.
	 *
	 * @var string|null
	 */
	private ?string $version;

	/**
	 * Plugin handle.
	 *
	 * @var string|null
	 */
	private ?string $plugin_name;

	/**
	 * Runtime configuration overrides.
	 *
	 * @var array<string, mixed>
	 */
	private array $runtime_config = array();

	/**
	 * Initializes the API client.
	 *
	 * @param string|null $plugin_name Plugin handle.
	 * @param string|null $version     Plugin version.
	 */
	public function __construct( ?string $plugin_name = null, ?string $version = null ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Applies runtime config overrides for the next requests.
	 *
	 * @param array $config Runtime config values.
	 * @return self
	 */
	public function dweb_ps_with_runtime_config( array $config ) {
		$this->runtime_config = $config;
		return $this;
	}

	/**
	 * Creates a new design session.
	 *
	 * @param string $product_id   Product identifier.
	 * @param string $callback_url Callback URL.
	 * @return array
	 */
	public function dweb_ps_create_new_session( $product_id, $callback_url ) {
		$end_point = 'sessions/' . $product_id;
		return $this->dweb_ps_perform_post(
			$end_point,
			array(
				'callback_url' => $callback_url,
			)
		);
	}

	/**
	 * Fetches assets for a session.
	 *
	 * @param string $session_id Session identifier.
	 * @return array
	 */
	public function dweb_ps_get_session_assets( $session_id ) {
		$end_point = 'sessions/' . $session_id . '/assets';
		return $this->dweb_ps_perform_get( $end_point );
	}

	/**
	 * Searches products through the API.
	 *
	 * @param string $search Search term.
	 * @return array
	 */
	public function dweb_ps_search_products( $search = '' ) {
		$end_point = 'products';
		if ( ! empty( $search ) ) {
			$end_point .= '?search=' . rawurlencode( $search );
		}
		return $this->dweb_ps_perform_get( $end_point );
	}

	/**
	 * Performs a GET request.
	 *
	 * @param string $end_point API endpoint.
	 * @return array
	 */
	public function dweb_ps_perform_get( $end_point ) {
		$version           = $this->get_api_version();
		$configurator_host = $this->dweb_ps_get_configurator_host();
		$url               = sprintf( '%s/%s/%s', $configurator_host, $version, $end_point );

		$args = $this->dweb_ps_get_args();

		$response = wp_remote_get( $url, $args );
		if ( is_wp_error( $response ) ) {
			return array(
				'error' => $response->get_error_message(),
			);
		}

		$body = wp_remote_retrieve_body( $response );
		$code = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $code ) {
			$data = json_decode( $body, true );
			if ( ! is_array( $data ) ) {
				$data = array(
					'error' => 'API returned code ' . $code,
					'body'  => $body,
				);
			}
			return $data;
		}

		return json_decode( $body, true );
	}

	/**
	 * Performs a POST request.
	 *
	 * @param string $end_point API endpoint.
	 * @param array  $data      Request payload.
	 * @return array
	 */
	public function dweb_ps_perform_post( $end_point, $data ) {
		$version           = $this->get_api_version();
		$configurator_host = $this->dweb_ps_get_configurator_host();
		$url               = sprintf( '%s/%s/%s', $configurator_host, $version, $end_point );

		$args = $this->dweb_ps_get_args( $data );

		$response = wp_remote_post( $url, $args );

		if ( is_wp_error( $response ) ) {
			return array(
				'error' => $response->get_error_message(),
			);
		}

		$body = wp_remote_retrieve_body( $response );
		$code = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $code ) {
			$data = json_decode( $body, true );
			if ( ! is_array( $data ) ) {
				$data = array(
					'error' => 'API returned code ' . $code,
					'body'  => $body,
				);
			}
			return $data;
		}

		return json_decode( $body, true );
	}

	/**
	 * Builds request arguments for the API.
	 *
	 * @param array|null $data Request payload.
	 * @return array
	 */
	public function dweb_ps_get_args( $data = null ): array {
		$token = isset( $this->runtime_config['token'] )
			? $this->runtime_config['token']
			: get_option( DWeb_PS_ADMIN_API::TOKEN );
		$args  = array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json',
			),
			'timeout' => 60,
		);

		if ( $data ) {
			$args['body'] = wp_json_encode( $data );
		}
		return $args;
	}

	/**
	 * Gets the configured API host.
	 *
	 * @return string
	 */
	public function dweb_ps_get_configurator_host(): string {
		$configurator_host = isset( $this->runtime_config['host'] )
			? $this->runtime_config['host']
			: get_option( DWeb_PS_ADMIN_API::CONFIGURATOR_HOST, self::DEFAULT_CONFIGURATOR_HOST );
		if ( empty( $configurator_host ) ) {
			$configurator_host = self::DEFAULT_CONFIGURATOR_HOST;
		}
		return rtrim( $configurator_host, '/' );
	}

	/**
	 * Gets the configured API version.
	 *
	 * @return string
	 */
	private function get_api_version(): string {
		$version = isset( $this->runtime_config['version'] )
			? $this->runtime_config['version']
			: get_option( DWeb_PS_ADMIN_API::CONFIGURATOR_HOST_VERSION, self::DEFAULT_API_VERSION );
		if ( empty( $version ) ) {
			$version = self::DEFAULT_API_VERSION;
		}
		return $version;
	}
}
