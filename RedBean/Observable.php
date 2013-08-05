<?php
/**
 * Observable
 * Base class for Observables
 *
 * @file            RedBean/Observable.php
 * @description     Part of the observer pattern in RedBean
 * @author          Gabor de Mooij and the RedBeanPHP community
 * @license         BSD/GPLv2
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
abstract class RedBean_Observable { //bracket must be here - otherwise coverage software does not understand.

	/**
	 * @var array
	 */
	private $observers = array();

	/**
	 * Implementation of the Observer Pattern.
	 *
	 * @param string           $eventname event
	 * @param RedBean_Observer $observer  observer
	 *
	 * @return void
	 */
	public function addEventListener( $eventname, RedBean_Observer $observer )
	{
		if ( !isset( $this->observers[$eventname] ) ) {
			$this->observers[$eventname] = array();
		}

		foreach ( $this->observers[$eventname] as $o ) {
			if ( $o == $observer ) {
				return;
			}
		}

		$this->observers[$eventname][] = $observer;
	}

	/**
	 * Notifies listeners.
	 *
	 * @param string $eventname eventname
	 * @param mixed  $info      info
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
