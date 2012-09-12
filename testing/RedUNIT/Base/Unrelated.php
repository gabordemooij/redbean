<?php
/**
 * RedUNIT_Base_Unrelated
 * 
 * @file 			RedUNIT/Base/Unrelated.php
 * @description		Tests finding of unrelated beans.
 * 					This class is part of the RedUNIT test suite for RedBeanPHP.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Base_Unrelated extends RedUNIT_Base {

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
		$painter = R::dispense('person');
		$painter->job = 'painter';
		$accountant = R::dispense('person');
		$accountant->job = 'accountant';
		$developer = R::dispense('person');
		$developer->job = 'developer';
		$salesman = R::dispense('person');
		$salesman->job = 'salesman';
		R::associate($painter, $accountant);
		R::associate($salesman, $accountant);
		R::associate($developer, $accountant);
		R::associate($salesman, $developer);
		asrt( getList( R::unrelated($salesman,"person"),"job" ), "painter,salesman" ) ;
		asrt( getList( R::unrelated($accountant,"person"),"job" ), "accountant" ) ;
		asrt( getList( R::unrelated($painter,"person"),"job" ), "developer,painter,salesman" ) ;
		R::associate($accountant, $accountant);
		R::associate($salesman, $salesman);
		R::associate($developer, $developer);
		R::associate($painter, $painter);
		asrt( getList( R::unrelated($accountant,"person"),"job" ), "" ) ;
		asrt( getList( R::unrelated($painter,"person"),"job" ), "developer,salesman" ) ;
		asrt( getList( R::unrelated($salesman,"person"),"job" ), "painter" ) ;
		asrt( getList( R::unrelated($developer,"person"),"job" ), "painter" ) ;
		$manufacturer = R::dispense('manufacturer');
		$manufacturer->name = 'Ford';
		R::store($manufacturer);
		$car_a = R::dispense('car');
		$car_a->model = 'Taurus';
		R::store($car_a);
		$car_b = R::dispense('car');
		$car_b->model = 'Focus';
		R::store($car_b);
		R::associate($car_a, $manufacturer);
		R::unrelated($car_a, 'manufacturer'); // No error
		asrt(count(R::unrelated($car_b, 'manufacturer')),1); // Error
		pass();
	
	}

}