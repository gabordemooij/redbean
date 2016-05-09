<?php

namespace RedUNIT\Blackhole;

use RedUNIT\Blackhole as Blackhole;
use RedBeanPHP\Facade as R;
use RedBeanPHP\Facade as Facade;
use RedBeanPHP\RedException as RedException;

/**
 * Plugins
 *
 * This test suite tests whether we can define dynamic
 * plugins using the ext() method on the facade.
 *
 * @file    RedUNIT/Blackhole/Plugins.php
 * @desc    Tests extending R facade dynamically.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Plugins extends Blackhole
{
	/**
	 * Test if we can dynamically extend the R-facade.
	 *
	 * @return void
	 */
	public function testDynamicPlugins()
	{
		testpack('Test dynamic plugins');

		//basic behaviour
		R::ext( 'makeTea', function() {
			return 'sorry cant do that!';
		});

		asrt( R::makeTea(), 'sorry cant do that!' );

		//with parameters
		R::ext( 'multiply', function( $a, $b ) {
			return $a * $b;
		});

		asrt( R::multiply( 3, 4 ), 12 );

		//can we call R inside?
		R::ext( 'singVersion', function() {
			return R::getVersion() . ' lalala !';
		} );

		asrt( R::singVersion(), ( R::getVersion().' lalala !' ) );

		//should also work with Facade
		asrt( Facade::singVersion(), ( R::getVersion().' lalala !' ) );

		//test error handling
		try {
			R::ext( '---', function() {} );
			fail();
		} catch ( RedException $e ) {
			asrt( $e->getMessage(), 'Plugin name may only contain alphanumeric characters.' );
		}

		try {
			R::__callStatic( '---', function() {} );
			fail();
		} catch ( RedException $e ) {
			asrt( $e->getMessage(), 'Plugin name may only contain alphanumeric characters.' );
		}

		try {
			R::invalidMethod();
			fail();
		} catch ( RedException $e ) {
			asrt( $e->getMessage(), 'Plugin \'invalidMethod\' does not exist, add this plugin using: R::ext(\'invalidMethod\')' );
		}
	}
}
