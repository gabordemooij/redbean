<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;
use RedBeanPHP\RedException as RedException; 
use RedBeanPHP\QueryWriter\AQueryWriter as AQueryWriter;

/**
 * 3 way tables
 *
 * @file    RedUNIT/Base/Threeway.php
 * @desc    Various tests for 3-way tables or X-way tables.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Threeway extends Base
{
	/**
	 * Test whether we can use threeway tables without being
	 * bothered by unique constraints.
	 * 
	 * @return void
	 */
	public function testUniqueConstraintOnThreeways()
	{
		AQueryWriter::clearRenames();
		
		R::nuke();
		
		$person = R::dispense( 'person' );
		$role = R::dispense( 'role' );
		$person->sharedRole[] = $role;
		R::store( $person );
		
		$person->link( 'person_role', array(
			 'unit' => R::dispense('unit')
		))->role = $role;
		
		//Can we add a duplicate role now? - No because we started with a simple N-M table
		//and unique constraint has been applied accordingly, manually change database.
		asrt( R::count( 'person_role' ), 1 );
		
		R::nuke();
		
		$person = R::dispense( 'person' );
		$role = R::dispense( 'role' );
		$person->via('participant')->sharedRole[] = $role;
		R::store( $person );
		
		$person->link( 'participant', array(
			 'unit' => R::dispense('unit')
		))->role = $role;
		
		//Can we add a duplicate role now? - No because we started with a simple N-M table
		//and unique constraint has been applied accordingly, manually change database.
		asrt( R::count( 'participant' ), 1 );
		
		R::nuke();
		
		$participant = R::dispense( 'participant' );
		$person = R::dispense( 'person' );
		$role = R::dispense( 'role' );
		$unit = R::dispense( 'unit' );
		$participant->person = $person;
		$participant->role = $role;
		$participant->unit = $unit;
		R::store( $participant );
		
		$person->link( 'participant', array(
			 'unit' => R::dispense('unit')
		))->role = $role;
		
		R::store( $person );
		
		//Can we add a duplicate role now?
		asrt( R::count( 'participant' ), 2 );
		AQueryWriter::clearRenames();
	}
	
	/**
	 * Test whether a duplicate bean in the list isnt saved.
	 * This was an issue with Postgres while testing the threeway tables.
	 * Postgres returned the ID as a string while other drivers returned
	 * a numeric value causing different outcome in array_diff when
	 * calculating the shared additions.
	 * 
	 * @return void
	 */
	public function testIssueWithDriverReturnID()
	{
		AQueryWriter::clearRenames();
		R::nuke();
		$book = R::dispense( 'book' );
		$page = R::dispense( 'page' );
		$book->sharedPageList[] = $page;
		R::store( $book );
		asrt( R::count( 'page' ), 1 );
		$book = $book->fresh();
		$book->sharedPageList[] = $page;
		R::store( $book );
		
		//don't save the duplicate bean!
		asrt( R::count( 'page' ), 1 );
		
		$book = $book->fresh();
		$page->item = 2; //even if we change a property ?
		$book->sharedPageList[] = $page;
		R::store( $book );
		
		foreach( $book->sharedPageList as $listItem) {
			asrt( is_string( $listItem->id ), TRUE );
		}
		
		//same test but for own-list
		R::nuke();
		$book = R::dispense( 'book' );
		$page = R::dispense( 'page' );
		$book->ownPageList[] = $page;
		R::store( $book );
		asrt( R::count( 'page' ), 1 );
		$book = $book->fresh();
		$book->ownPageList[] = $page;
		R::store( $book );
		//don't save the duplicate bean!
		asrt( R::count( 'page' ), 1 );
		
		$book = $book->fresh();
		$book->ownPageList[] = $page;
		$page->item = 3;
		R::store( $book );
		//don't save the duplicate bean!
		asrt( R::count( 'page' ), 1 );
		
		foreach( $book->ownPageList as $listItem) {
			asrt( is_string( $listItem->id ), TRUE );
		}
		AQueryWriter::clearRenames();
	}
}