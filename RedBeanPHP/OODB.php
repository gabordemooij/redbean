<?php 

namespace RedBeanPHP;

use RedBeanPHP\OODBBean as OODBBean;
use RedBeanPHP\Observable as Observable;
use RedBeanPHP\Adapter\DBAdapter as DBAdapter;
use RedBeanPHP\BeanHelper\FacadeBeanHelper as FacadeBeanHelper;
use RedBeanPHP\AssociationManager as AssociationManager;
use RedBeanPHP\QueryWriter as QueryWriter;
use RedBeanPHP\RedException\Security as Security;
use RedBeanPHP\SimpleModel as SimpleModel;
use RedBeanPHP\BeanHelper as BeanHelper;
use RedBeanPHP\RedException\SQL as SQL;
use RedBeanPHP\QueryWriter\AQueryWriter as AQueryWriter;

/**
 * RedBean Object Oriented DataBase
 *
 * @file    RedBean/OODB.php
 * @desc    RedBean Object Database
 * @author  Gabor de Mooij and the RedBeanPHP community
 * @license BSD/GPLv2
 *
 * The RedBean OODB Class is the main class of RedBeanPHP.
 * It takes OODBBean objects and stores them to and loads them from the
 * database as well as providing other CRUD functions. This class acts as a
 * object database.
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class OODB extends Observable
{

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
	 * Handles\Exceptions. Suppresses exceptions caused by missing structures.
	 *
	 * @param\Exception $exception exception
	 *
	 * @return void
	 *
	 * @throws\Exception
	 */
	private function handleException(\Exception $exception )
	{
		if ( $this->isFrozen || !$this->writer->sqlStateIn( $exception->getSQLState(),
			array(
				QueryWriter::C_SQLSTATE_NO_SUCH_TABLE,
				QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN ) )
		) {
			throw $exception;
		}
	}

	/**
	 * Unboxes a bean from a FUSE model if needed and checks whether the bean is
	 * an instance of OODBBean.
	 *
	 * @param OODBBean $bean bean you wish to unbox
	 *
	 * @return OODBBean
	 *
	 * @throws Security
	 */
	private function unboxIfNeeded( $bean )
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
	private function processGroups( $originals, $current, $additions, $trashcan, $residue )
	{
		return array(
			array_merge( $additions, array_diff( $current, $originals ) ),
			array_merge( $trashcan, array_diff( $originals, $current ) ),
			array_merge( $residue, array_intersect( $current, $originals ) )
		);
	}

	/**
	 * Figures out the desired type given the cast string ID.
	 *
	 * @param string $cast cast identifier
	 *
	 * @return integer
	 *
	 * @throws Security
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
	 * Processes an embedded bean.
	 *
	 * @param OODBBean|SimpleModel $embeddedBean the bean or model
	 *
	 * @return integer
	 */
	private function prepareEmbeddedBean( $embeddedBean )
	{
		if ( !$embeddedBean->id || $embeddedBean->getMeta( 'tainted' ) ) {
			$this->store( $embeddedBean );
		}

		return $embeddedBean->id;
	}

	/**
	 * Orders the Query Writer to create a table if it does not exist already and
	 * adds a note in the build report about the creation.
	 *
	 * @param OODBBean $bean  bean to update report of
	 * @param string           $table table to check and create if not exists
	 *
	 * @return void
	 */
	private function createTableIfNotExists( OODBBean $bean, $table )
	{
		//Does table exist? If not, create
		if ( !$this->isFrozen && !$this->tableExists( $this->writer->esc( $table, TRUE ) ) ) {
			$this->writer->createTable( $table );
			$bean->setMeta( 'buildreport.flags.created', TRUE );
		}
	}

	/**
	 * Adds the unique constraints described in the meta data.
	 *
	 * @param OODBBean $bean bean
	 *
	 * @return void
	 */
	private function addUniqueConstraints( OODBBean $bean )
	{
		if ( $uniques = $bean->getMeta( 'buildcommand.unique' ) ) {
			$table = $bean->getMeta( 'type' );
			foreach ( $uniques as $unique ) {
				if ( !$this->isChilled($table) ) $this->writer->addUniqueIndex( $table, $unique );
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
	private function storeBean( OODBBean $bean )
	{
		if ( $bean->getMeta( 'tainted' ) ) {
			if ( !$this->isFrozen ) {
				$this->check( $bean );
				$table = $bean->getMeta( 'type' );
				$this->createTableIfNotExists( $bean, $table );
				$updateValues = $this->getUpdateValues( $bean );			
				$this->addUniqueConstraints( $bean );
				$bean->id = $this->writer->updateRecord( $table, $updateValues, $bean->id );
				$bean->setMeta( 'tainted', FALSE );
			} else {
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
				$bean->setMeta( 'tainted', FALSE );
			}
		}
	}

	/**
	 * Returns a structured array of update values using the following format:
	 * array(
	 *        property => $property,
	 *        value => $value
	 * );
	 *
	 * @param OODBBean $bean bean to extract update values from
	 *
	 * @return array
	 */
	private function getUpdateValues( OODBBean $bean )
	{
		$updateValues = array();
		foreach ( $bean as $property => $value ) {
			if ( !$this->isFrozen && $property !== 'id' ) {
				$this->moldTable( $bean, $property, $value );
			}
			if ( $property !== 'id' ) {
				$updateValues[] = array( 'property' => $property, 'value' => $value );
			}
		}

		return $updateValues;
	}

	/**
	 * Molds the table to fit the bean data.
	 * Given a property and a value and the bean, this method will
	 * adjust the table structure to fit the requirements of the property and value.
	 * This may include adding a new column or widening an existing column to hold a larger
	 * or different kind of value. This method employs the writer to adjust the table
	 * structure in the database. Schema updates are recorded in meta properties of the bean.
	 *
	 * @param OODBBean $bean     bean to get cast data from and store meta in
	 * @param string           $property property to store
	 * @param mixed            $value    value to store
	 *
	 * @return void
	 */
	private function moldTable( OODBBean $bean, $property, $value )
	{
		$table   = $bean->getMeta( 'type' );
		$columns = $this->writer->getColumns( $table );
		if ( !in_array( $bean->getMeta( 'type' ), $this->chillList ) ) {
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
				}
			} else {
				$this->writer->addColumn( $table, $property, $typeno );
				$bean->setMeta( 'buildreport.flags.addcolumn', TRUE );
				$this->processBuildCommands( $table, $property, $bean );
			}
		}
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
	private function processSharedAdditions( $bean, $sharedAdditions )
	{
		foreach ( $sharedAdditions as $addition ) {
			if ( $addition instanceof OODBBean ) {
				$this->assocManager->associate( $addition, $bean );
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
	private function processResidue( $ownresidue )
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
	private function processTrashcan( $bean, $ownTrashcan )
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
	private function processSharedTrashcan( $bean, $sharedTrashcan )
	{
		foreach ( $sharedTrashcan as $trash ) {
			$this->assocManager->unassociate( $trash, $bean );
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
	private function processSharedResidue( $bean, $sharedresidue )
	{
		foreach ( $sharedresidue as $residue ) {
			$this->store( $residue );
		}
	}

	/**
	 * Processes embedded beans.
	 * Each embedded bean will be indexed and foreign keys will
	 * be created if the bean is in the dependency list.
	 *
	 * @param OODBBean $bean          bean
	 * @param array            $embeddedBeans embedded beans
	 *
	 * @return void
	 */
	private function addForeignKeysForParentBeans( $bean, $embeddedBeans )
	{
		$cachedIndex = array();
		foreach ( $embeddedBeans as $linkField => $embeddedBean ) {
			$beanType = $bean->getMeta( 'type' );
			$embeddedType = $embeddedBean->getMeta( 'type' );
			$key = $beanType . '|' . $embeddedType . '>' . $linkField;
			if ( !isset( $cachedIndex[$key] ) ) {
				$this->writer->addIndex( $bean->getMeta( 'type' ),
				'index_foreignkey_' . $beanType . '_' . $embeddedType,
				$linkField );
				$this->writer->addFK( $beanType, $embeddedType, $linkField, 'id', FALSE );
				$cachedIndex[$key] = TRUE;
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
	 * @param OODBBean $bean         bean
	 * @param array            $ownAdditions list of addition beans in own-list
	 *
	 * @return void
	 *
	 * @throws Security
	 */
	private function processAdditions( $bean, $ownAdditions )
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
				if ( !$this->isFrozen ) {
					$additionType = $addition->getMeta( 'type' );
					$key = $additionType . '|' . $beanType . '>' . $myFieldLink;
					if ( !isset( $cachedIndex[$key] ) ) {
						$this->writer->addIndex( $additionType,
						'index_foreignkey_' . $additionType . '_' . $beanType,
						$myFieldLink );
						$isDep = $bean->getMeta( 'sys.exclusive-'.$additionType );
						$this->writer->addFK( $additionType, $beanType, $myFieldLink, 'id', $isDep );
						$cachedIndex[$key] = TRUE;
					}
				}
			} else {
				throw new RedException( 'Array may only contain OODBBeans' );
			}
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
	private function hasListsOrObjects( OODBBean $bean )
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
	 * Processes all column based build commands.
	 * A build command is an additional instruction for the Query Writer. It is processed only when
	 * a column gets created. The build command is often used to instruct the writer to write some
	 * extra SQL to create indexes or constraints. Build commands are stored in meta data of the bean.
	 * They are only for internal use, try to refrain from using them in your code directly.
	 *
	 * @param  string           $table    name of the table to process build commands for
	 * @param  string           $property name of the property to process build commands for
	 * @param  OODBBean $bean     bean that contains the build commands
	 *
	 * @return void
	 */
	private function processBuildCommands( $table, $property, OODBBean $bean )
	{
		if ( $inx = ( $bean->getMeta( 'buildcommand.indexes' ) ) ) {
			if ( isset( $inx[$property] ) ) {
				$this->writer->addIndex( $table, $inx[$property], $property );
			}
		}
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
	private function processEmbeddedBean( &$embeddedBeans, $bean, $property, OODBBean $value )
	{
		$linkField        = $property . '_id';
		$bean->$linkField = $this->prepareEmbeddedBean( $value );
		$bean->setMeta( 'cast.' . $linkField, 'id' );
		$embeddedBeans[$linkField] = $value;
		unset( $bean->$property );
	}

	/**
	 * Stores a bean and its lists in one run.
	 *
	 * @param OODBBean $bean
	 *
	 * @return void
	 */
	private function processLists( OODBBean $bean )
	{
		$sharedAdditions = $sharedTrashcan = $sharedresidue = $sharedItems = $ownAdditions = $ownTrashcan = $ownresidue = $embeddedBeans = array(); //Define groups
		foreach ( $bean as $property => $value ) {
			$value = ( $value instanceof SimpleModel ) ? $value->unbox() : $value;
			if ( $value instanceof OODBBean ) {
				$this->processEmbeddedBean( $embeddedBeans, $bean, $property, $value );
			} elseif ( is_array( $value ) ) {
				$originals = $bean->getMeta( 'sys.shadow.' . $property, array() );
				$bean->setMeta( 'sys.shadow.' . $property, NULL ); //clear shadow
				if ( strpos( $property, 'own' ) === 0 ) {
					list( $ownAdditions, $ownTrashcan, $ownresidue ) = $this->processGroups( $originals, $value, $ownAdditions, $ownTrashcan, $ownresidue );
					$listName = lcfirst( substr( $property, 3 ) );
					if ($bean->getMeta( 'sys.exclusive-'.  $listName ) ) {
						OODBBean::setMetaAll( $ownTrashcan, 'sys.garbage', TRUE );
					}
					unset( $bean->$property );
				} elseif ( strpos( $property, 'shared' ) === 0 ) {
					list( $sharedAdditions, $sharedTrashcan, $sharedresidue ) = $this->processGroups( $originals, $value, $sharedAdditions, $sharedTrashcan, $sharedresidue );
					unset( $bean->$property );
				}
			}
		}
		$this->storeBean( $bean );
		if ( !$this->isFrozen ) {
			$this->addForeignKeysForParentBeans( $bean, $embeddedBeans );
		}
		$this->processTrashcan( $bean, $ownTrashcan );
		$this->processAdditions( $bean, $ownAdditions );
		$this->processResidue( $ownresidue );
		$this->processSharedTrashcan( $bean, $sharedTrashcan );
		$this->processSharedAdditions( $bean, $sharedAdditions );
		$this->processSharedResidue( $bean, $sharedresidue );
	}

	/**
	 * Constructor, requires a query writer.
	 *
	 * @param QueryWriter $writer writer
	 */
	public function __construct( QueryWriter $writer )
	{
		if ( $writer instanceof QueryWriter ) {
			$this->writer = $writer;
		}
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
		
		$beans = array();
		for ( $i = 0; $i < $number; $i++ ) {
			$bean = new OODBBean;
			$bean->initializeForDispense( $type, $this->beanhelper );
			if ( !$this->isFrozen ) {
				$this->check( $bean );
			}
			$this->signal( 'dispense', $bean );
			$beans[] = $bean;
		}

		return ( count( $beans ) === 1 && !$alwaysReturnArray ) ? array_pop( $beans ) : $beans;
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
		$bean         = $this->unboxIfNeeded( $bean );
		$processLists = $this->hasListsOrObjects( $bean );
		if ( !$processLists && !$bean->getMeta( 'tainted' ) ) {
			return $bean->getID(); //bail out!
		}
		$this->signal( 'update', $bean );
		$processLists = $this->hasListsOrObjects( $bean ); //check again, might have changed by model!
		if ( $processLists ) {
			$this->processLists( $bean );
		} else {
			$this->storeBean( $bean );
		}
		$this->signal( 'after_update', $bean );

		return ( (string) $bean->id === (string) (int) $bean->id ) ? (int) $bean->id : (string) $bean->id;
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
	 * @throws SQL
	 *
	 * @return OODBBean
	 *
	 */
	public function load( $type, $id )
	{
		$bean = $this->dispense( $type );
		if ( isset( $this->stash[$this->nesting][$id] ) ) {
			$row = $this->stash[$this->nesting][$id];
		} else {
			try {
				$rows = $this->writer->queryRecord( $type, array( 'id' => array( $id ) ) );
			} catch ( SQL $exception ) {
				if ( $this->writer->sqlStateIn( $exception->getSQLState(),
					array(
						QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN,
						QueryWriter::C_SQLSTATE_NO_SUCH_TABLE )
				)
				) {
					$rows = 0;
					if ( $this->isFrozen ) {
						throw $exception; //only throw if frozen
					}
				}
			}
			if ( empty( $rows ) ) {
				return $bean;
			}
			$row = array_pop( $rows );
		}
		$bean->importRow( $row );
		$this->nesting++;
		$this->signal( 'open', $bean );
		$this->nesting--;

		return $bean->setMeta( 'tainted', FALSE );
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
		if ( $bean instanceof SimpleModel ) {
			$bean = $bean->unbox();
		}
		if ( !( $bean instanceof OODBBean ) ) {
			throw new RedException( 'OODB Store requires a bean, got: ' . gettype( $bean ) );
		}
		$this->signal( 'delete', $bean );
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
		if ( !$this->isFrozen ) {
			$this->check( $bean );
		}
		try {
			$this->writer->deleteRecord( $bean->getMeta( 'type' ), array( 'id' => array( $bean->id ) ), NULL );
		} catch ( SQL $exception ) {
			$this->handleException( $exception );
		}
		$bean->id = 0;
		$this->signal( 'after_delete', $bean );
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

	/**
	 * Returns an Association Manager for use with OODB.
	 * A simple getter function to obtain a reference to the association manager used for
	 * storage and more.
	 *
	 * @return AssociationManager
	 *
	 * @throws Security
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
}
