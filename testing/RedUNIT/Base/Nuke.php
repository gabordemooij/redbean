<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;

/**
 * Nuke
 *
 * Tests the nuke() command. The nuke command empties an entire database
 * and should also be capable of removing all foreign keys, constraints and
 * indexes.
 *
 * @file    RedUNIT/Base/Nuke.php
 * @desc    Test the nuke() function.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Nuke extends Base
{
	/**
	 * Test wipeAll().
	 *
	 * @return void
	 */
	public function testWipe()
	{
		R::nuke();
		$bean = R::dispense( 'bean' );
		asrt( count( R::inspect() ), 0 );
		R::store( $bean );
		asrt( count( R::inspect() ), 1 );
		asrt( R::count( 'bean' ), 1 );
		R::debug(1);
		R::wipeAll();
		asrt( count( R::inspect() ), 1 );
		asrt( R::count( 'bean' ), 0 );
		R::wipeAll( TRUE );
		asrt( count( R::inspect() ), 0 );
		asrt( R::count( 'bean' ), 0 );
	}

	/**
	 * Nuclear test suite.
	 *
	 * @return void
	 */
	public function testNuke()
	{
		$bean = R::dispense( 'bean' );
		R::store( $bean );
		asrt( count( R::getWriter()->getTables() ), 1 );
		R::nuke();
		asrt( count( R::getWriter()->getTables() ), 0 );
		$bean = R::dispense( 'bean' );
		R::store( $bean );
		asrt( count( R::getWriter()->getTables() ), 1 );
		R::freeze();
		R::nuke();
		// No effect
		asrt( count( R::getWriter()->getTables() ), 1 );
		R::freeze( FALSE );
	}

	/**
	 * Test noNuke().
	 *
	 * @return void
	 */
	public function testNoNuke() {
		$bean = R::dispense( 'bean' );
		R::store( $bean );
		asrt( count( R::getWriter()->getTables() ), 1 );
		R::noNuke( TRUE );
		try {
			R::nuke();
			fail();
		} catch( \Exception $e ) {
			pass();
		}
		asrt( count( R::getWriter()->getTables() ), 1 );
		R::noNuke( FALSE );
		R::nuke();
		asrt( count( R::getWriter()->getTables() ), 0 );
	}
}
