<?php
/**
 * RedUNIT_Base
 *
 * @file    RedUNIT/Base.php
 * @desc    Base class for all drivers that support all database systems.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Base extends RedUNIT
{
	/**
	 * What drivers should be loaded for this test pack?
	 */
	public function getTargetDrivers()
	{
		return array( 'mysql', 'pgsql', 'sqlite', 'CUBRID', 'oracle' );
	}

	/**
	 * Begin testing.
	 * This method runs the actual test pack.
	 *
	 * @return void
	 */
	public function run()
	{
		$name = get_class($this);

		$class = new ReflectionClass( $name );

		// Call all methods except run automatically
		foreach ( $class->getMethods(ReflectionMethod::IS_PUBLIC) as $method ) {
			// Skip methods inherited from parent class
			if ( $method->class != $name ) continue;

			if ( $method->name == 'run' ) continue;

			if ( $method->name == 'getTargetDrivers' ) continue;

			$class = str_replace( $class->getParentClass()->getName(), '', $method->class );
			testpack( "[".$method->class."->".$method->name."]");

			$call = $method->name;

			$this->$call();

			R::nuke();
		}
	}
}
