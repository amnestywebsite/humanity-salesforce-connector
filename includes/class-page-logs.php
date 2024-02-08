<?php

declare( strict_types = 1 );

namespace Amnesty\Salesforce;

/**
 * Log page handler
 */
class Page_Logs {

	/**
	 * Required settings management capability
	 *
	 * @var string
	 */
	protected $cap = 'manage_options';

	/**
	 * Logger class instance
	 *
	 * @var \Amnesty\Salesforce\AbstractLogger
	 */
	protected $logger = null;

	/**
	 * List table class instance
	 *
	 * @var \WP_List_Table
	 */
	protected $list_table = null;

	/**
	 * Bind hooks
	 *
	 * @param AbstractLogger $logger logger class instance
	 */
	public function __construct( AbstractLogger $logger = null ) {
		$this->logger = $logger;

		add_action( 'cmb2_init', [ $this, 'register_page' ], 20 );
		add_filter( 'set-screen-option', [ $this, 'set_screen_options' ], 10, 3 );
		add_action( 'load-salesforce-settings_page_aisc_logs', [ $this, 'add_screen_options' ] );
		add_action( 'load-salesforce-settings_page_aisc_logs-network', [ $this, 'add_screen_options' ] );
	}

	/**
	 * Register settings pages
	 *
	 * @return void
	 */
	public function register_page(): void {
		$menu_hook = 'admin_menu';
		if ( Connector::is_network_level() ) {
			$menu_hook = 'network_admin_menu';
		}

		new_cmb2_box(
			[
				'id'              => 'aisc_logs',
				'parent_slug'     => Settings::key(),
				'title'           => __( 'Logs', 'aip-sf' ),
				'object_types'    => [ 'options-page' ],
				'option_key'      => 'aisc_logs',
				'icon_url'        => 'dashicons-hammer',
				'admin_menu_hook' => $menu_hook,
				'display_cb'      => [ $this, 'render' ],
			] 
		);
	}

	/**
	 * Render the logs page
	 *
	 * @return void
	 */
	public function render(): void {
		$this->list_table = new Logs_List_Table( $this->logger );

		require_once dirname( __DIR__ ) . '/views/logs.php';
	}

	/**
	 * Callback for setting per-page screen option
	 *
	 * @param bool   $status whether to keep option value. Unused here
	 * @param string $option the option name. Unused here
	 * @param int    $value  the option value
	 *
	 * @return int
	 */
	public function set_screen_options( bool $status = false, string $option = '', int $value = 10 ): int {
		return $value;
	}

	/**
	 * Register screen option(s)
	 *
	 * @return void
	 */
	public function add_screen_options(): void {
		add_screen_option(
			'per_page',
			[
				'label'   => __( 'Logs Per Page', 'aip' ),
				'option'  => 'aisc_logs_per_page',
				'default' => 20,
			] 
		);
	}

}
