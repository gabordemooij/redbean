<?php

namespace RedBeanPHP;

use RedBeanPHP\OODBBean as OODBBean;

/**
 * SimpleModel
 * Base Model For All RedBeanPHP Models using FUSE.
 *
 * RedBeanPHP FUSE is a mechanism to connect beans to posthoc
 * models. Models are connected to beans by naming conventions.
 * Actions on beans will result in actions on models.
 *
 * @file       RedBeanPHP/SimpleModel.php
 * @author     Gabor de Mooij and the RedBeanPHP Team
 * @license    BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class SimpleModel implements SimpleModelInterface
{
	/**
	 * @var OODBBean
	 */
	protected $bean;

	/**
	 * Used by FUSE: the ModelHelper class to connect a bean to a model.
	 * This method loads a bean in the model.
	 *
	 * @param OODBBean $bean bean to load
	 *
	 * @return void
	 */
	public function loadBean( OODBBean $bean )
	{
		$this->bean = $bean;
	}

	/**
	 * Magic Getter to make the bean properties available from
	 * the $this-scope.
	 *
	 * @note this method returns a value, not a reference!
	 *       To obtain a reference unbox the bean first!
	 *
	 * @param string $prop property to get
	 *
	 * @return mixed
	 */
	public function __get( $prop )
	{
		return $this->bean->$prop;
	}

	/**
	 * Magic Setter.
	 * Sets the value directly as a bean property.
	 *
	 * @param string $prop  property to set value of
	 * @param mixed  $value value to set
	 *
	 * @return void
	 */
	public function __set( $prop, $value )
	{
		$this->bean->$prop = $value;
	}

	/**
	 * Isset implementation.
	 * Implements the isset function for array-like access.
	 *
	 * @param  string $key key to check
	 *
	 * @return boolean
	 */
	public function __isset( $key )
	{
		return isset( $this->bean->$key );
	}

	/**
	 * Box the bean using the current model.
	 * This method wraps the current bean in this model.
	 * This method can be reached using FUSE through a simple
	 * OODBBean. The method returns a RedBeanPHP Simple Model.
	 * This is useful if you would like to rely on PHP type hinting.
	 * You can box your beans before passing them to functions or methods
	 * with typed parameters.
	 *
	 * Note about beans vs models:
	 * Use unbox to obtain the bean powering the model. If you want to use bean functionality,
	 * you should -always- unbox first. While some functionality (like magic get/set) is
	 * available in the model, this is just read-only. To use a model as a typical RedBean
	 * OODBBean you should always unbox the model to a bean. Models are meant to
	 * expose only domain logic added by the developer (business logic, no ORM logic).
	 *
	 * @return SimpleModel|SimpleModelInterface
	 */
	public function box()
	{
		return $this;
	}

	/**
	 * Unbox the bean from the model.
	 * This method returns the bean inside the model.
	 *
	 * Note about beans vs models:
	 * Use unbox to obtain the bean powering the model. If you want to use bean functionality,
	 * you should -always- unbox first. While some functionality (like magic get/set) is
	 * available in the model, this is just read-only. To use a model as a typical RedBean
	 * OODBBean you should always unbox the model to a bean. Models are meant to
	 * expose only domain logic added by the developer (business logic, no ORM logic).
	 *
	 * @return OODBBean
	 */
	public function unbox()
	{
		return $this->bean;
	}
}
