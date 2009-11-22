<?php
/**
 * RedBean Domain Object
 * @file 		RedBean/DomainObject.php
 * @description		This class serves as a source of inspiration and
 *					is an example how a layer super type pattern can be
 *					used with RedBean. This class has not been tested.
 * @author			Gabor de Mooij
 * @license			BSD
 */
abstract class RedBean_DomainObject {


	/**
	 *
	 * @var RedBean_ToolBox
	 */
	protected $tools;

	/**
	 *
	 * @var RedBean_OODB
	 */
	protected $redbean;

	/**
	 *
	 * @var RedBean_OODBBean
	 */
	protected $bean;

	/**
	 *
	 * @var RedBean_AssociationManager
	 */
	protected $associationManager;


	/**
	 *
	 * @var RedBean_TreeManager
	 */
	protected $treeManager;

	/**
	 *
	 * Constructor, requires a type name
	 * @param string $typeName
	 */
	public function __construct( $typeName ) {
		$this->tools = RedBean_Setup::getToolBox();
		$this->redbean = $this->tools->getRedBean();
		$this->bean = $this->redbean->dispense( $typeName );
		$this->associationManager = new RedBean_AssociationManager($this->tools);
		$this->treeManager = new RedBean_TreeManager($this->tools);
	}

	/**
	 * Associates the bean inside with another OODBBean
	 * @param RedBean_DomainObject $other
	 */
	protected function associate(RedBean_DomainObject $other) {
		$this->associationManager->associate($this->bean, $other->bean);
	}

	/**
	 * Breaks the association between this OODBBean an the one belonging
	 * to the other model.
	 * @param RedBean_DomainObject $other
	 */
	protected function unassociate(RedBean_DomainObject $other) {
		$this->associationManager->unassociate($this->bean, $other->bean);
	}

	
	/**
	 *
	 * @param RedBean_DomainObject $other
	 */
	protected function set1toNAssoc(RedBean_DomainObject $other) {
		$this->associationManager->set1toNAssoc($this->bean, $other->bean);
	}

	/**
	 * Clears associations
	 */
	protected function clearRelations() {
		$this->associationManager->clearRelations($this->bean);
	}

	/**
	 *
	 * @param RedBean_DomainObject $other
	 */
	protected function attach(RedBean_DomainObject $other) {
		$this->treeManager->attach($this->bean, $other->bean);
	}

	
	/**
	 * PUBLIC FUNCTIONS
	 */
	 
	 
	 /**
	  * Loads the Bean internally
	  * @param integer $id 
	  */
	 public function find( $id ) {
		 $this->bean = $this->redbean->load( $this->bean->getMeta("type"), (int) $id );
	 }


}