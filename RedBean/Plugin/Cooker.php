<?php
/**
 * RedBean Cooker
 *
 * @file    RedBean/Cooker.php
 * @desc    Turns arrays into bean collections for easy persistence.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * (c) copyright G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_Plugin_Cooker implements RedBean_Plugin
{
	/**
	 * @var boolean
	 */
	private static $loadBeans = FALSE;

	/**
	 * @var boolean
	 */
	private static $useNULLForEmptyString = FALSE;

	/**
	 * @var RedBean_Toolbox
	 */
	private $toolbox;

	/**
	 * @var RedBean_OODB
	 */
	private $redbean;

	/**
	 * If you enable bean loading graph will load beans if there is an ID in the array.
	 * This is very powerful but can also cause security issues if a user knows how to
	 * manipulate beans and there is no model based ID validation.
	 *
	 * @param boolean $yesNo
	 *
	 * @return void
	 */
	public static function enableBeanLoading( $yesNo )
	{
		self::$loadBeans = ( $yesNo );
	}

	/**
	 * Static version of setUseNullFlag.
	 *
	 * @param boolean $yesNo
	 *
	 * @return void
	 */
	public static function setUseNullFlagSt( $yesNo )
	{
		self::$useNULLForEmptyString = (boolean) $yesNo;
	}

	/**
	 * Sets the toolbox to be used by graph()
	 *
	 * @param RedBean_Toolbox $toolbox toolbox
	 *
	 * @return void
	 */
	public function setToolbox( RedBean_Toolbox $toolbox )
	{
		$this->toolbox = $toolbox;
		$this->redbean = $this->toolbox->getRedbean();
	}

	/**
	 * Loads bean, recurses if one of the property appears to be a list.
	 *
	 * @param array   $array       data array to import as a bean
	 * @param boolean $filterEmpty if TRUE empty STRING values are converted to NULL (default FALSE)
	 *
	 * @return RedBean_OODBBean
	 *
	 * @throws RedBean_Exception_Security
	 */
	private function loadBean( &$array, $filterEmpty )
	{
		$type = $array['type'];

		unset( $array['type'] );

		if ( isset( $array['id'] ) ) { // Do we need to load the bean?
			if ( self::$loadBeans ) {
				$bean = $this->redbean->load( $type, (int) $array['id'] );
			} else {
				throw new RedBean_Exception_Security( 'Attempt to load a bean in Cooker. Use enableBeanLoading to override but please read security notices first.' );
			}
		} else {
			$bean = $this->redbean->dispense( $type );
		}

		foreach ( $array as $property => $value ) {
			if ( is_array( $value ) ) {
				$bean->$property = $this->graph( $value, $filterEmpty );
			} else {
				$bean->$property = ( $value == '' && self::$useNULLForEmptyString ) ? NULL : $value;
			}
		}

		return $bean;
	}

	/**
	 * Loads a list. Recurses for every bean in the list.
	 *
	 * @param array   $array       data array to import as a list
	 * @param boolean $filterEmpty if TRUE empty lists will NOT be imported
	 *
	 * @return array
	 *
	 * @throws RedBean_Exception_Security
	 */
	private function loadList( &$array, $filterEmpty )
	{
		$beans = array();
		foreach ( $array as $key => $value ) {
			$listBean = $this->graph( $value, $filterEmpty );

			if ( !( $listBean instanceof RedBean_OODBBean ) ) {
				throw new RedBean_Exception_Security( 'Expected bean but got :' . gettype( $listBean ) );
			}

			if ( $listBean->isEmpty() ) {
				if ( !$filterEmpty ) {
					$beans[$key] = $listBean;
				}
			} else {
				$beans[$key] = $listBean;
			}
		}

		return $beans;
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
	 *    $form = array(
	 *        'type' => 'order',
	 *        'ownProduct' => array(
	 *            array('id' => 171, 'type' => 'product'),
	 *        ),
	 *        'ownCustomer' => array(
	 *            array('type' => 'customer', 'name' => 'Bill')
	 *        ),
	 *        'sharedCoupon' => array(
	 *            array('type' => 'coupon', 'name' => '123'),
	 *            array('type' => 'coupon', 'id' => 3)
	 *        )
	 *    );
	 *
	 * Each entry in the array will become a property of the bean.
	 * The array needs to have a type-field indicating the type of bean it is
	 * going to be. The array can have nested arrays. A nested array has to be
	 * named conform the bean-relation conventions, i.e. ownPage/sharedPage
	 * each entry in the nested array represents another bean.
	 *
	 * @param    array  $array       array to be turned into a bean collection
	 * @param   boolean $filterEmpty whether you want to exclude empty beans
	 *
	 * @return    array
	 *
	 * @throws RedBean_Exception_Security
	 */
	public function graph( $array, $filterEmpty = FALSE )
	{
		if ( is_array( $array ) && isset( $array['type'] ) ) {
			return $this->loadBean( $array, $filterEmpty );
		} elseif ( is_array( $array ) ) {
			return $this->loadList( $array, $filterEmpty );
		} else {
			throw new RedBean_Exception_Security( 'Expected array but got :' . gettype( $array ) );
		}
	}

	/**
	 * Toggles the use-NULL flag.
	 *
	 * @param boolean $yesNo
	 *
	 * @return void
	 */
	public function setUseNullFlag( $yesNo )
	{
		self::$useNULLForEmptyString = (bool) $yesNo;
	}
}
