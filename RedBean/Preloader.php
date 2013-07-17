<?php
/**
 * Preloader
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
class RedBean_Preloader {
	
	/**
	 * @var RedBean_AssociationManager
	 */
	protected $assocManager;
	
	/**
	 * @var RedBean_OODB 
	 */
	protected $oodb;
	
	/**
	 * @var integer
	 */
	protected $counterID = 0;
	
	/**
	 * Extracts the type list for preloader.
	 * 
	 * @param array|string $typeList
	 * 
	 * @return array
	 */
	private function extractTypesFromTypeList($typeList) {
		if (is_string($typeList)) {
			$typeList = explode(',', $typeList);
			foreach($typeList as $value) {
				if (strpos($value, '|') !== false) {
					list($key, $newValue) = explode('|', $value);
					$types[$key] = $newValue;
				} else { 
					$types[] = $value;
				}
			}
		} else {
			$types = $typeList;
		}
		return $types;
	}
	
	/**
	 * Marks the input beans.
	 * 
	 * @param array $beans beans
	 */
	private function markBeans($filteredBeans) {
		$this->counterID = 0;
		foreach($filteredBeans as $bean) {
			$bean->setMeta('sys.input-bean-id', array($this->counterID => $this->counterID));
			$this->counterID++;
		}
		return $filteredBeans;
	}
	
	/**
	 * Adds the beans from the next step in the path to the collection of filtered
	 * beans.
	 * 
	 * @param array  $filteredBeans list of filtered beans
	 * @param string $nesting       property (list or bean property)
	 */
	private function addBeansForNextStepInPath(&$filteredBeans, $nesting) {
		$filtered = array();
		foreach($filteredBeans as $bean) {
			$addInputIDs = $bean->getMeta('sys.input-bean-id');
			if (is_array($bean->$nesting)) {
				$nestedBeans = $bean->$nesting;
				foreach($nestedBeans as $nestedBean) {
					$this->addInputBeanIDsToBean($nestedBean, $addInputIDs);
				}
				$filtered = array_merge($filtered, $nestedBeans);
			} elseif (!is_null($bean->$nesting)) {
				$this->addInputBeanIDsToBean($bean->$nesting, $addInputIDs);
				$filtered[] = $bean->$nesting;
			}
		}
		$filteredBeans = $filtered;
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
	private function getPreloadField($key, $type, $oldField, &$oldFields) {
		$field = (is_numeric($key)) ? $type : $key;//use an alias?
		if (strpos($field, '*') !== false) { 
			$oldFields[] = $oldField; 
			$field = str_replace('*', implode('.', $oldFields), $field);
		}
		if (strpos($field, '&') !== false) {
			$field = str_replace('&', implode('.', $oldFields), $field);
		}
		return $field;
	}
	
	/**
	 * For Preloader: adds the IDs of your input beans to the nested beans, otherwise
	 * we dont know how to pass them to the each-function later on.
	 * 
	 * @param RedBean_OODBBean $nestedBean
	 * @param array $addInputIDs
	 */
	private function addInputBeanIDsToBean($nestedBean, $addInputIDs) {
		$currentInputBeanIDs = $nestedBean->getMeta('sys.input-bean-id'); 
		if (!is_array($currentInputBeanIDs)) {
			$currentInputBeanIDs = array();
		}
		foreach($addInputIDs as $addInputID) {
			$currentInputBeanIDs[$addInputID] = $addInputID;
		}
		$nestedBean->setMeta('sys.input-bean-id', $currentInputBeanIDs);
	}

	/**
	 * For preloader: calls the function defined in $closure with retrievals for each
	 * bean in the first parameter.
	 * 
	 * @param closure|string $closure    closure to invoke per bean
	 * @param array          $beans      beans to iterate over
	 * @param array          $retrievals retrievals to send as arguments to closure
	 */
	private function invokePreloadEachFunction($closure, $beans, $retrievals) {
		if ($closure) {
			$key = 0; 
			foreach($beans as $bean) {
				$bindings = array();
				foreach($retrievals as $r) $bindings[] = (isset($r[$key])) ? $r[$key] : null; 
				array_unshift($bindings, $bean);
				call_user_func_array($closure, $bindings);
				$key ++;
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
	 * @param array            $retrievals     reference to the retrieval array
	 * @param RedBean_OODBBean $filteredBean   the bean we've retrieved lists for
	 * @param array            $list				 the list we've retrieved for the bean	
	 * @param integer          $iterationIndex the iteration index of the param array we're going to fill
	 */
	private function fillParamArrayRetrievals(&$retrievals, $filteredBean, $list, $iterationIndex) {
		$inputBeanIDs = $filteredBean->getMeta('sys.input-bean-id');
		foreach($inputBeanIDs as $inputBeanID) {
			if (!isset($retrievals[$iterationIndex][$inputBeanID])) {
				$retrievals[$iterationIndex][$inputBeanID] = array();
			}
			foreach($list as $listKey => $listBean) {
				$retrievals[$iterationIndex][$inputBeanID][$listKey] = $listBean;
			}
		}
	}
	
	/**
	 * Gathers the IDs to preload and maps the ids to the original beans.
	 * 
	 * @param array $filteredBeans
	 * @param string $field
	 * @return array
	 */
	private function gatherIDsToPreloadAndMap($filteredBeans, $field) {
		$ids = $map = array();
		if (strpos($field, 'shared') !== 0) {
			foreach($filteredBeans as $bean) { //gather ids to load the desired bean collections
				if (strpos($field, 'own') === 0) { //based on bean->id for ownlist
					$id = $bean->id; $ids[$id] = $id;
				} elseif($id = $bean->{$field.'_id'}){ //based on bean_id for parent
					$ids[$id] = $id; 
					if (!isset($map[$id])) {
						$map[$id] = array();
					}
					$map[$id][] = $bean;
				}
			}
		}
		return array($ids, $map);
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
	private function gatherOwnBeansFromPool($filteredBean, $children, $link) {
		$list = array();
		foreach($children as $child) {
			if ($child->$link == $filteredBean->id) {
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
	private function gatherSharedBeansFromPool($filteredBean, $sharedBeans) {
		$list = array();
		foreach($sharedBeans as $sharedBean) {
			if (in_array($filteredBean->id, $sharedBean->getMeta('sys.belongs-to'))) {
				$list[] = $sharedBean;
			}
		}
		return $list;
	}
	
	/**
	 * Constructor
	 * @param RedBean_OODB $oodb
	 */
	public function __construct($oodb) {
		$this->oodb = $oodb;
		$this->assocManager = $oodb->getAssociationManager();
	}
	
	/**
	 * Preloads certain properties for beans.
	 * Understands aliases.
	 * 
	 * @param array $beans beans
	 * @param array $types types to load
	 */
	public function load($beans, $typeList, $closure = null) {
		if (!is_array($beans)) {
			$beans = array($beans);
		}
		$types = $this->extractTypesFromTypeList($typeList);
		$oldFields = $retrievals = array();
		$i = 0;
		$oldField = '';
		foreach($types as $key => $typeInfo) {
			list($type,$sqlObj) = (is_array($typeInfo) ? $typeInfo : array($typeInfo, null));
			list($sql, $bindings) = $sqlObj;
			if (!is_array($bindings)) {
					$bindings = array();
			}
			$map = $ids = $retrievals[$i] = array();
			$field = $this->getPreloadField($key, $type, $oldField, $oldFields);
			$filteredBeans = $this->markBeans($beans);
			while($p = strpos($field, '.')) { //filtering: find the right beans in the path
				$nesting = substr($field, 0, $p);
				$this->addBeansForNextStepInPath($filteredBeans, $nesting);
				$field = substr($field, $p+1);
			}
			$oldField = $field;
			if (strpos($type, '.')) {
				$type = $field;
			}
			if (count($filteredBeans) === 0) continue;
			list($ids, $map) = $this->gatherIDsToPreloadAndMap($filteredBeans, $field);
			if (strpos($field, 'shared') === 0) {
				$sharedBeans = $this->assocManager->relatedSimple($filteredBeans, $type, $sql, $bindings);
				foreach($filteredBeans as $filteredBean) { //now let the filtered beans gather their beans
					$list = $this->gatherSharedBeansFromPool($filteredBean, $sharedBeans);
					$filteredBean->setProperty($field, $list, true, true);
					$this->fillParamArrayRetrievals($retrievals, $filteredBean, $list, $i);
				}
			} elseif (strpos($field, 'own') === 0) {//preload for own-list using find
				$bean = reset($filteredBeans);
				$link = $bean->getMeta('type').'_id';
				$children = $this->oodb->find($type, array($link => $ids), $sql, $bindings);
				foreach($filteredBeans as $filteredBean) {
					$list = $this->gatherOwnBeansFromPool($filteredBean, $children, $link);
					$filteredBean->setProperty($field, $list, true, true);
					$this->fillParamArrayRetrievals($retrievals, $filteredBean, $list, $i);
				}
			} else { //preload for parent objects using batch()
				foreach($this->oodb->batch($type, $ids) as $parent) {
					foreach($map[$parent->id] as $childBean) {
						$childBean->setProperty($field, $parent);
						$inputBeanIDs = $childBean->getMeta('sys.input-bean-id');
						foreach($inputBeanIDs as $inputBeanID) {
							$retrievals[$i][$inputBeanID] = $parent;
						}
					}
				}
			}
			$i++;
		}
		$this->invokePreloadEachFunction($closure, $beans, $retrievals);
	}
}