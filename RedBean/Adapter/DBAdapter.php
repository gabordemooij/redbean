<?php
/**
 * DBAdapter		(Database Adapter)
 * @file				RedBean/Adapter/DBAdapter.php
 * @description	An adapter class to connect various database systems to RedBean
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_Adapter_DBAdapter extends RedBean_Observable implements RedBean_Adapter {

	/**
	 * @var RedBean_Driver
	 *
	 * ADODB compatible class
	 */
	private $db = null;

	/**
	 * @var string
	 *
	 * Contains SQL snippet
	 */
	private $sql = "";


	/**
	 * Constructor.
	 * Creates an instance of the RedBean Adapter Class.
	 * This class provides an interface for RedBean to work
	 * with ADO compatible DB instances.
	 *
	 * @param RedBean_Driver $database ADO Compatible DB Instance
	 */
	public function __construct($database) {
		$this->db = $database;
	}

	/**
	 * Returns the latest SQL Statement.
	 *
	 * @return string $SQL latest SQL statement
	 */
	public function getSQL() {
		return $this->sql;
	}

	/**
	 * Escapes a string for use in a Query.
	 *
	 * @param  string $sqlvalue SQL value to escape
	 *
	 * @return string $escapedValue escaped value
	 */
	public function escape( $sqlvalue ) {
		return $this->db->Escape($sqlvalue);
	}

	/**
	 * Executes SQL code; any query without
	 * returning a resultset.
	 * This function allows you to provide an array with values to bind
	 * to query parameters. For instance you can bind values to question
	 * marks in the query. Each value in the array corresponds to the
	 * question mark in the query that matches the position of the value in the
	 * array. You can also bind values using explicit keys, for instance
	 * array(":key"=>123) will bind the integer 123 to the key :key in the
	 * SQL.
	 *
	 * @param  string  $sql			SQL Code to execute
	 * @param  array   $values		assoc. array binding values
	 * @param  boolean $noevent   if TRUE this will suppress the event 'sql_exec'
	 *
	 * @return mixed  $undefSet	whatever driver returns, undefined
	 */
	public function exec( $sql , $aValues=array(), $noevent=false) {
		if (!$noevent) {
			$this->sql = $sql;
			$this->signal("sql_exec", $this);
		}
		return $this->db->Execute( $sql, $aValues );
	}

	/**
	 * Multi array SQL fetch. Fetches a multi dimensional array.
	 * This function allows you to provide an array with values to bind
	 * to query parameters. For instance you can bind values to question
	 * marks in the query. Each value in the array corresponds to the
	 * question mark in the query that matches the position of the value in the
	 * array. You can also bind values using explicit keys, for instance
	 * array(":key"=>123) will bind the integer 123 to the key :key in the
	 * SQL.
	 *
	 * @param  string $sql		SQL code to execute
	 * @param  array  $values	assoc. array binding values
	 *
	 * @return array  $result	two dimensional array result set
	 */
	public function get( $sql, $aValues = array() ) {
		$this->sql = $sql;
		$this->signal("sql_exec", $this);
		return $this->db->GetAll( $sql,$aValues );
	}

	/**
	 * Executes SQL and fetches a single row.
	 * This function allows you to provide an array with values to bind
	 * to query parameters. For instance you can bind values to question
	 * marks in the query. Each value in the array corresponds to the
	 * question mark in the query that matches the position of the value in the
	 * array. You can also bind values using explicit keys, for instance
	 * array(":key"=>123) will bind the integer 123 to the key :key in the
	 * SQL.
	 *
	 * @param  string $sql		SQL code to execute
	 * @param  array  $values	assoc. array binding values
	 *
	 * @return array	$result	one dimensional array result set
	 */
	public function getRow( $sql, $aValues = array() ) {
		$this->sql = $sql;
		$this->signal("sql_exec", $this);
		return $this->db->GetRow( $sql,$aValues );
	}

	/**
	 * Executes SQL and returns a one dimensional array result set.
	 * This function rotates the result matrix to obtain a column result set.
	 * This function allows you to provide an array with values to bind
	 * to query parameters. For instance you can bind values to question
	 * marks in the query. Each value in the array corresponds to the
	 * question mark in the query that matches the position of the value in the
	 * array. You can also bind values using explicit keys, for instance
	 * array(":key"=>123) will bind the integer 123 to the key :key in the
	 * SQL.
	 *
	 * @param  string $sql		SQL code to execute
	 * @param  array  $values	assoc. array binding values
	 *
	 * @return array  $result	one dimensional array result set
	 */
	public function getCol( $sql, $aValues = array() ) {
		$this->sql = $sql;
		$this->signal("sql_exec", $this);
		return $this->db->GetCol( $sql,$aValues );
	}


	/**
	 * Executes an SQL Query and fetches the first two columns only.
	 * Then this function builds an associative array using the first
	 * column for the keys and the second result column for the
	 * values. For instance: SELECT id, name FROM... will produce
	 * an array like: id => name.
	 * This function allows you to provide an array with values to bind
	 * to query parameters. For instance you can bind values to question
	 * marks in the query. Each value in the array corresponds to the
	 * question mark in the query that matches the position of the value in the
	 * array. You can also bind values using explicit keys, for instance
	 * array(":key"=>123) will bind the integer 123 to the key :key in the
	 * SQL.
	 *
	 * @param  string $sql		SQL code to execute
	 * @param  array  $values	assoc. array binding values
	 *
	 * @return array  $result	multi dimensional assoc. array result set
	 */
	public function getAssoc( $sql, $aValues = array() ) {
		$this->sql = $sql;
		$this->signal("sql_exec", $this);
		$rows = $this->db->GetAll( $sql, $aValues );
		$assoc = array();
		if ($rows) {
			foreach($rows as $row) {
				if (count($row)>0) {
					$key = array_shift($row);
				}

				if (count($row)>0) {
					$value = array_shift($row);
				}
				else {
					$value = $key;
				}

				$assoc[ $key ] = $value;
			}
		}
		return $assoc;
	}


	/**
	 * Retrieves a single cell.
	 * This function allows you to provide an array with values to bind
	 * to query parameters. For instance you can bind values to question
	 * marks in the query. Each value in the array corresponds to the
	 * question mark in the query that matches the position of the value in the
	 * array. You can also bind values using explicit keys, for instance
	 * array(":key"=>123) will bind the integer 123 to the key :key in the
	 * SQL.
	 *
	 * @param  string $sql	  sql code to execute
	 * @param  array  $values assoc. array binding values
	 *
	 * @return array  $result scalar result set
	 */

	public function getCell( $sql, $aValues = array(), $noSignal = null ) {
		$this->sql = $sql;
		if (!$noSignal) $this->signal("sql_exec", $this);
		$arr = $this->db->getCol( $sql, $aValues );
		if ($arr && is_array($arr))	return ($arr[0]); else return false;
	}

	/**
	 * Returns latest insert id, most recently inserted id.
	 *
	 * @return integer $id latest insert ID
	 */
	public function getInsertID() {
		return $this->db->getInsertID();
	}

	/**
	 * Returns number of affected rows.
	 *
	 * @return integer $numOfAffectRows
	 */
	public function getAffectedRows() {
		return $this->db->Affected_Rows();
	}

	/**
	 * Unwrap the original database object.
	 *
	 * @return RedBean_Driver $database	returns the inner database object
	 */
	public function getDatabase() {
		return $this->db;
	}

	/**
	 * Return latest error message.
	 *
	 * @return string $message most recent error message
	 */
	public function getErrorMsg() {
		return $this->db->Errormsg();
	}

	/**
	 * Transactions.
	 * Part of the transaction management infrastructure of RedBean.
	 * Starts a transaction.
	 */
	public function startTransaction() {
		return $this->db->StartTrans();
	}

	/**
	 * Transactions.
	 * Part of the transaction management infrastructure of RedBean.
	 * Commits a transaction.
	 */
	public function commit() {
		return $this->db->CommitTrans();
	}

	/**
	 * Transactions.
	 * Part of the transaction management infrastructure of RedBean.
	 * Rolls back transaction.
	 */
	public function rollback() {
		return $this->db->FailTrans();
	}

}
