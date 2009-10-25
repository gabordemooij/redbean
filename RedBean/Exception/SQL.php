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
	 * Returns an ANSI-92 compliant SQL state
	 * @return string $state
	 */
	public function getSQLState() {
		return $this->getMessage();
	}
}