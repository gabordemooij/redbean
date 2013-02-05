<?php
/**
 * RedUNIT_Base_Keywords 
 * 
 * @file 			RedUNIT/Base/Keywords.php
 * @description		Tests for possible keyword clashes.
 * 					This class is part of the RedUNIT test suite for RedBeanPHP.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Base_Keywords extends RedUNIT_Base {

	/**
	 * What drivers should be loaded for this test pack? 
	 */
	public function getTargetDrivers() {
		return array('mysql','pgsql','sqlite'); //CUBRID excluded for now.
	}
	
	/**
	 * Begin testing.
	 * This method runs the actual test pack.
	 * 
	 * @return void
	 */
	public function run() {
		
		$keywords = array('anokeyword','znokeyword','group','DROP','inner','JOIN','select',
		'table','int','cascade','float','CALL','in','status','order',
		'limit','having','else','if','while','distinct','like');
		
		$counter = 0;
		
		R::setStrictTyping(false);
		RedBean_OODBBean::setFlagBeautifulColumnNames(false);
		
		foreach($keywords as $k) {
			R::nuke();
			$bean = R::dispense($k);
			$bean->$k = $k;
			$id = R::store($bean);
			$bean = R::load($k,$id);
			$bean2 = R::dispense('other');
			$bean2->name = $k;
			$bean->bean = $bean2;
			$bean->ownBean[] = $bean2;
			$bean->sharedBean[] = $bean2;
			$id = R::store($bean);
			R::trash($bean);
			pass();
				
		}
		
		RedBean_OODBBean::setFlagBeautifulColumnNames(true);
		R::setStrictTyping(true);
	}
}