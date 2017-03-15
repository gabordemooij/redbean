<?php

namespace RedBeanPHP {

/**
 * RedBean Logging interface.
 * Provides a uniform and convenient logging
 * interface throughout RedBeanPHP.
 *
 * @file    RedBean/Logging.php
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
interface Logger
{
	/**
	 * A logger (for PDO or OCI driver) needs to implement the log method.
	 * The log method will receive logging data. Note that the number of parameters is 0, this means
	 * all parameters are optional and the number may vary. This way the logger can be used in a very
	 * flexible way. Sometimes the logger is used to log a simple error message and in other
	 * situations sql and bindings are passed.
	 * The log method should be able to accept all kinds of parameters and data by using
	 * functions like func_num_args/func_get_args.
	 *
	 * @param string $message, ...
	 *
	 * @return void
	 */
	public function log();
}
}

namespace RedBeanPHP\Logger {

use RedBeanPHP\Logger as Logger;
use RedBeanPHP\RedException as RedException;

/**
 * Logger. Provides a basic logging function for RedBeanPHP.
 *
 * @file    RedBeanPHP/Logger.php
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RDefault implements Logger
{
	/**
	 * Logger modes
	 */
	const C_LOGGER_ECHO  = 0;
	const C_LOGGER_ARRAY = 1;

	/**
	 * @var integer
	 */
	protected $mode = 0;

	/**
	 * @var array
	 */
	protected $logs = array();

	/**
	 * Default logger method logging to STDOUT.
	 * This is the default/reference implementation of a logger.
	 * This method will write the message value to STDOUT (screen) unless
	 * you have changed the mode of operation to C_LOGGER_ARRAY.
	 *
	 * @param $message (optional) message to log (might also be data or output)
	 *
	 * @return void
	 */
	public function log()
	{
		if ( func_num_args() < 1 ) return;

		foreach ( func_get_args() as $argument ) {
			if ( is_array( $argument ) ) {
				$log = var_export( $argument, TRUE );
				if ( $this->mode === self::C_LOGGER_ECHO ) {
					echo $log;
				} else {
					$this->logs[] = $log;
				}
			} else {
				if ( $this->mode === self::C_LOGGER_ECHO ) {
					echo $argument;
				} else {
					$this->logs[] = $argument;
				}
			}

			if ( $this->mode === self::C_LOGGER_ECHO ) echo "<br>" . PHP_EOL;
		}
	}

	/**
	 * Returns the internal log array.
	 * The internal log array is where all log messages are stored.
	 *
	 * @return array
	 */
	public function getLogs()
	{
		return $this->logs;
	}

	/**
	 * Clears the internal log array, removing all
	 * previously stored entries.
	 *
	 * @return self
	 */
	public function clear()
	{
		$this->logs = array();
		return $this;
	}

	/**
	 * Selects a logging mode.
	 * There are several options available.
	 *
	 * * C_LOGGER_ARRAY - log silently, stores entries in internal log array only
	 * * C_LOGGER_ECHO  - also forward log messages directly to STDOUT
	 *
	 * @param integer $mode mode of operation for logging object
	 *
	 * @return self
	 */
	public function setMode( $mode )
	{
		if ($mode !== self::C_LOGGER_ARRAY && $mode !== self::C_LOGGER_ECHO ) {
			throw new RedException( 'Invalid mode selected for logger, use C_LOGGER_ARRAY or C_LOGGER_ECHO.' );
		}
		$this->mode = $mode;
		return $this;
	}

	/**
	 * Searches for all log entries in internal log array
	 * for $needle and returns those entries.
	 * This method will return an array containing all matches for your
	 * search query.
	 *
	 * @param string $needle phrase to look for in internal log array
	 *
	 * @return array
	 */
	public function grep( $needle )
	{
		$found = array();
		foreach( $this->logs as $logEntry ) {
			if ( strpos( $logEntry, $needle ) !== FALSE ) $found[] = $logEntry;
		}
		return $found;
	}
}
}

namespace RedBeanPHP\Logger\RDefault {

use RedBeanPHP\Logger as Logger;
use RedBeanPHP\Logger\RDefault as RDefault;
use RedBeanPHP\RedException as RedException;

/**
 * Debug logger.
 * A special logger for debugging purposes.
 * Provides debugging logging functions for RedBeanPHP.
 *
 * @file    RedBeanPHP/Logger/RDefault/Debug.php
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Debug extends RDefault implements Logger
{
	/**
	 * @var integer
	 */
	private $strLen = 40;

	/**
	 * Writes a query for logging with all bindings / params filled
	 * in.
	 *
	 * @param string $newSql   the query
	 * @param array  $bindings the bindings to process (key-value pairs)
	 *
	 * @return string
	 */
	private function writeQuery( $newSql, $newBindings )
	{
		//avoid str_replace collisions: slot1 and slot10 (issue 407).
		uksort( $newBindings, function( $a, $b ) {
			return ( strlen( $b ) - strlen( $a ) );
		} );

		$newStr = $newSql;
		foreach( $newBindings as $slot => $value ) {
			if ( strpos( $slot, ':' ) === 0 ) {
				$newStr = str_replace( $slot, $this->fillInValue( $value ), $newStr );
			}
		}
		return $newStr;
	}

	/**
	 * Fills in a value of a binding and truncates the
	 * resulting string if necessary.
	 *
	 * @param mixed $value bound value
	 *
	 * @return string
	 */
	protected function fillInValue( $value )
	{
		if ( is_null( $value ) ) $value = 'NULL';

		$value = strval( $value );
		if ( strlen( $value ) > ( $this->strLen ) ) {
			$value = substr( $value, 0, ( $this->strLen ) ).'... ';
		}

		if ( !\RedBeanPHP\QueryWriter\AQueryWriter::canBeTreatedAsInt( $value ) && $value !== 'NULL') {
			$value = '\''.$value.'\'';
		}

		return $value;
	}

	/**
	 * Dependending on the current mode of operation,
	 * this method will either log and output to STDIN or
	 * just log.
	 *
	 * Depending on the value of constant PHP_SAPI this function
	 * will format output for console or HTML.
	 *
	 * @param string $str string to log or output and log
	 *
	 * @return void
	 */
	protected function output( $str )
	{
		$this->logs[] = $str;
		if ( !$this->mode ) {
			$highlight = FALSE;
			/* just a quick heuritsic to highlight schema changes */
			if ( strpos( $str, 'CREATE' ) === 0
			|| strpos( $str, 'ALTER' ) === 0
			|| strpos( $str, 'DROP' ) === 0) {
				$highlight = TRUE;
			}
			if (PHP_SAPI === 'cli') {
				if ($highlight) echo "\e[91m";
				echo $str, PHP_EOL;
				echo "\e[39m";
			} else {
				if ($highlight) {
					echo "<b style=\"color:red\">{$str}</b>";
				} else {
					echo $str;
				}
				echo '<br />';
			}
		}
	}

	/**
	 * Normalizes the slots in an SQL string.
	 * Replaces question mark slots with :slot1 :slot2 etc.
	 *
	 * @param string $sql sql to normalize
	 *
	 * @return string
	 */
	protected function normalizeSlots( $sql )
	{
		$newSql = $sql;
		$i = 0;
		while(strpos($newSql, '?') !== FALSE ){
			$pos   = strpos( $newSql, '?' );
			$slot  = ':slot'.$i;
			$begin = substr( $newSql, 0, $pos );
			$end   = substr( $newSql, $pos+1 );
			if (PHP_SAPI === 'cli') {
				$newSql = "{$begin}\e[32m{$slot}\e[39m{$end}";
			} else {
				$newSql = "{$begin}<b style=\"color:green\">$slot</b>{$end}";
			}
			$i ++;
		}
		return $newSql;
	}

	/**
	 * Normalizes the bindings.
	 * Replaces numeric binding keys with :slot1 :slot2 etc.
	 *
	 * @param array $bindings bindings to normalize
	 *
	 * @return array
	 */
	protected function normalizeBindings( $bindings )
	{
		$i = 0;
		$newBindings = array();
		foreach( $bindings as $key => $value ) {
			if ( is_numeric($key) ) {
				$newKey = ':slot'.$i;
				$newBindings[$newKey] = $value;
				$i++;
			} else {
				$newBindings[$key] = $value;
			}
		}
		return $newBindings;
	}

	/**
	 * Logger method.
	 *
	 * Takes a number of arguments tries to create
	 * a proper debug log based on the available data.
	 *
	 * @return void
	 */
	public function log()
	{
		if ( func_num_args() < 1 ) return;

		$sql = func_get_arg( 0 );

		if ( func_num_args() < 2) {
			$bindings = array();
		} else {
			$bindings = func_get_arg( 1 );
		}

		if ( !is_array( $bindings ) ) {
			return $this->output( $sql );
		}

		$newSql = $this->normalizeSlots( $sql );
		$newBindings = $this->normalizeBindings( $bindings );
		$newStr = $this->writeQuery( $newSql, $newBindings );
		$this->output( $newStr );
	}

	/**
	 * Sets the max string length for the parameter output in
	 * SQL queries. Set this value to a reasonable number to
	 * keep you SQL queries readable.
	 *
	 * @param integer $len string length
	 *
	 * @return self
	 */
	public function setParamStringLength( $len = 20 )
	{
		$this->strLen = max(0, $len);
		return $this;
	}
}
}

namespace RedBeanPHP {

/**
 * Interface for database drivers.
 * The Driver API conforms to the ADODB pseudo standard
 * for database drivers.
 *
 * @file       RedBeanPHP/Driver.php
 * @author     Gabor de Mooij and the RedBeanPHP Community
 * @license    BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
interface Driver
{
	/**
	 * Runs a query and fetches results as a multi dimensional array.
	 *
	 * @param string $sql      SQL query to execute
	 * @param array  $bindings list of values to bind to SQL snippet
	 *
	 * @return array
	 */
	public function GetAll( $sql, $bindings = array() );

	/**
	 * Runs a query and fetches results as a column.
	 *
	 * @param string $sql      SQL query to execute
	 * @param array  $bindings list of values to bind to SQL snippet
	 *
	 * @return array
	 */
	public function GetCol( $sql, $bindings = array() );

	/**
	 * Runs a query and returns results as a single cell.
	 *
	 * @param string $sql      SQL query to execute
	 * @param array  $bindings list of values to bind to SQL snippet
	 *
	 * @return mixed
	 */
	public function GetOne( $sql, $bindings = array() );

	/**
	 * Runs a query and returns results as an associative array
	 * indexed by the first column.
	 *
	 * @param string $sql      SQL query to execute
	 * @param array  $bindings list of values to bind to SQL snippet
	 *
	 * @return mixed
	 */
	public function GetAssocRow( $sql, $bindings = array() );

	/**
	 * Runs a query and returns a flat array containing the values of
	 * one row.
	 *
	 * @param string $sql      SQL query to execute
	 * @param array  $bindings list of values to bind to SQL snippet
	 *
	 * @return array
	 */
	public function GetRow( $sql, $bindings = array() );

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
	 * @param string $sql      SQL query to execute
	 * @param array  $bindings list of values to bind to SQL snippet
	 *
	 * @return array Affected Rows
	 */
	public function Execute( $sql, $bindings = array() );

	/**
	 * Returns the latest insert ID if driver does support this
	 * feature.
	 *
	 * @return integer
	 */
	public function GetInsertID();

	/**
	 * Returns the number of rows affected by the most recent query
	 * if the currently selected driver driver supports this feature.
	 *
	 * @return integer
	 */
	public function Affected_Rows();

	/**
	 * Returns a cursor-like object from the database.
	 *
	 * @param string $sql      SQL query to execute
	 * @param array  $bindings list of values to bind to SQL snippet
	 *
	 * @return mixed
	 */
	public function GetCursor( $sql, $bindings = array() );

	/**
	 * Toggles debug mode. In debug mode the driver will print all
	 * SQL to the screen together with some information about the
	 * results. All SQL code that passes through the driver will be
	 * passes on to the screen for inspection.
	 * This method has no return value.
	 *
	 * @param boolean $tf TRUE = debug mode ON
	 * @param Logger $customLogger
	 *
	 * @return void
	 */
	public function setDebugMode( $tf, $customLogger );

	/**
	 * Starts a transaction.
	 *
	 * @return void
	 */
	public function CommitTrans();

	/**
	 * Commits a transaction.
	 *
	 * @return void
	 */
	public function StartTrans();

	/**
	 * Rolls back a transaction.
	 *
	 * @return void
	 */
	public function FailTrans();

	/**
	 * Resets the internal Query Counter.
	 *
	 * @return self
	 */
	public function resetCounter();

	/**
	 * Returns the number of SQL queries processed.
	 *
	 * @return integer
	 */
	public function getQueryCount();
}
}

namespace RedBeanPHP\Driver {

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
			$this->pdo->setAttribute( \PDO::ATTR_STRINGIFY_FETCHES, TRUE );
			//cant pass these as argument to constructor, CUBRID driver does not understand...
			$this->pdo->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
			$this->pdo->setAttribute( \PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC );
			$this->isConnected = TRUE;
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
}
}

namespace RedBeanPHP {

use RedBeanPHP\QueryWriter\AQueryWriter as AQueryWriter;
use RedBeanPHP\BeanHelper as BeanHelper;
use RedBeanPHP\RedException as RedException;

/* PHP 5.3 compatibility */
if (interface_exists('\JsonSerializable')) {
		/* We extend JsonSerializable to avoid namespace conflicts,
		can't define interface with special namespace in PHP */
		interface Jsonable extends \JsonSerializable {};
} else {
	interface Jsonable {};
}

/**
 * OODBBean (Object Oriented DataBase Bean).
 *
 * to exchange information with the database. A bean represents
 * a single table row and offers generic services for interaction
 * with databases systems as well as some meta-data.
 *
 * @file    RedBeanPHP/OODBBean.php
 * @author  Gabor de Mooij and the RedBeanPHP community
 * @license BSD/GPLv2
 * @desc    OODBBean represents a bean. RedBeanPHP uses beans
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class OODBBean implements\IteratorAggregate,\ArrayAccess,\Countable,Jsonable
{
	/**
	 * FUSE error modes.
	 */
	const C_ERR_IGNORE    = FALSE;
	const C_ERR_LOG       = 1;
	const C_ERR_NOTICE    = 2;
	const C_ERR_WARN      = 3;
	const C_ERR_EXCEPTION = 4;
	const C_ERR_FUNC      = 5;
	const C_ERR_FATAL     = 6;

	/**
	 * @var boolean
	 */
	protected static $errorHandlingFUSE = FALSE;

	/**
	 * @var callable|NULL
	 */
	protected static $errorHandler = NULL;

	/**
	 * @var array
	 */
	protected static $aliases = array();

	/**
	 * @var boolean
	 */
	protected static $autoResolve = FALSE;

	/**
	 * This is where the real properties of the bean live. They are stored and retrieved
	 * by the magic getter and setter (__get and __set).
	 *
	 * @var array $properties
	 */
	protected $properties = array();

	/**
	 * Here we keep the meta data of a bean.
	 *
	 * @var array
	 */
	protected $__info = array();

	/**
	 * The BeanHelper allows the bean to access the toolbox objects to implement
	 * rich functionality, otherwise you would have to do everything with R or
	 * external objects.
	 *
	 * @var BeanHelper
	 */
	protected $beanHelper = NULL;

	/**
	 * @var null
	 */
	protected $fetchType = NULL;

	/**
	 * @var string
	 */
	protected $withSql = '';

	/**
	 * @var array
	 */
	protected $withParams = array();

	/**
	 * @var string
	 */
	protected $aliasName = NULL;

	/**
	 * @var string
	 */
	protected $via = NULL;

	/**
	 * @var boolean
	 */
	protected $noLoad = FALSE;

	/**
	 * @var boolean
	 */
	protected $all = FALSE;

	/**
	 * Sets the error mode for FUSE.
	 * What to do if a FUSE model method does not exist?
	 * You can set the following options:
	 *
	 * * OODBBean::C_ERR_IGNORE (default), ignores the call, returns NULL
	 * * OODBBean::C_ERR_LOG, logs the incident using error_log
	 * * OODBBean::C_ERR_NOTICE, triggers a E_USER_NOTICE
	 * * OODBBean::C_ERR_WARN, triggers a E_USER_WARNING
	 * * OODBBean::C_ERR_EXCEPTION, throws an exception
	 * * OODBBean::C_ERR_FUNC, allows you to specify a custom handler (function)
	 * * OODBBean::C_ERR_FATAL, triggers a E_USER_ERROR
	 *
	 * <code>
	 * Custom handler method signature: handler( array (
	 * 	'message' => string
	 * 	'bean' => OODBBean
	 * 	'method' => string
	 * ) )
	 * </code>
	 *
	 * This method returns the old mode and handler as an array.
	 *
	 * @param integer       $mode error handling mode
	 * @param callable|NULL $func custom handler
	 *
	 * @return array
	 */
	public static function setErrorHandlingFUSE($mode, $func = NULL) {
		if (
			   $mode !== self::C_ERR_IGNORE
			&& $mode !== self::C_ERR_LOG
			&& $mode !== self::C_ERR_NOTICE
			&& $mode !== self::C_ERR_WARN
			&& $mode !== self::C_ERR_EXCEPTION
			&& $mode !== self::C_ERR_FUNC
			&& $mode !== self::C_ERR_FATAL
		) throw new \Exception( 'Invalid error mode selected' );

		if ( $mode === self::C_ERR_FUNC && !is_callable( $func ) ) {
			throw new \Exception( 'Invalid error handler' );
		}

		$old = array( self::$errorHandlingFUSE, self::$errorHandler );
		self::$errorHandlingFUSE = $mode;
		if ( is_callable( $func ) ) {
			self::$errorHandler = $func;
		} else {
			self::$errorHandler = NULL;
		}
		return $old;
	}

	/**
	 * Sets global aliases.
	 * Registers a batch of aliases in one go. This works the same as
	 * fetchAs and setAutoResolve but explicitly. For instance if you register
	 * the alias 'cover' for 'page' a property containing a reference to a
	 * page bean called 'cover' will correctly return the page bean and not
	 * a (non-existant) cover bean.
	 *
	 * <code>
	 * R::aliases( array( 'cover' => 'page' ) );
	 * $book = R::dispense( 'book' );
	 * $page = R::dispense( 'page' );
	 * $book->cover = $page;
	 * R::store( $book );
	 * $book = $book->fresh();
	 * $cover = $book->cover;
	 * echo $cover->getMeta( 'type' ); //page
	 * </code>
	 *
	 * The format of the aliases registration array is:
	 *
	 * {alias} => {actual type}
	 *
	 * In the example above we use:
	 *
	 * cover => page
	 *
	 * From that point on, every bean reference to a cover
	 * will return a 'page' bean. Note that with autoResolve this
	 * feature along with fetchAs() is no longer very important, although
	 * relying on explicit aliases can be a bit faster.
	 *
	 * @param array $list list of global aliases to use
	 *
	 * @return void
	 */
	public static function aliases( $list )
	{
		self::$aliases = $list;
	}

	/**
	 * Enables or disables auto-resolving fetch types.
	 * Auto-resolving aliased parent beans is convenient but can
	 * be slower and can create infinite recursion if you
	 * used aliases to break cyclic relations in your domain.
	 *
	 * @param boolean $automatic TRUE to enable automatic resolving aliased parents
	 *
	 * @return void
	 */
	public static function setAutoResolve( $automatic = TRUE )
	{
		self::$autoResolve = (boolean) $automatic;
	}

	/**
	 * Sets a meta property for all beans. This is a quicker way to set
	 * the meta properties for a collection of beans because this method
	 * can directly access the property arrays of the beans.
	 * This method returns the beans.
	 *
	 * @param array  $beans    beans to set the meta property of
	 * @param string $property property to set
	 * @param mixed  $value    value
	 *
	 * @return array
	 */
	public static function setMetaAll( $beans, $property, $value )
	{
		foreach( $beans as $bean ) {
			if ( $bean instanceof OODBBean ) $bean->__info[ $property ] = $value;
		}

		return $beans;
	}

	/**
	 * Parses the join in the with-snippet.
	 * For instance:
	 *
	 * <code>
	 * $author
	 * 	->withCondition(' @joined.detail.title LIKE ? ')
	 *  ->ownBookList;
	 * </code>
	 *
	 * will automatically join 'detail' on book to
	 * access the title field.
	 *
	 * @note this feature requires Narrow Field Mode and Join Feature
	 * to be both activated (default).
	 *
	 * @param string $type the source type for the join
	 *
	 * @return string
	 */
	private function parseJoin( $type )
	{
		$joinSql = '';
		$joins = array();
		if ( strpos($this->withSql, '@joined.' ) !== FALSE ) {
			$writer   = $this->beanHelper->getToolBox()->getWriter();
			$oldParts = $parts = explode( '@joined.', $this->withSql );
			array_shift( $parts );
			foreach($parts as $part) {
				$explosion = explode( '.', $part );
				$joinInfo  = array_shift( $explosion );
				//Dont join more than once..
				if ( !isset( $joins[$joinInfo] ) ) {
					$joins[ $joinInfo ] = true;
					$joinSql  .= $writer->writeJoin( $type, $joinInfo, 'LEFT' );
				}
			}
			$this->withSql = implode( '', $oldParts );
			$joinSql      .= ' WHERE ';
		}
		return $joinSql;
	}

	/**
	 * Internal method.
	 * Obtains a shared list for a certain type.
	 *
	 * @param string $type the name of the list you want to retrieve.
	 *
	 * @return array
	 */
	private function getSharedList( $type, $redbean, $toolbox )
	{
		$writer = $toolbox->getWriter();

		if ( $this->via ) {
			$oldName = $writer->getAssocTable( array( $this->__info['type'], $type ) );
			if ( $oldName !== $this->via ) {
				//set the new renaming rule
				$writer->renameAssocTable( $oldName, $this->via );
			}
			$this->via = NULL;
		}

		$beans = array();
		if ($this->getID()) {
			$type             = $this->beau( $type );
			$assocManager     = $redbean->getAssociationManager();
			$beans            = $assocManager->related( $this, $type, $this->withSql, $this->withParams );
		}

		$this->withSql    = '';
		$this->withParams = array();

		return $beans;
	}

	/**
	 * Internal method.
	 * Obtains the own list of a certain type.
	 *
	 * @param string      $type   name of the list you want to retrieve
	 * @param OODB        $oodb   The RB OODB object database instance
	 *
	 * @return array
	 */
	private function getOwnList( $type, $redbean )
	{
		$type = $this->beau( $type );

		if ( $this->aliasName ) {
			$parentField = $this->aliasName;
			$myFieldLink = $parentField . '_id';

			$this->__info['sys.alias.' . $type] = $this->aliasName;

			$this->aliasName = NULL;
		} else {
			$parentField = $this->__info['type'];
			$myFieldLink = $parentField . '_id';
		}

		$beans = array();

		if ( $this->getID() ) {

			$firstKey = NULL;
			if ( count( $this->withParams ) > 0 ) {
				reset( $this->withParams );

				$firstKey = key( $this->withParams );
			}

			$joinSql = $this->parseJoin( $type );

			if ( !is_numeric( $firstKey ) || $firstKey === NULL ) {
				$bindings           = $this->withParams;
				$bindings[':slot0'] = $this->getID();

				$beans = $redbean->find( $type, array(), " {$joinSql} $myFieldLink = :slot0 " . $this->withSql, $bindings );
			} else {
				$bindings = array_merge( array( $this->getID() ), $this->withParams );

				$beans = $redbean->find( $type, array(), " {$joinSql} $myFieldLink = ? " . $this->withSql, $bindings );
			}
		}

		$this->withSql    = '';
		$this->withParams = array();

		foreach ( $beans as $beanFromList ) {
			$beanFromList->__info['sys.parentcache.' . $parentField] = $this;
		}

		return $beans;
	}

	/**
	 * Initializes a bean. Used by OODB for dispensing beans.
	 * It is not recommended to use this method to initialize beans. Instead
	 * use the OODB object to dispense new beans. You can use this method
	 * if you build your own bean dispensing mechanism.
	 *
	 * @param string     $type       type of the new bean
	 * @param BeanHelper $beanhelper bean helper to obtain a toolbox and a model
	 *
	 * @return void
	 */
	public function initializeForDispense( $type, BeanHelper $beanhelper )
	{
		$this->beanHelper         = $beanhelper;
		$this->__info['type']     = $type;
		$this->__info['sys.id']   = 'id';
		$this->__info['sys.orig'] = array( 'id' => 0 );
		$this->__info['tainted']  = TRUE;
		$this->__info['changed']  = TRUE;
		$this->properties['id']   = 0;
	}

	/**
	 * Sets the Bean Helper. Normally the Bean Helper is set by OODB.
	 * Here you can change the Bean Helper. The Bean Helper is an object
	 * providing access to a toolbox for the bean necessary to retrieve
	 * nested beans (bean lists: ownBean, sharedBean) without the need to
	 * rely on static calls to the facade (or make this class dep. on OODB).
	 *
	 * @param BeanHelper $helper helper to use for this bean
	 *
	 * @return void
	 */
	public function setBeanHelper( BeanHelper $helper )
	{
		$this->beanHelper = $helper;
	}

	/**
	 * Returns an ArrayIterator so you can treat the bean like
	 * an array with the properties container as its contents.
	 * This method is meant for PHP and allows you to access beans as if
	 * they were arrays, i.e. using array notation:
	 *
	 * $bean[$key] = $value;
	 *
	 * Note that not all PHP functions work with the array interface.
	 *
	 * @return ArrayIterator
	 */
	public function getIterator()
	{
		return new \ArrayIterator( $this->properties );
	}

	/**
	 * Imports all values from an associative array $array. Chainable.
	 * This method imports the values in the first argument as bean
	 * propery and value pairs. Use the second parameter to provide a
	 * selection. If a selection array is passed, only the entries
	 * having keys mentioned in the selection array will be imported.
	 * Set the third parameter to TRUE to preserve spaces in selection keys.
	 *
	 * @param array        $array     what you want to import
	 * @param string|array $selection selection of values
	 * @param boolean      $notrim    if TRUE selection keys will NOT be trimmed
	 *
	 * @return OODBBean
	 */
	public function import( $array, $selection = FALSE, $notrim = FALSE )
	{
		if ( is_string( $selection ) ) {
			$selection = explode( ',', $selection );
		}

		if ( !$notrim && is_array( $selection ) ) {
			foreach ( $selection as $key => $selected ) {
				$selection[$key] = trim( $selected );
			}
		}

		foreach ( $array as $key => $value ) {
			if ( $key != '__info' ) {
				if ( !$selection || ( $selection && in_array( $key, $selection ) ) ) {
					if ( is_array($value ) ) {
						if ( isset( $value['_type'] ) ) {
							$bean = $this->beanHelper->getToolbox()->getRedBean()->dispense( $value['_type'] );
							unset( $value['_type'] );
							$bean->import($value);
							$this->$key = $bean;
						} else {
							$listBeans = array();
							foreach( $value as $listKey => $listItem ) {
								$bean = $this->beanHelper->getToolbox()->getRedBean()->dispense( $listItem['_type'] );
								unset( $listItem['_type'] );
								$bean->import($listItem);
								$list = &$this->$key;
								$list[ $listKey ] = $bean;
							}
						}
					} else {
						$this->$key = $value;
					}
				}
			}
		}

		return $this;
	}

	/**
	* Fast way to import a row.
	* Does not perform any checks.
	*
	* @param array $row a database row
	*
	* @return self
	*/
	public function importRow( $row )
	{
		$this->properties = $row;
		$this->__info['sys.orig'] = $row;
		$this->__info['changed'] = FALSE;
		return $this;
	}

	/**
	 * Imports data from another bean. Chainable.
	 * Copies the properties from the source bean to the internal
	 * property list.
	 *
	 * @param OODBBean $sourceBean the source bean to take properties from
	 *
	 * @return OODBBean
	 */
	public function importFrom( OODBBean $sourceBean )
	{
		$this->__info['tainted'] = TRUE;
		$this->__info['changed'] = TRUE;
		$this->properties = $sourceBean->properties;

		return $this;
	}

	/**
	 * Injects the properties of another bean but keeps the original ID.
	 * Just like import() but keeps the original ID.
	 * Chainable.
	 *
	 * @param OODBBean $otherBean the bean whose properties you would like to copy
	 *
	 * @return OODBBean
	 */
	public function inject( OODBBean $otherBean )
	{
		$myID = $this->properties['id'];

		$this->import( $otherBean->export( FALSE, FALSE, TRUE ) );

		$this->id = $myID;

		return $this;
	}

	/**
	 * Exports the bean as an array.
	 * This function exports the contents of a bean to an array and returns
	 * the resulting array.
	 *
	 * @param boolean $meta    set to TRUE if you want to export meta data as well
	 * @param boolean $parents set to TRUE if you want to export parents as well
	 * @param boolean $onlyMe  set to TRUE if you want to export only this bean
	 * @param array   $filters optional whitelist for export
	 *
	 * @return array
	 */
	public function export( $meta = FALSE, $parents = FALSE, $onlyMe = FALSE, $filters = array() )
	{
		$arr = array();

		if ( $parents ) {
			foreach ( $this as $key => $value ) {
				if ( substr( $key, -3 ) != '_id' ) continue;

				$prop = substr( $key, 0, strlen( $key ) - 3 );
				$this->$prop;
			}
		}

		$hasFilters = is_array( $filters ) && count( $filters );

		foreach ( $this as $key => $value ) {
			if ( !$onlyMe && is_array( $value ) ) {
				$vn = array();

				foreach ( $value as $i => $b ) {
					if ( !( $b instanceof OODBBean ) ) continue;
					$vn[] = $b->export( $meta, FALSE, FALSE, $filters );
					$value = $vn;
				}
			} elseif ( $value instanceof OODBBean ) {
				if ( $hasFilters ) {
					if ( !in_array( strtolower( $value->getMeta( 'type' ) ), $filters ) ) continue;
				}

				$value = $value->export( $meta, $parents, FALSE, $filters );
			}

			$arr[$key] = $value;
		}

		if ( $meta ) {
			$arr['__info'] = $this->__info;
		}

		return $arr;
	}

	/**
	 * Implements isset() function for use as an array.
	 *
	 * @param string $property name of the property you want to check
	 *
	 * @return boolean
	 */
	public function __isset( $property )
	{
		$property = $this->beau( $property );

		if ( strpos( $property, 'xown' ) === 0 && ctype_upper( substr( $property, 4, 1 ) ) ) {
			$property = substr($property, 1);
		}
		return isset( $this->properties[$property] );
	}

	/**
	 * Returns the ID of the bean no matter what the ID field is.
	 *
	 * @return string|null
	 */
	public function getID()
	{
		return ( isset( $this->properties['id'] ) ) ? (string) $this->properties['id'] : NULL;
	}

	/**
	 * Unsets a property of a bean.
	 * Magic method, gets called implicitly when performing the unset() operation
	 * on a bean property.
	 *
	 * @param  string $property property to unset
	 *
	 * @return void
	 */
	public function __unset( $property )
	{
		$property = $this->beau( $property );

		if ( strpos( $property, 'xown' ) === 0 && ctype_upper( substr( $property, 4, 1 ) ) ) {
			$property = substr($property, 1);
		}

		unset( $this->properties[$property] );

		$shadowKey = 'sys.shadow.'.$property;
		if ( isset( $this->__info[ $shadowKey ] ) ) unset( $this->__info[$shadowKey] );

		//also clear modifiers
		$this->withSql    = '';
		$this->withParams = array();
		$this->aliasName  = NULL;
		$this->fetchType  = NULL;
		$this->noLoad     = FALSE;
		$this->all        = FALSE;
		$this->via        = NULL;

		return;
	}

	/**
	 * Adds WHERE clause conditions to ownList retrieval.
	 * For instance to get the pages that belong to a book you would
	 * issue the following command: $book->ownPage
	 * However, to order these pages by number use:
	 *
	 * <code>
	 * $book->with(' ORDER BY `number` ASC ')->ownPage
	 * </code>
	 *
	 * the additional SQL snippet will be merged into the final
	 * query.
	 *
	 * @param string $sql      SQL to be added to retrieval query.
	 * @param array  $bindings array with parameters to bind to SQL snippet
	 *
	 * @return OODBBean
	 */
	public function with( $sql, $bindings = array() )
	{
		$this->withSql    = $sql;
		$this->withParams = $bindings;
		return $this;
	}

	/**
	 * Just like with(). Except that this method prepends the SQL query snippet
	 * with AND which makes it slightly more comfortable to use a conditional
	 * SQL snippet. For instance to filter an own-list with pages (belonging to
	 * a book) on specific chapters you can use:
	 *
	 * $book->withCondition(' chapter = 3 ')->ownPage
	 *
	 * This will return in the own list only the pages having 'chapter == 3'.
	 *
	 * @param string $sql      SQL to be added to retrieval query (prefixed by AND)
	 * @param array  $bindings array with parameters to bind to SQL snippet
	 *
	 * @return OODBBean
	 */
	public function withCondition( $sql, $bindings = array() )
	{
		$this->withSql    = ' AND ' . $sql;
		$this->withParams = $bindings;
		return $this;
	}

	/**
	 * Tells the bean to (re)load the following list without any
	 * conditions. If you have an ownList or sharedList with a
	 * condition you can use this method to reload the entire list.
	 *
	 * Usage:
	 *
	 * <code>
	 * $bean->with( ' LIMIT 3 ' )->ownPage; //Just 3
	 * $bean->all()->ownPage; //Reload all pages
	 * </code>
	 *
	 * @return self
	 */
	public function all()
	{
		$this->all = TRUE;
		return $this;
	}

	/**
	 * Tells the bean to only access the list but not load
	 * its contents. Use this if you only want to add something to a list
	 * and you have no interest in retrieving its contents from the database.
	 *
	 * @return self
	 */
	public function noLoad()
	{
		$this->noLoad = TRUE;
		return $this;
	}

	/**
	 * Prepares an own-list to use an alias. This is best explained using
	 * an example. Imagine a project and a person. The project always involves
	 * two persons: a teacher and a student. The person beans have been aliased in this
	 * case, so to the project has a teacher_id pointing to a person, and a student_id
	 * also pointing to a person. Given a project, we obtain the teacher like this:
	 *
	 * <code>
	 * $project->fetchAs('person')->teacher;
	 * </code>
	 *
	 * Now, if we want all projects of a teacher we cant say:
	 *
	 * <code>
	 * $teacher->ownProject
	 * </code>
	 *
	 * because the $teacher is a bean of type 'person' and no project has been
	 * assigned to a person. Instead we use the alias() method like this:
	 *
	 * <code>
	 * $teacher->alias('teacher')->ownProject
	 * </code>
	 *
	 * now we get the projects associated with the person bean aliased as
	 * a teacher.
	 *
	 * @param string $aliasName the alias name to use
	 *
	 * @return OODBBean
	 */
	public function alias( $aliasName )
	{
		$this->aliasName = $this->beau( $aliasName );

		return $this;
	}

	/**
	 * Returns properties of bean as an array.
	 * This method returns the raw internal property list of the
	 * bean. Only use this method for optimization purposes. Otherwise
	 * use the export() method to export bean data to arrays.
	 *
	 * @return array
	 */
	public function getProperties()
	{
		return $this->properties;
	}

	/**
	 * Returns properties of bean as an array.
	 * This method returns the raw internal property list of the
	 * bean. Only use this method for optimization purposes. Otherwise
	 * use the export() method to export bean data to arrays.
	 * This method returns an array with the properties array and
	 * the type (string).
	 *
	 * @return array
	 */
	public function getPropertiesAndType()
	{
		return array( $this->properties, $this->__info['type'] );
	}

	/**
	 * Turns a camelcase property name into an underscored property name.
	 *
	 * Examples:
	 *
	 * * oneACLRoute -> one_acl_route
	 * * camelCase -> camel_case
	 *
	 * Also caches the result to improve performance.
	 *
	 * @param string $property property to un-beautify
	 *
	 * @return string
	 */
	public function beau( $property )
	{
		static $beautifulColumns = array();

		if ( ctype_lower( $property ) ) return $property;

		if (
			( strpos( $property, 'own' ) === 0 && ctype_upper( substr( $property, 3, 1 ) ) )
			|| ( strpos( $property, 'xown' ) === 0 && ctype_upper( substr( $property, 4, 1 ) ) )
			|| ( strpos( $property, 'shared' ) === 0 && ctype_upper( substr( $property, 6, 1 ) ) )
		) {

			$property = preg_replace( '/List$/', '', $property );
			return $property;
		}

		if ( !isset( $beautifulColumns[$property] ) ) {
			$beautifulColumns[$property] = AQueryWriter::camelsSnake( $property );
		}

		return $beautifulColumns[$property];
	}

	/**
	 * Clears all modifiers.
	 *
	 * @return self
	 */
	public function clearModifiers()
	{
		$this->withSql    = '';
		$this->withParams = array();
		$this->aliasName  = NULL;
		$this->fetchType  = NULL;
		$this->noLoad     = FALSE;
		$this->all        = FALSE;
		$this->via        = NULL;
		return $this;
	}

	/**
	 * Determines whether a list is opened in exclusive mode or not.
	 * If a list has been opened in exclusive mode this method will return TRUE,
	 * othwerwise it will return FALSE.
	 *
	 * @param string $listName name of the list to check
	 *
	 * @return boolean
	 */
	public function isListInExclusiveMode( $listName )
	{
		$listName = $this->beau( $listName );

		if ( strpos( $listName, 'xown' ) === 0 && ctype_upper( substr( $listName, 4, 1 ) ) ) {
			$listName = substr($listName, 1);
		}

		$listName = lcfirst( substr( $listName, 3 ) );

		return ( isset( $this->__info['sys.exclusive-'.$listName] ) && $this->__info['sys.exclusive-'.$listName] );
	}

	/**
	 * Magic Getter. Gets the value for a specific property in the bean.
	 * If the property does not exist this getter will make sure no error
	 * occurs. This is because RedBean allows you to query (probe) for
	 * properties. If the property can not be found this method will
	 * return NULL instead.
	 *
	 * @param string $property name of the property you wish to obtain the value of
	 *
	 * @return mixed
	 */
	public function &__get( $property )
	{
		$isEx          = FALSE;
		$isOwn         = FALSE;
		$isShared      = FALSE;

		if ( !ctype_lower( $property ) ) {
			$property = $this->beau( $property );
			if ( strpos( $property, 'xown' ) === 0 && ctype_upper( substr( $property, 4, 1 ) ) ) {
				$property = substr($property, 1);
				$listName = lcfirst( substr( $property, 3 ) );
				$isEx     = TRUE;
				$isOwn    = TRUE;
				$this->__info['sys.exclusive-'.$listName] = TRUE;
			} elseif ( strpos( $property, 'own' ) === 0 && ctype_upper( substr( $property, 3, 1 ) ) )  {
				$isOwn    = TRUE;
				$listName = lcfirst( substr( $property, 3 ) );
			} elseif ( strpos( $property, 'shared' ) === 0 && ctype_upper( substr( $property, 6, 1 ) ) ) {
				$isShared = TRUE;
			}
		}

		$fieldLink      = $property . '_id';
		$exists         = isset( $this->properties[$property] );

		//If not exists and no field link and no list, bail out.
		if ( !$exists && !isset($this->$fieldLink) && (!$isOwn && !$isShared )) {

			$this->withSql    = '';
			$this->withParams = array();
			$this->aliasName  = NULL;
			$this->fetchType  = NULL;
			$this->noLoad     = FALSE;
			$this->all        = FALSE;
			$this->via        = NULL;

			$NULL = NULL;
			return $NULL;
		}

		$hasAlias       = (!is_null($this->aliasName));
		$differentAlias = ($hasAlias && $isOwn && isset($this->__info['sys.alias.'.$listName])) ?
								($this->__info['sys.alias.'.$listName] !== $this->aliasName) : FALSE;
		$hasSQL         = ($this->withSql !== '' || $this->via !== NULL);
		$hasAll         = (boolean) ($this->all);

		//If exists and no list or exits and list not changed, bail out.
		if ( $exists && ((!$isOwn && !$isShared ) ||  (!$hasSQL && !$differentAlias && !$hasAll)) ) {

			$this->withSql    = '';
			$this->withParams = array();
			$this->aliasName  = NULL;
			$this->fetchType  = NULL;
			$this->noLoad     = FALSE;
			$this->all        = FALSE;
			$this->via        = NULL;
			return $this->properties[$property];
		}

		list( $redbean, , , $toolbox ) = $this->beanHelper->getExtractedToolbox();

		if ( isset( $this->$fieldLink ) ) {
			$this->__info['tainted'] = TRUE;

			if ( isset( $this->__info["sys.parentcache.$property"] ) ) {
				$bean = $this->__info["sys.parentcache.$property"];
			} else {
				if ( isset( self::$aliases[$property] ) ) {
					$type = self::$aliases[$property];
				} elseif ( $this->fetchType ) {
					$type = $this->fetchType;
					$this->fetchType = NULL;
				} else {
					$type = $property;
				}
				$bean = NULL;
				if ( !is_null( $this->properties[$fieldLink] ) ) {
					$bean = $redbean->load( $type, $this->properties[$fieldLink] );
					//If the IDs dont match, we failed to load, so try autoresolv in that case...
					if ( $bean->id !== $this->properties[$fieldLink] && self::$autoResolve ) {
						$type = $this->beanHelper->getToolbox()->getWriter()->inferFetchType( $this->__info['type'], $property );
						if ( !is_null( $type) ) {
							$bean = $redbean->load( $type, $this->properties[$fieldLink] );
							$this->__info["sys.autoresolved.{$property}"] = $type;
						}
					}
				}
			}

			$this->properties[$property] = $bean;
			$this->withSql               = '';
			$this->withParams            = array();
			$this->aliasName             = NULL;
			$this->fetchType             = NULL;
			$this->noLoad                = FALSE;
			$this->all                   = FALSE;
			$this->via                   = NULL;

			return $this->properties[$property];

		}
		//Implicit: elseif ( $isOwn || $isShared ) {
		if ( $this->noLoad ) {
			$beans = array();
		} elseif ( $isOwn ) {
			$beans = $this->getOwnList( $listName, $redbean );
		} else {
			$beans = $this->getSharedList( lcfirst( substr( $property, 6 ) ), $redbean, $toolbox );
		}

		$this->properties[$property]          = $beans;
		$this->__info["sys.shadow.$property"] = $beans;
		$this->__info['tainted']              = TRUE;

		$this->withSql    = '';
		$this->withParams = array();
		$this->aliasName  = NULL;
		$this->fetchType  = NULL;
		$this->noLoad     = FALSE;
		$this->all        = FALSE;
		$this->via        = NULL;

		return $this->properties[$property];
	}

	/**
	 * Magic Setter. Sets the value for a specific property.
	 * This setter acts as a hook for OODB to mark beans as tainted.
	 * The tainted meta property can be retrieved using getMeta("tainted").
	 * The tainted meta property indicates whether a bean has been modified and
	 * can be used in various caching mechanisms.
	 *
	 * @param string $property name of the property you wish to assign a value to
	 * @param  mixed $value    the value you want to assign
	 *
	 * @return void
	 */
	public function __set( $property, $value )
	{
		$isEx          = FALSE;
		$isOwn         = FALSE;
		$isShared      = FALSE;

		if ( !ctype_lower( $property ) ) {
			$property = $this->beau( $property );
			if ( strpos( $property, 'xown' ) === 0 && ctype_upper( substr( $property, 4, 1 ) ) ) {
				$property = substr($property, 1);
				$listName = lcfirst( substr( $property, 3 ) );
				$isEx     = TRUE;
				$isOwn    = TRUE;
				$this->__info['sys.exclusive-'.$listName] = TRUE;
			} elseif ( strpos( $property, 'own' ) === 0 && ctype_upper( substr( $property, 3, 1 ) ) )  {
				$isOwn    = TRUE;
				$listName = lcfirst( substr( $property, 3 ) );
			} elseif ( strpos( $property, 'shared' ) === 0 && ctype_upper( substr( $property, 6, 1 ) ) ) {
				$isShared = TRUE;
			}
		}

		$hasAlias       = (!is_null($this->aliasName));
		$differentAlias = ($hasAlias && $isOwn && isset($this->__info['sys.alias.'.$listName])) ?
								($this->__info['sys.alias.'.$listName] !== $this->aliasName) : FALSE;
		$hasSQL         = ($this->withSql !== '' || $this->via !== NULL);
		$exists         = isset( $this->properties[$property] );
		$fieldLink      = $property . '_id';
		$isFieldLink	= (($pos = strrpos($property, '_id')) !== FALSE) && array_key_exists( ($fieldName = substr($property, 0, $pos)), $this->properties );


		if ( ($isOwn || $isShared) &&  (!$exists || $hasSQL || $differentAlias) ) {

			if ( !$this->noLoad ) {
				list( $redbean, , , $toolbox ) = $this->beanHelper->getExtractedToolbox();
				if ( $isOwn ) {
					$beans = $this->getOwnList( $listName, $redbean );
				} else {
					$beans = $this->getSharedList( lcfirst( substr( $property, 6 ) ), $redbean, $toolbox );
				}
				$this->__info["sys.shadow.$property"] = $beans;
			}
		}

		$this->withSql    = '';
		$this->withParams = array();
		$this->aliasName  = NULL;
		$this->fetchType  = NULL;
		$this->noLoad     = FALSE;
		$this->all        = FALSE;
		$this->via        = NULL;

		$this->__info['tainted'] = TRUE;
		$this->__info['changed'] = TRUE;

		if ( array_key_exists( $fieldLink, $this->properties ) && !( $value instanceof OODBBean ) ) {
			if ( is_null( $value ) || $value === FALSE ) {

				unset( $this->properties[ $property ]);
				$this->properties[ $fieldLink ] = NULL;

				return;
			} else {
				throw new RedException( 'Cannot cast to bean.' );
			}
		}
		
		if ( $isFieldLink ){
			unset( $this->properties[ $fieldName ]);
			$this->properties[ $property ] = NULL;
		}


		if ( $value === FALSE ) {
			$value = '0';
		} elseif ( $value === TRUE ) {
			$value = '1';
		} elseif ( $value instanceof \DateTime ) {
			$value = $value->format( 'Y-m-d H:i:s' );
		}

		$this->properties[$property] = $value;
	}

	/**
	 * Sets a property directly, for internal use only.
	 *
	 * @param string  $property     property
	 * @param mixed   $value        value
	 * @param boolean $updateShadow whether you want to update the shadow
	 * @param boolean $taint        whether you want to mark the bean as tainted
	 *
	 * @return void
	 */
	public function setProperty( $property, $value, $updateShadow = FALSE, $taint = FALSE )
	{
		$this->properties[$property] = $value;

		if ( $updateShadow ) {
			$this->__info['sys.shadow.' . $property] = $value;
		}

		if ( $taint ) {
			$this->__info['tainted'] = TRUE;
			$this->__info['changed'] = TRUE;
		}
	}

	/**
	 * Returns the value of a meta property. A meta property
	 * contains additional information about the bean object that will not
	 * be stored in the database. Meta information is used to instruct
	 * RedBeanPHP as well as other systems how to deal with the bean.
	 * If the property cannot be found this getter will return NULL instead.
	 *
	 * Example:
	 *
	 * <code>
	 * $bean->setMeta( 'flush-cache', TRUE );
	 * </code>
	 *
	 * RedBeanPHP also stores meta data in beans, this meta data uses
	 * keys prefixed with 'sys.' (system).
	 *
	 * @param string $path    path to property in meta data
	 * @param mixed  $default default value
	 *
	 * @return mixed
	 */
	public function getMeta( $path, $default = NULL )
	{
		return ( isset( $this->__info[$path] ) ) ? $this->__info[$path] : $default;
	}

	/**
	 * Gets and unsets a meta property.
	 * Moves a meta property out of the bean.
	 * This is a short-cut method that can be used instead
	 * of combining a get/unset.
	 *
	 * @param string $path    path to property in meta data
	 * @param mixed  $default default value
	 *
	 * @return mixed
	 */
	public function moveMeta( $path, $value = NULL )
	{
		if ( isset( $this->__info[$path] ) ) {
			$value = $this->__info[ $path ];
			unset( $this->__info[ $path ] );
		}
		return $value;
	}

	/**
	 * Stores a value in the specified Meta information property.
	 * The first argument should be the key to store the value under,
	 * the second argument should be the value. It is common to use
	 * a path-like notation for meta data in RedBeanPHP like:
	 * 'my.meta.data', however the dots are purely for readability, the
	 * meta data methods do not store nested structures or hierarchies.
	 *
	 * @param string $path  path / key to store value under
	 * @param mixed  $value value to store in bean (not in database) as meta data
	 *
	 * @return OODBBean
	 */
	public function setMeta( $path, $value )
	{
		$this->__info[$path] = $value;

		return $this;
	}

	/**
	 * Copies the meta information of the specified bean
	 * This is a convenience method to enable you to
	 * exchange meta information easily.
	 *
	 * @param OODBBean $bean bean to copy meta data of
	 *
	 * @return OODBBean
	 */
	public function copyMetaFrom( OODBBean $bean )
	{
		$this->__info = $bean->__info;

		return $this;
	}

	/**
	 * Sends the call to the registered model.
	 * This method can also be used to override bean behaviour.
	 * In that case you don't want an error or exception to be triggered
	 * if the method does not exist in the model (because it's optional).
	 * Unfortunately we cannot add an extra argument to __call() for this
	 * because the signature is fixed. Another option would be to set
	 * a special flag ( i.e. $this->isOptionalCall ) but that would
	 * cause additional complexity because we have to deal with extra temporary state.
	 * So, instead I allowed the method name to be prefixed with '@', in practice
	 * nobody creates methods like that - however the '@' symbol in PHP is widely known
	 * to suppress error handling, so we can reuse the semantics of this symbol.
	 * If a method name gets passed starting with '@' the overrideDontFail variable
	 * will be set to TRUE and the '@' will be stripped from the function name before
	 * attempting to invoke the method on the model. This way, we have all the
	 * logic in one place.
	 *
	 * @param string $method name of the method
	 * @param array  $args   argument list
	 *
	 * @return mixed
	 */
	public function __call( $method, $args )
	{
		$overrideDontFail = FALSE;
		if ( strpos( $method, '@' ) === 0 ) {
			$method = substr( $method, 1 );
			$overrideDontFail = TRUE;
		}

		if ( !isset( $this->__info['model'] ) ) {
			$model = $this->beanHelper->getModelForBean( $this );

			if ( !$model ) {
				return NULL;
			}

			$this->__info['model'] = $model;
		}
		if ( !method_exists( $this->__info['model'], $method ) ) {

			if ( self::$errorHandlingFUSE === FALSE || $overrideDontFail ) {
				return NULL;
			}

			if ( in_array( $method, array( 'update', 'open', 'delete', 'after_delete', 'after_update', 'dispense' ), TRUE ) ) {
				return NULL;
			}

			$message = "FUSE: method does not exist in model: $method";
			if ( self::$errorHandlingFUSE === self::C_ERR_LOG ) {
				error_log( $message );
				return NULL;
			} elseif ( self::$errorHandlingFUSE === self::C_ERR_NOTICE ) {
				trigger_error( $message, E_USER_NOTICE );
				return NULL;
			} elseif ( self::$errorHandlingFUSE === self::C_ERR_WARN ) {
				trigger_error( $message, E_USER_WARNING );
				return NULL;
			} elseif ( self::$errorHandlingFUSE === self::C_ERR_EXCEPTION ) {
				throw new \Exception( $message );
			} elseif ( self::$errorHandlingFUSE === self::C_ERR_FUNC ) {
				$func = self::$errorHandler;
				return $func(array(
					'message' => $message,
					'method' => $method,
					'args' => $args,
					'bean' => $this
				));
			}
			trigger_error( $message, E_USER_ERROR );
			return NULL;
		}

		return call_user_func_array( array( $this->__info['model'], $method ), $args );
	}

	/**
	 * Implementation of __toString Method
	 * Routes call to Model. If the model implements a __toString() method this
	 * method will be called and the result will be returned. In case of an
	 * echo-statement this result will be printed. If the model does not
	 * implement a __toString method, this method will return a JSON
	 * representation of the current bean.
	 *
	 * @return string
	 */
	public function __toString()
	{
		$string = $this->__call( '@__toString', array() );

		if ( $string === NULL ) {
			$list = array();
			foreach($this->properties as $property => $value) {
				if (is_scalar($value)) {
					$list[$property] = $value;
				}
			}
			return json_encode( $list );
		} else {
			return $string;
		}
	}

	/**
	 * Implementation of Array Access Interface, you can access bean objects
	 * like an array.
	 * Call gets routed to __set.
	 *
	 * @param  mixed $offset offset string
	 * @param  mixed $value  value
	 *
	 * @return void
	 */
	public function offsetSet( $offset, $value )
	{
		$this->__set( $offset, $value );
	}

	/**
	 * Implementation of Array Access Interface, you can access bean objects
	 * like an array.
	 *
	 * Array functions do not reveal x-own-lists and list-alias because
	 * you dont want duplicate entries in foreach-loops.
	 * Also offers a slight performance improvement for array access.
	 *
	 * @param  mixed $offset property
	 *
	 * @return boolean
	 */
	public function offsetExists( $offset )
	{
		return $this->__isset( $offset );
	}

	/**
	 * Implementation of Array Access Interface, you can access bean objects
	 * like an array.
	 * Unsets a value from the array/bean.
	 *
	 * Array functions do not reveal x-own-lists and list-alias because
	 * you dont want duplicate entries in foreach-loops.
	 * Also offers a slight performance improvement for array access.
	 *
	 * @param  mixed $offset property
	 *
	 * @return void
	 */
	public function offsetUnset( $offset )
	{
		$this->__unset( $offset );
	}

	/**
	 * Implementation of Array Access Interface, you can access bean objects
	 * like an array.
	 * Returns value of a property.
	 *
	 * Array functions do not reveal x-own-lists and list-alias because
	 * you dont want duplicate entries in foreach-loops.
	 * Also offers a slight performance improvement for array access.
	 *
	 * @param  mixed $offset property
	 *
	 * @return mixed
	 */
	public function &offsetGet( $offset )
	{
		return $this->__get( $offset );
	}

	/**
	 * Chainable method to cast a certain ID to a bean; for instance:
	 * $person = $club->fetchAs('person')->member;
	 * This will load a bean of type person using member_id as ID.
	 *
	 * @param  string $type preferred fetch type
	 *
	 * @return OODBBean
	 */
	public function fetchAs( $type )
	{
		$this->fetchType = $type;

		return $this;
	}

	/**
	 * For polymorphic bean relations.
	 * Same as fetchAs but uses a column instead of a direct value.
	 *
	 * @param string $field field name to use for mapping
	 *
	 * @return OODBBean
	 */
	public function poly( $field )
	{
		return $this->fetchAs( $this->$field );
	}

	/**
	 * Traverses a bean property with the specified function.
	 * Recursively iterates through the property invoking the
	 * function for each bean along the way passing the bean to it.
	 *
	 * Can be used together with with, withCondition, alias and fetchAs.
	 *
	 * @param string $property property
	 * @param callable $function function
	 * @param integer $maxDepth maximum depth for traversal
	 *
	 * @return OODBBean
	 * @throws RedException
	 */
	public function traverse( $property, $function, $maxDepth = NULL )
	{
		$this->via = NULL;
		if ( strpos( $property, 'shared' ) !== FALSE ) {
			throw new RedException( 'Traverse only works with (x)own-lists.' );
		}

		if ( !is_null( $maxDepth ) ) {
			if ( !$maxDepth-- ) return $this;
		}

		$oldFetchType = $this->fetchType;
		$oldAliasName = $this->aliasName;
		$oldWith      = $this->withSql;
		$oldBindings  = $this->withParams;

		$beans = $this->$property;

		if ( $beans === NULL ) return $this;

		if ( !is_array( $beans ) ) $beans = array( $beans );

		foreach( $beans as $bean ) {
			/** @var OODBBean $bean */
			$function( $bean );

			$bean->fetchType  = $oldFetchType;
			$bean->aliasName  = $oldAliasName;
			$bean->withSql    = $oldWith;
			$bean->withParams = $oldBindings;

			$bean->traverse( $property, $function, $maxDepth );
		}

		return $this;
	}

	/**
	 * Implementation of Countable interface. Makes it possible to use
	 * count() function on a bean.
	 *
	 * @return integer
	 */
	public function count()
	{
		return count( $this->properties );
	}

	/**
	 * Checks whether a bean is empty or not.
	 * A bean is empty if it has no other properties than the id field OR
	 * if all the other property are empty().
	 *
	 * @return boolean
	 */
	public function isEmpty()
	{
		$empty = TRUE;
		foreach ( $this->properties as $key => $value ) {
			if ( $key == 'id' ) {
				continue;
			}
			if ( !empty( $value ) ) {
				$empty = FALSE;
			}
		}

		return $empty;
	}

	/**
	 * Chainable setter.
	 *
	 * @param string $property the property of the bean
	 * @param mixed  $value    the value you want to set
	 *
	 * @return OODBBean
	 */
	public function setAttr( $property, $value )
	{
		$this->$property = $value;

		return $this;
	}

	/**
	 * Comfort method.
	 * Unsets all properties in array.
	 *
	 * @param array $properties properties you want to unset.
	 *
	 * @return OODBBean
	 */
	public function unsetAll( $properties )
	{
		foreach ( $properties as $prop ) {
			if ( isset( $this->properties[$prop] ) ) {
				unset( $this->properties[$prop] );
			}
		}

		return $this;
	}

	/**
	 * Returns original (old) value of a property.
	 * You can use this method to see what has changed in a
	 * bean.
	 *
	 * @param string $property name of the property you want the old value of
	 *
	 * @return mixed
	 */
	public function old( $property )
	{
		$old = $this->getMeta( 'sys.orig', array() );

		if ( array_key_exists( $property, $old ) ) {
			return $old[$property];
		}

		return NULL;
	}

	/**
	 * Convenience method.
	 * Returns TRUE if the bean has been changed, or FALSE otherwise.
	 * Same as $bean->getMeta('tainted');
	 * Note that a bean becomes tainted as soon as you retrieve a list from
	 * the bean. This is because the bean lists are arrays and the bean cannot
	 * determine whether you have made modifications to a list so RedBeanPHP
	 * will mark the whole bean as tainted.
	 *
	 * @return boolean
	 */
	public function isTainted()
	{
		return $this->getMeta( 'tainted' );
	}

	/**
	 * Returns TRUE if the value of a certain property of the bean has been changed and
	 * FALSE otherwise.
	 *
	 * Note that this method will return TRUE if applied to a loaded list.
	 * Also note that this method keeps track of the bean's history regardless whether
	 * it has been stored or not. Storing a bean does not undo it's history,
	 * to clean the history of a bean use: clearHistory().
	 *
	 * @param string  $property name of the property you want the change-status of
	 *
	 * @return boolean
	 */
	public function hasChanged( $property )
	{
		return ( array_key_exists( $property, $this->properties ) ) ?
			$this->old( $property ) != $this->properties[$property] : FALSE;
	}

	/**
	 * Returns TRUE if the specified list exists, has been loaded and has been changed:
	 * beans have been added or deleted. This method will not tell you anything about
	 * the state of the beans in the list.
	 *
	 * @param string $property name of the list to check
	 *
	 * @return boolean
	 */
	public function hasListChanged( $property )
	{
		if ( !array_key_exists( $property, $this->properties ) ) return FALSE;
		$diffAdded = array_diff_assoc( $this->properties[$property], $this->__info['sys.shadow.'.$property] );
		if ( count( $diffAdded ) ) return TRUE;
		$diffMissing = array_diff_assoc( $this->__info['sys.shadow.'.$property], $this->properties[$property] );
		if ( count( $diffMissing ) ) return TRUE;
		return FALSE;
	}

	/**
	 * Clears (syncs) the history of the bean.
	 * Resets all shadow values of the bean to their current value.
	 *
	 * @return self
	 */
	public function clearHistory()
	{
		$this->__info['sys.orig'] = array();
		foreach( $this->properties as $key => $value ) {
			if ( is_scalar($value) ) {
				$this->__info['sys.orig'][$key] = $value;
			} else {
				$this->__info['sys.shadow.'.$key] = $value;
			}
		}
		return $this;
	}

	/**
	 * Creates a N-M relation by linking an intermediate bean.
	 * This method can be used to quickly connect beans using indirect
	 * relations. For instance, given an album and a song you can connect the two
	 * using a track with a number like this:
	 *
	 * Usage:
	 *
	 * <code>
	 * $album->link('track', array('number'=>1))->song = $song;
	 * </code>
	 *
	 * or:
	 *
	 * <code>
	 * $album->link($trackBean)->song = $song;
	 * </code>
	 *
	 * What this method does is adding the link bean to the own-list, in this case
	 * ownTrack. If the first argument is a string and the second is an array or
	 * a JSON string then the linking bean gets dispensed on-the-fly as seen in
	 * example #1. After preparing the linking bean, the bean is returned thus
	 * allowing the chained setter: ->song = $song.
	 *
	 * @param string|OODBBean $type          type of bean to dispense or the full bean
	 * @param string|array    $qualification JSON string or array (optional)
	 *
	 * @return OODBBean
	 */
	public function link( $typeOrBean, $qualification = array() )
	{
		if ( is_string( $typeOrBean ) ) {

			$typeOrBean = AQueryWriter::camelsSnake( $typeOrBean );

			$bean = $this->beanHelper->getToolBox()->getRedBean()->dispense( $typeOrBean );

			if ( is_string( $qualification ) ) {
				$data = json_decode( $qualification, TRUE );
			} else {
				$data = $qualification;
			}

			foreach ( $data as $key => $value ) {
				$bean->$key = $value;
			}
		} else {
			$bean = $typeOrBean;
		}

		$list = 'own' . ucfirst( $bean->getMeta( 'type' ) );

		array_push( $this->$list, $bean );

		return $bean;
	}

	/**
	 * Returns a bean of the given type with the same ID of as
	 * the current one. This only happens in a one-to-one relation.
	 * This is as far as support for 1-1 goes in RedBeanPHP. This
	 * method will only return a reference to the bean, changing it
	 * and storing the bean will not update the related one-bean.
	 *
	 * @return OODBBean
	 */
	public function one( $type ) {
		return $this->beanHelper->getToolBox()->getRedBean()->load( $type, $this->id );
	}

	/**
	 * Returns the same bean freshly loaded from the database.
	 *
	 * @return OODBBean
	 */
	public function fresh()
	{
		return $this->beanHelper->getToolbox()->getRedBean()->load( $this->getMeta( 'type' ), $this->properties['id'] );
	}

	/**
	 * Registers a association renaming globally.
	 *
	 * @param string $via type you wish to use for shared lists
	 *
	 * @return OODBBean
	 */
	public function via( $via )
	{
		$this->via = AQueryWriter::camelsSnake( $via );

		return $this;
	}

	/**
	 * Counts all own beans of type $type.
	 * Also works with alias(), with() and withCondition().
	 *
	 * @param string $type the type of bean you want to count
	 *
	 * @return integer
	 */
	public function countOwn( $type )
	{
		$type = $this->beau( $type );

		if ( $this->aliasName ) {
			$myFieldLink     = $this->aliasName . '_id';

			$this->aliasName = NULL;
		} else {
			$myFieldLink = $this->__info['type'] . '_id';
		}

		$count = 0;

		if ( $this->getID() ) {

			$firstKey = NULL;
			if ( count( $this->withParams ) > 0 ) {
				reset( $this->withParams );
				$firstKey = key( $this->withParams );
			}

			$joinSql = $this->parseJoin( $type );

			if ( !is_numeric( $firstKey ) || $firstKey === NULL ) {
					$bindings           = $this->withParams;
					$bindings[':slot0'] = $this->getID();
					$count              = $this->beanHelper->getToolbox()->getWriter()->queryRecordCount( $type, array(), " {$joinSql} $myFieldLink = :slot0 " . $this->withSql, $bindings );
			} else {
					$bindings = array_merge( array( $this->getID() ), $this->withParams );
					$count    = $this->beanHelper->getToolbox()->getWriter()->queryRecordCount( $type, array(), " {$joinSql} $myFieldLink = ? " . $this->withSql, $bindings );
			}

		}

		$this->clearModifiers();
		return (int) $count;
	}

	/**
	 * Counts all shared beans of type $type.
	 * Also works with via(), with() and withCondition().
	 *
	 * @param string $type type of bean you wish to count
	 *
	 * @return integer
	 */
	public function countShared( $type )
	{
		$toolbox = $this->beanHelper->getToolbox();
		$redbean = $toolbox->getRedBean();
		$writer  = $toolbox->getWriter();

		if ( $this->via ) {
			$oldName = $writer->getAssocTable( array( $this->__info['type'], $type ) );

			if ( $oldName !== $this->via ) {
				//set the new renaming rule
				$writer->renameAssocTable( $oldName, $this->via );
				$this->via = NULL;
			}
		}

		$type  = $this->beau( $type );
		$count = 0;

		if ( $this->getID() ) {
			$count = $redbean->getAssociationManager()->relatedCount( $this, $type, $this->withSql, $this->withParams );
		}

		$this->clearModifiers();
		return (integer) $count;
	}

	/**
	 * Iterates through the specified own-list and
	 * fetches all properties (with their type) and
	 * returns the references.
	 * Use this method to quickly load indirectly related
	 * beans in an own-list. Whenever you cannot use a
	 * shared-list this method offers the same convenience
	 * by aggregating the parent beans of all children in
	 * the specified own-list.
	 *
	 * Example:
	 *
	 * <code>
	 * $quest->aggr( 'xownQuestTarget', 'target', 'quest' );
	 * </code>
	 *
	 * Loads (in batch) and returns references to all
	 * quest beans residing in the $questTarget->target properties
	 * of each element in the xownQuestTargetList.
	 *
	 * @param string $list     the list you wish to process
	 * @param string $property the property to load
	 * @param string $type     the type of bean residing in this property (optional)
	 *
	 * @return array
	 */
	public function &aggr( $list, $property, $type = NULL )
	{
		$this->via = NULL;
		$ids = $beanIndex = $references = array();

		if ( strlen( $list ) < 4 ) throw new RedException('Invalid own-list.');
		if ( strpos( $list, 'own') !== 0 ) throw new RedException('Only own-lists can be aggregated.');
		if ( !ctype_upper( substr( $list, 3, 1 ) ) ) throw new RedException('Invalid own-list.');

		if ( is_null( $type ) ) $type = $property;

		foreach( $this->$list as $bean ) {
			$field = $property . '_id';
			if ( isset( $bean->$field)  ) {
				$ids[] = $bean->$field;
				$beanIndex[$bean->$field] = $bean;
			}
		}

		$beans = $this->beanHelper->getToolBox()->getRedBean()->batch( $type, $ids );

		//now preload the beans as well
		foreach( $beans as $bean ) {
			$beanIndex[$bean->id]->setProperty( $property, $bean );
		}

		foreach( $beanIndex as $indexedBean ) {
			$references[] = $indexedBean->$property;
		}

		return $references;
	}

	/**
	 * Tests whether the database identities of two beans are equal.
	 *
	 * @param OODBBean $bean other bean
	 *
	 * @return boolean
	 */
	public function equals(OODBBean $bean)
	{
		return (bool) (
			   ( (string) $this->properties['id'] === (string) $bean->properties['id'] )
			&& ( (string) $this->__info['type']   === (string) $bean->__info['type']   )
		);
	}

	/**
	 * Magic method jsonSerialize, implementation for the \JsonSerializable interface,
	 * this method gets called by json_encode and facilitates a better JSON representation
	 * of the bean. Exports the bean on JSON serialization, for the JSON fans.
	 *
	 * @see  http://php.net/manual/en/class.jsonserializable.php
	 *
	 * @return array
	 */
	public function jsonSerialize()
	{
		return $this->export();
	}
}
}

namespace RedBeanPHP {

use RedBeanPHP\Observer as Observer;

/**
 * Observable
 * Base class for Observables
 *
 * @file            RedBeanPHP/Observable.php
 * @author          Gabor de Mooij and the RedBeanPHP community
 * @license         BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
abstract class Observable { //bracket must be here - otherwise coverage software does not understand.

	/**
	 * @var array
	 */
	private $observers = array();

	/**
	 * Implementation of the Observer Pattern.
	 * Adds an event listener to the observable object.
	 * First argument should be the name of the event you wish to listen for.
	 * Second argument should be the object that wants to be notified in case
	 * the event occurs.
	 *
	 * @param string   $eventname event identifier
	 * @param Observer $observer  observer instance
	 *
	 * @return void
	 */
	public function addEventListener( $eventname, Observer $observer )
	{
		if ( !isset( $this->observers[$eventname] ) ) {
			$this->observers[$eventname] = array();
		}

		foreach ( $this->observers[$eventname] as $o ) {
			if ( $o == $observer ) {
				return;
			}
		}

		$this->observers[$eventname][] = $observer;
	}

	/**
	 * Notifies listeners.
	 * Sends the signal $eventname, the event identifier and a message object
	 * to all observers that have been registered to receive notification for
	 * this event. Part of the observer pattern implementation in RedBeanPHP.
	 *
	 * @param string $eventname event you want signal
	 * @param mixed  $info      message object to send along
	 *
	 * @return void
	 */
	public function signal( $eventname, $info )
	{
		if ( !isset( $this->observers[$eventname] ) ) {
			$this->observers[$eventname] = array();
		}

		foreach ( $this->observers[$eventname] as $observer ) {
			$observer->onEvent( $eventname, $info );
		}
	}
}
}

namespace RedBeanPHP {

/**
 * Observer.
 *
 * Interface for Observer object. Implementation of the
 * observer pattern.
 *
 * @file    RedBeanPHP/Observer.php
 * @author  Gabor de Mooij and the RedBeanPHP community
 * @license BSD/GPLv2
 * @desc    Part of the observer pattern in RedBean
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
interface Observer
{
	/**
	 * An observer object needs to be capable of receiving
	 * notifications. Therefore the observer needs to implement the
	 * onEvent method with two parameters: the event identifier specifying the
	 * current event and a message object (in RedBeanPHP this can also be a bean).
	 *
	 * @param string $eventname event identifier
	 * @param mixed  $bean      a message sent along with the notification
	 *
	 * @return void
	 */
	public function onEvent( $eventname, $bean );
}
}

namespace RedBeanPHP {

/**
 * Adapter Interface.
 * Describes the API for a RedBeanPHP Database Adapter.
 * This interface defines the API contract for
 * a RedBeanPHP Database Adapter.
 *
 * @file    RedBeanPHP/Adapter.php
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * @copyright
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
interface Adapter
{
	/**
	 * Should returns a string containing the most recent SQL query
	 * that has been processed by the adapter.
	 *
	 * @return string
	 */
	public function getSQL();

	/**
	 * Executes an SQL Statement using an array of values to bind
	 * If $noevent is TRUE then this function will not signal its
	 * observers to notify about the SQL execution; this to prevent
	 * infinite recursion when using observers.
	 *
	 * @param string  $sql      string containing SQL code for database
	 * @param array   $bindings array of values to bind to parameters in query string
	 * @param boolean $noevent  no event firing
	 *
	 * @return void
	 */
	public function exec( $sql, $bindings = array(), $noevent = FALSE );

	/**
	 * Executes an SQL Query and returns a resultset.
	 * This method returns a multi dimensional resultset similar to getAll
	 * The values array can be used to bind values to the place holders in the
	 * SQL query.
	 *
	 * @param string $sql      string containing SQL code for database
	 * @param array  $bindings array of values to bind to parameters in query string
	 *
	 * @return array
	 */
	public function get( $sql, $bindings = array() );

	/**
	 * Executes an SQL Query and returns a resultset.
	 * This method returns a single row (one array) resultset.
	 * The values array can be used to bind values to the place holders in the
	 * SQL query.
	 *
	 * @param string $sql      string containing SQL code for database
	 * @param array  $bindings array of values to bind to parameters in query string
	 *
	 * @return array
	 */
	public function getRow( $sql, $bindings = array() );

	/**
	 * Executes an SQL Query and returns a resultset.
	 * This method returns a single column (one array) resultset.
	 * The values array can be used to bind values to the place holders in the
	 * SQL query.
	 *
	 * @param string $sql      string containing SQL code for database
	 * @param array  $bindings array of values to bind to parameters in query string
	 *
	 * @return array
	 */
	public function getCol( $sql, $bindings = array() );

	/**
	 * Executes an SQL Query and returns a resultset.
	 * This method returns a single cell, a scalar value as the resultset.
	 * The values array can be used to bind values to the place holders in the
	 * SQL query.
	 *
	 * @param string $sql      string containing SQL code for database
	 * @param array  $bindings array of values to bind to parameters in query string
	 *
	 * @return string
	 */
	public function getCell( $sql, $bindings = array() );

	/**
	 * Executes the SQL query specified in $sql and takes
	 * the first two columns of the resultset. This function transforms the
	 * resultset into an associative array. Values from the the first column will
	 * serve as keys while the values of the second column will be used as values.
	 * The values array can be used to bind values to the place holders in the
	 * SQL query.
	 *
	 * @param string $sql      string containing SQL code for database
	 * @param array  $bindings array of values to bind to parameters in query string
	 *
	 * @return array
	 */
	public function getAssoc( $sql, $bindings = array() );

	/**
	 * Executes the SQL query specified in $sql and indexes
	 * the row by the first column.
	 *
	 * @param string $sql      Sstring containing SQL code for databaseQL
	 * @param array  $bindings values to bind
	 *
	 * @return array
	 */
	public function getAssocRow( $sql, $bindings = array() );

	/**
	 * Returns the latest insert ID.
	 *
	 * @return integer
	 */
	public function getInsertID();

	/**
	 * Returns the number of rows that have been
	 * affected by the last update statement.
	 *
	 * @return integer
	 */
	public function getAffectedRows();

	/**
	 * Returns a database agnostic Cursor object.
	 *
	 * @param string $sql      string containing SQL code for database
	 * @param array  $bindings array of values to bind to parameters in query string
	 *
	 * @return Cursor
	 */
	public function getCursor( $sql, $bindings = array() );

	/**
	 * Returns the original database resource. This is useful if you want to
	 * perform operations on the driver directly instead of working with the
	 * adapter. RedBean will only access the adapter and never to talk
	 * directly to the driver though.
	 *
	 * @return mixed
	 */
	public function getDatabase();

	/**
	 * This method is part of the RedBean Transaction Management
	 * mechanisms.
	 * Starts a transaction.
	 *
	 * @return void
	 */
	public function startTransaction();

	/**
	 * This method is part of the RedBean Transaction Management
	 * mechanisms.
	 * Commits the transaction.
	 *
	 * @return void
	 */
	public function commit();

	/**
	 * This method is part of the RedBean Transaction Management
	 * mechanisms.
	 * Rolls back the transaction.
	 *
	 * @return void
	 */
	public function rollback();

	/**
	 * Closes database connection.
	 *
	 * @return void
	 */
	public function close();
}
}

namespace RedBeanPHP\Adapter {

use RedBeanPHP\Observable as Observable;
use RedBeanPHP\Adapter as Adapter;
use RedBeanPHP\Driver as Driver;

/**
 * DBAdapter (Database Adapter)
 *
 * An adapter class to connect various database systems to RedBean
 * Database Adapter Class. The task of the database adapter class is to
 * communicate with the database driver. You can use all sorts of database
 * drivers with RedBeanPHP. The default database drivers that ships with
 * the RedBeanPHP library is the RPDO driver ( which uses the PHP Data Objects
 * Architecture aka PDO ).
 *
 * @file    RedBeanPHP/Adapter/DBAdapter.php
 * @author  Gabor de Mooij and the RedBeanPHP Community.
 * @license BSD/GPLv2
 *
 * @copyright
 * (c) copyright G.J.G.T. (Gabor) de Mooij and the RedBeanPHP community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class DBAdapter extends Observable implements Adapter
{
	/**
	 * @var Driver
	 */
	private $db = NULL;

	/**
	 * @var string
	 */
	private $sql = '';

	/**
	 * Constructor.
	 *
	 * Creates an instance of the RedBean Adapter Class.
	 * This class provides an interface for RedBean to work
	 * with ADO compatible DB instances.
	 *
	 * @param Driver $database ADO Compatible DB Instance
	 */
	public function __construct( $database )
	{
		$this->db = $database;
	}

	/**
	 * Returns a string containing the most recent SQL query
	 * processed by the database adapter, thus conforming to the
	 * interface:
	 *
	 * @see Adapter::getSQL
	 *
	 * Methods like get(), getRow() and exec() cause this SQL cache
	 * to get filled. If no SQL query has been processed yet this function
	 * will return an empty string.
	 *
	 * @return string
	 */
	public function getSQL()
	{
		return $this->sql;
	}

	/**
	 * @see Adapter::exec
	 */
	public function exec( $sql, $bindings = array(), $noevent = FALSE )
	{
		if ( !$noevent ) {
			$this->sql = $sql;
			$this->signal( 'sql_exec', $this );
		}

		return $this->db->Execute( $sql, $bindings );
	}

	/**
	 * @see Adapter::get
	 */
	public function get( $sql, $bindings = array() )
	{
		$this->sql = $sql;
		$this->signal( 'sql_exec', $this );

		return $this->db->GetAll( $sql, $bindings );
	}

	/**
	 * @see Adapter::getRow
	 */
	public function getRow( $sql, $bindings = array() )
	{
		$this->sql = $sql;
		$this->signal( 'sql_exec', $this );

		return $this->db->GetRow( $sql, $bindings );
	}

	/**
	 * @see Adapter::getCol
	 */
	public function getCol( $sql, $bindings = array() )
	{
		$this->sql = $sql;
		$this->signal( 'sql_exec', $this );

		return $this->db->GetCol( $sql, $bindings );
	}

	/**
	 * @see Adapter::getAssoc
	 */
	public function getAssoc( $sql, $bindings = array() )
	{
		$this->sql = $sql;

		$this->signal( 'sql_exec', $this );

		$rows  = $this->db->GetAll( $sql, $bindings );

		$assoc = array();
		if ( !$rows ) {
			return $assoc;
		}

		foreach ( $rows as $row ) {
			if ( empty( $row ) ) continue;

			if ( count( $row ) > 2 ) {
            $key   = array_shift( $row );
            $value = $row;
        } elseif ( count( $row ) > 1 ) {
				$key   = array_shift( $row );
				$value = array_shift( $row );
			} else {
				$key   = array_shift( $row );
				$value = $key;
			}

			$assoc[$key] = $value;
		}

		return $assoc;
	}

	/**
	 * @see Adapter::getAssocRow
	 */
	public function getAssocRow($sql, $bindings = array())
	{
		$this->sql = $sql;
		$this->signal( 'sql_exec', $this );

		return $this->db->GetAssocRow( $sql, $bindings );
	}

	/**
	 * @see Adapter::getCell
	 */
	public function getCell( $sql, $bindings = array(), $noSignal = NULL )
	{
		$this->sql = $sql;

		if ( !$noSignal ) $this->signal( 'sql_exec', $this );

		return $this->db->GetOne( $sql, $bindings );
	}

	/**
	 * @see Adapter::getCursor
	 */
	public function getCursor( $sql, $bindings = array() )
	{
		return $this->db->GetCursor( $sql, $bindings );
	}

	/**
	 * @see Adapter::getInsertID
	 */
	public function getInsertID()
	{
		return $this->db->getInsertID();
	}

	/**
	 * @see Adapter::getAffectedRows
	 */
	public function getAffectedRows()
	{
		return $this->db->Affected_Rows();
	}

	/**
	 * @see Adapter::getDatabase
	 */
	public function getDatabase()
	{
		return $this->db;
	}

	/**
	 * @see Adapter::startTransaction
	 */
	public function startTransaction()
	{
		$this->db->StartTrans();
	}

	/**
	 * @see Adapter::commit
	 */
	public function commit()
	{
		$this->db->CommitTrans();
	}

	/**
	 * @see Adapter::rollback
	 */
	public function rollback()
	{
		$this->db->FailTrans();
	}

	/**
	 * @see Adapter::close.
	 */
	public function close()
	{
		$this->db->close();
	}
}
}

namespace RedBeanPHP {

/**
 * Database Cursor Interface.
 * A cursor is used by Query Writers to fetch Query Result rows
 * one row at a time. This is useful if you expect the result set to
 * be quite large. This interface dscribes the API of a database
 * cursor. There can be multiple implementations of the Cursor,
 * by default RedBeanPHP offers the PDOCursor for drivers shipping
 * with RedBeanPHP and the NULLCursor.
 *
 * @file    RedBeanPHP/Cursor.php
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
interface Cursor
{
	/**
	 * Should retrieve the next row of the result set.
	 * This method is used to iterate over the result set.
	 *
	 * @return array
	 */
	public function getNextItem();

	/**
	 * Closes the database cursor.
	 * Some databases require a cursor to be closed before executing
	 * another statement/opening a new cursor.
	 *
	 * @return void
	 */
	public function close();
}
}

namespace RedBeanPHP\Cursor {

use RedBeanPHP\Cursor as Cursor;

/**
 * PDO Database Cursor
 * Implementation of PDO Database Cursor.
 * Used by the BeanCollection to fetch one bean at a time.
 * The PDO Cursor is used by Query Writers to support retrieval
 * of large bean collections. For instance, this class is used to
 * implement the findCollection()/BeanCollection functionality.
 *
 * @file    RedBeanPHP/Cursor/PDOCursor.php
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * @copyright
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class PDOCursor implements Cursor
{
	/**
	 * @var PDOStatement
	 */
	protected $res;

	/**
	 * @var string
	 */
	protected $fetchStyle;

	/**
	 * Constructor, creates a new instance of a PDO Database Cursor.
	 *
	 * @param PDOStatement $res        the PDO statement
	 * @param string       $fetchStyle fetch style constant to use
	 *
	 * @return void
	 */
	public function __construct( \PDOStatement $res, $fetchStyle )
	{
		$this->res = $res;
		$this->fetchStyle = $fetchStyle;
	}

	/**
	 * @see Cursor::getNextItem
	 */
	public function getNextItem()
	{
		return $this->res->fetch();
	}

	/**
	 * @see Cursor::close
	 */
	public function close()
	{
		$this->res->closeCursor();
	}
}
}

namespace RedBeanPHP\Cursor {

use RedBeanPHP\Cursor as Cursor;

/**
 * NULL Database Cursor
 * Implementation of the NULL Cursor.
 * Used for an empty BeanCollection. This Cursor
 * can be used for instance if a query fails but the interface
 * demands a cursor to be returned.
 *
 * @file    RedBeanPHP/Cursor/NULLCursor.php
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * @copyright
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class NullCursor implements Cursor
{
	/**
	 * @see Cursor::getNextItem
	 */
	public function getNextItem()
	{
		return NULL;
	}

	/**
	 * @see Cursor::close
	 */
	public function close()
	{
		return NULL;
	}
}
}

namespace RedBeanPHP {

use RedBeanPHP\Cursor as Cursor;
use RedBeanPHP\Repository as Repository;

/**
 * BeanCollection.
 *
 * The BeanCollection represents a collection of beans and
 * makes it possible to use database cursors. The BeanCollection
 * has a method next() to obtain the first, next and last bean
 * in the collection. The BeanCollection does not implement the array
 * interface nor does it try to act like an array because it cannot go
 * backward or rewind itself.
 *
 * Use the BeanCollection for large datasets where skip/limit is not an
 * option. Keep in mind that ID-marking (querying a start ID) is a decent
 * alternative though.
 *
 * @file    RedBeanPHP/BeanCollection.php
 * @author  Gabor de Mooij and the RedBeanPHP community
 * @license BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class BeanCollection
{
	/**
	 * @var Cursor
	 */
	protected $cursor = NULL;

	/**
	 * @var Repository
	 */
	protected $repository = NULL;

	/**
	 * @var string
	 */
	protected $type = NULL;

	/**
	 * Constructor, creates a new instance of the BeanCollection.
	 *
	 * @param string     $type       type of beans in this collection
	 * @param Repository $repository repository to use to generate bean objects
	 * @param Cursor     $cursor     cursor object to use
	 *
	 * @return void
	 */
	public function __construct( $type, Repository $repository, Cursor $cursor )
	{
		$this->type = $type;
		$this->cursor = $cursor;
		$this->repository = $repository;
	}

	/**
	 * Returns the next bean in the collection.
	 * If called the first time, this will return the first bean in the collection.
	 * If there are no more beans left in the collection, this method
	 * will return NULL.
	 *
	 * @return OODBBean|NULL
	 */
	public function next()
	{
		$row = $this->cursor->getNextItem();
		if ( $row ) {
			$beans = $this->repository->convertToBeans( $this->type, array( $row ) );
			$bean = array_shift( $beans );
			return $bean;
		}
		return NULL;
	}

	/**
	 * Closes the underlying cursor (needed for some databases).
	 *
	 * @return void
	 */
	public function close()
	{
		$this->cursor->close();
	}
}
}

namespace RedBeanPHP {

/**
 * QueryWriter
 * Interface for QueryWriters.
 * Describes the API for a QueryWriter.
 *
 * Terminology:
 *
 * - beautified property (a camelCased property, has to be converted first)
 * - beautified type (a camelCased type, has to be converted first)
 * - type (a bean type, corresponds directly to a table)
 * - property (a bean property, corresponds directly to a column)
 * - table (a checked and quoted type, ready for use in a query)
 * - column (a checked and quoted property, ready for use in query)
 * - tableNoQ (same as type, but in context of a database operation)
 * - columnNoQ (same as property, but in context of a database operation)
 *
 * @file    RedBeanPHP/QueryWriter.php
 * @author  Gabor de Mooij and the RedBeanPHP community
 * @license BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
interface QueryWriter
{
	/**
	 * SQL filter constants
	 */
	const C_SQLFILTER_READ  = 'r';
	const C_SQLFILTER_WRITE = 'w';

	/**
	 * Query Writer constants.
	 */
	const C_SQLSTATE_NO_SUCH_TABLE                  = 1;
	const C_SQLSTATE_NO_SUCH_COLUMN                 = 2;
	const C_SQLSTATE_INTEGRITY_CONSTRAINT_VIOLATION = 3;

	/**
	 * Define data type regions
	 *
	 * 00 - 80: normal data types
	 * 80 - 99: special data types, only scan/code if requested
	 * 99     : specified by user, don't change
	 */
	const C_DATATYPE_RANGE_SPECIAL   = 80;
	const C_DATATYPE_RANGE_SPECIFIED = 99;

	/**
	 * Define GLUE types for use with glueSQLCondition methods.
	 * Determines how to prefix a snippet of SQL before appending it
	 * to other SQL (or integrating it, mixing it otherwise).
	 *
	 * WHERE - glue as WHERE condition
	 * AND   - glue as AND condition
	 */
	const C_GLUE_WHERE = 1;
	const C_GLUE_AND   = 2;

	/**
	 * Writes an SQL Snippet for a JOIN, returns the
	 * SQL snippet string.
	 *
	 * @note A default implementation is available in AQueryWriter
	 * unless a database uses very different SQL this should suffice.
	 *
	 * @param string $type       source type
	 * @param string $targetType target type (type to join)
	 * @param string $leftRight  type of join (possible: 'LEFT', 'RIGHT' or 'INNER').
	 *
	 * @return string $joinSQLSnippet
	 */
	public function writeJoin( $type, $targetType, $joinType );

	/**
	 * Glues an SQL snippet to the beginning of a WHERE clause.
	 * This ensures users don't have to add WHERE to their query snippets.
	 *
	 * The snippet gets prefixed with WHERE or AND
	 * if it starts with a condition.
	 *
	 * If the snippet does NOT start with a condition (or this function thinks so)
	 * the snippet is returned as-is.
	 *
	 * The GLUE type determines the prefix:
	 *
	 * * NONE  prefixes with WHERE
	 * * WHERE prefixes with WHERE and replaces AND if snippets starts with AND
	 * * AND   prefixes with AND
	 *
	 * This method will never replace WHERE with AND since a snippet should never
	 * begin with WHERE in the first place. OR is not supported.
	 *
	 * Only a limited set of clauses will be recognized as non-conditions.
	 * For instance beginning a snippet with complex statements like JOIN or UNION
	 * will not work. This is too complex for use in a snippet.
	 *
	 * @note A default implementation is available in AQueryWriter
	 * unless a database uses very different SQL this should suffice.
	 *
	 * @param string  $sql  SQL Snippet
	 * @param integer $glue the GLUE type - how to glue (C_GLUE_WHERE or C_GLUE_AND)
	 *
	 * @return string
	 */
	public function glueSQLCondition( $sql, $glue = NULL );

	/**
	 * Determines if there is a LIMIT 1 clause in the SQL.
	 * If not, it will add a LIMIT 1. (used for findOne).
	 *
	 * @note A default implementation is available in AQueryWriter
	 * unless a database uses very different SQL this should suffice.
	 *
	 * @param string $sql query to scan and adjust
	 *
	 * @return string
	 */
	public function glueLimitOne( $sql );

	/**
	 * Returns the tables that are in the database.
	 *
	 * @return array
	 */
	public function getTables();

	/**
	 * This method will create a table for the bean.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param string $type type of bean you want to create a table for
	 *
	 * @return void
	 */
	public function createTable( $type );

	/**
	 * Returns an array containing all the columns of the specified type.
	 * The format of the return array looks like this:
	 * $field => $type where $field is the name of the column and $type
	 * is a database specific description of the datatype.
	 *
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param string $type type of bean you want to obtain a column list of
	 *
	 * @return array
	 */
	public function getColumns( $type );

	/**
	 * Returns the Column Type Code (integer) that corresponds
	 * to the given value type. This method is used to determine the minimum
	 * column type required to represent the given value.
	 *
	 * @param string $value value
	 *
	 * @return integer
	 */
	public function scanType( $value, $alsoScanSpecialForTypes = FALSE );

	/**
	 * This method will add a column to a table.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param string  $type   name of the table
	 * @param string  $column name of the column
	 * @param integer $field  data type for field
	 *
	 * @return void
	 */
	public function addColumn( $type, $column, $field );

	/**
	 * Returns the Type Code for a Column Description.
	 * Given an SQL column description this method will return the corresponding
	 * code for the writer. If the include specials flag is set it will also
	 * return codes for special columns. Otherwise special columns will be identified
	 * as specified columns.
	 *
	 * @param string  $typedescription description
	 * @param boolean $includeSpecials whether you want to get codes for special columns as well
	 *
	 * @return integer
	 */
	public function code( $typedescription, $includeSpecials = FALSE );

	/**
	 * This method will widen the column to the specified data type.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param string  $type     type / table that needs to be adjusted
	 * @param string  $column   column that needs to be altered
	 * @param integer $datatype target data type
	 *
	 * @return void
	 */
	public function widenColumn( $type, $column, $datatype );

	/**
	 * Selects records from the database.
	 * This methods selects the records from the database that match the specified
	 * type, conditions (optional) and additional SQL snippet (optional).
	 *
	 * @param string $type       name of the table you want to query
	 * @param array  $conditions criteria ( $column => array( $values ) )
	 * @param string $addSQL     additional SQL snippet
	 * @param array  $bindings   bindings for SQL snippet
	 *
	 * @return array
	 */
	public function queryRecord( $type, $conditions = array(), $addSql = NULL, $bindings = array() );

	/**
	 * Selects records from the database and returns a cursor.
	 * This methods selects the records from the database that match the specified
	 * type, conditions (optional) and additional SQL snippet (optional).
	 *
	 * @param string $type       name of the table you want to query
	 * @param array  $conditions criteria ( $column => array( $values ) )
	 * @param string $addSQL     additional SQL snippet
	 * @param array  $bindings   bindings for SQL snippet
	 *
	 * @return Cursor
	 */
	public function queryRecordWithCursor( $type, $addSql = NULL, $bindings = array() );

	/**
	 * Returns records through an intermediate type. This method is used to obtain records using a link table and
	 * allows the SQL snippets to reference columns in the link table for additional filtering or ordering.
	 *
	 * @param string $sourceType source type, the reference type you want to use to fetch related items on the other side
	 * @param string $destType   destination type, the target type you want to get beans of
	 * @param mixed  $linkID     ID to use for the link table
	 * @param string $addSql     Additional SQL snippet
	 * @param array  $bindings   Bindings for SQL snippet
	 *
	 * @return array
	 */
	public function queryRecordRelated( $sourceType, $destType, $linkID, $addSql = '', $bindings = array() );

	/**
	 * Returns the row that links $sourceType $sourcID to $destType $destID in an N-M relation.
	 *
	 * @param string $sourceType source type, the first part of the link you're looking for
	 * @param string $destType   destination type, the second part of the link you're looking for
	 * @param string $sourceID   ID for the source
	 * @param string $destID     ID for the destination
	 *
	 * @return array|null
	 */
	public function queryRecordLink( $sourceType, $destType, $sourceID, $destID );

	/**
	 * Counts the number of records in the database that match the
	 * conditions and additional SQL.
	 *
	 * @param string $type       name of the table you want to query
	 * @param array  $conditions criteria ( $column => array( $values ) )
	 * @param string $addSQL     additional SQL snippet
	 * @param array  $bindings   bindings for SQL snippet
	 *
	 * @return integer
	 */
	public function queryRecordCount( $type, $conditions = array(), $addSql = NULL, $bindings = array() );

	/**
	 * Returns the number of records linked through $linkType and satisfying the SQL in $addSQL/$bindings.
	 *
	 * @param string $sourceType source type
	 * @param string $targetType the thing you want to count
	 * @param mixed  $linkID     the of the source type
	 * @param string $addSQL     additional SQL snippet
	 * @param array  $bindings   bindings for SQL snippet
	 *
	 * @return integer
	 */
	public function queryRecordCountRelated( $sourceType, $targetType, $linkID, $addSQL = '', $bindings = array() );

	/**
	 * Returns all rows of specified type that have been tagged with one of the
	 * strings in the specified tag list array.
	 *
	 * Note that the additional SQL snippet can only be used for pagination,
	 * the SQL snippet will be appended to the end of the query.
	 *
	 * @param string  $type     the bean type you want to query
	 * @param array   $tagList  an array of strings, each string containing a tag title
	 * @param boolean $all      if TRUE only return records that have been associated with ALL the tags in the list
	 * @param string  $addSql   addition SQL snippet, for pagination
	 * @param array   $bindings parameter bindings for additional SQL snippet
	 *
	 * @return array
	 */
	public function queryTagged( $type, $tagList, $all = FALSE, $addSql = '', $bindings = array() );

	/**
	 * This method should update (or insert a record), it takes
	 * a table name, a list of update values ( $field => $value ) and an
	 * primary key ID (optional). If no primary key ID is provided, an
	 * INSERT will take place.
	 * Returns the new ID.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param string  $type         name of the table to update
	 * @param array   $updatevalues list of update values
	 * @param integer $id           optional primary key ID value
	 *
	 * @return integer
	 */
	public function updateRecord( $type, $updatevalues, $id = NULL );

	/**
	 * Deletes records from the database.
	 * @note $addSql is always prefixed with ' WHERE ' or ' AND .'
	 *
	 * @param string $type       name of the table you want to query
	 * @param array  $conditions criteria ( $column => array( $values ) )
	 * @param string $sql        additional SQL
	 * @param array  $bindings   bindings
	 *
	 * @return void
	 */
	public function deleteRecord( $type, $conditions = array(), $addSql = '', $bindings = array() );

	/**
	 * Deletes all links between $sourceType and $destType in an N-M relation.
	 *
	 * @param string $sourceType source type
	 * @param string $destType   destination type
	 * @param string $sourceID   source ID
	 *
	 * @return void
	 */
	public function deleteRelations( $sourceType, $destType, $sourceID );

	/**
	 * @see QueryWriter::addUniqueConstaint
	 */
	public function addUniqueIndex( $type, $columns );

	/**
	 * This method will add a UNIQUE constraint index to a table on columns $columns.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param string $type               target bean type
	 * @param array  $columnsPartOfIndex columns to include in index
	 *
	 * @return void
	 */
	public function addUniqueConstraint( $type, $columns );

	/**
	 * This method will check whether the SQL state is in the list of specified states
	 * and returns TRUE if it does appear in this list or FALSE if it
	 * does not. The purpose of this method is to translate the database specific state to
	 * a one of the constants defined in this class and then check whether it is in the list
	 * of standard states provided.
	 *
	 * @param string $state SQL state to consider
	 * @param array  $list  list of standardized SQL state constants to check against
	 *
	 * @return boolean
	 */
	public function sqlStateIn( $state, $list );

	/**
	 * This method will remove all beans of a certain type.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param  string $type bean type
	 *
	 * @return void
	 */
	public function wipe( $type );

	/**
	 * This method will add a foreign key from type and field to
	 * target type and target field.
	 * The foreign key is created without an action. On delete/update
	 * no action will be triggered. The FK is only used to allow database
	 * tools to generate pretty diagrams and to make it easy to add actions
	 * later on.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 *
	 * @param  string $type           type that will have a foreign key field
	 * @param  string $targetType     points to this type
	 * @param  string $property       field that contains the foreign key value
	 * @param  string $targetProperty field where the fk points to
	 * @param  string $isDep          whether target is dependent and should cascade on update/delete
	 *
	 * @return void
	 */
	public function addFK( $type, $targetType, $property, $targetProperty, $isDep = false );

	/**
	 * This method will add an index to a type and field with name
	 * $name.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param string $type     type to add index to
	 * @param string $name     name of the new index
	 * @param string $property field to index
	 *
	 * @return void
	 */
	public function addIndex( $type, $name, $property );

	/**
	 * Checks and filters a database structure element like a table of column
	 * for safe use in a query. A database structure has to conform to the
	 * RedBeanPHP DB security policy which basically means only alphanumeric
	 * symbols are allowed. This security policy is more strict than conventional
	 * SQL policies and does therefore not require database specific escaping rules.
	 *
	 * @param string  $databaseStructure name of the column/table to check
	 * @param boolean $noQuotes          TRUE to NOT put backticks or quotes around the string
	 *
	 * @return string
	 */
	public function esc( $databaseStructure, $dontQuote = FALSE );

	/**
	 * Removes all tables and views from the database.
	 *
	 * @return void
	 */
	public function wipeAll();

	/**
	 * Renames an association. For instance if you would like to refer to
	 * album_song as: track you can specify this by calling this method like:
	 *
	 * <code>
	 * renameAssociation('album_song','track')
	 * </code>
	 *
	 * This allows:
	 *
	 * <code>
	 * $album->sharedSong
	 * </code>
	 *
	 * to add/retrieve beans from track instead of album_song.
	 * Also works for exportAll().
	 *
	 * This method also accepts a single associative array as
	 * its first argument.
	 *
	 * @param string|array $fromType original type name, or array
	 * @param string       $toType   new type name (only if 1st argument is string)
	 *
	 * @return void
	 */
	public function renameAssocTable( $fromType, $toType = NULL );

	/**
	 * Returns the format for link tables.
	 * Given an array containing two type names this method returns the
	 * name of the link table to be used to store and retrieve
	 * association records. For instance, given two types: person and
	 * project, the corresponding link table might be: 'person_project'.
	 *
	 * @param  array $types two types array($type1, $type2)
	 *
	 * @return string
	 */
	public function getAssocTable( $types );

	/**
	 * Given a bean type and a property, this method
	 * tries to infer the fetch type using the foreign key
	 * definitions in the database.
	 * For instance: project, student -> person.
	 * If no fetchType can be inferred, this method will return NULL.
	 *
	 * @note QueryWriters do not have to implement this method,
	 * it's optional. A default version is available in AQueryWriter.
	 *
	 * @param $type     the source type to fetch a target type for
	 * @param $property the property to fetch the type of
	 *
	 * @return string|NULL
	 */
	public function inferFetchType( $type, $property );
}
}

namespace RedBeanPHP\QueryWriter {

use RedBeanPHP\Adapter\DBAdapter as DBAdapter;
use RedBeanPHP\RedException as RedException;
use RedBeanPHP\QueryWriter as QueryWriter;
use RedBeanPHP\OODBBean as OODBBean;
use RedBeanPHP\RedException\SQL as SQLException;

/**
 * RedBeanPHP Abstract Query Writer.
 * Represents an abstract Database to RedBean
 * To write a driver for a different database for RedBean
 * Contains a number of functions all implementors can
 * inherit or override.
 *
 * @file    RedBeanPHP/QueryWriter/AQueryWriter.php
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * @copyright
 * (c) copyright G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
abstract class AQueryWriter
{
	/**
	 * @var array
	 */
	private static $sqlFilters = array();

	/**
	 * @var boolean
	 */
	private static $flagSQLFilterSafeMode = false;

	/**
	 * @var boolean
	 */
	private static $flagNarrowFieldMode = true;

	/**
	 * @var array
	 */
	public static $renames = array();

	/**
	 * @var DBAdapter
	 */
	protected $adapter;

	/**
	 * @var string
	 */
	protected $defaultValue = 'NULL';

	/**
	 * @var string
	 */
	protected $quoteCharacter = '';

	/**
	 * @var boolean
	 */
	protected $flagUseCache = TRUE;

	/**
	 * @var array
	 */
	protected $cache = array();

	/**
	 * @var integer
	 */
	protected $maxCacheSizePerType = 20;

	/**
	 * @var array
	 */
	public $typeno_sqltype = array();

	/**
	 * Checks whether a number can be treated like an int.
	 *
	 * @param  string $value string representation of a certain value
	 *
	 * @return boolean
	 */
	public static function canBeTreatedAsInt( $value )
	{
		return (bool) ( strval( $value ) === strval( intval( $value ) ) );
	}

	/**
	 * @see QueryWriter::getAssocTableFormat
	 */
	public static function getAssocTableFormat( $types )
	{
		sort( $types );

		$assoc = implode( '_', $types );

		return ( isset( self::$renames[$assoc] ) ) ? self::$renames[$assoc] : $assoc;
	}

	/**
	 * @see QueryWriter::renameAssociation
	 */
	public static function renameAssociation( $from, $to = NULL )
	{
		if ( is_array( $from ) ) {
			foreach ( $from as $key => $value ) self::$renames[$key] = $value;

			return;
		}

		self::$renames[$from] = $to;
	}

	/**
	 * Globally available service method for RedBeanPHP.
	 * Converts a camel cased string to a snake cased string.
	 *
	 * @param string $camel camelCased string to converty to snake case
	 *
	 * @return string
	 */
	public static function camelsSnake( $camel )
	{
		return strtolower( preg_replace( '/(?<=[a-z])([A-Z])|([A-Z])(?=[a-z])/', '_$1$2', $camel ) );
	}

	/**
	 * Clears renames.
	 *
	 * @return void
	 */
	public static function clearRenames()
	{
		self::$renames = array();
	}

	/**
	 * Toggles 'Narrow Field Mode'.
	 * In Narrow Field mode the queryRecord method will
	 * narrow its selection field to
	 *
	 * SELECT {table}.*
	 *
	 * instead of
	 *
	 * SELECT *
	 *
	 * This is a better way of querying because it allows
	 * more flexibility (for instance joins). However if you need
	 * the wide selector for backward compatibility; use this method
	 * to turn OFF Narrow Field Mode by passing FALSE.
	 *
	 * @param boolean $narrowField TRUE = Narrow Field FALSE = Wide Field
	 *
	 * @return void
	 */
	public static function setNarrowFieldMode( $narrowField )
	{
		self::$flagNarrowFieldMode = (boolean) $narrowField;
	}

	/**
	 * Sets SQL filters.
	 * This is a lowlevel method to set the SQL filter array.
	 * The format of this array is:
	 *
	 * <code>
	 * array(
	 * 		'<MODE, i.e. 'r' for read, 'w' for write>' => array(
	 * 			'<TABLE NAME>' => array(
	 * 				'<COLUMN NAME>' => '<SQL>'
	 * 			)
	 * 		)
	 * )
	 * </code>
	 *
	 * Example:
	 *
	 * <code>
	 * array(
	 *   QueryWriter::C_SQLFILTER_READ => array(
	 * 	'book' => array(
	 * 		'title' => ' LOWER(book.title) '
	 * 	)
	 * )
	 * </code>
	 *
	 * Note that you can use constants instead of magical chars
	 * as keys for the uppermost array.
	 * This is a lowlevel method. For a more friendly method
	 * please take a look at the facade: R::bindFunc().
	 *
	 * @param array list of filters to set
	 *
	 * @return void
	 */
	public static function setSQLFilters( $sqlFilters, $safeMode = false )
	{
		self::$flagSQLFilterSafeMode = (boolean) $safeMode;
		self::$sqlFilters = $sqlFilters;
	}

	/**
	 * Returns current SQL Filters.
	 * This method returns the raw SQL filter array.
	 * This is a lowlevel method. For a more friendly method
	 * please take a look at the facade: R::bindFunc().
	 *
	 * @return array
	 */
	public static function getSQLFilters()
	{
		return self::$sqlFilters;
	}

	/**
	 * Returns a cache key for the cache values passed.
	 * This method returns a fingerprint string to be used as a key to store
	 * data in the writer cache.
	 *
	 * @param array $keyValues key-value to generate key for
	 *
	 * @return string
	 */
	private function getCacheKey( $keyValues )
	{
		return json_encode( $keyValues );
	}

	/**
	 * Returns the values associated with the provided cache tag and key.
	 *
	 * @param string $cacheTag cache tag to use for lookup
	 * @param string $key      key to use for lookup
	 *
	 * @return mixed
	 */
	private function getCached( $cacheTag, $key )
	{
		$sql = $this->adapter->getSQL();

		if ($this->updateCache()) {
			if ( isset( $this->cache[$cacheTag][$key] ) ) {
				return $this->cache[$cacheTag][$key];
			}
		}

		return NULL;
	}

	/**
	 * Checks if the previous query had a keep-cache tag.
	 * If so, the cache will persist, otherwise the cache will be flushed.
	 *
	 * Returns TRUE if the cache will remain and FALSE if a flush has
	 * been performed.
	 *
	 * @return boolean
	 */
	private function updateCache()
	{
		$sql = $this->adapter->getSQL();
		if ( strpos( $sql, '-- keep-cache' ) !== strlen( $sql ) - 13 ) {
			// If SQL has been taken place outside of this method then something else then
			// a select query might have happened! (or instruct to keep cache)
			$this->cache = array();
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Stores data from the writer in the cache under a specific key and cache tag.
	 * A cache tag is used to make sure the cache remains consistent. In most cases the cache tag
	 * will be the bean type, this makes sure queries associated with a certain reference type will
	 * never contain conflicting data.
	 * Why not use the cache tag as a key? Well
	 * we need to make sure the cache contents fits the key (and key is based on the cache values).
	 * Otherwise it would be possible to store two different result sets under the same key (the cache tag).
	 *
	 * In previous versions you could only store one key-entry, I have changed this to
	 * improve caching efficiency (issue #400).
	 *
	 * @param string $cacheTag cache tag (secondary key)
	 * @param string $key      key to store values under
	 * @param array  $values   content to be stored
	 *
	 * @return void
	 */
	private function putResultInCache( $cacheTag, $key, $values )
	{
		if ( isset( $this->cache[$cacheTag] ) ) {
			if ( count( $this->cache[$cacheTag] ) > $this->maxCacheSizePerType ) array_shift( $this->cache[$cacheTag] );
		} else {
			$this->cache[$cacheTag] = array();
		}

		$this->cache[$cacheTag][$key] = $values;
	}

	/**
	 * Creates an SQL snippet from a list of conditions of format:
	 *
	 * <code>
	 * array(
	 *    key => array(
	 *           value1, value2, value3 ....
	 *        )
	 * )
	 * </code>
	 *
	 * @param array  $conditions list of conditions
	 * @param array  $bindings   parameter bindings for SQL snippet
	 * @param string $addSql     additional SQL snippet to append to result
	 *
	 * @return string
	 */
	private function makeSQLFromConditions( $conditions, &$bindings, $addSql = '' )
	{
		reset( $bindings );
		$firstKey       = key( $bindings );
		$paramTypeIsNum = ( is_numeric( $firstKey ) );
		$counter        = 0;

		$sqlConditions = array();
		foreach ( $conditions as $column => $values ) {
			if ( !count( $values ) ) continue;

			$sql = $this->esc( $column );
			$sql .= ' IN ( ';

			if ( !is_array( $values ) ) $values = array( $values );

			if ( $paramTypeIsNum ) {
				$sql .= implode( ',', array_fill( 0, count( $values ), '?' ) ) . ' ) ';

				array_unshift($sqlConditions, $sql);

				foreach ( $values as $k => $v ) {
					$values[$k] = strval( $v );

					array_unshift( $bindings, $v );
				}
			} else {

				$slots = array();

				foreach( $values as $k => $v ) {
					$slot            = ':slot'.$counter++;
					$slots[]         = $slot;
					$bindings[$slot] = strval( $v );
				}

				$sql .= implode( ',', $slots ).' ) ';
				$sqlConditions[] = $sql;
			}
		}

		$sql = '';
		if ( is_array( $sqlConditions ) && count( $sqlConditions ) > 0 ) {
			$sql = implode( ' AND ', $sqlConditions );
			$sql = " WHERE ( $sql ) ";

			if ( $addSql ) $sql .= $addSql;
		} elseif ( $addSql ) {
			$sql = $addSql;
		}

		return $sql;
	}

	/**
	 * Returns the table names and column names for a relational query.
	 *
	 * @param string  $sourceType type of the source bean
	 * @param string  $destType   type of the bean you want to obtain using the relation
	 * @param boolean $noQuote    TRUE if you want to omit quotes
	 *
	 * @return array
	 */
	private function getRelationalTablesAndColumns( $sourceType, $destType, $noQuote = FALSE )
	{
		$linkTable   = $this->esc( $this->getAssocTable( array( $sourceType, $destType ) ), $noQuote );
		$sourceCol   = $this->esc( $sourceType . '_id', $noQuote );

		if ( $sourceType === $destType ) {
			$destCol = $this->esc( $destType . '2_id', $noQuote );
		} else {
			$destCol = $this->esc( $destType . '_id', $noQuote );
		}

		$sourceTable = $this->esc( $sourceType, $noQuote );
		$destTable   = $this->esc( $destType, $noQuote );

		return array( $sourceTable, $destTable, $linkTable, $sourceCol, $destCol );
	}

	/**
	 * Given a type and a property name this method
	 * returns the foreign key map section associated with this pair.
	 *
	 * @param string $type     name of the type
	 * @param string $property name of the property
	 *
	 * @return array|NULL
	 */
	protected function getForeignKeyForTypeProperty( $type, $property )
	{
		$property = $this->esc( $property, TRUE );

		try {
			$map = $this->getKeyMapForType( $type );
		} catch ( SQLException $e ) {
			return NULL;
		}

		foreach( $map as $key ) {
			if ( $key['from'] === $property ) return $key;
		}
		return NULL;
	}

	/**
	 * Returns the foreign key map (FKM) for a type.
	 * A foreign key map describes the foreign keys in a table.
	 * A FKM always has the same structure:
	 *
	 * <code>
	 * array(
	 * 	'name'      => <name of the foreign key>
	 *    'from'      => <name of the column on the source table>
	 *    'table'     => <name of the target table>
	 *    'to'        => <name of the target column> (most of the time 'id')
	 *    'on_update' => <update rule: 'SET NULL','CASCADE' or 'RESTRICT'>
	 *    'on_delete' => <delete rule: 'SET NULL','CASCADE' or 'RESTRICT'>
	 * )
	 * </code>
	 *
	 * @note the keys in the result array are FKDLs, i.e. descriptive unique
	 * keys per source table. Also see: AQueryWriter::makeFKLabel for details.
	 *
	 * @param string $type the bean type you wish to obtain a key map of
	 *
	 * @return array
	 */
	protected function getKeyMapForType( $type )
	{
		return array();
	}

	/**
	 * This method makes a key for a foreign key description array.
	 * This key is a readable string unique for every source table.
	 * This uniform key is called the FKDL Foreign Key Description Label.
	 * Note that the source table is not part of the FKDL because
	 * this key is supposed to be 'per source table'. If you wish to
	 * include a source table, prefix the key with 'on_table_<SOURCE>_'.
	 *
	 * @param string $from  the column of the key in the source table
	 * @param string $type  the type (table) where the key points to
	 * @param string $to    the target column of the foreign key (mostly just 'id')
	 *
	 * @return string
	 */
	protected function makeFKLabel($from, $type, $to)
	{
		return "from_{$from}_to_table_{$type}_col_{$to}";
	}

	/**
	 * Returns an SQL Filter snippet for reading.
	 *
	 * @param string $type type of bean
	 *
	 * @return string
	 */
	protected function getSQLFilterSnippet( $type )
	{
		$existingCols = array();
		if (self::$flagSQLFilterSafeMode) {
			$existingCols = $this->getColumns( $type );
		}

		$sqlFilters = array();
		if ( isset( self::$sqlFilters[QueryWriter::C_SQLFILTER_READ][$type] ) ) {
			foreach( self::$sqlFilters[QueryWriter::C_SQLFILTER_READ][$type] as $property => $sqlFilter ) {
				if ( !self::$flagSQLFilterSafeMode || isset( $existingCols[$property] ) ) {
					$sqlFilters[] = $sqlFilter.' AS '.$property.' ';
				}
			}
		}
		$sqlFilterStr = ( count($sqlFilters) ) ? ( ','.implode( ',', $sqlFilters ) ) : '';
		return $sqlFilterStr;
	}

	/**
	 * Generates a list of parameters (slots) for an SQL snippet.
	 * This method calculates the correct number of slots to insert in the
	 * SQL snippet and determines the correct type of slot. If the bindings
	 * array contains named parameters this method will return named ones and
	 * update the keys in the value list accordingly (that's why we use the &).
	 *
	 * If you pass an offset the bindings will be re-added to the value list.
	 * Some databases cant handle duplicate parameter names in queries.
	 *
	 * @param array   &$valueList    list of values to generate slots for (gets modified if needed)
	 * @param array   $otherBindings list of additional bindings
	 * @param integer $offset        start counter at...
	 *
	 * @return string
	 */
	protected function getParametersForInClause( &$valueList, $otherBindings, $offset = 0 )
	{
		if ( is_array( $otherBindings ) && count( $otherBindings ) > 0 ) {
			reset( $otherBindings );

			$key = key( $otherBindings );

			if ( !is_numeric($key) ) {
				$filler  = array();
				$newList = (!$offset) ? array() : $valueList;
				$counter = $offset;

				foreach( $valueList as $value ) {
					$slot           = ':slot' . ( $counter++ );
					$filler[]       = $slot;
					$newList[$slot] = $value;
				}

				// Change the keys!
				$valueList = $newList;

				return implode( ',', $filler );
			}
		}

		return implode( ',', array_fill( 0, count( $valueList ), '?' ) );
	}

	/**
	 * Adds a data type to the list of data types.
	 * Use this method to add a new column type definition to the writer.
	 * Used for UUID support.
	 *
	 * @param integer $dataTypeID    magic number constant assigned to this data type
	 * @param string  $SQLDefinition SQL column definition (i.e. INT(11))
	 *
	 * @return self
	 */
	protected function addDataType( $dataTypeID, $SQLDefinition )
	{
		$this->typeno_sqltype[ $dataTypeID ] = $SQLDefinition;
		$this->sqltype_typeno[ $SQLDefinition ] = $dataTypeID;
		return $this;
	}

	/**
	 * Returns the sql that should follow an insert statement.
	 *
	 * @param string $table name
	 *
	 * @return string
	 */
	protected function getInsertSuffix( $table )
	{
		return '';
	}

	/**
	 * Checks whether a value starts with zeros. In this case
	 * the value should probably be stored using a text datatype instead of a
	 * numerical type in order to preserve the zeros.
	 *
	 * @param string $value value to be checked.
	 *
	 * @return boolean
	 */
	protected function startsWithZeros( $value )
	{
		$value = strval( $value );

		if ( strlen( $value ) > 1 && strpos( $value, '0' ) === 0 && strpos( $value, '0.' ) !== 0 ) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 * Inserts a record into the database using a series of insert columns
	 * and corresponding insertvalues. Returns the insert id.
	 *
	 * @param string $table         table to perform query on
	 * @param array  $insertcolumns columns to be inserted
	 * @param array  $insertvalues  values to be inserted
	 *
	 * @return integer
	 */
	protected function insertRecord( $type, $insertcolumns, $insertvalues )
	{
		$default = $this->defaultValue;
		$suffix  = $this->getInsertSuffix( $type );
		$table   = $this->esc( $type );

		if ( count( $insertvalues ) > 0 && is_array( $insertvalues[0] ) && count( $insertvalues[0] ) > 0 ) {

			$insertSlots = array();
			foreach ( $insertcolumns as $k => $v ) {
				$insertcolumns[$k] = $this->esc( $v );

				if (isset(self::$sqlFilters['w'][$type][$v])) {
					$insertSlots[] = self::$sqlFilters['w'][$type][$v];
				} else {
					$insertSlots[] = '?';
				}
			}

			$insertSQL = "INSERT INTO $table ( id, " . implode( ',', $insertcolumns ) . " ) VALUES
			( $default, " . implode( ',', $insertSlots ) . " ) $suffix";

			$ids = array();
			foreach ( $insertvalues as $i => $insertvalue ) {
				$ids[] = $this->adapter->getCell( $insertSQL, $insertvalue, $i );
			}

			$result = count( $ids ) === 1 ? array_pop( $ids ) : $ids;
		} else {
			$result = $this->adapter->getCell( "INSERT INTO $table (id) VALUES($default) $suffix" );
		}

		if ( $suffix ) return $result;

		$last_id = $this->adapter->getInsertID();

		return $last_id;
	}

	/**
	 * Checks table name or column name.
	 *
	 * @param string $table table string
	 *
	 * @return string
	 */
	protected function check( $struct )
	{
		if ( !is_string( $struct ) || !preg_match( '/^[a-zA-Z0-9_]+$/', $struct ) ) {
			throw new RedException( 'Identifier does not conform to RedBeanPHP security policies.' );
		}

		return $struct;
	}

	/**
	 * Checks whether the specified type (i.e. table) already exists in the database.
	 * Not part of the Object Database interface!
	 *
	 * @param string $table table name
	 *
	 * @return boolean
	 */
	public function tableExists( $table )
	{
		$tables = $this->getTables();

		return in_array( $table, $tables );
	}

	/**
	 * @see QueryWriter::glueSQLCondition
	 */
	public function glueSQLCondition( $sql, $glue = NULL )
	{
		static $snippetCache = array();

		if ( trim( $sql ) === '' ) {
			return $sql;
		}

		$key = $glue . '|' . $sql;

		if ( isset( $snippetCache[$key] ) ) {
			return $snippetCache[$key];
		}

		$lsql = ltrim( $sql );

		if ( preg_match( '/^(INNER|LEFT|RIGHT|JOIN|AND|OR|WHERE|ORDER|GROUP|HAVING|LIMIT|OFFSET)\s+/i', $lsql ) ) {
			if ( $glue === QueryWriter::C_GLUE_WHERE && stripos( $lsql, 'AND' ) === 0 ) {
				$snippetCache[$key] = ' WHERE ' . substr( $lsql, 3 );
			} else {
				$snippetCache[$key] = $sql;
			}
		} else {
			$snippetCache[$key] = ( ( $glue === QueryWriter::C_GLUE_AND ) ? ' AND ' : ' WHERE ') . $sql;
		}

		return $snippetCache[$key];
	}

	/**
	 * @see QueryWriter::glueLimitOne
	 */
	public function glueLimitOne( $sql = '')
	{
		return ( strpos( strtoupper( $sql ), 'LIMIT' ) === FALSE ) ? ( $sql . ' LIMIT 1 ' ) : $sql;
	}

	/**
	 * @see QueryWriter::esc
	 */
	public function esc( $dbStructure, $dontQuote = FALSE )
	{
		$this->check( $dbStructure );

		return ( $dontQuote ) ? $dbStructure : $this->quoteCharacter . $dbStructure . $this->quoteCharacter;
	}

	/**
	 * @see QueryWriter::addColumn
	 */
	public function addColumn( $type, $column, $field )
	{
		$table  = $type;
		$type   = $field;
		$table  = $this->esc( $table );
		$column = $this->esc( $column );

		$type = ( isset( $this->typeno_sqltype[$type] ) ) ? $this->typeno_sqltype[$type] : '';

		$this->adapter->exec( "ALTER TABLE $table ADD $column $type " );
	}

	/**
	 * @see QueryWriter::updateRecord
	 */
	public function updateRecord( $type, $updatevalues, $id = NULL )
	{
		$table = $type;

		if ( !$id ) {
			$insertcolumns = $insertvalues = array();

			foreach ( $updatevalues as $pair ) {
				$insertcolumns[] = $pair['property'];
				$insertvalues[]  = $pair['value'];
			}

			//Otherwise psql returns string while MySQL/SQLite return numeric causing problems with additions (array_diff)
			return (string) $this->insertRecord( $table, $insertcolumns, array( $insertvalues ) );
		}

		if ( $id && !count( $updatevalues ) ) {
			return $id;
		}

		$table = $this->esc( $table );
		$sql   = "UPDATE $table SET ";

		$p = $v = array();

		foreach ( $updatevalues as $uv ) {

			if ( isset( self::$sqlFilters['w'][$type][$uv['property']] ) ) {
				$p[] = " {$this->esc( $uv["property"] )} = ". self::$sqlFilters['w'][$type][$uv['property']];
			} else {
				$p[] = " {$this->esc( $uv["property"] )} = ? ";
			}

			$v[] = $uv['value'];
		}

		$sql .= implode( ',', $p ) . ' WHERE id = ? ';

		$v[] = $id;

		$this->adapter->exec( $sql, $v );

		return $id;
	}

	/**
	 * @see QueryWriter::writeJoin
	 */
	public function writeJoin( $type, $targetType, $leftRight = 'LEFT' )
	{
		if ( $leftRight !== 'LEFT' && $leftRight !== 'RIGHT' && $leftRight !== 'INNER' )
			throw new RedException( 'Invalid JOIN.' );

		$table = $this->esc( $type );
		$targetTable = $this->esc( $targetType );
		$field = $this->esc( $targetType, TRUE );
		return " {$leftRight} JOIN {$targetTable} ON {$targetTable}.id = {$table}.{$field}_id ";
	}

	/**
	 * @see QueryWriter::queryRecord
	 */
	public function queryRecord( $type, $conditions = array(), $addSql = NULL, $bindings = array() )
	{
		$addSql = $this->glueSQLCondition( $addSql, ( count($conditions) > 0) ? QueryWriter::C_GLUE_AND : NULL );

		$key = NULL;
		if ( $this->flagUseCache ) {
			$key = $this->getCacheKey( array( $conditions, $addSql, $bindings, 'select' ) );

			if ( $cached = $this->getCached( $type, $key ) ) {
				return $cached;
			}
		}

		$table = $this->esc( $type );

		$sqlFilterStr = '';
		if ( count( self::$sqlFilters ) ) {
			$sqlFilterStr = $this->getSQLFilterSnippet( $type );
		}

		$sql   = $this->makeSQLFromConditions( $conditions, $bindings, $addSql );

		$fieldSelection = ( self::$flagNarrowFieldMode ) ? "{$table}.*" : '*';
		$sql   = "SELECT {$fieldSelection} {$sqlFilterStr} FROM {$table} {$sql} -- keep-cache";

		$rows  = $this->adapter->get( $sql, $bindings );

		if ( $this->flagUseCache && $key ) {
			$this->putResultInCache( $type, $key, $rows );
		}

		return $rows;
	}

	/**
	 * @see QueryWriter::queryRecordWithCursor
	 */
	public function queryRecordWithCursor( $type, $addSql = NULL, $bindings = array() )
	{
		$sql = $this->glueSQLCondition( $addSql, NULL );
		$table = $this->esc( $type );
		$sql   = "SELECT {$table}.* FROM {$table} {$sql}";
		return $this->adapter->getCursor( $sql, $bindings );
	}

	/**
	 * @see QueryWriter::queryRecordRelated
	 */
	public function queryRecordRelated( $sourceType, $destType, $linkIDs, $addSql = '', $bindings = array() )
	{
		$addSql = $this->glueSQLCondition( $addSql, QueryWriter::C_GLUE_WHERE );

		list( $sourceTable, $destTable, $linkTable, $sourceCol, $destCol ) = $this->getRelationalTablesAndColumns( $sourceType, $destType );

		$key = $this->getCacheKey( array( $sourceType, $destType, implode( ',', $linkIDs ), $addSql, $bindings ) );

		if ( $this->flagUseCache && $cached = $this->getCached( $destType, $key ) ) {
			return $cached;
		}

		$inClause = $this->getParametersForInClause( $linkIDs, $bindings );

		$sqlFilterStr = '';
		if ( count( self::$sqlFilters ) ) {
			$sqlFilterStr = $this->getSQLFilterSnippet( $destType );
		}

		if ( $sourceType === $destType ) {
			$inClause2 = $this->getParametersForInClause( $linkIDs, $bindings, count( $bindings ) ); //for some databases
			$sql = "
			SELECT
				{$destTable}.* {$sqlFilterStr} ,
				COALESCE(
				NULLIF({$linkTable}.{$sourceCol}, {$destTable}.id),
				NULLIF({$linkTable}.{$destCol}, {$destTable}.id)) AS linked_by
			FROM {$linkTable}
			INNER JOIN {$destTable} ON
			( {$destTable}.id = {$linkTable}.{$destCol} AND {$linkTable}.{$sourceCol} IN ($inClause) ) OR
			( {$destTable}.id = {$linkTable}.{$sourceCol} AND {$linkTable}.{$destCol} IN ($inClause2) )
			{$addSql}
			-- keep-cache";

			$linkIDs = array_merge( $linkIDs, $linkIDs );
		} else {
			$sql = "
			SELECT
				{$destTable}.* {$sqlFilterStr},
				{$linkTable}.{$sourceCol} AS linked_by
			FROM {$linkTable}
			INNER JOIN {$destTable} ON
			( {$destTable}.id = {$linkTable}.{$destCol} AND {$linkTable}.{$sourceCol} IN ($inClause) )
			{$addSql}
			-- keep-cache";
		}

		$bindings = array_merge( $linkIDs, $bindings );

		$rows = $this->adapter->get( $sql, $bindings );

		$this->putResultInCache( $destType, $key, $rows );

		return $rows;
	}

	/**
	 * @see QueryWriter::queryRecordLink
	 */
	public function queryRecordLink( $sourceType, $destType, $sourceID, $destID )
	{
		list( $sourceTable, $destTable, $linkTable, $sourceCol, $destCol ) = $this->getRelationalTablesAndColumns( $sourceType, $destType );

		$key = $this->getCacheKey( array( $sourceType, $destType, $sourceID, $destID ) );

		if ( $this->flagUseCache && $cached = $this->getCached( $linkTable, $key ) ) {
			return $cached;
		}

		$sqlFilterStr = '';
		if ( count( self::$sqlFilters ) ) {
			$sqlFilterStr = $this->getSQLFilterSnippet( $destType );
		}

		if ( $sourceTable === $destTable ) {
			$sql = "SELECT {$linkTable}.* {$sqlFilterStr} FROM {$linkTable}
				WHERE ( {$sourceCol} = ? AND {$destCol} = ? ) OR
				 ( {$destCol} = ? AND {$sourceCol} = ? ) -- keep-cache";
			$row = $this->adapter->getRow( $sql, array( $sourceID, $destID, $sourceID, $destID ) );
		} else {
			$sql = "SELECT {$linkTable}.* {$sqlFilterStr} FROM {$linkTable}
				WHERE {$sourceCol} = ? AND {$destCol} = ? -- keep-cache";
			$row = $this->adapter->getRow( $sql, array( $sourceID, $destID ) );
		}

		$this->putResultInCache( $linkTable, $key, $row );

		return $row;
	}

	/**
	 * @see QueryWriter::queryTagged
	 */
	public function queryTagged( $type, $tagList, $all = FALSE, $addSql = '', $bindings = array() )
	{
		$assocType = $this->getAssocTable( array( $type, 'tag' ) );
		$assocTable = $this->esc( $assocType );
		$assocField = $type . '_id';
		$table = $this->esc( $type );
		$slots = implode( ',', array_fill( 0, count( $tagList ), '?' ) );
		$score = ( $all ) ? count( $tagList ) : 1;

		$sql = "
			SELECT {$table}.*, count({$table}.id) FROM {$table}
			INNER JOIN {$assocTable} ON {$assocField} = {$table}.id
			INNER JOIN tag ON {$assocTable}.tag_id = tag.id
			WHERE tag.title IN ({$slots})
			GROUP BY {$table}.id
			HAVING count({$table}.id) >= ?
			{$addSql}
		";

		$bindings = array_merge( $tagList, array( $score ), $bindings );
		$rows = $this->adapter->get( $sql, $bindings );
		return $rows;
	}

	/**
	 * @see QueryWriter::queryRecordCount
	 */
	public function queryRecordCount( $type, $conditions = array(), $addSql = NULL, $bindings = array() )
	{
		$addSql = $this->glueSQLCondition( $addSql );

		$table  = $this->esc( $type );

		$this->updateCache(); //check if cache chain has been broken

		$sql    = $this->makeSQLFromConditions( $conditions, $bindings, $addSql );
		$sql    = "SELECT COUNT(*) FROM {$table} {$sql} -- keep-cache";

		return (int) $this->adapter->getCell( $sql, $bindings );
	}

	/**
	 * @see QueryWriter::queryRecordCountRelated
	 */
	public function queryRecordCountRelated( $sourceType, $destType, $linkID, $addSql = '', $bindings = array() )
	{
		list( $sourceTable, $destTable, $linkTable, $sourceCol, $destCol ) = $this->getRelationalTablesAndColumns( $sourceType, $destType );

		$this->updateCache(); //check if cache chain has been broken

		if ( $sourceType === $destType ) {
			$sql = "
			SELECT COUNT(*) FROM {$linkTable}
			INNER JOIN {$destTable} ON
			( {$destTable}.id = {$linkTable}.{$destCol} AND {$linkTable}.{$sourceCol} = ? ) OR
			( {$destTable}.id = {$linkTable}.{$sourceCol} AND {$linkTable}.{$destCol} = ? )
			{$addSql}
			-- keep-cache";

			$bindings = array_merge( array( $linkID, $linkID ), $bindings );
		} else {
			$sql = "
			SELECT COUNT(*) FROM {$linkTable}
			INNER JOIN {$destTable} ON
			( {$destTable}.id = {$linkTable}.{$destCol} AND {$linkTable}.{$sourceCol} = ? )
			{$addSql}
			-- keep-cache";

			$bindings = array_merge( array( $linkID ), $bindings );
		}

		return (int) $this->adapter->getCell( $sql, $bindings );
	}

	/**
	 * @see QueryWriter::deleteRecord
	 */
	public function deleteRecord( $type, $conditions = array(), $addSql = NULL, $bindings = array() )
	{
		$addSql = $this->glueSQLCondition( $addSql );

		$table  = $this->esc( $type );

		$sql    = $this->makeSQLFromConditions( $conditions, $bindings, $addSql );
		$sql    = "DELETE FROM {$table} {$sql}";

		$this->adapter->exec( $sql, $bindings );
	}

	/**
	 * @see QueryWriter::deleteRelations
	 */
	public function deleteRelations( $sourceType, $destType, $sourceID )
	{
		list( $sourceTable, $destTable, $linkTable, $sourceCol, $destCol ) = $this->getRelationalTablesAndColumns( $sourceType, $destType );

		if ( $sourceTable === $destTable ) {
			$sql = "DELETE FROM {$linkTable}
				WHERE ( {$sourceCol} = ? ) OR
				( {$destCol} = ?  )
			";

			$this->adapter->exec( $sql, array( $sourceID, $sourceID ) );
		} else {
			$sql = "DELETE FROM {$linkTable}
				WHERE {$sourceCol} = ? ";

			$this->adapter->exec( $sql, array( $sourceID ) );
		}
	}

	/**
	 * @see QueryWriter::widenColumn
	 */
	public function widenColumn( $type, $property, $dataType )
	{
		if ( !isset($this->typeno_sqltype[$dataType]) ) return FALSE;

		$table   = $this->esc( $type );
		$column  = $this->esc( $property );

		$newType = $this->typeno_sqltype[$dataType];

		$this->adapter->exec( "ALTER TABLE $table CHANGE $column $column $newType " );

		return TRUE;
	}

	/**
	 * @see QueryWriter::wipe
	 */
	public function wipe( $type )
	{
		$table = $this->esc( $type );

		$this->adapter->exec( "TRUNCATE $table " );
	}

	/**
	 * @see QueryWriter::renameAssocTable
	 */
	public function renameAssocTable( $from, $to = NULL )
	{
		self::renameAssociation( $from, $to );
	}

	/**
	 * @see QueryWriter::getAssocTable
	 */
	public function getAssocTable( $types )
	{
		return self::getAssocTableFormat( $types );
	}

	/**
	 * Turns caching on or off. Default: off.
	 * If caching is turned on retrieval queries fired after eachother will
	 * use a result row cache.
	 *
	 * @param boolean
	 *
	 * @return void
	 */
	public function setUseCache( $yesNo )
	{
		$this->flushCache();

		$this->flagUseCache = (bool) $yesNo;
	}

	/**
	 * Flushes the Query Writer Cache.
	 * Clears the internal query cache array and returns its overall
	 * size.
	 *
	 * @return integer
	 */
	public function flushCache( $newMaxCacheSizePerType = NULL )
	{
		if ( !is_null( $newMaxCacheSizePerType ) && $newMaxCacheSizePerType > 0 ) {
			$this->maxCacheSizePerType = $newMaxCacheSizePerType;
		}
		$count = count( $this->cache, COUNT_RECURSIVE );
		$this->cache = array();
		return $count;
	}

	/**
	 * @deprecated Use esc() instead.
	 *
	 * @param string  $column   column to be escaped
	 * @param boolean $noQuotes omit quotes
	 *
	 * @return string
	 */
	public function safeColumn( $column, $noQuotes = FALSE )
	{
		return $this->esc( $column, $noQuotes );
	}

	/**
	 * @deprecated Use esc() instead.
	 *
	 * @param string  $table    table to be escaped
	 * @param boolean $noQuotes omit quotes
	 *
	 * @return string
	 */
	public function safeTable( $table, $noQuotes = FALSE )
	{
		return $this->esc( $table, $noQuotes );
	}

	/**
	 * @see QueryWriter::inferFetchType
	 */
	public function inferFetchType( $type, $property )
	{
		$type = $this->esc( $type, TRUE );
		$field = $this->esc( $property, TRUE ) . '_id';
		$keys = $this->getKeyMapForType( $type );

		foreach( $keys as $key ) {
			if (
				$key['from'] === $field
			) return $key['table'];
		}
		return NULL;
	}

	/**
	 * @see QueryWriter::addUniqueConstraint
	 */
	public function addUniqueIndex( $type, $properties )
	{
		return $this->addUniqueConstraint( $type, $properties );
	}
}
}

namespace RedBeanPHP\QueryWriter {

use RedBeanPHP\QueryWriter\AQueryWriter as AQueryWriter;
use RedBeanPHP\QueryWriter as QueryWriter;
use RedBeanPHP\Adapter\DBAdapter as DBAdapter;
use RedBeanPHP\Adapter as Adapter;
use RedBeanPHP\RedException\SQL as SQLException;

/**
 * RedBeanPHP MySQLWriter.
 * This is a QueryWriter class for RedBeanPHP.
 * This QueryWriter provides support for the MySQL/MariaDB database platform.
 *
 * @file    RedBeanPHP/QueryWriter/MySQL.php
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * @copyright
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class MySQL extends AQueryWriter implements QueryWriter
{
	/**
	 * Data types
	 */
	const C_DATATYPE_BOOL             = 0;
	const C_DATATYPE_UINT32           = 2;
	const C_DATATYPE_DOUBLE           = 3;
	const C_DATATYPE_TEXT7            = 4; //InnoDB cant index varchar(255) utf8mb4 - so keep 191 as long as possible
	const C_DATATYPE_TEXT8            = 5;
	const C_DATATYPE_TEXT16           = 6;
	const C_DATATYPE_TEXT32           = 7;
	const C_DATATYPE_SPECIAL_DATE     = 80;
	const C_DATATYPE_SPECIAL_DATETIME = 81;
	const C_DATATYPE_SPECIAL_POINT    = 90;
	const C_DATATYPE_SPECIAL_LINESTRING = 91;
	const C_DATATYPE_SPECIAL_POLYGON    = 92;
	const C_DATATYPE_SPECIAL_MONEY    = 93;

	const C_DATATYPE_SPECIFIED        = 99;

	/**
	 * @var DBAdapter
	 */
	protected $adapter;

	/**
	 * @var string
	 */
	protected $quoteCharacter = '`';

	/**
	 * @see AQueryWriter::getKeyMapForType
	 */
	protected function getKeyMapForType( $type )
	{
		$databaseName = $this->adapter->getCell('SELECT DATABASE()');
		$table = $this->esc( $type, TRUE );
		$keys = $this->adapter->get('
			SELECT
				information_schema.key_column_usage.constraint_name AS `name`,
				information_schema.key_column_usage.referenced_table_name AS `table`,
				information_schema.key_column_usage.column_name AS `from`,
				information_schema.key_column_usage.referenced_column_name AS `to`,
				information_schema.referential_constraints.update_rule AS `on_update`,
				information_schema.referential_constraints.delete_rule AS `on_delete`
				FROM information_schema.key_column_usage
				INNER JOIN information_schema.referential_constraints
				ON information_schema.referential_constraints.constraint_name = information_schema.key_column_usage.constraint_name
			WHERE
				information_schema.key_column_usage.table_schema = :database
				AND information_schema.referential_constraints.constraint_schema  = :database
				AND information_schema.key_column_usage.constraint_schema  = :database
				AND information_schema.key_column_usage.table_name = :table
				AND information_schema.key_column_usage.constraint_name != \'PRIMARY\'
				AND information_schema.key_column_usage.referenced_table_name IS NOT NULL
		', array( ':database' => $databaseName, ':table' => $table ) );
		$keyInfoList = array();
		foreach ( $keys as $k ) {
			$label = $this->makeFKLabel( $k['from'], $k['table'], $k['to'] );
			$keyInfoList[$label] = array(
				'name'          => $k['name'],
				'from'          => $k['from'],
				'table'         => $k['table'],
				'to'            => $k['to'],
				'on_update'     => $k['on_update'],
				'on_delete'     => $k['on_delete']
			);
		}
		return $keyInfoList;
	}

	/**
	 * Constructor
	 *
	 * @param Adapter $adapter Database Adapter
	 */
	public function __construct( Adapter $adapter )
	{
		$this->typeno_sqltype = array(
			MySQL::C_DATATYPE_BOOL             => ' TINYINT(1) UNSIGNED ',
			MySQL::C_DATATYPE_UINT32           => ' INT(11) UNSIGNED ',
			MySQL::C_DATATYPE_DOUBLE           => ' DOUBLE ',
			MySQL::C_DATATYPE_TEXT7            => ' VARCHAR(191) ',
			MYSQL::C_DATATYPE_TEXT8	           => ' VARCHAR(255) ',
			MySQL::C_DATATYPE_TEXT16           => ' TEXT ',
			MySQL::C_DATATYPE_TEXT32           => ' LONGTEXT ',
			MySQL::C_DATATYPE_SPECIAL_DATE     => ' DATE ',
			MySQL::C_DATATYPE_SPECIAL_DATETIME => ' DATETIME ',
			MySQL::C_DATATYPE_SPECIAL_POINT    => ' POINT ',
			MySQL::C_DATATYPE_SPECIAL_LINESTRING => ' LINESTRING ',
			MySQL::C_DATATYPE_SPECIAL_POLYGON => ' POLYGON ',
			MySQL::C_DATATYPE_SPECIAL_MONEY    => ' DECIMAL(10,2) '
		);

		$this->sqltype_typeno = array();

		foreach ( $this->typeno_sqltype as $k => $v ) {
			$this->sqltype_typeno[trim( strtolower( $v ) )] = $k;
		}

		$this->adapter = $adapter;

		$this->encoding = $this->adapter->getDatabase()->getMysqlEncoding();
	}

	/**
	 * This method returns the datatype to be used for primary key IDS and
	 * foreign keys. Returns one if the data type constants.
	 *
	 * @return integer
	 */
	public function getTypeForID()
	{
		return self::C_DATATYPE_UINT32;
	}

	/**
	 * @see QueryWriter::getTables
	 */
	public function getTables()
	{
		return $this->adapter->getCol( 'show tables' );
	}

	/**
	 * @see QueryWriter::createTable
	 */
	public function createTable( $table )
	{
		$table = $this->esc( $table );

		$encoding = $this->adapter->getDatabase()->getMysqlEncoding();
		$sql   = "CREATE TABLE $table (id INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT, PRIMARY KEY ( id )) ENGINE = InnoDB DEFAULT CHARSET={$encoding} COLLATE={$encoding}_unicode_ci ";

		$this->adapter->exec( $sql );
	}

	/**
	 * @see QueryWriter::getColumns
	 */
	public function getColumns( $table )
	{
		$columnsRaw = $this->adapter->get( "DESCRIBE " . $this->esc( $table ) );

		$columns = array();
		foreach ( $columnsRaw as $r ) {
			$columns[$r['Field']] = $r['Type'];
		}

		return $columns;
	}

	/**
	 * @see QueryWriter::scanType
	 */
	public function scanType( $value, $flagSpecial = FALSE )
	{
		$this->svalue = $value;

		if ( is_null( $value ) ) return MySQL::C_DATATYPE_BOOL;
		if ( $value === INF ) return MySQL::C_DATATYPE_TEXT7;

		if ( $flagSpecial ) {
			if ( preg_match( '/^-?\d+\.\d{2}$/', $value ) ) {
				return MySQL::C_DATATYPE_SPECIAL_MONEY;
			}
			if ( preg_match( '/^\d{4}\-\d\d-\d\d$/', $value ) ) {
				return MySQL::C_DATATYPE_SPECIAL_DATE;
			}
			if ( preg_match( '/^\d{4}\-\d\d-\d\d\s\d\d:\d\d:\d\d$/', $value ) ) {
				return MySQL::C_DATATYPE_SPECIAL_DATETIME;
			}
			if ( preg_match( '/^POINT\(/', $value ) ) {
				return MySQL::C_DATATYPE_SPECIAL_POINT;
			}
			if ( preg_match( '/^LINESTRING\(/', $value ) ) {
				return MySQL::C_DATATYPE_SPECIAL_LINESTRING;
			}
			if ( preg_match( '/^POLYGON\(/', $value ) ) {
				return MySQL::C_DATATYPE_SPECIAL_POLYGON;
			}
		}

		//setter turns TRUE FALSE into 0 and 1 because database has no real bools (TRUE and FALSE only for test?).
		if ( $value === FALSE || $value === TRUE || $value === '0' || $value === '1' ) {
			return MySQL::C_DATATYPE_BOOL;
		}

		if ( is_float( $value ) ) return self::C_DATATYPE_DOUBLE;

		if ( !$this->startsWithZeros( $value ) ) {

			if ( is_numeric( $value ) && ( floor( $value ) == $value ) && $value >= 0 && $value <= 4294967295 ) {
				return MySQL::C_DATATYPE_UINT32;
			}

			if ( is_numeric( $value ) ) {
				return MySQL::C_DATATYPE_DOUBLE;
			}
		}

		if ( mb_strlen( $value, 'UTF-8' ) <= 191 ) {
			return MySQL::C_DATATYPE_TEXT7;
		}

		if ( mb_strlen( $value, 'UTF-8' ) <= 255 ) {
			return MySQL::C_DATATYPE_TEXT8;
		}

		if ( mb_strlen( $value, 'UTF-8' ) <= 65535 ) {
			return MySQL::C_DATATYPE_TEXT16;
		}

		return MySQL::C_DATATYPE_TEXT32;
	}

	/**
	 * @see QueryWriter::code
	 */
	public function code( $typedescription, $includeSpecials = FALSE )
	{
		if ( isset( $this->sqltype_typeno[$typedescription] ) ) {
			$r = $this->sqltype_typeno[$typedescription];
		} else {
			$r = self::C_DATATYPE_SPECIFIED;
		}

		if ( $includeSpecials ) {
			return $r;
		}

		if ( $r >= QueryWriter::C_DATATYPE_RANGE_SPECIAL ) {
			return self::C_DATATYPE_SPECIFIED;
		}

		return $r;
	}

	/**
	 * @see QueryWriter::addUniqueIndex
	 */
	public function addUniqueConstraint( $type, $properties )
	{
		$tableNoQ = $this->esc( $type, TRUE );
		$columns = array();
		foreach( $properties as $key => $column ) $columns[$key] = $this->esc( $column );
		$table = $this->esc( $type );
		sort( $columns ); // Else we get multiple indexes due to order-effects
		$name = 'UQ_' . sha1( implode( ',', $columns ) );
		try {
			$sql = "ALTER TABLE $table
						 ADD UNIQUE INDEX $name (" . implode( ',', $columns ) . ")";
			$this->adapter->exec( $sql );
		} catch ( SQLException $e ) {
			//do nothing, dont use alter table ignore, this will delete duplicate records in 3-ways!
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * @see QueryWriter::addIndex
	 */
	public function addIndex( $type, $name, $property )
	{
		try {
			$table  = $this->esc( $type );
			$name   = preg_replace( '/\W/', '', $name );
			$column = $this->esc( $property );
			$this->adapter->exec( "CREATE INDEX $name ON $table ($column) " );
			return TRUE;
		} catch ( SQLException $e ) {
			return FALSE;
		}
	}

	/**
	 * @see QueryWriter::addFK
	 * @return bool
	 */
	public function addFK( $type, $targetType, $property, $targetProperty, $isDependent = FALSE )
	{
		$table = $this->esc( $type );
		$targetTable = $this->esc( $targetType );
		$targetTableNoQ = $this->esc( $targetType, TRUE );
		$field = $this->esc( $property );
		$fieldNoQ = $this->esc( $property, TRUE );
		$targetField = $this->esc( $targetProperty );
		$targetFieldNoQ = $this->esc( $targetProperty, TRUE );
		$tableNoQ = $this->esc( $type, TRUE );
		$fieldNoQ = $this->esc( $property, TRUE );
		if ( !is_null( $this->getForeignKeyForTypeProperty( $tableNoQ, $fieldNoQ ) ) ) return FALSE;

		//Widen the column if it's incapable of representing a foreign key (at least INT).
		$columns = $this->getColumns( $tableNoQ );
		$idType = $this->getTypeForID();
		if ( $this->code( $columns[$fieldNoQ] ) !==  $idType ) {
			$this->widenColumn( $type, $property, $idType );
		}

		$fkName = 'fk_'.($tableNoQ.'_'.$fieldNoQ);
		$cName = 'c_'.$fkName;
		try {
			$this->adapter->exec( "
				ALTER TABLE {$table}
				ADD CONSTRAINT $cName
				FOREIGN KEY $fkName ( `{$fieldNoQ}` ) REFERENCES `{$targetTableNoQ}`
				(`{$targetFieldNoQ}`) ON DELETE " . ( $isDependent ? 'CASCADE' : 'SET NULL' ) . ' ON UPDATE '.( $isDependent ? 'CASCADE' : 'SET NULL' ).';');
		} catch ( SQLException $e ) {
			// Failure of fk-constraints is not a problem
		}
		return true;
	}

	/**
	 * @see QueryWriter::sqlStateIn
	 */
	public function sqlStateIn( $state, $list )
	{
		$stateMap = array(
			'42S02' => QueryWriter::C_SQLSTATE_NO_SUCH_TABLE,
			'42S22' => QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN,
			'23000' => QueryWriter::C_SQLSTATE_INTEGRITY_CONSTRAINT_VIOLATION
		);

		return in_array( ( isset( $stateMap[$state] ) ? $stateMap[$state] : '0' ), $list );
	}

	/**
	 * @see QueryWriter::wipeAll
	 */
	public function wipeAll()
	{
		$this->adapter->exec( 'SET FOREIGN_KEY_CHECKS = 0;' );

		foreach ( $this->getTables() as $t ) {
			try {
				$this->adapter->exec( "DROP TABLE IF EXISTS `$t`" );
			} catch ( SQLException $e ) {
			}

			try {
				$this->adapter->exec( "DROP VIEW IF EXISTS `$t`" );
			} catch ( SQLException $e ) {
			}
		}

		$this->adapter->exec( 'SET FOREIGN_KEY_CHECKS = 1;' );
	}
}
}

namespace RedBeanPHP\QueryWriter {

use RedBeanPHP\QueryWriter\AQueryWriter as AQueryWriter;
use RedBeanPHP\QueryWriter as QueryWriter;
use RedBeanPHP\Adapter\DBAdapter as DBAdapter;
use RedBeanPHP\Adapter as Adapter;
use RedBeanPHP\RedException\SQL as SQLException;

/**
 * RedBeanPHP SQLiteWriter with support for SQLite types
 * This is a QueryWriter class for RedBeanPHP.
 * This QueryWriter provides support for the SQLite database platform.
 *
 * @file    RedBeanPHP/QueryWriter/SQLiteT.php
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * @copyright
 * (c) copyright G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class SQLiteT extends AQueryWriter implements QueryWriter
{
	/**
	 * Data types
	 */
	const C_DATATYPE_INTEGER   = 0;
	const C_DATATYPE_NUMERIC   = 1;
	const C_DATATYPE_TEXT      = 2;
	const C_DATATYPE_SPECIFIED = 99;

	/**
	 * @var DBAdapter
	 */
	protected $adapter;

	/**
	 * @var string
	 */
	protected $quoteCharacter = '`';

	/**
     * Tasks to be done at DB-connect.
     *
	 * @return void
	 */
    private function boot()
    {
        // See Github issue #545
        $this->adapter->exec( "PRAGMA foreign_keys = 1" );
    }

	/**
	 * Gets all information about a table (from a type).
	 *
	 * Format:
	 * array(
	 *    name => name of the table
	 *    columns => array( name => datatype )
	 *    indexes => array() raw index information rows from PRAGMA query
	 *    keys => array() raw key information rows from PRAGMA query
	 * )
	 *
	 * @param string $type type you want to get info of
	 *
	 * @return array
	 */
	protected function getTable( $type )
	{
		$tableName = $this->esc( $type, TRUE );
		$columns   = $this->getColumns( $type );
		$indexes   = $this->getIndexes( $type );
		$keys      = $this->getKeyMapForType( $type );

		$table = array(
			'columns' => $columns,
			'indexes' => $indexes,
			'keys' => $keys,
			'name' => $tableName
		);

		$this->tableArchive[$tableName] = $table;

		return $table;
	}

	/**
	 * Puts a table. Updates the table structure.
	 * In SQLite we can't change columns, drop columns, change or add foreign keys so we
	 * have a table-rebuild function. You simply load your table with getTable(), modify it and
	 * then store it with putTable()...
	 *
	 * @param array $tableMap information array
	 *
	 * @return void
	 */
	protected function putTable( $tableMap )
	{
		$table = $tableMap['name'];
		$q     = array();
		$q[]   = "DROP TABLE IF EXISTS tmp_backup;";

		$oldColumnNames = array_keys( $this->getColumns( $table ) );

		foreach ( $oldColumnNames as $k => $v ) $oldColumnNames[$k] = "`$v`";

		$q[] = "CREATE TEMPORARY TABLE tmp_backup(" . implode( ",", $oldColumnNames ) . ");";
		$q[] = "INSERT INTO tmp_backup SELECT * FROM `$table`;";
		$q[] = "PRAGMA foreign_keys = 0 ";
		$q[] = "DROP TABLE `$table`;";

		$newTableDefStr = '';
		foreach ( $tableMap['columns'] as $column => $type ) {
			if ( $column != 'id' ) {
				$newTableDefStr .= ",`$column` $type";
			}
		}

		$fkDef = '';
		foreach ( $tableMap['keys'] as $key ) {
			$fkDef .= ", FOREIGN KEY(`{$key['from']}`)
						 REFERENCES `{$key['table']}`(`{$key['to']}`)
						 ON DELETE {$key['on_delete']} ON UPDATE {$key['on_update']}";
		}

		$q[] = "CREATE TABLE `$table` ( `id` INTEGER PRIMARY KEY AUTOINCREMENT  $newTableDefStr  $fkDef );";

		foreach ( $tableMap['indexes'] as $name => $index ) {
			if ( strpos( $name, 'UQ_' ) === 0 ) {
				$cols = explode( '__', substr( $name, strlen( 'UQ_' . $table ) ) );
				foreach ( $cols as $k => $v ) $cols[$k] = "`$v`";
				$q[] = "CREATE UNIQUE INDEX $name ON `$table` (" . implode( ',', $cols ) . ")";
			} else $q[] = "CREATE INDEX $name ON `$table` ({$index['name']}) ";
		}

		$q[] = "INSERT INTO `$table` SELECT * FROM tmp_backup;";
		$q[] = "DROP TABLE tmp_backup;";
		$q[] = "PRAGMA foreign_keys = 1 ";

		foreach ( $q as $sq ) $this->adapter->exec( $sq );
	}

	/**
	 * Returns the an array describing the indexes for type $type.
	 *
	 * @param string $type type to describe indexes of
	 *
	 * @return array
	 */
	protected function getIndexes( $type )
	{
		$table   = $this->esc( $type, TRUE );
		$indexes = $this->adapter->get( "PRAGMA index_list('$table')" );

		$indexInfoList = array();
		foreach ( $indexes as $i ) {
			$indexInfoList[$i['name']] = $this->adapter->getRow( "PRAGMA index_info('{$i['name']}') " );

			$indexInfoList[$i['name']]['unique'] = $i['unique'];
		}

		return $indexInfoList;
	}

	/**
	 * Adds a foreign key to a type.
	 * Note: cant put this in try-catch because that can hide the fact
	 * that database has been damaged.
	 *
	 * @param  string  $type        type you want to modify table of
	 * @param  string  $targetType  target type
	 * @param  string  $field       field of the type that needs to get the fk
	 * @param  string  $targetField field where the fk needs to point to
	 * @param  integer $buildopt    0 = NO ACTION, 1 = ON DELETE CASCADE
	 *
	 * @return boolean
	 */
	protected function buildFK( $type, $targetType, $property, $targetProperty, $constraint = FALSE )
	{
		$table           = $this->esc( $type, TRUE );
		$targetTable     = $this->esc( $targetType, TRUE );
		$column          = $this->esc( $property, TRUE );
		$targetColumn    = $this->esc( $targetProperty, TRUE );

		$tables = $this->getTables();
		if ( !in_array( $targetTable, $tables ) ) return FALSE;

		if ( !is_null( $this->getForeignKeyForTypeProperty( $table, $column ) ) ) return FALSE;
		$t = $this->getTable( $table );
		$consSQL = ( $constraint ? 'CASCADE' : 'SET NULL' );
		$label   = 'from_' . $column . '_to_table_' . $targetTable . '_col_' . $targetColumn;
		$t['keys'][$label] = array(
			'table'     => $targetTable,
			'from'      => $column,
			'to'        => $targetColumn,
			'on_update' => $consSQL,
			'on_delete' => $consSQL
		);
		$this->putTable( $t );
		return TRUE;
	}

	/**
	 * @see AQueryWriter::getKeyMapForType
	 */
	protected function getKeyMapForType( $type )
	{
		$table = $this->esc( $type, TRUE );
		$keys  = $this->adapter->get( "PRAGMA foreign_key_list('$table')" );
		$keyInfoList = array();
		foreach ( $keys as $k ) {
			$label = $this->makeFKLabel( $k['from'], $k['table'], $k['to'] );
			$keyInfoList[$label] = array(
				'name'          => $label,
				'from'          => $k['from'],
				'table'         => $k['table'],
				'to'            => $k['to'],
				'on_update'     => $k['on_update'],
				'on_delete'     => $k['on_delete']
			);
		}
		return $keyInfoList;
	}

	/**
	 * Constructor
	 *
	 * @param Adapter $adapter Database Adapter
	 */
	public function __construct( Adapter $adapter )
	{
		$this->typeno_sqltype = array(
			SQLiteT::C_DATATYPE_INTEGER => 'INTEGER',
			SQLiteT::C_DATATYPE_NUMERIC => 'NUMERIC',
			SQLiteT::C_DATATYPE_TEXT    => 'TEXT',
		);

		$this->sqltype_typeno = array();

		foreach ( $this->typeno_sqltype as $k => $v ) {
			$this->sqltype_typeno[$v] = $k;
		}

		$this->adapter = $adapter;

        $this->boot();
	}

	/**
	 * This method returns the datatype to be used for primary key IDS and
	 * foreign keys. Returns one if the data type constants.
	 *
	 * @return integer $const data type to be used for IDS.
	 */
	public function getTypeForID()
	{
		return self::C_DATATYPE_INTEGER;
	}

	/**
	 * @see QueryWriter::scanType
	 */
	public function scanType( $value, $flagSpecial = FALSE )
	{
		$this->svalue = $value;

		if ( $value === NULL ) return self::C_DATATYPE_INTEGER;
		if ( $value === INF ) return self::C_DATATYPE_TEXT;

		if ( $this->startsWithZeros( $value ) ) return self::C_DATATYPE_TEXT;

		if ( $value === TRUE || $value === FALSE )  return self::C_DATATYPE_INTEGER;

		if ( is_numeric( $value ) && ( intval( $value ) == $value ) && $value < 2147483648 && $value > -2147483648 ) return self::C_DATATYPE_INTEGER;

		if ( ( is_numeric( $value ) && $value < 2147483648 && $value > -2147483648)
			|| preg_match( '/\d{4}\-\d\d\-\d\d/', $value )
			|| preg_match( '/\d{4}\-\d\d\-\d\d\s\d\d:\d\d:\d\d/', $value )
		) {
			return self::C_DATATYPE_NUMERIC;
		}

		return self::C_DATATYPE_TEXT;
	}

	/**
	 * @see QueryWriter::addColumn
	 */
	public function addColumn( $table, $column, $type )
	{
		$column = $this->check( $column );
		$table  = $this->check( $table );
		$type   = $this->typeno_sqltype[$type];

		$this->adapter->exec( "ALTER TABLE `$table` ADD `$column` $type " );
	}

	/**
	 * @see QueryWriter::code
	 */
	public function code( $typedescription, $includeSpecials = FALSE )
	{
		$r = ( ( isset( $this->sqltype_typeno[$typedescription] ) ) ? $this->sqltype_typeno[$typedescription] : 99 );

		return $r;
	}

	/**
	 * @see QueryWriter::widenColumn
	 */
	public function widenColumn( $type, $column, $datatype )
	{
		$t = $this->getTable( $type );

		$t['columns'][$column] = $this->typeno_sqltype[$datatype];

		$this->putTable( $t );
	}

	/**
	 * @see QueryWriter::getTables();
	 */
	public function getTables()
	{
		return $this->adapter->getCol( "SELECT name FROM sqlite_master
			WHERE type='table' AND name!='sqlite_sequence';" );
	}

	/**
	 * @see QueryWriter::createTable
	 */
	public function createTable( $table )
	{
		$table = $this->esc( $table );

		$sql   = "CREATE TABLE $table ( id INTEGER PRIMARY KEY AUTOINCREMENT ) ";

		$this->adapter->exec( $sql );
	}

	/**
	 * @see QueryWriter::getColumns
	 */
	public function getColumns( $table )
	{
		$table      = $this->esc( $table, TRUE );

		$columnsRaw = $this->adapter->get( "PRAGMA table_info('$table')" );

		$columns    = array();
		foreach ( $columnsRaw as $r ) $columns[$r['name']] = $r['type'];

		return $columns;
	}

	/**
	 * @see QueryWriter::addUniqueIndex
	 */
	public function addUniqueConstraint( $type, $properties )
	{
		$tableNoQ = $this->esc( $type, TRUE );
		$name  = 'UQ_' . $this->esc( $type, TRUE ) . implode( '__', $properties );
		$t     = $this->getTable( $type );
		$t['indexes'][$name] = array( 'name' => $name );
		try {
			$this->putTable( $t );
		} catch( SQLException $e ) {
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * @see QueryWriter::sqlStateIn
	 */
	public function sqlStateIn( $state, $list )
	{
		$stateMap = array(
			'HY000' => QueryWriter::C_SQLSTATE_NO_SUCH_TABLE,
			'23000' => QueryWriter::C_SQLSTATE_INTEGRITY_CONSTRAINT_VIOLATION
		);

		return in_array( ( isset( $stateMap[$state] ) ? $stateMap[$state] : '0' ), $list );
	}

	/**
	 * @see QueryWriter::addIndex
	 */
	public function addIndex( $type, $name, $column )
	{
		$columns = $this->getColumns( $type );
		if ( !isset( $columns[$column] ) ) return FALSE;

		$table  = $this->esc( $type );
		$name   = preg_replace( '/\W/', '', $name );
		$column = $this->esc( $column, TRUE );

		try {
			$t = $this->getTable( $type );
			$t['indexes'][$name] = array( 'name' => $column );
			$this->putTable( $t );
			return TRUE;
		} catch( SQLException $exception ) {
			return FALSE;
		}
	}

	/**
	 * @see QueryWriter::wipe
	 */
	public function wipe( $type )
	{
		$table = $this->esc( $type );

		$this->adapter->exec( "DELETE FROM $table " );
	}

	/**
	 * @see QueryWriter::addFK
	 */
	public function addFK( $type, $targetType, $property, $targetProperty, $isDep = FALSE )
	{
		return $this->buildFK( $type, $targetType, $property, $targetProperty, $isDep );
	}

	/**
	 * @see QueryWriter::wipeAll
	 */
	public function wipeAll()
	{
		$this->adapter->exec( 'PRAGMA foreign_keys = 0 ' );

		foreach ( $this->getTables() as $t ) {
			try {
				$this->adapter->exec( "DROP TABLE IF EXISTS `$t`" );
			} catch ( SQLException $e ) {
			}

			try {
				$this->adapter->exec( "DROP TABLE IF EXISTS `$t`" );
			} catch ( SQLException $e ) {
			}
		}

		$this->adapter->exec( 'PRAGMA foreign_keys = 1 ' );
	}
}
}

namespace RedBeanPHP\QueryWriter {

use RedBeanPHP\QueryWriter\AQueryWriter as AQueryWriter;
use RedBeanPHP\QueryWriter as QueryWriter;
use RedBeanPHP\Adapter\DBAdapter as DBAdapter;
use RedBeanPHP\Adapter as Adapter;
use RedBeanPHP\RedException\SQL as SQLException;

/**
 * RedBeanPHP PostgreSQL Query Writer.
 * This is a QueryWriter class for RedBeanPHP.
 * This QueryWriter provides support for the PostgreSQL database platform.
 *
 * @file    RedBeanPHP/QueryWriter/PostgreSQL.php
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * @copyright
 * (c) copyright G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class PostgreSQL extends AQueryWriter implements QueryWriter
{
	/**
	 * Data types
	 */
	const C_DATATYPE_INTEGER          = 0;
	const C_DATATYPE_DOUBLE           = 1;
	const C_DATATYPE_TEXT             = 3;
	const C_DATATYPE_SPECIAL_DATE     = 80;
	const C_DATATYPE_SPECIAL_DATETIME = 81;
	const C_DATATYPE_SPECIAL_POINT    = 90;
	const C_DATATYPE_SPECIAL_LSEG     = 91;
	const C_DATATYPE_SPECIAL_CIRCLE   = 92;
	const C_DATATYPE_SPECIAL_MONEY    = 93;
	const C_DATATYPE_SPECIAL_POLYGON  = 94;
	const C_DATATYPE_SPECIAL_MONEY2   = 95; //Numbers only money, i.e. fixed point numeric
	const C_DATATYPE_SPECIAL_JSON     = 96; //JSON support (only manual)
	const C_DATATYPE_SPECIFIED        = 99;

	/**
	 * @var DBAdapter
	 */
	protected $adapter;

	/**
	 * @var string
	 */
	protected $quoteCharacter = '"';

	/**
	 * @var string
	 */
	protected $defaultValue = 'DEFAULT';

	/**
	 * Returns the insert suffix SQL Snippet
	 *
	 * @param string $table table
	 *
	 * @return  string $sql SQL Snippet
	 */
	protected function getInsertSuffix( $table )
	{
		return 'RETURNING id ';
	}

	/**
	 * @see AQueryWriter::getKeyMapForType
	 */
	protected function getKeyMapForType( $type )
	{
		$table = $this->esc( $type, TRUE );
		$keys = $this->adapter->get( '
			SELECT
			information_schema.key_column_usage.constraint_name AS "name",
			information_schema.key_column_usage.column_name AS "from",
			information_schema.constraint_table_usage.table_name AS "table",
			information_schema.constraint_column_usage.column_name AS "to",
			information_schema.referential_constraints.update_rule AS "on_update",
			information_schema.referential_constraints.delete_rule AS "on_delete"
				FROM information_schema.key_column_usage
			INNER JOIN information_schema.constraint_table_usage
				ON (
					information_schema.key_column_usage.constraint_name = information_schema.constraint_table_usage.constraint_name
					AND information_schema.key_column_usage.constraint_schema = information_schema.constraint_table_usage.constraint_schema
					AND information_schema.key_column_usage.constraint_catalog = information_schema.constraint_table_usage.constraint_catalog
				)
			INNER JOIN information_schema.constraint_column_usage
				ON (
					information_schema.key_column_usage.constraint_name = information_schema.constraint_column_usage.constraint_name
					AND information_schema.key_column_usage.constraint_schema = information_schema.constraint_column_usage.constraint_schema
					AND information_schema.key_column_usage.constraint_catalog = information_schema.constraint_column_usage.constraint_catalog
				)
			INNER JOIN information_schema.referential_constraints
				ON (
					information_schema.key_column_usage.constraint_name = information_schema.referential_constraints.constraint_name
					AND information_schema.key_column_usage.constraint_schema = information_schema.referential_constraints.constraint_schema
					AND information_schema.key_column_usage.constraint_catalog = information_schema.referential_constraints.constraint_catalog
				)
			WHERE
				information_schema.key_column_usage.table_catalog = current_database()
				AND information_schema.key_column_usage.table_schema = ANY( current_schemas( FALSE ) )
				AND information_schema.key_column_usage.table_name = ?
		', array( $type ) );
		$keyInfoList = array();
		foreach ( $keys as $k ) {
			$label = $this->makeFKLabel( $k['from'], $k['table'], $k['to'] );
			$keyInfoList[$label] = array(
				'name'          => $k['name'],
				'from'          => $k['from'],
				'table'         => $k['table'],
				'to'            => $k['to'],
				'on_update'     => $k['on_update'],
				'on_delete'     => $k['on_delete']
			);
		}
		return $keyInfoList;
	}

	/**
	 * Constructor
	 *
	 * @param Adapter $adapter Database Adapter
	 */
	public function __construct( Adapter $adapter )
	{
		$this->typeno_sqltype = array(
			self::C_DATATYPE_INTEGER          => ' integer ',
			self::C_DATATYPE_DOUBLE           => ' double precision ',
			self::C_DATATYPE_TEXT             => ' text ',
			self::C_DATATYPE_SPECIAL_DATE     => ' date ',
			self::C_DATATYPE_SPECIAL_DATETIME => ' timestamp without time zone ',
			self::C_DATATYPE_SPECIAL_POINT    => ' point ',
			self::C_DATATYPE_SPECIAL_LSEG     => ' lseg ',
			self::C_DATATYPE_SPECIAL_CIRCLE   => ' circle ',
			self::C_DATATYPE_SPECIAL_MONEY    => ' money ',
			self::C_DATATYPE_SPECIAL_MONEY2   => ' numeric(10,2) ',
			self::C_DATATYPE_SPECIAL_POLYGON  => ' polygon ',
			self::C_DATATYPE_SPECIAL_JSON     => ' json ',
		);

		$this->sqltype_typeno = array();

		foreach ( $this->typeno_sqltype as $k => $v ) {
			$this->sqltype_typeno[trim( strtolower( $v ) )] = $k;
		}

		$this->adapter = $adapter;
	}

	/**
	 * This method returns the datatype to be used for primary key IDS and
	 * foreign keys. Returns one if the data type constants.
	 *
	 * @return integer
	 */
	public function getTypeForID()
	{
		return self::C_DATATYPE_INTEGER;
	}

	/**
	 * @see QueryWriter::getTables
	 */
	public function getTables()
	{
		return $this->adapter->getCol( 'SELECT table_name FROM information_schema.tables WHERE table_schema = ANY( current_schemas( FALSE ) )' );
	}

	/**
	 * @see QueryWriter::createTable
	 */
	public function createTable( $table )
	{
		$table = $this->esc( $table );

		$this->adapter->exec( " CREATE TABLE $table (id SERIAL PRIMARY KEY); " );
	}

	/**
	 * @see QueryWriter::getColumns
	 */
	public function getColumns( $table )
	{
		$table      = $this->esc( $table, TRUE );

		$columnsRaw = $this->adapter->get( "SELECT column_name, data_type FROM information_schema.columns WHERE table_name='$table' AND table_schema = ANY( current_schemas( FALSE ) )" );

		$columns = array();
		foreach ( $columnsRaw as $r ) {
			$columns[$r['column_name']] = $r['data_type'];
		}

		return $columns;
	}

	/**
	 * @see QueryWriter::scanType
	 */
	public function scanType( $value, $flagSpecial = FALSE )
	{
		$this->svalue = $value;

		if ( $value === INF ) return self::C_DATATYPE_TEXT;

		if ( $flagSpecial && $value ) {
			if ( preg_match( '/^\d{4}\-\d\d-\d\d$/', $value ) ) {
				return PostgreSQL::C_DATATYPE_SPECIAL_DATE;
			}

			if ( preg_match( '/^\d{4}\-\d\d-\d\d\s\d\d:\d\d:\d\d(\.\d{1,6})?$/', $value ) ) {
				return PostgreSQL::C_DATATYPE_SPECIAL_DATETIME;
			}

			if ( preg_match( '/^\([\d\.]+,[\d\.]+\)$/', $value ) ) {
				return PostgreSQL::C_DATATYPE_SPECIAL_POINT;
			}

			if ( preg_match( '/^\[\([\d\.]+,[\d\.]+\),\([\d\.]+,[\d\.]+\)\]$/', $value ) ) {
				return PostgreSQL::C_DATATYPE_SPECIAL_LSEG;
			}

			if ( preg_match( '/^\<\([\d\.]+,[\d\.]+\),[\d\.]+\>$/', $value ) ) {
				return PostgreSQL::C_DATATYPE_SPECIAL_CIRCLE;
			}

			if ( preg_match( '/^\((\([\d\.]+,[\d\.]+\),?)+\)$/', $value ) ) {
				return PostgreSQL::C_DATATYPE_SPECIAL_POLYGON;
			}

			if ( preg_match( '/^\-?(\$|€|¥|£)[\d,\.]+$/', $value ) ) {
				return PostgreSQL::C_DATATYPE_SPECIAL_MONEY;
			}

			if ( preg_match( '/^-?\d+\.\d{2}$/', $value ) ) {
				return PostgreSQL::C_DATATYPE_SPECIAL_MONEY2;
			}
		}

		if ( is_float( $value ) ) return self::C_DATATYPE_DOUBLE;

		if ( $this->startsWithZeros( $value ) ) return self::C_DATATYPE_TEXT;

		if ( $value === FALSE || $value === TRUE || $value === NULL || ( is_numeric( $value )
				&& AQueryWriter::canBeTreatedAsInt( $value )
				&& $value < 2147483648
				&& $value > -2147483648 )
		) {
			return self::C_DATATYPE_INTEGER;
		} elseif ( is_numeric( $value ) ) {
			return self::C_DATATYPE_DOUBLE;
		} else {
			return self::C_DATATYPE_TEXT;
		}
	}

	/**
	 * @see QueryWriter::code
	 */
	public function code( $typedescription, $includeSpecials = FALSE )
	{
		$r = ( isset( $this->sqltype_typeno[$typedescription] ) ) ? $this->sqltype_typeno[$typedescription] : 99;

		if ( $includeSpecials ) return $r;

		if ( $r >= QueryWriter::C_DATATYPE_RANGE_SPECIAL ) {
			return self::C_DATATYPE_SPECIFIED;
		}

		return $r;
	}

	/**
	 * @see QueryWriter::widenColumn
	 */
	public function widenColumn( $type, $column, $datatype )
	{
		$table   = $type;
		$type    = $datatype;

		$table   = $this->esc( $table );
		$column  = $this->esc( $column );

		$newtype = $this->typeno_sqltype[$type];

		$this->adapter->exec( "ALTER TABLE $table \n\t ALTER COLUMN $column TYPE $newtype " );
	}

	/**
	 * @see QueryWriter::addUniqueIndex
	 */
	public function addUniqueConstraint( $type, $properties )
	{
		$tableNoQ = $this->esc( $type, TRUE );
		$columns = array();
		foreach( $properties as $key => $column ) $columns[$key] = $this->esc( $column );
		$table = $this->esc( $type );
		sort( $columns ); //else we get multiple indexes due to order-effects
		$name = "UQ_" . sha1( $table . implode( ',', $columns ) );
		$sql = "ALTER TABLE {$table}
                ADD CONSTRAINT $name UNIQUE (" . implode( ',', $columns ) . ")";
		try {
			$this->adapter->exec( $sql );
		} catch( SQLException $e ) {
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * @see QueryWriter::sqlStateIn
	 */
	public function sqlStateIn( $state, $list )
	{
		$stateMap = array(
			'42P01' => QueryWriter::C_SQLSTATE_NO_SUCH_TABLE,
			'42703' => QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN,
			'23505' => QueryWriter::C_SQLSTATE_INTEGRITY_CONSTRAINT_VIOLATION
		);

		return in_array( ( isset( $stateMap[$state] ) ? $stateMap[$state] : '0' ), $list );
	}

	/**
	 * @see QueryWriter::addIndex
	 */
	public function addIndex( $type, $name, $property )
	{
		$table  = $this->esc( $type );
		$name   = preg_replace( '/\W/', '', $name );
		$column = $this->esc( $property );

		try {
			$this->adapter->exec( "CREATE INDEX {$name} ON $table ({$column}) " );
			return TRUE;
		} catch ( SQLException $e ) {
			return FALSE;
		}
	}

	/**
	 * @see QueryWriter::addFK
	 */
	public function addFK( $type, $targetType, $property, $targetProperty, $isDep = FALSE )
	{
		$table = $this->esc( $type );
		$targetTable = $this->esc( $targetType );
		$field = $this->esc( $property );
		$targetField = $this->esc( $targetProperty );
		$tableNoQ = $this->esc( $type, TRUE );
		$fieldNoQ = $this->esc( $property, TRUE );
		if ( !is_null( $this->getForeignKeyForTypeProperty( $tableNoQ, $fieldNoQ ) ) ) return FALSE;
		try{
			$delRule = ( $isDep ? 'CASCADE' : 'SET NULL' );
			$this->adapter->exec( "ALTER TABLE {$table}
				ADD FOREIGN KEY ( {$field} ) REFERENCES  {$targetTable}
				({$targetField}) ON DELETE {$delRule} ON UPDATE {$delRule} DEFERRABLE ;" );
			return TRUE;
		} catch ( SQLException $e ) {
			return FALSE;
		}
	}

	/**
	 * @see QueryWriter::wipeAll
	 */
	public function wipeAll()
	{
		$this->adapter->exec( 'SET CONSTRAINTS ALL DEFERRED' );

		foreach ( $this->getTables() as $t ) {
			$t = $this->esc( $t );
			//Some plugins (PostGIS have unremovable tables/views), avoid exceptions.
			try { $this->adapter->exec( "DROP TABLE IF EXISTS $t CASCADE " ); }catch( \Exception $e ) {}
		}

		$this->adapter->exec( 'SET CONSTRAINTS ALL IMMEDIATE' );
	}
}
}

namespace RedBeanPHP {

/**
 * RedBean\Exception Base.
 * Represents the base class for RedBeanPHP\Exceptions.
 *
 * @file    RedBeanPHP/Exception.php
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedException extends \Exception
{
}
}

namespace RedBeanPHP\RedException {

use RedBeanPHP\RedException as RedException;

/**
 * SQL Exception.
 * Represents a generic database exception independent of the underlying driver.
 *
 * @file       RedBeanPHP/RedException/SQL.php
 * @author     Gabor de Mooij and the RedBeanPHP Community
 * @license    BSD/GPLv2
 *
 * @copyright
 * (c) copyright G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class SQL extends RedException
{
	/**
	 * @var string
	 */
	private $sqlState;

	/**
	 * Returns an ANSI-92 compliant SQL state.
	 *
	 * @return string
	 */
	public function getSQLState()
	{
		return $this->sqlState;
	}

	/**
	 * Returns the raw SQL STATE, possibly compliant with
	 * ANSI SQL error codes - but this depends on database driver.
	 *
	 * @param string $sqlState SQL state error code
	 *
	 * @return void
	 */
	public function setSQLState( $sqlState )
	{
		$this->sqlState = $sqlState;
	}

	/**
	 * To String prints both code and SQL state.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return '[' . $this->getSQLState() . '] - ' . $this->getMessage()."\n".
				'trace: ' . $this->getTraceAsString();
	}
}
}

namespace RedBeanPHP {

use RedBeanPHP\Adapter\DBAdapter as DBAdapter;
use RedBeanPHP\QueryWriter as QueryWriter;
use RedBeanPHP\BeanHelper as BeanHelper;
use RedBeanPHP\RedException\SQL as SQLException;
use RedBeanPHP\QueryWriter\AQueryWriter as AQueryWriter;
use RedBeanPHP\Cursor as Cursor;
use RedBeanPHP\Cursor\NullCursor as NullCursor;

/**
 * Abstract Repository.
 *
 * OODB manages two repositories, a fluid one that
 * adjust the database schema on-the-fly to accomodate for
 * new bean types (tables) and new properties (columns) and
 * a frozen one for use in a production environment. OODB
 * allows you to swap the repository instances using the freeze()
 * method.
 *
 * @file    RedBeanPHP/Repository.php
 * @author  Gabor de Mooij and the RedBeanPHP community
 * @license BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
abstract class Repository
{
	/**
	 * @var array
	 */
	protected $stash = NULL;

	/*
	 * @var integer
	 */
	protected $nesting = 0;

	/**
	 * @var DBAdapter
	 */
	protected $writer;

	/**
	 * Stores a bean and its lists in one run.
	 *
	 * @param OODBBean $bean bean to process
	 *
	 * @return void
	 */
	protected function storeBeanWithLists( OODBBean $bean )
	{
		$sharedAdditions = $sharedTrashcan = $sharedresidue = $sharedItems = $ownAdditions = $ownTrashcan = $ownresidue = $embeddedBeans = array(); //Define groups
		foreach ( $bean as $property => $value ) {
			$value = ( $value instanceof SimpleModel ) ? $value->unbox() : $value;
			if ( $value instanceof OODBBean ) {
				$this->processEmbeddedBean( $embeddedBeans, $bean, $property, $value );
				$bean->setMeta("sys.typeof.{$property}", $value->getMeta('type'));
			} elseif ( is_array( $value ) ) {
				foreach($value as &$item) {
					$item = ( $item instanceof SimpleModel ) ? $item->unbox() : $item;
				}
				$originals = $bean->moveMeta( 'sys.shadow.' . $property, array() );
				if ( strpos( $property, 'own' ) === 0 ) {
					list( $ownAdditions, $ownTrashcan, $ownresidue ) = $this->processGroups( $originals, $value, $ownAdditions, $ownTrashcan, $ownresidue );
					$listName = lcfirst( substr( $property, 3 ) );
					if ($bean->moveMeta( 'sys.exclusive-'.  $listName ) ) {
						OODBBean::setMetaAll( $ownTrashcan, 'sys.garbage', TRUE );
						OODBBean::setMetaAll( $ownAdditions, 'sys.buildcommand.fkdependson', $bean->getMeta( 'type' ) );
					}
					unset( $bean->$property );
				} elseif ( strpos( $property, 'shared' ) === 0 ) {
					list( $sharedAdditions, $sharedTrashcan, $sharedresidue ) = $this->processGroups( $originals, $value, $sharedAdditions, $sharedTrashcan, $sharedresidue );
					unset( $bean->$property );
				}
			}
		}
		$this->storeBean( $bean );
		$this->processTrashcan( $bean, $ownTrashcan );
		$this->processAdditions( $bean, $ownAdditions );
		$this->processResidue( $ownresidue );
		$this->processSharedTrashcan( $bean, $sharedTrashcan );
		$this->processSharedAdditions( $bean, $sharedAdditions );
		$this->processSharedResidue( $bean, $sharedresidue );
	}

	/**
	 * Process groups. Internal function. Processes different kind of groups for
	 * storage function. Given a list of original beans and a list of current beans,
	 * this function calculates which beans remain in the list (residue), which
	 * have been deleted (are in the trashcan) and which beans have been added
	 * (additions).
	 *
	 * @param  array $originals originals
	 * @param  array $current   the current beans
	 * @param  array $additions beans that have been added
	 * @param  array $trashcan  beans that have been deleted
	 * @param  array $residue   beans that have been left untouched
	 *
	 * @return array
	 */
	protected function processGroups( $originals, $current, $additions, $trashcan, $residue )
	{
		return array(
			array_merge( $additions, array_diff( $current, $originals ) ),
			array_merge( $trashcan, array_diff( $originals, $current ) ),
			array_merge( $residue, array_intersect( $current, $originals ) )
		);
	}

	/**
	 * Processes an embedded bean.
	 *
	 * @param OODBBean|SimpleModel $embeddedBean the bean or model
	 *
	 * @return integer
	 */
	protected function prepareEmbeddedBean( $embeddedBean )
	{
		if ( !$embeddedBean->id || $embeddedBean->getMeta( 'tainted' ) ) {
			$this->store( $embeddedBean );
		}

		return $embeddedBean->id;
	}

	/**
	 * Processes a list of beans from a bean. A bean may contain lists. This
	 * method handles shared addition lists; i.e. the $bean->sharedObject properties.
	 *
	 * @param OODBBean $bean             the bean
	 * @param array            $sharedAdditions  list with shared additions
	 *
	 * @return void
	 */
	protected function processSharedAdditions( $bean, $sharedAdditions )
	{
		foreach ( $sharedAdditions as $addition ) {
			if ( $addition instanceof OODBBean ) {
				$this->oodb->getAssociationManager()->associate( $addition, $bean );
			} else {
				throw new RedException( 'Array may only contain OODBBeans' );
			}
		}
	}

	/**
	 * Processes a list of beans from a bean. A bean may contain lists. This
	 * method handles own lists; i.e. the $bean->ownObject properties.
	 * A residue is a bean in an own-list that stays where it is. This method
	 * checks if there have been any modification to this bean, in that case
	 * the bean is stored once again, otherwise the bean will be left untouched.
	 *
	 * @param OODBBean $bean       bean tor process
	 * @param array    $ownresidue list to process
	 *
	 * @return void
	 */
	protected function processResidue( $ownresidue )
	{
		foreach ( $ownresidue as $residue ) {
			if ( $residue->getMeta( 'tainted' ) ) {
				$this->store( $residue );
			}
		}
	}

	/**
	 * Processes a list of beans from a bean. A bean may contain lists. This
	 * method handles own lists; i.e. the $bean->ownObject properties.
	 * A trash can bean is a bean in an own-list that has been removed
	 * (when checked with the shadow). This method
	 * checks if the bean is also in the dependency list. If it is the bean will be removed.
	 * If not, the connection between the bean and the owner bean will be broken by
	 * setting the ID to NULL.
	 *
	 * @param OODBBean $bean bean   to process
	 * @param array    $ownTrashcan list to process
	 *
	 * @return void
	 */
	protected function processTrashcan( $bean, $ownTrashcan )
	{
		foreach ( $ownTrashcan as $trash ) {

			$myFieldLink = $bean->getMeta( 'type' ) . '_id';
			$alias = $bean->getMeta( 'sys.alias.' . $trash->getMeta( 'type' ) );
			if ( $alias ) $myFieldLink = $alias . '_id';

			if ( $trash->getMeta( 'sys.garbage' ) === true ) {
				$this->trash( $trash );
			} else {
				$trash->$myFieldLink = NULL;
				$this->store( $trash );
			}
		}
	}

	/**
	 * Unassociates the list items in the trashcan.
	 *
	 * @param OODBBean $bean           bean to process
	 * @param array    $sharedTrashcan list to process
	 *
	 * @return void
	 */
	protected function processSharedTrashcan( $bean, $sharedTrashcan )
	{
		foreach ( $sharedTrashcan as $trash ) {
			$this->oodb->getAssociationManager()->unassociate( $trash, $bean );
		}
	}

	/**
	 * Stores all the beans in the residue group.
	 *
	 * @param OODBBean $bean          bean to process
	 * @param array    $sharedresidue list to process
	 *
	 * @return void
	 */
	protected function processSharedResidue( $bean, $sharedresidue )
	{
		foreach ( $sharedresidue as $residue ) {
			$this->store( $residue );
		}
	}

	/**
	 * Determines whether the bean has 'loaded lists' or
	 * 'loaded embedded beans' that need to be processed
	 * by the store() method.
	 *
	 * @param OODBBean $bean bean to be examined
	 *
	 * @return boolean
	 */
	protected function hasListsOrObjects( OODBBean $bean )
	{
		$processLists = FALSE;
		foreach ( $bean as $value ) {
			if ( is_array( $value ) || is_object( $value ) ) {
				$processLists = TRUE;
				break;
			}
		}

		return $processLists;
	}

	/**
	 * Converts an embedded bean to an ID, removed the bean property and
	 * stores the bean in the embedded beans array.
	 *
	 * @param array    $embeddedBeans destination array for embedded bean
	 * @param OODBBean $bean          target bean to process
	 * @param string   $property      property that contains the embedded bean
	 * @param OODBBean $value         embedded bean itself
	 *
	 * @return void
	 */
	protected function processEmbeddedBean( &$embeddedBeans, $bean, $property, OODBBean $value )
	{
		$linkField = $property . '_id';
		$id = $this->prepareEmbeddedBean( $value );
		if ($bean->$linkField != $id) $bean->$linkField = $id;
		$bean->setMeta( 'cast.' . $linkField, 'id' );
		$embeddedBeans[$linkField] = $value;
		unset( $bean->$property );
	}

	/**
	 * Constructor, requires a query writer.
	 * Creates a new instance of the bean respository class.
	 *
	 * @param QueryWriter $writer the Query Writer to use for this repository
	 *
	 * @return void
	 */
	public function __construct( OODB $oodb, QueryWriter $writer )
	{
		$this->writer = $writer;
		$this->oodb = $oodb;
	}

	/**
	 * Checks whether a OODBBean bean is valid.
	 * If the type is not valid or the ID is not valid it will
	 * throw an exception: Security.
	 *
	 * @param OODBBean $bean the bean that needs to be checked
	 *
	 * @return void
	 */
	public function check( OODBBean $bean )
	{
		//Is all meta information present?
		if ( !isset( $bean->id ) ) {
			throw new RedException( 'Bean has incomplete Meta Information id ' );
		}
		if ( !( $bean->getMeta( 'type' ) ) ) {
			throw new RedException( 'Bean has incomplete Meta Information II' );
		}
		//Pattern of allowed characters
		$pattern = '/[^a-z0-9_]/i';
		//Does the type contain invalid characters?
		if ( preg_match( $pattern, $bean->getMeta( 'type' ) ) ) {
			throw new RedException( 'Bean Type is invalid' );
		}
		//Are the properties and values valid?
		foreach ( $bean as $prop => $value ) {
			if (
				is_array( $value )
				|| ( is_object( $value ) )
			) {
				throw new RedException( "Invalid Bean value: property $prop" );
			} else if (
				strlen( $prop ) < 1
				|| preg_match( $pattern, $prop )
			) {
				throw new RedException( "Invalid Bean property: property $prop" );
			}
		}
	}

	/**
	 * Searches the database for a bean that matches conditions $conditions and sql $addSQL
	 * and returns an array containing all the beans that have been found.
	 *
	 * Conditions need to take form:
	 *
	 * <code>
	 * array(
	 *    'PROPERTY' => array( POSSIBLE VALUES... 'John', 'Steve' )
	 *    'PROPERTY' => array( POSSIBLE VALUES... )
	 * );
	 * </code>
	 *
	 * All conditions are glued together using the AND-operator, while all value lists
	 * are glued using IN-operators thus acting as OR-conditions.
	 *
	 * Note that you can use property names; the columns will be extracted using the
	 * appropriate bean formatter.
	 *
	 * @param string $type       type of beans you are looking for
	 * @param array  $conditions list of conditions
	 * @param string $addSQL     SQL to be used in query
	 * @param array  $bindings   whether you prefer to use a WHERE clause or not (TRUE = not)
	 *
	 * @return array
	 */
	public function find( $type, $conditions = array(), $sql = NULL, $bindings = array() )
	{
		//for backward compatibility, allow mismatch arguments:
		if ( is_array( $sql ) ) {
			if ( isset( $sql[1] ) ) {
				$bindings = $sql[1];
			}
			$sql = $sql[0];
		}
		try {
			$beans = $this->convertToBeans( $type, $this->writer->queryRecord( $type, $conditions, $sql, $bindings ) );

			return $beans;
		} catch ( SQLException $exception ) {
			$this->handleException( $exception );
		}

		return array();
	}

	/**
	 * Finds a BeanCollection.
	 *
	 * @param string $type     type of beans you are looking for
	 * @param string $sql      SQL to be used in query
	 * @param array  $bindings whether you prefer to use a WHERE clause or not (TRUE = not)
	 *
	 * @return BeanCollection
	 */
	public function findCollection( $type, $sql, $bindings = array() )
	{
		try {
			$cursor = $this->writer->queryRecordWithCursor( $type, $sql, $bindings );
			return new BeanCollection( $type, $this, $cursor );
		} catch ( SQLException $exception ) {
			$this->handleException( $exception );
		}
		return new BeanCollection( $type, $this, new NullCursor );
	}

	/**
	 * Stores a bean in the database. This method takes a
	 * OODBBean Bean Object $bean and stores it
	 * in the database. If the database schema is not compatible
	 * with this bean and RedBean runs in fluid mode the schema
	 * will be altered to store the bean correctly.
	 * If the database schema is not compatible with this bean and
	 * RedBean runs in frozen mode it will throw an exception.
	 * This function returns the primary key ID of the inserted
	 * bean.
	 *
	 * The return value is an integer if possible. If it is not possible to
	 * represent the value as an integer a string will be returned. We use
	 * explicit casts instead of functions to preserve performance
	 * (0.13 vs 0.28 for 10000 iterations on Core i3).
	 *
	 * @param OODBBean|SimpleModel $bean bean to store
	 *
	 * @return integer|string
	 */
	public function store( $bean )
	{
		$processLists = $this->hasListsOrObjects( $bean );
		if ( !$processLists && !$bean->getMeta( 'tainted' ) ) {
			return $bean->getID(); //bail out!
		}
		$this->oodb->signal( 'update', $bean );
		$processLists = $this->hasListsOrObjects( $bean ); //check again, might have changed by model!
		if ( $processLists ) {
			$this->storeBeanWithLists( $bean );
		} else {
			$this->storeBean( $bean );
		}
		$this->oodb->signal( 'after_update', $bean );

		return ( (string) $bean->id === (string) (int) $bean->id ) ? (int) $bean->id : (string) $bean->id;
	}

	/**
	 * Returns an array of beans. Pass a type and a series of ids and
	 * this method will bring you the corresponding beans.
	 *
	 * important note: Because this method loads beans using the load()
	 * function (but faster) it will return empty beans with ID 0 for
	 * every bean that could not be located. The resulting beans will have the
	 * passed IDs as their keys.
	 *
	 * @param string $type type of beans
	 * @param array  $ids  ids to load
	 *
	 * @return array
	 */
	public function batch( $type, $ids )
	{
		if ( !$ids ) {
			return array();
		}
		$collection = array();
		try {
			$rows = $this->writer->queryRecord( $type, array( 'id' => $ids ) );
		} catch ( SQLException $e ) {
			$this->handleException( $e );
			$rows = FALSE;
		}
		$this->stash[$this->nesting] = array();
		if ( !$rows ) {
			return array();
		}
		foreach ( $rows as $row ) {
			$this->stash[$this->nesting][$row['id']] = $row;
		}
		foreach ( $ids as $id ) {
			$collection[$id] = $this->load( $type, $id );
		}
		$this->stash[$this->nesting] = NULL;

		return $collection;
	}

	/**
	 * This is a convenience method; it converts database rows
	 * (arrays) into beans. Given a type and a set of rows this method
	 * will return an array of beans of the specified type loaded with
	 * the data fields provided by the result set from the database.
	 *
	 * New in 4.3.2: meta mask. The meta mask is a special mask to send
	 * data from raw result rows to the meta store of the bean. This is
	 * useful for bundling additional information with custom queries.
	 * Values of every column whos name starts with $mask will be
	 * transferred to the meta section of the bean under key 'data.bundle'.
	 *
	 * @param string $type type of beans you would like to have
	 * @param array  $rows rows from the database result
	 * @param string $mask meta mask to apply (optional)
	 *
	 * @return array
	 */
	public function convertToBeans( $type, $rows, $mask = NULL )
	{
		$masklen = 0;
		if ( $mask !== NULL ) $masklen = mb_strlen( $mask );

		$collection                  = array();
		$this->stash[$this->nesting] = array();
		foreach ( $rows as $row ) {
			$meta = array();
			if ( !is_null( $mask ) ) {
				foreach( $row as $key => $value ) {
					if ( strpos( $key, $mask ) === 0 ) {
						unset( $row[$key] );
						$meta[$key] = $value;
					}
				}
			}

			$id                               = $row['id'];
			$this->stash[$this->nesting][$id] = $row;
			$collection[$id]                  = $this->load( $type, $id );

			if ( $mask !== NULL ) {
				$collection[$id]->setMeta( 'data.bundle', $meta );
			}
		}
		$this->stash[$this->nesting] = NULL;

		return $collection;
	}

	/**
	 * Counts the number of beans of type $type.
	 * This method accepts a second argument to modify the count-query.
	 * A third argument can be used to provide bindings for the SQL snippet.
	 *
	 * @param string $type     type of bean we are looking for
	 * @param string $addSQL   additional SQL snippet
	 * @param array  $bindings parameters to bind to SQL
	 *
	 * @return integer
	 */
	public function count( $type, $addSQL = '', $bindings = array() )
	{
		$type = AQueryWriter::camelsSnake( $type );
		if ( count( explode( '_', $type ) ) > 2 ) {
			throw new RedException( 'Invalid type for count.' );
		}

		try {
			return (int) $this->writer->queryRecordCount( $type, array(), $addSQL, $bindings );
		} catch ( SQLException $exception ) {
			if ( !$this->writer->sqlStateIn( $exception->getSQLState(), array(
				 QueryWriter::C_SQLSTATE_NO_SUCH_TABLE,
				 QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN ) ) ) {
				throw $exception;
			}
		}

		return 0;
	}

	/**
	 * Removes a bean from the database.
	 * This function will remove the specified OODBBean
	 * Bean Object from the database.
	 *
	 * @param OODBBean|SimpleModel $bean bean you want to remove from database
	 *
	 * @return void
	 */
	public function trash( $bean )
	{
		$this->oodb->signal( 'delete', $bean );
		foreach ( $bean as $property => $value ) {
			if ( $value instanceof OODBBean ) {
				unset( $bean->$property );
			}
			if ( is_array( $value ) ) {
				if ( strpos( $property, 'own' ) === 0 ) {
					unset( $bean->$property );
				} elseif ( strpos( $property, 'shared' ) === 0 ) {
					unset( $bean->$property );
				}
			}
		}
		try {
			$this->writer->deleteRecord( $bean->getMeta( 'type' ), array( 'id' => array( $bean->id ) ), NULL );
		} catch ( SQLException $exception ) {
			$this->handleException( $exception );
		}
		$bean->id = 0;
		$this->oodb->signal( 'after_delete', $bean );
	}

	/**
	 * Checks whether the specified table already exists in the database.
	 * Not part of the Object Database interface!
	 *
	 * @deprecated Use AQueryWriter::typeExists() instead.
	 *
	 * @param string $table table name
	 *
	 * @return boolean
	 */
	public function tableExists( $table )
	{
		return $this->writer->tableExists( $table );
	}

	/**
	 * Trash all beans of a given type. Wipes an entire type of bean.
	 *
	 * @param string $type type of bean you wish to delete all instances of
	 *
	 * @return boolean
	 */
	public function wipe( $type )
	{
		try {
			$this->writer->wipe( $type );

			return TRUE;
		} catch ( SQLException $exception ) {
			if ( !$this->writer->sqlStateIn( $exception->getSQLState(), array( QueryWriter::C_SQLSTATE_NO_SUCH_TABLE ) ) ) {
				throw $exception;
			}

			return FALSE;
		}
	}
}
}

namespace RedBeanPHP\Repository {

use RedBeanPHP\OODBBean as OODBBean;
use RedBeanPHP\QueryWriter as QueryWriter;
use RedBeanPHP\RedException as RedException;
use RedBeanPHP\BeanHelper as BeanHelper;
use RedBeanPHP\RedException\SQL as SQLException;
use RedBeanPHP\Repository as Repository;

/**
 * Fluid Repository.
 * OODB manages two repositories, a fluid one that
 * adjust the database schema on-the-fly to accomodate for
 * new bean types (tables) and new properties (columns) and
 * a frozen one for use in a production environment. OODB
 * allows you to swap the repository instances using the freeze()
 * method.
 *
 * @file    RedBeanPHP/Repository/Fluid.php
 * @author  Gabor de Mooij and the RedBeanPHP community
 * @license BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Fluid extends Repository
{
	/**
	 * Figures out the desired type given the cast string ID.
	 * Given a cast ID, this method will return the associated
	 * type (INT(10) or VARCHAR for instance). The returned type
	 * can be processed by the Query Writer to build the specified
	 * column for you in the database. The Cast ID is actually just
	 * a superset of the QueryWriter types. In addition to default
	 * Query Writer column types you can pass the following 'cast types':
	 * 'id' and 'string'. These will map to Query Writer specific
	 * column types (probably INT and VARCHAR).
	 *
	 * @param string $cast cast identifier
	 *
	 * @return integer
	 */
	private function getTypeFromCast( $cast )
	{
		if ( $cast == 'string' ) {
			$typeno = $this->writer->scanType( 'STRING' );
		} elseif ( $cast == 'id' ) {
			$typeno = $this->writer->getTypeForID();
		} elseif ( isset( $this->writer->sqltype_typeno[$cast] ) ) {
			$typeno = $this->writer->sqltype_typeno[$cast];
		} else {
			throw new RedException( 'Invalid Cast' );
		}

		return $typeno;
	}

	/**
	 * Orders the Query Writer to create a table if it does not exist already and
	 * adds a note in the build report about the creation.
	 *
	 * @param OODBBean $bean bean to update report of
	 * @param string         $table table to check and create if not exists
	 *
	 * @return void
	 */
	private function createTableIfNotExists( OODBBean $bean, $table )
	{
		//Does table exist? If not, create
		if ( !$this->tableExists( $this->writer->esc( $table, TRUE ) ) ) {
			$this->writer->createTable( $table );
			$bean->setMeta( 'buildreport.flags.created', TRUE );
		}
	}

	/**
	 * Modifies the table to fit the bean data.
	 * Given a property and a value and the bean, this method will
	 * adjust the table structure to fit the requirements of the property and value.
	 * This may include adding a new column or widening an existing column to hold a larger
	 * or different kind of value. This method employs the writer to adjust the table
	 * structure in the database. Schema updates are recorded in meta properties of the bean.
	 *
	 * This method will also apply indexes, unique constraints and foreign keys.
	 *
	 * @param OODBBean $bean     bean to get cast data from and store meta in
	 * @param string   $property property to store
	 * @param mixed    $value    value to store
	 *
	 * @return void
	 */
	private function modifySchema( OODBBean $bean, $property, $value )
	{
		$doFKStuff = FALSE;
		$table   = $bean->getMeta( 'type' );
		$columns = $this->writer->getColumns( $table );
		$columnNoQ = $this->writer->esc( $property, TRUE );
		if ( !$this->oodb->isChilled( $bean->getMeta( 'type' ) ) ) {
			if ( $bean->getMeta( "cast.$property", -1 ) !== -1 ) { //check for explicitly specified types
				$cast   = $bean->getMeta( "cast.$property" );
				$typeno = $this->getTypeFromCast( $cast );
			} else {
				$cast   = FALSE;
				$typeno = $this->writer->scanType( $value, TRUE );
			}
			if ( isset( $columns[$this->writer->esc( $property, TRUE )] ) ) { //Is this property represented in the table ?
				if ( !$cast ) { //rescan without taking into account special types >80
					$typeno = $this->writer->scanType( $value, FALSE );
				}
				$sqlt = $this->writer->code( $columns[$this->writer->esc( $property, TRUE )] );
				if ( $typeno > $sqlt ) { //no, we have to widen the database column type
					$this->writer->widenColumn( $table, $property, $typeno );
					$bean->setMeta( 'buildreport.flags.widen', TRUE );
					$doFKStuff = TRUE;
				}
			} else {
				$this->writer->addColumn( $table, $property, $typeno );
				$bean->setMeta( 'buildreport.flags.addcolumn', TRUE );
				$doFKStuff = TRUE;
			}
			if ($doFKStuff) {
				if (strrpos($columnNoQ, '_id')===(strlen($columnNoQ)-3)) {
					$destinationColumnNoQ = substr($columnNoQ, 0, strlen($columnNoQ)-3);
					$indexName = "index_foreignkey_{$table}_{$destinationColumnNoQ}";
					$this->writer->addIndex($table, $indexName, $columnNoQ);
					$typeof = $bean->getMeta("sys.typeof.{$destinationColumnNoQ}", $destinationColumnNoQ);
					$isLink = $bean->getMeta( 'sys.buildcommand.unique', FALSE );
					//Make FK CASCADING if part of exclusive list (dependson=typeof) or if link bean
					$isDep = ( $bean->moveMeta( 'sys.buildcommand.fkdependson' ) === $typeof || is_array( $isLink ) );
					$result = $this->writer->addFK( $table, $typeof, $columnNoQ, 'id', $isDep );
					//If this is a link bean and all unique columns have been added already, then apply unique constraint
					if ( is_array( $isLink ) && !count( array_diff( $isLink, array_keys( $this->writer->getColumns( $table ) ) ) ) ) {
						$this->writer->addUniqueConstraint( $table, $bean->moveMeta('sys.buildcommand.unique') );
						$bean->setMeta("sys.typeof.{$destinationColumnNoQ}", NULL);
					}
				}
			}
		}
	}

	/**
	 * Part of the store() functionality.
	 * Handles all new additions after the bean has been saved.
	 * Stores addition bean in own-list, extracts the id and
	 * adds a foreign key. Also adds a constraint in case the type is
	 * in the dependent list.
	 *
	 * Note that this method raises a custom exception if the bean
	 * is not an instance of OODBBean. Therefore it does not use
	 * a type hint. This allows the user to take action in case
	 * invalid objects are passed in the list.
	 *
	 * @param OODBBean $bean         bean to process
	 * @param array    $ownAdditions list of addition beans in own-list
	 *
	 * @return void
	 */
	protected function processAdditions( $bean, $ownAdditions )
	{
		$beanType = $bean->getMeta( 'type' );

		foreach ( $ownAdditions as $addition ) {
			if ( $addition instanceof OODBBean ) {

				$myFieldLink = $beanType . '_id';
				$alias = $bean->getMeta( 'sys.alias.' . $addition->getMeta( 'type' ) );
				if ( $alias ) $myFieldLink = $alias . '_id';

				$addition->$myFieldLink = $bean->id;
				$addition->setMeta( 'cast.' . $myFieldLink, 'id' );

				if ($alias) {
					$addition->setMeta( "sys.typeof.{$alias}", $beanType );
				} else {
					$addition->setMeta( "sys.typeof.{$beanType}", $beanType );
				}

				$this->store( $addition );
			} else {
				throw new RedException( 'Array may only contain OODBBeans' );
			}
		}
	}

	/**
	 * Stores a cleaned bean; i.e. only scalar values. This is the core of the store()
	 * method. When all lists and embedded beans (parent objects) have been processed and
	 * removed from the original bean the bean is passed to this method to be stored
	 * in the database.
	 *
	 * @param OODBBean $bean the clean bean
	 *
	 * @return void
	 */
	protected function storeBean( OODBBean $bean )
	{
		if ( $bean->getMeta( 'changed' ) ) {
			$this->check( $bean );
			$table = $bean->getMeta( 'type' );
			$this->createTableIfNotExists( $bean, $table );

			$updateValues = array();
			foreach ( $bean as $property => $value ) {
				if ( $property !== 'id' ) {
					$this->modifySchema( $bean, $property, $value );
				}
				if ( $property !== 'id' ) {
					$updateValues[] = array( 'property' => $property, 'value' => $value );
				}
			}

			$bean->id = $this->writer->updateRecord( $table, $updateValues, $bean->id );
			$bean->setMeta( 'changed', FALSE );
		}
		$bean->setMeta( 'tainted', FALSE );
	}

	/**
	 * Handles exceptions. Suppresses exceptions caused by missing structures.
	 *
	 * @param Exception $exception exception
	 *
	 * @return void
	 */
	protected function handleException( \Exception $exception )
	{
		if ( !$this->writer->sqlStateIn( $exception->getSQLState(),
			array(
				QueryWriter::C_SQLSTATE_NO_SUCH_TABLE,
				QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN ) )
		) {
			throw $exception;
		}
	}

	/**
	 * Dispenses a new bean (a OODBBean Bean Object)
	 * of the specified type. Always
	 * use this function to get an empty bean object. Never
	 * instantiate a OODBBean yourself because it needs
	 * to be configured before you can use it with RedBean. This
	 * function applies the appropriate initialization /
	 * configuration for you.
	 *
	 * @param string  $type              type of bean you want to dispense
	 * @param string  $number            number of beans you would like to get
	 * @param boolean $alwaysReturnArray if TRUE always returns the result as an array
	 *
	 * @return OODBBean
	 */
	public function dispense( $type, $number = 1, $alwaysReturnArray = FALSE )
	{
		$OODBBEAN = defined( 'REDBEAN_OODBBEAN_CLASS' ) ? REDBEAN_OODBBEAN_CLASS : '\RedBeanPHP\OODBBean';
		$beans = array();
		for ( $i = 0; $i < $number; $i++ ) {
			$bean = new $OODBBEAN;
			$bean->initializeForDispense( $type, $this->oodb->getBeanHelper() );
			$this->check( $bean );
			$this->oodb->signal( 'dispense', $bean );
			$beans[] = $bean;
		}

		return ( count( $beans ) === 1 && !$alwaysReturnArray ) ? array_pop( $beans ) : $beans;
	}

	/**
	 * Loads a bean from the object database.
	 * It searches for a OODBBean Bean Object in the
	 * database. It does not matter how this bean has been stored.
	 * RedBean uses the primary key ID $id and the string $type
	 * to find the bean. The $type specifies what kind of bean you
	 * are looking for; this is the same type as used with the
	 * dispense() function. If RedBean finds the bean it will return
	 * the OODB Bean object; if it cannot find the bean
	 * RedBean will return a new bean of type $type and with
	 * primary key ID 0. In the latter case it acts basically the
	 * same as dispense().
	 *
	 * Important note:
	 * If the bean cannot be found in the database a new bean of
	 * the specified type will be generated and returned.
	 *
	 * @param string  $type type of bean you want to load
	 * @param integer $id   ID of the bean you want to load
	 *
	 * @return OODBBean
	 */
	public function load( $type, $id )
	{
		$bean = $this->dispense( $type );
		if ( isset( $this->stash[$this->nesting][$id] ) ) {
			$row = $this->stash[$this->nesting][$id];
		} else {
			try {
				$rows = $this->writer->queryRecord( $type, array( 'id' => array( $id ) ) );
			} catch ( SQLException $exception ) {
				if ( $this->writer->sqlStateIn( $exception->getSQLState(),
					array(
						QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN,
						QueryWriter::C_SQLSTATE_NO_SUCH_TABLE )
				)
				) {
					$rows = 0;
				}
			}
			if ( empty( $rows ) ) {
				return $bean;
			}
			$row = array_pop( $rows );
		}
		$bean->importRow( $row );
		$this->nesting++;
		$this->oodb->signal( 'open', $bean );
		$this->nesting--;

		return $bean->setMeta( 'tainted', FALSE );
	}
}
}

namespace RedBeanPHP\Repository {

use RedBeanPHP\OODBBean as OODBBean;
use RedBeanPHP\QueryWriter as QueryWriter;
use RedBeanPHP\RedException as RedException;
use RedBeanPHP\BeanHelper as BeanHelper;
use RedBeanPHP\RedException\SQL as SQLException;
use RedBeanPHP\Repository as Repository;

/**
 * Frozen Repository.
 * OODB manages two repositories, a fluid one that
 * adjust the database schema on-the-fly to accomodate for
 * new bean types (tables) and new properties (columns) and
 * a frozen one for use in a production environment. OODB
 * allows you to swap the repository instances using the freeze()
 * method.
 *
 * @file    RedBeanPHP/Repository/Frozen.php
 * @author  Gabor de Mooij and the RedBeanPHP community
 * @license BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Frozen extends Repository
{
	/**
	 * Handles exceptions.
	 * In fluid mode, this suppresses exceptions caused by missing structures.
	 * However the implementation in frozen mode is rather the opposite, it
	 * will just re-throw every exception.
	 *
	 * @param \Exception $exception exception to handle
	 *
	 * @return void
	 */
	protected function handleException( \Exception $exception )
	{
		throw $exception;
	}

	/**
	 * Stores a cleaned bean; i.e. only scalar values. This is the core of the store()
	 * method. When all lists and embedded beans (parent objects) have been processed and
	 * removed from the original bean the bean is passed to this method to be stored
	 * in the database.
	 *
	 * @param OODBBean $bean the clean bean
	 *
	 * @return void
	 */
	protected function storeBean( OODBBean $bean )
	{
		if ( $bean->getMeta( 'changed' ) ) {

			list( $properties, $table ) = $bean->getPropertiesAndType();
			$id = $properties['id'];
			unset($properties['id']);
			$updateValues = array();
			$k1 = 'property';
			$k2 = 'value';
			foreach( $properties as $key => $value ) {
				$updateValues[] = array( $k1 => $key, $k2 => $value );
			}
			$bean->id = $this->writer->updateRecord( $table, $updateValues, $id );
			$bean->setMeta( 'changed', FALSE );
		}
		$bean->setMeta( 'tainted', FALSE );
	}

	/**
	 * Part of the store() functionality.
	 * Handles all new additions after the bean has been saved.
	 * Stores addition bean in own-list, extracts the id and
	 * adds a foreign key. Also adds a constraint in case the type is
	 * in the dependent list.
	 *
	 * Note that this method raises a custom exception if the bean
	 * is not an instance of OODBBean. Therefore it does not use
	 * a type hint. This allows the user to take action in case
	 * invalid objects are passed in the list.
	 *
	 * @param OODBBean $bean         bean to process
	 * @param array    $ownAdditions list of addition beans in own-list
	 *
	 * @return void
	 * @throws RedException
	 */
	protected function processAdditions( $bean, $ownAdditions )
	{
		$beanType = $bean->getMeta( 'type' );

		$cachedIndex = array();
		foreach ( $ownAdditions as $addition ) {
			if ( $addition instanceof OODBBean ) {

				$myFieldLink = $beanType . '_id';
				$alias = $bean->getMeta( 'sys.alias.' . $addition->getMeta( 'type' ) );
				if ( $alias ) $myFieldLink = $alias . '_id';

				$addition->$myFieldLink = $bean->id;
				$addition->setMeta( 'cast.' . $myFieldLink, 'id' );
				$this->store( $addition );

			} else {
				throw new RedException( 'Array may only contain OODBBeans' );
			}
		}
	}

	/**
	 * Dispenses a new bean (a OODBBean Bean Object)
	 * of the specified type. Always
	 * use this function to get an empty bean object. Never
	 * instantiate a OODBBean yourself because it needs
	 * to be configured before you can use it with RedBean. This
	 * function applies the appropriate initialization /
	 * configuration for you.
	 *
	 * @param string  $type              type of bean you want to dispense
	 * @param int  $number            number of beans you would like to get
	 * @param boolean $alwaysReturnArray if TRUE always returns the result as an array
	 *
	 * @return OODBBean
	 */
	public function dispense( $type, $number = 1, $alwaysReturnArray = FALSE )
	{
		$OODBBEAN = defined( 'REDBEAN_OODBBEAN_CLASS' ) ? REDBEAN_OODBBEAN_CLASS : '\RedBeanPHP\OODBBean';
		$beans = array();
		for ( $i = 0; $i < $number; $i++ ) {
			/** @var \RedBeanPHP\OODBBean $bean */
			$bean = new $OODBBEAN;
			$bean->initializeForDispense( $type, $this->oodb->getBeanHelper() );
			$this->oodb->signal( 'dispense', $bean );
			$beans[] = $bean;
		}

		return ( count( $beans ) === 1 && !$alwaysReturnArray ) ? array_pop( $beans ) : $beans;
	}

	/**
	 * Loads a bean from the object database.
	 * It searches for a OODBBean Bean Object in the
	 * database. It does not matter how this bean has been stored.
	 * RedBean uses the primary key ID $id and the string $type
	 * to find the bean. The $type specifies what kind of bean you
	 * are looking for; this is the same type as used with the
	 * dispense() function. If RedBean finds the bean it will return
	 * the OODB Bean object; if it cannot find the bean
	 * RedBean will return a new bean of type $type and with
	 * primary key ID 0. In the latter case it acts basically the
	 * same as dispense().
	 *
	 * Important note:
	 * If the bean cannot be found in the database a new bean of
	 * the specified type will be generated and returned.
	 *
	 * @param string  $type type of bean you want to load
	 * @param integer $id   ID of the bean you want to load
	 *
	 * @return OODBBean
	 * @throws SQLException
	 */
	public function load( $type, $id )
	{
		$bean = $this->dispense( $type );
		if ( isset( $this->stash[$this->nesting][$id] ) ) {
			$row = $this->stash[$this->nesting][$id];
		} else {
			try {
				$rows = $this->writer->queryRecord( $type, array( 'id' => array( $id ) ) );
			} catch ( SQLException $exception ) {
				if ( $this->writer->sqlStateIn( $exception->getSQLState(),
					array(
						QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN,
						QueryWriter::C_SQLSTATE_NO_SUCH_TABLE )
				)
				) {
					throw $exception; //only throw if frozen
				}
			}
			if ( empty( $rows ) ) {
				return $bean;
			}
			$row = array_pop( $rows );
		}
		$bean->importRow( $row );
		$this->nesting++;
		$this->oodb->signal( 'open', $bean );
		$this->nesting--;

		return $bean->setMeta( 'tainted', FALSE );
	}
}
}

namespace RedBeanPHP {

use RedBeanPHP\Adapter\DBAdapter as DBAdapter;
use RedBeanPHP\QueryWriter as QueryWriter;
use RedBeanPHP\BeanHelper as BeanHelper;
use RedBeanPHP\QueryWriter\AQueryWriter as AQueryWriter;
use RedBeanPHP\Repository as Repository;
use RedBeanPHP\Repository\Fluid as FluidRepo;
use RedBeanPHP\Repository\Frozen as FrozenRepo;

/**
 * RedBean Object Oriented DataBase.
 *
 * The RedBean OODB Class is the main class of RedBeanPHP.
 * It takes OODBBean objects and stores them to and loads them from the
 * database as well as providing other CRUD functions. This class acts as a
 * object database.
 *
 * @file    RedBeanPHP/OODB.php
 * @author  Gabor de Mooij and the RedBeanPHP community
 * @license BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class OODB extends Observable
{
	/**
	 * @var array
	 */
	private static $sqlFilters = array();

	/**
	 * @var array
	 */
	protected $chillList = array();

	/**
	 * @var array
	 */
	protected $stash = NULL;

	/*
	 * @var integer
	 */
	protected $nesting = 0;

	/**
	 * @var DBAdapter
	 */
	protected $writer;

	/**
	 * @var boolean
	 */
	protected $isFrozen = FALSE;

	/**
	 * @var FacadeBeanHelper
	 */
	protected $beanhelper = NULL;

	/**
	 * @var AssociationManager
	 */
	protected $assocManager = NULL;

	/**
	 * @var Repository
	 */
	protected $repository = NULL;

	/**
	 * @var FrozenRepo
	 */
	protected $frozenRepository = NULL;

	/**
	 * @var FluidRepo
	 */
	protected $fluidRepository = NULL;

	/**
	 * @var boolean
	 */
	protected static $autoClearHistoryAfterStore = FALSE;

	/**
	 * If set to TRUE, this method will call clearHistory every time
	 * the bean gets stored.
	 *
	 * @param boolean $autoClear auto clear option
	 *
	 * @return void
	 */
	public static function autoClearHistoryAfterStore( $autoClear = TRUE )
	{
		self::$autoClearHistoryAfterStore = (boolean) $autoClear;
	}

	/**
	 * Unboxes a bean from a FUSE model if needed and checks whether the bean is
	 * an instance of OODBBean.
	 *
	 * @param OODBBean $bean bean you wish to unbox
	 *
	 * @return OODBBean
	 */
	protected function unboxIfNeeded( $bean )
	{
		if ( $bean instanceof SimpleModel ) {
			$bean = $bean->unbox();
		}
		if ( !( $bean instanceof OODBBean ) ) {
			throw new RedException( 'OODB Store requires a bean, got: ' . gettype( $bean ) );
		}

		return $bean;
	}

	/**
	 * Constructor, requires a query writer.
	 *
	 * @param QueryWriter   $writer writer
	 * @param array|boolean $frozen mode of operation: TRUE (frozen), FALSE (default, fluid) or ARRAY (chilled)
	 */
	public function __construct( QueryWriter $writer, $frozen = FALSE )
	{
		if ( $writer instanceof QueryWriter ) {
			$this->writer = $writer;
		}

		$this->freeze( $frozen );
	}

	/**
	 * Toggles fluid or frozen mode. In fluid mode the database
	 * structure is adjusted to accomodate your objects. In frozen mode
	 * this is not the case.
	 *
	 * You can also pass an array containing a selection of frozen types.
	 * Let's call this chilly mode, it's just like fluid mode except that
	 * certain types (i.e. tables) aren't touched.
	 *
	 * @param boolean|array $toggle TRUE if you want to use OODB instance in frozen mode
	 *
	 * @return void
	 */
	public function freeze( $toggle )
	{
		if ( is_array( $toggle ) ) {
			$this->chillList = $toggle;
			$this->isFrozen  = FALSE;
		} else {
			$this->isFrozen = (boolean) $toggle;
		}

		if ( $this->isFrozen ) {
			if ( !$this->frozenRepository ) {
				$this->frozenRepository = new FrozenRepo( $this, $this->writer );
			}

			$this->repository = $this->frozenRepository;

		} else {
			if ( !$this->fluidRepository ) {
				$this->fluidRepository = new FluidRepo( $this, $this->writer );
			}

			$this->repository = $this->fluidRepository;
		}

		if ( count( self::$sqlFilters ) ) {
			AQueryWriter::setSQLFilters( self::$sqlFilters, ( !$this->isFrozen ) );
		}

	}

	/**
	 * Returns the current mode of operation of RedBean.
	 * In fluid mode the database
	 * structure is adjusted to accomodate your objects.
	 * In frozen mode
	 * this is not the case.
	 *
	 * @return boolean
	 */
	public function isFrozen()
	{
		return (bool) $this->isFrozen;
	}

	/**
	 * Determines whether a type is in the chill list.
	 * If a type is 'chilled' it's frozen, so its schema cannot be
	 * changed anymore. However other bean types may still be modified.
	 * This method is a convenience method for other objects to check if
	 * the schema of a certain type is locked for modification.
	 *
	 * @param string $type the type you wish to check
	 *
	 * @return boolean
	 */
	public function isChilled( $type )
	{
		return (boolean) ( in_array( $type, $this->chillList ) );
	}

	/**
	 * Dispenses a new bean (a OODBBean Bean Object)
	 * of the specified type. Always
	 * use this function to get an empty bean object. Never
	 * instantiate a OODBBean yourself because it needs
	 * to be configured before you can use it with RedBean. This
	 * function applies the appropriate initialization /
	 * configuration for you.
	 *
	 * @param string  $type              type of bean you want to dispense
	 * @param string  $number            number of beans you would like to get
	 * @param boolean $alwaysReturnArray if TRUE always returns the result as an array
	 *
	 * @return OODBBean
	 */
	public function dispense( $type, $number = 1, $alwaysReturnArray = FALSE )
	{
		if ( $number < 1 ) {
			if ( $alwaysReturnArray ) return array();
			return NULL;
		}

		return $this->repository->dispense( $type, $number, $alwaysReturnArray );
	}

	/**
	 * Sets bean helper to be given to beans.
	 * Bean helpers assist beans in getting a reference to a toolbox.
	 *
	 * @param BeanHelper $beanhelper helper
	 *
	 * @return void
	 */
	public function setBeanHelper( BeanHelper $beanhelper )
	{
		$this->beanhelper = $beanhelper;
	}

	/**
	 * Returns the current bean helper.
	 * Bean helpers assist beans in getting a reference to a toolbox.
	 *
	 * @return BeanHelper
	 */
	public function getBeanHelper()
	{
		return $this->beanhelper;
	}

	/**
	 * Checks whether a OODBBean bean is valid.
	 * If the type is not valid or the ID is not valid it will
	 * throw an exception: Security.
	 *
	 * @param OODBBean $bean the bean that needs to be checked
	 *
	 * @return void
	 */
	public function check( OODBBean $bean )
	{
		$this->repository->check( $bean );
	}

	/**
	 * Searches the database for a bean that matches conditions $conditions and sql $addSQL
	 * and returns an array containing all the beans that have been found.
	 *
	 * Conditions need to take form:
	 *
	 * <code>
	 * array(
	 *    'PROPERTY' => array( POSSIBLE VALUES... 'John', 'Steve' )
	 *    'PROPERTY' => array( POSSIBLE VALUES... )
	 * );
	 * </code>
	 *
	 * All conditions are glued together using the AND-operator, while all value lists
	 * are glued using IN-operators thus acting as OR-conditions.
	 *
	 * Note that you can use property names; the columns will be extracted using the
	 * appropriate bean formatter.
	 *
	 * @param string $type       type of beans you are looking for
	 * @param array  $conditions list of conditions
	 * @param string $addSQL     SQL to be used in query
	 * @param array  $bindings   a list of values to bind to query parameters
	 *
	 * @return array
	 */
	public function find( $type, $conditions = array(), $sql = NULL, $bindings = array() )
	{
		return $this->repository->find( $type, $conditions, $sql, $bindings );
	}

	/**
	 * Same as find() but returns a BeanCollection.
	 *
	 * @param string $type     type of beans you are looking for
	 * @param string $addSQL   SQL to be used in query
	 * @param array  $bindings a list of values to bind to query parameters
	 *
	 * @return array
	 */
	public function findCollection(  $type, $sql = NULL, $bindings = array() )
	{
		return $this->repository->findCollection( $type, $sql, $bindings );
	}

	/**
	 * Checks whether the specified table already exists in the database.
	 * Not part of the Object Database interface!
	 *
	 * @deprecated Use AQueryWriter::typeExists() instead.
	 *
	 * @param string $table table name
	 *
	 * @return boolean
	 */
	public function tableExists( $table )
	{
		return $this->repository->tableExists( $table );
	}

	/**
	 * Stores a bean in the database. This method takes a
	 * OODBBean Bean Object $bean and stores it
	 * in the database. If the database schema is not compatible
	 * with this bean and RedBean runs in fluid mode the schema
	 * will be altered to store the bean correctly.
	 * If the database schema is not compatible with this bean and
	 * RedBean runs in frozen mode it will throw an exception.
	 * This function returns the primary key ID of the inserted
	 * bean.
	 *
	 * The return value is an integer if possible. If it is not possible to
	 * represent the value as an integer a string will be returned. We use
	 * explicit casts instead of functions to preserve performance
	 * (0.13 vs 0.28 for 10000 iterations on Core i3).
	 *
	 * @param OODBBean|SimpleModel $bean bean to store
	 *
	 * @return integer|string
	 */
	public function store( $bean )
	{
		$bean = $this->unboxIfNeeded( $bean );
		$id = $this->repository->store( $bean );
		if ( self::$autoClearHistoryAfterStore ) {
				$bean->clearHistory();
		}
		return $id;
	}

	/**
	 * Loads a bean from the object database.
	 * It searches for a OODBBean Bean Object in the
	 * database. It does not matter how this bean has been stored.
	 * RedBean uses the primary key ID $id and the string $type
	 * to find the bean. The $type specifies what kind of bean you
	 * are looking for; this is the same type as used with the
	 * dispense() function. If RedBean finds the bean it will return
	 * the OODB Bean object; if it cannot find the bean
	 * RedBean will return a new bean of type $type and with
	 * primary key ID 0. In the latter case it acts basically the
	 * same as dispense().
	 *
	 * Important note:
	 * If the bean cannot be found in the database a new bean of
	 * the specified type will be generated and returned.
	 *
	 * @param string  $type type of bean you want to load
	 * @param integer $id   ID of the bean you want to load
	 *
	 * @return OODBBean
	 */
	public function load( $type, $id )
	{
		return $this->repository->load( $type, $id );
	}

	/**
	 * Removes a bean from the database.
	 * This function will remove the specified OODBBean
	 * Bean Object from the database.
	 *
	 * @param OODBBean|SimpleModel $bean bean you want to remove from database
	 *
	 * @return void
	 */
	public function trash( $bean )
	{
		$bean = $this->unboxIfNeeded( $bean );
		return $this->repository->trash( $bean );
	}

	/**
	 * Returns an array of beans. Pass a type and a series of ids and
	 * this method will bring you the corresponding beans.
	 *
	 * important note: Because this method loads beans using the load()
	 * function (but faster) it will return empty beans with ID 0 for
	 * every bean that could not be located. The resulting beans will have the
	 * passed IDs as their keys.
	 *
	 * @param string $type type of beans
	 * @param array  $ids  ids to load
	 *
	 * @return array
	 */
	public function batch( $type, $ids )
	{
		return $this->repository->batch( $type, $ids );
	}

	/**
	 * This is a convenience method; it converts database rows
	 * (arrays) into beans. Given a type and a set of rows this method
	 * will return an array of beans of the specified type loaded with
	 * the data fields provided by the result set from the database.
	 *
	 * @param string $type type of beans you would like to have
	 * @param array  $rows rows from the database result
	 * @param string $mask mask to apply for meta data
	 *
	 * @return array
	 */
	public function convertToBeans( $type, $rows, $mask = NULL )
	{
		return $this->repository->convertToBeans( $type, $rows, $mask );
	}

	/**
	 * Counts the number of beans of type $type.
	 * This method accepts a second argument to modify the count-query.
	 * A third argument can be used to provide bindings for the SQL snippet.
	 *
	 * @param string $type     type of bean we are looking for
	 * @param string $addSQL   additional SQL snippet
	 * @param array  $bindings parameters to bind to SQL
	 *
	 * @return integer
	 */
	public function count( $type, $addSQL = '', $bindings = array() )
	{
		return $this->repository->count( $type, $addSQL, $bindings );
	}

	/**
	 * Trash all beans of a given type. Wipes an entire type of bean.
	 *
	 * @param string $type type of bean you wish to delete all instances of
	 *
	 * @return boolean
	 */
	public function wipe( $type )
	{
		return $this->repository->wipe( $type );
	}

	/**
	 * Returns an Association Manager for use with OODB.
	 * A simple getter function to obtain a reference to the association manager used for
	 * storage and more.
	 *
	 * @return AssociationManager
	 */
	public function getAssociationManager()
	{
		if ( !isset( $this->assocManager ) ) {
			throw new RedException( 'No association manager available.' );
		}

		return $this->assocManager;
	}

	/**
	 * Sets the association manager instance to be used by this OODB.
	 * A simple setter function to set the association manager to be used for storage and
	 * more.
	 *
	 * @param AssociationManager $assoc sets the association manager to be used
	 *
	 * @return void
	 */
	public function setAssociationManager( AssociationManager $assocManager )
	{
		$this->assocManager = $assocManager;
	}

	/**
	 * Returns the currently used repository instance.
	 * For testing purposes only.
	 *
	 * @return Repository
	 */
	public function getCurrentRepository()
	{
		return $this->repository;
	}

	/**
	 * Binds an SQL function to a column.
	 * This method can be used to setup a decode/encode scheme or
	 * perform UUID insertion. This method is especially useful for handling
	 * MySQL spatial columns, because they need to be processed first using
	 * the asText/GeomFromText functions.
	 *
	 * @param string $mode     mode to set function for, i.e. read or write
	 * @param string $field    field (table.column) to bind SQL function to
	 * @param string $function SQL function to bind to field
	 *
	 * @return void
	 */
	public function bindFunc( $mode, $field, $function )
	{
		list( $type, $property ) = explode( '.', $field );
		$mode = ($mode === 'write') ? QueryWriter::C_SQLFILTER_WRITE : QueryWriter::C_SQLFILTER_READ;

		if ( !isset( self::$sqlFilters[$mode] ) ) self::$sqlFilters[$mode] = array();
		if ( !isset( self::$sqlFilters[$mode][$type] ) ) self::$sqlFilters[$mode][$type] = array();

		if ( is_null( $function ) ) {
			unset( self::$sqlFilters[$mode][$type][$property] );
		} else {
			if ($mode === QueryWriter::C_SQLFILTER_WRITE) {
				self::$sqlFilters[$mode][$type][$property] = $function.'(?)';
			} else {
				self::$sqlFilters[$mode][$type][$property] = $function."($field)";
			}
		}

		AQueryWriter::setSQLFilters( self::$sqlFilters, ( !$this->isFrozen ) );
	}
}
}

namespace RedBeanPHP {

use RedBeanPHP\OODB as OODB;
use RedBeanPHP\QueryWriter as QueryWriter;
use RedBeanPHP\Adapter\DBAdapter as DBAdapter;
use RedBeanPHP\Adapter as Adapter;

/**
 * ToolBox.
 *
 * The toolbox is an integral part of RedBeanPHP providing the basic
 * architectural building blocks to manager objects, helpers and additional tools
 * like plugins. A toolbox contains the three core components of RedBeanPHP:
 * the adapter, the query writer and the core functionality of RedBeanPHP in
 * OODB.
 *
 * @file      RedBeanPHP/ToolBox.php
 * @author    Gabor de Mooij and the RedBeanPHP community
 * @license   BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class ToolBox
{
	/**
	 * @var OODB
	 */
	protected $oodb;

	/**
	 * @var QueryWriter
	 */
	protected $writer;

	/**
	 * @var DBAdapter
	 */
	protected $adapter;

	/**
	 * Constructor.
	 * The toolbox is an integral part of RedBeanPHP providing the basic
	 * architectural building blocks to manager objects, helpers and additional tools
	 * like plugins. A toolbox contains the three core components of RedBeanPHP:
	 * the adapter, the query writer and the core functionality of RedBeanPHP in
	 * OODB.
	 *
	 * @param OODB        $oodb    Object Database, OODB
	 * @param DBAdapter   $adapter Database Adapter
	 * @param QueryWriter $writer  Query Writer
	 */
	public function __construct( OODB $oodb, Adapter $adapter, QueryWriter $writer )
	{
		$this->oodb    = $oodb;
		$this->adapter = $adapter;
		$this->writer  = $writer;
		return $this;
	}

	/**
	 * Returns the query writer in this toolbox.
	 * The Query Writer is responsible for building the queries for a
	 * specific database and executing them through the adapter.
	 *
	 * @return QueryWriter
	 */
	public function getWriter()
	{
		return $this->writer;
	}

	/**
	 * Returns the OODB instance in this toolbox.
	 * OODB is responsible for creating, storing, retrieving and deleting
	 * single beans. Other components rely
	 * on OODB for their basic functionality.
	 *
	 * @return OODB
	 */
	public function getRedBean()
	{
		return $this->oodb;
	}

	/**
	 * Returns the database adapter in this toolbox.
	 * The adapter is responsible for executing the query and binding the values.
	 * The adapter also takes care of transaction handling.
	 *
	 * @return DBAdapter
	 */
	public function getDatabaseAdapter()
	{
		return $this->adapter;
	}
}
}

namespace RedBeanPHP {


/**
 * RedBeanPHP Finder.
 * Service class to find beans. For the most part this class
 * offers user friendly utility methods for interacting with the
 * OODB::find() method, which is rather complex. This class can be
 * used to find beans using plain old SQL queries.
 *
 * @file    RedBeanPHP/Finder.php
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Finder
{
	/**
	 * @var ToolBox
	 */
	protected $toolbox;

	/**
	 * @var OODB
	 */
	protected $redbean;

	/**
	 * Constructor.
	 * The Finder requires a toolbox.
	 *
	 * @param ToolBox $toolbox
	 */
	public function __construct( ToolBox $toolbox )
	{
		$this->toolbox = $toolbox;
		$this->redbean = $toolbox->getRedBean();
	}

	/**
	 * Finds a bean using a type and a where clause (SQL).
	 * As with most Query tools in RedBean you can provide values to
	 * be inserted in the SQL statement by populating the value
	 * array parameter; you can either use the question mark notation
	 * or the slot-notation (:keyname).
	 *
	 * @param string $type     type   the type of bean you are looking for
	 * @param string $sql      sql    SQL query to find the desired bean, starting right after WHERE clause
	 * @param array  $bindings values array of values to be bound to parameters in query
	 *
	 * @return array
	 */
	public function find( $type, $sql = NULL, $bindings = array() )
	{
		if ( !is_array( $bindings ) ) {
			throw new RedException(
				'Expected array, ' . gettype( $bindings ) . ' given.'
			);
		}

		return $this->redbean->find( $type, array(), $sql, $bindings );
	}

	/**
	 * Like find() but also exports the beans as an array.
	 * This method will perform a find-operation. For every bean
	 * in the result collection this method will call the export() method.
	 * This method returns an array containing the array representations
	 * of every bean in the result set.
	 *
	 * @see Finder::find
	 *
	 * @param string $type     type   the type of bean you are looking for
	 * @param string $sql      sql    SQL query to find the desired bean, starting right after WHERE clause
	 * @param array  $bindings values array of values to be bound to parameters in query
	 *
	 * @return array
	 */
	public function findAndExport( $type, $sql = NULL, $bindings = array() )
	{
		$arr = array();
		foreach ( $this->find( $type, $sql, $bindings ) as $key => $item ) {
			$arr[] = $item->export();
		}

		return $arr;
	}

	/**
	 * Like find() but returns just one bean instead of an array of beans.
	 * This method will return only the first bean of the array.
	 * If no beans are found, this method will return NULL.
	 *
	 * @see Finder::find
	 *
	 * @param string $type     type   the type of bean you are looking for
	 * @param string $sql      sql    SQL query to find the desired bean, starting right after WHERE clause
	 * @param array  $bindings values array of values to be bound to parameters in query
	 *
	 * @return OODBBean
	 */
	public function findOne( $type, $sql = NULL, $bindings = array() )
	{
		$sql = $this->toolbox->getWriter()->glueLimitOne( $sql );

		$items = $this->find( $type, $sql, $bindings );

		if ( empty($items) ) {
			return NULL;
		}

		return reset( $items );
	}

	/**
	 * Like find() but returns the last bean of the result array.
	 * Opposite of Finder::findLast().
	 * If no beans are found, this method will return NULL.
	 *
	 * @see Finder::find
	 *
	 * @param string $type     the type of bean you are looking for
	 * @param string $sql      SQL query to find the desired bean, starting right after WHERE clause
	 * @param array  $bindings values array of values to be bound to parameters in query
	 *
	 * @return OODBBean
	 */
	public function findLast( $type, $sql = NULL, $bindings = array() )
	{
		$items = $this->find( $type, $sql, $bindings );

		if ( empty($items) ) {
			return NULL;
		}

		return end( $items );
	}

	/**
	 * Tries to find beans of a certain type,
	 * if no beans are found, it dispenses a bean of that type.
	 * Note that this function always returns an array.
	 *
	 * @see Finder::find
	 *
	 * @param  string $type     the type of bean you are looking for
	 * @param  string $sql      SQL query to find the desired bean, starting right after WHERE clause
	 * @param  array  $bindings values array of values to be bound to parameters in query
	 *
	 * @return array
	 */
	public function findOrDispense( $type, $sql = NULL, $bindings = array() )
	{
		$foundBeans = $this->find( $type, $sql, $bindings );

		if ( empty( $foundBeans ) ) {
			return array( $this->redbean->dispense( $type ) );
		} else {
			return $foundBeans;
		}
	}

	/**
	 * Finds a BeanCollection using the repository.
	 * A bean collection can be used to retrieve one bean at a time using
	 * cursors - this is useful for processing large datasets. A bean collection
	 * will not load all beans into memory all at once, just one at a time.
	 *
	 * @param  string $type     the type of bean you are looking for
	 * @param  string $sql      SQL query to find the desired bean, starting right after WHERE clause
	 * @param  array  $bindings values array of values to be bound to parameters in query
	 *
	 * @return BeanCollection
	 */
	public function findCollection( $type, $sql, $bindings = array() )
	{
		return $this->redbean->findCollection( $type, $sql, $bindings );
	}

	/**
	 * Finds or creates a bean.
	 * Tries to find a bean with certain properties specified in the second
	 * parameter ($like). If the bean is found, it will be returned.
	 * If multiple beans are found, only the first will be returned.
	 * If no beans match the criteria, a new bean will be dispensed,
	 * the criteria will be imported as properties and this new bean
	 * will be stored and returned.
	 *
	 * Format of criteria set: property => value
	 * The criteria set also supports OR-conditions: property => array( value1, orValue2 )
	 *
	 * @param string $type type of bean to search for
	 * @param array  $like criteria set describing bean to search for
	 *
	 * @return OODBBean
	 */
	public function findOrCreate( $type, $like = array() )
	{
			$beans = $this->findLike( $type, $like );
			if ( count( $beans ) ) {
				$bean = reset( $beans );
				return $bean;
			}

			$bean = $this->redbean->dispense( $type );
			$bean->import( $like );
			$this->redbean->store( $bean );
			return $bean;
	}

	/**
	 * Finds beans by its type and a certain criteria set.
	 *
	 * Format of criteria set: property => value
	 * The criteria set also supports OR-conditions: property => array( value1, orValue2 )
	 *
	 * If the additional SQL is a condition, this condition will be glued to the rest
	 * of the query using an AND operator. Note that this is as far as this method
	 * can go, there is no way to glue additional SQL using an OR-condition.
	 * This method provides access to an underlying mechanism in the RedBeanPHP architecture
	 * to find beans using criteria sets. However, please do not use this method
	 * for complex queries, use plain SQL instead ( the regular find method ) as it is
	 * more suitable for the job. This method is
	 * meant for basic search-by-example operations.
	 *
	 * @param string $type       type of bean to search for
	 * @param array  $conditions criteria set describing the bean to search for
	 * @param string $sql        additional SQL (for sorting)
	 *
	 * @return array
	 */
	public function findLike( $type, $conditions = array(), $sql = '' )
	{
		if ( count( $conditions ) > 0 ) {
			foreach( $conditions as $key => $condition ) {
				if ( !count( $condition ) ) unset( $conditions[$key] );
			}
		}

		return $this->redbean->find( $type, $conditions, $sql );
	}

	/**
	 * Returns a hashmap with bean arrays keyed by type using an SQL
	 * query as its resource. Given an SQL query like 'SELECT movie.*, review.* FROM movie... JOIN review'
	 * this method will return movie and review beans.
	 *
	 * Example:
	 *
	 * <code>
	 * $stuff = $finder->findMulti('movie,review', '
	 *          SELECT movie.*, review.* FROM movie
	 *          LEFT JOIN review ON review.movie_id = movie.id');
	 * </code>
	 *
	 * After this operation, $stuff will contain an entry 'movie' containing all
	 * movies and an entry named 'review' containing all reviews (all beans).
	 * You can also pass bindings.
	 *
	 * If you want to re-map your beans, so you can use $movie->ownReviewList without
	 * having RedBeanPHP executing an SQL query you can use the fourth parameter to
	 * define a selection of remapping closures.
	 *
	 * The remapping argument (optional) should contain an array of arrays.
	 * Each array in the remapping array should contain the following entries:
	 *
	 * <code>
	 * array(
	 * 	'a'       => TYPE A
	 *    'b'       => TYPE B
	 *    'matcher' => MATCHING FUNCTION ACCEPTING A, B and ALL BEANS
	 *    'do'      => OPERATION FUNCTION ACCEPTING A, B, ALL BEANS, ALL REMAPPINGS
	 * )
	 * </code>
	 *
	 * Using this mechanism you can build your own 'preloader' with tiny function
	 * snippets (and those can be re-used and shared online of course).
	 *
	 * Example:
	 *
	 * <code>
	 * array(
	 * 	'a'       => 'movie'     //define A as movie
	 *    'b'       => 'review'    //define B as review
	 *    'matcher' => function( $a, $b ) {
	 *       return ( $b->movie_id == $a->id );  //Perform action if review.movie_id equals movie.id
	 *    }
	 *    'do'      => function( $a, $b ) {
	 *       $a->noLoad()->ownReviewList[] = $b; //Add the review to the movie
	 *       $a->clearHistory();                 //optional, act 'as if these beans have been loaded through ownReviewList'.
	 *    }
	 * )
	 * </code>
	 *
	 * The Query Template parameter is optional as well but can be used to
	 * set a different SQL template (sprintf-style) for processing the original query.
	 *
	 * @note the SQL query provided IS NOT THE ONE used internally by this function,
	 * this function will pre-process the query to get all the data required to find the beans.
	 *
	 * @note if you use the 'book.*' notation make SURE you're
	 * selector starts with a SPACE. ' book.*' NOT ',book.*'. This is because
	 * it's actually an SQL-like template SLOT, not real SQL.
	 *
	 * @note instead of an SQL query you can pass a result array as well.
	 *
	 * @param string|array $types         a list of types (either array or comma separated string)
	 * @param string|array $sqlOrArr      an SQL query or an array of prefetched records
	 * @param array        $bindings      optional, bindings for SQL query
	 * @param array        $remappings    optional, an array of remapping arrays
	 * @param string       $queryTemplate optional, query template
	 *
	 * @return array
	 */
	public function findMulti( $types, $sql, $bindings = array(), $remappings = array(), $queryTemplate = ' %s.%s AS %s__%s' )
	{
		if ( !is_array( $types ) ) $types = explode( ',', $types );
		if ( !is_array( $sql ) ) {
			$writer = $this->toolbox->getWriter();
			$adapter = $this->toolbox->getDatabaseAdapter();

			//Repair the query, replace book.* with book.id AS book_id etc..
			foreach( $types as $type ) {
				$pattern = " {$type}.*";
				if ( strpos( $sql, $pattern ) !== FALSE ) {
					$newSelectorArray = array();
					$columns = $writer->getColumns( $type );
					foreach( $columns as $column => $definition ) {
						$newSelectorArray[] = sprintf( $queryTemplate, $type, $column, $type, $column );
					}
					$newSelector = implode( ',', $newSelectorArray );
					$sql = str_replace( $pattern, $newSelector, $sql );
				}
			}

			$rows = $adapter->get( $sql, $bindings );
		} else {
			$rows = $sql;
		}

		//Gather the bean data from the query results using the prefix
		$wannaBeans = array();
		foreach( $types as $type ) {
			$wannaBeans[$type] = array();
			$prefix            = "{$type}__";
			foreach( $rows as $rowkey=>$row ) {
				$wannaBean = array();
				foreach( $row as $cell => $value ) {
					if ( strpos( $cell, $prefix ) === 0 ) {
						$property = substr( $cell, strlen( $prefix ) );
						unset( $rows[$rowkey][$cell] );
						$wannaBean[$property] = $value;
					}
				}
				if ( !isset( $wannaBean['id'] ) ) continue;
				if ( is_null( $wannaBean['id'] ) ) continue;
				$wannaBeans[$type][$wannaBean['id']] = $wannaBean;
			}
		}

		//Turn the rows into beans
		$beans = array();
		foreach( $wannaBeans as $type => $wannabees ) {
			$beans[$type] = $this->redbean->convertToBeans( $type, $wannabees );
		}

		//Apply additional re-mappings
		foreach($remappings as $remapping) {
			$a       = $remapping['a'];
			$b       = $remapping['b'];
			$matcher = $remapping['matcher'];
			$do      = $remapping['do'];
			foreach( $beans[$a] as $bean ) {
				foreach( $beans[$b] as $putBean ) {
					if ( $matcher( $bean, $putBean, $beans ) ) $do( $bean, $putBean, $beans, $remapping );
				}
			}
		}
		return $beans;
	}
}
}

namespace RedBeanPHP {

use RedBeanPHP\Adapter\DBAdapter as DBAdapter;
use RedBeanPHP\QueryWriter as QueryWriter;
use RedBeanPHP\RedException as RedException;
use RedBeanPHP\RedException\SQL as SQLException;

/**
 * Association Manager.
 * Manages simple bean associations.
 *
 * @file    RedBeanPHP/AssociationManager.php
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * @copyright
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class AssociationManager extends Observable
{
	/**
	 * @var OODB
	 */
	protected $oodb;

	/**
	 * @var DBAdapter
	 */
	protected $adapter;

	/**
	 * @var QueryWriter
	 */
	protected $writer;

	/**
	 * Handles exceptions. Suppresses exceptions caused by missing structures.
	 *
	 * @param \Exception $exception exception to handle
	 *
	 * @return void
	 */
	private function handleException( \Exception $exception )
	{
		if ( $this->oodb->isFrozen() || !$this->writer->sqlStateIn( $exception->getSQLState(),
			array(
				QueryWriter::C_SQLSTATE_NO_SUCH_TABLE,
				QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN )
			)
		) {
			throw $exception;
		}
	}

	/**
	 * Internal method.
	 * Returns the many-to-many related rows of table $type for bean $bean using additional SQL in $sql and
	 * $bindings bindings. If $getLinks is TRUE, link rows are returned instead.
	 *
	 * @param OODBBean $bean     reference bean instance
	 * @param string   $type     target bean type
	 * @param string   $sql      additional SQL snippet
	 * @param array    $bindings bindings for query
	 *
	 * @return array
	 */
	private function relatedRows( $bean, $type, $sql = '', $bindings = array() )
	{
		$ids = array( $bean->id );
		$sourceType = $bean->getMeta( 'type' );
		try {
			return $this->writer->queryRecordRelated( $sourceType, $type, $ids, $sql, $bindings );
		} catch ( SQLException $exception ) {
			$this->handleException( $exception );
			return array();
		}
	}

	/**
	 * Associates a pair of beans. This method associates two beans, no matter
	 * what types. Accepts a base bean that contains data for the linking record.
	 * This method is used by associate. This method also accepts a base bean to be used
	 * as the template for the link record in the database.
	 *
	 * @param OODBBean $bean1 first bean
	 * @param OODBBean $bean2 second bean
	 * @param OODBBean $bean  base bean (association record)
	 *
	 * @return mixed
	 */
	protected function associateBeans( OODBBean $bean1, OODBBean $bean2, OODBBean $bean )
	{
		$type      = $bean->getMeta( 'type' );
		$property1 = $bean1->getMeta( 'type' ) . '_id';
		$property2 = $bean2->getMeta( 'type' ) . '_id';

		if ( $property1 == $property2 ) {
			$property2 = $bean2->getMeta( 'type' ) . '2_id';
		}

		$this->oodb->store( $bean1 );
		$this->oodb->store( $bean2 );

		$bean->setMeta( "cast.$property1", "id" );
		$bean->setMeta( "cast.$property2", "id" );
		$bean->setMeta( 'sys.buildcommand.unique', array( $property1, $property2 ) );

		$bean->$property1 = $bean1->id;
		$bean->$property2 = $bean2->id;

		$results   = array();

		try {
			$id = $this->oodb->store( $bean );
			$results[] = $id;
		} catch ( SQLException $exception ) {
			if ( !$this->writer->sqlStateIn( $exception->getSQLState(),
				array( QueryWriter::C_SQLSTATE_INTEGRITY_CONSTRAINT_VIOLATION ) )
			) {
				throw $exception;
			}
		}

		return $results;
	}

	/**
	 * Constructor
	 *
	 * @param ToolBox $tools toolbox
	 */
	public function __construct( ToolBox $tools )
	{
		$this->oodb    = $tools->getRedBean();
		$this->adapter = $tools->getDatabaseAdapter();
		$this->writer  = $tools->getWriter();
		$this->toolbox = $tools;
	}

	/**
	 * Creates a table name based on a types array.
	 * Manages the get the correct name for the linking table for the
	 * types provided.
	 *
	 * @param array $types 2 types as strings
	 *
	 * @return string
	 */
	public function getTable( $types )
	{
		return $this->writer->getAssocTable( $types );
	}

	/**
	 * Associates two beans in a many-to-many relation.
	 * This method will associate two beans and store the connection between the
	 * two in a link table. Instead of two single beans this method also accepts
	 * two sets of beans. Returns the ID or the IDs of the linking beans.
	 *
	 * @param OODBBean|array $beans1 one or more beans to form the association
	 * @param OODBBean|array $beans2 one or more beans to form the association
	 *
	 * @return array
	 */
	public function associate( $beans1, $beans2 )
	{
		if ( !is_array( $beans1 ) ) {
			$beans1 = array( $beans1 );
		}

		if ( !is_array( $beans2 ) ) {
			$beans2 = array( $beans2 );
		}

		$results = array();
		foreach ( $beans1 as $bean1 ) {
			foreach ( $beans2 as $bean2 ) {
				$table     = $this->getTable( array( $bean1->getMeta( 'type' ), $bean2->getMeta( 'type' ) ) );
				$bean      = $this->oodb->dispense( $table );
				$results[] = $this->associateBeans( $bean1, $bean2, $bean );
			}
		}

		return ( count( $results ) > 1 ) ? $results : reset( $results );
	}

	/**
	 * Counts the number of related beans in an N-M relation.
	 * This method returns the number of beans of type $type associated
	 * with reference bean(s) $bean. The query can be tuned using an
	 * SQL snippet for additional filtering.
	 *
	 * @param OODBBean|array $bean     a bean object or an array of beans
	 * @param string         $type     type of bean you're interested in
	 * @param string         $sql      SQL snippet (optional)
	 * @param array          $bindings bindings for your SQL string
	 *
	 * @return integer
	 */
	public function relatedCount( $bean, $type, $sql = NULL, $bindings = array() )
	{
		if ( !( $bean instanceof OODBBean ) ) {
			throw new RedException(
				'Expected array or OODBBean but got:' . gettype( $bean )
			);
		}

		if ( !$bean->id ) {
			return 0;
		}

		$beanType = $bean->getMeta( 'type' );

		try {
			return $this->writer->queryRecordCountRelated( $beanType, $type, $bean->id, $sql, $bindings );
		} catch ( SQLException $exception ) {
			$this->handleException( $exception );

			return 0;
		}
	}

	/**
	 * Breaks the association between two beans. This method unassociates two beans. If the
	 * method succeeds the beans will no longer form an association. In the database
	 * this means that the association record will be removed. This method uses the
	 * OODB trash() method to remove the association links, thus giving FUSE models the
	 * opportunity to hook-in additional business logic. If the $fast parameter is
	 * set to boolean TRUE this method will remove the beans without their consent,
	 * bypassing FUSE. This can be used to improve performance.
	 *
	 * @param OODBBean $bean1 first bean in target association
	 * @param OODBBean $bean2 second bean in target association
	 * @param boolean  $fast  if TRUE, removes the entries by query without FUSE
	 *
	 * @return void
	 */
	public function unassociate( $beans1, $beans2, $fast = NULL )
	{
		$beans1 = ( !is_array( $beans1 ) ) ? array( $beans1 ) : $beans1;
		$beans2 = ( !is_array( $beans2 ) ) ? array( $beans2 ) : $beans2;

		foreach ( $beans1 as $bean1 ) {
			foreach ( $beans2 as $bean2 ) {
				try {
					$this->oodb->store( $bean1 );
					$this->oodb->store( $bean2 );

					$type1 = $bean1->getMeta( 'type' );
					$type2 = $bean2->getMeta( 'type' );

					$row      = $this->writer->queryRecordLink( $type1, $type2, $bean1->id, $bean2->id );
					$linkType = $this->getTable( array( $type1, $type2 ) );

					if ( $fast ) {
						$this->writer->deleteRecord( $linkType, array( 'id' => $row['id'] ) );

						return;
					}

					$beans = $this->oodb->convertToBeans( $linkType, array( $row ) );

					if ( count( $beans ) > 0 ) {
						$bean = reset( $beans );
						$this->oodb->trash( $bean );
					}
				} catch ( SQLException $exception ) {
					$this->handleException( $exception );
				}
			}
		}
	}

	/**
	 * Removes all relations for a bean. This method breaks every connection between
	 * a certain bean $bean and every other bean of type $type. Warning: this method
	 * is really fast because it uses a direct SQL query however it does not inform the
	 * models about this. If you want to notify FUSE models about deletion use a foreach-loop
	 * with unassociate() instead. (that might be slower though)
	 *
	 * @param OODBBean $bean reference bean
	 * @param string   $type type of beans that need to be unassociated
	 *
	 * @return void
	 */
	public function clearRelations( OODBBean $bean, $type )
	{
		$this->oodb->store( $bean );
		try {
			$this->writer->deleteRelations( $bean->getMeta( 'type' ), $type, $bean->id );
		} catch ( SQLException $exception ) {
			$this->handleException( $exception );
		}
	}

	/**
	 * Returns all the beans associated with $bean.
	 * This method will return an array containing all the beans that have
	 * been associated once with the associate() function and are still
	 * associated with the bean specified. The type parameter indicates the
	 * type of beans you are looking for. You can also pass some extra SQL and
	 * values for that SQL to filter your results after fetching the
	 * related beans.
	 *
	 * Don't try to make use of subqueries, a subquery using IN() seems to
	 * be slower than two queries!
	 *
	 * Since 3.2, you can now also pass an array of beans instead just one
	 * bean as the first parameter.
	 *
	 * @param OODBBean|array $bean the bean you have
	 * @param string         $type      the type of beans you want
	 * @param string         $sql       SQL snippet for extra filtering
	 * @param array          $bindings  values to be inserted in SQL slots
	 *
	 * @return array
	 */
	public function related( $bean, $type, $sql = '', $bindings = array() )
	{
		$sql   = $this->writer->glueSQLCondition( $sql );
		$rows  = $this->relatedRows( $bean, $type, $sql, $bindings );
		$links = array();

		foreach ( $rows as $key => $row ) {
			if ( !isset( $links[$row['id']] ) ) $links[$row['id']] = array();
			$links[$row['id']][] = $row['linked_by'];
			unset( $rows[$key]['linked_by'] );
		}

		$beans = $this->oodb->convertToBeans( $type, $rows );
		foreach ( $beans as $bean ) $bean->setMeta( 'sys.belongs-to', $links[$bean->id] );

		return $beans;
	}
}
}

namespace RedBeanPHP {

use RedBeanPHP\ToolBox as ToolBox;
use RedBeanPHP\OODBBean as OODBBean;

/**
 * Bean Helper Interface.
 *
 * Interface for Bean Helper.
 * A little bolt that glues the whole machinery together.
 * The Bean Helper is passed to the OODB RedBeanPHP Object to
 * faciliatte the creation of beans and providing them with
 * a toolbox. The Helper also facilitates the FUSE feature,
 * determining how beans relate to their models. By overriding
 * the getModelForBean method you can tune the FUSEing to
 * fit your business application needs.
 *
 * @file    RedBeanPHP/IBeanHelper.php
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
interface BeanHelper
{
	/**
	 * Returns a toolbox to empower the bean.
	 * This allows beans to perform OODB operations by themselves,
	 * as such the bean is a proxy for OODB. This allows beans to implement
	 * their magic getters and setters and return lists.
	 *
	 * @return ToolBox
	 */
	public function getToolbox();

	/**
	 * Does approximately the same as getToolbox but also extracts the
	 * toolbox for you.
	 * This method returns a list with all toolbox items in Toolbox Constructor order:
	 * OODB, adapter, writer and finally the toolbox itself!.
	 *
	 * @return array
	 */
	public function getExtractedToolbox();

	/**
	 * Given a certain bean this method will
	 * return the corresponding model.
	 * If no model is returned (NULL), RedBeanPHP might ask again.
	 *
	 * @note You can make RedBeanPHP faster by doing the setup wiring yourself.
	 * The event listeners take time, so to speed-up RedBeanPHP you can
	 * drop 'FUSE', if you're not interested in the Models.
	 *
	 * @note You can do funny stuff with this method but please be careful.
	 * You *could* create a model depending on properties of the bean, but
	 * it's a bit well... adventurous, here is an example:
	 *
	 * <code>
	 * class Book extends RedBeanPHP\SimpleModel {};
	 * class Booklet extends RedBeanPHP\SimpleModel {};
	 *
	 * class FlexBeanHelper extends RedBeanPHP\BeanHelper\SimpleFacadeBeanHelper {
	 *  public function getModelForBean( RedBeanPHP\OODBBean $bean ) {
	 *   if (!isset($bean->pages)) return NULL; //will ask again
	 *   if ($bean->pages <= 10) return new Booklet;
	 *   return new Book;
	 *	 }
	 * }
	 *
	 * $h = new FlexBeanHelper;
	 * R::getRedBean()->setBeanHelper($h);
	 * $book = R::dispense('book');
	 * var_dump($book->box()); //NULL cant reach model
	 * $book->pages = 5;
	 * var_dump($book->box()); //Booklet
	 * $book->pages = 15;
	 * var_dump($book->box()); //still.. Booklet, model has been set
	 * $book2 = R::dispense('book');
	 * $book2->pages = 15;
	 * var_dump($book2->box()); //Book, more than 10 pages
	 * </code>
	 *
	 * @param OODBBean $bean bean to obtain the corresponding model of
	 *
	 * @return SimpleModel|CustomModel|NULL
	 */
	public function getModelForBean( OODBBean $bean );
}
}

namespace RedBeanPHP\BeanHelper {

use RedBeanPHP\BeanHelper as BeanHelper;
use RedBeanPHP\Facade as Facade;
use RedBeanPHP\OODBBean as OODBBean;
use RedBeanPHP\SimpleModelHelper as SimpleModelHelper;

/**
 * Bean Helper.
 *
 * The Bean helper helps beans to access access the toolbox and
 * FUSE models. This Bean Helper makes use of the facade to obtain a
 * reference to the toolbox.
 *
 * @file    RedBeanPHP/BeanHelperFacade.php
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * @copyright
 * (c) copyright G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class SimpleFacadeBeanHelper implements BeanHelper
{
	/**
	 * Factory function to create instance of Simple Model, if any.
	 *
	 * @var \Closure
	 */
	private static $factory = null;

	/**
	 * Factory method using a customizable factory function to create
	 * the instance of the Simple Model.
	 *
	 * @param string $modelClassName name of the class
	 *
	 * @return SimpleModel
	 */
	public static function factory( $modelClassName )
	{
		$factory = self::$factory;
		return ( $factory ) ? $factory( $modelClassName ) : new $modelClassName();
	}

	/**
	 * Sets the factory function to create the model when using FUSE
	 * to connect a bean to a model.
	 *
	 * @param \Closure $factory factory function
	 *
	 * @return void
	 */
	public static function setFactoryFunction( $factory )
	{
		self::$factory = $factory;
	}

	/**
	 * @see BeanHelper::getToolbox
	 */
	public function getToolbox()
	{
		return Facade::getToolBox();
	}

	/**
	 * @see BeanHelper::getModelForBean
	 */
	public function getModelForBean( OODBBean $bean )
	{
		$model     = $bean->getMeta( 'type' );
		$prefix    = defined( 'REDBEAN_MODEL_PREFIX' ) ? REDBEAN_MODEL_PREFIX : '\\Model_';

		if ( strpos( $model, '_' ) !== FALSE ) {
			$modelParts = explode( '_', $model );
			$modelName = '';
			foreach( $modelParts as $part ) {
				$modelName .= ucfirst( $part );
			}
			$modelName = $prefix . $modelName;

			if ( !class_exists( $modelName ) ) {
				//second try
				$modelName = $prefix . ucfirst( $model );

				if ( !class_exists( $modelName ) ) {
					return NULL;
				}
			}

		} else {

			$modelName = $prefix . ucfirst( $model );
			if ( !class_exists( $modelName ) ) {
				return NULL;
			}
		}
		$obj = self::factory( $modelName );
		$obj->loadBean( $bean );

		return $obj;
	}

	/**
	 * @see BeanHelper::getExtractedToolbox
	 */
	public function getExtractedToolbox()
	{
		return Facade::getExtractedToolbox();
	}
}
}

namespace RedBeanPHP {

use RedBeanPHP\OODBBean as OODBBean;

/**
 * SimpleModel
 * Base Model For All RedBeanPHP Models using FUSE.
 *
 * RedBeanPHP FUSE is a mechanism to connect beans to posthoc
 * models. Models are connected to beans by naming conventions.
 * Actions on beans will result in actions on models.
 *
 * @file       RedBeanPHP/SimpleModel.php
 * @author     Gabor de Mooij and the RedBeanPHP Team
 * @license    BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class SimpleModel
{
	/**
	 * @var OODBBean
	 */
	protected $bean;

	/**
	 * Used by FUSE: the ModelHelper class to connect a bean to a model.
	 * This method loads a bean in the model.
	 *
	 * @param OODBBean $bean bean to load
	 *
	 * @return void
	 */
	public function loadBean( OODBBean $bean )
	{
		$this->bean = $bean;
	}

	/**
	 * Magic Getter to make the bean properties available from
	 * the $this-scope.
	 *
	 * @note this method returns a value, not a reference!
	 *       To obtain a reference unbox the bean first!
	 *
	 * @param string $prop property to get
	 *
	 * @return mixed
	 */
	public function __get( $prop )
	{
		return $this->bean->$prop;
	}

	/**
	 * Magic Setter.
	 * Sets the value directly as a bean property.
	 *
	 * @param string $prop  property to set value of
	 * @param mixed  $value value to set
	 *
	 * @return void
	 */
	public function __set( $prop, $value )
	{
		$this->bean->$prop = $value;
	}

	/**
	 * Isset implementation.
	 * Implements the isset function for array-like access.
	 *
	 * @param  string $key key to check
	 *
	 * @return boolean
	 */
	public function __isset( $key )
	{
		return isset( $this->bean->$key );
	}

	/**
	 * Box the bean using the current model.
	 * This method wraps the current bean in this model.
	 * This method can be reached using FUSE through a simple
	 * OODBBean. The method returns a RedBeanPHP Simple Model.
	 * This is useful if you would like to rely on PHP type hinting.
	 * You can box your beans before passing them to functions or methods
	 * with typed parameters.
	 *
	 * @return SimpleModel
	 */
	public function box()
	{
		return $this;
	}

	/**
	 * Unbox the bean from the model.
	 * This method returns the bean inside the model.
	 *
	 * @return OODBBean
	 */
	public function unbox()
	{
		return $this->bean;
	}
}
}

namespace RedBeanPHP {

use RedBeanPHP\Observer as Observer;
use RedBeanPHP\OODBBean as OODBBean;
use RedBeanPHP\Observable as Observable;

/**
 * RedBean Model Helper.
 *
 * Connects beans to models.
 * This is the core of so-called FUSE.
 *
 * @file    RedBeanPHP/ModelHelper.php
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class SimpleModelHelper implements Observer
{
	/**
	 * Gets notified by an observable.
	 * This method decouples the FUSE system from the actual beans.
	 * If a FUSE event happens 'update', this method will attempt to
	 * invoke the corresponding method on the bean.
	 *
	 * @param string  $eventName i.e. 'delete', 'after_delete'
	 * @param OODBean $bean      affected bean
	 *
	 * @return void
	 */
	public function onEvent( $eventName, $bean )
	{
		$bean->$eventName();
	}

	/**
	 * Attaches the FUSE event listeners. Now the Model Helper will listen for
	 * CRUD events. If a CRUD event occurs it will send a signal to the model
	 * that belongs to the CRUD bean and this model will take over control from
	 * there. This method will attach the following event listeners to the observable:
	 *
	 * - 'update'       (gets called by R::store, before the records gets inserted / updated)
	 * - 'after_update' (gets called by R::store, after the records have been inserted / updated)
	 * - 'open'         (gets called by R::load, after the record has been retrieved)
	 * - 'delete'       (gets called by R::trash, before deletion of record)
	 * - 'after_delete' (gets called by R::trash, after deletion)
	 * - 'dispense'     (gets called by R::dispense)
	 *
	 * For every event type, this method will register this helper as a listener.
	 * The observable will notify the listener (this object) with the event ID and the
	 * affected bean. This helper will then process the event (onEvent) by invoking
	 * the event on the bean. If a bean offers a method with the same name as the
	 * event ID, this method will be invoked.
	 *
	 * @param Observable $observable object to observe
	 *
	 * @return void
	 */
	public function attachEventListeners( Observable $observable )
	{
		foreach ( array( 'update', 'open', 'delete', 'after_delete', 'after_update', 'dispense' ) as $eventID ) {
			$observable->addEventListener( $eventID, $this );
		}
	}
}
}

namespace RedBeanPHP {

use RedBeanPHP\ToolBox as ToolBox;
use RedBeanPHP\AssociationManager as AssociationManager;
use RedBeanPHP\OODBBean as OODBBean;

/**
 * RedBeanPHP Tag Manager.
 *
 * The tag manager offers an easy way to quickly implement basic tagging
 * functionality.
 *
 * Provides methods to tag beans and perform tag-based searches in the
 * bean database.
 *
 * @file       RedBeanPHP/TagManager.php
 * @author     Gabor de Mooij and the RedBeanPHP community
 * @license    BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class TagManager
{
	/**
	 * @var ToolBox
	 */
	protected $toolbox;

	/**
	 * @var AssociationManager
	 */
	protected $associationManager;

	/**
	 * @var OODBBean
	 */
	protected $redbean;

	/**
	 * Checks if the argument is a comma separated string, in this case
	 * it will split the string into words and return an array instead.
	 * In case of an array the argument will be returned 'as is'.
	 *
	 * @param array|string $tagList list of tags
	 *
	 * @return array
	 */
	private function extractTagsIfNeeded( $tagList )
	{
		if ( $tagList !== FALSE && !is_array( $tagList ) ) {
			$tags = explode( ',', (string) $tagList );
		} else {
			$tags = $tagList;
		}

		return $tags;
	}

	/**
	 * Finds a tag bean by it's title.
	 * Internal method.
	 *
	 * @param string $title title to search for
	 *
	 * @return OODBBean
	 */
	protected function findTagByTitle( $title )
	{
		$beans = $this->redbean->find( 'tag', array( 'title' => array( $title ) ) );

		if ( $beans ) {
			$bean = reset( $beans );

			return $bean;
		}

		return NULL;
	}

	/**
	 * Constructor.
	 * The tag manager offers an easy way to quickly implement basic tagging
	 * functionality.
	 *
	 * @param ToolBox $toolbox toolbox object
	 */
	public function __construct( ToolBox $toolbox )
	{
		$this->toolbox = $toolbox;
		$this->redbean = $toolbox->getRedBean();

		$this->associationManager = $this->redbean->getAssociationManager();
	}

	/**
	 * Tests whether a bean has been associated with one ore more
	 * of the listed tags. If the third parameter is TRUE this method
	 * will return TRUE only if all tags that have been specified are indeed
	 * associated with the given bean, otherwise FALSE.
	 * If the third parameter is FALSE this
	 * method will return TRUE if one of the tags matches, FALSE if none
	 * match.
	 *
	 * Tag list can be either an array with tag names or a comma separated list
	 * of tag names.
	 *
	 * @param  OODBBean     $bean bean to check for tags
	 * @param  array|string $tags list of tags
	 * @param  boolean      $all  whether they must all match or just some
	 *
	 * @return boolean
	 */
	public function hasTag( $bean, $tags, $all = FALSE )
	{
		$foundtags = $this->tag( $bean );

		$tags = $this->extractTagsIfNeeded( $tags );
		$same = array_intersect( $tags, $foundtags );

		if ( $all ) {
			return ( implode( ',', $same ) === implode( ',', $tags ) );
		}

		return (bool) ( count( $same ) > 0 );
	}

	/**
	 * Removes all sepcified tags from the bean. The tags specified in
	 * the second parameter will no longer be associated with the bean.
	 *
	 * Tag list can be either an array with tag names or a comma separated list
	 * of tag names.
	 *
	 * @param  OODBBean     $bean    tagged bean
	 * @param  array|string $tagList list of tags (names)
	 *
	 * @return void
	 */
	public function untag( $bean, $tagList )
	{
		$tags = $this->extractTagsIfNeeded( $tagList );

		foreach ( $tags as $tag ) {
			if ( $t = $this->findTagByTitle( $tag ) ) {
				$this->associationManager->unassociate( $bean, $t );
			}
		}
	}

	/**
	 * Tags a bean or returns tags associated with a bean.
	 * If $tagList is NULL or omitted this method will return a
	 * comma separated list of tags associated with the bean provided.
	 * If $tagList is a comma separated list (string) of tags all tags will
	 * be associated with the bean.
	 * You may also pass an array instead of a string.
	 *
	 * Tag list can be either an array with tag names or a comma separated list
	 * of tag names.
	 *
	 * @param OODBBean     $bean    bean to be tagged
	 * @param array|string $tagList a list of tags
	 *
	 * @return array
	 */
	public function tag( OODBBean $bean, $tagList = NULL )
	{
		if ( is_null( $tagList ) ) {

			$tags = $bean->sharedTag;
			$foundTags = array();

			foreach ( $tags as $tag ) {
				$foundTags[] = $tag->title;
			}

			return $foundTags;
		}

		$this->associationManager->clearRelations( $bean, 'tag' );
		$this->addTags( $bean, $tagList );

		return $tagList;
	}

	/**
	 * Adds tags to a bean.
	 * If $tagList is a comma separated list of tags all tags will
	 * be associated with the bean.
	 * You may also pass an array instead of a string.
	 *
	 * Tag list can be either an array with tag names or a comma separated list
	 * of tag names.
	 *
	 * @param OODBBean     $bean    bean to add tags to
	 * @param array|string $tagList list of tags to add to bean
	 *
	 * @return void
	 */
	public function addTags( OODBBean $bean, $tagList )
	{
		$tags = $this->extractTagsIfNeeded( $tagList );

		if ( $tagList === FALSE ) {
			return;
		}

		foreach ( $tags as $tag ) {
			if ( !$t = $this->findTagByTitle( $tag ) ) {
				$t        = $this->redbean->dispense( 'tag' );
				$t->title = $tag;

				$this->redbean->store( $t );
			}

			$this->associationManager->associate( $bean, $t );
		}
	}

	/**
	 * Returns all beans that have been tagged with one or more
	 * of the specified tags.
	 *
	 * Tag list can be either an array with tag names or a comma separated list
	 * of tag names.
	 *
	 * @param string       $beanType type of bean you are looking for
	 * @param array|string $tagList  list of tags to match
	 * @param string       $sql      additional SQL (use only for pagination)
	 * @param array        $bindings bindings
	 *
	 * @return array
	 */
	public function tagged( $beanType, $tagList, $sql = '', $bindings = array() )
	{
		$tags       = $this->extractTagsIfNeeded( $tagList );
		$records    = $this->toolbox->getWriter()->queryTagged( $beanType, $tags, FALSE, $sql, $bindings );

		return $this->redbean->convertToBeans( $beanType, $records );
	}

	/**
	 * Returns all beans that have been tagged with ALL of the tags given.
	 *
	 * Tag list can be either an array with tag names or a comma separated list
	 * of tag names.
	 *
	 * @param string       $beanType type of bean you are looking for
	 * @param array|string $tagList  list of tags to match
	 *
	 * @return array
	 */
	public function taggedAll( $beanType, $tagList, $sql = '', $bindings = array() )
	{
		$tags  = $this->extractTagsIfNeeded( $tagList );
		$records    = $this->toolbox->getWriter()->queryTagged( $beanType, $tags, TRUE, $sql, $bindings );

		return $this->redbean->convertToBeans( $beanType, $records );
	}
}
}

namespace RedBeanPHP {

use RedBeanPHP\ToolBox as ToolBox;
use RedBeanPHP\OODBBean as OODBBean;

/**
 * Label Maker.
 * Makes so-called label beans.
 * A label is a bean with only an id, type and name property.
 * Labels can be used to create simple entities like categories, tags or enums.
 * This service class provides convenience methods to deal with this kind of
 * beans.
 *
 * @file       RedBeanPHP/LabelMaker.php
 * @author     Gabor de Mooij and the RedBeanPHP Community
 * @license    BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class LabelMaker
{
	/**
	 * @var ToolBox
	 */
	protected $toolbox;

	/**
	 * Constructor.
	 *
	 * @param ToolBox $toolbox
	 */
	public function __construct( ToolBox $toolbox )
	{
		$this->toolbox = $toolbox;
	}

	/**
	 * A label is a bean with only an id, type and name property.
	 * This function will dispense beans for all entries in the array. The
	 * values of the array will be assigned to the name property of each
	 * individual bean.
	 *
	 * <code>
	 * $people = R::dispenseLabels( 'person', [ 'Santa', 'Claus' ] );
	 * </code>
	 *
	 * @param string $type   type of beans you would like to have
	 * @param array  $labels list of labels, names for each bean
	 *
	 * @return array
	 */
	public function dispenseLabels( $type, $labels )
	{
		$labelBeans = array();
		foreach ( $labels as $label ) {
			$labelBean       = $this->toolbox->getRedBean()->dispense( $type );
			$labelBean->name = $label;
			$labelBeans[]    = $labelBean;
		}

		return $labelBeans;
	}

	/**
	 * Gathers labels from beans. This function loops through the beans,
	 * collects the value of the name property for each individual bean
	 * and stores the names in a new array. The array then gets sorted using the
	 * default sort function of PHP (sort).
	 *
	 * Usage:
	 *
	 * <code>
	 * $o1->name = 'hamburger';
	 * $o2->name = 'pizza';
	 * implode( ',', R::gatherLabels( [ $o1, $o2 ] ) ); //hamburger,pizza
	 * </code>
	 *
	 * Note that the return value is an array of strings, not beans.
	 *
	 * @param array $beans list of beans to loop through
	 *
	 * @return array
	 */
	public function gatherLabels( $beans )
	{
		$labels = array();

		foreach ( $beans as $bean ) {
			$labels[] = $bean->name;
		}

		sort( $labels );

		return $labels;
	}

	/**
	 * Fetches an ENUM from the database and creates it if necessary.
	 * An ENUM has the following format:
	 *
	 * <code>
	 * ENUM:VALUE
	 * </code>
	 *
	 * If you pass 'ENUM' only, this method will return an array of its
	 * values:
	 *
	 * <code>
	 * implode( ',', R::gatherLabels( R::enum( 'flavour' ) ) ) //'BANANA,MOCCA'
	 * </code>
	 *
	 * If you pass 'ENUM:VALUE' this method will return the specified enum bean
	 * and create it in the database if it does not exist yet:
	 *
	 * <code>
	 * $bananaFlavour = R::enum( 'flavour:banana' );
	 * $bananaFlavour->name;
	 * </code>
	 *
	 * So you can use this method to set an ENUM value in a bean:
	 *
	 * <code>
	 * $shake->flavour = R::enum( 'flavour:banana' );
	 * </code>
	 *
	 * the property flavour now contains the enum bean, a parent bean.
	 * In the database, flavour_id will point to the flavour record with name 'banana'.
	 *
	 * @param string $enum ENUM specification for label
	 *
	 * @return array|OODBBean
	 */
	public function enum( $enum )
	{
		$oodb = $this->toolbox->getRedBean();

		if ( strpos( $enum, ':' ) === FALSE ) {
			$type  = $enum;
			$value = FALSE;
		} else {
			list( $type, $value ) = explode( ':', $enum );
			$value                = preg_replace( '/\W+/', '_', strtoupper( trim( $value ) ) );
		}

		/**
		 * We use simply find here, we could use inspect() in fluid mode etc,
		 * but this would be useless. At first sight it looks clean, you could even
		 * bake this into find(), however, find not only has to deal with the primary
		 * search type, people can also include references in the SQL part, so avoiding
		 * find failures does not matter, this is still the quickest way making use
		 * of existing functionality.
		 *
		 * @note There seems to be a bug in XDebug v2.3.2 causing suppressed
		 * exceptions like these to surface anyway, to prevent this use:
		 *
		 * "xdebug.default_enable = 0"
		 *
		 *  Also see Github Issue #464
		 */
		$values = $oodb->find( $type );

		if ( $value === FALSE ) {
			return $values;
		}

		foreach( $values as $enumItem ) {
				if ( $enumItem->name === $value ) return $enumItem;
		}

		$newEnumItems = $this->dispenseLabels( $type, array( $value ) );
		$newEnumItem  = reset( $newEnumItems );

		$oodb->store( $newEnumItem );

		return $newEnumItem;
	}
}
}

namespace RedBeanPHP {

use RedBeanPHP\QueryWriter as QueryWriter;
use RedBeanPHP\Adapter\DBAdapter as DBAdapter;
use RedBeanPHP\RedException\SQL as SQLException;
use RedBeanPHP\Logger as Logger;
use RedBeanPHP\Logger\RDefault as RDefault;
use RedBeanPHP\Logger\RDefault\Debug as Debug;
use RedBeanPHP\Adapter as Adapter;
use RedBeanPHP\QueryWriter\AQueryWriter as AQueryWriter;
use RedBeanPHP\RedException as RedException;
use RedBeanPHP\BeanHelper\SimpleFacadeBeanHelper as SimpleFacadeBeanHelper;
use RedBeanPHP\Driver\RPDO as RPDO;
use RedBeanPHP\Util\MultiLoader as MultiLoader;
use RedBeanPHP\Util\Transaction as Transaction;
use RedBeanPHP\Util\Dump as Dump;
use RedBeanPHP\Util\DispenseHelper as DispenseHelper;
use RedBeanPHP\Util\ArrayTool as ArrayTool;

/**
 * RedBean Facade
 *
 * Version Information
 * RedBean Version @version 4.3
 *
 * This class hides the object landscape of
 * RedBeanPHP behind a single letter class providing
 * almost all functionality with simple static calls.
 *
 * @file    RedBeanPHP/Facade.php
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Facade
{
	/**
	 * RedBeanPHP version constant.
	 */
	const C_REDBEANPHP_VERSION = '4.3';

	/**
	 * @var ToolBox
	 */
	public static $toolbox;

	/**
	 * @var OODB
	 */
	private static $redbean;

	/**
	 * @var QueryWriter
	 */
	private static $writer;

	/**
	 * @var DBAdapter
	 */
	private static $adapter;

	/**
	 * @var AssociationManager
	 */
	private static $associationManager;

	/**
	 * @var TagManager
	 */
	private static $tagManager;

	/**
	 * @var DuplicationManager
	 */
	private static $duplicationManager;

	/**
	 * @var LabelMaker
	 */
	private static $labelMaker;

	/**
	 * @var Finder
	 */
	private static $finder;

	/**
	 * @var Logger
	 */
	private static $logger;

	/**
	 * @var array
	 */
	private static $plugins = array();

	/**
	 * @var string
	 */
	private static $exportCaseStyle = 'default';

	/**
	 * Not in use (backward compatibility SQLHelper)
	 */
	public static $f;

	/**
	 * @var string
	 */
	public static $currentDB = '';

	/**
	 * @var array
	 */
	public static $toolboxes = array();

	/**
	 * Internal Query function, executes the desired query. Used by
	 * all facade query functions. This keeps things DRY.
	 *
	 * @param string $method   desired query method (i.e. 'cell', 'col', 'exec' etc..)
	 * @param string $sql      the sql you want to execute
	 * @param array  $bindings array of values to be bound to query statement
	 *
	 * @return array
	 */
	private static function query( $method, $sql, $bindings )
	{
		if ( !self::$redbean->isFrozen() ) {
			try {
				$rs = Facade::$adapter->$method( $sql, $bindings );
			} catch ( SQLException $exception ) {
				if ( self::$writer->sqlStateIn( $exception->getSQLState(),
					array(
						QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN,
						QueryWriter::C_SQLSTATE_NO_SUCH_TABLE )
					)
				) {
					return ( $method === 'getCell' ) ? NULL : array();
				} else {
					throw $exception;
				}
			}

			return $rs;
		} else {
			return Facade::$adapter->$method( $sql, $bindings );
		}
	}

	/**
	 * Returns the RedBeanPHP version string.
	 * The RedBeanPHP version string always has the same format "X.Y"
	 * where X is the major version number and Y is the minor version number.
	 * Point releases are not mentioned in the version string.
	 *
	 * @return string
	 */
	public static function getVersion()
	{
		return self::C_REDBEANPHP_VERSION;
	}

	/**
	 * Tests the connection.
	 * Returns TRUE if connection has been established and
	 * FALSE otherwise.
	 *
	 * @return boolean
	 */
	public static function testConnection()
	{
		if ( !isset( self::$adapter ) ) return FALSE;

		$database = self::$adapter->getDatabase();
		try {
			@$database->connect();
		} catch ( \Exception $e ) {}
		return $database->isConnected();
	}

	/**
	 * Kickstarts redbean for you. This method should be called before you start using
	 * RedBean. The Setup() method can be called without any arguments, in this case it will
	 * try to create a SQLite database in /tmp called red.db (this only works on UNIX-like systems).
	 *
	 * @param string  $dsn      Database connection string
	 * @param string  $username Username for database
	 * @param string  $password Password for database
	 * @param boolean $frozen   TRUE if you want to setup in frozen mode
	 *
	 * @return ToolBox
	 */
	public static function setup( $dsn = NULL, $username = NULL, $password = NULL, $frozen = FALSE )
	{
		if ( is_null( $dsn ) ) {
			$dsn = 'sqlite:/' . sys_get_temp_dir() . '/red.db';
		}

		self::addDatabase( 'default', $dsn, $username, $password, $frozen );
		self::selectDatabase( 'default' );

		return self::$toolbox;
	}

	/**
	 * Toggles Narrow Field Mode.
	 * See documentation in QueryWriter.
	 *
	 * @param boolean $mode TRUE = Narrow Field Mode
	 *
	 * @return void
	 */
	public static function setNarrowFieldMode( $mode )
	{
		AQueryWriter::setNarrowFieldMode( $mode );
	}

	/**
	 * Wraps a transaction around a closure or string callback.
	 * If an Exception is thrown inside, the operation is automatically rolled back.
	 * If no Exception happens, it commits automatically.
	 * It also supports (simulated) nested transactions (that is useful when
	 * you have many methods that needs transactions but are unaware of
	 * each other).
	 *
	 * Example:
	 *
	 * <code>
	 * $from = 1;
	 * $to = 2;
	 * $amount = 300;
	 *
	 * R::transaction(function() use($from, $to, $amount)
	 * {
	 *   $accountFrom = R::load('account', $from);
	 *   $accountTo = R::load('account', $to);
	 *   $accountFrom->money -= $amount;
	 *   $accountTo->money += $amount;
	 *   R::store($accountFrom);
	 *   R::store($accountTo);
	 * });
	 * </code>
	 *
	 * @param callable $callback Closure (or other callable) with the transaction logic
	 *
	 * @return mixed
	 */
	public static function transaction( $callback )
	{
		return Transaction::transaction( self::$adapter, $callback );
	}

	/**
	 * Adds a database to the facade, afterwards you can select the database using
	 * selectDatabase($key), where $key is the name you assigned to this database.
	 *
	 * Usage:
	 *
	 * <code>
	 * R::addDatabase( 'database-1', 'sqlite:/tmp/db1.txt' );
	 * R::selectDatabase( 'database-1' ); //to select database again
	 * </code>
	 *
	 * This method allows you to dynamically add (and select) new databases
	 * to the facade. Adding a database with the same key will cause an exception.
	 *
	 * @param string      $key    ID for the database
	 * @param string      $dsn    DSN for the database
	 * @param string      $user   user for connection
	 * @param NULL|string $pass   password for connection
	 * @param bool        $frozen whether this database is frozen or not
	 *
	 * @return void
	 */
	public static function addDatabase( $key, $dsn, $user = NULL, $pass = NULL, $frozen = FALSE )
	{
		if ( isset( self::$toolboxes[$key] ) ) {
			throw new RedException( 'A database has already been specified for this key.' );
		}

		if ( is_object($dsn) ) {
			$db  = new RPDO( $dsn );
			$dbType = $db->getDatabaseType();
		} else {
			$db = new RPDO( $dsn, $user, $pass, TRUE );
			$dbType = substr( $dsn, 0, strpos( $dsn, ':' ) );
		}

		$adapter = new DBAdapter( $db );

		$writers = array(
			'pgsql'  => 'PostgreSQL',
			'sqlite' => 'SQLiteT',
			'cubrid' => 'CUBRID',
			'mysql'  => 'MySQL',
			'sqlsrv' => 'SQLServer',
		);

		$wkey = trim( strtolower( $dbType ) );
		if ( !isset( $writers[$wkey] ) ) {
			$wkey = preg_replace( '/\W/', '' , $wkey );
			throw new RedException( 'Unsupported database ('.$wkey.').' );
		}
		$writerClass = '\\RedBeanPHP\\QueryWriter\\'.$writers[$wkey];
		$writer      = new $writerClass( $adapter );
		$redbean     = new OODB( $writer, $frozen );

		self::$toolboxes[$key] = new ToolBox( $redbean, $adapter, $writer );
	}

	/**
	 * Determines whether a database identified with the specified key has
	 * already been added to the facade. This function will return TRUE
	 * if the database indicated by the key is available and FALSE otherwise.
	 *
	 * @param string $key the key/name of the database to check for
	 *
	 * @return boolean
	 */
	public static function hasDatabase( $key )
	{
		return ( isset( self::$toolboxes[$key] ) );
	}

	/**
	 * Selects a different database for the Facade to work with.
	 * If you use the R::setup() you don't need this method. This method is meant
	 * for multiple database setups. This method selects the database identified by the
	 * database ID ($key). Use addDatabase() to add a new database, which in turn
	 * can be selected using selectDatabase(). If you use R::setup(), the resulting
	 * database will be stored under key 'default', to switch (back) to this database
	 * use R::selectDatabase( 'default' ). This method returns TRUE if the database has been
	 * switched and FALSE otherwise (for instance if you already using the specified database).
	 *
	 * @param  string $key Key of the database to select
	 *
	 * @return boolean
	 */
	public static function selectDatabase( $key )
	{
		if ( self::$currentDB === $key ) {
			return FALSE;
		}

		if ( !isset( self::$toolboxes[$key] ) ) {
			throw new RedException( 'Database not found in registry. Add database using R::addDatabase().' );
		}

		self::configureFacadeWithToolbox( self::$toolboxes[$key] );
		self::$currentDB = $key;

		return TRUE;
	}

	/**
	 * Toggles DEBUG mode.
	 * In Debug mode all SQL that happens under the hood will
	 * be printed to the screen and/or logged.
	 * If no database connection has been configured using R::setup() or
	 * R::selectDatabase() this method will throw an exception.
	 *
	 * There are 2 debug styles:
	 *
	 * Classic: separate parameter bindings, explicit and complete but less readable
	 * Fancy:   interpersed bindings, truncates large strings, highlighted schema changes
	 *
	 * Fancy style is more readable but sometimes incomplete.
	 *
	 * The first parameter turns debugging ON or OFF.
	 * The second parameter indicates the mode of operation:
	 *
	 * 0 Log and write to STDOUT classic style (default)
	 * 1 Log only, class style
	 * 2 Log and write to STDOUT fancy style
	 * 3 Log only, fancy style
	 *
	 * This function always returns the logger instance created to generate the
	 * debug messages.
	 *
	 * @param boolean $tf   debug mode (TRUE or FALSE)
	 * @param integer $mode mode of operation
	 *
	 * @return RDefault
	 * @throws RedException
	 */
	public static function debug( $tf = TRUE, $mode = 0 )
	{
		if ($mode > 1) {
			$mode -= 2;
			$logger = new Debug;
		} else {
			$logger = new RDefault;
		}

		if ( !isset( self::$adapter ) ) {
			throw new RedException( 'Use R::setup() first.' );
		}
		$logger->setMode($mode);
		self::$adapter->getDatabase()->setDebugMode( $tf, $logger );

		return $logger;
	}

	/**
	 * Turns on the fancy debugger.
	 * In 'fancy' mode the debugger will output queries with bound
	 * parameters inside the SQL itself. This method has been added to
	 * offer a convenient way to activate the fancy debugger system
	 * in one call.
	 *
	 * @param boolean $toggle TRUE to activate debugger and select 'fancy' mode
	 *
	 * @return void
	 */
	public static function fancyDebug( $toggle = TRUE )
	{
		self::debug( $toggle, 2 );
	}

	/**
	* Inspects the database schema. If you pass the type of a bean this
	* method will return the fields of its table in the database.
	* The keys of this array will be the field names and the values will be
	* the column types used to store their values.
	* If no type is passed, this method returns a list of all tables in the database.
	*
	* @param string $type Type of bean (i.e. table) you want to inspect
	*
	* @return array
	*/
	public static function inspect( $type = NULL )
	{
		return ($type === NULL) ? self::$writer->getTables() : self::$writer->getColumns( $type );
	}

	/**
	 * Stores a bean in the database. This method takes a
	 * OODBBean Bean Object $bean and stores it
	 * in the database. If the database schema is not compatible
	 * with this bean and RedBean runs in fluid mode the schema
	 * will be altered to store the bean correctly.
	 * If the database schema is not compatible with this bean and
	 * RedBean runs in frozen mode it will throw an exception.
	 * This function returns the primary key ID of the inserted
	 * bean.
	 *
	 * The return value is an integer if possible. If it is not possible to
	 * represent the value as an integer a string will be returned.
	 *
	 * @param OODBBean|SimpleModel $bean bean to store
	 *
	 * @return integer|string
	 */
	public static function store( $bean )
	{
		return self::$redbean->store( $bean );
	}

	/**
	 * Toggles fluid or frozen mode. In fluid mode the database
	 * structure is adjusted to accomodate your objects. In frozen mode
	 * this is not the case.
	 *
	 * You can also pass an array containing a selection of frozen types.
	 * Let's call this chilly mode, it's just like fluid mode except that
	 * certain types (i.e. tables) aren't touched.
	 *
	 * @param boolean|array $trueFalse
	 */
	public static function freeze( $tf = TRUE )
	{
		self::$redbean->freeze( $tf );
	}

	/**
	 * Loads multiple types of beans with the same ID.
	 * This might look like a strange method, however it can be useful
	 * for loading a one-to-one relation.
	 *
	 * Usage:
	 * list( $author, $bio ) = R::loadMulti( 'author, bio', $id );
	 *
	 * @param string|array $types the set of types to load at once
	 * @param mixed        $id    the common ID
	 *
	 * @return OODBBean
	 */
	public static function loadMulti( $types, $id )
	{
		return MultiLoader::load( self::$redbean, $types, $id );
	}

	/**
	 * Loads a bean from the object database.
	 * It searches for a OODBBean Bean Object in the
	 * database. It does not matter how this bean has been stored.
	 * RedBean uses the primary key ID $id and the string $type
	 * to find the bean. The $type specifies what kind of bean you
	 * are looking for; this is the same type as used with the
	 * dispense() function. If RedBean finds the bean it will return
	 * the OODB Bean object; if it cannot find the bean
	 * RedBean will return a new bean of type $type and with
	 * primary key ID 0. In the latter case it acts basically the
	 * same as dispense().
	 *
	 * Important note:
	 * If the bean cannot be found in the database a new bean of
	 * the specified type will be generated and returned.
	 *
	 * @param string  $type type of bean you want to load
	 * @param integer $id   ID of the bean you want to load
	 *
	 * @return OODBBean
	 */
	public static function load( $type, $id )
	{
		return self::$redbean->load( $type, $id );
	}

	/**
	 * Removes a bean from the database.
	 * This function will remove the specified OODBBean
	 * Bean Object from the database.
	 *
	 * This facade method also accepts a type-id combination,
	 * in the latter case this method will attempt to load the specified bean
	 * and THEN trash it.
	 *
	 * @param string|OODBBean|SimpleModel $bean bean you want to remove from database
	 * @param integer                     $id   ID if the bean to trash (optional, type-id variant only)
	 *
	 * @return void
	 */
	public static function trash( $beanOrType, $id = NULL )
	{
		if ( is_string( $beanOrType ) ) return self::trash( self::load( $beanOrType, $id ) );
		return self::$redbean->trash( $beanOrType );
	}

	/**
	 * Dispenses a new RedBean OODB Bean for use with
	 * the rest of the methods.
	 *
	 * @param string|array $typeOrBeanArray   type or bean array to import
	 * @param integer      $number            number of beans to dispense
	 * @param boolean      $alwaysReturnArray if TRUE always returns the result as an array
	 *
	 * @return array|OODBBean
	 */
	public static function dispense( $typeOrBeanArray, $num = 1, $alwaysReturnArray = FALSE )
	{
		return DispenseHelper::dispense( self::$redbean, $typeOrBeanArray, $num, $alwaysReturnArray );
	}

	/**
	 * Takes a comma separated list of bean types
	 * and dispenses these beans. For each type in the list
	 * you can specify the number of beans to be dispensed.
	 *
	 * Usage:
	 *
	 * <code>
	 * list( $book, $page, $text ) = R::dispenseAll( 'book,page,text' );
	 * </code>
	 *
	 * This will dispense a book, a page and a text. This way you can
	 * quickly dispense beans of various types in just one line of code.
	 *
	 * Usage:
	 *
	 * <code>
	 * list($book, $pages) = R::dispenseAll('book,page*100');
	 * </code>
	 *
	 * This returns an array with a book bean and then another array
	 * containing 100 page beans.
	 *
	 * @param string  $order      a description of the desired dispense order using the syntax above
	 * @param boolean $onlyArrays return only arrays even if amount < 2
	 *
	 * @return array
	 */
	public static function dispenseAll( $order, $onlyArrays = FALSE )
	{
		return DispenseHelper::dispenseAll( self::$redbean, $order, $onlyArrays );
	}

	/**
	 * Convience method. Tries to find beans of a certain type,
	 * if no beans are found, it dispenses a bean of that type.
	 * Note that this function always returns an array.
	 *
	 * @param  string $type     type of bean you are looking for
	 * @param  string $sql      SQL code for finding the bean
	 * @param  array  $bindings parameters to bind to SQL
	 *
	 * @return array
	 */
	public static function findOrDispense( $type, $sql = NULL, $bindings = array() )
	{
		return self::$finder->findOrDispense( $type, $sql, $bindings );
	}

	/**
	 * Same as findOrDispense but returns just one element.
	 *
	 * @param  string $type     type of bean you are looking for
	 * @param  string $sql      SQL code for finding the bean
	 * @param  array  $bindings parameters to bind to SQL
	 *
	 * @return OODBBean
	 */
	public static function findOneOrDispense( $type, $sql = NULL, $bindings = array() )
	{
		return reset( self::findOrDispense( $type, $sql, $bindings ) );
	}

	/**
	 * Finds a bean using a type and a where clause (SQL).
	 * As with most Query tools in RedBean you can provide values to
	 * be inserted in the SQL statement by populating the value
	 * array parameter; you can either use the question mark notation
	 * or the slot-notation (:keyname).
	 *
	 * @param string $type     the type of bean you are looking for
	 * @param string $sql      SQL query to find the desired bean, starting right after WHERE clause
	 * @param array  $bindings array of values to be bound to parameters in query
	 *
	 * @return array
	 */
	public static function find( $type, $sql = NULL, $bindings = array() )
	{
		return self::$finder->find( $type, $sql, $bindings );
	}

	/**
	 * @see Facade::find
	 *      The findAll() method differs from the find() method in that it does
	 *      not assume a WHERE-clause, so this is valid:
	 *
	 * R::findAll('person',' ORDER BY name DESC ');
	 *
	 * Your SQL does not have to start with a valid WHERE-clause condition.
	 *
	 * @param string $type     the type of bean you are looking for
	 * @param string $sql      SQL query to find the desired bean, starting right after WHERE clause
	 * @param array  $bindings array of values to be bound to parameters in query
	 *
	 * @return array
	 */
	public static function findAll( $type, $sql = NULL, $bindings = array() )
	{
		return self::$finder->find( $type, $sql, $bindings );
	}

	/**
	 * @see Facade::find
	 * The variation also exports the beans (i.e. it returns arrays).
	 *
	 * @param string $type     the type of bean you are looking for
	 * @param string $sql      SQL query to find the desired bean, starting right after WHERE clause
	 * @param array  $bindings array of values to be bound to parameters in query
	 *
	 * @return array
	 */
	public static function findAndExport( $type, $sql = NULL, $bindings = array() )
	{
		return self::$finder->findAndExport( $type, $sql, $bindings );
	}

	/**
	 * @see Facade::find
	 * This variation returns the first bean only.
	 *
	 * @param string $type     the type of bean you are looking for
	 * @param string $sql      SQL query to find the desired bean, starting right after WHERE clause
	 * @param array  $bindings array of values to be bound to parameters in query
	 *
	 * @return OODBBean
	 */
	public static function findOne( $type, $sql = NULL, $bindings = array() )
	{
		return self::$finder->findOne( $type, $sql, $bindings );
	}

	/**
	 * @see Facade::find
	 * This variation returns the last bean only.
	 *
	 * @param string $type     the type of bean you are looking for
	 * @param string $sql      SQL query to find the desired bean, starting right after WHERE clause
	 * @param array  $bindings array of values to be bound to parameters in query
	 *
	 * @return OODBBean
	 */
	public static function findLast( $type, $sql = NULL, $bindings = array() )
	{
		return self::$finder->findLast( $type, $sql, $bindings );
	}

	/**
	 * Finds a bean collection.
	 * Use this for large datasets.
	 *
	 * @param string $type     the type of bean you are looking for
	 * @param string $sql      SQL query to find the desired bean, starting right after WHERE clause
	 * @param array  $bindings array of values to be bound to parameters in query
	 *
	 * @return BeanCollection
	 */
	public static function findCollection( $type, $sql = NULL, $bindings = array() )
	{
		return self::$finder->findCollection( $type, $sql, $bindings );
	}

	/**
	 * Finds multiple types of beans at once and offers additional
	 * remapping functionality. This is a very powerful yet complex function.
	 * For details see Finder::findMulti().
	 *
	 * @see Finder::findMulti()
	 *
	 * @param array|string $types      a list of bean types to find
	 * @param string|array $sqlOrArr   SQL query string or result set array
	 * @param array        $bindings   SQL bindings
	 * @param array        $remappings an array of remapping arrays containing closures
	 *
	 * @return array
	 */
	public static function findMulti( $types, $sql, $bindings = array(), $remappings = array() )
	{
		return self::$finder->findMulti( $types, $sql, $bindings, $remappings );
	}

	/**
	 * Returns an array of beans. Pass a type and a series of ids and
	 * this method will bring you the corresponding beans.
	 *
	 * important note: Because this method loads beans using the load()
	 * function (but faster) it will return empty beans with ID 0 for
	 * every bean that could not be located. The resulting beans will have the
	 * passed IDs as their keys.
	 *
	 * @param string $type type of beans
	 * @param array  $ids  ids to load
	 *
	 * @return array
	 */
	public static function batch( $type, $ids )
	{
		return self::$redbean->batch( $type, $ids );
	}

	/**
	 * @see Facade::batch
	 *
	 * Alias for batch(). Batch method is older but since we added so-called *All
	 * methods like storeAll, trashAll, dispenseAll and findAll it seemed logical to
	 * improve the consistency of the Facade API and also add an alias for batch() called
	 * loadAll.
	 *
	 * @param string $type type of beans
	 * @param array  $ids  ids to load
	 *
	 * @return array
	 */
	public static function loadAll( $type, $ids )
	{
		return self::$redbean->batch( $type, $ids );
	}

	/**
	 * Convenience function to execute Queries directly.
	 * Executes SQL.
	 *
	 * @param string $sql       SQL query to execute
	 * @param array  $bindings  a list of values to be bound to query parameters
	 *
	 * @return integer
	 */
	public static function exec( $sql, $bindings = array() )
	{
		return self::query( 'exec', $sql, $bindings );
	}

	/**
	 * Convenience function to execute Queries directly.
	 * Executes SQL.
	 *
	 * @param string $sql      SQL query to execute
	 * @param array  $bindings a list of values to be bound to query parameters
	 *
	 * @return array
	 */
	public static function getAll( $sql, $bindings = array() )
	{
		return self::query( 'get', $sql, $bindings );
	}

	/**
	 * Convenience function to execute Queries directly.
	 * Executes SQL.
	 *
	 * @param string $sql      SQL query to execute
	 * @param array  $bindings a list of values to be bound to query parameters
	 *
	 * @return string
	 */
	public static function getCell( $sql, $bindings = array() )
	{
		return self::query( 'getCell', $sql, $bindings );
	}

	/**
	 * Convenience function to execute Queries directly.
	 * Executes SQL.
	 *
	 * @param string $sql      SQL query to execute
	 * @param array  $bindings a list of values to be bound to query parameters
	 *
	 * @return array
	 */
	public static function getRow( $sql, $bindings = array() )
	{
		return self::query( 'getRow', $sql, $bindings );
	}

	/**
	 * Convenience function to execute Queries directly.
	 * Executes SQL.
	 *
	 * @param string $sql      SQL query to execute
	 * @param array  $bindings a list of values to be bound to query parameters
	 *
	 * @return array
	 */
	public static function getCol( $sql, $bindings = array() )
	{
		return self::query( 'getCol', $sql, $bindings );
	}

	/**
	 * Convenience function to execute Queries directly.
	 * Executes SQL.
	 * Results will be returned as an associative array. The first
	 * column in the select clause will be used for the keys in this array and
	 * the second column will be used for the values. If only one column is
	 * selected in the query, both key and value of the array will have the
	 * value of this field for each row.
	 *
	 * @param string $sql      SQL query to execute
	 * @param array  $bindings a list of values to be bound to query parameters
	 *
	 * @return array
	 */
	public static function getAssoc( $sql, $bindings = array() )
	{
		return self::query( 'getAssoc', $sql, $bindings );
	}

	/**
	 * Convenience function to execute Queries directly.
	 * Executes SQL.
	 * Results will be returned as an associative array indexed by the first
	 * column in the select.
	 *
	 * @param string $sql      SQL query to execute
	 * @param array  $bindings a list of values to be bound to query parameters
	 *
	 * @return array
	 */
	public static function getAssocRow( $sql, $bindings = array() )
	{
		return self::query( 'getAssocRow', $sql, $bindings );
	}

	/**
	 * Returns the insert ID for databases that support/require this
	 * functionality. Alias for R::getAdapter()->getInsertID().
	 *
	 * @return mixed
	 */
	public static function getInsertID()
	{
		return self::$adapter->getInsertID();
	}

	/**
	 * Makes a copy of a bean. This method makes a deep copy
	 * of the bean.The copy will have the following features.
	 * - All beans in own-lists will be duplicated as well
	 * - All references to shared beans will be copied but not the shared beans themselves
	 * - All references to parent objects (_id fields) will be copied but not the parents themselves
	 * In most cases this is the desired scenario for copying beans.
	 * This function uses a trail-array to prevent infinite recursion, if a recursive bean is found
	 * (i.e. one that already has been processed) the ID of the bean will be returned.
	 * This should not happen though.
	 *
	 * Note:
	 * This function does a reflectional database query so it may be slow.
	 *
	 * @deprecated
	 * This function is deprecated in favour of R::duplicate().
	 * This function has a confusing method signature, the R::duplicate() function
	 * only accepts two arguments: bean and filters.
	 *
	 * @param OODBBean $bean  bean to be copied
	 * @param array    $trail for internal usage, pass array()
	 * @param boolean  $pid   for internal usage
	 * @param array    $white white list filter with bean types to duplicate
	 *
	 * @return array
	 */
	public static function dup( $bean, $trail = array(), $pid = FALSE, $filters = array() )
	{
		self::$duplicationManager->setFilters( $filters );
		return self::$duplicationManager->dup( $bean, $trail, $pid );
	}

	/**
	 * Makes a deep copy of a bean. This method makes a deep copy
	 * of the bean.The copy will have the following:
	 *
	 * * All beans in own-lists will be duplicated as well
	 * * All references to shared beans will be copied but not the shared beans themselves
	 * * All references to parent objects (_id fields) will be copied but not the parents themselves
	 *
	 * In most cases this is the desired scenario for copying beans.
	 * This function uses a trail-array to prevent infinite recursion, if a recursive bean is found
	 * (i.e. one that already has been processed) the ID of the bean will be returned.
	 * This should not happen though.
	 *
	 * Note:
	 * This function does a reflectional database query so it may be slow.
	 *
	 * Note:
	 * This is a simplified version of the deprecated R::dup() function.
	 *
	 * @param OODBBean $bean  bean to be copied
	 * @param array    $white white list filter with bean types to duplicate
	 *
	 * @return array
	 */
	public static function duplicate( $bean, $filters = array() )
	{
		return self::dup( $bean, array(), FALSE, $filters );
	}

	/**
	 * Exports a collection of beans. Handy for XML/JSON exports with a
	 * Javascript framework like Dojo or ExtJS.
	 * What will be exported:
	 *
	 * * contents of the bean
	 * * all own bean lists (recursively)
	 * * all shared beans (not THEIR own lists)
	 *
	 * @param    array|OODBBean $beans   beans to be exported
	 * @param    boolean        $parents whether you want parent beans to be exported
	 * @param    array          $filters whitelist of types
	 *
	 * @return array
	 */
	public static function exportAll( $beans, $parents = FALSE, $filters = array())
	{
		return self::$duplicationManager->exportAll( $beans, $parents, $filters, self::$exportCaseStyle );
	}

	/**
	 * Selects case style for export.
	 * This will determine the case style for the keys of exported beans (see exportAll).
	 * The following options are accepted:
	 *
	 * * 'default' RedBeanPHP by default enforces Snake Case (i.e. book_id is_valid )
	 * * 'camel'   Camel Case   (i.e. bookId isValid   )
	 * * 'dolphin' Dolphin Case (i.e. bookID isValid   ) Like CamelCase but ID is written all uppercase
	 *
	 * @warning RedBeanPHP transforms camelCase to snake_case using a slightly different
	 * algorithm, it also converts isACL to is_acl (not is_a_c_l) and bookID to book_id.
	 * Due to information loss this cannot be corrected. However if you might try
	 * DolphinCase for IDs it takes into account the exception concerning IDs.
	 *
	 * @param string $caseStyle case style identifier
	 *
	 * @return void
	 */
	public static function useExportCase( $caseStyle = 'default' )
	{
		if ( !in_array( $caseStyle, array( 'default', 'camel', 'dolphin' ) ) ) throw new RedException( 'Invalid case selected.' );
		self::$exportCaseStyle = $caseStyle;
	}

	/**
	 * Converts a series of rows to beans.
	 * This method converts a series of rows to beans.
	 * The type of the desired output beans can be specified in the
	 * first parameter. The second parameter is meant for the database
	 * result rows.
	 *
	 * Usage:
	 *
	 * <code>
	 * $rows = R::getAll( 'SELECT * FROM ...' )
	 * $beans = R::convertToBeans( $rows );
	 * </code>
	 *
	 * As of version 4.3.2 you can specify a meta-mask.
	 * Data from columns with names starting with the value specified in the mask
	 * will be transferred to the meta section of a bean (under data.bundle).
	 *
	 * <code>
	 * $rows = R::getAll( 'SELECT FROM... COUNT(*) AS extra_count ...' );
	 * $beans = R::convertToBeans( $rows );
	 * $bean = reset( $beans );
	 * $data = $bean->getMeta( 'data.bundle' );
	 * $extra_count = $data['extra_count'];
	 * </code>
	 *
	 * @param string $type type of beans to produce
	 * @param array  $rows must contain an array of array
	 *
	 * @return array
	 */
	public static function convertToBeans( $type, $rows, $metamask = NULL )
	{
		return self::$redbean->convertToBeans( $type, $rows, $metamask );
	}

	/**
	 * Just like converToBeans, but for one bean.
	 * @see convertToBeans for more details.
	 *
	 * @param string $type type of beans to produce
	 * @param array  $row  one row from the database
	 *
	 * @return array
	 */
	public static function convertToBean( $type, $row, $metamask = NULL )
	{
		$beans = self::$redbean->convertToBeans( $type, array( $row ), $metamask );
		$bean  = reset( $beans );
		return $bean;
	}

	/**
	 * Part of RedBeanPHP Tagging API.
	 * Tests whether a bean has been associated with one ore more
	 * of the listed tags. If the third parameter is TRUE this method
	 * will return TRUE only if all tags that have been specified are indeed
	 * associated with the given bean, otherwise FALSE.
	 * If the third parameter is FALSE this
	 * method will return TRUE if one of the tags matches, FALSE if none
	 * match.
	 *
	 * @param  OODBBean $bean bean to check for tags
	 * @param  array    $tags list of tags
	 * @param  boolean  $all  whether they must all match or just some
	 *
	 * @return boolean
	 */
	public static function hasTag( $bean, $tags, $all = FALSE )
	{
		return self::$tagManager->hasTag( $bean, $tags, $all );
	}

	/**
	 * Part of RedBeanPHP Tagging API.
	 * Removes all specified tags from the bean. The tags specified in
	 * the second parameter will no longer be associated with the bean.
	 *
	 * @param  OODBBean $bean    tagged bean
	 * @param  array    $tagList list of tags (names)
	 *
	 * @return void
	 */
	public static function untag( $bean, $tagList )
	{
		self::$tagManager->untag( $bean, $tagList );
	}

	/**
	 * Part of RedBeanPHP Tagging API.
	 * Tags a bean or returns tags associated with a bean.
	 * If $tagList is NULL or omitted this method will return a
	 * comma separated list of tags associated with the bean provided.
	 * If $tagList is a comma separated list (string) of tags all tags will
	 * be associated with the bean.
	 * You may also pass an array instead of a string.
	 *
	 * @param OODBBean $bean    bean to tag
	 * @param mixed    $tagList tags to attach to the specified bean
	 *
	 * @return string
	 */
	public static function tag( OODBBean $bean, $tagList = NULL )
	{
		return self::$tagManager->tag( $bean, $tagList );
	}

	/**
	 * Part of RedBeanPHP Tagging API.
	 * Adds tags to a bean.
	 * If $tagList is a comma separated list of tags all tags will
	 * be associated with the bean.
	 * You may also pass an array instead of a string.
	 *
	 * @param OODBBean $bean    bean to tag
	 * @param array    $tagList list of tags to add to bean
	 *
	 * @return void
	 */
	public static function addTags( OODBBean $bean, $tagList )
	{
		self::$tagManager->addTags( $bean, $tagList );
	}

	/**
	 * Part of RedBeanPHP Tagging API.
	 * Returns all beans that have been tagged with one of the tags given.
	 *
	 * @param string $beanType type of bean you are looking for
	 * @param array  $tagList  list of tags to match
	 * @param string $sql      additional SQL query snippet
	 * @param array  $bindings a list of values to bind to the query parameters
	 *
	 * @return array
	 */
	public static function tagged( $beanType, $tagList, $sql = '', $bindings = array() )
	{
		return self::$tagManager->tagged( $beanType, $tagList, $sql, $bindings );
	}

	/**
	 * Part of RedBeanPHP Tagging API.
	 * Returns all beans that have been tagged with ALL of the tags given.
	 *
	 * @param string $beanType type of bean you are looking for
	 * @param array  $tagList  list of tags to match
	 * @param string $sql      additional SQL query snippet
	 * @param array  $bindings a list of values to bind to the query parameters
	 *
	 * @return array
	 */
	public static function taggedAll( $beanType, $tagList, $sql = '', $bindings = array() )
	{
		return self::$tagManager->taggedAll( $beanType, $tagList, $sql, $bindings );
	}

	/**
	 * Wipes all beans of type $beanType.
	 *
	 * @param string $beanType type of bean you want to destroy entirely
	 *
	 * @return boolean
	 */
	public static function wipe( $beanType )
	{
		return Facade::$redbean->wipe( $beanType );
	}

	/**
	 * Counts the number of beans of type $type.
	 * This method accepts a second argument to modify the count-query.
	 * A third argument can be used to provide bindings for the SQL snippet.
	 *
	 * @param string $type     type of bean we are looking for
	 * @param string $addSQL   additional SQL snippet
	 * @param array  $bindings parameters to bind to SQL
	 *
	 * @return integer
	 */
	public static function count( $type, $addSQL = '', $bindings = array() )
	{
		return Facade::$redbean->count( $type, $addSQL, $bindings );
	}

	/**
	 * Configures the facade, want to have a new Writer? A new Object Database or a new
	 * Adapter and you want it on-the-fly? Use this method to hot-swap your facade with a new
	 * toolbox.
	 *
	 * @param ToolBox $tb toolbox to configure facade with
	 *
	 * @return ToolBox
	 */
	public static function configureFacadeWithToolbox( ToolBox $tb )
	{
		$oldTools                 = self::$toolbox;
		self::$toolbox            = $tb;
		self::$writer             = self::$toolbox->getWriter();
		self::$adapter            = self::$toolbox->getDatabaseAdapter();
		self::$redbean            = self::$toolbox->getRedBean();
		self::$finder             = new Finder( self::$toolbox );
		self::$associationManager = new AssociationManager( self::$toolbox );
		self::$redbean->setAssociationManager( self::$associationManager );
		self::$labelMaker         = new LabelMaker( self::$toolbox );
		$helper                   = new SimpleModelHelper();
		$helper->attachEventListeners( self::$redbean );
		self::$redbean->setBeanHelper( new SimpleFacadeBeanHelper );
		self::$duplicationManager = new DuplicationManager( self::$toolbox );
		self::$tagManager         = new TagManager( self::$toolbox );
		return $oldTools;
	}

	/**
	 * Facade Convience method for adapter transaction system.
	 * Begins a transaction.
	 *
	 * @return bool
	 */
	public static function begin()
	{
		if ( !self::$redbean->isFrozen() ) return FALSE;
		self::$adapter->startTransaction();
		return TRUE;
	}

	/**
	 * Facade Convience method for adapter transaction system.
	 * Commits a transaction.
	 *
	 * @return bool
	 */
	public static function commit()
	{
		if ( !self::$redbean->isFrozen() ) return FALSE;
		self::$adapter->commit();
		return TRUE;
	}

	/**
	 * Facade Convience method for adapter transaction system.
	 * Rolls back a transaction.
	 *
	 * @return bool
	 */
	public static function rollback()
	{
		if ( !self::$redbean->isFrozen() ) return FALSE;
		self::$adapter->rollback();
		return TRUE;
	}

	/**
	 * Returns a list of columns. Format of this array:
	 * array( fieldname => type )
	 * Note that this method only works in fluid mode because it might be
	 * quite heavy on production servers!
	 *
	 * @param  string $table name of the table (not type) you want to get columns of
	 *
	 * @return array
	 */
	public static function getColumns( $table )
	{
		return self::$writer->getColumns( $table );
	}

	/**
	 * Generates question mark slots for an array of values.
	 *
	 * @param array  $array array to generate question mark slots for
	 *
	 * @return string
	 */
	public static function genSlots( $array, $template = NULL )
	{
		return ArrayTool::genSlots( $array, $template );
	}

	/**
	 * Flattens a multi dimensional bindings array for use with genSlots().
	 *
	 * @param array $array array to flatten
	 *
	 * @return array
	 */
	public static function flat( $array, $result = array() )
	{
		return ArrayTool::flat( $array, $result );
	}

	/**
	 * Nukes the entire database.
	 * This will remove all schema structures from the database.
	 * Only works in fluid mode. Be careful with this method.
	 *
	 * @warning dangerous method, will remove all tables, columns etc.
	 *
	 * @return void
	 */
	public static function nuke()
	{
		if ( !self::$redbean->isFrozen() ) {
			self::$writer->wipeAll();
		}
	}

	/**
	 * Short hand function to store a set of beans at once, IDs will be
	 * returned as an array. For information please consult the R::store()
	 * function.
	 * A loop saver.
	 *
	 * @param array $beans list of beans to be stored
	 *
	 * @return array
	 */
	public static function storeAll( $beans )
	{
		$ids = array();
		foreach ( $beans as $bean ) {
			$ids[] = self::store( $bean );
		}
		return $ids;
	}

	/**
	 * Short hand function to trash a set of beans at once.
	 * For information please consult the R::trash() function.
	 * A loop saver.
	 *
	 * @param array $beans list of beans to be trashed
	 *
	 * @return void
	 */
	public static function trashAll( $beans )
	{
		foreach ( $beans as $bean ) {
			self::trash( $bean );
		}
	}

	/**
	 * Toggles Writer Cache.
	 * Turns the Writer Cache on or off. The Writer Cache is a simple
	 * query based caching system that may improve performance without the need
	 * for cache management. This caching system will cache non-modifying queries
	 * that are marked with special SQL comments. As soon as a non-marked query
	 * gets executed the cache will be flushed. Only non-modifying select queries
	 * have been marked therefore this mechanism is a rather safe way of caching, requiring
	 * no explicit flushes or reloads. Of course this does not apply if you intend to test
	 * or simulate concurrent querying.
	 *
	 * @param boolean $yesNo TRUE to enable cache, FALSE to disable cache
	 *
	 * @return void
	 */
	public static function useWriterCache( $yesNo )
	{
		self::getWriter()->setUseCache( $yesNo );
	}

	/**
	 * A label is a bean with only an id, type and name property.
	 * This function will dispense beans for all entries in the array. The
	 * values of the array will be assigned to the name property of each
	 * individual bean.
	 *
	 * @param string $type   type of beans you would like to have
	 * @param array  $labels list of labels, names for each bean
	 *
	 * @return array
	 */
	public static function dispenseLabels( $type, $labels )
	{
		return self::$labelMaker->dispenseLabels( $type, $labels );
	}

	/**
	 * Generates and returns an ENUM value. This is how RedBeanPHP handles ENUMs.
	 * Either returns a (newly created) bean respresenting the desired ENUM
	 * value or returns a list of all enums for the type.
	 *
	 * To obtain (and add if necessary) an ENUM value:
	 *
	 * <code>
	 * $tea->flavour = R::enum( 'flavour:apple' );
	 * </code>
	 *
	 * Returns a bean of type 'flavour' with  name = apple.
	 * This will add a bean with property name (set to APPLE) to the database
	 * if it does not exist yet.
	 *
	 * To obtain all flavours:
	 *
	 * <code>
	 * R::enum('flavour');
	 * </code>
	 *
	 * To get a list of all flavour names:
	 *
	 * <code>
	 * R::gatherLabels( R::enum( 'flavour' ) );
	 * </code>
	 *
	 * @param string $enum either type or type-value
	 *
	 * @return array|OODBBean
	 */
	public static function enum( $enum )
	{
		return self::$labelMaker->enum( $enum );
	}

	/**
	 * Gathers labels from beans. This function loops through the beans,
	 * collects the values of the name properties of each individual bean
	 * and stores the names in a new array. The array then gets sorted using the
	 * default sort function of PHP (sort).
	 *
	 * @param array $beans list of beans to loop
	 *
	 * @return array
	 */
	public static function gatherLabels( $beans )
	{
		return self::$labelMaker->gatherLabels( $beans );
	}

	/**
	 * Closes the database connection.
	 *
	 * @return void
	 */
	public static function close()
	{
		if ( isset( self::$adapter ) ) {
			self::$adapter->close();
		}
	}

	/**
	 * Simple convenience function, returns ISO date formatted representation
	 * of $time.
	 *
	 * @param mixed $time UNIX timestamp
	 *
	 * @return string
	 */
	public static function isoDate( $time = NULL )
	{
		if ( !$time ) {
			$time = time();
		}

		return @date( 'Y-m-d', $time );
	}

	/**
	 * Simple convenience function, returns ISO date time
	 * formatted representation
	 * of $time.
	 *
	 * @param mixed $time UNIX timestamp
	 *
	 * @return string
	 */
	public static function isoDateTime( $time = NULL )
	{
		if ( !$time ) $time = time();
		return @date( 'Y-m-d H:i:s', $time );
	}

	/**
	 * Optional accessor for neat code.
	 * Sets the database adapter you want to use.
	 *
	 * @param Adapter $adapter Database Adapter for facade to use
	 *
	 * @return void
	 */
	public static function setDatabaseAdapter( Adapter $adapter )
	{
		self::$adapter = $adapter;
	}

	/**
	 * Optional accessor for neat code.
	 * Sets the database adapter you want to use.
	 *
	 * @param QueryWriter $writer Query Writer instance for facade to use
	 *
	 * @return void
	 */
	public static function setWriter( QueryWriter $writer )
	{
		self::$writer = $writer;
	}

	/**
	 * Optional accessor for neat code.
	 * Sets the database adapter you want to use.
	 *
	 * @param OODB $redbean Object Database for facade to use
	 */
	public static function setRedBean( OODB $redbean )
	{
		self::$redbean = $redbean;
	}

	/**
	 * Optional accessor for neat code.
	 * Sets the database adapter you want to use.
	 *
	 * @return DBAdapter
	 */
	public static function getDatabaseAdapter()
	{
		return self::$adapter;
	}

	/**
	 * In case you use PDO (which is recommended and the default but not mandatory, hence
	 * the database adapter), you can use this method to obtain the PDO object directly.
	 * This is a convenience method, it will do the same as:
	 *
	 * <code>
	 * R::getDatabaseAdapter()->getDatabase()->getPDO();
	 * </code>
	 *
	 * If the PDO object could not be found, for whatever reason, this method
	 * will return NULL instead.
	 *
	 * @return NULL|PDO
	 */
	public static function getPDO()
	{
		$databaseAdapter = self::getDatabaseAdapter();
		if ( is_null( $databaseAdapter ) ) return NULL;
		$database = $databaseAdapter->getDatabase();
		if ( is_null( $database ) ) return NULL;
		if ( !method_exists( $database, 'getPDO' ) ) return NULL;
		return $database->getPDO();
	}

	/**
	 * Returns the current duplication manager instance.
	 *
	 * @return DuplicationManager
	 */
	public static function getDuplicationManager()
	{
		return self::$duplicationManager;
	}

	/**
	 * Optional accessor for neat code.
	 * Sets the database adapter you want to use.
	 *
	 * @return QueryWriter
	 */
	public static function getWriter()
	{
		return self::$writer;
	}

	/**
	 * Optional accessor for neat code.
	 * Sets the database adapter you want to use.
	 *
	 * @return OODB
	 */
	public static function getRedBean()
	{
		return self::$redbean;
	}

	/**
	 * Returns the toolbox currently used by the facade.
	 * To set the toolbox use R::setup() or R::configureFacadeWithToolbox().
	 * To create a toolbox use Setup::kickstart(). Or create a manual
	 * toolbox using the ToolBox class.
	 *
	 * @return ToolBox
	 */
	public static function getToolBox()
	{
		return self::$toolbox;
	}

	/**
	 * Mostly for internal use, but might be handy
	 * for some users.
	 * This returns all the components of the currently
	 * selected toolbox.
	 *
	 * Returns the components in the following order:
	 *
	 * # OODB instance (getRedBean())
	 * # Database Adapter
	 * # Query Writer
	 * # Toolbox itself
	 *
	 * @return array
	 */
	public static function getExtractedToolbox()
	{
		return array( self::$redbean, self::$adapter, self::$writer, self::$toolbox );
	}

	/**
	 * Facade method for AQueryWriter::renameAssociation()
	 *
	 * @param string|array $from
	 * @param string       $to
	 *
	 * @return void
	 */
	public static function renameAssociation( $from, $to = NULL )
	{
		AQueryWriter::renameAssociation( $from, $to );
	}

	/**
	 * Little helper method for Resty Bean Can server and others.
	 * Takes an array of beans and exports each bean.
	 * Unlike exportAll this method does not recurse into own lists
	 * and shared lists, the beans are exported as-is, only loaded lists
	 * are exported.
	 *
	 * @param array $beans beans
	 *
	 * @return array
	 */
	public static function beansToArray( $beans )
	{
		$list = array();
		foreach( $beans as $bean ) $list[] = $bean->export();
		return $list;
	}

	/**
	 * Sets the error mode for FUSE.
	 * What to do if a FUSE model method does not exist?
	 * You can set the following options:
	 *
	 * * OODBBean::C_ERR_IGNORE (default), ignores the call, returns NULL
	 * * OODBBean::C_ERR_LOG, logs the incident using error_log
	 * * OODBBean::C_ERR_NOTICE, triggers a E_USER_NOTICE
	 * * OODBBean::C_ERR_WARN, triggers a E_USER_WARNING
	 * * OODBBean::C_ERR_EXCEPTION, throws an exception
	 * * OODBBean::C_ERR_FUNC, allows you to specify a custom handler (function)
	 * * OODBBean::C_ERR_FATAL, triggers a E_USER_ERROR
	 *
	 * <code>
	 * Custom handler method signature: handler( array (
	 * 	'message' => string
	 * 	'bean' => OODBBean
	 * 	'method' => string
	 * ) )
	 * </code>
	 *
	 * This method returns the old mode and handler as an array.
	 *
	 * @param integer       $mode mode, determines how to handle errors
	 * @param callable|NULL $func custom handler (if applicable)
	 *
	 * @return array
	 */
	public static function setErrorHandlingFUSE( $mode, $func = NULL )
	{
		return OODBBean::setErrorHandlingFUSE( $mode, $func );
	}

	/**
	 * Simple but effective debug function.
	 * Given a one or more beans this method will
	 * return an array containing first part of the string
	 * representation of each item in the array.
	 *
	 * @param OODBBean|array $data either a bean or an array of beans
	 *
	 * @return array
	 */
	public static function dump( $data )
	{
		return Dump::dump( $data );
	}

	/**
	 * Binds an SQL function to a column.
	 * This method can be used to setup a decode/encode scheme or
	 * perform UUID insertion. This method is especially useful for handling
	 * MySQL spatial columns, because they need to be processed first using
	 * the asText/GeomFromText functions.
	 *
	 * Example:
	 *
	 * <code>
	 * R::bindFunc( 'read', 'location.point', 'asText' );
	 * R::bindFunc( 'write', 'location.point', 'GeomFromText' );
	 * </code>
	 *
	 * Passing NULL as the function will reset (clear) the function
	 * for this column/mode.
	 *
	 * @param string $mode     mode for function: i.e. read or write
	 * @param string $field    field (table.column) to bind function to
	 * @param string $function SQL function to bind to specified column
	 *
	 * @return void
	 */
	public static function bindFunc( $mode, $field, $function )
	{
		self::$redbean->bindFunc( $mode, $field, $function );
	}

	/**
	 * Sets global aliases.
	 * Registers a batch of aliases in one go. This works the same as
	 * fetchAs and setAutoResolve but explicitly. For instance if you register
	 * the alias 'cover' for 'page' a property containing a reference to a
	 * page bean called 'cover' will correctly return the page bean and not
	 * a (non-existant) cover bean.
	 *
	 * <code>
	 * R::aliases( array( 'cover' => 'page' ) );
	 * $book = R::dispense( 'book' );
	 * $page = R::dispense( 'page' );
	 * $book->cover = $page;
	 * R::store( $book );
	 * $book = $book->fresh();
	 * $cover = $book->cover;
	 * echo $cover->getMeta( 'type' ); //page
	 * </code>
	 *
	 * The format of the aliases registration array is:
	 *
	 * {alias} => {actual type}
	 *
	 * In the example above we use:
	 *
	 * cover => page
	 *
	 * From that point on, every bean reference to a cover
	 * will return a 'page' bean. Note that with autoResolve this
	 * feature along with fetchAs() is no longer very important, although
	 * relying on explicit aliases can be a bit faster.
	 *
	 * @param array $list list of global aliases to use
	 *
	 * @return void
	 */
	public static function aliases( $list )
	{
		OODBBean::aliases( $list );
	}

	/**
	 * Tries to find a bean matching a certain type and
	 * criteria set. If no beans are found a new bean
	 * will be created, the criteria will be imported into this
	 * bean and the bean will be stored and returned.
	 * If multiple beans match the criteria only the first one
	 * will be returned.
	 *
	 * @param string $type type of bean to search for
	 * @param array  $like criteria set describing the bean to search for
	 *
	 * @return OODBBean
	 */
	public static function findOrCreate( $type, $like = array() )
	{
		return self::$finder->findOrCreate( $type, $like );
	}

	/**
	 * Tries to find beans matching the specified type and
	 * criteria set.
	 *
	 * If the optional additional SQL snippet is a condition, it will
	 * be glued to the rest of the query using the AND operator.
	 *
	 * @param string $type type of bean to search for
	 * @param array  $like optional criteria set describing the bean to search for
	 * @param string $sql  optional additional SQL for sorting
	 *
	 * @return array
	 */
	public static function findLike( $type, $like = array(), $sql = '' )
	{
		return self::$finder->findLike( $type, $like, $sql );
	}

	/**
	 * Starts logging queries.
	 * Use this method to start logging SQL queries being
	 * executed by the adapter.
	 *
	 * @note you cannot use R::debug and R::startLogging
	 * at the same time because R::debug is essentially a
	 * special kind of logging.
	 *
	 * @return void
	 */
	public static function startLogging()
	{
		self::debug( TRUE, RDefault::C_LOGGER_ARRAY );
	}

	/**
	 * Stops logging, comfortable method to stop logging of queries.
	 *
	 * @return void
	 */
	public static function stopLogging()
	{
		self::debug( FALSE );
	}

	/**
	 * Returns the log entries written after the startLogging.
	 *
	 * @return array
	 */
	public static function getLogs()
	{
		return self::getLogger()->getLogs();
	}

	/**
	 * Resets the Query counter.
	 *
	 * @return integer
	 */
	public static function resetQueryCount()
	{
		self::$adapter->getDatabase()->resetCounter();
	}

	/**
	 * Returns the number of SQL queries processed.
	 *
	 * @return integer
	 */
	public static function getQueryCount()
	{
		return self::$adapter->getDatabase()->getQueryCount();
	}

	/**
	 * Returns the current logger instance being used by the
	 * database object.
	 *
	 * @return Logger
	 */
	public static function getLogger()
	{
		return self::$adapter->getDatabase()->getLogger();
	}

	/**
	 * Alias for setAutoResolve() method on OODBBean.
	 * Enables or disables auto-resolving fetch types.
	 * Auto-resolving aliased parent beans is convenient but can
	 * be slower and can create infinite recursion if you
	 * used aliases to break cyclic relations in your domain.
	 *
	 * @param boolean $automatic TRUE to enable automatic resolving aliased parents
	 *
	 * @return void
	 */
	public static function setAutoResolve( $automatic = TRUE )
	{
		OODBBean::setAutoResolve( (boolean) $automatic );
	}

	/**
	 * Dynamically extends the facade with a plugin.
	 * Using this method you can register your plugin with the facade and then
	 * use the plugin by invoking the name specified plugin name as a method on
	 * the facade.
	 *
	 * Usage:
	 *
	 * <code>
	 * R::ext( 'makeTea', function() { ... }  );
	 * </code>
	 *
	 * Now you can use your makeTea plugin like this:
	 *
	 * <code>
	 * R::makeTea();
	 * </code>
	 *
	 * @param string   $pluginName name of the method to call the plugin
	 * @param callable $callable   a PHP callable
	 *
	 * @return void
	 */
	public static function ext( $pluginName, $callable )
	{
		if ( !ctype_alnum( $pluginName ) ) {
			throw new RedException( 'Plugin name may only contain alphanumeric characters.' );
		}
		self::$plugins[$pluginName] = $callable;
	}

	/**
	 * Call static for use with dynamic plugins. This magic method will
	 * intercept static calls and route them to the specified plugin.
	 *
	 * @param string $pluginName name of the plugin
	 * @param array  $params     list of arguments to pass to plugin method
	 *
	 * @return mixed
	 */
	public static function __callStatic( $pluginName, $params )
	{
		if ( !ctype_alnum( $pluginName) ) {
			throw new RedException( 'Plugin name may only contain alphanumeric characters.' );
		}
		if ( !isset( self::$plugins[$pluginName] ) ) {
			throw new RedException( 'Plugin \''.$pluginName.'\' does not exist, add this plugin using: R::ext(\''.$pluginName.'\')' );
		}
		return call_user_func_array( self::$plugins[$pluginName], $params );
	}
}

}

namespace RedBeanPHP {

use RedBeanPHP\ToolBox as ToolBox;
use RedBeanPHP\AssociationManager as AssociationManager;
use RedBeanPHP\OODB as OODB;
use RedBeanPHP\OODBBean as OODBBean;
use RedBeanPHP\QueryWriter\AQueryWriter as AQueryWriter;

/**
 * Duplication Manager
 * The Duplication Manager creates deep copies from beans, this means
 * it can duplicate an entire bean hierarchy. You can use this feature to
 * implement versioning for instance. Because duplication and exporting are
 * closely related this class is also used to export beans recursively
 * (i.e. we make a duplicate and then convert to array). This class allows
 * you to tune the duplication process by specifying filters determining
 * which relations to take into account and by specifying tables
 * (in which case no reflective queries have to be issued thus improving
 * performance). This class also hosts the Camelfy function used to
 * reformat the keys of an array, this method is publicly available and
 * used internally by exportAll().
 *
 * @file    RedBeanPHP/DuplicationManager.php
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class DuplicationManager
{
	/**
	 * @var ToolBox
	 */
	protected $toolbox;

	/**
	 * @var AssociationManager
	 */
	protected $associationManager;

	/**
	 * @var OODB
	 */
	protected $redbean;

	/**
	 * @var array
	 */
	protected $tables = array();

	/**
	 * @var array
	 */
	protected $columns = array();

	/**
	 * @var array
	 */
	protected $filters = array();

	/**
	 * @var array
	 */
	protected $cacheTables = FALSE;

	/**
	 * Copies the shared beans in a bean, i.e. all the sharedBean-lists.
	 *
	 * @param OODBBean $copy   target bean to copy lists to
	 * @param string   $shared name of the shared list
	 * @param array    $beans  array with shared beans to copy
	 *
	 * @return void
	 */
	private function copySharedBeans( OODBBean $copy, $shared, $beans )
	{
		$copy->$shared = array();

		foreach ( $beans as $subBean ) {
			array_push( $copy->$shared, $subBean );
		}
	}

	/**
	 * Copies the own beans in a bean, i.e. all the ownBean-lists.
	 * Each bean in the own-list belongs exclusively to its owner so
	 * we need to invoke the duplicate method again to duplicate each bean here.
	 *
	 * @param OODBBean $copy        target bean to copy lists to
	 * @param string   $owned       name of the own list
	 * @param array    $beans       array with shared beans to copy
	 * @param array    $trail       array with former beans to detect recursion
	 * @param boolean  $preserveIDs TRUE means preserve IDs, for export only
	 *
	 * @return void
	 */
	private function copyOwnBeans( OODBBean $copy, $owned, $beans, $trail, $preserveIDs )
	{
		$copy->$owned = array();
		foreach ( $beans as $subBean ) {
			array_push( $copy->$owned, $this->duplicate( $subBean, $trail, $preserveIDs ) );
		}
	}

	/**
	 * Creates a copy of bean $bean and copies all primitive properties (not lists)
	 * and the parents beans to the newly created bean. Also sets the ID of the bean
	 * to 0.
	 *
	 * @param OODBBean $bean bean to copy
	 *
	 * @return OODBBean
	 */
	private function createCopy( OODBBean $bean )
	{
		$type = $bean->getMeta( 'type' );

		$copy = $this->redbean->dispense( $type );
		$copy->setMeta( 'sys.dup-from-id', $bean->id );
		$copy->setMeta( 'sys.old-id', $bean->id );
		$copy->importFrom( $bean );
		$copy->id = 0;

		return $copy;
	}

	/**
	 * Generates a key from the bean type and its ID and determines if the bean
	 * occurs in the trail, if not the bean will be added to the trail.
	 * Returns TRUE if the bean occurs in the trail and FALSE otherwise.
	 *
	 * @param array    $trail list of former beans
	 * @param OODBBean $bean  currently selected bean
	 *
	 * @return boolean
	 */
	private function inTrailOrAdd( &$trail, OODBBean $bean )
	{
		$type = $bean->getMeta( 'type' );
		$key  = $type . $bean->getID();

		if ( isset( $trail[$key] ) ) {
			return TRUE;
		}

		$trail[$key] = $bean;

		return FALSE;
	}

	/**
	 * Given the type name of a bean this method returns the canonical names
	 * of the own-list and the shared-list properties respectively.
	 * Returns a list with two elements: name of the own-list, and name
	 * of the shared list.
	 *
	 * @param string $typeName bean type name
	 *
	 * @return array
	 */
	private function getListNames( $typeName )
	{
		$owned  = 'own' . ucfirst( $typeName );
		$shared = 'shared' . ucfirst( $typeName );

		return array( $owned, $shared );
	}

	/**
	 * Determines whether the bean has an own list based on
	 * schema inspection from realtime schema or cache.
	 *
	 * @param string $type   bean type to get list for
	 * @param string $target type of list you want to detect
	 *
	 * @return boolean
	 */
	protected function hasOwnList( $type, $target )
	{
		return isset( $this->columns[$target][$type . '_id'] );
	}

	/**
	 * Determines whether the bea has a shared list based on
	 * schema inspection from realtime schema or cache.
	 *
	 * @param string $type   bean type to get list for
	 * @param string $target type of list you are looking for
	 *
	 * @return boolean
	 */
	protected function hasSharedList( $type, $target )
	{
		return in_array( AQueryWriter::getAssocTableFormat( array( $type, $target ) ), $this->tables );
	}

	/**
	 * @see DuplicationManager::dup
	 *
	 * @param OODBBean $bean        bean to be copied
	 * @param array    $trail       trail to prevent infinite loops
	 * @param boolean  $preserveIDs preserve IDs
	 *
	 * @return OODBBean
	 */
	protected function duplicate( OODBBean $bean, $trail = array(), $preserveIDs = FALSE )
	{
		if ( $this->inTrailOrAdd( $trail, $bean ) ) return $bean;

		$type = $bean->getMeta( 'type' );

		$copy = $this->createCopy( $bean );
		foreach ( $this->tables as $table ) {

			if ( !empty( $this->filters ) ) {
				if ( !in_array( $table, $this->filters ) ) continue;
			}

			list( $owned, $shared ) = $this->getListNames( $table );

			if ( $this->hasSharedList( $type, $table ) ) {
				if ( $beans = $bean->$shared ) {
					$this->copySharedBeans( $copy, $shared, $beans );
				}
			} elseif ( $this->hasOwnList( $type, $table ) ) {
				if ( $beans = $bean->$owned ) {
					$this->copyOwnBeans( $copy, $owned, $beans, $trail, $preserveIDs );
				}

				$copy->setMeta( 'sys.shadow.' . $owned, NULL );
			}

			$copy->setMeta( 'sys.shadow.' . $shared, NULL );
		}

		$copy->id = ( $preserveIDs ) ? $bean->id : $copy->id;

		return $copy;
	}

	/**
	 * Constructor,
	 * creates a new instance of DupManager.
	 *
	 * @param ToolBox $toolbox
	 */
	public function __construct( ToolBox $toolbox )
	{
		$this->toolbox            = $toolbox;
		$this->redbean            = $toolbox->getRedBean();
		$this->associationManager = $this->redbean->getAssociationManager();
	}

	/**
	 * Recursively turns the keys of an array into
	 * camelCase.
	 *
	 * @param array   $array       array to camelize
	 * @param boolean $dolphinMode whether you want the exception for IDs.
	 *
	 * @return array
	 */
	public function camelfy( $array, $dolphinMode = false ) {
		$newArray = array();
		foreach( $array as $key => $element ) {
			$newKey = preg_replace_callback( '/_(\w)/', function( &$matches ){
				return strtoupper( $matches[1] );
			}, $key);

			if ( $dolphinMode ) {
				$newKey = preg_replace( '/(\w)Id$/', '$1ID', $newKey );
			}

			$newArray[$newKey] = ( is_array($element) ) ? $this->camelfy( $element, $dolphinMode ) : $element;
		}
		return $newArray;
	}

	/**
	 * For better performance you can pass the tables in an array to this method.
	 * If the tables are available the duplication manager will not query them so
	 * this might be beneficial for performance.
	 *
	 * This method allows two array formats:
	 *
	 * <code>
	 * array( TABLE1, TABLE2 ... )
	 * </code>
	 *
	 * or
	 *
	 * <code>
	 * array( TABLE1 => array( COLUMN1, COLUMN2 ... ) ... )
	 * </code>
	 *
	 * @param array $tables a table cache array
	 *
	 * @return void
	 */
	public function setTables( $tables )
	{
		foreach ( $tables as $key => $value ) {
			if ( is_numeric( $key ) ) {
				$this->tables[] = $value;
			} else {
				$this->tables[]      = $key;
				$this->columns[$key] = $value;
			}
		}

		$this->cacheTables = TRUE;
	}

	/**
	 * Returns a schema array for cache.
	 * You can use the return value of this method as a cache,
	 * store it in RAM or on disk and pass it to setTables later.
	 *
	 * @return array
	 */
	public function getSchema()
	{
		return $this->columns;
	}

	/**
	 * Indicates whether you want the duplication manager to cache the database schema.
	 * If this flag is set to TRUE the duplication manager will query the database schema
	 * only once. Otherwise the duplicationmanager will, by default, query the schema
	 * every time a duplication action is performed (dup()).
	 *
	 * @param boolean $yesNo TRUE to use caching, FALSE otherwise
	 */
	public function setCacheTables( $yesNo )
	{
		$this->cacheTables = $yesNo;
	}

	/**
	 * A filter array is an array with table names.
	 * By setting a table filter you can make the duplication manager only take into account
	 * certain bean types. Other bean types will be ignored when exporting or making a
	 * deep copy. If no filters are set all types will be taking into account, this is
	 * the default behavior.
	 *
	 * @param array $filters list of tables to be filtered
	 *
	 * @return void
	 */
	public function setFilters( $filters )
	{
		if ( !is_array( $filters ) ) {
			$filters = array( $filters );
		}

		$this->filters = $filters;
	}

	/**
	 * Makes a copy of a bean. This method makes a deep copy
	 * of the bean.The copy will have the following features.
	 * - All beans in own-lists will be duplicated as well
	 * - All references to shared beans will be copied but not the shared beans themselves
	 * - All references to parent objects (_id fields) will be copied but not the parents themselves
	 * In most cases this is the desired scenario for copying beans.
	 * This function uses a trail-array to prevent infinite recursion, if a recursive bean is found
	 * (i.e. one that already has been processed) the ID of the bean will be returned.
	 * This should not happen though.
	 *
	 * Note:
	 * This function does a reflectional database query so it may be slow.
	 *
	 * Note:
	 * this function actually passes the arguments to a protected function called
	 * duplicate() that does all the work. This method takes care of creating a clone
	 * of the bean to avoid the bean getting tainted (triggering saving when storing it).
	 *
	 * @param OODBBean $bean        bean to be copied
	 * @param array    $trail       for internal usage, pass array()
	 * @param boolean  $preserveIDs for internal usage
	 *
	 * @return OODBBean
	 */
	public function dup( OODBBean $bean, $trail = array(), $preserveIDs = FALSE )
	{
		if ( !count( $this->tables ) ) {
			$this->tables = $this->toolbox->getWriter()->getTables();
		}

		if ( !count( $this->columns ) ) {
			foreach ( $this->tables as $table ) {
				$this->columns[$table] = $this->toolbox->getWriter()->getColumns( $table );
			}
		}

		$rs = $this->duplicate( ( clone $bean ), $trail, $preserveIDs );

		if ( !$this->cacheTables ) {
			$this->tables  = array();
			$this->columns = array();
		}

		return $rs;
	}

	/**
	 * Exports a collection of beans recursively.
	 * This method will export an array of beans in the first argument to a
	 * set of arrays. This can be used to send JSON or XML representations
	 * of bean hierarchies to the client.
	 *
	 * For every bean in the array this method will export:
	 *
	 * - contents of the bean
	 * - all own bean lists (recursively)
	 * - all shared beans (but not THEIR own lists)
	 *
	 * If the second parameter is set to TRUE the parents of the beans in the
	 * array will be exported as well (but not THEIR parents).
	 *
	 * The third parameter can be used to provide a white-list array
	 * for filtering. This is an array of strings representing type names,
	 * only the type names in the filter list will be exported.
	 *
	 * The fourth parameter can be used to change the keys of the resulting
	 * export arrays. The default mode is 'snake case' but this leaves the
	 * keys as-is, because 'snake' is the default case style used by
	 * RedBeanPHP in the database. You can set this to 'camel' for
	 * camel cased keys or 'dolphin' (same as camelcase but id will be
	 * converted to ID instead of Id).
	 *
	 * @param array|OODBBean $beans     beans to be exported
	 * @param boolean        $parents   also export parents
	 * @param array          $filters   only these types (whitelist)
	 * @param string         $caseStyle case style identifier
	 *
	 * @return array
	 */
	public function exportAll( $beans, $parents = FALSE, $filters = array(), $caseStyle = 'snake')
	{
		$array = array();

		if ( !is_array( $beans ) ) {
			$beans = array( $beans );
		}

		foreach ( $beans as $bean ) {
			$this->setFilters( $filters );

			$duplicate = $this->dup( $bean, array(), TRUE );

			$array[]   = $duplicate->export( FALSE, $parents, FALSE, $filters );
		}

		if ( $caseStyle === 'camel' ) $array = $this->camelfy( $array );
		if ( $caseStyle === 'dolphin' ) $array = $this->camelfy( $array, true );

		return $array;
	}
}
}

namespace RedBeanPHP\Util {

use RedBeanPHP\OODB as OODB;
use RedBeanPHP\OODBBean as OODBBean;
use RedBeanPHP\RedException as RedException;

/**
 * Array Tool Helper
 *
 * This code was originally part of the facade, however it has
 * been decided to remove unique features to service classes like
 * this to make them available to developers not using the facade class.
 *
 * This is a helper or service class containing frequently used
 * array functions for dealing with SQL queries.
 * 
 * @file    RedBeanPHP/Util/ArrayTool.php
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class ArrayTool
{
	/**
	 * Generates question mark slots for an array of values.
	 *
	 * @param array  $array array to generate question mark slots for
	 *
	 * @return string
	 */
	public static function genSlots( $array, $template = NULL )
	{
		$str = count( $array ) ? implode( ',', array_fill( 0, count( $array ), '?' ) ) : '';
		return ( is_null( $template ) ||  $str === '' ) ? $str : sprintf( $template, $str );
	}

	/**
	 * Flattens a multi dimensional bindings array for use with genSlots().
	 *
	 * @param array $array  array to flatten
	 * @param array $result result array parameter (for recursion)
	 *
	 * @return array
	 */
	public static function flat( $array, $result = array() )
	{		
		foreach( $array as $value ) {
			if ( is_array( $value ) ) $result = self::flat( $value, $result );
			else $result[] = $value;
		}
		return $result;
	}
}
}

namespace RedBeanPHP\Util {

use RedBeanPHP\OODB as OODB;
use RedBeanPHP\RedException as RedException;

/**
 * Dispense Helper
 *
 * A helper class containing a dispense utility.
 * 
 * @file    RedBeanPHP/Util/DispenseHelper.php
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class DispenseHelper
{
	/**
	 * Dispenses a new RedBean OODB Bean for use with
	 * the rest of the methods.
	 *
	 * @param OODB         $oodb              OODB
	 * @param string|array $typeOrBeanArray   type or bean array to import
	 * @param integer      $number            number of beans to dispense
	 * @param boolean	   $alwaysReturnArray if TRUE always returns the result as an array
	 *
	 * @return array|OODBBean
	 */
	public static function dispense( OODB $oodb, $typeOrBeanArray, $num = 1, $alwaysReturnArray = FALSE ) {

		if ( is_array($typeOrBeanArray) ) {

			if ( !isset( $typeOrBeanArray['_type'] ) ) {
				$list = array();
				foreach( $typeOrBeanArray as $beanArray ) {
					if ( 
						!( is_array( $beanArray ) 
						&& isset( $beanArray['_type'] ) ) ) {
						throw new RedException( 'Invalid Array Bean' );
					}
				}
				foreach( $typeOrBeanArray as $beanArray ) $list[] = self::dispense( $oodb, $beanArray );
				return $list;
			}

			$import = $typeOrBeanArray;
			$type = $import['_type'];
			unset( $import['_type'] );
		} else {
			$type = $typeOrBeanArray;
		}

		if ( !preg_match( '/^[a-z0-9]+$/', $type ) ) {
			throw new RedException( 'Invalid type: ' . $type );
		}

		$beanOrBeans = $oodb->dispense( $type, $num, $alwaysReturnArray );

		if ( isset( $import ) ) {
			$beanOrBeans->import( $import );
		}

		return $beanOrBeans;
	}
	
	
	/**
	 * Takes a comma separated list of bean types
	 * and dispenses these beans. For each type in the list
	 * you can specify the number of beans to be dispensed.
	 *
	 * Usage:
	 *
	 * <code>
	 * list( $book, $page, $text ) = R::dispenseAll( 'book,page,text' );
	 * </code>
	 *
	 * This will dispense a book, a page and a text. This way you can
	 * quickly dispense beans of various types in just one line of code.
	 *
	 * Usage:
	 *
	 * <code>
	 * list($book, $pages) = R::dispenseAll('book,page*100');
	 * </code>
	 *
	 * This returns an array with a book bean and then another array
	 * containing 100 page beans.
	 *
	 * @param OODB    $oodb       OODB
	 * @param string  $order      a description of the desired dispense order using the syntax above
	 * @param boolean $onlyArrays return only arrays even if amount < 2
	 *
	 * @return array
	 */
	public static function dispenseAll( OODB $oodb, $order, $onlyArrays = FALSE )
	{
		$list = array();

		foreach( explode( ',', $order ) as $order ) {
			if ( strpos( $order, '*' ) !== FALSE ) {
				list( $type, $amount ) = explode( '*', $order );
			} else {
				$type   = $order;
				$amount = 1;
			}

			$list[] = self::dispense( $oodb, $type, $amount, $onlyArrays );
		}

		return $list;
	}
}
}

namespace RedBeanPHP\Util {

use RedBeanPHP\OODB as OODB;
use RedBeanPHP\OODBBean as OODBBean;

/**
 * Dump helper
 *
 * This code was originally part of the facade, however it has
 * been decided to remove unique features to service classes like
 * this to make them available to developers not using the facade class.
 *
 * Dumps the contents of a bean in an array for
 * debugging purposes.
 * 
 * @file    RedBeanPHP/Util/Dump.php
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Dump
{
	/**
	 * Simple but effective debug function.
	 * Given a one or more beans this method will
	 * return an array containing first part of the string
	 * representation of each item in the array.
	 *
	 * @param OODBBean|array $data either a bean or an array of beans
	 *
	 * @return array
	 */
	public static function dump( $data )
	{
		$array = array();

		if ( $data instanceof OODBBean ) {
			$str = strval( $data );
			if (strlen($str) > 35) {
				$beanStr = substr( $str, 0, 35 ).'... ';
			} else {
				$beanStr = $str;
			}
			return $beanStr;
		}

		if ( is_array( $data ) ) {
			foreach( $data as $key => $item ) {
				$array[$key] = self::dump( $item );
			}
		}

		return $array;
	}
}
}

namespace RedBeanPHP\Util {

use RedBeanPHP\OODB as OODB;

/**
 * Multi Bean Loader Helper
 *
 * This code was originally part of the facade, however it has
 * been decided to remove unique features to service classes like
 * this to make them available to developers not using the facade class.
 *
 * This helper class offers limited support for one-to-one
 * relations by providing a service to load a set of beans
 * with differnt types and a common ID.
 *
 * @file    RedBeanPHP/Util/MultiLoader.php
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class MultiLoader
{
	/**
	 * Loads multiple types of beans with the same ID.
	 * This might look like a strange method, however it can be useful
	 * for loading a one-to-one relation.
	 *
	 * @param OODB         $oodb  OODB object
	 * @param string|array $types the set of types to load at once
	 * @param mixed        $id    the common ID
	 *
	 * @return OODBBean
	 */
	public static function load( OODB $oodb, $types, $id )
	{
		if ( is_string( $types ) ) {
			$types = explode( ',', $types );
		}

		if ( !is_array( $types ) ) {
			return array();
		}

		foreach ( $types as $k => $typeItem ) {
			$types[$k] = $oodb->load( $typeItem, $id );
		}

		return $types;
	}
}
}

namespace RedBeanPHP\Util {

use RedBeanPHP\OODB as OODB;
use RedBeanPHP\OODBBean as OODBBean;
use RedBeanPHP\RedException as RedException;
use RedBeanPHP\Adapter as Adapter;

/**
 * Transaction Helper
 *
 * This code was originally part of the facade, however it has
 * been decided to remove unique features to service classes like
 * this to make them available to developers not using the facade class.
 *
 * Database transaction helper. This is a convenience class
 * to perform a callback in a database transaction. This class
 * contains a method to wrap your callback in a transaction.
 *
 * @file    RedBeanPHP/Util/Transaction.php
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Transaction
{
	/**
	 * Wraps a transaction around a closure or string callback.
	 * If an Exception is thrown inside, the operation is automatically rolled back.
	 * If no Exception happens, it commits automatically.
	 * It also supports (simulated) nested transactions (that is useful when
	 * you have many methods that needs transactions but are unaware of
	 * each other).
	 *
	 * Example:
	 *
	 * <code>
	 * $from = 1;
	 * $to = 2;
	 * $amount = 300;
	 *
	 * R::transaction(function() use($from, $to, $amount)
	 * {
	 *   $accountFrom = R::load('account', $from);
	 *   $accountTo = R::load('account', $to);
	 *   $accountFrom->money -= $amount;
	 *   $accountTo->money += $amount;
	 *   R::store($accountFrom);
	 *   R::store($accountTo);
	 * });
	 * </code>
	 *
	 * @param Adapter  $adapter  Database Adapter providing transaction mechanisms.
	 * @param callable $callback Closure (or other callable) with the transaction logic
	 *
	 * @return mixed
	 */
	public static function transaction( Adapter $adapter, $callback )
	{
		if ( !is_callable( $callback ) ) {
			throw new RedException( 'R::transaction needs a valid callback.' );
		}

		static $depth = 0;
		$result = null;
		try {
			if ( $depth == 0 ) {
				$adapter->startTransaction();
			}
			$depth++;
			$result = call_user_func( $callback ); //maintain 5.2 compatibility
			$depth--;
			if ( $depth == 0 ) {
				$adapter->commit();
			}
		} catch ( \Exception $exception ) {
			$depth--;
			if ( $depth == 0 ) {
				$adapter->rollback();
			}
			throw $exception;
		}
		return $result;
	}
}
}

namespace RedBeanPHP {

/**
 * RedBean Plugin.
 * Marker interface for plugins.
 * Use this interface when defining new plugins, it's an
 * easy way for the rest of the application to recognize your
 * plugin. This plugin interface does not require you to
 * implement a specific API.
 *
 * @file    RedBean/Plugin.php
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
interface Plugin
{
}

;
}
namespace {

//make some classes available for backward compatibility
class RedBean_SimpleModel extends \RedBeanPHP\SimpleModel {};

if (!class_exists('R')) {
	class R extends \RedBeanPHP\Facade{};
}



/**
 * Support functions for RedBeanPHP.
 * Additional convenience shortcut functions for RedBeanPHP.
 *
 * @file    RedBeanPHP/Functions.php
 * @author  Gabor de Mooij and the RedBeanPHP community
 * @license BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */

/**
 * Convenience function for ENUM short syntax in queries.
 *
 * Usage:
 *
 * <code>
 * R::find( 'paint', ' color_id = ? ', [ EID('color:yellow') ] );
 * </code>
 *
 * If a function called EID() already exists you'll have to write this
 * wrapper yourself ;)
 *
 * @param string $enumName enum code as you would pass to R::enum()
 *
 * @return mixed
 */
if (!function_exists('EID')) {

	function EID($enumName)
	{
		return \RedBeanPHP\Facade::enum( $enumName )->id;
	}

}

/**
 * Prints the result of R::dump() to the screen using
 * print_r.
 *
 * @param mixed $data data to dump
 *
 * @return void
 */
if ( !function_exists( 'dmp' ) ) {

	function dmp( $list )
	{
		print_r( \RedBeanPHP\Facade::dump( $list ) );
	}
}

/**
 * Function alias for R::genSlots().
 */
if ( !function_exists( 'genslots' ) ) {

	function genslots( $slots, $tpl = NULL )
	{
		return \RedBeanPHP\Facade::genSlots( $slots, $tpl );
	}
}

/**
 * Function alias for R::flat().
 */
if ( !function_exists( 'array_flatten' ) ) {

	function array_flatten( $array )
	{
		return \RedBeanPHP\Facade::flat( $array );
	}
}


}
