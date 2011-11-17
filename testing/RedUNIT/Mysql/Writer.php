<?php

class RedUNIT_Mysql_Writer extends RedUNIT_Mysql {

	public function run() {
		
		$toolbox = R::$toolbox;
		$adapter = $toolbox->getDatabaseAdapter();
		$writer  = $toolbox->getWriter();
		$redbean = $toolbox->getRedBean();
		$pdo = $adapter->getDatabase();
		$a = new RedBean_AssociationManager( $toolbox );
		
		
		$adapter->exec("DROP TABLE IF EXISTS testtable");
		asrt(in_array("testtable",$adapter->getCol("show tables")),false);
		$writer->createTable("testtable");
		asrt(in_array("testtable",$adapter->getCol("show tables")),true);
		asrt(count(array_diff($writer->getTables(),$adapter->getCol("show tables"))),0);
		asrt(count(array_keys($writer->getColumns("testtable"))),1);
		asrt(in_array("id",array_keys($writer->getColumns("testtable"))),true);
		asrt(in_array("c1",array_keys($writer->getColumns("testtable"))),false);
		$writer->addColumn("testtable", "c1", 1);
		asrt(count(array_keys($writer->getColumns("testtable"))),2);
		asrt(in_array("c1",array_keys($writer->getColumns("testtable"))),true);
		foreach($writer->sqltype_typeno as $key=>$type) {
			asrt($writer->code($key),$type);
		}
		asrt($writer->code("unknown"),99);
		asrt($writer->scanType(false),0);
		asrt($writer->scanType(NULL),0);
		asrt($writer->scanType(2),1);
		asrt($writer->scanType(255),1);
		asrt($writer->scanType(256),2);
		asrt($writer->scanType(-1),3);
		asrt($writer->scanType(1.5),3);
		asrt($writer->scanType(INF),4);
		asrt($writer->scanType("abc"),4);
		asrt($writer->scanType(str_repeat("lorem ipsum",100)),5);
		$writer->widenColumn("testtable", "c1", 2);
		$cols=$writer->getColumns("testtable");
		asrt($writer->code($cols["c1"]),2);
		$writer->widenColumn("testtable", "c1", 3);
		$cols=$writer->getColumns("testtable");
		asrt($writer->code($cols["c1"]),3);
		$writer->widenColumn("testtable", "c1", 4);
		$cols=$writer->getColumns("testtable");
		asrt($writer->code($cols["c1"]),4);
		$writer->widenColumn("testtable", "c1", 5);
		$cols=$writer->getColumns("testtable");
		asrt($writer->code($cols["c1"]),5);
		//$id = $writer->insertRecord("testtable", array("c1"), array(array("lorem ipsum")));
		$id = $writer->updateRecord("testtable", array(array("property"=>"c1","value"=>"lorem ipsum")));
		$row = $writer->selectRecord("testtable", array("id"=>array($id)));
		asrt($row[0]["c1"],"lorem ipsum");
		$writer->updateRecord("testtable", array(array("property"=>"c1","value"=>"ipsum lorem")), $id);
		$row = $writer->selectRecord("testtable", array("id"=>array($id)));
		asrt($row[0]["c1"],"ipsum lorem");
		$writer->selectRecord("testtable", array("id"=>array($id)),null,true);
		$row = $writer->selectRecord("testtable", array("id"=>array($id)));
		asrt(empty($row),true);
		//$pdo->setDebugMode(1);
		$writer->addColumn("testtable", "c2", 2);
		try {
			$writer->addUniqueIndex("testtable", array("c1","c2"));
			fail(); //should fail, no content length blob
		}catch(RedBean_Exception_SQL $e) {
			pass();
		}
		$writer->addColumn("testtable", "c3", 2);
		try {
			$writer->addUniqueIndex("testtable", array("c2","c3"));
			pass(); //should fail, no content length blob
		}catch(RedBean_Exception_SQL $e) {
			fail();
		}
		
		$a = $adapter->get("show index from testtable");
		
		asrt(count($a),3);
		asrt($a[1]["Key_name"],"UQ_64b283449b9c396053fe1724b4c685a80fd1a54d");
		asrt($a[2]["Key_name"],"UQ_64b283449b9c396053fe1724b4c685a80fd1a54d");
		
		
		
		//Zero issue (false should be stored as 0 not as '')
		testpack("Zero issue");
		$pdo->Execute("DROP TABLE IF EXISTS `zero`");
		$bean = $redbean->dispense("zero");
		$bean->zero = false;
		$bean->title = "bla";
		$redbean->store($bean);
		asrt( count($redbean->find("zero",array()," zero = 0 ")), 1 );
		
		//Section D Security Tests
		R::store(R::dispense('hack'));
		testpack("Test RedBean Security - bean interface ");
		asrt(in_array("hack",$adapter->getCol("show tables")),true);
		$bean = $redbean->load("page","13; drop table hack");
		asrt(in_array("hack",$adapter->getCol("show tables")),true);
		try {
			$bean = $redbean->load("page where 1; drop table hack",1);
		}catch(Exception $e) {
		
		}
		asrt(in_array("hack",$adapter->getCol("show tables")),true);
		$bean = $redbean->dispense("page");
		$evil = "; drop table hack";
		$bean->id = $evil;
		try {
			$redbean->store($bean);
		}catch(Exception $e) {
		
		}
		asrt(in_array("hack",$adapter->getCol("show tables")),true);
		unset($bean->id);
		$bean->name = "\"".$evil;
		try {
			$redbean->store($bean);
		}catch(Exception $e) {
		
		}
		asrt(in_array("hack",$adapter->getCol("show tables")),true);
		$bean->name = "'".$evil;
		try {
			$redbean->store($bean);
		}catch(Exception $e) {
		
		}
		asrt(in_array("hack",$adapter->getCol("show tables")),true);
		$bean->$evil = 1;
		try {
			$redbean->store($bean);
		}catch(Exception $e) {
		
		}
		asrt(in_array("hack",$adapter->getCol("show tables")),true);
		unset($bean->$evil);
		$bean->id = 1;
		$bean->name = "\"".$evil;
		try {
			$redbean->store($bean);
		}catch(Exception $e) {
		
		}
		asrt(in_array("hack",$adapter->getCol("show tables")),true);
		$bean->name = "'".$evil;
		try {
			$redbean->store($bean);
		}catch(Exception $e) {
		
		}
		asrt(in_array("hack",$adapter->getCol("show tables")),true);
		$bean->$evil = 1;
		try {
			$redbean->store($bean);
		}catch(Exception $e) {
		
		}
		asrt(in_array("hack",$adapter->getCol("show tables")),true);
		try {
			$redbean->trash($bean);
		}catch(Exception $e) {
		
		}
		asrt(in_array("hack",$adapter->getCol("show tables")),true);
		try {
			$redbean->find("::",array(),"");
		}catch(Exception $e) {
			pass();
		}
		
		
		
		$adapter->exec("drop table if exists sometable");
		testpack("Test RedBean Security - query writer");
		try {
			$writer->createTable("sometable` ( `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT , PRIMARY KEY ( `id` ) ) ENGINE = InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ; drop table hack; --");
		}catch(Exception $e) {
		
		}
		asrt(in_array("hack",$adapter->getCol("show tables")),true);
		
		
				
		testpack("Test ANSI92 issue in clearrelations");
		
		$pdo->Execute("DROP TABLE IF EXISTS book_group");
		$pdo->Execute("DROP TABLE IF EXISTS author_book");
		
		$pdo->Execute("DROP TABLE IF EXISTS book");
		$pdo->Execute("DROP TABLE IF EXISTS author");
		$redbean = $toolbox->getRedBean();
		$a = new RedBean_AssociationManager( $toolbox );
		$book = $redbean->dispense("book");
		$author1 = $redbean->dispense("author");
		$author2 = $redbean->dispense("author");
		$book->title = "My First Post";
		$author1->name="Derek";
		$author2->name="Whoever";
		set1toNAssoc($a,$book,$author1);
		set1toNAssoc($a,$book, $author2);
		pass();
		$pdo->Execute("DROP TABLE IF EXISTS book_group");
		$pdo->Execute("DROP TABLE IF EXISTS book_author");
		$pdo->Execute("DROP TABLE IF EXISTS author_book");
		
		$pdo->Execute("DROP TABLE IF EXISTS book");
		$pdo->Execute("DROP TABLE IF EXISTS author");
		$redbean = $toolbox->getRedBean();
		$a = new RedBean_AssociationManager( $toolbox );
		$book = $redbean->dispense("book");
		$author1 = $redbean->dispense("author");
		$author2 = $redbean->dispense("author");
		$book->title = "My First Post";
		$author1->name="Derek";
		$author2->name="Whoever";
		$a->associate($book,$author1);
		$a->associate($book, $author2);
		pass();
		
		testpack("Test Association Issue Group keyword (Issues 9 and 10)");
		$pdo->Execute("DROP TABLE IF EXISTS `book_group`");
		$pdo->Execute("DROP TABLE IF EXISTS `group`");
		
		$group = $redbean->dispense("group");
		$group->name ="mygroup";
		$redbean->store( $group );
		try {
			$a->associate($group,$book);
			pass();
		}catch(RedBean_Exception_SQL $e) {
			fail();
		}
		//test issue SQL error 23000
		try {
			$a->associate($group,$book);
			pass();
		}catch(RedBean_Exception_SQL $e) {
			fail();
		}
		asrt((int)$adapter->getCell("select count(*) from book_group"),1); //just 1 rec!
		$pdo->Execute("DROP TABLE IF EXISTS book_group");
		$pdo->Execute("DROP TABLE IF EXISTS author_book");
		
		$pdo->Execute("DROP TABLE IF EXISTS book");
		$pdo->Execute("DROP TABLE IF EXISTS author");
		$redbean = $toolbox->getRedBean();
		$a = new RedBean_AssociationManager( $toolbox );
		$book = $redbean->dispense("book");
		$author1 = $redbean->dispense("author");
		$author2 = $redbean->dispense("author");
		$book->title = "My First Post";
		$author1->name="Derek";
		$author2->name="Whoever";
		$a->unassociate($book,$author1);
		$a->unassociate($book, $author2);
		pass();
		$redbean->trash($redbean->dispense("bla"));
		pass();
		$bean = $redbean->dispense("bla");
		$bean->name = 1;
		$bean->id = 2;
		$redbean->trash($bean);
		pass();
			
		
		
	}	
	
}