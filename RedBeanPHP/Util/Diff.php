<?php

namespace RedBeanPHP\Util;

use RedBeanPHP\OODB as OODB;
use RedBeanPHP\OODBBean as OODBBean;
use RedBeanPHP\ToolBox as ToolBox;
use RedBeanPHP\Finder;

/**
 * Diff Utility
 *
 * The Look Utility class provides an easy way to generate
 * tables and selects (pulldowns) from the database.
 * 
 * @file    RedBeanPHP/Util/Diff.php
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Diff
{
	/**
	 * @var Toolbox
	 */
	protected $toolbox;

	/**
	 * Constructor.
	 * The MatchUp class requires a toolbox
	 *
	 * @param ToolBox $toolbox
	 */
	public function __construct( ToolBox $toolbox )
	{
		$this->toolbox = $toolbox;
	}

	/**
	 * Calculates a diff between two beans (or arrays of beans).
	 * The result of this method is an array describing the differences of the second bean compared to
	 * the first, where the first bean is taken as reference. The array is keyed by type/property, id and property name, where
	 * type/property is either the type (in case of the root bean) or the property of the parent bean where the type resides.
	 * The diffs are mainly intended for logging, you cannot apply these diffs as patches to other beans.
	 * However this functionality might be added in the future.
	 *
	 * The keys of the array can be formatted using the $format parameter.
	 * A key will be composed of a path (1st), id (2nd) and property (3rd).
	 * Using printf-style notation you can determine the exact format of the key.
	 * The default format will look like:
	 *
	 * 'book.1.title' => array( <OLDVALUE>, <NEWVALUE> )
	 *
	 * If you only want a simple diff of one bean and you don't care about ids,
	 * you might pass a format like: '%1$s.%3$s' which gives:
	 *
	 * 'book.1.title' => array( <OLDVALUE>, <NEWVALUE> )
	 *
	 * The filter parameter can be used to set filters, it should be an array
	 * of property names that have to be skipped. By default this array is filled with
	 * two strings: 'created' and 'modified'.
	 *
	 * @param OODBBean|array $beans   reference beans
	 * @param OODBBean|array $others  beans to compare
	 * @param array          $filters names of properties of all beans to skip
	 * @param string         $format  the format of the key, defaults to '%s.%s.%s'
	 * @param string         $type    type/property of bean to use for key generation
	 *
	 * @return array
	 */
	public function diff( $beans, $others, $filters = array( 'created', 'modified' ), $format = '%s.%s.%s', $type = NULL )
	{
		$diff = array();
		if ( !is_array( $beans ) ) $beans = array( $beans );
		if ( !is_array( $others ) ) $others = array( $others );
		foreach( $beans as $bean ) {
			if ( !is_object( $bean ) ) continue;
			if ( !( $bean instanceof OODBBean ) ) continue;
			if ( $type == NULL ) $type = $bean->getMeta( 'type' );
			foreach( $others as $other ) {
				if ( !is_object( $other ) ) continue;
				if ( !( $other instanceof OODBBean ) ) continue;
				if ( $other->id == $bean->id ) {
					foreach( $bean as $property => $value ) {
						if ( in_array( $property, $filters ) ) continue;
						$key = vsprintf( $format, array( $type, $bean->id, $property ) );
						$compare = $other->{$property};
						if ( !is_object( $value ) && !is_array( $value ) ) {
							if ( $value != $compare ) {
								$diff[$key] = array( $value, $compare );
							}
							continue;
						} else {
							$diff = array_merge( $diff, $this->diff( $value, $compare, $filters, $format, $key ) );
							continue;
						}
					}
				}
			}
		}
		return $diff;
	}
}
