<?php
/**
 * RedUNIT_Blackhole_Misc
 *  
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
	
	/*
	 * What drivers should be loaded for this test pack? 
	 */
	public function getTargetDrivers() {
		return array('sqlite');
	}
	
	/**
	 * Begin testing.
	 * This method runs the actual test pack.
	 * 
	 * @return void
	 */
	public function run() {

		testpack('Test with- and withCondition with Query Builder');
		R::nuke();
		$book = R::dispense('book');
		$page = R::dispense('page');
		$page->num = 1;
		$book->ownPage[] = $page;
		$page = R::dispense('page');
		$page->num = 2;
		$book->ownPage[] = $page;
		$id = R::store($book);
		$book = R::load('book',$id);
		asrt(count($book->ownPage),2);
		$book = R::load('book',$id);
		asrt(count($book->withCondition(R::$f->begin()->num(' > 1'))->ownPage),1);
		$book = R::load('book',$id); 
		asrt(count($book->withCondition(R::$f->begin()->num(' < ?')->put(2))->ownPage),1);
		$book = R::load('book',$id);
		asrt(count($book->with(R::$f->begin()->limit(' 1 '))->ownPage),1);
		$book = R::load('book',$id);
		asrt(count($book->withCondition(R::$f->begin()->num(' < 3'))->ownPage),2);
		$book = R::load('book',$id);
		asrt(count($book->with(R::$f->begin()->limit(' 2 '))->ownPage),2);
		
		testpack('Transaction suppr. in fluid mode');
		R::freeze(false);
		asrt(R::begin(),false);
		asrt(R::commit(),false);
		asrt(R::rollback(),false);
		R::freeze(true);
		asrt(R::begin(),true);
		asrt(R::commit(),true);
		R::freeze(false);
		
		testpack('Test transaction in facade');
		R::nuke();
		$bean = R::dispense('bean');
		$bean->name = 'a';
		R::store($bean);
		R::trash($bean);
		R::freeze(true);
		$bean = R::dispense('bean');
		$bean->name = 'a';
		R::store($bean);	
		asrt(R::count('bean'),1);
		R::trash($bean);
		asrt(R::count('bean'),0);
		$bean = R::dispense('bean');
		$bean->name = 'a';
		try {
			R::transaction(function()use($bean){
				R::store($bean);	
				R::transaction(function(){
					throw new Exception();
				});
			});
		}
		catch(Exception $e) {
			pass();
		}
		asrt(R::count('bean'),0);
		$bean = R::dispense('bean');
		$bean->name = 'a';
		try {
			R::transaction(function()use($bean){
				R::transaction(function()use($bean){
					R::store($bean);
					throw new Exception();
				});
			});
		}
		catch(Exception $e) {
			pass();
		}
		asrt(R::count('bean'),0);
		$bean = R::dispense('bean');
		$bean->name = 'a';
		try {
			R::transaction(function()use($bean){
				R::transaction(function()use($bean){
					R::store($bean);
				});
			});
		}
		catch(Exception $e) {
			pass();
		}
		asrt(R::count('bean'),1);
		R::freeze(false);
		try {
			R::transaction('nope');
			fail();
		}
		catch(Exception $e) {
			pass();
		}
		testpack('Test Camelcase 2 underscore');
		$names = array(
			'oneACLRoute'=>'one_acl_route',
			'ALLUPPERCASE'=>'alluppercase',
			'clientServerArchitecture'=>'client_server_architecture',
			'camelCase'=>'camel_case',
			'peer2peer'=>'peer2peer',
			'fromUs4You'=>'from_us4_you',
			'lowercase'=>'lowercase',
			'a1A2b'=>'a1a2b',
		);
		$bean = R::dispense('bean');
		foreach($names as $name => $becomes) {
			$bean->$name = 1;
			asrt(isset($bean->$becomes),true);
		}
		
		testpack('Test debugger check.');
		$old = R::$adapter;
		R::$adapter = null;
		try {
			R::debug(true);
			fail();
		}
		catch(RedBean_Exception_Security $e) {
			pass();
		}
		R::$adapter = $old;
		R::debug(false);
		
		testpack('Misc Tests');
		
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
		
		testpack('Test blackhole DSN and setup()');
		
		R::setup('blackhole:database');
		pass();
		asrt(isset(R::$toolboxes['default']),true);
		try{
			(R::$toolboxes['default']->getDatabaseAdapter()->getDatabase()->connect());
			fail();
		}
		catch(PDOException $e){
			pass();
			//make sure the message is non-descriptive - avoid revealing security details if user hasnt configured error reporting improperly.
			asrt($e->getMessage(),'Could not connect to database.');
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
		
		//test whether properties like owner and shareditem are still possible
		testpack('Test Bean Interface for Lists');
		$bean = R::dispense('bean');
		asrt(is_array($bean->owner),false); //must not be list, because first char after own is lowercase
		asrt(is_array($bean->shareditem),false); //must not be list, because first char after shared is lowercase
		asrt(is_array($bean->own),false);
		asrt(is_array($bean->shared),false);
		asrt(is_array($bean->own_item),false);
		asrt(is_array($bean->shared_item),false);
		asrt(is_array($bean->{'own item'}),false);
		asrt(is_array($bean->{'shared Item'}),false);
	}
	
}
