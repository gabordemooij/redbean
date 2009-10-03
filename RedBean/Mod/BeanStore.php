<?php

class RedBean_Mod_BeanStore extends RedBean_Mod {

    /**
     * Inserts a bean into the database
     * @param $bean
     * @return $id
     */
    public function set( RedBean_OODBBean $bean ) {

        $this->provider->checkBean($bean);


        $db = $this->provider->getDatabase(); //I am lazy, I dont want to waste characters...


        $table = $db->escape($bean->type); //what table does it want

        //may we adjust the database?
        if (!$this->provider->isFrozen()) {

        //does this table exist?
            $tables = $this->provider->showTables();

            if (!in_array($table, $tables)) {

                $createtableSQL = $this->provider->getWriter()->getQuery("create_table", array(
                    "engine"=>$this->provider->getEngine(),
                    "table"=>$table
                ));

                //get a table for our friend!
                $db->exec( $createtableSQL );
                //jupz, now he has its own table!...
                $this->provider->addTable( $table );
            }

            //does the table fit?
            $columnsRaw = $this->provider->getWriter()->getTableColumns($table, $db) ;

            $columns = array();
            foreach($columnsRaw as $r) {
                $columns[$r["Field"]]=$r["Type"];
            }

            $insertvalues = array();
            $insertcolumns = array();
            $updatevalues = array();

            foreach( $bean as $p=>$v) {
                if ($p!="type" && $p!="id") {
                    $p = $db->escape($p);
                    $v = $db->escape($v);
                    //What kind of property are we dealing with?
                    $typeno = $this->provider->inferType($v);
                    //Is this property represented in the table?
                    if (isset($columns[$p])) {
                    //yes it is, does it still fit?
                        $sqlt = $this->provider->getType($columns[$p]);
                        //echo "TYPE = $sqlt .... $typeno ";
                        if ($typeno > $sqlt) {
                        //no, we have to widen the database column type
                            $changecolumnSQL = $this->provider->getWriter()->getQuery( "widen_column", array(
                                "table" => $table,
                                "column" => $p,
                                "newtype" => $this->provider->getWriter()->typeno_sqltype[$typeno]
                                ) );

                            $db->exec( $changecolumnSQL );
                        }
                    }
                    else {
                    //no it is not
                        $addcolumnSQL = $this->provider->getWriter()->getQuery("add_column",array(
                            "table"=>$table,
                            "column"=>$p,
                            "type"=> $this->provider->getWriter()->typeno_sqltype[$typeno]
                        ));

                        $db->exec( $addcolumnSQL );
                    }
                    //Okay, now we are sure that the property value will fit
                    $insertvalues[] = $v;
                    $insertcolumns[] = $p;
                    $updatevalues[] = array( "property"=>$p, "value"=>$v );
                }
            }

        }
        else {

            foreach( $bean as $p=>$v) {
                if ($p!="type" && $p!="id") {
                    $p = $db->escape($p);
                    $v = $db->escape($v);
                    $insertvalues[] = $v;
                    $insertcolumns[] = $p;
                    $updatevalues[] = array( "property"=>$p, "value"=>$v );
                }
            }

        }

        //Does the record exist already?
        if ($bean->id) {
        //echo "<hr>Now trying to open bean....";
            $this->provider->openBean($bean, true);
            //yes it exists, update it
            if (count($updatevalues)>0) {
                $updateSQL = $this->provider->getWriter()->getQuery("update", array(
                    "table"=>$table,
                    "updatevalues"=>$updatevalues,
                    "id"=>$bean->id
                ));

                //execute the previously build query
                $db->exec( $updateSQL );
            }
        }
        else {
        //no it does not exist, create it
            if (count($insertvalues)>0) {

                $insertSQL = $this->provider->getWriter()->getQuery("insert",array(
                    "table"=>$table,
                    "insertcolumns"=>$insertcolumns,
                    "insertvalues"=>$insertvalues
                ));

            }
            else {
                $insertSQL = $this->provider->getWriter()->getQuery("create", array("table"=>$table));
            }
            //execute the previously build query
            $db->exec( $insertSQL );
            $bean->id = $db->getInsertID();
            $this->provider->openBean($bean);
        }

        return $bean->id;

    }
    


}