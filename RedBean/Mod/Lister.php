<?php

class RedBean_Mod_Lister extends RedBean_Mod {


        public function get($type, $start=false, $end=false, $orderby="id ASC", $extraSQL = false) {

    	$db = $this->provider->getDatabase();

			$listSQL = $this->provider->getWriter()->getQuery("list",array(
				"type"=>$type,
				"start"=>$start,
				"end"=>$end,
				"orderby"=>$orderby,
				"extraSQL"=>$extraSQL
			));


			return $db->get( $listSQL );

    }



    public function distinct($type,$field){
        //TODO: Consider if GROUP BY (equivalent meaning) is more portable
			//across DB types?
			$db = $this->provider->getDatabase();
			$type = $this->provider->getToolBox()->getFilter()->table( $db->escape( $type ) );
			$field = $db->escape( $field );

			$alltables = $this->provider->showTables();

			if (!in_array($type, $alltables)) {
				return array();
			}
			else {
				$ids = $db->getCol( $this->provider->getWriter()->getQuery("distinct",array(
					"type"=>$type,
					"field"=>$field
				)));
				$beans = array();
				if (is_array($ids) && count($ids)>0) {
					foreach( $ids as $id ) {
						$beans[ $id ] = $this->provider->getById( $type, $id , false);
					}
				}
				return $beans;
			}
		
    }


    public function stat( $type, $field, $stat) {
        $db = $this->provider->getDatabase();
			$type = $this->provider->getToolBox()->getFilter()->table( $db->escape( $type ) );
			$field = $this->provider->getToolBox()->getFilter()->property( $db->escape( $field ) );
			$stat = $db->escape( $stat );

			$alltables = $this->provider->showTables();

			if (!in_array($type, $alltables)) {
				return 0;
			}
			else {
				$no = $db->getCell($this->provider->getWriter()->getQuery("stat",array(
					"stat"=>$stat,
					"field"=>$field,
					"type"=>$type
				)));
				return floatval( $no );
			}
		}
  
}