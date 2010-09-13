<?php
/**
 * RedBean Tree
 *
 * @file			RedBean/TreeManager.php
 * @description		Tree structure for beans.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_TreeManager extends RedBean_CompatManager {

	/**
	 * Specify what database systems are supported by this class.
	 * @var array $databaseSpecs
	 */
	protected $supportedSystems = array(
			  RedBean_CompatManager::C_SYSTEM_MYSQL => "5",
			  RedBean_CompatManager::C_SYSTEM_SQLITE=>"3"
	);
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
	 * @var RedBean_Adapter_DBAdapter
	 */
	private $adapter;

	/**
	 * @var RedBean_QueryWriter
	 */
	private $writer;

	/**
	 * Constructor.
	 * @param RedBean_ToolBox $tools
	 */
	public function __construct( RedBean_ToolBox $tools ) {


		$this->oodb = $tools->getRedBean();
		$this->adapter = $tools->getDatabaseAdapter();
		$this->writer = $tools->getWriter();
	}

	/**
	 * Checks whether types of beans match. If the types do not match
	 * this method will throw a RedBean_Exception_Security exception.
	 * @param RedBean_OODBBean $bean1
	 * @param RedBean_OODBBean $bean2
	 */
	private function equalTypes( RedBean_OODBBean $bean1, RedBean_OODBBean $bean2 ) {
		if ($bean1->getMeta("type")!==$bean2->getMeta("type")) {
			throw new RedBean_Exception_Security("Incompatible types, tree can only work with identical types.");
		}
	}


	/**
	 * Attaches the specified child node to the specified parent node.
	 * @param RedBean_OODBBean $parent
	 * @param RedBean_OODBBean $child
	 */
	public function attach( RedBean_OODBBean $parent, RedBean_OODBBean $child ) {

		$this->equalTypes( $parent, $child );

		$idfield = $this->writer->getIDField($parent->getMeta("type"));
		if (!intval($parent->$idfield)) $this->oodb->store($parent);
		$child->{$this->property} = $parent->$idfield;
		$this->oodb->store($child);
	}

	/**
	 * Returns all the nodes that have been attached to the specified
	 * parent node.
	 * @param RedBean_OODBBean $parent
	 * @return array $childObjects
	 */
	public function children( RedBean_OODBBean $parent ) {
		$idfield = $this->writer->getIDField($parent->getMeta("type"));
		try {
			$ids = $this->writer->selectByCrit( $idfield,
					  $parent->getMeta("type"),
					  $this->property,
					  intval( $parent->$idfield ) );

		}
		catch(RedBean_Exception_SQL $e) {
			return array();
		}
		return $this->oodb->batch($parent->getMeta("type"),$ids	);
	}


	public function getParent( RedBean_OODBBean $bean ) {
		return $this->oodb->load( $bean->getMeta("type"), (int)$bean->parent_id);
	}

}