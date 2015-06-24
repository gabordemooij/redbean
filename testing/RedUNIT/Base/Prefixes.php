<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;
use RedBeanPHP\OODBBean as OODBBean;
use RedBeanPHP\QueryWriter\AQueryWriter as AQueryWriter;

R::ext('xdispense', function($type){
	return R::getRedBean()->dispense($type);
});

define('BOOK', 'tbl_book');
define('AUTHOR', 'tbl_author');
define('COAUTHOR', 'coAuthor');
define('FRIEND', 'tbl_friend');
define('PUBLISHER', 'tbl_publisher');
define('BOOKLIST', 'ownTbl_book');
define('FRIENDLIST', 'sharedTbl_friend');

/**
 * Prefixes
 *
 * @file    RedUNIT/Base/Prefixes.php
 * @desc    Tests whether you can use RedBeanPHP with table prefixes.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Prefixes extends Base
{
	/**
	 * Test prerequisites.
	 */
	public function testPrerequisites()
	{
		R::nuke();
		$bean = R::xdispense( 'type_with_underscore' );
		asrt( ( $bean instanceof OODBBean ), TRUE );
		asrt( constant( 'BOOK' ), 'tbl_book' );
		asrt( constant( 'AUTHOR' ), 'tbl_author' );
		asrt( constant( 'PUBLISHER' ), 'tbl_publisher' );
		asrt( constant( 'FRIEND' ), 'tbl_friend' );
		asrt( constant( 'BOOKLIST' ), 'ownTbl_book' );
		asrt( constant( 'FRIENDLIST' ), 'sharedTbl_friend' );
		asrt( constant( 'COAUTHOR' ), 'coAuthor' );
	}

	/**
	 * Test basic CRUD operations.
	 */
	public function testBasicOperations()
	{
		//Can we dispense a naughty bean? (with underscore)
		$author = R::xdispense( AUTHOR );
		asrt( ( $author instanceof OODBBean ), TRUE );
		asrt( $author->getMeta('type'), AUTHOR );
		$author->name = 'Mr. Quill';
		$book = R::xdispense( BOOK );
		asrt( ( $book instanceof OODBBean ), TRUE );
		asrt( $book->getMeta('type'), BOOK );
		$book->title = 'Good Stories';
		$friend = R::xdispense( FRIEND );
		$friend->name = 'Muse';
		asrt( ( $friend instanceof OODBBean ), TRUE );
		asrt( $friend->getMeta('type'), FRIEND );
		$publisher = R::xdispense( PUBLISHER );
		$publisher->name = 'Good Books';
		asrt( ( $publisher instanceof OODBBean ), TRUE );
		asrt( $publisher->getMeta('type'), PUBLISHER );
		asrt( is_array( $author->{BOOKLIST} ), TRUE );
		//add books to the book list using the constant
		$author->{BOOKLIST}[] = $book;
		asrt( count( $author->{BOOKLIST} ), 1 );
		//can we also add friends? (N-M)
		$author->{FRIENDLIST}[] = $friend;
		$author->{PUBLISHER} = $publisher;
		$id = R::store( $author );
		asrt( ( $id > 0 ), TRUE );
		$author = $author->fresh();
		//Can we add another friend after reload?
		$author->{FRIENDLIST}[] = R::xdispense( FRIEND )->setAttr( 'name', 'buddy' );
		R::store($author);
		$author = $author->fresh();
		//Now check the contents of the bean, its lists (books,friends) and parent (publisher)
		asrt( $author->name, 'Mr. Quill' );
		asrt( count( $author->{BOOKLIST} ), 1 );
		$firstBook = reset( $author->{BOOKLIST} );
		asrt( $firstBook->title, 'Good Stories' );
		asrt( count( $author->{FRIENDLIST} ), 2 );
		$firstFriend = reset( $author->{FRIENDLIST} );
		$parent = $author->{PUBLISHER};
		asrt( ( $parent instanceof OODBBean ), TRUE );
		$tables = R::inspect();
		//have all tables been prefixed?
		foreach( $tables as $table ) asrt( strpos( $table, 'tbl_' ), 0 );
		//Can we make an export?
		$export = R::exportAll( R::findOne( AUTHOR ), TRUE );
		$export = reset( $export );
		asrt( isset( $export[ PUBLISHER ] ), TRUE );
		asrt( isset( $export[ BOOKLIST ] ), TRUE );
		asrt( isset( $export[ FRIENDLIST ] ), TRUE );
		asrt( isset( $export[ 'ownBook' ] ), FALSE );
		asrt( isset( $export[ 'sharedFriend' ] ), FALSE );
		asrt( isset( $export[ 'publisher' ] ), FALSE );
		//Can we duplicate?
		$copy = R::dup( $author );
		$copy->name = 'Mr. Clone';
		R::store( $copy );
		$copy = $copy->fresh();
		asrt( $copy->name, 'Mr. Clone' );
		asrt( count( $copy->{BOOKLIST} ), 1 );
		$firstBook = reset( $copy->{BOOKLIST} );
		asrt( $firstBook->title, 'Good Stories' );
		asrt( count( $copy->{FRIENDLIST} ), 2 );
		$firstFriend = reset( $copy->{FRIENDLIST} );
		$parent = $copy->{PUBLISHER};
		asrt( ( $parent instanceof OODBBean ), TRUE );
		//Can we count?
		asrt( R::count( AUTHOR ), 2 );
		$copy = $copy->fresh();
		asrt( $copy->countOwn( BOOK ), 1 );
		asrt( $copy->countShared( FRIEND ), 2 );
		//Can we delete?
		R::trash( $author );
		asrt( R::count( AUTHOR ), 1 );
		//Can we nuke?
		R::nuke();
		asrt( R::count( AUTHOR ), 0 );
		asrt( count( R::inspect() ), 0 );
	}

	/**
	 * Test basic operations in frozen mode.
	 */
	public function testBasicOperationsFrozen()
	{
		R::nuke();
		$author = R::xdispense( AUTHOR );
		$author->name = 'Mr. Quill';
		$book = R::xdispense( BOOK );
		$book->title = 'Good Stories';
		$book2 = R::xdispense( BOOK );
		$book2->title = 'Good Stories 2';
		$friend = R::xdispense( FRIEND );
		$friend->name = 'Muse';
		$publisher = R::xdispense( PUBLISHER );
		$publisher->name = 'Good Books';
		$author->{BOOKLIST} = array( $book, $book2 );
		$author->{FRIENDLIST}[] = $friend;
		$author->{PUBLISHER} = $publisher;
		$coAuthor = R::xdispense( AUTHOR );
		$coAuthor->name = 'Xavier';
		$book2->{COAUTHOR} = $coAuthor;
		R::store( $author );
		R::freeze( TRUE );
		asrt( $author->name, 'Mr. Quill' );
		asrt( count( $author->{BOOKLIST} ), 2 );
		$firstBook = reset( $author->{BOOKLIST} );
		asrt( $firstBook->title, 'Good Stories' );
		asrt( count( $author->{FRIENDLIST} ), 1 );
		$firstFriend = reset( $author->{FRIENDLIST} );
		$parent = $author->{PUBLISHER};
		asrt( ( $parent instanceof OODBBean ), TRUE );
		$tables = R::inspect();
		//have all tables been prefixed?
		foreach( $tables as $table ) asrt( strpos( $table, 'tbl_' ), 0 );
		//Can we make an export?
		$export = R::exportAll( R::findOne( AUTHOR ), TRUE );
		$export = reset( $export );
		asrt( isset( $export[ PUBLISHER ] ), TRUE );
		asrt( isset( $export[ BOOKLIST ] ), TRUE );
		asrt( isset( $export[ FRIENDLIST ] ), TRUE );
		asrt( isset( $export[ 'ownBook' ] ), FALSE );
		asrt( isset( $export[ 'sharedFriend' ] ), FALSE );
		asrt( isset( $export[ 'publisher' ] ), FALSE );
		R::freeze( FALSE );
	}

	/**
	 * Test conditions and aliases.
	 */
	public function testConditionsAndAliases()
	{
		R::nuke();
		$author = R::xdispense( AUTHOR );
		$author->name = 'Mr. Quill';
		$book = R::xdispense( BOOK );
		$book->title = 'Good Stories';
		$book2 = R::xdispense( BOOK );
		$book2->title = 'Good Stories 2';
		$friend = R::xdispense( FRIEND );
		$friend->name = 'Muse';
		$publisher = R::xdispense( PUBLISHER );
		$publisher->name = 'Good Books';
		$author->{BOOKLIST} = array( $book, $book2 );
		$author->{FRIENDLIST}[] = $friend;
		$author->{PUBLISHER} = $publisher;
		$coAuthor = R::xdispense( AUTHOR );
		$coAuthor->name = 'Xavier';
		$book2->{COAUTHOR} = $coAuthor;
		R::store( $author );
		$author = $author->fresh();
		asrt( R::count( AUTHOR ), 2 );
		//Can we use with and withCondition?
		asrt( count( $author->{BOOKLIST} ), 2 );
		asrt( count( $author->with(' LIMIT 1 ')->{BOOKLIST} ), 1 );
		asrt( count( $author->withCondition(' title LIKE ? ', array( '%2%' ) )->{BOOKLIST} ), 1 );
		//Can we use an alias?
		$book2 = $book2->fresh();
		asrt( $book2->fetchAs( AUTHOR )->{COAUTHOR}->name, 'Xavier' );
		$coAuthor = $book2->fetchAs( AUTHOR )->{COAUTHOR}->fresh();
		asrt( count( $coAuthor->alias( COAUTHOR )->{BOOKLIST} ), 1 );
	}

	/**
	 * Test prettier tables using via().
	 */
	public function testViaPrettification()
	{
		R::nuke();
		R::renameAssociation( 'tbl_author_tbl_friend', 'tbl_author_friend' );
		$author = R::xdispense( AUTHOR );
		$author->name = 'Mr. Quill';
		$friend = R::xdispense( FRIEND );
		$friend->name = 'Muse';
		$author->{FRIENDLIST}[] = $friend;
		$id = R::store( $author );
		//print_r(R::inspect()); exit;
		$author = R::load( AUTHOR, $id );
		$tables = array_flip( R::inspect() );
		asrt( isset( $tables[ 'tbl_author_friend' ] ), TRUE );
		asrt( isset( $tables[ 'tbl_author_tbl_friend' ] ), FALSE );
		asrt( count( $author->{FRIENDLIST} ), 1 );
		AQueryWriter::clearRenames();
	}

	/**
	 * Test self-referential N-M relations.
	 */
	public function testSelfRefNM()
	{
		R::nuke();
		$friend1 = R::xdispense( FRIEND );
		$friend1->name = 'f1';
		$friend2 = R::xdispense( FRIEND );
		$friend2->name = 'f2';
		$friend3 = R::xdispense( FRIEND );
		$friend3->name = 'f3';
		$friend1->{FRIENDLIST} = array( $friend2, $friend3 );
		$friend3->{FRIENDLIST} = array( $friend1 );
		R::storeAll( array( $friend1, $friend2, $friend3 ) );
		$friend1 = $friend1->fresh();
		$friend2 = $friend2->fresh();
		$friend3 = $friend3->fresh();
		asrt( count( $friend1->{FRIENDLIST} ), 2 );
		asrt( count( $friend2->{FRIENDLIST} ), 1 );
		asrt( count( $friend3->{FRIENDLIST} ), 1 );
		$friend = reset( $friend3->{FRIENDLIST} );
		asrt( $friend->name, 'f1' );
	}
}
