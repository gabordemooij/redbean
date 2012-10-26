<?php
/**
 * RedUNIT_Postgres_Writer
 * 
 * @file 			RedUNIT/Postgres/Writer.php
 * @description		A collection of writer specific tests.
 * 					This class is part of the RedUNIT test suite for RedBeanPHP.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Postgres_Writer extends RedUNIT_Postgres {

	/**
	 * Begin testing.
	 * This method runs the actual test pack.
	 * 
	 * @return void
	 */
	public function run() {
		
		$toolbox = R::$toolbox;
		$adapter = $toolbox->getDatabaseAdapter();
		$writer  = $toolbox->getWriter();
		$redbean = $toolbox->getRedBean();
		$pdo = $adapter->getDatabase();
		$a = new RedBean_AssociationManager( $toolbox );
		
		
		$adapter->exec("DROP TABLE IF EXISTS testtable");
		asrt(in_array("testtable",$writer->getTables()),false);
		$writer->createTable("testtable");
		asrt(in_array("testtable",$writer->getTables()),true);
		asrt(count(array_keys($writer->getColumns("testtable"))),1);
		asrt(in_array("id",array_keys($writer->getColumns("testtable"))),true);
		asrt(in_array("c1",array_keys($writer->getColumns("testtable"))),false);
		$writer->addColumn("testtable", "c1", 1);
		asrt(count(array_keys($writer->getColumns("testtable"))),2);
		asrt(in_array("c1",array_keys($writer->getColumns("testtable"))),true);
		foreach($writer->sqltype_typeno as $key=>$type) {
			if ($type < 100) {
				asrt($writer->code($key),$type);
			}
			else {
				asrt($writer->code($key),99);
			}
		}
		asrt($writer->code("unknown"),99);
		asrt($writer->scanType(false),3);
		asrt($writer->scanType(NULL),0);
		asrt($writer->scanType(2),0);
		asrt($writer->scanType(255),0);
		asrt($writer->scanType(256),0);
		asrt($writer->scanType(-1),0);
		asrt($writer->scanType(1.5),1);
		asrt($writer->scanType(INF),1);
		asrt($writer->scanType("abc"),3);
		asrt($writer->scanType("2001-10-10",true),RedBean_QueryWriter_MySQL::C_DATATYPE_SPECIAL_DATE);
		asrt($writer->scanType("2001-10-10 10:00:00",true),RedBean_QueryWriter_MySQL::C_DATATYPE_SPECIAL_DATETIME);
		asrt($writer->scanType("2001-10-10 10:00:00"),3);
		asrt($writer->scanType("2001-10-10"),3);
		asrt($writer->scanType(str_repeat("lorem ipsum",100)),3);
		$writer->widenColumn("testtable", "c1", 3);
		$cols=$writer->getColumns("testtable");
		asrt($writer->code($cols["c1"]),3);
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
		
		//Zero issue (false should be stored as 0 not as '')
		testpack("Zero issue");
		R::nuke();
		$bean = $redbean->dispense("zero");
		$bean->zero = false;
		$bean->title = "bla";
		$redbean->store($bean);
		asrt( count($redbean->find("zero",array()," zero = 0 ")), 1 );
		
		
				
		testpack("Test ANSI92 issue in clearrelations");
		
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
		R::nuke();
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
		R::nuke();
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
		R::nuke();
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
		
		testpack('Special data types');
	
		
		R::nuke();
		$bean = R::dispense('bean');
		$bean->date = 'someday';
		R::store($bean);
		$cols = R::getColumns('bean');
		
		asrt($cols['date'],'text');
		$bean = R::dispense('bean');
		$bean->date = '2011-10-10';
		R::store($bean);
		$cols = R::getColumns('bean');
		asrt($cols['date'],'text');

        R::nuke();
		$bean = R::dispense('bean');
		$bean->date = '2011-10-10';
		R::store($bean);
		$cols = R::getColumns('bean');
		asrt($cols['date'],'date');
		
		R::nuke();
		$bean = R::dispense('bean');
		$bean->date = '2011-10-10 10:00:00';
		R::store($bean);
		$cols = R::getColumns('bean');
		asrt($cols['date'],'timestamp without time zone');
        }	
	
}