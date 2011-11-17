<?php

class RedUNIT_Base_Nuke extends RedUNIT_Base {
	
	public function run() {
	
		R::nuke();
		$bean = R::dispense('bean');
		R::store($bean);
		asrt(count(R::$writer->getTables()),1);
		R::nuke();
		asrt(count(R::$writer->getTables()),0);
		$bean = R::dispense('bean');
		R::store($bean);
		asrt(count(R::$writer->getTables()),1);
		R::freeze();
		R::nuke();
		asrt(count(R::$writer->getTables()),1); //no effect
		R::freeze(false);		
		
	}

}