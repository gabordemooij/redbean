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
	 * Implementation of the Observer Pattern.
	 * Adds a listener to this instance.
	 * This method can be used to attach an observer to an object.
	 * You can subscribe to a specific event by providing the ID
	 * of the event you are interested in. Once the event occurs
	 * the observable will notify the listeners by calling
	 * onEvent(); providing the event ID and either a bean or
	 * an information array.
	 *
	 * @param string           $eventname event
	 * @param RedBean_Observer $observer observer
	 *
	 * @return void
	 */
	public function addEventListener( $eventname, RedBean_Observer $observer ) {
		if (!isset($this->observers[ $eventname ])) {
			$this->observers[ $eventname ] = array();
		}
		foreach($this->observers[$eventname] as $o) if ($o==$observer) return;
		$this->observers[ $eventname ][] = $observer;
	}

	/**
	 * Implementation of the Observer Pattern.
	 * Sends an event (signal) to the registered listeners
	 * This method is provided by the abstract class Observable for
	 * convience. Observables can use this method to notify their
	 * observers by sending an event ID and information parameter.
	 *
	 * @param string $eventname eventname
	 * @param mixed  $info      info
	 * @return unknown_ty
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