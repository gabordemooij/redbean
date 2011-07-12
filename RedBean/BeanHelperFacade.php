<?php
/**
 * Created by JetBrains PhpStorm.
 * User: prive
 * Date: 11-07-11
 * Time: 08:25
 * To change this template use File | Settings | File Templates.
 */
 
class RedBean_BeanHelperFacade implements RedBean_IBeanHelper {

	/**
	 * @return RedBean_ToolBox $toolbox
	 */
	public function getToolbox() {
		return R::$toolbox;
	}
}
