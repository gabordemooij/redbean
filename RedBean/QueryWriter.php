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
	public function updateRecord( $table, $updatevalues, $id);

	/**
	 * Inserts a record
	 * @param string $table
	 * @param array $insertcolumns
	 * @param array $insertvalues
	 */
	public function insertRecord( $table, $insertcolumns, $insertvalues );

	/**
	 * Selects a record
	 * @param string $type
	 * @param integer $ids
	 */
	public function selectRecord($type, $ids);

	/**
	 * Removes a record from a table
	 * @param string $table
	 * @param integer $id
	 */
	public function deleteRecord( $table, $id );

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
	 * Selects a set of columns using criteria.
	 * @param string $select - the column to be selected
	 * @param string $table - the name of the table
	 * @param string $column - name of the column that needs to be compared
	 * @param string $value - value to compare against
	 * @param boolean $withUnion - whether you want a union with inverted column
	 */
	public function selectByCrit( $select, $table, $column, $value, $withUnion=false );

	/**
	 * Deletes by criteria.
	 * @param string $table
	 * @param array $crits
	 */
	public function deleteByCrit( $table, $crits );

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
	 * Returns a snippet of SQL to filter records using SQL and a list of
	 * keys.
	 *
	 * @param string  $idfield ID Field to use for selecting primary key
	 * @param array   $keys		List of keys to use for filtering
	 * @param string  $sql		SQL to append, if any
	 * @param boolean $inverse Whether you want to inverse the selection
	 *
	 * @return string $snippet SQL Snippet crafted by function
	 */
	public function getSQLSnippetFilter( $idfield, $keys, $sql=null, $inverse=false );


}