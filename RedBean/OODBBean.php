<?php 
/**
 * RedBean_OODBBean (Object Oriented DataBase Bean)
 * @file 		RedBean/RedBean_OODBBean.php
 * @description		The Bean class used for passing information
 * @author			Gabor de Mooij
 * @license			BSD
 */
class RedBean_OODBBean {

	/**
	 * Meta Data storage. This is the internal property where all
	 * Meta information gets stored.
	 * @var array
	 */
	private $__info = NULL;

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
	 * @param array $array
	 * @param mixed $selection
	 * @return RedBean_OODBBean $this
	 */
	public function import( $arr, $selection=false ) {
		if (is_string($selection)) $selection = explode(",",$selection);
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
	 * the resulting array. If $meta equals boolean TRUE, then the array will
	 * also contain the __info section containing the meta data inside the
	 * RedBean_OODBBean Bean object.
	 * @param boolean $meta
	 * @return array $arr
	 */
	public function export($meta = false) {
		$arr = array();
		foreach($this as $p=>$v) {
			if ($p != "__info" || $meta) {
				$arr[ $p ] = $v;
			}
		}
		return $arr;
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
	public function __get( $property ) {
		if (!isset($this->$property) || $this->$property=="") {
			return NULL;
		}
		return $this->$property;
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
		$ref = $this->__info;
		$parts = explode(".", $path);
		foreach($parts as $part) {
			if (isset($ref[$part])) {
				$ref = $ref[$part];
			}
			else {
				return $default;
			}
		}
		return $ref;
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
		$ref = &$this->__info;
		$parts = explode(".", $path);
		$lastpart = array_pop( $parts );
		foreach($parts as $part) {
			if (!isset($ref[$part])) {
				$ref[$part] = array();
			}
			$ref = &$ref[$part];
		}
		$ref[$lastpart] = $value;
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

	//@todo copy/clone a bean


}

