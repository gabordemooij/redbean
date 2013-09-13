<?php
/**
 * RedUNIT_Blackhole_Fusebox
 *
 * @file    RedUNIT/Blackhole/Fusebox.php
 * @desc    Tests Boxing/Unboxing of beans.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */

class RedUNIT_Blackhole_Fusebox extends RedUNIT_Blackhole
{
	/**
	 * Test boxing.
	 * 
	 * @return void
	 */
	public function testBasicBox()
	{
		$soup          = R::dispense( 'soup' );

		$soup->flavour = 'tomato';

		$this->giveMeSoup( $soup->box() );

		$this->giveMeBean( $soup->box()->unbox() );

		$this->giveMeBean( $soup );
	}

	/**
	 * Test type hinting with boxed model
	 *
	 * @param Model_Soup $soup
	 */
	private function giveMeSoup( Model_Soup $soup )
	{
		asrt( ( $soup instanceof Model_Soup ), TRUE );

		asrt( 'A bit too salty', $soup->taste() );

		asrt( 'tomato', $soup->flavour );
	}

	/**
	 * Test unboxing
	 *
	 * @param RedBean_OODBBean $bean
	 */
	private function giveMeBean( RedBean_OODBBean $bean )
	{
		asrt( ( $bean instanceof RedBean_OODBBean ), TRUE );

		asrt( 'A bit too salty', $bean->taste() );

		asrt( 'tomato', $bean->flavour );
	}
}

/**
 * A model to box soup models :)
 */
class Model_Soup extends RedBean_SimpleModel
{

	public function taste()
	{
		return 'A bit too salty';
	}
}
