<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;
use RedBeanPHP\OODBBean as OODBBean;

/**
 * Trash
 *
 * Test trashing of beans.
 *
 * @file    RedUNIT/Base/Trash.php
 * @desc    Tests R::trash()
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Trash extends Base
{
	/**
	 * Test whether R::trash/trashAll() returns the correct
	 * number of deleted beans.
	 */
	public function testTrash()
	{
		R::nuke();
		$id = R::store(R::dispense(array(
			'_type'=>'book',
			'pages'=>3
			)
		));
		
		asrt( R::count('book'), 1 );
		$n = R::trash(R::findOne('book'));
		asrt( $n, 1 );
		asrt( R::count('book'), 0 );
		
		list($books) = R::dispenseAll('book*10');
		R::storeAll( $books );
		asrt( R::count('book'), 10 );
		$n = R::trashAll( $books );
		asrt( R::count('book'), 0 );
	}
}
