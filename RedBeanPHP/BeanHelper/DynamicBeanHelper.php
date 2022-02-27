<?php

namespace RedBeanPHP\BeanHelper;

use RedBeanPHP\BeanHelper as BeanHelper;
use RedBeanPHP\Facade as Facade;
use RedBeanPHP\OODBBean as OODBBean;
use RedBeanPHP\SimpleModelHelper as SimpleModelHelper;
use RedBeanPHP\BeanHelper\SimpleFacadeBeanHelper as SimpleFacadeBeanHelper;

/**
 * Dynamic Bean Helper.
 *
 * The dynamic bean helper allows you to use differently namespaced
 * classes for models per database connection.
 *
 * @file    RedBeanPHP/BeanHelper/DynamicBeanHelper.php
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * @copyright
 * (c) copyright G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class DynamicBeanHelper extends SimpleFacadeBeanHelper implements BeanHelper
{
	/**
	 * Model prefix to be used for the current database connection.
	 *
	 * @var string
	 */
	private $modelPrefix;

	/**
	 * Constructor
	 *
	 * Usage:
	 *
	 * <code>
	 * R::addDatabase( ..., new DynamicBeanHelper('Prefix1_')  );
	 * </code>
	 *
	 * @param string $modelPrefix prefix
	 */
	public function __construct( $modelPrefix ) {
		$this->modelPrefix = $modelPrefix;
	}

	/**
	 * @see BeanHelper::getModelForBean
	 */
	public function getModelForBean( OODBBean $bean )
	{
		return $this->resolveModel( $this->modelPrefix, $bean->getMeta( 'type' ), $bean );
	}	
}
