<?php

class RedBean_Mod_BeanChecker extends RedBean_Mod {

    
    public function check( RedBean_OODBBean $bean ) {
        if (!$this->provider->getDatabase()) {
            throw new RedBean_Exception_Security("No database object. Have you used kickstart to initialize RedBean?");
        }
        foreach($bean as $prop=>$value) {
            
            if (preg_match('/[^abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_]/',$prop)) {
                throw new RedBean_Exception_Security("Invalid Characters in property $prop ");
            }

            $prop = preg_replace('/[^abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_]/',"",$prop);
            if (strlen(trim($prop))===0) {
                throw new RedBean_Exception_Security("Invalid Characters in property");
            }
            else {
                if (is_array($value)) {
                    throw new RedBean_Exception_Security("Cannot store an array, use composition instead or serialize first.");
                }
                if (is_object($value)) {
                    throw new RedBean_Exception_Security("Cannot store an object, use composition instead or serialize first.");
                }
                $bean->$prop = $value;
            }
        }

        //Is the bean valid? does the bean have an id?
        if (!isset($bean->id)) {
            throw new RedBean_Exception_Security("Invalid bean, no id");
        }

        //is the id numeric?
        if (!is_numeric($bean->id) || $bean->id < 0 || (round($bean->id)!=$bean->id)) {
            throw new RedBean_Exception_Security("Invalid bean, id not numeric");
        }

        //does the bean have a type?
        if (!isset($bean->type)) {
            throw new RedBean_Exception_Security("Invalid bean, no type");
        }

        //is the beantype correct and valid?
        if (!is_string($bean->type) || is_numeric($bean->type) || strlen($bean->type)<3) {
            throw new RedBean_Exception_Security("Invalid bean, wrong type");
        }

        //is the beantype legal?
        if ($bean->type==="locking" || $bean->type==="dtyp" || $bean->type==="redbeantables") {
            throw new RedBean_Exception_Security("Beantype is reserved table");
        }

        //is the beantype allowed?
        if (strpos($bean->type,"_")!==false && ctype_alnum($bean->type)) {
            throw new RedBean_Exception_Security("Beantype contains illegal characters");
        }

    }

    public function checkBeanForAssoc( $bean ) {

    //check the bean
        $this->check($bean);

        //make sure it has already been saved to the database, else we have no id.
        if (intval($bean->id) < 1) {
        //if it's not saved, save it
            $bean->id = $this->provider->getBeanStore()->set( $bean );
        }

        return $bean;

    }


}
