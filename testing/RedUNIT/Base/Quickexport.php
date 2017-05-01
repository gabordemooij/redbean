<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;
use RedBeanPHP\RedException as RedException;
use RedBeanPHP\OODBBean as OODBBean;

/**
 * QuickExport
 *
 * Tests the Quick Export functionality.
 * The Quick Export Utility Class provides functionality to easily
 * expose the result of SQL queries as well-known formats like CSV.
 * 
 * @file    RedUNIT/Base/Quickexport.php
 * @desc    Tests Quick Export
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Quickexport extends Base
{
	/**
	 * Test whether we can generate a CSV file from a query.
	 *
	 * @return void
	 */
	public function testCSV()
	{
		if ( phpversion() < 5.5 || strpos( strtolower( phpversion() ), 'hhvm' ) !== FALSE ) return;
		R::store( R::dispense( array( '_type'=>'bean', 'a' => 1, 'b' => 2, 'c' => 3 ) ) );
		$path = '/tmp/redbeantest.txt';
		R::csv( 'SELECT a,b,c FROM bean', array(), array( 'A', 'B', 'C' ), $path, FALSE );
		$csv = file_get_contents( $path );
		$expected = "A,B,C\n1,2,3";
		asrt( strpos($csv, $expected) !== FALSE, TRUE  );
	}
}
