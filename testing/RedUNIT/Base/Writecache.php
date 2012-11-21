<?php
/**
 * RedUNIT_Base_Writecace
 *  
 * @file 			RedUNIT/Base/Writecache.php
 * @description		Tests the Query Writer cache implemented in the
 * 					abstract RedBean_QueryWriter_AQueryWriter class.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Base_Writecache extends RedUNIT_Base {
	
	/**
	 * Begin testing.
	 * This method runs the actual test pack.
	 * 
	 * @return void
	 */
	public function run(){
		R::nuke();
		$logger = RedBean_Plugin_QueryLogger::getInstanceAndAttach(R::$adapter);
		$book = R::dispense('book')->setAttr('title','ABC');
		$id = R::store($book);
		$logger->clear();
		$book = R::load('book',$id);
		$book = R::load('book',$id);
		asrt(count($logger->grep('SELECT')),2);
		R::$writer->setUseCache(true);
		$logger->clear();
		$book = R::load('book',$id);
		$book = R::load('book',$id);
		asrt(count($logger->grep('SELECT')),1);
		R::$writer->setUseCache(false);
	}
}