<?php
/**
 * RedBean_OODBBean (Object Oriented DataBase Bean)
 * 
 * @file    RedBean/RedBean_OODBBean.php
 * @desc    The Bean class used for passing information
 * @author  Gabor de Mooij and the RedBeanPHP community
 * @license BSD/GPLv2
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_OODBBean implements IteratorAggregate, ArrayAccess, Countable {
	
	/**
	 * Setting: use beautiful columns, i.e. turn camelcase column names into snake case column names
	 * for database.
	 * 
	 * @var boolean
	 */
	private static $flagUseBeautyCols = true;
	
	/**
	 * Setting: use IDs as keys when exporting. By default this has been turned off because exports
	 * to Javascript may cause problems due to Javascript Sparse Array implementation (i.e. causing large arrays
	 * with lots of 'gaps').
	 * 
	 * @var boolean  
	 */
	private static $flagKeyedExport = false;
	
	/**
	* @var boolean
	*/
	private $flagSkipBeau = false;
	
	/**
	 * This is where the real properties of the bean live. They are stored and retrieved
	 * by the magic getter and setter (__get and __set).
	 * 
	 * @var array $properties
	 */
	private $properties = array();
	
	/**
	 * Here we keep the meta data of a bean.
	 * 
	 * @var array
	 */
	private $__info = array();
	
	/**
	 * The BeanHelper allows the bean to access the toolbox objects to implement
	 * rich functionality, otherwise you would have to do everything with R or
	 * external objects.
	 * 
	 * @var RedBean_BeanHelper
	 */
	private $beanHelper = NULL;
	
	/**
	 * @var null
	 */
	private $fetchType = NULL;
	
	/**
	 * @var string 
	 */
	private $withSql = '';
	
	/**
	 * @var array 
	 */
	private $withParams = array();
	
	/**
	 * @var string 
	 */
	private $aliasName = NULL;
	
	/**
	 * @var string
	 */
	private $via = NULL;
	
	/** Returns the alias for a type
	 *
	 * @param  $type aliased type
	 *
	 * @return string $type type
	 */
	private function getAlias($type) {
		if ($this->fetchType) {
			$type = $this->fetchType;
			$this->fetchType = null;
		}
		return $type;
	}
	
	/**
	* Internal method.
	* Obtains a shared list for a certain type.
	*
	* @param string $type the name of the list you want to retrieve.
	*
	* @return array
	*/
	private function getSharedList($type) {
		$toolbox = $this->beanHelper->getToolbox();
		$redbean = $toolbox->getRedBean();
		$writer = $toolbox->getWriter();
		if ($this->via) {
			$oldName = $writer->getAssocTable(array($this->__info['type'],$type));
			if ($oldName !== $this->via) {
				//set the new renaming rule
				$writer->renameAssocTable($oldName, $this->via);
				$this->via = null;
			} 
		}
		$type = $this->beau($type);
		$types = array($this->__info['type'], $type);
		$linkID = $this->properties['id'];
		$assocManager = $redbean->getAssociationManager();
		$beans = $assocManager->relatedSimple($this, $type, $this->withSql, $this->withParams);
		$this->withSql = '';
		$this->withParams = array();
		return $beans;
	}
	
	/**
	* Internal method.
	* Obtains the own list of a certain type.
	*
	* @param string $type name of the list you want to retrieve
	*
	* @return array
	*/
	private function getOwnList($type) {
		$type = $this->beau($type);
		if ($this->aliasName) {
			$parentField = $this->aliasName;
			$myFieldLink = $this->aliasName.'_id';
			$this->__info['sys.alias.'.$type] = $this->aliasName;
			$this->aliasName = null;
		} else {
			$myFieldLink = $this->__info['type'].'_id';
			$parentField = $this->__info['type'];
		}
		$beans = array();
		if ($this->getID()>0) {
			$bindings = array_merge(array($this->getID()), $this->withParams);
			$beans = $this->beanHelper->getToolbox()->getRedBean()->find($type, array(), " $myFieldLink = ? ".$this->withSql, $bindings);
		}
		$this->withSql = '';
		$this->withParams = array();
		foreach($beans as $beanFromList) {
			$beanFromList->__info['sys.parentcache.'.$parentField] = $this;
		}
		return $beans;
	}
	
	/**
	 * By default own-lists and shared-lists no longer have IDs as keys (3.3+),
	 * this is because exportAll also does not offer this feature and we want the
	 * ORM to be more consistent. Also, exporting without keys makes it easier to
	 * export lists to Javascript because unlike in PHP in JS arrays will fill up gaps.
	 * 
	 * @var boolean $yesNo 
	 */
	public static function setFlagKeyedExport($flag) {
		self::$flagKeyedExport = (boolean) $flag;
	}
	
	/**
	 * Flag indicates whether column names with CamelCase are supported and automatically
	 * converted; example: isForSale -> is_for_sale
	 * 
	 * @param boolean
	 */
	public static function setFlagBeautifulColumnNames($flag) {
		self::$flagUseBeautyCols = (boolean) $flag;
	}
	
	/**
	 * Sets the Bean Helper. Normally the Bean Helper is set by OODB.
	 * Here you can change the Bean Helper. The Bean Helper is an object
	 * providing access to a toolbox for the bean necessary to retrieve
	 * nested beans (bean lists: ownBean, sharedBean) without the need to
	 * rely on static calls to the facade (or make this class dep. on OODB).
	 *
	 * @param RedBean_IBeanHelper $helper
	 * 
	 * @return void
	 */
	public function setBeanHelper(RedBean_BeanHelper $helper) {
		$this->beanHelper = $helper;
	}
	
	/**
	 * Returns an ArrayIterator so you can treat the bean like
	 * an array with the properties container as its contents.
	 *
	 * @return ArrayIterator $arrayIt an array iterator instance with $properties
	 */
	public function getIterator() {
		return new ArrayIterator($this->properties);
	}
	
	/**
	 * Imports all values from an associative array $array. Chainable.
	 *
	 * @param array        $array     what you want to import
	 * @param string|array $selection selection of values
	 * @param boolean      $notrim    if TRUE values will not be trimmed
	 *
	 * @return RedBean_OODBBean $this
	 */
	public function import($arr, $selection = false, $notrim = false) {
		if (is_string($selection)) {
			$selection = explode(',', $selection);
		}
		if (!$notrim && is_array($selection)) {
			foreach($selection as $key => $selected){ 
				$selection[$key] = trim($selected); 
			}
		}
		foreach($arr as $key => $value) {
			if ($key != '__info') {
				if (!$selection || ($selection && in_array($key, $selection))) {
					$this->$key = $value;
				}
			}
		}
		return $this;
	}
	
	/**
	* Imports data from another bean. Chainable.
	* 
	* @param RedBean_OODBBean $sourceBean the source bean to take properties from
	*
	* @return RedBean_OODBBean $self
	*/
	public function importFrom(RedBean_OODBBean $sourceBean) {
		$this->__info['tainted'] = true;
		$array = $sourceBean->properties;
		$this->properties = $array;
		return $this;
	}
	
	/**
	 * Injects the properties of another bean but keeps the original ID.
	 * Just like import() but keeps the original ID.
	 * Chainable.
	 * 
	 * @param RedBean_OODBBean $otherBean the bean whose properties you would like to copy
	 * 
	 * @return RedBean_OODBBean $self
	 */
	public function inject(RedBean_OODBBean $otherBean) {
		$myID = $this->id;
		$array = $otherBean->export();
		$this->import($array);
		$this->id = $myID;
		return $this;
	}
	
	/**
	 * Exports the bean as an array.
	 * This function exports the contents of a bean to an array and returns
	 * the resulting array. 
	 * 
	 * @param boolean $meta    set to TRUE if you want to export meta data as well 
	 * @param boolean $parents set to TRUE if you want to export parents as well
	 * @param boolean $onlyMe  set to TRUE if you want to export only this bean
	 * @param array   $filters optional whitelist for export
	 * 
	 * @return array $arr
	 */
	public function export($meta = false, $parents = false, $onlyMe = false, $filters = array()) {
		$arr = array();
		if ($parents) {
			foreach($this as $key => $value) {
				if (substr($key, -3) == '_id') {
					$prop = substr($key, 0, strlen($key)-3);
					$this->$prop;
				}
			}
		}
		foreach($this as $key => $value) {
			if (!$onlyMe && is_array($value)) {
				$vn = array();
				foreach($value as $i => $b) {
					if (is_numeric($i) && !self::$flagKeyedExport) {
						$vn[] = $b->export($meta, false, false, $filters);
					} else {
						$vn[$i] = $b->export($meta, false, false, $filters);
					}
					$value = $vn;
				}
			} elseif ($value instanceof RedBean_OODBBean) {
				if (is_array($filters) && count($filters) && !in_array(strtolower($value->getMeta('type')), $filters)) {
					continue;
				}
				$value = $value->export($meta, $parents, false, $filters);
			}
			$arr[$key] = $value;
		}
		if ($meta) {
			$arr['__info'] = $this->__info;
		}
		return $arr;
	}
	
	/**
	 * Exports the bean to an object.
	 * 
	 * @param object $obj target object
	 * 
	 * @return array $arr
	 */
	public function exportToObj($object) {
		foreach($this->properties as $key => $value) {
			if (!is_array($value) && !is_object($value)) {
				$object->$key = $value;
			}
		}
	}
	
	/**
	 * Implements isset() function for use as an array.
	 * 
	 * @param string $property name of the property you want to check
	 * 
	 * @return boolean
	 */
	public function __isset($property) {
		return (isset($this->properties[$property]));
	}
	
	/**
	 * Returns the ID of the bean no matter what the ID field is.
	 *
	 * @return string $id record Identifier for bean
	 */
	public function getID() {
		return (string) $this->id;
	}
	
	/**
	 * Unsets a property. This method will load the property first using
	 * __get.
	 *
	 * @param  string $property property
	 *
	 * @return void
	 */
	public function __unset($property) {
		$this->__get($property);
		$fieldLink = $property.'_id';
		if (isset($this->$fieldLink)) {
			//wanna unset a bean reference?
			$this->$fieldLink = null;
		}
		if ((isset($this->properties[$property]))) {
			unset($this->properties[$property]);
		}
	}
	
	/**
	 * Removes a property from the properties list without invoking
	 * an __unset on the bean.
	 *
	 * @param  string $property property that needs to be unset
	 *
	 * @return void
	 */
	public function removeProperty($property) {
		unset($this->properties[$property]);
	}
	
	/**
	 * Adds WHERE clause conditions to ownList retrieval.
	 * For instance to get the pages that belong to a book you would
	 * issue the following command: $book->ownPage
	 * However, to order these pages by number use:
	 * 
	 * $book->with(' ORDER BY `number` ASC ')->ownPage
	 * 
	 * the additional SQL snippet will be merged into the final
	 * query.
	 * 
	 * @param string|RedBean_SQLHelper $sql      SQL to be added to retrieval query.
	 * @param array                    $bindings array with parameters to bind to SQL snippet
	 * 
	 * @return RedBean_OODBBean $self
	 */
	public function with($sql, $bindings = array()) {
		if ($sql instanceof RedBean_SQLHelper) {
			list($this->withSql, $this->withParams) = $sql->getQuery();
		} else {
			$this->withSql = $sql;
			$this->withParams = $bindings;
		}
		return $this;
	}
	
	/**
	 * Just like with(). Except that this method prepends the SQL query snippet 
	 * with AND which makes it slightly more comfortable to use a conditional
	 * SQL snippet. For instance to filter an own-list with pages (belonging to
	 * a book) on specific chapters you can use:
	 * 
	 * $book->withCondition(' chapter = 3 ')->ownPage
	 * 
	 * This will return in the own list only the pages having 'chapter == 3'. 
	 * 
	 * @param string|RedBean_SQLHelper $sql      SQL to be added to retrieval query (prefixed by AND)
	 * @param array                    $bindings array with parameters to bind to SQL snippet
	 * 
	 * @return RedBean_OODBBean $self
	 */
	public function withCondition($sql, $bindings = array()) {
		if ($sql instanceof RedBean_SQLHelper) {
			list($sql, $bindings) = $sql->getQuery();
		} 
		$this->withSql = ' AND '.$sql;
		$this->withParams = $bindings;
		return $this;
	}
	
	/**
	 * Prepares an own-list to use an alias. This is best explained using
	 * an example. Imagine a project and a person. The project always involves
	 * two persons: a teacher and a student. The person beans have been aliased in this
	 * case, so to the project has a teacher_id pointing to a person, and a student_id
	 * also pointing to a person. Given a project, we obtain the teacher like this:
	 * 
	 * $project->fetchAs('person')->teacher;
	 * 
	 * Now, if we want all projects of a teacher we cant say:
	 * 
	 * $teacher->ownProject
	 * 
	 * because the $teacher is a bean of type 'person' and no project has been
	 * assigned to a person. Instead we use the alias() method like this:
	 * 
	 * $teacher->alias('teacher')->ownProject
	 * 
	 * now we get the projects associated with the person bean aliased as
	 * a teacher.
	 * 
	 * @param string $aliasName the alias name to use
	 * 
	 * @return RedBean_OODBBean 
	 */
	public function alias($aliasName) {
		$aliasName = $this->beau($aliasName);
		$this->aliasName = $aliasName;
		return $this;
	}
	
	/**
	* Returns properties of bean as an array.
	*
	* @return array
	*/
	public function getProperties() { 
		return $this->properties; 
	}
	
	/**
	* Turns a camelcase property name into an underscored property name.
	* Examples:
	*	oneACLRoute -> one_acl_route
	*	camelCase -> camel_case
	*
	* Also caches the result to improve performance.
	*
	* @param string $property
	*
	* @return string	
	*/
	public function beau($property) {
		static $beautifulColumns = array();
		if (!self::$flagUseBeautyCols) {
			return $property;
		}
		if (strpos($property, 'own') !== 0 && strpos($property, 'shared') !== 0) {
			if (isset($beautifulColumns[$property])) {
				$propertyBeau = $beautifulColumns[$property];
			} else {
				$propertyBeau = strtolower(preg_replace('/(?<=[a-z])([A-Z])|([A-Z])(?=[a-z])/', '_$1$2', $property));
				$beautifulColumns[$property] = $propertyBeau;
			}
			return $propertyBeau;
		} else {
			return $property;
		}
	}
	
	/**
	 * Magic Getter. Gets the value for a specific property in the bean.
	 * If the property does not exist this getter will make sure no error
	 * occurs. This is because RedBean allows you to query (probe) for
	 * properties. If the property can not be found this method will
	 * return NULL instead.
	 * 
	 * @param string $property name of the property you wish to obtain the value of
	 * 
	 * @return mixed
	 */
	public function &__get($property) {
		if (!$this->flagSkipBeau) {
			$property = $this->beau($property);	
		}
		if ($this->beanHelper) {
			$toolbox = $this->beanHelper->getToolbox();
			$redbean = $toolbox->getRedBean();
		}
		if (!isset($this->properties[$property]) || ($this->withSql !== '' && ((strpos($property, 'own') === 0) || (strpos($property, 'shared') === 0)))) { 
			$fieldLink = $property.'_id'; 
			if (isset($this->$fieldLink) && $fieldLink !== $this->getMeta('sys.idfield')) {
				$this->__info['tainted'] = true; 
				$bean = $this->getMeta('sys.parentcache.'.$property);
				if (!$bean) { 
					$type = $this->getAlias($property);
					$bean = $redbean->load($type, $this->properties[$fieldLink]);
				}
				$this->properties[$property] = $bean;
				return $this->properties[$property];
			} elseif (strpos($property, 'own') === 0 && ctype_upper(substr($property, 3, 1))) {
				$type = lcfirst(substr($property, 3));
				$beans = $this->getOwnList($type);
				$this->properties[$property] = $beans;
				$this->__info['sys.shadow.'.$property] = $beans;
				$this->__info['tainted'] = true;
				return $this->properties[$property];
			} elseif (strpos($property, 'shared') === 0 && ctype_upper(substr($property, 6, 1))) {
				$type = lcfirst(substr($property, 6));
				$beans = $this->getSharedList($type);
				$this->properties[$property] = $beans;
				$this->__info['sys.shadow.'.$property] = $beans;
				$this->__info['tainted'] = true;
				return $this->properties[$property];
			} else {
				$null = null;
				return $null;
			}
		} else {
			return $this->properties[$property];
		}
	}
	
	/**
	 * Magic Setter. Sets the value for a specific property.
	 * This setter acts as a hook for OODB to mark beans as tainted.
	 * The tainted meta property can be retrieved using getMeta("tainted").
	 * The tainted meta property indicates whether a bean has been modified and
	 * can be used in various caching mechanisms.
	 * 
	 * @param string $property name of the propery you wish to assign a value to
	 * @param  mixed $value    the value you want to assign
	 * 
	 * @throws RedBean_Exception_Security
	 */
	public function __set($property, $value) {
		$property = $this->beau($property);
		$this->flagSkipBeau = true;
		$this->__get($property);
		$this->flagSkipBeau = false;
		$this->setMeta('tainted', true);
		$linkField = $property.'_id';
		if (isset($this->properties[$linkField]) && !($value instanceof RedBean_OODBBean)) {
			if (is_null($value) || $value === false) {
				return $this->__unset($property);
			} else {
				throw new RedBean_Exception_Security('Cannot cast to bean.');
			}
		}
		if ($value === false) {
			$value = '0';
		} elseif ($value === true) {
			$value = '1';
		} elseif ($value instanceof DateTime) {
			$value = $value->format('Y-m-d H:i:s');
		}
		$this->properties[$property] = $value;
	}
	
	/**
	 * Sets a property directly, for internal use only.
	 * 
	 * @param string  $property     property
	 * @param mixed   $value        value
	 * @param boolean $updateShadow whether you want to update the shadow
	 * @param boolean $taint        whether you want to mark the bean as tainted
	 */
	public function setProperty($property, $value, $updateShadow = false, $taint = false) {
		$this->properties[$property] = $value;
		if ($updateShadow) {
			$this->__info['sys.shadow.'.$property] = $value;
		}
		if ($taint) {
			$this->__info['tainted'] = true;
		}
	}
	
	/**
	 * Returns the value of a meta property. A meta property
	 * contains extra information about the bean object that will not
	 * get stored in the database. Meta information is used to instruct
	 * RedBean as well as other systems how to deal with the bean.
	 * For instance: $bean->setMeta("buildcommand.unique", array(
	 * array("column1", "column2", "column3") ) );
	 * Will add a UNIQUE constaint for the bean on columns: column1, column2 and
	 * column 3.
	 * To access a Meta property we use a dot separated notation.
	 * If the property cannot be found this getter will return NULL instead.
	 * 
	 * @param string $path    path
	 * @param mixed  $default default value
	 * 
	 * @return mixed $value
	 */
	public function getMeta($path, $default = NULL) {
		return (isset($this->__info[$path])) ? $this->__info[$path] : $default;
	}
	
	/**
	 * Stores a value in the specified Meta information property. $value contains
	 * the value you want to store in the Meta section of the bean and $path
	 * specifies the dot separated path to the property. For instance "my.meta.property".
	 * If "my" and "meta" do not exist they will be created automatically.
	 * 
	 * @param string $path  path
	 * @param mixed  $value value
	 */
	public function setMeta($path, $value) {
		$this->__info[$path] = $value;
	}
	
	/**
	 * Copies the meta information of the specified bean
	 * This is a convenience method to enable you to
	 * exchange meta information easily.
	 * 
	 * @param RedBean_OODBBean $bean
	 * 
	 * @return RedBean_OODBBean
	 */
	public function copyMetaFrom(RedBean_OODBBean $bean) {
		$this->__info = $bean->__info;
		return $this;
	}
	
	/**
	 * Sends the call to the registered model.
	 * 
	 * @param string $method name of the method
	 * @param array  $args   argument list
	 * 
	 * @return mixed $mixed
	 */
	public function __call($method, $args) {
		if (!isset($this->__info['model'])) {
			$model = $this->beanHelper->getModelForBean($this);
			if (!$model) {
				return;
			}
			$this->__info['model'] = $model;
		}
		if (!method_exists($this->__info['model'], $method)) {
			return null;
		}
		return call_user_func_array(array($this->__info['model'], $method), $args);
	}
	
	/**
	 * Implementation of __toString Method
	 * Routes call to Model.
	 * 
	 * @return string
	 */
	public function __toString() {
		$string = $this->__call('__toString', array());
		if ($string === null) {
			return json_encode($this->properties);
		} else {
			return $string;
		}
	}
	
	/**
	 * Implementation of Array Access Interface, you can access bean objects
	 * like an array.
	 * Call gets routed to __set.
	 *
	 * @param  mixed $offset offset string
	 * @param  mixed $value  value
	 */
	public function offsetSet($offset, $value) {
		$this->__set($offset, $value);
	}
	
	/**
	 * Implementation of Array Access Interface, you can access bean objects
	 * like an array.
	 *
	 * @param  mixed $offset property
	 *
	 * @return
	 */
	public function offsetExists($offset) {
		return isset($this->properties[$offset]);
	}
	
	/**
	 * Implementation of Array Access Interface, you can access bean objects
	 * like an array.
	 * Unsets a value from the array/bean.
	 *
	 * @param  mixed $offset property
	 *
	 * @return
	 */
	public function offsetUnset($offset) {
		unset($this->properties[$offset]);
	}
	
	/**
	 * Implementation of Array Access Interface, you can access bean objects
	 * like an array.
	 * Returns value of a property.
	 *
	 * @param  mixed $offset property
	 *
	 * @return mixed
	 */
	public function offsetGet($offset) {
		return $this->__get($offset);
	}
	
	/**
	 * Chainable method to cast a certain ID to a bean; for instance:
	 * $person = $club->fetchAs('person')->member;
	 * This will load a bean of type person using member_id as ID.
	 *
	 * @param  string $type preferred fetch type
	 *
	 * @return RedBean_OODBBean
	 */
	public function fetchAs($type) {
		$this->fetchType = $type;
		return $this;
	}
	
	/**
	* For polymorphic bean relations.
	* Same as fetchAs but uses a column instead of a direct value.
	*
	* @param string $column
	*
	* @return RedBean_OODBean
	*
	*/
	public function poly($field) {
		return $this->fetchAs($this->$field);
	}
	
	/**
	 * Implementation of Countable interface. Makes it possible to use
	 * count() function on a bean.
	 * 
	 * @return integer
	 */
	public function count() {
		return count($this->properties);
	}
	
	/**
	 * Checks wether a bean is empty or not.
	 * A bean is empty if it has no other properties than the id field OR
	 * if all the other property are empty().
	 * 
	 * @return boolean 
	 */
	public function isEmpty() {
		$empty = true;
		foreach($this->properties as $key => $value) {
			if ($key == 'id') {
				continue;
			}
			if (!empty($value)) { 
				$empty = false;
			}	
		}
		return $empty;
	}
	
	/**
	 * Chainable setter.
	 * 
	 * @param string $property the property of the bean
	 * @param mixed  $value    the value you want to set 
	 * 
	 * @return RedBean_OODBBean
	 */
	public function setAttr($property, $value) {
		$this->$property = $value;
		return $this;
	}
	
	/**
	 * Comfort method.
	 * Unsets all properties in array.
	 * 
	 * @param array $properties properties you want to unset.
	 * 
	 * @return RedBean_OODBBean 
	 */
	public function unsetAll($properties) {
		foreach($properties as $prop) {
			if (isset($this->properties[$prop])) {
				unset($this->properties[$prop]);
			}
		}
		return $this;
	}
	
	/**
	 * Returns original (old) value of a property. 
	 * You can use this method to see what has changed in a
	 * bean.
	 * 
	 * @param string $property name of the property you want the old value of
	 * 
	 * @return mixed
	 */
	public function old($property) {
		$old = $this->getMeta('sys.orig', array());
		if (array_key_exists($property, $old)) {
			return $old[$property];
		}
	}
	
	/**
	 * Convenience method.
	 * Returns true if the bean has been changed, or false otherwise.
	 * Same as $bean->getMeta('tainted');
	 * Note that a bean becomes tainted as soon as you retrieve a list from
	 * the bean. This is because the bean lists are arrays and the bean cannot 
	 * determine whether you have made modifications to a list so RedBeanPHP
	 * will mark the whole bean as tainted.
	 * 
	 * @return boolean 
	 */
	public function isTainted() {
		return $this->getMeta('tainted');
	}
	
	/**
	 * Returns TRUE if the value of a certain property of the bean has been changed and
	 * FALSE otherwise.
	 * 
	 * @param string $property name of the property you want the change-status of
	 * 
	 * @return boolean 
	 */
	public function hasChanged($property){
		return (array_key_exists($property, $this->properties)) ? 
			$this->old($property) != $this->properties[$property] : false;
	}
	
	/**
	 * Creates a N-M relation by linking an intermediate bean.
	 * This method can be used to quickly connect beans using indirect
	 * relations. For instance, given an album and a song you can connect the two
	 * using a track with a number like this:
	 * 
	 * Usage:
	 * 
	 * $album->link('track', array('number'=>1))->song = $song;
	 * 
	 * or:
	 * 
	 * $album->link($trackBean)->song = $song;
	 * 	
	 * What this method does is adding the link bean to the own-list, in this case
	 * ownTrack. If the first argument is a string and the second is an array or
	 * a JSON string then the linking bean gets dispensed on-the-fly as seen in
	 * example #1. After preparing the linking bean, the bean is returned thus
	 * allowing the chained setter: ->song = $song.
	 * 
	 * @param string|RedBean_OODBBean $type          type of bean to dispense or the full bean
	 * @param string|array            $qualification JSON string or array (optional)
	 */
	public function link($typeOrBean, $qualification = array()) {
		if (is_string($typeOrBean)) {
			$bean = $this->beanHelper->getToolBox()->getRedBean()->dispense($typeOrBean);
			if (is_string($qualification)) {
				$data = json_decode($qualification, true);
			} else {
				$data = $qualification;
			}
			foreach($data as $key => $value) {
				$bean->$key = $value;
			}
		} else {
			$bean = $typeOrBean;
		}		
		$list = 'own'.ucfirst($bean->getMeta('type'));
		array_push($this->$list, $bean);
		return $bean;
	}
	
	/**
	 * Returns the same bean freshly loaded from the database.
	 * 
	 * @return RedBean_OODBBean 
	 */
	public function fresh() {
		return $this->beanHelper->getToolbox()->getRedBean()->load($this->getMeta('type'), $this->id);
	}
	
	/**
	 * Registers a association renaming globally.
	 * 
	 * @param string $via 
	 */
	public function via($via) {
		$this->via = $via;
		return $this;
	}
	
	/**
	 * Counts all own beans of type $type.
	 * Also works with alias(), with() and withCondition().
	 * 
	 * @param string $type the type of bean you want to count
	 *
	 * @return integer
	 */
	public function countOwn($type) {
		$type = $this->beau($type);
		if ($this->aliasName) {
			$parentField = $this->aliasName;
			$myFieldLink = $this->aliasName.'_id';
			$this->aliasName = null;
		} else {
			$myFieldLink = $this->__info['type'].'_id';
			$parentField = $this->__info['type'];
		}
		$count = 0;
		if ($this->getID()>0) {
			$bindings = array_merge(array($this->getID()), $this->withParams);
			$count = $this->beanHelper->getToolbox()->getWriter()->queryRecordCount($type, array(), " $myFieldLink = ? ".$this->withSql, $bindings);
		}
		$this->withSql = '';
		$this->withParams = array();
		return (integer) $count;
	}
	
	/**
	 * Counts all shared beans of type $type.
	 * Also works with via(), with() and withCondition().
	 * 
	 * @param string $type type of bean you wish to count
	 * 
	 * @return integer
	 */
	public function countShared($type) {
		$toolbox = $this->beanHelper->getToolbox();
		$redbean = $toolbox->getRedBean();
		$writer = $toolbox->getWriter();
		if ($this->via) {
			$oldName = $writer->getAssocTable(array($this->__info['type'],$type));
			if ($oldName !== $this->via) {
				//set the new renaming rule
				$writer->renameAssocTable($oldName, $this->via);
				$this->via = null;
			} 
		}
		$type = $this->beau($type);
		$count = 0;
		if ($this->getID()>0) {
			$count = $redbean->getAssociationManager()->relatedCount($this, $type, $this->withSql, $this->withParams, true);
		}
		$this->withSql = '';
		$this->withParams = array();
		return (integer) $count;
	}
}