<?php

declare( strict_types = 1 );

namespace Amnesty\Salesforce;

use WP_Error;
use WP_REST_Request;

/**
 * The oAuth2 handler
 */
class OAuth2 extends Singleton {

	/**
	 * Instance var
	 *
	 * @var static
	 */
	protected static $instance = null;

	/**
	 * Salesforce oAuth route base
	 *
	 * @var string
	 */
	protected static $oauth2_base = 'https://login.salesforce.com/services/oauth2';

	/**
	 * Callback path
	 *
	 * @var string
	 */
	protected static $callback = '/amnesty/v1/salesforce/oauth2/code/';

	/**
	 * Nonce value
	 *
	 * @var string
	 */
	protected static $state = '';

	/**
	 * Random data for use in request verification
	 *
	 * @var string
	 */
	protected static $verifier = '';

	/**
	 * Map of oAuth response keys and their sanitisation functions
	 *
	 * @var array<string,string>
	 */
	protected static $sanitisers = [
		'refresh_token' => 'sanitize_text_field',
		'access_token'  => 'sanitize_text_field',
		'signature'     => 'sanitize_text_field',
		'id'            => 'sanitize_text_field',
		'issued_at'     => 'sanitize_text_field',
		'instance_url'  => 'esc_url_raw',

	];

	/**
	 * Generate tokens
	 */
	protected function __construct() {
		if ( false === get_transient( 'amnesty_salesforce_challenge' ) ) {
			set_transient( 'amnesty_salesforce_challenge', bin2hex( random_bytes( 40 ) ), 5 * MINUTE_IN_SECONDS );
		}

		static::$verifier = $this::encode( hex2bin( get_transient( 'amnesty_salesforce_challenge' ) ) );
		static::$state    = wp_create_nonce( 'aisc_salesforce' );
	}

	/**
	 * Return the oAuth2 callback path
	 *
	 * @return string
	 */
	public static function callback_path(): string {
		return static::$callback;
	}

	/**
	 * Return the oAuth2 callback URL
	 *
	 * @return string
	 */
	public static function callback(): string {
		return home_url( static::$callback, 'https' );
	}

	/**
	 * Instantiate oAuth flow.
	 *
	 * Creates link to Salesforce for application authorisation
	 * and code generation.
	 *
	 * @param string $client_id the Salesforce Consumer ID
	 *
	 * @return string
	 */
	public static function oauth_init( string $client_id = '' ): string {
		return add_query_arg(
			[
				'response_type'         => 'code',
				'client_id'             => strlen( $client_id ) > 0 ? $client_id : Settings::get( 'client_id' ),
				'state'                 => static::$state,
				'redirect_uri'          => static::callback(),
				'code_challenge'        => static::encode( hash( 'sha256', static::$verifier, true ) ),
				'code_challenge_method' => 'S256',
			],
			static::$oauth2_base . '/authorize'
		);
	}

	/**
	 * Handle oAuth response from Salesforce.
	 *
	 * Triggered by redirection from the Salesforce Callback URL
	 * with approval/denial of authorisation.
	 *
	 * @param \WP_REST_Request $request the request object
	 */
	public static function code_callback( WP_REST_Request $request ) {
		// check for auth code in response
		$code = $request->get_param( 'code' );
		if ( null === $code ) {
			static::redirect( __( 'oAuth response invalid', 'aisc' ) );
			return;
		}

		// validate state "nonce"
		$state = $request->get_param( 'state' );
		if ( static::$state !== $state ) {
			static::redirect( __( 'oAuth state invalid', 'aisc' ) );
			return;
		}

		// request access token using auth token
		$response = wp_remote_post(
			static::$oauth2_base . '/token',
			[
				'body' => [
					'grant_type'    => 'authorization_code',
					'client_id'     => Settings::get( 'client_id' ),
					'redirect_uri'  => static::callback(),
					'code'          => rawurldecode( $code ),
					'code_verifier' => static::$verifier,
				],
			]
		);

		$resp_code = wp_remote_retrieve_response_code( $response );

		if ( ! preg_match( '/20\d/', (string) $resp_code ) ) {
			static::redirect( '/oauth2/token ' . wp_remote_retrieve_response_message( $response ) );
			return;
		}

		$resp_data = wp_remote_retrieve_body( $response );
		$resp_data = json_decode( $resp_data, true );

		foreach ( static::$sanitisers as $key => $callback ) {
			if ( ! isset( $resp_data[ $key ] ) ) {
				continue;
			}

			$resp_data[ $key ] = $callback( $resp_data[ $key ] );
		}

		$validate = hash_hmac( 'sha256', $resp_data['id'] . $resp_data['issued_at'], Settings::get( 'client_secret' ), true );
		$is_valid = hash_equals( $resp_data['signature'], base64_encode( $validate ) );

		if ( ! $is_valid ) {
			static::redirect( __( 'Token signature verification failed', 'aisc' ) );
			return;
		}

		if ( ! isset( $resp_data['access_token'], $resp_data['refresh_token'], $resp_data['instance_url'] ) ) {
			static::redirect( __( 'oAuth response invalid', 'aisc' ) );
			return;
		}

		Tokens::set( $resp_data );

		static::redirect( __( 'Successfully authenticated', 'aisc' ), 'info' );
	}

	/**
	 * Retrieve new oAuth2 refresh token from Salesforce
	 *
	 * @return void
	 */
	public static function refresh_token() {
		if ( ! Tokens::has( 'refresh_token' ) ) {
			Tokens::clear();
			static::log( __( 'Failed to retrieve refresh token', 'aisc' ) );
			return;
		}

		if ( ! Settings::has( 'client_id', 'client_secret' ) ) {
			Tokens::clear();
			static::log( __( 'Missing credentials for refreshing token', 'aisc' ) );
			return;
		}

		$request = wp_remote_post(
			static::$oauth2_base . '/token',
			[
				'body' => [
					'grant_type'    => 'refresh_token',
					'refresh_token' => Tokens::get( 'refresh_token' ),
					'client_id'     => Settings::get( 'client_id' ),
					'client_secret' => Settings::get( 'client_secret' ),
				],
			]
		);

		$resp_code = wp_remote_retrieve_response_code( $request );

		if ( ! preg_match( '/20\d/', (string) $resp_code ) ) {
			static::log( '/oauth2/token ' . wp_remote_retrieve_response_message( $request ) );
			return;
		}

		$resp_data = wp_remote_retrieve_body( $request );
		$resp_data = json_decode( $resp_data, true );

		foreach ( static::$sanitisers as $key => $callback ) {
			if ( ! isset( $resp_data[ $key ] ) ) {
				continue;
			}

			$resp_data[ $key ] = $callback( $resp_data[ $key ] );
		}

		$validate = hash_hmac( 'sha256', $resp_data['id'] . $resp_data['issued_at'], Settings::get( 'client_secret' ), true );
		$is_valid = hash_equals( (string) $resp_data['signature'], base64_encode( $validate ) );

		if ( ! $is_valid ) {
			static::redirect( __( 'Token signature verification failed', 'aisc' ) );
			return;
		}

		Tokens::set( $resp_data );
		static::log( __( 'Successfully refreshed token', 'aisc' ), 'info' );
	}

	/**
	 * Revoke oAuth refresh token and associated access token(s)
	 *
	 * @param string $token the refresh token to revoke
	 *
	 * @return \WP_Error
	 */
	public static function revoke_token( string $token = null ): \WP_Error {
		if ( null === $token ) {
			return static::log( 'info', __( 'No Access Token to revoke', 'aisc' ) );
		}

		$response = wp_remote_post(
			static::$oauth2_base . '/revoke',
			[
				'body' => [
					'token' => $token,
				],
			]
		);

		$resp_code = wp_remote_retrieve_response_code( $response );

		if ( ! preg_match( '/20\d/', (string) $resp_code ) ) {
			return static::log( 'info', '/oauth2/revoke' . wp_remote_retrieve_response_message( $response ) );
		}

		Tokens::clear();
		return static::log( 'info', __( 'Access Token(s) successfully revoked', 'aisc' ) );
	}

	/**
	 * Log a message and redirect back.
	 *
	 * @param string $message the message to log
	 * @param string $level the error level
	 *
	 * @return void
	 */
	protected static function redirect( string $message = '', string $level = 'error' ): void {
		static::log( $level, $message );

		$callback = 'admin_url';
		$location = 'options-general.php?page=amnesty_salesforce_connector';
		if ( Connector::is_network_level() ) {
			$callback = 'network_admin_url';
			$location = 'admin.php?page=amnesty_salesforce_connector';
		}

		wp_safe_redirect( call_user_func( $callback, $location ) );
		die;
	}

	/**
	 * Log an error and return an error response
	 *
	 * @param string $level the error level
	 * @param string $message the error message
	 *
	 * @return \WP_Error
	 */
	protected static function log( string $level = 'error', string $message = '' ): WP_Error {
		Database_Logger::instance()->log( $level, $message );
		return new WP_Error( sprintf( 'amnesty_salesforce_oauth2_%s', $level ), $message );
	}

	/**
	 * Performs URL-safe base64 encoding
	 *
	 * @param string $str the string to encode
	 *
	 * @return string the encoded string
	 */
	protected static function encode( string $str = '' ): string {
		return rtrim( strtr( base64_encode( $str ), '+/', '-_' ), '=' );
	}

	/**
	 * Peforms base64 decoding of URL-safe encoded string
	 *
	 * @param string $str the string to decode
	 *
	 * @return string the decoded string
	 */
	protected static function base64_decode_url( string $str = '' ): ?string {
		$result = base64_decode( strtr( $str, '-_', '+/' ), true );

		if ( ! is_string( $result ) ) {
			return null;
		}

		return $result;
	}

}
