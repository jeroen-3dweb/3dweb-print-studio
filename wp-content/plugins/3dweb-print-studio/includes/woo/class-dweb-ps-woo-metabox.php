<?php
/**
 * WooCommerce product metabox integration.
 *
 * @package DWeb_PS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Product metabox for configurator product mapping.
 */
class DWeb_PS_WOO_METABOX {
	const FIELD_PRODUCT_SKU        = 'dweb_ps_woo_product_sku';
	const LEGACY_FIELD_PRODUCT_SKU = 'DWeb_PS_woo_product_sku_';
	const FIELD_NONCE              = 'dweb_ps_woo_nonce';
	const FIELD_NONCE_ACTION       = 'dweb_ps_save_product_metabox';

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
	 * Initializes the metabox integration.
	 *
	 * @param string $plugin_name Plugin handle.
	 * @param string $version     Plugin version.
	 */
	public function __construct( string $plugin_name, string $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Registers the product metabox.
	 *
	 * @param string $post_type Current post type.
	 * @return void
	 */
	public function dweb_ps_add_boxes( $post_type ) {
		if ( in_array( $post_type, array( 'product' ), true ) ) {
			add_meta_box(
				'3dweb-ps-woo-product-sku',
				__( 'Product ID in Configurator', '3dweb-print-studio' ),
				array(
					self::class,
					'dweb_ps_product_360_view_callback',
				),
				$post_type,
				'normal',
				'core'
			);
		}
	}

	/**
	 * Saves the metabox values.
	 *
	 * @param int $post_id Current post ID.
	 * @return void
	 */
	public function dweb_ps_save_boxes( $post_id ) {
		if ( ! $this->dweb_ps_is_valid_save_request( $post_id ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification happens in dweb_ps_is_valid_save_request().
		if ( isset( $_POST[ self::FIELD_PRODUCT_SKU ] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification happens in dweb_ps_is_valid_save_request().
			update_post_meta( $post_id, self::FIELD_PRODUCT_SKU, sanitize_text_field( wp_unslash( $_POST[ self::FIELD_PRODUCT_SKU ] ) ) );
			delete_post_meta( $post_id, self::LEGACY_FIELD_PRODUCT_SKU );
		} else {
			delete_post_meta( $post_id, self::FIELD_PRODUCT_SKU );
			delete_post_meta( $post_id, self::LEGACY_FIELD_PRODUCT_SKU );
		}
	}

	/**
	 * Searches configurator products via AJAX.
	 *
	 * @return void
	 */
	public function dweb_ps_search_products() {
		check_ajax_referer( DWeb_PS_Admin::AJAX_NONCE_ACTION, '_ajax_nonce' );
		// phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce registers product editing capabilities.
		if ( ! current_user_can( 'edit_products' ) ) {
			wp_send_json_error( array( 'error' => 'Permission denied' ), 403 );
		}
		$raw_search = filter_input( INPUT_GET, 'search', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$search     = is_string( $raw_search ) ? sanitize_text_field( wp_unslash( $raw_search ) ) : '';
		$api        = new DWeb_PS_API();
		$result     = $api->dweb_ps_search_products( $search );

		if ( isset( $result['error'] ) ) {
			wp_send_json_error( $result );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Enqueues metabox scripts on product edit screens.
	 *
	 * @param string $hook Current admin hook suffix.
	 * @return void
	 */
	public function dweb_ps_enqueue_scripts( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'product' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_script( '3dweb-ps-admin' );
		wp_register_script(
			'3dweb-ps-woo-metabox',
			plugin_dir_url( dirname( __DIR__ ) ) . 'admin/js/woo-metabox.js',
			array( 'jquery', '3dweb-ps-admin' ),
			$this->version,
			true
		);

		wp_localize_script(
			'3dweb-ps-woo-metabox',
			'dwebPsWooMetabox',
			array(
				'noProductsFound' => __( 'No products found', '3dweb-print-studio' ),
				'searchError'     => __( 'Error searching products', '3dweb-print-studio' ),
			)
		);

		wp_enqueue_script( '3dweb-ps-woo-metabox' );
	}

	/**
	 * Validates the product save request.
	 *
	 * @param int $post_id Current post ID.
	 * @return bool
	 */
	private function dweb_ps_is_valid_save_request( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false;
		}

		if ( wp_is_post_revision( $post_id ) ) {
			return false;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return false;
		}

		if ( ! isset( $_POST[ self::FIELD_NONCE ] ) ) {
			return false;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST[ self::FIELD_NONCE ] ) );
		if ( '' === $nonce ) {
			return false;
		}

		return (bool) wp_verify_nonce( $nonce, self::FIELD_NONCE_ACTION );
	}

	/**
	 * Renders the product mapping metabox.
	 *
	 * @param WP_Post $post Current product post object.
	 * @return void
	 */
	public static function dweb_ps_product_360_view_callback( $post ) {
		wp_nonce_field( self::FIELD_NONCE_ACTION, self::FIELD_NONCE );
		$product_id = self::dweb_ps_get_product_sku( $post->ID );

		$token = get_option( DWeb_PS_ADMIN_API::TOKEN, '' );
		$host  = get_option( DWeb_PS_ADMIN_API::CONFIGURATOR_HOST, DWeb_PS_API::DEFAULT_CONFIGURATOR_HOST );
		if ( empty( $host ) ) {
			$host = DWeb_PS_API::DEFAULT_CONFIGURATOR_HOST;
		}
		$has_credentials = ! empty( $token ) && ! empty( $host );
		?>
		<table class="form-table">
			<tr>
				<td>
					<div>
						<?php if ( ! $has_credentials ) : ?>
							<p style="color: #d63638;">
								<?php esc_html_e( 'Please configure your API credentials first.', '3dweb-print-studio' ); ?>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=3dweb-ps-api-settings' ) ); ?>"><?php esc_html_e( 'Go to API Settings', '3dweb-print-studio' ); ?></a>
							</p>
						<?php else : ?>
							<p><?php esc_html_e( 'Search and select the product for the model you want to configure.', '3dweb-print-studio' ); ?></p>
						<?php endif; ?>

						<div style="position: relative; width: 80%;">
							<div id="dweb_ps_select_wrapper" style="position:relative; border:1px solid #8c8f94; border-radius:4px; background:#fff; cursor:pointer; <?php echo ! $has_credentials ? 'opacity:0.6; pointer-events:none;' : ''; ?>">
								<div id="dweb_ps_select_display" style="padding:6px 30px 6px 8px; min-height:20px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
									<?php if ( $product_id ) : ?>
										<?php echo esc_html( $product_id ); ?>
									<?php else : ?>
										<span style="color:#999;"><?php esc_html_e( 'Select a product...', '3dweb-print-studio' ); ?></span>
									<?php endif; ?>
								</div>
								<span style="position:absolute; right:8px; top:50%; transform:translateY(-50%); pointer-events:none;">&#9662;</span>
								<input
									type="text"
									id="dweb_ps_product_search"
									placeholder="<?php esc_attr_e( 'Search...', '3dweb-print-studio' ); ?>"
									style="display:none; width:100%; border:none; border-top:1px solid #ccc; padding:6px 8px; outline:none; box-sizing:border-box;"
									autocomplete="off"
								/>
								<div id="dweb_ps_product_dropdown" style="display:none; border-top:1px solid #ccc; max-height:200px; overflow-y:auto;"></div>
							</div>
							<input
								type="hidden"
								name="<?php echo esc_attr( self::FIELD_PRODUCT_SKU ); ?>"
								id="dweb_ps_product_id"
								value="<?php echo esc_attr( $product_id ); ?>"
							/>
						</div>
					</div>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Gets the configurator product SKU for a product.
	 *
	 * @param int $post_id Product post ID.
	 * @return string
	 */
	public static function dweb_ps_get_product_sku( $post_id ) {
		$product_sku = get_post_meta( $post_id, self::FIELD_PRODUCT_SKU, true );
		if ( '' === $product_sku ) {
			$product_sku = get_post_meta( $post_id, self::LEGACY_FIELD_PRODUCT_SKU, true );
		}

		return is_string( $product_sku ) ? $product_sku : '';
	}
}
