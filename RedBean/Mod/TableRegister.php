<?php

class RedBean_Mod_TableRegister extends RedBean_Mod {

    public function getTables( $all=false ) {
        $db = $this->provider->getDatabase();

        if ($all && $this->provider->isFrozen()) {
            $alltables = $db->getCol($this->provider->getWriter()->getQuery("show_tables"));
            return $alltables;
        }
        else {
            $alltables = $db->getCol($this->provider->getWriter()->getQuery("show_rtables"));
            return $alltables;
        }

    }


    public function register( $tablename ) {
        
        $db = $this->provider->getDatabase();

        $tablename = $db->escape( $tablename );

        $db->exec($this->provider->getWriter()->getQuery("register_table",array("table"=>$tablename)));

    }


    public function unregister( $tablename ) {
        $db = $this->provider->getDatabase();
        $tablename = $db->escape( $tablename );

        $db->exec($this->provider->getWriter()->getQuery("unregister_table",array("table"=>$tablename)));


    }



}