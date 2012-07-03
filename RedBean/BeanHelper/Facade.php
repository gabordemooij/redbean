<?php
/**
 * RedBean Bean Helper
 * @file			RedBean/BeanHelperFacade.php
 * @description		Finds the toolbox for the bean.
 * @author			Gabor de Mooij and the RedBeanPHP Community
 * @license			BSD/GPLv2
 *
 * (c) copyright G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_BeanHelperFacade implements RedBean_IBeanHelper {

	/**
	 * Returns a reference to the toolbox. This method returns a toolbox
	 * for beans that need to use toolbox functions. Since beans can contain
	 * lists they need a toolbox to lazy-load their relationships.
	 *  
	 * @return RedBean_ToolBox $toolbox toolbox containing all kinds of goodies
	 */
	public function getToolbox() {
		return RedBean_Facade::$toolbox;
	}
	
	/**
	 * Fuse connector.
	 * Gets the model for a bean $bean.
	 * Allows you to implement your own way to find the
	 * right model for a bean and to do dependency injection
	 * etc.
	 *
	 * @param RedBean_OODBBean $bean bean
	 *  
	 * @return type 
	 */
	public function getModelForBean(RedBean_OODBBean $bean) {
		$modelName = RedBean_ModelHelper::getModelName( $bean->getMeta('type'), $bean );
		if (!class_exists($modelName)) return null;
		$obj = RedBean_ModelHelper::factory($modelName);
		$obj->loadBean($bean);
		return $obj;
	}
	
}
