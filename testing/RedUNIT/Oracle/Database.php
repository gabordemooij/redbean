<?php
/**
 * RedUNIT_Oracle_Database
 *
 * @file    RedUNIT/Oracle/Database.php
 * @desc    Tests basic database behaviors
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Oracle_Database extends RedUNIT_Oracle
{
	/**
	 * Various tests for OCI.
	 * 
	 * @return void
	 */
	public function testVaria()
	{
		$toolbox = R::$toolbox;
		$adapter = $toolbox->getDatabaseAdapter();
		$writer  = $toolbox->getWriter();
		$redbean = $toolbox->getRedBean();
		$pdo     = $adapter->getDatabase();

		$page = $redbean->dispense( "page" );

		try {
			$adapter->exec( "an invalid query" );

			fail();
		} catch ( RedBean_Exception_SQL $e ) {
			pass();
		}

		asrt( (int) $adapter->getCell( "SELECT 123 FROM DUAL" ), 123 );

		$page->aname = "my page";

		$id = (int) $redbean->store( $page );

		asrt( (int) $page->id, 1 );
		asrt( (int) $pdo->GetCell( "SELECT count(*) FROM page" ), 1 );

		asrt( $pdo->GetCell( "SELECT aname FROM page WHERE ROWNUM<=1" ), "my page" );

		asrt( (int) $id, 1 );

		$page = $redbean->load( "page", 1 );

		asrt( $page->aname, "my page" );

		asrt( ( (bool) $page->getMeta( "type" ) ), TRUE );

		asrt( isset( $page->id ), TRUE );

		asrt( ( $page->getMeta( "type" ) ), "page" );

		asrt( (int) $page->id, $id );

		R::nuke();

		$rooms = R::dispense( 'room', 2 );

		$rooms[0]->kind = 'suite';
		$rooms[1]->kind = 'classic';

		$rooms[0]->number = 6;
		$rooms[1]->number = 7;

		R::store( $rooms[0] );
		R::store( $rooms[1] );

		$rooms = R::getAssoc( 'SELECT ' . R::$writer->esc( 'number' ) . ', kind FROM room ORDER BY kind ASC' );
		foreach ( $rooms as $key => $room ) {
			asrt( ( $key === 6 || $key === 7 ), TRUE );

			asrt( ( $room == 'classic' || $room == 'suite' ), TRUE );
		}

		$rooms = R::$adapter->getAssoc( 'SELECT kind FROM room' );
		foreach ( $rooms as $key => $room ) {
			asrt( ( $room == 'classic' || $room == 'suite' ), TRUE );

			asrt( $room, $key );
		}

		$rooms = R::getAssoc( 'SELECT ' . R::$writer->esc( 'number' ) . ', kind FROM rooms2 ORDER BY kind ASC' );

		asrt( count( $rooms ), 0 );

		asrt( is_array( $rooms ), TRUE );

		$date = R::dispense( 'mydate' );

		$date->date = '2012-12-12 20:50';
		$date->time = '12:15';

		$id = R::store( $date );

		$ok = R::load( 'mydate', 1 );
	}
}
