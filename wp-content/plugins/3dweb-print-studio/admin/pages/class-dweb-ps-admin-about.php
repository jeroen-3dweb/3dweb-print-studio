<?php
/**
 * About admin page.
 *
 * @package DWeb_PS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * About page controller.
 */
class DWeb_PS_ADMIN_ABOUT extends DWeb_PS_ADMIN_PAGE_ABSTRACT {

	const PATH = '3dweb-ps-about';

	/**
	 * Page title.
	 *
	 * @var string
	 */
	protected string $page_title = '3DWeb Print Studio';

	/**
	 * Menu title.
	 *
	 * @var string
	 */
	protected string $menu_title = '3DWeb Print Studio';

	/**
	 * Template slug.
	 *
	 * @var string
	 */
	protected string $template = '3dweb-ps-admin-display-about';

	/**
	 * Whether this is the main menu page.
	 *
	 * @var bool
	 */
	protected bool $is_main_menu = true;
}
