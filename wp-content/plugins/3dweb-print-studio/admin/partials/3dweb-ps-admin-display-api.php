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
			<?php esc_html_e( 'Connect your webshop to 3DWeb so customers can design and preview products before production.', '3dweb-print-studio' ); ?>
		</p>

		<div class="dweb_ps__value-note">
			<div class="dweb_ps__value-note-title"><?php esc_html_e( 'Need an API token?', '3dweb-print-studio' ); ?></div>
			<p class="dweb_ps__value-note-text"><?php esc_html_e( 'Create a free 3DWeb account to get your dashboard token. No subscription; only pay when a design is completed.', '3dweb-print-studio' ); ?></p>
			<a href="https://3dweb.io?utm_source=wordpress_plugin&amp;utm_medium=admin&amp;utm_campaign=api_credentials&amp;utm_content=create_free_account" target="_blank" rel="noopener noreferrer" class="dweb_ps__button dweb_ps__button--normal dweb_ps__button--secondary"><?php esc_html_e( 'Create free account', '3dweb-print-studio' ); ?></a>
		</div>

		<form method='post' data-source="<?php echo esc_attr( DWeb_PS_ADMIN_API::PATH ); ?>">

			<div class="dweb_ps__settings__table">
				<?php
				echo wp_kses(
					dweb_ps_setting_create_row(
						__( 'Token', '3dweb-print-studio' ),
						__( 'Paste your API token (get it from your 3dweb.io dashboard).', '3dweb-print-studio' ),
						DWeb_PS_ADMIN_API::TOKEN,
						get_option( DWeb_PS_ADMIN_API::TOKEN, '' ),
						'textarea'
					),
					dweb_ps_setting_allowed_html()
				);
				?>
			</div>

			<input type="hidden" name="<?php echo esc_attr( DWeb_PS_ADMIN_API::CONFIGURATOR_HOST ); ?>" value="<?php echo esc_attr( get_option( DWeb_PS_ADMIN_API::CONFIGURATOR_HOST, DWeb_PS_API::DEFAULT_CONFIGURATOR_HOST ) ); ?>">
			<input type="hidden" name="<?php echo esc_attr( DWeb_PS_ADMIN_API::CONFIGURATOR_HOST_VERSION ); ?>" value="<?php echo esc_attr( get_option( DWeb_PS_ADMIN_API::CONFIGURATOR_HOST_VERSION, DWeb_PS_API::DEFAULT_API_VERSION ) ); ?>">

				<div class="dweb_ps__settings__row dweb_ps__settings__row--actions">
					<div class="dweb_ps__settings__meta">
						<div class="dweb_ps__settings__label"><?php esc_html_e( 'Connection test', '3dweb-print-studio' ); ?></div>
						<small class="dweb_ps__settings-holder__description"><?php esc_html_e( 'Verify if the current credentials can authenticate.', '3dweb-print-studio' ); ?></small>
					</div>
					<div class="dweb_ps__settings-holder">
						<div class="dweb_ps__settings__actions">
							<a id="dweb_ps-test-auth" class="dweb_ps__button dweb_ps__button--normal dweb_ps__button--secondary"><?php esc_html_e( 'Test credentials', '3dweb-print-studio' ); ?></a>
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
