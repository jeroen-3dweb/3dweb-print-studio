<?php
/**
 * Admin settings save button partial.
 *
 * @package DWeb_PS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="dweb_ps__settings__holder">
	<p id="dweb_ps__save-settings-error" class="dweb_ps__error"></p>
	<a id="dweb_ps-save-settings" class="dweb_ps__button dweb_ps__button--normal dweb_ps__button--primary">
		<?php esc_html_e( 'Save', '3dweb-print-studio' ); ?>
	</a>
</div>
