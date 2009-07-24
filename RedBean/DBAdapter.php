<?php 
/**
 * Adapter for ADODB database layer AND RedBean
 * @author gabordemooij
 *
 */
class RedBean_DBAdapter extends RedBean_Observable {

	/**
	 *
	 * @var ADODB
	 */
	private $db = null;
	
	private $sql = "";

	
	/**
	 *
	 * @param $database
	 * @return unknown_type
	 */
	public function __construct($database) {
		$this->db = $database;
	}
	
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
	public function exec( $sql , $noevent=false) {
		
		if (!$noevent){
			$this->sql = $sql;
			$this->signal("sql_exec", $this);
		}
		return $this->db->Execute( $sql );
	}

	/**
	 * Multi array SQL fetch
	 * @param $sql
	 * @return unknown_type
	 */
	public function get( $sql ) {
		
		$this->sql = $sql;
		$this->signal("sql_exec", $this);
		
		
		return $this->db->GetAll( $sql );
	}

	/**
	 * SQL row fetch
	 * @param $sql
	 * @return unknown_type
	 */
	public function getRow( $sql ) {
		
		$this->sql = $sql;
		$this->signal("sql_exec", $this);
		
		
		return $this->db->GetRow( $sql );
	}

	/**
	 * SQL column fetch
	 * @param $sql
	 * @return unknown_type
	 */
	public function getCol( $sql ) {
		
		$this->sql = $sql;
		$this->signal("sql_exec", $this);
		
		
		return $this->db->GetCol( $sql );
	}

	/**
	 * Retrieves a single cell
	 * @param $sql
	 * @return unknown_type
	 */
	public function getCell( $sql ) {
		
		$this->sql = $sql;
		$this->signal("sql_exec", $this);
		
		
		$arr = $this->db->GetCol( $sql );
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

}
