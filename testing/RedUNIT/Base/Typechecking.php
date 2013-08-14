<?php
/**
 * RedUNIT_Base_Typechecking
 *
 * @file    RedUNIT/Base/Typechecking.php
 * @desc    Tests basic bean validation rules; invalid bean handling.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Base_Typechecking extends RedUNIT_Base
{
	
	/**
	 * Test bean type checking.
	 * 
	 * @return void
	 */
	public function testBeanTypeChecking()
	{
		$redbean = R::$redbean;

		$bean    = $redbean->dispense( "page" );

		// Set some illegal values in the bean; this should trigger Security exceptions.
		// Arrays are not allowed.
		$bean->name = array( "1" );

		try {
			$redbean->store( $bean );

			fail();
		} catch ( RedBean_Exception_Security $e ) {
			pass();
		}

		try {
			$redbean->check( $bean );

			fail();
		} catch ( RedBean_Exception_Security $e ) {
			pass();
		}

		$bean->name = new RedBean_OODBBean;

		try {
			$redbean->check( $bean );

			fail();
		} catch ( RedBean_Exception_Security $e ) {
			pass();
		}

		// Property names should be alphanumeric
		$prop        = ".";

		$bean->$prop = 1;

		try {
			$redbean->store( $bean );

			fail();
		} catch ( RedBean_Exception_Security $e ) {
			pass();
		}

		try {
			$redbean->check( $bean );

			fail();
		} catch ( RedBean_Exception_Security $e ) {
			pass();
		}

		// Really...
		$prop        = "-";

		$bean->$prop = 1;

		try {
			$redbean->store( $bean );

			fail();
		} catch ( RedBean_Exception_Security $e ) {
			pass();
		}

		try {
			$redbean->check( $bean );

			fail();
		} catch ( RedBean_Exception_Security $e ) {
			pass();
		}
	}
}
