<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;
use RedBeanPHP\RedException as RedException;
use RedBeanPHP\OODBBean as OODBBean;

/**
 * Typechecking
 *
 * Tests whether RedBeanPHP handles type casting correctly.
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
class Typechecking extends Base
{
	/**
	 * Test types.
	 * Test how RedBeanPHP OODB and OODBBean handle type and type casts.
	 *
	 * Rules:
	 *
	 * 1. before storing a bean all types are preserved except booleans (they are converted to STRINGS '0' or '1')
	 * 2. after store-reload all bean property values are STRINGS or NULL
	 *    (or ARRAYS but that's only from a user perspective because these are lazy loaded)
	 * 3. the ID returned by store() is an INTEGER (if possible; on 32 bit systems overflowing values will be cast to STRINGS!)
	 *
	 * After loading:
	 * ALL VALUES EXCEPT NULL -> STRING
	 * NULL -> NULL
	 *
	 * @note Why not simply return bean->id in store()? Because not every driver returns the same type:
	 * databases without insert_id support require a separate query or a suffix returning STRINGS, not INTEGERS.
	 *
	 * @note Why not preserve types? I.e. I store integer, why do I get back a string?
	 * Answer: types are handled different across database platforms, would cause overhead to inspect every value for type,
	 * also PHP is a dynamically typed language so types should not matter that much. Another reason: due to the nature
	 * of RB columns in the database might change (INT -> STRING) this would cause return types to change as well which would
	 * cause 'cascading errors', i.e. a column gets widened and suddenly your code would break.
	 *
	 * @note Unfortunately the 32/64-bit issue cannot be tested fully. Return-strategy store() is probably the safest
	 * solution.
	 *
	 * @return void
	 */
	public function testTypes()
	{
		testpack( 'Beans can only contain STRING and NULL after reload' );
		R::nuke();
		$bean = R::dispense( 'bean' );
		$bean->number   = 123;
		$bean->float    = 12.3;
		$bean->bool     = false;
		$bean->bool2    = true;
		$bean->text     = 'abc';
		$bean->null     = null;
		$bean->datetime = new\DateTime( 'NOW', new\DateTimeZone( 'Europe/Amsterdam' ) );
		$id = R::store( $bean );
		asrt( is_int( $id ), TRUE );
		asrt( is_float( $bean->float ), TRUE );
		asrt( is_integer( $bean->number ), TRUE );
		asrt( is_string( $bean->bool ), TRUE );
		asrt( is_string( $bean->bool2 ), TRUE );
		asrt( is_string( $bean->datetime ), TRUE );
		asrt( is_string( $bean->text ), TRUE );
		asrt( is_null( $bean->null ), TRUE );
		$bean = R::load('bean', $id );
		asrt( is_string( $bean->id ), TRUE );
		asrt( is_string( $bean->float ), TRUE );
		asrt( is_string( $bean->number ), TRUE );
		asrt( is_string( $bean->bool ), TRUE );
		asrt( is_string( $bean->bool2 ), TRUE );
		asrt( is_string( $bean->datetime ), TRUE );
		asrt( is_string( $bean->text ), TRUE );
		asrt( is_null( $bean->null ), TRUE );
		asrt( $bean->bool, '0' );
		asrt( $bean->bool2, '1' );
	}

	/**
	 * Test bean type checking.
	 *
	 * @return void
	 */
	public function testBeanTypeChecking()
	{
		$redbean = R::getRedBean();
		$bean    = $redbean->dispense( "page" );
		// Set some illegal values in the bean; this should trigger Security exceptions.
		// Arrays are not allowed.
		$bean->name = array( "1" );
		try {
			$redbean->store( $bean );
			fail();
		} catch ( RedException $e ) {
			pass();
		}
		try {
			$redbean->check( $bean );
			fail();
		} catch ( RedException $e ) {
			pass();
		}
		$bean->name = new OODBBean;
		try {
			$redbean->check( $bean );
			fail();
		} catch ( RedException $e ) {
			pass();
		}
		// Property names should be alphanumeric
		$prop        = ".";
		$bean->$prop = 1;
		try {
			$redbean->store( $bean );
			fail();
		} catch ( RedException $e ) {
			pass();
		}
		try {
			$redbean->check( $bean );
			fail();
		} catch ( RedException $e ) {
			pass();
		}
		// Really...
		$prop        = "-";
		$bean->$prop = 1;
		try {
			$redbean->store( $bean );
			fail();
		} catch ( RedException $e ) {
			pass();
		}
		try {
			$redbean->check( $bean );
			fail();
		} catch ( RedException $e ) {
			pass();
		}
	}
}
