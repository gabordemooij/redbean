<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;
use RedBeanPHP\AssociationManager as AssociationManager;
use RedBeanPHP\OODB as OODB;
use RedBeanPHP\OODBBean as OODBBean;
use RedBeanPHP\RedException as RedException;
use RedBeanPHP\RedException\SQL as SQL;
use RedBeanPHP\Finder;

/**
 * Finding
 *
 * Tests whether we can find beans by passing SQL queries to the
 * Facade or the Finder Service Class.
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
	 * Inserts data for findMulti-tests.
	 *
	 * @return void
	 */
	private function insertBookData()
	{
		list( $books, $pages, $texts, $categories ) = R::dispenseAll( 'book*5,page*25,text*60,category*3' );
		$texts[0]->content = 'C is a beautiful language.';
		$texts[1]->content = 'But also a bit dangerous.';
		$texts[2]->content = 'You need to know what you are doing.';
		$texts[3]->content = 'Javascript is very flexible.';
		$texts[4]->content = 'It can be anything you want...';
		$texts[5]->content = 'But it can lead to chaos.';
		$texts[6]->content = 'CSS was meant for documents';
		$texts[7]->content = 'Now we use it for applications...';
		$texts[8]->content = 'PHP is an easy language to learn,';
		$texts[9]->content = 'Maybe a bit too easy...';
		$texts[10]->content = 'SQL is much more powerful than you think.';
		$pages[0]->ownTextList = array( $texts[0], $texts[1] );
		$pages[1]->ownTextList = array( $texts[2] );
		$pages[2]->ownTextList = array( $texts[3] );
		$pages[3]->ownTextList = array( $texts[4] );
		$pages[4]->ownTextList = array( $texts[5] );
		$pages[5]->ownTextList = array( $texts[6], $texts[7] );
		$pages[6]->ownTextList = array( $texts[8] );
		$pages[7]->ownTextList = array( $texts[9] );
		$pages[8]->ownTextList = array( $texts[10] );
		$books[0]->ownPageList = array( $pages[0], $pages[1] );
		$books[1]->ownPageList = array( $pages[2], $pages[3], $pages[4] );
		$books[2]->ownPageList = array( $pages[5] );
		$books[3]->ownPageList = array( $pages[6], $pages[7] );
		$books[4]->ownPageList = array( $pages[8] );
		$books[0]->title = 'Diehard C';
		$books[1]->title = 'Adventures in JavaScript';
		$books[2]->title = 'CSS ala Picasso';
		$books[3]->title = 'PHP Tips and Tricks';
		$books[4]->title = 'Secrets of SQL';
		$categories[0]->name = 'Programming';
		$categories[1]->name = 'Design';
		$categories[2]->name = 'Web Development';
		$books[0]->sharedCategoryList = array( $categories[0] );
		$books[1]->sharedCategoryList = array( $categories[0], $categories[2] );
		$books[2]->sharedCategoryList = array( $categories[0], $categories[2], $categories[1] );
		$books[3]->sharedCategoryList = array( $categories[0], $categories[2] );
		$books[4]->sharedCategoryList = array( $categories[0], $categories[2] );
		R::storeAll( $books );
	}

	/**
	 * Test NM-Map.
	 *
	 * @return void
	 */
	public function testNMMap()
	{
		R::nuke();
		$book1 = R::dispense( 'book' );
		$book1->title = 'book 1';
		R::tag( $book1, array('tag 1','tag 2') );
		$book2 = R::dispense( 'book' );
		$book2->title = 'book 2';
		R::tag( $book2, array('tag 2', 'tag 3') );
		$collection = R::findMulti( 'book,book_tag,tag',
			'SELECT book.*, book_tag.*, tag.* FROM book
			LEFT JOIN book_tag ON book_tag.book_id = book.id
			LEFT JOIN tag ON book_tag.tag_id = tag.id
			ORDER BY tag.title ASC
			', array(), array(
			Finder::nmMap( 'book', 'tag' ),
		));
		asrt( count( $collection ), 3 );
		asrt( isset( $collection['book'] ), TRUE );
		asrt( isset( $collection['book_tag'] ), TRUE );
		asrt( isset( $collection['tag'] ), TRUE );
		$books = $collection['book'];
		foreach( $books as $book ) {
			asrt( count( $book->noLoad()->sharedTagList ), 2 );
			$tags = array();
			if ( $book->title == 'book 1' ) {
				foreach( $book->sharedTagList as $tag ) {
					$tags[] = $tag->title;
				}
				asrt( implode( ',', $tags ), 'tag 1,tag 2' );
			}
			$tags = array();
			if ( $book->title == 'book 2' ) {
				foreach( $book->sharedTagList as $tag ) {
					$tags[] = $tag->title;
				}
				asrt( implode( ',', $tags ), 'tag 2,tag 3' );
			}
		}
	}

	/**
	 * Test explicit param binding.
	 *
	 * @return void
	 */
	 public function testExplParaBin()
	 {
		 R::nuke();
		 $bean = R::dispense('bean');
		 $bean->property = 1;
		 $bean->property2 = 2;
		 $bean->property3 = '3';
		 R::store($bean);
		 $value = 1;
		 $value2 = 2;
		 $value3 = '3';
		 $found = R::findOne( 'bean', ' property = ? AND property2 = ? AND property3 = ? ',
		 array($value, array( $value2, \PDO::PARAM_INT ),array( $value3, \PDO::PARAM_STR )));
		 asrt( $bean->id, $found->id );
	 }

	/**
	 * FindMulti should not throw errors in case of
	 * a record-type mismatch.
	 *
	 * @return void
	 */
	public function testFindMultiErrorHandling()
	{
		$result = R::findMulti('a,b', array());
		asrt( is_array( $result ), TRUE );
		asrt( count( $result ), 2 );
		asrt( isset( $result['a'] ), TRUE );
		asrt( isset( $result['b'] ), TRUE );
		asrt( is_array( $result['a'] ), TRUE );
		asrt( is_array( $result['b'] ), TRUE );
		asrt( count( $result['a'] ), 0 );
		asrt( count( $result['b'] ), 0 );
		pass();
		$result = R::findMulti( 'book', array(
				array( 'book__title' => 'The missing ID.' )
		) );
		asrt( is_array( $result ), TRUE );
		asrt( count( $result ), 1 );
		asrt( isset( $result['book'] ), TRUE );
		asrt( is_array( $result['book'] ), TRUE );
		asrt( count( $result['book'] ), 0 );
		pass();
	}

	/**
	 * Like testFindMultiExtFunc but uses findMulti with
	 * $sql = NULL.
	 *
	 * @return void
	 */
	public function testFindMultiWithSQLNULL()
	{
		R::nuke();
		$shop = R::dispense( 'shop' );
		$shop2 = R::dispense( 'shop' );
		$products = R::dispense( 'product', 3 );
		$price = R::dispense( 'price' );
		$price->tag = 5;
		$products[0]->name = 'vase';
		$products[1]->name = 'candle';
		$products[2]->name = 'plate';
		$products[1]->ownPriceList[] = $price;
		$shop->ownProduct[] = $products[0];
		$shop->ownProduct[] = $products[1];
		$shop2->ownProduct[] = $products[2];
		R::storeAll( array( $shop, $shop2 ) );
		$collection = R::findMulti( 'shop,product,price', NULL, array(), array(
			'0' => Finder::map( 'shop', 'product' ),
			'1' => Finder::map( 'product', 'price' ),
		));
		asrt( is_array( $collection ), TRUE );
		asrt( count( $collection ), 3 );
		asrt( count( $collection['shop'] ), 2 );
		asrt( count( $collection['product'] ), 3 );
		asrt( count( $collection['price'] ), 1 );
		$shop = reset( $collection['shop'] );
		asrt( count( $shop->ownProductList ), 2 );
		$shop2 = next( $collection['shop'] );
		asrt( count( $shop2->ownProductList ), 1 );
		$candle = NULL;
		foreach( $shop->ownProduct as $product ) {
				if ( $product->name == 'candle' ) {
					$candle = $product;
				}
		}
		asrt( is_null( $candle ), FALSE );
		asrt( count( $candle->ownPrice ), 1 );
		asrt( $candle->name, 'candle' );
		$price = reset( $candle->ownPrice );
		asrt( (int) $price->tag, 5 );
	}

	/**
	 * You can build your own mapping functions to remap records to bean.
	 * Just like the preloader once did. However now you can define the
	 * mapping yourself using closures. This test verifies that such a
	 * function would actually work.
	 *
	 * This method also tests whether empty records (resulting from LEFT JOINS for
	 * instance) do not produce unnecessary, empty beans.
	 *
	 * @return void
	 */
	public function testFindMultiExtFunc()
	{
		R::nuke();
		$shop = R::dispense( 'shop' );
		$shop2 = R::dispense( 'shop' );
		$products = R::dispense( 'product', 3 );
		$price = R::dispense( 'price' );
		$price->tag = 5;
		$products[0]->name = 'vase';
		$products[1]->name = 'candle';
		$products[2]->name = 'plate';
		$products[1]->ownPriceList[] = $price;
		$shop->ownProduct[] = $products[0];
		$shop->ownProduct[] = $products[1];
		$shop2->ownProduct[] = $products[2];
		R::storeAll( array( $shop, $shop2 ) );
		$collection = R::findMulti( 'shop,product,price', '
			SELECT shop.*, product.*, price.* FROM shop
			LEFT JOIN product ON product.shop_id = shop.id
			LEFT JOIN price ON price.product_id = product.id
		', array(), array(
			'0' => Finder::map( 'shop', 'product' ),
			'1' => Finder::map( 'product', 'price' ),
		));
		asrt( is_array( $collection ), TRUE );
		asrt( count( $collection ), 3 );
		asrt( count( $collection['shop'] ), 2 );
		asrt( count( $collection['product'] ), 3 );
		asrt( count( $collection['price'] ), 1 );
		$shop = reset( $collection['shop'] );
		asrt( count( $shop->ownProductList ), 2 );
		$shop2 = next( $collection['shop'] );
		asrt( count( $shop2->ownProductList ), 1 );
		$candle = NULL;
		foreach( $shop->ownProduct as $product ) {
				if ( $product->name == 'candle' ) {
					$candle = $product;
				}
		}
		asrt( is_null( $candle ), FALSE );
		asrt( count( $candle->ownPrice ), 1 );
		asrt( $candle->name, 'candle' );
		$price = reset( $candle->ownPrice );
		asrt( (int) $price->tag, 5 );
	}

	/**
	 * Test findMuli with self-made arrays.
	 *
	 * @return void
	 */
	public function testFindMultiDirectArray()
	{
		R::nuke();
		$collection = R::findMulti( 'shop,product', array(
			array( 'shop__id' => 1, 'product__id' => 1, 'product__name' => 'vase', 'product__shop_id' => 1 ),
			array( 'shop__id' => 1, 'product__id' => 2, 'product__name' => 'candle', 'product__shop_id' => 1 ),
			array( 'shop__id' => 1, 'product__id' => 3, 'product__name' => 'plate', 'product__shop_id' => 1 ),
		) );
		asrt( is_array( $collection ), TRUE );
		asrt( isset( $collection['shop'] ), TRUE );
		asrt( isset( $collection['product'] ), TRUE );
		asrt( (int) $collection['shop'][1]->id, 1 );
		asrt( (int) $collection['product'][1]->id, 1 );
		asrt( (int) $collection['product'][2]->id, 2 );
		asrt( (int) $collection['product'][3]->id, 3 );
		asrt( (int) $collection['product'][1]->shopID, 1 );
		asrt( (int) $collection['product'][2]->shopID, 1 );
		asrt( (int) $collection['product'][3]->shopID, 1 );
		asrt( $collection['product'][1]->name, 'vase' );
		asrt( $collection['product'][2]->name, 'candle' );
		asrt( $collection['product'][3]->name, 'plate' );
		R::nuke();
		$shop = R::dispense('shop');
		$shop2 = R::dispense('shop');
		$products = R::dispense('product', 3);
		$price = R::dispense('price');
		$price->tag = 5;
		$products[0]->name = 'vase';
		$products[1]->name = 'candle';
		$products[2]->name = 'plate';
		$products[1]->ownPriceList[] = $price;
		$shop->ownProduct = $products;
		R::store($shop);
		$collection = R::findMulti('shop,product,price', '
			SELECT shop.*, product.*, price.* FROM shop
			LEFT JOIN product ON product.shop_id = shop.id
			LEFT JOIN price ON price.product_id = product.id
		', array(), array(
			'0' => Finder::map('shop', 'product'),
			'1' => Finder::map('product', 'price'),
		));
		$collection = R::findMulti( 'shop,product', array(
			array( 'shop__id' => 1, 'product__id' => 1, 'product__name' => 'vase', 'product__shop_id' => 1 ),
			array( 'shop__id' => 1, 'product__id' => 2, 'product__name' => 'candle', 'product__shop_id' => 1 ),
			array( 'shop__id' => 1, 'product__id' => 3, 'product__name' => 'plate', 'product__shop_id' => 1 ),
			array( 'shop__id' => 1, 'product__id' => 2, 'product__name' => 'candle', 'product__shop_id' => 1)
		), array(), array(
			array(
				'a' => 'shop',
				'b' => 'product',
				'matcher' => function( $a, $b ) { return ( $b->shopID == $a->id ); },
				'do'      => function( $a, $b ) { return $a->noLoad()->ownProductList[] = $b; }
			)
		) );
		asrt( is_array( $collection ), TRUE );
		asrt( isset( $collection['shop'] ), TRUE );
		asrt( isset( $collection['product'] ), TRUE );
		asrt( (int) $collection['shop'][1]->id, 1 );
		asrt( (int) $collection['product'][1]->id, 1 );
		asrt( (int) $collection['product'][2]->id, 2 );
		asrt( (int) $collection['product'][3]->id, 3 );
		asrt( (int) $collection['product'][1]->shopID, 1 );
		asrt( (int) $collection['product'][2]->shopID, 1 );
		asrt( (int) $collection['product'][3]->shopID, 1 );
		asrt( $collection['product'][1]->name, 'vase' );
		asrt( $collection['product'][2]->name, 'candle' );
		asrt( $collection['product'][3]->name, 'plate' );
		asrt( isset( $collection['shop'][1]->ownProductList ), TRUE );
		asrt( is_array( $collection['shop'][1]->ownProductList ), TRUE );
		asrt( count( $collection['shop'][1]->ownProductList ), 3 );
		asrt( $collection['shop'][1]->ownProductList[0]->name, 'vase' );
		asrt( $collection['shop'][1]->ownProductList[1]->name, 'candle' );
		asrt( $collection['shop'][1]->ownProductList[2]->name, 'plate' );
	}

	/**
	 * Test findMulti() with manual crafted fields.
	 *
	 * @return void
	 */
	public function testFindMultiDIY()
	{
		R::nuke();
		$movie = R::dispense( 'movie' );
		$review = R::dispense( 'review' );
		$movie->ownReviewList[] = $review;
		$review->stars = 5;
		$movie->title = 'Gambit';
		R::store( $movie );
		$stuff = R::findMulti( 'movie,review', 'SELECT
			movie.id AS movie__id,
			movie.title AS movie__title,
			review.id AS review__id,
			review.stars AS review__stars,
			review.movie_id AS review__movie_id
			FROM movie
			LEFT JOIN review ON review.movie_id = movie.id
		' );
		asrt( count( $stuff ), 2 );
		asrt( isset( $stuff['movie'] ), TRUE );
		asrt( isset( $stuff['review'] ), TRUE );
		asrt( is_array( $stuff['movie'] ), TRUE );
		asrt( is_array( $stuff['review'] ), TRUE );
		asrt( count( $stuff['movie'] ), 1 );
		asrt( count( $stuff['review'] ), 1 );
		$movie = reset( $stuff['movie'] );
		asrt( $movie->title, 'Gambit' );
		$review = reset( $stuff['review'] );
		asrt( (int) $review->stars, 5 );
		R::nuke();
		$movie = R::dispense( 'movie' );
		$review = R::dispense( 'review' );
		$movie->ownReviewList[] = $review;
		$review->stars = 5;
		$movie->title = 'Gambit';
		R::store( $movie );
		$stuff = R::findMulti( array( 'movie', 'review' ), 'SELECT
			movie.id AS movie__id,
			movie.title AS movie__title,
			review.id AS review__id,
			review.stars AS review__stars,
			review.movie_id AS review__movie_id
			FROM movie
			LEFT JOIN review ON review.movie_id = movie.id
		' );
		asrt( count( $stuff ), 2 );
		asrt( isset( $stuff['movie'] ), TRUE );
		asrt( isset( $stuff['review'] ), TRUE );
		asrt( is_array( $stuff['movie'] ), TRUE );
		asrt( is_array( $stuff['review'] ), TRUE );
		asrt( count( $stuff['movie'] ), 1 );
		asrt( count( $stuff['review'] ), 1 );
		$movie = reset( $stuff['movie'] );
		asrt( $movie->title, 'Gambit' );
		$review = reset( $stuff['review'] );
		asrt( (int) $review->stars, 5 );
	}

	/**
	 * Test findMulti(). Basic version.
	 *
	 * @return void
	 */
	public function testFindMulti()
	{
		$book = R::dispense( 'book' );
		$book->title = 'My Book';
		$book->ownPageList = R::dispense( 'page', 3 );
		$no = 1;
		foreach( $book->ownPageList as $page ) {
			$page->num = $no++;
		}
		R::store( $book );
		$collection = R::findMulti( 'book,page', '
			SELECT book.*, page.* FROM book
			LEFT JOIN page ON page.book_id = book.id
		' );
		asrt( count( $collection ), 2 );
		asrt( isset( $collection['book'] ), TRUE );
		asrt( isset( $collection['page'] ), TRUE );
		asrt( count( $collection['book'] ), 1 );
		asrt( count( $collection['page'] ), 3 );
		foreach( $collection['book'] as $bean ) asrt( ( $bean instanceof OODBBean ), TRUE );
		foreach( $collection['page'] as $bean ) asrt( ( $bean instanceof OODBBean ), TRUE );
		$book = reset( $collection['book'] );
		asrt( $book->title, 'My Book' );
		$no = 1;
		foreach( $collection['page'] as $page ) asrt( (int) $page->num, $no++ );
		R::nuke();
		$book->noLoad()->ownPageList = $collection['page'];
		asrt( count( $book->ownPageList ), 3 );
	}

	/**
	 * Tests the complex use case for findMulti().
	 *
	 * @return void
	 */
	public function testMultiAdvanced()
	{
		$this->insertBookData();
		$collection = R::findMulti( 'book,page,text,category', '
			SELECT book.*, page.*, text.*, category.*
			FROM book
			LEFT JOIN page ON page.book_id = book.id
			LEFT JOIN text ON text.page_id = page.id
			LEFT JOIN book_category ON book_category.book_id = book.id
			LEFT JOIN category ON book_category.category_id = category.id
		' );
		asrt( count( $collection ), 4 );
		asrt( isset( $collection['book'] ), TRUE );
		asrt( isset( $collection['page'] ), TRUE );
		asrt( isset( $collection['text'] ), TRUE );
		asrt( isset( $collection['category'] ), TRUE );
		asrt( count( $collection['book'] ), 5 );
		asrt( count( $collection['page'] ), 9 );
		asrt( count( $collection['text'] ), 11 );
		asrt( count( $collection['category'] ), 3 );
		foreach( $collection['book'] as $bean ) asrt( ( $bean instanceof OODBBean ), TRUE );
		foreach( $collection['page'] as $bean ) asrt( ( $bean instanceof OODBBean ), TRUE );
		foreach( $collection['text'] as $bean ) asrt( ( $bean instanceof OODBBean ), TRUE );
		foreach( $collection['category'] as $bean ) asrt( ( $bean instanceof OODBBean ), TRUE );
		foreach( $collection['book']  as $book ) $titles[] = $book->title;
		asrt( in_array( 'Diehard C', $titles ), TRUE );
		asrt( in_array( 'Adventures in JavaScript', $titles ), TRUE );
		asrt( in_array( 'CSS ala Picasso', $titles ), TRUE );
		asrt( in_array( 'PHP Tips and Tricks', $titles ), TRUE );
		asrt( in_array( 'Secrets of SQL', $titles ), TRUE );
		$collection = R::findMulti( 'book,page,text,category,book_category', '
			SELECT book.*, page.*, text.*, category.*, book_category.*
			FROM book
			LEFT JOIN page ON page.book_id = book.id
			LEFT JOIN text ON text.page_id = page.id
			LEFT JOIN book_category ON book_category.book_id = book.id
			LEFT JOIN category ON book_category.category_id = category.id
			WHERE category_id > ?
			ORDER BY book.title,page.id ASC
		', array( 0 ), array(
				array(
					'b'=>'page',
					'a'=>'text',
					'do' => function( $a, $b ) {
						$b->noLoad()->ownTextList[] = $a;
						$b->clearHistory();
					},
					'matcher' => function( $a, $b ){ return ($a->page_id == $b->id);  }
					),
				array(
					'b'=>'book',
					'a'=>'page',
					'do' => function( $a, $b ) {
						$b->noLoad()->ownPageList[] = $a;
						$b->clearHistory();
					},
					'matcher' => function( $a, $b ){ return ($a->book_id == $b->id);  }
					),
				array(
					'b' => 'category',
					'a' => 'book',
					'do'  => function($a, $b) {
						$a->noLoad()->sharedCategoryList[] = $b;
						$a->clearHistory();
					},
					'matcher' => function( $a, $b, $beans ) {
						foreach( $beans['book_category'] as $bean ) {
							if ( $bean->book_id == $a->id && $bean->category_id == $b->id ) return TRUE;
						}
						return FALSE;
					}
				),
			)
		);
		$books = $collection['book'];
		$book = reset( $books );
		asrt( $book->title, 'Adventures in JavaScript' );
		R::nuke();
		asrt( count( $book->ownPageList ), 3 );
		$page = reset( $book->ownPageList );
		asrt( count( $page->ownTextList ), 1 );
		asrt( count( $book->sharedCategoryList ), 2);
		$categories = array();
		foreach( $book->sharedCategoryList as $category ) {
			$categories[] = $category->name;
		}
		sort( $categories );
		asrt( implode( ',', $categories ), 'Programming,Web Development' );
		$book = next( $books );
		asrt( $book->title, 'CSS ala Picasso' );
		asrt( count( $book->ownPage ), 1 );
		$page = reset( $book->ownPage );
		asrt( count( $page->ownTextList ), 2 );
		$texts = array();
		foreach( $page->ownTextList as $text ) $texts[] = $text->content;
		asrt( in_array( 'Now we use it for applications...', $texts ), TRUE );
		$categories = array();
		foreach( $book->sharedCategoryList as $category ) {
			$categories[] = $category->name;
		}
		sort( $categories );
		asrt( implode( ',', $categories ), 'Design,Programming,Web Development' );
		$book = next( $books );
		asrt( $book->title, 'Diehard C' );
		asrt( count( $book->ownPageList ), 2 );
		$page = reset( $book->ownPageList );
		asrt( count( $page->ownTextList ), 2 );
		$page = next( $book->ownPageList );
		asrt( count( $page->ownTextList ), 1 );
		$categories = array();
		foreach( $book->sharedCategoryList as $category ) {
			$categories[] = $category->name;
		}
		sort( $categories );
		asrt( implode( ',', $categories ), 'Programming' );
		//should have no effect, nothing should have changed
		R::storeAll($books);
		asrt( R::count('book'), 0 );
		asrt( R::count('page'), 0 );
		asrt( R::count('text'), 0 );
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
		asrt( genslots( array( 1, 2 ) ), '?,?' );
		asrt( json_encode( array_flatten( array( array( 'a', array( 'b', array( array( 'c' ) ) ) ) ) ) ), '["a","b","c"]' );
		asrt( genslots( array( 1, 2 ), 'IN (%s) AND' ), 'IN (?,?) AND' );
		asrt( genslots( array(), ' IN (%s) AND ' ), '' );
		$colors = array( 'blue', 'purple', 'red' );
		$flowers = R::find( 'flower', genslots( $colors, ' color IN (%s) AND ' ).' price > ? ', array_flatten( array( $colors, 11 ) ) );
		asrt( $this->getColors( $flowers ), 'blue,purple' );
		$flowers = R::find( 'flower', genslots( array(), ' color IN (%s) AND ' ).' price > ? ', array_flatten( array( array(), 11 ) ) );
		asrt( $this->getColors( $flowers ), 'blue,purple,yellow' );
		$flowers = R::find( 'flower', ' id > 0 AND '.genslots( $colors, ' color IN (%s) AND ' ).' price > ? ', array_flatten( array( $colors, 11 ) ) );
		asrt( $this->getColors( $flowers ), 'blue,purple' );
		$flowers = R::find( 'flower', ' id > 0 AND '.genslots( array(), ' color IN (%s) AND ' ).' price > ? ', array_flatten( array( array(), 11 ) ) );
		asrt( $this->getColors( $flowers ), 'blue,purple,yellow' );
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
	 * Tests OODBBean as conditions
	 *
	 * @return void
	 */
	public function testFindLikeWithOODBBeans() {
		R::nuke();
		$book = R::dispense( 'book' );
		$page = R::dispense( 'page' );
		$page->book = $book;
		R::store( $page );
		$book2 = R::dispense( 'book' );
		$page2 = R::dispense( 'page' );
		$page2->book = $book2;
		R::store( $page2 );
		$pages = R::findLike( 'page', array( 'book_id' => array( 1, 2 ) ) );
		$pagesWithOODB = R::findLike( 'page', array( 'book' => array( $book, $book2 ) ) );
		asrt( count( $pagesWithOODB ), 2 );
		asrt( json_encode($pagesWithOODB), json_encode($pages) );
		asrt( reset( $pagesWithOODB )->id, $page->id );
		asrt( end( $pagesWithOODB )->id, $page2->id );

		$pages = R::findLike( 'page', array( 'book' => array( $book, $book2->id ) ) );
		asrt( count( $pages ), 2 );
		asrt( reset( $pages )->id, $page->id );
		asrt( end( $pages )->id, $page2->id );

		$pages = R::findLike( 'page', array( 'book_id' => array( $book->id, $book2 ) ) );
		asrt( count( $pages ), 2 );
		asrt( reset( $pages )->id, $page->id );
		asrt( end( $pages )->id, $page2->id );

		$pagesFail = R::findLike( 'page', array( 'book' => array( $book->id, $book2 ) ) );
		asrt( count( $pagesFail ), 0 );

		$book3 = R::dispense( 'book' );
		$page3 = R::dispense( 'page' );
		R::store( $page3 );
		$page3->book = $book3;
		$pagesFail = R::findLike( 'page', array( 'book' => $book3 ) );
		asrt( count( $pagesFail ), 0 );

		$pen = R::dispense( 'pen' );
		R::store( $pen );
		asrt( $pen->id, $book->id );
		$pagesFail = R::findLike( 'page', array( 'book' => $pen ) );
		asrt( count( $pagesFail ), 0 );

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
	 * Can we find books based on associations with other
	 * entities?
	 *
	 * @return void
	 */
	public function testFindLikeBean()
	{
		R::nuke();
		$book1 = R::dispense( 'book' );
		$page1 = R::dispense( 'page' );
		$book2 = R::dispense( 'book' );
		$page2 = R::dispense( 'page' );
		$book1->page = $page1;
		$book2->page = $page2;
		R::storeAll( array( $book1, $book2 ) );
		$books = R::findLike( 'book', array( 'page' => array( $page2 ) ), ' AND id > ?', array( 0 ) );
		$book = reset( $books );
		asrt( $book->id, $book2->id );
		$books = R::findLike( 'book', array( 'page' => array( $page1 ) ), ' AND id > ?', array( 0 )  );
		$book = reset( $books );
		asrt( $book->id, $book1->id );
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
		$logger->clear();
		$found = R::findOne( 'book', ' title = ? limit 1', array( 'b' ) );
		asrt( count( $logger->grep('LIMIT 1') ), 0 );
		asrt( count( $logger->grep('limit 1') ), 1 );
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
	public function testFinding()
	{
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
		R::nuke();
		$bean = R::findOneOrDispense( 'jellybean' );
		asrt( is_object( $bean ), TRUE );
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
