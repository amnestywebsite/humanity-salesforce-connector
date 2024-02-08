<?php

declare( strict_types = 1 );

namespace Amnesty\Salesforce;

/**
 * General petitions Exception class
 */
class Exception extends \Exception {

	/**
	 * Error severity
	 *
	 * @var string
	 */
	protected $severity = 'error';

	/**
	 * Construct the exception
	 *
	 * @param string  $message  the error message
	 * @param string  $severity the error severity
	 * @param integer $code     the error code
	 */
	public function __construct( string $message = '', string $severity = '', int $code = 500 ) {
		$this->severity = $severity;

		parent::__construct( $message, $code, null );
	}

	/**
	 * Get error severity
	 *
	 * @return string
	 */
	public function getSeverity(): string {
		return $this->severity;
	}

	/**
	 * Stringify class instance
	 *
	 * @return string
	 */
	public function __toString(): string {
		return sprintf( '%s: %s', ucfirst( $this->getSeverity() ), $this->getMessage() );
	}

}
