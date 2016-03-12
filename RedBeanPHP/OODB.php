<?php

namespace RedBeanPHP;

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
