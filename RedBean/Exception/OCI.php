<?php
class RedBean_Exception_OCI extends Exception {
	
	public function __toString()
	{
		return '[RedBean_Exception_OCI]:'.$this->getMessage();
	}
}
?>
