<?php
/**
 * RedUNIT_Base_Facade
 *
 * @file    RedUNIT/Base/Facade.php
 * @desc    Tests basic functions through facade.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Base_Facade extends RedUNIT_Base
{

	/**
	 * What drivers should be loaded for this test pack?
	 * This pack contains some SQL incomp. with OCI
	 */
	public function getTargetDrivers()
	{
		return array( 'mysql', 'pgsql', 'sqlite', 'CUBRID' );
	}

	/**
	 * Test common Facade usage scenarios.
	 * 
	 * @return void
	 */
	public function testCommonUsageFacade()
	{
		$toolbox = R::$toolbox;
		$adapter = $toolbox->getDatabaseAdapter();
		$writer  = $toolbox->getWriter();
		$redbean = $toolbox->getRedBean();
		$pdo     = $adapter->getDatabase();
		$a       = new RedBean_AssociationManager( $toolbox );

		asrt( R::$redbean instanceof RedBean_OODB, TRUE );
		asrt( R::$toolbox instanceof RedBean_Toolbox, TRUE );
		asrt( R::$adapter instanceof RedBean_Adapter, TRUE );
		asrt( R::$writer instanceof RedBean_QueryWriter, TRUE );

		$book = R::dispense( "book" );

		asrt( $book instanceof RedBean_OODBBean, TRUE );

		$book->title = "a nice book";

		$id = R::store( $book );

		asrt( ( $id > 0 ), TRUE );

		$book = R::load( "book", (int) $id );

		asrt( $book->title, "a nice book" );

		asrt( R::load( 'book', 999 )->title, NULL );

		R::freeze( TRUE );

		try {
			R::load( 'bookies', 999 );

			fail();
		} catch ( Exception $e ) {
			pass();
		}

		R::freeze( FALSE );

		$author = R::dispense( "author" );

		$author->name = "me";

		R::store( $author );

		$book9   = R::dispense( "book" );
		$author9 = R::dispense( "author" );

		$author9->name = "mr Nine";

		$a9 = R::store( $author9 );

		$book9->author_id = $a9;

		$bk9 = R::store( $book9 );

		$book9  = R::load( "book", $bk9 );
		$author = R::load( "author", $book9->author_id );

		asrt( $author->name, "mr Nine" );

		R::trash( $author );
		R::trash( $book9 );

		pass();

		$book2 = R::dispense( "book" );

		$book2->title = "second";

		R::store( $book2 );

		R::associate( $book, $book2 );

		asrt( count( R::related( $book, "book" ) ), 1 );

		$book3 = R::dispense( "book" );

		$book3->title = "third";

		R::store( $book3 );

		R::associate( $book, $book3 );

		asrt( count( R::related( $book, "book" ) ), 2 );

		asrt( count( R::find( "book" ) ), 3 );
		asrt( count( R::findAll( "book" ) ), 3 );
		asrt( count( R::findAll( "book", " LIMIT 2" ) ), 2 );

		asrt( count( R::find( "book", " id=id " ) ), 3 );
		asrt( count( R::find( "book", " title LIKE ?", array( "third" ) ) ), 1 );
		asrt( count( R::find( "book", " title LIKE ?", array( "%d%" ) ) ), 2 );

		// Now with new SQL Helper argument
		asrt( count( R::find( "book", R::$f->begin()->addSQL( 'title LIKE ? ' )->put( 'third' ) ) ), 1 );
		asrt( count( R::find( "book", R::$f->begin()->addSQL( 'title LIKE ? ' )->put( '%d%' ) ) ), 2 );
		asrt( count( R::find( "book", R::$f->begin()->addSQL( 'title' )->like( ' ? ' )->addSQL( ' ORDER BY id ' )->desc()->put( '%d%' ) ) ), 2 );

		// Find without where clause
		asrt( count( R::findAll( 'book', ' order by id' ) ), 3 );

		R::unassociate( $book, $book2 );

		asrt( count( R::related( $book, "book" ) ), 1 );

		R::trash( $book3 );
		R::trash( $book2 );

		asrt( count( R::related( $book, "book" ) ), 0 );

		asrt( count( R::getAll( "SELECT * FROM book " ) ), 1 );
		asrt( count( R::getCol( "SELECT title FROM book " ) ), 1 );

		asrt( (int) R::getCell( "SELECT 123 " ), 123 );

		$book = R::dispense( "book" );

		$book->title = "not so original title";

		$author = R::dispense( "author" );

		$author->name = "Bobby";

		R::store( $book );

		$aid = R::store( $author );

		R::associate( $book, $author );

		$author = R::findOne( "author", " name = ? ", array( "Bobby" ) );

		$books = R::related( $author, "book" );

		$book = reset( $books );

		testpack( "Test Swap function in R-facade" );

		$book = R::dispense( "book" );

		$book->title  = "firstbook";
		$book->rating = 2;

		$id1 = R::store( $book );

		$book = R::dispense( "book" );

		$book->title  = "secondbook";
		$book->rating = 3;

		$id2 = R::store( $book );

		$book1 = R::load( "book", $id1 );
		$book2 = R::load( "book", $id2 );

		asrt( $book1->rating, '2' );
		asrt( $book2->rating, '3' );

		$books = R::batch( "book", array( $id1, $id2 ) );

		R::swap( $books, "rating" );

		$book1 = R::load( "book", $id1 );
		$book2 = R::load( "book", $id2 );

		asrt( $book1->rating, '3' );
		asrt( $book2->rating, '2' );

		testpack( "Test R::convertToBeans" );

		$SQL = "SELECT '1' as id, a.name AS name, b.title AS title, '123' as rating FROM author AS a LEFT JOIN book as b ON b.id = ?  WHERE a.id = ? ";

		$rows = R::$adapter->get( $SQL, array( $id2, $aid ) );

		$beans = R::convertToBeans( "something", $rows );

		$bean = reset( $beans );

		asrt( $bean->getMeta( "type" ), "something" );

		asrt( $bean->name, "Bobby" );
		asrt( $bean->title, "secondbook" );
		asrt( $bean->rating, "123" );

		testpack( "Ext Assoc with facade and findRelated" );

		//R::setup("sqlite:/Users/prive/blaataap.db");

		R::exec( "DROP TABLE IF EXISTS performer" );
		R::exec( "DROP TABLE IF EXISTS cd_track" );

		R::exec( "DROP TABLE IF EXISTS track" );
		R::exec( "DROP TABLE IF EXISTS cd" );

		$cd = R::dispense( "cd" );

		$cd->title = "Midnight Jazzfest";

		R::store( $cd );

		$track = R::dispense( "track" );

		$track->title = "Night in Tunesia";

		$track2 = R::dispense( "track" );

		$track2->title = "Stompin at one o clock";

		$track3 = R::dispense( "track" );

		$track3->title = "Nightlife";

		R::store( $track );
		R::store( $track2 );
		R::store( $track3 );

		// Assoc ext with json
		R::associate( $track, $cd, '{"order":1}' );

		pass();

		// Width array
		R::associate( $track2, $cd, array( "order" => 2 ) );

		pass();

		R::associate( $track3, $cd, '{"order":3}' );

		pass();

		$tracks = R::related( $cd, "track", " title LIKE ? ", array( "Night%" ) );

		asrt( count( $tracks ), 2 );

		$track = array_pop( $tracks );

		asrt( ( strpos( $track->title, "Night" ) === 0 ), TRUE );

		$track = array_pop( $tracks );

		asrt( ( strpos( $track->title, "Night" ) === 0 ), TRUE );

		$track = R::dispense( "track" );

		$track->title = "test";

		R::associate( $track, $cd, "this column should be named extra" );

		asrt( R::getCell( "SELECT count(*) FROM cd_track WHERE extra = 'this column should be named extra' " ), "1" );

		$composer = R::dispense( "performer" );

		$composer->name = "Miles Davis";

		R::store( $composer );
	}
}
