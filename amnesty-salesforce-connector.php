<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName

/**
 * Plugin Name:       Humanity Salesforce Connector
 * Plugin URI:        https://github.com/amnestywebsite/humanity-salesforce-connector
 * Description:       Add Salesforce oAuth connector for use by other Humanity plugins
 * Version:           1.0.1
 * Author:            Amnesty International
 * Author URI:        https://www.amnesty.org
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       aisc
 * Domain Path:       /languages
 * Network:           true
 * Requires PHP:      8.2
 * Requires at least: 5.8.0
 * Tested up to:      6.7.2
 */

declare( strict_types = 1 );

namespace Amnesty\Salesforce;

use WP;
use WP_REST_Request;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'get_plugin_data' ) ) {
	require_once ABSPATH . '/wp-admin/includes/plugin.php';
}

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/interface-logger.php';
require_once __DIR__ . '/includes/abstract-class-singleton.php';
require_once __DIR__ . '/includes/abstract-class-logger.php';
require_once __DIR__ . '/includes/class-exception.php';
require_once __DIR__ . '/includes/class-database-logger.php';
require_once __DIR__ . '/includes/class-logs-list-table.php';
require_once __DIR__ . '/includes/class-option.php';
require_once __DIR__ . '/includes/class-settings.php';
require_once __DIR__ . '/includes/class-tokens.php';
require_once __DIR__ . '/includes/class-oauth2.php';
require_once __DIR__ . '/includes/class-rest-api.php';
require_once __DIR__ . '/includes/class-request.php';
require_once __DIR__ . '/includes/class-sobjects.php';
require_once __DIR__ . '/includes/class-sobject.php';
require_once __DIR__ . '/includes/class-sobject-field.php';
require_once __DIR__ . '/includes/fields/class-field-type.php';
require_once __DIR__ . '/includes/fields/class-boolean.php';
require_once __DIR__ . '/includes/fields/class-integer.php';
require_once __DIR__ . '/includes/fields/class-picklist.php';
require_once __DIR__ . '/includes/fields/class-string.php';
require_once __DIR__ . '/includes/fields/class-email.php';
require_once __DIR__ . '/includes/class-page-logs.php';
require_once __DIR__ . '/includes/class-page-settings.php';

register_activation_hook(
	__FILE__,
	function (): void {
		add_action( 'shutdown', 'flush_rewrite_rules', 200 );
		Database_Logger::up();
	}
);

register_deactivation_hook(
	__FILE__,
	function (): void {
		OAuth2::revoke_token( Tokens::get( 'refresh_token' ) );
		Settings::clear();
		Tokens::clear();
		Database_Logger::down();
	}
);

if ( ! defined( 'AISC_RELPATH' ) ) {
	define( 'AISC_RELPATH', sprintf( '%s/%s', basename( __DIR__ ), basename( __FILE__ ) ) );
}

new Connector();

/**
 * Plugin setup class
 */
class Connector {

	/**
	 * Absolute path to this file
	 *
	 * @var string
	 */
	public static $file = __FILE__;

	/**
	 * Plugin data
	 *
	 * @var array
	 */
	protected $data = [];

	/**
	 * Bind hooks
	 */
	public function __construct() {
		$this->data = get_plugin_data( __FILE__ );

		add_filter( 'translatable_packages', [ $this, 'register_translatable_package' ], 12 );

		add_action( 'all_admin_notices', [ $this, 'check_dependencies' ] );

		add_action( 'plugins_loaded', [ $this, 'textdomain' ] );
		add_action( 'plugins_loaded', [ $this, 'boot' ], 1 );
		add_action( 'init', [ $this, 'var' ] );
		add_action( 'parse_request', [ $this, 'request' ] );
		add_action( 'init', [ $this, 'rewrite' ], 10 );
		add_action( 'rest_api_init', [ $this, 'api' ] );
		add_action( 'toplevel_page_amnesty_salesforce_connector', [ $this, 'flush' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ], 1 );
	}

	/**
	 * Register this plugin as a translatable package
	 *
	 * @param array<int,array<string,string>> $packages existing packages
	 *
	 * @return array<int,array<string,string>>
	 */
	public function register_translatable_package( array $packages = [] ): array {
		$packages[] = [
			'id'     => 'humanity-salesforce-connector',
			'path'   => realpath( __DIR__ ),
			'pot'    => realpath( __DIR__ ) . '/languages/aisc.pot',
			'domain' => 'aisc',
		];

		return $packages;
	}

	/**
	 * Output warning & deactivate if dependent plugins aren't active
	 *
	 * @return void
	 */
	public function check_dependencies(): void {
		if ( function_exists( 'cmb2_bootstrap' ) ) {
			return;
		}

		$missing = 'CMB2';

		// translators: %1$s: the name of this plugin, %2$s: list of missing plugins
		printf( '<div class="notice notice-error"><p>%s</p></div>', sprintf( esc_html__( '%1$s requires these plugins to be active: %2$s', 'aip-sf' ), esc_html( $this->data['Name'] ), esc_html( $missing ) ) );
		deactivate_plugins( plugin_basename( __FILE__ ), false, is_multisite() );
	}

	/**
	 * Whether the plugin is network active or not
	 *
	 * @return boolean
	 */
	public static function is_network_level(): bool {
		return is_multisite() && is_plugin_active_for_network( AISC_RELPATH );
	}

	/**
	 * Register textdomain
	 *
	 * @return void
	 */
	public function textdomain(): void {
		load_plugin_textdomain( 'aisc', false, basename( __DIR__ ) . '/languages' );
	}

	/**
	 * Boot required classes
	 *
	 * @return void
	 */
	public function boot(): void {
		new Page_Logs( Database_Logger::instance() );
		new Page_Settings();
	}

	/**
	 * Register plugin query var
	 *
	 * @return void
	 */
	public function var(): void {
		/**
		 * Access query vars from global $wp
		 *
		 * @var \WP $wp
		 */
		global $wp;

		$wp->add_query_var( 'aisc' );
	}

	/**
	 * Handle load request
	 *
	 * @param WP $wp WP class instance
	 *
	 * @return void
	 */
	public function request( WP $wp ): void {
		if ( ! isset( $wp->query_vars['aisc'] ) || 'code' !== $wp->query_vars['aisc'] ) {
			return;
		}

		$request = new WP_REST_Request( 'GET', $wp->request );
		$request->set_query_params( $_GET ); // phpcs:ignore
		OAuth2::code_callback( $request );
	}

	/**
	 * Register oauth callback route
	 *
	 * @return void
	 */
	public function rewrite(): void {
		$path = trim( OAuth2::callback_path(), '/' );
		add_rewrite_rule( sprintf( '%s/?$', $path ), 'index.php?aisc=code', 'top' );
	}

	/**
	 * Register the REST API routes
	 *
	 * @return void
	 */
	public function api(): void {
		new Rest_Api( SObjects::class, Database_Logger::instance() );
	}

	/**
	 * Maybe flush rewrite rules
	 *
	 * @return void
	 */
	public function flush(): void {
		global $pagenow;

		if ( 'admin.php' !== $pagenow ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- no nonce required
		if ( ! isset( $_GET['page'] ) || 'amnesty_salesforce_connector' !== $_GET['page'] ) {
			return;
		}

		$rules = get_option( 'rewrite_rules' );
		$rule  = sprintf( '%s/?$', trim( OAuth2::callback_path(), '/' ) );

		if ( isset( $rules[ $rule ] ) ) {
			return;
		}

		// we're loading the salesforce settings screen,
		// and our rewrite rule(s) aren't set. flush them
		// to ensure that our oauth routes are registered.
		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules
		flush_rewrite_rules();
	}

	/**
	 * Add REST API data to script
	 *
	 * @return void
	 */
	public function enqueue(): void {
		// v no need for nonce verification
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = sanitize_key( $_GET['page'] ?? '' );
		if ( false === strpos( $page, 'amnesty_salesforce' ) ) {
			return;
		}

		wp_enqueue_script( 'aisc', plugins_url( '/assets/app.js', __FILE__ ), [ 'lodash', 'wp-api-fetch', 'wp-hooks', 'wp-i18n' ], $this->data['Version'], true );
		wp_set_script_translations( 'aisc', 'aisc', __DIR__ . '/languages' );

		wp_localize_script(
			'aisc',
			'AISC',
			[
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'baseurl' => rest_url( '/aisc/v1/', 'https' ),
			]
		);
	}

}
