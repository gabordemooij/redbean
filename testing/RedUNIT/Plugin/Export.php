<?php
/**
 * RedUNIT_Plugin_Export
 *
 * @file    RedUNIT/Plugin/Export.php
 * @desc    Tests export functions for beans.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Plugin_Export extends RedUNIT_Plugin
{
	
	/**
	 * Test export.
	 * 
	 * @return void
	 */
	public function testExport()
	{
		// Export with parents / embedded objects
		$wines = R::dispense( 'wine', 3 );

		$wines[0]->name = 'Cabernet Franc';
		$wines[1]->name = 'Chardonnay';
		$wines[2]->name = 'Malbec';

		$shelves = R::dispense( 'shelf', 2 );

		$shelves[0]->number = 1;
		$shelves[1]->number = 2;

		$cellar = R::dispense( 'cellar' );

		$cellar->name     = 'My Cellar';
		$cellar->ownShelf = $shelves;

		$shelves[0]->ownWine   = array( $wines[0], $wines[1] );
		$shelves[1]->ownWine[] = $wines[2];

		$id = R::store( $cellar );

		$wine = R::load( 'wine', $wines[1]->id );

		$list1 = R::exportAll( array( $wine, $shelves[1] ) );
		$list2 = R::exportAll( array( $wine, $shelves[1] ), TRUE );

		asrt( $list1[0]['name'], 'Chardonnay' );

		asrt( isset( $list1[0]['shelf'] ), FALSE );
		asrt( isset( $list1[0]['shelf_id'] ), TRUE );
		asrt( isset( $list1[0]['shelf']['cellar'] ), FALSE );

		asrt( $list2[0]['name'], 'Chardonnay' );

		asrt( isset( $list2[0]['shelf'] ), TRUE );

		asrt( intval( $list2[0]['shelf']['number'] ), 1 );

		asrt( isset( $list2[0]['shelf']['ownWine'] ), FALSE );
		asrt( isset( $list2[0]['shelf']['cellar'] ), TRUE );
		asrt( isset( $list2[0]['shelf']['cellar']['name'] ), TRUE );
		asrt( isset( $list2[0]['shelf_id'] ), TRUE );

		asrt( intval( $list1[1]['number'] ), 2 );

		asrt( isset( $list1[1]['ownWine'] ), TRUE );
		asrt( isset( $list1[1]['cellar'] ), FALSE );
		asrt( isset( $list1[1]['cellar']['name'] ), FALSE );

		asrt( intval( $list2[1]['number'] ), 2 );

		asrt( isset( $list2[1]['ownWine'] ), TRUE );
		asrt( isset( $list2[1]['cellar'] ), TRUE );
		asrt( isset( $list2[1]['cellar']['name'] ), TRUE );

		R::nuke();

		$sheep = R::dispense( 'sheep' );

		$sheep->aname = 'Shawn';

		R::store( $sheep );

		$sheep = ( R::findAndExport( 'sheep', ' aname = ? ', array( 'Shawn' ) ) );

		asrt( count( $sheep ), 1 );

		$sheep = array_shift( $sheep );

		asrt( count( $sheep ), 2 );

		asrt( $sheep['aname'], 'Shawn' );

		R::nuke();

		testpack( 'Extended export algorithm, feature issue 105' );

		$city   = R::dispense( 'city' );
		$people = R::dispense( 'person', 10 );

		$me = reset( $people );

		$him = end( $people );

		$city->sharedPeople = $people;

		$me->name = 'me';

		$suitcase = R::dispense( 'suitcase' );

		$him->suitcase = $suitcase;

		$him->ownShoe = R::dispense( 'shoe', 2 );

		R::store( $city );

		$id = $him->getID();

		$data = R::exportAll( $city );

		$data = reset( $data );

		asrt( isset( $data['sharedPerson'] ), TRUE );
		asrt( count( $data['sharedPerson'] ), 10 );

		$last = end( $data['sharedPerson'] );

		asrt( ( $last['suitcase_id'] > 0 ), TRUE );

		$data = R::exportAll( $him );

		$data = reset( $data );

		asrt( isset( $data['ownShoe'] ), TRUE );

		asrt( count( $data['ownShoe'] ), 2 );
		asrt( count( $data['sharedCity'] ), 1 );
	}
}
