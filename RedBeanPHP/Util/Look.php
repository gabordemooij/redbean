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
				$values[] = call_user_func_array( $filter, array( $row[$key] ) );
			}
			$lines[] = vsprintf( $template, $values );
		}
		$string = implode( $glue, $lines );
		return $string;
	}
}
