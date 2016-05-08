<?php

namespace RedUNIT\Blackhole;

use RedUNIT\Blackhole as Blackhole;
use RedBeanPHP\Facade as R;
use RedBeanPHP\OODBBean as OODBBean;
use RedBeanPHP\SimpleModel as SimpleModel;

/**
 * Fusebox
 *
 * Tests whether we can convert a bean to a model and
 * a model to a bean. This process is called boxing and
 * unboxing.
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

class Fusebox extends Blackhole
{
	/**
	 * Test type hinting with boxed model
	 *
	 * @param Model_Soup $soup
	 */
	private function giveMeSoup( \Model_Soup $soup )
	{
		asrt( ( $soup instanceof \Model_Soup ), TRUE );
		asrt( 'A bit too salty', $soup->taste() );
		asrt( 'tomato', $soup->flavour );
	}

	/**
	 * Test unboxing
	 *
	 * @param OODBBean $bean
	 */
	private function giveMeBean( OODBBean $bean )
	{
		asrt( ( $bean instanceof OODBBean ), TRUE );
		asrt( 'A bit too salty', $bean->taste() );
		asrt( 'tomato', $bean->flavour );
	}

	/**
	 * Test boxing.
	 *
	 * @return void
	 */
	public function testBasicBox()
	{
		$soup = R::dispense( 'soup' );
		$soup->flavour = 'tomato';
		$this->giveMeSoup( $soup->box() );
		$this->giveMeBean( $soup->box()->unbox() );
		$this->giveMeBean( $soup );
	}
}
