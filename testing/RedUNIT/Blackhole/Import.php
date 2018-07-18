<?php

namespace RedUNIT\Blackhole;

use RedUNIT\Blackhole as Blackhole;
use RedBeanPHP\Facade as R;
use RedBeanPHP\OODBBean as OODBBean;
use RedBeanPHP\RedException as RedException;

/**
 * Import
 *
 * RedBeanPHP offers some methods to import arrays into
 * beans. For instance using the dispense() method. This
 * test suite checks whether RedBeanPHP can correctly convert
 * array structures to beans and also checks the expected effects
 * on the taint flags. This test suite further tests the 'simple'
 * single bean import() function, the inject() function (bean-to-bean) and
 * array access (because this is somehow related).
 *
 * @file    RedUNIT/Blackhole/Import.php
 * @desc    Tests basic bean importing features.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */

class Import extends Blackhole
{
	/**
	 * Test import without trimming.
	 *
	 * @return void
	 */
	public function testImportWithoutTrim()
	{
		$book = R::dispense( 'book' );
		$book->import( array( ' title ' => 'my book' ), array( ' title ' ), TRUE );
		asrt( $book[' title '], 'my book' );
	}

	/**
	 * Test multi array dispense import.
	 *
	 * @return void
	 */
	public function testMultiRecurImport()
	{
		$books = R::dispense( array(
			array( '_type' => 'book', 'title' => 'book one' ),
			array( '_type' => 'book', 'title' => 'book two' ),
		) );
		asrt( is_array( $books ), TRUE );
		asrt( count( $books ), 2 );
		$book = reset( $books );
		asrt( ( $book instanceof OODBBean ), TRUE );
		asrt( $book->title, 'book one' );
		$book = next( $books );
		asrt( ( $book instanceof OODBBean ), TRUE );
		asrt( $book->title, 'book two' );
	}

	/**
	 * Test recursive imports (formely known as R::graph).
	 *
	 * @return void
	 */
	public function testRecursiveImport()
	{
		$book = R::dispense(
			array(
				'_type'=>'book',
				'title'=>'The magic book',
				'ownPageList' => array(
					 array(
						'_type' => 'page',
						'content'  => 'magic potions',
					 ),
					 array(
						'_type' => 'page',
						'content'  => 'magic spells',
					 )
				)
			)
		);
		$id = R::store( $book );
		$book = R::load( 'book', $id );
		asrt( $book->title, 'The magic book' );
		$pages = $book->with(' ORDER BY content ASC ')->ownPageList;
		asrt( count($pages), 2 );
		$page1 = array_shift( $pages );
		asrt( $page1->content, 'magic potions' );
		$page2 = array_shift( $pages );
		asrt( $page2->content, 'magic spells' );
		R::nuke();
		$book = R::dispense(
			array(
				'_type'=>'book',
				'title'=>'The magic book',
				'author' => array(
					 '_type' => 'author',
					 'name'  => 'Dr. Evil'
				),
				'coAuthor' => array(
					 '_type' => 'author',
					 'name'  => 'Dr. Creepy'
				),
				'ownPageList' => array(
					 array(
						'_type' => 'page',
						'content'  => 'magic potions',
						'ownRecipe' => array(
							 'a' => array('_type'=>'recipe', 'name'=>'Invisibility Salad'),
							 'b' => array('_type'=>'recipe', 'name'=>'Soup of Madness'),
							 'c' => array('_type'=>'recipe', 'name'=>'Love cake'),
						)
					 ),
					 array(
						'_type' => 'page',
						'content'  => 'magic spells',
					 )
				),
				'sharedCategory' => array(
					 array(
						  '_type' => 'category',
						  'label' => 'wizardry'
					 ),
				)
			)
		);
		$id = R::store( $book );
		$book = R::load( 'book', $id );
		asrt( $book->title, 'The magic book' );
		$pages = $book->with(' ORDER BY content ASC ')->ownPageList;
		asrt( count($pages), 2 );
		$page1 = array_shift( $pages );
		asrt( $page1->content, 'magic potions' );
		$page2 = array_shift( $pages );
		asrt( $page2->content, 'magic spells' );
		$recipes = $page1->with(' ORDER BY name ASC ')->ownRecipeList;
		asrt( count( $recipes ), 3 );
		$recipe1 = array_shift( $recipes );
		asrt( $recipe1->name, 'Invisibility Salad' );
		$recipe2 = array_shift( $recipes );
		asrt( $recipe2->name, 'Love cake' );
		$recipe3 = array_shift( $recipes );
		asrt( $recipe3->name, 'Soup of Madness' );
		$categories = $book->sharedCategoryList;
		asrt( count($categories), 1 );
		$category = reset( $categories );
		asrt( $category->label, 'wizardry' );
		asrt( $book->author->name, 'Dr. Evil' );
		asrt( $book->fetchAs('author')->coAuthor->name, 'Dr. Creepy' );
		try {
			$list = R::dispense( array() );
			pass();
			asrt( is_array( $list ), TRUE );
			asrt( count( $list ), 0 );
		} catch ( RedException $ex ) {
			pass();
		}
		try {
			R::dispense( array( array() ) );
			fail();
		} catch ( RedException $ex ) {
			pass();
		}
		try {
			R::dispense( array( 'a' ) );
			fail();
		} catch ( RedException $ex ) {
			pass();
		}
		try {
			R::dispense( array( 'property' => 'value' ) );
			fail();
		} catch ( RedException $ex ) {
			pass();
		}
	}

	/**
	 * Test import from and tainted.
	 *
	 * @return void
	 */
	public function testImportFromAndTainted()
	{
		testpack( 'Test importFrom() and Tainted' );
		$bean = R::dispense( 'bean' );
		R::store( $bean );
		$bean->name = 'abc';
		asrt( $bean->getMeta( 'tainted' ), TRUE );
		R::store( $bean );
		asrt( $bean->getMeta( 'tainted' ), FALSE );
		$copy = R::dispense( 'bean' );
		R::store( $copy );
		$copy = R::load( 'bean', $copy->id );
		asrt( $copy->getMeta( 'tainted' ), FALSE );
		$copy->import( array( 'name' => 'xyz' ) );
		asrt( $copy->getMeta( 'tainted' ), TRUE );
		$copy->setMeta( 'tainted', FALSE );
		asrt( $copy->getMeta( 'tainted' ), FALSE );
		$copy->importFrom( $bean );
		asrt( $copy->getMeta( 'tainted' ), TRUE );
		testpack( 'Test basic import() feature.' );
		$bean = R::dispense('bean');
		$bean->import( array( "a" => 1, "b" => 2 ) );
		asrt( $bean->a, 1 );
		asrt( $bean->b, 2 );
		$bean->import( array( "a" => 3, "b" => 4 ), "a,b" );
		asrt( $bean->a, 3 );
		asrt( $bean->b, 4 );
		$bean->import( array( "a" => 5, "b" => 6 ), " a , b " );
		asrt( $bean->a, 5 );
		asrt( $bean->b, 6 );
		$bean->import( array( "a" => 1, "b" => 2 ) );
		testpack( 'Test inject() feature.' );
		$coffee = R::dispense( 'coffee' );
		$coffee->id     = 2;
		$coffee->liquid = 'black';
		$cup = R::dispense( 'cup' );
		$cup->color = 'green';
		// Pour coffee in cup
		$cup->inject( $coffee );
		// Do we still have our own property?
		asrt( $cup->color, 'green' );
		// Did we pour the liquid in the cup?
		asrt( $cup->liquid, 'black' );
		// Id should not be transferred
		asrt( $cup->id, 0 );
	}

	/**
	 * Test import using array access functions
	 *
	 * @return void
	 */
	public function testArrayAccess()
	{
		$book = R::dispense( 'book' );
		$book->isAwesome = TRUE;
		asrt( isset( $book->isAwesome ), TRUE );
		$book = R::dispense( 'book' );
		$book['isAwesome'] = TRUE;
		asrt( isset( $book->isAwesome ), TRUE );
		$book = R::dispense( 'book' );
		$book['xownPageList'] = R::dispense( 'page', 2 );
		asrt( isset( $book->ownPage ), TRUE );
		asrt( isset( $book->xownPage ), TRUE );
		asrt( isset( $book->ownPageList ), TRUE );
		asrt( isset( $book->xownPageList ), TRUE );
		$book = R::dispense( 'book' );
		$book['ownPageList'] = R::dispense( 'page', 2 );
		asrt( isset( $book->ownPage ), TRUE );
		asrt( isset( $book->xownPage ), TRUE );
		asrt( isset( $book->ownPageList ), TRUE );
		asrt( isset( $book->xownPageList ), TRUE );
		$book = R::dispense( 'book' );
		$book['xownPage'] = R::dispense( 'page', 2 );
		asrt( isset( $book->ownPage ), TRUE );
		asrt( isset( $book->xownPage ), TRUE );
		asrt( isset( $book->ownPageList ), TRUE );
		asrt( isset( $book->xownPageList ), TRUE );
		$book = R::dispense( 'book' );
		$book['ownPage'] = R::dispense( 'page', 2 );
		asrt( isset( $book->ownPage ), TRUE );
		asrt( isset( $book->xownPage ), TRUE );
		asrt( isset( $book->ownPageList ), TRUE );
		asrt( isset( $book->xownPageList ), TRUE );
		$book = R::dispense( 'book' );
		$book['sharedTag'] = R::dispense( 'tag', 2 );
		asrt( isset( $book->sharedTag ), TRUE );
		asrt( isset( $book->sharedTagList ), TRUE );
		$book = R::dispense( 'book' );
		$book['sharedTagList'] = R::dispense( 'tag', 2 );
		asrt( isset( $book->sharedTag ), TRUE );
		asrt( isset( $book->sharedTagList ), TRUE );
	}
}
