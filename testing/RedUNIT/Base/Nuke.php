<?php
/**
 * RedUNIT_Base_Nuke
 * 
 * @file 			RedUNIT/Base/Nuke.php
 * @description		Test the nuke() function.
 * 					This class is part of the RedUNIT test suite for RedBeanPHP.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Base_Nuke extends RedUNIT_Base {
	
	/**
	 * Begin testing.
	 * This method runs the actual test pack.
	 * 
	 * @return void
	 */
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