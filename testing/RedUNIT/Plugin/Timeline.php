<?php
/**
 * RedUNIT_Plugin_Timeline 
 * 
 * @file 			RedUNIT/Plugin/Timeline.php
 * @description		Tests the Time Line feature for logging schema modifications.
 *					This test pack is part of the RedBeanPHP ORM Plugin test suite.
 * 					This class is part of the RedUNIT test suite for RedBeanPHP.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Plugin_Timeline extends RedUNIT_Plugin {

	/**
	 * Begin testing.
	 * This method runs the actual test pack.
	 * 
	 * @return void
	 */
	public function run()
	{
		R::nuke();

		file_put_contents( '/tmp/test_log.txt', '' );

		R::log( '/tmp/test_log.txt' );

		$bean       = R::dispense( 'bean' );
		$bean->name = TRUE;

		R::store( $bean );
		$bean->name = 'test';

		R::store( $bean );

		$log = file_get_contents( '/tmp/test_log.txt' );
		asrt( strlen( $log ) > 0, TRUE );
		echo $log;
	}
}