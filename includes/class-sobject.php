<?php

declare( strict_types = 1 );

namespace Amnesty\Salesforce;

use Amnesty\Salesforce\Fields\Field_Type;

/**
 * Salesforce Object representation
 */
class SObject {

	/**
	 * Raw sObject data
	 *
	 * @var array
	 */
	protected $data = [];

	/**
	 * The sObject name
	 *
	 * @var string
	 */
	protected $name = '';

	/**
	 * The sObject label
	 *
	 * @var string
	 */
	protected $label = '';

	/**
	 * The sObject fields
	 *
	 * @var array
	 */
	protected $fields = [];

	/**
	 * The sObject description
	 *
	 * @var array
	 */
	protected $describe = [];

	/**
	 * Set instance variables
	 *
	 * @param string $name the object name
	 * @param array  $data the object data
	 */
	public function __construct( string $name, array $data = [] ) {
		$this->name  = $name;
		$this->data  = $data;
		$this->label = $this->data['label'];
	}

	/**
	 * List the sObject's fields
	 *
	 * @return array
	 */
	public function list(): array {
		$this->init();

		$options = [];

		foreach ( $this->fields as $name => $object ) {
			$options[ $name ] = $object->label();
		}

		natsort( $options );

		return [ '~' => __( 'None', 'cmb2' ) ] + $options;
	}

	/**
	 * Whether sObject has field
	 *
	 * @param string $name the field name
	 *
	 * @return boolean
	 */
	public function has( string $name ): bool {
		$this->init();

		return isset( $this->fields[ $name ] );
	}

	/**
	 * Retrieve a field from the sObject
	 *
	 * @param string $name the field to retrieve
	 *
	 * @return Field_Type|null
	 */
	public function get( string $name ): ?Field_Type {
		$this->init();

		return $this->has( $name ) ? $this->fields[ $name ] : null;
	}

	/**
	 * Retrieve all fields from the sObject
	 *
	 * @return array
	 */
	public function fields(): array {
		$this->init();

		return $this->fields;
	}

	/**
	 * Retrieve all field names from the sObject
	 *
	 * @return array
	 */
	public function field_names(): array {
		return array_keys( $this->fields() );
	}

	/**
	 * Return the sObject's name
	 *
	 * @return string
	 */
	public function name(): string {
		return $this->name;
	}

	/**
	 * Return the sObject's label
	 *
	 * @return string
	 */
	public function label(): string {
		return $this->label;
	}

	/**
	 * Return the raw sObject describe response
	 *
	 * @return array
	 */
	public function describe(): array {
		return $this->describe;
	}

	/**
	 * Retrieve the sObject from Salesforce
	 *
	 * @return void
	 */
	protected function init(): void {
		if ( count( $this->fields ) > 0 ) {
			return;
		}

		$data = wp_cache_get( $this->name, 'aisc' );

		if ( false === $data ) {
			$data = Request::get( '/sobjects/' . $this->name . '/describe/' );
			wp_cache_add( $this->name, $data, 'aisc' );
		}

		$this->label    = $data['label'] ?? $this->name;
		$this->describe = $data;

		$fields = array_column( $data['fields'] ?? [], null, 'name' );
		foreach ( $fields as $name => $field ) {
			$fields[ $name ] = SObject_Field::new( $field );
		}

		$this->fields = $fields;
	}

}
