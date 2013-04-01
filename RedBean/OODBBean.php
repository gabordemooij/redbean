<?php
/**
 * RedBean_OODBBean (Object Oriented DataBase Bean)
 * 
 * @file 			RedBean/RedBean_OODBBean.php
 * @desc			The Bean class used for passing information
 * @author			Gabor de Mooij and the RedBeanPHP community
 * @license			BSD/GPLv2
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_OODBBean implements IteratorAggregate, ArrayAccess, Countable {
	/**
	 * @var boolean
	 */
	private static $flagUseBeautyfulColumnnames = true;
	/**
	 * @var array
	 */
	private static $beautifulColumns = array();
	/**
	 * @var boolean  
	 */
	private static $flagKeyedExport = false;
	/**
	* @var boolean
	*/
	private $flagSkipBeau = false;
	/**
	 * @var array $properties
	 */
	private $properties = array();
	/**
	 * @var array
	 */
	private $__info = array();
	/**
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
		self::$flagUseBeautyfulColumnnames = (boolean) $flag;
	}
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
		if (!$notrim && is_array($selection)) foreach($selection as $k => $s){ $selection[$k] = trim($s); }
		foreach($arr as $k => $v) {
			if ($k != '__info') {
				if (!$selection || ($selection && in_array($k, $selection))) {
					$this->$k = $v;
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
			foreach($this as $k => $v) {
				if (substr($k, -3) == '_id') {
					$prop = substr($k, 0, strlen($k)-3);
					$this->$prop;
				}
			}
		}
		foreach($this as $k => $v) {
			if (!$onlyMe && is_array($v)) {
				$vn = array();
				foreach($v as $i => $b) {
					if (is_numeric($i) && !self::$flagKeyedExport) {
						$vn[] = $b->export($meta, false, false, $filters);
					} else {
						$vn[$i] = $b->export($meta, false, false, $filters);
					}
					$v = $vn;
				}
			} elseif ($v instanceof RedBean_OODBBean) {
				if (is_array($filters) && count($filters) && !in_array(strtolower($v->getMeta('type')), $filters)) {
					continue;
				}
				$v = $v->export($meta, $parents, false, $filters);
			}
			$arr[$k] = $v;
		}
		if ($meta) $arr['__info'] = $this->__info;
		return $arr;
	}
	/**
	 * Exports the bean to an object.
	 * 
	 * @param object $obj target object
	 * 
	 * @return array $arr
	 */
	public function exportToObj($obj) {
		foreach($this->properties as $k => $v) {
			if (!is_array($v) && !is_object($v))
			$obj->$k = $v;
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
	 * @param string|RedBean_SQLHelper $sql SQL to be added to retrieval query.
	 * @param array                    $params array with parameters to bind to SQL snippet
	 * 
	 * @return RedBean_OODBBean $self
	 */
	public function with($sql, $params = array()) {
		if ($sql instanceof RedBean_SQLHelper) {
			list($this->withSql, $this->withParams) = $sql->getQuery();
		} else {
			$this->withSql = $sql;
			$this->withParams = $params;
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
	 * @param string|RedBean_SQLHelper $sql    SQL to be added to retrieval query (prefixed by AND)
	 * @param array                    $params array with parameters to bind to SQL snippet
	 * 
	 * @return RedBean_OODBBean $self
	 */
	public function withCondition($sql, $params = array()) {
		if ($sql instanceof RedBean_SQLHelper) {
			list($sql, $params) = $sql->getQuery();
		} 
		$this->withSql = ' AND '.$sql;
		$this->withParams = $params;
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
		$this->aliasName = $aliasName;
		return $this;
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
		if (strpos($property, 'own') !== 0 && strpos($property, 'shared') !== 0) {
			if (isset(self::$beautifulColumns[$property])) {
				$propertyBeau = self::$beautifulColumns[$property];
			} else {
				$propertyBeau = strtolower(preg_replace('/(?<=[a-z])([A-Z])|([A-Z])(?=[a-z])/', '_$1$2', $property));
				self::$beautifulColumns[$property] = $propertyBeau;
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
	 * @param string $property
	 * @return mixed $value
	 */
	public function &__get($property) {
		if (self::$flagUseBeautyfulColumnnames && !$this->flagSkipBeau) {
			$property = $this->beau($property);	
		}
		if ($this->beanHelper) {
			$toolbox = $this->beanHelper->getToolbox();
			$redbean = $toolbox->getRedBean();
		}
		if ($this->withSql !== '') {
			if (strpos($property, 'own') === 0) {
				unset($this->properties[$property]);
			}
		}	
		if (!isset($this->properties[$property])) { 
			$fieldLink = $property.'_id'; 
			if (isset($this->$fieldLink) && $fieldLink !== $this->getMeta('sys.idfield')) {
				$this->__info['tainted'] = true; 
				$bean = $this->getMeta('sys.parentcache.'.$property);
				if (!$bean) { 
					$type =  $this->getAlias($property);
					$targetType = $this->properties[$fieldLink];
					$bean =  $redbean->load($type, $targetType);
				}
				$this->properties[$property] = $bean;
				return $this->properties[$property];
			}
			elseif (strpos($property, 'own') === 0 && ctype_upper(substr($property, 3, 1))) {
				$type = lcfirst(substr($property, 3));
				if (self::$flagUseBeautyfulColumnnames) {
					$type = $this->beau($type);
				}
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
					$params = array_merge(array($this->getID()), $this->withParams);
					$beans = $redbean->find($type, array(), array(" $myFieldLink = ? ".$this->withSql, $params));
				}
				$this->withSql = '';
				$this->withParams = array();
				foreach($beans as $b) {
					$b->__info['sys.parentcache.'.$parentField] = $this;
				}
				$this->properties[$property] = $beans;
				$this->__info['sys.shadow.'.$property] = $beans;
				$this->__info['tainted'] = true;
				return $this->properties[$property];
			}
			elseif (strpos($property, 'shared') === 0 && ctype_upper(substr($property, 6, 1))) {
				$type = lcfirst(substr($property, 6));
				if (self::$flagUseBeautyfulColumnnames ) {
					$type = $this->beau($type);
				}	
				$keys = $redbean->getAssociationManager()->related($this, $type);
				if (!count($keys)) $beans = array(); else
				if (trim($this->withSql) !== '') {
					$beans = $redbean->find($type, array('id' => $keys), array($this->withSql, $this->withParams), true);
				} else {
					$beans = $redbean->batch($type, $keys);
				}
				$this->withSql = '';
				$this->withParams = array();
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
	 * @param string $property
	 * @param  mixed $value
	 */
	public function __set($property, $value) {
		if (self::$flagUseBeautyfulColumnnames) {
			$property = $this->beau($property);
		}
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
	 * @param string $property property
	 * @param mixed  $value    value
	 */
	public function setProperty($property, $value) {
		$this->properties[$property] = $value;
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
	 * Reroutes a call to Model if exists. (new fuse)
	 * 
	 * @param string $method
	 * @param array $args
	 * 
	 * @return mixed $mixed
	 */
	public function __call($method, $args) {
		if (!isset($this->__info['model'])) {
			$model = $this->beanHelper->getModelForBean($this);
			if (!$model) return;
			$this->__info['model'] = $model;
		}
		if (!method_exists($this->__info['model'], $method)) return null;
		return call_user_func_array(array($this->__info['model'], $method), $args);
	}
	/**
	 * Implementation of __toString Method
	 * Routes call to Model.
	 * 
	 * @return string $string
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
	 * @param  mixed $value value
	 *
	 * @return void
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
	 * @return
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
	 * @return integer $numberOfProperties number of properties in the bean. 
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
			if ($key == 'id') continue;
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
	 * @return RedBean_OODBBean the bean 
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
		if (isset($old[$property])) {
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
	public function hasChanged($property) {
		if (!isset($this->properties[$property])) return false;
		return ($this->old($property) != $this->properties[$property]);
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
}