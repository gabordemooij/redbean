<?php

namespace RedUNIT\Postgres;

use RedUNIT\Postgres as Postgres;
use RedBeanPHP\Facade as R;
use RedBeanPHP\BeanHelper\SimpleFacadeBeanHelper as SimpleFacadeBeanHelper;
use RedBeanPHP\OODB as OODB;
use RedBeanPHP\OODBBean as OODBBean;
use RedBeanPHP\ToolBox as ToolBox;

/**
 * UUID
 *
 * Tests whether we can use UUIDs with PostgreSQL, to this
 * end we use a reference implementation of a UUID MySQL Writer:
 * UUIDWriterPostgres, however this class is not part of the code base,
 * it should be considered a reference or example implementation.
 * These tests focus on whether UUIDs in general do not cause any
 * unexpected issues.
 *
 * @file    RedUNIT/Postgres/Uuid.php
 * @desc    Tests read support for UUID tables.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Uuid extends Postgres
{
	/**
	 * Test Read-support.
	 *
	 * @return void
	 */
	public function testUUIDReadSupport()
	{

		R::nuke();

		$createPageTableSQL = '
			CREATE TABLE
			page
			(
				id UUID PRIMARY KEY,
				book_id UUID,
				magazine_id UUID,
				title VARCHAR(255)
			)';

		$createBookTableSQL = '
			CREATE TABLE
			book
			(
				id UUID PRIMARY KEY,
				title VARCHAR(255)
			)';

		$createPagePageTableSQL = '
			CREATE TABLE
			page_page
			(
				id UUID PRIMARY KEY,
				page_id UUID,
				page2_id UUID
			)';

		R::exec( $createBookTableSQL );
		R::exec( $createPageTableSQL );
		R::exec( $createPagePageTableSQL );

		//insert some records

		$book1ID     = '6ccd780c-baba-1026-9564-0040f4311e21';
		$book2ID     = '6ccd780c-baba-1026-9564-0040f4311e22';
		$page1ID     = '6ccd780c-baba-1026-9564-0040f4311e23';
		$page2ID     = '6ccd780c-baba-1026-9564-0040f4311e24';
		$page3ID     = '6ccd780c-baba-1026-9564-0040f4311e25';
		$pagePage1ID = '6ccd780c-baba-1026-9564-0040f4311e26';

		$insertBook1SQL = "
			INSERT INTO book (id, title) VALUES( '$book1ID', 'book 1' );
		";

		$insertBook2SQL = "
			INSERT INTO book (id, title) VALUES( '$book2ID', 'book 2' );
		";

		$insertPage1SQL = "
			INSERT INTO page (id, book_id, title, magazine_id) VALUES( '$page1ID', '$book1ID', 'page 1 of book 1', '$book2ID' );
		";

		$insertPage2SQL = "
			INSERT INTO page (id, book_id, title) VALUES( '$page2ID', '$book1ID', 'page 2 of book 1' );
		";

		$insertPage3SQL = "
			INSERT INTO page (id, book_id, title) VALUES( '$page3ID', '$book2ID', 'page 1 of book 2' );
		";

		$insertPagePage1SQL = "
			INSERT INTO page_page (id, page_id, page2_id) VALUES( '$pagePage1ID', '$page2ID', '$page3ID' );
		";

		R::exec( $insertBook1SQL );
		R::exec( $insertBook2SQL );
		R::exec( $insertPage1SQL );
		R::exec( $insertPage2SQL );
		R::exec( $insertPage3SQL );
		R::exec( $insertPagePage1SQL );

		//basic tour of basic functions....

		$book1 = R::load( 'book', $book1ID );

		asrt( $book1->id, $book1ID );
		asrt( $book1->title, 'book 1' );

		$book2 = R::load( 'book', $book2ID );

		asrt( $book2->id, $book2ID );
		asrt( $book2->title, 'book 2' );

		asrt( count( $book1->ownPage ), 2 );
		asrt( count( $book1->fresh()->with( 'LIMIT 1' )->ownPage ), 1 );
		asrt( count( $book1->fresh()->withCondition( ' title = ? ', array('page 2 of book 1'))->ownPage ), 1 );

		asrt( count($book2->ownPage), 1 );
		asrt( $book2->fresh()->countOwn( 'page' ), 1 );

		$page1 = R::load( 'page', $page1ID );
		asrt( count( $page1->sharedPage ), 0 );
		asrt( $page1->fetchAs( 'book' )->magazine->id, $book2ID );

		$page2 = R::load( 'page', $page2ID );
		asrt( count($page2->sharedPage), 1 );
		asrt( $page2->fresh()->countShared( 'page' ), 1 );

		$page3 = R::findOne( 'page', ' title = ? ', array( 'page 1 of book 2' ) );
		asrt( $page3->id, $page3ID );
		asrt( $page3->book->id, $book2ID );
	}

	/**
	 * Test Full fluid UUID support.
	 *
	 */
	public function testFullSupport()
	{

		//Rewire objects to support UUIDs.
		$oldToolBox = R::getToolBox();
		$oldAdapter = $oldToolBox->getDatabaseAdapter();
		$uuidWriter = new \UUIDWriterPostgres( $oldAdapter );
		$newRedBean = new OODB( $uuidWriter );
		$newToolBox = new ToolBox( $newRedBean, $oldAdapter, $uuidWriter );
		R::configureFacadeWithToolbox( $newToolBox );

		list( $mansion, $rooms, $ghosts, $key ) = R::dispenseAll( 'mansion,room*3,ghost*4,key' );
		$mansion->name = 'Haunted Mansion';
		$mansion->xownRoomList = $rooms;
		$rooms[0]->name = 'Green Room';
		$rooms[1]->name = 'Red Room';
		$rooms[2]->name = 'Blue Room';
		$ghosts[0]->name = 'zero';
		$ghosts[1]->name = 'one';
		$ghosts[2]->name = 'two';
		$ghosts[3]->name = 'three';
		$rooms[0]->noLoad()->sharedGhostList = array( $ghosts[0], $ghosts[1] );
		$rooms[1]->noLoad()->sharedGhostList = array( $ghosts[0], $ghosts[2] );
		$rooms[2]->noLoad()->sharedGhostList = array( $ghosts[1], $ghosts[3], $ghosts[2] );
		$rooms[2]->xownKey = array( $key );
		//Can we store a bean hierachy with UUIDs?

		R::debug(1);
		$id = R::store( $mansion );
		//exit;

		asrt( is_string( $id ), TRUE );
		asrt( strlen( $id ), 36 );
		$haunted = R::load( 'mansion', $id );
		asrt( $haunted->name, 'Haunted Mansion' );
		asrt( is_string( $haunted->id ), TRUE );
		asrt( strlen( $haunted->id ), 36 );
		asrt( is_array( $haunted->xownRoomList ), TRUE );
		asrt( count( $haunted->ownRoom ), 3 );
		$rooms = $haunted->xownRoomList;

		//Do some counting...
		$greenRoom = NULL;
		foreach( $rooms as $room ) {
			if ( $room->name === 'Green Room' ) {
				$greenRoom = $room;
				break;
			}
		}
		asrt( !is_null( $greenRoom ), TRUE );
		asrt( is_array( $greenRoom->with(' ORDER BY id ')->sharedGhostList ), TRUE );
		asrt( count( $greenRoom->sharedGhostList ), 2 );
		$names = array();
		foreach( $greenRoom->sharedGhost as $ghost ) $names[] = $ghost->name;
		sort($names);
		$names = implode(',', $names);
		asrt($names, 'one,zero');
		$rooms = $haunted->xownRoomList;
		$blueRoom = NULL;
		foreach( $rooms as $room ) {
			if ( $room->name === 'Blue Room' ) {
				$blueRoom = $room;
				break;
			}
		}
		asrt( !is_null( $blueRoom ), TRUE );
		asrt( is_array( $blueRoom->sharedGhostList ), TRUE );
		asrt( count( $blueRoom->sharedGhostList ), 3 );
		$names = array();
		foreach( $blueRoom->sharedGhost as $ghost ) $names[] = $ghost->name;
		sort($names);
		$names = implode(',', $names);
		asrt($names, 'one,three,two');
		$rooms = $haunted->xownRoomList;
		$redRoom = NULL;
		foreach( $rooms as $room ) {
			if ( $room->name === 'Red Room' ) {
				$redRoom = $room; break;
			}
		}
		$names = array();
		foreach( $redRoom->sharedGhost as $ghost ) $names[] = $ghost->name;
		sort($names);
		$names = implode(',', $names);
		asrt($names, 'two,zero');
		asrt( !is_null( $redRoom ), TRUE );
		asrt( is_array( $redRoom->sharedGhostList ), TRUE );
		asrt( count( $redRoom->sharedGhostList ), 2 );

		//Can we repaint a room?
		$redRoom->name = 'Yellow Room';
		$id = R::store($redRoom);
		$yellowRoom = R::load( 'room', $id );
		asrt( $yellowRoom->name, 'Yellow Room');
		asrt( !is_null( $yellowRoom ), TRUE );
		asrt( is_array( $yellowRoom->sharedGhostList ), TRUE );
		asrt( count( $yellowRoom->sharedGhostList ), 2 );

		//Can we throw one ghost out?
		array_pop( $yellowRoom->sharedGhost );
		R::store( $yellowRoom );
		$yellowRoom = $yellowRoom->fresh();
		asrt( $yellowRoom->name, 'Yellow Room');
		asrt( !is_null( $yellowRoom ), TRUE );
		asrt( is_array( $yellowRoom->sharedGhostList ), TRUE );
		asrt( count( $yellowRoom->sharedGhostList ), 1 );

		//can we remove one of the rooms?
		asrt( R::count('key'), 1);
		$list = $mansion->withCondition(' "name" = ? ', array('Blue Room'))->xownRoomList;
		$room = reset($list);
		unset($mansion->xownRoomList[$room->id]);
		R::store($mansion);
		asrt(R::count('room'), 2);

		//and what about its dependent beans?
		asrt(R::count('key'), 0);
		asrt(R::count('ghost_room'), 3);

		//and can we find ghosts?
		$ghosts = R::find('ghost');
		asrt(count($ghosts), 4);
		$ghosts = R::findAll('ghost', 'ORDER BY id');
		asrt(count($ghosts), 4);
		$ghosts = R::findAll('ghost', 'ORDER BY id LIMIT 2');
		asrt(count($ghosts), 2);
		$ghostZero = R::findOne('ghost', ' "name" = ? ', array( 'zero' ) );
		asrt( ($ghostZero instanceof OODBBean), TRUE );

		//can we create link properties on existing tables?
		$blackRoom = R::dispense( 'room' );
		$blackRoom->name = 'Black Room';
		$ghostZero->link('ghost_room', array('mood'=>'grumpy'))->room = $blackRoom;
		R::store($ghostZero);
		$ghostZero  = $ghostZero->fresh();
		$list = $ghostZero->sharedRoomList;
		asrt(count($list), 3);
		$ghostZero  = $ghostZero->fresh();
		$list = $ghostZero->withCondition(' ghost_room.mood = ? ', array('grumpy'))->sharedRoomList;
		asrt(count($list), 1);

		//can we load a batch?
		$ids = R::getCol('SELECT id FROM ghost');
		$ghosts = R::batch('ghost', $ids);
		asrt(count($ghosts), 4);

		//can we do an aggregation?
		$ghosts = $greenRoom->aggr('ownGhostRoom', 'ghost', 'ghost');
		asrt(count($ghosts), 2);

		//can we duplicate the mansion?
		asrt(R::count('mansion'), 1);
		asrt(R::count('room'), 3);
		asrt(R::count('ghost'), 4);
		$copy = R::dup($mansion);
		R::store($copy);
		asrt(R::count('mansion'), 2);
		asrt(R::count('room'), 5); //black room does not belong to mansion 1
		asrt(R::count('ghost'), 4);

		//can we do some counting using the list?
		asrt( $copy->countOwn('room'), 2);
		$rooms = $copy->withCondition(' "name" = ? ', array('Green Room'))->xownRoomList;
		$room = reset($rooms);
		asrt($room->countShared('ghost'), 2);

		//Finally restore old toolbox
		R::configureFacadeWithToolbox( $oldToolBox );
	}
}
