<?php

namespace RedBeanPHP;

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

