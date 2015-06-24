<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;

/**
 * Large Number Limit Test (issue #386)
 *
 * @file    RedUNIT/Base/Largenum.php
 * @desc    Test whether we can use large numbers in LIMIT clause (PDO bindings).
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Largenum extends Base
{
	/**
	 * Test for issue #386.
	 * Can we use large numbers in LIMIT ?
	 *
	 * @return void
	 */
	public function testLargeNum()
	{
		if ( defined( 'HHVM_VERSION' ) ) return; //oops hhvm has incorrect binding for large nums.
		$number = R::dispense( 'number' );
		$number->name = 'big number';
		R::store( $number );
		//This should not cause an error... (some people use LIMIT 0, HUGE to simulate OFFSET on MYSQL).
		$beans = R::findAll( 'number', ' LIMIT ? ', array( PHP_INT_MAX ) );
		asrt( is_array( $beans ), TRUE );
		asrt( count( $beans ), 1 );
		pass();
	}
}
