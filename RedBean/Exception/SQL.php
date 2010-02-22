<?php
/**
 * RedBean Exception SQL
 * @file			RedBean/Exception/SQL.php
 * @description		Represents a generic database exception independent of the
 *					underlying driver.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD license that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_Exception_SQL extends Exception {

	/**
	 * @var string
	 */
	private $sqlState;

	/**
	 * Returns an ANSI-92 compliant SQL state.
	 * @return string $state
	 */
	public function getSQLState() {
		return $this->sqlState;
	}

	/**
	 * @todo parse state to verify valid ANSI92!
	 * Stores ANSI-92 compliant SQL state.
	 * @param string $sqlState
	 */
	public function setSQLState( $sqlState ) {
		$this->sqlState = $sqlState;
	}


	/**
	 * To String prints both code and SQL state.
	 * @return string
	 */
	public function __toString() {
		return "[".$this->getSQLState()."] - ".$this->getMessage();
	}
}