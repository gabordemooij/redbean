<?php
class RedBean_Exception_OCI extends RedBean_Exception_SQL {
	
	public function __toString()
	{
		return '[RedBean_Exception_OCI]:'.$this->getMessage();
	}
}
?>
