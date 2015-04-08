<?php

namespace RedBeanPHP;

/**
 * Bean Helper Interface.
 * 
 * Interface for Bean Helper.
 * A little bolt that glues the whole machinery together.
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
interface IBeanHelper
{
	/**
	 * Returns a toolbox to empower the bean.
	 * This allows beans to perform OODB operations by themselves,
	 * as such the bean is a proxy for OODB. This allows beans to implement
	 * their magic getters and setters and return lists.
	 *
	 * @return ToolBox $toolbox toolbox
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
	 * @param OODBBean $bean
	 *
	 * @return object
	 */
	public function getModelForBean( OODBBean $bean );
}
