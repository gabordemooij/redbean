<?php

class RedBean_Mod_Filter_Strict implements RedBean_Mod_Filter {

    public function property( $name, $forReading = false ) {
        $name = strtolower($name);
          if (!$forReading) {
            if ($name=="type") {
                    throw new RedBean_Exception_Security("type is a reserved property to identify the table, pleae use another name for this property.");
            }
            if ($name=="id") {
                    throw new RedBean_Exception_Security("id is a reserved property to identify the record, pleae use another name for this property.");
            }
        }
        $name =  trim(preg_replace("/[^abcdefghijklmnopqrstuvwxyz0123456789]/","",$name));
        if (strlen($name)===0) {
            throw new RedBean_Exception_Security("Empty property is not allowed");
        }
        return $name;
    }

    public function table( $name ) {
          $name =  strtolower(trim(preg_replace("/[^abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789]/","",$name)));
          return $name;

    }

}

