<?php

class RedUNIT_Sqlite extends RedUNIT {

	/*
	 * What drivers should be loaded for this test pack? 
	 */
	public function getTargetDrivers() {
		return array('sqlite');
	}
}