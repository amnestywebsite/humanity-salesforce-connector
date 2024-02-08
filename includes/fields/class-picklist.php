<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName

declare( strict_types = 1 );

namespace Amnesty\Salesforce\Fields;

/**
 * Representation of picklist type Salesforce object field
 */
class Type_Picklist extends Field_Type {

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
		if ( 0 === count( $this->options ) ) {
			return [
				'~' => __( 'None', 'cmb2' ),
			];
		}

		$list = array_column( $this->options, 'value', 'label' );

		natsort( $list );

		return [ '~' => __( 'None', 'cmb2' ) ] + $list;
	}

	/**
	 * Run any type-specific setup
	 *
	 * @return void
	 */
	protected function boot(): void {
		$this->options = $this->data['picklistValues'] ?? [];
	}

}
