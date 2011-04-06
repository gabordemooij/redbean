<?php
/**
 * Created by JetBrains PhpStorm.
 * User: prive
 * Date: 06-04-11
 * Time: 11:59
 * To change this template use File | Settings | File Templates.
 */
 
class RedBean_Coolbox extends RedBean_ToolBox {

	public function __construct( RedBean_OODB $oodb, RedBean_Adapter $adapter, RedBean_IceWriter $writer ) {

		if (!$oodb->isFrozen()) {
			$oodb->freeze(true);
		}

		$this->oodb = $oodb;
		$this->adapter = $adapter;
		$this->writer = $writer;
		return $this;
	}

}
