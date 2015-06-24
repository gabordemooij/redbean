<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;
use RedBeanPHP\Logger as Logger;
use RedBeanPHP\Logger\RDefault as RDefault;

/**
 * Logging
 *
 * @file    RedUNIT/Base/Logging.php
 * @desc    Tests Logging facilities.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Logging extends Base
{
	/**
	 * Test basic logging functionality.
	 *
	 * @return void
	 */
	public function testBasicLogging()
	{
		R::nuke();
		R::startLogging();
		R::store( R::dispense( 'book' ) );
		$logs = R::getLogs();
		$count = count( $logs );
		asrt( ( count( $logs ) > 0 ), TRUE );
		asrt( ( R::getLogger() instanceof Logger ), TRUE );
		R::stopLogging();
		R::store( R::dispense( 'book' ) );
		$logs = R::getLogs();
		asrt( ( count( $logs ) === 0 ), TRUE );
	}

	/**
	 * Can we manually set a logger and enable logging?
	 *
	 * @return void
	 */
	public function testCanSetLogger()
	{
			R::nuke();
			R::store( R::dispense( 'bean' ) );
			$logger = new RDefault;
			$logger->setMode( RDefault::C_LOGGER_ARRAY );
			$database = R::getDatabaseAdapter()->getDatabase();
			$database->setLogger( $logger );
			asrt( $database->getLogger(), $logger );
			$database->setEnableLogging( FALSE );
			$logs = $logger->getLogs();
			asrt( is_array( $logs ), TRUE );
			asrt( count( $logs ), 0 );
			$database->setEnableLogging( TRUE );
			$logs = $logger->getLogs();
			asrt( is_array( $logs ), TRUE );
			asrt( count( $logs ), 0 );
			R::findOne( 'bean' ); //writes 3 log entries
			$logs = $logger->getLogs();
			asrt( is_array( $logs ), TRUE );
			asrt( count( $logs ), 3 );
	}

	/**
	 * Test query counter.
	 *
	 * @return void
	 */
	public function testQueryCount()
	{
		R::nuke();
		R::store( R::dispense( 'bean' ) );
		R::resetQueryCount();
		asrt( R::getQueryCount(), 0 );
		R::findOne( 'bean' );
		asrt( R::getQueryCount(), 1 );
		R::resetQueryCount();
		asrt( R::getQueryCount(), 0 );
		R::findOne( 'bean' );
		R::findOne( 'bean' );
		R::findOne( 'bean' );
		asrt( R::getQueryCount(), 0 );
		R::store( R::dispense( 'bean2' ) );
		R::resetQueryCount();
		R::findOne( 'bean' );
		R::findOne( 'bean2' );
		asrt( R::getQueryCount(), 2 );
		R::resetQueryCount();
		R::findOne( 'bean', ' id < 100' );
		R::findOne( 'bean', ' id < 101' );
		R::findOne( 'bean', ' id < 102' );
		R::findOne( 'bean', ' id < 103' );
		asrt( R::getQueryCount(), 4 );
		R::findOne( 'bean', ' id < 100' );
		R::findOne( 'bean', ' id < 101' );
		R::findOne( 'bean', ' id < 102' );
		R::findOne( 'bean', ' id < 103' );
		asrt( R::getQueryCount(), 4 );
		R::findOne( 'bean', ' id < 104' );
		asrt( R::getQueryCount(), 5 );
	}
}
