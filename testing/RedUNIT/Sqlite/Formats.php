<?php
/**
 * RedUNIT_Sqlite_Formats
 * 
 * @file 			RedUNIT/Sqlite/Formats.php
 * @description		Tests bean formatting with various data types.
 * 					This class is part of the RedUNIT test suite for RedBeanPHP.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Sqlite_Formats extends RedUNIT_Sqlite {
	
	/**
	 * Begin testing.
	 * This method runs the actual test pack.
	 * 
	 * @return void
	 */
	public function run() {
		R::$writer->setBeanFormatter(new BF);
		$bean = R::dispense('page');
		$bean->rating = 1;
		R::store($bean);
		$cols = R::$writer->getColumns('page');
		asrt($cols['rating'],'INTEGER');
		$bean->rating = 1.4;
		R::store($bean);
		$cols = R::$writer->getColumns('page');
		asrt($cols['rating'],'NUMERIC');
		$bean->rating = '1999-02-02';
		R::store($bean);
		$cols = R::$writer->getColumns('page');
		asrt($cols['rating'],'NUMERIC');
		$bean->rating = 'reasonable';
		R::store($bean);
		$cols = R::$writer->getColumns('page');
		asrt($cols['rating'],'TEXT');
		R::$writer->setBeanFormatter(new RedBean_DefaultBeanFormatter);
				
	}
	
}