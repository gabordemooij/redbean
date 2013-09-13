<?php
/**
 * RedUNIT_Base_Observers
 *
 * @file    RedUNIT/Base/Observers.php
 * @desc    Tests the observer pattern in RedBeanPHP.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Base_Observers extends RedUNIT_Base
{
	/**
	 * Test RedBeanPHP observers.
	 * 
	 * @return void
	 */
	public function testObserverMechanism()
	{
		$toolbox = R::$toolbox;
		$adapter = $toolbox->getDatabaseAdapter();
		$writer  = $toolbox->getWriter();
		$redbean = $toolbox->getRedBean();

		asrt( ( $adapter instanceof RedBean_Adapter_DBAdapter ), TRUE );
		asrt( ( $writer instanceof RedBean_QueryWriter ), TRUE );
		asrt( ( $redbean instanceof RedBean_OODB ), TRUE );

		$observable = new ObservableMock();
		$observer   = new ObserverMock();

		$observable->addEventListener( "event1", $observer );
		$observable->addEventListener( "event3", $observer );

		$observable->test( "event1", "testsignal1" );

		asrt( $observer->event, "event1" );
		asrt( $observer->info, "testsignal1" );

		$observable->test( "event2", "testsignal2" );

		asrt( $observer->event, "event1" );
		asrt( $observer->info, "testsignal1" );

		$observable->test( "event3", "testsignal3" );

		asrt( $observer->event, "event3" );
		asrt( $observer->info, "testsignal3" );
	}
}
