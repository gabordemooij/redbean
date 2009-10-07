<?php

class RedBean_Mod_Scanner extends RedBean_Mod {



    public function type( $value ) {
        $v = $value;
        $db = $this->provider->getDatabase();
        $rawv = $v;

			$checktypeSQL = $this->provider->getWriter()->getQuery("infertype", array(
				"value"=> $db->escape(strval($v))
			));


			$db->exec( $checktypeSQL );
			$id = $db->getInsertID();

			$readtypeSQL = $this->provider->getWriter()->getQuery("readtype",array(
				"id"=>$id
			));

			$row=$db->getRow($readtypeSQL);


			$db->exec( $this->provider->getWriter()->getQuery("reset_dtyp") );

			$tp = 0;
			foreach($row as $t=>$tv) {
				if (strval($tv) === strval($rawv)) {
					return $tp;
				}
				$tp++;
			}
			return $tp;
		}

    public function code( $sqlType ) {
        		if (in_array($sqlType,$this->provider->getWriter()->sqltype_typeno)) {
				$typeno = $this->provider->getWriter()->sqltype_typeno[$sqlType];
			}
			else {
				$typeno = -1;
			}

			return $typeno;

    }

}