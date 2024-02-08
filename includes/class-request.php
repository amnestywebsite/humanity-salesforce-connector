<?php

declare( strict_types = 1 );

namespace Amnesty\Salesforce;

/**
 * Salesforce API request handler
 */
final class Request {

	/**
	 * Salesforce API version to use
	 *
	 * @var string
	 */
	protected static $api_version = 'v49.0';

	/**
	 * Request method
	 *
	 * @var string
	 */
	protected $method = '';

	/**
	 * Target endpoint
	 *
	 * @var string
	 */
	protected $endpoint = '';

	/**
	 * Data to send with a request
	 *
	 * @var null|array
	 */
	protected $data = null;

	/**
	 * Perform a DELETE request
	 *
	 * @param string $endpoint the endpoint to send the request to
	 * @param array  $data     the data to send with the request
	 *
	 * @return array the response data
	 */
	public static function delete( string $endpoint, array $data = [] ): array {
		$request = new self( 'DELETE', $endpoint, $data );
		return $request->execute();
	}

	/**
	 * Perform a HEAD request
	 *
	 * @param string $endpoint the endpoint to send the request to
	 * @param array  $data     the data to send with the request
	 *
	 * @return array the response data
	 */
	public static function head( string $endpoint, array $data = [] ): array {
		$request = new self( 'HEAD', $endpoint, $data );
		return $request->execute();
	}

	/**
	 * Perform a GET request
	 *
	 * @param string $endpoint the endpoint to send the request to
	 * @param array  $data     the data to send with the request
	 *
	 * @return array the response data
	 */
	public static function get( string $endpoint, array $data = [] ): array {
		$request = new self( 'GET', $endpoint, $data );
		return $request->execute();
	}

	/**
	 * Perform an OPTIONS request
	 *
	 * @param string $endpoint the endpoint to send the request to
	 * @param array  $data     the data to send with the request
	 *
	 * @return array the response data
	 */
	public static function options( string $endpoint, array $data = [] ): array {
		$request = new self( 'OPTIONS', $endpoint, $data );
		return $request->execute();
	}

	/**
	 * Perform a PATCH request
	 *
	 * @param string $endpoint the endpoint to send the request to
	 * @param array  $data     the data to send with the request
	 *
	 * @return array the response data
	 */
	public static function patch( string $endpoint, array $data = [] ): array {
		$request = new self( 'PATCH', $endpoint, $data );
		return $request->execute();
	}

	/**
	 * Perform a POST request
	 *
	 * @param string $endpoint the endpoint to send the request to
	 * @param array  $data     the data to send with the request
	 *
	 * @return array the response data
	 */
	public static function post( string $endpoint, array $data = [] ): array {
		$request = new self( 'POST', $endpoint, $data );
		return $request->execute();
	}

	/**
	 * Perform a PUT request
	 *
	 * @param string $endpoint the endpoint to send the request to
	 * @param array  $data     the data to send with the request
	 *
	 * @return array the response data
	 */
	public static function put( string $endpoint, array $data = [] ): array {
		$request = new self( 'PUT', $endpoint, $data );
		return $request->execute();
	}

	/**
	 * Set required data
	 *
	 * @param string $method the request method
	 * @param string $endpoint the target endpoint
	 * @param array  $data additional data to send with request
	 */
	protected function __construct( string $method = '', string $endpoint = '', array $data = [] ) {
		$this->method   = $method;
		$this->endpoint = $endpoint;
		$this->data     = $data;
	}

	/**
	 * Execute a request
	 *
	 * @param bool $retry whether to refresh access token and retry request
	 *
	 * @return array the response data
	 */
	protected function execute( bool $retry = true ): array {
		$http = _wp_http_get_object();
		$args = [ 'method' => $this->method ];

		if ( in_array( $this->method, [ 'PATCH', 'POST', 'PUT' ], true ) ) {
			$args['body'] = wp_json_encode( $this->data );
		}

		$cache_key = sprintf( '%s:%s:%s', $this->method, md5( $this->url() ), md5( wp_json_encode( $args ) ) );
		$cached    = wp_cache_get( $cache_key, 'amnesty_salesforce' );

		if ( false !== $cached && false === $retry ) {
			return $cached;
		}

		try {
			$response = $http->request( $this->url(), $args + [ 'headers' => $this->headers() ] );
			$response = $this->validate_response( $response, $this->endpoint );

			wp_cache_set( $cache_key, $response, 'amnesty_salesforce' );

			return $response;
		} catch ( \Exception $e ) {
			if ( ! $retry ) {
				return [];
			}

			if ( ! in_array( absint( $e->getCode() ), [ 401, 403 ], true ) ) {
				return [];
			}

			OAuth2::refresh_token();
			return $this->execute( false );
		}
	}

	/**
	 * Build a fully-qualified request URI
	 *
	 * @throws \Amnesty\Salesforce\Exception if Salesforce instance URL not set
	 *
	 * @return string
	 */
	protected function url() {
		if ( ! Tokens::has( 'instance_url' ) ) {
			throw new Exception( esc_html__( 'Instance URL not found.', 'aisc' ), 'error' );
		}

		$endpoint = sprintf(
			'%s/services/data/%s/%s',
			rtrim( Tokens::get( 'instance_url' ), '/' ),
			trim( self::$api_version, '/' ),
			ltrim( $this->endpoint, '/' )
		);

		if ( 'GET' !== $this->method ) {
			return $endpoint;
		}

		return add_query_arg( $this->data, $endpoint );
	}

	/**
	 * Build request headers
	 *
	 * @throws \Amnesty\Salesforce\Exception if no access token
	 *
	 * @return array
	 */
	protected function headers(): array {
		if ( ! Tokens::has( 'access_token' ) ) {
			throw new Exception( esc_html__( 'Access Token Not Found', 'aisc' ), 'error' );
		}

		return [
			'Authorization' => sprintf( 'Bearer %s', Tokens::get( 'access_token' ) ),
			'Content-Type'  => 'application/json',
		];
	}

	/**
	 * Validate the response from Salesforce
	 *
	 * @param array|\WP_Error $response the response data or an error
	 * @param string          $label    error label, if required
	 *
	 * @throws \Amnesty\Salesforce\Exception if API response is invalid
	 *
	 * @return array decoded response data
	 */
	protected function validate_response( $response, $label = '' ): array {
		$resp_code = absint( wp_remote_retrieve_response_code( $response ) );

		if ( preg_match( '/20\d/', (string) $resp_code ) ) {
			return $this->decode_response( $response );
		}

		if ( 400 === $resp_code ) {
			return $this->decode_response( $response );
		}

		$error = str_pad( $label, 1 );
		if ( is_wp_error( $response ) ) {
			$error .= $response->get_error_message();
		} else {
			$error .= wp_remote_retrieve_response_message( $response );
		}

		throw new Exception( esc_html( $error ), 'Error', absint( $resp_code ) );
	}

	/**
	 * Attempt to JSON decode the Salesforce response
	 *
	 * @param array $response successful response data
	 *
	 * @throws \Amnesty\Salesforce\Exception if API response body invalid
	 *
	 * @return array deccoded data
	 */
	protected function decode_response( $response ): array {
		$resp_data = wp_remote_retrieve_body( $response );

		// handle No Content style response
		if ( 0 === strlen( $resp_data ) ) {
			return [];
		}

		$resp_data = json_decode( $resp_data, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			throw new Exception( esc_html( 'JSON Error: ' . json_last_error_msg() ), 'error' );
		}

		return $resp_data;
	}

}
