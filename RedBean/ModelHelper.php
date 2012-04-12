<?php
/**
 * RedBean Model Helper
 * 
 * @file			RedBean/ModelHelper.php
 * @description		Connects beans to models, in essence 
 *					this is the core of so-called FUSE.
 * 		
 * @author			Gabor de Mooij and the RedBeanPHP Community
 * @license			BSD/GPLv2
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 *
 */
class RedBean_ModelHelper implements RedBean_Observer {

	/**
	 * Holds a model formatter
	 * @var RedBean_IModelFormatter
	 */
	private static $modelFormatter;
	
	
	/**
	 * Holds a dependency injector
	 * @var type 
	 */
	private static $dependencyInjector;

	/**
	 * Connects OODB to a model if a model exists for that
	 * type of bean. This connector is used in the facade.
	 *
	 * @param string $eventName
	 * @param RedBean_OODBBean $bean
	 */
	public function onEvent( $eventName, $bean ) {
		$bean->$eventName();
	}


	/**
	 * Given a model ID (model identifier) this method returns the
	 * full model name.
	 *
	 * @param string $model
	 * @param RedBean_OODBBean $bean
	 * 
	 * @return string $fullname
	 */
	public static function getModelName( $model, $bean = null ) {
		if (self::$modelFormatter){
			return self::$modelFormatter->formatModel($model,$bean);
		}
		else {
			return 'Model_'.ucfirst($model);
		}
	}

	/**
	 * Sets the model formatter to be used to discover a model
	 * for Fuse.
	 *
	 * @param string $modelFormatter
	 */
	public static function setModelFormatter( $modelFormatter ) {
		self::$modelFormatter = $modelFormatter;
	}
	
	
	/**
	 * Obtains a new instance of $modelClassName, using a dependency injection
	 * container if possible.
	 * 
	 * @param string $modelClassName name of the model
	 */
	public static function factory( $modelClassName ) {
		if (self::$dependencyInjector) {
			return self::$dependencyInjector->getInstance($modelClassName);
		}
		return new $modelClassName();
	}

	/**
	 * Sets the dependency injector to be used.
	 * 
	 * @param RedBean_DependencyInjector $di injecto to be used
	 */
	public static function setDependencyInjector( RedBean_DependencyInjector $di ) {
		self::$dependencyInjector = $di;
	}
	
	/**
	 * Stops the dependency injector from resolving dependencies. Removes the
	 * reference to the dependency injector.
	 */
	public static function clearDependencyInjector() {
		self::$dependencyInjector = null;
	}
	
}