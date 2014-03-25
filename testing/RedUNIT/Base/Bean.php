<?php
namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;

/**
 * Bean tests, tests various combinations of list manipulations.
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