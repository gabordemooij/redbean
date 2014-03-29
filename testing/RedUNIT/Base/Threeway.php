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
		
	}
}