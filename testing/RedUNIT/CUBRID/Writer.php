<?php
/**
 * RedUNIT_CUBRID_Writer
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
class RedUNIT_CUBRID_Writer extends RedUNIT_CUBRID
{
	/**
	 * Test scanning and coding of values.
	 * 
	 * @return void
	 */
	public function testScanningAndCoding()
	{
		$toolbox = R::$toolbox;
		$adapter = $toolbox->getDatabaseAdapter();
		$writer  = $toolbox->getWriter();
		$redbean = $toolbox->getRedBean();
		$pdo     = $adapter->getDatabase();
		
		$writer->createTable( "testtable" );

		$writer->addColumn( "testtable", "special", RedBean_QueryWriter_CUBRID::C_DATATYPE_SPECIAL_DATE );
		
		$cols = $writer->getColumns( "testtable" );

		asrt( $writer->code( $cols['special'], TRUE ), RedBean_QueryWriter_CUBRID::C_DATATYPE_SPECIAL_DATE );
		
		asrt( $writer->code( $cols['special'], FALSE ), RedBean_QueryWriter_CUBRID::C_DATATYPE_SPECIFIED );
		
		$writer->addColumn( "testtable", "special2", RedBean_QueryWriter_CUBRID::C_DATATYPE_SPECIAL_DATETIME );
		
		$cols = $writer->getColumns( "testtable" );

		asrt( $writer->code( $cols['special2'], TRUE ), RedBean_QueryWriter_CUBRID::C_DATATYPE_SPECIAL_DATETIME );
		
		asrt( $writer->code( $cols['special'], FALSE ), RedBean_QueryWriter_CUBRID::C_DATATYPE_SPECIFIED );
		
		
	}
	
}
