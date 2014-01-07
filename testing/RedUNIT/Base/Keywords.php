<?php 

use \ReadBean\OODBBean as OODBBean; 
/**
 * RedUNIT_Base_Keywords
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
class RedUNIT_Base_Keywords extends RedUNIT_Base
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
			'anokeyword', 'znokeyword', 'group', 'DROP',
			'inner', 'JOIN', 'select', 'table',
			'int', 'cascade', 'float', 'CALL',
			'in', 'status', 'order', 'limit',
			'having', 'else', 'if', 'while',
			'distinct', 'like'
		);

		R::setStrictTyping( FALSE );

		OODBBean::setFlagBeautifulColumnNames( FALSE );

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

		OODBBean::setFlagBeautifulColumnNames( TRUE );

		R::setStrictTyping( TRUE );
	}
}
