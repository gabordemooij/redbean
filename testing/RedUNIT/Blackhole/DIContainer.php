<?php
/**
 * RedUNIT_Blackhole_DIContainer
 *
 * @file    RedUNIT/Blackhole/DIContainer.php
 * @desc    Tests dependency injection architecture.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Blackhole_DIContainer extends RedUNIT_Blackhole
{
	/**
	 * Test dependency injection with RedBeanPHP.
	 * 
	 * @return void
	 */
	public function testDependencyInjection()
	{
		// base scenario
		$geek = R::dispense( 'geek' );

		list( $coffee, $cocoa ) = $geek->getObjects();

		asrt( ( $coffee instanceof Dependency_Coffee ), FALSE );
		asrt( ( $cocoa instanceof Dependency_Cocoa ), FALSE );

		// Base scenario with empty container, don't fail
		$di = new RedBean_DependencyInjector;

		$geek = R::dispense( 'geek' );

		list( $coffee, $cocoa ) = $geek->getObjects();

		asrt( ( $coffee instanceof Dependency_Coffee ), FALSE );
		asrt( ( $cocoa instanceof Dependency_Cocoa ), FALSE );

		// Succesfull scenario, one missing
		$di = new RedBean_DependencyInjector;

		$di->addDependency( 'Coffee', new Dependency_Coffee );

		RedBean_ModelHelper::setDependencyInjector( $di );

		$geek = R::dispense( 'geek' );

		list( $coffee, $cocoa ) = $geek->getObjects();

		asrt( ( $coffee instanceof Dependency_Coffee ), TRUE );
		asrt( ( $cocoa instanceof Dependency_Cocoa ), FALSE );

		// Success scenario
		$di = new RedBean_DependencyInjector;

		$di->addDependency( 'Coffee', new Dependency_Coffee );
		$di->addDependency( 'Cocoa', new Dependency_Cocoa );

		RedBean_ModelHelper::setDependencyInjector( $di );

		$geek = R::dispense( 'geek' );

		list( $coffee, $cocoa ) = $geek->getObjects();

		asrt( ( $coffee instanceof Dependency_Coffee ), TRUE );
		asrt( ( $cocoa instanceof Dependency_Cocoa ), TRUE );

		// Don't fail if not exists
		$di->addDependency( 'NonExistantObject', new Dependency_Coffee );

		$geek = R::dispense( 'geek' );
		$geek = R::dispense( 'geek' );

		list( $coffee, $cocoa ) = $geek->getObjects();

		asrt( ( $coffee instanceof Dependency_Coffee ), TRUE );
		asrt( ( $cocoa instanceof Dependency_Cocoa ), TRUE );

		// Can we go back to base scenario?
		RedBean_ModelHelper::clearDependencyInjector();

		$geek = R::dispense( 'geek' );

		list( $coffee, $cocoa ) = $geek->getObjects();

		asrt( ( $coffee instanceof Dependency_Coffee ), FALSE );
		asrt( ( $cocoa instanceof Dependency_Cocoa ), FALSE );
	}
}

/*
 * Mock object needed for DI testing
 */
class Dependency_Coffee
{
}

/*
 * Mock object needed for DI testing
 */
class Dependency_Cocoa
{
}

/*
 * Mock object needed for DI testing
 */
class Model_Geek extends RedBean_SimpleModel
{
	private $cocoa;
	private $coffee;

	public function setCoffee( Dependency_Coffee $coffee )
	{
		$this->coffee = $coffee;
	}

	public function setCocoa( Dependency_Cocoa $cocoa )
	{
		$this->cocoa = $cocoa;
	}

	public function getObjects()
	{
		return array( $this->coffee, $this->cocoa );
	}
}


