<?php

class RedUNIT_Mysql_Parambind extends RedUNIT_Mysql {

	public function run() {
				
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
				
	}

}