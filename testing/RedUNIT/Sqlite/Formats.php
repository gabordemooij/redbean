<?php

class RedUNIT_Sqlite_Formats extends RedUNIT_Sqlite {
	
	
	public function run() {
				
		R::$writer->setBeanFormatter(new BF);
		$bean = R::dispense('page');
		$bean->rating = 1;
		R::store($bean);
		$cols = R::$writer->getColumns('page');
		asrt($cols['rating'],'INTEGER');
		
		$bean->rating = 1.4;
		R::store($bean);
		$cols = R::$writer->getColumns('page');
		asrt($cols['rating'],'NUMERIC');
		
		$bean->rating = '1999-02-02';
		R::store($bean);
		$cols = R::$writer->getColumns('page');
		asrt($cols['rating'],'NUMERIC');
		
		$bean->rating = 'reasonable';
		R::store($bean);
		$cols = R::$writer->getColumns('page');
		asrt($cols['rating'],'TEXT');
		R::$writer->setBeanFormatter(new RedBean_DefaultBeanFormatter);
				
	}
	
}