<?php 

namespace RedBeanPHP\Logger;

use RedBeanPHP\Logger as Logger;
use RedBeanPHP\RedException as RedException;
use RedBeanPHP\RedException\Security as Security;

/**
 * Logger. Provides a basic logging function for RedBeanPHP.
 *
 * @file    RedBeanPHP/Logger.php
 * @desc    Logger
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * Provides a basic logging function for RedBeanPHP.
 *
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
	 * This method will write the message value to STDOUT (screen).
	 *
	 * @param $message (optional)
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

			if ( $this->mode === self::C_LOGGER_ECHO ) echo "<br>\n";
		}
	}
	
	/**
	 * Returns the logs array.
	 * 
	 * @return array
	 */
	public function getLogs()
	{
		return $this->logs;
	}
	
	/**
	 * Empties the logs array.
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
	 * Mode 0 means echoing all statements, while mode 1
	 * means populating the logs array.
	 * 
	 * @param integer $mode mode
	 * 
	 * @return self
	 */
	public function setMode( $mode )
	{
		if ($mode !== self::C_LOGGER_ARRAY && $mode !== self::C_LOGGER_ECHO ) {
			throw new RedException( 'Invalid mode selected for logger, use 1 or 0.' );
		}
		$this->mode = $mode;
		return $this;
	}
	
	/**
	 * Searches for all log entries in internal log array
	 * for $needle and returns those entries.
	 * 
	 * @param string $needle needle
	 * 
	 * @return array
	 */
	public function grep( $needle )
	{
		$found = array();
		foreach( $this->logs as $logEntry ) {
			if (strpos( $logEntry, $needle ) !== false) $found[] = $logEntry;
		}
		return $found;
	}
}
