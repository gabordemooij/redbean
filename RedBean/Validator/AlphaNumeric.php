<?php
/**
 * RedBean Validator Alphanumeric
 * @package 		RedBean/Validator/AlphaNumeric.php
 * @description		Checks whether a value is alpha numeric
 * @author			Gabor de Mooij
 * @license			BSD
 */
class RedBean_Validator_AlphaNumeric implements RedBean_Validator {
	/**
	 * (non-PHPdoc)
	 * @see RedBean/RedBean_Validator#check()
	 */
	public function check( $v ) {
		return (bool) preg_match('/^[A-Za-z0-9]+$/', $v);
	}
}