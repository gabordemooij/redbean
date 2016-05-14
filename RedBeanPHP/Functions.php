<?php

/**
 * Support functions for RedBeanPHP.
 * Additional convenience shortcut functions for RedBeanPHP.
 *
 * @file    RedBeanPHP/Functions.php
 * @author  Gabor de Mooij and the RedBeanPHP community
 * @license BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */

/**
 * Convenience function for ENUM short syntax in queries.
 *
 * Usage:
 *
 * <code>
 * R::find( 'paint', ' color_id = ? ', [ EID('color:yellow') ] );
 * </code>
 *
 * If a function called EID() already exists you'll have to write this
 * wrapper yourself ;)
 *
 * @param string $enumName enum code as you would pass to R::enum()
 *
 * @return mixed
 */
if (!function_exists('EID')) {

	function EID($enumName)
	{
		return \RedBeanPHP\Facade::enum( $enumName )->id;
	}

}

/**
 * Prints the result of R::dump() to the screen using
 * print_r.
 *
 * @param mixed $data data to dump
 *
 * @return void
 */
if ( !function_exists( 'dmp' ) ) {

	function dmp( $list )
	{
		print_r( \RedBeanPHP\Facade::dump( $list ) );
	}
}

/**
 * Function alias for R::genSlots().
 */
if ( !function_exists( 'genslots' ) ) {

	function genslots( $slots, $tpl = NULL )
	{
		return \RedBeanPHP\Facade::genSlots( $slots, $tpl );
	}
}

/**
 * Function alias for R::flat().
 */
if ( !function_exists( 'array_flatten' ) ) {

	function array_flatten( $array )
	{
		return \RedBeanPHP\Facade::flat( $array );
	}
}
