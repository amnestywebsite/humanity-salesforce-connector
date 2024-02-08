<?php

declare( strict_types = 1 );

namespace Amnesty\Salesforce;

/**
 * Logger interface
 */
interface Logger {

	/**
	 * Log a message
	 *
	 * @param string  $type    the log type
	 * @param string  $message the log message
	 * @param integer $code    the log code
	 *
	 * @return void
	 */
	public function log( string $type = '', string $message = '', int $code = 500 ): void;

	/**
	 * Retrieve logs
	 *
	 * @param int $per_page logs per page to retrieve
	 * @param int $page the page of logs to retrieve
	 *
	 * @return array
	 */
	public function get( int $per_page = 10, int $page = 1 ): array;

	/**
	 * Retrieve log count
	 *
	 * @return integer
	 */
	public function count(): int;

}
