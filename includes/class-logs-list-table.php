<?php

declare( strict_types = 1 );

namespace Amnesty\Salesforce;

use WP_List_Table;

/**
 * Logger list table handler
 */
class Logs_List_Table extends WP_List_Table {

	/**
	 * Logger instance
	 *
	 * @var \Amnesty\Salesforce\AbstractLogger
	 */
	protected $logger = null;

	/**
	 * Set up list table
	 *
	 * @param AbstractLogger $logger logger instance
	 */
	public function __construct( AbstractLogger $logger ) {
		$this->logger = $logger;

		parent::__construct(
			[
				'singular' => __( 'Log', 'aisc' ),
				'plural'   => __( 'Logs', 'aisc' ),
				'screen'   => 'aisc_logs_per_page',
				'ajax'     => false,
			] 
		);
	}

	/**
	 * Column list
	 *
	 * @return array
	 */
	public function get_columns(): array {
		return [
			'id'        => __( 'ID', 'aisc' ),
			'timestamp' => __( 'Timestamp', 'aisc' ),
			'severity'  => __( 'Severity', 'aisc' ),
			'message'   => __( 'Message', 'aisc' ),
			'trace'     => __( 'Stacktrace', 'aisc' ),
		];
	}

	/**
	 * Render ID column value
	 *
	 * @param array $log the log line to render
	 *
	 * @return string
	 */
	public function column_id( array $log = [] ): string {
		return $log['id'];
	}

	/**
	 * Render timestamp column value
	 *
	 * @param array $log the log line to render
	 *
	 * @return string
	 */
	public function column_timestamp( array $log = [] ): string {
		return $log['timestamp'];
	}

	/**
	 * Render severity column value
	 *
	 * @param array $log the log line to render
	 *
	 * @return string
	 */
	public function column_severity( array $log = [] ): string {
		return ucfirst( $log['severity'] ?? 'error' );
	}

	/**
	 * Render message column value
	 *
	 * @param array $log the log line to render
	 *
	 * @return string
	 */
	public function column_message( array $log = [] ): string {
		return $log['message'] ?? '-';
	}

	/**
	 * Render backtrace column value
	 *
	 * @param array $log the log line to render
	 *
	 * @return string
	 */
	public function column_trace( array $log = [] ): string {
		if ( ! isset( $log['trace'] ) ) {
			return '-';
		}

		$lines = explode( "\n", $log['trace'] );

		return $lines[0];
	}

	/**
	 * Retrieve log line items
	 *
	 * @return void
	 */
	public function prepare_items(): void {
		$per_page = $this->get_items_per_page( 'aisc_logs_per_page' );
		$page     = $this->get_pagenum();
		$count    = $this->logger->count();

		$this->set_pagination_args(
			[
				'total_items' => $count,
				'per_page'    => $per_page,
			] 
		);


		$this->items = $this->logger->get( $per_page, $page );
	}

}
