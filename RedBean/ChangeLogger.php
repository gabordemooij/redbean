<?php

class RedBean_ChangeLogger implements RedBean_Observer {

    /**
     *
     * @var RedBean_DBAdapter
     */
    private $writer;

    public function __construct(RedBean_QueryWriter $writer) {
        $this->writer = $writer;
        $this->writer->deleteRecord("__log", "logstamp", ((microtime(1)*100)-1000), "<" );
    }


    public function onEvent( $event, $item ) {

        $id = $item->id;
        if (! ((int) $id)) return;
        $type = $item->__info["type"];
        $time =  microtime(1)*100;
        if ($event=="open") {
            $this->writer->insertRecord("__log",array("action","tbl","itemid","logstamp"),
            array(1,  $type, $id, $time));
            $item->__info["opened"] = $time;
            //echo "\n opening item on: $time ";
        }
        if ($event=="update") {
            $oldstamp = $item->__info["opened"];
            //echo "\n this item was opened on: $oldstamp ";
            $sql = "SELECT count(*) FROM __log WHERE tbl=\"$type\" AND itemid=$id AND action=2 AND logstamp >= $oldstamp ";

            //echo "\n".$sql;
            $r = $this->writer->getLoggedChanges($type,$id,$oldstamp);
            if ($r) throw new RedBean_Exception_FailedAccessBean("Locked, failed to access (type:$type, id:$id)");
            //$sql = "INSERT INTO __log (id,action,tbl,itemid,logstamp) VALUES (NULL, 2, \"$type\", $id, $time ) ";
            $this->writer->insertRecord("__log",array("action","tbl","itemid","logstamp"),
            array(2,  $type, $id, $time));
            //echo "\n".$sql;
            //$db->exec( $sql );
            $item->__info["opened"] = $time;
            //echo "\n updating opened time item to: $time ";

            
        }
    }
}