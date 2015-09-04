<?php

namespace RedBeanPHP\Logger;

use RedBeanPHP\Logger as Logger;
use RedBeanPHP\RedException as RedException;

/**
 * Logger. Provides a basic logging function for RedBeanPHP.
 *
 * @file    RedBeanPHP/Logger.php
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RDefault implements Logger
{
	/**
	 * Logger modes
	 */
	const C_LOGGER_ECHO  = 0;
	const C_LOGGER_ARRAY = 1;

	/**
	 * @var integer
	 */
	protected $mode = 0;

	/**
	 * @var array
	 */
	protected $logs = array();

	/**
	 * Default logger method logging to STDOUT.
	 * This is the default/reference implementation of a logger.
	 * This method will write the message value to STDOUT (screen) unless
	 * you have changed the mode of operation to C_LOGGER_ARRAY.
	 *
	 * @param $message (optional) message to log (might also be data or output)
	 *
	 * @return void
	 */
	public function log()
	{
		if ( func_num_args() < 1 ) return;

		foreach ( func_get_args() as $argument ) {
			if ( is_array( $argument ) ) {
				$log = print_r( $argument, TRUE );
				if ( $this->mode === self::C_LOGGER_ECHO ) {
					echo $log;
				} else {
					$this->logs[] = $log;
				}
			} else {
				if ( $this->mode === self::C_LOGGER_ECHO ) {
					echo $argument;
				} else {
					$this->logs[] = $argument;
				}
			}

			if ( $this->mode === self::C_LOGGER_ECHO ) echo "<br>" . PHP_EOL;
		}
	}

	/**
	 * Returns the internal log array.
	 * The internal log array is where all log messages are stored.
	 *
	 * @return array
	 */
	public function getLogs()
	{
		return $this->logs;
	}

	/**
	 * Clears the internal log array, removing all
	 * previously stored entries.
	 *
	 * @return self
	 */
	public function clear()
	{
		$this->logs = array();
		return $this;
	}

	/**
	 * Selects a logging mode.
	 * There are several options available.
	 *
	 * * C_LOGGER_ARRAY - log silently, stores entries in internal log array only
	 * * C_LOGGER_ECHO  - also forward log messages directly to STDOUT
	 *
	 * @param integer $mode mode of operation for logging object
	 *
	 * @return self
	 */
	public function setMode( $mode )
	{
		if ($mode !== self::C_LOGGER_ARRAY && $mode !== self::C_LOGGER_ECHO ) {
			throw new RedException( 'Invalid mode selected for logger, use C_LOGGER_ARRAY or C_LOGGER_ECHO.' );
		}
		$this->mode = $mode;
		return $this;
	}

	/**
	 * Searches for all log entries in internal log array
	 * for $needle and returns those entries.
	 * This method will return an array containing all matches for your
	 * search query.
	 *
	 * @param string $needle phrase to look for in internal log array
	 *
	 * @return array
	 */
	public function grep( $needle )
	{
		$found = array();
		foreach( $this->logs as $logEntry ) {
			if ( strpos( $logEntry, $needle ) !== FALSE ) $found[] = $logEntry;
		}
		return $found;
	}
}
