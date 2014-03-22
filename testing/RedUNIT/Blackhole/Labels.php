<?php 

namespace RedUNIT\Blackhole;

use RedUNIT\Blackhole as Blackhole;
use RedBeanPHP\Facade as R;
use RedBeanPHP\OODBBean as OODBBean; 

/**
 * RedUNIT_Blackhole_Labels
 *
 * @file    RedUNIT/Blackhole/Labels.php
 * @desc    Tests Facade Label functions.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */

class Labels extends Blackhole
{
	/**
	 * Test basic labels.
	 * 
	 * @return void
	 */
	public function testLabels()
	{
		testpack( 'Test Labels' );

		$meals = R::dispenseLabels( 'meal', array( 'meat', 'fish', 'vegetarian' ) );

		asrt( is_array( $meals ), TRUE );

		asrt( count( $meals ), 3 );

		foreach ( $meals as $m ) {
			asrt( ( $m instanceof OODBBean ), TRUE );
		}

		$listOfMeals = implode( ',', R::gatherLabels( $meals ) );

		asrt( $listOfMeals, 'fish,meat,vegetarian' );
	}
}
