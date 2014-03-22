<?php

/**
 * Pretests
 * 
 * These tests will run before the configuration takes place
 * in the unit test suite (mostly error handling tests).
 *
 * @file    RedUNIT/Pretest.php
 * @desc    Test scripts that run before configuration
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */

testpack('Running pre-tests. (before config).');

try {
	R::debug( TRUE );
	fail();
} catch( Exception $e ) {
	pass();
}

