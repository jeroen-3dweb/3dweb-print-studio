<?php
/**
 * Options admin page.
 *
 * @package DWeb_PS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Options page controller.
 */
class DWeb_PS_ADMIN_OPTIONS extends DWeb_PS_ADMIN_PAGE_ABSTRACT {

	const PATH = '3dweb-ps-options';

	/**
	 * Page title.
	 *
	 * @var string
	 */
	protected string $page_title = 'Options';

	/**
	 * Menu title.
	 *
	 * @var string
	 */
	protected string $menu_title = 'Options';

	/**
	 * Template slug.
	 *
	 * @var string
	 */
	protected string $template = '3dweb-ps-admin-options';

	const CONFIGURATOR_DEBUG                    = 'dweb_ps_configurator_debug';
	const CONFIGURATOR_START_BUTTON_TEXT        = 'dweb_ps_start_button_text';
	const CONFIGURATOR_SESSION_CLOSED_TEXT      = 'dweb_ps_session_closed_text';
	const CONFIGURATOR_GALLERY_LOADING_TEXT     = 'dweb_ps_gallery_loading_text';
	const CONFIGURATOR_SESSION_LOADING_TEXT     = 'dweb_ps_session_loading_text';
	const CONFIGURATOR_SHOW_SESSION_DESIGN_LINK = 'dweb_ps_show_session_design_link';

	/**
	 * Checkbox field keys.
	 *
	 * @var array
	 */
	protected array $check_boxes = array(
		self::CONFIGURATOR_DEBUG,
		self::CONFIGURATOR_SHOW_SESSION_DESIGN_LINK,
	);

	/**
	 * Supported option fields.
	 *
	 * @var array
	 */
	protected array $fields = array(
		self::CONFIGURATOR_DEBUG,
		self::CONFIGURATOR_START_BUTTON_TEXT,
		self::CONFIGURATOR_SESSION_CLOSED_TEXT,
		self::CONFIGURATOR_GALLERY_LOADING_TEXT,
		self::CONFIGURATOR_SESSION_LOADING_TEXT,
		self::CONFIGURATOR_SHOW_SESSION_DESIGN_LINK,
	);
}
