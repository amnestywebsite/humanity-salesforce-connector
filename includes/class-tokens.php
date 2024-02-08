<?php

declare( strict_types = 1 );

namespace Amnesty\Salesforce;

/**
 * API Tokens handler
 */
class Tokens extends Option {

	/**
	 * The option key
	 *
	 * @var string
	 */
	protected static $key = 'amnesty_salesforce_tokens';

	/**
	 * Instance variable
	 *
	 * @var static
	 */
	protected static $instance = null;

	/**
	 * Option data
	 *
	 * @var array
	 */
	protected static $option = [];

}
