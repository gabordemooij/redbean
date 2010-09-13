<?php

/**
 * ModelHelper
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_ModelHelper implements RedBean_Observer {

	/**
	 * Connects OODB to a model if a model exists for that
	 * type of bean. This connector is used in the facade.
	 * @param string $eventName
	 * @param RedBean_OODBBean $bean
	 */
	public function onEvent( $eventName, $bean ) {
		$className = $this->getModelName( $bean->getMeta("type") );
		if (class_exists($className)) {
			$model = new $className;
			if ($model instanceof RedBean_SimpleModel) {
				$model->loadBean( $bean );
				if (method_exists($model, $eventName)) {
					$model->$eventName();
				}
			}
		}
	}

	/**
	 * Returns the model associated with a certain bean.
	 * @param string $beanType
	 * @return string $modelClassName
	 */
	public function getModelName( $beanType ) {
		return "Model_".ucfirst( $beanType );
	}

}