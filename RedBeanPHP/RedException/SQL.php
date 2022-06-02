<?php

namespace RedBeanPHP\RedException;

use RedBeanPHP\RedException as RedException;

/**
 * SQL Exception.
 * Represents a generic database exception independent of the underlying driver.
 *
 * @file       RedBeanPHP/RedException/SQL.php
 * @author     Gabor de Mooij and the RedBeanPHP Community
 * @license    BSD/GPLv2
 *
 * @copyright
 * (c) copyright G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class SQL extends RedException
{
	/**
	 * @var string
	 */
	private $sqlState;

	/**
	 * @var array
	 */
	private $driverDetails = array();

	/**
	 * @return array
	 */
	public function getDriverDetails()
	{
		return $this->driverDetails;
	}

	/**
	 * @param array $driverDetails
	 *
	 * @return void
	 */
	public function setDriverDetails($driverDetails)
	{
		$this->driverDetails = $driverDetails;
	}

	/**
	 * Returns an ANSI-92 compliant SQL state.
	 *
	 * @return string
	 */
	public function getSQLState()
	{
		return $this->sqlState;
	}

	/**
	 * Returns the raw SQL STATE, possibly compliant with
	 * ANSI SQL error codes - but this depends on database driver.
	 *
	 * @param string $sqlState SQL state error code
	 *
	 * @return void
	 */
	public function setSQLState( $sqlState )
	{
		$this->sqlState = $sqlState;
	}

	/**
	 * To String prints both code and SQL state.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return '[' . $this->getSQLState() . '] - ' . $this->getMessage()."\n".
				'trace: ' . $this->getTraceAsString();
	}
}
