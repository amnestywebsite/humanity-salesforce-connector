<?php

declare( strict_types = 1 );

namespace Amnesty\Salesforce;

use Amnesty\Salesforce\Fields\Field_Type;

/**
 * Salesforce object field representation
 */
class SObject_Field {

	/**
	 * Class map
	 *
	 * @var array
	 */
	protected static $types = [
		'boolean'  => 'Type_Boolean',
		'email'    => 'Type_Email',
		'integer'  => 'Type_Integer',
		'picklist' => 'Type_Picklist',
		'string'   => 'Type_String',
	];

	/**
	 * Instantiate new field type class
	 *
	 * @param array $data the field data
	 *
	 * @return \Amnesty\Salesforce\Fields\Field_Type|null
	 */
	public static function new( array $data = [] ): ?Field_Type {
		$class = static::$types[ $data['type'] ] ?? 'Field_Type';
		$class = sprintf( '\\Amnesty\\Salesforce\\Fields\\%s', $class );

		return new $class( $data );
	}

}
