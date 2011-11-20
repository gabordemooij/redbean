<?php

class RedBean_SQLHelper {

	public function __call( $funcName, $args=array() ) {
		return RedBean_Facade::$adapter->getCell('SELECT '.$funcName.'('.implode(',',$args).')');	
	}

}