<?php
/**
 * RedUNIT_Blackhole_DIContainer
 *  
 * @file 			RedUNIT/Blackhole/DIContainer.php
 * @description		Tests dependency injection architecture.
 * 					This class is part of the RedUNIT test suite for RedBeanPHP.
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Blackhole_DIContainer extends RedUNIT_Blackhole {
	/**
	 * Begin testing.
	 * This method runs the actual test pack.
	 * 
	 * @return void
	 */
	public function run() {
		
		//base scenario
		$geek = R::dispense('geek');
		list($coffee,$cocoa) = $geek->getObjects();
		asrt(($coffee instanceof Dependency_Coffee),false);
		asrt(($cocoa instanceof Dependency_Cocoa),false);
		
		//base scenario with empty container, dont fail
		$di = new RedBean_DependencyInjector;
		$geek = R::dispense('geek');
		list($coffee,$cocoa) = $geek->getObjects();
		asrt(($coffee instanceof Dependency_Coffee),false);
		asrt(($cocoa instanceof Dependency_Cocoa),false);
		
		//succesful scenario, one missing
		$di = new RedBean_DependencyInjector;
		$di->addDependency('Coffee',new Dependency_Coffee);
		RedBean_ModelHelper::setDependencyInjector( $di );
		$geek = R::dispense('geek');
		list($coffee,$cocoa) = $geek->getObjects();
		asrt(($coffee instanceof Dependency_Coffee),true);
		asrt(($cocoa instanceof Dependency_Cocoa),false);
		
		//success scenario
		$di = new RedBean_DependencyInjector;
		$di->addDependency('Coffee',new Dependency_Coffee);
		$di->addDependency('Cocoa',new Dependency_Cocoa);
		RedBean_ModelHelper::setDependencyInjector( $di );
		$geek = R::dispense('geek');
		list($coffee,$cocoa) = $geek->getObjects();
		asrt(($coffee instanceof Dependency_Coffee),true);
		asrt(($cocoa instanceof Dependency_Cocoa),true);
		
		//dont fail if not exists
		$di->addDependency('NonExistantObject',new Dependency_Coffee);
		$geek = R::dispense('geek');
		$geek = R::dispense('geek');
		list($coffee,$cocoa) = $geek->getObjects();
		asrt(($coffee instanceof Dependency_Coffee),true);
		asrt(($cocoa instanceof Dependency_Cocoa),true);
		
		//can we go back to base scenario?
		RedBean_ModelHelper::clearDependencyInjector();
		$geek = R::dispense('geek');
		list($coffee,$cocoa) = $geek->getObjects();
		asrt(($coffee instanceof Dependency_Coffee),false);
		asrt(($cocoa instanceof Dependency_Cocoa),false);
		
		
	}
}

/*
 * Mock object needed for DI testing
 */
class Dependency_Coffee {}
/*
 * Mock object needed for DI testing
 */
class Dependency_Cocoa {}

/*
 * Mock object needed for DI testing
 */
class Model_Geek extends RedBean_SimpleModel {
	private $cocoa;
	private $coffee;
	public function setCoffee(Dependency_Coffee $coffee) {
		$this->coffee = $coffee;
	}
	public function setCocoa(Dependency_Cocoa $cocoa) {
		$this->cocoa = $cocoa;
	}
	public function getObjects() {
		return array($this->coffee,$this->cocoa);
	}
}


