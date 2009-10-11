<?php

/**
 * @class BeanStore
 * @desc The BeanStore is responsible for storing, retrieving, updating and deleting beans.
 * It performs the BASIC CRUD operations on bean objects.
 * 
 */
class RedBean_Mod_BeanStore extends RedBean_Mod {

    /**
     * Inserts a bean into the database
     * @param $bean
     * @return $id
     */
    public function set( RedBean_OODBBean $bean ) {

        $this->provider->getBeanChecker()->check($bean);


        $db = $this->provider->getDatabase(); //I am lazy, I dont want to waste characters...


        $table = $db->escape($bean->type); //what table does it want

        //may we adjust the database?
        if (!$this->provider->getFacade()->isFrozen()) {

        //does this table exist?
            $tables = $this->provider->getTableRegister()->getTables();

            if (!in_array($table, $tables)) {

                $createtableSQL = $this->provider->getWriter()->getQuery("create_table", array(
                    "engine"=>$this->provider->getFacade()->getEngine(),
                    "table"=>$table
                ));

                //get a table for our friend!
                $db->exec( $createtableSQL );
                //jupz, now he has its own table!...
                $this->provider->getTableRegister()->register( $table );
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

            //@todo: move this logic to a table manager
            foreach( $bean as $p=>$v) {
                if ($p!="type" && $p!="id") {
                    $p = $db->escape($p);
                    $v = $db->escape($v);
                    //What kind of property are we dealing with?
                    $typeno = $this->provider->getScanner()->type($v);
                    //Is this property represented in the table?
                    if (isset($columns[$p])) {
                    //yes it is, does it still fit?
                        $sqlt = $this->provider->getScanner()->code($columns[$p]);
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
            $this->provider->getLockManager()->openBean($bean, true);
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
            $this->provider->getLockManager()->openBean($bean);
        }

        return $bean->id;

    }

   
    public function get($type, $id, $data=false) {
        $bean = $this->provider->getDispenser()->dispense( $type );
        $db = $this->provider->getDatabase();
        $table = $db->escape( $type );
        $id = abs( intval( $id ) );
        $bean->id = $id;

        //try to open the bean
        $this->provider->getLockManager()->openBean($bean);

        //load the bean using sql
        if (!$data) {
                $getSQL = $this->provider->getWriter()->getQuery("get_bean",array(
                        "type"=>$type,
                        "id"=>$id
                ));
                $row = $db->getRow( $getSQL );
        }
        else {
                $row = $data;
        }

        if ($row && is_array($row) && count($row)>0) {
                foreach($row as $p=>$v) {
                        //populate the bean with the database row
                        $bean->$p = $v;
                }
        }
        else {
                throw new RedBean_Exception_FailedAccessBean("bean not found");
        }

        return $bean;
    }

    //@todo tested?
    public function trash( RedBean_OODBBean $bean ) {
        $this->provider->getBeanChecker()->check( $bean );
	if (intval($bean->id)===0) return;
	$this->provider->getAssociation()->deleteAllAssoc( $bean );
	$this->provider->getLockManager()->openBean($bean);
	$table = $this->provider->getDatabase()->escape($bean->type);
	$id = intval($bean->id);
	$this->provider->getDatabase()->exec( $this->provider->getWriter()->getQuery("trash",array(
		"table"=>$table,
		"id"=>$id
	)) );
    }

    public function exists($type,$id) {

    	$db = $this->provider->getDatabase();
			$id = intval( $id );
			$type = $db->escape( $type );

			//$alltables = $db->getCol("show tables");
			$alltables = $this->provider->getTableRegister()->getTables();

			if (!in_array($type, $alltables)) {
				return false;
			}
			else {
				$no = $db->getCell( $this->provider->getWriter()->getQuery("bean_exists",array(
					"type"=>$type,
					"id"=>$id
				)) );
				if (intval($no)) {
					return true;
				}
				else {
					return false;
				}
			}
		}

           public function numberof($type) {
                	$db = $this->provider->getDatabase();
			$type = $this->provider->getFilter()->table( $db->escape( $type ) );

			$alltables = $this->provider->getTableRegister()->getTables();

			if (!in_array($type, $alltables)) {
				return 0;
			}
			else {
				$no = $db->getCell( $this->provider->getWriter()->getQuery("count",array(
					"type"=>$type
				)));
				return intval( $no );
			}
           }

           public function fastloader($type, $ids) {

                $db = $this->provider->getDatabase();
                $sql = $this->provider->getWriter()->getQuery("fastload", array(
                    "type"=>$type,
                    "ids"=>$ids
                ));
                return $db->get( $sql );

           }

        public function trashAll($type) {
            $this->provider->getDatabase()->exec( $this->provider->getWriter()->getQuery("drop_type",array("type"=>$this->provider->getFilter()->table($type))));
        }


}