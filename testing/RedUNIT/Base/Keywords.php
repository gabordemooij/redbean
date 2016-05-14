<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;
use RedBeanPHP\OODBBean as OODBBean;

/**
 * Keywords
 *
 * Tests whether we can use keywords as bean types and
 * property names without running into security or stablity issues.
 * RedBeanPHP should properly escape all bean types and properties
 * so we may use whatever string we want.
 *
 * @file    RedUNIT/Base/Keywords.php
 * @desc    Tests for possible keyword clashes.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Keywords extends Base
{
	/**
	 * What drivers should be loaded for this test pack?
	 *
	 * CUBRID has inescapable keywords :/
	 *
	 * @return array
	 */
	public function getTargetDrivers()
	{
		return array( 'mysql', 'pgsql', 'sqlite' ); // CUBRID excluded for now.
	}

	/**
	 * Test if RedBeanPHP can properly handle keywords.
	 *
	 * @return void
	 */
	public function testKeywords()
	{
		$keywords = array(
			'anokeyword', 'znokeyword', 'group', 'drop',
			'inner', 'join', 'select', 'table',
			'int', 'cascade', 'float', 'call',
			'in', 'status', 'order', 'limit',
			'having', 'else', 'if', 'while',
			'distinct', 'like'
		);
		foreach ( $keywords as $k ) {
			R::nuke();
			$bean = R::dispense( $k );
			$bean->$k = $k;
			$id = R::store( $bean );
			$bean = R::load( $k, $id );
			$bean2 = R::dispense( 'other' );
			$bean2->name = $k;
			$bean->bean = $bean2;
			$bean->ownBean[]    = $bean2;
			$bean->sharedBean[] = $bean2;
			$id = R::store( $bean );
			R::trash( $bean );
			pass();
		}
	}
}
