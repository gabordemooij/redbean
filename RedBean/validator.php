<?php
/**
 * RedBean Validator
 * @package 		RedBean/Validator.php
 * @description		API for Validators
 * @author			Gabor de Mooij
 * @license			BSD
 */
interface RedBean_Validator {
	/**
	 * 
	 * @param $property
	 * @return unknown_type
	 */
	public function check( $property );
}