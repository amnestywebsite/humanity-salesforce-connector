<?php

declare( strict_types = 1 );

namespace Amnesty\Salesforce;

/**
 * Salesforce object handler
 */
class SObjects extends Singleton {

	/**
	 * Instance variable
	 *
	 * @var static
	 */
	protected static $instance = null;

	/**
	 * Available sObjects
	 *
	 * @var array
	 */
	protected static $objects = [];

	/**
	 * List the available sObjects
	 *
	 * @return array
	 */
	protected function list(): array {
		$list = [];

		foreach ( static::$objects as $name => $object ) {
			$list[ $name ] = $object->label();
		}

		natsort( $list );

		return [ '~' => __( 'None', 'cmb2' ) ] + $list;
	}

	/**
	 * Retrieve an sObject
	 *
	 * @param string $name the Salesforce object name
	 *
	 * @return SObject|null
	 */
	protected function get( string $name ): ?SObject {
		return static::$objects[ $name ] ?? null;
	}

	/**
	 * Retrieve object list
	 */
	protected function __construct() {
		$objects = wp_cache_get( 'sobject-list', 'aisc' );

		if ( is_array( $objects ) && count( $objects ) > 0 ) {
			$this->load( $objects );
			return;
		}

		$objects = Request::get( '/sobjects/' );
		$objects = array_column( $objects['sobjects'] ?? [], null, 'name' );
		wp_cache_add( 'sobject-list', $objects, 'aisc' );

		$this->load( $objects );
	}

	/**
	 * Instantiate objects
	 *
	 * @param array $raw the raw Salesforce object list
	 *
	 * @return void
	 */
	protected function load( array $raw = [] ): void {
		$objects = [];

		foreach ( $raw as $name => $data ) {
			$objects[ $name ] = new SObject( $name, $data );
		}

		static::$objects = $objects;
	}

}
