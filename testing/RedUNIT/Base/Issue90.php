<?php

class RedUNIT_Base_Issue90 extends RedUNIT_Base {

	public function run() {
		
	//	testpack('Test issue #90 - cannot trash bean with ownproperty if checked in model');
	
	$s = R::dispense('box');
	$s->name = 'a';
	$f = R::dispense('bottle');
	$s->ownBottle[] = $f;
	R::store($s);
	$s2 = R::dispense('box');
	$s2->name = 'a';
	R::store($s2);
	R::trash($s2);
	pass();
	}

}