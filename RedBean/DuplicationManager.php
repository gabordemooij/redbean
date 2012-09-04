<?php
/**
 * RedBean Duplication Manager
 * 
 * @file			RedBean/DuplicationManager.php
 * @description		Creates deep copies of beans
 * 		
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
	 * The Dup Manager requires a toolbox
	 * @var RedBean_Toolbox 
	 */
	protected $toolbox;
	
	/**
	 * Association Manager 
	 * @var RedBean_AssociationManager
	 */
	protected $associationManager;
	
	/**
	 * RedBeanPHP OODB instance
	 * @var RedBean_OODBBean 
	 */
	protected $redbean;
	
	protected $tables = array();
	protected $filters = array();
	protected $cacheTables = false;
	/**
	 * Constructor,
	 * creates a new instance of DupManager.
	 * @param RedBean_Toolbox $toolbox 
	 */
	public function __construct( RedBean_Toolbox $toolbox ) {
		$this->toolbox = $toolbox;
		$this->redbean = $toolbox->getRedBean();
		$this->associationManager = $this->redbean->getAssociationManager();
	}
	
	
	public function setTables($tables) {
		$this->tables = $tables;
		$this->cacheTables = true;
	}
	
	public function setCacheTables($yesNo) {
		$this->cacheTables = $yesNo;
	}
	
	public function setFilters($filters) {
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
	 * @param RedBean_OODBBean $bean  bean to be copied
	 * @param array            $trail for internal usage, pass array()
	 * @param boolean          $pid   for internal usage
	 *
	 * @return array $copiedBean the duplicated bean
	 */
	public function dup($bean,$trail=array(),$pid=false) {
		if (!count($this->tables))  $this->tables = $this->toolbox->getWriter()->getTables();
		$beanCopy = clone($bean);
		$rs = $this->duplicate($beanCopy,$trail,$pid);
		if (!$this->cacheTables) $this->tables = array();
		return $rs;
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
	 * @param RedBean_OODBBean $bean  bean to be copied
	 * @param array            $trail for internal usage, pass array()
	 * @param boolean          $pid   for internal usage
	 *
	 * @return array $copiedBean the duplicated bean
	 */
	protected function duplicate($bean,$trail=array(),$pid=false) {
	
	$type = $bean->getMeta('type');
		$key = $type.$bean->getID();
		if (isset($trail[$key])) return $bean;
		$trail[$key]=$bean;
		$copy =$this->redbean->dispense($type);
		$copy->import( $bean->getProperties() );
		$copy->id = 0;
		$tables = $this->tables;
		foreach($tables as $table) {
			if (count($this->filters) && !in_array($table,$this->filters)) continue;
			if (strpos($table,'_')!==false || $table==$type) continue;
			$owned = 'own'.ucfirst($table);
			$shared = 'shared'.ucfirst($table);
			if ($beans = $bean->$owned) {
				$copy->$owned = array();
				foreach($beans as $subBean) {
					array_push($copy->$owned,$this->duplicate($subBean,$trail,$pid));
				}
			}
			$copy->setMeta('sys.shadow.'.$owned,null);
			if ($beans = $bean->$shared) {
				$copy->$shared = array();
				foreach($beans as $subBean) {
					array_push($copy->$shared,$subBean);
				}
			}
			$copy->setMeta('sys.shadow.'.$shared,null);

		}

		if ($pid) $copy->id = $bean->id;
		return $copy;
	}
}