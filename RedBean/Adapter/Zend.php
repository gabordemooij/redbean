<?php 
/**
 * Zend Adapter		(Database Adapter)
 * @package 		RedBean/Adapter/Zend.php
 * @description		An adapter class to connect Zend Db Database from
 *					the Zend Framework to RedBean.
 * @author			Gabor de Mooij
 * @license			BSD
 */
class RedBean_Adapter_Zend extends RedBean_Observable implements RedBean_Adapter {

	/**
	 *
	 * @var Zend_Db_Adapter_Abstract
	 */
	private $db = null;
	
	/**
	 * 
	 * @var string
	 */
	private $sql = "";


	private $affected = 0;


	/**
	 *
	 * @param $database
	 * @return unknown_type
	 */
	public function __construct(Zend_Db_Adapter_Abstract $database) {
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
		return $this->db->quote($sqlvalue);
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
		
		if (stripos(trim($sql),"UPDATE")===0){
			$this->affected = $this->db->update( $sql, $aValues );
			return $this->affected;
		}
		else {
			return $this->db->query( $sql, $aValues );
		}



	}

	/**
	 * Multi array SQL fetch
	 * @param $sql
	 * @return unknown_type
	 */
	public function get( $sql, $aValues = array() ) {
		
		$this->sql = $sql;
		$this->signal("sql_exec", $this);
		
		return $this->db->fetchAll( $sql,$aValues );
	}

	/**
	 * SQL row fetch
	 * @param $sql
	 * @return unknown_type
	 */
	public function getRow( $sql, $aValues = array() ) {
		
		$this->sql = $sql;
		$this->signal("sql_exec", $this);
		return $this->db->fetchRow( $sql,$aValues );
	}

	/**
	 * SQL column fetch
	 * @param $sql
	 * @return unknown_type
	 */
	public function getCol( $sql, $aValues = array() ) {
		$this->sql = $sql;
		$this->signal("sql_exec", $this);
		return $this->db->fetchCol( $sql,$aValues );
	}

	/**
	 * Retrieves a single cell
	 * @param $sql
	 * @return unknown_type
	 */
	public function getCell( $sql, $aValues = array() ) {
		
		$this->sql = $sql;
		$this->signal("sql_exec", $this);
		$this->db->fetchOne( $sql, $aValues );
		
	}

	/**
	 * Returns last inserted id
	 * @return unknown_type
	 */
	public function getInsertID() {
		return $this->db->lastInsertId();
	}

	/**
	 * Returns number of affected rows
	 * @return unknown_type
	 */
	public function getAffectedRows() {
		return $this->affected;
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
		return "";
	}

	/**
	 *
	 * Starts a transaction
	 */
	public function startTransaction() {
		return $this->db->beginTransaction();
	}

	/**
	 *
	 * Commits a transaction
	 */
	public function commit() {
		return $this->db->commit();
	}

	/**
	 *
	 * Rolls back transaction
	 */
	public function rollBack() {
		return $this->db->rollback();
	}


}
