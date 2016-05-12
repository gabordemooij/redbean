<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;
use RedBeanPHP\DuplicationManager as DuplicationManager;
use RedBeanPHP\OODBBean as OODBBean;
use RedBeanPHP\RedException as RedException;

/**
 * Dup
 *
 * Tests duplication. Like the 'copy' test suite but
 * focuses on more complex scenarios.
 *
 * @file    RedUNIT/Base/Dup.php
 * @desc    Intensive test for dup()
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Dup extends Base
{
	/**
	 * Tests whether the original ID is stored
	 * in meta data (quite handy for ID mappings).
	 */
	public function testKeepOldID()
	{
		R::nuke();
		$book = R::dispense( 'book' );
		$book->xownPageList[] = R::dispense( 'page' );
		R::store( $book );
		$bookID = $book->id;
		$page = reset( $book->xownPageList );
		$pageID = $page->id;
		$book = $book->fresh();
		$copy = R::duplicate( $book );
		asrt( $copy->getMeta( 'sys.dup-from-id' ), $bookID );
		$copyPage = reset( $copy->xownPageList );
		asrt( $copyPage->getMeta( 'sys.dup-from-id' ), $pageID );
	}

	/**
	 * Test export camelCase.
	 *
	 * @return void
	 */
	public function testExportCamelCase()
	{
		R::nuke();
		$book = R::dispense( 'book' );
		$book->isCheap = true;
		$book->hasISBNCode = false;
		$page = R::dispense('page');
		$page->isWrittenWell = true;
		$page->containsInterestingText = true;
		$book->ownPageList[] = $page;
		R::store( $book );
		$book = $book->fresh();
		$export = R::exportAll( $book );

		asrt( isset( $export[0]['id'] ), true );
		asrt( isset( $export[0]['is_cheap'] ), true );
		asrt( isset( $export[0]['has_isbn_code'] ), true );
		asrt( isset( $export[0]['ownPage']['0']['id'] ), true );
		asrt( isset( $export[0]['ownPage']['0']['is_written_well'] ), true );
		asrt( isset( $export[0]['ownPage']['0']['contains_interesting_text'] ), true );
		asrt( isset( $export[0]['ownPage']['0']['book_id'] ), true );

		R::useExportCase( 'camel' );
		$export = R::exportAll( $book );
		asrt( isset( $export[0]['id'] ), true );
		asrt( isset( $export[0]['isCheap'] ), true );
		asrt( isset( $export[0]['hasIsbnCode'] ), true );
		asrt( isset( $export[0]['ownPage']['0']['id'] ), true );
		asrt( isset( $export[0]['ownPage']['0']['isWrittenWell'] ), true );
		asrt( isset( $export[0]['ownPage']['0']['containsInterestingText'] ), true );
		asrt( isset( $export[0]['ownPage']['0']['bookId'] ), true );

		R::useExportCase( 'dolphin' );
		$export = R::exportAll( $book );
		asrt( isset( $export[0]['id'] ), true );
		asrt( isset( $export[0]['isCheap'] ), true );
		asrt( isset( $export[0]['hasIsbnCode'] ), true );
		asrt( isset( $export[0]['ownPage']['0']['id'] ), true );
		asrt( isset( $export[0]['ownPage']['0']['isWrittenWell'] ), true );
		asrt( isset( $export[0]['ownPage']['0']['containsInterestingText'] ), true );
		asrt( isset( $export[0]['ownPage']['0']['bookID'] ), true );

		R::useExportCase( 'default' );
		$export = R::exportAll( $book );
		asrt( isset( $export[0]['id'] ), true );
		asrt( isset( $export[0]['is_cheap'] ), true );
		asrt( isset( $export[0]['has_isbn_code'] ), true );
		asrt( isset( $export[0]['ownPage']['0']['id'] ), true );
		asrt( isset( $export[0]['ownPage']['0']['is_written_well'] ), true );
		asrt( isset( $export[0]['ownPage']['0']['contains_interesting_text'] ), true );
		asrt( isset( $export[0]['ownPage']['0']['book_id'] ), true );

		try {
			R::useExportCase( 'invalid' );
			fail();
		} catch ( RedException $exception ) {
			pass();
		}
	}

	/**
	 * Test whether we can duplicate part of a tree
	 * without infinite loops.
	 *
	 * @return void
	 */
	public function testDupPortionOfATree()
	{
		R::nuke();
		$article = R::dispense( 'article' );
		$article->name = 'article 1';
		list( $article2, $article3 ) = R::dispense( 'article', 2 );
		$article2->name = 'article 2';
		$article3->name = 'article 3';
		list( $article4, $article5 ) = R::dispense( 'article' , 2);
		$article4->name = 'article 4';
		$article5->name = 'article 5';
		list( $article6, $article7 ) = R::dispense( 'article' , 2);
		$article6->name = 'article 6';
		$article7->name = 'article 7';
		$article3->xownArticleList[] = $article7;
		$article4->xownArticleList[] = $article6;
		$article2->xownArticleList = array( $article5, $article4 );
		$article->xownArticleList = array( $article2, $article3 );
		R::store( $article );
		asrt( R::count( 'article' ), 7 );
		$article2 = $article2->fresh();
		$dupArticle2 = R::duplicate( $article2 );
		$dupArticle2->name = 'article 2b';
		$dupBeans = $dupArticle2->xownArticleList;
		foreach( $dupBeans as $dupBean ) {
			$list[] = $dupBean->name;
		}
		sort( $list );
		$listStr = implode( ',', $list );
		asrt( $listStr, 'article 4,article 5' );
		foreach( $dupBeans as $dupBean ) {
			if ( $dupBean->name === 'article 4' ) {
				$dup4 = $dupBean;
			}
		}
		asrt( isset( $dup4 ), TRUE );
		$dupBeans = $dup4->xownArticleList;
		foreach( $dupBeans as $dupBean ) {
			asrt( $dupBean->name, 'article 6' );
		}

		//so we have extracted part of the tree, can we store it?
		$id = R::store( $dupArticle2 );
		asrt( ( $id > 0 ), TRUE );
		asrt( R::count( 'article' ), 11 );

		$originalArticle = $article->fresh();
		asrt( $originalArticle->name, 'article 1' );

		$subArticles = $originalArticle->xownArticleList;
		$list = array();
		foreach( $subArticles as $subArticle ) {
			$list[] = $subArticle->name;
		}
		sort( $list );
		$listStr = implode( ',', $list );
		asrt( $listStr, 'article 2,article 2b,article 3' );

		foreach( $subArticles as $subArticle ) {
			if ( $subArticle->name === 'article 2' ) {
				$sub2 = $subArticle;
			}
			if ( $subArticle->name === 'article 3' ) {
				$sub3 = $subArticle;
			}
		}

		$subArticles = $sub2->xownArticleList;
		$list = array();
		foreach( $subArticles as $subArticle ) {
			$list[] = $subArticle->name;
		}
		sort( $list );
		$listStr = implode( ',', $list );
		asrt( $listStr, 'article 4,article 5' );

		$subArticles = $sub3->xownArticleList;
		$list = array();
		foreach( $subArticles as $subArticle ) {
			$list[] = $subArticle->name;
		}
		sort( $list );
		$listStr = implode( ',', $list );
		asrt( $listStr, 'article 7' );

		$subArticles = $sub2->xownArticleList;
		foreach( $subArticles as $subArticle ) {
			if ( $subArticle->name === 'article 4' ) {
				$sub4 = $subArticle;
			}
			if ( $subArticle->name === 'article 5' ) {
				$sub5 = $subArticle;
			}
		}

		asrt( count( $sub4->xownArticleList ), 1 );
		$subBeans = $sub4->xownArticleList;
		$subBean = reset( $subBeans );
		asrt( $subBean->name, 'article 6');

		asrt( count( $sub5->xownArticleList ), 0 );

		$dupArticle2 = $dupArticle2->fresh();
		$subArticles = $dupArticle2->xownArticleList;
		$list = array();
		foreach( $subArticles as $subArticle ) {
			$list[] = $subArticle->name;
		}
		sort( $list );
		$listStr = implode( ',', $list );
		asrt( $listStr, 'article 4,article 5' );

		foreach( $subArticles as $subArticle ) {
			if ( $subArticle->name === 'article 4' ) {
				$sub4 = $subArticle;
			}
			if ( $subArticle->name === 'article 5' ) {
				$sub5 = $subArticle;
			}
		}

		asrt( count( $sub4->xownArticleList ), 1 );
		$subBeans = $sub4->xownArticleList;
		$subBean = reset( $subBeans );
		asrt( $subBean->name, 'article 6');

		asrt( count( $sub5->xownArticleList ), 0 );
	}

	/**
	 * Test exportAll and caching.
	 *
	 * @return void
	 */
	public function testExportAllAndCache()
	{
		testpack( 'exportAll() and Cache' );

		$can = R::dispense( 'can' )->setAttr( 'size', 3 );

		$can->ownCoffee[] = R::dispense( 'coffee' )->setAttr( 'color', 'black' );
		$can->sharedTag[] = R::dispense( 'tag' )->setAttr( 'name', 'cool' );

		$id = R::store( $can );

		R::debug( TRUE );

		ob_start();

		$can = R::load( 'can', $id );

		$cache = $this->getCache();

		$data = R::exportAll( array( $can ), TRUE );

		$queries = ob_get_contents();

		R::debug( FALSE );
		ob_end_clean();
		$len1 = strlen( $queries );

		$can              = R::dispense( 'can' )->setAttr( 'size', 3 );
		$can->ownCoffee[] = R::dispense( 'coffee' )->setAttr( 'color', 'black' );
		$can->sharedTag[] = R::dispense( 'tag' )->setAttr( 'name', 'cool' );

		$id = R::store( $can );

		R::debug( TRUE );

		ob_start();

		$can = R::load( 'can', $id );

		$cache = $this->getCache();

		$data = R::exportAll( array( $can ), TRUE );

		$queries = ob_get_contents();

		R::debug( FALSE );

		ob_end_clean();

		$len2 = strlen( $queries );

		asrt( ( $len1 ), ( $len2 ) );

		$can = R::dispense( 'can' )->setAttr( 'size', 3 );

		$can->ownCoffee[] = R::dispense( 'coffee' )->setAttr( 'color', 'black' );
		$can->sharedTag[] = R::dispense( 'tag' )->setAttr( 'name', 'cool' );

		$id = R::store( $can );

		R::debug( TRUE );

		ob_start();

		$can = R::load( 'can', $id );

		$cache = $this->getCache();

		R::getDuplicationManager()->setTables( $cache );

		$data = R::exportAll( array( $can ), TRUE );

		$queries = ob_get_contents();

		R::debug( FALSE );

		ob_end_clean();

		$len3 = strlen( $queries );

		asrt( ( ( $len3 ) < ( $len2 ) ), TRUE );
		asrt( count( $data ), 1 );
		asrt( $data[0]['ownCoffee'][0]['color'], 'black' );

		R::getDuplicationManager()->setCacheTables( FALSE );
	}

	/**
	 * Test duplication and caching.
	 *
	 * @return void
	 */
	public function DupAndCache()
	{
		testpack( 'Dup() and Cache' );

		$can = R::dispense( 'can' )->setAttr( 'size', 3 );

		$can->ownCoffee[] = R::dispense( 'coffee' )->setAttr( 'color', 'black' );
		$can->sharedTag[] = R::dispense( 'tag' )->setAttr( 'name', 'cool' );

		$can = R::load( 'can', R::store( $can ) );

		$d = new DuplicationManager( R::getToolBox() );

		$d->setCacheTables( TRUE );

		ob_start();

		R::debug( 1 );

		$x = $d->dup( $can );

		$queries = ob_get_contents();

		R::debug( 0 );

		ob_end_clean();

		$len1 = strlen( $queries );

		asrt( ( $len1 > 40 ), TRUE );
		asrt( isset( $x->ownCoffee ), TRUE );
		asrt( count( $x->ownCoffee ), 1 );
		asrt( isset( $x->sharedTag ), TRUE );
		asrt( count( $x->sharedTag ), 1 );

		$cache = $d->getSchema();

		R::nuke();

		$can = R::dispense( 'can' )->setAttr( 'size', 3 );

		$can->ownCoffee[] = R::dispense( 'coffee' )->setAttr( 'color', 'black' );
		$can->sharedTag[] = R::dispense( 'tag' )->setAttr( 'name', 'cool' );

		$can = R::load( 'can', R::store( $can ) );

		$d = new DuplicationManager( R::getToolBox() );

		/**
		 * $cache = '{"book": {
		 *  "id": "INTEGER",
		 *  "title": "TEXT"
		 * }, "bean": {
		 *  "id": "INTEGER",
		 *  "prop": "INTEGER"
		 * }, "pessoa": {
		 *  "id": "INTEGER",
		 *  "nome": "TEXT",
		 *  "nome_meio": "TEXT",
		 *  "sobrenome": "TEXT",
		 *  "nascimento": "NUMERIC",
		 *  "reg_owner": "TEXT"
		 * }, "documento": {
		 *  "id": "INTEGER",
		 *  "nome_documento": "TEXT",
		 *  "numero_documento": "TEXT",
		 *  "reg_owner": "TEXT",
		 *  "ownPessoa_id": "INTEGER"
		 * }, "can": {
		 *  "id": "INTEGER",
		 *  "size": "INTEGER"
		 * }, "coffee": {
		 *  "id": "INTEGER",
		 *  "color": "TEXT",
		 *  "can_id": "INTEGER"
		 * }, "tag": {
		 *  "id": "INTEGER",
		 *  "name": "TEXT"
		 * }, "can_tag": {
		 *  "id": "INTEGER",
		 *  "tag_id": "INTEGER",
		 *  "can_id": "INTEGER"
		 * }}'
		 */

		$d->setTables( $cache );

		ob_start();

		R::debug( 1 );

		$x = $d->dup( $can );

		$queries = ob_get_contents();

		ob_end_clean();

		R::debug( 0 );

		$len2 = strlen( $queries );

		asrt( isset( $x->ownCoffee ), TRUE );
		asrt( count( $x->ownCoffee ), 1 );
		asrt( isset( $x->sharedTag ), TRUE );
		asrt( count( $x->sharedTag ), 1 );
		asrt( json_encode( $cache ), json_encode( $d->getSchema() ) );
		asrt( ( $len1 > $len2 ), TRUE );
	}

	/**
	 * Test duplication and tainting.
	 *
	 * @return void
	 */
	public function testDupAndExportNonTainting()
	{
		testpack( 'Dup() and Export() should not taint beans' );

		$p            = R::dispense( 'page' );
		$b            = R::dispense( 'book' );

		$b->ownPage[] = $p;
		$b->title     = 'a';

		$id           = R::store( $b );

		$b            = R::load( 'book', $id );

		asrt( ( !$b->getMeta( 'tainted' ) ), TRUE );

		R::exportAll( $b );

		asrt( ( !$b->getMeta( 'tainted' ) ), TRUE );

		R::dup( $b );

		asrt( ( !$b->getMeta( 'tainted' ) ), TRUE );

		testpack( 'Test issue with ownItems and stealing Ids.' );

		R::nuke();
		$bill                  = R::dispense( 'bill' );
		$item                  = R::dispense( 'item' );
		$element               = R::dispense( 'element' );
		$bill->ownItem[]       = $item;
		$bill->sharedElement[] = $element;
		R::store( $bill );
		$bill = R::load( 'bill', 1 );
		$bill->ownItem;
		$bill->sharedElement;
		$copy = R::dup( $bill );
		R::store( $copy );

		$rows = ( R::getAll( 'select * from bill_element' ) );
		asrt( count( $rows ), 2 );

		$rows = ( R::getAll( 'select * from item' ) );

		foreach ( $rows as $row ) {
			asrt( ( $row['bill_id'] > 0 ), TRUE );
		}

		R::nuke();

		$this->runOnce();

		R::freeze( TRUE );

		$this->runOnce( FALSE );

		R::freeze( FALSE );
	}

	/**
	 * Test exporting with filters.
	 *
	 * @return void
	 */
	public function ExportWithFilters()
	{
		testpack( 'Export with filters' );

		$book      = R::dispense( 'book' );
		$pages     = R::dispense( 'page', 2 );
		$texts     = R::dispense( 'text', 2 );
		$images    = R::dispense( 'image', 2 );
		$author    = R::dispense( 'author' );
		$pub       = R::dispense( 'publisher' );
		$bookmarks = R::dispense( 'bookmark', 2 );

		$pages[0]->ownText  = array( $texts[0] );
		$pages[0]->ownImage = array( $images[0] );
		$pages[1]->ownText  = array( $texts[1] );
		$pages[1]->ownImage = array( $images[1] );

		$pages[0]->sharedBookmark[] = $bookmarks[0];
		$pages[1]->sharedBookmark[] = $bookmarks[1];

		$bookmarks[0]->ownNote[] = R::dispense( 'note' )->setAttr( 'text', 'a note' );
		$bookmarks[1]->ownNote[] = R::dispense( 'note' )->setAttr( 'text', 'a note' );

		$book->ownPage = $pages;
		$book->author  = $author;

		$author->publisher = $pub;
		$bookID            = R::store( $book );

		R::getDuplicationManager()->setTables( R::getWriter()->getTables() );

		$objects = ( R::exportAll( array( $book ), TRUE, array() ) );

		asrt( isset( $objects[0]['ownPage'] ), TRUE );
		asrt( count( $objects[0]['ownPage'] ), 2 );
		asrt( isset( $objects[0]['author'] ), TRUE );
		asrt( isset( $objects[0]['ownPage'][0]['ownText'] ), TRUE );
		asrt( count( $objects[0]['ownPage'][0]['ownText'] ), 1 );
		asrt( isset( $objects[0]['ownPage'][0]['ownImage'] ), TRUE );
		asrt( count( $objects[0]['ownPage'][0]['ownImage'] ), 1 );

		$objects = ( R::exportAll( array( $book ), TRUE, array( 'page', 'author', 'text', 'image' ) ) );

		asrt( isset( $objects[0]['ownPage'] ), TRUE );
		asrt( count( $objects[0]['ownPage'] ), 2 );
		asrt( isset( $objects[0]['author'] ), TRUE );
		asrt( isset( $objects[0]['ownPage'][0]['ownText'] ), TRUE );
		asrt( count( $objects[0]['ownPage'][0]['ownText'] ), 1 );
		asrt( isset( $objects[0]['ownPage'][0]['ownImage'] ), TRUE );
		asrt( count( $objects[0]['ownPage'][0]['ownImage'] ), 1 );

		$objects = ( R::exportAll( array( $book ), TRUE, 'author' ) );

		asrt( isset( $objects[0]['ownPage'] ), FALSE );
		asrt( isset( $objects[0]['ownPage'][0]['ownText'] ), FALSE );

		$objects = ( R::exportAll( array( $book ), TRUE, array( 'page' ) ) );

		asrt( isset( $objects[0]['author'] ), FALSE );
		asrt( isset( $objects[0]['ownPage'][0]['ownText'] ), FALSE );

		$objects = ( R::exportAll( array( $book ), TRUE, array( 'page', 'text' ) ) );

		asrt( isset( $objects[0]['author'] ), FALSE );
		asrt( isset( $objects[0]['ownPage'] ), TRUE );
		asrt( isset( $objects[0]['ownPage'][0]['ownText'] ), TRUE );
		asrt( count( $objects[0]['ownPage'][0]['ownText'] ), 1 );
		asrt( isset( $objects[0]['ownPage'][0]['ownImage'] ), FALSE );

		$objects = ( R::exportAll( array( $book ), TRUE, array( 'none' ) ) );

		asrt( isset( $objects[0]['author'] ), FALSE );
		asrt( isset( $objects[0]['ownPage'] ), FALSE );

		$texts = R::find( 'text' );

		R::getDuplicationManager()->setCacheTables( FALSE );

		testpack( 'Keyless export' );

		$book = R::load( 'book', $bookID );

		$book->ownPage;

		$export = $book->export();

		asrt( isset( $export['ownPage'][0] ), TRUE );

	}

	/**
	 * Helper function getCache().
	 *
	 * @return array
	 */
	private function getCache()
	{
		return array(
			'coffee' => array(
				'color' => 'color',
				'id' => 'id',
				'can_id' => 'can_id'
			),
			'can' => array(
				'size' => 'size',
				'id' => 'id'
			),
			'can_tag' => array(
				'id' => 'id',
				'can_id' => 'can_id',
				'tag_id' => 'tag_id'
			),
			'tag' => array(
				'id' => 'id',
				'name' => 'name' )
		);
	}

	/**
	 * Compares object with export
	 *
	 * @param type $object
	 * @param type $array
	 */
	private function compare( $object, $array )
	{
		foreach ( $object as $property => $value ) {
			if ( is_array( $value ) ) {
				foreach ( $value as $index => $nestedObject ) {
					if ( $nestedObject->id ) {
						$foundMatch = FALSE;
						//order might be different
						foreach ( $array[$property] as $k => $a ) {
							if ( $a['id'] == $nestedObject->id ) {
								$foundMatch = TRUE;
								$index      = $k;
							}
						}
						if ( !$foundMatch ) throw new\Exception( 'failed to find match for object ' . $nestedObject->id );
					}
					$this->compare( $nestedObject, $array[$property][$index] );
				}
			} elseif ( !is_object( $value ) ) {
				asrt( strval( $array[$property] ), strval( $value ) );
			}
		}
	}

	/**
	 * Run tests
	 */
	private function runOnce( $n = TRUE )
	{

		$books   = R::dispense( 'book', 10 );
		$pages   = R::dispense( 'page', 10 );
		$readers = R::dispense( 'reader', 10 );
		$texts   = R::dispense( 'text', 10 );

		$i = 0;
		foreach ( $books as $book ) $book->name = 'book-' . ( $i++ );
		$i = 0;
		foreach ( $pages as $page ) $page->name = 'page-' . ( $i++ );
		$i = 0;
		foreach ( $readers as $reader ) $reader->name = 'reader-' . ( $i++ );
		$i = 0;
		foreach ( $texts as $text ) $text->content = 'lorem ipsum -' . ( $i++ );

		foreach ( $texts as $text ) {
			$pages[array_rand( $pages )]->ownText[] = $text;
		}
		foreach ( $pages as $page ) {
			$books[array_rand( $books )]->ownPage[] = $page;
		}
		foreach ( $readers as $reader ) {
			$books[array_rand( $books )]->sharedReader[] = $reader;
		}
		$i = $noOfReaders = $noOfPages = $noOfTexts = 0;
		foreach ( $books as $key => $book ) {
			$i++;
			$noOfPages += count( $book->ownPage );
			$noOfReaders += count( $book->sharedReader );
			foreach ( $book->ownPage as $page ) $noOfTexts += count( $page->ownText );
			$arr = R::exportAll( $book );
			echo "\nIntermediate info: " . json_encode( $arr ) . ": Totals = $i,$noOfPages,$noOfReaders,$noOfTexts ";

			$this->compare( $book, $arr[0] );
			$copiedBook      = R::dup( $book );
			$copiedBookArray = R::exportAll( $copiedBook );
			$this->compare( $book, $copiedBookArray[0] );
			$copiedBookArrayII = $copiedBook->export();
			$this->compare( $book, $copiedBookArrayII );
			$copyFromCopy      = R::dup( $copiedBook );
			$copyFromCopyArray = R::exportAll( $copyFromCopy );
			$this->compare( $book, $copyFromCopyArray[0] );
			$copyFromCopyArrayII = $copyFromCopy->export();
			$this->compare( $book, $copyFromCopyArrayII );
			$id         = R::store( $book );
			$copiedBook = R::dup( $book );
			R::store( $book ); //should not be damaged
			$copiedBookArray   = R::exportAll( $copiedBook );
			$originalBookArray = R::exportAll( $book );
			$this->compare( $copiedBook, $copiedBookArray[0] );
			$this->compare( $book, $originalBookArray[0] );
			$book = R::load( 'book', $id );
			$this->compare( $book, $originalBookArray[0] );
			$copiedBook = R::dup( $book );
			$this->compare( $copiedBook, $copiedBook->export() );
			R::store( $copiedBook );
			$this->compare( $copiedBook, $copiedBook->export() );
			$copyFromCopy = R::dup( $copiedBook );
			$this->compare( $copyFromCopy, $copyFromCopy->export() );
			R::store( $copyFromCopy );
			$newPage                 = R::dispense( 'page' );
			$newPage->name           = 'new';
			$copyFromCopy->ownPage[] = $newPage;
			$modifiedCopy            = R::dup( $copyFromCopy );
			$exportMod               = R::exportAll( $modifiedCopy );
			$this->compare( $modifiedCopy, $exportMod[0] );
			asrt( count( $modifiedCopy->ownPage ), count( $copiedBook->ownPage ) + 1 );
			R::store( $modifiedCopy );

			if ( $n ) {
				asrt( (int) R::getCell( 'SELECT count(*) FROM book' ), $i * 4 );
				asrt( (int) R::getCell( 'SELECT count(*) FROM page' ), ( $noOfPages * 4 ) + $i );
				asrt( (int) R::getCell( 'SELECT count(*) FROM text' ), $noOfTexts * 4 );
				asrt( (int) R::getCell( 'SELECT count(*) FROM book_reader' ), $noOfReaders * 4 );
				asrt( (int) R::getCell( 'SELECT count(*) FROM reader' ), $noOfReaders );
			}
		}

		if ( $n ) {
			asrt( $noOfTexts, 10 );
			asrt( $noOfReaders, 10 );
			asrt( $noOfPages, 10 );
			asrt( $i, 10 );
		}
	}
}
