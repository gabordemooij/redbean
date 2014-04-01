<?php

use RedBeanPHP\OODBBean as OODBBean;

/**
 * Do not include this file, but let your IDE scan it automatically. 
 */
class R
{
	const C_REDBEANPHP_VERSION = '4.0';
	public static function getVersion(){}
	
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
	public static function setup( $dsn = NULL, $username = NULL, $password = NULL, $frozen = FALSE ){}

	/**
	 * Starts a transaction within a closure (or other valid callback).
	 * If an\Exception is thrown inside, the operation is automatically rolled back.
	 * If no\Exception happens, it commits automatically.
	 * It also supports (simulated) nested transactions (that is useful when
	 * you have many methods that needs transactions but are unaware of
	 * each other).
	 * ex:
	 *        $from = 1;
	 *        $to = 2;
	 *        $amount = 300;
	 *
	 *        R::transaction(function() use($from, $to, $amount)
	 *        {
	 *            $accountFrom = R::load('account', $from);
	 *            $accountTo = R::load('account', $to);
	 *
	 *            $accountFrom->money -= $amount;
	 *            $accountTo->money += $amount;
	 *
	 *            R::store($accountFrom);
	 *            R::store($accountTo);
	 *      });
	 *
	 * @param callable $callback Closure (or other callable) with the transaction logic
	 *
	 * @throws Security
	 *
	 * @return mixed
	 *
	 */
	public static function transaction( $callback ){}

	/**
	 * Adds a database to the facade, afterwards you can select the database using
	 * selectDatabase($key), where $key is the name you assigned to this database.
	 * 
	 * Usage:
	 * 
	 * R::addDatabase( 'database-1', 'sqlite:/tmp/db1.txt' );
	 * R::selectDatabase( 'database-1' ); //to select database again
	 * 
	 * This method allows you to dynamically add (and select) new databases
	 * to the facade. Adding a database with the same key will cause an exception.
	 *
	 * @param string      $key    ID for the database
	 * @param string      $dsn    DSN for the database
	 * @param string      $user   User for connection
	 * @param NULL|string $pass   Password for connection
	 * @param bool        $frozen Whether this database is frozen or not
	 *
	 * @return void
	 */
	public static function addDatabase( $key, $dsn, $user = NULL, $pass = NULL, $frozen = FALSE ){}

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
	public static function selectDatabase( $key ){}

	/**
	 * Toggles DEBUG mode.
	 * In Debug mode all SQL that happens under the hood will
	 * be printed to the screen or logged by provided logger.
	 * If no database connection has been configured using R::setup() or
	 * R::selectDatabase() this method will throw an exception.
	 * Returns the attached logger instance.
	 *
	 * @param boolean $tf
	 * @param integer $mode (0 = to STDOUT, 1 = to ARRAY)   
	 *
	 * @throws Security
	 * 
	 * @return Logger\RDefault
	 */
	public static function debug( $tf = TRUE, $mode = 0 ){}

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
	public static function inspect( $type = NULL ){}

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
	 *
	 * @throws Security
	 */
	public static function store( $bean ){}

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
	public static function freeze( $tf = TRUE ){}

	/**
	 * Loads multiple types of beans with the same ID.
	 * This might look like a strange method, however it can be useful
	 * for loading a one-to-one relation.
	 *
	 * Usage:
	 * list($author, $bio) = R::load('author, bio', $id);
	 *
	 * @param string|array $types
	 * @param mixed        $id
	 *
	 * @return OODBBean
	 */
	public static function loadMulti( $types, $id ){}

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
	 * @throws SQL
	 *
	 * @return OODBBean
	 */
	public static function load( $type, $id ){}

	/**
	 * Removes a bean from the database.
	 * This function will remove the specified OODBBean
	 * Bean Object from the database.
	 *
	 * @param OODBBean|SimpleModel $bean bean you want to remove from database
	 *
	 * @return void
	 *
	 * @throws Security
	 */
	public static function trash( $bean ){}

	/**
	 * Dispenses a new RedBean OODB Bean for use with
	 * the rest of the methods.
	 *
	 * @param string|array $typeOrBeanArray   type or bean array to import
	 * @param integer      $number            number of beans to dispense
	 * @param boolean	     $alwaysReturnArray if TRUE always returns the result as an array
	 *
	 * @return array|OODBBean
	 *
	 * @throws Security
	 */
	public static function dispense( $typeOrBeanArray, $num = 1, $alwaysReturnArray = FALSE ){}
	
	/**
	 * Takes a comma separated list of bean types
	 * and dispenses these beans. For each type in the list
	 * you can specify the number of beans to be dispensed.
	 * 
	 * Usage:
	 * 
	 * list($book, $page, $text) = R::dispenseAll('book,page,text');
	 * 
	 * This will dispense a book, a page and a text. This way you can
	 * quickly dispense beans of various types in just one line of code.
	 * 
	 * Usage:
	 * 
	 * list($book, $pages) = R::dispenseAll('book,page*100');
	 * 
	 * This returns an array with a book bean and then another array
	 * containing 100 page beans.
	 * 
	 * @param string  $order      a description of the desired dispense order using the syntax above
	 * @param boolean $onlyArrays return only arrays even if amount < 2
	 * 
	 * @return array
	 */
	public static function dispenseAll( $order, $onlyArrays = FALSE ){}

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
	public static function findOrDispense( $type, $sql = NULL, $bindings = array() ){}

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
	public static function find( $type, $sql = NULL, $bindings = array() ){}

	/**
	 * @see Facade::find
	 *      The findAll() method differs from the find() method in that it does
	 *      not assume a WHERE-clause, so this is valid:
	 *
	 * R::findAll('person',' ORDER BY name DESC ');
	 *
	 * Your SQL does not have to start with a valid WHERE-clause condition.
	 *
	 * @param string $type     type   the type of bean you are looking for
	 * @param string $sql      sql    SQL query to find the desired bean, starting right after WHERE clause
	 * @param array  $bindings values array of values to be bound to parameters in query
	 *
	 * @return array
	 */
	public static function findAll( $type, $sql = NULL, $bindings = array() ){}

	/**
	 * @see Facade::find
	 * The variation also exports the beans (i.e. it returns arrays).
	 *
	 * @param string $type     type   the type of bean you are looking for
	 * @param string $sql      sql    SQL query to find the desired bean, starting right after WHERE clause
	 * @param array  $bindings values array of values to be bound to parameters in query
	 *
	 * @return array
	 */
	public static function findAndExport( $type, $sql = NULL, $bindings = array() ){}

	/**
	 * @see Facade::find
	 * This variation returns the first bean only.
	 *
	 * @param string $type     type   the type of bean you are looking for
	 * @param string $sql      sql    SQL query to find the desired bean, starting right after WHERE clause
	 * @param array  $bindings values array of values to be bound to parameters in query
	 *
	 * @return OODBBean
	 */
	public static function findOne( $type, $sql = NULL, $bindings = array() ){}

	/**
	 * @see Facade::find
	 * This variation returns the last bean only.
	 *
	 * @param string $type     type   the type of bean you are looking for
	 * @param string $sql      sql    SQL query to find the desired bean, starting right after WHERE clause
	 * @param array  $bindings values array of values to be bound to parameters in query
	 *
	 * @return OODBBean
	 */
	public static function findLast( $type, $sql = NULL, $bindings = array() ){}

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
	public static function batch( $type, $ids ){}
	
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
	public static function loadAll( $type, $ids ) {}

	/**
	 * Convenience function to execute Queries directly.
	 * Executes SQL.
	 *
	 * @param string $sql       sql    SQL query to execute
	 * @param array  $bindings  values a list of values to be bound to query parameters
	 *
	 * @return integer
	 */
	public static function exec( $sql, $bindings = array() ){}

	/**
	 * Convenience function to execute Queries directly.
	 * Executes SQL.
	 *
	 * @param string $sql       sql    SQL query to execute
	 * @param array  $bindings  values a list of values to be bound to query parameters
	 *
	 * @return array
	 */
	public static function getAll( $sql, $bindings = array() ){}

	/**
	 * Convenience function to execute Queries directly.
	 * Executes SQL.
	 *
	 * @param string $sql       sql    SQL query to execute
	 * @param array  $bindings  values a list of values to be bound to query parameters
	 *
	 * @return string
	 */
	public static function getCell( $sql, $bindings = array() ){}

	/**
	 * Convenience function to execute Queries directly.
	 * Executes SQL.
	 *
	 * @param string $sql       sql    SQL query to execute
	 * @param array  $bindings  values a list of values to be bound to query parameters
	 *
	 * @return array
	 */
	public static function getRow( $sql, $bindings = array() ){}

	/**
	 * Convenience function to execute Queries directly.
	 * Executes SQL.
	 *
	 * @param string $sql       sql    SQL query to execute
	 * @param array  $bindings  values a list of values to be bound to query parameters
	 *
	 * @return array
	 */
	public static function getCol( $sql, $bindings = array() ){}

	/**
	 * Convenience function to execute Queries directly.
	 * Executes SQL.
	 * Results will be returned as an associative array. The first
	 * column in the select clause will be used for the keys in this array and
	 * the second column will be used for the values. If only one column is
	 * selected in the query, both key and value of the array will have the
	 * value of this field for each row.
	 *
	 * @param string $sql       sql    SQL query to execute
	 * @param array  $bindings  values a list of values to be bound to query parameters
	 *
	 * @return array
	 */
	public static function getAssoc( $sql, $bindings = array() ){}
	
	/**
	 * Convenience function to execute Queries directly.
	 * Executes SQL.
	 * Results will be returned as an associative array indexed by the first
	 * column in the select.
	 * 
	 * @param string $sql       sql    SQL query to execute
	 * @param array  $bindings  values a list of values to be bound to query parameters
	 *
	 * @return array
	 */
	public static function getAssocRow( $sql, $bindings = array() )
	{}

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
	 * @param OODBBean $bean  bean to be copied
	 * @param array            $trail for internal usage, pass array()
	 * @param boolean          $pid   for internal usage
	 *
	 * @return array
	 */
	public static function dup( $bean, $trail = array(), $pid = FALSE, $filters = array() ){}

	/**
	 * Exports a collection of beans. Handy for XML/JSON exports with a
	 * Javascript framework like Dojo or ExtJS.
	 * What will be exported:
	 * - contents of the bean
	 * - all own bean lists (recursively)
	 * - all shared beans (not THEIR own lists)
	 *
	 * @param    array|OODBBean $beans   beans to be exported
	 * @param    boolean                $parents whether you want parent beans to be exported
	 * @param   array                   $filters whitelist of types
	 *
	 * @return    array
	 */
	public static function exportAll( $beans, $parents = FALSE, $filters = array() ){}

	/**
	 * Converts a series of rows to beans.
	 * This method converts a series of rows to beans.
	 * The type of the desired output beans can be specified in the
	 * first parameter. The second parameter is meant for the database
	 * result rows.
	 *
	 * @param string $type type of beans to produce
	 * @param array  $rows must contain an array of array
	 *
	 * @return array
	 */
	public static function convertToBeans( $type, $rows )
	{}

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
	 * @param  array            $tags list of tags
	 * @param  boolean          $all  whether they must all match or just some
	 *
	 * @return boolean
	 */
	public static function hasTag( $bean, $tags, $all = FALSE )
	{}

	/**
	 * Part of RedBeanPHP Tagging API.
	 * Removes all specified tags from the bean. The tags specified in
	 * the second parameter will no longer be associated with the bean.
	 *
	 * @param  OODBBean $bean    tagged bean
	 * @param  array            $tagList list of tags (names)
	 *
	 * @return void
	 */
	public static function untag( $bean, $tagList )
	{}

	/**
	 * Part of RedBeanPHP Tagging API.
	 * Tags a bean or returns tags associated with a bean.
	 * If $tagList is NULL or omitted this method will return a
	 * comma separated list of tags associated with the bean provided.
	 * If $tagList is a comma separated list (string) of tags all tags will
	 * be associated with the bean.
	 * You may also pass an array instead of a string.
	 *
	 * @param OODBBean $bean    bean
	 * @param mixed            $tagList tags
	 *
	 * @return string
	 */
	public static function tag( OODBBean $bean, $tagList = NULL ){}

	/**
	 * Part of RedBeanPHP Tagging API.
	 * Adds tags to a bean.
	 * If $tagList is a comma separated list of tags all tags will
	 * be associated with the bean.
	 * You may also pass an array instead of a string.
	 *
	 * @param OODBBean $bean    bean
	 * @param array            $tagList list of tags to add to bean
	 *
	 * @return void
	 */
	public static function addTags( OODBBean $bean, $tagList ){}

	/**
	 * Part of RedBeanPHP Tagging API.
	 * Returns all beans that have been tagged with one of the tags given.
	 *
	 * @param string $beanType type of bean you are looking for
	 * @param array  $tagList  list of tags to match
	 *
	 * @return array
	 */
	public static function tagged( $beanType, $tagList ){}

	/**
	 * Part of RedBeanPHP Tagging API.
	 * Returns all beans that have been tagged with ALL of the tags given.
	 *
	 * @param string $beanType type of bean you are looking for
	 * @param array  $tagList  list of tags to match
	 *
	 * @return array
	 */
	public static function taggedAll( $beanType, $tagList ){}

	/**
	 * Wipes all beans of type $beanType.
	 *
	 * @param string $beanType type of bean you want to destroy entirely
	 *
	 * @return boolean
	 */
	public static function wipe( $beanType ){}

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
	 *
	 * @throws SQL
	 */
	public static function count( $type, $addSQL = '', $bindings = array() ){}

	/**
	 * Configures the facade, want to have a new Writer? A new Object Database or a new
	 * Adapter and you want it on-the-fly? Use this method to hot-swap your facade with a new
	 * toolbox.
	 *
	 * @param ToolBox $tb toolbox
	 *
	 * @return ToolBox
	 */
	public static function configureFacadeWithToolbox( ToolBox $tb ){}


	/**
	 * Facade Convience method for adapter transaction system.
	 * Begins a transaction.
	 *
	 * @return bool
	 */
	public static function begin(){}

	/**
	 * Facade Convience method for adapter transaction system.
	 * Commits a transaction.
	 *
	 * @return bool
	 */
	public static function commit(){}

	/**
	 * Facade Convience method for adapter transaction system.
	 * Rolls back a transaction.
	 *
	 * @return bool
	 */
	public static function rollback(){}

	/**
	 * Returns a list of columns. Format of this array:
	 * array( fieldname => type )
	 * Note that this method only works in fluid mode because it might be
	 * quite heavy on production servers!
	 *
	 * @param  string $table   name of the table (not type) you want to get columns of
	 *
	 * @return array
	 */
	public static function getColumns( $table ){}

	/**
	 * Generates question mark slots for an array of values.
	 *
	 * @param array $array
	 *
	 * @return string
	 */
	public static function genSlots( $array ){}

	/**
	 * Nukes the entire database.
	 * This will remove all schema structures from the database.
	 * Only works in fluid mode. Be careful with this method.
	 * 
	 * @warning dangerous method, will remove all tables, columns etc.
	 *
	 * @return void
	 */
	public static function nuke(){}
	
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
	public static function storeAll( $beans ){}

	/**
	 * Short hand function to trash a set of beans at once.
	 * For information please consult the R::trash() function.
	 * A loop saver.
	 *
	 * @param array $beans list of beans to be trashed
	 *
	 * @return void
	 */
	public static function trashAll( $beans ){}

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
	public static function useWriterCache( $yesNo ){}

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
	public static function dispenseLabels( $type, $labels ){}
	
	/**
	 * Generates and returns an ENUM value. This is how RedBeanPHP handles ENUMs.
	 * Either returns a (newly created) bean respresenting the desired ENUM
	 * value or returns a list of all enums for the type.
	 * 
	 * To obtain (and add if necessary) an ENUM value:
	 * 
	 * $tea->flavour = R::enum( 'flavour:apple' );
	 * 
	 * Returns a bean of type 'flavour' with  name = apple.
	 * This will add a bean with property name (set to APPLE) to the database
	 * if it does not exist yet. 
	 * 
	 * To obtain all flavours:
	 * 
	 * R::enum('flavour');
	 * 
	 * To get a list of all flavour names:
	 * 
	 * R::gatherLabels( R::enum( 'flavour' ) );
	 * 
	 * @param string $enum either type or type-value
	 * 
	 * @return array|OODBBean
	 */
	public static function enum( $enum ) {}

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
	public static function gatherLabels( $beans ){}
	
	/**
	 * Closes the database connection.
	 *
	 * @return void
	 */
	public static function close(){}

	/**
	 * Simple convenience function, returns ISO date formatted representation
	 * of $time.
	 *
	 * @param mixed $time UNIX timestamp
	 *
	 * @return string
	 */
	public static function isoDate( $time = NULL ){}

	/**
	 * Simple convenience function, returns ISO date time
	 * formatted representation
	 * of $time.
	 *
	 * @param mixed $time UNIX timestamp
	 *
	 * @return string
	 */
	public static function isoDateTime( $time = NULL ){}

	/**
	 * Optional accessor for neat code.
	 * Sets the database adapter you want to use.
	 *
	 * @param Adapter $adapter
	 *
	 * @return void
	 */
	public static function setDatabaseAdapter( Adapter $adapter ){}

	/**
	 * Optional accessor for neat code.
	 * Sets the database adapter you want to use.
	 *
	 * @param QueryWriter $writer
	 *
	 * @return void
	 */
	public static function setWriter( QueryWriter $writer ){}

	/**
	 * Optional accessor for neat code.
	 * Sets the database adapter you want to use.
	 *
	 * @param OODB $redbean
	 */
	public static function setRedBean( OODB $redbean ){}

	/**
	 * Optional accessor for neat code.
	 * Sets the database adapter you want to use.
	 *
	 * @return DBAdapter
	 */
	public static function getDatabaseAdapter(){}
	
	/**
	 * Returns the current duplication manager instance.
	 * 
	 * @return DuplicationManager
	 */
	public static function getDuplicationManager(){}

	/**
	 * Optional accessor for neat code.
	 * Sets the database adapter you want to use.
	 *
	 * @return QueryWriter
	 */
	public static function getWriter(){}

	/**
	 * Optional accessor for neat code.
	 * Sets the database adapter you want to use.
	 *
	 * @return OODB
	 */
	public static function getRedBean(){}

	/**
	 * Returns the toolbox currently used by the facade.
	 * To set the toolbox use R::setup() or R::configureFacadeWithToolbox().
	 * To create a toolbox use Setup::kickstart(). Or create a manual
	 * toolbox using the ToolBox class.
	 *
	 * @return ToolBox
	 */
	public static function getToolBox(){}

	/**
	 * Facade method for AQueryWriter::renameAssociation()
	 *
	 * @param string|array $from
	 * @param string       $to
	 *
	 * @return void
	 */
	public static function renameAssociation( $from, $to = NULL ){}
	
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
	public static function beansToArray( $beans ){}
	
	/**
	 * Dynamically extends the facade with a plugin.
	 * Using this method you can register your plugin with the facade and then
	 * use the plugin by invoking the name specified plugin name as a method on
	 * the facade.
	 * 
	 * Usage:
	 * 
	 * R::ext( 'makeTea', function() { ... }  );
	 * 
	 * Now you can use your makeTea plugin like this:
	 * 
	 * R::makeTea();
	 * 
	 * @param string   $pluginName name of the method to call the plugin
	 * @param callable $callable   a PHP callable
	 */
	public static function ext( $pluginName, $callable ){}
}


namespace RedBeanPHP {
	
	/**
	* OODBBean (Object Oriented DataBase Bean)
	*
	* @file    RedBean/OODBBean.php
	* @desc    The Bean class used for passing information
	* @author  Gabor de Mooij and the RedBeanPHP community
	* @license BSD/GPLv2
	*
	* copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
	* This source file is subject to the BSD/GPLv2 License that is bundled
	* with this source code in the file license.txt.
	*/
  class OODBBean implements\IteratorAggregate,\ArrayAccess,\Countable
  {


	  /**
		* Initializes a bean. Used by OODB for dispensing beans.
		* It is not recommended to use this method to initialize beans. Instead
		* use the OODB object to dispense new beans. You can use this method
		* if you build your own bean dispensing mechanism.
		*
		* @param string             $type       type of the new bean
		* @param BeanHelper $beanhelper bean helper to obtain a toolbox and a model
		*
		* @return void
		*/
	  public function initializeForDispense( $type, BeanHelper $beanhelper ){}

	  /**
		* Sets the Bean Helper. Normally the Bean Helper is set by OODB.
		* Here you can change the Bean Helper. The Bean Helper is an object
		* providing access to a toolbox for the bean necessary to retrieve
		* nested beans (bean lists: ownBean, sharedBean) without the need to
		* rely on static calls to the facade (or make this class dep. on OODB).
		*
		* @param BeanHelper $helper
		*
		* @return void
		*/
	  public function setBeanHelper( BeanHelper $helper ){}

	  /**
		* Returns an\ArrayIterator so you can treat the bean like
		* an array with the properties container as its contents.
		* This method is meant for PHP and allows you to access beans as if
		* they were arrays, i.e. using array notation:
		* 
		* $bean[ $key ] = $value;
		* 
		* Note that not all PHP functions work with the array interface.
		*
		* @return\ArrayIterator
		*/
	  public function getIterator(){}

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
	  public function import( $array, $selection = FALSE, $notrim = FALSE ){}

	  /**
	  * Fast way to import a row.
	  * Does not perform any checks.
	  *
	  * @param array $row a database row
	  *
	  * @return self
	  */
	  public function importRow( $row ){}

	  /**
		* Imports data from another bean. Chainable.
		* Copies the properties from the source bean to the internal
		* property list.
		*
		* @param OODBBean $sourceBean the source bean to take properties from
		*
		* @return OODBBean
		*/
	  public function importFrom( OODBBean $sourceBean ){}

	  /**
		* Injects the properties of another bean but keeps the original ID.
		* Just like import() but keeps the original ID.
		* Chainable.
		*
		* @param OODBBean $otherBean the bean whose properties you would like to copy
		*
		* @return OODBBean
		*/
	  public function inject( OODBBean $otherBean ){}

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
	  public function export( $meta = FALSE, $parents = FALSE, $onlyMe = FALSE, $filters = array() ){}

	  /**
		* Returns the ID of the bean no matter what the ID field is.
		*
		* @return string|null
		*/
	  public function getID(){}

	  /**
		* Removes a property from the properties list without invoking
		* an __unset on the bean.
		*
		* @param  string $property property that needs to be unset
		*
		* @return void
		*/
	  public function removeProperty( $property ){}

	  /**
		* Adds WHERE clause conditions to ownList retrieval.
		* For instance to get the pages that belong to a book you would
		* issue the following command: $book->ownPage
		* However, to order these pages by number use:
		*
		* $book->with(' ORDER BY `number` ASC ')->ownPage
		*
		* the additional SQL snippet will be merged into the final
		* query.
		*
		* @param string $sql SQL to be added to retrieval query.
		* @param array       $bindings array with parameters to bind to SQL snippet
		*
		* @return OODBBean
		*/
	  public function with( $sql, $bindings = array() ){}

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
	  public function withCondition( $sql, $bindings = array() ){}

	  /**
		* When prefix for a list, this causes the list to reload.
		* 
		* @return self
		*/
	  public function all(){}

	  /**
		* Prepares an own-list to use an alias. This is best explained using
		* an example. Imagine a project and a person. The project always involves
		* two persons: a teacher and a student. The person beans have been aliased in this
		* case, so to the project has a teacher_id pointing to a person, and a student_id
		* also pointing to a person. Given a project, we obtain the teacher like this:
		*
		* $project->fetchAs('person')->teacher;
		*
		* Now, if we want all projects of a teacher we cant say:
		*
		* $teacher->ownProject
		*
		* because the $teacher is a bean of type 'person' and no project has been
		* assigned to a person. Instead we use the alias() method like this:
		*
		* $teacher->alias('teacher')->ownProject
		*
		* now we get the projects associated with the person bean aliased as
		* a teacher.
		*
		* @param string $aliasName the alias name to use
		*
		* @return OODBBean
		*/
	  public function alias( $aliasName ){}

	  /**
		* Returns properties of bean as an array.
		* This method returns the raw internal property list of the
		* bean. Only use this method for optimization purposes. Otherwise
		* use the export() method to export bean data to arrays.
		*
		* @return array
		*/
	  public function getProperties(){}

	  /**
		* Turns a camelcase property name into an underscored property name.
		* Examples:
		*    oneACLRoute -> one_acl_route
		*    camelCase -> camel_case
		*
		* Also caches the result to improve performance.
		*
		* @param string $property
		*
		* @return string
		*/
	  public function beau( $property ){}

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
	  public function setProperty( $property, $value, $updateShadow = FALSE, $taint = FALSE ){}

	  /**
		* Returns the value of a meta property. A meta property
		* contains extra information about the bean object that will not
		* get stored in the database. Meta information is used to instruct
		* RedBean as well as other systems how to deal with the bean.
		* For instance: $bean->setMeta("buildcommand.unique", array(
		* array("column1", "column2", "column3") ) );
		* Will add a UNIQUE constraint for the bean on columns: column1, column2 and
		* column 3.
		* To access a Meta property we use a dot separated notation.
		* If the property cannot be found this getter will return NULL instead.
		*
		* @param string $path    path
		* @param mixed  $default default value
		*
		* @return mixed
		*/
	  public function getMeta( $path, $default = NULL ){}

	  /**
		* Stores a value in the specified Meta information property. $value contains
		* the value you want to store in the Meta section of the bean and $path
		* specifies the dot separated path to the property. For instance "my.meta.property".
		* If "my" and "meta" do not exist they will be created automatically.
		*
		* @param string $path  path
		* @param mixed  $value value
		*
		* @return OODBBean
		*/
	  public function setMeta( $path, $value ){}

	  /**
		* Copies the meta information of the specified bean
		* This is a convenience method to enable you to
		* exchange meta information easily.
		*
		* @param OODBBean $bean
		*
		* @return OODBBean
		*/
	  public function copyMetaFrom( OODBBean $bean ){}

	  /**
		* Chainable method to cast a certain ID to a bean; for instance:
		* $person = $club->fetchAs('person')->member;
		* This will load a bean of type person using member_id as ID.
		*
		* @param  string $type preferred fetch type
		*
		* @return OODBBean
		*/
	  public function fetchAs( $type ){}

	  /**
		* For polymorphic bean relations.
		* Same as fetchAs but uses a column instead of a direct value.
		*
		* @param string $column
		*
		* @return OODBBean
		*/
	  public function poly( $field ){}

	  /**
		* Traverses a bean property with the specified function.
		* Recursively iterates through the property invoking the
		* function for each bean along the way passing the bean to it.
		* 
		* Can be used together with with, withCondition, alias and fetchAs.
		* 
		* @param string  $property property
		* @param closure $function function
		* 
		* @return OODBBean
		*/
	  public function traverse( $property, $function, $maxDepth = NULL ){}

	  /**
		* Implementation of\Countable interface. Makes it possible to use
		* count() function on a bean.
		*
		* @return integer
		*/
	  public function count(){}

	  /**
		* Checks whether a bean is empty or not.
		* A bean is empty if it has no other properties than the id field OR
		* if all the other property are empty().
		*
		* @return boolean
		*/
	  public function isEmpty(){}

	  /**
		* Chainable setter.
		*
		* @param string $property the property of the bean
		* @param mixed  $value    the value you want to set
		*
		* @return OODBBean
		*/
	  public function setAttr( $property, $value ){}

	  /**
		* Comfort method.
		* Unsets all properties in array.
		*
		* @param array $properties properties you want to unset.
		*
		* @return OODBBean
		*/
	  public function unsetAll( $properties ){}

	  /**
		* Returns original (old) value of a property.
		* You can use this method to see what has changed in a
		* bean.
		*
		* @param string $property name of the property you want the old value of
		*
		* @return mixed
		*/
	  public function old( $property ){}

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
	  public function isTainted(){}

	  /**
		* Returns TRUE if the value of a certain property of the bean has been changed and
		* FALSE otherwise.
		*
		* @param string $property name of the property you want the change-status of
		*
		* @return boolean
		*/
	  public function hasChanged( $property ){}

	  /**
		* Creates a N-M relation by linking an intermediate bean.
		* This method can be used to quickly connect beans using indirect
		* relations. For instance, given an album and a song you can connect the two
		* using a track with a number like this:
		*
		* Usage:
		*
		* $album->link('track', array('number'=>1))->song = $song;
		*
		* or:
		*
		* $album->link($trackBean)->song = $song;
		*
		* What this method does is adding the link bean to the own-list, in this case
		* ownTrack. If the first argument is a string and the second is an array or
		* a JSON string then the linking bean gets dispensed on-the-fly as seen in
		* example #1. After preparing the linking bean, the bean is returned thus
		* allowing the chained setter: ->song = $song.
		*
		* @param string|OODBBean $type          type of bean to dispense or the full bean
		* @param string|array            $qualification JSON string or array (optional)
		*
		* @return OODBBean
		*/
	  public function link( $typeOrBean, $qualification = array() ){}

	  /**
		* Returns the same bean freshly loaded from the database.
		*
		* @return OODBBean
		*/
	  public function fresh(){}

	  /**
		* Registers a association renaming globally.
		*
		* @param string $via type you wish to use for shared lists
		*
		* @return OODBBean
		*/
	  public function via( $via ){}

	  /**
		* Counts all own beans of type $type.
		* Also works with alias(), with() and withCondition().
		*
		* @param string $type the type of bean you want to count
		*
		* @return integer
		*/
	  public function countOwn( $type ){}

	  /**
		* Counts all shared beans of type $type.
		* Also works with via(), with() and withCondition().
		*
		* @param string $type type of bean you wish to count
		*
		* @return integer
		*/
	  public function countShared( $type ){}

	  /**
		* Tests whether the database identities of two beans are equal.
		*
		* @param OODBBean $bean other bean
		*
		* @return boolean
		*/
	  public function equals(OODBBean $bean) {}
  }
}