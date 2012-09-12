<?php
/**
 * RedUNIT_Base_Count 
 * 
 * @file 			RedUNIT/Base/Count.php
 * @description		Tests for simple bean counting.
 * 					This class is part of the RedUNIT test suite for RedBeanPHP.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Base_Count extends RedUNIT_Base {

	/**
	 * Begin testing.
	 * This method runs the actual test pack.
	 * 
	 * @return void
	 */
	public function run() {
		testpack("Test count and wipe");
		$page = R::dispense("page");
		$page->name = "ABC";
		R::store($page);
		$n1 = R::count("page");
		$page = R::dispense("page");
		$page->name = "DEF";
		R::store($page);
		$n2 = R::count("page");
		asrt($n1+1, $n2);
		R::wipe("page");
		asrt(R::count("page"),0);
		asrt(R::$redbean->count("page"),0);
		asrt(R::$redbean->count("kazoo"),0); //non existing table
		R::freeze(true);
		asrt(R::$redbean->count("kazoo"),0); //non existing table
		R::freeze(false);
		$page = R::dispense('page');
		$page->name = 'foo';
		R::store($page);
		$page = R::dispense('page');
		$page->name = 'bar';
		R::store($page);
		asrt(R::count('page',' name = ? ',array('foo')),1);
		
	}

}