<?php

class RedBean_Validator_AlphaNumeric implements RedBean_Validator {
	public function check( $v ) {
		return (bool) preg_match('/^[A-Za-z0-9]+$/', $v);
	}
}