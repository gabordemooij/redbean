<?php

class RedBean_QueryWriter_SQLiteT extends RedBean_QueryWriter_SQLite {

	/**
	 * Here we describe the datatypes that RedBean
	 * Uses internally. If you write a QueryWriter for
	 * RedBean you should provide a list of types like this.
	 */

	/**
	 * DATA TYPE
	 * Integer Data type
	 * @var integer
	 */
	const C_DATATYPE_INTEGER = 0;

	/**
	 * DATA TYPE
	 * Numeric Data type (for REAL and date/time)
	 * @var integer
	 */
	const C_DATATYPE_NUMERIC = 1;

	/**
	 * DATA TYPE
	 * Text type
	 * @var integer
	 */
	const C_DATATYPE_TEXT = 2;

	/**
	 * DATA TYPE
	 * Specified. This means the developer or DBA
	 * has altered the column to a different type not
	 * recognized by RedBean. This high number makes sure
	 * it will not be converted back to another type by accident.
	 * @var integer
	 */
	const C_DATATYPE_SPECIFIED = 99;




	/**
	 * @var array
	 * Supported Column Types
	 */
	public $typeno_sqltype = array(
			  RedBean_QueryWriter_SQLiteT::C_DATATYPE_INTEGER=>"INTEGER",
			  RedBean_QueryWriter_SQLiteT::C_DATATYPE_NUMERIC=>"NUMERIC",
			  RedBean_QueryWriter_SQLiteT::C_DATATYPE_TEXT=>"TEXT",
	);

	/**
	 *
	 * @var array
	 * Supported Column Types and their
	 * constants (magic numbers)
	 */
	public $sqltype_typeno = array(
			  "INTEGER"=>RedBean_QueryWriter_SQLiteT::C_DATATYPE_INTEGER,
			  "NUMERIC"=>RedBean_QueryWriter_SQLiteT::C_DATATYPE_NUMERIC,
			  "TEXT"=>RedBean_QueryWriter_SQLiteT::C_DATATYPE_TEXT,
	);


	/**
	 * Returns the MySQL Column Type Code (integer) that corresponds
	 * to the given value type.
	 *
	 * @param  string $value value
	 * 
	 * @return integer $type type
	 */
	public function scanType( $value ) {


		if (is_numeric($value) && (intval($value)==$value)) return self::C_DATATYPE_INTEGER;
		if (is_numeric($value)
				  || preg_match("/\d\d\d\d\-\d\d\-\d\d/",$value)
				  || preg_match("/\d\d\d\d\-\d\d\-\d\d\s\d\d:\d\d:\d\d/",$value)
		) {
			return self::C_DATATYPE_NUMERIC;
		}
		return self::C_DATATYPE_TEXT;
	}

	/**
	 * Adds a column of a given type to a table
	 *
	 * @param string  $table  table
	 * @param string  $column column
	 * @param integer $type	  type
	 */
	public function addColumn( $table, $column, $type) {
		$table = $this->getFormattedTableName($table);
		$column = $this->check($column);
		$table = $this->check($table);
		$type=$this->typeno_sqltype[$type];
		$sql = "ALTER TABLE `$table` ADD `$column` $type ";
		$this->adapter->exec( $sql );
	}

	/**
	 * Returns the Type Code for a Column Description
	 *
	 * @param string $typedescription description
	 *
	 * @return integer $typecode code
	 */
	public function code( $typedescription ) {
		return ((isset($this->sqltype_typeno[$typedescription])) ? $this->sqltype_typeno[$typedescription] : 99);
	}

	/**
	 * Quote Items, to prevent issues with reserved words.
	 *
	 * @param array $items items to quote
	 *
	 * @return $quotedfItems quoted items
	 */
	private function quote( $items ) {
		foreach($items as $k=>$item) {
			$items[$k]=$this->noKW($item);
		}
		return $items;
	}

	/**
	 * Change (Widen) the column to the give type.
	 *
	 * @param string  $table  table to widen
	 * @param string  $column column to widen
	 * @param integer $type   new column type
	 */
	public function widenColumn( $table, $column, $type ) {
		$table = $this->getFormattedTableName($table);
		$idfield = $this->idfield;
		$column = $this->check($column);
		$table = $this->check($table);
		$newtype = $this->typeno_sqltype[$type];
		$oldColumns = $this->getColumns($table);
		$oldColumnNames = $this->quote(array_keys($oldColumns));
		$newTableDefStr="";
		foreach($oldColumns as $oldName=>$oldType) {
			if ($oldName != $idfield) {
				if ($oldName!=$column) {
					$newTableDefStr .= ",`$oldName` $oldType";
				}
				else {
					$newTableDefStr .= ",`$oldName` $newtype";
				}
			}
		}
	
		$q = array();
		$q[] = "DROP TABLE IF EXISTS tmp_backup;";
		$q[] = "CREATE TEMPORARY TABLE tmp_backup(".implode(",",$oldColumnNames).");";
		$q[] = "INSERT INTO tmp_backup SELECT * FROM `$table`;";
		$q[] = "DROP TABLE `$table`;";
		$q[] = "CREATE TABLE `$table` ( `$idfield` INTEGER PRIMARY KEY AUTOINCREMENT  $newTableDefStr  );";
		$q[] = "INSERT INTO `$table` SELECT * FROM tmp_backup;";
		$q[] = "DROP TABLE tmp_backup;";
		foreach($q as $sq) {
			$this->adapter->exec($sq);
		}


	}



}
