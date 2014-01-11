<?php

namespace RedUNIT;
use RedBeanPHP\Facade as R;

/**
 * RedUNIT
 * Base class for RedUNIT, the micro unit test suite for RedBeanPHP
 *
 * @file               RedUNIT/RedUNIT.php
 * @description        Provides the basic logic for any unit test in RedUNIT.
 * @author             Gabor de Mooij
 * @license            BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
abstract class RedUNIT
{
	/**
	 * @var string
	 */
	protected $currentlyActiveDriverID = 'unknown';

	/**
	 * What drivers should be loaded for this test pack?
	 */
	abstract public function getTargetDrivers();

	/**
	 * Prepare test pack (mostly: nuke the entire database)
	 */
	public function prepare()
	{
		R::freeze( FALSE );
		
		R::debug( FALSE );

		R::nuke();
	}

	/**
	 * Run the actual test
	 */
	public function run()
	{
		$class = new \ReflectionClass( $this );

		$skip = array( 'run', 'getTargetDrivers', 'onEvent');
		// Call all methods except run automatically
		foreach ( $class->getMethods( \ReflectionMethod::IS_PUBLIC ) as $method ) {
			// Skip methods inherited from parent class
			if ( $method->class != $class->getName() ) continue;

			if ( in_array( $method->name, $skip ) ) continue;

			$classname = str_replace( $class->getParentClass()->getName().'_', '', $method->class );

			printtext( "\n\t" . $classname."->".$method->name." [".$method->class."->".$method->name."]" . " \n\t" );

			$call = $method->name;

			$this->$call();

			try {
				R::nuke();
			} catch( \PDOException $e ) {
				// Some tests use a broken database on purpose, so an exception is ok
			}
		}
	}

	/**
	 * Do some cleanup (if necessary..)
	 */
	public function cleanUp()
	{
	}

	/**
	 * Sets the current driver.
	 */
	public function setCurrentDriver( $driver )
	{
		$this->currentlyActiveDriverID = $driver;
	}
}
