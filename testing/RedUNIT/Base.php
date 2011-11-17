<?php

class RedUNIT_Base extends RedUNIT {

	/**
	 * What drivers should be loaded for this test pack? 
	 */
	public function getTargetDrivers() {
		return array('mysql','pgsql','sqlite');
	}

}