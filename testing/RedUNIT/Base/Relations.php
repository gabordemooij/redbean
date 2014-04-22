<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;
use RedBeanPHP\RedException as RedException;
use RedBeanPHP\OODBBean as OODBBean;

/**
 * RedUNIT_Base_Relations
 *
 * @file    RedUNIT/Base/Relations.php
 * @desc    Tests N:1 relations, nested beans.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Relations extends Base
{
	/**
	 * Test whether we can't add more than one FK.
	 */
	public function testDuplicateFK()
	{
		R::nuke();
		list( $book, $page ) = R::dispenseAll( 'book,page' );
		$book->sharedPage[] = $page;
		R::store( $page );
		R::store( $book );
		$added = R::getWriter()->addConstraintForTypes( 'page', 'book' );
		asrt( $added, FALSE );
	}

	/**
	 * Test whether ->all() reloads a list.
	 *
	 * @return void
	 */
	public function testAllPrefix()
	{
		R::nuke();
		$book = R::dispense( 'book' );
		$book->ownPage = R::dispense( 'page', 10 );
		$book->sharedTag = R::dispense( 'tag', 2 );
		$i = 0;
		foreach( $book->ownPage as $page ) {
			$page->pageno = $i++;
		}
		R::store( $book );
		$book = $book->fresh();
		asrt( count( $book->ownPage ), 10 );
		asrt( count( $book->withCondition(' pageno < 5 ')->ownPage ), 5 );
		asrt( count( $book->ownPage ), 5 );
		asrt( count( $book->all()->ownPage ), 10 );
		asrt( count( $book->with(' LIMIT 3 ')->ownPage ), 3 );
		asrt( count( $book->ownPage ), 3 );
		asrt( count( $book->all()->ownPage ), 10 );
		asrt( count( $book->sharedTag ), 2 );
		asrt( count( $book->with( ' LIMIT 1 ' )->sharedTag ), 1 );
		asrt( count( $book->sharedTag ), 1 );
		asrt( count( $book->all()->sharedTag ), 2 );
	}

	/**
	 * Test Relations and conditions.
	 *
	 * @return void
	 */
	public function testRelationsAndConditions()
	{
		list( $book1, $book2 ) = R::dispense( 'book', 2 );

		list( $page1, $page2, $page3, $page4 ) = R::dispense( 'page', 4 );

		list( $author1, $author2 ) = R::dispense( 'author', 2 );

		$book1->title = 'a';
		$book2->title = 'b';

		$page1->thename = '1';
		$page2->thename = '2';
		$page3->thename = '3';
		$page3->thename = '4';

		$book1->ownPage = array( $page1, $page2 );
		$book2->ownPage = array( $page3, $page4 );

		$author1->sharedBook = array( $book1, $book2 );
		$author2->sharedBook = array( $book2 );

		R::storeAll( array( $author1, $author2 ) );

		asrt( count( $author1->sharedBook ), 2 );
		asrt( count( $author1->withCondition( ' title = ? ', array( 'a' ) )->sharedBook ), 1 );

		R::store( $author1 );

		asrt( count( $author1->sharedBook ), 2 );
		asrt( count( $author1->withCondition( ' xtitle = ? ', array( 'a' ) )->sharedBook ), 0 );

		R::store( $author1 );

		asrt( count( $author1->sharedBook ), 2 );

		$book1 = R::load( 'book', $book1->id );

		$book2 = $book2->fresh();

		asrt( count( $book1->ownPage ), 2 );
		asrt( count( $book1->with( ' LIMIT 1 ' )->ownPage ), 1 );

		$book1 = $book1->fresh();

		asrt( count( $book1->ownPage ), 2 );
		asrt( count( $book1->withCondition( ' thename = ? ', array( '1' ) )->ownPage ), 1 );
	}

	/**
	 * Test filtering relations on links (using columns in the link table).
	 *
	 * @return void
	 */
	public function testSharedLinkCond()
	{
		testpack( 'Test new shared relations with link conditions' );

		$w = R::getWriter();

		list( $b1, $b2 ) = R::dispense( 'book', 2 );

		$b1->name = 'book1';
		$b2->name = 'book2';

		list( $p1, $p2, $p3 ) = R::dispense( 'page', 3 );

		$p1->text   = 'page1';
		$p1->number = 3;
		$p2->text   = 'page2';
		$p3->text   = 'page3';

		$b1->link( 'book_page', array( 'order' => 1 ) )->page = $p1;
		$b1->link( 'bookPage', array( 'order' => 2 ) )->page = $p2;
		$b2->link( 'book_page', array( 'order' => 1 ) )->page = $p3;
		$b2->link( 'bookPage', array( 'order' => 2 ) )->page = $p2;
		$b2->link( 'book_page', array( 'order' => 3 ) )->page = $p1;

		R::storeAll( array( $b1, $b2 ) );

		$b1 = R::load( 'book', $b1->id );
		$b2 = R::load( 'book', $b2->id );

		$pages = $b1->withCondition( ' book_page.' . $w->esc( 'order' ) . ' = 2 ' )->sharedPage;

		$page = reset( $pages );

		asrt( $page->text, 'page2' );

		$pages = $b2->withCondition( ' ' . $w->esc( 'order' ) . ' = 3 ' )->sharedPage;

		$page = reset( $pages );

		asrt( $page->text, 'page1' );

		$b1 = R::load( 'book', $b1->id );
		$b2 = R::load( 'book', $b2->id );

		$pages = $b1->withCondition( ' book_page.' . $w->esc( 'order' ) . ' < 3  AND page.number = 3' )->sharedPage;

		$page = reset( $pages );

		asrt( $page->text, 'page1' );

		$pages = $b2->withCondition( ' ' . $w->esc( 'order' ) . ' > 1  ORDER BY book_page.' . $w->esc( 'order' ) . ' ASC ' )->sharedPage;

		$page = array_shift( $pages );

		asrt( $page->text, 'page2' );

		$page = array_shift( $pages );

		asrt( $page->text, 'page1' );

		testpack( 'Test new shared relations and cache' );

		/**
		 * why does this not destroy cache in psql?
		 * ah: An error occurred: SQLSTATE[42703]: Undefined column: 7
		 * ERROR:  column "page" of relation "page" does not exist
		 */
		R::exec( 'UPDATE page SET ' . $w->esc( 'number' ) . ' = 1 ' );

		R::getWriter()->setUseCache( TRUE );

		$p1 = R::load( 'page', (int) $p1->id );

		// Someone else changes the records. Cache remains.
		R::exec( ' UPDATE page SET ' . $w->esc( 'number' ) . ' = 9 -- keep-cache' );

		$b1 = R::load( 'book', $b1->id );
		$p1 = R::load( 'page', (int) $p1->id );

		// Yupz a stale cache, phantom read!
		asrt( (int) $p1->number, 1 );

		$pages = $b1->withCondition( ' book_page.' . $w->esc( 'order' ) . ' = 1 ' )->sharedPage;

		$page = reset( $pages );

		// Inconsistent, sad but TRUE, different query -> cache key is different
		asrt( (int) $page->number, 9 );

		// However, cache must have been invalidated by this query
		$p1 = R::load( 'page', (int) $p1->id );

		// Yes! we're consistent again! -- as if the change just happened later!
		asrt( (int) $page->number, 9 );

		// By doing this we keep getting 9 instead of 8
		$b1->fresh()->withCondition( ' book_page.' . $w->esc( 'order' ) . ' = 1 ' )->sharedPage;

		// Someone else is busy again...
		R::exec( ' UPDATE page SET ' . $w->esc( 'number' ) . ' = 8 -- keep-cache' );

		$b1 = R::load( 'book', $b1->id );

		$pages = $b1->withCondition( ' book_page.' . $w->esc( 'order' ) . ' = 1 ' )->sharedPage;

		$page = reset( $pages );

		/**
		 * yes! we get 9 instead of 8, why because the cache key has not changed,
		 * our last query was PAGE-BOOK-RELATION and now we ask for
		 * PAGE-BOOK-RELATION again. if we would have used just a load page
		 * query we would have gotten the new value (8).... let's test that!
		 */
		asrt( (int) $page->number, 9 );

		R::exec( ' UPDATE page SET ' . $w->esc( 'number' ) . ' = 9' );

		$p1 = R::load( 'page', (int) $p1->id );

		asrt( (int) $page->number, 9 );

		// Someone else is busy again...
		R::exec( ' UPDATE page SET ' . $w->esc( 'number' ) . ' = 8 -- keep-cache' );

		$b1 = R::load( 'book', $b1->id );

		$pages = $b1->withCondition( ' book_page.' . $w->esc( 'order' ) . ' = 1 ' )->sharedPage;

		$page = reset( $pages );

		// Yes, keep-cache wont help, cache key changed!
		asrt( (int) $page->number, 8 );

		R::getWriter()->setUseCache( FALSE );

	}

	/**
	 * Test related count using via().
	 *
	 * @return void
	 */
	public function testRelatedCountVia()
	{
		testpack( 'Test relatedCount with via()' );

		$shop              = R::dispense( 'shop' );
		$shop->ownRelation = R::dispense( 'relation', 13 );
		foreach ( $shop->ownRelation as $relation ) {
			$relation->shop     = $shop;
			$relation->customer = R::dispense( 'customer' );
		}
		R::store( $shop );
		$shop = $shop->fresh();

		asrt( $shop->via( 'relation' )->countShared( 'customer' ), 13 );
	}

	/**
	 * Test counting and aliasing.
	 *
	 * @return void
	 */
	public function testCountingAndAliasing()
	{
		$book = R::dispense( 'book' );

		$book->ownPage = R::dispense( 'page', 10 );

		$book2 = R::dispense( 'book' );

		$book2->ownPage = R::dispense( 'page', 4 );

		list( $Bill, $James, $Andy ) = R::dispense( 'person', 3 );

		$book->author   = $Bill;
		$book->coAuthor = $James;

		$book2->author   = $Bill;
		$book2->coAuthor = $Andy;

		$book->price  = 25;
		$book2->price = 50;

		$notes = R::dispense( 'note', 10 );

		$book->sharedNote  = array( $notes[0], $notes[1], $notes[2] );
		$book2->sharedNote = array( $notes[3], $notes[4], $notes[1], $notes[0] );

		$books = R::dispense( 'book', 5 );

		$books[2]->title = 'boe';

		$book->sharedBook = array( $books[0], $books[1] );

		$book2->sharedBook = array( $books[0], $books[2], $books[4] );

		R::storeAll( array( $book, $book2 ) );

		asrt( $book->countOwn( 'page' ), 10 );
		asrt( $book->withCondition( ' id < 5 ' )->countOwn( 'page' ), 4 );

		asrt( $Bill->alias( 'author' )->countOwn( 'book' ), 2 );
		asrt( $Andy->alias( 'coAuthor' )->countOwn( 'book' ), 1 );
		asrt( $James->alias( 'coAuthor' )->countOwn( 'book' ), 1 );
		asrt( $Bill->alias( 'author' )->countOwn( 'book' ), 2 );

		asrt( $book->countShared( 'note' ), 3 );

		asrt( $book2->countShared( 'note' ), 4 );
		asrt( $book2->countShared( 'book' ), 3 );

		$book2 = $book2->fresh();

		asrt( $book2->withCondition( ' title = ? ', array( 'boe' ) )->countShared( 'book' ), 1 );
	}

	/**
	 * Test via.
	 *
	 * @return void
	 */
	public function testVia()
	{
		testpack( 'Test via()' );

		$d = R::dispense( 'doctor' )->setAttr( 'name', 'd1' );
		$p = R::dispense( 'patient' )->setAttr( 'name', 'p1' );

		$d->via( 'consult' )->sharedPatient[] = $p;

		R::store( $d );

		$d = R::load( 'doctor', $d->id );

		asrt( count( $d->sharedPatient ), 1 );
		asrt( in_array( 'consult', R::getWriter()->getTables() ), TRUE );
	}

	public function testIssue348()
	{
		//issue #348 via() should reload shared list

		$product = R::dispense( 'product' );
		$product->name = 'test';
		$color = R::dispense( 'color' );
		$color->name = 'cname';
		$color->code = 'ccode';
		R::store( $product );
		R::store( $color );
		$product->link( 'productColor', array(
			 'stock' => 1,
			 'position' => 0
		) )->color = $color;
		R::store( $product );
		asrt( count( $product->sharedColor ), 0 );
		asrt( count( $product->via( 'product_color' )->sharedColor ), 1 );
		asrt( count( $product->sharedColor ), 1 );
		R::renameAssociation( 'color_product', NULL );
	}

	/**
	 * Test creation of link table.
	 *
	 * @return void
	 */
	public function testCreationOfLinkTable()
	{
		asrt( in_array( 'consult', R::getWriter()->getTables() ), FALSE );

		$d = R::dispense( 'doctor' )->setAttr( 'name', 'd1' );
		$p = R::dispense( 'patient' )->setAttr( 'name', 'p1' );

		$d->sharedPatient[] = $p;
		R::store($d);

		asrt( in_array( 'consult', R::getWriter()->getTables() ), TRUE );
	}

	/**
	 * Fast track link block code should not affect self-referential N-M relations.
	 *
	 * @return void
	 */
	public function testFastTrackRelations()
	{
		testpack( 'Test fast-track linkBlock exceptions' );

		list( $donald, $mickey, $goofy, $pluto ) = R::dispense( 'friend', 4 );

		$donald->name = 'D';
		$mickey->name = 'M';
		$goofy->name  = 'G';
		$pluto->name  = 'P';

		$donald->sharedFriend = array( $mickey, $goofy );
		$mickey->sharedFriend = array( $pluto, $goofy );
		$mickey->sharedBook   = array( R::dispense( 'book' ) );

		R::storeAll( array( $mickey, $donald, $goofy, $pluto ) );

		$donald = R::load( 'friend', $donald->id );
		$mickey = R::load( 'friend', $mickey->id );
		$goofy  = R::load( 'friend', $goofy->id );
		$pluto  = R::load( 'friend', $pluto->id );

		$names = implode( ',', R::gatherLabels( $donald->sharedFriend ) );

		asrt( $names, 'G,M' );

		$names = implode( ',', R::gatherLabels( $goofy->sharedFriend ) );

		asrt( $names, 'D,M' );

		$names = implode( ',', R::gatherLabels( $mickey->sharedFriend ) );

		asrt( $names, 'D,G,P' );

		$names = implode( ',', R::gatherLabels( $pluto->sharedFriend ) );

		asrt( $names, 'M' );

		// Now in combination with with() conditions...
		$donald = R::load( 'friend', $donald->id );

		$names = implode( ',', R::gatherLabels( $donald->withCondition( ' name = ? ', array( 'M' ) )->sharedFriend ) );

		asrt( $names, 'M' );

		// Now in combination with with() conditions...
		$donald = R::load( 'friend', $donald->id );

		$names = implode( ',', R::gatherLabels( $donald->with( ' ORDER BY name ' )->sharedFriend ) );

		asrt( $names, 'G,M' );

		// Now counting
		$goofy = R::load( 'friend', $goofy->id );

		asrt( (int) $goofy->countShared( 'friend' ), 2 );
		asrt( (int) $donald->countShared( 'friend' ), 2 );
		asrt( (int) $mickey->countShared( 'friend' ), 3 );
		asrt( (int) $pluto->countShared( 'friend' ), 1 );
	}

	/**
	 * Test list beautifications.
	 *
	 * @return void
	 */
	public function testListBeautifications()
	{
		testpack( 'Test list beautifications' );

		$book = R::dispense( 'book' );
		$page = R::dispense( 'page' )->setAttr( 'name', 'a' );
		$book->sharedPage[] = $page;

		$id = R::store( $book );

		$book = R::load( 'book', $id );

		$p = reset( $book->ownBookPage );

		asrt( $p->page->name, 'a' );

		$bean = R::dispense( 'bean' );

		$bean->sharedAclRole[] = R::dispense( 'role' )->setAttr( 'name', 'x' );

		R::store( $bean );

		asrt( R::count( 'role' ), 1 );

		$aclrole = R::getRedBean()->dispense( 'acl_role' );

		$aclrole->name = 'role';

		$bean = R::dispense( 'bean' );

		$bean->sharedAclRole[] = $aclrole;

		R::store( $bean );

		asrt( count( $bean->sharedAclRole ), 1 );
	}

	/**
	 * Test list add and delete.
	 *
	 * @return void
	 */
	public function testListAddDelete()
	{
		testpack( 'Test list add/delete scenarios.' );

		R::nuke();
		$b = R::dispense( 'book' );
		$p = R::dispense( 'page' );

		$b->title = 'a';
		$p->name  = 'b';

		$b->xownPage[] = $p;

		R::store( $b );

		$b->xownPage = array();

		R::store( $b );

		asrt( R::count( 'page' ), 0 );

		$p = R::dispense( 'page' );
		$z = R::dispense( 'paper' );

		$z->xownPage[] = $p;

		R::store( $z );

		asrt( R::count( 'page' ), 1 );

		$z->xownPage = array();

		R::store( $z );

		asrt( R::count( 'page' ), 0 );

		$i = R::dispense( 'magazine' );

		$i->ownPage[] = R::dispense( 'page' );

		R::store( $i );

		asrt( R::count( 'page' ), 1 );

		$i->ownPage = array();

		R::store( $i );

		asrt( R::count( 'page' ), 1 );

	}

	/**
	 * Test basic and complex common usage scenarios for
	 * relations and associations.
	 *
	 * @return void
	 */
	public function testScenarios()
	{
		list( $q1, $q2 ) = R::dispense( 'quote', 2 );

		list( $pic1, $pic2 ) = R::dispense( 'picture', 2 );

		list( $book, $book2, $book3 ) = R::dispense( 'book', 4 );

		list( $topic1, $topic2, $topic3, $topic4, $topic5 ) = R::dispense( 'topic', 5 );

		list( $page1, $page2, $page3, $page4, $page5, $page6, $page7 ) = R::dispense( 'page', 7 );

		$q1->text = 'lorem';
		$q2->text = 'ipsum';

		$book->title  = 'abc';
		$book2->title = 'def';
		$book3->title = 'ghi';

		$page1->title = 'pagina1';
		$page2->title = 'pagina2';
		$page3->title = 'pagina3';
		$page4->title = 'pagina4';
		$page5->title = 'pagina5';
		$page6->title = 'cover1';
		$page7->title = 'cover2';

		$topic1->name = 'holiday';
		$topic2->name = 'cooking';
		$topic3->name = 'gardening';
		$topic4->name = 'computing';
		$topic5->name = 'christmas';

		// Add one page to the book
		$book->ownPage[] = $page1;

		$id = R::store( $book );

		asrt( count( $book->ownPage ), 1 );
		asrt( reset( $book->ownPage )->getMeta( 'type' ), 'page' );

		$book = R::load( 'book', $id );

		asrt( count( $book->ownPage ), 1 );
		asrt( reset( $book->ownPage )->getMeta( 'type' ), 'page' );

		// Performing an own addition
		$book->ownPage[] = $page2;

		$id   = R::store( $book );
		$book = R::load( 'book', $id );

		asrt( count( $book->ownPage ), 2 );

		// Performing a deletion
		$book = R::load( 'book', $id );

		unset( $book->ownPage[1] );

		$id = R::store( $book );

		$book = R::load( 'book', $id );

		asrt( count( $book->ownPage ), 1 );
		asrt( reset( $book->ownPage )->getMeta( 'type' ), 'page' );
		asrt( R::count( 'page' ), 2 ); //still exists
		asrt( reset( $book->ownPage )->id, '2' );

		// Doing a change in one of the owned items
		$book->ownPage[2]->title = 'page II';

		$id   = R::store( $book );
		$book = R::load( 'book', $id );

		asrt( reset( $book->ownPage )->title, 'page II' );

		// Change by reference now... don't copy!
		$refToPage2 = $book->ownPage[2];

		$refToPage2->title = 'page II b';

		$id   = R::store( $book );
		$book = R::load( 'book', $id );

		asrt( reset( $book->ownPage )->title, 'page II b' );

		// Doing all actions combined
		$book->ownPage[] = $page3;

		R::store( $book );

		$book = R::load( 'book', $id );

		unset( $book->ownPage[2] );

		// And test custom key
		$book->ownPage['customkey'] = $page4;
		$book->ownPage[3]->title    = "THIRD";

		R::store( $book );

		$book = R::load( 'book', $id );

		asrt( count( $book->ownPage ), 2 );

		$p4 = $book->ownPage[4];
		$p3 = $book->ownPage[3];

		asrt( $p4->title, 'pagina4' );
		asrt( $p3->title, 'THIRD' );

		// Test replacing an element
		$book = R::load( 'book', $id );

		$book->ownPage[4] = $page5;

		R::store( $book );

		$book = R::load( 'book', $id );

		asrt( count( $book->ownPage ), 2 );

		$p5 = $book->ownPage[5];

		asrt( $p5->title, 'pagina5' );

		// Other way around - single bean
		asrt( $p5->book->title, 'abc' );
		asrt( R::load( 'page', 5 )->book->title, 'abc' );
		asrt( R::load( 'page', 3 )->book->title, 'abc' );

		// Add the other way around - single bean
		$page1->id   = 0;

		$page1->book = $book2;

		$page1 = R::load( 'page', R::store( $page1 ) );

		asrt( $page1->book->title, 'def' );

		$b2 = R::load( 'book', $id );

		asrt( count( $b2->ownPage ), 2 );

		// Remove the other way around - single bean
		unset( $page1->book );

		R::store( $page1 );

		$b2 = R::load( 'book', $book2->id );

		asrt( count( $b2->ownPage ), 1 ); //does not work
		$page1->book = NULL;

		R::store( $page1 );

		$b2 = R::load( 'book', $book2->id );

		asrt( count( $b2->ownPage ), 0 ); //works

		// Re-add the page
		$b2->ownPage[] = $page1;

		R::store( $b2 );

		$b2 = R::load( 'book', $book2->id );

		asrt( count( $b2->ownPage ), 1 );

		// Different, less elegant way to remove
		$page1 = reset( $b2->ownPage );

		$page1->book_id = NULL;

		R::store( $page1 );

		$b2 = R::load( 'book', $book2->id );

		asrt( count( $b2->ownPage ), 0 );

		// Re-add the page
		$b2->ownPage[] = $page1;

		R::store( $b2 );

		$b2 = R::load( 'book', $book2->id );

		asrt( count( $b2->ownPage ), 1 );

		// Another less elegant way to remove
		$page1->book = NULL;

		R::store( $page1 );

		$cols = R::getColumns( 'page' );

		asrt( isset( $cols['book'] ), FALSE );

		$b2 = R::load( 'book', $book2->id );

		asrt( count( $b2->ownPage ), 0 );

		// Re-add the page
		$b2->ownPage[] = $page1;

		R::store( $b2 );

		$b2 = R::load( 'book', $book2->id );

		asrt( count( $b2->ownPage ), 1 );

		// Another less elegant... just plain ugly... way to remove
		$page1->book = FALSE;

		R::store( $page1 );

		$cols = R::getColumns( 'page' );

		asrt( isset( $cols['book'] ), FALSE );

		$b2 = R::load( 'book', $book2->id );

		asrt( count( $b2->ownPage ), 0 );

		// Re-add the page
		$b2->ownPage[] = $page1;

		R::store( $b2 );

		$b2 = R::load( 'book', $book2->id );

		asrt( count( $b2->ownPage ), 1 );

		// You are not allowed to re-use the field for something else
		foreach (
			array(
				1, -2.1, array(),
				TRUE, 'NULL', new \stdClass,
				'just a string', array( 'a' => 1 ), 0
			) as $value
		) {
			try {
				$page1->book = $value;
				fail();
			} catch ( RedException $e ) {
				pass();
			}
		}

		// Test fk, not allowed to set to 0
		$page1 = reset( $b2->ownPage );

		$page1->book_id = 0;

		// Even uglier way, but still needs to work
		$page1 = reset( $b2->ownPage );

		$page1->book_id = NULL;

		R::store( $b2 );

		$b2 = R::load( 'book', $book2->id );

		asrt( count( $b2->ownPage ), 0 );

		// Test shared items
		$book = R::load( 'book', $id );

		$book->sharedTopic[] = $topic1;

		$id = R::store( $book );

		// Add an item
		asrt( count( $book->sharedTopic ), 1 );
		asrt( reset( $book->sharedTopic )->name, 'holiday' );

		$book = R::load( 'book', $id );

		asrt( count( $book->sharedTopic ), 1 );
		asrt( reset( $book->sharedTopic )->name, 'holiday' );

		// Add another item
		$book->sharedTopic[] = $topic2;

		$id   = R::store( $book );
		$tidx = R::store( R::dispense( 'topic' ) );

		$book = R::load( 'book', $id );

		asrt( count( $book->sharedTopic ), 2 );

		$t1 = $book->sharedTopic[1];
		$t2 = $book->sharedTopic[2];

		asrt( $t1->name, 'holiday' );
		asrt( $t2->name, 'cooking' );

		// Remove an item
		unset( $book->sharedTopic[2] );

		asrt( count( $book->sharedTopic ), 1 );

		$id = R::store( $book );

		$book = R::load( 'book', $id );

		asrt( count( $book->sharedTopic ), 1 );
		asrt( reset( $book->sharedTopic )->name, 'holiday' );

		// Add and change
		$book->sharedTopic[]        = $topic3;
		$book->sharedTopic[1]->name = 'tropics';

		$id = R::store( $book );

		$book = R::load( 'book', $id );

		asrt( count( $book->sharedTopic ), 2 );
		asrt( $book->sharedTopic[1]->name, 'tropics' );

		testids( $book->sharedTopic );

		R::trash( R::load( 'topic', $tidx ) );

		$id = R::store( $book );

		$book = R::load( 'book', $id );

		// Delete without save
		unset( $book->sharedTopic[1] );

		$book = R::load( 'book', $id );

		asrt( count( $book->sharedTopic ), 2 );

		$book = R::load( 'book', $id );

		// Delete without init
		asrt( ( R::count( 'topic' ) ), 3 );

		unset( $book->sharedTopic[1] );

		$id = R::store( $book );

		asrt( ( R::count( 'topic' ) ), 3 );
		asrt( count( $book->sharedTopic ), 1 );
		asrt( count( $book2->sharedTopic ), 0 );

		// Add same topic to other book
		$book2->sharedTopic[] = $topic3;

		asrt( count( $book2->sharedTopic ), 1 );

		$id2 = R::store( $book2 );

		asrt( count( $book2->sharedTopic ), 1 );

		$book2 = R::load( 'book', $id2 );

		asrt( count( $book2->sharedTopic ), 1 );

		// Get books for topic
		asrt( $topic3->countShared('book'), 2 );


		$t3 = R::load( 'topic', $topic3->id );

		asrt( count( $t3->sharedBook ), 2 );


		// Nuke an own-array, replace entire array at once without getting first
		$page2->id    = 0;
		$page2->title = 'yet another page 2';
		$page4->id    = 0;
		$page4->title = 'yet another page 4';

		$book = R::load( 'book', $id );

		$book->ownPage = array( $page2, $page4 );

		R::store( $book );

		$book = R::load( 'book', $id );

		asrt( count( $book->ownPage ), 2 );
		asrt( reset( $book->ownPage )->title, 'yet another page 2' );
		asrt( end( $book->ownPage )->title, 'yet another page 4' );

		testids( $book->ownPage );

		// Test with alias format
		$book3->cover = $page6;

		$idb3 = R::store( $book3 );

		$book3 = R::load( 'book', $idb3 );

		$justACover = $book3->fetchAs( 'page' )->cover;

		asrt( ( $book3->cover instanceof OODBBean ), TRUE );
		asrt( $justACover->title, 'cover1' );

		// No page property
		asrt( isset( $book3->page ), FALSE );

		// Test doubling and other side effects ... should not occur..
		$book3->sharedTopic = array( $topic1, $topic2 );

		$book3 = R::load( 'book', R::store( $book3 ) );

		$book3->sharedTopic = array();

		$book3 = R::load( 'book', R::store( $book3 ) );

		asrt( count( $book3->sharedTopic ), 0 );

		$book3->sharedTopic[] = $topic1;

		$book3 = R::load( 'book', R::store( $book3 ) );

		// Added only one, not more?
		asrt( count( $book3->sharedTopic ), 1 );
		asrt( intval( R::getCell( "select count(*) from book_topic where book_id = $idb3" ) ), 1 );

		// Add the same
		$book3->sharedTopic[] = $topic1;

		$book3 = R::load( 'book', R::store( $book3 ) );

		asrt( count( $book3->sharedTopic ), 1 );
		asrt( intval( R::getCell( "select count(*) from book_topic where book_id = $idb3" ) ), 1 );

		$book3->sharedTopic['differentkey'] = $topic1;

		$book3 = R::load( 'book', R::store( $book3 ) );

		asrt( count( $book3->sharedTopic ), 1 );
		asrt( intval( R::getCell( "select count(*) from book_topic where book_id = $idb3" ) ), 1 );

		// Ugly assign, auto array generation
		$book3->ownPage[] = $page1;

		$book3 = R::load( 'book', R::store( $book3 ) );

		asrt( count( $book3->ownPage ), 1 );
		asrt( intval( R::getCell( "select count(*) from page where book_id = $idb3 " ) ), 1 );

		$book3 = R::load( 'book', $idb3 );

		$book3->ownPage = array();

		// No change until saved
		asrt( intval( R::getCell( "select count(*) from page where book_id = $idb3 " ) ), 1 );

		$book3 = R::load( 'book', R::store( $book3 ) );

		asrt( intval( R::getCell( "select count(*) from page where book_id = $idb3 " ) ), 0 );
		asrt( count( $book3->ownPage ), 0 );

		$book3 = R::load( 'book', $idb3 );

		/**
		 * Why do I need to do this ---> why does trash() not set id -> 0?
		 * Because you unset() so trash is done on origin not bean
		 */
		$page1->id = 0;
		$page2->id = 0;
		$page3->id = 0;

		$book3->ownPage[] = $page1;
		$book3->ownPage[] = $page2;
		$book3->ownPage[] = $page3;

		$book3 = R::load( 'book', R::store( $book3 ) );

		asrt( intval( R::getCell( "select count(*) from page where book_id = $idb3 " ) ), 3 );

		asrt( count( $book3->ownPage ), 3 );

		unset( $book3->ownPage[$page2->id] );

		$book3->ownPage[]                  = $page3;
		$book3->ownPage['try_to_trick_ya'] = $page3;

		$book3 = R::load( 'book', R::store( $book3 ) );

		asrt( intval( R::getCell( "select count(*) from page where book_id = $idb3 " ) ), 2 );

		asrt( count( $book3->ownPage ), 2 );

		// Delete and re-add
		$book3 = R::load( 'book', $idb3 );

		unset( $book3->ownPage[10] );

		$book3->ownPage[] = $page1;

		$book3 = R::load( 'book', R::store( $book3 ) );

		asrt( count( $book3->ownPage ), 2 );

		$book3 = R::load( 'book', $idb3 );

		unset( $book3->sharedTopic[1] );

		$book3->sharedTopic[] = $topic1;

		$book3 = R::load( 'book', R::store( $book3 ) );

		asrt( count( $book3->sharedTopic ), 1 );

		// Test performance
		$logger = R::debug( true, 1 );

		$book = R::load( 'book', 1 );

		$book->sharedTopic = array();

		R::store( $book );

		// No more than 1 update
		asrt( count( $logger->grep( 'UPDATE' ) ), 1 );

		$book = R::load( 'book', 1 );

		$logger->clear();

		print_r( $book->sharedTopic, 1 );

		// No more than 1 select
		asrt( count( $logger->grep( 'SELECT' ) ), 1 );

		$logger->clear();

		$book->sharedTopic[] = $topic1;
		$book->sharedTopic[] = $topic2;

		asrt( count( $logger->grep( 'SELECT' ) ), 0 );

		R::store( $book );

		$book->sharedTopic[] = $topic3;

		// Now do NOT clear all and then add one, just add the one
		$logger->clear();

		R::store( $book );

		$book = R::load( 'book', 1 );

		asrt( count( $book->sharedTopic ), 3 );

		// No deletes
		asrt( count( $logger->grep( "DELETE FROM" ) ), 0 );

		$book->sharedTopic['a'] = $topic3;

		unset( $book->sharedTopic['a'] );

		R::store( $book );

		$book = R::load( 'book', 1 );

		asrt( count( $book->sharedTopic ), 3 );

		// No deletes
		asrt( count( $logger->grep( "DELETE FROM" ) ), 0 );

		$book->ownPage = array();

		R::store( $book );

		asrt( count( $book->ownPage ), 0 );

		$book->ownPage[]    = $page1;
		$book->ownPage['a'] = $page2;

		asrt( count( $book->ownPage ), 2 );

		R::store( $book );

		unset( $book->ownPage['a'] );

		asrt( count( $book->ownPage ), 2 );

		unset( $book->ownPage[11] );

		R::store( $book );

		$book = R::load( 'book', 1 );

		asrt( count( $book->ownPage ), 1 );

		$aPage = $book->ownPage[10];

		unset( $book->ownPage[10] );

		$aPage->title .= ' changed ';

		$book->ownPage['anotherPage'] = $aPage;

		$logger->clear();

		R::store( $book );

		// if ($db=="mysql") asrt(count($logger->grep("SELECT")),0);
		$book = R::load( 'book', 1 );

		asrt( count( $book->ownPage ), 1 );

		$ap = reset( $book->ownPage );

		asrt( $ap->title, "pagina1 changed " );

		// Fix udiff instead of diff
		$book3->ownPage = array( $page3, $page1 );

		$i = R::store( $book3 );

		$book3 = R::load( 'book', $i );

		asrt( intval( R::getCell( "select count(*) from page where book_id = $idb3 " ) ), 2 );
		asrt( count( $book3->ownPage ), 2 );

		$pic1->name = 'aaa';
		$pic2->name = 'bbb';

		R::store( $pic1 );
		R::store( $q1 );

		$book3->ownPicture[] = $pic1;
		$book3->ownQuote[]   = $q1;

		$book3 = R::load( 'book', R::store( $book3 ) );

		// two own-arrays -->forgot array_merge
		asrt( count( $book3->ownPicture ), 1 );
		asrt( count( $book3->ownQuote ), 1 );
		asrt( count( $book3->ownPage ), 2 );

		$book3 = R::load( 'book', R::store( $book3 ) );

		unset( $book3->ownPicture[1] );

		$book3 = R::load( 'book', R::store( $book3 ) );

		asrt( count( $book3->ownPicture ), 0 );
		asrt( count( $book3->ownQuote ), 1 );
		asrt( count( $book3->ownPage ), 2 );

		$book3 = R::load( 'book', R::store( $book3 ) );

		$NOTE = 0;

		$quotes = R::dispense( 'quote', 10 );

		foreach ( $quotes as &$justSomeQuote ) {
			$justSomeQuote->note = 'note' . ( ++$NOTE );
		}

		$pictures = R::dispense( 'picture', 10 );
		foreach ( $pictures as &$justSomePic ) {
			$justSomePic->note = 'note' . ( ++$NOTE );
		}

		$topics = R::dispense( 'topic', 10 );
		foreach ( $topics as &$justSomeTopic ) {
			$justSomeTopic->note = 'note' . ( ++$NOTE );
		}

		for ( $j = 0; $j < 10; $j++ ) {
			// Do several mutations
			for ( $x = 0; $x < rand( 1, 20 ); $x++ ) {
				modgr( $book3, $quotes, $pictures, $topics );
			}

			$qbefore = count( $book3->ownQuote );
			$pbefore = count( $book3->ownPicture );
			$tbefore = count( $book3->sharedTopic );

			$qjson = json_encode( $book->ownQuote );
			$pjson = json_encode( $book->ownPicture );
			$tjson = json_encode( $book->sharedTopic );

			$book3 = R::load( 'book', R::store( $book3 ) );

			asrt( count( $book3->ownQuote ), $qbefore );
			asrt( count( $book3->ownPicture ), $pbefore );
			asrt( count( $book3->sharedTopic ), $tbefore );

			asrt( json_encode( $book->ownQuote ), $qjson );
			asrt( json_encode( $book->ownPicture ), $pjson );
			asrt( json_encode( $book->sharedTopic ), $tjson );

			testids( $book->ownQuote );
			testids( $book->ownPicture );
			testids( $book->sharedTopic );
		}
	}

	/**
	 * Test parent bean relations.
	 *
	 * @return void
	 */
	public function testParentBean()
	{
		$village = R::dispense( 'village' );

		$village->name = 'village';

		$home = R::dispense( 'building' );

		$home->village = $village;

		$id = R::store( $home );

		$home = R::load( 'building', $id );

		asrt( $home->village->name, 'village' );
		asrt( R::count( 'village' ), 1 );
		asrt( R::count( 'building' ), 1 );

		R::trash( $home );

		pass();

		asrt( R::count( 'village' ), 1 );
		asrt( R::count( 'building' ), 0 );
	}

	/**
	 * test N-M relations through intermediate beans
	 *
	 * @return void
	 */
	public function testNMRelationsIntermediate()
	{
		list( $mrA, $mrB, $mrC ) = R::dispense( 'person', 3 );
		list( $projA, $projB, $projC ) = R::dispense( 'project', 3 );

		$projA->title = 'A';
		$projB->title = 'B';
		$projC->title = 'C';

		$participant = R::dispense( 'participant' );

		$projA->link( 'participant', array( 'role' => 'manager' ) )->person                  = $mrA;
		$projA->link( $participant->setAttr( 'role', 'developer' ) )->person                 = $mrB;
		$projB->link( R::dispense( 'participant' )->setAttr( 'role', 'developer' ) )->person = $mrB;
		$projB->link( 'participant', '{"role":"helpdesk"}' )->person                         = $mrC;
		$projC->link( 'participant', '{"role":"sales"}' )->person                            = $mrC;

		R::storeAll( array( $projA, $projB, $projC ) );

		$a = R::findOne( 'project', ' title = ? ', array( 'A' ) );
		$b = R::findOne( 'project', ' title = ? ', array( 'B' ) );
		$c = R::findOne( 'project', ' title = ? ', array( 'C' ) );

		asrt( count( $a->ownParticipant ), 2 );
		asrt( count( $b->ownParticipant ), 2 );
		asrt( count( $c->ownParticipant ), 1 );

		$managers = $developers = 0;

		foreach ( $a->ownParticipant as $p ) {
			if ( $p->role === 'manager' ) {
				$managers++;
			}
			if ( $p->role === 'developer' ) {
				$developers++;
			}
		}
		$p = reset( $a->ownParticipant );

		asrt( $p->person->getMeta( 'type' ), 'person' );

		asrt( ( $p->person->id > 0 ), TRUE );

		asrt( $managers, 1 );
		asrt( $developers, 1 );

		asrt( (int) R::count( 'participant' ), 5 );
		asrt( (int) R::count( 'person' ), 3 );
	}

	/**
	 * test emulation of sharedList through intermediate beans
	 *
	 * @return void
	 */
	public function testSharedListIntermediate()
	{
		list( $v1, $v2, $v3 ) = R::dispense( 'village', 3 );
		list( $a1, $a2, $a3 ) = R::dispense( 'army', 3 );

		$a1->name = 'one';
		$a2->name = 'two';
		$a3->name = 'three';

		$v1->name = 'Ville 1';
		$v2->name = 'Ville 2';
		$v3->name = 'Ville 3';


		$v1->link( 'armyVillage' )->army    = $a3;
		$v2->link( 'army_village' )->army    = $a2;
		$v3->link( 'armyVillage' )->army    = $a1;
		$a2->link( 'army_village' )->village = $v1;

		$id1 = R::store( $v1 );
		$id2 = R::store( $v2 );
		$id3 = R::store( $v3 );

		$village1 = R::load( 'village', $id1 );
		$village2 = R::load( 'village', $id2 );
		$village3 = R::load( 'village', $id3 );

		asrt( count( $village1->sharedArmy ), 2 );
		asrt( count( $village2->sharedArmy ), 1 );
		asrt( count( $village3->sharedArmy ), 1 );
	}

	/**
	 * test emulation via association renaming
	 *
	 * @return void
	 */
	public function testAssociationRenaming()
	{
		list( $p1, $p2, $p3 ) = R::dispense( 'painting', 3 );

		list( $m1, $m2, $m3 ) = R::dispense( 'museum', 3 );

		$p1->name = 'painting1';
		$p2->name = 'painting2';
		$p3->name = 'painting3';

		$m1->thename = 'a';
		$m2->thename = 'b';
		$m3->thename = 'c';

		R::renameAssociation( 'museum_painting', 'exhibited' );

		// Also test array syntax
		R::renameAssociation( array( 'museum_museum' => 'center' ) );

		$m1->link( 'center', array( 'name' => 'History Center' ) )->museum2            = $m2;
		$m1->link( 'exhibited', '{"from":"2014-02-01","til":"2014-07-02"}' )->painting = $p3;
		$m2->link( 'exhibited', '{"from":"2014-07-03","til":"2014-10-02"}' )->painting = $p3;
		$m3->link( 'exhibited', '{"from":"2014-02-01","til":"2014-07-02"}' )->painting = $p1;
		$m2->link( 'exhibited', '{"from":"2014-02-01","til":"2014-07-02"}' )->painting = $p2;

		R::storeAll( array( $m1, $m2, $m3 ) );

		list( $m1, $m2, $m3 ) = array_values( R::findAll( 'museum', ' ORDER BY thename ASC' ) );

		asrt( count( $m1->sharedMuseum ), 1 );
		asrt( count( $m1->sharedPainting ), 1 );
		asrt( count( $m2->sharedPainting ), 2 );
		asrt( count( $m3->sharedPainting ), 1 );

		$p3 = reset( $m1->sharedPainting );

		asrt( count( $p3->ownExhibited ), 2 );
		asrt( count( $m2->ownExhibited ), 2 );

		R::storeAll( array( $m1, $m2, $m3 ) );

		list( $m1, $m2, $m3 ) = array_values( R::findAll( 'museum', ' ORDER BY thename ASC' ) );

		asrt( count( $m1->sharedPainting ), 1 );
		asrt( count( $m2->sharedPainting ), 2 );
		asrt( count( $m3->sharedPainting ), 1 );

		$p3 = reset( $m1->sharedPainting );

		asrt( count( $p3->ownExhibited ), 2 );

		$paintings = $m2->sharedPainting;

		foreach ( $paintings as $painting ) {
			if ( $painting->name === 'painting2' ) {
				pass();
				$paintingX = $painting;
			}
		}

		unset( $m2->sharedPainting[$paintingX->id] );

		R::store( $m2 );

		$m2 = R::load( 'museum', $m2->id );

		asrt( count( $m2->sharedPainting ), 1 );

		$left = reset( $m2->sharedPainting );

		asrt( $left->name, 'painting3' );
		asrt( count( $m2->ownExhibited ), 1 );

		$exhibition = reset( $m2->ownExhibited );

		asrt( $exhibition->from, '2014-07-03' );
		asrt( $exhibition->til, '2014-10-02' );
	}

	/**
	 * Test don't try to store other things in shared list.
	 *
	 * @return void
	 */
	public function testDontTryToStoreOtherThingsInSharedList() {

		$book = R::dispense( 'book' );
		$book->sharedPage[] = 'nonsense';

		try {
			R::store( $book );
			fail();
		} catch( RedException $exception) {
			pass();
		}

		$book->sharedPageList = R::dispense( 'page', 2 );
		R::store( $book );
		$book->sharedPageList;
		R::trash( $book );
		asrt( R::count('page'), 2 );
	}

	/**
	 * Test whether magic array interface functions like isset() and
	 * unset work correctly with the x-own-list and the List-suffix.
	 *
	 * Array functions do not reveal x-own-lists and list-alias because
	 * you dont want duplicate entries in foreach-loops.
	 * Also offers a slight performance improvement for array access.
	 *
	 * @return void
	 */
	public function testWhetherIssetWorksWithXList()
	{
		R::nuke();
		$book = R::dispense( 'book' );
		$page = R::dispense( 'page' );

		asrt( isset( $book->xownPageList ), FALSE );
		asrt( isset( $book->ownPageList ), FALSE );
		asrt( isset( $book->xownPage ), FALSE );
		asrt( isset( $book->ownPage ), FALSE );

		$book->xownPageList[] = $page;

		asrt( isset( $book->xownPageList ), TRUE );
		asrt( isset( $book->ownPageList ), TRUE );
		asrt( isset( $book->xownPage ), TRUE );
		asrt( isset( $book->ownPage ), TRUE );

		//Test array access
		asrt( isset( $book['xownPageList'] ), TRUE );
		asrt( isset( $book['ownPageList'] ), TRUE );
		asrt( isset( $book['xownPage'] ), TRUE );
		asrt( isset( $book['ownPage'] ), TRUE );

		R::store( $book );
		$book = $book->fresh();

		asrt( isset( $book->xownPageList ), FALSE );
		asrt( isset( $book->ownPageList ), FALSE );
		asrt( isset( $book->xownPage ), FALSE );
		asrt( isset( $book->ownPage ), FALSE );

		asrt( isset( $book['xownPageList'] ), FALSE );
		asrt( isset( $book['ownPageList'] ), FALSE );
		asrt( isset( $book['xownPage'] ), FALSE );
		asrt( isset( $book['ownPage'] ), FALSE );

		$book->xownPageList;

		asrt( isset( $book->xownPageList ), TRUE );
		asrt( isset( $book->ownPageList ), TRUE );
		asrt( isset( $book->xownPage ), TRUE );
		asrt( isset( $book->ownPage ), TRUE );

		asrt( isset( $book['xownPageList'] ), TRUE );
		asrt( isset( $book['ownPageList'] ), TRUE );
		asrt( isset( $book['xownPage'] ), TRUE );
		asrt( isset( $book['ownPage'] ), TRUE );

		$book = $book->fresh();

		asrt( isset( $book->xownPageList ), FALSE );
		asrt( isset( $book->ownPageList ), FALSE );
		asrt( isset( $book->xownPage ), FALSE );
		asrt( isset( $book->ownPage ), FALSE );

		asrt( isset( $book['xownPageList'] ), FALSE );
		asrt( isset( $book['ownPageList'] ), FALSE );
		asrt( isset( $book['xownPage'] ), FALSE );
		asrt( isset( $book['ownPage'] ), FALSE );

		$book->noLoad()->xownPageList;

		asrt( isset( $book->xownPageList ), TRUE );
		asrt( isset( $book->ownPageList ), TRUE );

		//but empty
		asrt( count( $book->ownPageList ), 0 );
		asrt( count( $book->xownPageList ), 0 );
		asrt( count( $book->ownPage ), 0 );
		asrt( count( $book->xownPage ), 0 );

		$book->xownPageList[] = $page;
		asrt( isset( $book->xownPageList ), TRUE );
		asrt( isset( $book->ownPageList ), TRUE );
		asrt( isset( $book->xownPage ), TRUE );
		asrt( isset( $book->ownPage ), TRUE );

		asrt( isset( $book['xownPageList'] ), TRUE );
		asrt( isset( $book['ownPageList'] ), TRUE );
		asrt( isset( $book['xownPage'] ), TRUE );
		asrt( isset( $book['ownPage'] ), TRUE );

		unset( $book->xownPageList );

		asrt( isset( $book->xownPageList ), FALSE );
		asrt( isset( $book->ownPageList ), FALSE );
		asrt( isset( $book->xownPage ), FALSE );
		asrt( isset( $book->ownPage ), FALSE );

		asrt( isset( $book['xownPageList'] ), FALSE );
		asrt( isset( $book['ownPageList'] ), FALSE );
		asrt( isset( $book['xownPage'] ), FALSE );
		asrt( isset( $book['ownPage'] ), FALSE );

		$book->xownPageList[] = $page;

		asrt( isset( $book->xownPageList ), TRUE );
		asrt( isset( $book->ownPageList ), TRUE );
		asrt( isset( $book->xownPage ), TRUE );
		asrt( isset( $book->ownPage ), TRUE );

		asrt( isset( $book['xownPageList'] ), TRUE );
		asrt( isset( $book['ownPageList'] ), TRUE );
		asrt( isset( $book['xownPage'] ), TRUE );
		asrt( isset( $book['ownPage'] ), TRUE );

		unset( $book->xownPage );

		asrt( isset( $book->xownPageList ), FALSE );
		asrt( isset( $book->ownPageList ), FALSE );
		asrt( isset( $book->xownPage ), FALSE );
		asrt( isset( $book->ownPage ), FALSE );

		asrt( isset( $book['xownPageList'] ), FALSE );
		asrt( isset( $book['ownPageList'] ), FALSE );
		asrt( isset( $book['xownPage'] ), FALSE );
		asrt( isset( $book['ownPage'] ), FALSE );

		$book = $book->fresh();
		asrt( isset( $book->xownPageList ), FALSE );
		asrt( isset( $book->ownPageList ), FALSE );
		asrt( isset( $book->xownPage ), FALSE );
		asrt( isset( $book->ownPage ), FALSE );

		asrt( isset( $book['xownPageList'] ), FALSE );
		asrt( isset( $book['ownPageList'] ), FALSE );
		asrt( isset( $book['xownPage'] ), FALSE );
		asrt( isset( $book['ownPage'] ), FALSE );

		$book->ownPageList;
		asrt( isset( $book->xownPageList ), TRUE );
		asrt( isset( $book->ownPageList ), TRUE );
		asrt( isset( $book->xownPage ), TRUE );
		asrt( isset( $book->ownPage ), TRUE );

		asrt( isset( $book['xownPageList'] ), TRUE );
		asrt( isset( $book['ownPageList'] ), TRUE );
		asrt( isset( $book['xownPage'] ), TRUE );
		asrt( isset( $book['ownPage'] ), TRUE );
	}

	/**
	 * Test whether you can still set items starting with 'xown' or
	 * 'own' not followed by an uppercase character.
	 *
	 * @return void
	 */
	public function testConfusionWithXOwnList()
	{
		$book = R::dispense( 'book' );
		$book->xownitem = 1;
		asrt( isset( $book->xownitem ), TRUE );
		asrt( (int) $book->xownitem, 1 );
		asrt( isset( $book->xownItem ), FALSE );
		asrt( isset( $book->xownItemList ), FALSE );
		$book->ownitem = 1;
		asrt( isset( $book->ownitem ), TRUE );
		asrt( (int) $book->ownitem, 1 );
		asrt( isset( $book->ownItemList ), FALSE );
		R::store( $book );
		$book = $book->fresh();
		asrt( isset( $book->xownitem ), TRUE );
		asrt( (int) $book->xownitem, 1 );
		asrt( isset( $book->xownItem ), FALSE );
		asrt( isset( $book->xownItemList ), FALSE );
		asrt( isset( $book->ownitem ), TRUE );
		asrt( (int) $book->ownitem, 1 );
		asrt( isset( $book->ownItemList ), FALSE );
	}

	/**
	 * Test whether we can determine the mode of a list.
	 *
	 * @return void
	 */
	public function testModeCheckerOfLists()
	{
		foreach( array( 'ownPage', 'xownPage', 'ownPageList', 'xownPageList' ) as $listName ) {
			$book = R::dispense( 'book' );
			asrt( $book->isListInExclusiveMode( $listName ), FALSE );
			$book->ownPageList[] = R::dispense( 'page' );
			asrt( $book->isListInExclusiveMode( $listName ), FALSE );

			$book = R::dispense( 'book' );
			asrt( $book->isListInExclusiveMode( $listName ), FALSE );
			$book->xownPageList[] = R::dispense( 'page' );
			asrt( $book->isListInExclusiveMode( $listName ), TRUE );

			$book = R::dispense( 'book' );
			asrt( $book->isListInExclusiveMode( $listName ), FALSE );
			$book->ownPage[] = R::dispense( 'page' );
			asrt( $book->isListInExclusiveMode( $listName ), FALSE );

			$book = R::dispense( 'book' );
			asrt( $book->isListInExclusiveMode( $listName ), FALSE );
			$book->xownPage[] = R::dispense( 'page' );
			asrt( $book->isListInExclusiveMode( $listName ), TRUE );
		}
	}
}
