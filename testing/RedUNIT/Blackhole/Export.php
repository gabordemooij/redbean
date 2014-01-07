<?php 

use \ReadBean\OODBBean as OODBBean; 
/**
 * RedUNIT_Blackhole_Export
 *
 * @file    RedUNIT/Blackhole/Export.php
 * @desc    Tests basic bean exporting features.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Blackhole_Export extends RedUNIT_Blackhole
{
	/**
	 * ExportAll.
	 * 
	 * @return void
	 */
	public function testExportAll()
	{
		testpack( 'Test exportAll' );

		$redbean = R::$redbean;

		$bean = new OODBBean;

		$bean->import( array( "a" => 1, "b" => 2 ) );

		$bean->setMeta( "justametaproperty", "hellothere" );

		$arr = $bean->export();

		asrt( is_array( $arr ), TRUE );

		asrt( isset( $arr["a"] ), TRUE );
		asrt( isset( $arr["b"] ), TRUE );

		asrt( $arr["a"], 1 );
		asrt( $arr["b"], 2 );

		asrt( isset( $arr["__info"] ), FALSE );

		$arr = $bean->export( TRUE );

		asrt( isset( $arr["__info"] ), TRUE );

		asrt( $arr["a"], 1 );
		asrt( $arr["b"], 2 );

		$exportBean = $redbean->dispense( "abean" );

		$exportBean->setMeta( "metaitem.bla", 1 );

		$exportedBean = $exportBean->export( TRUE );

		asrt( $exportedBean["__info"]["metaitem.bla"], 1 );
		asrt( $exportedBean["__info"]["type"], "abean" );

		// Can we determine whether a bean is empty?
		testpack( 'test $bean->isEmpty() function' );

		$bean = R::dispense( 'bean' );

		asrt( $bean->isEmpty(), TRUE );
		asrt( ( count( $bean ) > 0 ), TRUE );

		$bean->property = 1;

		asrt( $bean->isEmpty(), FALSE );
		asrt( ( count( $bean ) > 0 ), TRUE );

		$bean->property = 0;

		asrt( $bean->isEmpty(), TRUE );
		asrt( ( count( $bean ) > 0 ), TRUE );

		$bean->property = FALSE;

		asrt( $bean->isEmpty(), TRUE );
		asrt( ( count( $bean ) > 0 ), TRUE );

		$bean->property = NULL;

		asrt( $bean->isEmpty(), TRUE );
		asrt( ( count( $bean ) > 0 ), TRUE );

		unset( $bean->property );

		asrt( $bean->isEmpty(), TRUE );
		asrt( ( count( $bean ) > 0 ), TRUE );

		// Export bug I found
		$object = R::graph( json_decode( '{"type":"bandmember","name":"Duke","ownInstrument":[{"type":"instrument","name":"Piano"}]}', TRUE ) );

		$a = R::exportAll( $object );

		pass();

		asrt( isset( $a[0] ), TRUE );
		asrt( (int) $a[0]['id'], 0 );

		asrt( $a[0]['name'], 'Duke' );

		asrt( $a[0]['ownInstrument'][0]['name'], 'Piano' );

		R::nuke();

		$v = R::dispense( 'village' );
		$b = R::dispense( 'building' );

		$v->name = 'a';
		$b->name = 'b';

		$v->ownBuilding[] = $b;

		$id = R::store( $v );

		$a = R::exportAll( $v );

		asrt( $a[0]['name'], 'a' );
		asrt( $a[0]['ownBuilding'][0]['name'], 'b' );

		$v = R::load( 'village', $id );

		$b2 = R::dispense( 'building' );

		$b2->name = 'c';

		$v->ownBuilding[] = $b2;

		$a = R::exportAll( $v );

		asrt( $a[0]['name'], 'a' );
		asrt( $a[0]['ownBuilding'][0]['name'], 'b' );

		asrt( count( $a[0]['ownBuilding'] ), 2 );

		list( $r1, $r2 ) = R::dispense( 'army', 2 );

		$r1->name = '1';
		$r2->name = '2';

		$v->sharedArmy[] = $r2;

		$a = R::exportAll( $v );

		asrt( count( $a[0]['sharedArmy'] ), 1 );

		R::store( $v );

		$v = R::load( 'village', $id );

		$a = R::exportAll( $v );

		asrt( count( $a[0]['sharedArmy'] ), 1 );

		asrt( $a[0]['name'], 'a' );
		asrt( $a[0]['ownBuilding'][0]['name'], 'b' );

		asrt( count( $a[0]['ownBuilding'] ), 2 );

		$v->sharedArmy[] = $r1;

		$a = R::exportAll( $v );

		asrt( count( $a[0]['sharedArmy'] ), 2 );

		$v = R::load( 'village', $id );

		$a = R::exportAll( $v );

		asrt( count( $a[0]['sharedArmy'] ), 1 );

		$v->sharedArmy[] = $r1;

		R::store( $v );

		$v = R::load( 'village', $id );

		$a = R::exportAll( $v );

		asrt( count( $a[0]['sharedArmy'] ), 2 );
	}
}
