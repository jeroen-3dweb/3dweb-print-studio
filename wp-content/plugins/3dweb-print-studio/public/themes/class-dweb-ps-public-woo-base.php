<?php
/**
 * Base WooCommerce theme integration.
 *
 * @package DWeb_PS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared WooCommerce integration for theme adapters.
 */
	class DWeb_PS_Public_Woo_Base {

	const THEME_NAME             = 'base';
	const TEAM_SESSION_REFERENCE = 'teamSessionReference';
	const TEAM_SESSION_NONCE_KEY = 'dwebPsTeamNonce';
	const TEAM_SESSION_NONCE_ACTION = '3dweb_ps_team_session_reference';

	/**
	 * Plugin handle.
	 *
	 * @var string
	 */
	protected string $plugin_name;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	protected string $version;

	/**
	 * Loaded main session images.
	 *
	 * @var array
	 */
	private array $main_images = array();

	/**
	 * Whether session images have been loaded.
	 *
	 * @var bool
	 */
	private bool $session_images_loaded = false;

	/**
	 * Session asset cache keyed by team reference.
	 *
	 * @var array
	 */
	private array $session_assets_cache = array();

	/**
	 * Initializes the base Woo integration.
	 *
	 * @param string $plugin_name Plugin handle.
	 * @param string $version     Plugin version.
	 */
	public function __construct( string $plugin_name, string $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		add_action( 'wp_ajax_3dweb_ps_action_get_endpoint', array( self::class, 'dweb_ps_handle_get_endpoint_request' ) );
		add_action( 'wp_ajax_nopriv_3dweb_ps_action_get_endpoint', array( self::class, 'dweb_ps_handle_get_endpoint_request' ) );
		add_action( 'wp_ajax_3dweb_ps_public_download_design', array( $this, 'dweb_ps_handle_public_design_download' ) );
		add_action( 'wp_ajax_nopriv_3dweb_ps_public_download_design', array( $this, 'dweb_ps_handle_public_design_download' ) );
	}

	/**
	 * Generates a temporary DOM identifier.
	 *
	 * @return string
	 */
	protected function generate_id() {
		$permitted_chars = implode( '', range( 'a', 'z' ) );
		return 'cnf--' . substr( str_shuffle( $permitted_chars ), 0, 10 );
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.5.0
	 */
	protected function load_scripts() {
		wp_enqueue_script(
			$this->plugin_name . '_public_core',
			plugin_dir_url( __DIR__ ) . 'js/3dweb-ps-public.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		wp_enqueue_script(
			$this->plugin_name . '_public_flexslider',
			plugin_dir_url( __DIR__ ) . 'js/sliders/3dweb-ps-flexslider.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		$this->dweb_ps_load_extra_scripts();
	}

	/**
	 * Enqueues theme-specific styles.
	 *
	 * @return void
	 */
	public function dweb_ps_enqueue_styles() {
		$this->dweb_ps_load_extra_styles();
	}

	/**
	 * Enqueues public scripts and localized session data.
	 *
	 * @return void
	 */
	public function dweb_ps_enqueue_scripts() {
		global $post;
		$this->load_scripts();

		if ( isset( $post ) && is_object( $post ) && isset( $post->ID ) ) {
			$post_id = (int) $post->ID;
		} else {
			$post_id = (int) get_queried_object_id();
		}

		$product_sku = '';
		if ( $post_id > 0 ) {
			$product_sku = sanitize_meta(
				DWeb_PS_WOO_METABOX::FIELD_PRODUCT_SKU,
				get_post_meta( $post_id, DWeb_PS_WOO_METABOX::FIELD_PRODUCT_SKU, true ),
				'post'
			);
		}

		if ( empty( $product_sku ) ) {
			return;
		}

		$assets         = array();
		$team_reference = $this->get_team_reference();
		if ( $team_reference ) {
			$response = ( new DWeb_PS_API() )->dweb_ps_get_session_assets( $team_reference );
			if ( $response && ! isset( $response['error'] ) && ! isset( $response['errors'] ) && isset( $response['data'] ) ) {
				$assets = $response['data'];
			}
		}
		// Hook for the endpoint request.
		wp_localize_script(
			$this->plugin_name . '_woo_js',
			'dwebPsConfig',
			array(
				'ajax_url'           => admin_url( 'admin-ajax.php' ),
				'security'           => wp_create_nonce( '3dweb_ps-nonce' ),
				'action'             => '3dweb_ps_action_get_endpoint',
				'product_id'         => $post_id,
				'product_sku'        => $product_sku,
				'assets'             => $assets,
				'team_reference'     => $this->get_team_reference(),
				'team_reference_key' => self::TEAM_SESSION_REFERENCE,
				'showDesignLink'     => (bool) get_option( DWeb_PS_ADMIN_OPTIONS::CONFIGURATOR_SHOW_SESSION_DESIGN_LINK, true ),
				'useThreeSixtyView'  => false,
				'debug'              => get_option( DWeb_PS_ADMIN_OPTIONS::CONFIGURATOR_DEBUG, false ),
				'threeSixtyConfig'   => array(),
				'translations'       => array(
					'startConfiguration' => get_option( DWeb_PS_ADMIN_OPTIONS::CONFIGURATOR_START_BUTTON_TEXT, __( 'Start configuration', '3dweb-print-studio' ) ),
					'loading'            => __( 'Loading ...', '3dweb-print-studio' ),
					'sessionClosed'      => get_option( DWeb_PS_ADMIN_OPTIONS::CONFIGURATOR_SESSION_CLOSED_TEXT, 'Design: {reference}' ),
					'galleryLoading'     => get_option( DWeb_PS_ADMIN_OPTIONS::CONFIGURATOR_GALLERY_LOADING_TEXT, __( 'Preparing your preview...', '3dweb-print-studio' ) ),
					'sessionLoading'     => get_option( DWeb_PS_ADMIN_OPTIONS::CONFIGURATOR_SESSION_LOADING_TEXT, __( 'Opening configurator...', '3dweb-print-studio' ) ),
				),
			)
		);
	}

	/**
	 * Handles the public AJAX endpoint requests.
	 *
	 * @return void
	 */
	public static function dweb_ps_handle_get_endpoint_request() {
		check_ajax_referer( '3dweb_ps-nonce', 'security' );

		$method = isset( $_POST['method'] ) ? sanitize_text_field( wp_unslash( $_POST['method'] ) ) : '';

		switch ( $method ) {

			case 'get_session':
				$product_id   = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
				$product_sku  = isset( $_POST['product_sku'] )
					? sanitize_text_field( wp_unslash( $_POST['product_sku'] ) )
					: '';
				$callback_url = isset( $_POST['post_url'] ) ? esc_url_raw( wp_unslash( $_POST['post_url'] ) ) : '';

				if ( $product_id <= 0 ) {
					wp_send_json_error( array( 'message' => 'Invalid product ID.' ), 400 );
				}

				if ( get_post_type( $product_id ) !== 'product' ) {
					wp_send_json_error( array( 'message' => 'Invalid product.' ), 400 );
				}

				if ( ! wc_get_product( $product_id ) ) {
					wp_send_json_error( array( 'message' => 'Product not found.' ), 400 );
				}

				if ( '' === $product_sku ) {
					wp_send_json_error( array( 'message' => 'Missing product SKU.' ), 400 );
				}

				if ( '' === $callback_url ) {
					wp_send_json_error( array( 'message' => 'Missing callback URL.' ), 400 );
				}

				// TODO: Build the callback URL server-side instead of accepting it from client input.
				$validator = new self( '3dweb_ps', DWEB_PS_VERSION );
				if ( ! $validator->is_allowed_callback_url( $callback_url ) ) {
					wp_send_json_error( array( 'message' => 'Invalid callback URL.' ), 400 );
				}

				$stored_product_sku = get_post_meta( $product_id, DWeb_PS_WOO_METABOX::FIELD_PRODUCT_SKU, true );
				$stored_product_sku = is_string( $stored_product_sku ) ? sanitize_text_field( $stored_product_sku ) : '';

				if ( '' === $stored_product_sku || ! hash_equals( $stored_product_sku, $product_sku ) ) {
					wp_send_json_error( array( 'message' => 'Product SKU does not match the selected product.' ), 400 );
				}

				$callback_url = add_query_arg(
					self::TEAM_SESSION_NONCE_KEY,
					wp_create_nonce( self::TEAM_SESSION_NONCE_ACTION ),
					$callback_url
				);

				$response = ( new DWeb_PS_API() )->dweb_ps_create_new_session( $product_sku, $callback_url );
				break;

			case 'get_assets':
				$self = new self( '3dweb_ps', '1.0.0' );
				$self->load_session_urls();
				$response = $self->main_images;
				break;

			default:
				$response = false;
				break;
		}

		if ( false === $response || isset( $response['error'] ) || isset( $response['errors'] ) ) {
			$error_message = 'Something went wrong';
			if ( is_array( $response ) ) {
				if ( isset( $response['message'] ) ) {
					$error_message = $response['message'];
				} elseif ( isset( $response['error'] ) ) {
					$error_message = $response['error'];
				} elseif ( isset( $response['errors'] ) ) {
					// Handle multiple errors if returned by the API.
					if ( is_array( $response['errors'] ) ) {
						$error_message = implode(
							', ',
							array_map(
								function ( $err ) {
									return is_array( $err ) ? implode( ': ', $err ) : $err;
								},
								$response['errors']
							)
						);
					} else {
						$error_message = $response['errors'];
					}
				}
			}
			wp_send_json_error( array( 'message' => $error_message ) );
		}

		wp_send_json_success( $response );
	}

	/**
	 * Gets the team reference from the current request.
	 *
	 * @return string|null
	 */
	private function get_team_reference() {
		$raw_team_reference = filter_input( INPUT_GET, self::TEAM_SESSION_REFERENCE, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( ! is_string( $raw_team_reference ) || '' === $raw_team_reference ) {
			return null;
		}

		$nonce = $this->get_team_reference_nonce_from_request( INPUT_GET );
		if ( ! $this->is_valid_team_reference_nonce( $nonce ) ) {
			return null;
		}

		return $this->sanitize_team_reference( wp_unslash( $raw_team_reference ) );
	}

	/**
	 * Outputs the hidden configurator reference field.
	 *
	 * @return void
	 */
	public function dweb_ps_handle_add_custom_hidden_field() {
		$team_reference = $this->get_team_reference();
		if ( $team_reference ) {
			printf(
				'<input type="hidden" name="%1$s" value="%2$s">',
				esc_attr( self::TEAM_SESSION_REFERENCE ),
				esc_attr( $team_reference )
			);
			printf(
				'<input type="hidden" name="%1$s" value="%2$s">',
				esc_attr( self::TEAM_SESSION_NONCE_KEY ),
				esc_attr( wp_create_nonce( self::TEAM_SESSION_NONCE_ACTION ) )
			);
		}
	}

	/**
	 * Stores the configurator reference on cart items.
	 *
	 * @param array $cart_item_data Cart item data.
	 * @param int   $product_id     Product ID.
	 * @return array
	 */
	public function dweb_ps_handle_add_to_cart_item( $cart_item_data, $product_id ) {
		if ( $product_id ) {
			$nonce = $this->get_team_reference_nonce_from_request( INPUT_POST );
			if ( ! $this->is_valid_team_reference_nonce( $nonce ) ) {
				return $cart_item_data;
			}

			$raw_team_reference = filter_input( INPUT_POST, self::TEAM_SESSION_REFERENCE, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			$team_reference     = is_string( $raw_team_reference )
				? $this->sanitize_team_reference( wp_unslash( $raw_team_reference ) )
				: null;

			if ( null !== $team_reference ) {
				$cart_item_data[ self::TEAM_SESSION_REFERENCE ] = $team_reference;
			}
		}
		return $cart_item_data;
	}

	/**
	 * Redirects back to the source page after add to cart.
	 *
	 * @param string $url Fallback redirect URL.
	 * @return string
	 */
	public function dweb_ps_handle_add_to_cart_redirect( $url ) {
		$redirect_url = wp_get_referer();
		if ( ! $redirect_url ) {
			$redirect_url = $url;
		}
		if ( ! $redirect_url ) {
			$redirect_url = home_url( '/' );
		}

		$team_reference = null;
		$team_nonce     = null;
		$redirect_query = wp_parse_url( $redirect_url, PHP_URL_QUERY );
		if ( is_string( $redirect_query ) ) {
			parse_str( $redirect_query, $query_args );
			if ( isset( $query_args[ self::TEAM_SESSION_REFERENCE ] ) ) {
				$team_reference = $this->sanitize_team_reference( $query_args[ self::TEAM_SESSION_REFERENCE ] );
			}
			if ( isset( $query_args[ self::TEAM_SESSION_NONCE_KEY ] ) ) {
				$team_nonce = sanitize_text_field( (string) $query_args[ self::TEAM_SESSION_NONCE_KEY ] );
			}
		}

		if ( null === $team_reference || ! $this->is_valid_team_reference_nonce( $team_nonce ) ) {
			return $redirect_url;
		}

		$redirect_url = remove_query_arg( array( 'add-to-cart', 'added-to-cart', 'quantity' ), $redirect_url );

		return add_query_arg(
			array(
				self::TEAM_SESSION_REFERENCE => $team_reference,
				self::TEAM_SESSION_NONCE_KEY => $team_nonce,
			),
			$redirect_url
		);
	}

	/**
	 * Gets the team reference nonce from request input.
	 *
	 * @param int $input_type Request input type.
	 * @return string
	 */
	private function get_team_reference_nonce_from_request( $input_type ) {
		$raw_nonce = filter_input( $input_type, self::TEAM_SESSION_NONCE_KEY, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( ! is_string( $raw_nonce ) || '' === $raw_nonce ) {
			return '';
		}

		return sanitize_text_field( wp_unslash( $raw_nonce ) );
	}

	/**
	 * Validates the team reference nonce value.
	 *
	 * @param string $nonce Nonce.
	 * @return bool
	 */
	private function is_valid_team_reference_nonce( $nonce ) {
		if ( ! is_string( $nonce ) || '' === $nonce ) {
			return false;
		}

		return (bool) wp_verify_nonce( $nonce, self::TEAM_SESSION_NONCE_ACTION );
	}

	/**
	 * Adds the configurator reference to displayed cart item data.
	 *
	 * @param array $item_data Cart item display data.
	 * @param array $cart_item Cart item values.
	 * @return array
	 */
	public function dweb_ps_handle_get_item_data( $item_data, $cart_item ) {
		$team_reference = isset( $cart_item[ self::TEAM_SESSION_REFERENCE ] )
			? $this->sanitize_team_reference( $cart_item[ self::TEAM_SESSION_REFERENCE ] )
			: null;

		if ( null !== $team_reference ) {
			$item_data[] = array(
				'key'   => 'reference',
				'value' => wc_clean( $team_reference ),
			);
		}
		return $item_data;
	}

	/**
	 * Persists configurator metadata on order items.
	 *
	 * @param WC_Order_Item_Product $item          Order item.
	 * @param string                $cart_item_key Cart item key.
	 * @param array                 $values        Cart item values.
	 * @param WC_Order              $order         Order object.
	 * @return WC_Order_Item_Product
	 */
	public function dweb_ps_handle_create_order_line_item( $item, $cart_item_key, $values, $order ) {
		unset( $order, $cart_item_key );

		if ( isset( $values[ self::TEAM_SESSION_REFERENCE ] ) ) {
			$team_reference = $this->sanitize_team_reference( $values[ self::TEAM_SESSION_REFERENCE ] );
			if ( null === $team_reference ) {
				return $item;
			}

			$item->add_meta_data( self::TEAM_SESSION_REFERENCE, $team_reference, true );

			$design_url = $this->get_session_design_url( $team_reference );
			if ( $design_url ) {
				$item->add_meta_data( 'design', $design_url, true );
			}
		}
		return $item;
	}

	/**
	 * Replaces the cart image with the generated session image.
	 *
	 * @param string $image         Original image HTML.
	 * @param array  $cart_item     Cart item values.
	 * @param string $cart_item_key Cart item key.
	 * @return string
	 */
	public function dweb_ps_handle_change_cart_image( $image, $cart_item, $cart_item_key ) {
		unset( $cart_item_key );

		$custom_image_url = $this->get_cart_item_custom_image_url( $cart_item );
		if ( ! $custom_image_url ) {
			return $image;
		}

		$alt_text = '';
		if ( isset( $cart_item['data'] ) && $cart_item['data'] instanceof WC_Product ) {
			$alt_text = $cart_item['data']->get_name();
		}

		return sprintf(
			'<img src="%s" alt="%s" class="attachment-woocommerce_thumbnail size-woocommerce_thumbnail" loading="lazy" decoding="async" />',
			esc_url( $custom_image_url ),
			esc_attr( $alt_text )
		);
	}

	/**
	 * Replaces Store API cart images with the generated session image.
	 *
	 * @param array  $product_images Product image objects.
	 * @param array  $cart_item      Cart item values.
	 * @param string $cart_item_key  Cart item key.
	 * @return array
	 */
	public function dweb_ps_handle_store_api_cart_item_images( $product_images, $cart_item, $cart_item_key ) {
		unset( $cart_item_key );

		$custom_image_url = $this->get_cart_item_custom_image_url( $cart_item );
		if ( ! $custom_image_url ) {
			return $product_images;
		}

		$base_image = null;
		if ( is_array( $product_images ) && ! empty( $product_images ) && is_object( $product_images[0] ) ) {
			$base_image = $product_images[0];
		}

		$image = (object) array(
			'id'        => isset( $base_image->id ) ? (int) $base_image->id : (int) ( $cart_item['product_id'] ?? 0 ),
			'src'       => $custom_image_url,
			'thumbnail' => $custom_image_url,
			'srcset'    => isset( $base_image->srcset ) ? (string) $base_image->srcset : '',
			'sizes'     => isset( $base_image->sizes ) ? (string) $base_image->sizes : '',
			'name'      => isset( $base_image->name ) ? (string) $base_image->name : '',
			'alt'       => isset( $base_image->alt ) ? (string) $base_image->alt : '',
		);

		return array( $image );
	}

	/**
	 * Replaces admin order thumbnails with the generated session image.
	 *
	 * @param string                $thumbnail Original thumbnail HTML.
	 * @param int                   $item_id   Order item ID.
	 * @param WC_Order_Item_Product $item      Order item.
	 * @return string
	 */
	public function dweb_ps_handle_admin_order_item_thumbnail( $thumbnail, $item_id, $item ) {
		if ( ! $item || ! is_a( $item, 'WC_Order_Item_Product' ) ) {
			return $thumbnail;
		}

		$team_reference = $this->sanitize_team_reference( $item->get_meta( self::TEAM_SESSION_REFERENCE, true ) );
		if ( null === $team_reference ) {
			return $thumbnail;
		}

		$custom_image_url = $this->get_session_main_image_url( $team_reference );
		if ( ! $custom_image_url ) {
			return $thumbnail;
		}

		$alt_text = $item->get_name();

		return sprintf(
			'<img src="%s" class="attachment-thumbnail size-thumbnail" alt="%s" title="" loading="lazy" style="width:100%%;height:100%%;max-width:none;max-height:none;object-fit:contain;display:block;margin:0;padding:0;" />',
			esc_url( $custom_image_url ),
			esc_attr( $alt_text )
		);
	}

	/**
	 * Formats displayed order item meta values.
	 *
	 * @param string $display_value Display value.
	 * @param object $meta          Meta object.
	 * @param mixed  $item          Order item.
	 * @return string
	 */
	public function dweb_ps_handle_order_item_display_meta_value( $display_value, $meta, $item ) {
		if ( ! is_object( $meta ) || ! isset( $meta->key ) || 'design' !== $meta->key ) {
			return $display_value;
		}

		if ( ! $item || ! is_a( $item, 'WC_Order_Item_Product' ) ) {
			return $display_value;
		}

		if ( ! is_string( $display_value ) || ! filter_var( $display_value, FILTER_VALIDATE_URL ) ) {
			return $display_value;
		}

		return $this->build_design_actions_html( $display_value, is_admin(), $item );
	}

	/**
	 * Formats order item meta labels and injects the design link metadata.
	 *
	 * @param array $formatted_meta Formatted meta array.
	 * @param mixed $item           Order item.
	 * @return array
	 */
	public function dweb_ps_handle_order_item_formatted_meta_data( $formatted_meta, $item ) {
		if ( ! $item || ! is_a( $item, 'WC_Order_Item_Product' ) ) {
			return $formatted_meta;
		}

		$team_reference = $this->sanitize_team_reference( $item->get_meta( self::TEAM_SESSION_REFERENCE, true ) );
		if ( null === $team_reference ) {
			return $formatted_meta;
		}

		$team_reference_label = apply_filters(
			'dweb_ps_team_reference_label',
			__( 'Reference', '3dweb-print-studio' ),
			$item
		);

		foreach ( $formatted_meta as $meta_key => $meta ) {
			if ( isset( $meta->key ) && self::TEAM_SESSION_REFERENCE === $meta->key ) {
				$formatted_meta[ $meta_key ]->display_key = $team_reference_label;
			}
		}

		foreach ( $formatted_meta as $meta ) {
			if ( isset( $meta->key ) && 'design' === $meta->key ) {
				return $formatted_meta;
			}
		}

		$design_url = $item->get_meta( 'design', true );
		if ( ! $design_url ) {
			$design_url = $this->get_session_design_url( $team_reference );
		}

		if ( ! $design_url ) {
			return $formatted_meta;
		}

		$display_value = $this->build_design_actions_html( $design_url, is_admin(), $item );

		$formatted_meta['cnf_design'] = (object) array(
			'key'           => 'design',
			'value'         => $design_url,
			'display_key'   => 'design',
			'display_value' => $display_value,
		);

		return $formatted_meta;
	}

	/**
	 * Streams a design download for administrators.
	 *
	 * @return void
	 */
	public function dweb_ps_handle_admin_design_download() {
		// phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce registers this capability.
		if ( ! current_user_can( 'edit_shop_orders' ) ) {
			wp_die( 'Forbidden', 403 );
		}

		$raw_nonce = filter_input( INPUT_GET, '_wpnonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$nonce     = is_string( $raw_nonce ) ? sanitize_text_field( wp_unslash( $raw_nonce ) ) : '';
		if ( ! wp_verify_nonce( $nonce, '3dweb_ps_download_design' ) ) {
			wp_die( 'Invalid nonce', 403 );
		}

		$raw_design_url = filter_input( INPUT_GET, 'url', FILTER_SANITIZE_URL );
		$design_url     = is_string( $raw_design_url ) ? esc_url_raw( wp_unslash( $raw_design_url ) ) : '';

		if (
			! $design_url ||
			! wp_http_validate_url( $design_url ) ||
			! $this->is_allowed_design_download_url( $design_url )
		) {
			wp_die( 'Invalid design URL', 400 );
		}

		$this->stream_design_download( $design_url );
	}

	/**
	 * Streams a design download for public order links.
	 *
	 * @return void
	 */
	public function dweb_ps_handle_public_design_download() {
		$raw_order_id   = filter_input( INPUT_GET, 'order_id', FILTER_SANITIZE_NUMBER_INT );
		$order_id       = is_string( $raw_order_id ) ? absint( wp_unslash( $raw_order_id ) ) : 0;
		$raw_order_key  = filter_input( INPUT_GET, 'order_key', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$order_key      = is_string( $raw_order_key ) ? sanitize_text_field( wp_unslash( $raw_order_key ) ) : '';
		$raw_nonce      = filter_input( INPUT_GET, '_wpnonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$nonce          = is_string( $raw_nonce ) ? sanitize_text_field( wp_unslash( $raw_nonce ) ) : '';
		$raw_design_url = filter_input( INPUT_GET, 'url', FILTER_SANITIZE_URL );
		$design_url     = is_string( $raw_design_url ) ? esc_url_raw( wp_unslash( $raw_design_url ) ) : '';

		if ( ! $order_id || ! $order_key || ! $nonce || ! wp_verify_nonce( $nonce, '3dweb_ps_public_download_design_' . $order_id ) ) {
			wp_die( 'Invalid request', 403 );
		}

		$order = wc_get_order( $order_id );
		if ( ! $order || ! hash_equals( (string) $order->get_order_key(), (string) $order_key ) ) {
			wp_die( 'Invalid order', 403 );
		}

		if (
			! $design_url ||
			! wp_http_validate_url( $design_url ) ||
			! $this->is_allowed_design_download_url( $design_url )
		) {
			wp_die( 'Invalid design URL', 400 );
		}

		$this->stream_design_download( $design_url );
	}

	/**
	 * Validates whether a design download URL is allowed.
	 *
	 * @param string $url Design URL.
	 * @return bool
	 */
	private function is_allowed_design_download_url( $url ) {
		$host = wp_parse_url( $url, PHP_URL_HOST );
		if ( ! $host ) {
			return false;
		}
		$host = strtolower( $host );

		$allowed_hosts = array();

		$configurator_host        = ( new DWeb_PS_API() )->dweb_ps_get_configurator_host();
		$configurator_parsed_host = wp_parse_url( $configurator_host, PHP_URL_HOST );
		if ( $configurator_parsed_host ) {
			$allowed_hosts[] = strtolower( $configurator_parsed_host );
		}

		$allowed_suffixes = array(
			'.3dweb.io',
		);

		if ( in_array( $host, $allowed_hosts, true ) ) {
			return true;
		}

		foreach ( $allowed_suffixes as $suffix ) {
			if ( 0 === substr_compare( $host, $suffix, -strlen( $suffix ) ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Validates whether a callback URL points to the current site.
	 *
	 * @param string $url Callback URL.
	 * @return bool
	 */
	private function is_allowed_callback_url( $url ) {
		if ( ! wp_http_validate_url( $url ) ) {
			return false;
		}

		$callback_host = wp_parse_url( $url, PHP_URL_HOST );
		$site_host     = wp_parse_url( home_url( '/' ), PHP_URL_HOST );

		if ( ! $callback_host || ! $site_host ) {
			return false;
		}

		return strtolower( $callback_host ) === strtolower( $site_host );
	}

	/**
	 * Gets the generated image URL for a cart item.
	 *
	 * @param array $cart_item Cart item values.
	 * @return string|null
	 */
	private function get_cart_item_custom_image_url( $cart_item ) {
		if ( ! is_array( $cart_item ) || empty( $cart_item[ self::TEAM_SESSION_REFERENCE ] ) ) {
			return null;
		}

		$team_reference = $this->sanitize_team_reference( $cart_item[ self::TEAM_SESSION_REFERENCE ] );
		if ( null === $team_reference ) {
			return null;
		}

		return $this->get_session_main_image_url( $team_reference );
	}

	/**
	 * Gets cached session assets for a team reference.
	 *
	 * @param string $team_reference Team reference.
	 * @return array|null
	 */
	private function get_session_assets_by_reference( $team_reference ) {
		$team_reference = $this->sanitize_team_reference( $team_reference );
		if ( null === $team_reference ) {
			return null;
		}

		if ( isset( $this->session_assets_cache[ $team_reference ] ) ) {
			return $this->session_assets_cache[ $team_reference ];
		}

		$response = ( new DWeb_PS_API() )->dweb_ps_get_session_assets( $team_reference );
		if ( ! $response || isset( $response['error'] ) || isset( $response['errors'] ) || ! isset( $response['data'] ) || ! is_array( $response['data'] ) ) {
			$this->session_assets_cache[ $team_reference ] = null;
			return null;
		}

		$this->session_assets_cache[ $team_reference ] = $response['data'];
		return $this->session_assets_cache[ $team_reference ];
	}

	/**
	 * Sanitizes a team reference value.
	 *
	 * @param mixed $value Raw value.
	 * @return string|null
	 */
	private function sanitize_team_reference( $value ) {
		if ( ! is_scalar( $value ) ) {
			return null;
		}

		$team_reference = sanitize_text_field( (string) $value );
		// Support both observed 3DWeb session reference lengths.
		if ( '' === $team_reference || ! preg_match( '/^[a-f0-9]{13,14}$/', $team_reference ) ) {
			return null;
		}

		return $team_reference;
	}

	/**
	 * Gets the main generated image URL for a session.
	 *
	 * @param string $team_reference Team reference.
	 * @return string|null
	 */
	private function get_session_main_image_url( $team_reference ) {
		$assets = $this->get_session_assets_by_reference( $team_reference );
		if ( ! $assets || ! isset( $assets['main_0'] ) ) {
			return null;
		}

		$main_image = $assets['main_0'];

		if ( is_array( $main_image ) && ! empty( $main_image['url'] ) ) {
			return esc_url_raw( $main_image['url'] );
		}

		if ( is_string( $main_image ) && filter_var( $main_image, FILTER_VALIDATE_URL ) ) {
			return esc_url_raw( $main_image );
		}

		return null;
	}

	/**
	 * Gets the generated design URL for a session.
	 *
	 * @param string $team_reference Team reference.
	 * @return string|null
	 */
	private function get_session_design_url( $team_reference ) {
		$assets = $this->get_session_assets_by_reference( $team_reference );
		if ( ! $assets || ! isset( $assets['design'] ) || ! is_array( $assets['design'] ) || empty( $assets['design'] ) ) {
			return null;
		}

		$first_design = reset( $assets['design'] );

		if ( is_string( $first_design ) && filter_var( $first_design, FILTER_VALIDATE_URL ) ) {
			return esc_url_raw( $first_design );
		}

		if ( is_array( $first_design ) && ! empty( $first_design['url'] ) && filter_var( $first_design['url'], FILTER_VALIDATE_URL ) ) {
			return esc_url_raw( $first_design['url'] );
		}

		return null;
	}

	/**
	 * Builds the HTML for design open/download actions.
	 *
	 * @param string     $url      Design URL.
	 * @param bool       $is_admin Whether the current context is admin.
	 * @param mixed|null $item     Optional order item.
	 * @return string
	 */
	private function build_design_actions_html( $url, $is_admin = false, $item = null ) {
		$safe_url = esc_url( $url );
		if ( $is_admin ) {
			$download_url = add_query_arg(
				array(
					'action'   => '3dweb_ps_download_design',
					'url'      => $url,
					'_wpnonce' => wp_create_nonce( '3dweb_ps_download_design' ),
				),
				admin_url( 'admin-ajax.php' )
			);

			return sprintf(
				'<a href="%1$s" target="_blank" rel="noopener noreferrer">Open design <span class="dashicons dashicons-external" style="font-size:11px;width:11px;height:11px;line-height:11px;vertical-align:middle;margin-left:2px;"></span></a> / <a href="%2$s">Download</a>',
				$safe_url,
				esc_url( $download_url )
			);
		}

		$download_url = $safe_url;
		if ( $item && is_a( $item, 'WC_Order_Item_Product' ) ) {
			$order_id = (int) $item->get_order_id();
			$order    = $order_id ? wc_get_order( $order_id ) : null;
			if ( $order ) {
				$download_url = add_query_arg(
					array(
						'action'    => '3dweb_ps_public_download_design',
						'url'       => $url,
						'order_id'  => $order_id,
						'order_key' => $order->get_order_key(),
						'_wpnonce'  => wp_create_nonce( '3dweb_ps_public_download_design_' . $order_id ),
					),
					admin_url( 'admin-ajax.php' )
				);
			}
		}

		return sprintf(
			'<a href="%1$s" target="_blank" rel="noopener noreferrer">Open design</a> / <a href="%2$s" target="_blank" rel="noopener noreferrer">Download</a>',
			$safe_url,
			esc_url( $download_url )
		);
	}

	/**
	 * Streams the design file to the browser.
	 *
	 * @param string $design_url Design URL.
	 * @return void
	 */
	private function stream_design_download( $design_url ) {
		$response = wp_safe_remote_get(
			$design_url,
			array(
				'timeout'     => 30,
				'redirection' => 5,
			)
		);

		if ( is_wp_error( $response ) ) {
			wp_die( 'Download failed', 502 );
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( $status_code < 200 || $status_code >= 300 ) {
			wp_die( 'Design unavailable', 502 );
		}

		$body = wp_remote_retrieve_body( $response );
		if ( '' === $body ) {
			wp_die( 'Empty design file', 502 );
		}

		$path     = wp_parse_url( $design_url, PHP_URL_PATH );
		$filename = $path ? wp_basename( $path ) : 'design.png';
		if ( ! $filename || false === strpos( $filename, '.' ) ) {
			$filename = 'design.png';
		}
		$filename = sanitize_file_name( $filename );

		$content_type = wp_remote_retrieve_header( $response, 'content-type' );
		if ( ! $content_type ) {
			$content_type = 'application/octet-stream';
		}

		nocache_headers();
		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: ' . $content_type );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . strlen( $body ) );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Binary file stream output.
		echo $body;
		exit;
	}

	/**
	 * Loads session image URLs for the current team reference.
	 *
	 * @return void
	 */
	private function load_session_urls() {
		$team_reference = $this->get_team_reference();
		if ( $team_reference && ! $this->session_images_loaded ) {
			$response = ( new DWeb_PS_API() )->dweb_ps_get_session_assets( $team_reference );
			if (
				! $response ||
				isset( $response['error'] ) ||
				isset( $response['errors'] ) ||
				! isset( $response['data'] ) ||
				! is_array( $response['data'] )
			) {
				return;
			}

			$this->session_images_loaded = true;
			$this->main_images           = array();

			foreach ( array( 'main_0', 'main_90', 'main_180', 'main_270' ) as $key ) {
				if ( isset( $response['data'][ $key ] ) ) {
					$this->main_images[] = $response['data'][ $key ];
				}
			}
		}
	}

	/**
	 * Replaces main gallery image params with generated session images.
	 *
	 * @param array $params        Image params.
	 * @param int   $attachment_id Attachment ID.
	 * @return array
	 */
	public function dweb_ps_handle_image_params( $params, $attachment_id ) {
		$product = wc_get_product();
		if ( ! $product ) {
			return $params;
		}

		if ( ! is_array( $params ) ) {
			return $params;
		}

		$this->load_session_urls();
		if ( empty( $this->main_images ) ) {
			return $params;
		}

		$gallery               = $product->get_gallery_image_ids();
		$attachment_product_id = (int) get_post_meta( $attachment_id, '_product_id', true );
		$current_product_id    = (int) get_the_ID();

			// Ensure the product ID matches the current attachment product.
		if ( $product->get_id() !== $attachment_product_id || $product->get_id() !== $current_product_id ) {
			return $params;
		}

			// Add the main product ID to the gallery.
			array_unshift( $gallery, $product->get_image_id() );

			// Find the matching attachment in the gallery.
			$index = array_search( $attachment_id, $gallery, true );

		if ( false === $index ) {
			return $params;
		}

		$old_url = $params['data-src'];

			// Use a temporary placeholder image.

		$polled_url = null;

		if ( false !== $index && isset( $this->main_images[ $index ] ) ) {
			$polled_url = $this->main_images[ $index ];
		}

		$url = plugin_dir_url( __DIR__ ) . 'img/wait-placeholder.svg';

			// Replace the main source with the placeholder image.
			$params['src'] = $this->add_height_to_image_url( $url, 302 );

			// Replace related responsive attributes.
			$params['srcset'] = $this->add_height_to_image_url( $url, 600, 600 );

			// Change the thumbnail source.
			$params['data-src']     = $this->add_height_to_image_url( $url, 210 );
		$params['data-large_image'] = $url;

		$params['data-cnf3dweb_url']     = $polled_url;
		$params['data-cnf3dweb_url_old'] = $old_url;

		return $params;
	}

	/**
	 * Replaces thumbnail image params with generated session images.
	 *
	 * @param array $image         Image data.
	 * @param int   $attachment_id Attachment ID.
	 * @param mixed $size          Requested size.
	 * @param bool  $icon          Whether the image is an icon.
	 * @return array
	 */
	public function dweb_ps_handle_image_params_thumb( $image, $attachment_id, $size, $icon ) {
		unset( $size, $icon );

		if ( ! $this->get_team_reference() ) {
			return $image;
		}

		$product = wc_get_product();
		if ( ! $product ) {
			return $image;
		}

		$attachment_product_id = (int) get_post_meta( $attachment_id, '_product_id', true );

			// Ensure the product ID matches the current attachment product.
		if ( $product->get_id() !== $attachment_product_id ) {
			return $image;
		}

			$gallery = $product->get_gallery_image_ids();

			// Add the main product ID to the gallery.
			array_unshift( $gallery, $product->get_image_id() );

			// Find the matching attachment in the gallery.
			$index = array_search( $attachment_id, $gallery, true );

		if ( false === $index ) {
			return $image;
		}

		$image[0] = plugin_dir_url( __DIR__ ) . 'img/wait-placeholder.svg';

		return $image;
	}

	/**
	 * Adds width and height query args to an image URL.
	 *
	 * @param string   $url    Image URL.
	 * @param int      $height Image height.
	 * @param int|null $width  Image width.
	 * @return string
	 */
	private function add_height_to_image_url( $url, $height, $width = null ) {
		if ( $width ) {
			return add_query_arg(
				array(
					'h'    => $height,
					'w'    => $width,
					'mode' => 'fill',
				),
				$url
			);
		}

		return add_query_arg(
			array(
				'h' => $height,
			),
			$url
		);
	}
}
