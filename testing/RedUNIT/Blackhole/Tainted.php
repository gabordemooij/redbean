<?php

class RedUNIT_Blackhole_Tainted extends RedUNIT_Blackhole {

	public function run() {
		$redbean = R::$redbean;
		$spoon = $redbean->dispense("spoon");
		asrt($spoon->getMeta("tainted"),true);
		$spoon->dirty = "yes";
		asrt($spoon->getMeta("tainted"),true);
	}
}