<?php
/**
 * Bean Helper Interface
 * 
 * @file			RedBean/IBeanHelper.php
 * @desc			Interface for Bean Helper.
 * @author			Gabor de Mooij and the RedBeanPHP Community
 * @license			BSD/GPLv2
 * 
 * Interface for Bean Helper.
 * A little bolt that glues the whole machinery together.
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 *
 */
interface RedBean_BeanHelper {
	/**
	 * @abstract
	 * @return RedBean_Toolbox $toolbox toolbox
	 */
	public function getToolbox();	
	/**
	 * Given a certain bean this method will
	 * return the corresponding model.
	 * 
	 * @param RedBean_OODBBean $bean
	 */
	public function getModelForBean(RedBean_OODBBean $bean);	
}