<?php

/**
 * ModelHelper
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD license that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_ModelHelper implements RedBean_Observer {


    public function onEvent( $eventName, $bean ) {
       $className = "Model_".ucfirst( $bean->getMeta("type") );
       if (class_exists($className)) {
        $model = new $className;
        if ($model instanceof RedBean_SimpleModel) {
            $model->loadBean( $bean );
            if (method_exists($model, $eventName)){
                $model->$eventName();
            }
           }
       }
    }




}