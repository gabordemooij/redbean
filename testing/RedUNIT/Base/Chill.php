<?php
/**
 * RedUNIT_Base_Chill
 * 
 * @file 			RedUNIT/Base/Chill.php
 * @description		Tests chill list functionality, i.e. freezing a subset of all types.
 * 					This class is part of the RedUNIT test suite for RedBeanPHP.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Base_Chill extends RedUNIT_Base {
	
	/**
	 * Begin testing.
	 * This method runs the actual test pack.
	 * 
	 * @return void
	 */
	public function run() {
	
		R::freeze(false);
		R::nuke();
		$bean = R::dispense('bean');
		$bean->col1 = '1';
		$bean->col2 = '2';
		R::store($bean);
		asrt( count(R::$writer->getColumns('bean')), 3);
		$bean->col3 = '3';
		R::store($bean);
		asrt( count(R::$writer->getColumns('bean')), 4);
		R::freeze( array('umbrella') );
		$bean->col4 = '4';
		R::store($bean);
		asrt( count(R::$writer->getColumns('bean')), 5);
		R::freeze( array('bean') );
		$bean->col5 = '5';
		try{ 
			R::store($bean);
			fail();
		}
		catch(Exception $e){ pass(); }
		asrt( count(R::$writer->getColumns('bean')), 5);
		R::freeze( array() );
		$bean->col5 = '5';
		R::store($bean);
		asrt( count(R::$writer->getColumns('bean')), 6);
		
		
	}
	
}