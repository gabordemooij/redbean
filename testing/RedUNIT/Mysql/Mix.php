<?php
/**
 * RedUNIT_Mysql_Mix
 * 
 * @file 			RedUNIT/Mysql/Mix.php
 * @description		Tests mixing SQL with PHP, SQLHelper class.
 * 					This class is part of the RedUNIT test suite for RedBeanPHP.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Mysql_Mix extends RedUNIT_Mysql {

	/**
	 * Begin testing.
	 * This method runs the actual test pack.
	 * 
	 * @return void
	 */
	public function run() {
		$toolbox = R::$toolbox;
		$adapter = $toolbox->getDatabaseAdapter();
		$mixer = new RedBean_SQLHelper($adapter);
		$now = $mixer->now();
		asrt(is_string($now),true);
		asrt((strlen($now)>5),true);
		$bean = R::dispense('bean');
		$bean->field1 = 'a';
		$bean->field2 = 'b';
		R::store($bean);
		$data = $mixer->begin()->select('*')->from('bean')
				->where(' field1 = ? ')->put('a')->get();
		
		asrt(is_array($data),true);
		$row = array_pop($data);
		asrt(is_array($row),true);
		asrt($row['field1'],'a');
		asrt($row['field2'],'b');
		$row = $mixer->begin()->select('field1','field2')->from('bean')
			->where(' 1 ')->limit('1')->get('row');
		asrt(is_array($row),true);
		asrt($row['field1'],'a');
		asrt($row['field2'],'b');
		$cell = $mixer->begin()->select('field1')->from('bean')
				->get('cell');
		
		asrt($cell,'a');
		$cell = $mixer->begin()->select_field1_from('bean')
				->get('cell');
		
		asrt($cell,'a');
		
		//now swictch back to non-capture mode (issue #142)
		$value = $mixer->now();
		asrt(is_object($value),false);
		asrt(is_scalar($value),true);
		asrt($value>0,true);
		
		$mixer->begin()->select_field1_from('bean');
		$mixer->clear();
		$value = $mixer->now();
		asrt(is_scalar($value),true);
		
		//test open and close block commands
		$bean = R::dispense('bean');
		$bean->num = 2;
		R::store($bean);
		$value = $mixer->begin()
				->select('num')->from('bean')->where('num IN')
					->open()
						->addSQL('2')
					->close()
				->get('cell');
		asrt(($value == 2),true);
		
		//test nesting
		$bean = R::dispense('bean');
		$bean->num = 2;
		R::store($bean);
		$value = $mixer->begin()
				->select('num')->from('bean')->where('num IN')
					->nest($mixer->getNew()->begin()->addSQL(' ( 2 ) '))
				->get('cell');
		asrt(($value == 2),true);
		
	}

}




