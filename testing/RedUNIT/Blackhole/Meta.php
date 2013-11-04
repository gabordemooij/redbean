<?php
/**
 * RedUNIT_Blackhole_Meta
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
class RedUNIT_Blackhole_Meta extends RedUNIT_Blackhole
{
	/**
	 * Test meta data methods.
	 * 
	 * @return void
	 */
	public function testMetaData()
	{
		testpack( 'Test meta data' );

		$bean = new RedBean_OODBBean;

		$bean->setMeta( "this.is.a.custom.metaproperty", "yes" );

		$this->assertEquals( $bean->getMeta( "this.is.a.custom.metaproperty" ), "yes" );

		$this->assertEquals( $bean->getMeta( "nonexistant" ), NULL );
		$this->assertEquals( $bean->getMeta( "nonexistant", "abc" ), "abc" );

		$this->assertEquals( $bean->getMeta( "nonexistant.nested" ), NULL );
		$this->assertEquals( $bean->getMeta( "nonexistant,nested", "abc" ), "abc" );

		$bean->setMeta( "test.two", "second" );

		$this->assertEquals( $bean->getMeta( "test.two" ), "second" );

		$bean->setMeta( "another.little.property", "yes" );

		$this->assertEquals( $bean->getMeta( "another.little.property" ), "yes" );

		$this->assertEquals( $bean->getMeta( "test.two" ), "second" );

		// Copy Metadata
		$bean = new RedBean_OODBBean;

		$bean->setMeta( "meta.meta", "123" );

		$bean2 = new RedBean_OODBBean;

		$this->assertEquals( $bean2->getMeta( "meta.meta" ), NULL );

		$bean2->copyMetaFrom( $bean );

		$this->assertEquals( $bean2->getMeta( "meta.meta" ), "123" );
	}
}
