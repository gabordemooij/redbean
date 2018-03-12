<?php

namespace RedUNIT\Blackhole;

use RedUNIT\Blackhole as Blackhole;
use RedBeanPHP\Facade as R;
use RedBeanPHP\OODBBean as OODBBean;
use RedBeanPHP\Logger\RDefault\Debug as Debugger;

/**
 * Debug
 *
 * Tests debugging functions and checks whether the output
 * of the debugger displays the correct information.
 *
 * @file    RedUNIT/Blackhole/Debug.php
 * @desc    Tests Debugger II.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Debug extends Blackhole
{
	/**
	 * Given a query, a set of bindings and an expected outcome,
	 * this method tests the result of the debugger.
	 *
	 * @param string  $query
	 * @param mixed   $bindings
	 * @param string  $expected
	 * @param integer $mode
	 * @param string  $expected2
	 *
	 * @return void
	 */
	private function testDebug($query, $bindings = NULL, $expected, $mode = 1, $expected2 = NULL)
	{
		$debugger = new Debugger;
		$debugger->setMode( $mode  );
		$debugger->setParamStringLength( 20 );
		ob_start();
		if (!is_null($bindings)) {
			$debugger->log($query, $bindings);
		} else {
			$debugger->log($query);
		}
		$out = ob_get_contents();
		ob_clean();
		ob_end_flush();
		$logs = $debugger->getLogs();
		$log = reset($logs);
		$log = str_replace( "\e[32m", '', $log );
		$log = str_replace( "\e[39m", '', $log );
		asrt($log, $expected);
		if (!$mode) {
			asrt($out, $expected2);
		}
		$debugger->clear();
	}

	/**
	 * Tests the bean dump function used to inspect
	 * the contents of a bean.
	 *
	 * @return void
	 */
	public function testDump()
	{
		$beans = R::dispense( 'bean', 2 );
		$beans[0]->name = 'hello';
		$beans[1]->name = 'world';
		$array = R::dump($beans);
		asrt( is_array( $array ), TRUE );
		foreach( $array as $item ) {
			asrt( is_string( $item ), TRUE );
		}
		$beans[1]->name = 'world, and a very long string that should be shortened';
		$array = R::dump($beans);
		asrt( is_array( $array ), TRUE );
		asrt( strpos( $array[1], '...' ), 35 );
		//just to get 100% test cov, we dont need to test this
		dmp( $beans );
		pass();
		//test wrong input
		asrt( is_array( R::dump( NULL ) ), TRUE );
		asrt( count( R::dump( NULL ) ), 0 );
		asrt( is_array( R::dump( '' ) ), TRUE );
		asrt( count( R::dump( '' ) ), 0 );
		asrt( is_array( R::dump( 1 ) ), TRUE );
		asrt( count( R::dump( 1 ) ), 0 );
		asrt( is_array( R::dump( TRUE ) ), TRUE );
		asrt( count( R::dump( FALSE ) ), 0 );
	}

	/**
	 * Tests debugging with parameters.
	 *
	 * @return void
	 */
	public function testDebugger2()
	{
		testpack( 'Test debugger with params.' );
		$this->testDebug('SELECT * FROM table', NULL, 'SELECT * FROM table');
		$this->testDebug('SELECT * FROM book WHERE title = ?', array('my book'), 'SELECT * FROM book WHERE title = \'my book\'');
		$this->testDebug('title = ? OR title = ?', array('book1', 'book2'), 'title = \'book1\' OR title = \'book2\'');
		$this->testDebug('title = ? OR price = ?', array('book1', 20), 'title = \'book1\' OR price = 20');
		$this->testDebug('number IN (?,?)', array(8,900), 'number IN (8,900)');
		$this->testDebug('?', array(20), '20');
		$this->testDebug('?,?', array('test',20), '\'test\',20');
		$this->testDebug('?', array( NULL ), 'NULL');
		$this->testDebug('title = ?', array( NULL ), 'title = NULL');
		$this->testDebug('?,?', array( NULL,NULL ), 'NULL,NULL');
		$this->testDebug('title = ?', array('a very long title that should be shortened'), 'title = \'a very long title th... \'');
		$this->testDebug('title = ? OR title = ?', array('a very long title that should be shortened', 'another long title that should be shortened'), 'title = \'a very long title th... \' OR title = \'another long title t... \'');
		$this->testDebug('title = ? OR ?', array('a very long title that should be shortened', NULL), 'title = \'a very long title th... \' OR NULL');
		$this->testDebug('?,?', array('hello'), '\'hello\',:slot1');
		$this->testDebug('title = :first OR title = :second', array(':first'=>'book1', ':second'=>'book2'), 'title = \'book1\' OR title = \'book2\'');
		$this->testDebug('title = :first OR price = :second', array(':first'=>'book1', ':second'=>20), 'title = \'book1\' OR price = 20');
		$this->testDebug('number IN (:one,:two)', array(':one'=>8, ':two'=>900), 'number IN (8,900)');
		$this->testDebug('number IN (:one,:two)', array(':one'=>8, ':two'=>900, ':three'=>999), 'number IN (8,900)');
		$this->testDebug('number IN (:one,:two)', array(':three'=>999, ':one'=>8, ':two'=>900), 'number IN (8,900)');
		$this->testDebug('number IN (:one,:two)', array(':one'=>8, ':three'=>999, ':two'=>900), 'number IN (8,900)');
		$this->testDebug(':a', array(':a'=>20), '20');
		$this->testDebug(':a,?', array(':a'=>20, 30), '20,30');
		$this->testDebug(':a,?', array(30, ':a'=>20), '20,30');
		$this->testDebug('?,?', array('test',20), '\'test\',20');
		$this->testDebug('?', array( NULL ), 'NULL');
		$this->testDebug('title = ?', array( NULL ), 'title = NULL');
		$this->testDebug('?,?', array( NULL,NULL ), 'NULL,NULL');
		$this->testDebug('title = ?', array('a very long title that should be shortened'), 'title = \'a very long title th... \'');
		$this->testDebug('title = ? OR title = ?', array('a very long title that should be shortened', 'another long title that should be shortened'), 'title = \'a very long title th... \' OR title = \'another long title t... \'');
		$this->testDebug('title = ? OR ?', array('a very long title that should be shortened', NULL), 'title = \'a very long title th... \' OR NULL');
		$this->testDebug('?,?', array('hello'), '\'hello\',:slot1');
		$this->testDebug('hello ?', 'world', 'hello ?');
		$this->testDebug(':slot0 :slot1 :slot2 :slot3 :slot4 :slot5 :slot6 :slot7 :slot8 :slot9 :slot10', array(
		'a','b','c','d','e','f','g','h','i','j','k'
		),"'a' 'b' 'c' 'd' 'e' 'f' 'g' 'h' 'i' 'j' 'k'");
		$this->testDebug('? ? ? ? ? ? ? ? ? ? ?', array(
		'a','b','c','d','e','f','g','h','i','j','k'
		),"'a' 'b' 'c' 'd' 'e' 'f' 'g' 'h' 'i' 'j' 'k'");
		$this->testDebug(':a :aaa :ab', array(':a'=>1,':aaa'=>2,':ab'=>3),'1 2 3');
		Debugger::setOverrideCLIOutput( TRUE );
		$this->testDebug('SELECT * FROM table', NULL, 'SELECT * FROM table', 0, 'SELECT * FROM table<br />');
		$this->testDebug('DROP TABLE myths', NULL, 'DROP TABLE myths', 0, '<b style="color:red">DROP TABLE myths</b><br />');
		$this->testDebug('SELECT * FROM book WHERE title = ?', array('my book'), 'SELECT * FROM book WHERE title = <b style="color:green">\'my book\'</b>');
		Debugger::setOverrideCLIOutput( FALSE );
	}

	/**
	 * Test facade fancyDebug function
	 */
	public function testDebug2InFacade()
	{
		R::fancyDebug( TRUE );
		pass();
	}
}
