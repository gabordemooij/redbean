<?php

class RedBean_Sieve {
	
	private $vals;
	private $report = array();
	private $succes = true;
	
	
	public static function make( $validations ) {
		
		$sieve = new self;
		$sieve->vals = $validations;
		return $sieve;
			
	}
	
	public function valid( RedBean_Decorator $deco ) {
	
		foreach($this->vals as $p => $v) {
			if (class_exists($v)) {
				$validator = new $v( $deco, $report );
				if ($validator instanceof RedBean_Validator) { 
					$message = $validator->check( $deco->$p );
					if ($message !== true) {
						$this->succes = false;
					}
					if (!is_array($this->report[$v])) {
						$this->report[$v]=array();
					}
					$this->report[ $v ][ $p ] = $message;
						
				}
			}
		}
		return $this->succes;	
	}
	
	public function validAndReport( RedBean_Decorator $deco, $key=false ) {
		$this->valid( $deco );
		if ($key) {
			if (isset($this->report[$key])) {
				return $this->report[$key];
			}
		}
		return $this->report;
	}
	
	public function getReport() {
		return $this->report;
	}
	
	
	
	
}