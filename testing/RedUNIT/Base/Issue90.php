<?php 

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R; 

/**
 * RedUNIT_Base_Issue90
 *
 * @file    RedUNIT/Base/Issue90.php
 * @desc    Issue #90 - cannot trash bean with ownproperty if checked in model.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Issue90 extends Base
{
	/**
	 * Test for issue90.
	 * Checking 'own' relationship, makes it impossible to trash a bean.
	 *
	 * @return void
	 */
	public function testIssue90()
	{
		$s = R::dispense( 'box' );

		$s->name = 'a';

		$f = R::dispense( 'bottle' );

		$s->ownBottle[] = $f;

		R::store( $s );

		$s2 = R::dispense( 'box' );

		$s2->name = 'a';

		R::store( $s2 );

		R::trash( $s2 );

		pass();
	}
}
