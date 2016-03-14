<?php
namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;

/**
 * Bean
 *
 * @file    RedUNIT/Base/Bean.php
 * @desc    Tests list manipulations of bean.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Bean extends Base
{
	/**
	 * Tests whether we can send results of a query to meta data
	 * when converting to bean.
	 */
	public function testImportMeta()
	{
		R::nuke();
		$book = R::dispense( array(
			'_type'  => 'book',
			'title'  => 'Bean Recipes',
			'author' => 'Meastro de la Bean'
		) );
		$pages = R::dispenseAll( 'page*2' );
		$book->ownPageList = reset( $pages );
		R::store( $book );
		$data = R::getRow('SELECT book.*,
								  COUNT(page.id) AS meta_count,
								  1234 AS meta_extra
						   FROM book
						   LEFT JOIN page ON page.book_id = book.id
						   GROUP BY book.id
						   ');
		$bean = R::convertToBean( 'book', $data, 'meta_' );
		asrt( isset( $bean->title ), TRUE );
		asrt( isset( $bean->author ), TRUE );
		asrt( isset( $bean->meta_count ), FALSE );
		asrt( isset( $bean->meta_extra ), FALSE );
		$data = $bean->getMeta( 'data.bundle' );
		asrt( intval( $data['meta_count'] ), 2);
		asrt( intval( $data['meta_extra'] ), 1234);
		//now with multiple beans
		$book = R::dispense( array(
			'_type'  => 'book',
			'title'  => 'Bean Adventures',
			'author' => 'Mr Adventure'
		) );
		$pages = R::dispenseAll( 'page*3' );
		$book->ownPageList = reset( $pages );
		R::store( $book );
		$data = R::getAll('SELECT book.*,
								  COUNT(page.id) AS meta_pages
						   FROM book
						   LEFT JOIN page ON page.book_id = book.id
						   GROUP BY book.id
						   ');
		$books = R::convertToBeans( 'book', $data, 'meta_' );
		$found = 0;
		foreach( $books as $book ) {
			if ( $book->title == 'Bean Recipes' ) {
				$found++;
				asrt( isset( $book->title ), TRUE );
				asrt( isset( $book->author ), TRUE );
				asrt( isset( $book->meta_count ), FALSE );
				asrt( isset( $book->meta_extra ), FALSE );
				$data = $book->getMeta( 'data.bundle' );
				asrt( intval( $data['meta_pages'] ), 2);
			}
			if ( $book->title == 'Bean Adventures' ) {
				$found++;
				asrt( isset( $book->title ), TRUE );
				asrt( isset( $book->author ), TRUE );
				asrt( isset( $book->meta_pages ), FALSE );
				asrt( isset( $book->meta_extra ), FALSE );
				$data = $book->getMeta( 'data.bundle' );
				asrt( intval( $data['meta_pages'] ), 3);
			}
		}
		asrt( $found, 2 );
	}

	/**
	 * Test beautification conflicts...
	 * Issue #418
	 *
	 * @return void
	 */
	public function testBeau()
	{
		R::nuke();
		$book = R::dispense( 'book' );
		$book->ownerId = 2;
		$book->ownerCritic = 'a';
		$book->sharedbyReader = 'b';
		$id = R::store( $book );
		$columns = R::inspect( 'book' );
		asrt( isset( $columns['owner_id'] ), TRUE );
		asrt( isset( $columns['owner_critic'] ), TRUE );
		asrt( isset( $columns['sharedby_reader'] ), TRUE );
		asrt( isset( $columns['ownerId'] ), FALSE );
		asrt( isset( $columns['ownerCritic'] ), FALSE );
		asrt( isset( $columns['sharedbyReader'] ), FALSE );
		R::nuke();
		$book = R::dispense( 'book' );
		$book->xownerId = 2;
		$book->xownerCritic = 'a';
		$book->sharedbyReader = 'b';
		$id = R::store( $book );
		$columns = R::inspect( 'book' );
		asrt( isset( $columns['xowner_id'] ), TRUE );
		asrt( isset( $columns['xowner_critic'] ), TRUE );
		asrt( isset( $columns['sharedby_reader'] ), TRUE );
		asrt( isset( $columns['xownerId'] ), FALSE );
		asrt( isset( $columns['xownerCritic'] ), FALSE );
		asrt( isset( $columns['sharedbyReader'] ), FALSE );
	}

	/**
	 * Other tests...
	 */
	public function testMisc()
	{
		R::nuke();
		$book = R::dispense( 'book' );
		$book->ownPage[] = R::dispense( 'page' );
		R::store( $book );
		R::nuke();
		R::store( $book );
		asrt( R::count( 'book' ), 0 );
		$book->ownPage;
		R::store( $book );
		asrt( R::count( 'book' ), 0 );
		$book->title = 'x';
		R::store( $book );
		asrt( R::count( 'book' ), 0 );
	}

	/**
	 * Only fire update query if the bean really contains different
	 * values. But make sure beans several 'parents' away still get
	 * saved.
	 *
	 * @return void
	 */
	public function testBeanTainting()
	{
		$logger = R::getDatabaseAdapter()->getDatabase()->getLogger();
		list( $i, $k, $c, $s ) = R::dispenseAll( 'invoice,customer,city,state' );
		$i->customer = $k;
		$i->status = 0;
		$k->city = $c;
		$c->state = $s;
		$s->name = 'x';
		R::store( $i );
		$i = $i->fresh();
		asrt( $i->customer->city->state->name, 'x' );
		$i->status = 1;
		R::freeze( true );
		$logger = R::debug( 1, 1 );
		//do we properly skip unmodified but tainted parent beans?
		R::store( $i );
		$numberOfUpdateQueries = $logger->grep( 'UPDATE' );
		asrt( count( $numberOfUpdateQueries ), 1 );
		//does cascade update still work?
		$i = $i->fresh();
		$i->customer->city->state->name = 'y';
		R::store( $i );
		$i = $i->fresh();
		asrt( $i->customer->city->state->name, 'y' );
		$i = $i->fresh();
		$differentCity = R::dispense( 'city' );
		R::store( $differentCity );
		$i->customer->city = $differentCity;
		R::store( $i );
		$i = $i->fresh();
		asrt( ( $i->customer->city->id != $c->id ), TRUE );
		asrt( is_null( $i->customer->city->state ), TRUE );
		$i->customer->city = NULL;
		R::store( $i );
		$i = $i->fresh();
		asrt( is_null( $i->customer->city ), TRUE );
		$i->customer = $k;
		$i->status = 0;
		$k->city = $c;
		$c->state = $s;
		$s->name = 'x';
		R::store( $i );
		R::freeze( FALSE );
		$i = $i->fresh();
		//can we still change remote parent?
		$i->customer->city->name = 'q';
		$logger->clear();
		R::store($i);
		$numberOfUpdateQueries = $logger->grep( 'UPDATE' );
		//print_r($logger->getLogs());
		asrt( count( $numberOfUpdateQueries ), 1 );
		$i = $i->fresh();
		asrt( $i->customer->city->name, 'q' );
		//do we properly skip unmodified but tainted parent beans?
		$i->status = 3;
		$logger->clear();
		R::store( $i );
		$numberOfUpdateQueries = $logger->grep( 'UPDATE' );
		asrt( count( $numberOfUpdateQueries ), 1 );
	}

	/**
	 * Test whether the number of update queries
	 * executed is limited to the ones that are absolutely
	 * necessary to sync the database.
	 *
	 * @return void
	 */
	public function testUpdateQueries()
	{
		$book = R::dispense( 'book' );
		$book->title = 'Eye of Wight';
		$book->xownPageList = R::dispense( 'page', 10 );
		$book->sharedCategoryList = R::dispense( 'category', 2 );
		$n = 1;
		foreach( $book->xownPageList as $page ) {
			$page->number = $n++;
		}
		$book->sharedCategory[0]->name = 'adventure';
		$book->sharedCategory[1]->name = 'puzzle';
		$book->author = R::dispense( 'author' );
		$book->author->name = 'John';
		$book->map = R::dispense( 'map' );
		$book->map->name = 'Wight';
		$book->map->xownLocationList = R::dispense( 'location', 3 );
		asrt( $book->getMeta('tainted'), TRUE );
		asrt( $book->getMeta('changed'), TRUE );
		R::store( $book );
		asrt( $book->getMeta('tainted'), FALSE );
		asrt( $book->getMeta('changed'), FALSE );
		$logger = R::debug( 1, 1 );
		$book = $book->fresh();
		asrt( $book->getMeta('tainted'), FALSE );
		asrt( $book->getMeta('changed'), FALSE );
		$book->author;
		asrt( $book->getMeta('tainted'), TRUE );
		asrt( $book->getMeta('changed'), FALSE );
		$logger->clear();
		R::store( $book );
		//read only, no updates
		$numberOfUpdateQueries = $logger->grep( 'UPDATE' );
		asrt( count( $numberOfUpdateQueries ), 0 );
		$book->title = 'Spirit of the Stones';
		R::store( $book );
		//changed title, 1 update
		$numberOfUpdateQueries = $logger->grep( 'UPDATE' );
		asrt( count( $numberOfUpdateQueries ), 1 );
		$logger->clear();
		//store again, no changes, no updates
		R::store( $book );
		$numberOfUpdateQueries = $logger->grep( 'UPDATE' );
		asrt( count( $numberOfUpdateQueries ), 0 );
		$logger->clear();
		$book = $book->fresh();
		$book->xownPageList;
		asrt( $book->getMeta('tainted'), TRUE );
		asrt( $book->getMeta('changed'), FALSE );
		R::store( $book );
		//access only, no update
		$numberOfUpdateQueries = $logger->grep( 'UPDATE' );
		asrt( count( $numberOfUpdateQueries ), 0 );
		$logger->clear();
		$book = $book->fresh();
		$book->sharedCategoryList;
		asrt( $book->getMeta('tainted'), TRUE );
		asrt( $book->getMeta('changed'), FALSE );
		R::store( $book );
		//access only, no update
		$numberOfUpdateQueries = $logger->grep( 'UPDATE' );
		asrt( count( $numberOfUpdateQueries ), 0 );
		$logger->clear();
		$book = $book->fresh();
		unset($book->xownPageList[5]);
		asrt( $book->getMeta('tainted'), TRUE );
		asrt( $book->getMeta('changed'), FALSE );
		R::store( $book );
		//remove only, no update, just 1 delete
		$numberOfUpdateQueries = $logger->grep( 'UPDATE' );
		asrt( count( $numberOfUpdateQueries ), 0 );
		$numberOfUpdateQueries = $logger->grep( 'DELETE' );
		asrt( count( $numberOfUpdateQueries ), 1 );
		$book = $book->fresh();
		asrt( count( $book->xownPageList ), 9 );
		$logger->clear();
		$book = $book->fresh();
		$book->xownPageList[] = R::dispense('page');
		asrt( $book->getMeta('tainted'), TRUE );
		asrt( $book->getMeta('changed'), FALSE );
		R::store( $book );
		//no update, 1 insert, just adding
		$numberOfUpdateQueries = $logger->grep( 'UPDATE' );
		asrt( count( $numberOfUpdateQueries ), 0 );
		$numberOfUpdateQueries = $logger->grep( 'INSERT' );
		asrt( count( $numberOfUpdateQueries ), 1 );
		$book = $book->fresh();
		asrt( count( $book->xownPageList ), 10 );
		$logger->clear();
		$book = $book->fresh();
		$book->map->xownLocationList[1]->name = 'Horshoe Bay';
		asrt( $book->getMeta('tainted'), TRUE );
		asrt( $book->getMeta('changed'), FALSE );
		asrt( $book->map->getMeta('tainted'), TRUE );
		asrt( $book->map->getMeta('changed'), FALSE );
		asrt( $book->map->xownLocationList[1]->getMeta('tainted'), TRUE );
		asrt( $book->map->xownLocationList[1]->getMeta('changed'), TRUE );
		R::store( $book );
		//1 update for child of parent, no other updates
		$numberOfUpdateQueries = $logger->grep( 'UPDATE' );
		asrt( count( $numberOfUpdateQueries ), 1 );
		$book = $book->fresh();
		asrt( $book->map->xownLocationList[1]->name, 'Horshoe Bay' );
		$logger->clear();
		R::store( $book );
		//just access, no updates
		$numberOfUpdateQueries = $logger->grep( 'UPDATE' );
		asrt( count( $numberOfUpdateQueries ), 0 );
		$logger->clear();
		$book = $book->fresh();
		$book->ownPageList[2]->number = 99;
		R::store( $book );
		//1 update, do not update rest of pages or book itself
		$numberOfUpdateQueries = $logger->grep( 'UPDATE' );
		asrt( count( $numberOfUpdateQueries ), 1 );
		$book = $book->fresh();
		$book->author->name = 'Worsley';
		$logger->clear();
		R::store( $book );
		//1 update for parent
		$numberOfUpdateQueries = $logger->grep( 'UPDATE' );
		asrt( count( $numberOfUpdateQueries ), 1 );
		$author = R::dispense('author');
		$author->name = 'J.W.';
		R::store( $author );
		$book = $book->fresh();
		$book->author = $author;
		$author->name = 'JW';
		$logger->clear();
		R::store( $book );
		//2 updates, one for author, one for link field: author_id needs update.
		$numberOfUpdateQueries = $logger->grep( 'UPDATE' );
		asrt( count( $numberOfUpdateQueries ), 2 );
		$author->country = R::dispense( 'country' )->setAttr( 'name', 'England' );
		R::store( $author );
		$book = $book->fresh();
		$book->author->country->name = 'Wight';
		$logger->clear();
		R::store( $book );
		//1 update, country only, dont update for intermediate parents: book -> author -> ...
		$numberOfUpdateQueries = $logger->grep( 'UPDATE' );
		asrt( count( $numberOfUpdateQueries ), 1 );
	}

	/**
	 * Tests effects of importFrom and setProperty.
	 *
	 * @return void
	 */
	public function testImportFromAndSetProp()
	{
		$bean = R::dispense( 'bean' );
		asrt( $bean->getMeta( 'tainted' ), TRUE );
		asrt( $bean->getMeta( 'changed' ), TRUE );
		$bean->setMeta( 'tainted', FALSE );
		$bean->setMeta( 'changed', FALSE );
		asrt( $bean->getMeta( 'tainted' ), FALSE );
		asrt( $bean->getMeta( 'changed' ), FALSE );
		$bean->importFrom( R::dispense( 'bean' ) );
		asrt( $bean->getMeta( 'tainted' ), TRUE );
		asrt( $bean->getMeta( 'changed' ), TRUE );
		$bean->setMeta( 'tainted', FALSE );
		$bean->setMeta( 'changed', FALSE );
		asrt( $bean->getMeta( 'tainted' ), FALSE );
		asrt( $bean->getMeta( 'changed' ), FALSE );
		$bean->setProperty( 'id', 0, TRUE, TRUE );
		asrt( $bean->getMeta( 'tainted' ), TRUE );
		asrt( $bean->getMeta( 'changed' ), TRUE );
		$bean->setMeta( 'tainted', FALSE );
		$bean->setMeta( 'changed', FALSE );
		asrt( $bean->getMeta( 'tainted' ), FALSE );
		asrt( $bean->getMeta( 'changed' ), FALSE );
		$bean->setProperty( 'id', 0, TRUE, FALSE );
		asrt( $bean->getMeta( 'tainted' ), FALSE );
		asrt( $bean->getMeta( 'changed' ), FALSE );
		$bean->name = 'x';
		asrt( $bean->getMeta( 'tainted' ), TRUE );
		asrt( $bean->getMeta( 'changed' ), TRUE );
	}

	/**
	 * Setup
	 *
	 * @return void
	 */
	private function _createBook()
	{
		R::nuke();
		$book = R::dispense( 'book' );
		$pages = R::dispense( 'page', 2 );
		$ads = R::dispense('ad', 3 );
		$tags = R::dispense( 'tag', 2 );
		$author = R::dispense( 'author' );
		$coauthor = R::dispense( 'author' );
		$book->alias( 'magazine' )->ownAd = $ads;
		$book->ownPage = $pages;
		$book->sharedTag = $tags;
		$book->via( 'connection' )->sharedUser = array( R::dispense( 'user' ) );
		$book->author = $author;
		$book->coauthor = $coauthor;
		R::store( $book );
		return $book->fresh();
	}

	/*
	 * Can we add a bean to a list?
	 *
	 * @return void
	 */
	public function testWhetherWeCanAddToLists()
	{

		$book = $this->_createBook();
		$book->ownPage[] = R::dispense( 'page' );
		R::store( $book );
		asrt( R::count('page'), 3 );
		$book = $this->_createBook();
		$book->ownPageList[] = R::dispense('page');
		R::store( $book );
		asrt( R::count('page'), 3 );
		$book = $this->_createBook();
		$book->xownPage[] = R::dispense('page');
		R::store( $book );
		asrt( R::count('page'), 3 );
		$book = $this->_createBook();
		$book->xownPageList[] = R::dispense('page');
		R::store( $book );
		asrt( R::count('page'), 3 );

		$ads = R::dispense('ad', 3 );
		$book = $this->_createBook();
		$book->alias('magazine')->ownAd = $ads;
		$book->ownPage[] = R::dispense('page');
		R::store( $book );
		asrt( R::count('ad'), 6 );
		asrt( R::count('page'), 3 );
		$ads = R::dispense('ad', 3 );
		$book = $this->_createBook();
		$book->alias('magazine')->ownAdList = $ads;
		$book->ownPageList[] = R::dispense('page');
		R::store( $book );
		asrt( R::count('ad'), 6 );
		asrt( R::count('page'), 3 );
		$ads = R::dispense('ad', 3 );
		$book = $this->_createBook();
		$book->alias('magazine')->xownAd = $ads;
		$book->xownPage[] = R::dispense('page');
		R::store( $book );
		asrt( R::count('ad'), 3 );
		asrt( R::count('page'), 3 );
		$ads = R::dispense('ad', 3 );
		$book = $this->_createBook();
		$book->alias('magazine')->xownAdList = $ads;
		$book->xownPageList[] = R::dispense('page');
		R::store( $book );
		asrt( R::count('ad'), 3 );
		asrt( R::count('page'), 3 );

		$book = $this->_createBook();
		$book->sharedTag[] = R::dispense('tag');
		R::store( $book );
		asrt( R::count('tag'), 3 );
		$book = $this->_createBook();
		$book->sharedTagList[] = R::dispense('tag');
		R::store( $book );
		asrt( R::count('tag'), 3 );
	}

	/**
	 * Can we delete a bean in a list by its ID?
	 * Only the UNSET() variant should work.
	 *
	 * @return void
	 */
	public function testDeleteByIDs()
	{
		$book = $this->_createBook();
		$firstPage = reset( $book->ownPageList );
		$book->ownPage[ $firstPage->id ] = NULL;
		try { R::store( $book ); fail(); }catch(\Exception $e) { pass(); }
		$book = $this->_createBook();
		asrt( count( $book->ownPage ), 2 );
		$firstPage = reset( $book->ownPageList );
		unset( $book->ownPage[ $firstPage->id ] );
		R::store( $book );
		$book = $book->fresh();
		asrt( count( $book->ownPage ), 1 );
		$firstPage = reset( $book->ownPageList );
		$book->ownPage[ $firstPage->id ] = FALSE;
		try { R::store( $book ); fail(); }catch(\Exception $e) { pass(); }
		$book = $book->fresh();
		asrt( count( $book->ownPage ), 0 );

		$book = $this->_createBook();
		$firstAd = reset( $book->alias('magazine')->ownAd );
		$book->alias('magazine')->ownAd[ $firstAd->id ] = NULL;
		try { R::store( $book ); fail(); }catch(\Exception $e) { pass(); }
		$book = $this->_createBook();
		asrt( count( $book->alias('magazine')->ownAd ), 3 );
		$firstAd = reset( $book->alias('magazine')->ownAdList );
		unset( $book->alias('magazine')->ownAdList[ $firstAd->id ] );
		R::store( $book );
		$book = $book->fresh();
		asrt( count( $book->alias('magazine')->ownAd ), 2 );
		$firstAd = reset( $book->alias('magazine')->ownAd );
		$book->alias('magazine')->ownAd[ $firstAd->id ] = FALSE;
		try { R::store( $book ); fail(); }catch(\Exception $e) { pass(); }
		$book = $book->fresh();
		asrt( count( $book->alias('magazine')->ownAd ), 1 );

	}

	/**
	 * You CAN delete an own-list by assiging an empty array.
	 *
	 * @return void
	 */
	public function testDeleteOwnListWithEmptyArray()
	{
		$book = $this->_createBook();
		asrt( isset($book->ownPage), FALSE ); //not loaded yet, lazy loading
		asrt( count( $book->ownPage ), 2 ); //when loaded has 2
		$book->ownPage = array(); //remove all
		R::store( $book );
		asrt( isset($book->ownPage), FALSE ); //not loaded yet, lazy loading
		asrt( count( $book->ownPage ), 0 );
	}

	/**
	 * You cannot delete an own-list by assigning NULL.
	 *
	 * @return void
	 */
	public function testCANTDeleteOwnListWithNULL()
	{
		$book = $this->_createBook();
		asrt( isset($book->ownPage), FALSE ); //not loaded yet, lazy loading
		asrt( count( $book->ownPage ), 2 ); //when loaded has 2
		$book->ownPage = NULL; //remove all
		R::store( $book );
		asrt( isset($book->ownPage), FALSE ); //not loaded yet, lazy loading
		asrt( count( $book->ownPage ), 2 );
	}

	/**
	 * You cannot delete an own-list by assigning FALSE.
	 *
	 * @return void
	 */
	public function testCANTDeleteOwnListWithFalse()
	{
		$book = $this->_createBook();
		asrt( isset($book->ownPage), FALSE ); //not loaded yet, lazy loading
		asrt( count( $book->ownPage ), 2 ); //when loaded has 2
		$book->ownPage = FALSE; //remove all
		R::store( $book );
		asrt( isset($book->ownPage), TRUE ); //not loaded yet, lazy loading
		asrt( $book->ownPage, '0' );
	}

	/**
	 * You cannot delete an own-list by unsetting it.
	 */
	public function testCANTDeleteOwnListWithUnset()
	{
		$book = $this->_createBook();
		asrt( isset($book->ownPage), FALSE ); //not loaded yet, lazy loading
		asrt( count( $book->ownPage ), 2 ); //when loaded has 2
		unset( $book->ownPage ); //does NOT remove all
		R::store( $book );
		asrt( isset($book->ownPage), FALSE ); //not loaded yet, lazy loading
		asrt( count( $book->ownPage ), 2 );
	}

	/**
	 * You CAN delete an aliased own-list by assiging an empty array.
	 *
	 * @return void
	 */
	public function testDeleteAliasedOwnListWithEmptyArray()
	{
		$book = $this->_createBook();
		asrt( isset($book->alias('magazine')->ownAd), FALSE ); //not loaded yet, lazy loading
		asrt( count( $book->alias('magazine')->ownAd ), 3 ); //when loaded has 2
		$book->alias('magazine')->ownAd = array(); //remove all
		$book->ownPage[] = R::dispense('page');
		R::store( $book );
		asrt( isset($book->alias('magazine')->ownAd), FALSE ); //not loaded yet, lazy loading
		asrt( count( $book->alias('magazine')->ownAd ), 0 );
		asrt( count( $book->alias('magazine')->ownPage ), 0 ); //also test possible confusion
		asrt( count( $book->all()->ownPageList ), 3 );
	}

	/**
	 * You cannot delete an aliased own-list by assigning NULL.
	 *
	 * @return void
	 */
	public function testCANTDeleteAliasedOwnListWithNULL()
	{
		$book = $this->_createBook();
		asrt( isset($book->alias('magazine')->ownAd), FALSE ); //not loaded yet, lazy loading
		asrt( count( $book->alias('magazine')->ownAd ), 3 ); //when loaded has 2
		$book->alias('magazine')->ownAd = NULL; //remove all
		R::store( $book );
		asrt( isset($book->alias('magazine')->ownAd), FALSE ); //not loaded yet, lazy loading
		asrt( count( $book->alias('magazine')->ownAd ), 3 );
	}

	/**
	 * You cannot delete an aliased own-list by assigning FALSE.
	 *
	 * @return void
	 */
	public function testCANTDeleteAliasedOwnListWithFalse()
	{
		$book = $this->_createBook();
		asrt( isset($book->alias('magazine')->ownAd), FALSE ); //not loaded yet, lazy loading
		asrt( count( $book->alias('magazine')->ownAd ), 3 ); //when loaded has 2
		$book->alias('magazine')->ownAd = FALSE; //remove all
		R::store( $book );
		asrt( isset($book->alias('magazine')->ownAd), TRUE ); //not loaded yet, lazy loading
		asrt( $book->alias('magazine')->ownAd, '0' );
	}

	/**
	 * You cannot delete an aliased own-list by unsetting it.
	 *
	 * @return void
	 */
	public function testCANTDeleteAliasedOwnListWithUnset()
	{
		$book = $this->_createBook();
		asrt( isset($book->alias('magazine')->ownAd), FALSE ); //not loaded yet, lazy loading
		asrt( count( $book->alias('magazine')->ownAd ), 3 ); //when loaded has 2
		unset( $book->alias('magazine')->ownAd ); //does NOT remove all
		R::store( $book );
		asrt( isset($book->alias('magazine')->ownAd), FALSE ); //not loaded yet, lazy loading
		asrt( count( $book->alias('magazine')->ownAd ), 3 );
	}

	/**
	 * You CAN delete an x-own-list by assiging an empty array.
	 *
	 * @return void
	 */
	public function testDeleteXOwnListWithEmptyArray()
	{
		$book = $this->_createBook();
		asrt( isset($book->xownPage), FALSE ); //not loaded yet, lazy loading
		asrt( count( $book->xownPage ), 2 ); //when loaded has 2
		$book->xownPage = array(); //remove all
		R::store( $book );
		asrt( isset($book->xownPage), FALSE ); //not loaded yet, lazy loading
		asrt( count( $book->xownPage ), 0 );
	}

	/**
	 * You cannot delete an x-own-list by assigning NULL.
	 *
	 * @return  void
	 */
	public function testCANTDeleteXOwnListWithNULL()
	{
		$book = $this->_createBook();
		asrt( isset($book->xownPage), FALSE ); //not loaded yet, lazy loading
		asrt( count( $book->xownPage ), 2 ); //when loaded has 2
		$book->xownPage = NULL; //remove all
		R::store( $book );
		asrt( isset($book->xownPage), FALSE ); //not loaded yet, lazy loading
		asrt( count( $book->xownPage ), 2 );
	}

	/**
	 * You cannot delete an x-own-list by assigning FALSE.
	 *
	 * @return void
	 */
	public function testCANTDeleteXOwnListWithFalse()
	{
		$book = $this->_createBook();
		asrt( isset($book->xownPage), FALSE ); //not loaded yet, lazy loading
		asrt( count( $book->xownPage ), 2 ); //when loaded has 2
		$book->xownPage = FALSE; //remove all
		R::store( $book );
		asrt( isset($book->xownPage), TRUE ); //not loaded yet, lazy loading
		asrt( $book->xownPage, '0' );
	}

	/**
	 * You cannot delete an x-own-list by unsetting it.
	 *
	 * @return void
	 */
	public function testCANTDeleteXOwnListWithUnset()
	{
		$book = $this->_createBook();
		asrt( isset($book->xownPage), FALSE ); //not loaded yet, lazy loading
		asrt( count( $book->xownPage ), 2 ); //when loaded has 2
		unset( $book->xownPage ); //does NOT remove all
		R::store( $book );
		asrt( isset($book->xownPage), FALSE ); //not loaded yet, lazy loading
		asrt( count( $book->xownPage ), 2 );
	}

	/**
	 * You CAN delete a shared-list by assiging an empty array.
	 *
	 * @return void
	 */
	public function testDeleteSharedListWithEmptyArray()
	{
		$book = $this->_createBook();
		asrt( isset($book->sharedTag), FALSE ); //not loaded yet, lazy loading
		asrt( count( $book->sharedTag ), 2 ); //when loaded has 2
		$book->sharedTag = array(); //remove all
		R::store( $book );
		asrt( isset($book->sharedTag), FALSE ); //not loaded yet, lazy loading
		asrt( count( $book->sharedTag ), 0 );
	}

	/**
	 * You cannot delete a shared list by assigning NULL.
	 *
	 * @return void
	 */
	public function testCANTDeleteSharedListWithNULL()
	{
		$book = $this->_createBook();
		asrt( isset($book->sharedTag), FALSE ); //not loaded yet, lazy loading
		asrt( count( $book->sharedTag ), 2 ); //when loaded has 2
		$book->sharedTag = NULL; //remove all
		R::store( $book );
		asrt( isset($book->sharedTag), FALSE ); //not loaded yet, lazy loading
		asrt( count( $book->sharedTag ), 2 );
	}

	/**
	 * You cannot delete a shared-list by assigning FALSE.
	 *
	 * @return void
	 */
	public function testCANTDeleteSharedListWithFalse()
	{
		$book = $this->_createBook();
		asrt( isset($book->sharedTag), FALSE ); //not loaded yet, lazy loading
		asrt( count( $book->sharedTag ), 2 ); //when loaded has 2
		$book->sharedTag = FALSE; //remove all
		R::store( $book );
		asrt( isset($book->sharedTag), TRUE ); //not loaded yet, lazy loading
		asrt( $book->sharedTag, '0' );
	}

	/**
	 * You cannot delete a shared-list by unsetting it.
	 *
	 * @return void
	 */
	public function testCANTDeleteSharedWithUnset()
	{
		$book = $this->_createBook();
		asrt( isset($book->sharedTag), FALSE ); //not loaded yet, lazy loading
		asrt( count( $book->sharedTag ), 2 ); //when loaded has 2
		unset( $book->sharedTag ); //does NOT remove all
		R::store( $book );
		asrt( isset($book->sharedTag), FALSE ); //not loaded yet, lazy loading
		asrt( count( $book->sharedTag ), 2 );
	}

	/**
	 * You CAN delete a shared-list by assiging an empty array.
	 *
	 * @return void
	 */
	public function testDeleteViaSharedListWithEmptyArray()
	{
		$book = $this->_createBook();
		asrt( isset($book->via('connection')->sharedUser), FALSE ); //not loaded yet, lazy loading
		asrt( count( $book->via('connection')->sharedUser ), 1 ); //when loaded has 2
		$book->via('connection')->sharedUser = array(); //remove all
		R::store( $book );
		asrt( isset($book->via('connection')->sharedUser), FALSE ); //not loaded yet, lazy loading
		asrt( count( $book->via('connection')->sharedUser ), 0 );
	}

	/**
	 * You cannot delete a shared-list by assigning NULL.
	 *
	 * @return void
	 */
	public function testCANTDeleteViaSharedListWithNULL()
	{
		$book = $this->_createBook();
		asrt( isset($book->via('connection')->sharedUser), FALSE ); //not loaded yet, lazy loading
		asrt( count( $book->via('connection')->sharedUser ), 1 ); //when loaded has 2
		$book->via('connection')->sharedUser = NULL; //remove all
		R::store( $book );
		asrt( isset($book->via('connection')->sharedUser), FALSE ); //not loaded yet, lazy loading
		asrt( count( $book->via('connection')->sharedUser ), 1 );
	}

	/**
	 * You cannot delete a shared list by assigning FALSE.
	 *
	 * @return void
	 */
	public function testCANTDeleteViaSharedListWithFalse()
	{
		$book = $this->_createBook();
		asrt( isset($book->via('connection')->sharedUser), FALSE ); //not loaded yet, lazy loading
		asrt( count( $book->via('connection')->sharedUser ), 1 ); //when loaded has 1
		$book->via('connection')->sharedUser = FALSE; //remove all
		R::store( $book );
		asrt( isset($book->via('connection')->sharedUser), TRUE ); //not loaded yet, lazy loading
		asrt( count( $book->via('connection')->sharedUser ), 1 ); //when loaded has 1

	}

	/**
	 * You cannot delete a shared-list by unsetting it.
	 *
	 * @return void
	 */
	public function testCANTDeleteViaSharedWithUnset()
	{
		$book = $this->_createBook();
		asrt( isset($book->via('connection')->sharedUser), FALSE ); //not loaded yet, lazy loading
		asrt( count( $book->via('connection')->sharedUser ), 1 ); //when loaded has 2
		unset( $book->via('connection')->sharedUser ); //does NOT remove all
		R::store( $book );
		asrt( isset($book->via('connection')->sharedUser), FALSE ); //not loaded yet, lazy loading
		asrt( count( $book->via('connection')->sharedUser ), 1 );
	}

	/**
	 * You cannot delete a parent bean by unsetting it.
	 *
	 * @return void
	 */
	public function testYouCANTDeleteParentBeanWithUnset()
	{
		$book = $this->_createBook();
		asrt( isset($book->author), FALSE );
		asrt( (boolean) ($book->author), TRUE );
		unset( $book->author );
		R::store( $book );
		$book = $book->fresh();
		asrt( isset($book->author), FALSE );
		asrt( (boolean) ($book->author), TRUE );
	}

	/**
	 * You cannot delete a parent bean by setting it to NULL.
	 *
	 * @return void
	 */
	public function testYouCANDeleteParentBeanWithNULL()
	{
		$book = $this->_createBook();
		asrt( isset($book->author), FALSE );
		asrt( (boolean) ($book->author), TRUE );
		$book->author = NULL;
		R::store( $book );
		$book = $book->fresh();
		asrt( isset($book->author), FALSE );
		asrt( (boolean) ($book->author), FALSE );
	}

	/**
	 * You CAN delete a parent bean by setting it to FALSE.
	 *
	 * @return void
	 */
	public function testYouCANDeleteParentBeanWithFALSE()
	{
		$book = $this->_createBook();
		asrt( isset($book->author), FALSE );
		asrt( (boolean) ($book->author), TRUE );
		$book->author = FALSE;
		R::store( $book );
		$book = $book->fresh();
		asrt( isset($book->author), FALSE );
		asrt( (boolean) ($book->author), FALSE );
	}

	/**
	 * You cannot delete an aliased parent bean by unsetting it.
	 *
	 * @return void
	 */
	public function testYouCANTDeleteAliasedParentBeanWithUnset()
	{
		$book = $this->_createBook();
		asrt( isset($book->fetchAs('author')->coauthor), FALSE );
		asrt( (boolean) ($book->fetchAs('author')->coauthor), TRUE );
		unset( $book->fetchAs('author')->coauthor );
		R::store( $book );
		$book = $book->fresh();
		asrt( isset($book->fetchAs('author')->coauthor), FALSE );
		asrt( (boolean) ($book->fetchAs('author')->coauthor), TRUE );
	}

	/**
	 * You CAN delete an aliased parent bean by setting it to NULL.
	 *
	 * @return void
	 */
	public function testYouCANDeleteAliasedParentBeanWithNULL()
	{
		$book = $this->_createBook();
		asrt( isset($book->fetchAs('author')->coauthor), FALSE );
		asrt( (boolean) ($book->fetchAs('author')->coauthor), TRUE );
		$book->fetchAs('author')->coauthor = NULL;
		R::store( $book );
		$book = $book->fresh();
		asrt( isset($book->fetchAs('author')->coauthor), FALSE );
		asrt( (boolean) ($book->fetchAs('author')->coauthor), FALSE );
	}

	/**
	 * You cannot delete an aliased parent bean by setting it to FALSE.
	 *
	 * @return void
	 */
	public function testYouCANDeleteAliasedParentBeanWithFALSE()
	{
		$book = $this->_createBook();
		asrt( isset($book->fetchAs('author')->coauthor), FALSE );
		asrt( (boolean) ($book->fetchAs('author')->coauthor), TRUE );
		$book->fetchAs('author')->coauthor = FALSE;
		R::store( $book );
		$book = $book->fresh();
		asrt( isset($book->fetchAs('author')->coauthor), FALSE );
		asrt( (boolean) ($book->fetchAs('author')->coauthor), FALSE );
	}

	/**
	 * Tests the effects of unsetting on the shadow of a list.
	 *
	 * @return void
	 */
	public function testUnsettingAListAndShadow()
	{
		$book = $this->_createBook();
		//should work with ownPage and ownPageList as well...
		unset( $book->ownPageList );
		R::store( $book );
		$book = $book->fresh();
		asrt( count( $book->ownPage ), 2 );
		unset( $book->ownPage );
		//shadow should be reloaded as well...
		$book->with(' LIMIT 1 ')->ownPage;
		R::store( $book );
		$book = $book->fresh();
		asrt( count( $book->ownPage ), 2 );
		asrt( count( $book->getMeta('sys.shadow.ownPage') ), 2 );
		unset( $book->ownPage );
		asrt( $book->getMeta('sys.shadow.ownPage'), NULL );
		//no load must clear shadow as well...
		$book->noLoad()->ownPage[] = R::dispense( 'page' );
		asrt( count( $book->getMeta('sys.shadow.ownPage') ), 0 );
		R::store( $book );
		$book = $book->fresh();
		asrt( count( $book->ownPage ), 3 );
		$lists = array( 'ownPage', 'ownPageList', 'xownPage', 'xownPageList', 'sharedPage', 'sharedPageList' );
		foreach( $lists as $list ) {
			$book = R::dispense( 'book' );
			$book->$list;
			$shadowKey = $list;
			if ( strpos( $list, 'x' ) === 0) $shadowKey = substr( $shadowKey, 1 );
			$shadowKey = preg_replace( '/List$/', '', $shadowKey );
			asrt( is_array( $book->getMeta('sys.shadow.'.$shadowKey) ), TRUE );
			unset( $book->$list );
			asrt( $book->getMeta('sys.shadow.'.$shadowKey), NULL );
			$book->$list; //reloading brings back shadow
			asrt( is_array( $book->getMeta('sys.shadow.'.$shadowKey) ), TRUE );
			$book->$list = array(); //keeps shadow (very important to compare deletions!)
			asrt( is_array( $book->getMeta('sys.shadow.'.$shadowKey) ), TRUE );
			R::store( $book ); //clears shadow
			$book->alias('magazine')->$list; //reloading with alias also brings back shadow
			unset( $book->$list );
			asrt( $book->getMeta('sys.shadow.'.$shadowKey), NULL );
			$book = $book->fresh(); //clears shadow, reload
			asrt( $book->getMeta('sys.shadow.'.$shadowKey), NULL );
			$book->noLoad()->$list; //reloading with noload also brings back shadow
			asrt( is_array( $book->getMeta('sys.shadow.'.$shadowKey) ), TRUE );
			asrt( count( $book->getMeta('sys.shadow.'.$shadowKey) ), 0 );
			$book = $book->fresh(); //clears shadow, reload
			asrt( $book->getMeta('sys.shadow.'.$shadowKey), NULL );
			$book->all()->$list; //reloading with all also brings back shadow
			asrt( is_array( $book->getMeta('sys.shadow.'.$shadowKey) ), TRUE );
			$book = $book->fresh(); //clears shadow, reload
			asrt( $book->getMeta('sys.shadow.'.$shadowKey), NULL );
			$book->with(' LIMIT 1 ')->$list; //reloading with with- all also brings back shadow
			asrt( is_array( $book->getMeta('sys.shadow.'.$shadowKey) ), TRUE );
			$book = $book->fresh(); //clears shadow, reload
			asrt( $book->getMeta('sys.shadow.'.$shadowKey), NULL );
			$book->$list = array(); //keeps shadow (very important to compare deletions!)
			asrt( is_array( $book->getMeta('sys.shadow.'.$shadowKey) ), TRUE );
			$book = $book->fresh(); //clears shadow, reload
			asrt( $book->getMeta('sys.shadow.'.$shadowKey), NULL );
			$book->$list = array(); //keeps shadow (very important to compare deletions!)
			asrt( is_array( $book->getMeta('sys.shadow.'.$shadowKey) ), TRUE );
			R::trash( $book );
			asrt( $book->getMeta('sys.shadow.'.$shadowKey), NULL );
		}

		//no shadow for parent bean
		$book = $book->fresh();
		$book->author = R::dispense( 'author' );
		asrt( $book->getMeta('sys.shadow.author'), NULL );
		R::store( $book );
		$book = $book->fresh();
		unset( $book->author ); //we can unset and it does not remove
		R::store( $book );
		$book = $book->fresh();
		asrt( is_object( $book->author ),TRUE );
		//but we can also remove
		$book->author = NULL;
		R::store( $book );
		$book = $book->fresh();
		asrt( $book->author, NULL );

	}

	/**
	 * Test whether the tainted flag gets set correctly.
	 *
	 * @return void
	 */
	public function testAccessingTainting()
	{
		$book = $this->_createBook();
		asrt( $book->isTainted(), FALSE );
		$book->ownPage;
		asrt( $book->isTainted(), TRUE );
		$book = $book->fresh();
		asrt( $book->isTainted(), FALSE );
		$book->author;
		asrt( $book->isTainted(), TRUE );
		$book = $book->fresh();
		asrt( $book->isTainted(), FALSE );
		$book->fetchAs('author')->coauthor;
		asrt( $book->isTainted(), TRUE );
		$book = $book->fresh();
		asrt( $book->isTainted(), FALSE );
		$book->alias('magazine')->xownAdList;
		asrt( $book->isTainted(), TRUE );
		$book = $book->fresh();
		asrt( $book->isTainted(), FALSE );
		$book->title = 'Hello';
		asrt( $book->isTainted(), TRUE );
		$book = $book->fresh();
		asrt( $book->isTainted(), FALSE );
		$book->sharedTag;
		asrt( $book->isTainted(), TRUE );
		$book = $book->fresh();
		asrt( $book->isTainted(), FALSE );
		$book->via('connection')->sharedUser;
		asrt( $book->isTainted(), TRUE );
		$book = $book->fresh();
		asrt( $book->isTainted(), FALSE );
		$book->coauthor;
		asrt( $book->isTainted(), TRUE );
		$book = $book->fresh();
		asrt( $book->isTainted(), FALSE );
		$book->ownFakeList;
		asrt( $book->isTainted(), TRUE );
		$book = $book->fresh();
		asrt( $book->isTainted(), FALSE );
		$book->sharedFakeList;
		asrt( $book->isTainted(), TRUE );
		$book = $book->fresh();
		asrt( $book->isTainted(), FALSE );
		$book->alias('fake')->ownFakeList;
		asrt( $book->isTainted(), TRUE );
		$book = $book->fresh();
		asrt( $book->isTainted(), FALSE );
		$book->title;
		asrt( $book->isTainted(), FALSE );
		$book = $book->fresh();
		asrt( $book->isTainted(), FALSE );
		$book->title = 1;
		$book->setMeta( 'tainted', FALSE );
		asrt( $book->isTainted(), FALSE );
	}
}
