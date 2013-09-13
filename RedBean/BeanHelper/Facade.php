<?php
/**
 * Bean Helper.
 * The Bean helper helps beans to access access the toolbox and
 * FUSE models. This Bean Helper makes use of the facade to obtain a
 * reference to the toolbox.
 *
 * @file    RedBean/BeanHelperFacade.php
 * @desc    Finds the toolbox for the bean.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * (c) copyright G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_BeanHelper_Facade implements RedBean_BeanHelper
{

	/**
	 * @see RedBean_BeanHelper::getToolbox
	 */
	public function getToolbox()
	{
		return RedBean_Facade::$toolbox;
	}

	/**
	 * @see RedBean_BeanHelper::getModelForBean
	 */
	public function getModelForBean( RedBean_OODBBean $bean )
	{
		$modelName = RedBean_ModelHelper::getModelName( $bean->getMeta( 'type' ), $bean );

		if ( !class_exists( $modelName ) ) {
			return NULL;
		}

		$obj = RedBean_ModelHelper::factory( $modelName );
		$obj->loadBean( $bean );

		return $obj;
	}

	/**
	 * @see RedBean_BeanHelper::getExtractedToolbox
	 */
	public function getExtractedToolbox()
	{
		$toolbox = $this->getToolbox();

		return array( $toolbox->getRedBean(), $toolbox->getDatabaseAdapter(), $toolbox->getWriter(), $toolbox );
	}
}
