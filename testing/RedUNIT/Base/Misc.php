<?php
/**
 * RedUNIT_Base_Misc
 * 
 * @file 			RedUNIT/Base/Misc.php
 * @description		Various tests.
 * 					This class is part of the RedUNIT test suite for RedBeanPHP.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Base_Misc extends RedUNIT_Base {
	/**
	 * Begin testing.
	 * This method runs the actual test pack.
	 * 
	 * @return void
	 */
	public function run() {
		
		//test backward compatibility functions
		testpack('Test backward compatability methods');
		asrt(R::$writer->safeColumn('column',true),R::$writer->esc('column',true));
		asrt(R::$writer->safeColumn('column',false),R::$writer->esc('column',false));
		asrt(R::$writer->safeTable('table',true),R::$writer->esc('table',true));
		asrt(R::$writer->safeTable('table',false),R::$writer->esc('table',false));
		
		testpack('Beautiful column names');
		R::nuke();
		$town = R::dispense('town');
		$town->isCapital = false;
		$town->hasTrainStation = true;
		$town->name = 'BeautyVille';
		$houses = R::dispense('house',2);
		$houses[0]->isForSale = true;
		$town->ownHouse = $houses;
		R::store($town);
		$town = R::load('town',$town->id);
		asrt(($town->isCapital==false),true);
		asrt(($town->hasTrainStation==true),true);
		asrt(($town->name=='BeautyVille'),true);

		
		testpack('Accept datetime objects.');
		$cal = R::dispense('calendar');
		$cal->when = new DateTime('2000-01-01', new DateTimeZone('Pacific/Nauru'));
		asrt($cal->when,'2000-01-01 00:00:00');
		
		testpack('Affected rows test');
		global $currentDriver; 
		$toolbox = R::$toolbox;
		$adapter = $toolbox->getDatabaseAdapter();
		$writer  = $toolbox->getWriter();
		$redbean = $toolbox->getRedBean();
		$pdo = $adapter->getDatabase();
		
		$bean=$redbean->dispense('bean');
		$bean->prop = 3; //make test run with strict mode as well
		$redbean->store($bean);
		$adapter->exec('UPDATE bean SET prop = 2');
		asrt($adapter->getAffectedRows(),1);
		
		testpack('Testing Logger');
		R::$adapter->getDatabase()->setLogger( new RedBean_Logger_Default);
		asrt((R::$adapter->getDatabase()->getLogger() instanceof RedBean_Logger),true);
		asrt((R::$adapter->getDatabase()->getLogger() instanceof RedBean_Logger_Default),true);
		
		$bean = R::dispense('bean');
		$bean->property = 1;
		$bean->unsetAll(array('property'));
		asrt($bean->property,null);
		
		asrt(($bean->setAttr('property',2) instanceof RedBean_OODBBean),true);
		asrt($bean->property,2);
		
		asrt(preg_match('/\d\d\d\d\-\d\d\-\d\d/',R::isoDate()),1);
		asrt(preg_match('/\d\d\d\d\-\d\d\-\d\d\s\d\d:\d\d:\d\d/',R::isoDateTime()),1);
		
		$redbean = R::getRedBean();
		$adapter = R::getDatabaseAdapter();
		$writer = R::getWriter();
		asrt(($redbean instanceof RedBean_OODB),true);
		asrt(($adapter instanceof RedBean_Adapter),true);
		asrt(($writer instanceof RedBean_QueryWriter),true);
		
		R::setRedBean($redbean); pass(); //cant really test this
		R::setDatabaseAdapter($adapter); pass(); //cant really test this
		R::setWriter($writer); pass(); //cant really test this
		
		$u1 = R::dispense('user');
		$u1->name = 'Gabor';
		$u1->login = 'g';
		$u2 = R::dispense('user');
		$u2->name = 'Eric';
		$u2->login = 'e';
		R::store($u1);
		R::store($u2);
		$list = R::getAssoc('select login,'.R::$writer->esc('name').' from '.R::$writer->esc('user').' ');
		asrt($list['e'],'Eric');
		asrt($list['g'],'Gabor');
		
		$painting = R::dispense('painting');
		$painting->name = 'Nighthawks';
		$id=R::store($painting);
		
		$cooker = new RedBean_Plugin_Cooker();
		$cooker->setToolbox($toolbox);
		try {
			asrt($cooker->graph('abc'),'abc');
			fail();
		}
		catch(RedBean_Exception_Security $e) {
			pass();
		}
			
		foreach($writer->typeno_sqltype as $code=>$text) {
			asrt(is_integer($code),true);
			asrt(is_string($text),true);
		}
		foreach($writer->sqltype_typeno as $text=>$code) {
			asrt(is_integer($code),true);
			asrt(is_string($text),true);
		}
		
		R::exec('select * from nowhere');
		pass();
		R::getAll('select * from nowhere');
		pass();
		R::getAssoc('select * from nowhere');
		pass();
		R::getCol('select * from nowhere');
		pass();
		R::getCell('select * from nowhere');
		pass();
		R::getRow('select * from nowhere');
		pass();
		
		R::freeze(true);
		try{ R::exec('select * from nowhere'); fail(); }catch(RedBean_Exception_SQL $e){ pass(); }
		try{ R::getAll('select * from nowhere'); fail(); }catch(RedBean_Exception_SQL $e){ pass(); }
		try{ R::getCell('select * from nowhere'); fail(); }catch(RedBean_Exception_SQL $e){ pass(); }
		try{ R::getAssoc('select * from nowhere'); fail(); }catch(RedBean_Exception_SQL $e){ pass(); }
		try{ R::getRow('select * from nowhere'); fail(); }catch(RedBean_Exception_SQL $e){ pass(); }
		try{ R::getCol('select * from nowhere'); fail(); }catch(RedBean_Exception_SQL $e){ pass(); }
		R::freeze(false);
		
		
		
		R::nuke();
		
		
		if (method_exists(R::$adapter->getDatabase(),'getPDO')) 
		asrt($adapter->getDatabase()->getPDO() instanceof PDO, true);
		
		asrt(strlen($adapter->getDatabase()->getDatabaseVersion())>0,true);
		asrt(strlen($adapter->getDatabase()->getDatabaseType())>0,true);
		 
		
		R::nuke();
		$track = R::dispense('track');
		$album = R::dispense('cd');
		$track->name = 'a';
		$track->ordernum = 1;
		$track2 = R::dispense('track');
		$track2->ordernum = 2;
		$track2->name = 'b';
		R::associate( $album, $track );
		R::associate( $album, $track2 );
		$tracks = R::related( $album, 'track');
		$track = array_shift($tracks);
		$track2 = array_shift($tracks);
		$ab = $track->name.$track2->name;
		asrt(($ab=='ab' || $ab=='ba'),true);
		
		$t = R::dispense('person');
		$s = R::dispense('person');
		$s2 = R::dispense('person');
		$t->name = 'a';
		$t->role = 'teacher';
		$s->role = 'student';
		$s2->role = 'student';
		$s->name = 'a';
		$s2->name = 'b';
		$role = R::$writer->esc('role');
		R::associate($t, $s);
		R::associate($t, $s2);
		$students = R::related($t, 'person', sprintf(' %s  = ? ',$role),array("student"));
		$s = array_shift($students);
		$s2 = array_shift($students);
		asrt(($s->name=='a' || $s2->name=='a'),true);
		asrt(($s->name=='b' || $s2->name=='b'),true);
		//empty classroom
		R::clearRelations($t, 'person');
		R::associate($t,$s2);
		$students = R::related($t, 'person', sprintf(' %s  = ? ',$role),array("student"));
		asrt(count($students),1);
		$s = reset($students);
		asrt($s->name, 'b');

		testpack('transactions');
		R::nuke();
		R::begin();
		$bean = R::dispense('bean');
		R::store($bean);
		R::commit();
		asrt(R::count('bean'),1);
		R::wipe('bean');
		R::freeze(1);
		R::begin();
		$bean = R::dispense('bean');
		R::store($bean);
		R::rollback();
		asrt(R::count('bean'),0);
		R::freeze(false);
		
		
		testpack('genSlots');
		asrt(R::genSlots(array('a','b')),'?,?');				
		asrt(R::genSlots(array('a')),'?');
		asrt(R::genSlots(array()),'');
		
		
		
				
		testpack('FUSE models cant touch nested beans in update() - issue 106');
		R::nuke();
		
		$spoon = R::dispense('spoon');
		$spoon->name = 'spoon for test bean';
		$deep = R::dispense('deep');
		$deep->name = 'deepbean';
		$item = R::dispense('item');
		$item->val = 'Test';
		$item->deep = $deep;
		
		$test = R::dispense('test');
		$test->item = $item;
		$test->sharedSpoon[] = $spoon;
		
		
		$test->isnowtainted = true;
		$id=R::store($test); 
		$test = R::load('test',$id);
		asrt($test->item->val,'Test2');
		$can = reset($test->ownCan);
		$spoon = reset($test->sharedSpoon);
		asrt($can->name,'can for bean');
		asrt($spoon->name,'S2');
		asrt($test->item->deep->name,'123');
		asrt(count($test->ownCan),1);
		asrt(count($test->sharedSpoon),1);
		asrt(count($test->sharedPeas),10);
		asrt(count($test->ownChip),9);
		
		R::nuke();
		$coffee = R::dispense('coffee');
		$coffee->size = 'XL';
		$coffee->ownSugar = R::dispense('sugar',5);
		
		$id = R::store($coffee);
		
		
		$coffee=R::load('coffee',$id);
		asrt(count($coffee->ownSugar),3);
		$coffee->ownSugar = R::dispense('sugar',2);
		$id = R::store($coffee);
		$coffee=R::load('coffee',$id);
		asrt(count($coffee->ownSugar),2);
		
		
		
		$cocoa = R::dispense('cocoa');
		$cocoa->name = 'Fair Cocoa';
		list($taste1,$taste2) = R::dispense('taste',2);
		$taste1->name = 'sweet';
		$taste2->name = 'bitter';
		$cocoa->ownTaste = array($taste1, $taste2);
		R::store($cocoa);
		
		$cocoa->name = 'Koko';
		R::store($cocoa);
		
		if (method_exists(R::$adapter->getDatabase(),'getPDO')) {
			$pdo = R::$adapter->getDatabase()->getPDO();
			$driver = new RedBean_Driver_PDO($pdo);
			pass();
			asrt($pdo->getAttribute(PDO::ATTR_ERRMODE), PDO::ERRMODE_EXCEPTION);
			asrt($pdo->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE), PDO::FETCH_ASSOC);
			asrt(strval($driver->GetCell('select 123')),'123');
		}
		
		$a = new RedBean_Exception_SQL;
		$a->setSqlState('test');
		$b = strval($a);
		asrt($b,'[test] - ');
		
		
		testpack('test multi delete and multi update');
		R::nuke();
		$beans = R::dispenseLabels('bean',array('a','b'));
		$ids = R::storeAll($beans);
		asrt((int)R::count('bean'),2);
		R::trashAll(R::batch('bean',$ids));
		asrt((int)R::count('bean'),0);
	}

}



