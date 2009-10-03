<?php

class RedBean_Mod_Filter_NullFilter extends RedBean_Mod implements RedBean_Mod_Filter {

    public function __construct(){}

    public function property( $name, $forReading = false ) {
        return $name;
    }

    public function table( $name ) {
          return $name;
    }

}

