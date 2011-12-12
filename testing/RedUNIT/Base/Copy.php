<?php
/**
 * RedUNIT_Base_Copy 
 * @file 			RedUNIT/Base/Copy.php
 * @description		Tests whether we can make a deep copy of a bean.
 * 					This class is part of the RedUNIT test suite for RedBeanPHP.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Base_Copy extends RedUNIT_Base {

	

	/**
	 * Begin testing.
	 * This method runs the actual test pack.
	 * 
	 * @return void
	 */
	public function run() {
		
		
		//test recursion
		R::nuke();
		list($d1,$d2) = R::dispense('document',2);
		$page = R::dispense('page');
		list($p1,$p2) = R::dispense('paragraph',2);
		list($e1,$e2) = R::dispense('excerpt',2);
		$id2 = R::store($d2);
		$p1->name = 'a';
		$p2->name = 'b';
		$page->title = 'my page';
		$page->ownParagraph = array($p1,$p2);
		$p1->ownExcerpt[] = $e1;
		$p2->ownExcerpt[] = $e2;
		$e1->ownDocument[] = $d2;
		$e2->ownDocument[] = $d1;
		$d1->ownPage[] = $page;
		$id1 = R::store($d1);
		$d1 = R::load('document',$id1);
		$d = R::dup($d1);
		$ids = array();
		asrt(($d instanceof RedBean_OODBBean),true);
		asrt(count($d->ownPage),1);
		foreach(end($d->ownPage)->ownParagraph as $p) {
			foreach($p->ownExcerpt as $e) {
				$ids[] = end($e->ownDocument)->id;
			}
		}
		sort($ids);
		asrt((int)$ids[0],0);
		asrt((int)$ids[1],$id1);
		R::store($d);
		pass();
		
		
		$phillies = R::dispense('diner');
		list($lonelyman,$man,$woman) = R::dispense('guest',3);
		$attendent = R::dispense('employee');
		$lonelyman->name = 'Bennie Moten';
		$man->name = 'Daddy Stovepipe';
		$woman->name = 'Missisipi Sarah';
		$attendent->name = 'Gus Cannon';
		$phillies->sharedGuest = array( $lonelyman, $man, $woman );
		$phillies->ownEmployee[] = $attendent;
		$props = R::dispense('prop',2);
		$props[0]->kind = 'cigarette';
		$props[1]->kind = 'coffee';
		$thought = R::dispense('thought');
		$thought->content = 'Blues';
		$thought2 = R::dispense('thought');
		$thought2->content = 'Jazz';
		$woman->ownProp[] = $props[0];
		$man->sharedProp[] = $props[1];
		$attendent->ownThought = array( $thought,$thought2 );	
		R::store($phillies);
		$diner = R::findOne('diner');
		$diner2 = R::dup($diner);
		$id2 = R::store($diner2);
		$diner2 = R::load('diner',$id2);
		
		
		asrt(count($diner->ownEmployee),1);
		asrt(count($diner2->ownEmployee),1);
		asrt(count($diner->sharedGuest),3);
		asrt(count($diner2->sharedGuest),3);
		
		$empl = reset($diner->ownEmployee);
		asrt(count($empl->ownThought),2);
		$empl = reset($diner2->ownEmployee);
		asrt(count($empl->ownThought),2);
		
		//can we change something in the duplicate without changing the original?
		
		$empl->name = 'Marvin';
		$thought =  R::dispense('thought');
		$thought->content = 'depression';
		$empl->ownThought[] = $thought;
		array_pop( $diner2->sharedGuest );
		$guest = reset($diner2->sharedGuest);
		$guest->name = 'Arthur Dent';
		$id2 = R::store($diner2);
		$diner2 = R::load('diner',$id2);
		
		asrt(count($diner->ownEmployee),1);
		asrt(count($diner2->ownEmployee),1);
		asrt(count($diner->sharedGuest),3);
		asrt(count($diner2->sharedGuest),2);
		$emplOld = reset($diner->ownEmployee);
		asrt(count($emplOld->ownThought),2);
		$empl = reset($diner2->ownEmployee);
		asrt(count($empl->ownThought),3);
		asrt($empl->name,'Marvin');
		asrt($emplOld->name,'Gus Cannon');
		
		//However the shared beans must not be copied
		R::count('guest',3);
		R::count('shared_prop',1);
		$arthur = R::findOne('guest',' '.R::$writer->safeColumn('name').' = ? ',array('Arthur Dent'));
		asrt($arthur->name,'Arthur Dent');	
		
		
		
		
		
				
	}

}