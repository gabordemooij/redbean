<?php 
namespace RedBeanPHP\BeanHelper; 
use \RedBeanPHP\BeanHelper as BeanHelper;
use \RedBeanPHP\Facade as Facade;
use \RedBeanPHP\OODBBean as OODBBean;
use \RedBeanPHP\ModelHelper as ModelHelper; 
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
class FacadeBeanHelper implements BeanHelper
{

	/**
	 * @see BeanHelper::getToolbox
	 */
	public function getToolbox()
	{
		return Facade::$toolbox;
	}

	/**
	 * @see BeanHelper::getModelForBean
	 */
	public function getModelForBean( OODBBean $bean )
	{
		$modelName = ModelHelper::getModelName( $bean->getMeta( 'type' ), $bean );

		if ( !class_exists( $modelName ) ) {
			return NULL;
		}

		$obj = ModelHelper::factory( $modelName );
		$obj->loadBean( $bean );

		return $obj;
	}

	/**
	 * @see BeanHelper::getExtractedToolbox
	 */
	public function getExtractedToolbox()
	{
		$toolbox = $this->getToolbox();

		return array( $toolbox->getRedBean(), $toolbox->getDatabaseAdapter(), $toolbox->getWriter(), $toolbox );
	}
}
