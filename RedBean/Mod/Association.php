<?php

class RedBean_Mod_Association extends RedBean_Mod {

    public function link( RedBean_OODBBean $bean1, RedBean_OODBBean $bean2 ) {
    //get a database
        $db = $this->provider->getDatabase();

        //first we check the beans whether they are valid
        $bean1 = $this->provider->getBeanChecker()->checkBeanForAssoc($bean1);
        $bean2 = $this->provider->getBeanChecker()->checkBeanForAssoc($bean2);

        $this->provider->getLockManager()->openBean( $bean1, true );
        $this->provider->getLockManager()->openBean( $bean2, true );

        //sort the beans
        $tp1 = $bean1->type;
        $tp2 = $bean2->type;
        if ($tp1==$tp2) {
            $arr = array( 0=>$bean1, 1 =>$bean2 );
        }
        else {
            $arr = array( $tp1=>$bean1, $tp2 =>$bean2 );
        }
        ksort($arr);
        $bean1 = array_shift( $arr );
        $bean2 = array_shift( $arr );

        $id1 = intval($bean1->id);
        $id2 = intval($bean2->id);

        //infer the association table
        $tables = array();
        array_push( $tables, $db->escape( $bean1->type ) );
        array_push( $tables, $db->escape( $bean2->type ) );
        //sort the table names to make sure we only get one assoc table
        sort($tables);
        $assoctable = $db->escape( implode("_",$tables) );

        //check whether this assoctable already exists
        if (!$this->provider->getFacade()->isFrozen()) {
            $alltables = $this->provider->getTableRegister()->getTables();
            if (!in_array($assoctable, $alltables)) {
            //no assoc table does not exist, create it..
                $t1 = $tables[0];
                $t2 = $tables[1];

                if ($t1==$t2) {
                    $t2.="2";
                }

                $assoccreateSQL = $this->provider->getWriter()->getQuery("create_assoc",array(
                    "assoctable"=> $assoctable,
                    "t1" =>$t1,
                    "t2" =>$t2,
                    "engine"=>$this->provider->getFacade()->getEngine()
                ));

                $db->exec( $assoccreateSQL );

                //add a unique constraint
                $db->exec( $this->provider->getWriter()->getQuery("add_assoc",array(
                    "assoctable"=> $assoctable,
                    "t1" =>$t1,
                    "t2" =>$t2
                    )) );

                $this->provider->getTableRegister()->register( $assoctable );
            }
        }

        //now insert the association record
        $assocSQL = $this->provider->getWriter()->getQuery("add_assoc_now", array(
            "id1"=>$id1,
            "id2"=>$id2,
            "assoctable"=>$assoctable
        ));

        $db->exec( $assocSQL );

    }


    /**
     * Breaks the association between a pair of beans
     * @param $bean1
     * @param $bean2
     * @return unknown_type
     */
    public function breakLink(RedBean_OODBBean $bean1, RedBean_OODBBean $bean2) {
    //get a database
        $db = $this->provider->getDatabase();

        //first we check the beans whether they are valid
        $bean1 = $this->provider->getBeanChecker()->checkBeanForAssoc($bean1);
        $bean2 = $this->provider->getBeanChecker()->checkBeanForAssoc($bean2);


        $this->provider->getLockManager()->openBean( $bean1, true );
        $this->provider->getLockManager()->openBean( $bean2, true );


        $idx1 = intval($bean1->id);
        $idx2 = intval($bean2->id);

        //sort the beans
        $tp1 = $bean1->type;
        $tp2 = $bean2->type;

        if ($tp1==$tp2) {
            $arr = array( 0=>$bean1, 1 =>$bean2 );
        }
        else {
            $arr = array( $tp1=>$bean1, $tp2 =>$bean2 );
        }

        ksort($arr);
        $bean1 = array_shift( $arr );
        $bean2 = array_shift( $arr );

        $id1 = intval($bean1->id);
        $id2 = intval($bean2->id);

        //infer the association table
        $tables = array();
        array_push( $tables, $db->escape( $bean1->type ) );
        array_push( $tables, $db->escape( $bean2->type ) );
        //sort the table names to make sure we only get one assoc table
        sort($tables);


        $assoctable = $db->escape( implode("_",$tables) );

        //check whether this assoctable already exists
        $alltables = $this->provider->getTableRegister()->getTables();

        if (in_array($assoctable, $alltables)) {
            $t1 = $tables[0];
            $t2 = $tables[1];
            if ($t1==$t2) {
                $t2.="2";
                $unassocSQL = $this->provider->getWriter()->getQuery("unassoc",array(
                    "assoctable"=>$assoctable,
                    "t1"=>$t2,
                    "t2"=>$t1,
                    "id1"=>$id1,
                    "id2"=>$id2
                ));
                //$unassocSQL = "DELETE FROM `$assoctable` WHERE ".$t2."_id = $id1 AND ".$t1."_id = $id2 ";
                $db->exec($unassocSQL);
            }

            //$unassocSQL = "DELETE FROM `$assoctable` WHERE ".$t1."_id = $id1 AND ".$t2."_id = $id2 ";

            $unassocSQL = $this->provider->getWriter()->getQuery("unassoc",array(
                "assoctable"=>$assoctable,
                "t1"=>$t1,
                "t2"=>$t2,
                "id1"=>$id1,
                "id2"=>$id2
            ));

            $db->exec($unassocSQL);
        }
        if ($tp1==$tp2) {
            $assoctable2 = "pc_".$db->escape( $bean1->type )."_".$db->escape( $bean1->type );
            //echo $assoctable2;
            //check whether this assoctable already exists
            $alltables = $this->provider->getTableRegister()->getTables();
            if (in_array($assoctable2, $alltables)) {

            //$id1 = intval($bean1->id);
            //$id2 = intval($bean2->id);
                $unassocSQL = $this->provider->getWriter()->getQuery("untree", array(
                    "assoctable2"=>$assoctable2,
                    "idx1"=>$idx1,
                    "idx2"=>$idx2
                ));

                $db->exec($unassocSQL);
            }
        }
    }




    public function get( RedBean_OODBBean $bean, $targettype ) {
    //get a database
        $db = $this->provider->getDatabase();
        //first we check the beans whether they are valid
        $bean = $this->provider->getBeanChecker()->checkBeanForAssoc($bean);

        $id = intval($bean->id);


        //obtain the table names
        $t1 = $db->escape( $this->provider->getFilter()->table($bean->type) );
        $t2 = $db->escape( $targettype );

        //infer the association table
        $tables = array();
        array_push( $tables, $t1 );
        array_push( $tables, $t2 );
        //sort the table names to make sure we only get one assoc table
        sort($tables);
        $assoctable = $db->escape( implode("_",$tables) );

        //check whether this assoctable exists
        $alltables = $this->provider->getTableRegister()->getTables();

        if (!in_array($assoctable, $alltables)) {
            return array(); //nope, so no associations...!
        }
        else {
            if ($t1==$t2) {
                $t2.="2";
            }

            $getassocSQL = $this->provider->getWriter()->getQuery("get_assoc",array(
                "t1"=>$t1,
                "t2"=>$t2,
                "assoctable"=>$assoctable,
                "id"=>$id
            ));


            $rows = $db->getCol( $getassocSQL );
            $beans = array();
            if ($rows && is_array($rows) && count($rows)>0) {
                foreach($rows as $i) {
                    $beans[$i] = $this->provider->getBeanStore()->get( $targettype, $i, false);
                }
            }
            return $beans;
        }


    }

    public function deleteAllAssoc( $bean ) {

        $db = $this->provider->getDatabase();
        $bean = $this->provider->getBeanChecker()->checkBeanForAssoc($bean);

        $this->provider->getLockManager()->openBean( $bean, true );


        $id = intval( $bean->id );

        //get all tables
        $alltables = $this->provider->getTableRegister()->getTables();

        //are there any possible associations?
        $t = $db->escape($bean->type);
        $checktables = array();
        foreach( $alltables as $table ) {
            if (strpos($table,$t."_")!==false || strpos($table,"_".$t)!==false) {
                $checktables[] = $table;
            }
        }

        //remove every possible association
        foreach($checktables as $table) {
            if (strpos($table,"pc_")===0) {

                $db->exec( $this->provider->getWriter()->getQuery("deltree",array(
                    "id"=>$id,
                    "table"=>$table
                    )) );
            }
            else {

                $db->exec( $this->provider->getWriter()->getQuery("unassoc_all_t1",array("table"=>$table,"t"=>$t,"id"=>$id)) );
                $db->exec( $this->provider->getWriter()->getQuery("unassoc_all_t2",array("table"=>$table,"t"=>$t,"id"=>$id)) );
            }


        }
        return true;
    }


    public function deleteAllAssocType( $targettype, $bean ) {
        $db = $this->provider->getDatabase();
        $bean = $this->provider->getBeanChecker()->checkBeanForAssoc($bean);
        $this->provider->getLockManager()->openBean( $bean, true );

        $id = intval( $bean->id );

        //obtain the table names
        $t1 = $db->escape( $this->provider->getFilter()->table($bean->type) );
        $t2 = $db->escape( $targettype );

        //infer the association table
        $tables = array();
        array_push( $tables, $t1 );
        array_push( $tables, $t2 );
        //sort the table names to make sure we only get one assoc table
        sort($tables);
        $assoctable = $db->escape( implode("_",$tables) );

        $availabletables = $this->provider->getTableRegister()->getTables();


        if (in_array('pc_'.$assoctable,$availabletables)) {
            $db->exec( $this->provider->getWriter()->getQuery("deltreetype",array(
                "assoctable"=>'pc_'.$assoctable,
                "id"=>$id
                )) );
        }
        if (in_array($assoctable,$availabletables)) {
            $db->exec( $this->provider->getWriter()->getQuery("unassoctype1",array(
                "assoctable"=>$assoctable,
                "t1"=>$t1,
                "id"=>$id
                )) );
            $db->exec( $this->provider->getWriter()->getQuery("unassoctype2",array(
                "assoctable"=>$assoctable,
                "t1"=>$t1,
                "id"=>$id
                )) );

        }

        return true;
    }

    public function numOfRelated( $type, RedBean_OODBBean $bean ) {

    			$db = $this->provider->getDatabase();

			$t2 = $this->provider->getFilter()->table( $db->escape( $type ) );

			//is this bean valid?
			$this->provider->getBeanChecker()->check( $bean );
			$t1 = $this->provider->getFilter()->table( $bean->type  );
			$tref = $this->provider->getFilter()->table( $db->escape( $bean->type ) );
			$id = intval( $bean->id );

			//infer the association table
			$tables = array();
			array_push( $tables, $t1 );
			array_push( $tables, $t2 );

			//sort the table names to make sure we only get one assoc table
			sort($tables);
			$assoctable = $db->escape( implode("_",$tables) );

			//get all tables
			$tables = $this->provider->getTableRegister()->getTables();

			if ($tables && is_array($tables) && count($tables) > 0) {
				if (in_array( $t1, $tables ) && in_array($t2, $tables)){
					$sqlCountRelations = $this->provider->getWriter()->getQuery(
						"num_related", array(
							"assoctable"=>$assoctable,
							"t1"=>$t1,
							"id"=>$id
						)
					);

					return (int) $db->getCell( $sqlCountRelations );
				}
			}
			else {
				return 0;
			}
		}

}