<?php

abstract class RedBean_Mod {

    protected $provider;

    public function __construct(RedBean_OODB $provider) {
        $this->provider = $provider;
    }

}