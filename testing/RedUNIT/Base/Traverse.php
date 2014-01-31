<?php 
namespace RedUNIT\Base;
use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;
use \RedBeanPHP\RedException as RedException;
use \RedBeanPHP\OODBBean as OODBBean;

/**
 * Traverse Test
 *
 * @file    RedUNIT/Base/Traverse.php
 * @desc    Tests traversal functionality
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Traverse extends Base
{

	/**
	 * Tests basic traversal.
	 * 
	 * @return void
	 */
	public function testBasicTraversal()
	{
		R::nuke();
		$pageA = R::dispense('page')->setAttr('title', 'a');
		$pageB = R::dispense('page')->setAttr('title', 'b');
		$pageC = R::dispense('page')->setAttr('title', 'c');
		$pageD = R::dispense('page')->setAttr('title', 'd');
		$pageE = R::dispense('page')->setAttr('title', 'e');
		$pageF = R::dispense('page')->setAttr('title', 'f');
		$pageG = R::dispense('page')->setAttr('title', 'g');
		$pageH = R::dispense('page')->setAttr('title', 'h');

		$pageA->ownPage = array($pageB, $pageC);
		$pageB->ownPage = array($pageD);
		$pageC->ownPage = array($pageE, $pageF);
		$pageD->ownPage = array($pageG);
		$pageF->ownPage = array($pageH);
		
		$pageA->sharedTagList = R::dispense( 'tag', 4 );

		R::store( $pageA );
		$pageA = $pageA->fresh();
		
		//also tests non-existant column handling by count().
		asrt( R::count( 'page', ' price = ? ', array( '5' ) ), 0);
		asrt( R::count( 'tag',  ' title = ? ', array( 'new' ) ), 0);
		
		$pageA->traverse('ownPageList', function( $bean ) {
			$bean->price = 5;
		});
		
		R::store($pageA);
		
		asrt( R::count( 'page', ' price = ? ', array( '5' ) ), 7);
		
		$pageA->traverse('sharedTagList', function( $bean ) {
			$bean->title = 'new';
		});
		
		R::store($pageA);
		
		asrt( R::count( 'tag',  ' title = ? ', array( 'new' ) ), 4);
		
	}
}