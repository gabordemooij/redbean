<?php
/**
 * SimpleModel
 * @file 		RedBean/SimpleModel.php
 * @description		Part of FUSE
 * @author              Gabor de Mooij
 * @license		BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD license that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_SimpleModel {

    protected $bean;

    public function loadBean( RedBean_OODBBean $bean ) {
        $this->bean = $bean;
    }

    public function __get( $prop ) {
        return $this->bean->$prop;
    }

    public function __set( $prop, $value ) {
        $this->bean->$prop = $value;
    }
}