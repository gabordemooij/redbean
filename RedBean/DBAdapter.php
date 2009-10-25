<?php 
/**
 * DBAdapter (Database Adapter)
 * @package 		RedBean/DBAdapter.php
 * @description		An adapter class to connect various database systems to RedBean
 * @author			Gabor de Mooij
 * @license			BSD
 */
class RedBean_DBAdapter extends RedBean_Observable {

	/**
	 *
	 * @var ADODB
	 */
	private $db = null;
	
	/**
	 * 
	 * @var string
	 */
	private $sql = "";


	/**
	 *
	 * @param $database
	 * @return unknown_type
	 */
	public function __construct($database) {
		$this->db = $database;
	}

	/**
	 * 
	 * @return unknown_type
	 */
	public function getSQL() {
		return $this->sql;
	}

	/**
	 * Escapes a string for use in a Query
	 * @param $sqlvalue
	 * @return unknown_type
	 */
	public function escape( $sqlvalue ) {
		return $this->db->Escape($sqlvalue);
	}

	

	/**
	 * Executes SQL code
	 * @param $sql
	 * @return unknown_type
	 */
	public function exec( $sql , $aValues=array(), $noevent=false) {
		
		if (!$noevent){
			$this->sql = $sql;
			$this->signal("sql_exec", $this);
		}
		return $this->db->Execute( $sql, $aValues );
	}

	/**
	 * Multi array SQL fetch
	 * @param $sql
	 * @return unknown_type
	 */
	public function get( $sql, $aValues = array() ) {
		
		$this->sql = $sql;
		$this->signal("sql_exec", $this);
		
		return $this->db->GetAll( $sql,$aValues );
	}

	/**
	 * SQL row fetch
	 * @param $sql
	 * @return unknown_type
	 */
	public function getRow( $sql, $aValues = array() ) {
		
		$this->sql = $sql;
		$this->signal("sql_exec", $this);
		return $this->db->GetRow( $sql,$aValues );
	}

	/**
	 * SQL column fetch
	 * @param $sql
	 * @return unknown_type
	 */
	public function getCol( $sql, $aValues = array() ) {
		$this->sql = $sql;
		$this->signal("sql_exec", $this);
		return $this->db->GetCol( $sql,$aValues );
	}

	/**
	 * Retrieves a single cell
	 * @param $sql
	 * @return unknown_type
	 */
	public function getCell( $sql, $aValues = array() ) {
		
		$this->sql = $sql;
		$this->signal("sql_exec", $this);
		$arr = $this->db->getCol( $sql, $aValues );
		if ($arr && is_array($arr))	return ($arr[0]); else return false;
	}

	/**
	 * Returns last inserted id
	 * @return unknown_type
	 */
	public function getInsertID() {
		return $this->db->getInsertID();
	}

	/**
	 * Returns number of affected rows
	 * @return unknown_type
	 */
	public function getAffectedRows() {
		return $this->db->Affected_Rows();
	}
	
	/**
	 * Unwrap the original database object
	 * @return $database
	 */
	public function getDatabase() {
		return $this->db;
	}
	
	/**
	 * Return latest error message
	 * @return string $message
	 */
	public function getErrorMsg() {
		return $this->db->Errormsg();
	}

	/**
	 *
	 * Starts a transaction
	 */
	public function startTransaction() {
		return $this->db->StartTrans();
	}

	/**
	 *
	 * Commits a transaction
	 */
	public function commit() {
		return $this->db->CommitTrans();
	}

	/**
	 *
	 * Rolls back transaction
	 */
	public function rollback() {
		return $this->db->FailTrans();
	}


}
