<?php
/**
 * Decorator
 * @package 		RedBean/Decorator.php
 * @description		Adds additional so-called 'porcelain' functions to the bean objects
 * @author			Gabor de Mooij
 * @license			BSD
 */
class RedBean_Decorator extends RedBean_Observable implements IteratorAggregate {

	/**
	 *
	 * @var OODBBean
	 */
	protected $data = null;

	/**
	 *
	 * @var string
	 */
	protected $type = "";

	/**
	 * @var array
	 */

	protected $problems = array();


	/**
	 * @var RedBean_OODB
	 */
	protected $provider = null;

	/**
	 * Constructor, loads directly from main table
	 * @param $type
	 * @param $id
	 * @return unknown_type
	 */
	public function __construct( RedBean_OODB $provider, $type=false, $id=0, $lock=false ) {

		$this->provider = $provider;
		$id = floatval( $id );
		if (!$type) {
			throw new Exception("Undefined bean type");
		}
		else {
			$this->type = preg_replace( "[\W_]","", strtolower($type));
			//echo $this->type;
			if ($id > 0) { //if the id is higher than 0 load data
				$this->data = $this->provider->getById( $this->type, $id, $lock );
			}
			else { //otherwise, dispense a regular empty OODBBean
				$this->data = $this->provider->dispense( $this->type );
			}
		}
	}
	
	/**
	 * This is a service for static convenience methods that
	 * have no object context but still need a provider.
	 * @return unknown_type
	 */
	private static function getStaticProvider() {
		return RedBean_OODB::getInstance();
	}

	/**
	 * Free memory of a class, drop column in db
	 * @param $property
	 * @return unknown_type
	 */
	public function free( $property ) {
		$this->signal("deco_free", $this);
		$this->provider->dropColumn( $this->type, $property );
	}

	/**

	* Quick service to copy post values to properties
	* @param $selection
	* @return unknown_type
	*/
	public function importFromPost( $selection=null ) {
		$this->signal("deco_importpost", $this);
		if (!$selection) {
			$selection = array_keys($_POST);
		}

		if (is_string($selection)) {
			$selection = explode(",",$selection);
		}

		if ($selection && is_array($selection) && count($selection) > 0) {
			foreach( $selection as $field ) {
				$setter = "set".ucfirst( $field );
				if (isset( $_POST[$field] )) {
					$resp = $this->$setter( $_POST[ $field ]  );
				}
			}
		}

		return $this;
	}

	/**
	 * Imports an array or object
	 * If this function returns boolean true, no problems
	 * have occurred during the import and all values have been copies
	 * succesfully.
	 * @param $arr or $obj
	 * @return boolean $anyproblems
	 */
	public function import( $arr ) {
		$this->signal("deco_import", $this);
		foreach( $arr as $key=>$val ) {
			$setter = "set".ucfirst( $key );
			$resp = $this->$setter( $val );
				
		}
		return $this;

	}


	/**
	 * Magic method call, this provides basic accessor functionalities
	 */
	public function __call( $method, $arguments ) {
		return $this->command( $method, $arguments );
	}

	/**
	 * Magic getter. Another way to handle accessors
	 */
	public function __get( $name ) {
		$this->signal("deco_get", $this);
		$name = strtolower( $name );
		return isset($this->data->$name) ? $this->data->$name : null;
	}

	/**
	 * Magic setter. Another way to handle accessors
	 */
	public function __set( $name, $value ) {
		$this->signal("deco_set", $this);
		$name = strtolower( $name );
		
		
		if ($name=="type") {
			throw new RedBean_Exception_Security("type is a reserved property to identify the table, pleae use another name for this property.");	
		}
		if ($name=="id") {
			throw new RedBean_Exception_Security("id is a reserved property to identify the record, pleae use another name for this property.");	
		}
	
		$this->data->$name = $value;
	}

	/**
	 * To perform a command normally handles by the magic method __call
	 * use this one. This makes it easy to overwrite a method like set
	 * and then route its result to the original method
	 * @param $method
	 * @param $arguments
	 * @return unknown_type
	 */
	public function command( $method, $arguments ) {

		if (strpos( $method,"set" ) === 0) {
			$prop = substr( $method, 3 );
			if ($prop=="type") {
				throw new RedBean_Exception_Security("type is a reserved property to identify the table, pleae use another name for this property.");	
			}
			if ($prop=="id") {
				throw new RedBean_Exception_Security("id is a reserved property to identify the record, pleae use another name for this property.");	
			}
			$this->$prop = $arguments[0];
			return $this;

		}
		elseif (strpos($method,"getRelated")===0)	{
			$this->signal("deco_get", $this);
			$prop = strtolower( substr( $method, 10 ) );
			$beans = $this->provider->getAssoc( $this->data, $prop );
			$decos = array();
			$dclass = RedBean_Setup_Namespace_PRFX.$prop.RedBean_Setup_Namespace_SFFX;

			if ($beans && is_array($beans)) {
				foreach($beans as $b) {
					$d = new $dclass();
					$d->setData( $b );
					$decos[] = $d;
				}
			}
			return $decos;
		}
		elseif (strpos( $method, "get" ) === 0) {
			$prop = substr( $method, 3 );
			return $this->$prop;
		}
		elseif (strpos( $method, "is" ) === 0) {
			$prop = strtolower( substr( $method, 2 ) );
			if (!isset($this->data->$prop)) {
				$this->signal("deco_get",$this);
				return false;
			}
			return ($this->data->$prop ? TRUE : FALSE);
		}
		else if (strpos($method,"add") === 0) { //@add
			$this->signal("deco_add",$this);
			$deco = $arguments[0];
			$bean = $deco->getData();
			$this->provider->associate($this->data, $bean);
			return $this;
		}
		else if (strpos($method,"remove")===0) {
			$this->signal("deco_remove",$this);
			$deco = $arguments[0];
			$bean = $deco->getData();
			$this->provider->unassociate($this->data, $bean);
			return $this;
		}
		else if (strpos($method,"attach")===0) {
			$this->signal("deco_attach",$this);
			$deco = $arguments[0];
			$bean = $deco->getData();
			$this->provider->addChild($this->data, $bean);
			return $this;
		}
		else if (strpos($method,"clearRelated")===0) {
			$this->signal("deco_clearrelated",$this);
			$type = strtolower( substr( $method, 12 ) );
			$this->provider->deleteAllAssocType($type, $this->data);
			return $this;
		}
		else if (strpos($method,"numof")===0) {
			$this->signal("deco_numof",$this);
			$type = strtolower( substr( $method, 5 ) );
			return $this->provider->numOfRelated($type, $this->data);
				
		}
	}

	/**
	 * Enforces an n-to-1 relationship
	 * @param $deco
	 * @return unknown_type
	 */
	public function belongsTo( $deco ) {
		$this->signal("deco_belongsto", $this);
		$this->provider->deleteAllAssocType($deco->getType(), $this->data);
		$this->provider->associate($this->data, $deco->getData());
	}

	/**
	 * Enforces an 1-to-n relationship
	 * @param $deco
	 * @return unknown_type
	 */
	public function exclusiveAdd( $deco ) {
		$this->signal("deco_exclusiveadd", $this);
		$this->provider->deleteAllAssocType($this->type,$deco->getData());
		$this->provider->associate($deco->getData(), $this->data);
	}

	/**
	 * Returns the parent object of the current object if any
	 * @return RedBean_Decorator $oBean
	 */
	public function parent() {
		$this->signal("deco_parent", $this);
		$beans = $this->provider->getParent( $this->data );
		if (count($beans) > 0 ) $bean = array_pop($beans); else return null;
		$dclass = RedBean_Setup_Namespace_PRFX.$this->type.RedBean_Setup_Namespace_SFFX;
		$deco = new $dclass();
		$deco->setData( $bean );
		return $deco;
	}

	/**
	 * Returns array of siblings (objects with the same parent except the object itself)
	 * @return array $aObjects
	 */
	public function siblings() {
		$this->signal("deco_siblings", $this);
		$beans = $this->provider->getParent( $this->data );
		if (count($beans) > 0 ) {
			$bean = array_pop($beans);
		}
		else {
			return null;
		}
		$beans = $this->provider->getChildren( $bean );
		$decos = array();
		$dclass = RedBean_Setup_Namespace_PRFX.$this->type.RedBean_Setup_Namespace_SFFX;
		if ($beans && is_array($beans)) {
			foreach($beans as $b) {
				if ($b->id != $this->data->id) {
					$d = new $dclass();
					$d->setData( $b );
					$decos[] = $d;
				}
			}
		}
		return $decos;
	}

	/**
	 * Returns array of child objects
	 * @return array $aObjects
	 */
	public function children() {
		$this->signal("deco_children", $this);
		$beans = $this->provider->getChildren( $this->data );
		$decos = array();
		$dclass = RedBean_Setup_Namespace_PRFX.$this->type.RedBean_Setup_Namespace_SFFX;
		if ($beans && is_array($beans)) {
			foreach($beans as $b) {
				$d = new $dclass();
				$d->setData( $b );
				$decos[] = $d;
			}
		}
		return $decos;
	}

	/**
	 * Returns whether a node has a certain parent in its ancestry
	 * @param $deco
	 * @return boolean $found
	 */
	public function hasParent( $deco ) {
		$me = $this;
		while( $parent = $me->parent() ) {
			if ($deco->getID() == $parent->getID()) {
				return true;
			}
			else {
				$me = $parent;
			}
		}
		return false;
	}

	/**
	 * Searches children of a specific tree node for the target child
	 * @param $deco
	 * @return boolean $found
	 */
	public function hasChild( $deco ) {

		$nodes = array($this);
		while($node = array_shift($nodes)) {
			if ($node->getID() == $deco->getID() &&
			($node->getID() != $this->getID())) {
				return true;
			}
			if ($children = $node->children()) {
				$nodes = array_merge($nodes, $children);
			}
		}
		return false;

	}

	/**
	 * Searches if a node has the specified sibling
	 * @param $deco
	 * @return boolean $found
	 */
	public function hasSibling( $deco ) {
		$siblings = $this->siblings();
		foreach( $siblings as $sibling ) {
			if ($sibling->getID() == $deco->getID()) {
				return true;
			}
		}
		return false;
	}

	/**
	 * This function simply copies the model and returns it
	 * @return RedBean_Decorator $oRD
	 */
	public function copy() {
		$this->signal("deco_copy", $this);
		$clone = new self( $this->provider, $this->type, 0 );
		$clone->setData( $this->getData() );
		return $clone;
	}

	/**
	 * Clears all associations
	 * @return unknown_type
	 */
	public function clearAllRelations() {
		$this->signal("deco_clearrelations", $this);
		$this->provider->deleteAllAssoc( $this->getData() );
	}

	/**
	 * Gets data directly
	 * @return OODBBean
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * Sets data directly
	 * @param $data
	 * @return void
	 */
	public function setData( $data ) {
		$this->data = $data;
	}

	/**
	 * Inserts or updates the bean
	 * Returns the ID
	 * @return unknown_type
	 */
	public function save() {
		$this->signal("deco_save", $this);
		return $this->provider->set( $this->data );
	}

	/**
	 * Deletes the bean
	 * @param $deco
	 * @return unknown_type
	 */
	public static function delete( $deco ) {
		self::getStaticProvider()->trash( $deco->getData() );
	}


	/**
	 * Explicitly forward-locks a decorated bean
	 * @return unknown_type
	 */
	public function lock() {
		$this->provider->openBean($this->getData());
	}

	/**
	 * Explicitly unlocks a decorated bean
	 * @return unknown_type
	 */
	public function unlock() {
		$this->provider->closeBean( $this->getData());
	}


	/**
	 * Closes and unlocks the bean
	 * @param $deco
	 * @return unknown_type
	 */
	public static function close( $deco ) {
		self::getStaticProvider()->closeBean( $deco->getData() );
	}

	/**
	 * Creates a redbean decorator for a specified type
	 * @param $type
	 * @param $id
	 * @return unknown_type
	 */
	public static function make( $type="", $id ){
		return new RedBean_Decorator( self::getStaticProvider(), $type, $id );
	}


	/**
	 * Exports a bean to a view
	 * @param $bean
	 * @return unknown_type
	 */
	public function exportTo( &$bean, $overridebean=false ) {


		foreach($this->data as $prop=>$value) {

			//what value should we use?
			if (is_object($overridebean) && isset($overridebean->$prop)) {
				$value = $overridebean->$prop;
			}
			elseif (is_array($overridebean) && isset($overridebean[$prop])) {
				$value = $overridebean[$prop];
			}

			if (is_object($value)){
				$value = $value->getID();
			}

			if (is_object($bean)) {
				$bean->$prop = $value;
			}
			elseif (is_array($bean)) {
				$bean[$prop] = $value;
			}
		}

		return $bean;
	}


	/**
	 * Exports a bean as an array
	 * @param $bean
	 * @return array $arr
	 */
	public function exportAsArr() {
		$arr = array();
		foreach($this->data as $prop=>$value) {
			if ($value instanceof RedBean_Decorator){
				$value = $value->getID();
			}
			$arr[ $prop ] = $value;

		}
		return  $arr;
	}

	/**
	 * Finds another decorator
	 * @param $deco
	 * @param $filter
	 * @return array $decorators
	 */
	public static function find( $deco, $filter, $start=0, $end=100, $orderby=" id ASC ", $extraSQL=false ) {

		if (!is_array($filter)) {
			return array();
		}

		if (count($filter)<1) {
			return array();
		}

		//make all keys of the filter lowercase
		$filters = array();
		foreach($filter as $key=>$f) {
			$filters[strtolower($key)] =$f;

			if (!in_array($f,array("=","!=","<",">","<=",">=","like","LIKE"))) {
				throw new ExceptionInvalidFindOperator();
			}

		}

		$beans = self::getStaticProvider()->find( $deco->getData(), $filters, $start, $end, $orderby, $extraSQL );

		$decos = array();
		$dclass = RedBean_Setup_Namespace_PRFX.$deco->type.RedBean_Setup_Namespace_SFFX;
		foreach( $beans as $bean ) {
			$decos[ $bean->id ] = new $dclass( floatval( $bean->id ) );
			$decos[ $bean->id ]->setData( $bean );
		}
		return $decos;
	}

	/**
	 * Returns an iterator
	 * @return Iterator $i
	 */
	public function getIterator() {
		$o = new ArrayObject($this->data);
		return $o->getIterator();
	}
	
	/**
	 * Whether you can write to this bean or not
	 * @return boolean $locked
	 */
	public function isReadOnly() {
		try{
			$this->provider->openBean($this->data, true);
		}
		catch(RedBean_Exception_FailedAccessBean $e){
			return false;	
		}
		return true;
	}

}

