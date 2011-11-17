<?php

class RedUNIT_Mysql_Double extends RedUNIT_Mysql {

	public function run() {
		
		$toolbox = R::$toolbox;
		$adapter = $toolbox->getDatabaseAdapter();
		$writer  = $toolbox->getWriter();
		$redbean = $toolbox->getRedBean();
		$pdo = $adapter->getDatabase();
			
		$largeDouble = 999999888889999922211111; //8.88889999922211e+17;
		$page = $redbean->dispense("page");
		$page->weight = $largeDouble;
		$id = $redbean->store($page);
		$cols = $writer->getColumns("page");
		asrt($cols["weight"],"double");
		$page = $redbean->load("page", $id);
		$page->name = "dont change the numbers!";
		$redbean->store($page);
		$page = $redbean->load("page", $id);
		$cols = $writer->getColumns("page");
		asrt($cols["weight"],"double");
		
	}	
	
}