<?php
/**
 * MySQL Database object driver
 * @desc performs all redbean actions for MySQL
 *
 */
class RedBean_Driver_MySQL implements RedBean_Driver {

	/**
	 *
	 * @var MySQLDatabase instance
	 */
	private static $me = null;

	/**
	 *
	 * @var int
	 */
	public $Insert_ID;

	/**
	 *
	 * @var boolean
	 */
	private $debug = false;

	/**
	 *
	 * @var unknown_type
	 */
	private $rs = null;

	/**
	 * Singleton Design Pattern
	 * @return DB $DB
	 */
	private function __construct(){}

	/**
	 * Gets an instance of the database object (singleton) and connects to the database
	 * @return MySQLDatabase $databasewrapper
	 */
	public static function getInstance( $host, $user, $pass, $dbname ) {

		if (!self::$me) {
			mysql_connect(

			$host,
			$user,
			$pass

			);

			mysql_selectdb( $dbname );

			self::$me = new RedBean_Driver_MySQL();
		}
		return self::$me;
	}

	/**
	 * Retrieves a record or more using an SQL statement
	 * @return array $rows
	 */
	public function GetAll( $sql ) {

		if ($this->debug) {
			echo "<HR>".$sql;
		}

		$rs = mysql_query( $sql );
		$this->rs=$rs;
		$arr = array();
		while( $r = @mysql_fetch_assoc($rs) ) {
			$arr[] = $r;
		}

		if ($this->debug) {

			if (count($arr) > 0) {
				echo "<br><b style='color:green'>resultset: ".count($arr)." rows</b>";
			}

			$str = mysql_error();
			if ($str!="") {
				echo "<br><b style='color:red'>".$str."</b>";
			}
		}

		return $arr;

	}


	/**
	 * Retrieves a column
	 * @param $sql
	 * @return unknown_type
	 */
	public function GetCol( $sql ) {

		$rows = $this->GetAll($sql);
		$cols = array();

		foreach( $rows as $row ) {
			$cols[] = array_shift( $row );
		}

		return $cols;

	}

	/**
	 * Retrieves a single cell
	 * @param $sql
	 * @return unknown_type
	 */
	public function GetCell( $sql ) {

		$arr = $this->GetAll( $sql );

		$row1 = array_shift( $arr );

		$col1 = array_shift( $row1 );

		return $col1;

	}


	/**
	 * Retrieves a single row
	 * @param $sql
	 * @return unknown_type
	 */
	public function GetRow( $sql ) {

		$arr = $this->GetAll( $sql );

		return array_shift( $arr );

	}

	/**
	 * Returns latest error number
	 * @return unknown_type
	 */
	public function ErrorNo() {
		return mysql_errno();
	}

	/**
	 * Returns latest error message
	 * @return unknown_type
	 */
	public function Errormsg() {
		return mysql_error();
	}



	/**
	 * Executes an SQL statement and returns the number of
	 * affected rows.
	 * @return int $affected
	 */
	public function Execute( $sql ) {


		if ($this->debug) {
			echo "<HR>".$sql;
		}

		$rs = mysql_query( $sql );
		$this->rs=$rs;

		if ($this->debug) {
			$str = mysql_error();
			if ($str!="") {
				echo "<br><b style='color:red'>".$str."</b>";
			}
		}

		$this->Insert_ID = $this->GetInsertID();

		return intval( mysql_affected_rows());

	}

	/**
	 * Prepares a string for usage in SQL code
	 * @see IDB#esc()
	 */
	public function Escape( $str ) {
		return mysql_real_escape_string( $str );
	}


	/**
	 * Returns the insert id of an insert query
	 * @see IDB#getInsertID()
	 */
	public function GetInsertID() {
		return intval( mysql_insert_id());
	}


	/**
	 * Return the number of rows affected by the latest query
	 * @return unknown_type
	 */
	public function Affected_Rows() {
		return mysql_affected_rows();
	}

	public function setDebugMode($tf) {
		$this->debug = $tf;
	}

	public function getRaw() {
		return $this->rs;
	}

}

