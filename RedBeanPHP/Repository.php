<?php

namespace RedBeanPHP;

use RedBeanPHP\OODBBean as OODBBean;
use RedBeanPHP\Observable as Observable;
use RedBeanPHP\Adapter\DBAdapter as DBAdapter;
use RedBeanPHP\BeanHelper\FacadeBeanHelper as FacadeBeanHelper;
use RedBeanPHP\QueryWriter as QueryWriter;
use RedBeanPHP\RedException\Security as Security;
use RedBeanPHP\SimpleModel as SimpleModel;
use RedBeanPHP\BeanHelper as BeanHelper;
use RedBeanPHP\RedException\SQL as SQL;
use RedBeanPHP\QueryWriter\AQueryWriter as AQueryWriter;
use RedBeanPHP\OODB as OODB;

/**
 * Abstract Repository
 *
 * @file    RedBean/Repository.php
 * @desc    RedBean Object Database
 * @author  Gabor de Mooij and the RedBeanPHP community
 * @license BSD/GPLv2
 *
 * OODB manages two repositories, a fluid one that
 * adjust the database schema on-the-fly to accomodate for
 * new bean types (tables) and new properties (columns) and
 * a frozen one for use in a production environment. OODB
 * allows you to swap the repository instances using the freeze()
 * method.
 *
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
	 *
	 * @throws Security
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
	 * @param OODBBean $bean       the bean
	 * @param array            $ownresidue list
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
	 * @param OODBBean $bean        the bean
	 * @param array            $ownTrashcan list
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
	 * @param OODBBean $bean           bean
	 * @param array            $sharedTrashcan list
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
	 * @param OODBBean $bean          bean
	 * @param array            $sharedresidue list
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
	 * @param array            $embeddedBeans destination array for embedded bean
	 * @param OODBBean $bean          target bean
	 * @param string           $property      property that contains the embedded bean
	 * @param OODBBean $value         embedded bean itself
	 */
	protected function processEmbeddedBean( &$embeddedBeans, $bean, $property, OODBBean $value )
	{
		$linkField        = $property . '_id';
		$bean->$linkField = $this->prepareEmbeddedBean( $value );
		$bean->setMeta( 'cast.' . $linkField, 'id' );
		$embeddedBeans[$linkField] = $value;
		unset( $bean->$property );
	}


	/**
	 * Constructor, requires a query writer.
	 *
	 * @param QueryWriter $writer writer
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
	 *
	 * @throws Security $exception
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
	 * array(
	 *    'PROPERTY' => array( POSSIBLE VALUES... 'John', 'Steve' )
	 *    'PROPERTY' => array( POSSIBLE VALUES... )
	 * );
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
	 *
	 * @throws SQL
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
		} catch ( SQL $exception ) {
			$this->handleException( $exception );
		}

		return array();
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
	 *
	 * @throws Security
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
			$this->processLists( $bean );
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
		} catch ( SQL $e ) {
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
	 * @param string $type type of beans you would like to have
	 * @param array  $rows rows from the database result
	 *
	 * @return array
	 */
	public function convertToBeans( $type, $rows )
	{
		$collection                  = array();
		$this->stash[$this->nesting] = array();
		foreach ( $rows as $row ) {
			$id                               = $row['id'];
			$this->stash[$this->nesting][$id] = $row;
			$collection[$id]                  = $this->load( $type, $id );
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
	 *
	 * @throws SQL
	 */
	public function count( $type, $addSQL = '', $bindings = array() )
	{
		$type = AQueryWriter::camelsSnake( $type );
		if ( count( explode( '_', $type ) ) > 2 ) {
			throw new RedException( 'Invalid type for count.' );
		}

		try {
			return (int) $this->writer->queryRecordCount( $type, array(), $addSQL, $bindings );
		} catch ( SQL $exception ) {
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
	 *
	 * @throws Security
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
		} catch ( SQL $exception ) {
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
	 *
	 * @throws SQL
	 */
	public function wipe( $type )
	{
		try {
			$this->writer->wipe( $type );

			return TRUE;
		} catch ( SQL $exception ) {
			if ( !$this->writer->sqlStateIn( $exception->getSQLState(), array( QueryWriter::C_SQLSTATE_NO_SUCH_TABLE ) ) ) {
				throw $exception;
			}

			return FALSE;
		}
	}


}
