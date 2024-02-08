<?php

declare( strict_types = 1 );

namespace Amnesty\Salesforce;

use Exception;
use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

/**
 * REST API endpoints for Salesforce objects
 */
class Rest_Api {

	/**
	 * API namespace
	 *
	 * @var string
	 */
	protected $namespace = 'aisc/v1';

	/**
	 * Logger instance
	 *
	 * @var AbstractLogger
	 */
	protected $logger = null;

	/**
	 * Salesforce Objects handler class name
	 *
	 * @var string
	 */
	protected $objects = '';

	/**
	 * Setup objects
	 *
	 * @param string         $objects Salesforce objects handler class name
	 * @param AbstractLogger $logger  logger class instance
	 */
	public function __construct( string $objects, AbstractLogger $logger ) {
		$this->objects = $objects;
		$this->logger  = $logger;

		$this->endpoints();
	}

	/**
	 * Register routes
	 *
	 * @return void
	 */
	protected function endpoints(): void {
		register_rest_route(
			$this->namespace,
			'/sobjects',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_items' ],
				'permission_callback' => [ $this, 'permissions' ],
			] 
		);

		register_rest_route(
			$this->namespace,
			'/sobjects/(?P<object>\w+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_item' ],
				'permission_callback' => [ $this, 'permissions' ],
			] 
		);

		register_rest_route(
			$this->namespace,
			'/sobjects/(?P<object>\w+)/(?P<field>[^/]+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_item_data' ],
				'permission_callback' => [ $this, 'permissions' ],
			] 
		);
	}

	/**
	 * Check that user can manage options
	 *
	 * @return boolean
	 */
	public function permissions(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Retrieve all Salesforce Objects
	 *
	 * @return array
	 */
	public function get_items(): array {
		try {
			return $this->objects::instance()->list();
		} catch ( Exception $e ) {
			$this->logger->error( $e->getMessage(), $e->getCode() );
			return [];
		}
	}

	/**
	 * Retrieve a Salesforce Object
	 *
	 * @param \WP_REST_Request $request the request object
	 *
	 * @return WP_Error|array
	 */
	public function get_item( WP_REST_Request $request ) {
		$object = $request->get_param( 'object' );

		if ( null === $object ) {
			$this->logger->error( __( 'Bad Request' ), 400 );
			return new WP_Error( 'http_request_failed', __( 'Bad Request' ), [ 'status' => 400 ] );
		}

		try {
			return $this->objects::instance()->get( $object )->list();
		} catch ( Exception $e ) {
			$this->logger->error( $e->getMessage(), $e->getCode() );
			return [];
		}
	}

	/**
	 * Retrieve a Salesforce Object Field
	 *
	 * @param \WP_REST_Request $request the request object
	 *
	 * @return WP_Error|array
	 */
	public function get_item_data( WP_REST_Request $request ) {
		$object = $request->get_param( 'object' );

		if ( null === $object ) {
			$this->logger->error( __( 'Bad Request' ), 400 );
			return new WP_Error( 'http_request_failed', __( 'Bad Request' ), [ 'status' => 400 ] );
		}

		$field = $request->get_param( 'field' );

		if ( null === $field ) {
			$this->logger->error( __( 'Bad Request' ), 400 );
			return new WP_Error( 'http_request_failed', __( 'Bad Request' ), [ 'status' => 400 ] );
		}

		try {
			$field = $this->objects::instance()->get( $object )->get( rawurldecode( $field ) );

			return [
				'type'    => $field->type(),
				'subtype' => $field->subtype(),
				'list'    => $field->list(),
			];
		} catch ( Exception $e ) {
			$this->logger->error( __( 'Failed to get object field', 'aisc' ), 500 );

			return [
				'type'    => null,
				'subtype' => null,
				'list'    => [],
			];
		}
	}

}
