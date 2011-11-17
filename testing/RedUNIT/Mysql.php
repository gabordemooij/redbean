<?php


class RedUNIT_Mysql extends RedUNIT {
	/*
	 * What drivers should be loaded for this test pack? 
	 */
	public function getTargetDrivers() {
		return array('mysql');
	}
}