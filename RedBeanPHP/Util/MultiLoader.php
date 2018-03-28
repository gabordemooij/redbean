<?php

namespace RedBeanPHP\Util;

use RedBeanPHP\OODB as OODB;

/**
 * Multi Bean Loader Helper
 *
 * This code was originally part of the facade, however it has
 * been decided to remove unique features to service classes like
 * this to make them available to developers not using the facade class.
 *
 * This helper class offers limited support for one-to-one
 * relations by providing a service to load a set of beans
 * with differnt types and a common ID.
 *
 * @file    RedBeanPHP/Util/MultiLoader.php
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class MultiLoader
{
	/**
	 * Loads multiple types of beans with the same ID.
	 * This might look like a strange method, however it can be useful
	 * for loading a one-to-one relation. In a typical 1-1 relation,
	 * you have two records sharing the same primary key.
	 * RedBeanPHP has only limited support for 1-1 relations.
	 * In general it is recommended to use 1-N for this.
	 *
	 * Usage:
	 *
	 * <code>
	 * list( $author, $bio ) = R::loadMulti( 'author, bio', $id );
	 * </code>
	 *
	 * @param OODB         $oodb  OODB object
	 * @param string|array $types the set of types to load at once
	 * @param mixed        $id    the common ID
	 *
	 * @return OODBBean
	 */
	public static function load( OODB $oodb, $types, $id )
	{
		if ( is_string( $types ) ) {
			$types = explode( ',', $types );
		}

		if ( !is_array( $types ) ) {
			return array();
		}

		foreach ( $types as $k => $typeItem ) {
			$types[$k] = $oodb->load( $typeItem, $id );
		}

		return $types;
	}
}
