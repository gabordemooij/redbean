<?php
/**
 * RedBean Bean Helper Interface
 * 
 * @file			RedBean/IBeanHelper.php
 * @description		Interface for Bean Helper.
 *					A little bolt that glues the whole machinery together.
 * 		
 * @author			Gabor de Mooij and the RedBeanPHP Community
 * @license			BSD/GPLv2
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 *
 */
interface RedBean_IBeanHelper {

	/**
	 * @abstract
	 * @return RedBean_Toolbox $toolbox toolbox
	 */
	public function getToolbox();
	
	public function getModelForBean(RedBean_OODBBean $bean);
	
}
