<?php 

namespace RedUNIT\Blackhole;
use RedUNIT\Blackhole as Blackhole;
use RedBeanPHP\Facade as R;

/**
 * RedUNIT_Blackhole_Version
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
}
