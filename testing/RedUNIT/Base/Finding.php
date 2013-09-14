<?php
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
class RedUNIT_Base_Finding extends RedUNIT_Base {
	
	/**
	 * Begin testing.
	 * This method runs the actual test pack.
	 * 
	 * @return void
	 */
	public function testFinding() {
		
		$toolbox = R::$toolbox;
		$adapter = $toolbox->getDatabaseAdapter();
		$writer  = $toolbox->getWriter();
		$redbean = $toolbox->getRedBean();
		$pdo     = $adapter->getDatabase();

		$a = new RedBean_AssociationManager( $toolbox );

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

		$redbean->find( "wine", array( "id" => 5 ) ); //  Finder:where call RedBean_OODB::convertToBeans

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
		} catch ( RedBean_Exception_Security $exception ) {
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

		//Basics, load child nodes in tree
		$referencePage = R::load( 'page', $id );
		$found         = $referencePage->searchIn( 'ownPage' );

		$foundStr      = '';
		$foundItems = array();

		foreach( $found as $foundBean ) {
			$foundItems[] = $foundBean->name;
		}

		sort( $foundItems );
		$foundStr = implode( '', $foundItems );

		asrt( $foundStr, 'page1page2page3page4page5page7' );

		//A subset of a tree...
		$referencePage = R::load( 'page', $page[3]->id );
		$found         = $referencePage->searchIn( 'ownPage' );

		$foundStr      = '';
		$foundItems = array();

		foreach( $found as $foundBean ) {
			$foundItems[] = $foundBean->name;
		}

		sort( $foundItems );
		$foundStr = implode( '', $foundItems );

		asrt( $foundStr, 'page5page7' );

		//Now test with a condition
		$referencePage = R::load( 'page', $page[1]->id );
		$found         = $referencePage->withCondition(' page.number > 6 ')->searchIn( 'ownPage' );
		
		$foundStr      = '';
		$foundItems = array();

		foreach( $found as $foundBean ) {
			$foundItems[] = $foundBean->name;
		}

		sort( $foundItems );
		$foundStr = implode( '', $foundItems );
		
		asrt($foundStr, 'page7');

		//Condition and slot
		$referencePage = R::load( 'page', $page[1]->id );
		$found         = $referencePage->withCondition(' page.number > ? ', array( 6 ) )->searchIn( 'ownPage' );

		$foundStr      = '';
		$foundItems = array();

		foreach( $found as $foundBean ) {
			$foundItems[] = $foundBean->name;
		}

		sort( $foundItems );
		$foundStr = implode( '', $foundItems );

		asrt($foundStr, 'page7');

		//Condition and named slot
		$referencePage = R::load( 'page', $page[1]->id );
		$found         = $referencePage->withCondition(' page.number > :num ', array( ':num' => 6 ) )->searchIn( 'ownPage' );

		$foundStr      = '';
		$foundItems = array();

		foreach( $found as $foundBean ) {
			$foundItems[] = $foundBean->name;
		}

		sort( $foundItems );
		$foundStr = implode( '', $foundItems );

		asrt($foundStr, 'page7');
		
		//Ordering... works different, orders within a set!
		$referencePage = R::load( 'page', $page[0]->id );
		$found         = $referencePage->with(' ORDER BY page.number DESC ')->searchIn( 'ownPage' );

		$foundStr      = '';
		$foundItems = array();

		foreach( $found as $foundBean ) {
			$foundItems[] = $foundBean->name;
		}

		$foundStr = implode( '', $foundItems );

		asrt($foundStr, 'page2page1page4page3page5page7');

		//now with parents
		$referencePage = R::load( 'page', $page[5]->id );
		$found         = $referencePage->searchIn( 'page' );

		$foundStr      = '';
		$foundItems = array();

		foreach( $found as $foundBean ) {
			$foundItems[] = $foundBean->name;
		}

		sort( $foundItems );
		$foundStr = implode( '', $foundItems );

		asrt( $foundStr, 'page0page1page3' );

		//now with parents and aliases...
		$otherPage           = R::dispense( 'page' );
		$otherPage->document = $page[0];
		$otherPage->name     = 'pagex';
		$page[0]->document   = $page[6];
		$page[6]->document   = $page[8];

		R::store( $otherPage );

		$referencePage = R::load( 'page', $otherPage->id );
		$found = $referencePage->fetchAs( 'page' )
				  ->searchIn( 'document' );

		$foundStr      = '';
		$foundItems = array();

		foreach( $found as $foundBean ) {
			$foundItems[] = $foundBean->name;
		}

		sort( $foundItems );
		$foundStr = implode( '', $foundItems );

		asrt( $foundStr, 'page0page6page8' );

		//now with parents and condition
		$referencePage = R::load( 'page', $otherPage->id );
		$found = $referencePage->withCondition(' page.number > 6 ')
				  ->fetchAs( 'page' )
				  ->searchIn( 'document' );

		$foundStr      = '';
		$foundItems = array();

		foreach( $found as $foundBean ) {
			$foundItems[] = $foundBean->name;
		}

		sort( $foundItems );
		$foundStr = implode( '', $foundItems );

		asrt( $foundStr, 'page8' );

		//now with parents and condition (variation)
		R::$writer->setUseCache(FALSE);
		$referencePage = R::load( 'page', $page[7]->id );
		$found = $referencePage->withCondition(' ( page.number < ? OR  page.number = 5 ) ', array( 3 ) )
				  ->searchIn( 'page' );

		$foundStr      = '';
		$foundItems = array();

		foreach( $found as $foundBean ) {
			$foundItems[] = $foundBean->name;
		}

		sort( $foundItems );
		$foundStr = implode( '', $foundItems );
		
		asrt( $foundStr, 'page0page1page5' );

		//try to cause a cache error... (parent cache)
		$referencePage = R::load( 'page', $otherPage->id );
		
		$parentPage = $referencePage->fetchAs('page')->document;
		$referencePages = $parentPage->alias('document')
				  ->withCondition(' id = ?', array($otherPage->id))
				  ->ownPage;
		
		$referencePage = reset($referencePages);

		$found = $referencePage->withCondition(' page.number > 6 ')
				  ->fetchAs( 'page' )
				  ->searchIn( 'document' );

		$foundStr      = '';
		$foundItems = array();

		foreach( $found as $foundBean ) {
			$foundItems[] = $foundBean->name;
		}

		sort( $foundItems );
		$foundStr = implode( '', $foundItems );

		asrt( $foundStr, 'page8' );

		$referencePage = R::load( 'page', $page[8]->id );
		$found         = $referencePage->alias( 'document' )
				  ->withCondition( ' page.number > 5 ' )
				  ->searchIn( 'ownPage' );

		$foundStr      = '';
		$foundItems = array();

		foreach( $found as $foundBean ) {
			$foundItems[] = $foundBean->name;
		}

		sort( $foundItems );
		$foundStr = implode( '', $foundItems );

		asrt( $foundStr, 'page6page9' );
		
		//also test if with-condition has been cleared!
		asrt( count( $book->fresh()->ownPage ) ,1 );

		//also if you find nothing..
		$found = $referencePage->withCondition( ' page.number = 999 ' )
				  ->searchIn( 'ownPage' );

		asrt( count( $found ) ,0 );
		asrt( count( $book->ownPage ) ,1 );

		//store should not affect next search
		R::store( $referencePage );

		$referencePage = R::load( 'page', $page[8]->id );

		$found = $referencePage->alias( 'document' )->searchIn( 'ownPage' );

		$foundStr      = '';
		$foundItems = array();

		foreach( $found as $foundBean ) {
			$foundItems[] = $foundBean->name;
		}

		sort( $foundItems );
		$foundStr = implode( '', $foundItems );

		asrt( $foundStr, 'page0page6page9pagex' );

		//shared search not allowed
		try {
			$referencePage->searchIn('sharedPage');
			fail();
		} catch (RedBean_Exception_Security $exception) {
			pass();
		}
		
		//but shareditem should be allowed
		try {
			$referencePage->searchIn('sharedpage');
			pass();
		} catch (RedBean_Exception_Security $exception) {
			fail();
		}
		
	}
}
