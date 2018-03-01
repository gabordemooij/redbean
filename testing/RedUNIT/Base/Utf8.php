<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;
use RedBeanPHP\OODBBean as OODBBean;

/**
 * Utf8
 *
 * Tests whether we can store and retrive unicode characters.
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
	 * Tests whether we can process malformed strings in beans.
	 *
	 * @return void
	 */
	public function testMalformed()
	{
		$byte = pack( 'I', 129 );
		$bean = R::dispense( 'bean' );
		$bean->byte = $byte;
		OODBBean::setEnforceUTF8encoding( TRUE );
		$str = strval( $bean );
		OODBBean::setEnforceUTF8encoding( FALSE );
		pass();
	}

	/**
	 * Test UTF8 handling.
	 *
	 * @return void
	 */
	public function testUTF8()
	{
		//skip if < 5.3
		if (version_compare(PHP_VERSION, '5.4', '<')) return pass();
		$str = '𠜎ὃ𠻗𠻹𠻺𠼭𠼮𠽌𠾴𠾼𠿪𡁜';
		$bean      = R::dispense( 'bean' );
		$bean->bla = $str;

		R::store( $bean );
		$bean = R::load( 'bean', $bean->id );
		asrt( $bean->bla, $str );

		pass();
	}
}
