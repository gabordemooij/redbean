<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;
use RedBeanPHP\OODBBean as OODBBean;

/**
 * Copy
 *
 * Tests whether we can make a copy or a deep copy of a bean
 * and whether recursion is handled well. Also tests
 * versioning: copying can be used to implement a versioning feature,
 * some test cases will reflect this particular use case.
 *
 * @file    RedUNIT/Base/Copy.php
 * @desc    Tests whether we can make a deep copy of a bean.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Copy extends Base
{
	/**
	 * Test whether recursion happens
	 */
	public function testCopyRecursion()
	{
		$document = R::dispense( 'document' );
		$id = R::store( $document );
		$document->ownDocument[] = $document;
		R::store( $document );
		$duplicate = R::dup( $document );
		pass(); //if RB cant handle this is will crash (nesting level error from PHP).
		$id2 = R::store( $duplicate );
		$duplicate = R::load( 'document', $id );
		asrt( (int) $document->document_id, $id );
		asrt( (int) $duplicate->document_id, $id2 );
		// Export variant
		$duplicate = R::exportAll( $document );
		asrt( (int) $duplicate[0]['document_id'], $id );
	}

	/**
	 * Test real world scenario: Versioning
	 */
	public function testVersioning()
	{
		$document = R::dispense( 'document' );
		$page = R::dispense( 'page' );
		$document->title = 'test';
		$page->content = 'lorem ipsum';
		$user = R::dispense( 'user' );
		$user->name = 'Leo';
		$document->sharedUser[] = $user;
		$document->ownPage[]    = $page;
		$document->starship_id = 3;
		$document->planet      = R::dispense( 'planet' );
		R::store( $document );
		$duplicate = R::dup( $document );
		R::store( $duplicate );
		$duplicate = R::dup( $document );
		R::store( $duplicate );
		asrt( R::count( 'planet' ), 1 );
		asrt( R::count( 'user' ), 1 );
		asrt( R::count( 'document' ), 3 );
		asrt( R::count( 'page' ), 3 );
		asrt( R::count( 'spaceship' ), 0 );
	}

	/**
	 * Same as above but now with intermediate save, counts must be same
	 */
	public function testVersioningIntermediateSaves()
	{
		$document = R::dispense( 'document' );
		$page = R::dispense( 'page' );
		$document->title = 'test';
		$page->content = 'lorem ipsum';
		$user = R::dispense( 'user' );
		$user->name = 'Leo';
		$document->sharedUser[] = $user;
		$document->ownPage[]    = $page;
		$document->starship_id = 3;
		$document->planet = R::dispense( 'planet' );
		R::store( $document );
		$duplicate = R::dup( $document );
		R::store( $document );
		R::store( $duplicate );
		R::store( $document );
		$duplicate = R::dup( $document );
		R::store( $document );
		R::store( $duplicate );
		asrt( R::count( 'planet' ), 1 );
		asrt( R::count( 'user' ), 1 );
		asrt( R::count( 'document' ), 3 );
		asrt( R::count( 'page' ), 3 );
		asrt( R::count( 'spaceship' ), 0 );
		// same, but now with intermediate save, counts must be same
		R::freeze( TRUE );
		$document = R::dispense( 'document' );
		$page     = R::dispense( 'page' );
		$document->title = 'test';
		$page->content = 'lorem ipsum';
		$user = R::dispense( 'user' );
		$user->name = 'Leo';
		$document->sharedUser[] = $user;
		$document->ownPage[]    = $page;
		$document->starship_id = 3;
		$document->planet      = R::dispense( 'planet' );
		R::store( $document );
		$duplicate = R::dup( $document );
		R::store( $document );
		R::store( $duplicate );
		R::store( $document );
		$duplicate = R::dup( $document );
		R::store( $document );
		R::store( $duplicate );
		asrt( R::count( 'planet' ), 2 );
		asrt( R::count( 'user' ), 2 );
		asrt( R::count( 'document' ), 6 );
		asrt( R::count( 'page' ), 6 );
		try { asrt( R::count( 'spaceship' ), 0 ); }catch(\Exception $e){pass();}
		R::freeze( FALSE );
	}

	/**
	 * Test Recursion
	 */
	public function testRecursion()
	{
		list( $d1, $d2 ) = R::dispense( 'document', 2 );
		$page = R::dispense( 'page' );
		list( $p1, $p2 ) = R::dispense( 'paragraph', 2 );
		list( $e1, $e2 ) = R::dispense( 'excerpt', 2 );
		$id2 = R::store( $d2 );
		$p1->name = 'a';
		$p2->name = 'b';
		$page->title = 'my page';
		$page->ownParagraph = array( $p1, $p2 );
		$p1->ownExcerpt[]  = $e1;
		$p2->ownExcerpt[]  = $e2;
		$e1->ownDocument[] = $d2;
		$e2->ownDocument[] = $d1;
		$d1->ownPage[]     = $page;
		$id1 = R::store( $d1 );
		$d1 = R::load( 'document', $id1 );
		$d = R::dup( $d1 );
		$ids = array();
		asrt( ( $d instanceof OODBBean ), TRUE );
		asrt( count( $d->ownPage ), 1 );
		foreach ( end( $d->ownPage )->ownParagraph as $p ) {
			foreach ( $p->ownExcerpt as $e ) {
				$ids[] = end( $e->ownDocument )->id;
			}
		}
		sort( $ids );
		asrt( (int) $ids[0], 0 );
		asrt( (int) $ids[1], $id1 );
		R::store( $d );
		pass();
		$phillies = R::dispense( 'diner' );
		list( $lonelyman, $man, $woman ) = R::dispense( 'guest', 3 );
		$attendant = R::dispense( 'employee' );
		$lonelyman->name = 'Bennie Moten';
		$man->name       = 'Daddy Stovepipe';
		$woman->name     = 'Mississippi Sarah';
		$attendant->name = 'Gus Cannon';
		$phillies->sharedGuest = array( $lonelyman, $man, $woman );
		$phillies->ownEmployee[] = $attendant;
		$props = R::dispense( 'prop', 2 );
		$props[0]->kind = 'cigarette';
		$props[1]->kind = 'coffee';
		$thought = R::dispense( 'thought' );
		$thought->content = 'Blues';
		$thought2 = R::dispense( 'thought' );
		$thought2->content = 'Jazz';
		$woman->ownProp[]  = $props[0];
		$man->sharedProp[] = $props[1];
		$attendant->ownThought = array( $thought, $thought2 );
		R::store( $phillies );
		$diner  = R::findOne( 'diner' );
		$diner2 = R::dup( $diner );
		$id2    = R::store( $diner2 );
		$diner2 = R::load( 'diner', $id2 );
		asrt( count( $diner->ownEmployee ), 1 );
		asrt( count( $diner2->ownEmployee ), 1 );
		asrt( count( $diner->sharedGuest ), 3 );
		asrt( count( $diner2->sharedGuest ), 3 );
		$employee = reset( $diner->ownEmployee );
		asrt( count( $employee->ownThought ), 2 );
		$employee = reset( $diner2->ownEmployee );
		asrt( count( $employee->ownThought ), 2 );
		// Can we change something in the duplicate without changing the original?
		$employee->name = 'Marvin';
		$thought = R::dispense( 'thought' );
		$thought->content = 'depression';
		$employee->ownThought[] = $thought;
		array_pop( $diner2->sharedGuest );
		$guest       = reset( $diner2->sharedGuest );
		$guest->name = 'Arthur Dent';
		$id2         = R::store( $diner2 );
		$diner2      = R::load( 'diner', $id2 );
		asrt( count( $diner->ownEmployee ), 1 );
		asrt( count( $diner2->ownEmployee ), 1 );
		asrt( count( $diner->sharedGuest ), 3 );
		asrt( count( $diner2->sharedGuest ), 2 );
		$employeeOld = reset( $diner->ownEmployee );
		asrt( count( $employeeOld->ownThought ), 2 );
		$employee = reset( $diner2->ownEmployee );
		asrt( count( $employee->ownThought ), 3 );
		asrt( $employee->name, 'Marvin' );
		asrt( $employeeOld->name, 'Gus Cannon' );
		// However the shared beans must not be copied
		asrt( R::count( 'guest' ), 3 );
		asrt( R::count( 'guest_prop' ), 1 );
		$arthur = R::findOne( 'guest', ' ' . R::getWriter()->esc( 'name' ) . ' = ? ', array( 'Arthur Dent' ) );
		asrt( $arthur->name, 'Arthur Dent' );
	}
}
