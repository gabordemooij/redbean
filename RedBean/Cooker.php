<?php
/**
 * RedBean Cooker
 * @file			RedBean/Cooker.php
 * @description		Turns arrays into bean collections for easy persistence.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 * The Cooker is a little candy to make it easier to read-in an HTML form.
 * This class turns a form into a collection of beans plus an array
 * describing the desired associations.
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_Cooker {

	/**
	 * Reference to RedBean OODB instance from toolbox.
	 * 
	 * @var RedBean_OODB 
	 */
	private $redbean;
	
	/**
	 * Flag indicating mode of operation for Cooker. If this flag is set
	 * to TRUE, the Cooker will not enforce policies.
	 * 
	 * @var boolean
	 */
	private $flagUnsafe = false;

	/**
	 * An array containing all valid policy codes.
	 * 
	 * @var array
	 */
	private $policyCodes;
	
	/**
	 * Contains the schema of the database, this can be cached. 
	 * 
	 * @var array 
	 */
	private $schema;
	
	/**
	 * Constants defining the valid policy codes
	 */
	
	/**
	 * Constant Policy Read.
	 * Represents the READ policy. Adding a bean with READ policy allows
	 * the Cooker to load this bean but it cannot be modified in any way.
	 * The primary key ID of the bean may be used to reference the bean in
	 * other beans though.
	 */
	const C_POLICY_READ = 'r';
	
	/**
	 * Constant Policy Write.
	 * Represents the WRITE Policy. Adding a bean with WRITE policy allows
	 * the Cooker to modify this bean and add other beans to this bean.
	 */
	const C_POLICY_WRITE = 'w';
	
	/**
	 * Constant Policy New.
	 * Represents the NEW Policy. Adding a type string with a NEW Policy
	 * allows the Cooker to dispense beans of this type.
	 */
	const C_POLICY_NEW = 'n';
	
	/**
	 * Constructor.
	 * The task of the constructor is to prepare an array of valid codes
	 * for the policy methods.
	 * 
	 * @param $schema schema of database (optional for caching).
	 * 
	 */
	public function __construct($schema = null) {
		
		$this->policyCodes = array(
			self::C_POLICY_READ => true,
			self::C_POLICY_WRITE => true,
			self::C_POLICY_NEW => true
		);
		
		if ($schema) {
			$this->schema = $schema;
		}
	}
	
	/**
	 * Sets the toolbox to be used by graph(). The Cooker requires
	 * a toolbox for service location.
	 *
	 * @param RedBean_Toolbox $toolbox toolbox to be used to perform operations
	 * 
	 * @return void
	 */
	public function setToolbox(RedBean_Toolbox $toolbox) {
		$this->toolbox = $toolbox;
		$this->redbean = $this->toolbox->getRedbean();
	}

	/**
	 * Turns an array (post/request array) into a collection of beans.
	 * Handy for turning forms into bean structures that can be stored with a
	 * single call.
	 * 
	 * Typical usage:
	 * 
	 * $struct = R::graph($_POST);
	 * R::store($struct);
	 * 
	 * Example of a valid array:
	 * 
	 *	$form = array(
	 *		'type'=>'order',
	 *		'ownProduct'=>array(
	 *			array('id'=>171,'type'=>'product'),
	 *		),
	 *		'ownCustomer'=>array(
	 *			array('type'=>'customer','name'=>'Bill')
	 *		),
	 * 		'sharedCoupon'=>array(
	 *			array('type'=>'coupon','name'=>'123'),
	 *			array('type'=>'coupon','id'=>3)
	 *		)
	 *	);
	 * 
	 * Each entry in the array will become a property of the bean.
	 * The array needs to have a type-field indicating the type of bean it is
	 * going to be. The array can have nested arrays. A nested array has to be
	 * named conform the bean-relation conventions, i.e. ownPage/sharedPage
	 * each entry in the nested array represents another bean.
	 *  
	 * @param	array   $array       array to be turned into a bean collection
	 * @param   boolean $filterEmpty whether you want to exclude empty beans
	 *
	 * @return	array $beans beans
	 */
	public function graph( $array, $filterEmpty = false ) {
       $beans = array();
		if (is_array($array) && isset($array['type'])) {
			$type = $array['type'];
			unset($array['type']);
			//Do we need to load the bean?
			if (isset($array['id'])) {
				$id = (int) $array['id'];
				unset($array['id']);
				if (count($array)>0) {
					//the array contains more properties, we are going to change the bean,
					//ask for a bean with write policy.
					$bean = $this->loadFromPool($type,$id,'w');
				}
				else {
					//no more properties besides type and id, read is enough.
					//ask for a bean to read.
					return $this->loadFromPool($type,$id,'r');
				}
			}
			else {
				//ask for bean dispense
				$bean = $this->loadFromPool($type,0,'n');
			}
			
			foreach($array as $property=>$value) {
				if (is_array($value)) {
					$bean->$property = $this->graph($value,$filterEmpty);
				}
				else {
					if (strpos($property,'_id')!==false) {
						//property contains a reference -- must be checked
						$this->loadFromPool(substr($property,0,-3), $value,'r');
					}
					$bean->$property = $value;
				}
			}
			$this->purify($bean);
			return $bean;
		}
		elseif (is_array($array)) {
			foreach($array as $key=>$value) {
				$listBean = $this->graph($value,$filterEmpty);
				if (!($listBean instanceof RedBean_OODBBean)) {
					throw new RedBean_Exception_Security('Expected bean but got :'.gettype($listBean)); 
				}
				if ($listBean->isEmpty()) {  
					if (!$filterEmpty) { 
						$beans[$key] = $listBean;
					}
				}
				else { 
					$beans[$key] = $listBean;
				}
			}
			return $beans;
		}
		else {
			throw new RedBean_Exception_Security('Expected array but got :'.gettype($array)); 
		}
	}
	
	/**
	 * Tries to load a bean from the pool. If the bean has been added to the pool of
	 * accesible beans. If the bean can be found in the pool and the policy code
	 * matches the required policy the bean will be returned. If the bean cannot be
	 * found in the pool or the policy does not match this method will throw a
	 * security exception notifying the developer about the possible security issue.
	 * 
	 * @param string  $type   the name of the type of bean you want to load or dispense
	 * @param integer $id     either 0 or a valid primary key ID to load the desired bean
	 * @param string  $policy a valid policy code (i.e. 'r','w' or 'n')
	 * 
	 * @return RedBean_OODBBean $bean the desired RedBean_OODBBean instance 
	 */
	public function loadFromPool($type, $id, $policy) {
		$id = (int) $id;
		if ($this->flagUnsafe) return R::load($type,$id);
		$this->checkPolicyCode($policy);
		if (!isset($this->pool[$policy][$type][$id])) {
			throw new RedBean_Exception_Security('Denied, no '.$policy.'-access to:'.$type.'-'.$id);
		}
		else {
			if ($policy == self::C_POLICY_NEW) {
				$bean = $this->redbean->dispense($type);
			}
			else {
				$bean = $this->pool[$policy][$type][$id];
			}
			return $bean;
		}
	}
	
	/**
	 * Checks the existance of a security policy. If the code does not represent
	 * a security policy this method will throw a security exception notifying the
	 * developer about a malformed security policy code string.
	 * 
	 * @param string $code the security code string to be verified
	 */
	private function checkPolicyCode($code) {
		if (!isset($this->policyCodes[$code])) {
			throw new RedBean_Exception_Security('Invalid security policy.');
		}
	}
	

	/**
	 * Adds one or more beans to the bean pool of the Cooker with a certain
	 * policy code attached to it. By sending a bean with a policy to the
	 * pool you make this bean availabe for reading or writing by the Cooker.
	 * 
	 * Example: $cooker->addPolicy($bean,'r');
	 * Adding a bean with READ policy allows
	 * the Cooker to load this bean but it cannot be modified in any way.
	 * The primary key ID of the bean may be used to reference the bean in
	 * other beans though.
	 * 
	 * Example: $cooker->addPolicy($bean,'w');
	 * Adding a bean with WRITE policy allows
	 * the Cooker to modify this bean and add other beans to this bean.
	 * 
	 * @param RedBean_OODBBean|array $beans  one or more beans to be added to the pool
	 * @param string                 $policy send the beans with this policy
	 */
	public function addPolicy($beans, $policy='r') {
		$this->checkPolicyCode($policy);
		if ($policy == self::C_POLICY_NEW) throw new RedBean_Exception_Security('Method cannot handle new-policy.');
		if (is_array($beans)) {
			foreach($beans as $bean) $this->addPolicy($bean,$policy);
		}
		else {
			$bean = $beans;
			$this->pool[$policy][$bean->getMeta('type')][(int)$bean->id] = $bean;
		}
	}
	
	/**
	 * Sets the policy to allow the Cooker to dispense beans of the indicated types.
	 * 
	 * @param string|array $types one or more types.
	 */
	public function allowCreationOfTypes($types) {
		if (is_array($types)) {
			foreach($types as $type) $this->allowCreationOfTypes($type);
		}
		else {
			$type = $types;
			$this->pool[self::C_POLICY_NEW][$type][0] = true;
		}
	}
	
	/**
	 * This method resets all policies. After invocation there will be no more
	 * active policies for the Cooker. Cleaning the policies will render the
	 * Cooker powerless to handle any form. Use this method before you start
	 * adding new policies for a new array processing task. Also make sure
	 * you use this method before calling setUnsafe(TRUE). You cannot turn the
	 * Cooker in unsafe mode if there are still any policies active.
	 */
	public function cleanPolicies() {
		$this->pool = array();
	}
	
	/**
	 * Toggles unsafe mode. Be very careful with this method. Setting the unsafe mode
	 * in the Cooker will no longer enforce any policies on bean processing.
	 * 
	 * @param boolean $tf TRUE means unsafe, FALSE means safe. 
	 */
	public function setUnsafe($tf) {
		if (!empty($this->pool)) {
			throw new RedBean_Exception_Security('Policies active.');
		}
		$this->flagUnsafe = (boolean) $tf;
	}
	
	
	/**
	 * Returns the entire schema of the database.
	 * Format:
	 * 
	 * array(
	 *	'table' => array(
	 *			'column1'=>'fieldtype',
	 *			'columns2'=>'fieldtype' ... )
	 * )
	 * 
	 * @return array $schema the schema of the database
	 */
	public function getSchema() {
		$tables = array_flip($this->toolbox->getWriter()->getTables());
		foreach($tables as $table=>$columns) {
			try{
				$tables[$table] = $this->toolbox->getWriter()->getColumns($table);
			}
			catch(RedBean_Exception_SQL $e) {
				$tables[$table] = array();
			}
		}
		return $tables;
	}
	
	
	/**
	 * Validates bean against schema (in frozen mode only).
	 * Purify will check whether:
	 * 1. The type of bean exists in the current schema
	 * 2. The fields are correct
	 * 
	 * @param RedBean_OODBBean $bean bean to be validated.
	 */
	public function purify(RedBean_OODBBean $bean) {
		if (!$this->redbean->isFrozen()) return;
		if (!$this->schema) {
			$this->schema = $this->getSchema();
		}
		if (!isset($this->schema[$bean->getMeta('type')])) throw new RedBean_Exception_Security('No table for type: '.$bean->getMeta('type'));
		foreach($bean as $key=>$value) { 
			if (!isset($this->schema[$bean->getMeta('type')][$key]) && !is_object($value) && !is_array($value)) throw new RedBean_Exception_Security('Invalid field: '.preg_replace('/\W/','',$key));
		}
	}
	
}
