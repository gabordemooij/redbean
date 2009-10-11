<?php

class RedBean_Mod_GarbageCollector extends RedBean_Mod {

 
    
    public function removeUnused() {

            if ($this->provider->getFacade()->isFrozen()) return;

            $toolbox = $this->provider;

            $db = $toolbox->getDatabase();
            $writer = $toolbox->getWriter();

            //get all tables
            $tables = $this->provider->getTableRegister()->getTables();
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


    public function dropColumn($table,$property) {
        	//oops, we are frozen, so no change..
			if ($this->provider->getFacade()->isFrozen()) {
				return false;
			}

			//get a database
			$db = $this->provider->getDatabase();

			$db->exec( $this->provider->getWriter()->getQuery("drop_column", array(
				"table"=>$table,
				"property"=>$property
			)) );

		}

    public function clean() {

			if ($this->provider->getFacade()->isFrozen()) {
				return false;
			}

			$db = $this->provider->getDatabase();

			$tables = $db->getCol( $this->provider->getWriter()->getQuery("show_rtables") );

			foreach($tables as $key=>$table) {
				$tables[$key] = $this->provider->getWriter()->getEscape().$table.$this->provider->getWriter()->getEscape();
			}

			$sqlcleandatabase = $this->provider->getWriter()->getQuery("drop_tables",array(
				"tables"=>$tables
			));

			$db->exec( $sqlcleandatabase );

			$db->exec( $this->provider->getWriter()->getQuery("truncate_rtables") );
			$this->provider->getLockManager()->reset();
			return true;

		
    }
    
}

