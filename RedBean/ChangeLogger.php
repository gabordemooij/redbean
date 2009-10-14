<?php

class RedBean_ChangeLogger implements RedBean_Observer {

    /**
     *
     * @var RedBean_DBAdapter
     */
    private $db;

    public function __construct(RedBean_DBAdapter $db) {
        $this->db = $db;

        $this->db->exec("
                          CREATE TABLE IF NOT EXISTS `__log` (
                        `id` INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                        `tbl` VARCHAR( 255 ) NOT NULL ,
                        `action` TINYINT( 2 ) NOT NULL ,
                        `itemid` INT( 11 ) NOT NULL ,
                        `logstamp` BIGINT( 20 ) unsigned NOT NULL
                        ) ENGINE = MYISAM ;

                    ");
    }


    public function onEvent( $event, $item ) {

        $db = $this->db;
        $id = $item->id;
        if (! ((int) $id)) return;
        $type = $item->__info["type"];
        $time =  microtime(1)*100;
        if ($event=="open") {
            $sql = "INSERT INTO __log  (id,action,tbl,itemid,logstamp) VALUES (NULL, 1, \"$type\", $id, $time ) ";
            //echo "\n".$sql;
            $db->exec( $sql );
            $item->__info["opened"] = $time;
            //echo "\n opening item on: $time ";
        }
        if ($event=="update") {
            $oldstamp = $item->__info["opened"];
            //echo "\n this item was opened on: $oldstamp ";
            $sql = "SELECT count(*) FROM __log WHERE tbl=\"$type\" AND itemid=$id AND action=2 AND logstamp >= $oldstamp ";
            //echo "\n".$sql;
            $r = $db->getCell($sql);
            if ($r) throw new Exception("Locked, failed to access");
            $sql = "INSERT INTO __log (id,action,tbl,itemid,logstamp) VALUES (NULL, 2, \"$type\", $id, $time ) ";
            //echo "\n".$sql;
            $db->exec( $sql );
            $item->__info["opened"] = $time;
            //echo "\n updating opened time item to: $time ";

            
        }
    }
}