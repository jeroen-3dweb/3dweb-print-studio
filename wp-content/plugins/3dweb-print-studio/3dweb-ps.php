<?php
/**
 * Backward-compatible shim for legacy plugin entrypoint references.
 *
 * @package DWeb_PS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once plugin_dir_path( __FILE__ ) . '3dweb-print-studio.php';
