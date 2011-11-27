<?php
/**
 * RedBean Bean Helper
 * @file			RedBean/BeanHelperFacade.php
 * @description		Finds the toolbox for the bean.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 * copyright(c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_BeanHelperFacade implements RedBean_IBeanHelper {

	/**
	 * @return RedBean_ToolBox $toolbox
	 */
	public function getToolbox() {
		return RedBean_Facade::$toolbox;
	}
}
