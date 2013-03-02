<?php
/**
 * RedUNIT_Base_Update
 * 
 * @file 			RedUNIT/Base/Update.php
 * @description		Tests basic storage features through OODB class.
 * 					This class is part of the RedUNIT test suite for RedBeanPHP.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Base_Update extends RedUNIT_Base {
	/**
	 * Begin testing.
	 * This method runs the actual test pack.
	 * 
	 * @return void
	 */
	public function run(){
		$toolbox = R::$toolbox;
		$adapter = $toolbox->getDatabaseAdapter();
		$writer  = $toolbox->getWriter();
		$redbean = $toolbox->getRedBean();
		$pdo = $adapter->getDatabase();
		$page = $redbean->dispense("page");
		$page->name = "old name";
		$id = $redbean->store($page);
		asrt($page->getMeta('tainted'),false);
		$page->setAttr('name',"new name");
		asrt($page->getMeta('tainted'),true);
		$id = $redbean->store($page);
		$page = $redbean->load( "page", $id );
		asrt( $page->name, "new name" );
		//Null should == NULL after saving
		$page->rating = null;
		$newid = $redbean->store( $page );
		$page = $redbean->load( "page", $id );
		asrt( $page->name, "new name" );
		asrt( ($page->rating === null), true );
		asrt( !$page->rating, true );
		$page->rating = false;
		$newid = $redbean->store( $page );
		asrt( $newid, $id );
		$page = $redbean->load( "page", $id );
		asrt( $page->name, "new name" );
		asrt( (bool) $page->rating, false );
		asrt( ($page->rating==false), true );
		asrt( !$page->rating, true );
		$page->rating = true;
		$newid = $redbean->store( $page );
		asrt( $newid, $id );
		$page = $redbean->load( "page", $id );
		asrt( $page->name, "new name" );
		asrt( (bool) $page->rating, true );
		asrt( ($page->rating==true), true);
		asrt( ($page->rating==true), true );
		$page->rating = null;
		R::store($page);
		$page = R::load('page',$page->id);
		asrt($page->rating,null);
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
		asrt( !$page->rating, true );
		asrt( ($page->rating==0), true );
		asrt( ($page->rating==false), true );
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
		asrt(  ( $page->rating == 2.5 ), true );
		$page->rating = -3.3;
		$newid = $redbean->store( $page );
		asrt( $newid, $id );
		$page = $redbean->load( "page", $id );
		asrt( $page->name, "new name" );
		asrt( ( $page->rating == -3.3 ), true );
		$page->rating = "good";
		$newid = $redbean->store( $page );
		asrt( $newid, $id );
		$page = $redbean->load( "page", $id );
		asrt( $page->name, "new name" );
		asrt( $page->rating, "good" );
		$longtext = str_repeat('great! because..',100);
		$page->rating = $longtext;
		$newid = $redbean->store( $page );
		asrt( $newid, $id );
		$page = $redbean->load( "page", $id );
		asrt( $page->name, "new name" );
		asrt( $page->rating, $longtext );
		//test leading zeros
		$numAsString = "0001";
		$page->numasstring = $numAsString;
		$redbean->store($page);
		$page = $redbean->load( "page", $id );
		asrt($page->numasstring,"0001");
		$page->numnotstring = "0.123";
		$redbean->store($page);
		$page = $redbean->load( "page", $id );
		asrt($page->numnotstring==0.123,true);
		$page->numasstring2 = "00.123";
		$redbean->store($page);
		$page = $redbean->load( "page", $id );
		asrt($page->numasstring2,"00.123");
		$redbean->trash( $page );
		asrt( (int) $pdo->GetCell("SELECT count(*) FROM page"), 0 );
		}
}
