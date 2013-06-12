<?php
/**
 * PDO Driver
 *
 * @file			RedBean/PDO.php
 * @desc			PDO Driver
 * @author			Gabor de Mooij and the RedBeanPHP Community, Desfrenes
 * @license			BSD/GPLv2
 *
 * This Driver implements the RedBean Driver API
 *
 * (c) copyright Desfrenes & Gabor de Mooij and the RedBeanPHP community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_Driver_PDO implements RedBean_Driver {
	/**
	 * @var string
	 */
	protected $dsn;
	/**
	 * @var boolean
	 */
	protected $debug = false;
	/**
	 * @var RedBean_Logger
	 */
	protected $logger = NULL;
	/**
	 * @var PDO
	 */
	protected $pdo;
	/**
	 * @var integer
	 */
	protected $affected_rows;
	/**
	 * @var integer
	 */
	protected $rs;
	/**
	 * @var array
	 */
	protected $connectInfo = array();
	/**
	 * @var bool
	 */
	public $flagUseStringOnlyBinding = false;
	/**
	 * @var boolean
	 */
	protected $isConnected = false;
	/**
	 * Constructor. You may either specify dsn, user and password or
	 * just give an existing PDO connection.
	 * Examples:
	 *    $driver = new RedBean_Driver_PDO($dsn, $user, $password);
	 *    $driver = new RedBean_Driver_PDO($existingConnection);
	 *
	 * @param string|PDO  $dsn	database connection string
	 * @param string      $user optional, usename to sign in 
	 * @param string      $pass optional, password for connection login
	 *
	 * @return void
	 */
	public function __construct($dsn, $user = null, $pass = null) {
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
			$this->connectInfo = array('pass' => $pass, 'user' => $user);
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
		try {
			$user = $this->connectInfo['user'];
			$pass = $this->connectInfo['pass'];
			$this->pdo = new PDO(
					  $this->dsn,
					  $user,
					  $pass,
					  array(1002 => 'SET NAMES utf8',
								 PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
								 PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
					  )
			);
			$this->pdo->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, true);
			$this->isConnected = true;
		} catch(PDOException $e) {
			$matches = array();
			$dbname = (preg_match('/dbname=(\w+)/', $this->dsn, $matches)) ? $matches[1] : '?';
			throw new PDOException('Could not connect to database ('.$dbname.').', $e->getCode());
		}
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
	protected function bindParams($s, $aValues) {
		foreach($aValues as $key => &$value) {
			if (is_integer($key)) {
				if (is_null($value)){
					$s->bindValue($key+1, null, PDO::PARAM_NULL);
				} elseif (!$this->flagUseStringOnlyBinding && RedBean_QueryWriter_AQueryWriter::canBeTreatedAsInt($value) && $value < 2147483648) {
					$s->bindParam($key+1, $value, PDO::PARAM_INT);
				} else {
					$s->bindParam($key+1, $value, PDO::PARAM_STR);
				}
			} else {
				if (is_null($value)){
					$s->bindValue($key, null, PDO::PARAM_NULL);
				} elseif (!$this->flagUseStringOnlyBinding && RedBean_QueryWriter_AQueryWriter::canBeTreatedAsInt($value) &&  $value < 2147483648) {
					$s->bindParam($key, $value, PDO::PARAM_INT);
				} else {
					$s->bindParam($key, $value, PDO::PARAM_STR);
				}
			}
		}
	}
	/**
	 * This method runs the actual SQL query and binds a list of parameters to the query.
	 * slots. The result of the query will be stored in the protected property
	 * $rs (always array). The number of rows affected (result of rowcount, if supported by database)
	 * is stored in protected property $affected_rows. If the debug flag is set
	 * this function will send debugging output to screen buffer.
	 * 
	 * @throws RedBean_Exception_SQL 
	 * 
	 * @param string $sql     the SQL string to be send to database server
	 * @param array  $aValues the values that need to get bound to the query slots
	 */
	protected function runQuery($sql, $aValues) {
		$this->connect();
		if ($this->debug && $this->logger) {
			$this->logger->log($sql, $aValues);
		}
		try {
			if (strpos('pgsql', $this->dsn) === 0) {
				$s = $this->pdo->prepare($sql, array(PDO::PGSQL_ATTR_DISABLE_NATIVE_PREPARED_STATEMENT => true));
			} else {
				$s = $this->pdo->prepare($sql);
			}
			$this->bindParams($s, $aValues);
			$s->execute();
			$this->affected_rows = $s->rowCount();
			if ($s->columnCount()) {
		    	$this->rs = $s->fetchAll();
		    	if ($this->debug && $this->logger) $this->logger->log('resultset: '.count($this->rs).' rows');
	    	} else {
		    	$this->rs = array();
		  	}
		} catch(PDOException $e) {
			//Unfortunately the code field is supposed to be int by default (php)
			//So we need a property to convey the SQL State code.
			$err = $e->getMessage();
			if ($this->debug && $this->logger) $this->logger->log('An error occurred: '.$err);
			$x = new RedBean_Exception_SQL($err, 0);
			$x->setSQLState($e->getCode());
			throw $x;
		}
	}
	/**
	 * @see RedBean_Driver::GetAll
	 */
	public function GetAll($sql, $aValues = array()) {
		$this->runQuery($sql, $aValues);
		return $this->rs;
	}
	 /**
	 * @see RedBean_Driver::GetCol
	 */
	public function GetCol($sql, $aValues = array()) {
		$rows = $this->GetAll($sql, $aValues);
		$cols = array();
		if ($rows && is_array($rows) && count($rows)>0) {
			foreach ($rows as $row) {
				$cols[] = array_shift($row);
			}
		}
		return $cols;
	}
	/**
	 * @see RedBean_Driver::GetCell
	 */
	public function GetCell($sql, $aValues = array()) {
		$arr = $this->GetAll($sql, $aValues);
		$row1 = array_shift($arr);
		$col1 = array_shift($row1);
		return $col1;
	}
	/**
	 * @see RedBean_Driver::GetRow
	 */
	public function GetRow($sql, $aValues = array()) {
		$arr = $this->GetAll($sql, $aValues);
		return array_shift($arr);
	}
	/**
	 * @see RedBean_Driver::Excecute
	 */
	public function Execute($sql, $aValues = array()) {
		$this->runQuery($sql, $aValues);
		return $this->affected_rows;
	}
	/**
	 * @see RedBean_Driver::GetInsertID
	 */
	public function GetInsertID() {
		$this->connect();
		return (int) $this->pdo->lastInsertId();
	}
	/**
	 * @see RedBean_Driver::Affected_Rows
	 */
	public function Affected_Rows() {
		$this->connect();
		return (int) $this->affected_rows;
	}
	/**
	 * Toggles debug mode. In debug mode the driver will print all
	 * SQL to the screen together with some information about the
	 * results. 
	 *
	 * @param boolean $trueFalse turn on/off
	 * @param RedBean_Logger $logger 
	 *
	 * @return void
	 */
	public function setDebugMode($tf, $logger = NULL) {
		$this->connect();
		$this->debug = (bool) $tf;
		if ($this->debug and !$logger) $logger = new RedBean_Logger_Default();
		$this->setLogger($logger);
	}
	/**
	 * Injects RedBean_Logger object.
	 *
	 * @param RedBean_Logger $logger
	 */
	public function setLogger(RedBean_Logger $logger) {
		$this->logger = $logger;
	}
	/**
	 * Gets RedBean_Logger object.
	 *
	 * @return RedBean_Logger
	 */
	public function getLogger() {
		return $this->logger;
	}
	/**
	 * @see RedBean_Driver::StartTrans
	 */
	public function StartTrans() {
		$this->connect();
		$this->pdo->beginTransaction();
	}
	/**
	 * @see RedBean_Driver::CommitTrans
	 */
	public function CommitTrans() {
		$this->connect();
		$this->pdo->commit();
	}
	/**
	 * @see RedBean_Driver::FailTrans
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
	/**
	 * Closes database connection by destructing PDO.
	 */
	public function close() {
		$this->pdo = null;
		$this->isConnected = false;
	}
	/**
	 * Returns TRUE if the current PDO instance is connected.
	 * 
	 * @return boolean $yesNO 
	 */
	public function isConnected() {
		if (!$this->isConnected && !$this->pdo) return false;
		return true;
	}
}
