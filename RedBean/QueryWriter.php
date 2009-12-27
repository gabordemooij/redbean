<?php 
/**
 * QueryWriter
 * Interface for QueryWriters
 * @file 		RedBean/QueryWriter.php
 * @description		Describes the API for a QueryWriter
 * @author			Gabor de Mooij
 * @license			BSD
 */
interface RedBean_QueryWriter {
	
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

}