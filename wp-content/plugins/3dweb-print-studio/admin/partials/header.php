<?php
/**
 * Admin settings header partial.
 *
 * @package DWeb_PS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="dweb_ps dweb_ps__admin-wrapper">
	<header class="dweb_ps__swoosh-header"></header>
	<div class="dweb_ps__swoosh-container">
		<div class="dweb_ps__swoosh-container">
			<div class="dweb_ps__header-offset">
				<div class="dweb_ps__backdrop">
					<div class="dweb_ps__backdrop-container">
						<div class="dweb_ps__backdrop-header">
							<div class="dweb_ps__logo-poppins"></div>
							<div>
								<img src="<?php echo esc_url( plugins_url( 'admin/img/sign-130.png', DWEB_PS_MAIN_URL ) ); ?>" class="dweb_ps__logo"
									alt="<?php esc_attr_e( '3DWeb logo', '3dweb-print-studio' ); ?>">
							</div>
						</div>
						<div class="dweb_ps__card">
