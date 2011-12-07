<?php
/**
 * RedUNIT_Blackhole_Misc 
 * @file 			RedUNIT/Blackhole/Misc.php
 * @description		Tests various features that do not rely on a database connection.
 * 					This class is part of the RedUNIT test suite for RedBeanPHP.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */

class RedUNIT_Blackhole_Misc extends RedUNIT_Blackhole {
	/**
	 * Begin testing.
	 * This method runs the actual test pack.
	 * 
	 * @return void
	 */
	public function run() {
		
		$candy = R::dispense('CandyBar');
		$s = strval($candy);
		asrt($s,'candy!');
		
		$obj = new stdClass;
		$bean = R::dispense('bean');
		$bean->property1 = 'property1';
		$bean->exportToObj($obj);
		asrt($obj->property1,'property1');
		
		R::debug(1);
		flush();
		ob_flush();
		ob_clean();
		ob_start();
		R::exec('SELECT 123');
		$out = ob_get_contents();
		ob_end_clean();
		flush();
		pass();
		asrt((strpos($out,'SELECT 123')!==false),true);
		R::debug(0);
		flush();
		ob_flush();
		ob_clean();
		ob_start();
		R::exec('SELECT 123');
		$out = ob_get_contents();
		ob_end_clean();
		flush();
		pass();
		asrt($out,'');
		R::debug(0);
		pass();
	}
	
}