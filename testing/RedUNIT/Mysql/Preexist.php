<?php

class RedUNIT_Mysql_Preexist extends RedUNIT_Mysql {

	public function run() {
	
		$toolbox = R::$toolbox;
		$adapter = $toolbox->getDatabaseAdapter();
		$writer  = $toolbox->getWriter();
		$redbean = $toolbox->getRedBean();
		$pdo = $adapter->getDatabase();
		$a = new RedBean_AssociationManager( $toolbox );
		
		
		$page = $redbean->dispense("page");
		$page->name = "John's page";
		$idpage = $redbean->store($page);
		$page2 = $redbean->dispense("page");
		$page2->name = "John's second page";
		$idpage2 = $redbean->store($page2);
		$a->associate($page, $page2);
	
		$adapter->exec("ALTER TABLE ".$writer->safeColumn('page')." 
		CHANGE ".$writer->safeColumn('name')." ".$writer->safeColumn('name')." 
		VARCHAR( 254 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL ");
		$page = $redbean->dispense("page");
		$page->name = "Just Another Page In a Table";
		$cols = $writer->getColumns("page");
		asrt($cols["name"],"varchar(254)");
		//$pdo->SethMode(1);
		$redbean->store( $page );
		pass(); //no crash?
		$cols = $writer->getColumns("page");
		asrt($cols["name"],"varchar(254)"); //must still be same
	
	}

}
