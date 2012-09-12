<?php
/**
 * RedUNIT_Sqlite_Parambind 
 * 
 * @file 			RedUNIT/Sqlite/Parambind.php
 * @description		Tests PDO parameter binding.
 * 					This class is part of the RedUNIT test suite for RedBeanPHP.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Sqlite_Parambind extends RedUNIT_Sqlite {

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
		
		testpack('Test whether we can properly bind and receive NULL values');
		asrt( $adapter->getCell('SELECT :nil ',array(':nil'=>'null')), 'null' );
		asrt( $adapter->getCell('SELECT :nil ',array(':nil'=>null)), null );
		asrt( $adapter->getCell('SELECT ? ',array('null')), 'null' );
		asrt( $adapter->getCell('SELECT ? ',array(null)), null );
		
	}

}