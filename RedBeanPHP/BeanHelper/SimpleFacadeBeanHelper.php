<?php

namespace RedBeanPHP\BeanHelper;

use RedBeanPHP\SimpleModel;
use RedBeanPHP\BeanHelperInterface as BeanHelperInterface;
use RedBeanPHP\Facade as Facade;
use RedBeanPHP\OODBBean as OODBBean;

/**
 * Bean Helper.
 *
 * The Bean helper helps beans to access access the toolbox and
 * FUSE models. This Bean Helper makes use of the facade to obtain a
 * reference to the toolbox.
 *
 * @file    RedBeanPHP/BeanHelperFacade.php
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * @copyright
 * (c) copyright G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class SimpleFacadeBeanHelper implements BeanHelperInterface
{
	/**
	 * Factory function to create instance of Simple Model, if any.
	 *
	 * @var \Closure
	 */
	private static $factory = null;

	/**
	 * Factory method using a customizable factory function to create
	 * the instance of the Simple Model.
	 *
	 * @param string $modelClassName name of the class
	 *
	 * @return SimpleModel
	 */
	public static function factory( $modelClassName )
	{
		$factory = self::$factory;
		return ( $factory ) ? $factory( $modelClassName ) : new $modelClassName();
	}

	/**
	 * Sets the factory function to create the model when using FUSE
	 * to connect a bean to a model.
	 *
	 * @param \Closure $factory
	 *
	 * @return void
	 */
	public static function setFactoryFunction( $factory ) 
	{
		self::$factory = $factory;
	}

	/**
	 * @see BeanHelperInterface::getToolbox
	 */
	public function getToolbox()
	{
		return Facade::getToolBox();
	}

	/**
	 * @see BeanHelperInterface::getModelForBean
	 * @param OODBBean $bean;
	 * @return self::factory()
	 */
	public function getModelForBean( OODBBean $bean )
	{
		$model     = $bean->getMeta( 'type' );
		$prefix    = defined( 'REDBEAN_MODEL_PREFIX' ) ? REDBEAN_MODEL_PREFIX : '\\Model_';

		if ( strpos( $model, '_' ) !== FALSE ) {
			$modelParts = explode( '_', $model );
			$modelName = '';
			foreach( $modelParts as $part ) {
				$modelName .= ucfirst( $part );
			}
			$modelName = $prefix . $modelName;

			if ( !class_exists( $modelName ) ) {
				//second try
				$modelName = $prefix . ucfirst( $model );
				
				if ( !class_exists( $modelName ) ) {
					return NULL;
				}
			}

		} else {

			$modelName = $prefix . ucfirst( $model );
			if ( !class_exists( $modelName ) ) {
				return NULL;
			}
		}
		$obj = self::factory( $modelName );
		$obj->loadBean( $bean );

		return $obj;
	}

	/**
	 * @see BeanHelperInterface::getExtractedToolbox
	 */
	public function getExtractedToolbox()
	{
		return Facade::getExtractedToolbox();
	}
}
