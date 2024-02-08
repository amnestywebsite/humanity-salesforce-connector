<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName

declare( strict_types = 1 );

namespace Amnesty\Salesforce;

/**
 * Logger base class
 */
abstract class AbstractLogger extends Singleton implements Logger {

	/**
	 * Instance variable
	 *
	 * @var static
	 */
	protected static $instance = null;

	/**
	 * Log an error
	 *
	 * @param string  $message the error message
	 * @param integer $code    the error code
	 *
	 * @return void
	 */
	public function error( string $message = '', int $code = 500 ): void {
		$this->log( 'Error', $message, $code );
	}

	/**
	 * Log a warning
	 *
	 * @param string  $message the warning message
	 * @param integer $code    the warning code
	 *
	 * @return void
	 */
	public function warning( string $message = '', int $code = 500 ): void {
		$this->log( 'Warning', $message, $code );
	}

	/**
	 * Log info
	 *
	 * @param string  $message the info message
	 * @param integer $code    the info code
	 *
	 * @return void
	 */
	public function info( string $message = '', int $code = 500 ): void {
		$this->log( 'Info', $message, $code );
	}

	/**
	 * Log a message
	 *
	 * @param string  $type    the message type
	 * @param string  $message the log message
	 * @param integer $code    the log code
	 *
	 * @return void
	 */
	abstract public function log( string $type = '', string $message = '', int $code = 500 ): void;

	/**
	 * Retrieve logs
	 *
	 * @param int $per_page logs per page to retrieve
	 * @param int $page the page of logs to retrieve
	 *
	 * @return array
	 */
	abstract public function get( int $per_page = 10, int $page = 1 ): array;

	/**
	 * Retrieve log count
	 *
	 * @return integer
	 */
	abstract public function count(): int;

}
