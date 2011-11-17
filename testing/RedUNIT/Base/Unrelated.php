<?php

class RedUNIT_Base_Unrelated extends RedUNIT_Base {

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
				
	
	
	}

}