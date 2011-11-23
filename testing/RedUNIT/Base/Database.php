<?php
/**
 * RedUNIT_Base_Database 
 * @file 			RedUNIT/Base/Database.php
 * @description		Tests basic database behaviors
 * 					This class is part of the RedUNIT test suite for RedBeanPHP.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Base_Database extends RedUNIT_Base {
	
	public function run() {
	
		$toolbox = R::$toolbox;
		$adapter = $toolbox->getDatabaseAdapter();
		$writer  = $toolbox->getWriter();
		$redbean = $toolbox->getRedBean();
		$pdo = $adapter->getDatabase();
			
		$page = $redbean->dispense("page");
		try {
			$adapter->exec("an invalid query");
			fail();
		}catch(RedBean_Exception_SQL $e ) {
			pass();
		}
		asrt( (int) $adapter->getCell("SELECT 123") ,123);
		
		
		
		$page->aname = "my page";
		$id = (int) $redbean->store($page);
		asrt( (int) $page->id, 1 );
		asrt( (int) $pdo->GetCell("SELECT count(*) FROM page"), 1 );
		asrt( $pdo->GetCell("SELECT aname FROM page LIMIT 1"), "my page" );
		asrt( (int) $id, 1 );
		
		$page = $redbean->load( "page", 1 );
		asrt($page->aname, "my page");
		asrt(( (bool) $page->getMeta("type")),true);
		asrt(isset($page->id),true);
		asrt(($page->getMeta("type")),"page");
		asrt((int)$page->id,$id);
				
	}
}