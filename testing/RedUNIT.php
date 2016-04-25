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
	 * @var integer
	 */
	protected $round;

	/**
	 * @var string
	 */
	protected $currentlyActiveDriverID = 'unknown';

	/**
	 * What drivers should be loaded for this test pack ?
	 * This method should be implemented by the test to tell the
	 * test controller system what drivers are supposed to be tested.
	 * Each driver will be fed to the test in a 'round' (explained below).
	 *
	 * @return array
	 */
	abstract public function getTargetDrivers();

	/**
	 * Prepare test pack (mostly: nuke the entire database).
	 * This method prepares the test for a run.
	 */
	public function prepare()
	{
		R::freeze( FALSE );
		R::debug( FALSE );
		R::nuke();
	}

	/**
	 * Runs the test. This method will run the tests implemented
	 * by the RedUNIT instance. The run method scans its class for
	 * all public instance methods except:
	 * run (to avoid recursion), getTargetDrivers, onEvent
	 * and prepare. -- added cleanUp/prepare just in case they get overridden.
	 *
	 * @return void
	 */
	public function run()
	{
		$class = new \ReflectionClass( $this );
		$skip = array( 'run', 'getTargetDrivers', 'onEvent', 'cleanUp', 'prepare' );
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
	 * Clean-up method, to be invoked after running the test.
	 * This is an empty implementation, it does nothing. However this method
	 * should be overridden by tests that require clean-up.
	 *
	 * @return void
	 */
	public function cleanUp()
	{
	}

	/**
	 * Sets the current driver.
	 * This method is called by a test controller, runner or manager
	 * to activate the mode associated with the specified driver
	 * identifier. This mechanism allows a test to run slightly different
	 * in the context of another driver, for instance SQLite might not need
	 * some tests, or MySQL might need some additional tests etc...
	 *
	 * @param string $driver the driver identifier
	 *
	 * @return void
	 */
	public function setCurrentDriver( $driver )
	{
		$this->currentlyActiveDriverID = $driver;
	}

	/**
	 * Sets the round number.
	 * Each test can have a varying number of flavors.
	 * A test flavor is 'that same test' but for a different driver.
	 * Each 'run on a specific driver' is called a round.
	 * Some tests might want to know what the current round is.
	 * This method can be used to set the current round number.
	 *
	 * @param integer $roundNumber round, the current round number
	 *
	 * @return void
	 */
	public function setRound( $roundNumber )
	{
		$this->round = (int) $roundNumber;
	}

	/**
	 * Returns the current round number.
	 * The current round number indicates how many times
	 * this test has been applied (to various drivers).
	 *
	 * @return integer
	 */
	public function getRound()
	{
		return $this->round;
	}

	/**
	 * Detects whether the current round is the first one.
	 * If the current round is indeed the first round, this method
	 * will return boolean TRUE, otherwise it will return FALSE. Note that
	 * the rounds are 0-based, so - if the current round equals 0, this
	 * method will return TRUE.
	 *
	 * @return boolean
	 */
	public function isFirstRound()
	{
		return ( $this->round === 0 );
	}
}
