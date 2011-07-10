<?php
/**
 * Created by JetBrains PhpStorm.
 * User: prive
 * Date: 06-04-11
 * Time: 11:54
 * To change this template use File | Settings | File Templates.
 */
 
interface RedBean_IceWriter {

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
	 * Returns the property that contains the Primary Key ID in an
	 * OODBBean instance.
	 * @param string $tableOfTheBean
	 */
	public function getIDField( $type );


	/**
	 * Returns $str surrounded by keyword protecting / esc symbols
	 * @param string $str
	 */
	public function noKW($str);

	public function count($type);

	public function check($table);

	public function setBeanFormatter(RedBean_IBeanFormatter $beanFormatter);

	public function safeColumn($name, $noQuotes = false);

	public function safeTable($name, $noQuotes = false);


}
