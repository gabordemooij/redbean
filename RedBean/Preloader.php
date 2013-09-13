<?php
/**
 * Preloader.
 *
 * @file    RedBean/Preloader.php
 * @desc    Used by OODB to facilitate preloading or eager loading
 * @author  Gabor de Mooij and the RedBeanPHP community
 * @license BSD/GPLv2
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_Preloader
{

	/**
	 * @var RedBean_AssociationManager
	 */
	private $assocManager;

	/**
	 * @var RedBean_OODB
	 */
	private $oodb;

	/**
	 * @var integer
	 */
	private $counterID = 0;

	/**
	 * @var array
	 */
	private $filteredBeans = array();

	/**
	 * @var array
	 */
	private $retrievals = array();

	/**
	 * @var integer
	 */
	private $iterationIndex = 0;

	/**
	 * Extracts the type list for preloader.
	 * Explodes a list of comma separated types and splits
	 * the type definition in list -> type pairs if needed.
	 *
	 * @param array|string $typeList list of types
	 *
	 * @return array
	 */
	private function extractTypesFromTypeList( $typeList )
	{
		if ( !is_string( $typeList ) ) {
			return $typeList;
		}

		$typeList = explode( ',', $typeList );

		$types = array();
		foreach ( $typeList as $value ) {
			if ( strpos( $value, '|' ) !== FALSE ) {
				list( $key, $newValue ) = explode( '|', $value );

				$types[$key] = $newValue;
			} else {
				$types[] = $value;
			}
		}

		return $types;
	}

	/**
	 * Extracts preloading request for type from array.
	 *
	 * @param array $typeInfo type info
	 *
	 * @return array
	 */
	private function extractTypeInfo( $typeInfo )
	{
		list( $type, $sqlObj ) = ( is_array( $typeInfo ) ? $typeInfo : array( $typeInfo, NULL ) );

		list( $sql, $bindings ) = $sqlObj;

		if ( !is_array( $bindings ) ) {
			$bindings = array();
		}

		return array( $type, $sql, $bindings );
	}

	/**
	 * Marks the input beans.
	 * This method is used to connect the current selection of beans to
	 * input beans.
	 *
	 * @param array $beans beans to connect to input beans
	 *
	 * @return array
	 */
	private function markBeans( $filteredBeans )
	{
		$this->counterID = 0;

		foreach ( $filteredBeans as $bean ) {
			$bean->setMeta( 'sys.input-bean-id', array( $this->counterID => $this->counterID ) );
			$this->counterID++;
		}

		return $filteredBeans;
	}

	/**
	 * Adds the beans from the next step in the path to the collection of filtered
	 * beans.
	 *
	 * @param string $nesting       property (list or bean property)
	 *
	 * @return void
	 */
	private function addBeansForNextStepInPath( $nesting )
	{
		$filtered = array();
		foreach ( $this->filteredBeans as $bean ) {
			$addInputIDs = $bean->getMeta( 'sys.input-bean-id' );

			if ( is_array( $bean->$nesting ) ) {
				$nestedBeans = $bean->$nesting;

				foreach ( $nestedBeans as $nestedBean ) {
					$this->addInputBeanIDsToBean( $nestedBean, $addInputIDs );
				}

				$filtered = array_merge( $filtered, $nestedBeans );
			} elseif ( !is_null( $bean->$nesting ) ) {
				$this->addInputBeanIDsToBean( $bean->$nesting, $addInputIDs );
				$filtered[] = $bean->$nesting;
			}
		}

		$this->filteredBeans = $filtered;
	}

	/**
	 * Expands * and & symbols in preload syntax.
	 * Also adds the returned field name to the list of fields.
	 *
	 * @param string $key       key value for this field
	 * @param string $type      type of bean
	 * @param string $oldField  last field we've processed
	 * @param array  $oldFields list of previously gathered field names
	 *
	 * @return string
	 */
	private function getPreloadField( $key, $type, $oldField, &$oldFields )
	{
		$field = ( is_numeric( $key ) ) ? $type : $key; //use an alias?

		if ( strpos( $field, '*' ) !== FALSE ) {
			$oldFields[] = $oldField;
			$field       = str_replace( '*', implode( '.', $oldFields ), $field );
		}

		if ( strpos( $field, '&' ) !== FALSE ) {
			$field = str_replace( '&', implode( '.', $oldFields ), $field );
		}

		return $field;
	}

	/**
	 * For Preloader: adds the IDs of your input beans to the nested beans, otherwise
	 * we dont know how to pass them to the each-function later on.
	 *
	 * @param RedBean_OODBBean $nestedBean  nested bean
	 * @param array            $addInputIDs input ids
	 *
	 * @return void
	 */
	private function addInputBeanIDsToBean( $nestedBean, $addInputIDs )
	{
		$currentInputBeanIDs = $nestedBean->getMeta( 'sys.input-bean-id' );

		if ( !is_array( $currentInputBeanIDs ) ) {
			$currentInputBeanIDs = array();
		}

		foreach ( $addInputIDs as $addInputID ) {
			$currentInputBeanIDs[$addInputID] = $addInputID;
		}

		$nestedBean->setMeta( 'sys.input-bean-id', $currentInputBeanIDs );
	}

	/**
	 * For preloader: calls the function defined in $closure with retrievals for each
	 * bean in the first parameter.
	 *
	 * @param closure|string $closure    closure to invoke per bean
	 * @param array          $beans      beans to iterate over
	 * @param array          $retrievals retrievals to send as arguments to closure
	 *
	 * @return void
	 */
	private function invokePreloadEachFunction( $closure, $beans, $retrievals )
	{
		if ( $closure ) {
			$key = 0;

			foreach ( $beans as $bean ) {
				$bindings = array();

				foreach ( $retrievals as $r ) {
					$bindings[] = ( isset( $r[$key] ) ) ? $r[$key] : NULL;
				}

				array_unshift( $bindings, $bean );

				call_user_func_array( $closure, $bindings );

				$key++;
			}
		}
	}

	/**
	 * Fills the retrieval array with the beans in the (recently) retrieved
	 * shared/own list. This method asks the $filteredBean which original input bean
	 * it belongs to, then it will fill the parameter array for the specified
	 * iteration with the beans obtained for the filtered bean. This ensures the
	 * callback function for ::each will receive the correct bean lists as
	 * parameters for every iteration.
	 *
	 * @param RedBean_OODBBean $filteredBean         the bean we've retrieved lists for
	 * @param array            $list                 the list we've retrieved for the bean
	 *
	 * @return void
	 */
	private function fillParamArrayRetrievals( $filteredBean, $list )
	{
		$inputBeanIDs = $filteredBean->getMeta( 'sys.input-bean-id' );

		foreach ( $inputBeanIDs as $inputBeanID ) {
			if ( !isset( $this->retrievals[$this->iterationIndex][$inputBeanID] ) ) {
				$this->retrievals[$this->iterationIndex][$inputBeanID] = array();
			}

			foreach ( $list as $listKey => $listBean ) {
				$this->retrievals[$this->iterationIndex][$inputBeanID][$listKey] = $listBean;
			}
		}
	}

	/**
	 * Fills retrieval array with parent beans.
	 *
	 * @param array            $inputBeanIDs ids
	 * @param RedBean_OODBBean $parent       parent bean
	 */
	private function fillParamArrayRetrievalsWithParent( $inputBeanIDs, $parent )
	{
		foreach ( $inputBeanIDs as $inputBeanID ) {
			$this->retrievals[$this->iterationIndex][$inputBeanID] = $parent;
		}
	}

	/**
	 * Gathers the IDs to preload and maps the ids to the original beans.
	 *
	 * @param array  $filteredBeans filtered beans
	 * @param string $field         field name
	 *
	 * @return array
	 */
	private function gatherIDsToPreloadAndMap( $filteredBeans, $field )
	{
		$ids = $map = array();

		if ( strpos( $field, 'shared' ) !== 0 ) {
			// Gather ids to load the desired bean collections
			foreach ( $filteredBeans as $bean ) {

				if ( strpos( $field, 'own' ) === 0 ) {
					// Based on bean->id for ownlist
					$id       = $bean->id;
					$ids[$id] = $id;
				} elseif ( $id = $bean->{$field . '_id'} ) {
					// Based on bean_id for parent
					$ids[$id] = $id;

					if ( !isset( $map[$id] ) ) {
						$map[$id] = array();
					}

					$map[$id][] = $bean;
				}
			}
		}

		return array( $ids, $map );
	}

	/**
	 * Gathers the own list for a bean from a pool of child beans loaded by
	 * the preloader.
	 *
	 * @param RedBean_OODBBean $filteredBean
	 * @param array            $children
	 * @param string           $link
	 *
	 * @return array
	 */
	private function gatherOwnBeansFromPool( $filteredBean, $children, $link )
	{
		$list = array();
		foreach ( $children as $child ) {
			if ( $child->$link == $filteredBean->id ) {
				$list[$child->id] = $child;
			}
		}

		return $list;
	}

	/**
	 * Gathers the shared list for a bean from a pool of shared beans loaded
	 * by the preloader.
	 *
	 * @param RedBean_OODBBean $filteredBean
	 * @param array            $sharedBeans
	 *
	 * @return array
	 */
	private function gatherSharedBeansFromPool( $filteredBean, $sharedBeans )
	{
		$list = array();
		foreach ( $sharedBeans as $sharedBean ) {
			if ( in_array( $filteredBean->id, $sharedBean->getMeta( 'sys.belongs-to' ) ) ) {
				$list[] = $sharedBean;
			}
		}

		return $list;
	}

	/**
	 * Initializes the preloader.
	 * Initializes the filtered beans array, the retrievals array and
	 * the iteration index.
	 */
	private function init()
	{
		$this->iterationIndex = 0;
		$this->retrievals     = array();
		$this->filteredBeans  = array();
	}

	/**
	 * Preloads the shared beans.
	 *
	 * @param string $type     type of beans to load
	 * @param string $sql      additional SQL snippet for loading
	 * @param array  $bindings parameter bindings for SQL snippet
	 * @param string $field    field to store preloaded beans in
	 *
	 * @return void
	 */
	private function preloadSharedBeans( $type, $sql, $bindings, $field )
	{
		$sharedBeans = $this->assocManager->relatedSimple( $this->filteredBeans, $type, $sql, $bindings );

		// Let the filtered beans gather their beans
		foreach ( $this->filteredBeans as $filteredBean ) {
			$list = $this->gatherSharedBeansFromPool( $filteredBean, $sharedBeans );

			$filteredBean->setProperty( $field, $list, TRUE, TRUE );

			$this->fillParamArrayRetrievals( $filteredBean, $list );
		}
	}

	/**
	 * Preloads the own beans.
	 *
	 * @param string $type     type of beans to load
	 * @param string $sql      additional SQL snippet for loading
	 * @param array  $bindings parameter bindings for SQL snippet
	 * @param string $field    field to store preloaded beans in
	 * @param array  $ids      list of ids to load
	 *
	 * @return void
	 */
	private function preloadOwnBeans( $type, $sql, $bindings, $field, $ids )
	{
		$bean = reset( $this->filteredBeans );
		$link = $bean->getMeta( 'type' ) . '_id';

		$children = $this->oodb->find( $type, array( $link => $ids ), $sql, $bindings );

		foreach ( $this->filteredBeans as $filteredBean ) {
			$list = $this->gatherOwnBeansFromPool( $filteredBean, $children, $link );

			$filteredBean->setProperty( $field, $list, TRUE, TRUE );

			$this->fillParamArrayRetrievals( $filteredBean, $list );
		}
	}

	/**
	 * Preloads parent beans.
	 *
	 * @param string $type  type of bean to load
	 * @param string $field field to store parent in
	 * @param array  $ids   list of ids to load
	 * @param array  $map   mapping to use (children indexed by parent bean ids)
	 *
	 * @return void
	 */
	private function preloadParentBeans( $type, $field, $ids, $map )
	{
		foreach ( $this->oodb->batch( $type, $ids ) as $parent ) {
			foreach ( $map[$parent->id] as $childBean ) {
				$childBean->setProperty( $field, $parent );

				$inputBeanIDs = $childBean->getMeta( 'sys.input-bean-id' );

				$this->fillParamArrayRetrievalsWithParent( $inputBeanIDs, $parent );
			}
		}
	}

	/**
	 * Simple input correction function. Checks whether input is a single bean
	 * and wraps it in an array if necessary.
	 *
	 * @param RedBean_OODBBean|array $beanOrBeans input
	 *
	 * @return array
	 */
	private function convertBeanToArrayIfNeeded( $beanOrBeans )
	{
		if ( !is_array( $beanOrBeans ) ) {
			$beanOrBeans = array( $beanOrBeans );
		}

		return $beanOrBeans;
	}

	/**
	 * Constructor.
	 *
	 * @param RedBean_OODB $oodb
	 */
	public function __construct( $oodb )
	{
		$this->oodb = $oodb;

		$this->assocManager = $oodb->getAssociationManager();
	}

	/**
	 * Preloads certain properties for beans.
	 * Understands aliases.
	 *
	 * @param array $beans beans
	 * @param array $types types to load
	 *
	 * @return array
	 */
	public function load( $beans, $typeList, $closure = NULL )
	{
		$beans = $this->convertBeanToArrayIfNeeded( $beans );

		$this->init();

		$types     = $this->extractTypesFromTypeList( $typeList );

		$oldFields = array();

		$oldField = '';

		foreach ( $types as $key => $typeInfo ) {
			list( $type, $sql, $bindings ) = $this->extractTypeInfo( $typeInfo );

			$this->retrievals[$this->iterationIndex] = array();

			$field = $this->getPreloadField( $key, $type, $oldField, $oldFields );

			$this->filteredBeans = $this->markBeans( $beans );

			// Filtering: find the right beans in the path
			while ( $p = strpos( $field, '.' ) ) {
				$this->addBeansForNextStepInPath( substr( $field, 0, $p ) );

				$field = substr( $field, $p + 1 );
			}

			$oldField = $field;

			$type = ( strpos( $type, '.' ) !== FALSE ) ? $field : $type;

			if ( count( $this->filteredBeans ) === 0 ) continue;

			list( $ids, $map ) = $this->gatherIDsToPreloadAndMap( $this->filteredBeans, $field );

			if ( strpos( $field, 'shared' ) === 0 ) {
				$this->preloadSharedBeans( $type, $sql, $bindings, $field );
			} elseif ( strpos( $field, 'own' ) === 0 ) {
				// Preload for own-list using find
				$this->preloadOwnBeans( $type, $sql, $bindings, $field, $ids );
			} else {
				// Preload for parent objects using batch()
				$this->preloadParentBeans( $type, $field, $ids, $map );
			}

			$this->iterationIndex++;
		}

		$this->invokePreloadEachFunction( $closure, $beans, $this->retrievals );
	}
}
