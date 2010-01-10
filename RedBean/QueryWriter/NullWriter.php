<?php
/**
 * RedBean MySQLWriter
 * @file 		RedBean/QueryWriter/NullWriter.php
 * @description		Represents a NULL Database to RedBean
 *					This class simply registers all actions invoked.
 *					It can be used for so-called white box testing, to see
 *					if your algorithms active the right methods with proper
 *					arguments.
 *				
 * @author			Gabor de Mooij
 * @license			BSD
 */
class RedBean_QueryWriter_NullWriter implements RedBean_QueryWriter {


	/**
	 * DATA TYPE
	 * Boolean Data type
	 * @var integer
	 */
	const C_DATATYPE_BOOL = 0;

	/**
	 * DATA TYPE
	 * Unsigned 8BIT Integer
	 * @var integer
	 */
	const C_DATATYPE_UINT8 = 1;

	/**
	 * DATA TYPE
	 * Unsigned 32BIT Integer
	 * @var integer
	 */
	const C_DATATYPE_UINT32 = 2;

	/**
	 * DATA TYPE
	 * Double precision floating point number and
	 * negative numbers.
	 * @var integer
	 */
	const C_DATATYPE_DOUBLE = 3;

	/**
	 * DATA TYPE
	 * Standard Text column (like varchar255)
	 * At least 8BIT character support.
	 * @var integer
	 */
	const C_DATATYPE_TEXT8 = 4;

	/**
	 * DATA TYPE
	 * Long text column (16BIT)
	 * @var integer
	 */
	const C_DATATYPE_TEXT16 = 5;

	/**
	 * DATA TYPE
	 * 32BIT long textfield (number of characters can be as high as 32BIT) Data type
	 * This is the biggest column that RedBean supports. If possible you may write
	 * an implementation that stores even bigger values.
	 * @var integer
	 */
	const C_DATATYPE_TEXT32 = 6;

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
	 *
	 * @var mixed
	 */
	public $createTableArgument = NULL;

	/**
	 *
	 * @var mixed
	 */

	public $getColumnsArgument = NULL;

	/**
	 *
	 * @var mixed
	 */

	public $scanTypeArgument = NULL;

	/**
	 *
	 * @var array
	 */

	public $addColumnArguments = array();

	/**
	 *
	 * @var mixed
	 */

	public $codeArgument = NULL;

	/**
	 *
	 * @var array
	 */

	public $widenColumnArguments = array();

	/**
	 *
	 * @var array
	 */

	public $updateRecordArguments = array();

	/**
	 *
	 * @var array
	 */

	public $insertRecordArguments = array();

	/**
	 *
	 * @var array
	 */

	public $selectRecordArguments = array();

	/**
	 *
	 * @var array
	 */

	public $deleteRecordArguments = array();

	/**
	 *
	 * @var array
	 */

	public $checkChangesArguments = array();

	/**
	 *
	 * @var array
	 */

	public $addUniqueIndexArguments = array();

	/**
	 *
	 * @var array
	 */
	public $selectByCritArguments = array();

	/**
	 *
	 * @var array
	 */
	public $deleteByCrit = array();


	/**
	 *
	 * @var array
	 */
	public $returnTables = array();


	/**
	 *
	 * @var array
	 */

	public $returnGetColumns = array();

	/**
	 *
	 * @var integer
	 */

	public $returnScanType = 1;

	/**
	 *
	 * @var mixed
	 */
	public $returnAddColumn = NULL;


	/**
	 *
	 * @var mixed
	 */
	public $returnCode = NULL;

	/**
	 *
	 * @var mixed
	 */
	public $returnWidenColumn = NULL;

	/**
	 *
	 * @var mixed
	 */
	public $returnUpdateRecord = NULL;

	/**
	 *
	 * @var mixed
	 */
	public $returnInsertRecord = NULL;

	/**
	 *
	 * @var mixed
	 */
	public $returnSelectRecord = NULL;

	/**
	 *
	 * @var mixed
	 */
	public $returnDeleteRecord = NULL;

	/**
	 *
	 * @var mixed
	 */
	public $returnCheckChanges = NULL;

	/**
	 *
	 * @var mixed
	 */
	public $returnDeleteByCrit = NULL;


	/**
	 *
	 * @var mixed
	 */
	public$returnSelectByCrit = NULL;

	/**
	 *
	 * @var mixed
	 */
	public $returnAddUniqueIndex = NULL;

	public function getTables(){ return $this->returnTables; }
	public function createTable( $table ){ $this->createTableArgument = $table; }
    public function getColumns( $table ){ $this->getColumnsArgument = $table; return $this->returnGetColumns; }
	public function scanType( $value ){ $this->scanTypeArgument = $value; return $this->returnScanType; }
    public function addColumn( $table, $column, $type ){
		$this->addColumnArguments = array( $table, $column, $type );
		return $this->returnAddColumn;
	}

	
    public function code( $typedescription ){ $this->codeArgument = $typedescription;
		return $this->returnCode;
	}
    public function widenColumn( $table, $column, $type ){
		$this->widenColumnArguments = array($table, $column, $type);
		return $this->returnWidenColumn;
	}
    public function updateRecord( $table, $updatevalues, $id){
		$this->updateRecordArguments = array($table, $updatevalues, $id);
		return $this->returnUpdateRecord;
	}
    public function insertRecord( $table, $insertcolumns, $insertvalues ){
		$this->insertRecordArguments = array( $table, $insertcolumns, $insertvalues );
		return $this->returnInsertRecord;
	}
    public function selectRecord($type, $ids){
		$this->selectRecordArguments = array($type, $ids);
		return $this->returnSelectRecord;
	}
	public function deleteRecord( $table, $id){
		$this->deleteRecordArguments = array($table, "id", $id);
		return $this->returnDeleteRecord;
	}
    public function checkChanges($type, $id, $logid){
		$this->checkChangesArguments = array($type, $id, $logid);
		return $this->returnCheckChanges;
	}
	public function addUniqueIndex( $table,$columns ){
		$this->addUniqueIndexArguments=array($table,$columns);
		return $this->returnAddUniqueIndex;
	}

	public function selectByCrit( $select, $table, $column, $value, $withUnion=false ){
		$this->selectByCritArguments=array($select, $table, $column, $value, $withUnion);
		return $this->returnSelectByCrit;
	}

	public function deleteByCrit( $table, $crits ){
		$this->deleteByCrit=array($table, $crits );
		return $this->returnDeleteByCrit;
	}


	public function getIDField( $type ) { return "id"; }

	public function noKW($str) { return $str; }

	/**
	 * Resets the mock object. All public
	 * properties will be assigned values like NULL or an empty
	 * array.
	 */
	public function reset() {
		$this->createTableArgument = NULL;
		$this->getColumnsArgument = NULL;
		$this->scanTypeArgument = NULL;
		$this->addColumnArguments = array();
		$this->codeArgument = NULL;
		$this->widenColumnArguments = array();
		$this->updateRecordArguments = array();
		$this->insertRecordArguments = array();
		$this->selectRecordArguments = array();
		$this->deleteRecordArguments = array();
		$this->checkChangesArguments = array();
		$this->addUniqueIndexArguments = array();

		$this->returnTables = array();
		$this->returnGetColumns = array();
		$this->returnScanType = 1;
		$this->returnAddColumn = NULL;
		$this->returnCode = NULL;
		$this->returnWidenColumn = NULL;
		$this->returnUpdateRecord = NULL;
		$this->returnInsertRecord = NULL;
		$this->returnSelectRecord = NULL;
		$this->returnDeleteRecord = NULL;
		$this->returnCheckChanges = NULL;
		$this->returnAddUniqueIndex = NULL;
	}
}