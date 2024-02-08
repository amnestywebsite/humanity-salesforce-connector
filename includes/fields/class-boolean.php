<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName

declare( strict_types = 1 );

namespace Amnesty\Salesforce\Fields;

/**
 * Representation of boolean type Salesforce object field
 */
class Type_Boolean extends Field_Type {

	/**
	 * Field type
	 *
	 * @var string
	 */
	protected $type = 'select';

	/**
	 * Field options
	 *
	 * @var array
	 */
	protected $options = [];

	/**
	 * List the field's options
	 *
	 * @return array|null
	 */
	public function list(): ?array {
		return [
			'no'  => __( 'No', 'aisc' ),
			'yes' => __( 'Yes', 'aisc' ),
		];
	}

}
