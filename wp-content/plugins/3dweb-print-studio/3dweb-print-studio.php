<?php
/**
Plugin Name: 3DWeb Print Studio
Plugin URI: https://3dweb.io
Description: Design your print in real-time 3D. Visualize, customize and approve print designs instantly - fully branded and easy to integrate.
Author: Jeroen Termaat
Author URI: https://3dweb.nl
Developer: Jeroen Termaat
Developer URI: https://3dweb.nl
Version: 1.0.0
Requires PHP: 7.4
Last Modified: 2026-01-27
License: GPLv2
Text Domain: 3dweb-print-studio
Domain Path: /languages
 *
 * @package DWeb_PS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$dweb_ps_version = '1.0.1';
define( 'DWEB_PS_VERSION', $dweb_ps_version );
define( 'DWEB_PS_PATH', plugin_dir_path( __FILE__ ) );
define( 'DWEB_PS_MAIN_URL', __FILE__ );
define( 'DWEB_PS_DOMAIN', '3dweb-print-studio' );

require plugin_dir_path( __FILE__ ) . 'includes/class-dweb-ps.php';

/**
 * Boots the plugin.
 *
 * @param string $version Plugin version.
 */
function dweb_ps_run( $version ) {
	( new DWeb_PS( $version ) )->dweb_ps_run();
}

dweb_ps_run( $dweb_ps_version );
