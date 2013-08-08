<?php


class RedUNIT_Proposal extends RedUNIT_Base
{
	/**
	 * Begin testing.
	 * This method runs the actual test pack.
	 *
	 * @return void
	 */
	public function run()
	{
		$class = new ReflectionClass( get_class($this) );

		// Call all methods except run automatically
		foreach ( $class->getMethods(ReflectionMethod::IS_PUBLIC) as $method ) {
			// Skip methods inherited from parent class
			if ( $method->class != $class->getName() ) continue;

			if ( $method->name == 'run' ) continue;

			$call = $method->name;

			$this->$call();

			// Maybe each test automatically nukes the database afterwards?
			// R::nuke();
		}
	}

	/**
	 * The idea here is that the run() method should be inherited from
	 * RedUNIT_Base - that way, existing tests work fine while we iterate through
	 * the existing tests
	 */

	/**
	 * Methods defined as non public can be used as utility functions
	 * that aren't called in run()
	 */
	private function getThing( $type, $name )
	{
		$thing       = R::dispense( $type );
		$thing->name = $name;

		R::store( $thing );

		return $thing;
	}

	/**
	 * Associating two beans, then loading the associated bean
	 */
	public function testAssociateAndLoad()
	{
		$person = $this->getThing( 'person', 'John' );

		$course = $this->getThing( 'course', 'Math' );

		$course->teacher = $person;

		$id      = R::store( $course );
		$course  = R::load( 'course', $id );
		$teacher = $course->fetchAs( 'person' )->teacher;

		asrt( $teacher->name, 'John' );
	}
}
