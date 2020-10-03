<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;

/**
 * Chill
 *
 * Tests 'chill' mode. In this mode some bean types are frozen,
 * their schemas cannot be modified while others are fluid and
 * can still be adjusted.
 *
 * @file    RedUNIT/Base/Chill.php
 * @desc    Tests chill list functionality, i.e. freezing a subset of all types.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Chill extends Base
{
	/**
	 * Test Chill mode.
	 *
	 * @return void
	 */
	public function testChill()
	{
		$bean = R::dispense( 'bean' );
		$bean->col1 = '1';
		$bean->col2 = '2';
		R::store( $bean );
		asrt( count( R::getWriter()->getColumns( 'bean' ) ), 3 );
		$bean->col3 = '3';
		R::store( $bean );
		asrt( count( R::getWriter()->getColumns( 'bean' ) ), 4 );
		R::freeze( array( 'umbrella' ) );
		$bean->col4 = '4';
		R::store( $bean );
		asrt( count( R::getWriter()->getColumns( 'bean' ) ), 5 );
		R::freeze( array( 'bean' ) );
		$bean->col5 = '5';
		try {
			R::store( $bean );
			fail();
		} catch (\Exception $e ) {
			pass();
		}
		asrt( count( R::getWriter()->getColumns( 'bean' ) ), 5 );
		R::freeze( array() );
		$bean->col5 = '5';
		R::store( $bean );
		asrt( count( R::getWriter()->getColumns( 'bean' ) ), 6 );
	}

	/**
	 * Test whether we cannot add unique constraints on chilled tables,
	 * otherwise you cannot avoid this from happening when adding beans to the
	 * shared list :) -- this is almost a theoretical issue however we want it
	 * to work according to specifications!
	 *
	 * @return void
	 */
	public function testDontAddUniqueConstraintForChilledBeanTypes()
	{
		R::nuke();
		$person = R::dispense( 'person' );
		$role = R::dispense( 'role' );
		$person->sharedRole[] = $role;
		R::store( $person );
		$person->sharedRole[] = R::dispense( 'role' );
		R::store( $person );
		$bean = R::getRedBean()->dispense('person_role');
		$bean->personId = $person->id;
		$bean->roleId = $role->id;
		try {
			R::store( $bean );
			fail();
		} catch(\Exception $e) {
			pass();
		}
		asrt(R::count('person_role'), 2);
		R::nuke();
		$link = R::getRedBean()->dispense('person_role');
		$person = R::dispense( 'person' );
		$role = R::dispense( 'role' );
		$link->person = $person;
		$link->role = $role;
		R::store( $link );
		R::freeze(array('person_role'));
		$person->sharedRole[] = R::dispense( 'role' );
		R::store( $person );
		$bean = R::getRedBean()->dispense('person_role');
		$bean->personId = $person->id;
		$bean->roleId = $role->id;
		try {
			R::store( $bean );
			pass();
		} catch(\Exception $e) {
			fail();
		}
		asrt(R::count('person_role'), 3);
		R::freeze( array() ); //set freeze to FALSE and clear CHILL LIST!
	}

	/**
	 * Test whether we can set and reset the chill list and check the contents
	 * of the chill list.
	 *
	 * @return void
	 */
	public function testChillTest()
	{
		R::freeze( array( 'beer' ) );
		$oodb = R::getRedBean();
		asrt( $oodb->isChilled( 'beer' ), TRUE );
		asrt( $oodb->isChilled( 'wine' ), FALSE );
		R::freeze( FALSE );
		$oodb = R::getRedBean();
		asrt( $oodb->isChilled( 'beer' ), TRUE );
		asrt( $oodb->isChilled( 'wine' ), FALSE );
		R::freeze( TRUE );
		$oodb = R::getRedBean();
		asrt( $oodb->isChilled( 'beer' ), TRUE );
		asrt( $oodb->isChilled( 'wine' ), FALSE );
		R::freeze( array() );
		$oodb = R::getRedBean();
		asrt( $oodb->isChilled( 'beer' ), FALSE );
		asrt( $oodb->isChilled( 'wine' ), FALSE );
	}
}
