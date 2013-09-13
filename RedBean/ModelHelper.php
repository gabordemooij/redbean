<?php
/**
 * RedBean Model Helper
 *
 * @file    RedBean/ModelHelper.php
 * @desc    Connects beans to models, in essence
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * This is the core of so-called FUSE.
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_ModelHelper implements RedBean_Observer
{

	/**
	 * @var RedBean_IModelFormatter
	 */
	private static $modelFormatter;

	/**
	 * @var RedBean_DependencyInjector
	 */
	private static $dependencyInjector;

	/**
	 * @see RedBean_Observer::onEvent
	 */
	public function onEvent( $eventName, $bean )
	{
		$bean->$eventName();
	}

	/**
	 * Given a model ID (model identifier) this method returns the
	 * full model name.
	 *
	 * @param string           $model
	 * @param RedBean_OODBBean $bean
	 *
	 * @return string
	 */
	public static function getModelName( $model, $bean = NULL )
	{
		if ( self::$modelFormatter ) {
			return self::$modelFormatter->formatModel( $model, $bean );
		} else {
			$prefix = defined('REDBEAN_MODEL_PREFIX') ? REDBEAN_MODEL_PREFIX : 'Model_';

			return $prefix . ucfirst( $model );
		}
	}

	/**
	 * Sets the model formatter to be used to discover a model
	 * for Fuse.
	 *
	 * @param string $modelFormatter
	 *
	 * @return void
	 */
	public static function setModelFormatter( $modelFormatter )
	{
		self::$modelFormatter = $modelFormatter;
	}

	/**
	 * Obtains a new instance of $modelClassName, using a dependency injection
	 * container if possible.
	 *
	 * @param string $modelClassName name of the model
	 *
	 * @return object
	 */
	public static function factory( $modelClassName )
	{
		if ( self::$dependencyInjector ) {
			return self::$dependencyInjector->getInstance( $modelClassName );
		}

		return new $modelClassName();
	}

	/**
	 * Sets the dependency injector to be used.
	 *
	 * @param RedBean_DependencyInjector $di injector to be used
	 *
	 * @return void
	 */
	public static function setDependencyInjector( RedBean_DependencyInjector $di )
	{
		self::$dependencyInjector = $di;
	}

	/**
	 * Stops the dependency injector from resolving dependencies. Removes the
	 * reference to the dependency injector.
	 *
	 * @return void
	 */
	public static function clearDependencyInjector()
	{
		self::$dependencyInjector = NULL;
	}

	/**
	 * Attaches the FUSE event listeners. Now the Model Helper will listen for
	 * CRUD events. If a CRUD event occurs it will send a signal to the model
	 * that belongs to the CRUD bean and this model will take over control from
	 * there.
	 *
	 * @param RedBean_Observable $observable
	 *
	 * @return void
	 */
	public function attachEventListeners( RedBean_Observable $observable )
	{
		foreach ( array( 'update', 'open', 'delete', 'after_delete', 'after_update', 'dispense' ) as $e ) {
			$observable->addEventListener( $e, $this );
		}
	}
}
