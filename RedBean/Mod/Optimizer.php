<?php

class RedBean_Mod_Optimizer extends RedBean_Mod {

    /**
     * Narrows columns to appropriate size if needed
     * @return unknown_type
     */
    public function run( $gc = false ,$stdTable=false, $stdCol=false) {

    //oops, we are frozen, so no change..
        if ($this->provider->isFrozen()) {
            return false;
        }

        //get a database
        $db = $this->provider->getDatabase();

        //get all tables
        $tables = $this->provider->showTables();

        //pick a random table
        if ($tables && is_array($tables) && count($tables) > 0) {
            if ($gc) $this->provider->removeUnused( $tables );
            $table = $tables[array_rand( $tables, 1 )];
        }
        else {
            return; //or return if there are no tables (yet)
        }
        if ($stdTable) $table = $stdTable;

        $table = $db->escape( $table );
        //do not remove columns from association tables
        if (strpos($table,'_')!==false) return;
        //table is still in use? But are all columns in use as well?

        $cols = $this->provider->getWriter()->getTableColumns( $table, $db );

        //$cols = $db->get( $this->provider->getWriter()->getQuery("describe",array(
        //	"table"=>$table
        //)) );
        //pick a random column
        if (count($cols)<1) return;
        $colr = $cols[array_rand( $cols )];
        $col = $db->escape( $colr["Field"] ); //fetch the name and escape
        if ($stdCol) {
            $exists = false;
            $col = $stdCol;
            foreach($cols as $cl) {
                if ($cl["Field"]==$col) {
                    $exists = $cl;
                }
            }
            if (!$exists) {
                return;
            }
            else {
                $colr = $exists;
            }
        }
        if ($col=="id" || strpos($col,"_id")!==false) {
            return; //special column, cant slim it down
        }


        //now we have a table and a column $table and $col
        if ($gc && !intval($db->getCell( $this->provider->getWriter()->getQuery("get_null",array(
            "table"=>$table,
            "col"=>$col
            )
        )))) {
            $db->exec( $this->provider->getWriter()->getQuery("drop_column",array("table"=>$table,"property"=>$col)));
            return;
        }

        //okay so this column is still in use, but maybe its to wide
        //get the field type
        //print_r($colr);
        $currenttype =  $this->writer->sqltype_typeno[$colr["Type"]];
        if ($currenttype > 0) {
            $trytype = rand(0,$currenttype - 1); //try a little smaller
            //add a test column
            $db->exec($this->provider->getWriter()->getQuery("test_column",array(
                "type"=>$this->writer->typeno_sqltype[$trytype],
                "table"=>$table
                )
            ));
            //fill the tinier column with the same values of the original column
            $db->exec($this->provider->getWriter()->getQuery("update_test",array(
                "table"=>$table,
                "col"=>$col
            )));
            //measure the difference
            $delta = $db->getCell($this->provider->getWriter()->getQuery("measure",array(
                "table"=>$table,
                "col"=>$col
            )));
            if (intval($delta)===0) {
            //no difference? then change the column to save some space
                $sql = $this->provider->getWriter()->getQuery("remove_test",array(
                    "table"=>$table,
                    "col"=>$col,
                    "type"=>$this->writer->typeno_sqltype[$trytype]
                ));
                $db->exec($sql);
            }
            //get rid of the test column..
            $db->exec( $this->provider->getWriter()->getQuery("drop_test",array(
                "table"=>$table
                )) );
        }

        //@todo -> querywriter!
        //Can we put an index on this column?
        //Is this column worth the trouble?
        if (
        strpos($colr["Type"],"TEXT")!==false ||
            strpos($colr["Type"],"LONGTEXT")!==false
        ) {
            return;
        }


        $variance = $db->getCell($this->provider->getWriter()->getQuery("variance",array(
            "col"=>$col,
            "table"=>$table
        )));
        $records = $db->getCell($this->provider->getWriter()->getQuery("count",array("type"=>$table)));
        if ($records) {
            $relvar = intval($variance) / intval($records); //how useful would this index be?
            //if this column describes the table well enough it might be used to
            //improve overall performance.
            $indexname = "reddex_".$col;
            if ($records > 1 && $relvar > 0.85) {
                $sqladdindex=$this->provider->getWriter()->getQuery("index1",array(
                    "table"=>$table,
                    "indexname"=>$indexname,
                    "col"=>$col
                ));
                $db->exec( $sqladdindex );
            }
            else {
                $sqldropindex = $this->provider->getWriter()->getQuery("index2",array("table"=>$table,"indexname"=>$indexname));
                $db->exec( $sqldropindex );
            }
        }

        return true;
    }


}