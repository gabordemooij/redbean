<?php
/**
 * RedBean MySQLWriter
 * @package 		RedBean/QueryWriter/MySQL.php
 * @description		Represents a MySQL Database to RedBean
 *					To write a driver for a different database for RedBean
 *					you should only have to change this file.
 * @author			Gabor de Mooij
 * @license			BSD
 */
class RedBean_QueryWriter_MySQL implements RedBean_QueryWriter {

	/**
	 * @var array
	 * Supported Column Types
	 */
    public $typeno_sqltype = array(
    " TINYINT(3) UNSIGNED ",
    " INT(11) UNSIGNED ",
    " BIGINT(20) ",
    " VARCHAR(255) ",
    " TEXT ",
    " LONGTEXT "
    );

	/**
	 *
	 * @var array
	 * Supported Column Types and their
	 * constants (magic numbers)
	 */
    public $sqltype_typeno = array(
    "tinyint(3) unsigned"=>0,
    "int(11) unsigned"=>1,
    "bigint(20)"=>2,
    "varchar(255)"=>3,
    "text"=>4,
    "longtext"=>5
    );

    /**
     * @var array
	 * DTYPES code names of the supported types,
	 * these are used for the column names
     */
    public $dtypes = array(
    "tintyintus","intus","ints","varchar255","text","ltext"
    );

    /**
     *
     * @var RedBean_DBAdapter
     */
    private $adapter;

    /**
     * Constructor
     * The Query Writer Constructor also sets up the database
     * @param RedBean_DBAdapter $adapter
     */
    public function __construct( RedBean_DBAdapter $adapter ) {
        $this->adapter = $adapter;
        $this->adapter->exec("
				CREATE TABLE IF NOT EXISTS `dtyp` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `tinyintus` tinyint(3) unsigned NOT NULL,
				  `intus` int(11) unsigned NOT NULL,
				  `ints` bigint(20) NOT NULL,
				  `varchar255` varchar(255) NOT NULL,
				  `text` text NOT NULL,
				  PRIMARY KEY  (`id`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
		");
		$this->adapter->exec("
                        CREATE TABLE IF NOT EXISTS `__log` (
                        `id` INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
                        `tbl` VARCHAR( 255 ) NOT NULL ,
                        `action` TINYINT( 2 ) NOT NULL ,
                        `itemid` INT( 11 ) NOT NULL 
                        ) ENGINE = MYISAM ;
        ");
    }


    /**
     * Gets a basic SQL query
     * @param array $options
     * @param string $sql_type
     * @return string $sql
	 * @todo: way to complex, eliminate!
     */
    private function getBasicQuery( $options, $sql_type="SELECT" ) {
        extract($options);
        if (isset($fields)) {
            $sqlfields = array();
            foreach($fields as $field) {
                $sqlfields[] = " `$field` ";
            }
            $field = implode(",", $fields);
        }
        if (!isset($field)) $field="";
        $sql = "$sql_type ".$field." FROM `$table` ";
        if (isset($where)) {
            if (is_array($where)) {
                $crit = array();
                foreach($where as $w=>$v) {
                    $crit[] = " `$w` = \"".$v."\"";
                }
                $sql .= " WHERE ".implode(" AND ",$crit);
            }
            else {
                $sql .= " WHERE ".$where;
            }
        }
        return $sql;
    }



    /**
     * Escapes a value using database specific escaping rules
	 * This is actually a pretty dumb function, it just plumbs the
	 * request to the driver. However there can be a difference between
	 * what the driver thinks is a useful escape and what the MySQL query writer
	 * thinks is the best way to escape. In our case, we simply agree with the
	 * driver.
     * @param string $value
     * @return string $escapedValue
     */
    public function escape( $value ) {
        return $this->adapter->escape( $value );
    }

    /**
     * Returns all tables in the database
     * @return array $tables
     */
    public function getTables() {
        return $this->adapter->getCol( "show tables" );
    }

	/**
	 * Creates an empty, column-less table for a bean.
	 * @param <string> $table
	 */
    public function createTable( $table ) {

        $sql = "
                     CREATE TABLE `$table` (
                    `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
                     PRIMARY KEY ( `id` )
                     ) ENGINE = InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
            ";
        $this->adapter->exec( $sql );

    }

	/**
	 * Returns an array containing the column names of the specified table.
	 * @param string $table
	 * @return array $columns
	 */
    public function getColumns( $table ) {
        $columnsRaw = $this->adapter->get("DESCRIBE `$table`");
        foreach($columnsRaw as $r) {
            $columns[$r["Field"]]=$r["Type"];
        }
        return $columns;
    }

	/**
	 * Returns the MySQL Column Type Code (integer) that corresponds
	 * to the given value type.
	 * @param string $value
	 * @return integer $type 
	 */
	public function scanType( $value ) {
        $this->adapter->exec( "truncate table dtyp" );
        $v = "\"".$value."\"";
        $checktypeSQL = "insert into dtyp VALUES(null,$v,$v,$v,$v,$v )";
        $this->adapter->exec( $checktypeSQL );
        $id = $this->adapter->getInsertID();
        $readtypeSQL = $this->getBasicQuery(
            array("fields"=>array("tinyintus","intus","ints","varchar255","text"),
            "table" =>"dtyp",
            "where"=>array("id"=>$id)));
        $row = $this->adapter->getRow($readtypeSQL);;
        $tp = 0;
        foreach($row as $t=>$tv) {
            if (strval($tv) === strval($value)) {
                return $tp;
            }
            $tp++;
        }
        return $tp;
    }

	/**
	 * Adds a column of a given type to a table
	 * @param string $table
	 * @param string $column
	 * @param integer $type
	 */
    public function addColumn( $table, $column, $type ) {
        $type=$this->typeno_sqltype[$type];
        $sql = "ALTER TABLE `$table` ADD `$column` $type ";
        $this->adapter->exec( $sql );
    }

	/**
	 * Returns the Type Code for a Column Description
	 * @param string $typedescription
	 * @return integer $typecode
	 */
    public function code( $typedescription ) {
        return ((isset($this->sqltype_typeno[$typedescription])) ? $this->sqltype_typeno[$typedescription] : -1);
    }

	/**
	 * Change (Widen) the column to the give type.
	 * @param string $table
	 * @param string $column
	 * @param integer $type
	 */
    public function widenColumn( $table, $column, $type ) {
        $newtype = $this->typeno_sqltype[$type];
        $changecolumnSQL = "ALTER TABLE `$table` CHANGE `$column` `$column` $newtype ";
        $this->adapter->exec( $changecolumnSQL );
    }

	/**
	 * Update a record using a series of update values.
	 * @param string $table
	 * @param array $updatevalues
	 * @param integer $id
	 */
    public function updateRecord( $table, $updatevalues, $id) {
        $update = array();
        foreach($updatevalues as $u) {
            $update[] = " `".$u["property"]."` = \"".$u["value"]."\" ";
        }
        $updateSQL = "UPDATE `$table` SET ".implode(",",$update)." WHERE id = ".$id;
        $this->adapter->exec( $updateSQL );
    }

	/**
	 * Inserts a record into the database using a series of insert columns
	 * and corresponding insertvalues. Returns the insert id.
	 * @param string $table
	 * @param array $insertcolumns
	 * @param array $insertvalues
	 * @return integer $insertid
	 */
    public function insertRecord( $table, $insertcolumns, $insertvalues ) {
        if (count($insertvalues)>0) {
            foreach($insertcolumns as $k=>$v) {
                $insertcolumns[$k] = "`".$v."`";
            }
            foreach($insertvalues as $k=>$v) {
                $insertvalues[$k] = "\"".$v."\"";
            }
            $insertSQL = "INSERT INTO `$table`
					  ( id, ".implode(",",$insertcolumns)." )
					  VALUES( null, ".implode(",",$insertvalues)." ) ";

            $this->adapter->exec( $insertSQL );
            return $this->adapter->getInsertID();
        }
        else {
            $this->adapter->exec( $this->getQuery("create", array("table"=>$table)));
            return $this->adapter->getInsertID();
        }
    }

	/**
	 * Selects a record based on type and id.
	 * @param string $type
	 * @param integer $id
	 * @return array $row
	 */
    public function selectRecord($type, $id) {
        $row = $this->adapter->getRow( "SELECT * FROM `$type` WHERE id = ".intval($id) );
        if ($row && is_array($row) && count($row)>0) {
            return $row;
        }
        else {
            throw RedBean_Exception_Security("Could not find bean");
        }
    }

	/**
	 * Deletes a record based on a table, column, value and operator
	 * @param string $table
	 * @param string $column
	 * @param mixed $value
	 * @param string $oper
	 * @todo validate arguments for security
	 */
    public function deleteRecord( $table, $column, $value, $oper="=" ) {
        $this->adapter->exec("DELETE FROM `$table` WHERE `$column` $oper \"$value\" ");
    }


	/**
	 * Gets information about changed records using a type and id and a logid.
	 * RedBean Locking shields you from race conditions by comparing the latest
	 * cached insert id with a the highest insert id associated with a write action
	 * on the same table. If there is any id between these two the record has
	 * been changed and RedBean will throw an exception.
	 * @param  string $type
	 * @param  integer $id
	 * @param  integer $logid
	 * @return integer $numberOfIntermediateChanges
	 */
    public function getLoggedChanges($type, $id, $logid) {
        return $this->adapter->getCell("
        SELECT count(*) FROM __log WHERE tbl=\"$type\" AND itemid=$id AND action=2 AND id >= $logid");

    }

	/**
	 * Adds a Unique index constrain to the table.
	 * @param string $table
	 * @param string $col1
	 * @param string $col2
	 * @return void
	 */
    public function addUniqueIndex( $table,$col1,$col2 ) {
        $r = $this->adapter->get("SHOW INDEX FROM $table");
        $name = "UQ_".$col1."_".$col2;
        if ($r) {
            foreach($r as $i) {
                if ($i["Key_name"]==$name) {
                    return;
                }
            }
        }
        $sql = "ALTER IGNORE TABLE `$table`
                ADD UNIQUE INDEX `$name` (`$col1`, `$col2`)";
        $this->adapter->exec($sql);
    }

	/**
	 * Just Cleans up the log to prevent it from
	 * getting really big.
	 */
    public function cleanUpLog() {
        $maxid = $this->adapter->getCell("SELECT MAX(id) FROM __log");
        $this->adapter->exec("DELETE FROM __log WHERE id < $maxid - 200 ");
    }

}