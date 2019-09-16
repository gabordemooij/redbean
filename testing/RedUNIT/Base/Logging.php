<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;
use RedBeanPHP\Logger as Logger;
use RedBeanPHP\Logger\RDefault as RDefault;
use RedBeanPHP\Logger\RDefault\Debug as Debug;

/**
 * Logging
 *
 * Tests the Query Logging tools that are part of RedBeanPHP.
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
	 * Test for Issue #751 (Update Logger to accept parameter typed bindings):
	 * While debugging some of our queries, we noticed
	 * the logger would often display
	 * 'Array' as value for the bindings,
	 * even when the SQL query seemed to work correctly.
	 * Debugging this, it appeared the debug logger did
	 * not support the new parameter type bindings added in 5.3.
	 * This merge request adds support for
	 * the PDO::PARAM_INT and PDO::PARAM_STR
	 * to the Debug logger, as well as a visible support
	 * for the RPDO flagUseStringOnlyBinding flag.
	 *
	 * @return void
	 */
	public function testIssue751()
	{
		R::nuke();
		$debugger = new Debug;
		$database = R::getDatabaseAdapter()->getDatabase();
		$database->setLogger( $debugger );
		asrt( $database->getLogger(), $debugger );
		$database->setEnableLogging( TRUE );
		$debugger->setMode( RDefault::C_LOGGER_ARRAY );
		/* debug logger with nostringonlybinding should have unquoted ints */
		R::store( R::dispense( 'book' ) );
		R::getAll( 'SELECT * FROM book WHERE id < ?', array( array( 999, \PDO::PARAM_INT ) ) );
		asrt( count( $debugger->grep('999') ), 1 );
		asrt( count( $debugger->grep('\'999\'') ), 0 );
		asrt( count( $debugger->grep('rray') ), 0 );
		$debugger->setUseStringOnlyBinding( FALSE );
		$debugger->clear();
		R::getAll( 'SELECT * FROM book WHERE id < ?', array( array( 999, \PDO::PARAM_STR ) ) );
		/* ...but quoted strings */
		asrt( count( $debugger->grep('\'999\'') ), 1 );
		asrt( count( $debugger->grep('rray') ), 0 );
		$debugger->setUseStringOnlyBinding( FALSE );
		$debugger->clear();
		/* even if PARAM INT if stringonlybinding then override */
		$debugger->setUseStringOnlyBinding( TRUE );
		R::getAll( 'SELECT * FROM book WHERE id < ?', array( array( 999, \PDO::PARAM_INT ) ) );
		asrt( count( $debugger->grep('\'999\'') ), 1 );
		asrt( count( $debugger->grep('rray') ), 0 );
		/* if no type and stringonlybinding always quote */
		$debugger->clear();
		R::getAll( 'SELECT * FROM book WHERE id < ?', array( 999 ) );
		asrt( count( $debugger->grep('\'999\'') ), 1 );
		asrt( count( $debugger->grep('rray') ), 0 );
		/* a more closer inspection */
		/* log implicit INT param without StringOnly */
		$debugger->clear();
		$debugger->setUseStringOnlyBinding( FALSE );
		$debugger->log(' Hello ? ', array( 123 ) );
		asrt( count( $debugger->grep('123') ), 1 );
		asrt( count( $debugger->grep('\'123\'') ), 0 );
		/* log implicit STR param without StringOnly */
		$debugger->clear();
		$debugger->setUseStringOnlyBinding( FALSE );
		$debugger->log(' Hello ? ', array( 'abc' ) );
		asrt( count( $debugger->grep('\'abc\'') ), 1 );
		/* log NULL param without StringOnly */
		$debugger->clear();
		$debugger->setUseStringOnlyBinding( FALSE );
		$debugger->log(' Hello ? ', array( NULL ) );
		asrt( count( $debugger->grep('NULL') ), 1 );
		asrt( count( $debugger->grep('\'NULL\'') ), 0 );
		/* log explicit INT param without StringOnly */
		$debugger->clear();
		$debugger->setUseStringOnlyBinding( FALSE );
		$debugger->log(' Hello ? ', array( 123, \PDO::PARAM_INT ) );
		asrt( count( $debugger->grep('123') ), 1 );
		asrt( count( $debugger->grep('\'123\'') ), 0 );
		/* log explicit STR param without StringOnly */
		$debugger->clear();
		$debugger->setUseStringOnlyBinding( FALSE );
		$debugger->log(' Hello ? ', array( 'abc', \PDO::PARAM_STR ) );
		asrt( count( $debugger->grep('\'abc\'') ), 1 );
		/* log NULL with explicit param type without StringOnly */
		$debugger->clear();
		$debugger->setUseStringOnlyBinding( FALSE );
		$debugger->log(' Hello ? ', array( NULL, \PDO::PARAM_STR ) );
		asrt( count( $debugger->grep('NULL') ), 1 );
		asrt( count( $debugger->grep('\'NULL\'') ), 0 );
		$debugger->clear();
		$debugger->setUseStringOnlyBinding( FALSE );
		$debugger->log(' Hello ? ', array( NULL, \PDO::PARAM_INT ) );
		asrt( count( $debugger->grep('NULL') ), 1 );
		asrt( count( $debugger->grep('\'NULL\'') ), 0 );
		/* log implicit INT param with StringOnly */
		$debugger->clear();
		$debugger->setUseStringOnlyBinding( TRUE );
		$debugger->log(' Hello ? ', array( 123 ) );
		asrt( count( $debugger->grep('\'123\'') ), 1 );
		/* log implicit STR param with StringOnly */
		$debugger->clear();
		$debugger->setUseStringOnlyBinding( TRUE );
		$debugger->log(' Hello ? ', array( 'abc' ) );
		asrt( count( $debugger->grep('\'abc\'') ), 1 );
		/* log NULL param with StringOnly */
		$debugger->clear();
		$debugger->setUseStringOnlyBinding( TRUE );
		$debugger->log(' Hello ? ', array( NULL ) );
		asrt( count( $debugger->grep('\'NULL\'') ), 1 );
		/* log explicit INT param with StringOnly */
		$debugger->clear();
		$debugger->setUseStringOnlyBinding( TRUE );
		$debugger->log(' Hello ? ', array( 123, \PDO::PARAM_INT ) );
		asrt( count( $debugger->grep('\'123\'') ), 1 );
		/* log explicit STR param with StringOnly */
		$debugger->clear();
		$debugger->setUseStringOnlyBinding( TRUE );
		$debugger->log(' Hello ? ', array( 'abc', \PDO::PARAM_STR ) );
		asrt( count( $debugger->grep('\'abc\'') ), 1 );
		/* log NULL with explicit param type with StringOnly - remains just NULL */
		$debugger->clear();
		$debugger->setUseStringOnlyBinding( FALSE );
		$debugger->log(' Hello ? ', array( NULL, \PDO::PARAM_STR ) );
		asrt( count( $debugger->grep('NULL') ), 1 );
		asrt( count( $debugger->grep('\'NULL\'') ), 0 );
		$debugger->clear();
		$debugger->setUseStringOnlyBinding( FALSE );
		$debugger->log(' Hello ? ', array( NULL, \PDO::PARAM_INT ) );
		asrt( count( $debugger->grep('NULL') ), 1 );
		asrt( count( $debugger->grep('\'NULL\'') ), 0 );
		$debugger->setUseStringOnlyBinding( FALSE );
		/* Does stringonly mode switch along with Database mode ? */
		$database->setUseStringOnlyBinding( TRUE );
		$debugger->clear();
		$debugger->log(' Hello ? ', array( 123, \PDO::PARAM_INT ) );
		asrt( count( $debugger->grep('\'123\'') ), 1 );
		$database->setUseStringOnlyBinding( FALSE );
		$debugger->clear();
		$debugger->log(' Hello ? ', array( 123, \PDO::PARAM_INT ) );
		asrt( count( $debugger->grep('\'123\'') ), 0 );
		asrt( count( $debugger->grep('123') ), 1 );
		$database->setUseStringOnlyBinding( TRUE );
		$debugger->clear();
		$debugger->log(' Hello ? ', array( 123, \PDO::PARAM_INT ) );
		asrt( count( $debugger->grep('\'123\'') ), 1 );
		$database->setUseStringOnlyBinding( FALSE );
		$debugger->setUseStringOnlyBinding( FALSE );
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
