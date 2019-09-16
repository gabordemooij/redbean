<?php

namespace RedBeanPHP\Util;

use RedBeanPHP\OODB as OODB;
use RedBeanPHP\OODBBean as OODBBean;
use RedBeanPHP\ToolBox as ToolBox;

/**
 * Quick Export Utility
 *
 * The Quick Export Utility Class provides functionality to easily
 * expose the result of SQL queries as well-known formats like CSV.
 *
 * @file    RedBeanPHP/Util/QuickExporft.php
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class QuickExport
{
	/**
	 * @var Finder
	 */
	protected $toolbox;

	/**
	 * @boolean
	 */
	private static $test = FALSE;

	/**
	 * Constructor.
	 * The Quick Export requires a toolbox.
	 *
	 * @param ToolBox $toolbox
	 */
	public function __construct( ToolBox $toolbox )
	{
		$this->toolbox = $toolbox;
	}

	/**
	 * Makes csv() testable.
	 */
	public static function operation( $name, $arg1, $arg2 = TRUE ) {
		$out = '';
		switch( $name ) {
			case 'test':
				self::$test = (boolean) $arg1;
				break;
			case 'header':
				$out = ( self::$test ) ? $arg1 : header( $arg1, $arg2 );
				break;
			case 'readfile':
				$out = ( self::$test ) ? file_get_contents( $arg1 ) : readfile( $arg1 );
				break;
			case 'exit':
				$out = ( self::$test ) ? 'exit' : exit();
				break;
		}
		return $out;
	}

	/**
	 * Exposes the result of the specified SQL query as a CSV file.
	 *
	 * Usage:
	 *
	 * <code>
	 * R::csv( 'SELECT
	 *   `name`,
	 *   population
	 *   FROM city
	 *   WHERE region = :region ',
	 *   array( ':region' => 'Denmark' ),
	 *   array( 'city', 'population' ),
	 *   '/tmp/cities.csv'
	 * );
	 * </code>
	 *
	 * The command above will select all cities in Denmark
	 * and create a CSV with columns 'city' and 'population' and
	 * populate the cells under these column headers with the
	 * names of the cities and the population numbers respectively.
	 *
	 * @param string  $sql      SQL query to expose result of
	 * @param array   $bindings parameter bindings
	 * @param array   $columns  column headers for CSV file
	 * @param string  $path     path to save CSV file to
	 * @param boolean $output   TRUE to output CSV directly using readfile
	 * @param array   $options  delimiter, quote and escape character respectively
	 *
	 * @return void
	 */
	public function csv( $sql = '', $bindings = array(), $columns = NULL, $path = '/tmp/redexport_%s.csv', $output = TRUE, $options = array(',','"','\\') )
	{
		list( $delimiter, $enclosure, $escapeChar ) = $options;
		$path = sprintf( $path, date('Ymd_his') );
		$handle = fopen( $path, 'w' );
		if ($columns) if (PHP_VERSION_ID>=505040) fputcsv($handle, $columns, $delimiter, $enclosure, $escapeChar ); else fputcsv($handle, $columns, $delimiter, $enclosure );
		$cursor = $this->toolbox->getDatabaseAdapter()->getCursor( $sql, $bindings );
		while( $row = $cursor->getNextItem() ) {
			if (PHP_VERSION_ID>=505040) fputcsv($handle, $row, $delimiter, $enclosure, $escapeChar ); else fputcsv($handle, $row, $delimiter, $enclosure );
		}
		fclose($handle);
		if ( $output ) {
			$file = basename($path);
			$out = self::operation('header',"Pragma: public");
			$out .= self::operation('header',"Expires: 0");
			$out .= self::operation('header',"Cache-Control: must-revalidate, post-check=0, pre-check=0");
			$out .= self::operation('header',"Cache-Control: private", FALSE );
			$out .= self::operation('header',"Content-Type: text/csv");
			$out .= self::operation('header',"Content-Disposition: attachment; filename={$file}" );
			$out .= self::operation('header',"Content-Transfer-Encoding: binary");
			$out .= self::operation('readfile',$path );
			@unlink( $path );
			self::operation('exit', FALSE);
			return $out;
		}
	}
}
