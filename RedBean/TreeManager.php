<?php
/**
 * RedBean Tree
 *
 * @package 		RedBean/TreeManager.php
 * @description		Shields you from race conditions automatically.
 * @author			Gabor de Mooij
 * @license			BSD
 */
class RedBean_TreeManager {

	/**
	 *
	 * @var string
	 */
	private $property = "parent_id";

	/**
	 * @var RedBean_OODB
	 */
	private $oodb;

	/**
	 * @var RedBean_DBAdapter
	 */
	private $adapter;

	/**
	 * Constructor
	 * @param RedBean_ToolBox $tools
	 */
	public function __construct( RedBean_ToolBox $tools ) {
		$this->oodb = $tools->getRedBean();
		$this->adapter = $tools->getDatabaseAdapter();
	}

	/**
	 *
	 * @param RedBean_OODBBean $parent
	 * @param RedBean_OODBBean $child
	 */
	public function attach( RedBean_OODBBean $parent, RedBean_OODBBean $child ) {

		if (!intval($parent->id)) $this->oodb->store($parent);
		$child->{$this->property} = $parent->id;
		$this->oodb->store($child);
	}

	/**
	 *
	 * @param RedBean_OODBBean $parent
	 * @return array $childObjects
	 */
	public function children( RedBean_OODBBean $parent ) {
		try {$ids = $this->adapter->getCol("SELECT id FROM
			`".$parent->getMeta("type")."`
			WHERE `".$this->property."` = ".intval( $parent->id )."
		");
		}
		catch(RedBean_Exception_SQL $e) {
			return array();
		}
		return $this->oodb->batch($parent->getMeta("type"),$ids	);
	}
	
}