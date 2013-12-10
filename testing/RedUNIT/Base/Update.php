<?php
/**
 * RedUNIT_Base_Update
 *
 * @file    RedUNIT/Base/Update.php
 * @desc    Tests basic storage features through OODB class.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Base_Update extends RedUNIT_Base
{
	
	/**
	 * All kinds of tests for basic CRUD.
	 * 
	 * Does the data survive?
	 * 
	 * @return void
	 */
	public function testUpdatingBeans()
	{
		testpack( 'Test basic support UUID/override ID default value' );

		$bean = R::dispense( 'bean' );

		R::store( $bean );

		if ($this->currentlyActiveDriverID === 'mysql') {
			//otherwise UTF8 causes index overflow in mysql: SQLSTATE[42000]: Syntax error or access violation: 1071 Specified key was too long; max key length is 767 bytes
			R::exec('alter table bean modify column id char(3);');
		} else {
			R::$writer->widenColumn( 'bean', 'id', R::$writer->scanType( 'abc' ) );
		}
			
		$bean->id = 'abc';

		R::store( $bean );

		asrt( $bean->id, 'abc' );

		testpack( 'Test Update' );

		try {
			R::store( array() );
			fail();
		} catch ( RedBean_Exception_Security $e ) {
			pass();
		}

		$toolbox = R::$toolbox;
		$adapter = $toolbox->getDatabaseAdapter();
		$writer  = $toolbox->getWriter();
		$redbean = $toolbox->getRedBean();
		$pdo     = $adapter->getDatabase();
		$page    = $redbean->dispense( "page" );

		$page->name = "old name";

		$id = $redbean->store( $page );

		asrt( $page->getMeta( 'tainted' ), FALSE );

		$page->setAttr( 'name', "new name" );

		asrt( $page->getMeta( 'tainted' ), TRUE );

		$id = $redbean->store( $page );

		$page = $redbean->load( "page", $id );

		asrt( $page->name, "new name" );

		// Null should == NULL after saving
		$page->rating = NULL;

		$newid = $redbean->store( $page );
		$page  = $redbean->load( "page", $id );

		asrt( $page->name, "new name" );
		asrt( ( $page->rating === NULL ), TRUE );
		asrt( !$page->rating, TRUE );

		$page->rating = FALSE;

		$newid = $redbean->store( $page );

		asrt( $newid, $id );

		$page = $redbean->load( "page", $id );

		asrt( $page->name, "new name" );
		asrt( (bool) $page->rating, FALSE );
		asrt( ( $page->rating == FALSE ), TRUE );
		asrt( !$page->rating, TRUE );

		$page->rating = TRUE;

		$newid = $redbean->store( $page );

		asrt( $newid, $id );

		$page = $redbean->load( "page", $id );

		asrt( $page->name, "new name" );
		asrt( (bool) $page->rating, TRUE );

		asrt( ( $page->rating == TRUE ), TRUE );
		asrt( ( $page->rating == TRUE ), TRUE );

		$page->rating = NULL;

		R::store( $page );

		$page = R::load( 'page', $page->id );

		asrt( $page->rating, NULL );

		$page->rating = "1";

		$newid = $redbean->store( $page );

		asrt( $newid, $id );

		$page = $redbean->load( "page", $id );

		asrt( $page->name, "new name" );
		asrt( $page->rating, "1" );

		$page->rating = "0";

		$newid = $redbean->store( $page );

		asrt( $page->rating, "0" );

		$page->rating = 0;

		$newid = $redbean->store( $page );

		asrt( $page->rating, 0 );

		$page->rating = "0";

		$newid = $redbean->store( $page );

		asrt( $newid, $id );
		$page = $redbean->load( "page", $id );

		asrt( $page->name, "new name" );
		asrt( !$page->rating, TRUE );

		asrt( ( $page->rating == 0 ), TRUE );
		asrt( ( $page->rating == FALSE ), TRUE );

		$page->rating = 5;

		$newid = $redbean->store( $page );

		asrt( $newid, $id );

		$page = $redbean->load( "page", $id );

		asrt( $page->name, "new name" );
		asrt( strval( $page->rating ), "5" );

		$page->rating = 300;

		$newid = $redbean->store( $page );

		asrt( $newid, $id );

		$page = $redbean->load( "page", $id );

		asrt( $page->name, "new name" );
		asrt( strval( $page->rating ), "300" );

		$page->rating = -2;

		$newid = $redbean->store( $page );

		asrt( $newid, $id );

		$page = $redbean->load( "page", $id );

		asrt( $page->name, "new name" );
		asrt( strval( $page->rating ), "-2" );

		$page->rating = 2.5;

		$newid = $redbean->store( $page );

		asrt( $newid, $id );

		$page = $redbean->load( "page", $id );

		asrt( $page->name, "new name" );
		asrt( ( $page->rating == 2.5 ), TRUE );

		$page->rating = -3.3;

		$newid = $redbean->store( $page );

		asrt( $newid, $id );

		$page = $redbean->load( "page", $id );

		asrt( $page->name, "new name" );
		asrt( ( $page->rating == -3.3 ), TRUE );

		$page->rating = "good";

		$newid = $redbean->store( $page );

		asrt( $newid, $id );

		$page = $redbean->load( "page", $id );

		asrt( $page->name, "new name" );
		asrt( $page->rating, "good" );

		$longtext = str_repeat( 'great! because..', 100 );

		$page->rating = $longtext;

		$newid = $redbean->store( $page );

		asrt( $newid, $id );

		$page = $redbean->load( "page", $id );

		asrt( $page->name, "new name" );
		asrt( $page->rating, $longtext );

		// Test leading zeros
		$numAsString = "0001";

		$page->numasstring = $numAsString;

		$redbean->store( $page );

		$page = $redbean->load( "page", $id );

		asrt( $page->numasstring, "0001" );

		$page->numnotstring = "0.123";

		$redbean->store( $page );

		$page = $redbean->load( "page", $id );

		asrt( $page->numnotstring == 0.123, TRUE );

		$page->numasstring2 = "00.123";

		$redbean->store( $page );

		$page = $redbean->load( "page", $id );

		asrt( $page->numasstring2, "00.123" );

		try {
			$redbean->trash( array() );

			fail();
		} catch ( RedBean_Exception_Security $e ) {
			pass();
		}

		$redbean->trash( $page );

		asrt( (int) $pdo->GetCell( "SELECT count(*) FROM page" ), 0 );
	}
}
