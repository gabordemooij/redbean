<?php
/**
 * RedBean NullWriter
 * @file				RedBean/QueryWriter/NullWriter.php
 * @description	Represents a NULL Database to RedBean
 *						This class simply registers all actions invoked.
 *						It can be used for so-called white box testing, to see
 *						if your algorithms active the right methods with proper
 *						arguments.
 *
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_QueryWriter_NullWriter extends RedBean_AQueryWriter implements RedBean_QueryWriter {


	/**
	 * @var integer
	 *
	 * DATA TYPE
	 * Boolean Data type
	 * 
	 */
	const C_DATATYPE_BOOL = 0;

	/**
	 * @var integer
	 *
	 * DATA TYPE
	 * Unsigned 8BIT Integer
	 * 
	 */
	const C_DATATYPE_UINT8 = 1;

	/**
	 * @var integer
	 *
	 * DATA TYPE
	 * Unsigned 32BIT Integer
	 * 
	 */
	const C_DATATYPE_UINT32 = 2;

	/**
	 * @var integer
	 *
	 * DATA TYPE
	 * Double precision floating point number and
	 * negative numbers.
	 * 
	 */
	const C_DATATYPE_DOUBLE = 3;

	/**
	 * @var integer
	 *
	 * DATA TYPE
	 * Standard Text column (like varchar255)
	 * At least 8BIT character support.
	 * 
	 */
	const C_DATATYPE_TEXT8 = 4;

	/**
	 * @var integer
	 *
	 * DATA TYPE
	 * Long text column (16BIT)
	 * 
	 */
	const C_DATATYPE_TEXT16 = 5;

	/**
	 * @var integer
	 *
	 * DATA TYPE
	 * 32BIT long textfield (number of characters can be as high as 32BIT) Data type
	 * This is the biggest column that RedBean supports. If possible you may write
	 * an implementation that stores even bigger values.
	 * 
	 */
	const C_DATATYPE_TEXT32 = 6;

	/**
	 * @var integer
	 *
	 * DATA TYPE
	 * Specified. This means the developer or DBA
	 * has altered the column to a different type not
	 * recognized by RedBean. This high number makes sure
	 * it will not be converted back to another type by accident.
	 * 
	 */
	const C_DATATYPE_SPECIFIED = 99;


	/**
	 * Part of test system. This property captures interactions
	 * between this object and the one that is being tested.
	 * Used for scanning behavior of objects that use query writers.
	 *
	 * @var mixed
	 */
	public $createTableArgument = NULL;

	/**
	 * Part of test system. This property captures interactions
	 * between this object and the one that is being tested.
	 * Used for scanning behavior of objects that use query writers.
	 *
	 * @var mixed
	 */
	public $getColumnsArgument = NULL;

	/**
	 * Part of test system. This property captures interactions
	 * between this object and the one that is being tested.
	 * Used for scanning behavior of objects that use query writers.
	 *
	 * @var mixed
	 */
	public $scanTypeArgument = NULL;

	/**
	 * Part of test system. This property captures interactions
	 * between this object and the one that is being tested.
	 * Used for scanning behavior of objects that use query writers.
	 *
	 * @var mixed
	 */
	public $addColumnArguments = array();

	/**
	 * Part of test system. This property captures interactions
	 * between this object and the one that is being tested.
	 * Used for scanning behavior of objects that use query writers.
	 *
	 * @var mixed
	 */
	public $codeArgument = NULL;


	/**
	 * Part of test system. This property captures interactions
	 * between this object and the one that is being tested.
	 * Used for scanning behavior of objects that use query writers.
	 *
	 * @var mixed
	 */
	public $widenColumnArguments = array();

	/**
	 * Part of test system. This property captures interactions
	 * between this object and the one that is being tested.
	 * Used for scanning behavior of objects that use query writers.
	 *
	 * @var mixed
	 */
	public $updateRecordArguments = array();

	/**
	 * Part of test system. This property captures interactions
	 * between this object and the one that is being tested.
	 * Used for scanning behavior of objects that use query writers.
	 * 
	 * @var mixed
	 */
	public $insertRecordArguments = array();

	/**
	 * Part of test system. This property captures interactions
	 * between this object and the one that is being tested.
	 * Used for scanning behavior of objects that use query writers.
	 * 
	 * @var mixed
	 */
	public $selectRecordArguments = array();

	/**
	 * Part of test system. This property captures interactions
	 * between this object and the one that is being tested.
	 * Used for scanning behavior of objects that use query writers.
	 * 
	 * @var mixed
	 */
	public $deleteRecordArguments = array();

	/**
	 * Part of test system. This property captures interactions
	 * between this object and the one that is being tested.
	 * Used for scanning behavior of objects that use query writers.
	 * 
	 * @var mixed
	 */
	public $checkChangesArguments = array();

	/**
	 * Part of test system. This property captures interactions
	 * between this object and the one that is being tested.
	 * Used for scanning behavior of objects that use query writers.
	 * 
	 * @var mixed
	 */
	public $addUniqueIndexArguments = array();

	/**
	 * Part of test system. This property captures interactions
	 * between this object and the one that is being tested.
	 * Used for scanning behavior of objects that use query writers.
	 *
	 * @var mixed
	 */
	public $selectByCritArguments = array();

	/**
	 * Part of test system. This property captures interactions
	 * between this object and the one that is being tested.
	 * Used for scanning behavior of objects that use query writers.
	 * 
	 * @var mixed
	 */
	public $deleteByCrit = array();


	/**
	 * Part of test system. This property captures interactions
	 * between this object and the one that is being tested.
	 * Used for scanning behavior of objects that use query writers.
	 *
	 * @var mixed
	 */
	public $returnTables = array();


	/**
	 * Part of test system. This property captures interactions
	 * between this object and the one that is being tested.
	 * Used for scanning behavior of objects that use query writers.
	 *
	 * @var mixed
	 */
	public $returnGetColumns = array();

	/**
	 * Part of test system. This property captures interactions
	 * between this object and the one that is being tested.
	 * Used for scanning behavior of objects that use query writers.
	 *
	 * @var mixed
	 */
	public $returnScanType = 1;


	/**
	 * Part of test system. This property captures interactions
	 * between this object and the one that is being tested.
	 * Used for scanning behavior of objects that use query writers.
	 *
	 * @var mixed
	 */
	public $returnAddColumn = NULL;


	/**
	 * Part of test system. This property captures interactions
	 * between this object and the one that is being tested.
	 * Used for scanning behavior of objects that use query writers.
	 *
	 * @var mixed
	 */
	public $returnCode = NULL;

	/**
	 * Part of test system. This property captures interactions
	 * between this object and the one that is being tested.
	 * Used for scanning behavior of objects that use query writers.
	 *
	 * @var mixed
	 */
	public $returnWidenColumn = NULL;

	/**
	 * Part of test system. This property captures interactions
	 * between this object and the one that is being tested.
	 * Used for scanning behavior of objects that use query writers.
	 *
	 * @var mixed
	 */
	public $returnUpdateRecord = NULL;

	/**
	 * Part of test system. This property captures interactions
	 * between this object and the one that is being tested.
	 * Used for scanning behavior of objects that use query writers.
	 * 
	 * @var mixed
	 */
	public $returnInsertRecord = NULL;


	/**
	 * Part of test system. This property captures interactions
	 * between this object and the one that is being tested.
	 * Used for scanning behavior of objects that use query writers.
	 *
	 * @var mixed
	 */
	public $returnSelectRecord = NULL;

	/**
	 * Part of test system. This property captures interactions
	 * between this object and the one that is being tested.
	 * Used for scanning behavior of objects that use query writers.
	 *
	 * @var mixed
	 */
	public $returnDeleteRecord = NULL;

	/**
	 * Part of test system. This property captures interactions
	 * between this object and the one that is being tested.
	 * Used for scanning behavior of objects that use query writers.
	 *
	 * @var mixed
	 */
	public $returnCheckChanges = NULL;

	/**
	 * Part of test system. This property captures interactions
	 * between this object and the one that is being tested.
	 * Used for scanning behavior of objects that use query writers.
	 *
	 * @var mixed
	 */
	public $returnDeleteByCrit = NULL;


	/**
	 * Part of test system. This property captures interactions
	 * between this object and the one that is being tested.
	 * Used for scanning behavior of objects that use query writers.
	 *
	 * @var mixed
	 */
	public$returnSelectByCrit = NULL;

	/**
	 * Part of test system. This property captures interactions
	 * between this object and the one that is being tested.
	 * Used for scanning behavior of objects that use query writers.
	 *
	 * @var mixed
	 */
	public $returnAddUniqueIndex = NULL;


	/**
	 * For testing purposes only. Returnes a predefined
	 * value.
	 *
	 * @return mixed
	 */
	public function getTables() {
		return $this->returnTables;
	}

	/**
	 * For testing purposes only. Returnes a predefined
	 * value.
	 *
	 * @return mixed
	 */
	public function createTable( $table ) {
		$this->createTableArgument = $table;
	}

	/**
	 * For testing purposes only. Returnes a predefined
	 * value.
	 *
	 * @return mixed
	 */
	public function getColumns( $table ) {
		$this->getColumnsArgument = $table;
		return $this->returnGetColumns;
	}

	/**
	 * For testing purposes only. Returnes a predefined
	 * value.
	 *
	 * @return mixed
	 */
	public function scanType( $value ) {
		$this->scanTypeArgument = $value;
		return $this->returnScanType;
	}

	/**
	 * For testing purposes only. Returnes a predefined
	 * value.
	 *
	 * @return mixed
	 */
	public function addColumn( $table, $column, $type ) {
		$this->addColumnArguments = array( $table, $column, $type );
		return $this->returnAddColumn;
	}

	/**
	 * For testing purposes only. Returnes a predefined
	 * value.
	 *
	 * @return mixed
	 */
	public function code( $typedescription ) {
		$this->codeArgument = $typedescription;
		return $this->returnCode;
	}
	/**
	 * For testing purposes only. Returnes a predefined
	 * value.
	 *
	 * @return mixed
	 */
	public function widenColumn( $table, $column, $type ) {
		$this->widenColumnArguments = array($table, $column, $type);
		return $this->returnWidenColumn;
	}
	/**
	 * For testing purposes only. Returnes a predefined
	 * value.
	 *
	 * @return mixed
	 */
	public function updateRecord( $table, $updatevalues, $id) {
		$this->updateRecordArguments = array($table, $updatevalues, $id);
		return $this->returnUpdateRecord;
	}
	/**
	 * For testing purposes only. Returnes a predefined
	 * value.
	 *
	 * @return mixed
	 */
	public function insertRecord( $table, $insertcolumns, $insertvalues ) {
		$this->insertRecordArguments = array( $table, $insertcolumns, $insertvalues );
		return $this->returnInsertRecord;
	}
	/**
	 * For testing purposes only. Returnes a predefined
	 * value.
	 *
	 * @return mixed
	 */
	public function selectRecord($type, $ids) {
		$this->selectRecordArguments = array($type, $ids);
		return $this->returnSelectRecord;
	}
	/**
	 * For testing purposes only. Returnes a predefined
	 * value.
	 *
	 * @return mixed
	 */
	public function deleteRecord( $table, $id) {
		$this->deleteRecordArguments = array($table, "id", $id);
		return $this->returnDeleteRecord;
	}
	/**
	 * For testing purposes only. Returnes a predefined
	 * value.
	 *
	 * @return mixed
	 */
	public function checkChanges($type, $id, $logid) {
		$this->checkChangesArguments = array($type, $id, $logid);
		return $this->returnCheckChanges;
	}
	/**
	 * For testing purposes only. Returnes a predefined
	 * value.
	 *
	 * @return mixed
	 */
	public function addUniqueIndex( $table,$columns ) {
		$this->addUniqueIndexArguments=array($table,$columns);
		return $this->returnAddUniqueIndex;
	}
	/**
	 * For testing purposes only. Returnes a predefined
	 * value.
	 *
	 * @return mixed
	 */
	public function selectByCrit( $select, $table, $column, $value, $withUnion=false ) {
		$this->selectByCritArguments=array($select, $table, $column, $value, $withUnion);
		return $this->returnSelectByCrit;
	}
	/**
	 * For testing purposes only. Returnes a predefined
	 * value.
	 *
	 * @return mixed
	 */
	public function deleteByCrit( $table, $crits ) {
		$this->deleteByCrit=array($table, $crits );
		return $this->returnDeleteByCrit;
	}

	/**
	 * For testing purposes only. Returnes a predefined
	 * value.
	 *
	 * @return mixed
	 */
	public function getIDField( $type ) {
		return "id";
	}
	/**
	 * For testing purposes only. Returnes a predefined
	 * value.
	 *
	 * @return mixed
	 */
	public function noKW($str) {
		return $str;
	}
	/**
	 * For testing purposes only. Returnes a predefined
	 * value.
	 *
	 * @return mixed
	 */
	public function sqlStateIn($state,$list) {
		return true;
	}

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