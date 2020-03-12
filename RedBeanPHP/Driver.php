<?php

namespace RedBeanPHP;

/**
 * Interface for database drivers.
 * The Driver API conforms to the ADODB pseudo standard
 * for database drivers.
 *
 * @file       RedBeanPHP/Driver.php
 * @author     Gabor de Mooij and the RedBeanPHP Community
 * @license    BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
interface Driver
{
	/**
	 * Runs a query and fetches results as a multi dimensional array.
	 *
	 * @param string $sql      SQL query to execute
	 * @param array  $bindings list of values to bind to SQL snippet
	 *
	 * @return array
	 */
	public function GetAll( $sql, $bindings = array() );

	/**
	 * Runs a query and fetches results as a column.
	 *
	 * @param string $sql      SQL query to execute
	 * @param array  $bindings list of values to bind to SQL snippet
	 *
	 * @return array
	 */
	public function GetCol( $sql, $bindings = array() );

	/**
	 * Runs a query and returns results as a single cell.
	 *
	 * @param string $sql      SQL query to execute
	 * @param array  $bindings list of values to bind to SQL snippet
	 *
	 * @return mixed
	 */
	public function GetOne( $sql, $bindings = array() );

	/**
	 * Runs a query and returns results as an associative array
	 * indexed by the first column.
	 *
	 * @param string $sql      SQL query to execute
	 * @param array  $bindings list of values to bind to SQL snippet
	 *
	 * @return mixed
	 */
	public function GetAssocRow( $sql, $bindings = array() );

	/**
	 * Runs a query and returns a flat array containing the values of
	 * one row.
	 *
	 * @param string $sql      SQL query to execute
	 * @param array  $bindings list of values to bind to SQL snippet
	 *
	 * @return array
	 */
	public function GetRow( $sql, $bindings = array() );

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
	 * @param string $sql      SQL query to execute
	 * @param array  $bindings list of values to bind to SQL snippet
	 *
	 * @return array Affected Rows
	 */
	public function Execute( $sql, $bindings = array() );

	/**
	 * Returns the latest insert ID if driver does support this
	 * feature.
	 *
	 * @return integer
	 */
	public function GetInsertID();

	/**
	 * Returns the number of rows affected by the most recent query
	 * if the currently selected driver driver supports this feature.
	 *
	 * @return integer
	 */
	public function Affected_Rows();

	/**
	 * Returns a cursor-like object from the database.
	 *
	 * @param string $sql      SQL query to execute
	 * @param array  $bindings list of values to bind to SQL snippet
	 *
	 * @return mixed
	 */
	public function GetCursor( $sql, $bindings = array() );

	/**
	 * Toggles debug mode. In debug mode the driver will print all
	 * SQL to the screen together with some information about the
	 * results.
	 *
	 * This method is for more fine-grained control. Normally
	 * you should use the facade to start the query debugger for
	 * you. The facade will manage the object wirings necessary
	 * to use the debugging functionality.
	 *
	 * Usage (through facade):
	 *
	 * <code>
	 * R::debug( TRUE );
	 * ...rest of program...
	 * R::debug( FALSE );
	 * </code>
	 *
	 * The example above illustrates how to use the RedBeanPHP
	 * query debugger through the facade.
	 *
	 * @param boolean $trueFalse turn on/off
	 * @param Logger  $logger    logger instance
	 *
	 * @return void
	 */
	public function setDebugMode( $tf, $customLogger );

	/**
	 * Starts a transaction.
	 *
	 * @return void
	 */
	public function CommitTrans();

	/**
	 * Commits a transaction.
	 *
	 * @return void
	 */
	public function StartTrans();

	/**
	 * Rolls back a transaction.
	 *
	 * @return void
	 */
	public function FailTrans();

	/**
	 * Resets the internal Query Counter.
	 *
	 * @return self
	 */
	public function resetCounter();

	/**
	 * Returns the number of SQL queries processed.
	 *
	 * @return integer
	 */
	public function getQueryCount();
}
