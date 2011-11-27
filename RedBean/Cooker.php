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
	 * Sets the toolbox to be used by graph()
	 *
	 * @param RedBean_Toolbox $toolbox toolbox
	 * @return void
	 */
	public function setToolbox(RedBean_Toolbox $toolbox) {
		$this->toolbox = $toolbox;
		$this->redbean = $this->toolbox->getRedbean();
	}

	/**
	 * Turns a request array into a collection of beans
	 *
	 * @param  $array array
	 *
	 * @return array $beans beans
	 */
	public function graph( $array ) {
		$beans = array();
		if (is_array($array) && isset($array['type'])) {
			$type = $array['type'];
			unset($array['type']);
			//Do we need to load the bean?
			if (isset($array['id'])) {
				$id = (int) $array['id'];
				$bean = $this->redbean->load($type,$id);
			}
			else {
				$bean = $this->redbean->dispense($type);
			}
			foreach($array as $property=>$value) {
				if (is_array($value)) {
					$bean->$property = $this->graph($value);
				}
				else {
					$bean->$property = $value;
				}
			}
			return $bean;
		}
		elseif (is_array($array)) {
			foreach($array as $key=>$value) {
				$beans[$key] = $this->graph($value);
			}
			return $beans;
		}
		else {
			return $array;
		}
	}
}
