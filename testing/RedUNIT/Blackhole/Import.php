<?php

class RedUNIT_Blackhole_Import extends RedUNIT_Blackhole {

	public function run() {
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
	}

}