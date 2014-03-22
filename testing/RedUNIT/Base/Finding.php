<?php 

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;
use RedBeanPHP\AssociationManager as AssociationManager;
use RedBeanPHP\OODB as OODB;
use RedBeanPHP\RedException as RedException; 
use RedBeanPHP\RedException\SQL as SQL;

/**
 * RedUNIT_Base_Finding
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
