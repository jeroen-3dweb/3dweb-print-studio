<?php
/**
 * Hook loader.
 *
 * @package DWeb_PS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers actions and filters for the plugin.
 */
class DWeb_PS_Loader {

	/**
	 * Registered actions.
	 *
	 * @var array
	 */
	protected array $actions;

	/**
	 * Registered filters.
	 *
	 * @var array
	 */
	protected array $filters;

	/**
	 * Initializes the loader collections.
	 */
	public function __construct() {
		$this->actions = array();
		$this->filters = array();
	}

	/**
	 * Adds an action definition.
	 *
	 * @param string $hook          Hook name.
	 * @param mixed  $component     Hook component.
	 * @param string $callback      Callback method name.
	 * @param int    $priority      Hook priority.
	 * @param int    $accepted_args Number of accepted args.
	 */
	public function dweb_ps_add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->actions = $this->add( $this->actions, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * Adds a filter definition.
	 *
	 * @param string $hook          Hook name.
	 * @param mixed  $component     Hook component.
	 * @param string $callback      Callback method name.
	 * @param int    $priority      Hook priority.
	 * @param int    $accepted_args Number of accepted args.
	 */
	public function dweb_ps_add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->filters = $this->add( $this->filters, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * Adds a hook definition to the collection.
	 *
	 * @param array  $hooks         Registered hooks.
	 * @param string $hook          Hook name.
	 * @param mixed  $component     Hook component.
	 * @param string $callback      Callback method name.
	 * @param int    $priority      Hook priority.
	 * @param int    $accepted_args Accepted args.
	 * @return mixed
	 */
	private function add( $hooks, $hook, $component, $callback, $priority, $accepted_args ) {
		$hooks[] = array(
			'hook'          => $hook,
			'component'     => $component,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);

		return $hooks;
	}

	/**
	 * Register the filters and actions with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function dweb_ps_run() {
		foreach ( $this->filters as $hook ) {
			add_filter( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
		}

		foreach ( $this->actions as $hook ) {
			add_action( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
		}
	}
}
