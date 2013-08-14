<?php
/**
 * RedUNIT_Base_Chill
 *
 * @file    RedUNIT/Base/Chill.php
 * @desc    Tests chill list functionality, i.e. freezing a subset of all types.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Base_Chill extends RedUNIT_Base
{
	/**
	 * Test Chill mode.
	 * 
	 * @return void
	 */
	public function testChill()
	{
		$bean = R::dispense( 'bean' );

		$bean->col1 = '1';
		$bean->col2 = '2';

		R::store( $bean );

		asrt( count( R::$writer->getColumns( 'bean' ) ), 3 );

		$bean->col3 = '3';

		R::store( $bean );

		asrt( count( R::$writer->getColumns( 'bean' ) ), 4 );

		R::freeze( array( 'umbrella' ) );

		$bean->col4 = '4';

		R::store( $bean );

		asrt( count( R::$writer->getColumns( 'bean' ) ), 5 );

		R::freeze( array( 'bean' ) );

		$bean->col5 = '5';

		try {
			R::store( $bean );
			fail();
		} catch ( Exception $e ) {
			pass();
		}

		asrt( count( R::$writer->getColumns( 'bean' ) ), 5 );

		R::freeze( array() );

		$bean->col5 = '5';

		R::store( $bean );

		asrt( count( R::$writer->getColumns( 'bean' ) ), 6 );
	}
}
