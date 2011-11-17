<?php

class RedUNIT_Base_Observers extends RedUNIT_Base {

	public function run() {
		
		$toolbox = R::$toolbox;
		$adapter = $toolbox->getDatabaseAdapter();
		$writer  = $toolbox->getWriter();
		$redbean = $toolbox->getRedBean();
		
		asrt(($adapter instanceof RedBean_Adapter_DBAdapter),true);
		asrt(($writer instanceof RedBean_QueryWriter),true);
		asrt(($redbean instanceof RedBean_OODB),true);

		$observable = new ObservableMock();
		$observer = new ObserverMock();
		$observable->addEventListener("event1",$observer);
		$observable->addEventListener("event3",$observer);
		$observable->test("event1", "testsignal1");
		asrt($observer->event,"event1");
		asrt($observer->info,"testsignal1");
		$observable->test("event2", "testsignal2");
		asrt($observer->event,"event1");
		asrt($observer->info,"testsignal1");
		$observable->test("event3", "testsignal3");
		asrt($observer->event,"event3");
		asrt($observer->info,"testsignal3");
		
		
		
	}	
	
}