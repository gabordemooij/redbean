<?php
namespace RedUNIT\CUBRID;
use RedBeanPHP\Facade as R;
use \RedBeanPHP\QueryWriter\CUBRID as CUBRID;

/**
 * Writer
 *
 * Tests for CUBRID Query Writer.
 * This test class contains Query Writer specific tests.
 * Use this class to add tests to test Query Writer specific
 * behaviours, quirks and issues.
 *
 * @file    RedUNIT/CUBRID/Writer.php
 * @desc    A collection of database specific writer functions.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Writer extends \RedUNIT\CUBRID
{
	/**
	 * Test scanning and coding of values.
	 *
	 * @return void
	 */
	public function testScanningAndCoding()
	{
		$toolbox = R::getToolBox();
		$adapter = $toolbox->getDatabaseAdapter();
		$writer  = $toolbox->getWriter();
		$redbean = $toolbox->getRedBean();
		$pdo     = $adapter->getDatabase();

		$writer->createTable( "testtable" );

		$writer->addColumn( "testtable", "special", CUBRID::C_DATATYPE_SPECIAL_DATE );

		$cols = $writer->getColumns( "testtable" );

		asrt( $writer->code( $cols['special'], TRUE ), CUBRID::C_DATATYPE_SPECIAL_DATE );

		asrt( $writer->code( $cols['special'], FALSE ), CUBRID::C_DATATYPE_SPECIFIED );

		$writer->addColumn( "testtable", "special2", CUBRID::C_DATATYPE_SPECIAL_DATETIME );

		$cols = $writer->getColumns( "testtable" );

		asrt( $writer->code( $cols['special2'], TRUE ), CUBRID::C_DATATYPE_SPECIAL_DATETIME );

		asrt( $writer->code( $cols['special'], FALSE ), CUBRID::C_DATATYPE_SPECIFIED );

	}

}
