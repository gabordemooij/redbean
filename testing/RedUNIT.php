<?php
/**
 * RedUNIT
 * Base class for RedUNIT, the micro unit test suite for RedBeanPHP
 * @file			RedUNIT/RedUNIT.php
 * @description		Provides the basic logic for any unit test in RedUNIT.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
abstract class RedUNIT {
	/**
	 * What drivers should be loaded for this test pack? 
	 */
	abstract public function getTargetDrivers();
	
	/**
	 * Prepare test pack (mostly: nuke the entire database)
	 */
	public function prepare() {
		R::freeze(false);
		R::$writer->setBeanFormatter(new DF);
		RedBean_ModelHelper::setModelFormatter(new DefaultModelFormatter);
		R::nuke();
		
	}
	/**
	 * Run the actual test
	 */
	public function run() {
	}
	/**
	 * Do some cleanup (if necessary..)
	 */
	public function cleanUp() {
	}
}	