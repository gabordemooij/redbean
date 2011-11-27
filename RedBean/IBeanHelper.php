<?php
/**
 * RedBean Bean Helper Interface
 * 
 * @file			RedBean/IBeanHelper.php
 * @description		Interface for Bean Helper.
 *					A little bolt that glues the whole machinery together.
 * 		
 * @author			Gabor de Mooij
 * @license			BSD
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij
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
}
