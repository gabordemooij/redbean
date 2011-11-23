<?php
/**
 * RedUNIT_Blackhole_Export 
 * @file 			RedUNIT/Blackhole/Export.php
 * @description		Tests basic bean exporting features.
 * 					This class is part of the RedUNIT test suite for RedBeanPHP.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Blackhole_Export extends RedUNIT_Blackhole {
	/**
	 * Begin testing.
	 * This method runs the actual test pack.
	 * 
	 * @return void
	 */
	public function run() {
		$redbean = R::$redbean;
		$bean = new RedBean_OODBBean;
		$bean->import(array("a"=>1,"b"=>2));
		$bean->setMeta("justametaproperty","hellothere");
		$arr = $bean->export();
		asrt(is_array($arr),true);
		asrt(isset($arr["a"]),true);
		asrt(isset($arr["b"]),true);
		asrt($arr["a"],1);
		asrt($arr["b"],2);
		asrt(isset($arr["__info"]),false);
		$arr = $bean->export( true );
		asrt(isset($arr["__info"]),true);
		asrt($arr["a"],1);
		asrt($arr["b"],2);
		$exportBean = $redbean->dispense("abean");
		$exportBean->setMeta("metaitem.bla",1);
		$exportedBean = $exportBean->export(true);
		asrt($exportedBean["__info"]["metaitem.bla"],1);
		asrt($exportedBean["__info"]["type"],"abean");
	}

}