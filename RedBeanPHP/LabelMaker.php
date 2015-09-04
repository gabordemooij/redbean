<?php

namespace RedBeanPHP;

use RedBeanPHP\ToolBox as ToolBox;
use RedBeanPHP\OODBBean as OODBBean;

/**
 * Label Maker.
 * Makes so-called label beans.
 * A label is a bean with only an id, type and name property.
 * Labels can be used to create simple entities like categories, tags or enums.
 * This service class provides convenience methods to deal with this kind of
 * beans.
 *
 * @file       RedBeanPHP/LabelMaker.php
 * @author     Gabor de Mooij and the RedBeanPHP Community
 * @license    BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class LabelMaker
{
	/**
	 * @var ToolBox
	 */
	protected $toolbox;

	/**
	 * Constructor.
	 *
	 * @param ToolBox $toolbox
	 */
	public function __construct( ToolBox $toolbox )
	{
		$this->toolbox = $toolbox;
	}

	/**
	 * A label is a bean with only an id, type and name property.
	 * This function will dispense beans for all entries in the array. The
	 * values of the array will be assigned to the name property of each
	 * individual bean.
	 *
	 * <code>
	 * $people = R::dispenseLabels( 'person', [ 'Santa', 'Claus' ] );
	 * </code>
	 *
	 * @param string $type   type of beans you would like to have
	 * @param array  $labels list of labels, names for each bean
	 *
	 * @return array
	 */
	public function dispenseLabels( $type, $labels )
	{
		$labelBeans = array();
		foreach ( $labels as $label ) {
			$labelBean       = $this->toolbox->getRedBean()->dispense( $type );
			$labelBean->name = $label;
			$labelBeans[]    = $labelBean;
		}

		return $labelBeans;
	}

	/**
	 * Gathers labels from beans. This function loops through the beans,
	 * collects the value of the name property for each individual bean
	 * and stores the names in a new array. The array then gets sorted using the
	 * default sort function of PHP (sort).
	 *
	 * Usage:
	 *
	 * <code>
	 * $o1->name = 'hamburger';
	 * $o2->name = 'pizza';
	 * implode( ',', R::gatherLabels( [ $o1, $o2 ] ) ); //hamburger,pizza
	 * </code>
	 *
	 * Note that the return value is an array of strings, not beans.
	 *
	 * @param array $beans list of beans to loop through
	 *
	 * @return array
	 */
	public function gatherLabels( $beans )
	{
		$labels = array();

		foreach ( $beans as $bean ) {
			$labels[] = $bean->name;
		}

		sort( $labels );

		return $labels;
	}

	/**
	 * Fetches an ENUM from the database and creates it if necessary.
	 * An ENUM has the following format:
	 *
	 * <code>
	 * ENUM:VALUE
	 * </code>
	 *
	 * If you pass 'ENUM' only, this method will return an array of its
	 * values:
	 *
	 * <code>
	 * implode( ',', R::gatherLabels( R::enum( 'flavour' ) ) ) //'BANANA,MOCCA'
	 * </code>
	 *
	 * If you pass 'ENUM:VALUE' this method will return the specified enum bean
	 * and create it in the database if it does not exist yet:
	 *
	 * <code>
	 * $bananaFlavour = R::enum( 'flavour:banana' );
	 * $bananaFlavour->name;
	 * </code>
	 *
	 * So you can use this method to set an ENUM value in a bean:
	 *
	 * <code>
	 * $shake->flavour = R::enum( 'flavour:banana' );
	 * </code>
	 *
	 * the property flavour now contains the enum bean, a parent bean.
	 * In the database, flavour_id will point to the flavour record with name 'banana'.
	 *
	 * @param string $enum ENUM specification for label
	 *
	 * @return array|OODBBean
	 */
	public function enum( $enum )
	{
		$oodb = $this->toolbox->getRedBean();

		if ( strpos( $enum, ':' ) === FALSE ) {
			$type  = $enum;
			$value = FALSE;
		} else {
			list( $type, $value ) = explode( ':', $enum );
			$value                = preg_replace( '/\W+/', '_', strtoupper( trim( $value ) ) );
		}

		/**
		 * We use simply find here, we could use inspect() in fluid mode etc,
		 * but this would be useless. At first sight it looks clean, you could even
		 * bake this into find(), however, find not only has to deal with the primary
		 * search type, people can also include references in the SQL part, so avoiding
		 * find failures does not matter, this is still the quickest way making use
		 * of existing functionality.
		 *
		 * @note There seems to be a bug in XDebug v2.3.2 causing suppressed
		 * exceptions like these to surface anyway, to prevent this use:
		 *
		 * "xdebug.default_enable = 0"
		 *
		 *  Also see Github Issue #464
		 */
		$values = $oodb->find( $type );

		if ( $value === FALSE ) {
			return $values;
		}

		foreach( $values as $enumItem ) {
				if ( $enumItem->name === $value ) return $enumItem;
		}

		$newEnumItems = $this->dispenseLabels( $type, array( $value ) );
		$newEnumItem  = reset( $newEnumItems );

		$oodb->store( $newEnumItem );

		return $newEnumItem;
	}
}
