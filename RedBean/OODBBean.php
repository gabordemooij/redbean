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
	public function export() {
		$arr = array();
		foreach($this as $p=>$v) {
			if ($p != "__info") {
				$arr[ $p ] = $v;
			}
		}
		return $arr;
	}


	public function __get( $property ) {
		if (!isset($this->$property)) return NULL;
	}



}

