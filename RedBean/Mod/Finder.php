<?php

class RedBean_Mod_Finder extends RedBean_Mod {

        /**
     * Finds a bean using search parameters
     * @param $bean
     * @param $searchoperators
     * @param $start
     * @param $end
     * @param $orderby
     * @return unknown_type
     */
    public function find(RedBean_OODBBean $bean, $searchoperators = array(), $start=0, $end=100, $orderby="id ASC", $extraSQL=false) {
      $this->provider->checkBean( $bean );
      $db = $this->provider->getDatabase();
      $tbl = $db->escape( $bean->type );

      $findSQL = $this->provider->getWriter()->getQuery("find",array(
      	"searchoperators"=>$searchoperators,
      	"bean"=>$bean,
      	"start"=>$start,
      	"end"=>$end,
      	"orderby"=>$orderby,
      	"extraSQL"=>$extraSQL,
      	"tbl"=>$tbl
      ));

      $ids = $db->getCol( $findSQL );
      $beans = array();

      if (is_array($ids) && count($ids)>0) {
          foreach( $ids as $id ) {
            $beans[ $id ] = $this->provider->getById( $bean->type, $id , false);
        }
      }

      return $beans;

    }





}