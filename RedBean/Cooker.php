<?php
 /**
 * @name RedBean Cooker
 * @file RedBean
 * @author Gabor de Mooij and the RedBean Team
 * @copyright Gabor de Mooij (c)
 * @license BSD
 *
 * The Cooker is a little candy to make it easier to read-in an HTML form.
 * This class turns a form into a collection of beans plus an array
 * describing the desired associations.
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_Cooker {

	public static $dontSetEmptyValues = false;

	/**
	 * This method will inspect the array provided and load/dispense the
	 * desired beans. To dispense a new bean, the array must contain:
	 *
	 * array( "newuser"=> array("type"=>"user","name"=>"John") )
	 *
	 * - Creates a new bean of type user, property name is set to "John"
	 *
	 * To load a bean (for association):
	 *
	 * array( "theaddress"=> array("type"=>"address","id"=>2) )
	 *
	 * - Loads a bean of type address with ID 2
	 *
	 * Now to associate this bean in your form:
	 *
	 * array("associations"=>array( "0" => array( "newuser-theaddress" ) ))
	 *
	 * - Associates the beans under keys newuser and theaddress.
	 *
	 * To modify an existing bean:
	 *
	 * array("existinguser"=>array("type"=>"user","id"=>2,"name"=>"Peter"))
	 *
	 * - Changes name of bean of type user with ID 2 to 'Peter'
	 *
	 * This function returns:
	 *
	 * array(
	 * 	"can" => an array with beans, either loaded or dispensed and populated
	 *  "pairs" => an array with pairs of beans to be associated
	 *  "sorted" => sorted by type
	 * );
	 *
	 * Note that this function actually does not store or associate anything at all,
	 * it just prepares two arrays.
	 *
	 * @static
	 * @param  $post the POST array containing the form data
	 * @return array hash table containing 'can' and 'pairs'
	 *
	 */
	public static function load($post, RedBean_ToolBox $toolbox) {
		$writer = $toolbox->getWriter();
		//fetch associations first and remove them from the array.
		if (isset($post["associations"])) {
			$associations = $post["associations"];
			unset($post["associations"]);
		}
		//We store beans here
		$can = $pairs = $sorted = array();
		foreach($post as $key => $rawBean) {
			if (is_array($rawBean) && isset($rawBean["type"])) {
				//get type and remove it from array
				$type = $rawBean["type"];
				unset($rawBean["type"]);
				//does it have an ID?
				$idfield = $writer->getIDField($type);
				if (isset($rawBean[$idfield])) {
					//yupz, get the id and remove it from array
					$id = $rawBean[$idfield];
					//ID == 0, and no more fields then this is an NULL option for a relation, skip.
					if ($id==0 && count($rawBean)===1) continue;
					unset($rawBean[$idfield]);
					//now we have the id, load the bean and store it in the can
					$bean = RedBean_Facade::load($type, $id);
				}
				else { //no id? then get a new bean...
					$bean = RedBean_Facade::dispense($type);
				}
				//do we need to modify this bean?
				foreach($rawBean as $field=>$value){
					if (!empty($value))
						$bean->$field = $value;
					elseif (!self::$dontSetEmptyValues)
						$bean->$field = $value;
				}
				$can[$key]=$bean;
				if (!isset($sorted[$type]))  $sorted[$type]=array();
				$sorted[$type][]=$bean;
			}
		}
		if (isset($associations) && is_array($associations)) {
			foreach($associations as $assoc) {
				foreach($assoc as $info) {
					if ($info=="0" || $info=="") continue;
					$keys = explode("-", $info);
					//first check if we can find the key in the can, --only key 1 is able to load
					if (isset($can[$keys[0]])) $bean1 = $can[$keys[0]]; else {
						$loader = explode(":",$keys[0]);
						$bean1 = RedBean_Facade::load( $loader[0], $loader[1] );
					}
					$bean2 = $can[$keys[1]];
					$pairs[] = array( $bean1, $bean2 );
				}
			}
		}
		return array(
			"can"=>$can, //contains the beans
			"pairs"=>$pairs, //contains pairs of beans
			"sorted"=>$sorted //contains beans sorted by type
		);
	}

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
		if (is_array($array) && isset($array["type"])) {
			$type = $array["type"];
			unset($array["type"]);
			//Do we need to load the bean?
			if (isset($array["id"])) {
				$id = (int) $array["id"];
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
		return $beans;
	}
}
