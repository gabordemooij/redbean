<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;
use RedBeanPHP\RedException as RedException;
use RedBeanPHP\OODBBean as OODBBean;

/**
 * Aliasing
 *
 * Tests aliasing functionality, i.e. fetching beans as,
 * inferring correct type and retrieving lists as alias.
 *
 * @file    RedUNIT/Base/Aliasing.php
 * @desc    Tests for nested beans with aliases, i.e. teacher alias for person etc.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Aliasing extends Base
{
	/**
	 * Test for aliasing issue for LTS version.
	 *
	 * @return void
	 */
	public function testIssueAliasingForLTSVersion() {
		$person = R::dispense('person');
		$pro = R::dispense('project');
		$c = R::dispense('course');
		$person->name = 'x';
		$person->alias('teacher')->ownProject[] = $pro;
		$person->alias('student')->ownCourse[] = $c;
		R::store($person);
		asrt($c->fresh()->fetchAs('person')->student->name, 'x');
		asrt($pro->fresh()->fetchAs('person')->teacher->name, 'x');
		$person = $person->fresh();
		$person->alias('teacher')->ownProject = array();
		$person->alias('student')->ownCourse = array();
		R::store($person);
		asrt($c->fresh()->fetchAs('person')->student, NULL);
		asrt($pro->fresh()->fetchAs('person')->teacher, NULL);
	}

	/**
	 * Describing how clearing state of bean works.
	 * Every method returning somthing (except getID)
	 * clears prefix-method-state (anything set by withCond,with,alias,fetchAs).
	 *
	 * @return void
	 */
	public function clearStateAdditionalTests()
	{
		list( $project1, $project2 ) = R::dispense( 'project', 2 );
		list( $irene, $ilse ) = R::dispense('person', 2);
		$project1->developer = $ilse;
		$project1->designer  = $irene;
		$ilse->name  = 'Ilse';
		$irene->name = 'Irene';
		$project2->developer = $ilse;
		R::storeAll( array( $project1, $project2 ) );
		$ilse = R::load( 'person', $ilse->id );
		asrt( count( $ilse->alias( 'developer' )->ownProject ), 2);
		//cached - same list
		asrt( count( $ilse->ownProject ), 2);
		asrt( count( $ilse->alias( 'designer' )->ownProject ), 0);
		//cached - same list
		asrt( count( $ilse->ownProject ), 0);
		//now test state
		asrt( count( $ilse->setAttr( 'a', 'b' )->alias( 'developer' )->ownProject ), 2);
		//now test state
		$ilse = $ilse->fresh();
		//attr clears state...
		asrt( count( $ilse->alias( 'developer' )->setAttr( 'a', 'b' )->ownProject ), 0);
		//but getID() does not!
		$ilse = $ilse->fresh();
		$ilse->alias('developer');
		$ilse->getID();
		asrt( count( $ilse->ownProject ), 2 );
	}

	/**
	 * Can switch fetchAs().
	 * Also checks shadow by storing.
	 *
	 * @return void
	 */
	public function canSwitchParentBean()
	{
		list( $project1, $project2 ) = R::dispense( 'project', 2 );
		list( $irene, $ilse ) = R::dispense('person', 2);
		$project1->developer = $ilse;
		$project1->designer  = $irene;
		$ilse->name  = 'Ilse';
		$irene->name = 'Irene';
		$project2->developer = $ilse;
		R::storeAll( array( $project1, $project2 ) );
		$project1 = R::load( 'project', $project1->id );
		asrt( $project1->fetchAs('person')->developer->name, 'Ilse' );
		asrt( $project1->fetchAs('person')->designer->name,  'Irene' );
		R::store( $project1 );
		$project1 = R::load( 'project', $project1->id );
		asrt( $project1->fetchAs('person')->designer->name,  'Irene' );
		asrt( $project1->fetchAs('person')->developer->name, 'Ilse' );
		R::store( $project1 );
		asrt( $project1->fetchAs('person')->developer->name, 'Ilse' );
		asrt( $project1->fetchAs('person')->designer->name,  'Irene' );
		asrt( $project1->fetchAs('person')->developer->name, 'Ilse' );
	}

	/**
	 * Switching aliases (->alias) should not change other list during
	 * storage.
	 *
	 * @return void
	 */
	public function testShadow()
	{
		list( $project1, $project2 ) = R::dispense( 'project', 2 );
		list( $irene, $ilse ) = R::dispense('person', 2);
		$project1->developer = $ilse;
		$project1->designer  = $irene;
		$project2->developer = $ilse;
		R::storeAll( array( $project1, $project2 ) );
		$ilse  = R::load( 'person', $ilse->id );
		$irene = R::load( 'person', $irene->id );
		asrt( count( $ilse->alias('developer')->ownProject ), 2 );
		asrt( count( $ilse->alias('designer')->ownProject ), 0 );
		R::store( $ilse );
		$ilse  = R::load( 'person', $ilse->id );
		$irene = R::load( 'person', $irene->id );
		asrt( count( $ilse->alias('designer')->ownProject ), 0 );
		asrt( count( $ilse->alias('developer')->ownProject ), 2 );
		R::storeAll( array( $ilse, $irene) );
		$ilse  = R::load( 'person', $ilse->id );
		$irene = R::load( 'person', $irene->id );
		asrt( count( $ilse->alias('designer')->ownProject ), 0 );
		asrt( count( $ilse->alias('developer')->ownProject ), 2 );
		asrt( count( $irene->alias('designer')->ownProject), 1 );
		asrt( count( $irene->alias('developer')->ownProject), 0 );
		R::storeAll( array( $ilse, $irene) );
		$ilse  = R::load( 'person', $ilse->id );
		$irene = R::load( 'person', $irene->id );
		asrt( count( $ilse->alias('designer')->ownProject ), 0 );
		asrt( count( $ilse->alias('developer')->ownProject ), 2 );
		asrt( count( $irene->alias('designer')->ownProject), 1 );
		asrt( count( $irene->alias('developer')->ownProject), 0 );
	}

	/**
	 * Issue 291. State not cleared.
	 *
	 * @return void
	 */
	public function testFetchTypeConfusionIssue291()
	{
		list( $teacher, $student ) = R::dispense( 'person', 2 ) ;
		$teacher->name = 'jimmy' ;
		$student->name = 'jacko' ;
		R::store( $teacher ) ;
		R::store( $student ) ;
		$client = R::dispense( 'client' ) ;
		$client->firm = 'bean AG' ;
		R::store( $client ) ;
		$project = R::dispense( 'project' ) ;
		$project->teacher = $teacher ;
		$project->student = $student ;
		$project->client = $client ;
		R::store( $project ) ;
		unset( $project->student ) ;
		R::store( $project ) ;
		$project = R::load( 'project', 1 ) ;
		$teacher = $project->fetchAs( 'person' )->teacher ;
		$student = $project->fetchAs( 'person' )->student ;
		$client = $project->client ; // this will select from "person" instead of "client"
		asrt( $client->firm, 'bean AG' );
	}

	/**
	 * Test switching alias (also issue #291).
	 *
	 * @return void
	 */
	public function testAliasSwitch()
	{
		$student = R::dispense( 'person' );
		$project = R::dispense( 'project' );
		$project->student = $student;
		R::store( $project );
		$person = R::load( 'person', $student->id );
		asrt( count( $person->alias( 'student' )->ownProject ), 1);
		asrt( count( $person->alias( 'teacher' )->ownProject ), 0);
	}

	/**
	 * Associating two beans, then loading the associated bean
	 *
	 * @return void
	 */
	public function testAssociated()
	{
		$person       = R::dispense( 'person' );
		$person->name = 'John';
		R::store( $person );
		$course       = R::dispense( 'course' );
		$course->name = 'Math';

		R::store( $course );
		$course->teacher = $person;
		$id      = R::store( $course );
		$course  = R::load( 'course', $id );
		$teacher = $course->fetchAs( 'person' )->teacher;
		asrt( $teacher->name, 'John' );

		//Trying to load a property that has an invalid name
		$book = R::dispense( 'book' );
		$page = R::dispense( 'page' );
		$book->wrongProperty = array( $page );
		try {
			$book->wrongProperty[] = $page;
			R::store( $book );
			fail();
		} catch ( RedException $e ) {
			pass();
		} catch ( \Exception $e ) {
			fail();
		}
	}

	/**
	 * Test for quick detect change.
	 *
	 * @return void
	 */
	public function basic()
	{
		$book = R::dispense( 'book' );

		asrt( isset( $book->prop ), FALSE ); //not a very good test
		asrt( in_array( 'prop', array_keys( $book->export() ) ), FALSE ); //better...

		$book = R::dispense( 'book' );
		$page = R::dispense( 'page' );

		$book->paper = $page;

		$id   = R::store( $book );
		$book = R::load( 'book', $id );

		asrt( FALSE, ( isset( $book->paper ) ) );
		asrt( FALSE, ( isset( $book->page ) ) );

		/**
		 * The following tests try to store various things that aren't
		 * beans (which is expected) with the own* and shared* properties
		 * which only accept beans as assignments, so they're expected to fail
		 */
		foreach ( array( 'a string', 1928, TRUE, NULL, array()) as $value ) {
			try {
				$book->ownPage[] = $value;
				R::store( $book );
				$book->sharedPage[] = $value;
				R::store( $book );
				fail();
			} catch ( RedException $e ) {
				pass();
			} catch ( \Exception $e ) {
				if (strpos($e->getMessage(),'Array to string conversion')===FALSE) {
					fail();
				}
			}
		}
	}

	/**
	 * Finding $person beans that have been aliased into various roles
	 *
	 * @return void
	 */
	public function testAliasedFinder()
	{
		$message          = R::dispense( 'message' );
		$message->subject = 'Roommate agreement';
		list( $sender, $recipient ) = R::dispense( 'person', 2 );
		$sender->name    = 'Sheldon';
		$recipient->name = 'Leonard';
		$message->sender    = $sender;
		$message->recipient = $recipient;
		$id      = R::store( $message );
		$message = R::load( 'message', $id );
		asrt( $message->fetchAs( 'person' )->sender->name, 'Sheldon' );
		asrt( $message->fetchAs( 'person' )->recipient->name, 'Leonard' );
		$otherRecipient       = R::dispense( 'person' );
		$otherRecipient->name = 'Penny';
		$message->recipient = $otherRecipient;
		R::store( $message );
		$message = R::load( 'message', $id );
		asrt( $message->fetchAs( 'person' )->sender->name, 'Sheldon' );
		asrt( $message->fetchAs( 'person' )->recipient->name, 'Penny' );
	}

	/**
	 * Test Basic Fetch AS functionality.
	 */
	public function testBasicFetchAs()
	{
		$project       = R::dispense( 'project' );
		$project->name = 'Mutant Project';
		list( $teacher, $student ) = R::dispense( 'person', 2 );
		$teacher->name = 'Charles Xavier';
		$project->student       = $student;
		$project->student->name = 'Wolverine';
		$project->teacher       = $teacher;
		$id      = R::store( $project );
		$project = R::load( 'project', $id );
		asrt( $project->fetchAs( 'person' )->teacher->name, 'Charles Xavier' );
		asrt( $project->fetchAs( 'person' )->student->name, 'Wolverine' );
	}

	/**
	 * Test Basic list variations.
	 *
	 * @return void
	 */
	public function testBasicListVariations()
	{
		$farm    = R::dispense( 'building' );
		$village = R::dispense( 'village' );
		$farm->name    = 'farm';
		$village->name = 'Dusty Mountains';
		$farm->village = $village;
		$id   = R::store( $farm );
		$farm = R::load( 'building', $id );
		asrt( $farm->name, 'farm' );
		asrt( $farm->village->name, 'Dusty Mountains' );
		$village = R::dispense( 'village' );
		list( $mill, $tavern ) = R::dispense( 'building', 2 );
		$mill->name   = 'Mill';
		$tavern->name = 'Tavern';
		$village->ownBuilding = array( $mill, $tavern );
		$id      = R::store( $village );
		$village = R::load( 'village', $id );
		asrt( count( $village->ownBuilding ), 2 );
		$village2 = R::dispense( 'village' );
		$army     = R::dispense( 'army' );
		$village->sharedArmy[]  = $army;
		$village2->sharedArmy[] = $army;
		$id1 = R::store( $village );
		$id2 = R::store( $village2 );
		$village1 = R::load( 'village', $id1 );
		$village2 = R::load( 'village', $id2 );
		asrt( count( $village1->sharedArmy ), 1 );
		asrt( count( $village2->sharedArmy ), 1 );
		asrt( count( $village1->ownArmy ), 0 );
		asrt( count( $village2->ownArmy ), 0 );
	}

	/**
	 * Tests whether aliasing plays nice with beautification.
	 * Ensure that aliased column aren't beautified
	 *
	 * @return void
	 */
	public function testAliasWithBeautify()
	{
		$points = R::dispense( 'point', 2 );
		$line   = R::dispense( 'line' );
		$line->pointA = $points[0];
		$line->pointB = $points[1];
		R::store( $line );
		$line2 = R::dispense( 'line' );
		$line2->pointA = $line->fetchAs('point')->pointA;
		$line2->pointB = R::dispense( 'point' );
		R::store( $line2 );

		//now we have two points per line (1-to-x)
		//I want to know which lines cross A:
		$a = R::load( 'point', $line->pointA->id ); //reload A
		$lines = $a->alias( 'pointA' )->ownLine;
		asrt( count( $lines ), 2 );
	}
}
