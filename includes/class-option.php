<?php

declare( strict_types = 1 );

namespace Amnesty\Salesforce;

/**
 * Option management base class
 */
class Option extends Singleton {

	/**
	 * Instance variable
	 *
	 * @var static
	 */
	protected static $instance = null;

	/**
	 * Option key
	 *
	 * @var string
	 */
	protected static $key = '';

	/**
	 * Option data
	 *
	 * @var array
	 */
	protected static $option = [];

	/**
	 * Whether option data has been changed
	 *
	 * @var boolean
	 */
	protected static $dirty = false;

	/**
	 * Retrieve stored option from DB
	 */
	protected function __construct() {
		$db_option = get_site_option( static::key() );

		if ( ! is_array( $db_option ) ) {
			$db_option = [];
		}

		static::$option = $db_option;
	}

	/**
	 * Update stored option in DB
	 */
	public function __destruct() {
		if ( static::$dirty ) {
			update_site_option( static::key(), static::$option );
		}
	}

	/**
	 * Get Option key
	 *
	 * @return string
	 */
	public static function key(): string {
		return static::$key;
	}

	/**
	 * Check option has all requested keys
	 *
	 * @param mixed ...$keys the option keys
	 *
	 * @return boolean
	 */
	public static function has( ...$keys ): bool {
		if ( 1 === count( $keys ) ) {
			if ( ! is_array( $keys[0] ) ) {
				return array_has( static::$option, $keys[0] );
			}

			return count( static::pick( $keys ) ) === count( $keys );
		}

		$has = false;

		foreach ( $keys as $key ) {
			$has = $has || array_has( static::$option, $key );
		}

		return $has;
	}

	/**
	 * Get an option value
	 *
	 * @param string $key           the option key
	 * @param mixed  $default_value a default value
	 *
	 * @return mixed
	 */
	public static function get( string $key = '', $default_value = null ) {
		return array_get( static::$option, $key, $default_value );
	}

	/**
	 * Get some option values
	 *
	 * @param array $keys the option keys
	 *
	 * @return array
	 */
	public static function pick( array $keys = [] ): array {
		return array_filter( array_map( [ static::class, 'get' ], $keys ) );
	}

	/**
	 * Get all option values
	 *
	 * @return array
	 */
	public static function all(): array {
		return static::$option;
	}

	/**
	 * Get all option keys
	 *
	 * @return array
	 */
	public static function keys(): array {
		return array_keys( static::$option );
	}

	/**
	 * Set option value(s)
	 *
	 * @param mixed ...$args the option key(s) and value(s)
	 *
	 * @return void
	 */
	public static function set( ...$args ): void {
		static::$dirty = true;

		if ( ! is_array( $args[0] ) ) {
			array_set( static::$option, $args[0], $args[1] ?? null );
			return;
		}

		foreach ( $args[0] as $key => $value ) {
			array_set( static::$option, $key, $value );
		}
	}

	/**
	 * Unset one or more option keys
	 *
	 * @param mixed ...$keys the option key(s)
	 *
	 * @return void
	 */
	public static function unset( ...$keys ): void {
		static::$dirty = true;

		if ( is_array( $keys[0] ) ) {
			$keys = $keys[0];
		}

		foreach ( $keys as $key ) {
			unset( static::$option[ $key ] );
		}
	}

	/**
	 * Remove all option values
	 *
	 * @return void
	 */
	public static function clear(): void {
		static::$dirty  = true;
		static::$option = [];
	}

}
