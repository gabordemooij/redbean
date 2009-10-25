<?php

class RedBean_Exception_SQL extends Exception {
	public function getSQLState() {
		return $this->getMessage();
	}
}