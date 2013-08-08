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
		$methods = get_class_methods( $this );

		// Call all methods except run automatically
		foreach ( $methods as $method ) {
			if ( $method == 'run' ) continue;

			$this->$method;

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
	 * Associating two beans, then loading the associated bean
	 */
	public function testAssociateAndLoad()
	{
		$person       = R::dispense( 'person' );
		$person->name = 'John';

		R::store( $person );

		$course       = R::dispense( 'course' );
		$course->name = 'Math';

		R::store( $course );

		$course->teacher = $person;

		$id      = R::store( $course );
		$course  = R::load( 'course', $id );
		$teacher = $course->fetchAs( 'person' )->teacher;

		asrt( $teacher->name, 'John' );
	}
}
