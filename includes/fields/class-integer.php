<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName

declare( strict_types = 1 );

namespace Amnesty\Salesforce\Fields;

/**
 * Representation of integer type Salesforce object field
 */
class Type_Integer extends Field_Type {

	/**
	 * Field type
	 *
	 * @var string
	 */
	protected $type = 'number';

}
