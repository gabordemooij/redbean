<?php

class RedUNIT_Base_Aliasing extends RedUNIT_Base {
	
	public function run() {
			
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
		
		
		//test views icw aliases and n1
		R::nuke();
		$book = R::dispense('book');
		$page = R::dispense('page');
		$book->title = 'my book';
		$page->title = 'my page';
		$book->ownPage[] = $page;
		R::store($book);
		R::view('library2','book,page');
		$l2 = R::getRow('select * from library2 limit 1');
		asrt($l2['title'],'my book');
		asrt($l2['title_of_page'],'my page');
		
		
		
		$formatter = new Aliaser2();
		R::$writer->setBeanFormatter($formatter);
		$message = R::dispense('message');
		list($creator,$recipient) = R::dispense('user',2);
		$recipient->name = 'r';
		$creator->name = 'c';
		$message->recipient = $recipient;
		$message->creator = $creator;
		$id = R::store($message);
		$message = R::load('message', $id);
		$recipient = $message->recipient;
		
		
		R::nuke();
	
		
		R::$writer->setBeanFormatter(new Alias3);
		
		list($p1,$p2,$p3)  = R::dispense('person',3);
		$p1->name = 'Joe';
		$p2->name = 'Jack';
		$p3->name = 'James';
		$fm = R::dispense('familymember');
		$fr = R::dispense('friend');
		$fr->buddy = $p1;
		$fm->familyman = $p2;
		$p3->ownFamilymember[] = $fm;
		$p3->ownFriend[] = $fr;
		$id = R::store($p3);
		
		
		$friend = R::load('person', $id);
		asrt(reset($friend->ownFamilymember)->familyman->name,'Jack');
		asrt(reset($friend->ownFriend)->buddy->name,'Joe');
		
		$Jill = R::dispense('person');
		$Jill->name = 'Jill';
		$familyJill = R::dispense('familymember');
		$friend->ownFamilymember[] = $familyJill;
		R::store($friend);
		$friend = R::load('person', $id);
		asrt(count($friend->ownFamilymember),2);
		array_pop($friend->ownFamilymember);
		R::store($friend);
		$friend = R::load('person', $id);
		asrt(count($friend->ownFamilymember),1);
		
		R::nuke();
		R::$writer->setBeanFormatter(new RedBean_DefaultBeanFormatter);
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
	
	
	}	
	
}

