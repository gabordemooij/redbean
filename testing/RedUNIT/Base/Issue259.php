<?php 

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;
use RedBeanPHP\SimpleModel as SimpleModel; 

/**
 * RedUNIT_Base_Issue259
 *
 * @file    RedUNIT/Base/Issue259.php
 * @desc    Issue #259 - Stash cache breaks model delegation in open().
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Issue259 extends Base
{
	/**
	 * Test to make sure stash cache works with recursively opening models
	 * with FUSE.
	 * 
	 * @return void
	 */
	public function testIssue259()
	{
		testpack( 'Testing Issue #259 - Stash Cache breaks model delegation in open().' );

		$mother = R::dispense( 'mother' );

		$mother->desc = 'I am mother';

		R::store( $mother );

		$child = R::dispense( 'child' );

		$child->mother = $mother;
		$child->desc   = 'I am child';

		$id = R::store( $child );

		R::findOne( 'child', ' id = ?', array( $id ) );

		R::find( 'child', ' id = ? ', array( $id ) );

		R::load( 'child', $id );
	}
}
/**
 * Mock Model.
 */
class Model_Mother extends SimpleModel
{
	public function open()
	{
		$bean = $this->bean;
		// $this & $bean are both referencing child incorrectly!
		asrt( $this->bean->desc, 'I am mother' );
	}
}
/**
 * Mock Model.
 */
class Model_Child extends SimpleModel
{
	public function open()
	{
		$this->bean->mother;
		asrt( $this->bean->desc, 'I am child' );
	}
}
