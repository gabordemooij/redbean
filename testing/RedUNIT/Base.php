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
		$class = new ReflectionClass( $this );

		$skip = array( 'run', 'getTargetDrivers', 'onEvent');
		// Call all methods except run automatically
		foreach ( $class->getMethods(ReflectionMethod::IS_PUBLIC) as $method ) {
			// Skip methods inherited from parent class
			if ( $method->class != $class->getName() ) continue;

			if ( in_array( $method->name, $skip ) ) continue;

			$classname = str_replace( $class->getParentClass()->getName().'_', '', $method->class );

			printtext( "\n\t" . $classname."->".$method->name." [".$method->class."->".$method->name."]" . " \n\t" );

			$call = $method->name;

			$this->$call();

			R::nuke();
		}
	}
}
