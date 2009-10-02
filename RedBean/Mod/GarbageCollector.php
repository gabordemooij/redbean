<?php

class RedBean_Mod_GarbageCollector {


    public function removeUnused( RedBean_OODB $oodb, RedBean_DBAdapter $db, RedBean_QueryWriter $writer ) {

            //get all tables
            $tables = $oodb->showTables();
            foreach($tables as $table) {
                    if (strpos($table,"_")!==false) {
                            //associative table
                            $tables = explode("_", $table);
                            //both classes need to exist in order to keep this table
                            $classname1 = RedBean_Setup_Namespace_PRFX . $tables[0] . RedBean_Setup_Namespace_SFFX;
                            $classname2 = RedBean_Setup_Namespace_PRFX . $tables[1] . RedBean_Setup_Namespace_SFFX;
                            if(!class_exists( $classname1 , true) || !class_exists( $classname2 , true)) {
                                    $db->exec( $writer->getQuery("drop_tables",array("tables"=>array($table))) );
                                    $db->exec($writer->getQuery("unregister_table",array("table"=>$table)));
                            }
                    }
                    else {
                            //does the class exist?
                            $classname = RedBean_Setup_Namespace_PRFX . $table . RedBean_Setup_Namespace_SFFX;
                            if(!class_exists( $classname , true)) {
                                    $db->exec( $writer->getQuery("drop_tables",array("tables"=>array($table))) );
                                    $db->exec($writer->getQuery("unregister_table",array("table"=>$table)));
                            }
                    }

            }
    }
}