<?php

abstract class RedBean_ToolBox {


    private $tools = array();



    protected function give( $toolname ) {
        if ($this->has($toolname)) {
            return $this->tools[$toolname];
        }
        else {
            throw new Exception("Module or tool $toolname has not been installed.");
        }
    }


    public function has( $toolname ) {
        return (isset($this->tools[$toolname]));
    }

    public function add( $label, RedBean_Tool $tool ) {
        $this->tools[$label] = $tool;
    }
    
    

}