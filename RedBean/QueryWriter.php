<?php 
/**
 * QueryWriter
 * Interface for QueryWriters
 * @package 		RedBean/QueryWriter.php
 * @description		Describes the API for a QueryWriter
 * @author			Gabor de Mooij
 * @license			BSD
 */
interface RedBean_QueryWriter {

	/**
	 * Here we describe the datatypes that RedBean
	 * Uses internally. If you write a QueryWriter for
	 * RedBean you should provide support for these types
	 * by mapping them on database specific column types.
	 */

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
	 * Checks a table.
	 * @param string $table
	 */
	public function check( $table );
}