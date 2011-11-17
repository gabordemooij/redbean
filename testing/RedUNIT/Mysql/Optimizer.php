<?php

class RedUNIT_Mysql_Optimizer extends RedUNIT_Mysql {
	
	public function run() {
		
		$toolbox = R::$toolbox;
		$adapter = $toolbox->getDatabaseAdapter();
		$writer  = $toolbox->getWriter();
		$redbean = $toolbox->getRedBean();
		$pdo = $adapter->getDatabase();
		$a = new RedBean_AssociationManager( $toolbox );
	
			
		$one = $redbean->dispense("one");
		$one->col = str_repeat('a long text',100);
		$redbean->store($one);
		$optimizer = new RedBean_Plugin_Optimizer( $toolbox );
		
		//order is important!
		$optimizer->addOptimizer(new RedBean_Plugin_Optimizer_DateTime($toolbox));
		$optimizer->addOptimizer(new RedBean_Plugin_Optimizer_Shrink($toolbox));
		
		$redbean->addEventListener("update", $optimizer);
		$writer  = $toolbox->getWriter();
		$cols = $writer->getColumns("one");
		asrt($cols["col"],"text");
		$one->col = 1;
		$redbean->store($one);
		$cols = $writer->getColumns("one");
		asrt($cols["col"],"text");
		$redbean->store($one);
		//$cols = $writer->getColumns("one");
		//asrt($cols["col"],"set('1')");
		
		$one->col = str_repeat('a long text',100);
		$redbean->store($one);
		$cols = $writer->getColumns("one");
		asrt($cols["col"],"text");
		$one->col = 12;
		$redbean->store($one);
		$one->setMeta('tainted',true);
		$redbean->store($one);
		$cols = $writer->getColumns("one");
		asrt($cols["col"],"tinyint(3) unsigned");
		
		$one->col = str_repeat('a long text',100);
		$redbean->store($one);
		$cols = $writer->getColumns("one");
		asrt($cols["col"],"text");
		$one->col = 9000;
		$redbean->store($one);
		$one->setMeta('tainted',true);
		$redbean->store($one);
		$cols = $writer->getColumns("one");
		asrt($cols["col"],"int(11) unsigned");
		
		$one->col = str_repeat('a long text',100);
		$redbean->store($one);
		$cols = $writer->getColumns("one");
		asrt($cols["col"],"text");
		$one->col = 1.23;
		$redbean->store($one);
		$one->setMeta('tainted',true);
		$redbean->store($one);
		$cols = $writer->getColumns("one");
		asrt($cols["col"],"double");
		$one->col = str_repeat('a long text',100);
		$redbean->store($one);
		$cols = $writer->getColumns("one");
		asrt($cols["col"],"text");
		$one->col = "short text";
		$redbean->store($one);
		$one->setMeta('tainted',true);
		$redbean->store($one);
		$cols = $writer->getColumns("one");
		asrt($cols["col"],"varchar(255)");
		
		testpack("Test Plugins: MySQL Spec. Column");
		$special = $redbean->dispense("special");
		$v = "2009-01-01 10:00:00";
		$special->datetime = $v;
		$redbean->store($special);
		$special->setMeta('tainted',true);
		$redbean->store($special);
		//$optimizer->MySQLSpecificColumns("special", "datetime", "varchar", $v);
		$cols = $writer->getColumns("special");
		asrt($cols["datetime"],"datetime");
		//$adapter->getDatabase()->setDebugMode(1);
		for($i=0; $i<100; $i++){
		$special2 = $redbean->dispense("special");
		$special2->test = md5(rand());
		//$redbean->store($special2);
		$redbean->store($special);
		$cols = $writer->getColumns("special");
		if($cols["datetime"]!=="datetime") fail();
		}
		pass();
		$special->datetime = "convertmeback";
		$redbean->store($special);
		$special->setMeta('tainted',true);
		$redbean->store($special);
		$cols = $writer->getColumns("special");
		asrt(($cols["datetime"]!="datetime"),true);
		$special2 = $redbean->dispense("special");
		$special2->datetime = "1990-10-10 12:00:00";
		$redbean->store($special2);
		$special->setMeta('tainted',true);
		$redbean->store($special2);
		$cols = $writer->getColumns("special");
		asrt(($cols["datetime"]!="datetime"),true);
		$special->datetime = "1990-10-10 12:00:00";
		$redbean->store($special);
		$special->setMeta('tainted',true);
		$redbean->store($special);
		$cols = $writer->getColumns("special");
		asrt(($cols["datetime"]!="datetime"),false);
		
	
		$writer->setBeanFormatter(new BF);
		$one = $redbean->dispense("one");
		$one->col = 'some text';
		$redbean->store($one);
		$one->col = '2010-10-10 10:00:00';
		$redbean->store($one);
		$one->col = '2012-10-10 12:00:00';
		$redbean->store($one);
		$cols = $writer->getColumns("one");
		asrt($cols["col"],"datetime");
		$one->col2 = 'some text';
		$redbean->store($one);
		$cols = $writer->getColumns("one");
		$one->col2 = '1';
		$redbean->store($one);
		for($i=0; $i<20; $i++){
		$one->col2 = '1';
		$redbean->store($one);
		}
		$cols = $writer->getColumns("one");
		asrt($cols['col2'],"set('1')");
		
	
			
	
	}
	
}