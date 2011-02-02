<?php
/**
 * @deprecated
 *
 * RedBean Domain Object
 * @file				RedBean/DomainObject.php
 * @description	This class serves as a source of inspiration and
 *						is an example how a layer super type pattern can be
 *						used with RedBean. This class has not been tested.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 *
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
	 * @param string $typeName typename
	 */
	public function __construct( $typeName = false ) {

		/**
		 * If no typeName has been specified,
		 * figure out the type of this model yourself.
		 * In this case the following rule applies:
		 * - the name of the model is the LAST part of the
		 * namespace.
		 * - Within that string, the name of the model is the LAST
		 * part of the poorman's name space.
		 *
		 * So the model name for class: /me/him/her is: her
		 * So the model name for class: /me/him/her_lover is: lover
		 */
		if (!$typeName) {
			//Fetch the bean type using the class
			$beanTypeName = get_class( $this );

			//Get last part of namespace
			$a = explode( "\\" , $beanTypeName );
			$lastInNameSpace = array_pop( $a );

			//Get last part of poorman's namespace (underscores)
			$a = explode( "_" , $lastInNameSpace );
			$lastInPoormanNameSpace = array_pop( $a );

			$beanTypeName = $lastInPoormanNameSpace;
		}
		else {
			$beanTypeName = $typeName;
		}
		/*
		 * Now do a little check to see whether this name
		 * can be used. - Just a quick check, we will re-check later on
		*/
		if ($beanTypeName && strlen($beanTypeName)>0) {

			//Fetch us a toolbox.
			$this->tools = RedBean_Setup::getToolBox();
			$this->redbean = $this->tools->getRedBean();

			//Here the bean type is checked properly.
			$this->bean = $this->redbean->dispense( strtolower( $beanTypeName ) );

			//Create some handy modules so you dont have to do the wiring yourself.
			$this->associationManager = new RedBean_AssociationManager($this->tools);
			$this->treeManager = new RedBean_TreeManager($this->tools);
		}
		else {
			throw new Exception("Invalid Domain Object TypeName");
		}
	}
	/**
	 * Associates the bean inside with another OODBBean
	 *
	 * @param RedBean_DomainObject $other other
	 */
	protected function associate(RedBean_DomainObject $other) {
		$this->associationManager->associate($this->bean, $other->bean);
	}
	/**
	 * Breaks the association between this OODBBean an the one belonging
	 * to the other model.
	 *
	 * @param RedBean_DomainObject $other other
	 */
	protected function unassociate(RedBean_DomainObject $other) {
		$this->associationManager->unassociate($this->bean, $other->bean);
	}
	/**
	 * Fetches related domain objects.
	 *
	 * @param string $className      class name
	 * @param mixed  $constructorArg constructor arguments
	 * 
	 * @return mixed $models
	 */
	protected function related( $className, $constructorArg = null ) {
		$models = array();
		$model = new $className;
		$keys = $this->associationManager->related($this->bean, $model->getBeanType());
		foreach($keys as $key) {
			$modelItem = new $className($constructorArg);
			$modelItem->find( (int) $key );
			$models[$key] = $modelItem;
		}
		return $models;
	}

	/**
	 * Returns the type of the bean.
	 *
	 * @return string $type type
	 */
	protected function getBeanType() {
		return $this->bean->getMeta("type");
	}

	/**
	 * Clears associations
	 */
	protected function clearRelations( $type ) {
		$this->associationManager->clearRelations($this->bean, $type);
	}
	/**
	 * Attach
	 * 
	 * @param RedBean_DomainObject $other other
	 */
	protected function attach(RedBean_DomainObject $other) {
		$this->treeManager->attach($this->bean, $other->bean);
	}
	/**
	 * Loads the Bean internally
	 * 
	 * @param integer $id id
	 */
	public function find( $id ) {
		$this->bean = $this->redbean->load( $this->bean->getMeta("type"), (int) $id );
	}

	/**
	 * Saves the current domain object.
	 * The function saves the inner bean to the database.
	 */
	public function save() {
		$this->redbean->store( $this->bean );
	}
	/**
	 * Deletes the inner bean from the database.
	 */
	public function delete() {
		$this->redbean->trash( $this->bean );
	}
	/**
	 * Returns the ID of the Model.
	 */
	public function getID() {
		$idField = $this->tools->getWriter()->getIDField( $this->bean->getMeta("type") );
		return $this->bean->$idField;
	}

	/**
	 * Exports bean.
	 *
	 * @return array $array array
	 */
	public function export() {
		return $this->bean;
	}

	/**
	 * Exports beans.
	 *
	 * @return array $array array
	 */

	public static function exportAll( $objects ) {
		$beans = array();
		foreach($objects as $object) {
			$beans[] = $object->export();
		}
		return $beans;
	}

	/**
	 * Loads bean.
	 * 
	 * @param RedBean_OODBBean $bean bean to load
	 */

	public function loadBean( RedBean_OODBBean $bean ) {
		$this->bean = $bean;
	}


}
