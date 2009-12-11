<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of MySQLS
 *
 * @author gabordemooij
 */
class RedBean_QueryWriter_MySQLS extends RedBean_QueryWriter_MySQL {
    //put your code here

	/**
	 * @var array
	 * Supported Column Types
	 */
    public $typeno_sqltype = array(
    RedBean_QueryWriter_MySQL::C_DATATYPE_BOOL=>"  SET('1')  ",
	RedBean_QueryWriter_MySQL::C_DATATYPE_UINT8=>" TINYINT(3) UNSIGNED ",
    RedBean_QueryWriter_MySQL::C_DATATYPE_UINT32=>" INT(11) UNSIGNED ",
  	RedBean_QueryWriter_MySQL::C_DATATYPE_DOUBLE=>" DOUBLE ",
    RedBean_QueryWriter_MySQL::C_DATATYPE_TEXT8=>" VARCHAR(255) ",
    RedBean_QueryWriter_MySQL::C_DATATYPE_TEXT16=>" TEXT ",
    RedBean_QueryWriter_MySQL::C_DATATYPE_TEXT32=>" LONGTEXT "
    );

	/**
	 *
	 * @var array
	 * Supported Column Types and their
	 * constants (magic numbers)
	 */
    public $sqltype_typeno = array(
	"set('1')"=>RedBean_QueryWriter_MySQL::C_DATATYPE_BOOL,
    "tinyint(3) unsigned"=>RedBean_QueryWriter_MySQL::C_DATATYPE_UINT8,
    "int(11) unsigned"=>RedBean_QueryWriter_MySQL::C_DATATYPE_UINT32,
    "double" => RedBean_QueryWriter_MySQL::C_DATATYPE_DOUBLE,
    "varchar(255)"=>RedBean_QueryWriter_MySQL::C_DATATYPE_TEXT8,
    "text"=>RedBean_QueryWriter_MySQL::C_DATATYPE_TEXT16,
    "longtext"=>RedBean_QueryWriter_MySQL::C_DATATYPE_TEXT32
    );

	/**
	 * Constructor
	 * @param RedBean_Adapter $adapter
	 * @param boolean $frozen
	 */
	public function __construct( RedBean_Adapter $adapter, $frozen = false ) {
        $this->adapter = $adapter;
		//try{ $this->adapter->exec("set session sql_mode='STRICT_ALL_TABLES'");
		//}catch(Exception $e){}
	}


	/**
	 * Scans the type using PHP.
	 * @param mixed $value
	 * @return integer $typeConstant
	 */
	public function scanType( $value ) {

		if (is_null($value)) {
			return RedBean_QueryWriter_MySQL::C_DATATYPE_BOOL;
		}
		$orig = $value;
		$value = strval($value);
		if ($value=="1" || $value=="" || $value=="0") {
			  return RedBean_QueryWriter_MySQL::C_DATATYPE_BOOL;
		}
	    if (is_numeric($value) && (floor($value)==$value) && $value >= 0 && $value <= 255 ) {
		      return RedBean_QueryWriter_MySQL::C_DATATYPE_UINT8;
	    }
	    if (is_numeric($value) && (floor($value)==$value) && $value >= 0  && $value <= 4294967295 ) {
	      return RedBean_QueryWriter_MySQL::C_DATATYPE_UINT32;
		}
	    if (is_numeric($value)) {
		  return RedBean_QueryWriter_MySQL::C_DATATYPE_DOUBLE;
		}
	    if (strlen($value) <= 255) {
	      return RedBean_QueryWriter_MySQL::C_DATATYPE_TEXT8;
		}
	    return RedBean_QueryWriter_MySQL::C_DATATYPE_TEXT16;
	}



	/**
	 * Selects a record based on type and id.
	 * @param string $type
	 * @param integer $id
	 * @return array $row
	 */
    public function selectRecord($type, $ids) {
		$rows = parent::selectRecord($type, $ids);
		if ($rows) {
			foreach($rows as $key=>$row) {
				foreach($row as $k=>$cell) {
					if ($cell=="") unset( $rows[$key][$k] );
				}
			}
		}
		return $rows;
    }


}

