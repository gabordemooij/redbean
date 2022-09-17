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

/**
 * Function pstr() generates [ $value, \PDO::PARAM_STR ]
 * Ensures that your parameter is being treated as a string.
 *
 * Usage:
 *
 * <code>
 * R::find('book', 'title = ?', [ pstr('1') ]);
 * </code>
 */
if ( !function_exists( 'pstr' ) ) {

	function pstr( $value )
	{
		return array( strval( $value ) , \PDO::PARAM_STR );
	}
}


/**
 * Function pint() generates [ $value, \PDO::PARAM_INT ]
 * Ensures that your parameter is being treated as an integer.
 *
 * Usage:
 *
 * <code>
 * R::find('book', ' pages > ? ', [ pint(2) ] );
 * </code>
 */
if ( !function_exists( 'pint' ) ) {

	function pint( $value )
	{
		return array( intval( $value ) , \PDO::PARAM_INT );
	}
}

/**
 * Function DBPrefix() is a simple function to allow you to
 * quickly set a different namespace for FUSE model resolution
 * per database connection. It works by creating a new DynamicBeanHelper
 * with the specified string as model prefix.
 *
 * Usage:
 *
 * <code>
 * R::addDatabase( ..., DBPrefix( 'Prefix1_' )  );
 * </code>
 */
if ( !function_exists( 'DBPrefix' ) ) {

	function DBPrefix( $prefix = '\\Model' ) {
		return new \RedBeanPHP\BeanHelper\DynamicBeanHelper( $prefix );
	}
}
