<?php
/**
 * RedUNIT_Blackhole_Import
 *
 * @file    RedUNIT/Blackhole/Import.php
 * @desc    Tests basic bean importing features.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */

class RedUNIT_Blackhole_Import extends RedUNIT_Blackhole
{
	/**
	 * Test import from and tainted.
	 * 
	 * @return void
	 */
	public function testImportFromAndTainted()
	{
		testpack( 'Test importFrom() and Tainted' );

		$bean = R::dispense( 'bean' );

		R::store( $bean );

		$bean->name = 'abc';

		asrt( $bean->getMeta( 'tainted' ), TRUE );

		R::store( $bean );

		asrt( $bean->getMeta( 'tainted' ), FALSE );

		$copy = R::dispense( 'bean' );

		R::store( $copy );

		$copy = R::load( 'bean', $copy->id );

		asrt( $copy->getMeta( 'tainted' ), FALSE );

		$copy->import( array( 'name' => 'xyz' ) );

		asrt( $copy->getMeta( 'tainted' ), TRUE );

		$copy->setMeta( 'tainted', FALSE );

		asrt( $copy->getMeta( 'tainted' ), FALSE );

		$copy->importFrom( $bean );

		asrt( $copy->getMeta( 'tainted' ), TRUE );

		testpack( 'Test basic import() feature.' );

		$bean = new RedBean_OODBBean;

		$bean->import( array( "a" => 1, "b" => 2 ) );

		asrt( $bean->a, 1 );
		asrt( $bean->b, 2 );

		$bean->import( array( "a" => 3, "b" => 4 ), "a,b" );

		asrt( $bean->a, 3 );
		asrt( $bean->b, 4 );

		$bean->import( array( "a" => 5, "b" => 6 ), " a , b " );

		asrt( $bean->a, 5 );
		asrt( $bean->b, 6 );

		$bean->import( array( "a" => 1, "b" => 2 ) );

		testpack( 'Test inject() feature.' );

		$coffee = R::dispense( 'coffee' );

		$coffee->id     = 2;
		$coffee->liquid = 'black';

		$cup = R::dispense( 'cup' );

		$cup->color = 'green';

		// Pour coffee in cup
		$cup->inject( $coffee );

		// Do we still have our own property?
		asrt( $cup->color, 'green' );

		// Did we pour the liquid in the cup?
		asrt( $cup->liquid, 'black' );

		// Id should not be transferred
		asrt( $cup->id, 0 );
	}
}
