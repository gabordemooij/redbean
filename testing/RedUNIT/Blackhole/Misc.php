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
		
		try {
			$candy = R::dispense('CandyBar');
			fail();
		}
		catch(RedBean_Exception_Security $e){
			pass();
		}
		
		$candy = R::dispense('candybar');
		
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
		
		testpack('test to string override');
		$band = R::dispense('band');
		$str = strval($band);
		asrt($str,'bigband');
		
		testpack('test whether we can use isset/set in model');
		$band->setProperty('property1',123);
		asrt($band->property1,123);
		asrt($band->checkProperty('property1'),true);
		asrt($band->checkProperty('property2'),false);
		$band = new Model_Band;
		$bean = R::dispense('band');
		$bean->property3 = 123;
		$band->loadBean($bean);
		$bean->property4 = 345;
		$band->setProperty('property1',123);
		asrt($band->property1,123);
		asrt($band->checkProperty('property1'),true);
		asrt($band->checkProperty('property2'),false);
		asrt($band->property3,123);
		asrt($band->property4,345);
		
		testpack('Test blackhold DSN and setup()');
		
		R::setup('blackhole:database');
		pass();
		asrt(isset(R::$toolboxes['default']),true);
		try{
			(R::$toolboxes['default']->getDatabaseAdapter()->getDatabase()->connect());
			fail();
		}
		catch(PDOException $e){
			pass();
		}
		
		testpack('Can we pass a PDO object to Setup?');
		$pdo = new PDO('sqlite:test.db');
		$toolbox = RedBean_Setup::kickstart($pdo);
		asrt(($toolbox instanceof RedBean_ToolBox),true);
		asrt(($toolbox->getDatabaseAdapter() instanceof RedBean_Adapter),true);
		asrt(($toolbox->getDatabaseAdapter()->getDatabase()->getPDO() instanceof PDO),true);
		
		
		testpack('Test array interface of beans');
		$bean = R::dispense('bean');
		$bean->hello = 'hi';
		$bean->world = 'planet';
		asrt($bean['hello'],'hi');
		asrt(isset($bean['hello']),true);
		asrt(isset($bean['bye']),false);
		$bean['world'] = 'sphere';
		asrt($bean->world,'sphere');
		foreach($bean as $key=>$el) { 
			if ($el=='sphere' || $el=='hi' || $el==0) pass(); else fail();
			if ($key=='hello' || $key=='world' || $key=='id') pass(); else fail();
		}
		asrt(count($bean),3);
		unset($bean['hello']);
		asrt(count($bean),2);
		asrt(count(R::dispense('countable')),1);
		
		//otherwise untestable...
		$bean->setBeanHelper( new RedBean_BeanHelper_Facade() );
		R::$redbean->setBeanHelper( new RedBean_BeanHelper_Facade() );
		pass();
		
		
		
			
	}
	
}