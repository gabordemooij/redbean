<?php
/**
 * RedUNIT_Base_Issue90 
 * 
 * @file 			RedUNIT/Base/Issue90.php
 * @description		Issue #90 - cannot trash bean with ownproperty if checked in model.
 * 					This class is part of the RedUNIT test suite for RedBeanPHP.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Base_Issue90 extends RedUNIT_Base {

	/**
	 * Begin testing.
	 * This method runs the actual test pack.
	 * 
	 * @return void
	 */
	public function run() {
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