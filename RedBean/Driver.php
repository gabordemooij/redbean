<?php
/**
 * Interface for database drivers
 *
 * @file			RedBean/Driver.php
 * @description		Describes the API for database classes
 *					The Driver API conforms to the ADODB pseudo standard
 *					for database drivers.
 * @author			Gabor de Mooij and the RedBeanPHP Community
 * @license			BSD/GPLv2
 *
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
interface RedBean_Driver {
	/**
	 * Runs a query and fetches results as a multi dimensional array.
	 *
	 * @param  string $sql SQL to be executed
	 *
	 * @return array $results result
	 */
	public function GetAll( $sql, $aValues=array() );

	/**
	 * Runs a query and fetches results as a column.
	 *
	 * @param  string $sql SQL Code to execute
	 *
	 * @return array	$results Resultset
	 */
	public function GetCol( $sql, $aValues=array() );

	/**
	 * Runs a query an returns results as a single cell.
	 *
	 * @param string $sql SQL to execute
	 *
	 * @return mixed $cellvalue result cell
	 */
	public function GetCell( $sql, $aValues=array() );

	/**
	 * Runs a query and returns a flat array containing the values of
	 * one row.
	 *
	 * @param string $sql SQL to execute
	 *
	 * @return array $row result row
	 */
	public function GetRow( $sql, $aValues=array() );

	/**
	 * Executes SQL code and allows key-value binding.
	 * This function allows you to provide an array with values to bind
	 * to query parameters. For instance you can bind values to question
	 * marks in the query. Each value in the array corresponds to the
	 * question mark in the query that matches the position of the value in the
	 * array. You can also bind values using explicit keys, for instance
	 * array(":key"=>123) will bind the integer 123 to the key :key in the
	 * SQL. This method has no return value.
	 *
	 * @param string $sql	  SQL Code to execute
	 * @param array  $aValues Values to bind to SQL query
	 *
	 * @return void
	 */
	public function Execute( $sql, $aValues=array() );

	/**
	 * Escapes a string for use in SQL using the currently selected
	 * driver driver.
	 *
	 * @param string $string string to be escaped
	 *
	 * @return string $string escaped string
	 */
	public function Escape( $str );

	/**
	 * Returns the latest insert ID if driver does support this
	 * feature.
	 *
	 * @return integer $id primary key ID
	 */
	public function GetInsertID();


	/**
	 * Returns the number of rows affected by the most recent query
	 * if the currently selected driver driver supports this feature.
	 *
	 * @return integer $numOfRows number of rows affected
	 */
	public function Affected_Rows();

	/**
	 * Toggles debug mode. In debug mode the driver will print all
	 * SQL to the screen together with some information about the
	 * results. All SQL code that passes through the driver will be
	 * passes on to the screen for inspection.
	 * This method has no return value.
	 *
	 * @param boolean $trueFalse turn on/off
	 *
	 * @return void
	 */
	public function setDebugMode( $tf );

	/**
	 * Starts a transaction.
	 * This method is part of the transaction mechanism of
	 * RedBeanPHP. All queries in a transaction are executed together.
	 * In case of an error all commands will be rolled back so none of the
	 * SQL in the transaction will affect the DB. Using transactions is
	 * considered best practice.
	 * This method has no return value.
	 *
	 * @return void
	 */
	public function CommitTrans();

	/**
	 * Commits a transaction.
	 * This method is part of the transaction mechanism of
	 * RedBeanPHP. All queries in a transaction are executed together.
	 * In case of an error all commands will be rolled back so none of the
	 * SQL in the transaction will affect the DB. Using transactions is
	 * considered best practice.
	 * This method has no return value.
	 *
	 * @return void
	 */
	public function StartTrans();

	/**
	 * Rolls back a transaction.
	 * This method is part of the transaction mechanism of
	 * RedBeanPHP. All queries in a transaction are executed together.
	 * In case of an error all commands will be rolled back so none of the
	 * SQL in the transaction will affect the DB. Using transactions is
	 * considered best practice.
	 * This method has no return value.
	 *
	 * @return void
	 */
	public function FailTrans();
}
