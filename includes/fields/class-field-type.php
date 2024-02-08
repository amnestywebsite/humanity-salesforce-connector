<?php

declare( strict_types = 1 );

namespace Amnesty\Salesforce\Fields;

/**
 * Base class for Salesforce object field representation
 */
class Field_Type {

	/**
	 * Raw field data
	 *
	 * @var array
	 */
	protected $data = [];

	/**
	 * Field name
	 *
	 * @var string
	 */
	protected $name = '';

	/**
	 * Field type
	 *
	 * @var string
	 */
	protected $type = 'text';

	/**
	 * Field subtype
	 *
	 * @var string
	 */
	protected $subtype = '';

	/**
	 * Field label
	 *
	 * @var string
	 */
	protected $label = '';

	/**
	 * Set field data
	 *
	 * @param array $field the raw field data
	 */
	public function __construct( array $field ) {
		$this->data    = $field;
		$this->name    = $field['name'];
		$this->label   = $field['label'];
		$this->subtype = $field['type'];

		$this->boot();
	}

	/**
	 * Return the field's name
	 *
	 * @return string
	 */
	public function name(): string {
		return $this->name;
	}

	/**
	 * Return the field's type
	 *
	 * @return string
	 */
	public function type(): string {
		return $this->type;
	}

	/**
	 * Return the field's subtype
	 *
	 * @return string
	 */
	public function subtype(): string {
		return $this->subtype;
	}

	/**
	 * Return the field's singular label
	 *
	 * @return string
	 */
	public function label(): string {
		return $this->label;
	}

	/**
	 * List the field's options
	 *
	 * @return array|null
	 */
	public function list(): ?array {
		return null;
	}

	/**
	 * Run any type-specific setup
	 *
	 * @return void
	 */
	protected function boot(): void {
	}

}
