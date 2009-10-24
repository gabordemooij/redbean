<?php 
/**
 * RedBean_OODBBean (Object Oriented DataBase Bean)
 * @package 		RedBean/RedBean_OODBBean.php
 * @description		The Bean class used for passing information
 * @author			Gabor de Mooij
 * @license			BSD
 */
class RedBean_OODBBean {

	/**
	 * Meta Data storage
	 * @var array
	 */
	private $__info = NULL;

	/**
	 * Imports data into bean
	 * @param array $arr
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
	}

	/**
	 * Exports the bean as an array
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
	 * Returns NULL instead of throwing errors
	 * @param string $property
	 * @return mixed $value
	 */
	public function __get( $property ) {
		if (!isset($this->$property)) return NULL;
	}


	/**
	 * Fetches a meta data item
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
	 * Sets a meta data item
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


}

