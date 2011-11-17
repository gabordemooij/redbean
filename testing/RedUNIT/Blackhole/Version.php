<?php

class RedUNIT_Blackhole_Version extends RedUNIT_Blackhole {
	

	public function getTargetDrivers() {
		return null;
	}
	
	public function run() {

		$version = R::getVersion();
		asrt(is_string($version),true);
				
	}
	
}