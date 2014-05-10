<?php

namespace RedUNIT\Blackhole;

use RedUNIT\Blackhole as Blackhole;
use RedBeanPHP\Facade as R;
use RedBeanPHP\OODBBean as OODBBean;
use RedBeanPHP\Logger\RDefault\Debug as Debugger;

/**
 * Debug
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
	 * Performs a test.
	 * Given a query, a set of bindings and an expected outcome,
	 * this method tests the result of the debugger.
	 *
	 * @param string $query
	 * @param mixed  $bindings
	 * @param string $expected
	 *
	 * @return void
	 */
	private function testDebug($query, $bindings = NULL, $expected)
	{
		$debugger = new Debugger;
		$debugger->setMode(1);
		if (!is_null($bindings)) {
			$debugger->log($query, $bindings);
		} else {
			$debugger->log($query);
		}
		$logs = $debugger->getLogs();
		$log = reset($logs);
		asrt($log, $expected);
		$debugger->clear();
	}

	/**
	 * Test dump().
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
	}

	/**
	 * Performs tests for debugger.
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
	}
}
