<?php
/**
 * API settings admin page partial.
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
		<h2><?php esc_html_e( 'API Credentials', '3dweb-print-studio' ); ?></h2>
		<p class="dweb_ps__settings__intro">
			<?php esc_html_e( 'Enter your API credentials to connect the plugin with your 3DWeb environment.', '3dweb-print-studio' ); ?>
			<?php esc_html_e( 'Need credentials? Get them from', '3dweb-print-studio' ); ?>
			<span class="external-link"><a href="https://3dweb.io" target="_blank" rel="noopener noreferrer">3dweb.io</a></span>.
		</p>

		<form method='post' data-source="<?php echo esc_attr( DWeb_PS_ADMIN_API::PATH ); ?>">

			<div class="dweb_ps__settings__table">
				<?php
				echo wp_kses(
					dweb_ps_setting_create_row(
						__( 'Token', '3dweb-print-studio' ),
						__( 'Paste your API token.', '3dweb-print-studio' ),
						DWeb_PS_ADMIN_API::TOKEN,
						get_option( DWeb_PS_ADMIN_API::TOKEN, '' ),
						'text'
					),
					dweb_ps_setting_allowed_html()
				);
				?>
				<?php
				echo wp_kses(
					dweb_ps_setting_create_row(
						__( 'Configurator Host', '3dweb-print-studio' ),
						__( 'Base URL of your configurator API host.', '3dweb-print-studio' ),
						DWeb_PS_ADMIN_API::CONFIGURATOR_HOST,
						get_option( DWeb_PS_ADMIN_API::CONFIGURATOR_HOST, DWeb_PS_API::DEFAULT_CONFIGURATOR_HOST ),
						'text'
					),
					dweb_ps_setting_allowed_html()
				);
				?>
				<?php
				echo wp_kses(
					dweb_ps_setting_create_select(
						__( 'API Version', '3dweb-print-studio' ),
						__( 'Version used for API requests.', '3dweb-print-studio' ),
						DWeb_PS_ADMIN_API::CONFIGURATOR_HOST_VERSION,
						get_option( DWeb_PS_ADMIN_API::CONFIGURATOR_HOST_VERSION, DWeb_PS_API::DEFAULT_API_VERSION ),
						array(
							array(
								'label' => 'v1',
								'value' => 'v1',
							),
						)
					),
					dweb_ps_setting_allowed_html()
				);
				?>
			</div>

				<div class="dweb_ps__settings__row dweb_ps__settings__row--actions">
					<div class="dweb_ps__settings__meta">
						<div class="dweb_ps__settings__label"><?php esc_html_e( 'Connection test', '3dweb-print-studio' ); ?></div>
						<small class="dweb_ps__settings-holder__description"><?php esc_html_e( 'Verify if the current credentials can authenticate.', '3dweb-print-studio' ); ?></small>
					</div>
					<div class="dweb_ps__settings-holder">
						<div class="dweb_ps__settings__actions">
							<a id="dweb_ps-test-auth" class="dweb_ps__button dweb_ps__button--normal"><?php esc_html_e( 'Test credentials', '3dweb-print-studio' ); ?></a>
							<small id="dweb_ps__check-auth-result" class="dweb_ps__auth-result"></small>
						</div>
					</div>
				</div>
		</form>

		<?php
		require 'button.php';
		?>

	</div>
<?php
require 'footer.php';
