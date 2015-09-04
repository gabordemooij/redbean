<?php

namespace RedBeanPHP\Util;

use RedBeanPHP\OODB as OODB;
use RedBeanPHP\RedException as RedException;

/**
 * Dispense Helper
 *
 * A helper class containing a dispense utility.
 * 
 * @file    RedBeanPHP/Util/DispenseHelper.php
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class DispenseHelper
{
	/**
	 * Dispenses a new RedBean OODB Bean for use with
	 * the rest of the methods.
	 *
	 * @param OODB         $oodb              OODB
	 * @param string|array $typeOrBeanArray   type or bean array to import
	 * @param integer      $number            number of beans to dispense
	 * @param boolean	   $alwaysReturnArray if TRUE always returns the result as an array
	 *
	 * @return array|OODBBean
	 */
	public static function dispense( OODB $oodb, $typeOrBeanArray, $num = 1, $alwaysReturnArray = FALSE ) {

		if ( is_array($typeOrBeanArray) ) {

			if ( !isset( $typeOrBeanArray['_type'] ) ) {
				$list = array();
				foreach( $typeOrBeanArray as $beanArray ) {
					if ( 
						!( is_array( $beanArray ) 
						&& isset( $beanArray['_type'] ) ) ) {
						throw new RedException( 'Invalid Array Bean' );
					}
				}
				foreach( $typeOrBeanArray as $beanArray ) $list[] = self::dispense( $oodb, $beanArray );
				return $list;
			}

			$import = $typeOrBeanArray;
			$type = $import['_type'];
			unset( $import['_type'] );
		} else {
			$type = $typeOrBeanArray;
		}

		if ( !preg_match( '/^[a-z0-9]+$/', $type ) ) {
			throw new RedException( 'Invalid type: ' . $type );
		}

		$beanOrBeans = $oodb->dispense( $type, $num, $alwaysReturnArray );

		if ( isset( $import ) ) {
			$beanOrBeans->import( $import );
		}

		return $beanOrBeans;
	}
	
	
	/**
	 * Takes a comma separated list of bean types
	 * and dispenses these beans. For each type in the list
	 * you can specify the number of beans to be dispensed.
	 *
	 * Usage:
	 *
	 * <code>
	 * list( $book, $page, $text ) = R::dispenseAll( 'book,page,text' );
	 * </code>
	 *
	 * This will dispense a book, a page and a text. This way you can
	 * quickly dispense beans of various types in just one line of code.
	 *
	 * Usage:
	 *
	 * <code>
	 * list($book, $pages) = R::dispenseAll('book,page*100');
	 * </code>
	 *
	 * This returns an array with a book bean and then another array
	 * containing 100 page beans.
	 *
	 * @param OODB    $oodb       OODB
	 * @param string  $order      a description of the desired dispense order using the syntax above
	 * @param boolean $onlyArrays return only arrays even if amount < 2
	 *
	 * @return array
	 */
	public static function dispenseAll( OODB $oodb, $order, $onlyArrays = FALSE )
	{
		$list = array();

		foreach( explode( ',', $order ) as $order ) {
			if ( strpos( $order, '*' ) !== FALSE ) {
				list( $type, $amount ) = explode( '*', $order );
			} else {
				$type   = $order;
				$amount = 1;
			}

			$list[] = self::dispense( $oodb, $type, $amount, $onlyArrays );
		}

		return $list;
	}
}
