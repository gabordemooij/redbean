<?php

abstract class RedBean_Mod implements RedBean_Tool  {

    protected $provider;

    public function __construct(RedBean_ToolBox $provider) {
        $this->provider = $provider;
    }

}