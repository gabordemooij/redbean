<?php
/**
 * RedBean_OODBBean (Object Oriented DataBase Bean)
 * @file 		RedBean/RedBean_OODBBean.php
 * @description		The Bean class used for passing information
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_OODBBean implements IteratorAggregate, ArrayAccess {

    /**
     * Reference to NULL property for magic getter.
     *
     * @var Null $null
     */
    private $null = null;


	/**
	 * Properties of the bean. These are kept in a private
	 * array called properties and exposed through the array interface.
	 *
	 * @var array $properties
	 */
	private $properties = array();

	/**
	 * Meta Data storage. This is the internal property where all
	 * Meta information gets stored.
	 *
	 * @var array
	 */
	private $__info = NULL;

	/**
	 * Contains a BeanHelper to access service objects like
	 * te association manager and OODB.
	 *
	 * @var RedBean_BeanHelper
	 */
	private $beanHelper = NULL;

	/**
	 * Contains the latest Fetch Type.
	 * A Fetch Type is a preferred type for the next nested bean.
	 *
	 * @var null
	 */
	public static $fetchType = NULL;

	/**
	 * Sets the Bean Helper. Normally the Bean Helper is set by OODB.
	 * Here you can change the Bean Helper. The Bean Helper is an object
	 * providing access to a toolbox for the bean necessary to retrieve
	 * nested beans (bean lists: ownBean,sharedBean) without the need to
	 * rely on static calls to the facade (or make this class dep. on OODB).
	 *
	 * @param RedBean_IBeanHelper $helper
	 * @return void
	 */
	public function setBeanHelper(RedBean_IBeanHelper $helper) {
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
	 * Imports all values in associative array $array. Every key is used
	 * for a property and every value will be assigned to the property
	 * identified by the key. So basically this method converts the
	 * associative array to a bean by loading the array. You can filter
	 * the values using the $selection parameter. If $selection is boolean
	 * false, no filtering will be applied. If $selection is an array
	 * only the properties specified (as values) in the $selection
	 * array will be taken into account. To skip a property, omit it from
	 * the $selection array. Also, instead of providing an array you may
	 * pass a comma separated list of property names. This method is
	 * chainable because it returns its own object.
	 * Imports data into bean
	 *
	 * @param array        $array     what you want to import
	 * @param string|array $selection selection of values
	 * @param boolean      $notrim    if TRUE values will not be trimmed
	 *
	 *    @return RedBean_OODBBean $this
	 */
	public function import( $arr, $selection=false, $notrim=false ) {
		if (is_string($selection)) $selection = explode(",",$selection);
		//trim whitespaces
		if (!$notrim && is_array($selection)) foreach($selection as $k=>$s){ $selection[$k]=trim($s); }
		foreach($arr as $k=>$v) {
			if ($k != "__info") {
				if (!$selection || ($selection && in_array($k,$selection))) {
					$this->$k = $v;
				}
			}
		}
		return $this;
	}

	/**
	 * Exports the bean as an array.
	 * This function exports the contents of a bean to an array and returns
	 * the resulting array. If $meta eq uals boolean TRUE, then the array will
	 * also contain the __info section containing the meta data inside the
	 * RedBean_OODBBean Bean object.
	 * @param boolean $meta
	 * @return array $arr
	 */
	public function export($meta = false) {
		$arr = $this->properties;
		foreach($arr as $k=>$v) {
			if (is_array($v) || is_object($v)) unset($arr[$k]);
		}
		if ($meta) $arr["__info"] = $this->__info;
		return $arr;
	}

	/**
	 * Exports the bean to an object.
	 * This function exports the contents of a bean to an object.
	 * @param object $obj
	 * @return array $arr
	 */
	public function exportToObj($obj) {
		foreach($this->properties as $k=>$v) {
			if (!is_array($v) && !is_object($v))
			$obj->$k = $v;
		}
	}

	/**
	 * Implements isset() function for use as an array.
	 * Returns whether bean has an element with key
	 * named $property. Returns TRUE if such an element exists
	 * and FALSE otherwise.
	 * @param string $property
	 * @return boolean $hasProperty
	 */
	public function __isset( $property ) {
		return (isset($this->properties[$property]));
	}



	/**
	 * Returns the ID of the bean no matter what the ID field is.
	 *
	 * @return string $id record Identifier for bean
	 */
	public function getID() {
		$idfield = $this->getMeta("sys.idfield");
		return (string) $this->$idfield;
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

		$fieldLink = $property."_id";
		if (isset($this->$fieldLink)) {
			//wanna unset a bean reference?
			$this->$fieldLink = null;
			//return;
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
	public function removeProperty( $property ) {
		unset($this->properties[$property]);
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
	public function &__get( $property ) {

		if ($this->beanHelper)
		$toolbox = $this->beanHelper->getToolbox();

		if (!isset($this->properties[$property])) {

			$fieldLink = $property."_id";

			/**
			 * All this magic can be become very complex quicly. For instance,
			 * my PHP CLI produced a segfault while testing this code. Turns out that
			 * if fieldlink equals idfield, scripts tend to recusrively load beans and
			 * instead of giving a clue they simply crash and burn isnt that nice?
			 */
			if (isset($this->$fieldLink) && $fieldLink != $this->getMeta('sys.idfield')) {
				$this->setMeta("tainted",true);
				$type =  $toolbox->getWriter()->getAlias($property);
				$targetType = $this->properties[$fieldLink];
				$bean =  $toolbox->getRedBean()->load($type,$targetType);
				//return $bean;
				$this->properties[$property] = $bean;
				return $this->properties[$property];
			}

			if (strpos($property,'own')===0) {
				$firstCharCode = ord(substr($property,3,1));
				if ($firstCharCode>=65 && $firstCharCode<=90) {
					$type = (lcfirst(str_replace('own','',$property)));
					$myFieldLink = $this->getMeta('type')."_id";
					$beans = $toolbox->getRedBean()->find($type,array(),array(" $myFieldLink = ? ",array($this->getID())));
					$this->properties[$property] = $beans;
					$this->setMeta("sys.shadow.".$property,$beans);
					$this->setMeta("tainted",true);
					return $this->properties[$property];
				}
			}

			if (strpos($property,'shared')===0) {
				$firstCharCode = ord(substr($property,6,1));
				if ($firstCharCode>=65 && $firstCharCode<=90) {
					$type = (lcfirst(str_replace('shared','',$property)));
					$keys = $toolbox->getRedBean()->getAssociationManager()->related($this,$type);
					if (!count($keys)) $beans = array(); else
					$beans = $toolbox->getRedBean()->batch($type,$keys);
					$this->properties[$property] = $beans;
					$this->setMeta("sys.shadow.".$property,$beans);
					$this->setMeta("tainted",true);
					return $this->properties[$property];
				}
			}

			return $this->null;

		}


		return $this->properties[$property];
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

	public function __set( $property, $value ) {

		$this->__get($property);
		$this->setMeta("tainted",true);

		if ($value===false) {
			$value = "0";
		}
		if ($value===true) {
			$value = "1";
		}
		$this->properties[$property] = $value;
	}

	/**
	 * Returns the value of a meta property. A meta property
	 * contains extra information about the bean object that will not
	 * get stored in the database. Meta information is used to instruct
	 * RedBean as well as other systems how to deal with the bean.
	 * For instance: $bean->setMeta("buildcommand.unique.0", array(
	 * "column1", "column2", "column3") );
	 * Will add a UNIQUE constaint for the bean on columns: column1, column2 and
	 * column 3.
	 * To access a Meta property we use a dot separated notation.
	 * If the property cannot be found this getter will return NULL instead.
	 * @param string $path
	 * @param mixed $default
	 * @return mixed $value
	 */
	public function getMeta( $path, $default = NULL) {
		return (isset($this->__info[$path])) ? $this->__info[$path] : $default;
	}

	/**
	 * Stores a value in the specified Meta information property. $value contains
	 * the value you want to store in the Meta section of the bean and $path
	 * specifies the dot separated path to the property. For instance "my.meta.property".
	 * If "my" and "meta" do not exist they will be created automatically.
	 * @param string $path
	 * @param mixed $value
	 */
	public function setMeta( $path, $value ) {
		$this->__info[$path] = $value;
	}

	/**
	 * Copies the meta information of the specified bean
	 * This is a convenience method to enable you to
	 * exchange meta information easily.
	 * @param RedBean_OODBBean $bean
	 * @return RedBean_OODBBean
	 */
	public function copyMetaFrom( RedBean_OODBBean $bean ) {
		$this->__info = $bean->__info;
		return $this;
	}

	/**
	 * Sleep function fore serialize() call. This will be invoked if you
	 * perform a serialize() operation.
	 *
	 * @return mixed $array
	 */
	public function __sleep() {
		//return the public stuff
		$this->setMeta("sys.oodb",null);
		return array('properties','__info');
	}

	/**
	 * Reroutes a call to Model if exists. (new fuse)
	 * @param string $method
	 * @param array $args
	 * @return mixed $mixed
	 */
	public function __call($method, $args) {
		if (!isset($this->__info["model"])) {
			//@todo eliminate this dependency!
			$modelName = RedBean_ModelHelper::getModelName( $this->getMeta("type") );
			if (!class_exists($modelName)) return null;
			$obj = new $modelName();
			$obj->loadBean($this);
			$this->__info["model"] = $obj;
		}
		if (!method_exists($this->__info["model"],$method)) return null;
		return call_user_func_array(array($this->__info["model"],$method), $args);
	}

	/**
	 * Implementation of __toString Method
	 * Routes call to Model.
	 * @return string $string
	 */
	public function __toString() {
		$string = $this->__call('__toString',array());
		if ($string === null) {
			return json_encode($this->properties);
		}
		else {
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
		self::$fetchType = $type;
		return $this;
	}





}


