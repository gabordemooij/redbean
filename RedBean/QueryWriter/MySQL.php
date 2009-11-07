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
    RedBean_QueryWriter::C_DATATYPE_BOOL=>" SET('1') ",
	RedBean_QueryWriter::C_DATATYPE_UINT8=>" TINYINT(3) UNSIGNED ",
    RedBean_QueryWriter::C_DATATYPE_UINT32=>" INT(11) UNSIGNED ",
  	RedBean_QueryWriter::C_DATATYPE_DOUBLE=>" DOUBLE ",
    RedBean_QueryWriter::C_DATATYPE_TEXT8=>" VARCHAR(255) ",
    RedBean_QueryWriter::C_DATATYPE_TEXT16=>" TEXT ",
    RedBean_QueryWriter::C_DATATYPE_TEXT32=>" LONGTEXT "
    );

	/**
	 *
	 * @var array
	 * Supported Column Types and their
	 * constants (magic numbers)
	 */
    public $sqltype_typeno = array(
	"set('1')"=>RedBean_QueryWriter::C_DATATYPE_BOOL,
    "tinyint(3) unsigned"=>RedBean_QueryWriter::C_DATATYPE_UINT8,
    "int(11) unsigned"=>RedBean_QueryWriter::C_DATATYPE_UINT32,
    "double" => RedBean_QueryWriter::C_DATATYPE_DOUBLE,
    "varchar(255)"=>RedBean_QueryWriter::C_DATATYPE_TEXT8,
    "text"=>RedBean_QueryWriter::C_DATATYPE_TEXT16,
    "longtext"=>RedBean_QueryWriter::C_DATATYPE_TEXT32
    );

    /**
     * @var array
	 * DTYPES code names of the supported types,
	 * these are used for the column names
     */
    public $dtypes = array(
    "booleanset","tinyintus","intus","doubles","varchar255","text","ltext"
    );

    /**
     *
     * @var RedBean_DBAdapter
     */
    private $adapter;

	/**
	 * Indicates the field name to be used for primary keys;
	 * default is 'id'
	 * @var string
	 */
	private $idfield = "id";



	/**
	 * Checks table name or column name
	 * @param string $table
	 * @return string $table
	 */
	public function check($table) {
		if (strpos($table,"`")!==false) throw new Redbean_Exception_Security("Illegal chars in table name");
		return $this->adapter->escape($table);
	}

    /**
     * Constructor
     * The Query Writer Constructor also sets up the database
     * @param RedBean_DBAdapter $adapter
     */
    public function __construct( RedBean_DBAdapter $adapter, $frozen = false ) {
        $this->adapter = $adapter;
		if (!$frozen) {
			$this->adapter->exec("DROP TABLE IF EXISTS `dtyp`");
			$this->adapter->exec("
					CREATE TABLE IF NOT EXISTS `dtyp` (
					  `id` int(11) unsigned NOT NULL auto_increment,
					  `booleanset` set('1'),
					  `tinyintus` tinyint(3) unsigned NOT NULL,
					  `intus` int(11) unsigned NOT NULL,
					  `doubles` double NOT NULL,
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
			"); //Must be MyISAM! else you run in trouble if you use transactions!
		}
		$maxid = $this->adapter->getCell("SELECT MAX(id) FROM __log");
        $this->adapter->exec("DELETE FROM __log WHERE id < $maxid - 200 ");
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
	 * @param string $table
	 */
    public function createTable( $table ) {
		$idfield = $this->idfield;
		$table = $this->check($table);
		$sql = "
                     CREATE TABLE `$table` (
                    `$idfield` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
                     PRIMARY KEY ( `$idfield` )
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
		$table = $this->check($table);
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
        $checktypeSQL = "insert into dtyp VALUES(null,$v,$v,$v,$v,$v,$v )";
        $this->adapter->exec( $checktypeSQL );
        $id = $this->adapter->getInsertID();
		$types = $this->dtypes;
		array_pop($types);
        $readtypeSQL = "SELECT ".implode(",",$types)." FROM dtyp WHERE id = $id ";
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
		$column = $this->check($column);
		$table = $this->check($table);
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
        return ((isset($this->sqltype_typeno[$typedescription])) ? $this->sqltype_typeno[$typedescription] : 99);
    }

	/**
	 * Change (Widen) the column to the give type.
	 * @param string $table
	 * @param string $column
	 * @param integer $type
	 */
    public function widenColumn( $table, $column, $type ) {
        $column = $this->check($column);
		$table = $this->check($table);
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
		$idfield = $this->idfield;
		$sql = "UPDATE ".$this->check($table)." SET ";
		$p = $v = array();
		foreach($updatevalues as $uv) {
			$p[] = " `".$uv["property"]."` = ? ";
			$v[]=strval( $uv["value"] );
		}
		$sql .= implode(",", $p ) ." WHERE $idfield = ".intval($id);
		$this->adapter->exec( $sql, $v );
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
		//if ($table == "__log") $idfield="id"; else
		$idfield = $this->idfield;
		$table = $this->check($table);
        if (count($insertvalues)>0 && is_array($insertvalues[0]) && count($insertvalues[0])>0) {
			foreach($insertcolumns as $k=>$v) {
                $insertcolumns[$k] = "`".$this->check($v)."`";
            }
			$insertSQL = "INSERT INTO `$table` ( $idfield, ".implode(",",$insertcolumns)." ) VALUES ";
			$pat = "( NULL, ". implode(",",array_fill(0,count($insertcolumns)," ? "))." )";
			$insertSQL .= implode(",",array_fill(0,count($insertvalues),$pat));
			foreach($insertvalues as $insertvalue) {
				foreach($insertvalue as $v) {
					$vs[] = strval( $v );
				}
			}
			$this->adapter->exec( $insertSQL, $vs );
		    return ($this->adapter->getErrorMsg()=="" ?  $this->adapter->getInsertID() : 0);
        }
        else {
		      $this->adapter->exec( "INSERT INTO `$table` ($idfield) VALUES(NULL) " );
              return ($this->adapter->getErrorMsg()=="" ?  $this->adapter->getInsertID() : 0);
        }
    }



	/**
	 * Selects a record based on type and id.
	 * @param string $type
	 * @param integer $id
	 * @return array $row
	 */
    public function selectRecord($type, $ids) {
		$idfield = $this->idfield;
		$type=$this->check($type);
		$sql = "SELECT * FROM `$type` WHERE $idfield IN ( ".implode(',', array_fill(0, count($ids), " ? "))." )";
		$rows = $this->adapter->get($sql,$ids);
		return ($rows && is_array($rows) && count($rows)>0) ? $rows : NULL;
    }

	/**
	 * Deletes a record based on a table, column, value and operator
	 * @param string $table
	 * @param string $column
	 * @param mixed $value
	 * @param string $oper
	 * @todo validate arguments for security
	 */
    public function deleteRecord( $table, $id) {
		$table = $this->check($table);
		$this->adapter->exec("DELETE FROM `$table` WHERE `".$this->idfield."` = ? ",array(strval($id)));
    }
	
	/**
	 * Adds a Unique index constrain to the table.
	 * @param string $table
	 * @param string $col1
	 * @param string $col2
	 * @return void
	 */
    public function addUniqueIndex( $table,$columns ) {
		sort($columns); //else we get multiple indexes due to order-effects
		foreach($columns as $k=>$v){
			$columns[$k]="`".$this->adapter->escape($v)."`";
		}
		$table = $this->check($table);
        $r = $this->adapter->get("SHOW INDEX FROM $table");
        $name = "UQ_".sha1(implode(',',$columns));
        if ($r) {
            foreach($r as $i) {
                if ($i["Key_name"]==$name) {
                    return;
                }
            }
        }
        $sql = "ALTER IGNORE TABLE `$table`
                ADD UNIQUE INDEX `$name` (".implode(",",$columns).")";
        $this->adapter->exec($sql);
    }
}