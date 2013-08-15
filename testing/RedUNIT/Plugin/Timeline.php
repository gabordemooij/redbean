<?php
/**
 * RedUNIT_Plugin_Timeline
 *
 * @file    RedUNIT/Plugin/Timeline.php
 * @desc    Tests the Time Line feature for logging schema modifications.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Plugin_Timeline extends RedUNIT_Plugin
{
	/**
	 * Test TimeLine plugin.
	 * 
	 * @return void
	 */
	public function testTimeLineLogger()
	{
		// test for correct exception in case of non-existant file.
		try {
			$timeLine = new RedBean_Plugin_TimeLine( 'some-non-existant-file' );

			fail();
		} catch ( RedBean_Exception_Security $exception ) {
			pass();
		}

		file_put_contents( '/tmp/test_log.txt', '' );

		R::log( '/tmp/test_log.txt' );

		$bean = R::dispense( 'bean' );

		$bean->name = true;

		R::store( $bean );

		$bean->name = 'test';

		R::store( $bean );

		$log = file_get_contents( '/tmp/test_log.txt' );

		asrt( strlen( $log ) > 0, true );

		echo $log;
	}
}
