<?php
/**
 * RedUNIT_Base_Issue259 
 * 
 * @file 			RedUNIT/Base/Issue90.php
 * @description		Issue #259 - Stash cache breaks model delegation in open().
 * 					This class is part of the RedUNIT test suite for RedBeanPHP.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Base_Issue259 extends RedUNIT_Base {

	/**
	 * Begin testing.
	 * This method runs the actual test pack.
	 * 
	 * @return void
	 */
	public function run() {
		testpack('Testing Issue #259 - Stash Cache breaks model delegation in open().');
		R::nuke();
		
		$mother = R::dispense('mother');
		$mother->desc = 'I am mother';
		R::store($mother);

		$child = R::dispense('child');
		$child->mother = $mother;
		$child->desc = 'I am child';
		$id = R::store($child);
		R::findOne('child', ' id = ?', array($id));
		R::find('child', ' id = ? ', array($id));
		R::load('child', $id);
	}
}


class Model_Mother extends RedBean_SimpleModel {
    public function open() {
        $bean = $this->bean;
        // $this & $bean are both referencing child incorrectly!
        asrt($this->bean->desc,'I am mother');
    }
}

class Model_Child extends RedBean_SimpleModel {
    public function open() {
         $this->bean->mother;
		 asrt($this->bean->desc,'I am child');
	}
}
