<?php
/**
 * Duplication Manager
 * 
 * @file			RedBean/DuplicationManager.php
 * @desc			Creates deep copies of beans
 * @author			Gabor de Mooij and the RedBeanPHP Community
 * @license			BSD/GPLv2
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 *
 */
class RedBean_DuplicationManager {
	/**
	 * @var RedBean_Toolbox
	 */
	protected $toolbox;
	/**
	 * @var RedBean_AssociationManager
	 */
	protected $associationManager;
	/**
	 * @var RedBean_OODB
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
	protected $cacheTables = false;
	/**
	 * Constructor,
	 * creates a new instance of DupManager.
	 * @param RedBean_Toolbox $toolbox 
	 */
	public function __construct(RedBean_Toolbox $toolbox) {
		$this->toolbox = $toolbox;
		$this->redbean = $toolbox->getRedBean();
		$this->associationManager = $this->redbean->getAssociationManager();
	}
	/**
	 * For better performance you can pass the tables in an array to this method.
	 * If the tables are available the duplication manager will not query them so
	 * this might be beneficial for performance.
	 * 
	 * @param array $tables 
	 */
	public function setTables($tables) {
		foreach($tables as $key => $value) {
			if (is_numeric($key)) {
				$this->tables[] = $value;
			} else {
				$this->tables[] = $key;
				$this->columns[$key] = $value;
			}
		}
		$this->cacheTables = true;
	}
	/**
	 * Returns a schema array for cache.
	 * 
	 * @return array 
	 */
	public function getSchema() {
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
	public function setCacheTables($yesNo) {
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
	public function setFilters($filters) {
		$this->filters = $filters;
	}
	/**
	 * Determines whether the bean has an own list based on
	 * schema inspection from realtime schema or cache.
	 * 
	 * @param string $type   bean type
	 * @param string $target type of list you want to detect
	 * 
	 * @return boolean 
	 */
	protected function hasOwnList($type, $target) {
		return (isset($this->columns[$target][$type.'_id']));
	}
	/**
	 * Determines whether the bea has a shared list based on
	 * schema inspection from realtime schema or cache.
	 * 
	 * @param string $type   bean type
	 * @param string $target type of list you are looking for
	 * 
	 * @return boolean 
	 */
	protected function hasSharedList($type, $target) {
		return (in_array(RedBean_QueryWriter_AQueryWriter::getAssocTableFormat(array($type, $target)), $this->tables));
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
	 * @param RedBean_OODBBean $bean  bean to be copied
	 * @param array            $trail for internal usage, pass array()
	 * @param boolean          $pid   for internal usage
	 *
	 * @return array $copiedBean the duplicated bean
	 */
	public function dup($bean, $trail = array(), $pid = false) {
		if (!count($this->tables))  $this->tables = $this->toolbox->getWriter()->getTables();
		if (!count($this->columns)) foreach($this->tables as $table) $this->columns[$table] = $this->toolbox->getWriter()->getColumns($table);
		$beanCopy = clone($bean);
		$rs = $this->duplicate($beanCopy, $trail, $pid);
		if (!$this->cacheTables) {
			$this->tables = array();
			$this->columns = array();
		}
		return $rs;
	}
	/**
	 * @see RedBean_DuplicationManager::dup
	 *
	 * @param RedBean_OODBBean $bean  bean to be copied
	 * @param array            $trail trail to prevent infinite loops
	 * @param boolean          $pid   preserve IDs
	 *
	 * @return array $copiedBean the duplicated bean
	 */
	protected function duplicate($bean, $trail = array(), $pid = false) {
		$type = $bean->getMeta('type');
		$key = $type.$bean->getID();
		if (isset($trail[$key])) return $bean;
		$trail[$key] = $bean;
		$copy = $this->redbean->dispense($type);
		$copy->importFrom($bean);
		$copy->id = 0;
		$tables = $this->tables;
		foreach($tables as $table) {
			if (is_array($this->filters) && count($this->filters) && !in_array($table, $this->filters)) continue;
			if ($table == $type) continue;
			$owned = 'own'.ucfirst($table);
			$shared = 'shared'.ucfirst($table);
			if ($this->hasSharedList($type, $table)) {
				if ($beans = $bean->$shared) {
					$copy->$shared = array();
					foreach($beans as $subBean) {
						array_push($copy->$shared, $subBean);
					}
				}
			} elseif ($this->hasOwnList($type, $table)) {
				if ($beans = $bean->$owned) {
					$copy->$owned = array();
					foreach($beans as $subBean) {
						array_push($copy->$owned, $this->duplicate($subBean, $trail, $pid));
					}
				}
				$copy->setMeta('sys.shadow.'.$owned, null);
			}
			$copy->setMeta('sys.shadow.'.$shared, null);
		}
		if ($pid) $copy->id = $bean->id;
		return $copy;
	}
	/**
	 * Exports a collection of beans. Handy for XML/JSON exports with a
	 * Javascript framework like Dojo or ExtJS.
	 * What will be exported:
	 * - contents of the bean
	 * - all own bean lists (recursively)
	 * - all shared beans (not THEIR own lists)
	 *
	 * @param	array|RedBean_OODBBean $beans   beans to be exported
	 * @param   boolean				   $parents also export parents
	 * @param   array                  $filters only these types (whitelist)
	 * 
	 * @return	array $array exported structure
	 */
	public function exportAll($beans, $parents = false, $filters = array()) {
		$array = array();
		if (!is_array($beans)) $beans = array($beans);
		foreach($beans as $bean) {
			   $this->setFilters($filters);
			   $f = $this->dup($bean, array(), true);
			   $array[] = $f->export(false, $parents, false, $filters);
		}
		return $array;
	}
}