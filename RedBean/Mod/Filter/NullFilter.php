<?php

class RedBean_Mod_Filter_NullFilter implements RedBean_Mod_Filter {

    public function property( $name, $forReading = false ) {
        return $name;
    }

    public function table( $name ) {
          return $name;
    }

}

