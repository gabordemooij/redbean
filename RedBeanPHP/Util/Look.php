<?php

namespace RedBeanPHP\Util;

use RedBeanPHP\OODB as OODB;
use RedBeanPHP\OODBBean as OODBBean;
use RedBeanPHP\ToolBox as ToolBox;
use RedBeanPHP\Finder;

/**
 * Look Utility
 *
 * The Look Utility class provides an easy way to generate
 * tables and selects (pulldowns) from the database.
 * 
 * @file    RedBeanPHP/Util/Look.php
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Look
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
	 * Takes an full SQL query with optional bindings, a series of keys, a template
	 * and optionally a filter function and glue and assembles a view from all this.
	 * This is the fastest way from SQL to view. Typically this function is used to
	 * generate pulldown (select tag) menus with options queried from the database.
	 *
	 * Usage:
	 *
	 * <code>
	 * $htmlPulldown = R::look(
	 *   'SELECT * FROM color WHERE value != ? ORDER BY value ASC',
	 *   [ 'g' ],
	 *   [ 'value', 'name' ],
	 *   '<option value="%s">%s</option>',
	 *   'strtoupper',
	 *   "\n"
	 * );
	 *</code>
	 *
	 * The example above creates an HTML fragment like this:
	 *
	 * <option value="B">BLUE</option>
	 * <option value="R">RED</option>
	 *
	 * to pick a color from a palette. The HTML fragment gets constructed by
	 * an SQL query that selects all colors that do not have value 'g' - this
	 * excludes green. Next, the bean properties 'value' and 'name' are mapped to the
	 * HTML template string, note that the order here is important. The mapping and
	 * the HTML template string follow vsprintf-rules. All property values are then
	 * passed through the specified filter function 'strtoupper' which in this case
	 * is a native PHP function to convert strings to uppercase characters only.
	 * Finally the resulting HTML fragment strings are glued together using a
	 * newline character specified in the last parameter for readability.
	 *
	 * In previous versions of RedBeanPHP you had to use:
	 * R::getLook()->look() instead of R::look(). However to improve useability of the
	 * library the look() function can now directly be invoked from the facade.
	 *
	 * @param string   $sql      query to execute
	 * @param array    $bindings parameters to bind to slots mentioned in query or an empty array
	 * @param array    $keys     names in result collection to map to template
	 * @param string   $template HTML template to fill with values associated with keys, use printf notation (i.e. %s)
	 * @param callable $filter   function to pass values through (for translation for instance)
	 * @param string   $glue     optional glue to use when joining resulting strings
	 *
	 * @return string
	 */
	public function look( $sql, $bindings = array(), $keys = array( 'selected', 'id', 'name' ), $template = '<option %s value="%s">%s</option>', $filter = 'trim', $glue = '' )
	{
		$adapter = $this->toolbox->getDatabaseAdapter();
		$lines = array();
		$rows = $adapter->get( $sql, $bindings );
		foreach( $rows as $row ) {
			$values = array();
			foreach( $keys as $key ) {
				if (!empty($filter)) {
					$values[] = call_user_func_array( $filter, array( $row[$key] ) );
				} else {
					$values[] = $row[$key];
				}
			}
			$lines[] = vsprintf( $template, $values );
		}
		$string = implode( $glue, $lines );
		return $string;
	}
}
