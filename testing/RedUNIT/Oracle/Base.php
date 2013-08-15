<?php
/**
 * RedUNIT_Oracle_Base
 *
 * @file    RedUNIT/Oracle/Base.php
 * @desc    Basic tests for Oracle database.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */

class RedUNIT_Oracle_Base extends RedUNIT_Oracle
{
	/**
	 * Various.
	 * Various test for OCI. Some basic test cannot be performed because
	 * practical issues (configuration testing VM image etc..).
	 * 
	 * @return void
	 */
	public function testOCIVaria()
	{
		$village = R::dispense( 'village' );

		$village->name = 'Lutry';

		$id = R::store( $village );

		$village = R::load( 'village', $id );

		asrt( $village->name, 'Lutry' );

		list( $mill, $tavern ) = R::dispense( 'building', 2 );

		$village->ownBuilding = array( $mill, $tavern ); //replaces entire list

		$id = R::store( $village );

		asrt( $id, 1 );

		$village = R::load( 'village', $id );

		asrt( count( $village->ownBuilding ), 2 );

		$village2 = R::dispense( 'village' );

		$army = R::dispense( 'army' );

		$village->sharedArmy[]  = $army;
		$village2->sharedArmy[] = $army;

		R::store( $village );

		$id = R::store( $village2 );

		$village = R::load( 'village', $id );

		$army = $village->sharedArmy;

		$myVillages = R::related( $army, 'village' );

		asrt( count( $myVillages ), 2 );

		echo PHP_EOL;
	}
}
