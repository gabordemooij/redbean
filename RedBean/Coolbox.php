<?php
 /**
 * @name RedBean Coolbox
 * @file RedBean
 * @author Gabor de Mooij and the RedBean Team
 * @copyright Gabor de Mooij (c)
 * @license BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_Coolbox extends RedBean_ToolBox {

	/**
	 * Constructor
	 *
	 * @param RedBean_OODB $oodb
	 * @param RedBean_Adapter $adapter
	 * @param RedBean_IceWriter $writer
	 */
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
