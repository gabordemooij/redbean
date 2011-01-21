<?php
/**
 * SimpleModel
 * @file 		RedBean/SimpleModel.php
 * @description		Part of FUSE
 * @author              Gabor de Mooij
 * @license		BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_SimpleModel {

	/**
	 * Contains the inner bean.
	 * @var RedBean_OODBBean
	 */
	protected $bean;

	/**
	 * Used by FUSE: the ModelHelper class to connect a bean to a model.
	 * This method loads a bean in the model.
	 * @param RedBean_OODBBean $bean
	 */
	public function loadBean( RedBean_OODBBean $bean ) {
		$this->bean = $bean;
	}

	/**
	 * Magic Getter to make the bean properties available from
	 * the $this-scope.
	 * @param string $prop
	 * @return mixed $propertyValue
	 */
	public function __get( $prop ) {
		return $this->bean->$prop;
	}

	/**
	 * Magic Setter
	 * @param string $prop
	 * @param mixed $value
	 */
	public function __set( $prop, $value ) {
		$this->bean->$prop = $value;
	}


	protected function __hasProperties( $list ) {
		$missing = array();
		$properties = explode(",", $list);
		foreach($properties as $property) {
			if (empty($this->bean->$property)) {
				$missing[] = $property;
			}
		}
		return $missing;
	}


}