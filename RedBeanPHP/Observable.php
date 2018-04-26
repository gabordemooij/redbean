<?php

namespace RedBeanPHP;

use RedBeanPHP\Observer as Observer;

/**
 * Observable
 * Base class for Observables
 *
 * @file            RedBeanPHP/Observable.php
 * @author          Gabor de Mooij and the RedBeanPHP community
 * @license         BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
abstract class Observable { //bracket must be here - otherwise coverage software does not understand.

	/**
	 * @var array
	 */
	private $observers = array();

	/**
	 * Implementation of the Observer Pattern.
	 * Adds an event listener to the observable object.
	 * First argument should be the name of the event you wish to listen for.
	 * Second argument should be the object that wants to be notified in case
	 * the event occurs.
	 *
	 * @param string   $eventname event identifier
	 * @param Observer $observer  observer instance
	 *
	 * @return void
	 */
	public function addEventListener( $eventname, Observer $observer )
	{
		if ( !isset( $this->observers[$eventname] ) ) {
			$this->observers[$eventname] = array();
		}

		if ( in_array( $observer, $this->observers[$eventname] ) ) {
			return;
		}

		$this->observers[$eventname][] = $observer;
	}

	/**
	 * Notifies listeners.
	 * Sends the signal $eventname, the event identifier and a message object
	 * to all observers that have been registered to receive notification for
	 * this event. Part of the observer pattern implementation in RedBeanPHP.
	 *
	 * @param string $eventname event you want signal
	 * @param mixed  $info      message object to send along
	 *
	 * @return void
	 */
	public function signal( $eventname, $info )
	{
		if ( !isset( $this->observers[$eventname] ) ) {
			$this->observers[$eventname] = array();
		}

		foreach ( $this->observers[$eventname] as $observer ) {
			$observer->onEvent( $eventname, $info );
		}
	}
}
