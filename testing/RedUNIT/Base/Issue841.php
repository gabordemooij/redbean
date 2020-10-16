<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;

/**
 * Issue 841
 * 
 * After bindFunc() Shared List cannot remove items.
 *
 * @file    RedUNIT/Base/Issue841.php
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Issue841 extends Base
{

	/**
	 * After bindFunc() Shared List cannot remove items.
	 *
	 * @return void
	 */
	public function testIssue841()
	{
		R::nuke();
		R::bindFunc( 'read', 'record.point', 'abs' );
		R::bindFunc( 'write', 'record.point', 'abs' );
		for($i = 0;$i < 3;$i++){
			$tag = R::dispense('tag');
			$tag->name = 'TAG_'.$i;
			R::store($tag);
		}
		$record = R::dispense('record');
		$record->point = rand(-100,-1);
		$record->sharedTagList[] = R::load('tag',2);
		R::store($record);
		asrt(count($record->sharedTagList),1);
		$record = R::load('record',1);
		$record->sharedTagList = array();
		R::store($record);
		$record = R::load('record',1);
		asrt(count($record->sharedTagList),0);
		R::bindFunc( 'read', 'record.point', null );
		R::bindFunc( 'write', 'record.point', null );
	}
}
