<?php

declare( strict_types = 1 );

namespace Amnesty\Salesforce;

if ( ! function_exists( 'dbDelta' ) ) {
	require_once ABSPATH . '/wp-admin/includes/upgrade.php';
}

/**
 * Database logger class
 */
class Database_Logger extends AbstractLogger {

	/**
	 * The database table to use
	 *
	 * @var string
	 */
	protected static $tablename = 'aisc_logs';

	/**
	 * No-op
	 */
	protected function __construct() {
		// do nothing
	}

	/**
	 * Log a message to the database
	 *
	 * @param string  $type    the message type
	 * @param string  $message the log message
	 * @param integer $code    the log code
	 *
	 * @return void
	 */
	public function log( string $type = '', string $message = '', int $code = 500 ): void {
		global $wpdb;

		ob_start();
		// phpcs:ignore
		debug_print_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
		$backtrace = $this->format_trace( ob_get_clean() );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$wpdb->prefix . static::$tablename,
			[
				'timestamp' => gmdate( 'Y-m-d H:i:s', time() ),
				'code'      => $code,
				'severity'  => $type,
				'message'   => $message,
				'trace'     => $backtrace,
			]
		);
	}

	/**
	 * Strip cruft from trace to reduce verbosity and db storage
	 *
	 * @param string $trace the backtrace to format
	 *
	 * @return string
	 */
	protected function format_trace( string $trace = '' ): string {
		// convert to array for line manipulation
		$trace = explode( PHP_EOL, $trace );

		// pop the callers (logger) off
		array_shift( $trace );
		array_shift( $trace );

		// decrement a call stack number
		$line_no = function ( array $matches = [] ): string {
			return '#' . ( --$matches[1] );
		};

		// phpcs:disable Generic.Formatting.MultipleStatementAlignment.NotSameWarning
		// readjust call stack numbers
		$lines = function ( string $line = '' ) use ( $line_no ): string {
			return preg_replace_callback( '/^#(\d+)\s/', $line_no, $line );
		};

		// strip extraneous path levels from file names
		$paths = function ( string $line = '' ): string {
			return str_replace( trailingslashit( WP_CONTENT_DIR ), '', $line );
		};

		$trace = array_map( $lines, $trace );
		$trace = array_map( $paths, $trace );
		// phpcs:enable Generic.Formatting.MultipleStatementAlignment.NotSameWarning

		// convert back to string
		return implode( PHP_EOL, $trace );
	}

	/**
	 * Retrieve logs
	 *
	 * @param int $limit logs per page to retrieve
	 * @param int $page  the page of logs to retrieve
	 *
	 * @return array
	 */
	public function get( int $limit = 10, int $page = 1 ): array {
		global $wpdb;

		$table  = $wpdb->prefix . static::$tablename;
		$offset = ( $page * $limit ) - $limit;

		$cache_key = md5( sprintf( '%s:%s:%s', $table, $page, $offset ) );
		$cached    = wp_cache_get( $cache_key, 'aisc_db' );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$results = $wpdb->get_results(
			$wpdb->prepare(
				// table name needs to be dynamic here, cos wpdb encloses it in single quotes otherwise
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT * FROM {$table} ORDER BY timestamp DESC LIMIT %d, %d",
				$offset,
				$limit
			),
			ARRAY_A
		);

		if ( is_array( $results ) ) {
			wp_cache_add( $cache_key, $results, 'aisc_db' );
		}

		return $results;
	}

	/**
	 * Retrieve log count
	 *
	 * @return integer
	 */
	public function count(): int {
		global $wpdb;

		$table = $wpdb->prefix . static::$tablename;

		// phpcs:ignore
		return absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ) );
	}

	/**
	 * Create DB table on activation
	 *
	 * @return void
	 */
	public static function up(): void {
		global $wpdb;

		$table  = $wpdb->prefix . static::$tablename;
		$create = "CREATE TABLE `{$table}` (
			`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			`timestamp` datetime NOT NULL,
			`code` int(3) NOT NULL,
			`severity` varchar(10) NOT NULL,
			`message` longtext COLLATE {$wpdb->collate},
			`trace` longtext COLLATE {$wpdb->collate},
			PRIMARY KEY (`id`),
			KEY `severity` (`severity`)
		) ENGINE=InnoDB DEFAULT CHARSET={$wpdb->charset} COLLATE={$wpdb->collate};";

		// phpcs:ignore
		dbDelta( $create );
	}

	/**
	 * Delete DB table on deactivation
	 *
	 * @return void
	 */
	public static function down(): void {
		global $wpdb;

		$table = $wpdb->prefix . static::$tablename;

		// phpcs:ignore
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
	}

}
