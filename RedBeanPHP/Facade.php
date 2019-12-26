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
use RedBeanPHP\Util\QuickExport as QuickExport;
use RedBeanPHP\Util\MatchUp as MatchUp;
use RedBeanPHP\Util\Look as Look;
use RedBeanPHP\Util\Diff as Diff;
use RedBeanPHP\Util\Tree as Tree;
use RedBeanPHP\Util\Feature;

/**
 * RedBean Facade
 *
 * Version Information
 * RedBean Version @version 5.4
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
	const C_REDBEANPHP_VERSION = '5.4';

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
	 * @var Tree
	 */
	private static $tree;

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
	 * @var flag allows transactions through facade in fluid mode
	 */
	private static $allowFluidTransactions = FALSE;

	/**
	 * @var flag allows to unfreeze if needed with store(all)
	 */
	private static $allowHybridMode = FALSE;

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
					,$exception->getDriverDetails()
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
	 * Sets allow hybrid mode flag. In Hybrid mode (default off),
	 * store/storeAll take an extra argument to switch to fluid
	 * mode in case of an exception. You can use this to speed up
	 * fluid mode. This method returns the previous value of the
	 * flag.
	 *
	 * @param boolean $hybrid
	 */
	public static function setAllowHybridMode( $hybrid )
	{
		$old = self::$allowHybridMode;
		self::$allowHybridMode = $hybrid;
		return $old;
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
	 * Tests the database connection.
	 * Returns TRUE if connection has been established and
	 * FALSE otherwise. Suppresses any warnings that may
	 * occur during the testing process and catches all
	 * exceptions that might be thrown during the test.
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
	 * RedBeanPHP. The Setup() method can be called without any arguments, in this case it will
	 * try to create a SQLite database in /tmp called red.db (this only works on UNIX-like systems).
	 *
	 * Usage:
	 *
	 * <code>
	 * R::setup( 'mysql:host=localhost;dbname=mydatabase', 'dba', 'dbapassword' );
	 * </code>
	 *
	 * You can replace 'mysql:' with the name of the database you want to use.
	 * Possible values are:
	 *
	 * - pgsql  (PostgreSQL database)
	 * - sqlite (SQLite database)
	 * - mysql  (MySQL database)
	 * - mysql  (also for Maria database)
	 * - sqlsrv (MS SQL Server - community supported experimental driver)
	 * - CUBRID (CUBRID driver - basic support provided by Plugin)
	 *
	 * Note that setup() will not immediately establish a connection to the database.
	 * Instead, it will prepare the connection and connect 'lazily', i.e. the moment
	 * a connection is really required, for instance when attempting to load
	 * a bean.
	 *
	 * @param string  $dsn      Database connection string
	 * @param string  $username Username for database
	 * @param string  $password Password for database
	 * @param boolean $frozen   TRUE if you want to setup in frozen mode
	 *
	 * @return ToolBox
	 */
	public static function setup( $dsn = NULL, $username = NULL, $password = NULL, $frozen = FALSE, $partialBeans = FALSE )
	{
		if ( is_null( $dsn ) ) {
			$dsn = 'sqlite:/' . sys_get_temp_dir() . '/red.db';
		}

		self::addDatabase( 'default', $dsn, $username, $password, $frozen, $partialBeans );
		self::selectDatabase( 'default' );

		return self::$toolbox;
	}

	/**
	 * Toggles 'Narrow Field Mode'.
	 * In Narrow Field mode the queryRecord method will
	 * narrow its selection field to
	 *
	 * <code>
	 * SELECT {table}.*
	 * </code>
	 *
	 * instead of
	 *
	 * <code>
	 * SELECT *
	 * </code>
	 *
	 * This is a better way of querying because it allows
	 * more flexibility (for instance joins). However if you need
	 * the wide selector for backward compatibility; use this method
	 * to turn OFF Narrow Field Mode by passing FALSE.
	 * Default is TRUE.
	 *
	 * @param boolean $narrowField TRUE = Narrow Field FALSE = Wide Field
	 *
	 * @return void
	 */
	public static function setNarrowFieldMode( $mode )
	{
		AQueryWriter::setNarrowFieldMode( $mode );
	}

	/**
	 * Toggles fluid transactions. By default fluid transactions
	 * are not active. Starting, committing or rolling back a transaction
	 * through the facade in fluid mode will have no effect. If you wish
	 * to replace this standard portable behavor with behavior depending
	 * on how the used database platform handles fluid (DDL) transactions
	 * set this flag to TRUE.
	 *
	 * @param boolean $mode allow fluid transaction mode
	 *
	 * @return void
	 */
	public static function setAllowFluidTransactions( $mode )
	{
		self::$allowFluidTransactions = $mode;
	}

	/**
	 * Toggles support for IS-NULL-conditions.
	 * If IS-NULL-conditions are enabled condition arrays
	 * for functions including findLike() are treated so that
	 * 'field' => NULL will be interpreted as field IS NULL
	 * instead of being skipped. Returns the previous
	 * value of the flag.
	 *
	 * @param boolean $flag TRUE or FALSE
	 *
	 * @return boolean
	 */
	public static function useISNULLConditions( $mode )
	{
		self::getWriter()->flushCache(); /* otherwise same queries might fail (see Unit test XNull) */
		return AQueryWriter::useISNULLConditions( $mode );
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
	public static function addDatabase( $key, $dsn, $user = NULL, $pass = NULL, $frozen = FALSE, $partialBeans = FALSE )
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

		if ( $partialBeans ) {
			$redbean->getCurrentRepository()->usePartialBeans( $partialBeans );
		}

		self::$toolboxes[$key] = new ToolBox( $redbean, $adapter, $writer );
	}

	/**
	 * Sets PDO attributes for MySQL SSL connection.
	 *
	 * @param string $key  path client key i.e. '/etc/mysql/ssl/client-key.pem'
	 * @param string $cert path client cert i.e. '/etc/mysql/ssl/client-cert.pem'
	 * @param string $ca   path certifying agent certificate '/etc/mysql/ssl/ca-cert.pem'
	 * @param string $id   apply to toolbox (default = 'default')
	 */
	public static function useMysqlSSL( $key, $cert, $ca, $id = 'default' ) {
		$pdo = self::$toolboxes[$id]->getDatabaseAdapter()->getDatabase()->getPDO();
		$pdo->setAttribute( \PDO::MYSQL_ATTR_SSL_KEY,  $key);
		$pdo->setAttribute( \PDO::MYSQL_ATTR_SSL_CERT,  $cert);
		$pdo->setAttribute( \PDO::MYSQL_ATTR_SSL_CA,  $ca);
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
	public static function selectDatabase( $key, $force = FALSE )
	{
		if ( self::$currentDB === $key && !$force ) {
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
	 * Usage:
	 *
	 * <code>
	 * $post = R::dispense('post');
	 * $post->title = 'my post';
	 * $id = R::store( $post );
	 * $post = R::load( 'post', $id );
	 * R::trash( $post );
	 * </code>
	 *
	 * In the example above, we create a new bean of type 'post'.
	 * We then set the title of the bean to 'my post' and we
	 * store the bean. The store() method will return the primary
	 * key ID $id assigned by the database. We can now use this
	 * ID to load the bean from the database again and delete it.
	 *
	 * If the second parameter is set to TRUE and
	 * Hybrid mode is allowed (default OFF for novice), then RedBeanPHP
	 * will automatically temporarily switch to fluid mode to attempt to store the
	 * bean in case of an SQLException.
	 *
	 * @param OODBBean|SimpleModel $bean             bean to store
	 * @param boolean              $unfreezeIfNeeded retries in fluid mode in hybrid mode
	 *
	 * @return integer|string
	 */
	public static function store( $bean, $unfreezeIfNeeded = FALSE )
	{
		$result = NULL;
		try {
			$result = self::$redbean->store( $bean );
		} catch (SQLException $exception) {
			$wasFrozen = self::$redbean->isFrozen();
			if ( !self::$allowHybridMode || !$unfreezeIfNeeded ) throw $exception;
			self::freeze( FALSE );
			$result = self::$redbean->store( $bean );
			self::freeze( $wasFrozen );
		}
		return $result;
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
	 * @param boolean|array $tf mode of operation (TRUE means frozen)
	 */
	public static function freeze( $tf = TRUE )
	{
		self::$redbean->freeze( $tf );
	}

	/**
	 * Loads multiple types of beans with the same ID.
	 * This might look like a strange method, however it can be useful
	 * for loading a one-to-one relation. In a typical 1-1 relation,
	 * you have two records sharing the same primary key.
	 * RedBeanPHP has only limited support for 1-1 relations.
	 * In general it is recommended to use 1-N for this.
	 *
	 * Usage:
	 *
	 * <code>
	 * list( $author, $bio ) = R::loadMulti( 'author, bio', $id );
	 * </code>
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
	 * Usage:
	 *
	 * <code>
	 * $post = R::dispense('post');
	 * $post->title = 'my post';
	 * $id = R::store( $post );
	 * $post = R::load( 'post', $id );
	 * R::trash( $post );
	 * </code>
	 *
	 * In the example above, we create a new bean of type 'post'.
	 * We then set the title of the bean to 'my post' and we
	 * store the bean. The store() method will return the primary
	 * key ID $id assigned by the database. We can now use this
	 * ID to load the bean from the database again and delete it.
	 *
	 * @param string  $type    type of bean you want to load
	 * @param integer $id      ID of the bean you want to load
	 * @param string  $snippet string to use after select  (optional)
	 *
	 * @return OODBBean
	 */
	public static function load( $type, $id, $snippet = NULL )
	{
		if ( $snippet !== NULL ) self::$writer->setSQLSelectSnippet( $snippet );
		$bean = self::$redbean->load( $type, $id );
		return $bean;
	}

	/**
	 * Same as load, but selects the bean for update, thus locking the bean.
	 * This equals an SQL query like 'SELECT ... FROM ... FOR UPDATE'.
	 * Use this method if you want to load a bean you intend to UPDATE.
	 * This method should be used to 'LOCK a bean'.
	 *
	 * Usage:
	 *
	 * <code>
	 * $bean = R::loadForUpdate( 'bean', $id );
	 * ...update...
	 * R::store( $bean );
	 * </code>
	 *
	 * @param string  $type    type of bean you want to load
	 * @param integer $id      ID of the bean you want to load
	 *
	 * @return OODBBean
	 */
	public static function loadForUpdate( $type, $id )
	{
		return self::load( $type, $id, AQueryWriter::C_SELECT_SNIPPET_FOR_UPDATE );
	}

	/**
	 * Same as find(), but selects the beans for update, thus locking the beans.
	 * This equals an SQL query like 'SELECT ... FROM ... FOR UPDATE'.
	 * Use this method if you want to load a bean you intend to UPDATE.
	 * This method should be used to 'LOCK a bean'.
	 *
	 * Usage:
	 *
	 * <code>
	 * $bean = R::findForUpdate(
	 *    'bean',
	 *    ' title LIKE ? ',
	 *    array('title')
	 * );
	 * ...update...
	 * R::store( $bean );
	 * </code>
	 *
	 * @param string $type     the type of bean you are looking for
	 * @param string $sql      SQL query to find the desired bean, starting right after WHERE clause
	 * @param array  $bindings array of values to be bound to parameters in query
	 *
	 * @return OODBBean
	 */
	public static function findForUpdate( $type, $sql, $bindings = array() )
	{
		return self::find( $type, $sql, $bindings, AQueryWriter::C_SELECT_SNIPPET_FOR_UPDATE );
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
	 * Usage:
	 *
	 * <code>
	 * $post = R::dispense('post');
	 * $post->title = 'my post';
	 * $id = R::store( $post );
	 * $post = R::load( 'post', $id );
	 * R::trash( $post );
	 * </code>
	 *
	 * In the example above, we create a new bean of type 'post'.
	 * We then set the title of the bean to 'my post' and we
	 * store the bean. The store() method will return the primary
	 * key ID $id assigned by the database. We can now use this
	 * ID to load the bean from the database again and delete it.
	 *
	 * @param string|OODBBean|SimpleModel $beanOrType bean you want to remove from database
	 * @param integer                     $id         ID if the bean to trash (optional, type-id variant only)
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
	 * the rest of the methods. RedBeanPHP thinks in beans, the bean is the
	 * primary way to interact with RedBeanPHP and the database managed by
	 * RedBeanPHP. To load, store and delete data from the database using RedBeanPHP
	 * you exchange these RedBeanPHP OODB Beans. The only exception to this rule
	 * are the raw query methods like R::getCell() or R::exec() and so on.
	 * The dispense method is the 'preferred way' to create a new bean.
	 *
	 * Usage:
	 *
	 * <code>
	 * $book = R::dispense( 'book' );
	 * $book->title = 'My Book';
	 * R::store( $book );
	 * </code>
	 *
	 * This method can also be used to create an entire bean graph at once.
	 * Given an array with keys specifying the property names of the beans
	 * and a special _type key to indicate the type of bean, one can
	 * make the Dispense Helper generate an entire hierarchy of beans, including
	 * lists. To make dispense() generate a list, simply add a key like:
	 * ownXList or sharedXList where X is the type of beans it contains and
	 * a set its value to an array filled with arrays representing the beans.
	 * Note that, although the type may have been hinted at in the list name,
	 * you still have to specify a _type key for every bean array in the list.
	 * Note that, if you specify an array to generate a bean graph, the number
	 * parameter will be ignored.
	 *
	 * Usage:
	 *
	 * <code>
	 *  $book = R::dispense( [
	 *   '_type' => 'book',
	 *   'title'  => 'Gifted Programmers',
	 *   'author' => [ '_type' => 'author', 'name' => 'Xavier' ],
	 *   'ownPageList' => [ ['_type'=>'page', 'text' => '...'] ]
	 * ] );
	 * </code>
	 *
	 * @param string|array $typeOrBeanArray   type or bean array to import
	 * @param integer      $num               number of beans to dispense
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
		DispenseHelper::checkType( $type );
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
		DispenseHelper::checkType( $type );
		$arrayOfBeans = self::findOrDispense( $type, $sql, $bindings );
		return reset($arrayOfBeans);
	}

	/**
	 * Finds beans using a type and optional SQL statement.
	 * As with most Query tools in RedBean you can provide values to
	 * be inserted in the SQL statement by populating the value
	 * array parameter; you can either use the question mark notation
	 * or the slot-notation (:keyname).
	 *
	 * Your SQL does not have to start with a WHERE-clause condition.
	 *
	 * @param string $type     the type of bean you are looking for
	 * @param string $sql      SQL query to find the desired bean, starting right after WHERE clause
	 * @param array  $bindings array of values to be bound to parameters in query
	 * @param string $snippet  SQL snippet to include in query (for example: FOR UPDATE)
	 *
	 * @return array
	 */
	public static function find( $type, $sql = NULL, $bindings = array(), $snippet = NULL )
	{
		if ( $snippet !== NULL ) self::$writer->setSQLSelectSnippet( $snippet );
		return self::$finder->find( $type, $sql, $bindings );
	}

	/**
	 * Alias for find().
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
	public static function findAndExport( $type, $sql = NULL, $bindings = array() )
	{
		return self::$finder->findAndExport( $type, $sql, $bindings );
	}

	/**
	 * Like R::find() but returns the first bean only.
	 *
	 * @param string $type     the type of bean you are looking for
	 * @param string $sql      SQL query to find the desired bean, starting right after WHERE clause
	 * @param array  $bindings array of values to be bound to parameters in query
	 *
	 * @return OODBBean|NULL
	 */
	public static function findOne( $type, $sql = NULL, $bindings = array() )
	{
		return self::$finder->findOne( $type, $sql, $bindings );
	}

	/**
	 * @deprecated
	 *
	 * Like find() but returns the last bean of the result array.
	 * Opposite of Finder::findLast().
	 * If no beans are found, this method will return NULL.
	 *
	 * Please do not use this function, it is horribly ineffective.
	 * Instead use a reversed ORDER BY clause and a LIMIT 1 with R::findOne().
	 * This function should never be used and only remains for
	 * the sake of backward compatibility.
	 *
	 * @see Finder::find
	 *
	 * @param string $type     the type of bean you are looking for
	 * @param string $sql      SQL query to find the desired bean, starting right after WHERE clause
	 * @param array  $bindings values array of values to be bound to parameters in query
	 *
	 * @return OODBBean|NULL
	 */
	public static function findLast( $type, $sql = NULL, $bindings = array() )
	{
		return self::$finder->findLast( $type, $sql, $bindings );
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
	public static function findCollection( $type, $sql = NULL, $bindings = array() )
	{
		return self::$finder->findCollection( $type, $sql, $bindings );
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
	 * @param string|array $sql           an SQL query or an array of prefetched records
	 * @param array        $bindings      optional, bindings for SQL query
	 * @param array        $remappings    optional, an array of remapping arrays
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
	 * Convenience function to fire an SQL query using the RedBeanPHP
	 * database adapter. This method allows you to directly query the
	 * database without having to obtain an database adapter instance first.
	 * Executes the specified SQL query together with the specified
	 * parameter bindings and returns all rows
	 * and all columns.
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
	 * Convenience function to fire an SQL query using the RedBeanPHP
	 * database adapter. This method allows you to directly query the
	 * database without having to obtain an database adapter instance first.
	 * Executes the specified SQL query together with the specified
	 * parameter bindings and returns a single cell.
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
	 * Convenience function to fire an SQL query using the RedBeanPHP
	 * database adapter. This method allows you to directly query the
	 * database without having to obtain an database adapter instance first.
	 * Executes the specified SQL query together with the specified
	 * parameter bindings and returns a PDOCursor instance.
	 *
	 * @param string $sql      SQL query to execute
	 * @param array  $bindings a list of values to be bound to query parameters
	 *
	 * @return RedBeanPHP\Cursor\PDOCursor
	 */
	public static function getCursor( $sql, $bindings = array() )
	{
		return self::query( 'getCursor', $sql, $bindings );
	}

	/**
	 * Convenience function to fire an SQL query using the RedBeanPHP
	 * database adapter. This method allows you to directly query the
	 * database without having to obtain an database adapter instance first.
	 * Executes the specified SQL query together with the specified
	 * parameter bindings and returns a single row.
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
	 * Convenience function to fire an SQL query using the RedBeanPHP
	 * database adapter. This method allows you to directly query the
	 * database without having to obtain an database adapter instance first.
	 * Executes the specified SQL query together with the specified
	 * parameter bindings and returns a single column.
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
	 *Convenience function to fire an SQL query using the RedBeanPHP
	 * database adapter. This method allows you to directly query the
	 * database without having to obtain an database adapter instance first.
	 * Executes the specified SQL query together with the specified
	 * parameter bindings and returns an associative array.
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
	 * @param OODBBean $bean    bean to be copied
	 * @param array    $trail   for internal usage, pass array()
	 * @param boolean  $pid     for internal usage
	 * @param array    $filters white list filter with bean types to duplicate
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
	 * New in 4.3.2: meta mask. The meta mask is a special mask to send
	 * data from raw result rows to the meta store of the bean. This is
	 * useful for bundling additional information with custom queries.
	 * Values of every column whos name starts with $mask will be
	 * transferred to the meta section of the bean under key 'data.bundle'.
	 *
	 * @param string $type     type of beans to produce
	 * @param array  $rows     must contain an array of array
	 * @param string $metamask meta mask to apply (optional)
	 *
	 * @return array
	 */
	public static function convertToBeans( $type, $rows, $metamask = NULL )
	{
		return self::$redbean->convertToBeans( $type, $rows, $metamask );
	}

	/**
	 * Just like converToBeans, but for one bean.
	 *
	 * @param string $type      type of bean to produce
	 * @param array  $row       one row from the database
	 * @param string $metamask  metamask (see convertToBeans)
	 *
	 * @return OODBBean|NULL
	 */
	public static function convertToBean( $type, $row, $metamask = NULL )
	{
		if ( !count( $row ) ) return NULL;
		$beans = self::$redbean->convertToBeans( $type, array( $row ), $metamask );
		$bean  = reset( $beans );
		return $bean;
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
	 * Usage:
	 *
	 * <code>
	 * R::hasTag( $blog, 'horror,movie', TRUE );
	 * </code>
	 *
	 * The example above returns TRUE if the $blog bean has been tagged
	 * as BOTH horror and movie. If the post has only been tagged as 'movie'
	 * or 'horror' this operation will return FALSE because the third parameter
	 * has been set to TRUE.
	 *
	 * @param  OODBBean     $bean bean to check for tags
	 * @param  array|string $tags list of tags
	 * @param  boolean      $all  whether they must all match or just some
	 *
	 * @return boolean
	 */
	public static function hasTag( $bean, $tags, $all = FALSE )
	{
		return self::$tagManager->hasTag( $bean, $tags, $all );
	}

	/**
	 * Removes all specified tags from the bean. The tags specified in
	 * the second parameter will no longer be associated with the bean.
	 *
	 * Tag list can be either an array with tag names or a comma separated list
	 * of tag names.
	 *
	 * Usage:
	 *
	 * <code>
	 * R::untag( $blog, 'smart,interesting' );
	 * </code>
	 *
	 * In the example above, the $blog bean will no longer
	 * be associated with the tags 'smart' and 'interesting'.
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
	 * Tags a bean or returns tags associated with a bean.
	 * If $tagList is NULL or omitted this method will return a
	 * comma separated list of tags associated with the bean provided.
	 * If $tagList is a comma separated list (string) of tags all tags will
	 * be associated with the bean.
	 * You may also pass an array instead of a string.
	 *
	 * Usage:
	 *
	 * <code>
	 * R::tag( $meal, "TexMex,Mexican" );
	 * $tags = R::tag( $meal );
	 * </code>
	 *
	 * The first line in the example above will tag the $meal
	 * as 'TexMex' and 'Mexican Cuisine'. The second line will
	 * retrieve all tags attached to the meal object.
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
	 * Adds tags to a bean.
	 * If $tagList is a comma separated list of tags all tags will
	 * be associated with the bean.
	 * You may also pass an array instead of a string.
	 *
	 * Usage:
	 *
	 * <code>
	 * R::addTags( $blog, ["halloween"] );
	 * </code>
	 *
	 * The example adds the tag 'halloween' to the $blog
	 * bean.
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
	 * Returns all beans that have been tagged with one or more
	 * of the specified tags.
	 *
	 * Tag list can be either an array with tag names or a comma separated list
	 * of tag names.
	 *
	 * Usage:
	 *
	 * <code>
	 * $watchList = R::tagged(
	 *   'movie',
	 *   'horror,gothic',
	 *   ' ORDER BY movie.title DESC LIMIT ?',
	 *   [ 10 ]
	 * );
	 * </code>
	 *
	 * The example uses R::tagged() to find all movies that have been
	 * tagged as 'horror' or 'gothic', order them by title and limit
	 * the number of movies to be returned to 10.
	 *
	 * @param string       $beanType type of bean you are looking for
	 * @param array|string $tagList  list of tags to match
	 * @param string       $sql      additional SQL (use only for pagination)
	 * @param array        $bindings bindings
	 *
	 * @return array
	 */
	public static function tagged( $beanType, $tagList, $sql = '', $bindings = array() )
	{
		return self::$tagManager->tagged( $beanType, $tagList, $sql, $bindings );
	}

	/**
	 * Returns all beans that have been tagged with ALL of the tags given.
	 * This method works the same as R::tagged() except that this method only returns
	 * beans that have been tagged with all the specified labels.
	 *
	 * Tag list can be either an array with tag names or a comma separated list
	 * of tag names.
	 *
	 * Usage:
	 *
	 * <code>
	 * $watchList = R::taggedAll(
	 *    'movie',
	 *    [ 'gothic', 'short' ],
	 *    ' ORDER BY movie.id DESC LIMIT ? ',
	 *    [ 4 ]
	 * );
	 * </code>
	 *
	 * The example above returns at most 4 movies (due to the LIMIT clause in the SQL
	 * Query Snippet) that have been tagged as BOTH 'short' AND 'gothic'.
	 *
	 * @param string       $beanType type of bean you are looking for
	 * @param array|string $tagList  list of tags to match
	 * @param string       $sql      additional sql snippet
	 * @param array        $bindings bindings
	 *
	 * @return array
	 */
	public static function taggedAll( $beanType, $tagList, $sql = '', $bindings = array() )
	{
		return self::$tagManager->taggedAll( $beanType, $tagList, $sql, $bindings );
	}

	/**
	 * Same as taggedAll() but counts beans only (does not return beans).
	 *
	 * @see R::taggedAll
	 *
	 * @param string       $beanType type of bean you are looking for
	 * @param array|string $tagList  list of tags to match
	 * @param string       $sql      additional sql snippet
	 * @param array        $bindings bindings
	 *
	 * @return integer
	 */
	public static function countTaggedAll( $beanType, $tagList, $sql = '', $bindings = array() )
	{
		return self::$tagManager->countTaggedAll( $beanType, $tagList, $sql, $bindings );
	}

	/**
	 * Same as tagged() but counts beans only (does not return beans).
	 *
	 * @see R::tagged
	 *
	 * @param string       $beanType type of bean you are looking for
	 * @param array|string $tagList  list of tags to match
	 * @param string       $sql      additional sql snippet
	 * @param array        $bindings bindings
	 *
	 * @return integer
	 */
	public static function countTagged( $beanType, $tagList, $sql = '', $bindings = array() )
	{
		return self::$tagManager->countTagged( $beanType, $tagList, $sql, $bindings );
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
		self::$tree               = new Tree( self::$toolbox );
		self::$redbean->setAssociationManager( self::$associationManager );
		self::$labelMaker         = new LabelMaker( self::$toolbox );
		$helper                   = new SimpleModelHelper();
		$helper->attachEventListeners( self::$redbean );
		if (self::$redbean->getBeanHelper() == NULL) {
			self::$redbean->setBeanHelper( new SimpleFacadeBeanHelper );
		}
		self::$duplicationManager = new DuplicationManager( self::$toolbox );
		self::$tagManager         = new TagManager( self::$toolbox );
		return $oldTools;
	}

	/**
	 * Facade Convience method for adapter transaction system.
	 * Begins a transaction.
	 *
	 * Usage:
	 *
	 * <code>
	 * R::begin();
	 * try {
	 *  $bean1 = R::dispense( 'bean' );
	 *  R::store( $bean1 );
	 *  $bean2 = R::dispense( 'bean' );
	 *  R::store( $bean2 );
	 *  R::commit();
	 * } catch( \Exception $e ) {
	 *  R::rollback();
	 * }
	 * </code>
	 *
	 * The example above illustrates how transactions in RedBeanPHP are used.
	 * In this example 2 beans are stored or nothing is stored at all.
	 * It's not possible for this piece of code to store only half of the beans.
	 * If an exception occurs, the transaction gets rolled back and the database
	 * will be left 'untouched'.
	 *
	 * In fluid mode transactions will be ignored and all queries will
	 * be executed as-is because database schema changes will automatically
	 * trigger the transaction system to commit everything in some database
	 * systems. If you use a database that can handle DDL changes you might wish
	 * to use setAllowFluidTransactions(TRUE). If you do this, the behavior of
	 * this function in fluid mode will depend on the database platform used.
	 *
	 * @return bool
	 */
	public static function begin()
	{
		if ( !self::$allowFluidTransactions && !self::$redbean->isFrozen() ) return FALSE;
		self::$adapter->startTransaction();
		return TRUE;
	}

	/**
	 * Facade Convience method for adapter transaction system.
	 * Commits a transaction.
	 *
	 * Usage:
	 *
	 * <code>
	 * R::begin();
	 * try {
	 *  $bean1 = R::dispense( 'bean' );
	 *  R::store( $bean1 );
	 *  $bean2 = R::dispense( 'bean' );
	 *  R::store( $bean2 );
	 *  R::commit();
	 * } catch( \Exception $e ) {
	 *  R::rollback();
	 * }
	 * </code>
	 *
	 * The example above illustrates how transactions in RedBeanPHP are used.
	 * In this example 2 beans are stored or nothing is stored at all.
	 * It's not possible for this piece of code to store only half of the beans.
	 * If an exception occurs, the transaction gets rolled back and the database
	 * will be left 'untouched'.
	 *
	 * In fluid mode transactions will be ignored and all queries will
	 * be executed as-is because database schema changes will automatically
	 * trigger the transaction system to commit everything in some database
	 * systems. If you use a database that can handle DDL changes you might wish
	 * to use setAllowFluidTransactions(TRUE). If you do this, the behavior of
	 * this function in fluid mode will depend on the database platform used.
	 *
	 * @return bool
	 */
	public static function commit()
	{
		if ( !self::$allowFluidTransactions && !self::$redbean->isFrozen() ) return FALSE;
		self::$adapter->commit();
		return TRUE;
	}

	/**
	 * Facade Convience method for adapter transaction system.
	 * Rolls back a transaction.
	 *
	 * Usage:
	 *
	 * <code>
	 * R::begin();
	 * try {
	 *  $bean1 = R::dispense( 'bean' );
	 *  R::store( $bean1 );
	 *  $bean2 = R::dispense( 'bean' );
	 *  R::store( $bean2 );
	 *  R::commit();
	 * } catch( \Exception $e ) {
	 *  R::rollback();
	 * }
	 * </code>
	 *
	 * The example above illustrates how transactions in RedBeanPHP are used.
	 * In this example 2 beans are stored or nothing is stored at all.
	 * It's not possible for this piece of code to store only half of the beans.
	 * If an exception occurs, the transaction gets rolled back and the database
	 * will be left 'untouched'.
	 *
	 * In fluid mode transactions will be ignored and all queries will
	 * be executed as-is because database schema changes will automatically
	 * trigger the transaction system to commit everything in some database
	 * systems. If you use a database that can handle DDL changes you might wish
	 * to use setAllowFluidTransactions(TRUE). If you do this, the behavior of
	 * this function in fluid mode will depend on the database platform used.
	 *
	 * @return bool
	 */
	public static function rollback()
	{
		if ( !self::$allowFluidTransactions && !self::$redbean->isFrozen() ) return FALSE;
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
	 * Given an array and an optional template string this method
	 * will produce string containing parameter slots for use in
	 * an SQL query string.
	 *
	 * Usage:
	 *
	 * <code>
	 * R::genSlots( array( 'a', 'b' ) );
	 * </code>
	 *
	 * The statement in the example will produce the string:
	 * '?,?'.
	 *
	 * Another example, using a template string:
	 *
	 * <code>
	 * R::genSlots( array('a', 'b'), ' IN( %s ) ' );
	 * </code>
	 *
	 * The statement in the example will produce the string:
	 * ' IN( ?,? ) '.
	 *
	 * @param array  $array    array to generate question mark slots for
	 * @param string $template template to use
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
	 * Usage:
	 *
	 * <code>
	 * R::flat( array( 'a', array( 'b' ), 'c' ) );
	 * </code>
	 *
	 * produces an array like: [ 'a', 'b', 'c' ]
	 *
	 * @param array $array  array to flatten
	 * @param array $result result array parameter (for recursion)
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
	 * If the second parameter is set to TRUE and
	 * Hybrid mode is allowed (default OFF for novice), then RedBeanPHP
	 * will automatically temporarily switch to fluid mode to attempt to store the
	 * bean in case of an SQLException.
	 *
	 * @param array   $beans            list of beans to be stored
	 * @param boolean $unfreezeIfNeeded retries in fluid mode in hybrid mode
	 *
	 * @return array
	 */
	public static function storeAll( $beans, $unfreezeIfNeeded = FALSE )
	{
		$ids = array();
		foreach ( $beans as $bean ) {
			$ids[] = self::store( $bean, $unfreezeIfNeeded );
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
		$numberOfDeletion = 0;
		foreach ( $beans as $bean ) {
			$numberOfDeletion += self::trash( $bean );
		}
		return $numberOfDeletion;
	}

	/**
	 * Short hand function to trash a series of beans using
	 * only IDs. This function combines trashAll and batch loading
	 * in one call. Note that while this function accepts just
	 * bean IDs, the beans will still be loaded first. This is because
	 * the function still respects all the FUSE hooks that may have beeb
	 * associated with the domain logic associated with these beans.
	 * If you really want to delete just records from the database use
	 * a simple DELETE-FROM SQL query instead.
	 *
	 * @param string type  $type the bean type you wish to trash
	 * @param string array $ids  list of bean IDs
	 *
	 * @return void
	 */
	public static function trashBatch( $type, $ids )
	{
		self::trashAll( self::batch( $type, $ids ) );
	}

	/**
	 * Short hand function to find and trash beans.
	 * This function combines trashAll and find.
	 * Given a bean type, a query snippet and optionally some parameter
	 * bindings, this function will search for the beans described in the
	 * query and its parameters and then feed them to the trashAll function
	 * to be trashed.
	 *
	 * Note that while this function accepts just
	 * a bean type and query snippet, the beans will still be loaded first. This is because
	 * the function still respects all the FUSE hooks that may have been
	 * associated with the domain logic associated with these beans.
	 * If you really want to delete just records from the database use
	 * a simple DELETE-FROM SQL query instead.
	 *
	 * Returns the number of beans deleted.
	 *
	 * @param string $type       bean type to look for in database
	 * @param string $sqlSnippet an SQL query snippet
	 * @param array  $bindings   SQL parameter bindings
	 *
	 * @return int
	 */
	public static function hunt( $type, $sqlSnippet = NULL, $bindings = array() )
	{
		$numberOfTrashedBeans = 0;
		$beans = self::findCollection( $type, $sqlSnippet, $bindings );
		while( $bean = $beans->next() ) {
			self::trash( $bean );
			$numberOfTrashedBeans++;
		}
		return $numberOfTrashedBeans;
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
	 * Sets the database adapter you want to use.
	 * The database adapter manages the connection to the database
	 * and abstracts away database driver specific interfaces.
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
	 * Sets the Query Writer you want to use.
	 * The Query Writer writes and executes database queries using
	 * the database adapter. It turns RedBeanPHP 'commands' into
	 * database 'statements'.
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
	 * Sets the OODB you want to use.
	 * The RedBeanPHP Object oriented database is the main RedBeanPHP
	 * interface that allows you to store and retrieve RedBeanPHP
	 * objects (i.e. beans).
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
	 * Dumps bean data to array.
	 * Given a one or more beans this method will
	 * return an array containing first part of the string
	 * representation of each item in the array.
	 *
	 * Usage:
	 *
	 * <code>
	 * echo R::dump( $bean );
	 * </code>
	 *
	 * The example shows how to echo the result of a simple
	 * dump. This will print the string representation of the
	 * specified bean to the screen, limiting the output per bean
	 * to 35 characters to improve readability. Nested beans will
	 * also be dumped.
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
	public static function findOrCreate( $type, $like = array(), $sql = '' )
	{
		return self::$finder->findOrCreate( $type, $like, $sql = '' );
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
	 * @param array  $bindings bindings
	 *
	 * @return array
	 */
	public static function findLike( $type, $like = array(), $sql = '', $bindings = array() )
	{
		return self::$finder->findLike( $type, $like, $sql, $bindings );
	}

	/**
	 * Starts logging queries.
	 * Use this method to start logging SQL queries being
	 * executed by the adapter. Logging queries will not
	 * print them on the screen. Use R::getLogs() to
	 * retrieve the logs.
	 *
	 * Usage:
	 *
	 * <code>
	 * R::startLogging();
	 * R::store( R::dispense( 'book' ) );
	 * R::find('book', 'id > ?',[0]);
	 * $logs = R::getLogs();
	 * $count = count( $logs );
	 * print_r( $logs );
	 * R::stopLogging();
	 * </code>
	 *
	 * In the example above we start a logging session during
	 * which we store an empty bean of type book. To inspect the
	 * logs we invoke R::getLogs() after stopping the logging.
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
	 * Stops logging and flushes the logs,
	 * convient method to stop logging of queries.
	 * Use this method to stop logging SQL queries being
	 * executed by the adapter. Logging queries will not
	 * print them on the screen. Use R::getLogs() to
	 * retrieve the logs.
	 *
	 * <code>
	 * R::startLogging();
	 * R::store( R::dispense( 'book' ) );
	 * R::find('book', 'id > ?',[0]);
	 * $logs = R::getLogs();
	 * $count = count( $logs );
	 * print_r( $logs );
	 * R::stopLogging();
	 * </code>
	 *
	 * In the example above we start a logging session during
	 * which we store an empty bean of type book. To inspect the
	 * logs we invoke R::getLogs() after stopping the logging.
	 *
	 * @note you cannot use R::debug and R::startLogging
	 * at the same time because R::debug is essentially a
	 * special kind of logging.
	 *
	 * @note by stopping the logging you also flush the logs.
	 * Therefore, only stop logging AFTER you have obtained the
	 * query logs using R::getLogs()
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
	 * Use this method to obtain the query logs gathered
	 * by the logging mechanisms.
	 * Logging queries will not
	 * print them on the screen. Use R::getLogs() to
	 * retrieve the logs.
	 *
	 * <code>
	 * R::startLogging();
	 * R::store( R::dispense( 'book' ) );
	 * R::find('book', 'id > ?',[0]);
	 * $logs = R::getLogs();
	 * $count = count( $logs );
	 * print_r( $logs );
	 * R::stopLogging();
	 * </code>
	 *
	 * In the example above we start a logging session during
	 * which we store an empty bean of type book. To inspect the
	 * logs we invoke R::getLogs() after stopping the logging.
	 *
	 * The logs may look like:
	 *
	 * [1] => SELECT `book`.*  FROM `book`  WHERE id > ?  -- keep-cache
	 * [2] => array ( 0 => 0, )
	 * [3] => resultset: 1 rows
	 *
	 * Basically, element in the array is a log entry.
	 * Parameter bindings are  represented as nested arrays (see 2).
	 *
	 * @note you cannot use R::debug and R::startLogging
	 * at the same time because R::debug is essentially a
	 * special kind of logging.
	 *
	 * @note by stopping the logging you also flush the logs.
	 * Therefore, only stop logging AFTER you have obtained the
	 * query logs using R::getLogs()
	 *
	 * @return array
	 */
	public static function getLogs()
	{
		return self::getLogger()->getLogs();
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
	 * @return void
	 */
	public static function resetQueryCount()
	{
		self::$adapter->getDatabase()->resetCounter();
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
	 * Returns previous value of the flag.
	 *
	 * @param boolean $automatic TRUE to enable automatic resolving aliased parents
	 *
	 * @return boolean
	 */
	public static function setAutoResolve( $automatic = TRUE )
	{
		return OODBBean::setAutoResolve( (boolean) $automatic );
	}

	/**
	 * Toggles 'partial bean mode'. If this mode has been
	 * selected the repository will only update the fields of a bean that
	 * have been changed rather than the entire bean.
	 * Pass the value TRUE to select 'partial mode' for all beans.
	 * Pass the value FALSE to disable 'partial mode'.
	 * Pass an array of bean types if you wish to use partial mode only
	 * for some types.
	 * This method will return the previous value.
	 *
	 * @param boolean|array $yesNoBeans List of type names or 'all'
	 *
	 * @return mixed
	 */
	public static function usePartialBeans( $yesNoBeans )
	{
		return self::$redbean->getCurrentRepository()->usePartialBeans( $yesNoBeans );
	}

	/**
	 * Exposes the result of the specified SQL query as a CSV file.
	 *
	 * Usage:
	 *
	 * <code>
	 * R::csv( 'SELECT
	 *                 `name`,
	 *                  population
	 *          FROM city
	 *          WHERE region = :region ',
	 *          array( ':region' => 'Denmark' ),
	 *          array( 'city', 'population' ),
	 *          '/tmp/cities.csv'
	 * );
	 * </code>
	 *
	 * The command above will select all cities in Denmark
	 * and create a CSV with columns 'city' and 'population' and
	 * populate the cells under these column headers with the
	 * names of the cities and the population numbers respectively.
	 *
	 * @param string  $sql      SQL query to expose result of
	 * @param array   $bindings parameter bindings
	 * @param array   $columns  column headers for CSV file
	 * @param string  $path     path to save CSV file to
	 * @param boolean $output   TRUE to output CSV directly using readfile
	 * @param array   $options  delimiter, quote and escape character respectively
	 *
	 * @return void
	 */
	public static function csv( $sql = '', $bindings = array(), $columns = NULL, $path = '/tmp/redexport_%s.csv', $output = TRUE )
	{
		$quickExport = new QuickExport( self::$toolbox );
		$quickExport->csv( $sql, $bindings, $columns, $path, $output );
	}

	/**
	 * MatchUp is a powerful productivity boosting method that can replace simple control
	 * scripts with a single RedBeanPHP command. Typically, matchUp() is used to
	 * replace login scripts, token generation scripts and password reset scripts.
	 * The MatchUp method takes a bean type, an SQL query snippet (starting at the WHERE clause),
	 * SQL bindings, a pair of task arrays and a bean reference.
	 *
	 * If the first 3 parameters match a bean, the first task list will be considered,
	 * otherwise the second one will be considered. On consideration, each task list,
	 * an array of keys and values will be executed. Every key in the task list should
	 * correspond to a bean property while every value can either be an expression to
	 * be evaluated or a closure (PHP 5.3+). After applying the task list to the bean
	 * it will be stored. If no bean has been found, a new bean will be dispensed.
	 *
	 * This method will return TRUE if the bean was found and FALSE if not AND
	 * there was a NOT-FOUND task list. If no bean was found AND there was also
	 * no second task list, NULL will be returned.
	 *
	 * To obtain the bean, pass a variable as the sixth parameter.
	 * The function will put the matching bean in the specified variable.
	 *
	 * @param string   $type         type of bean you're looking for
	 * @param string   $sql          SQL snippet (starting at the WHERE clause, omit WHERE-keyword)
	 * @param array    $bindings     array of parameter bindings for SQL snippet
	 * @param array    $onFoundDo    task list to be considered on finding the bean
	 * @param array    $onNotFoundDo task list to be considered on NOT finding the bean
	 * @param OODBBean &$bean        reference to obtain the found bean
	 *
	 * @return mixed
	 */
	public static function matchUp( $type, $sql, $bindings = array(), $onFoundDo = NULL, $onNotFoundDo = NULL, &$bean = NULL 	) {
		$matchUp = new MatchUp( self::$toolbox );
		return $matchUp->matchUp( $type, $sql, $bindings, $onFoundDo, $onNotFoundDo, $bean );
	}

	/**
	 * @deprecated
	 *
	 * Returns an instance of the Look Helper class.
	 * The instance will be configured with the current toolbox.
	 *
	 * In previous versions of RedBeanPHP you had to use:
	 * R::getLook()->look() instead of R::look(). However to improve useability of the
	 * library the look() function can now directly be invoked from the facade.
	 *
	 * For more details regarding the Look functionality, please consult R::look().
	 * @see Facade::look
	 * @see Look::look
	 *
	 * @return Look
	 */
	public static function getLook()
	{
		return new Look( self::$toolbox );
	}

	/**
	 * Takes an full SQL query with optional bindings, a series of keys, a template
	 * and optionally a filter function and glue and assembles a view from all this.
	 * This is the fastest way from SQL to view. Typically this function is used to
	 * generate pulldown (select tag) menus with options queried from the database.
	 *
	 * Usage:
	 *
	 * <code>
	 * $htmlPulldown = R::look(
	 *   'SELECT * FROM color WHERE value != ? ORDER BY value ASC',
	 *   [ 'g' ],
	 *   [ 'value', 'name' ],
	 *   '<option value="%s">%s</option>',
	 *   'strtoupper',
	 *   "\n"
	 * );
	 *</code>
	 *
	 * The example above creates an HTML fragment like this:
	 *
	 * <option value="B">BLUE</option>
	 * <option value="R">RED</option>
	 *
	 * to pick a color from a palette. The HTML fragment gets constructed by
	 * an SQL query that selects all colors that do not have value 'g' - this
	 * excludes green. Next, the bean properties 'value' and 'name' are mapped to the
	 * HTML template string, note that the order here is important. The mapping and
	 * the HTML template string follow vsprintf-rules. All property values are then
	 * passed through the specified filter function 'strtoupper' which in this case
	 * is a native PHP function to convert strings to uppercase characters only.
	 * Finally the resulting HTML fragment strings are glued together using a
	 * newline character specified in the last parameter for readability.
	 *
	 * In previous versions of RedBeanPHP you had to use:
	 * R::getLook()->look() instead of R::look(). However to improve useability of the
	 * library the look() function can now directly be invoked from the facade.
	 *
	 * @param string   $sql      query to execute
	 * @param array    $bindings parameters to bind to slots mentioned in query or an empty array
	 * @param array    $keys     names in result collection to map to template
	 * @param string   $template HTML template to fill with values associated with keys, use printf notation (i.e. %s)
	 * @param callable $filter   function to pass values through (for translation for instance)
	 * @param string   $glue     optional glue to use when joining resulting strings
	 *
	 * @return string
	 */
	public static function look( $sql, $bindings = array(), $keys = array( 'selected', 'id', 'name' ), $template = '<option %s value="%s">%s</option>', $filter = 'trim', $glue = '' )
	{
		return self::getLook()->look( $sql, $bindings, $keys, $template, $filter, $glue );
	}

	/**
	 * Calculates a diff between two beans (or arrays of beans).
	 * The result of this method is an array describing the differences of the second bean compared to
	 * the first, where the first bean is taken as reference. The array is keyed by type/property, id and property name, where
	 * type/property is either the type (in case of the root bean) or the property of the parent bean where the type resides.
	 * The diffs are mainly intended for logging, you cannot apply these diffs as patches to other beans.
	 * However this functionality might be added in the future.
	 *
	 * The keys of the array can be formatted using the $format parameter.
	 * A key will be composed of a path (1st), id (2nd) and property (3rd).
	 * Using printf-style notation you can determine the exact format of the key.
	 * The default format will look like:
	 *
	 * 'book.1.title' => array( <OLDVALUE>, <NEWVALUE> )
	 *
	 * If you only want a simple diff of one bean and you don't care about ids,
	 * you might pass a format like: '%1$s.%3$s' which gives:
	 *
	 * 'book.1.title' => array( <OLDVALUE>, <NEWVALUE> )
	 *
	 * The filter parameter can be used to set filters, it should be an array
	 * of property names that have to be skipped. By default this array is filled with
	 * two strings: 'created' and 'modified'.
	 *
	 * @param OODBBean|array $bean    reference beans
	 * @param OODBBean|array $other   beans to compare
	 * @param array          $filters names of properties of all beans to skip
	 * @param string         $format  the format of the key, defaults to '%s.%s.%s'
	 * @param string         $type    type/property of bean to use for key generation
	 *
	 * @return array
	 */
	public static function diff( $bean, $other, $filters = array( 'created', 'modified' ), $pattern = '%s.%s.%s' )
	{
		$diff = new Diff( self::$toolbox );
		return $diff->diff( $bean, $other, $filters, $pattern );
	}

	/**
	 * The gentleman's way to register a RedBeanPHP ToolBox instance
	 * with the facade. Stores the toolbox in the static toolbox
	 * registry of the facade class. This allows for a neat and
	 * explicit way to register a toolbox.
	 *
	 * @param string  $key     key to store toolbox instance under
	 * @param ToolBox $toolbox toolbox to register
	 *
	 * @return void
	 */
	public static function addToolBoxWithKey( $key, ToolBox $toolbox )
	{
		self::$toolboxes[$key] = $toolbox;
	}

	/**
	 * The gentleman's way to remove a RedBeanPHP ToolBox instance
	 * from the facade. Removes the toolbox identified by
	 * the specified key in the static toolbox
	 * registry of the facade class. This allows for a neat and
	 * explicit way to remove a toolbox.
	 * Returns TRUE if the specified toolbox was found and removed.
	 * Returns FALSE otherwise.
	 *
	 * @param string  $key     identifier of the toolbox to remove
	 *
	 * @return boolean
	 */
	public static function removeToolBoxByKey( $key )
	{
		if ( !array_key_exists( $key, self::$toolboxes ) ) {
			return FALSE;
		}
		unset( self::$toolboxes[$key] );
		return TRUE;
	}

	/**
	 * Returns the toolbox associated with the specified key.
	 *
	 * @param string  $key     key to store toolbox instance under
	 * @param ToolBox $toolbox toolbox to register
	 *
	 * @return ToolBox|NULL
	 */
	public static function getToolBoxByKey( $key )
	{
		if ( !array_key_exists( $key, self::$toolboxes ) ) {
			return NULL;
		}
		return self::$toolboxes[$key];
	}

	/**
	 * Toggles JSON column features.
	 * Invoking this method with boolean TRUE causes 2 JSON features to be enabled.
	 * Beans will automatically JSONify any array that's not in a list property and
	 * the Query Writer (if capable) will attempt to create a JSON column for strings that
	 * appear to contain JSON.
	 *
	 * Feature #1:
	 * AQueryWriter::useJSONColumns
	 *
	 * Toggles support for automatic generation of JSON columns.
	 * Using JSON columns means that strings containing JSON will
	 * cause the column to be created (not modified) as a JSON column.
	 * However it might also trigger exceptions if this means the DB attempts to
	 * convert a non-json column to a JSON column.
	 *
	 * Feature #2:
	 * OODBBean::convertArraysToJSON
	 *
	 * Toggles array to JSON conversion. If set to TRUE any array
	 * set to a bean property that's not a list will be turned into
	 * a JSON string. Used together with AQueryWriter::useJSONColumns this
	 * extends the data type support for JSON columns.
	 *
	 * So invoking this method is the same as:
	 *
	 * <code>
	 * AQueryWriter::useJSONColumns( $flag );
	 * OODBBean::convertArraysToJSON( $flag );
	 * </code>
	 *
	 * Unlike the methods above, that return the previous state, this
	 * method does not return anything (void).
	 *
	 * @param boolean $flag feature flag (either TRUE or FALSE)
	 *
	 * @return void
	 */
	public static function useJSONFeatures( $flag )
	{
		AQueryWriter::useJSONColumns( $flag );
		OODBBean::convertArraysToJSON( $flag );
	}

	/**
	 * @experimental
	 *
	 * Given a bean and an optional SQL snippet,
	 * this method will return all child beans in a hierarchically structured
	 * bean table.
	 *
	 * @note that not all database support this functionality. You'll need
	 * at least MariaDB 10.2.2 or Postgres. This method does not include
	 * a warning mechanism in case your database does not support this
	 * functionality.
	 *
	 * @note that this functionality is considered 'experimental'.
	 * It may still contain bugs.
	 *
	 * @param OODBBean $bean     bean to find children of
	 * @param string   $sql      optional SQL snippet
	 * @param array    $bindings SQL snippet parameter bindings
	 */
	public static function children( OODBBean $bean, $sql = NULL, $bindings = array() )
	{
		return self::$tree->children( $bean, $sql, $bindings );
	}

	/**
	 * @experimental
	 *
	 * Given a bean and an optional SQL snippet,
	 * this method will return all parent beans in a hierarchically structured
	 * bean table.
	 *
	 * @note that not all database support this functionality. You'll need
	 * at least MariaDB 10.2.2 or Postgres. This method does not include
	 * a warning mechanism in case your database does not support this
	 * functionality.
	 *
	 * @note that this functionality is considered 'experimental'.
	 * It may still contain bugs.
	 *
	 * @param OODBBean $bean     bean to find parents of
	 * @param string   $sql      optional SQL snippet
	 * @param array    $bindings SQL snippet parameter bindings
	 */
	public static function parents( OODBBean $bean, $sql = NULL, $bindings = array() )
	{
		return self::$tree->parents( $bean, $sql, $bindings );
	}

	/**
	 * Toggles support for nuke().
	 * Can be used to turn off the nuke() feature for security reasons.
	 * Returns the old flag value.
	 *
	 * @param boolean $flag TRUE or FALSE
	 *
	 * @return boolean
	 */
	public static function noNuke( $yesNo ) {
		return AQueryWriter::forbidNuke( $yesNo );
	}

	/**
	 * Selects the feature set you want as specified by
	 * the label.
	 *
	 * Usage:
	 *
	 * <code>
	 * R::useFeatureSet( 'novice/latest' );
	 * </code>
	 *
	 * @param string $label label
	 *
	 * @return void
	 */
	public static function useFeatureSet( $label ) {
		return Feature::feature($label);
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

