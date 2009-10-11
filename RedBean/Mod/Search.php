<?php

class RedBean_Mod_Search extends RedBean_Mod {

    /**
     * Fills slots in SQL query
     * @param $sql
     * @param $slots
     * @return unknown_type
     */
    public function processQuerySlots($sql, $slots) {

        $db = $this->provider->getDatabase();

        //Just a funny code to identify slots based on randomness
        $code = sha1(rand(1,1000)*time());

        //This ensures no one can hack our queries via SQL template injection
        foreach( $slots as $key=>$value ) {
            $sql = str_replace( "{".$key."}", "{".$code.$key."}" ,$sql );
        }

        //replace the slots inside the SQL template
        foreach( $slots as $key=>$value ) {
            $sql = str_replace( "{".$code.$key."}", $this->provider->getWriter()->getQuote().$db->escape( $value ).$this->provider->getWriter()->getQuote(),$sql );
        }

        return $sql;
    }



    public function sql($rawsql, $slots, $table, $max=0) {

        $db = $this->provider->getDatabase();
        
        $sql = $rawsql;

        if (is_array($slots)) {
            $sql = $this->processQuerySlots( $sql, $slots );
        }

        $sql = str_replace('@ifexists:','', $sql);
        $rs = $db->getCol( $this->provider->getWriter()->getQuery("where",array(
            "table"=>$table
            )) . $sql );

        $err = $db->getErrorMsg();
        if (!$this->provider->getFacade()->isFrozen() && strpos($err,"Unknown column")!==false && $max<10) {
            $matches = array();
            if (preg_match("/Unknown\scolumn\s'(.*?)'/",$err,$matches)) {
                if (count($matches)==2 && strpos($rawsql,'@ifexists')!==false) {
                    $rawsql = str_replace('@ifexists:`'.$matches[1].'`','NULL', $rawsql);
                    $rawsql = str_replace('@ifexists:'.$matches[1].'','NULL', $rawsql);
                    return $this->sql( $rawsql, $slots, $table, ++$max);
                }
            }
            return array();
        }
        else {
            if (is_array($rs)) {
                return $rs;
            }
            else {
                return array();
            }
        }


    }
}