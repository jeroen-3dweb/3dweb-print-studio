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
		<div class="dweb_ps__about-hero">
			<div class="dweb_ps__about-hero-content">
				<h2><?php esc_html_e( 'Sell custom print products with instant 3D preview', '3dweb-print-studio' ); ?></h2>
				<p>
					<?php esc_html_e( '3DWeb Print Studio lets shoppers personalize products in your WooCommerce store, approve the result visually, and send cleaner designs into production.', '3dweb-print-studio' ); ?>
				</p>
				<ul class="dweb_ps__about-benefits">
					<li><?php esc_html_e( 'White-label customer experience', '3dweb-print-studio' ); ?></li>
					<li><?php esc_html_e( 'No subscription, pay per completed design', '3dweb-print-studio' ); ?></li>
					<li><?php esc_html_e( 'Designed for WooCommerce workflows', '3dweb-print-studio' ); ?></li>
				</ul>
			</div>
			<div class="dweb_ps__about-actions">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=3dweb-ps-api-settings' ) ); ?>" class="dweb_ps__button dweb_ps__button--normal dweb_ps__button--secondary"><?php esc_html_e( 'Connect API', '3dweb-print-studio' ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=3dweb-ps-options' ) ); ?>" class="dweb_ps__button dweb_ps__button--normal dweb_ps__button--outline"><?php esc_html_e( 'Configure options', '3dweb-print-studio' ); ?></a>
				<a href="https://3dweb.io?utm_source=wordpress_plugin&amp;utm_medium=admin&amp;utm_campaign=main_settings&amp;utm_content=learn_more" target="_blank" rel="noopener noreferrer" class="dweb_ps__button dweb_ps__button--normal dweb_ps__button--outline"><?php esc_html_e( 'Learn more', '3dweb-print-studio' ); ?></a>
			</div>
		</div>

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

		<h3><?php esc_html_e( 'Plugin details', '3dweb-print-studio' ); ?></h3>
		<ul>
			<li><?php esc_html_e( 'Version:', '3dweb-print-studio' ); ?> <?php echo esc_html( DWEB_PS_VERSION ); ?></li>
			<li><?php esc_html_e( 'Pricing:', '3dweb-print-studio' ); ?> <?php esc_html_e( 'No subscription, only pay per completed design.', '3dweb-print-studio' ); ?></li>
		</ul>

		<hr>

		<h3><?php esc_html_e( 'Current Theme', '3dweb-print-studio' ); ?></h3>
		<ul>
			<li><?php echo esc_html( wp_get_theme()->get( 'Name' ) ); ?></li>
		</ul>
		<p class="dweb_ps__theme-support">
			<?php esc_html_e( 'Is your theme not working as expected?', '3dweb-print-studio' ); ?>
			<a href="https://3dweb.io/contact?utm_source=wordpress_plugin&amp;utm_medium=admin&amp;utm_campaign=main_settings&amp;utm_content=theme_support" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Let us know and we will build support for it.', '3dweb-print-studio' ); ?></a>
		</p>

	</div>

<?php
require 'footer.php';
