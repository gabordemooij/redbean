<?php
/**
 * RedBean MySQLWriter
 * @package 		RedBean/QueryWriter/MySQL.php
 * @description		Writes Queries for MySQL Databases
 * @author			Gabor de Mooij
 * @license			BSD
 */
class RedBean_QueryWriter_MySQL implements RedBean_QueryWriter {

    public $typeno_sqltype = array(
    " TINYINT(3) UNSIGNED ",
    " INT(11) UNSIGNED ",
    " BIGINT(20) ",
    " VARCHAR(255) ",
    " TEXT ",
    " LONGTEXT "
    );
    public $sqltype_typeno = array(
    "tinyint(3) unsigned"=>0,
    "int(11) unsigned"=>1,
    "bigint(20)"=>2,
    "varchar(255)"=>3,
    "text"=>4,
    "longtext"=>5
    );

    /**
     * @var array all dtype types
     */
    public $dtypes = array(
    "tintyintus","intus","ints","varchar255","text","ltext"
    );


    private $adapter;

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
    }




    /**
     *
     * @param $options
     * @return string $query
     */
    private function getQueryWiden( $options ) {
        extract($options);
        return "ALTER TABLE `$table` CHANGE `$column` `$column` $newtype ";
    }

    /**
     *
     * @param $options
     * @return string $query
     */
    private function getQueryAddColumn( $options ) {
        extract($options);
        return "ALTER TABLE `$table` ADD `$column` $type ";
    }

    /**
     *
     * @param $options
     * @return string $query
     */
    private function getQueryUpdate( $options ) {
        extract($options);
        $update = array();
        foreach($updatevalues as $u) {
            $update[] = " `".$u["property"]."` = \"".$u["value"]."\" ";
        }
        return "UPDATE `$table` SET ".implode(",",$update)." WHERE id = ".$id;
    }

    /**
     *
     * @param $options
     * @return string $query
     */
    private function getQueryInsert( $options ) {

        extract($options);

        foreach($insertcolumns as $k=>$v) {
            $insertcolumns[$k] = "`".$v."`";
        }

        foreach($insertvalues as $k=>$v) {
            $insertvalues[$k] = "\"".$v."\"";
        }

        $insertSQL = "INSERT INTO `$table`
					  ( id, ".implode(",",$insertcolumns)." ) 
					  VALUES( null, ".implode(",",$insertvalues)." ) ";
        return $insertSQL;
    }

    /**
     *
     * @param $options
     * @return string $query
     */
    private function getQueryCreate( $options ) {
        extract($options);
        return "INSERT INTO `$table` (id) VALUES(null) ";
    }

    /**
     *
     * @param $options
     * @return string $query
     */
    private function getQueryInferType( $options ) {
        extract($options);
        $v = "\"".$value."\"";
        $checktypeSQL = "insert into dtyp VALUES(null,$v,$v,$v,$v,$v )";
        return $checktypeSQL;
    }

    /**
     *
     * @return string $query
     */
    private function getQueryResetDTYP() {
        return "truncate table dtyp";
    }





    /**
     * Gets a basic SQL query
     * @param array $options
     * @param string $sql_type
     * @return string $sql
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
     * (non-PHPdoc)
     * @see RedBean/QueryWriter#getQuery()
     */
    private function getQuery( $queryname, $params=array() ) {
    //echo "<br><b style='color:yellow'>$queryname</b>";
        switch($queryname) {
            case "create_table":
                return $this->getQueryCreateTable($params);
                break;
            case "widen_column":
                return $this->getQueryWiden($params);
                break;
            case "add_column":
                return $this->getQueryAddColumn($params);
                break;
            case "update":
                return $this->getQueryUpdate($params);
                break;
            case "insert":
                return $this->getQueryInsert($params);
                break;
            case "create":
                return $this->getQueryCreate($params);
                break;
            case "infertype":
                return $this->getQueryInferType($params);
                break;
            case "readtype":
                return $this->getBasicQuery(
                array("fields"=>array("tinyintus","intus","ints","varchar255","text"),
                "table" =>"dtyp",
                "where"=>array("id"=>$params["id"])));
                break;
            case "reset_dtyp":
                return $this->getQueryResetDTYP();
                break;
            case "get_bean":
                return $this->getBasicQuery(array("field"=>"*","table"=>$params["type"],"where"=>array("id"=>$params["id"])));
                break;
            default:
                throw new Exception("QueryWriter has no support for Query:".$queryname);
        }
    }

    /**
     * Escapes a value using database specific escaping rules
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


    public function createTable( $table ) {

        $sql = "
                     CREATE TABLE `$table` (
                    `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
                     PRIMARY KEY ( `id` )
                     ) ENGINE = InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
            ";
        $this->adapter->exec( $sql );

    }

    public function getColumns( $table ) {

        $columnsRaw = $this->adapter->get("DESCRIBE `$table`");

        foreach($columnsRaw as $r) {
            $columns[$r["Field"]]=$r["Type"];
        }

        return $columns;

    }

    public function scanType( $value ) {

        $this->adapter->exec( $this->getQuery("reset_dtyp") );

        $checktypeSQL = $this->getQuery("infertype", array(
            "value"=> $this->escape(strval($value))
        ));

        $this->adapter->exec( $checktypeSQL );
        $id = $this->adapter->getInsertID();

        $readtypeSQL = $this->getQuery("readtype",array(
            "id"=>$id
        ));

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

    public function addColumn( $table, $column, $type ) {

        $sql = $this->getQuery("add_column",array(
            "table"=>$table,
            "column"=>$column,
            "type"=> $this->typeno_sqltype[$type]
        ));

        $this->adapter->exec( $sql );

    }

    public function code( $typedescription ) {
        return $this->sqltype_typeno[$typedescription];
    }


    public function widenColumn( $table, $column, $type ) {

        $changecolumnSQL = $this->getQuery( "widen_column", array(
            "table" => $table,
            "column" => $column,
            "newtype" => $this->typeno_sqltype[$type]
            ) );
        $this->adapter->exec( $changecolumnSQL );

    }

    public function updateRecord( $table, $updatevalues, $id) {

        $updateSQL = $this->getQuery("update", array(
            "table"=>$table,
            "updatevalues"=>$updatevalues,
            "id"=>$id
        ));

        $this->adapter->exec( $updateSQL );
    }

    public function insertRecord( $table, $insertcolumns, $insertvalues ) {

        if (count($insertvalues)>0) {

            $insertSQL = $this->getQuery("insert",array(
                "table"=>$table,
                "insertcolumns"=>$insertcolumns,
                "insertvalues"=>$insertvalues
            ));
            $this->adapter->exec( $insertSQL );
            return $this->adapter->getInsertID();
        }
        else {
            $this->adapter->exec( $this->getQuery("create", array("table"=>$table)));
            return $this->adapter->getInsertID();
        }
    }


    public function selectRecord($type, $id) {
        $getSQL = $this->getQuery("get_bean",array(
            "type"=>$type,
            "id"=>$id
        ));
        $row = $this->adapter->getRow( $getSQL );

        if ($row && is_array($row) && count($row)>0) {
            return $row;
        }
        else {
            throw RedBean_Exception_Security("Could not find bean");
        }
    }

    public function deleteRecord( $table, $id ) {
        $this->adapter->exec("DELETE FROM `$table` WHERE id = $id ");
    }



}