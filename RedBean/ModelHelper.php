<?php
/*
 * ModelHelper
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 * Interface definition of a Model Formatter for Fuse
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
	 * @return string $fullname
	 */
	public static function getModelName( $model ) {
		if (self::$modelFormatter){
			return self::$modelFormatter->formatModel($model);
		}
		else {
			return "Model_".ucfirst($model);
		}
	}


	/**
	 * Sets the model formatter to be used to discover a model
	 * for Fuse.
	 *
	 * @param string $modelFormatter
	 */
	public static function setModelFormatter( RedBean_IModelFormatter $modelFormatter ) {
		self::$modelFormatter = $modelFormatter;
	}


}
