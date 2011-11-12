<?php
/**
 * PDO Driver
 * @file				RedBean/PDO.php
 * @description	PDO Driver
 *						This Driver implements the RedBean Driver API
 * @author			Desfrenes
 * @license			BSD
 *
 *
 * (c) Desfrenes & Gabor de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 *
 */
class RedBean_Driver_PDO implements RedBean_Driver {


	/**
	 * @var string
	 * Contains database DSN for connecting to database.
	 */
	private $dsn;

	/**
	 * @var RedBean_Driver_PDO
	 * Holds the instance of this class.
	 */
	private static $instance;

	/**
	 * @var boolean
	 * Whether we are in debugging mode or not.
	 */
	private $debug = false;

	/**
	 * @var PDO
	 * Holds the PDO instance.
	 */
	private $pdo;

	/**
	 * @var integer
	 * Holds integer number of affected rows from latest query
	 * if driver supports this feature.
	 */
	private $affected_rows;

	/**
	 * @var resource
	 * Holds result resource.
	 */
	private $rs;

	/**
	 * @var boolean
	 * Flag, indicates whether SQL execution has taken place.
	 */
	private $exc =0;

	/**
	 * @var array
	 * Contains arbitrary connection data.
	 *
	 */
	private $connectInfo = array();


	/**
	 * @var bool
	 * Whether you want to use classic String Only binding -
	 * backward compatibility.
	 */
	public $flagUseStringOnlyBinding = false;

	/**
	 *
	 * @var boolean
	 *
	 * Whether we are currently connected or not.
	 * This flag is being used to delay the connection until necessary.
	 * Delaying connections is a good practice to speed up scripts that
	 * don't need database connectivity but for some reason want to
	 * init RedbeanPHP.
	 */
	private $isConnected = false;


	/**
	 * Returns an instance of the PDO Driver.
	 *
	 * @param string $dsn    Database connection string
	 * @param string $user   DB account to be used
	 * @param string $pass   password
	 *
	 * @return RedBean_Driver_PDO $pdo	  PDO wrapper instance
	 */
	public static function getInstance($dsn, $user, $pass) {
		if(is_null(self::$instance)) {
			self::$instance = new RedBean_Driver_PDO($dsn, $user, $pass);
		}
		return self::$instance;
	}

	/**
	 * Constructor. You may either specify dsn, user and password or
	 * just give an existing PDO connection.
	 * Examples:
	 *    $driver = new RedBean_Driver_PDO($dsn, $user, $password);
	 *    $driver = new RedBean_Driver_PDO($existingConnection);
	 *
	 * @param string|PDO  $dsn	 database connection string
	 * @param string      $user optional
	 * @param string      $pass optional
	 *
	 * @return void
	 */
	public function __construct($dsn, $user = NULL, $pass = NULL) {
		if ($dsn instanceof PDO) {
			$this->pdo = $dsn;
			$this->isConnected = true;
			$this->pdo->setAttribute(1002, 'SET NAMES utf8');
			$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
			// make sure that the dsn at least contains the type
			$this->dsn = $this->getDatabaseType();
		} else {
			$this->dsn = $dsn;
			$this->connectInfo = array( "pass"=>$pass, "user"=>$user );
		}
	}

	/**
	 * Establishes a connection to the database using PHP PDO
	 * functionality. If a connection has already been established this
	 * method will simply return directly. This method also turns on
	 * UTF8 for the database and PDO-ERRMODE-EXCEPTION as well as
	 * PDO-FETCH-ASSOC.
	 *
	 * @return void
	 */
	public function connect() {

		if ($this->isConnected) return;
		$user = $this->connectInfo["user"];
		$pass = $this->connectInfo["pass"];
		//PDO::MYSQL_ATTR_INIT_COMMAND
		$this->pdo = new PDO(
				  $this->dsn,
				  $user,
				  $pass,
				  array(1002 => 'SET NAMES utf8',
							 PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
							 PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

				  )
		);
		$this->pdo->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, TRUE);
		$this->isConnected = true;
	}

	/**
	 * Binds parameters. This method binds parameters to a PDOStatement for
	 * Query Execution. This method binds parameters as NULL, INTEGER or STRING
	 * and supports both named keys and question mark keys.
	 *
	 * @param  PDOStatement $s       PDO Statement instance
	 * @param  array        $aValues values that need to get bound to the statement
	 *
	 * @return void
	 */
	protected function bindParams($s,$aValues) {
		foreach($aValues as $key=>&$value) {
			if (is_integer($key)) {

				if (is_null($value)){
					$s->bindValue($key+1,null,PDO::PARAM_NULL);
				}elseif (!$this->flagUseStringOnlyBinding && RedBean_QueryWriter_AQueryWriter::canBeTreatedAsInt($value) && $value < 2147483648) {
					$s->bindParam($key+1,$value,PDO::PARAM_INT);
				}
				else {
					$s->bindParam($key+1,$value,PDO::PARAM_STR);
				}
			}
			else {

				if (is_null($value)){
					$s->bindValue($key,null,PDO::PARAM_NULL);
				}
				elseif (!$this->flagUseStringOnlyBinding && RedBean_QueryWriter_AQueryWriter::canBeTreatedAsInt($value) &&  $value < 2147483648) {
					$s->bindParam($key,$value,PDO::PARAM_INT);
				}
				else {
					$s->bindParam($key,$value,PDO::PARAM_STR);
				}
			}

		}
	}


	/**
	 * Runs a query and fetches results as a multi dimensional array.
	 *
	 * @param  string $sql SQL to be executed
	 *
	 * @return array $results result
	 */
	public function GetAll( $sql, $aValues=array() ) {
		$this->connect();
		$this->exc = 0;
		if ($this->debug) {
			echo "<HR>" . $sql.print_r($aValues,1);
		}
		try {
			if (strpos("pgsql",$this->dsn)===0) {
				$s = $this->pdo->prepare($sql, array(PDO::PGSQL_ATTR_DISABLE_NATIVE_PREPARED_STATEMENT => true));
			}
			else {
				$s = $this->pdo->prepare($sql);
			}
			$this->bindParams( $s, $aValues );
			$s->execute();
			$this->affected_rows=$s->rowCount();
		  	if ($s->columnCount()) {
		    	$this->rs = $s->fetchAll();
	    	}
		  	else {
		    	$this->rs = null;
		  	}
			$rows = $this->rs;
		}catch(PDOException $e) {
			//Unfortunately the code field is supposed to be int by default (php)
			//So we need a property to convey the SQL State code.
			if (version_compare(PHP_VERSION, '5.3.0', '<')) {
				$x = new RedBean_Exception_SQL( $e->getMessage(), 0);
			}
			else {
				$x = new RedBean_Exception_SQL( $e->getMessage(), 0, $e );
			}
			$x->setSQLState( $e->getCode() );
			throw $x;
		}
		if(!$rows) {
			$rows = array();
		}
		if ($this->debug) {
			if (count($rows) > 0) {
				echo "<br><b style='color:green'>resultset: " . count($rows) . " rows</b>";
			}
		}
		return $rows;
	}

	 /**
	 * Runs a query and fetches results as a column.
	 *
	 * @param  string $sql SQL Code to execute
	 *
	 * @return array	$results Resultset
	 */
	public function GetCol($sql, $aValues=array()) {
		$this->connect();
		$this->exc = 0;
		$rows = $this->GetAll($sql,$aValues);
		$cols = array();
		if ($rows && is_array($rows) && count($rows)>0) {
			foreach ($rows as $row) {
				$cols[] = array_shift($row);
			}
		}
		return $cols;
	}

	/**
	 * Runs a query an returns results as a single cell.
	 *
	 * @param string $sql SQL to execute
	 *
	 * @return mixed $cellvalue result cell
	 */
	public function GetCell($sql, $aValues=array()) {
		$this->connect();
		$this->exc = 0;
		$arr = $this->GetAll($sql,$aValues);
		$row1 = array_shift($arr);
		$col1 = array_shift($row1);
		return $col1;
	}

	/**
	 * Runs a query and returns a flat array containing the values of
	 * one row.
	 *
	 * @param string $sql SQL to execute
	 *
	 * @return array $row result row
	 */
	public function GetRow($sql, $aValues=array()) {
		$this->connect();
		$this->exc = 0;
		$arr = $this->GetAll($sql, $aValues);
		return array_shift($arr);
	}

	/**
	 * Returns the error constant of the most
	 * recent error.
	 *
	 * @return mixed $error error code
	 */
	public function ErrorNo() {
		$this->connect();
		if (!$this->exc) return 0;
		$infos = $this->pdo->errorInfo();
		return $infos[1];
	}

	/**
	 * Returns the error message of the most recent
	 * error.
	 *
	 * @return string $message error message
	 */
	public function Errormsg() {
		$this->connect();
		if (!$this->exc) return "";
		$infos = $this->pdo->errorInfo();
		return $infos[2];
	}

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
	public function Execute( $sql, $aValues=array() ) {
		$this->connect();
		$this->exc = 0;
		if ($this->debug) {
			echo "<HR>" . $sql.print_r($aValues,1);
		}
		try {
			if (strpos("pgsql",$this->dsn)===0) {
				$s = $this->pdo->prepare($sql, array(PDO::PGSQL_ATTR_DISABLE_NATIVE_PREPARED_STATEMENT => true));
			}
			else {
				$s = $this->pdo->prepare($sql);
			}
			$this->bindParams( $s, $aValues );
			$s->execute();
			$this->affected_rows=$s->rowCount();
			return $this->affected_rows;
		}
		catch(PDOException $e) {
			//Unfortunately the code field is supposed to be int by default (php)
			//So we need a property to convey the SQL State code.
			if (version_compare(PHP_VERSION, '5.3.0', '<')) {
				$x = new RedBean_Exception_SQL( $e->getMessage(), 0);
			}
			else {
				$x = new RedBean_Exception_SQL( $e->getMessage()." SQL:".$sql, 0, $e );
			}
			$x->setSQLState( $e->getCode() );
			throw $x;
		}
	}

	/**
	 * Escapes a string for use in SQL using the currently selected
	 * PDO driver.
	 *
	 * @param string $string string to be escaped
	 *
	 * @return string $string escaped string
	 */
	public function Escape( $str ) {
		$this->connect();
		return substr(substr($this->pdo->quote($str), 1), 0, -1);
	}

	/**
	 * Returns the latest insert ID if driver does support this
	 * feature.
	 *
	 * @return integer $id primary key ID
	 */
	public function GetInsertID() {
		$this->connect();
		return (int) $this->pdo->lastInsertId();
	}

	/**
	 * Returns the number of rows affected by the most recent query
	 * if the currently selected PDO driver supports this feature.
	 *
	 * @return integer $numOfRows number of rows affected
	 */
	public function Affected_Rows() {
		$this->connect();
		return (int) $this->affected_rows;
	}

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
	public function setDebugMode( $tf ) {
		$this->connect();
		$this->debug = (bool)$tf;
	}

	/**
	 * Returns a raw result resource from the underlying PDO driver.
	 *
	 * @return Resource $PDOResult PDO result resource object
	 */
	public function GetRaw() {
		$this->connect();
		return $this->rs;
	}


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
	public function StartTrans() {
		$this->connect();
		$this->pdo->beginTransaction();
	}

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
	public function CommitTrans() {
		$this->connect();
		$this->pdo->commit();
	}

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
	public function FailTrans() {
		$this->connect();
		$this->pdo->rollback();
	}

	/**
	 * Returns the name of the database type/brand: i.e. mysql, db2 etc.
	 *
	 * @return string $typeName database identification
	 */
	public function getDatabaseType() {
		$this->connect();
		return $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
	}

	/**
	 * Returns the version number of the database.
	 *
	 * @return mixed $version version number of the database
	 */
	public function getDatabaseVersion() {
		$this->connect();
		return $this->pdo->getAttribute(PDO::ATTR_CLIENT_VERSION);
	}

	/**
	 * Returns the underlying PHP PDO instance.
	 *
	 * @return PDO $pdo PDO instance used by PDO wrapper
	 */
	public function getPDO() {
		$this->connect();
		return $this->pdo;
	}

}

