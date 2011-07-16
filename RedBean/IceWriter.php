<?php
 /**
 * @name RedBean IceWriter
 * @file RedBean/IceWriter.php
 * @author Gabor de Mooij and the RedBean Team
 * @copyright Gabor de Mooij (c)
 * @license BSD
 *
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
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


}
