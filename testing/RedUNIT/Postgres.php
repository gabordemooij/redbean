<?php

class RedUNIT_Postgres extends RedUNIT {

	/*
	 * What drivers should be loaded for this test pack? 
	 */
	public function getTargetDrivers() {
		return array('pgsql');
	}
}