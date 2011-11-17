<?php

class RedUNIT_Mysql_Parambind extends RedUNIT_Mysql {

	public function run() {
				
		$toolbox = R::$toolbox;
		$adapter = $toolbox->getDatabaseAdapter();
		$writer  = $toolbox->getWriter();
		$redbean = $toolbox->getRedBean();
		$pdo = $adapter->getDatabase();
		
		R::$adapter->getDatabase()->flagUseStringOnlyBinding = TRUE;
		try{R::getAll("select * from job limit ? ", array(1)); fail(); }catch(Exception $e){ pass(); }
		try{R::getAll("select * from job limit :l ", array(":l"=>1)); fail(); }catch(Exception $e){ pass(); }
		try{R::exec("select * from job limit ? ", array(1)); fail(); }catch(Exception $e){ pass(); }
		try{R::exec("select * from job limit :l ", array(":l"=>1)); fail(); }catch(Exception $e){ pass(); }
		R::$adapter->getDatabase()->flagUseStringOnlyBinding = FALSE;
		try{R::getAll("select * from job limit ? ", array(1)); pass(); }catch(Exception $e){ print_r($e); fail(); }
		try{R::getAll("select * from job limit :l ", array(":l"=>1)); pass(); }catch(Exception $e){ fail(); }
		try{R::exec("select * from job limit ? ", array(1)); pass(); }catch(Exception $e){ fail(); }
		try{R::exec("select * from job limit :l ", array(":l"=>1)); pass(); }catch(Exception $e){ fail(); }
		
		testpack("Test findOrDispense");
		$person = R::findOrDispense("person", " job = ? ", array("developer"));
		asrt((count($person)>0), true);
		$person = R::findOrDispense("person", " job = ? ", array("musician"));
		asrt((count($person)>0), true);
		$musician = array_pop($person);
		asrt(intval($musician->id),0);
		
		try {
			$adapter->exec("an invalid query");
			fail();
		}catch(RedBean_Exception_SQL $e ) {
			pass();
		}
		asrt( (int) $adapter->getCell("SELECT 123") ,123);
		asrt( (int) $adapter->getCell("SELECT ?",array("987")) ,987);
		asrt( (int) $adapter->getCell("SELECT ?+?",array("987","2")) ,989);
		asrt( (int) $adapter->getCell("SELECT :numberOne+:numberTwo",array(
				  ":numberOne"=>42,":numberTwo"=>50)) ,92);
		$pair = $adapter->getAssoc("SELECT 'thekey','thevalue' ");
		asrt(is_array($pair),true);
		asrt(count($pair),1);
		asrt(isset($pair["thekey"]),true);
		asrt($pair["thekey"],"thevalue");
					
	}

}