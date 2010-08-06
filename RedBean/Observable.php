<?php
/**
 * Observable
 * Base class for Observables
 * @file 		RedBean/Observable.php
 * @description		Part of the observer pattern in RedBean
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
abstract class RedBean_Observable {
	/**
	 *
	 * @var array
	 */
	private $observers = array();

	/**
	 * Adds a listener to this instance
	 * @param $eventname
	 * @param $observer
	 * @return unknown_type
	 */
	public function addEventListener( $eventname, RedBean_Observer $observer ) {

		if (!isset($this->observers[ $eventname ])) {
			$this->observers[ $eventname ] = array();
		}

		$this->observers[ $eventname ][] = $observer;
	}

	/**
	 * Sends an event (signal) to the registered listeners
	 * @param $eventname
	 * @return unknown_type
	 */
	public function signal( $eventname, $info ) {

		if (!isset($this->observers[ $eventname ])) {
			$this->observers[ $eventname ] = array();
		}

		foreach($this->observers[$eventname] as $observer) {
			$observer->onEvent( $eventname, $info );
		}

	}


}