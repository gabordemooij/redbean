<?php

namespace RedUNIT\Mysql;

use RedUNIT\Mysql as Mysql;
use RedBeanPHP\Facade as R;
use RedBeanPHP\AssociationManager as AssociationManager;
use RedBeanPHP\RedException\SQL as SQL;

/**
 * Freeze
 *
 * Tests whether database schema remains unmodified in frozen
 * mode.
 *
 * @file    RedUNIT/Mysql/Freeze.php
 * @desc    Tests freezing of databases for production environments.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Freeze extends Mysql
{
	/**
	 * Tests freezing the database.
	 * After freezing the database, schema modifications are no longer
	 * allowed and referring to missing columns will now cause exceptions.
	 *
	 * @return void
	 */
	public function testFreezer()
	{
		$toolbox = R::getToolBox();
		$adapter = $toolbox->getDatabaseAdapter();
		$writer  = $toolbox->getWriter();
		$redbean = $toolbox->getRedBean();
		$pdo     = $adapter->getDatabase();
		$a = new AssociationManager( $toolbox );
		$post = $redbean->dispense( 'post' );
		$post->title = 'title';
		$redbean->store( $post );
		$page = $redbean->dispense( 'page' );
		$page->name = 'title';
		$redbean->store( $page );
		$page = $redbean->dispense( "page" );
		$page->name = "John's page";
		$idpage = $redbean->store( $page );
		$page2 = $redbean->dispense( "page" );
		$page2->name = "John's second page";
		$idpage2 = $redbean->store( $page2 );
		$a->associate( $page, $page2 );
		$redbean->freeze( TRUE );
		$page = $redbean->dispense( "page" );
		$page->sections = 10;
		$page->name = "half a page";
		try {
			$id = $redbean->store( $page );
			fail();
		} catch ( SQL $e ) {
			pass();
		}
		$post = $redbean->dispense( "post" );
		$post->title = "existing table";
		try {
			$id = $redbean->store( $post );
			pass();
		} catch ( SQL $e ) {
			fail();
		}
		asrt( in_array( "name", array_keys( $writer->getColumns( "page" ) ) ), TRUE );
		asrt( in_array( "sections", array_keys( $writer->getColumns( "page" ) ) ), FALSE );
		$newtype = $redbean->dispense( "newtype" );
		$newtype->property = 1;
		try {
			$id = $redbean->store( $newtype );
			fail();
		} catch ( SQL $e ) {
			pass();
		}
		$logger = R::debug( TRUE, 1 );
		// Now log and make sure no 'describe SQL' happens
		$page = $redbean->dispense( "page" );
		$page->name = "just another page that has been frozen...";
		$id = $redbean->store( $page );
		$page = $redbean->load( "page", $id );
		$page->name = "just a frozen page...";
		$redbean->store( $page );
		$page2 = $redbean->dispense( "page" );
		$page2->name = "an associated frozen page";
		$a->associate( $page, $page2 );
		$a->related( $page, "page" );
		$a->unassociate( $page, $page2 );
		$a->clearRelations( $page, "page" );
		$items = $redbean->find( "page", array(), array( "1" ) );
		$redbean->trash( $page );
		$redbean->freeze( FALSE );
		asrt( count( $logger->grep( "SELECT" ) ) > 0, TRUE );
		asrt( count( $logger->grep( "describe" ) ) < 1, TRUE );
		asrt( is_array( $logger->getLogs() ), TRUE );
		R::debug( FALSE );
	}
}
