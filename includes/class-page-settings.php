<?php

declare( strict_types = 1 );

namespace Amnesty\Salesforce;

/**
 * Settings page handler class
 */
class Page_Settings {

	/**
	 * Bind hooks
	 */
	public function __construct() {
		// spin up instances
		Settings::instance();
		Tokens::instance();
		OAuth2::instance();

		add_action( 'admin_notices', [ $this, 'create_oauth_notice' ] );
		add_action( 'network_admin_notices', [ $this, 'create_oauth_notice' ] );
		add_action( 'cmb2_init', [ $this, 'register_settings' ] );
		add_action( 'cmb2_save_options-page_fields_' . Settings::key(), [ $this, 'save_settings' ] );
	}

	/**
	 * Display notice which includes link to trigger oAuth flow
	 *
	 * @return void
	 */
	public function create_oauth_notice(): void {
		if ( ! Settings::has( 'client_id', 'client_secret' ) ) {
			return;
		}

		if ( Tokens::has( 'access_token', 'refresh_token' ) ) {
			return;
		}

		echo wp_kses_post(
			$this->get_message(
				'notice',
				[
					'link' => OAuth2::oauth_init( Settings::get( 'client_id' ) ),
				] 
			) 
		);
	}

	/**
	 * Register the adapter's required settings
	 *
	 * @return void
	 */
	public function register_settings(): void {
		$menu_hook = 'admin_menu';
		if ( Connector::is_network_level() ) {
			$menu_hook = 'network_admin_menu';
		}

		$settings = new_cmb2_box(
			[
				'id'              => Settings::key(),
				'title'           => __( 'Salesforce Settings', 'aip-sf' ),
				'object_types'    => [ 'options-page' ],
				'option_key'      => Settings::key(),
				'icon_url'        => 'dashicons-hammer',
				'admin_menu_hook' => $menu_hook,
			] 
		);

		if ( ! Tokens::has( 'refresh_token' ) ) {
			$settings->add_field(
				[
					'id'      => 'oauth2',
					'type'    => 'message',
					'message' => $this->get_message( 'oauth2' ),
				] 
			);
		}

		$settings->add_field(
			[
				'id'   => 'client_id',
				'name' => __( 'Consumer Key', 'aip-sf' ),
				'desc' => __( 'Connected App Consumer Key', 'aip-sf' ),
				'type' => 'text',
			] 
		);

		$settings->add_field(
			[
				'id'   => 'client_secret',
				'name' => __( 'Consumer Secret', 'aip-sf' ),
				'desc' => __( 'Connected App Consumer Secret', 'aip-sf' ),
				'type' => 'password',
			] 
		);

		do_action( 'amnesty_salesforce_connector_settings', $settings, $menu_hook );
	}

	/**
	 * Revoke oAuth tokens if client keys are deleted
	 */
	public function save_settings(): void {
		if ( Settings::has( 'client_id', 'client_secret' ) ) {
			return;
		}

		if ( ! Tokens::has( 'refresh_token' ) ) {
			return;
		}

		OAuth2::revoke_token( Tokens::get( 'refresh_token' ) );
	}

	/**
	 * Retrieve a message from the messages directory
	 *
	 * @param string $name the message name
	 * @param array  $vars the template data
	 *
	 * @return string
	 */
	protected function get_message( string $name = '', array $vars = [] ): string {
		$dir  = dirname( Connector::$file );
		$file = sprintf( '%s/messages/%s.php', untrailingslashit( $dir ), $name );

		extract( $vars );

		ob_start();
		include $file;
		return ob_get_clean();
	}

}
