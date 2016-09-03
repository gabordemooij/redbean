<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;
use RedBeanPHP\SimpleModel as SimpleModel;

/**
 * Boxing
 *
 * Test boxing and unboxing of beans.
 *
 * @file    RedUNIT/Base/Boxing.php
 * @desc    Tests bean boxing and unboxing functionality.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Boxing extends Base
{

	/**
	 * Test boxing beans.
	 *
	 * @return void
	 */
	public function testBoxing()
	{
		R::nuke();
		$bean = R::dispense( 'boxedbean' )->box();
		R::trash( $bean );
		pass();
		$bean = R::dispense( 'boxedbean' );
		$bean->sharedBoxbean = R::dispense( 'boxedbean' )->box();
		R::store( $bean );
		pass();
		$bean = R::dispense( 'boxedbean' );
		$bean->ownBoxedbean = R::dispense( 'boxedbean' )->box();
		R::store( $bean );
		pass();
		$bean = R::dispense( 'boxedbean' );
		$bean->other = R::dispense( 'boxedbean' )->box();
		R::store( $bean );
		pass();
		$bean = R::dispense( 'boxedbean' );
		$bean->title = 'MyBean';
		$box = $bean->box();
		asrt( ( $box instanceof \Model_Boxedbean ), TRUE );
		R::store( $box );
	}

	/**
	 * Test fix for issue #512 - thanks for reporting Bernhard H.
	 * OODBBean::__toString() implementation only works with C_ERR_IGNORE
	 *
	 * @return void
	 */
	public function testToStringIssue512()
	{
		R::setErrorHandlingFUSE( \RedBeanPHP\OODBBean::C_ERR_FATAL );
		$boxedBean = R::dispense( 'boxedbean' );
		$str = (string) $boxedBean;
		asrt( $str, '{"id":0}' ); //no fatal error
		R::setErrorHandlingFUSE( FALSE );
	}
}
