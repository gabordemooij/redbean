<?php 
/**
 * Interface for database drivers
 * @package 		RedBean/Driver.php
 * @description		Describes the API for database classes
 * @author			Gabor de Mooij
 * @license			BSD
 */
interface RedBean_Driver {

	/**
	 * Implements Singleton
	 * Requests an instance of the database 
	 * @param $host
	 * @param $user
	 * @param $pass
	 * @param $dbname
	 * @return RedBean_Driver $driver
	 */
	public static function getInstance( $host, $user, $pass, $dbname );

	/**
	 * Runs a query and fetches results as a multi dimensional array
	 * @param $sql
	 * @return array $results
	 */
	public function GetAll( $sql, $aValues=array() );

	/**
	 * Runs a query and fetches results as a column
	 * @param $sql
	 * @return array $results
	 */
	public function GetCol( $sql, $aValues=array() );

	/**
	 * Runs a query an returns results as a single cell
	 * @param $sql
	 * @return mixed $cellvalue
	 */
	public function GetCell( $sql, $aValues=array() );

	/**
	 * Runs a query and returns a flat array containing the values of
	 * one row
	 * @param $sql
	 * @return array $row
	 */
	public function GetRow( $sql, $aValues=array() );

	/**
	 * Returns the error constant of the most
	 * recent error
	 * @return mixed $error
	 */
	public function ErrorNo();

	/**
	 * Returns the error message of the most recent
	 * error
	 * @return string $message
	 */
	public function Errormsg();

	/**
	 * Runs an SQL query
	 * @param $sql
	 * @return void
	 */
	public function Execute( $sql, $aValues=array() );

	/**
	 * Escapes a value according to the
	 * escape policies of the current database instance
	 * @param $str
	 * @return string $escaped_str
	 */
	public function Escape( $str );

	/**
	 * Returns the latest insert_id value
	 * @return integer $id
	 */
	public function GetInsertID();

	/**
	 * Returns the number of rows affected
	 * by the latest query
	 * @return integer $id
	 */
	public function Affected_Rows();

	/**
	 * Toggles debug mode (printing queries on screen)
	 * @param $tf
	 * @return void
	 */
	public function setDebugMode( $tf );

	/**
	 * Returns the unwrapped version of the database object;
	 * the raw database driver.
	 * @return mixed $database
	 */
	public function GetRaw();

	/**
	 * Commits a transaction
	 */
	public function CommitTrans();

	/**
	 * Starts a transaction
	 */
	public function StartTrans();

	/**
	 * Rolls back a transaction
	 */
	public function FailTrans();

	
}
