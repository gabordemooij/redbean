<?php
/**
 * QueryWriter
 * Interface for QueryWriters
 * @file			RedBean/QueryWriter.php
 * @description		Describes the API for a QueryWriter
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
interface RedBean_QueryWriter {

	/**
	 * QueryWriter Constant Identifier.
	 * Identifies a situation in which a table has not been found in
	 * the database.
	 */
	const C_SQLSTATE_NO_SUCH_TABLE = 1;

	/**
	 * QueryWriter Constant Identifier.
	 * Identifies a situation in which a perticular column has not
	 * been found in the database.
	 */
	const C_SQLSTATE_NO_SUCH_COLUMN = 2;

	/**
	 * QueryWriter Constant Identifier.
	 * Identifies a situation in which a perticular column has not
	 * been found in the database.
	 */
	const C_SQLSTATE_INTEGRITY_CONSTRAINT_VIOLATION = 3;

	/**
	 * Returns the table to store beans of a given type.
	 * @param string $type 
	 */
	public function getFormattedTableName($type);

	/**
	 * Returns the tables that are in the database.
	 * @return array $arrayOfTables
	 */
	public function getTables();

	/**
	 * Creates the table with the specified name.
	 * @param string $table
	 */
	public function createTable( $table );

	/**
	 * Returns an array containing all the columns of the specified table.
	 * @param string $table
	 */
	public function getColumns( $table );

	/**
	 * Returns the type of a certain value.
	 * @param mixed $value
	 * @return integer $type
	 */
	public function scanType( $value );

	/**
	 * Adds the column to $table using column name $column and
	 * making it of type $type.
	 * @param string $table
	 * @param string $column
	 * @param integer $type
	 *
	 */
	public function addColumn( $table, $column, $type );

	/**
	 * Codes an SQL Column Type Description to a RedBean QueryWriter type.
	 * @param integer $typedescription
	 * @return integer $type
	 */
	public function code( $typedescription );

	/**
	 * Widens the column to support type $type.
	 * @param string $table
	 * @param string $column
	 * @param integer $type
	 */
	public function widenColumn( $table, $column, $type );

	/**
	 * Updates a record
	 * @param string $table
	 * @param array $updatevalues
	 * @param integer $id
	 */
	public function updateRecord( $table, $updatevalues, $id=null);

	
	/**
	 * Selects a record
	 * @param string $type
	 * @param integer $ids
	 */
	//public function selectRecord($type, $ids);
	public function selectRecord( $table, $conditions, $addSql = null, $delete = false);


	/**
	 * Adds a UNIQUE constraint index to a table on columns $columns.
	 * @param string $table
	 * @param array $columnsPartOfIndex
	 */
	public function addUniqueIndex( $table,$columns );

	/**
	 * Returns the property that contains the Primary Key ID in an
	 * OODBBean instance.
	 * @param string $tableOfTheBean
	 */
	public function getIDField( $table );


	/**
	 * Returns $str surrounded by keyword protecting / esc symbols
	 * @param string $str
	 */
	public function noKW($str);


	/**
	 * Checks whether the SQL state is in the list of specified states
	 * and returns true if it does appear in this list or false if it
	 * does not.
	 * @param string $state
	 * @param array $list
	 * @return boolean $isInList
	 */
	public function sqlStateIn( $state, $list );

	/**
	 * @abstract
	 * @param  $table
	 * @return void
	 */
	public function wipe($table);

	/**
	 * @abstract
	 * @param  $type
	 * @return void
	 */
	public function count($type);

	/**
	 * @abstract
	 * @param  $table
	 * @return void
	 */
	public function check($table);

	/**
	 * @abstract
	 * @param RedBean_IBeanFormatter $beanFormatter
	 * @return void
	 */
	public function setBeanFormatter(RedBean_IBeanFormatter $beanFormatter);

	/**
	 * @abstract
	 * @param  $referenceTable
	 * @param  $constraints
	 * @param  $viewID
	 * @return void
	 */
	public function createView($referenceTable, $constraints, $viewID);

	/**
	 * @abstract
	 * @param string $type
	 * @return void
	 */
	public function getFieldType( $type = "" );

	/**
	 * @abstract
	 * @param  $name
	 * @param bool $noQuotes
	 * @return void
	 */
	public function safeColumn($name, $noQuotes = false);

	/**
	 * @abstract
	 * @param  $name
	 * @param bool $noQuotes
	 * @return void
	 */
	public function safeTable($name, $noQuotes = false);

	/**
	 * @abstract
	 * @param RedBean_OODBBean $bean1
	 * @param RedBean_OODBBean $bean2
	 * @param bool $dontCache
	 * @return void
	 */
	public function addConstraint( RedBean_OODBBean $bean1, RedBean_OODBBean $bean2, $dontCache = false );

	/**
	 * @abstract
	 * @param  $types
	 * @return void
	 */
	public function getAssocTableFormat($types);

	/**
	 * Adds a foreign key to a certain column.
	 *
	 * @param  $type
	 * @param  $targetType
	 * @param  $field
	 * @param  $targetField
	 * @return void
	 */
	public function addFK( $type, $targetType, $field, $targetField);


}