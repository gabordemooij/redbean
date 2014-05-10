<?php

namespace RedUNIT\Mysql;

use RedUNIT\Mysql as Mysql;
use RedBeanPHP\Facade as R;
use RedBeanPHP\AssociationManager as AssociationManager;

/**
 * Preexist
 *
 * @file    RedUNIT/Mysql/Preexist.php
 * @desc    Tests integration with pre-existing schemas.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Preexist extends Mysql
{
	/**
	 * Test integration with pre-existing schemas.
	 *
	 * @return void
	 */
	public function testPlaysNiceWithPreExitsingSchema()
	{
		$toolbox     = R::getToolBox();
		$adapter     = $toolbox->getDatabaseAdapter();
		$writer      = $toolbox->getWriter();
		$redbean     = $toolbox->getRedBean();
		$pdo         = $adapter->getDatabase();

		$a           = new AssociationManager( $toolbox );

		$page        = $redbean->dispense( "page" );

		$page->name  = "John's page";

		$idpage      = $redbean->store( $page );

		$page2       = $redbean->dispense( "page" );

		$page2->name = "John's second page";

		$idpage2     = $redbean->store( $page2 );

		$a->associate( $page, $page2 );

		$adapter->exec( "ALTER TABLE " . $writer->esc( 'page' ) . "
		CHANGE " . $writer->esc( 'name' ) . " " . $writer->esc( 'name' ) . "
		VARCHAR( 254 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL " );

		$page       = $redbean->dispense( "page" );

		$page->name = "Just Another Page In a Table";

		$cols       = $writer->getColumns( "page" );

		asrt( $cols["name"], "varchar(254)" );

		//$pdo->SethMode(1);

		$redbean->store( $page );

		pass(); // No crash?

		$cols = $writer->getColumns( "page" );

		asrt( $cols["name"], "varchar(254)" ); //must still be same
	}
}
