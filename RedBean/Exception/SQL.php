<?php
/**
 * RedBean Exception SQL
 * @package 		RedBean/Exception/SQL.php
 * @description		Represents a generic database exception independent of the
 *					underlying driver.
 * @author			Gabor de Mooij
 * @license			BSD
 */
class RedBean_Exception_SQL extends Exception {


	/**
	 *
	 * @var string
	 */
	private $sqlState;

	/**
	 * Returns an ANSI-92 compliant SQL state
	 * @return string $state
	 */
	public function getSQLState() {
		return $this->sqlState;
	}

	/**
	 * @todo parse state to verify valid ANSI92!
	 * Stores ANSI-92 compliant SQL state
	 * @param string $sqlState
	 */
	public function setSQLState( $sqlState ) {
		$this->sqlState = $sqlState;
	}

	
	public function __toString() {
		echo "[".$this->getSQLState()."] - ".$this->getMessage();
	}
}