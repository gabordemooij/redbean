<?php 

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;
use RedBeanPHP\AssociationManager as AssociationManager;
use RedBeanPHP\RedException\SQL as SQL; 

/**
 * RedUNIT_Base_Cross
 *
 * @file    RedUNIT/Base/Cross.php
 * @desc    Tests associations within the same table (i.e. page_page2 alike)
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */

class Cross extends Base
{
	/**
	 * Test self referential N-M relations (page_page).
	 * 
	 * @return void
	 */
	public function testSelfReferential()
	{
		$page = R::dispense('page')->setAttr( 'title', 'a' );
		$page->sharedPage[] = R::dispense( 'page' )->setAttr( 'title', 'b' );
		R::store( $page );
		$page = $page->fresh();
		$page = reset( $page->sharedPage );
		asrt( $page->title, 'b' );
		$tables = array_flip( R::inspect() );
		asrt( isset( $tables['page_page'] ), true );
		$columns = R::inspect( 'page_page' );
		asrt( isset( $columns['page2_id'] ), true );
	}
}
