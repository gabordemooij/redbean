<?php
/**
 * RedBean Model Helper
 * 
 * @file			RedBean/ModelHelper.php
 * @description		Connects beans to models, in essence 
 *					this is the core of so-called FUSE.
 * 		
 * @author			Gabor de Mooij
 * @license			BSD
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij
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
			return self::$modelFormatter->formatModel($model);
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


}
