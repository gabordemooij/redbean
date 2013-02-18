<?php
/**
 * OneProxy - a proxy class for one-to-one relations.
 * 
 * @file			RedBean/OneProxy.php
 * @desc			Acts as a small proxy for 1-1 relations
 * @author			Gabor de Mooij and the RedBeanPHP Community
 * @license			BSD/GPLv2
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 *
 */
class RedBean_OneProxy {
	
	/**
	 * Bean Helper
	 * @var RedBean_BeanHelper 
	 */
	private $beanHelper;
	
	/**
	 * The original bean
	 * @var RedBean_OODBBean
	 */
	private $bean;
	
	/**
	 * Constructor
	 * @param type $bean
	 * @param type $beanHelper 
	 */
	public function __construct($bean,$beanHelper) {
		$this->beanHelper = $beanHelper;
		$this->bean = $bean;
	}
	
	/**
	 * Magic setter. This does a $bean->own<list> = array( $bean )
	 * on the bean and returns the bean; as if you were acting on the bean.
	 * 
	 * @param string $property
	 * @param mixed  $value
	 * 
	 * @return RedBean_OODBBean 
	 */
	public function __set($property,$value) {
		$list = 'own'.ucfirst($property);
		$this->bean->$list = array($value);
		return $this->bean;
	}
	
	/**
	 * Returns a reference to the property of the bean.
	 * @param type $property
	 * @return type 
	 */
	public function &__get($property) {
		$list = 'own'.ucfirst($property);
		$ownList = $this->bean->$list;
		$bean = null;
		if (count($ownList)>0) {
			$bean = reset($ownList);
		}
		return $bean;
	}
}