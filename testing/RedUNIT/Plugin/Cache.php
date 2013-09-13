<?php
/**
 * RedUNIT_Plugin_Cache
 *
 * @file    RedUNIT/Plugin/Cache.php
 * @desc    Tests caching plugin.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Plugin_Cache extends RedUNIT_Plugin
{

	/**
	 * What drivers should be loaded for this test pack?
	 */
	public function getTargetDrivers()
	{
		return array( 'sqlite' );
	}

	/**
	 * Caching tests.
	 * 
	 * @return void
	 */
	public function testCache()
	{
		$t = R::$toolbox;

		$cachedOODB = new RedBean_Plugin_Cache( $t->getWriter() );

		$old = R::configureFacadeWithToolbox( new RedBean_Toolbox( $cachedOODB, $t->getDatabaseAdapter(), $t->getWriter() ) );

		function hm() { return R::$redbean->getHits() . '-' . R::$redbean->getMisses(); }

		R::nuke();

		$game    = R::dispense( 'game' );
		$teams   = R::dispense( 'team', 2 );
		$players = R::dispense( 'player', 10 );

		$game->sharedPlayer = $players;

		$teams[0]->ownPlayer = array( $players[0], $players[1] );
		$teams[1]->ownPlayer = array( $players[1], $players[2] );

		$game->ownTeam = $teams;

		$id = R::store( $game );

		asrt( $this->hm(), '0-0' );

		$game = R::load( 'game', $id );

		asrt( $this->hm(), '1-0' );

		$game->ownTeam;

		asrt( $this->hm(), '3-0' );

		R::load( 'team', $game->ownTeam[1]->id );

		asrt( $this->hm(), '4-0' );

		$game->sharedPlayer;

		asrt( $this->hm(), '14-0' );

		$players = R::find( 'player' );

		asrt( $this->hm(), '24-0' );

		$player = reset( $players );

		$player->name = 'aaa';

		$id = R::store( $player );

		R::load( 'player', $id );

		asrt( $player->name, 'aaa' );
		asrt( $this->hm(), '25-0' );

		R::trash( $player );

		$player = R::load( 'player', $id );

		asrt( strval( $player->name ), '' );

		$cachedOODB->flushAll();
		$cachedOODB->resetHits();
		$cachedOODB->resetMisses();

		$p = R::dispense( 'person' );

		$p->name = 'Tom';

		$id = R::store( $p );

		$p = R::load( 'person', $id );

		asrt( $cachedOODB->getHits(), 1 );
		asrt( $cachedOODB->getMisses(), 0 );

		asrt( $p->name, 'Tom' );

		$cachedOODB->resetHits();

		$p = R::load( 'person', $id );

		asrt( $cachedOODB->getHits(), 1 );
		asrt( $cachedOODB->getMisses(), 0 );

		asrt( $p->name, 'Tom' );

		$p = R::load( 'person', $id );

		asrt( $cachedOODB->getHits(), 2 );
		asrt( $cachedOODB->getMisses(), 0 );

		asrt( $p->name, 'Tom' );

		$cachedOODB->flushAll();

		$p = R::load( 'person', $id );

		asrt( $cachedOODB->getHits(), 2 );
		asrt( $cachedOODB->getMisses(), 1 );

		asrt( $p->name, 'Tom' );

		$pizzas = R::dispense( 'pizza', 4 );

		$pizzas[0]->name = 'Funghi';
		$pizzas[1]->name = 'Quattro Fromaggi';
		$pizzas[2]->name = 'Tonno';
		$pizzas[3]->name = 'Caprese';

		R::storeAll( $pizzas );

		$ids = array( $pizzas[0]->id, $pizzas[1]->id, $pizzas[2]->id, $pizzas[3]->id );

		$pizzas = R::findAll( 'pizza' );

		$cachedOODB->flushAll();
		$cachedOODB->resetHits();
		$cachedOODB->resetMisses();

		asrt( $cachedOODB->getHits(), 0 );
		asrt( $cachedOODB->getMisses(), 0 );

		$pizzas = R::batch( 'pizza', $ids );

		asrt( $cachedOODB->getHits(), 0 );
		asrt( $cachedOODB->getMisses(), 4 );

		$pizzas = R::batch( 'pizza', $ids );

		asrt( $cachedOODB->getHits(), 4 );
		asrt( $cachedOODB->getMisses(), 4 );

		$id = array_pop( $ids );

		$pizza = R::load( 'pizza', $id );

		asrt( $cachedOODB->getHits(), 5 );
		asrt( $cachedOODB->getMisses(), 4 );

		R::trash( $pizza ); //flushes cache

		$pizzas = R::batch( 'pizza', $ids );

		asrt( $cachedOODB->getHits(), 8 );
		asrt( $cachedOODB->getMisses(), 4 );

		$pizzas = R::batch( 'pizza', $ids );

		asrt( $cachedOODB->getHits(), 11 );
		asrt( $cachedOODB->getMisses(), 4 );

		$p = end( $pizzas );

		$p->price = 7.00;

		R::store( $p );

		$pizzas = R::batch( 'pizza', $ids );

		asrt( $cachedOODB->getHits(), 14 );
		asrt( $cachedOODB->getMisses(), 4 );

		$cachedOODB->flush( 'cookies' );

		$pizzas = R::batch( 'pizza', $ids );

		asrt( $cachedOODB->getHits(), 17 );
		asrt( $cachedOODB->getMisses(), 4 );

		$burritos = R::dispense( 'burrito', 4 );

		R::storeAll( $burritos );

		$cachedOODB->flush( 'pizza' );

		$pizzas = R::batch( 'pizza', $ids );

		asrt( $cachedOODB->getHits(), 17 );
		asrt( $cachedOODB->getMisses(), 7 );

		R::find( 'burrito' );

		asrt( $cachedOODB->getHits(), 21 );
		asrt( $cachedOODB->getMisses(), 7 );

		testpack( 'Does bean cache benefit from find()?' );

		$cachedOODB->flushAll();

		R::storeAll( R::dispense( 'pancake', 10 ) );

		$cachedOODB->flushAll();
		$cachedOODB->resetHits();
		$cachedOODB->resetMisses();

		$pancakes = R::findAll( 'pancake' );

		asrt( $cachedOODB->getMisses(), 10 );
		asrt( $cachedOODB->getHits(), 0 );

		$p = reset( $pancakes );

		R::load( 'pancake', $p->id );

		asrt( $cachedOODB->getHits(), 1 );

		testpack( 'Does bean cache benefit from batch()?' );

		$burgers = R::dispense( 'hamburger', 10 );

		$ids = array();

		foreach ( $burgers as $b ) {
			$ids[] = R::store( $b );
		}

		$cachedOODB->resetHits();
		$cachedOODB->resetMisses();

		$burgers = R::batch( 'hamburger', $ids );

		asrt( $cachedOODB->getMisses(), 0 );
		asrt( $cachedOODB->getHits(), 10 );

		$cachedOODB->flushAll();
		$cachedOODB->resetHits();
		$cachedOODB->resetMisses();

		$burgers = R::batch( 'hamburger', $ids );

		asrt( $cachedOODB->getMisses(), 10 );
		asrt( $cachedOODB->getHits(), 0 );

		$burgers = R::batch( 'hamburger', $ids );

		asrt( $cachedOODB->getMisses(), 10 );
		asrt( $cachedOODB->getHits(), 10 );

		$b = reset( $burgers );

		R::load( 'hamburger', $b->id );

		asrt( $cachedOODB->getHits(), 11 );

		testpack( 'Test 0-loading issue' );

		$bean = R::load( 'nocacheplease', 0 );

		$bean->name = 'test';

		$bean = R::load( 'nocacheplease', 0 );

		asrt( !isset( $bean->name ), TRUE );
		asrt( !( $bean->name == 'test' ), TRUE );

		R::configureFacadeWithToolbox( $old );
	}

	/**
	 * Returns the hits-misses string (X-Y) for asrt()s.
	 * Format: HITS-MISSES
	 *
	 * @return string
	 */
	private function hm()
	{
		return R::$redbean->getHits() . '-' . R::$redbean->getMisses();
	}
}
