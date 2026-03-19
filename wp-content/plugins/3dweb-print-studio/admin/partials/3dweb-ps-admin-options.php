<?php
/**
 * Options admin page partial.
 *
 * @package DWeb_PS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require 'header.php';
require '3dweb-ps-settings-helper.php';

?>
	<div class="dweb_ps__settings">
		<h2><?php esc_html_e( 'Options', '3dweb-print-studio' ); ?></h2>
		<p><?php esc_html_e( 'Configure how the 3D configurator behaves on product and cart pages.', '3dweb-print-studio' ); ?></p>

		<form method='post' data-source="<?php echo esc_attr( DWeb_PS_ADMIN_OPTIONS::PATH ); ?>">

			<div class="dweb_ps__settings__table">
				<?php
				echo wp_kses(
					dweb_ps_setting_create_row(
						__( 'Debug mode', '3dweb-print-studio' ),
						__( 'Show debug information in the console.', '3dweb-print-studio' ),
						DWeb_PS_ADMIN_OPTIONS::CONFIGURATOR_DEBUG,
						get_option( DWeb_PS_ADMIN_OPTIONS::CONFIGURATOR_DEBUG, false ),
						'checkbox'
					),
					dweb_ps_setting_allowed_html()
				);

				echo wp_kses(
					dweb_ps_setting_create_row(
						__( 'Start button text', '3dweb-print-studio' ),
						__( 'Text on the button when the product is configurable.', '3dweb-print-studio' ),
						DWeb_PS_ADMIN_OPTIONS::CONFIGURATOR_START_BUTTON_TEXT,
						get_option( DWeb_PS_ADMIN_OPTIONS::CONFIGURATOR_START_BUTTON_TEXT, __( 'Start configuration', '3dweb-print-studio' ) ),
						'text'
					),
					dweb_ps_setting_allowed_html()
				);

				echo wp_kses(
					dweb_ps_setting_create_row(
						__( 'Session closed text', '3dweb-print-studio' ),
						__( 'Text shown below the button after the session is closed.', '3dweb-print-studio' ),
						DWeb_PS_ADMIN_OPTIONS::CONFIGURATOR_SESSION_CLOSED_TEXT,
						get_option( DWeb_PS_ADMIN_OPTIONS::CONFIGURATOR_SESSION_CLOSED_TEXT, 'Design: {reference}' ),
						'text'
					),
					dweb_ps_setting_allowed_html()
				);

				echo wp_kses(
					dweb_ps_setting_create_row(
						__( 'Gallery loading text', '3dweb-print-studio' ),
						__( 'Text shown while the generated preview image is loading.', '3dweb-print-studio' ),
						DWeb_PS_ADMIN_OPTIONS::CONFIGURATOR_GALLERY_LOADING_TEXT,
						get_option( DWeb_PS_ADMIN_OPTIONS::CONFIGURATOR_GALLERY_LOADING_TEXT, __( 'Preparing your preview...', '3dweb-print-studio' ) ),
						'text'
					),
					dweb_ps_setting_allowed_html()
				);

				echo wp_kses(
					dweb_ps_setting_create_row(
						__( 'Session loading text', '3dweb-print-studio' ),
						__( 'Text shown in the popup while opening the configurator.', '3dweb-print-studio' ),
						DWeb_PS_ADMIN_OPTIONS::CONFIGURATOR_SESSION_LOADING_TEXT,
						get_option( DWeb_PS_ADMIN_OPTIONS::CONFIGURATOR_SESSION_LOADING_TEXT, __( 'Opening configurator...', '3dweb-print-studio' ) ),
						'text'
					),
					dweb_ps_setting_allowed_html()
				);

				echo wp_kses(
					dweb_ps_setting_create_row(
						__( 'Show design link', '3dweb-print-studio' ),
						__( 'Show a clickable design reference under the add to cart button after returning from configurator.', '3dweb-print-studio' ),
						DWeb_PS_ADMIN_OPTIONS::CONFIGURATOR_SHOW_SESSION_DESIGN_LINK,
						get_option( DWeb_PS_ADMIN_OPTIONS::CONFIGURATOR_SHOW_SESSION_DESIGN_LINK, true ),
						'checkbox'
					),
					dweb_ps_setting_allowed_html()
				);
				?>
			</div>
		</form>
		<?php
		require 'button.php';
		?>
	</div>
<?php
require 'footer.php';
