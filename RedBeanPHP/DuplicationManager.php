<?php

namespace RedBeanPHP;

use RedBeanPHP\ToolBox as ToolBox;
use RedBeanPHP\AssociationManager as AssociationManager;
use RedBeanPHP\OODB as OODB;
use RedBeanPHP\OODBBean as OODBBean;
use RedBeanPHP\QueryWriter\AQueryWriter as AQueryWriter;

/**
 * Duplication Manager
 *
 * @file    RedBean/DuplicationManager.php
 * @desc    Creates deep copies of beans
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
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
	 * @param string           $shared name of the shared list
	 * @param array            $beans  array with shared beans to copy
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
	 * @param string           $owned       name of the own list
	 * @param array            $beans       array with shared beans to copy
	 * @param array            $trail       array with former beans to detect recursion
	 * @param boolean          $preserveIDs TRUE means preserve IDs, for export only
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

		$copy->importFrom( $bean );
		$copy->id = 0;

		return $copy;
	}

	/**
	 * Generates a key from the bean type and its ID and determines if the bean
	 * occurs in the trail, if not the bean will be added to the trail.
	 * Returns TRUE if the bean occurs in the trail and FALSE otherwise.
	 *
	 * @param array            $trail list of former beans
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
	 * @param OODBBean $bean          bean to be copied
	 * @param array            $trail         trail to prevent infinite loops
	 * @param boolean          $preserveIDs   preserve IDs
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
	 * For better performance you can pass the tables in an array to this method.
	 * If the tables are available the duplication manager will not query them so
	 * this might be beneficial for performance.
	 *
	 * @param array $tables
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
	 * @param boolean $yesNo
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
	 * @param array $filters
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
	 * @param OODBBean $bean          bean to be copied
	 * @param array            $trail         for internal usage, pass array()
	 * @param boolean          $preserveIDs   for internal usage
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

		$rs = $this->duplicate( clone( $bean ), $trail, $preserveIDs );

		if ( !$this->cacheTables ) {
			$this->tables  = array();
			$this->columns = array();
		}

		return $this->duplicate( $rs, $trail, $preserveIDs );
	}

	/**
	 * Exports a collection of beans. Handy for XML/JSON exports with a
	 * Javascript framework like Dojo or ExtJS.
	 * What will be exported:
	 * - contents of the bean
	 * - all own bean lists (recursively)
	 * - all shared beans (not THEIR own lists)
	 *
	 * @param   array|OODBBean  $beans   beans to be exported
	 * @param   boolean                 $parents also export parents
	 * @param   array                   $filters only these types (whitelist)
	 *
	 * @return array
	 */
	public function exportAll( $beans, $parents = FALSE, $filters = array() )
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

		return $array;
	}
}
