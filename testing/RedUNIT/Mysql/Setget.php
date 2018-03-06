<?php

namespace RedUNIT\Mysql;

use RedUNIT\Mysql as Mysql;
use RedBeanPHP\Facade as R;

/**
 * Setget
 *
 * This class has been designed to test set/get operations
 * for a specific Query Writer / Adapter. Since RedBeanPHP
 * creates columns based on values it's essential that you
 * get back the 'same' value as you put in - or - if that's
 * not the case, that there are at least very clear rules
 * about what to expect. Examples of possible issues tested in
 * this class include:
 *
 * - Test whether booleans are returned correctly (they will become integers)
 * - Test whether large numbers are preserved
 * - Test whether floating point numbers are preserved
 * - Test whether date/time values are preserved
 * and so on...
 *
 * @file    RedUNIT/Mysql/Setget.php
 * @desc    Tests whether values are stored correctly.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Setget extends Mysql
{
	/**
	 * Test whether we can store DateTime objects and get them back
	 * as 'date-time' strings representing the same date and time.
	 *
	 * @return void
	 */
	public function testDateObject()
	{
		$dt = new \DateTime();
		$dt->setTimeZone( new \DateTimeZone( 'Europe/Amsterdam' ) );
		$dt->setDate( 1981, 5, 1 );
		$dt->setTime( 3, 13, 13 );
		asrt( setget( $dt ), '1981-05-01 03:13:13' );
		$bean = R::dispense( 'bean' );
		$bean->dt = $dt;
	}

	/**
	 * Tests R::getInsertID convenience method.
	 *
	 * @return void
	 */
	public function testGetInsertID()
	{
		R::nuke();
		$id = R::store( R::dispense( 'book' ) );
		$id2 = R::getInsertID();
		asrt( $id, $id2 );
	}

	/**
	 * Test numbers.
	 *
	 * @return void
	 */
	public function testNumbers()
	{
		asrt( setget( "-1" ), "-1" );
		asrt( setget( -1 ), "-1" );

		asrt( setget( "-0.25" ), "-0.25" );
		asrt( setget( -0.25 ), "-0.25" );

		asrt( setget( "1.0" ), "1" );
		asrt( setget( 1.0 ), "1" );

		asrt( setget( "3.20" ), "3.20" );
		asrt( setget( "13.20" ), "13.20" );
		asrt( setget( "134.20" ), "134.20" );
		asrt( setget( 3.21 ), '3.21' );

		asrt( setget( "0.12345678" ), "0.12345678" );
		asrt( setget( 0.12345678 ), "0.12345678" );

		asrt( setget( "-0.12345678" ), "-0.12345678" );
		asrt( setget( -0.12345678 ), "-0.12345678" );

		asrt( setget( "2147483647" ), "2147483647" );
		asrt( setget( 2147483647 ), "2147483647" );

		asrt( setget( -2147483647 ), "-2147483647" );
		asrt( setget( "-2147483647" ), "-2147483647" );

		asrt( setget( -4294967295 ), "-4294967295" );
		asrt( setget( "-4294967295" ), "-4294967295" );

		asrt( setget( 4294967295 ), "4294967295" );
		asrt( setget( "4294967295" ), "4294967295" );

		asrt( setget( "2147483648" ), "2147483648" );
		asrt( setget( "-2147483648" ), "-2147483648" );

		asrt( setget( "199936710040730" ), "199936710040730" );
		asrt( setget( "-199936710040730" ), "-199936710040730" );

		//Architecture dependent... only test this if you are sure what arch
		//asrt(setget("2147483647123456"),"2.14748364712346e+15");
		//asrt(setget(2147483647123456),"2.14748364712e+15");
	}

	/**
	 * Test dates.
	 *
	 * @return void
	 */
	public function testDates()
	{
		asrt( setget( "2010-10-11" ), "2010-10-11" );
		asrt( setget( "2010-10-11 12:10" ), "2010-10-11 12:10" );
		asrt( setget( "2010-10-11 12:10:11" ), "2010-10-11 12:10:11" );
		asrt( setget( "x2010-10-11 12:10:11" ), "x2010-10-11 12:10:11" );
	}

	/**
	 * Test strings.
	 *
	 * @return void
	 */
	public function testStrings()
	{
		asrt( setget( "a" ), "a" );
		asrt( setget( "." ), "." );
		asrt( setget( "\"" ), "\"" );
		asrt( setget( "just some text" ), "just some text" );
	}

	/**
	 * Test booleans.
	 *
	 * @return void
	 */
	public function testBool()
	{
		asrt( setget( TRUE ), "1" );
		asrt( setget( FALSE ), "0" );

		asrt( setget( "TRUE" ), "TRUE" );
		asrt( setget( "FALSE" ), "FALSE" );
	}

	/**
	 * Test NULL.
	 *
	 * @return void
	 */
	public function testNull()
	{
		asrt( setget( "NULL" ), "NULL" );
		asrt( setget( "NULL" ), "NULL" );

		asrt( setget( "0123" ), "0123" );
		asrt( setget( "0000123" ), "0000123" );

		asrt( setget( NULL ), NULL );

		asrt( ( setget( 0 ) == 0 ), TRUE );
		asrt( ( setget( 1 ) == 1 ), TRUE );

		asrt( ( setget( TRUE ) == TRUE ), TRUE );
		asrt( ( setget( FALSE ) == FALSE ), TRUE );
	}
}
