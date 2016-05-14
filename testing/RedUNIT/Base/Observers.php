<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;
use RedBeanPHP\Adapter\DBAdapter as DBAdapter;
use RedBeanPHP\QueryWriter as QueryWriter;
use RedBeanPHP\OODB as OODB;

/**
 * Observers
 *
 * Tests the basic observer pattern used in RedBeanPHP.
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
class Observers extends Base
{
	/**
	 * Test RedBeanPHP observers.
	 *
	 * @return void
	 */
	public function testObserverMechanism()
	{
		$toolbox = R::getToolBox();
		$adapter = $toolbox->getDatabaseAdapter();
		$writer  = $toolbox->getWriter();
		$redbean = $toolbox->getRedBean();
		asrt( ( $adapter instanceof DBAdapter ), TRUE );
		asrt( ( $writer instanceof QueryWriter ), TRUE );
		asrt( ( $redbean instanceof OODB ), TRUE );
		$observable = new \ObservableMock();
		$observer   = new \ObserverMock();
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
