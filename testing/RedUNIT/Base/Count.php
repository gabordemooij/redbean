<?php
/**
 * RedUNIT_Base_Count
 *
 * @file    RedUNIT/Base/Count.php
 * @desc    Tests for simple bean counting.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Base_Count extends RedUNIT_Base
{
	/**
	 * Test count and wipe.
	 * 
	 * @return void
	 */
	public function testCountAndWipe()
	{
		testpack( "Test count and wipe" );

		$page = R::dispense( "page" );

		$page->name = "ABC";

		R::store( $page );

		$n1 = R::count( "page" );

		$page = R::dispense( "page" );

		$page->name = "DEF";

		R::store( $page );

		$n2 = R::count( "page" );

		asrt( $n1 + 1, $n2 );

		R::wipe( "page" );

		asrt( R::count( "page" ), 0 );
		asrt( R::$redbean->count( "page" ), 0 );
		asrt( R::$redbean->count( "kazoo" ), 0 ); // non existing table

		R::freeze( TRUE );

		asrt( R::$redbean->count( "kazoo" ), 0 ); // non existing table

		R::freeze( FALSE );

		$page = R::dispense( 'page' );

		$page->name = 'foo';

		R::store( $page );

		$page = R::dispense( 'page' );

		$page->name = 'bar';

		R::store( $page );

		asrt( R::count( 'page', ' name = ? ', array( 'foo' ) ), 1 );

		// Now count something that does not exist, this should return 0. (just be polite)
		asrt( R::count( 'teapot', ' name = ? ', array( 'flying' ) ), 0 );
		asrt( R::count( 'teapot' ), 0 );

		$currentDriver = $this->currentlyActiveDriverID;

		// Some drivers don't support that many error codes.
		if ( $currentDriver === 'mysql' || $currentDriver === 'postgres' ) {
			try {
				R::count( 'teaport', ' for tea ' );
				fail();
			} catch ( RedBean_Exception_SQL $e ) {
				pass();
			}
		}
	}
}
