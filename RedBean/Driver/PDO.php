<?php
/**
 * PDO Driver
 * This Driver implements the RedBean Driver API
 *
 * @file    RedBean/PDO.php
 * @desc    PDO Driver
 * @author  Gabor de Mooij and the RedBeanPHP Community, Desfrenes
 * @license BSD/GPLv2
 *
 * (c) copyright Desfrenes & Gabor de Mooij and the RedBeanPHP community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_Driver_PDO implements RedBean_Driver
{
	/**
	 * @var string
	 */
	protected $dsn;

	/**
	 * @var boolean
	 */
	protected $debug = FALSE;

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
	protected $affectedRows;

	/**
	 * @var integer
	 */
	protected $resultArray;

	/**
	 * @var array
	 */
	protected $connectInfo = array();

	/**
	 * @var boolean
	 */
	protected $isConnected = FALSE;

	/**
	 * @var bool
	 */
	protected $flagUseStringOnlyBinding = FALSE;
	
	/**
	 * @var string 
	 */
	protected $mysqlEncoding = '';

	/**
	 * Binds parameters. This method binds parameters to a PDOStatement for
	 * Query Execution. This method binds parameters as NULL, INTEGER or STRING
	 * and supports both named keys and question mark keys.
	 *
	 * @param  PDOStatement $statement  PDO Statement instance
	 * @param  array        $bindings   values that need to get bound to the statement
	 *
	 * @return void
	 */
	protected function bindParams( $statement, $bindings )
	{
		foreach ( $bindings as $key => &$value ) {
			if ( is_integer( $key ) ) {
				if ( is_null( $value ) ) {
					$statement->bindValue( $key + 1, NULL, PDO::PARAM_NULL );
				} elseif ( !$this->flagUseStringOnlyBinding && RedBean_QueryWriter_AQueryWriter::canBeTreatedAsInt( $value ) && $value < 2147483648 ) {
					$statement->bindParam( $key + 1, $value, PDO::PARAM_INT );
				} else {
					$statement->bindParam( $key + 1, $value, PDO::PARAM_STR );
				}
			} else {
				if ( is_null( $value ) ) {
					$statement->bindValue( $key, NULL, PDO::PARAM_NULL );
				} elseif ( !$this->flagUseStringOnlyBinding && RedBean_QueryWriter_AQueryWriter::canBeTreatedAsInt( $value ) && $value < 2147483648 ) {
					$statement->bindParam( $key, $value, PDO::PARAM_INT );
				} else {
					$statement->bindParam( $key, $value, PDO::PARAM_STR );
				}
			}
		}
	}

	/**
	 * This method runs the actual SQL query and binds a list of parameters to the query.
	 * slots. The result of the query will be stored in the protected property
	 * $rs (always array). The number of rows affected (result of rowcount, if supported by database)
	 * is stored in protected property $affectedRows. If the debug flag is set
	 * this function will send debugging output to screen buffer.
	 *
	 * @param string $sql      the SQL string to be send to database server
	 * @param array  $bindings the values that need to get bound to the query slots
	 *
	 * @return void
	 *
	 * @throws RedBean_Exception_SQL
	 */
	protected function runQuery( $sql, $bindings )
	{
		$this->connect();

		if ( $this->debug && $this->logger ) {
			$this->logger->log( $sql, $bindings );
		}

		try {
			if ( strpos( 'pgsql', $this->dsn ) === 0 ) {
				$statement = $this->pdo->prepare( $sql, array( PDO::PGSQL_ATTR_DISABLE_NATIVE_PREPARED_STATEMENT => TRUE ) );
			} else {
				$statement = $this->pdo->prepare( $sql );
			}

			$this->bindParams( $statement, $bindings );

			$statement->execute();

			$this->affectedRows = $statement->rowCount();

			if ( $statement->columnCount() ) {
				$this->resultArray = $statement->fetchAll();

				if ( $this->debug && $this->logger ) {
					$this->logger->log( 'resultset: ' . count( $this->resultArray ) . ' rows' );
				}
			} else {
				$this->resultArray = array();
			}
		} catch ( PDOException $e ) {
			//Unfortunately the code field is supposed to be int by default (php)
			//So we need a property to convey the SQL State code.
			$err = $e->getMessage();

			if ( $this->debug && $this->logger ) $this->logger->log( 'An error occurred: ' . $err );

			$exception = new RedBean_Exception_SQL( $err, 0 );
			$exception->setSQLState( $e->getCode() );

			throw $exception;
		}
	}

	/**
	 * Try to fix MySQL character encoding problems.
	 * MySQL < 5.5 does not support proper 4 byte unicode but they
	 * seem to have added it with version 5.5 under a different label: utf8mb4.
	 * We try to select the best possible charset based on your version data.
	 */
	protected function setEncoding() 
	{
		$driver = $this->pdo->getAttribute( PDO::ATTR_DRIVER_NAME );
		$version = floatval( $this->pdo->getAttribute( PDO::ATTR_SERVER_VERSION ) );

		if ($driver === 'mysql') {
			$encoding = ($version >= 5.5) ? 'utf8mb4' : 'utf8';
			$this->pdo->setAttribute( PDO::MYSQL_ATTR_INIT_COMMAND, 'SET NAMES '.$encoding ); //on every re-connect
			$this->pdo->exec(' SET NAMES '. $encoding); //also for current connection
			$this->mysqlEncoding = $encoding;
		}
	}

	/**
	 * Returns the best possible encoding for MySQL based on version data.
	 * 
	 * @return string
	 */
	public function getMysqlEncoding() 
	{
		return $this->mysqlEncoding;
	}

	/**
	 * Constructor. You may either specify dsn, user and password or
	 * just give an existing PDO connection.
	 * Examples:
	 *    $driver = new RedBean_Driver_PDO($dsn, $user, $password);
	 *    $driver = new RedBean_Driver_PDO($existingConnection);
	 *
	 * @param string|PDO $dsn    database connection string
	 * @param string     $user   optional, usename to sign in
	 * @param string     $pass   optional, password for connection login
	 *
	 */
	public function __construct( $dsn, $user = NULL, $pass = NULL )
	{
		if ( $dsn instanceof PDO ) {
			$this->pdo = $dsn;

			$this->isConnected = TRUE;

			$this->setEncoding();
			$this->pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			$this->pdo->setAttribute( PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC );

			// make sure that the dsn at least contains the type
			$this->dsn = $this->getDatabaseType();
		} else {
			$this->dsn = $dsn;

			$this->connectInfo = array( 'pass' => $pass, 'user' => $user );
		}
	}

	/**
	 * Whether to bind all parameters as strings.
	 *
	 * @param boolean $yesNo pass TRUE to bind all parameters as strings.
	 *
	 * @return void
	 */
	public function setUseStringOnlyBinding( $yesNo )
	{
		$this->flagUseStringOnlyBinding = (boolean) $yesNo;
	}

	/**
	 * Establishes a connection to the database using PHP PDO
	 * functionality. If a connection has already been established this
	 * method will simply return directly. This method also turns on
	 * UTF8 for the database and PDO-ERRMODE-EXCEPTION as well as
	 * PDO-FETCH-ASSOC.
	 *
	 * @throws PDOException
	 *
	 * @return void
	 */
	public function connect()
	{
		if ( $this->isConnected ) return;
		try {
			$user = $this->connectInfo['user'];
			$pass = $this->connectInfo['pass'];

			$this->pdo = new PDO(
				$this->dsn,
				$user,
				$pass,
				array(PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
					   PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
				)
			);
			
			$this->setEncoding();
			$this->pdo->setAttribute( PDO::ATTR_STRINGIFY_FETCHES, TRUE );

			$this->isConnected = TRUE;
		} catch ( PDOException $exception ) {
			$matches = array();

			$dbname  = ( preg_match( '/dbname=(\w+)/', $this->dsn, $matches ) ) ? $matches[1] : '?';

			throw new PDOException( 'Could not connect to database (' . $dbname . ').', $exception->getCode() );
		}
	}

	/**
	 * @see RedBean_Driver::GetAll
	 */
	public function GetAll( $sql, $bindings = array() )
	{
		$this->runQuery( $sql, $bindings );

		return $this->resultArray;
	}

	/**
	 * @see RedBean_Driver::GetCol
	 */
	public function GetCol( $sql, $bindings = array() )
	{
		$rows = $this->GetAll( $sql, $bindings );

		$cols = array();
		if ( $rows && is_array( $rows ) && count( $rows ) > 0 ) {
			foreach ( $rows as $row ) {
				$cols[] = array_shift( $row );
			}
		}

		return $cols;
	}

	/**
	 * @see RedBean_Driver::GetCell
	 */
	public function GetCell( $sql, $bindings = array() )
	{
		$arr = $this->GetAll( $sql, $bindings );

		$row1 = array_shift( $arr );
		$col1 = array_shift( $row1 );

		return $col1;
	}

	/**
	 * @see RedBean_Driver::GetRow
	 */
	public function GetRow( $sql, $bindings = array() )
	{
		$arr = $this->GetAll( $sql, $bindings );

		return array_shift( $arr );
	}

	/**
	 * @see RedBean_Driver::Excecute
	 */
	public function Execute( $sql, $bindings = array() )
	{
		$this->runQuery( $sql, $bindings );

		return $this->affectedRows;
	}

	/**
	 * @see RedBean_Driver::GetInsertID
	 */
	public function GetInsertID()
	{
		$this->connect();

		return (int) $this->pdo->lastInsertId();
	}

	/**
	 * @see RedBean_Driver::Affected_Rows
	 */
	public function Affected_Rows()
	{
		$this->connect();

		return (int) $this->affectedRows;
	}

	/**
	 * Toggles debug mode. In debug mode the driver will print all
	 * SQL to the screen together with some information about the
	 * results.
	 *
	 * @param boolean        $trueFalse turn on/off
	 * @param RedBean_Logger $logger    logger instance
	 *
	 * @return void
	 */
	public function setDebugMode( $tf, $logger = NULL )
	{
		$this->connect();

		$this->debug = (bool) $tf;

		if ( $this->debug and !$logger ) {
			$logger = new RedBean_Logger_Default();
		}

		$this->setLogger( $logger );
	}

	/**
	 * Injects RedBean_Logger object.
	 * Sets the logger instance you wish to use.
	 *
	 * @param RedBean_Logger $logger the logger instance to be used for logging
	 */
	public function setLogger( RedBean_Logger $logger )
	{
		$this->logger = $logger;
	}

	/**
	 * Gets RedBean_Logger object.
	 * Returns the currently active RedBean_Logger instance.
	 *
	 * @return RedBean_Logger
	 */
	public function getLogger()
	{
		return $this->logger;
	}

	/**
	 * @see RedBean_Driver::StartTrans
	 */
	public function StartTrans()
	{
		$this->connect();

		$this->pdo->beginTransaction();
	}

	/**
	 * @see RedBean_Driver::CommitTrans
	 */
	public function CommitTrans()
	{
		$this->connect();

		$this->pdo->commit();
	}

	/**
	 * @see RedBean_Driver::FailTrans
	 */
	public function FailTrans()
	{
		$this->connect();

		$this->pdo->rollback();
	}

	/**
	 * Returns the name of database driver for PDO.
	 * Uses the PDO attribute DRIVER NAME to obtain the name of the
	 * PDO driver.
	 *
	 * @return string
	 */
	public function getDatabaseType()
	{
		$this->connect();

		return $this->pdo->getAttribute( PDO::ATTR_DRIVER_NAME );
	}

	/**
	 * Returns the version number of the database.
	 *
	 * @return mixed $version version number of the database
	 */
	public function getDatabaseVersion()
	{
		$this->connect();

		return $this->pdo->getAttribute( PDO::ATTR_CLIENT_VERSION );
	}

	/**
	 * Returns the underlying PHP PDO instance.
	 *
	 * @return PDO
	 */
	public function getPDO()
	{
		$this->connect();

		return $this->pdo;
	}

	/**
	 * Closes database connection by destructing PDO.
	 *
	 * @return void
	 */
	public function close()
	{
		$this->pdo         = NULL;
		$this->isConnected = FALSE;
	}

	/**
	 * Returns TRUE if the current PDO instance is connected.
	 *
	 * @return boolean
	 */
	public function isConnected()
	{
		return $this->isConnected && $this->pdo;
	}
}
