<?php 

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;

/**
 * RedUNIT_Base_Utf8
 *
 * @file    RedUNIT/Base/UTF8.php
 * @desc    Tests handling of NULL values.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Utf8 extends Base
{
	/**
	 * Test UTF8 handling.
	 * 
	 * @return void
	 */
	public function testUTF8()
	{
		$str = '𠜎ὃ𠻗𠻹𠻺𠼭𠼮𠽌𠾴𠾼𠿪𡁜';
		$bean      = R::dispense( 'bean' );
		$bean->bla = $str;

		R::store( $bean );
		$bean = R::load( 'bean', $bean->id );
		asrt( $bean->bla, $str );
		
		pass();
	}
}
