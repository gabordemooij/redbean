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
	 * Constructor.
	 * The Quick Export requires a Finder.
	 *
	 * @param Finder $finder
	 */
	public function __construct( ToolBox $toolbox )
	{
		$this->toolbox = $toolbox;
	}

	/**
	 * Exposes the result of the specified SQL query as a CSV file.
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
	public function csv( $sql = '', $bindings = array(), $columns = NULL, $path = '/tmp/redexport_%s.csv', $output = true, $options = array(',','"','\\') )
	{
		list( $delimiter, $enclosure, $escapeChar ) = $options;
		$path = sprintf( $path, date('Ymd_his') );
		$handle = fopen( $path, 'w' );
		if ($columns) fputcsv($handle, $columns, $delimiter, $enclosure, $escapeChar );
		$cursor = $this->toolbox->getDatabaseAdapter()->getCursor( $sql, $bindings );
		while( $row = $cursor->getNextItem() ) {
			fputcsv($handle, $row, $delimiter, $enclosure, $escapeChar );
		}
		fclose($handle);
		if ( $output ) {
			$file = basename($path);
			header("Pragma: public");
			header("Expires: 0");
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Cache-Control: private",false);
			header("Content-Type: text/csv");
			header("Content-Disposition: attachment; filename={$file}" );
			header("Content-Transfer-Encoding: binary");
			readfile( $path );
			@unlink( $path );
			exit;
		}
	}
}
