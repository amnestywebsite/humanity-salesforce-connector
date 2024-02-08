<?php

declare( strict_types = 1 );

namespace Amnesty\Salesforce;

/**
 * Settings handler object
 */
class Settings extends Option {

	/**
	 * The setting key
	 *
	 * @var string
	 */
	protected static $key = 'amnesty_salesforce_connector';

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
