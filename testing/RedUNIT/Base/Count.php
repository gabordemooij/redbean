<?php

class RedUNIT_Base_Count extends RedUNIT_Base {

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
				
		
	}

}