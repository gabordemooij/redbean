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

	private $flagUnsafe = false;
	
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
                if(is_array($array) && isset($array['type']) && is_array(@$array['id'])){ // allowing multi-select and multi-checkbox by restructuring the array.
                    foreach($array['id'] as $key=>$value){
                        foreach($array as $kk=>$vv){
                            if(is_array($vv)) {
                                if(isset($vv[$key])) $new[$key][$kk] = $vv[$key];
                            }
                            else $new[$key][$kk] = $vv;
                        }
                        
                    }
                    $array = $new;
                    unset($new);                    
                }
                
		$beans = array();
		if (is_array($array) && isset($array['type'])) {
			$type = $array['type'];
			unset($array['type']);
			//Do we need to load the bean?
			if (isset($array['id'])) {
				$id = (int) $array['id'];
				unset($array['id']);
				if (count($array)>0) {
					//$bean = $this->redbean->load($type,$id);
					$bean = $this->loadFromPool($type,$id,'w');
				}
				else {
					//no more properties besides type and id, read is enough.
					$bean = $this->loadFromPool($type,$id,'r');
				}
			}
			else {
				$bean = $this->redbean->dispense($type);
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
	
	public function loadFromPool($type, $id, $policy) {
		if ($this->flagUnsafe) return R::load($type,$id);
		if ($policy!='r' && $policy!='w') {
			throw new RedBean_Security_Exception('Illegal policy.');
		}
		if (!isset($this->pool[$policy][$type][$id])) {
			throw new RedBean_Exception_Security('Access Denied, user has no '.$policy.'-access to: '.$type.' - '.$id);
		}
		else {
			return $this->pool[$policy][$type][$id];
		}
	}
	
	public function addToPool($beans, $policy='r') {
		if ($policy!='r' && $policy!='w') {
			throw new RedBean_Security_Exception('Illegal policy.');
		}
		if (is_array($beans)) {
			foreach($beans as $bean) $this->addToPool($bean);
		}
		else {
			$bean = $beans;
			$this->pool[$policy][$bean->getMeta('type')][$bean->id] = $bean;
		}
	} 
	
	public function cleanPool() {
		$this->pool = array();
	}
	
	public function setUnsafe($tf) {
		$this->flagUnsafe = $tf;
	}
	
}
