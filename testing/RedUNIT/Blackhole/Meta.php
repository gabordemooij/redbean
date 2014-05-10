<?php

namespace RedUNIT\Blackhole;

use RedUNIT\Blackhole as Blackhole;
use RedBeanPHP\Facade as R;
use RedBeanPHP\OODBBean as OODBBean;

/**
 * Meta
 *
 * @file    RedUNIT/Blackhole/Meta.php
 * @desc    Tests meta data features on OODBBean class.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Meta extends Blackhole
{
	/**
	 * Test meta data methods.
	 *
	 * @return void
	 */
	public function testMetaData()
	{
		testpack( 'Test meta data' );

		$bean = new OODBBean;

		$bean->setMeta( "this.is.a.custom.metaproperty", "yes" );

		asrt( $bean->getMeta( "this.is.a.custom.metaproperty" ), "yes" );

		asrt( $bean->getMeta( "nonexistant" ), NULL );
		asrt( $bean->getMeta( "nonexistant", "abc" ), "abc" );

		asrt( $bean->getMeta( "nonexistant.nested" ), NULL );
		asrt( $bean->getMeta( "nonexistant,nested", "abc" ), "abc" );

		$bean->setMeta( "test.two", "second" );

		asrt( $bean->getMeta( "test.two" ), "second" );

		$bean->setMeta( "another.little.property", "yes" );

		asrt( $bean->getMeta( "another.little.property" ), "yes" );

		asrt( $bean->getMeta( "test.two" ), "second" );

		// Copy Metadata
		$bean = new OODBBean;

		$bean->setMeta( "meta.meta", "123" );

		$bean2 = new OODBBean;

		asrt( $bean2->getMeta( "meta.meta" ), NULL );

		$bean2->copyMetaFrom( $bean );

		asrt( $bean2->getMeta( "meta.meta" ), "123" );
	}

	/**
	 * Meta properties should not be saved.
	 *
	 * @return void
	 */
	public function testMetaPersist()
	{
		$bean = R::dispense( 'bean' );
		$bean->property = 'test';
		$bean->setMeta( 'meta', 'hello' );
		R::store( $bean );
		asrt( $bean->getMeta( 'meta' ), 'hello' );
		$bean = $bean->fresh();
		asrt( $bean->getMeta( 'meta' ), NULL );
	}

	/**
	 * You cant access meta data using the array accessors.
	 *
	 * @return void
	 */
	public function testNoArrayMetaAccess()
	{
		$bean = R::dispense( 'bean' );
		$bean->setMeta( 'greet', 'hello' );
		asrt( isset( $bean['greet'] ), FALSE );
		asrt( isset( $bean['__info']['greet'] ), FALSE );
		asrt( isset( $bean['__info'] ), FALSE );
		asrt( isset( $bean['meta'] ), FALSE );
		asrt( count( $bean ), 1 );
	}
}
