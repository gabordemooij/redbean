<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;

/**
 * PullRequest530
 *
 * Tests whether this specific issue on github has been resolved.
 * Pull Request #530 - OODBBean __set() checks if $property is a field link 
 *
 * @file    RedUNIT/Base/PullRequest530.php
 * @desc    Pull Request #530 - OODBBean __set() checks if $property is a field link
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
 
class PullRequest530 extends Base
{
	/**
	 * testPullRequest530
	 * 
	 * Test to check if OODBBean correctly stores a bean if a field link is set directly.
	 * (We have to unset the linked bean (if loaded), so that the Repository::processEmbeddedBean
	 * function call does not update the field link property and overwrites the change
	 * in the following statement: <code>if ($bean->$linkField != $id) $bean->$linkField = $id;</code>
	 *
	 * @return void
	 */
	public function testPullRequest530()
	{
		testpack( 'Testing Pull Request #530 - OODBBean __set() checks if $property is a field link' );
		R::freeze( FALSE );
		$linkedObjects = R::dispense('linked', 2);
		R::storeAll($linkedObjects);
		$tester = R::dispense('parent');
		$tester->linked = $linkedObjects[0];
		R::store($tester);
		$tester = R::findOne('parent');
		asrt($tester->linked->id, $linkedObjects[0]->id);
		$tester->linked_id = $linkedObjects[1]->id;
		R::store($tester);
		asrt($tester->linked->id, $linkedObjects[1]->id);
	}
}
