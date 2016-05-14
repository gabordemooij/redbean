<?php

namespace RedUNIT\Base;

use RedUNIT\Base as Base;
use RedBeanPHP\Facade as R;

/**
 * Issue 408
 *
 * Tests whether this specific issue on github has been resolved.
 * Tests whether we can use export on beans having arrays in properties.
 *
 * @file    RedUNIT/Mysql/Issue408.php
 * @desc    Test whether we can export beans with arrays in properties
 *          (deserialized/serialized on open/update).
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Issue408 extends Base
{

	/**
	 * In the past it was not possible to export beans
	 * like 'feed' (Model_Feed).
	 *
	 * @return void
	 */
	public function testExportIssue()
	{
		R::nuke();
		$feed = R::dispense( 'feed' );
		$feed->post = array(
			'first',
			'second'
		);
		R::store( $feed );
		$rows = R::getAll('SELECT * FROM feed');
		asrt( $rows[0]['post'], '["first","second"]' );
		$feed = $feed->fresh();
		asrt( is_array( $feed->post ), TRUE );
		asrt( $feed->post[0], 'first' );
		asrt( $feed->post[1], 'second' );
		R::store( $feed );
		$rows = R::getAll('SELECT * FROM feed');
		asrt( $rows[0]['post'], '["first","second"]' );
		$feed = R::load( 'feed', $feed->id );
		$feed->post[] = 'third';
		R::store( $feed );
		$rows = R::getAll('SELECT * FROM feed');
		asrt( $rows[0]['post'], '["first","second","third"]' );
		$feed = $feed->fresh();
		asrt( is_array( $feed->post ), TRUE );
		asrt( $feed->post[0], 'first' );
		asrt( $feed->post[1], 'second' );
		asrt( $feed->post[2], 'third' );
		//now the catch: can we use export?
		//PHP Fatal error:  Call to a member function export() on a non-object
		$feeds = R::exportAll( R::find( 'feed' ) );
		asrt( is_array( $feeds ), TRUE );
		$feed = reset( $feeds );
		asrt( $feed['post'][0], 'first' );
		asrt( $feed['post'][1], 'second' );
		asrt( $feed['post'][2], 'third' );
		//can we also dup()?
		$feedOne = R::findOne( 'feed' );
		R::store( R::dup( $feedOne ) );
		asrt( R::count( 'feed' ), 2 );
		//can we delete?
		R::trash( $feedOne );
		asrt( R::count( 'feed' ), 1 );
		$feedTwo = R::findOne( 'feed' );
		$feed = $feedTwo->export();
		asrt( $feed['post'][0], 'first' );
		asrt( $feed['post'][1], 'second' );
		asrt( $feed['post'][2], 'third' );
	}
}
