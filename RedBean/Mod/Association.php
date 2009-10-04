<?php

class RedBean_Mod_Association extends RedBean_Mod {

    public function link( RedBean_OODBBean $bean1, RedBean_OODBBean $bean2 ) {
        //get a database
        $db = $this->provider->getDatabase();

        //first we check the beans whether they are valid
        $bean1 = $this->provider->checkBeanForAssoc($bean1);
        $bean2 = $this->provider->checkBeanForAssoc($bean2);

        $this->provider->openBean( $bean1, true );
        $this->provider->openBean( $bean2, true );

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
        if (!$this->provider->isFrozen()) {
            $alltables = $this->provider->showTables();
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
                    "engine"=>$this->provider->getEngine()
                ));

                $db->exec( $assoccreateSQL );

                //add a unique constraint
                $db->exec( $this->provider->getWriter()->getQuery("add_assoc",array(
                    "assoctable"=> $assoctable,
                    "t1" =>$t1,
                    "t2" =>$t2
                    )) );

                $this->provider->addTable( $assoctable );
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
        $bean1 = $this->provider->checkBeanForAssoc($bean1);
        $bean2 = $this->provider->checkBeanForAssoc($bean2);


        $this->provider->openBean( $bean1, true );
        $this->provider->openBean( $bean2, true );


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
        $alltables = $this->provider->showTables();

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
            $alltables = $this->provider->showTables();
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


}