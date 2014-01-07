<?php 

use \RedBean\SimpleModel as SimpleModel; 
/**
 * RedUNIT_Base_Boxing
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
class RedUNIT_Base_Boxing extends RedUNIT_Base
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

		asrt( ( $box instanceof Model_Boxedbean ), TRUE );

		R::store( $box );
	}
}

/**
 * Test Model.
 */
class Model_Boxedbean extends SimpleModel
{
}
