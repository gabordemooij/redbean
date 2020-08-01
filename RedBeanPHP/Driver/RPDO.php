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
	protected $mysqlCharset = '';

	/**
	 * @var string
	 */
	protected $mysqlCollate = '';

	/**
	 * @var boolean
	 */
	protected $stringifyFetches = TRUE;

	/**
	 * @var string
	 */
	protected $initSQL = NULL;

	/**
	 * @var callable
	 */
	protected $initCode = NULL;

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
			$k = is_integer( $key ) ? $key + 1 : $key;

			if ( is_array( $value ) && count( $value ) == 2 ) {
				$paramType = end( $value );
				$value = reset( $value );
			} else {
				$paramType = NULL;
			}

			if ( is_null( $value ) ) {
				$statement->bindValue( $k, NULL, \PDO::PARAM_NULL );
				continue;
			}

			if ( $paramType != \PDO::PARAM_INT && $paramType != \PDO::PARAM_STR ) {
				if ( !$this->flagUseStringOnlyBinding && AQueryWriter::canBeTreatedAsInt( $value ) && abs( $value ) <= $this->max ) {
					$paramType = \PDO::PARAM_INT;
				} else {
					$paramType = \PDO::PARAM_STR;
				}
			}

			$statement->bindParam( $k, $value, $paramType );
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
				if (defined('\\PDO::PGSQL_ATTR_DISABLE_NATIVE_PREPARED_STATEMENT')) {
                 			$statement = @$this->pdo->prepare($sql, array(\PDO::PGSQL_ATTR_DISABLE_NATIVE_PREPARED_STATEMENT => TRUE));
             			} else {
                 			$statement = $this->pdo->prepare($sql);
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
			$exception = new SQL( $err, 0, $e );
			$exception->setSQLState( $e->getCode() );
			$exception->setDriverDetails( $e->errorInfo );
			throw $exception;
		}
	}

	/**
	 * Try to fix MySQL character encoding problems.
	 * MySQL < 5.5.3 does not support proper 4 byte unicode but they
	 * seem to have added it with version 5.5.3 under a different label: utf8mb4.
	 * We try to select the best possible charset based on your version data.
	 *
	 * @return void
	 */
	protected function setEncoding()
	{
		$driver = $this->pdo->getAttribute( \PDO::ATTR_DRIVER_NAME );
		if ($driver === 'mysql') {
			$charset = $this->hasCap( 'utf8mb4' ) ? 'utf8mb4' : 'utf8';
			$collate = $this->hasCap( 'utf8mb4_520' ) ? '_unicode_520_ci' : '_unicode_ci';
			$this->pdo->setAttribute(\PDO::MYSQL_ATTR_INIT_COMMAND, 'SET NAMES '. $charset ); //on every re-connect
			/* #624 removed space before SET NAMES because it causes trouble with ProxySQL */
			$this->pdo->exec('SET NAMES '. $charset); //also for current connection
			$this->mysqlCharset = $charset;
			$this->mysqlCollate = $charset . $collate;
		}
	}

	/**
	 * Determine if a database supports a particular feature.
	 * Currently this function can be used to detect the following features:
	 *
	 * - utf8mb4
	 * - utf8mb4 520
	 *
	 * Usage:
	 *
	 * <code>
	 * $this->hasCap( 'utf8mb4_520' );
	 * </code>
	 *
	 * By default, RedBeanPHP uses this method under the hood to make sure
	 * you use the latest UTF8 encoding possible for your database.
	 *
	 * @param $db_cap identifier of database capability
	 *
	 * @return int|false Whether the database feature is supported, FALSE otherwise.
	 **/
	protected function hasCap( $db_cap )
	{
		$compare = FALSE;
		$version = $this->pdo->getAttribute( \PDO::ATTR_SERVER_VERSION );
		switch ( strtolower( $db_cap ) ) {
			case 'utf8mb4':
				//oneliner, to boost code coverage (coverage does not span versions)
				if ( version_compare( $version, '5.5.3', '<' ) ) { return FALSE; }
				$client_version = $this->pdo->getAttribute(\PDO::ATTR_CLIENT_VERSION );
				/*
				 * libmysql has supported utf8mb4 since 5.5.3, same as the MySQL server.
				 * mysqlnd has supported utf8mb4 since 5.0.9.
				 */
				if ( strpos( $client_version, 'mysqlnd' ) !== FALSE ) {
					$client_version = preg_replace( '/^\D+([\d.]+).*/', '$1', $client_version );
					$compare = version_compare( $client_version, '5.0.9', '>=' );
				} else {
					$compare = version_compare( $client_version, '5.5.3', '>=' );
				}
			break;
			case 'utf8mb4_520':
				$compare = version_compare( $version, '5.6', '>=' );
			break;
		}

		return $compare;
	}

	/**
	 * Constructor. You may either specify dsn, user and password or
	 * just give an existing PDO connection.
	 *
	 * Usage:
	 *
	 * <code>
	 * $driver = new RPDO( $dsn, $user, $password );
	 * </code>
	 *
	 * The example above illustrates how to create a driver
	 * instance from a database connection string (dsn), a username
	 * and a password. It's also possible to pass a PDO object.
	 *
	 * Usage:
	 *
	 * <code>
	 * $driver = new RPDO( $existingConnection );
	 * </code>
	 *
	 * The second example shows how to create an RPDO instance
	 * from an existing PDO object.
	 *
	 * @param string|object $dsn  database connection string
	 * @param string        $user optional, usename to sign in
	 * @param string        $pass optional, password for connection login
	 *
	 * @return void
	 */
	public function __construct( $dsn, $user = NULL, $pass = NULL, $options = array() )
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
			if (is_array($options)) $this->connectInfo['options'] = $options;
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
	 * If set to TRUE, this method will make sure all data retrieved from
	 * the database will be fetched as a string. Default: TRUE.
	 *
	 * To set it to FALSE...
	 *
	 * Usage:
	 *
	 * <code>
	 * R::getDatabaseAdapter()->getDatabase()->stringifyFetches( FALSE );
	 * </code>
	 *
	 * Important!
	 * Note, this method only works if you set the value BEFORE the connection
	 * has been establish. Also, this setting ONLY works with SOME drivers.
	 * It's up to the driver to honour this setting.
	 *
	 * @param boolean $bool
	 */
	public function stringifyFetches( $bool ) {
		$this->stringifyFetches = $bool;
	}

	/**
	 * Returns the best possible encoding for MySQL based on version data.
	 * This method can be used to obtain the best character set parameters
	 * possible for your database when constructing a table creation query
	 * containing clauses like:  CHARSET=... COLLATE=...
	 * This is a MySQL-specific method and not part of the driver interface.
	 *
	 * Usage:
	 *
	 * <code>
	 * $charset_collate = $this->adapter->getDatabase()->getMysqlEncoding( TRUE );
	 * </code>
	 *
	 * @param boolean $retCol pass TRUE to return both charset/collate
	 *
	 * @return string|array
	 */
	public function getMysqlEncoding( $retCol = FALSE )
	{
		if( $retCol )
			return array( 'charset' => $this->mysqlCharset, 'collate' => $this->mysqlCollate );
		return $this->mysqlCharset;
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
		if ( $this->loggingEnabled && $this->logger && method_exists($this->logger,'setUseStringOnlyBinding')) {
			$this->logger->setUseStringOnlyBinding( $this->flagUseStringOnlyBinding );
		}
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
	 * Sets initialization code to execute upon connecting.
	 *
	 * @param callable $code
	 *
	 * @return void
	 */
	public function setInitCode($code)
	{
		$this->initCode= $code;
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
			$options = array();
			if (isset($this->connectInfo['options']) && is_array($this->connectInfo['options'])) {
				$options = $this->connectInfo['options'];
			}
			$this->pdo = new \PDO( $this->dsn, $user, $pass, $options );
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
			if ( $this->initCode !== NULL ) {
				$code = $this->initCode;
				$code( $this->pdo->getAttribute( \PDO::ATTR_SERVER_VERSION ) );
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
	 * - connected     TRUE|FALSE (treat this instance as connected, default: TRUE)
	 * - setEncoding   TRUE|FALSE (let RedBeanPHP set encoding for you, default: TRUE)
	 * - setAttributes TRUE|FALSE (let RedBeanPHP set attributes for you, default: TRUE)*
	 * - setDSNString  TRUE|FALSE (extract DSN string from PDO instance, default: TRUE)
	 * - stringFetch   TRUE|FALSE (whether you want to stringify fetches or not, default: TRUE)
	 * - runInitCode   TRUE|FALSE (run init code if any, default: TRUE)
	 *
	 * *attributes:
	 * - RedBeanPHP will ask database driver to throw Exceptions on errors (recommended for compatibility)
         * - RedBeanPHP will ask database driver to use associative arrays when fetching (recommended for compatibility)
	 *
	 * @param PDO     $pdo       PDO instance
	 * @param array   $options   Options to apply
	 *
	 * @return void
	 */
	public function setPDO( \PDO $pdo, $options = array() ) {
		$this->pdo = $pdo;

		$connected     = TRUE;
		$setEncoding   = TRUE;
		$setAttributes = TRUE;
		$setDSNString  = TRUE;
		$runInitCode   = TRUE;
		$stringFetch   = TRUE;

		if ( isset($options['connected']) )     $connected     = $options['connected'];
		if ( isset($options['setEncoding']) )   $setEncoding   = $options['setEncoding'];
		if ( isset($options['setAttributes']) ) $setAttributes = $options['setAttributes'];
		if ( isset($options['setDSNString']) )  $setDSNString  = $options['setDSNString'];
		if ( isset($options['runInitCode']) )   $runInitCode   = $options['runInitCode'];
		if ( isset($options['stringFetch']) )   $stringFetch   = $options['stringFetch'];

		if ($connected) $this->connected = $connected;
		if ($setEncoding) $this->setEncoding();
		if ($setAttributes) {
			$this->pdo->setAttribute( \PDO::ATTR_ERRMODE,\PDO::ERRMODE_EXCEPTION );
			$this->pdo->setAttribute( \PDO::ATTR_DEFAULT_FETCH_MODE,\PDO::FETCH_ASSOC );
			$this->pdo->setAttribute( \PDO::ATTR_STRINGIFY_FETCHES, $stringFetch );
		}
		if ($runInitCode) {
			/* run initialisation query if any */
			if ( $this->initSQL !== NULL ) {
				$this->Execute( $this->initSQL );
				$this->initSQL = NULL;
			}
			if ( $this->initCode !== NULL ) {
				$code = $this->initCode;
				$code( $this->pdo->getAttribute( \PDO::ATTR_SERVER_VERSION ) );
			}
		}
		if ($setDSNString) $this->dsn = $this->getDatabaseType();
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

		if ( empty( $rows ) || !is_array( $rows ) ) {
			return array();
		}

		$cols = array();
		foreach ( $rows as $row ) {
			$cols[] = reset( $row );
		}

		return $cols;
	}

	/**
	 * @see Driver::GetOne
	 */
	public function GetOne( $sql, $bindings = array() )
	{
		$arr = $this->GetAll( $sql, $bindings );

		if ( empty( $arr[0] ) || !is_array( $arr[0] ) ) {
			return NULL;
		}

		return reset( $arr[0] );
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

		if ( is_array( $arr ) && count( $arr ) ) {
			return reset( $arr );
		}

		return array();
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
	 * @see Driver::setDebugMode
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
	 * This method is for more fine-grained control. Normally
	 * you should use the facade to start the query debugger for
	 * you. The facade will manage the object wirings necessary
	 * to use the debugging functionality.
	 *
	 * Usage (through facade):
	 *
	 * <code>
	 * R::debug( TRUE );
	 * ...rest of program...
	 * R::debug( FALSE );
	 * </code>
	 *
	 * The example above illustrates how to use the RedBeanPHP
	 * query debugger through the facade.
	 *
	 * @param Logger $logger the logger instance to be used for logging
	 *
	 * @return self
	 */
	public function setLogger( Logger $logger )
	{
		$this->logger = $logger;
		return $this;
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
	 * PDO driver. Use this method to identify the current PDO driver
	 * used to provide access to the database. Example of a database
	 * driver string:
	 *
	 * <code>
	 * mysql
	 * </code>
	 *
	 * Usage:
	 *
	 * <code>
	 * echo R::getDatabaseAdapter()->getDatabase()->getDatabaseType();
	 * </code>
	 *
	 * The example above prints the current database driver string to
	 * stdout.
	 *
	 * Note that this is a driver-specific method, not part of the
	 * driver interface. This method might not be available in other
	 * drivers since it relies on PDO.
	 *
	 * @return string
	 */
	public function getDatabaseType()
	{
		$this->connect();
		return $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME );
	}

	/**
	 * Returns the version identifier string of the database client.
	 * This method can be used to identify the currently installed
	 * database client. Note that this method will also establish a connection
	 * (because this is required to obtain the version information).
	 *
	 * Example of a version string:
	 *
	 * <code>
	 * mysqlnd 5.0.12-dev - 20150407 - $Id: b5c5906d452ec590732a93b051f3827e02749b83 $
	 * </code>
	 *
	 * Usage:
	 *
	 * <code>
	 * echo R::getDatabaseAdapter()->getDatabase()->getDatabaseVersion();
	 * </code>
	 *
	 * The example above will print the version string to stdout.
	 *
	 * Note that this is a driver-specific method, not part of the
	 * driver interface. This method might not be available in other
	 * drivers since it relies on PDO.
	 *
	 * To obtain the database server version, use getDatabaseServerVersion()
	 * instead.
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
	 * For some low-level database operations you'll need access to the PDO
	 * object. Not that this method is only available in RPDO and other
	 * PDO based database drivers for RedBeanPHP. Other drivers may not have
	 * a method like this. The following example demonstrates how to obtain
	 * a reference to the PDO instance from the facade:
	 *
	 * Usage:
	 *
	 * <code>
	 * $pdo = R::getDatabaseAdapter()->getDatabase()->getPDO();
	 * </code>
	 *
	 * @return PDO
	 */
	public function getPDO()
	{
		$this->connect();
		return $this->pdo;
	}

	/**
	 * Closes the database connection.
	 * While database connections are closed automatically at the end of the PHP script,
	 * closing database connections is generally recommended to improve performance.
	 * Closing a database connection will immediately return the resources to PHP.
	 *
	 * Usage:
	 *
	 * <code>
	 * R::setup( ... );
	 * ... do stuff ...
	 * R::close();
	 * </code>
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
		return $this;
	}

	/**
	 * Resets the query counter.
	 * The query counter can be used to monitor the number
	 * of database queries that have
	 * been processed according to the database driver. You can use this
	 * to monitor the number of queries required to render a page.
	 *
	 * Usage:
	 *
	 * <code>
	 * R::resetQueryCount();
	 * echo R::getQueryCount() . ' queries processed.';
	 * </code>
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
	 * This method returns the number of database queries that have
	 * been processed according to the database driver. You can use this
	 * to monitor the number of queries required to render a page.
	 *
	 * Usage:
	 *
	 * <code>
	 * echo R::getQueryCount() . ' queries processed.';
	 * </code>
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

	/**
	 * Returns the version string from the database server.
	 *
	 * @return string
	 */
	public function DatabaseServerVersion() {
		return trim( strval( $this->pdo->getAttribute(\PDO::ATTR_SERVER_VERSION) ) );
	}
}
