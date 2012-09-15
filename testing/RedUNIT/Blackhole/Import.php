<?php
/**
 * RedUNIT_Blackhole_Import
 * 
 * @file 			RedUNIT/Blackhole/Import.php
 * @description		Tests basic bean importing features.
 * 					This class is part of the RedUNIT test suite for RedBeanPHP.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */

class RedUNIT_Blackhole_Import extends RedUNIT_Blackhole {
	/**
	 * Begin testing.
	 * This method runs the actual test pack.
	 * 
	 * @return void
	 */
	public function run() {
		testpack('Test basic import() feature.');
		$bean = new RedBean_OODBBean;
		$bean->import(array("a"=>1,"b"=>2));
		asrt($bean->a, 1);
		asrt($bean->b, 2);
		$bean->import(array("a"=>3,"b"=>4),"a,b");
		asrt($bean->a, 3);
		asrt($bean->b, 4);
		$bean->import(array("a"=>5,"b"=>6)," a , b ");
		asrt($bean->a, 5);
		asrt($bean->b, 6);
		$bean->import(array("a"=>1,"b"=>2));
		testpack('Test inject() feature.');
		$coffee = R::dispense('coffee');
		$coffee->id = 2;
		$coffee->liquid = 'black';
		$cup = R::dispense('cup');
		$cup->color = 'green';
		$cup->inject($coffee); //pour coffee in cup
		asrt($cup->color,'green'); //do we still have our own property?
		asrt($cup->liquid,'black'); //did we pour the liquid in the cup?
		asrt($cup->id, 0);//id should not be transferred
	}

}