<?php 
/**
 * Adapter for ADODB database layer AND RedBean
 * @author gabordemooij
 *
 */
class RedBean_DBAdapter {

	/**
	 *
	 * @var ADODB
	 */
	private $db = null;

	public static $log = array();

	/**
	 *
	 * @param $database
	 * @return unknown_type
	 */
	public function __construct($database) {
		$this->db = $database;
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
	public function exec( $sql ) {
		self::$log[] = $sql;
		return $this->db->Execute( $sql );
	}

	/**
	 * Multi array SQL fetch
	 * @param $sql
	 * @return unknown_type
	 */
	public function get( $sql ) {
		self::$log[] = $sql;
		return $this->db->GetAll( $sql );
	}

	/**
	 * SQL row fetch
	 * @param $sql
	 * @return unknown_type
	 */
	public function getRow( $sql ) {
		self::$log[] = $sql;
		return $this->db->GetRow( $sql );
	}

	/**
	 * SQL column fetch
	 * @param $sql
	 * @return unknown_type
	 */
	public function getCol( $sql ) {
		self::$log[] = $sql;
		return $this->db->GetCol( $sql );
	}

	/**
	 * Retrieves a single cell
	 * @param $sql
	 * @return unknown_type
	 */
	public function getCell( $sql ) {
		self::$log[] = $sql;
		$arr = $this->db->GetCol( $sql );
		if ($arr && is_array($arr))	return ($arr[0]); else return false;
	}

	/**
	 * Returns last inserted id
	 * @return unknown_type
	 */
	public function getInsertID() {
		// self::$log[] = $sql;
		return $this->db->getInsertID();
	}

	/**
	 * Returns number of affected rows
	 * @return unknown_type
	 */
	public function getAffectedRows() {
		// self::$log[] = $sql;
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
