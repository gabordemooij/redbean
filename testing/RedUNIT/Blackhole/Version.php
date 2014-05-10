<?php

namespace RedUNIT\Blackhole;

use RedUNIT\Blackhole as Blackhole;
use RedBeanPHP\Facade as R;

/**
 * Version
 *
 * @file    RedUNIT/Blackhole/Version.php
 * @desc    Tests identification features.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Version extends Blackhole
{
	/**
	 * Test version info.
	 *
	 * @return void
	 */
	public function testVersion()
	{
		$version = R::getVersion();

		asrt( is_string( $version ), TRUE );
	}

	/**
	 * Test whether basic tools are available for use.
	 *
	 * @return void
	 */
	public function testTools()
	{
		asrt( class_exists( '\\RedBean_SimpleModel' ), TRUE );
		asrt( class_exists( '\\R' ), TRUE );
		asrt( function_exists( 'EID' ), TRUE );
	}
}
