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
	 * @var boolean
	 */
	private static $enforceNamingPolicy = TRUE;

	/**
	 * Sets the enforce naming policy flag. If set to
	 * TRUE the RedBeanPHP naming policy will be enforced.
	 * Otherwise it will not. Use at your own risk.
	 * Setting this to FALSE is not recommended.
	 *
	 * @param boolean $yesNo whether to enforce RB name policy
	 *
	 * @return void
	 */
	public static function setEnforceNamingPolicy( $yesNo )
	{
		self::$enforceNamingPolicy = (boolean) $yesNo;
	}

	/**
	 * Checks whether the bean type conforms to the RedbeanPHP
	 * naming policy. This method will throw an exception if the
	 * type does not conform to the RedBeanPHP database column naming
	 * policy.
	 *
	 * The RedBeanPHP naming policy for beans states that valid
	 * bean type names contain only:
	 *
	 * - lowercase alphanumeric characters a-z
	 * - numbers 0-9
	 * - at least one character
	 *
	 * Although there are no restrictions on length, database
	 * specific implementations may apply further restrictions
	 * regarding the length of a table which means these restrictions
	 * also apply to bean types.
	 *
	 * The RedBeanPHP naming policy ensures that, without any
	 * configuration, the core functionalities work across many
	 * databases and operating systems, including those that are
	 * case insensitive or restricted to the ASCII character set.
	 *
	 * Although these restrictions can be bypassed, this is not
	 * recommended.
	 *
	 * @param string $type type of bean
	 *
	 * @return void
	 */
	public static function checkType( $type )
	{
		if ( !preg_match( '/^[a-z0-9]+$/', $type ) ) {
			throw new RedException( 'Invalid type: ' . $type );
		}
	}

	/**
	 * Dispenses a new RedBean OODB Bean for use with
	 * the rest of the methods. RedBeanPHP thinks in beans, the bean is the
	 * primary way to interact with RedBeanPHP and the database managed by
	 * RedBeanPHP. To load, store and delete data from the database using RedBeanPHP
	 * you exchange these RedBeanPHP OODB Beans. The only exception to this rule
	 * are the raw query methods like R::getCell() or R::exec() and so on.
	 * The dispense method is the 'preferred way' to create a new bean.
	 *
	 * Usage:
	 *
	 * <code>
	 * $book = R::dispense( 'book' );
	 * $book->title = 'My Book';
	 * R::store( $book );
	 * </code>
	 *
	 * This method can also be used to create an entire bean graph at once.
	 * Given an array with keys specifying the property names of the beans
	 * and a special _type key to indicate the type of bean, one can
	 * make the Dispense Helper generate an entire hierarchy of beans, including
	 * lists. To make dispense() generate a list, simply add a key like:
	 * ownXList or sharedXList where X is the type of beans it contains and
	 * a set its value to an array filled with arrays representing the beans.
	 * Note that, although the type may have been hinted at in the list name,
	 * you still have to specify a _type key for every bean array in the list.
	 * Note that, if you specify an array to generate a bean graph, the number
	 * parameter will be ignored.
	 *
	 * Usage:
	 *
	 * <code>
	 *  $book = R::dispense( [
     *   '_type' => 'book',
     *   'title'  => 'Gifted Programmers',
     *   'author' => [ '_type' => 'author', 'name' => 'Xavier' ],
     *   'ownPageList' => [ ['_type'=>'page', 'text' => '...'] ]
     * ] );
	 * </code>
	 *
	 * @param string|array $typeOrBeanArray   type or bean array to import
	 * @param integer      $num               number of beans to dispense
	 * @param boolean      $alwaysReturnArray if TRUE always returns the result as an array
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

		if (self::$enforceNamingPolicy) self::checkType( $type );

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
