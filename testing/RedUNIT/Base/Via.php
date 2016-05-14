<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;
use RedBeanPHP\ToolBox as ToolBox;
use RedBeanPHP\AssociationManager as AssociationManager;
use RedBeanPHP\RedException\SQL as SQL;
use RedBeanPHP\QueryWriter\AQueryWriter as AQueryWriter;

/**
 * Via
 *
 * Tests the via() function to hop over an N-M table to
 * obtain a indirectly related bean, i.e. given person, participant
 * and project you should be able to obtain a the project associated
 * with a certain person via() the participant entity.
 *
 * @file    RedUNIT/Base/Via.php
 * @desc    Via tests
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Via extends Base
{
	/**
	 * Tests fix for issue #378.
	 * Via property does not get cleared properly.
	 *
	 * @return void
	 */
	public function testIssue378()
	{
		R::nuke();
		$mediaBean = R::dispense('media');
		$fooPerson = R::dispense('person');
		$mediaBean->sharedPersonList[] = $fooPerson;
		R::store($mediaBean);
		asrt( count( $mediaBean->sharedPersonList ), 1 );
		$person = R::findOne('person');
		$person->via('relation')->sharedMarriageList[] = R::dispense('marriage');
		//this second one caused the via property to not get cleared
		$person->via('relation')->sharedMarriageList;
		asrt( count( $person->sharedMediaList ), 1 );
		//also found this scenario, non-existing property
		$book = R::dispense('book');
		$book->sharedPage[] = R::dispense('page');
		R::store($book);
		$book = $book->fresh();
		$book->via('garbage')->nothing;
		asrt( count( $book->sharedPageList ), 1 );
		//yet another
		$book = R::dispense('magazine');
		$book->ownAdList[] = R::dispense('ad');
		$book->sharedPage[] = R::dispense('page');
		R::store($book);
		$book = $book->fresh();
		$book->via('garbage')->ownAdList;
		asrt( count( $book->sharedPageList ), 1 );
		$book = R::dispense('folder');
		$book->sharedPage[] = R::dispense('page');
		R::store($book);
		$book = $book->fresh();
		$book->via('garbage')->sharedItemList[] = R::dispense('item');
		asrt( count( $book->sharedPageList ), 1 );
		$book = R::dispense('folder2');
		$book->sharedPage[] = R::dispense('page');
		R::store($book);
		$book = $book->fresh();
		$book->via('garbage')->sharedItemList[] = R::dispense('item');
		$book->via('garbage')->sharedItemList[] = R::dispense('item');
		asrt( count( $book->sharedPageList ), 1 );
		$book = R::dispense('folder3');
		$book->sharedPage[] = R::dispense('page');
		R::store($book);
		$book = $book->fresh();
		$book->via('garbage')->item = R::dispense('item');
		asrt( count( $book->sharedPageList ), 1 );
		$book = R::dispense('folder3');
		$book->sharedPage[] = R::dispense('page');
		R::store($book);
		$book = $book->fresh();
		$book->via('garbage')->item = 'test';
		asrt( count( $book->sharedPageList ), 1 );
		//yet another
		$book = R::dispense('leaflet');
		$book->title = 'leaflet';
		$book->sharedPage[] = R::dispense('page');
		R::store($book);
		$book = $book->fresh();
		$book->via('garbage')->title;
		asrt( count( $book->sharedPageList ), 1 );
		//yet another
		$book = R::dispense('paper');
		$book->author = R::dispense('author');
		$book->sharedPage[] = R::dispense('page');
		R::store($book);
		$book = $book->fresh();
		$book->via('garbage')->author;
		asrt( count( $book->sharedPageList ), 1 );
		$book = R::dispense('paper2');
		$book->sharedPage[] = R::dispense('page');
		R::store($book);
		$book = $book->fresh();
		$book->via('garbage')->author;
		asrt( count( $book->sharedPageList ), 1 );
		//yet another one
		$book = R::dispense('archive');
		$book->sharedItem[] = R::dispense('item');
		$book->sharedPage[] = R::dispense('page');
		R::store($book);
		$book = $book->fresh();
		unset( $book->via('garbage')->sharedItem );
		asrt( count( $book->sharedPageList ), 1 );
		//theoretic cases
		$book = R::dispense('guide');
		$book->sharedItem[] = R::dispense('item');
		$book->sharedPage[] = R::dispense('page');
		R::store($book);
		$book = $book->fresh();
		$book->via('relation')->countShared('item');
		$book->via('relation')->countShared('item');
		asrt( count( $book->sharedPageList ), 1 );
		$book = R::dispense('catalogue');
		$book->sharedPage[] = R::dispense('page');
		R::store($book);
		$book = $book->fresh();
		$book->via('relation')->countShared('item');
		$book->via('relation')->countShared('item');
		asrt( count( $book->sharedPageList ), 1 );
		$book = R::dispense('tabloid');
		$book->ownItemList[] = R::dispense('item');
		$book->sharedPage[] = R::dispense('page');
		R::store($book);
		$book = $book->fresh();
		$book->via('relation')->countOwn('item');
		$book->via('relation')->countOwn('item');
		asrt( count( $book->sharedPageList ), 1 );
		$book = R::dispense('booklet');
		$book->ownItemList[] = R::dispense('item');
		$book->sharedPage[] = R::dispense('page');
		R::store($book);
		$book = $book->fresh();
		$book->via('relation')->countOwn('item');
		$book->via('relation')->countOwn('item');
		asrt( count( $book->sharedPageList ), 1 );
		AQueryWriter::clearRenames();
	}

	/**
	 * Via specific tests.
	 *
	 * @return void
	 */
	public function testViaAndSQL()
	{
		R::nuke();
		list($p1, $p2) = R::dispense('participant', 2);
		list($e1, $e2) = R::dispense('employee', 2);
		list($x1, $x2) = R::dispense('project', 2);
		$e1->name = 'Anna';
		$e2->name = 'John';
		$p1->project = $x1;
		$p1->employee = $e1;
		$p1->arole = 'designer';
		$p2->project = $x1;
		$p2->employee = $e2;
		$p2->arole = 'coder';
		R::storeAll(array( $p1, $p2 ));
		$project = R::load('project', $x1->id);
		$designers = $project
				->withCondition(' participant.arole = ? ', array( 'designer' ) )
				->via( 'participant' )
				->sharedEmployeeList;
		$anna = reset( $designers );
		asrt(count($designers), 1);
		asrt($anna->name, 'Anna');
		$coders = $project
				->withCondition(' participant.arole = ? ', array( 'coder' ) )
				->via( 'participant' )
				->sharedEmployeeList;
		$john = reset( $coders );
		asrt(count($coders), 1);
		asrt($john->name, 'John');
	}

	/**
	 * Test Via and Link together.
	 *
	 * @return void
	 */
	public function testViaAndLink()
	{
		R::nuke();
		list( $John, $Anna, $Celine ) = R::dispense( 'employee', 3 );
		$John->badge   = 'John';
		$Anna->badge   = 'Anna';
		$Celine->badge = 'Celine';
		$project = R::dispense( 'project' );
		$project->name = 'x';
		$project2 = R::dispense( 'project' );
		$project2->name = 'y';
		$John->link( 'participant', array(
			 'arole' => 'designer'
		) )->project = $project;
		$Anna->link( 'participant', array(
			 'arole' => 'developer'
		) )->project = $project;
		$Celine->link( 'participant', array(
			 'arole' => 'sales'
		) )->project = $project2;
		$Anna->link('participant', array(
			 'arole' => 'lead'
		) )->project = $project2;
		R::storeAll( array( $project, $project2, $John, $Anna, $Celine )  );
		$employees = $project
			->with(' ORDER BY badge ASC ')
			->via( 'participant' )
			->sharedEmployee;
		asrt( is_array( $employees ), TRUE );
		asrt( count( $employees ), 2 );
		$badges = array();
		foreach( $employees as $employee ) {
			$badges[] = $employee->badge;
		}
		asrt( implode( ',', $badges ), 'Anna,John' );
		$employees = $project2
			->with(' ORDER BY badge ASC ')
			->via( 'participant' )
			->sharedEmployee;
		asrt( is_array( $employees ), TRUE );
		asrt( count( $employees ), 2 );
		$badges = array();
		foreach( $employees as $employee ) {
			$badges[] = $employee->badge;
		}
		asrt( implode( ',', $badges ), 'Anna,Celine' );
		$projects = $John->sharedProject;
		asrt( is_array( $projects ), TRUE );
		asrt( count( $projects ), 1 );
		$projectList = array();
		foreach( $projects as $project ) {
			$projectList[] =  $project->name;
		}
		sort( $projectList );
		asrt( implode( ',', $projectList ), 'x' );
		$projects = $Anna->sharedProject;
		asrt( is_array( $projects ), TRUE );
		asrt( count( $projects ), 2 );
		$projectList = array();
		foreach( $projects as $project ) {
			$projectList[] =  $project->name;
		}
		sort( $projectList );
		asrt( implode( ',', $projectList ), 'x,y' );
		$projects = $Anna->via( 'participant' )->sharedProject;
		asrt( is_array( $projects ), TRUE );
		asrt( count( $projects ), 2 );
		$projectList = array();
		foreach( $projects as $project ) {
			$projectList[] =  $project->name;
		}
		sort( $projectList );
		asrt( implode( ',', $projectList ), 'x,y' );
		$projects = $Celine->via( 'participant' )->sharedProject;
		asrt( is_array( $projects ), TRUE );
		asrt( count( $projects ), 1 );
		$projectList = array();
		foreach( $projects as $project ) {
			$projectList[] =  $project->name;
		}
		sort( $projectList );
		asrt( implode( ',', $projectList ), 'y' );
		$roles = $Anna->ownParticipant;
		asrt( is_array( $roles ), TRUE );
		asrt( count( $roles ), 2 );
		$roleList = array();
		foreach( $roles as $role ) {
			$roleList[] = $role->arole;
		}
		sort( $roleList );
		asrt( implode( ',', $roleList ), 'developer,lead' );
		$project2->sharedEmployee[] = $John;
		R::store( $project2 );
		$projects = $John->sharedProject;
		asrt( is_array( $projects ), TRUE );
		asrt( count( $projects ), 2 );
		$projectList = array();
		foreach( $projects as $project ) {
			$projectList[] =  $project->name;
		}
		sort( $projectList );
		asrt( implode( ',', $projectList ), 'x,y' );
		$projects = $John->via( 'participant' )->sharedProject;
		asrt( is_array( $projects ), TRUE );
		asrt( count( $projects ), 2 );
		$projectList = array();
		foreach( $projects as $project ) {
			$projectList[] =  $project->name;
		}
		sort( $projectList );
		asrt( implode( ',', $projectList ), 'x,y' );
	}

	/**
	 * Test effect of via on shared list removal of beans.
	 *
	 * @return void
	 */
	public function testViaAndRemove()
	{
		R::nuke();
		$project   = R::dispense( 'project' );
		$employees = R::dispense( 'employee', 2);
		$project->via( 'partcipant' )->sharedEmployeeList = $employees;
		R::store( $project );
		asrt( R::count('employee'), 2 );
		asrt( R::count('participant'), 2 );
		$project = $project->fresh();
		$project->sharedEmployee = array();
		R::store( $project );
		asrt( R::count( 'employee' ), 2 );
		asrt( R::count( 'participant' ), 0 );
	}
}
