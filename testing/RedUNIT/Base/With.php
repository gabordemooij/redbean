<?php
/**
 * RedUNIT_Base_With
 *  
 * @file 			RedUNIT/Base/With.php
 * @description		Tests query modification of own-lists with prefix-with
 * 					method.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Base_With extends RedUNIT_Base {
	
	/**
	 * This test suite uses specific SQL, only suited for MySQL.
	 * @return array 
	 */
	public function getTargetDrivers() {
		return array('mysql');
	}
	
	/**
	 * Begin testing.
	 * This method runs the actual test pack.
	 * 
	 * @return void
	 */
	public function run(){
	
		R::nuke();
		list($book1,$book2,$book3) = R::dispense('book',3);
		$book1->position = 1;
		$book2->position = 2;
		$book3->position = 3;
		$shelf = R::dispense('shelf');
		$shelf->ownBook = array($book1,$book2,$book3);
		$id = R::store($shelf);
		$shelf = R::load('shelf',$id);
		$books = $shelf->with(' ORDER BY position ASC ')->ownBook;
		$book1 = array_shift($books);
		asrt((int)$book1->position,1);
		$book2 = array_shift($books);
		asrt((int)$book2->position,2);
		$book3 = array_shift($books);
		asrt((int)$book3->position,3);
		$books = $shelf->with(' ORDER BY position DESC ')->ownBook;
		$book1 = array_shift($books);
		asrt((int)$book1->position,3);
		$book2 = array_shift($books);
		asrt((int)$book2->position,2);
		$book3 = array_shift($books);
		asrt((int)$book3->position,1);
		//R::debug(1);
		$shelf = R::load('shelf',$id);
		$books = $shelf->with(' AND position > 2 ')->ownBook;
		asrt(count($books),1);
		$shelf = R::load('shelf',$id);
		$books = $shelf->with(' AND position < 3 ')->ownBook;
		asrt(count($books),2);
		$shelf = R::load('shelf',$id);
		$books = $shelf->with(' AND position = 1 ')->ownBook;
		asrt(count($books),1);
		$shelf = R::load('shelf',$id);
		$books = $shelf->withCondition(' position > -1 ')->ownBook;
		asrt(count($books),3);
		
		//with-condition should not affect storing
		$shelf = R::load('shelf',$id);
		$books = $shelf->with(' AND position = 1 ')->ownBook;
		asrt(count($books),1);
		asrt(count($shelf->ownBook),1);
		$book = reset($shelf->ownBook);
		$book->title = 'Trees and other Poems';
		R::store($shelf);
		$books = $shelf->withCondition(' position > -1 ')->ownBook;
		asrt(count($books),3);
		asrt(count($shelf->ownBook),3);
		$shelf = R::load('shelf',$id);
		$books = $shelf->with(' AND position = 1 ')->ownBook;
		$shelf->ownBook = array(); //also with trashing -- just trash one!
		R::store($shelf);
		$books = $shelf->withCondition(' position > -1 ')->ownBook;
		asrt(count($books),2);
		//with should cause a reload of a list
		$shelf = R::load('shelf',$id);
		$books = $shelf->with(' AND position = 2 ')->ownBook;
		asrt(count($books),1);
		$books = $shelf->withCondition(' position > -1 ')->ownBook;
		asrt(count($books),2);
		$book = reset($books);
		$book->title = 'Venetian Music';
		R::store($shelf); //should not affect storage (fact that we used with twice, unsetting prop)
		$shelf = R::load('shelf',$id);
		asrt(count($shelf->ownBook),2);
		
		//alias
		list($game1,$game2,$game3) = R::dispense('game',3);
		list($t1,$t2,$t3) = R::dispense('team',3);
		$t1->name = 'Bats';
		$t2->name = 'Tigers';
		$t3->name = 'Eagles';
		$game1->name = 'a';
		$game1->team1 = $t1;
		$game1->team2 = $t2;
		$game2->name = 'b';
		$game2->team1 = $t1;
		$game2->team2 = $t3;
		$game3->name = 'c';
		$game3->team1 = $t2;
		$game3->team2 = $t3;
		R::storeAll(array($game1,$game2,$game3));
		$team1 = R::load('team',$t1->id);
		$team2 = R::load('team',$t2->id);
		$team3 = R::load('team',$t3->id);
		asrt(count($team1->alias('team1')->ownGame),2);
		asrt(count($team2->alias('team1')->ownGame),1);
		$team1 = R::load('team',$t1->id);
		$team2 = R::load('team',$t2->id);
		asrt(count($team1->alias('team2')->ownGame),0);
		asrt(count($team2->alias('team2')->ownGame),1);
		asrt(count($team3->alias('team1')->ownGame),0);
		$team3 = R::load('team',$t3->id);
		asrt(count($team3->alias('team2')->ownGame),2);
		$team1 = R::load('team',$t1->id);
		$games = $team1->alias('team1')->ownGame;
		
		
		$game4 = R::dispense('game');
		$game4->name = 'd';
		$game4->team2 = $t3;
		$team1->alias('team1')->ownGame[] = $game4;
		R::store($team1);
		$team1 = R::load('team',$t1->id);
		asrt(count($team1->alias('team1')->ownGame),3);
		
		foreach($team1->ownGame as $g) if ($g->name=='a') $game = $g;
		$game->name = 'match';
		R::store($team1);
		$team1 = R::load('team',$t1->id);
		asrt(count($team1->alias('team1')->ownGame),3);
		$found = 0;
		foreach($team1->ownGame as $g) if ($g->name=='match') $found = 1;
		if ($found) pass();
		
		$team1->ownGame = array();
		R::store($team1);
		$team1 = R::load('team',$t1->id);
		asrt(count($team1->alias('team1')->ownGame),0);
		
		$team1->ownBook[] = $book1;
		R::store($team1);
		$team1 = R::load('team',$t1->id);
		asrt(count($team1->alias('team1')->ownGame),0);
		asrt(count($team1->ownBook),1);
	}
}