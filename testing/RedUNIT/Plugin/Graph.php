<?php
/**
 * RedUNIT_Plugin_Graph
 *
 * @file    RedUNIT/Plugin/Graph.php
 * @desc    Tests converting arrays into persistable bean collections.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Plugin_Graph extends RedUNIT_Plugin
{
	/**
	 * Test graph() method.
	 * 
	 * @return void
	 */
	public function testGraph()
	{
		RedBean_Plugin_Cooker::enableBeanLoading( TRUE );

		R::dependencies( array() );

		$currentDriver = $this->currentlyActiveDriverID;

		global $lifeCycle;

		$toolbox = R::$toolbox;

		$adapter = $toolbox->getDatabaseAdapter();
		$writer  = $toolbox->getWriter();
		$redbean = $toolbox->getRedBean();

		try {
			R::graph( array( array( array( 'a' => 'b' ) ) ) );

			fail();
		} catch ( RedBean_Exception_Security $e ) {
			pass();
		}

		try {
			R::graph( 'ABC' );

			fail();
		} catch ( RedBean_Exception_Security $e ) {
			pass();
		}

		try {
			R::graph( 123 );

			fail();
		} catch ( RedBean_Exception_Security $e ) {
			pass();
		}

		try {
			R::graph( array( new stdClass ) );

			fail();
		} catch ( RedBean_Exception_Security $e ) {
			pass();
		}

		list( $v1, $v2, $v3 ) = R::dispense( 'village', 3 );

		list( $b1, $b2, $b3, $b4, $b5, $b6 ) = R::dispense( 'building', 6 );

		list( $f1, $f2, $f3, $f4, $f5, $f6 ) = R::dispense( 'farmer', 6 );

		list( $u1, $u2, $u3, $u4, $u5, $u6 ) = R::dispense( 'furniture', 6 );

		list( $a1, $a2 ) = R::dispense( 'army', 2 );

		$a1->strength = 100;
		$a2->strength = 200;

		$v1->name = 'v1';
		$v2->name = 'v2';
		$v3->name = 'v3';

		$v1->ownBuilding = array( $b4, $b6 );
		$v2->ownBuilding = array( $b1 );
		$v3->ownBuilding = array( $b5 );

		$b1->ownFarmer = array( $f1, $f2 );
		$b6->ownFarmer = array( $f3 );
		$b5->ownFarmer = array( $f4 );

		$b5->ownFurniture = array( $u6, $u5, $u4 );

		$v2->sharedArmy[] = $a2;
		$v3->sharedArmy   = array( $a2, $a1 );

		$i2 = R::store( $v2 );
		$i1 = R::store( $v1 );
		$i3 = R::store( $v3 );

		$v1 = R::load( 'village', $i1 );
		$v2 = R::load( 'village', $i2 );
		$v3 = R::load( 'village', $i3 );

		asrt( count( $v3->ownBuilding ), 1 );

		asrt( count( reset( $v3->ownBuilding )->ownFarmer ), 1 );
		asrt( count( reset( $v3->ownBuilding )->ownFurniture ), 3 );

		asrt( count( ( $v3->sharedArmy ) ), 2 );

		asrt( count( $v1->sharedArmy ), 0 );
		asrt( count( $v2->sharedArmy ), 1 );

		asrt( count( $v2->ownBuilding ), 1 );
		asrt( count( $v1->ownBuilding ), 2 );

		asrt( count( reset( $v1->ownBuilding )->ownFarmer ), 0 );

		asrt( count( end( $v1->ownBuilding )->ownFarmer ), 1 );

		asrt( count( $v3->ownTapestry ), 0 );

		// Change the names and add the same building should not change the graph
		$v1->name = 'village I';
		$v2->name = 'village II';
		$v3->name = 'village III';

		$v1->ownBuilding[] = $b4;

		$i2 = R::store( $v2 );
		$i1 = R::store( $v1 );
		$i3 = R::store( $v3 );

		$v1 = R::load( 'village', $i1 );
		$v2 = R::load( 'village', $i2 );
		$v3 = R::load( 'village', $i3 );

		asrt( count( $v3->ownBuilding ), 1 );

		asrt( count( reset( $v3->ownBuilding )->ownFarmer ), 1 );
		asrt( count( reset( $v3->ownBuilding )->ownFurniture ), 3 );

		asrt( count( ( $v3->sharedArmy ) ), 2 );

		asrt( count( $v1->sharedArmy ), 0 );
		asrt( count( $v2->sharedArmy ), 1 );

		asrt( count( $v2->ownBuilding ), 1 );
		asrt( count( $v1->ownBuilding ), 2 );

		asrt( count( reset( $v1->ownBuilding )->ownFarmer ), 0 );

		asrt( count( end( $v1->ownBuilding )->ownFarmer ), 1 );

		asrt( count( $v3->ownTapestry ), 0 );

		$b = reset( $v1->ownBuilding );

		R::trash( $b );

		$n = R::count( 'village' );

		asrt( $n, 3 );

		$n = R::count( 'army' );

		R::trash( $v1 );

		asrt( R::count( 'army' ), $n );

		R::trash( $v2 );

		asrt( R::count( 'army' ), $n );

		R::trash( $v3 );

		asrt( R::count( 'army' ), $n );

		$json = '{"mysongs": {
			"type": "playlist",
			"name": "JazzList",
			"ownTrack": [
				{
					"type": "track",
					"name": "harlem nocturne",
					"order": "1",
					"sharedSong": [
						{
							"type": "song",
							"url": "music.com.harlem"
						}
					],
					"cover": {
						"type": "cover",
						"url": "albumart.com\/duke1"
					}
				},
				{
					"type": "track",
					"name": "brazil",
					"order": "2",
					"sharedSong": [
						{
							"type": "song",
							"url": "music.com\/djan"
						}
					],
					"cover": {
						"type": "cover",
						"url": "picasa\/django"
					}
				}
			]
		}}';

		$playList = json_decode( $json, TRUE );

		$playList = R::graph( $playList );

		$id = R::store( reset( $playList ) );

		$play = R::load( "playlist", $id );

		asrt( count( $play->ownTrack ), 2 );

		foreach ( $play->ownTrack as $track ) {
			asrt( count( $track->sharedSong ), 1 );

			asrt( ( $track->cover instanceof RedBean_OODBBean ), TRUE );
		}

		$json = '{"mysongs": {
			"type": "playlist",
			"id": "1",
			"ownTrack": [
				{
					"type": "track",
					"name": "harlem nocturne",
					"order": "1",
					"sharedSong": [
						{
							"type": "song",
							"id": "1"
						}
					],
					"cover": {
						"type": "cover",
						"id": "2"
					}
				},
				{
					"type": "track",
					"name": "brazil",
					"order": "2",
					"sharedSong": [
						{
							"type": "song",
							"url": "music.com\/djan"
						}
					],
					"cover": {
						"type": "cover",
						"url": "picasa\/django"
					}
				}
			]
		}}';

		$playList = json_decode( $json, TRUE );

		$cooker = new RedBean_Plugin_Cooker;

		$cooker->setToolbox( R::$toolbox );

		$playList = ( $cooker->graph( ( $playList ) ) );

		$id = R::store( reset( $playList ) );

		$play = R::load( "playlist", $id );

		asrt( count( $play->ownTrack ), 2 );

		foreach ( $play->ownTrack as $track ) {
			asrt( count( $track->sharedSong ), 1 );

			asrt( ( $track->cover instanceof RedBean_OODBBean ), TRUE );
		}

		$track = reset( $play->ownTrack );

		$song = reset( $track->sharedSong );

		asrt( intval( $song->id ), 1 );

		asrt( $song->url, "music.com.harlem" );

		$json = '{"mysongs": {
			"type": "playlist",
			"id": "1",
			"ownTrack": [
				{
					"type": "track",
					"name": "harlem nocturne",
					"order": "1",
					"sharedSong": [
						{
							"type": "song",
							"id": "1",
							"url": "changedurl"
						}
					],
					"cover": {
						"type": "cover",
						"id": "2"
					}
				},
				{
					"type": "track",
					"name": "brazil",
					"order": "2",
					"sharedSong": [
						{
							"type": "song",
							"url": "music.com\/djan"
						}
					],
					"cover": {
						"type": "cover",
						"url": "picasa\/django"
					}
				}
			]
		}}';

		$playList = json_decode( $json, TRUE );

		$cooker = new RedBean_Plugin_Cooker;

		$cooker->setToolbox( R::$toolbox );

		$playList = ( $cooker->graph( ( $playList ) ) );

		$id = R::store( reset( $playList ) );

		$play = R::load( "playlist", $id );

		asrt( count( $play->ownTrack ), 2 );

		foreach ( $play->ownTrack as $track ) {
			asrt( count( $track->sharedSong ), 1 );

			asrt( ( $track->cover instanceof RedBean_OODBBean ), TRUE );
		}

		$track = reset( $play->ownTrack );

		$song = reset( $track->sharedSong );

		asrt( intval( $song->id ), 1 );

		asrt( ( $song->url ), "changedurl" );

		// Tree
		$page = R::dispense( 'page' );

		$page->name = 'root of all evil';

		list( $subPage, $subSubPage, $subNeighbour, $subOfSubNeighbour, $subSister ) = R::dispense( 'page', 5 );

		$subPage->name           = 'subPage';
		$subSubPage->name        = 'subSubPage';
		$subOfSubNeighbour->name = 'subOfSubNeighbour';
		$subNeighbour->name      = 'subNeighbour';
		$subSister->name         = 'subSister';

		$page->ownPage = array( $subPage, $subNeighbour, $subSister );

		R::store( $page );

		asrt( count( $page->ownPage ), 3 );

		foreach ( $page->ownPage as $p ) {
			if ( $p->name == 'subPage' ) {
				$p->ownPage[] = $subSubPage;
			}

			if ( $p->name == 'subNeighbour' ) {
				$p->ownPage[] = $subOfSubNeighbour;
			}
		}

		R::store( $page );

		asrt( count( $page->ownPage ), 3 );

		list( $first, $second ) = array_keys( $page->ownPage );

		foreach ( $page->ownPage as $p ) {
			if ( $p->name == 'subPage' || $p->name == 'subNeighbour' ) {
				asrt( count( $p->ownPage ), 1 );
			} else {
				asrt( count( $p->ownPage ), 0 );
			}
		}

		R::nuke();

		$canes = candy_canes();

		$id = R::store( $canes[0] );

		$cane = R::load( 'cane', $id );

		asrt( $cane->label, 'Cane No. 0' );
		asrt( $cane->cane->label, 'Cane No. 1' );
		asrt( $cane->cane->cane->label, 'Cane No. 4' );
		asrt( $cane->cane->cane->cane->label, 'Cane No. 7' );
		asrt( $cane->cane->cane->cane->cane, NULL );

		// Test backward compatibility
		asrt( $page->owner, NULL );

		RedBean_ModelHelper::setModelFormatter( NULL );

		$band      = R::dispense( 'band' );
		$musicians = R::dispense( 'bandmember', 5 );

		$band->ownBandmember = $musicians;

		try {
			R::store( $band );

			fail();
		} catch ( Exception $e ) {
			pass();
		}

		$band      = R::dispense( 'band' );
		$musicians = R::dispense( 'bandmember', 4 );

		$band->ownBandmember = $musicians;

		try {
			$id = R::store( $band );

			pass();
		} catch ( Exception $e ) {
			fail();
		}

		$band = R::load( 'band', $id );

		$band->ownBandmember[] = R::dispense( 'bandmember' );

		try {
			R::store( $band );

			fail();
		} catch ( Exception $e ) {
			pass();
		}

		// Test fuse
		$lifeCycle = "";

		$bandmember = R::dispense( 'bandmember' );

		$bandmember->name = 'Fatz Waller';

		$id = R::store( $bandmember );

		$bandmember = R::load( 'bandmember', $id );

		R::trash( $bandmember );

		$expected = 'calleddispenseid0calledupdateid0nameFatzWallercalledafter_updateid5nameFatzWallercalleddispenseid0calledopen5calleddeleteid5band_idnullnameFatzWallercalledafter_deleteid0band_idnullnameFatzWaller';

		$lifeCycle = preg_replace( "/\W/", "", $lifeCycle );

		asrt( $lifeCycle, $expected );

		// Test whether a nested bean will be saved if tainted
		R::nuke();

		$page = R::dispense( 'page' );

		$page->title = 'a blank page';

		$book = R::dispense( 'book' );

		$book->title = 'shiny white pages';

		$book->ownPage[] = $page;

		$id = R::store( $book );

		$book = R::load( 'book', $id );

		$page = reset( $book->ownPage );

		asrt( $page->title, 'a blank page' );

		$page->title = 'slightly different white';

		R::store( $book );

		$book = R::load( 'book', $id );

		$page = reset( $book->ownPage );

		asrt( $page->title, 'slightly different white' );

		$page = R::dispense( 'page' );

		$page->title = 'x';

		$book = R::load( 'book', $id );

		$book->title = 'snow white pages';

		$page->book = $book;

		$pid = R::store( $page );

		$page = R::load( 'page', $pid );

		asrt( $page->book->title, 'snow white pages' );

		// Test you cannot unset a relation list
		asrt( count( $book->ownPage ), 2 );

		unset( $book->ownPage );

		$book = R::load( 'book', R::store( $book ) );

		asrt( count( $book->ownPage ), 2 );

		$book->sharedTree = R::dispense( 'tree' );

		R::store( $book );

		$c = R::count( 'page' );

		asrt( R::count( 'tree' ), 1 );

		R::trash( $book );

		asrt( R::count( 'page' ), $c );
		asrt( R::count( 'tree' ), 1 );

		R::nuke();

		$v = R::dispense( 'village' );

		list( $b1, $b2 ) = R::dispense( 'building', 2 );

		$b1->name = 'a';
		$b2->name = 'b';

		$b2->village = $v;

		$v->ownBuilding[]   = $b1;
		$b1->ownFurniture[] = R::dispense( 'furniture' );

		$id = R::store( $b2 );

		$b2 = R::load( 'building', $id );

		asrt( count( $b2->village->ownBuilding ), 2 );

		$buildings = $b2->village->ownBuilding;

		foreach ( $buildings as $b ) {
			if ( $b->id != $id ) {
				asrt( count( $b->ownFurniture ), 1 );
			}
		}

		// Save a form using graph and ignore empty beans
		R::nuke();

		$product = R::dispense( 'product' );

		$product->name = 'shampoo';

		$productID = R::store( $product );

		$coupon = R::dispense( 'coupon' );

		$coupon->name = '567';

		$couponID = R::store( $coupon );

		$form = array(
			'type'         => 'order',
			'ownProduct'   => array(
				array( 'id' => $productID, 'type' => 'product' ),
			),
			'ownCustomer'  => array(
				array( 'type' => 'customer', 'name' => 'Bill' ),
				array( 'type' => 'customer', 'name' => '' ) // This one should be ignored
			),
			'sharedCoupon' => array(
				array( 'type' => 'coupon', 'name' => '123' ),
				array( 'type' => 'coupon', 'id' => $couponID )
			)
		);

		$order = R::graph( $form, TRUE );

		asrt( $order->getMeta( 'type' ), 'order' );

		asrt( count( $order->ownProduct ), 1 );
		asrt( count( $order->ownCustomer ), 1 );
		asrt( count( $order->sharedCoupon ), 2 );

		asrt( end( $order->ownProduct )->id, $productID );
		asrt( end( $order->ownProduct )->name, 'shampoo' );
		asrt( end( $order->ownCustomer )->name, 'Bill' );

		asrt( $order->sharedCoupon[$couponID]->name, '567' );

		R::nuke();

		$form = array(
			'type'  => 'person',
			'name'  => 'Fred',
			'phone' => ''
		);

		$bean = R::graph( $form );

		asrt( $bean->name, 'Fred' );
		asrt( $bean->phone, '' );

		$cooker = new RedBean_Plugin_Cooker;

		$cooker->setUseNullFlag( TRUE );

		$form = array(
			'type'  => 'person',
			'name'  => 'Fred',
			'phone' => ''
		);

		$bean = R::graph( $form );

		asrt( $bean->name, 'Fred' );
		asrt( $bean->phone, NULL );

		RedBean_Plugin_Cooker::setUseNullFlagSt( FALSE );

		// Save a form using graph and ignore empty beans, wrong nesting
		R::nuke();

		$product = R::dispense( 'product' );

		$product->name = 'shampoo';

		$productID = R::store( $product );

		$coupon = R::dispense( 'coupon' );

		$coupon->name = '567';

		$couponID = R::store( $coupon );

		$form = array(
			'type'       => 'order',
			'ownProduct' => array(
				array(
					array( 'id' => $productID, 'type' => 'product' )
				),
			),
		);

		try {
			$order = R::graph( $form, TRUE );

			fail();
		} catch ( RedBean_Exception_Security $e ) {
			pass();
		}

		// Without ignore empty beans
		R::nuke();

		$product = R::dispense( 'product' );

		$product->name = 'shampoo';

		$productID = R::store( $product );

		$coupon = R::dispense( 'coupon' );

		$coupon->name = '567';

		$couponID = R::store( $coupon );

		$form = array(
			'type'         => 'order',
			'ownProduct'   => array(
				array( 'id' => $productID, 'type' => 'product' ),
			),
			'ownCustomer'  => array(
				array( 'type' => 'customer', 'name' => 'Bill' ),
				array( 'type' => 'customer', 'name' => '' ) //this one should be ignored
			),
			'sharedCoupon' => array(
				array( 'type' => 'coupon', 'name' => '123' ),
				array( 'type' => 'coupon', 'id' => $couponID )
			)
		);

		RedBean_Plugin_Cooker::enableBeanLoading( FALSE );

		$exc = FALSE;
		try {
			$order = R::graph( $form );

			fail();
		} catch ( Exception $e ) {
			$exc = $e;
		}

		asrt( ( $exc instanceof RedBean_Exception_Security ), TRUE );

		RedBean_Plugin_Cooker::enableBeanLoading( TRUE );

		$order = R::graph( $form );

		asrt( $order->getMeta( 'type' ), 'order' );

		asrt( count( $order->ownProduct ), 1 );
		asrt( count( $order->ownCustomer ), 2 );
		asrt( count( $order->sharedCoupon ), 2 );

		asrt( end( $order->ownProduct )->id, $productID );

		// Make sure zeros are preserved
		$form = array( 'type' => 'laptop', 'price' => 0 );

		$product = R::graph( $form );

		asrt( isset( $product->price ), TRUE );

		asrt( $product->price, 0 );
	}
}
