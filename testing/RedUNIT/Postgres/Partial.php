<?php

namespace RedUNIT\Postgres;

use RedUNIT\Postgres as Postgres;
use RedBeanPHP\Facade as R;
use RedBeanPHP\RedException\SQL as SQLException;

/**
 * Partial Beans
 *
 * This class has been designed to test 'partial bean mode'.
 * In 'partial bean mode' only changed properties are being saved,
 * not entire beans. This can be useful when you have unsupported
 * column types in your table, this is what we test here. In this
 * example we have a table that contains a boolean column, this column
 * does not accept the value '' as FALSE as shown in the test, it will
 * trigger an Invalid Text Representation Exception. Thanks to 'partial beans'
 * we can work around this, by selectively updating the non-boolean properties
 * of the bean. If we choose to update the boolean property this is no longer
 * a problem because we set the value ourselves it will be compatible. However
 * automatically loaded and stored properties by RedBeanPHP are subject to
 * type inference, and the boolean FALSE value will become '', which is the
 * crux of the issue here.
 *
 * @file    RedUNIT/Postgres/Partial.php
 * @desc    Tests whether 'partial beans' can be used to support non-RB columns
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Partial extends Postgres
{
	/**
	 * Excerpt from issue #547:
	 * "When I load a bean (via $bean = R::findOne(...);), only change a few
	 * values and then call R::store($bean);, it can happen that I get an error like:
	 * Error in SQL query:
	 * SQLSTATE[22P02]: Invalid text representation:
	 * 7 ERROR: invalid input syntax for type boolean: ""
	 * This happens, when there is a boolean field set to false and
	 * I don't update that field. When R::store() is called,
	 * the value isn't translated to '0' but instead stays false. PostgreSQL doesn't like this.
	 * When setting a boolean value, it gets converted correctly,
	 * but any unchanged values stay of type boolean.
	 *
	 * @return void
	 */
	public function testIssue547BoolCol()
	{
		R::nuke();
		R::usePartialBeans( FALSE );
		$bean = R::dispense( 'bean' );
		$bean->property1 = 'value1';
		$id = R::store( $bean );
		R::freeze( TRUE );
		R::exec('ALTER TABLE bean ADD COLUMN "property2" BOOLEAN DEFAULT FALSE;');
		$bean = R::load( 'bean', $id );
		$bean->property1 = 'value1b';
		/* we cant save the bean, because there is an unsupported field type in the table */
		/* this was the bug... (or missing feature?) */
		try {
			R::store( $bean );
			fail();
		} catch( SQLException $e ) {
			asrt( strpos( $e->getMessage(), 'Invalid text representation' ) > 1, TRUE );
			asrt( $e->getSQLState(), '22P02' );
		}
		/* solved by adding feature partial beans, now only the changed properties get saved */
		R::usePartialBeans( TRUE );
		$id = R::store( $bean );
		/* no exception... */
		pass();
		/* also test behavior of boolean column in general */
		$bean = R::load( 'bean', $id );
		asrt( $bean->property1, 'value1b' );
		asrt( $bean->property2, FALSE );
		$bean->property2 = TRUE;
		$id = R::store( $bean );
		$bean = R::load( 'bean', $id );
		asrt( $bean->property1, 'value1b' );
		asrt( $bean->property2, TRUE );
		$bean->property2 = FALSE;
		$id = R::store( $bean );
		$bean = R::load( 'bean', $id );
		asrt( $bean->property1, 'value1b' );
		asrt( $bean->property2, FALSE );
		$bean->property2 = 't';
		$id = R::store( $bean );
		$bean = R::load( 'bean', $id );
		asrt( $bean->property1, 'value1b' );
		asrt( $bean->property2, TRUE );
		$bean->property2 = 'f';
		$id = R::store( $bean );
		$bean = R::load( 'bean', $id );
		asrt( $bean->property1, 'value1b' );
		asrt( $bean->property2, FALSE );
		/* but invalid text should not work */
		$bean->property2 = 'tx'; //instead of just 't'
		try {
			$id = R::store( $bean );
			fail();
		} catch( SQLException $e ) {
			asrt( strpos( $e->getMessage(), 'Invalid text representation' ) > 1, TRUE );
			asrt( $e->getSQLState(), '22P02' );
		}
		R::usePartialBeans( FALSE );
	}
}
