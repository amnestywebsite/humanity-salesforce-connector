<?php

if ( ! function_exists( 'array_set' ) ) {
	/**
	 * Set an array value by dot-notation key
	 *
	 * @param array      $arr   the array to modify
	 * @param string     $key   the key to set
	 * @param mixed|null $value the value to set
	 *
	 * @return mixed|null
	 */
	function array_set( array &$arr, string $key = '', $value = null ) {
		$key = strtok( $key, '.' );

		while ( false !== $key ) {
			if ( is_numeric( $key ) ) {
				$key = intval( $key, 10 );
			}

			if ( ! isset( $arr[ $key ] ) ) {
				$arr[ $key ] = [];
			}

			// phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.VariableRedeclaration
			$arr = &$arr[ $key ];
			$key = strtok( '.' );
		}

		// phpcs:ignore
		return $arr = $value;
	}
}

if ( ! function_exists( 'array_get' ) ) {
	/**
	 * Get a value from an array
	 *
	 * @param array      $arr           the array to retrieve the value from
	 * @param string     $key           the key whose value we're retrieving
	 * @param mixed|null $default_value fallback value
	 *
	 * @return mixed|null
	 */
	function array_get( array $arr, string $key = '', $default_value = null ) {
		$key = strtok( $key, '.' );

		while ( false !== $key ) {
			if ( is_numeric( $key ) ) {
				$key = intval( $key, 10 );
			}

			if ( ! isset( $arr[ $key ] ) ) {
				return $default_value;
			}

			$arr = $arr[ $key ];
			$key = strtok( '.' );
		}

		return $arr;
	}
}

if ( ! function_exists( 'array_has' ) ) {
	/**
	 * Check whether array has a key
	 *
	 * @param array  $arr the source array
	 * @param string $key the key to locate
	 *
	 * @return bool
	 */
	function array_has( array $arr, string $key = '' ): bool {
		return ! is_null( array_get( $arr, $key ) );
	}
}

if ( ! function_exists( 'array_dot' ) ) {
	/**
	 * Flatten an array using dot notation
	 *
	 * @param array $arr the array to flatten
	 *
	 * @return array
	 */
	function array_dot( array $arr = [] ): array {
		$it  = new RecursiveArrayIterator( $arr );
		$it  = new RecursiveIteratorIterator( $it );
		$res = [];

		foreach ( $it as $leaf ) {
			$keys = [];

			foreach ( range( 0, $it->getDepth() ) as $depth ) {
				$keys[] = $it->getSubIterator( $depth )->key();
			}

			$res[ implode( '.', $keys ) ] = $leaf;
		}

		return $res;
	}
}
