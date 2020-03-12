<?php

namespace RedBeanPHP;

use RedBeanPHP\ToolBox as ToolBox;
use RedBeanPHP\OODBBean as OODBBean;

/**
 * Bean Helper Interface.
 *
 * Interface for Bean Helper.
 * A little bolt that glues the whole machinery together.
 * The Bean Helper is passed to the OODB RedBeanPHP Object to
 * faciliatte the creation of beans and providing them with
 * a toolbox. The Helper also facilitates the FUSE feature,
 * determining how beans relate to their models. By overriding
 * the getModelForBean method you can tune the FUSEing to
 * fit your business application needs.
 *
 * @file    RedBeanPHP/IBeanHelper.php
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
interface BeanHelper
{
	/**
	 * Returns a toolbox to empower the bean.
	 * This allows beans to perform OODB operations by themselves,
	 * as such the bean is a proxy for OODB. This allows beans to implement
	 * their magic getters and setters and return lists.
	 *
	 * @return ToolBox
	 */
	public function getToolbox();

	/**
	 * Does approximately the same as getToolbox but also extracts the
	 * toolbox for you.
	 * This method returns a list with all toolbox items in Toolbox Constructor order:
	 * OODB, adapter, writer and finally the toolbox itself!.
	 *
	 * @return array
	 */
	public function getExtractedToolbox();

	/**
	 * Given a certain bean this method will
	 * return the corresponding model.
	 *
	 * @param OODBBean $bean bean to obtain the corresponding model of
	 *
	 * @return SimpleModel|CustomModel|NULL
	 */
	public function getModelForBean( OODBBean $bean );
}
