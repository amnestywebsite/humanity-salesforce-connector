<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName

declare( strict_types = 1 );

namespace Amnesty\Salesforce\Fields;

/**
 * Representation of string type Salesforce object field
 */
class Type_String extends Field_Type {

	/**
	 * Field type
	 *
	 * @var string
	 */
	protected $type = 'text';

}
