<?php
/**
 * Sieve
 * @package 		RedBean/Sieve.php
 * @description		Filters a bean
 * @author			Gabor de Mooij
 * @license			BSD
 */
class RedBean_Sieve {
	
	/**
	 * 
	 * @var array
	 */
	private $vals;
	
	/**
	 * 
	 * @var array
	 */
	private $report = array();
	
	/**
	 * 
	 * @var boolean
	 */
	private $succes = true;
	
	/**
	 * 
	 * @param $validations
	 * @return unknown_type
	 */
	public static function make( $validations ) {
		
		$sieve = new self;
		$sieve->vals = $validations;
		return $sieve;
			
	}
	
	/**
	 * 
	 * @param $deco
	 * @return unknown_type
	 */
	public function valid( RedBean_Decorator $deco ) {
	
		foreach($this->vals as $p => $v) {
			if (class_exists($v)) {
				$validator = new $v( $deco, $report );
				if ($validator instanceof RedBean_Validator) { 
					$message = $validator->check( $deco->$p );
					if ($message !== true) {
						$this->succes = false;
					}
					if (!isset($this->report[$v])) {
						$this->report[$v]=array();
					}
					$this->report[ $v ][ $p ] = $message;
						
				}
			}
		}
		return $this->succes;	
	}
	
	/**
	 * 
	 * @param $deco
	 * @param $key
	 * @return unknown_type
	 */
	public function validAndReport( RedBean_Decorator $deco, $key=false ) {
		$this->valid( $deco );
		if ($key) {
			if (isset($this->report[$key])) {
				return $this->report[$key];
			}
		}
		return $this->report;
	}
	
	/**
	 * 
	 * @return unknown_type
	 */
	public function getReport() {
		return $this->report;
	}
	
	
}