<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;
use RedBeanPHP\RedException as RedException;

/**
 * Issue303
 *
 * @file    RedUNIT/Base/Issue303.php
 * @desc    Issue #303 - Split bean property exception.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Issue303 extends Base
{
	/**
	 * Test whether we have two different exception messages for
	 * properties and values.
	 *
	 * @return void
	 */
	public function testIssue303()
	{
		testpack( 'Testing Issue #303 - Test splitting bean exception property/value.' );

		try {
			R::store( R::dispense( 'invalidbean' )->setAttr( 'invalid.property', 'value' ) );
			fail();
		} catch (RedException $e ) {
			asrt( $e->getMessage(), 'Invalid Bean property: property invalid.property' );
		}

		try {
			R::store( R::dispense( 'invalidbean' )->setAttr( 'property', array() ) );
			fail();
		} catch (RedException $e ) {
			asrt( $e->getMessage(), 'Invalid Bean value: property property' );
		}

		try {
			R::store( R::dispense( 'invalidbean' )->setAttr( 'property', new \stdClass ) );
			fail();
		} catch (RedException $e ) {
			asrt( $e->getMessage(), 'Invalid Bean value: property property' );
		}
	}
}
