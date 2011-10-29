<?php
/**
 * RedBean Exception SQL
 *
 * @file				RedBean/Exception/SQL.php
 * @description	Represents a generic database exception independent of the
 *						underlying driver.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_Exception_SQL extends Exception {

	/**
	 * @var string
	 * Holds the current SQL Strate code.
	 */
	private $sqlState;

	/**
	 * Returns an ANSI-92 compliant SQL state.
	 *
	 * @return string $state ANSI state code
	 */
	public function getSQLState() {
		return $this->sqlState;
	}

	/**
	 * @todo parse state to verify valid ANSI92!
	 * Stores ANSI-92 compliant SQL state.
	 *
	 * @param string $sqlState code
	 */
	public function setSQLState( $sqlState ) {
		$this->sqlState = $sqlState;
	}


	/**
	 * To String prints both code and SQL state.
	 *
	 * @return string $message prints this exception instance as a string
	 */
	public function __toString() {
		return "[".$this->getSQLState()."] - ".$this->getMessage();
	}
}