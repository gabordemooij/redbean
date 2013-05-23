<?php
/**
 * RedUNIT_Base_Aliasing 
 * 
 * @file 			RedUNIT/Base/Aliasing.php
 * @description		Tests for nested beans with aliases, i.e. teacher alias for person etc.
 * 					This class is part of the RedUNIT test suite for RedBeanPHP.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Base_Aliasing extends RedUNIT_Base {
	
	/**
	 * Begin testing.
	 * This method runs the actual test pack.
	 * 
	 * @return void
	 */
	public function run() {
		
		$person = R::dispense('person');
		$person->name = 'John';
		R::store($person);
		$course = R::dispense('course');
		$course->name = 'Math';
		R::store($course);
		$course->teacher = $person;
		$id = R::store($course);
		$course = R::load('course',$id);
		$teacher = $course->fetchAs('person')->teacher;
		asrt($teacher->name,'John');	
			
		//Invalid properties
		$book = R::dispense('book');
		$page = R::dispense('page');
		//wrong property name
		$book->wrongProperty = array($page);
		try{
			$book->wrongProperty[] = $page;
			R::store($book);
			fail();
		}
		catch(RedBean_Exception_Security $e){
			pass();
		}
		catch(Exception $e){
			fail();
		}
		
		//Test for quick detect change
		R::nuke();
		$book = R::dispense('book');
		if ($book->prop) { }
		//echo $book;
		asrt(isset($book->prop),false);//not a very good test
		asrt(in_array('prop',array_keys($book->export())),false);//better...
		
		
		$book = R::dispense('book');
		$page = R::dispense('page');
		$book->paper = $page;
		$id = R::store($book);
		$book = R::load('book', $id);
		asrt(false,(isset($book->paper)));
		asrt(false,(isset($book->page)));
		
		//Try to add invalid things in arrays; should not be possible...
		try{
			$book->ownPage[] = new stdClass(); R::store($book); fail();
		}
		catch(RedBean_Exception_Security $e){ pass();}
		catch(Exception $e){fail();}
		
		try{
			$book->sharedPage[] = new stdClass(); R::store($book); fail();
		}
		catch(RedBean_Exception_Security $e){ pass();}
		catch(Exception $e){fail();}
		
		
		try{
			$book->ownPage[] = "a string"; R::store($book); fail();
		}
		catch(RedBean_Exception_Security $e){ pass();}
		catch(Exception $e){fail();}
		
		try{
			$book->sharedPage[] = "a string"; R::store($book); fail();
		}
		catch(RedBean_Exception_Security $e){ pass();}
		catch(Exception $e){fail();}
		
		try{
			$book->ownPage[] = 1928; R::store($book); fail();
		}
		catch(RedBean_Exception_Security $e){ pass();}
		catch(Exception $e){fail();}
		
		try{
			$book->sharedPage[] = 1928; R::store($book); fail();
		}
		catch(RedBean_Exception_Security $e){ pass();}
		catch(Exception $e){fail();}
		
		try{
			$book->ownPage[] = true; R::store($book); fail();
		}
		catch(RedBean_Exception_Security $e){ pass();}
		catch(Exception $e){fail();}
		
		try{
			$book->sharedPage[] = false; R::store($book); fail();
		}
		catch(RedBean_Exception_Security $e){ pass();}
		catch(Exception $e){fail();}
		
		try{
			$book->ownPage[] = null; R::store($book); fail();
		}
		catch(RedBean_Exception_Security $e){ pass();}
		catch(Exception $e){fail();}
		
		try{
			$book->sharedPage[] = null; R::store($book); fail();
		}
		catch(RedBean_Exception_Security $e){ pass();}
		catch(Exception $e){fail();}
		
		
		try{
			$book->ownPage[] = array(); R::store($book); fail();
		}
		catch(RedBean_Exception_Security $e){ pass();}
		catch(Exception $e){fail();}
		
		try{
			$book->sharedPage[] = array(); R::store($book); fail();
		}
		catch(RedBean_Exception_Security $e){ pass();}
		catch(Exception $e){fail();}
		
		
		R::nuke();
		$message = R::dispense('message');
		$message->subject = 'Roommate agreement';
		list($sender,$recipient) = R::dispense('person',2);
		$sender->name = 'Sheldon';
		$recipient->name = 'Leonard';
		$message->sender = $sender;
		$message->recipient = $recipient;
		$id = R::store($message);
		$message = R::load('message', $id);
		asrt($message->fetchAs('person')->sender->name,'Sheldon');
		asrt($message->fetchAs('person')->recipient->name,'Leonard');
		$otherRecipient = R::dispense('person');
		$otherRecipient->name = 'Penny';
		$message->recipient = $otherRecipient;
		R::store($message);
		$message = R::load('message', $id);
		asrt($message->fetchAs('person')->sender->name,'Sheldon');
		asrt($message->fetchAs('person')->recipient->name,'Penny');
		
		
		R::nuke();
		$project = R::dispense('project');
		$project->name = 'Mutant Project';
		list($teacher,$student) = R::dispense('person',2);
		$teacher->name = 'Charles Xavier';
		$project->student = $student;
		$project->student->name = 'Wolverine';
		$project->teacher = $teacher;
		$id = R::store($project);
		$project = R::load('project',$id);
		asrt($project->fetchAs('person')->teacher->name,'Charles Xavier');
		asrt($project->fetchAs('person')->student->name,'Wolverine');
		
		R::nuke();
		$farm = R::dispense('building');
		$village = R::dispense('village');
		$farm->name = 'farm';
		$village->name = 'Dusty Mountains';
		$farm->village = $village;
		$id = R::store($farm);
		$farm = R::load('building',$id);
		asrt($farm->name,'farm');
		asrt($farm->village->name,'Dusty Mountains');
		
		$village = R::dispense('village');
		list($mill,$tavern) = R::dispense('building',2);
		$mill->name = 'Mill';
		$tavern->name = 'Tavern';
		$village->ownBuilding = array($mill,$tavern);
		$id = R::store($village);
		$village = R::load('village',$id);
		asrt(count($village->ownBuilding),2);
		
		
		$village2 = R::dispense('village');
		$army = R::dispense('army');
		$village->sharedArmy[] = $army;
		$village2->sharedArmy[] = $army;
		$id1=R::store($village);
		$id2=R::store($village2);
		$village1 = R::load('village',$id1);
		$village2 = R::load('village',$id2);
		asrt(count($village1->sharedArmy),1);
		asrt(count($village2->sharedArmy),1);
		asrt(count($village1->ownArmy),0);
		asrt(count($village2->ownArmy),0);
		
		//aliased column should be beautified
		R::nuke();
		$points = R::dispense('point', 2);
		$line = R::dispense('line');
		$line->pointA = $points[0];
		$line->pointB = $points[1];
		R::store($line);
		$line2 = R::dispense('line');
		$line2->pointA = $line->pointA;
		$line2->pointB = R::dispense('point');
		R::store($line2);
		//now we have two points per line (1-to-x)
		//I want to know which lines cross A:
		$a = R::load('point', $line->pointA->id); //reload A
		$lines = $a->alias('pointA')->ownLine;
		asrt(count($lines), 2);
	}	
}

