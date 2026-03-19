<?php
/**
 * About admin page partial.
 *
 * @package DWeb_PS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require 'header.php';

?>
	<div class="dweb_ps__settings">
		<div class="dweb_ps__settings__holder">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=3dweb-ps-api-settings' ) ); ?>" class="dweb_ps__button dweb_ps__button--normal" style="background-color: #a5c100; color: white; font-weight: 600;"><?php esc_html_e( 'API Settings', '3dweb-print-studio' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=3dweb-ps-options' ) ); ?>" class="dweb_ps__button dweb_ps__button--normal" style="background-color: #a5c100; color: white; font-weight: 600;"><?php esc_html_e( 'Options', '3dweb-print-studio' ); ?></a>
		</div>

		<h2><?php esc_html_e( '3DWeb Print Studio', '3dweb-print-studio' ); ?></h2>
		<p>
			<?php esc_html_e( '3DWeb Print Studio turns print customization into a real-time 3D experience - fully brandable, easy to integrate, and built for modern B2B and B2C workflows.', '3dweb-print-studio' ); ?><br>
			<a href="https://3dweb.io" target="_blank"><?php esc_html_e( 'Learn more', '3dweb-print-studio' ); ?></a>
		</p>

		<hr>

		<h3><?php esc_html_e( 'Basic', '3dweb-print-studio' ); ?></h3>
		<ul>
			<li><?php esc_html_e( 'Version:', '3dweb-print-studio' ); ?> <?php echo esc_html( DWEB_PS_VERSION ); ?></li>
		</ul>

		<hr>

		<h3><?php esc_html_e( 'E-Commerce', '3dweb-print-studio' ); ?></h3>
		<ul>
			<?php
			$dweb_ps_wc_status = DWeb_PS_WOO::dweb_ps_woocommerce_is_active()
				? __( 'active', '3dweb-print-studio' )
				: __( "not active, it won't work without it", '3dweb-print-studio' );
			?>
			<li>
				<?php echo esc_html__( 'WooCommerce', '3dweb-print-studio' ); ?>
				(<?php echo esc_html( $dweb_ps_wc_status ); ?>)
			</li>
		</ul>

		<hr>

		<h3><?php esc_html_e( 'Current Theme', '3dweb-print-studio' ); ?></h3>
		<ul>
			<li><?php echo esc_html( wp_get_theme()->get( 'Name' ) ); ?></li>
		</ul>

	</div>

<?php
require 'footer.php';
