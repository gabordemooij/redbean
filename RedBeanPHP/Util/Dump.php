<?php

namespace RedBeanPHP\Util;

use RedBeanPHP\OODB as OODB;
use RedBeanPHP\OODBBean as OODBBean;

/**
 * Dump helper
 *
 * This code was originally part of the facade, however it has
 * been decided to remove unique features to service classes like
 * this to make them available to developers not using the facade class.
 *
 * Dumps the contents of a bean in an array for
 * debugging purposes.
 *
 * @file    RedBeanPHP/Util/Dump.php
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Dump
{
	/**
	 * Dumps bean data to array.
	 * Given a one or more beans this method will
	 * return an array containing first part of the string
	 * representation of each item in the array.
	 *
	 * Usage:
	 *
	 * <code>
	 * echo R::dump( $bean );
	 * </code>
	 *
	 * The example shows how to echo the result of a simple
	 * dump. This will print the string representation of the
	 * specified bean to the screen, limiting the output per bean
	 * to 35 characters to improve readability. Nested beans will
	 * also be dumped.
	 *
	 * @param OODBBean|array $data either a bean or an array of beans
	 *
	 * @return array
	 */
	public static function dump( $data )
	{
		$array = array();
		if ( $data instanceof OODBBean ) {
			$str = strval( $data );
			if (strlen($str) > 35) {
				$beanStr = substr( $str, 0, 35 ).'... ';
			} else {
				$beanStr = $str;
			}
			return $beanStr;
		}
		if ( is_array( $data ) ) {
			foreach( $data as $key => $item ) {
				$array[$key] = self::dump( $item );
			}
		}
		return $array;
	}
}
