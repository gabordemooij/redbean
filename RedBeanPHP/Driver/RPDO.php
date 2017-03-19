<?php

namespace RedBeanPHP\Driver;

use RedBeanPHP\Driver as Driver;
use RedBeanPHP\Logger as Logger;
use RedBeanPHP\QueryWriter\AQueryWriter as AQueryWriter;
use RedBeanPHP\RedException as RedException;
use RedBeanPHP\RedException\SQL as SQL;
use RedBeanPHP\Logger\RDefault as RDefault;
use RedBeanPHP\PDOCompatible as PDOCompatible;
use RedBeanPHP\Cursor\PDOCursor as PDOCursor;

/**
 * PDO Driver
 * This Driver implements the RedBean Driver API.
 * for RedBeanPHP. This is the standard / default database driver
 * for RedBeanPHP.
 *
 * @file    RedBeanPHP/PDO.php
 * @author  Gabor de Mooij and the RedBeanPHP Community, Desfrenes
 * @license BSD/GPLv2
 *
 * @copyright
 * copyright (c) Desfrenes & Gabor de Mooij and the RedBeanPHP community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RPDO implements Driver
{
	/**
	 * @var integer
	 */
	protected $max;

	/**
	 * @var string
	 */
	protected $dsn;

	/**
	 * @var boolean
	 */
	protected $loggingEnabled = FALSE;

	/**
	 * @var Logger
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
	 * @var integer
	 */
	protected $queryCounter = 0;

	/**
	 * @var string
	 */
	protected $mysqlEncoding = '';

	/**
	 * @var boolean
	 */
	protected $stringifyFetches = TRUE;

	/**
	 * @var string
	 */
	protected $initSQL = NULL;

	/**
	 * Binds parameters. This method binds parameters to a PDOStatement for
	 * Query Execution. This method binds parameters as NULL, INTEGER or STRING
	 * and supports both named keys and question mark keys.
	 *
	 * @param PDOStatement $statement PDO Statement instance
	 * @param array        $bindings  values that need to get bound to the statement
	 *
	 * @return void
	 */
	protected function bindParams( $statement, $bindings )
	{
		foreach ( $bindings as $key => &$value ) {
			if ( is_integer( $key ) ) {
				if ( is_null( $value ) ) {
					$statement->bindValue( $key + 1, NULL, \PDO::PARAM_NULL );
				} elseif ( !$this->flagUseStringOnlyBinding && AQueryWriter::canBeTreatedAsInt( $value ) && abs( $value ) <= $this->max ) {
					$statement->bindParam( $key + 1, $value, \PDO::PARAM_INT );
				} else {
					$statement->bindParam( $key + 1, $value, \PDO::PARAM_STR );
				}
			} else {
				if ( is_null( $value ) ) {
					$statement->bindValue( $key, NULL, \PDO::PARAM_NULL );
				} elseif ( !$this->flagUseStringOnlyBinding && AQueryWriter::canBeTreatedAsInt( $value ) && abs( $value ) <= $this->max ) {
					$statement->bindParam( $key, $value, \PDO::PARAM_INT );
				} else {
					$statement->bindParam( $key, $value, \PDO::PARAM_STR );
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
	 * @param array  $options
	 *
	 * @return mixed
	 * @throws SQL
	 */
	protected function runQuery( $sql, $bindings, $options = array() )
	{
		$this->connect();
		if ( $this->loggingEnabled && $this->logger ) {
			$this->logger->log( $sql, $bindings );
		}
		try {
			if ( strpos( 'pgsql', $this->dsn ) === 0 ) {
				if ( defined( '\PDO::PGSQL_ATTR_DISABLE_NATIVE_PREPARED_STATEMENT' ) ) {
					$statement = $this->pdo->prepare( $sql, array( \PDO::PGSQL_ATTR_DISABLE_NATIVE_PREPARED_STATEMENT => TRUE ) );
				} else {
					$statement = $this->pdo->prepare( $sql );
				}
			} else {
				$statement = $this->pdo->prepare( $sql );
			}
			$this->bindParams( $statement, $bindings );
			$statement->execute();
			$this->queryCounter ++;
			$this->affectedRows = $statement->rowCount();
			if ( $statement->columnCount() ) {
				$fetchStyle = ( isset( $options['fetchStyle'] ) ) ? $options['fetchStyle'] : NULL;
				if ( isset( $options['noFetch'] ) && $options['noFetch'] ) {
					$this->resultArray = array();
					return $statement;
				}
				$this->resultArray = $statement->fetchAll( $fetchStyle );
				if ( $this->loggingEnabled && $this->logger ) {
					$this->logger->log( 'resultset: ' . count( $this->resultArray ) . ' rows' );
				}
			} else {
				$this->resultArray = array();
			}
		} catch ( \PDOException $e ) {
			//Unfortunately the code field is supposed to be int by default (php)
			//So we need a property to convey the SQL State code.
			$err = $e->getMessage();
			if ( $this->loggingEnabled && $this->logger ) $this->logger->log( 'An error occurred: ' . $err );
			$exception = new SQL( $err, 0 );
			$exception->setSQLState( $e->getCode() );
			throw $exception;
		}
	}

	/**
	 * Try to fix MySQL character encoding problems.
	 * MySQL < 5.5 does not support proper 4 byte unicode but they
	 * seem to have added it with version 5.5 under a different label: utf8mb4.
	 * We try to select the best possible charset based on your version data.
	 *
	 * @return void
	 */
	protected function setEncoding()
	{
		$driver = $this->pdo->getAttribute( \PDO::ATTR_DRIVER_NAME );
		$version = floatval( $this->pdo->getAttribute( \PDO::ATTR_SERVER_VERSION ) );
		if ($driver === 'mysql') {
			$encoding = ($version >= 5.5) ? 'utf8mb4' : 'utf8';
			$this->pdo->setAttribute(\PDO::MYSQL_ATTR_INIT_COMMAND, 'SET NAMES '.$encoding ); //on every re-connect
			$this->pdo->exec(' SET NAMES '. $encoding); //also for current connection
			$this->mysqlEncoding = $encoding;
		}
	}

	/**
	 * Constructor. You may either specify dsn, user and password or
	 * just give an existing PDO connection.
	 *
	 * Examples:
	 *    $driver = new RPDO($dsn, $user, $password);
	 *    $driver = new RPDO($existingConnection);
	 *
	 * @param string|object $dsn  database connection string
	 * @param string        $user optional, usename to sign in
	 * @param string        $pass optional, password for connection login
	 *
	 * @return void
	 */
	public function __construct( $dsn, $user = NULL, $pass = NULL )
	{
		if ( is_object( $dsn ) ) {
			$this->pdo = $dsn;
			$this->isConnected = TRUE;
			$this->setEncoding();
			$this->pdo->setAttribute( \PDO::ATTR_ERRMODE,\PDO::ERRMODE_EXCEPTION );
			$this->pdo->setAttribute( \PDO::ATTR_DEFAULT_FETCH_MODE,\PDO::FETCH_ASSOC );
			// make sure that the dsn at least contains the type
			$this->dsn = $this->getDatabaseType();
		} else {
			$this->dsn = $dsn;
			$this->connectInfo = array( 'pass' => $pass, 'user' => $user );
		}

		//PHP 5.3 PDO SQLite has a bug with large numbers:
		if ( ( strpos( $this->dsn, 'sqlite' ) === 0 && PHP_MAJOR_VERSION === 5 && PHP_MINOR_VERSION === 3 ) ||  defined('HHVM_VERSION') || $this->dsn === 'test-sqlite-53' ) {
			$this->max = 2147483647; //otherwise you get -2147483648 ?! demonstrated in build #603 on Travis.
		} elseif ( strpos( $this->dsn, 'cubrid' ) === 0 ) {
			$this->max = 2147483647; //bindParam in pdo_cubrid also fails...
		} else {
			$this->max = PHP_INT_MAX; //the normal value of course (makes it possible to use large numbers in LIMIT clause)
		}
	}

	/**
	 * Sets PDO in stringify fetch mode.
	 *
	 * @param boolean $bool
	 */
	public function stringifyFetches( $bool ) {
		$this->stringifyFetches = $bool;
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
	 * Whether to bind all parameters as strings.
	 * If set to TRUE this will cause all integers to be bound as STRINGS.
	 * This will NOT affect NULL values.
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
	 * Sets the maximum value to be bound as integer, normally
	 * this value equals PHP's MAX INT constant, however sometimes
	 * PDO driver bindings cannot bind large integers as integers.
	 * This method allows you to manually set the max integer binding
	 * value to manage portability/compatibility issues among different
	 * PHP builds. This method will return the old value.
	 *
	 * @param integer $max maximum value for integer bindings
	 *
	 * @return integer
	 */
	public function setMaxIntBind( $max )
	{
		if ( !is_integer( $max ) ) throw new RedException( 'Parameter has to be integer.' );
		$oldMax = $this->max;
		$this->max = $max;
		return $oldMax;
	}

	/**
	 * Establishes a connection to the database using PHP\PDO
	 * functionality. If a connection has already been established this
	 * method will simply return directly. This method also turns on
	 * UTF8 for the database and PDO-ERRMODE-EXCEPTION as well as
	 * PDO-FETCH-ASSOC.
	 *
	 * @return void
	 */
	public function connect()
	{
		if ( $this->isConnected ) return;
		try {
			$user = $this->connectInfo['user'];
			$pass = $this->connectInfo['pass'];
			$this->pdo = new \PDO(
				$this->dsn,
				$user,
				$pass
			);
			$this->setEncoding();
			$this->pdo->setAttribute( \PDO::ATTR_STRINGIFY_FETCHES, $this->stringifyFetches );
			//cant pass these as argument to constructor, CUBRID driver does not understand...
			$this->pdo->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
			$this->pdo->setAttribute( \PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC );
			$this->isConnected = TRUE;
			/* run initialisation query if any */
			if ( $this->initSQL !== NULL ) {
				$this->Execute( $this->initSQL );
				$this->initSQL = NULL;
			}
		} catch ( \PDOException $exception ) {
			$matches = array();
			$dbname  = ( preg_match( '/dbname=(\w+)/', $this->dsn, $matches ) ) ? $matches[1] : '?';
			throw new \PDOException( 'Could not connect to database (' . $dbname . ').', $exception->getCode() );
		}
	}

	/**
	 * Directly sets PDO instance into driver.
	 * This method might improve performance, however since the driver does
	 * not configure this instance terrible things may happen... only use
	 * this method if you are an expert on RedBeanPHP, PDO and UTF8 connections and
	 * you know your database server VERY WELL.
	 *
	 * @param PDO $pdo PDO instance
	 *
	 * @return void
	 */
	public function setPDO( \PDO $pdo ) {
		$this->pdo = $pdo;
	}

	/**
	 * @see Driver::GetAll
	 */
	public function GetAll( $sql, $bindings = array() )
	{
		$this->runQuery( $sql, $bindings );
		return $this->resultArray;
	}

	/**
	 * @see Driver::GetAssocRow
	 */
	public function GetAssocRow( $sql, $bindings = array() )
	{
		$this->runQuery( $sql, $bindings, array(
				'fetchStyle' => \PDO::FETCH_ASSOC
			)
		);
		return $this->resultArray;
	}

	/**
	 * @see Driver::GetCol
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
	 * @see Driver::GetOne
	 */
	public function GetOne( $sql, $bindings = array() )
	{
		$arr = $this->GetAll( $sql, $bindings );
		$res = NULL;
		if ( !is_array( $arr ) ) return NULL;
		if ( count( $arr ) === 0 ) return NULL;
		$row1 = array_shift( $arr );
		if ( !is_array( $row1 ) ) return NULL;
		if ( count( $row1 ) === 0 ) return NULL;
		$col1 = array_shift( $row1 );
		return $col1;
	}

	/**
	 * Alias for getOne().
	 * Backward compatibility.
	 *
	 * @param string $sql      SQL
	 * @param array  $bindings bindings
	 *
	 * @return mixed
	 */
	public function GetCell( $sql, $bindings = array() )
	{
		return $this->GetOne( $sql, $bindings );
	}

	/**
	 * @see Driver::GetRow
	 */
	public function GetRow( $sql, $bindings = array() )
	{
		$arr = $this->GetAll( $sql, $bindings );
		return array_shift( $arr );
	}

	/**
	 * @see Driver::Excecute
	 */
	public function Execute( $sql, $bindings = array() )
	{
		$this->runQuery( $sql, $bindings );
		return $this->affectedRows;
	}

	/**
	 * @see Driver::GetInsertID
	 */
	public function GetInsertID()
	{
		$this->connect();

		return (int) $this->pdo->lastInsertId();
	}

	/**
	 * @see Driver::GetCursor
	 */
	public function GetCursor( $sql, $bindings = array() )
	{
		$statement = $this->runQuery( $sql, $bindings, array( 'noFetch' => TRUE ) );
		$cursor = new PDOCursor( $statement, \PDO::FETCH_ASSOC );
		return $cursor;
	}

	/**
	 * @see Driver::Affected_Rows
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
	 * @param boolean $trueFalse turn on/off
	 * @param Logger  $logger    logger instance
	 *
	 * @return void
	 */
	public function setDebugMode( $tf, $logger = NULL )
	{
		$this->connect();
		$this->loggingEnabled = (bool) $tf;
		if ( $this->loggingEnabled and !$logger ) {
			$logger = new RDefault();
		}
		$this->setLogger( $logger );
	}

	/**
	 * Injects Logger object.
	 * Sets the logger instance you wish to use.
	 *
	 * @param Logger $logger the logger instance to be used for logging
	 *
	 * @return void
	 */
	public function setLogger( Logger $logger )
	{
		$this->logger = $logger;
	}

	/**
	 * Gets Logger object.
	 * Returns the currently active Logger instance.
	 *
	 * @return Logger
	 */
	public function getLogger()
	{
		return $this->logger;
	}

	/**
	 * @see Driver::StartTrans
	 */
	public function StartTrans()
	{
		$this->connect();
		$this->pdo->beginTransaction();
	}

	/**
	 * @see Driver::CommitTrans
	 */
	public function CommitTrans()
	{
		$this->connect();
		$this->pdo->commit();
	}

	/**
	 * @see Driver::FailTrans
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

		return $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME );
	}

	/**
	 * Returns the version number of the database.
	 *
	 * @return mixed
	 */
	public function getDatabaseVersion()
	{
		$this->connect();
		return $this->pdo->getAttribute(\PDO::ATTR_CLIENT_VERSION );
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

	/**
	 * Toggles logging, enables or disables logging.
	 *
	 * @param boolean $enable TRUE to enable logging
	 *
	 * @return self
	 */
	public function setEnableLogging( $enable )
	{
		$this->loggingEnabled = (boolean) $enable;
	}

	/**
	 * Resets the internal Query Counter.
	 *
	 * @return self
	 */
	public function resetCounter()
	{
		$this->queryCounter = 0;
		return $this;
	}

	/**
	 * Returns the number of SQL queries processed.
	 *
	 * @return integer
	 */
	public function getQueryCount()
	{
		return $this->queryCounter;
	}

	/**
	 * Returns the maximum value treated as integer parameter
	 * binding.
	 *
	 * This method is mainly for testing purposes but it can help
	 * you solve some issues relating to integer bindings.
	 *
	 * @return integer
	 */
	public function getIntegerBindingMax()
	{
		return $this->max;
	}

	/**
	 * Sets a query to be executed upon connecting to the database.
	 * This method provides an opportunity to configure the connection
	 * to a database through an SQL-based interface. Objects can provide
	 * an SQL string to be executed upon establishing a connection to
	 * the database. This has been used to solve issues with default
	 * foreign key settings in SQLite3 for instance, see Github issues:
	 * #545 and #548.
	 *
	 * @param string $sql SQL query to run upon connecting to database
	 *
	 * @return self
	 */
	public function setInitQuery( $sql ) {
		$this->initSQL = $sql;
		return $this;
	}
}
