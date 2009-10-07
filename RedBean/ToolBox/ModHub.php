<?php

class RedBean_ToolBox_ModHub extends RedBean_ToolBox {

    private $facade;

  

    public function getDatabase() {
        return $this->give("database");
    }

    public function getWriter() {
        return $this->give("writer");
    }

    public function getFilter() {
        return $this->give("filter");
    }

    public function setFacade( $facade ) {
        $this->facade = $facade;
    }
    
    public function __call( $who, $args=array() ) {

        $tool = strtolower(substr($who,3));
        if ($this->has($tool)) {
            return $this->give( $tool );
        }
        else {
            return call_user_func_array( array($this->facade,$who), $args );
        }
        
    }

    public function __get($v) {
       return $this->facade->$v;
    }

    public function __set($v,$i) {
        $this->facade->$v = $i;
    }


}