<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;
use RedBeanPHP\RedException as RedException;
use RedBeanPHP\RedException\SQL as SQLException;
use RedBeanPHP\OODBBean as OODBBean;
use RedBeanPHP\Adapter\DBAdapter;
use RedBeanPHP\ToolBox;
use RedBeanPHP\QueryWriter\SQLiteT;
use RedBeanPHP\QueryWriter\AQueryWriter;
use RedBeanPHP\QueryWriter;
use RedBeanPHP\OODB;
use RedBeanPHP\Driver\RPDO;

/**
 * Exceptions
 *
 * Tests exception handling in various scenarios as well
 * as exception related functionalities.
 *
 * @file    RedUNIT/Base/Exceptions.php
 * @desc    Tests exception handling
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Exceptions extends Base {

	/**
	 * Tests load exceptions.
	 * Load Exceptions (version 5+)
	 *
	 * - If a bean does not exist,
	 *   R::load() and R::loadForUpdate() will return an empty bean.
	 * - If there is an error because of a missing table or column,
	 *   both methods will return an empty bean in fluid mode
	 *   and throw an exception in frozen mode.
	 * - If something else happens (lock timeout for instance)
	 *   both methods will always throw an exception, even in fluid mode. 
	 *
	 * @return void
	 */
	public function testLoadExceptions()
	{
		/* bean does not exist, and table does not exist */
		R::nuke();
		$book = R::load( 'book', 1 );
		pass();
		asrt( $book->id, 0 );
		R::freeze( TRUE );
		$exception = NULL;
		try {
			$book = R::load( 'book', 1 );
		} catch( RedException $exception ) {}
		asrt( ( $exception instanceof RedException ), TRUE );
		R::freeze( FALSE );
		R::store( $book );
		/* bean does not exist - table exists */
		$book = R::load( 'book', 2 );
		pass();
		asrt( $book->id, 0 );
		R::freeze( TRUE );
		$book = R::load( 'book', 2 );
		pass();
		asrt( $book->id, 0 );
		/* other error */
		if ( !( R::getWriter() instanceof SQLiteT ) ) {
			R::freeze( FALSE );
			$exception = NULL;
			try {
				$book = R::load( 'book', 1, 'invalid sql' );
			} catch( RedException $exception ) {}
			//not supported for CUBRID
			if ($this->currentlyActiveDriverID !== 'CUBRID') {
				asrt( ( $exception instanceof RedException ), TRUE );
			}
		} else {
			/* error handling in SQLite is suboptimal */
			R::freeze( FALSE );
			$book = R::load( 'book', 1, 'invalid sql' );
			pass();
			asrt( $book->id, 0 );
		}
		R::freeze( TRUE );
		$exception = NULL;
		try {
			$book = R::load( 'book', 1, 'invalid sql' );
		} catch( RedException $exception ) {}
		asrt( ( $exception instanceof RedException ), TRUE );
		R::freeze( FALSE );
		R::nuke();
	}

	/**
	 * Test delete exceptions
	 *
	 * - in fluid mode no complaining about missing structures
	 *
	 * @return void
	 */
	public function testDeleteExceptions()
	{
		R::nuke();
		$book = R::dispense( 'book' );
		R::store( $book );
		R::nuke();
		R::trash( $book );
		R::freeze( TRUE );
		$exception = NULL;
		try {
			R::trash( $book );
		} catch( RedException $exception ) {}
		asrt( ( $exception instanceof RedException ), TRUE );
		R::freeze( FALSE );
		$adapter = R::getDatabaseAdapter();
		R::nuke();
		$book = R::dispense( 'book' );
		R::store( $book );
		$broken = new BrokenWriter( $adapter );
		$redbean = R::getRedBean();
		$oodb = new OODB( $broken, $redbean->isFrozen() );
		R::setRedBean( $oodb );
		$exception = NULL;
		try {
			R::trash( $book );
		} catch( RedException $exception ) {}
		asrt( ( $exception instanceof RedException ), TRUE );
		R::freeze( TRUE );
		$exception = NULL;
		try {
			R::trash( $book );
		} catch( RedException $exception ) {}
		asrt( ( $exception instanceof RedException ), TRUE );
		R::setRedBean( $redbean );
	}

	/**
	 * Test chaining of exceptions.
	 *
	 * @return void
	 */
	 public function testChainingExceptions()
	 {
		 R::freeze( TRUE );
		 $exception = NULL;
		 try {
				$book = R::load( 'book', 1, 'invalid sql' );
		} catch( RedException $exception ) {}
		pass();
		asrt( ( $exception instanceof RedException ), TRUE );
		asrt( ( $exception->getPrevious() instanceof \Exception ), TRUE );
	 }
}

class BrokenWriter extends SQLiteT {
	public function deleteRecord( $type, $conditions = array(), $addSql = NULL, $bindings = array() )
	{
		throw new SQLException('oops');
	}
}
