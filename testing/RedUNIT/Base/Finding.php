<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;
use RedBeanPHP\AssociationManager as AssociationManager;
use RedBeanPHP\OODB as OODB;
use RedBeanPHP\OODBBean as OODBBean;
use RedBeanPHP\RedException as RedException;
use RedBeanPHP\RedException\SQL as SQL;

/**
 * Finding
 *
 * @file    RedUNIT/Base/Finding.php
 * @desc    Tests finding beans.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Finding extends Base {

	/**
	 * Helper for testing findLike.
	 *
	 * @param array   $flowers beans
	 * @param boolean $noSort  sorting?
	 *
	 * @return string
	 */
	private function getColors( $flowers, $noSort = FALSE )
	{
		$colors = array();
		foreach( $flowers as $flower ) $colors[] = $flower->color;
		if ( !$noSort) sort( $colors );
		return implode( ',', $colors );
	}

	/**
	 * Test forming IN-clause using genSlots and flat.
	 *
	 * @return void
	 */
	public function testINClause()
	{
		list( $flowers, $shop ) = R::dispenseAll( 'flower*4,shop' );
		$flowers[0]->color = 'red';
		$flowers[1]->color = 'yellow';
		$flowers[2]->color = 'blue';
		$flowers[3]->color = 'purple';
		$flowers[0]->price = 10;
		$flowers[1]->price = 15;
		$flowers[2]->price = 20;
		$flowers[3]->price = 25;
		$shop->xownFlowerList = $flowers;
		R::store( $shop );
		$colors = array( 'red', 'yellow' );
		$result = $this->getColors( R::find( 'flower', ' color IN ('.R::genSlots( $colors ).' ) AND price < ?' , R::flat( array( $colors, 100 ) ) ) );
		asrt( $result, 'red,yellow' );
		$colors = array( 'red', 'yellow' );
		$result = $this->getColors( R::find( 'flower', ' color IN ('.R::genSlots( $colors ).' ) AND price < ?' , R::flat( array( $colors, 10 ) ) ) );
		asrt( $result, '' );
		$colors = array( 'red', 'yellow' );
		$result = $this->getColors( R::find( 'flower', ' color IN ('.R::genSlots( $colors ).' ) AND price < ?' , R::flat( array( $colors, 15 ) ) ) );
		asrt( $result, 'red' );
		asrt( json_encode( R::flat( array( 'a', 'b', 'c' ) ) ), '["a","b","c"]' );
		asrt( json_encode( R::flat( array( 'a', array( 'b' ), 'c' ) ) ), '["a","b","c"]' );
		asrt( json_encode( R::flat( array( 'a', array( 'b', array( 'c' ) ) ) ) ), '["a","b","c"]' );
		asrt( json_encode( R::flat( array( array( 'a', array( 'b', array( array( 'c' ) ) ) ) ) ) ), '["a","b","c"]' );
		asrt( json_encode( R::flat( array( 'a', 'b', 'c', array() ) ) ), '["a","b","c"]' );
	}

	/**
	 * Test findLike.
	 *
	 * @return void
	 */
	public function testFindLike2()
	{
		list( $flowers, $shop ) = R::dispenseAll( 'flower*4,shop' );
		$flowers[0]->color = 'red';
		$flowers[1]->color = 'yellow';
		$flowers[2]->color = 'blue';
		$flowers[3]->color = 'purple';
		$flowers[0]->price = 10;
		$flowers[1]->price = 15;
		$flowers[2]->price = 20;
		$flowers[3]->price = 25;
		$shop->xownFlowerList = $flowers;
		R::store( $shop );
		asrt( $this->getColors( R::findLike( 'flower', array( 'color' => array( 'red', 'yellow' )  ), ' price < 20' ) ), 'red,yellow' );
		asrt( $this->getColors( R::findLike( 'flower', array( 'color' => array()  ), '' ) ), 'blue,purple,red,yellow' );
		asrt( $this->getColors( R::findLike( 'flower', array( 'color' => array()  ) ) ), 'blue,purple,red,yellow' );
		asrt( $this->getColors( R::findLike( 'flower', array( 'color' => array('blue')  ), ' OR price = 25' ) ), 'blue,purple' );
		asrt( $this->getColors( R::findLike( 'flower', array( 'color' => array()  ), ' price < 25' ) ), 'blue,red,yellow' );
		asrt( $this->getColors( R::findLike( 'flower', array( 'color' => array()  ), ' price < 20' ) ), 'red,yellow' );
		asrt( $this->getColors( R::findLike( 'flower', array( 'color' => array()  ), ' ORDER BY color DESC' ), TRUE ), 'yellow,red,purple,blue' );
		asrt( $this->getColors( R::findLike( 'flower', array( 'color' => array()  ), ' ORDER BY color LIMIT 1' ) ), 'blue' );
		asrt( $this->getColors( R::findLike( 'flower', array( 'color' => array( 'yellow', 'blue' )  ), ' ORDER BY color ASC  LIMIT 1' ) ), 'blue' );
	}

	/**
	 * Tests the findOrCreate method.
	 *
	 * @return void
	 */
	public function testFindOrCreate()
	{
		R::nuke();
		$book = R::findOrCreate( 'book', array( 'title' => 'my book', 'price' => 50 ) );
		asrt( ( $book instanceof OODBBean ), TRUE );
		$id = $book->id;
		$book = R::findOrCreate( 'book', array( 'title' => 'my book', 'price' => 50 ) );
		asrt( $book->id, $id );
		asrt( $book->title, 'my book' );
		asrt( (int) $book->price, 50 );
	}

	/**
	 * Tests the findLike method.
	 *
	 * @return void
	 */
	public function testFindLike()
	{
		R::nuke();
		$book = R::dispense( array(
			'_type' => 'book',
			'title' => 'my book',
			'price' => 80
		) );
		R::store( $book );
		$book = R::dispense( array(
			'_type' => 'book',
			'title' => 'other book',
			'price' => 80
		) );
		R::store( $book );
		$books = R::findLike( 'book', array( 'price' => 80 ) );
		asrt( count( $books ), 2 );
		foreach( $books as $book ) {
			asrt( $book->getMeta( 'type' ), 'book' );
		}
		$books = R::findLike( 'book' );
		asrt( count( $books ), 2 );
		$books = R::findLike( 'book', array( 'title' => 'my book' ) );
		asrt( count( $books ), 1 );
		$books = R::findLike( 'book', array( 'title' => array( 'my book', 'other book' ) ) );
		asrt( count( $books ), 2 );
		$books = R::findLike( 'book', array( 'title' => 'strange book') );
		asrt( is_array( $books ), TRUE );
		asrt( count( $books ), 0 );
		$books = R::findLike( 'magazine' );
		asrt( is_array( $books ), TRUE );
		asrt( count( $books ), 0 );
	}

	/**
	 * Test whether findOne gets a LIMIT 1
	 * clause.
	 *
	 * @return void
	 */
	public function testFindOneLimitOne()
	{
		R::nuke();
		list( $book1, $book2 ) = R::dispense( 'book', 2 );
		$book1->title = 'a';
		$book2->title = 'b';
		R::storeAll( array( $book1, $book2 ) );
		$logger = R::debug( 1, 1 );
		$logger->clear();
		$found = R::findOne( 'book' );
		asrt( count( $logger->grep('LIMIT 1') ), 1 );
		asrt( ( $found instanceof \RedBeanPHP\OODBBean ), TRUE );
		$logger->clear();
		$found = R::findOne( 'book', ' title = ? ', array( 'a' ) );
		asrt( count( $logger->grep('LIMIT 1') ), 1 );
		asrt( ( $found instanceof \RedBeanPHP\OODBBean ), TRUE );
		$logger->clear();
		$found = R::findOne( 'book', ' title = ? LIMIT 1', array( 'b' ) );
		asrt( count( $logger->grep('LIMIT 1') ), 1 );
		asrt( ( $found instanceof \RedBeanPHP\OODBBean ), TRUE );
		$found = R::findOne( 'book', ' title = ? LIMIT 2', array( 'b' ) );
		asrt( count( $logger->grep('LIMIT 2') ), 1 );
		asrt( ( $found instanceof \RedBeanPHP\OODBBean ), TRUE );
	}

	/**
	 * Begin testing.
	 * This method runs the actual test pack.
	 *
	 * @return void
	 */
	public function testFinding() {

		$toolbox = R::getToolBox();
		$adapter = $toolbox->getDatabaseAdapter();
		$writer  = $toolbox->getWriter();
		$redbean = $toolbox->getRedBean();
		$pdo     = $adapter->getDatabase();

		$a = new AssociationManager( $toolbox );

		$page = $redbean->dispense( "page" );

		$page->name = "John's page";

		$idpage = $redbean->store( $page );

		$page2 = $redbean->dispense( "page" );

		$page2->name = "John's second page";

		$idpage2 = $redbean->store( $page2 );

		$a->associate( $page, $page2 );

		$pageOne = $redbean->dispense( "page" );

		$pageOne->name = "one";

		$pageMore = $redbean->dispense( "page" );

		$pageMore->name = "more";

		$pageEvenMore = $redbean->dispense( "page" );

		$pageEvenMore->name = "evenmore";

		$pageOther = $redbean->dispense( "page" );

		$pageOther->name = "othermore";

		set1toNAssoc( $a, $pageOther, $pageMore );
		set1toNAssoc( $a, $pageOne, $pageMore );
		set1toNAssoc( $a, $pageOne, $pageEvenMore );

		asrt( count( $redbean->find( "page", array(), " name LIKE '%more%' ", array() ) ), 3 );
		asrt( count( $redbean->find( "page", array(), " name LIKE :str ", array( ":str" => '%more%' ) ) ), 3 );
		asrt( count( $redbean->find( "page", array(), array( " name LIKE :str ", array( ":str" => '%more%' ) ) ) ), 3 );
		asrt( count( $redbean->find( "page", array(), " name LIKE :str ", array( ":str" => '%mxore%' ) ) ), 0 );
		asrt( count( $redbean->find( "page", array( "id" => array( 2, 3 ) ) ) ), 2 );

		$bean = $redbean->dispense( "wine" );

		$bean->name = "bla";

		for ( $i = 0; $i < 10; $i++ ) {
			$redbean->store( $bean );
		}

		$redbean->find( "wine", array( "id" => 5 ) ); //  Finder:where call OODB::convertToBeans

		$bean2 = $redbean->load( "anotherbean", 5 );

		asrt( $bean2->id, 0 );

		$keys = $adapter->getCol( "SELECT id FROM page WHERE " . $writer->esc( 'name' ) . " LIKE '%John%'" );

		asrt( count( $keys ), 2 );

		$pages = $redbean->batch( "page", $keys );

		asrt( count( $pages ), 2 );

		$p = R::findLast( 'page' );

		pass();

		$row = R::getRow( 'select * from page ' );

		asrt( is_array( $row ), TRUE );

		asrt( isset( $row['name'] ), TRUE );

		// Test findAll -- should not throw an exception
		asrt( count( R::findAll( 'page' ) ) > 0, TRUE );
		asrt( count( R::findAll( 'page', ' ORDER BY id ' ) ) > 0, TRUE );

		$beans = R::findOrDispense( "page" );

		asrt( count( $beans ), 6 );

		asrt( is_null( R::findLast( 'nothing' ) ), TRUE );

		try {
			R::find( 'bean', ' id > 0 ', 'invalid bindings argument' );
			fail();
		} catch ( RedException $exception ) {
			pass();
		}
	}

	/**
	 * Test tree traversal with searchIn().
	 *
	 * @return void
	 */
	public function testTreeTraversal()
	{
		testpack( 'Test Tree Traversal' );
		R::nuke();

		$page = R::dispense( 'page', 10 );

		//Setup the test data for this series of tests
		$i = 0;
		foreach( $page as $pageItem ) {
			$pageItem->name = 'page' . $i;
			$pageItem->number = $i;
			$i++;
			R::store( $pageItem );
		}
		$page[0]->ownPage  = array( $page[1], $page[2] );
		$page[1]->ownPage  = array( $page[3], $page[4] );
		$page[3]->ownPage  = array( $page[5] );
		$page[5]->ownPage  = array( $page[7] );
		$page[9]->document = $page[8];
		$page[9]->book = R::dispense('book');
		R::store( $page[9] );
		$id = R::store( $page[0] );
		$book = $page[9]->book;
	}

	/**
	 * Test find and export.
	 *
	 * @return void
	 */
	public function testFindAndExport()
	{
		R::nuke();
		$pages = R::dispense( 'page', 3 );
		$i = 1;
		foreach( $pages as $page ) {
			$page->pageNumber = $i++;
		}
		R::storeAll( $pages );
		$pages = R::findAndExport( 'page' );
		asrt( is_array( $pages ), TRUE );
		asrt( isset( $pages[0] ), TRUE );
		asrt( is_array( $pages[0] ), TRUE );
		asrt( count( $pages ), 3 );
	}

	/**
	* Test error handling of SQL states.
	*
	* @return void
	*/
	public function testFindError()
	{
		R::freeze( FALSE );

		$page = R::dispense( 'page' );
		$page->title = 'abc';
		R::store( $page );

		//Column does not exist, in fluid mode no error!
		try {
			R::find( 'page', ' xtitle = ? ', array( 'x' ) );
			pass();
		} catch ( SQL $e ) {
			fail();
		}

		//Table does not exist, in fluid mode no error!
		try {
			R::find( 'pagex', ' title = ? ', array( 'x' ) );
			pass();
		} catch ( SQL $e ) {
			fail();
		}

		//Syntax error, error in fluid mode if possible to infer from SQLSTATE (MySQL/Postgres)
		try {
			R::find( 'page', ' invalid SQL ' );
			//In SQLite only get HY000 - not very descriptive so suppress more errors in fluid mode then.
			if (
			$this->currentlyActiveDriverID === 'sqlite'
			|| $this->currentlyActiveDriverID === 'CUBRID' ) {
				pass();
			} else {
				fail();
			}
		} catch ( SQL $e ) {
			pass();
		}

		//Frozen, always error...
		R::freeze( TRUE );

		//Column does not exist, in frozen mode error!
		try {
			R::find( 'page', ' xtitle = ? ', array( 'x' ) );
			fail();
		} catch ( SQL $e ) {
			pass();
		}

		//Table does not exist, in frozen mode error!
		try {
			R::find( 'pagex', ' title = ? ', array( 'x' ) );
			fail();
		} catch ( SQL $e ) {
			pass();
		}

		//Syntax error, in frozen mode error!
		try {
			R::find( 'page', ' invalid SQL ' );
			fail();
		} catch ( SQL $e ) {
			pass();
		}
		R::freeze( FALSE );
	}
}
