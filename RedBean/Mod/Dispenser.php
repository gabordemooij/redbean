<?php

class RedBean_Mod_Dispenser extends RedBean_Mod {

        public function dispense($type ) {
                $oBean = new RedBean_OODBBean();
		$oBean->type = $type;
		$oBean->id = 0;
		return $oBean;
        }


}