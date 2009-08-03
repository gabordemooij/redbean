<?php
/**
 * 
 * @author gabordemooij
 *
 */
class RedBean_Observable {
	/**
	 * 
	 * @var array
	 */
	private $observers = array();
	
	/**
	 * 
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
	 * 
	 * @param $eventname
	 * @return unknown_type
	 */
	public function signal( $eventname ) {
		
		if (!isset($this->observers[ $eventname ])) {
			$this->observers[ $eventname ] = array();
		}
		
		foreach($this->observers[$eventname] as $observer) {
			$observer->onEvent( $eventname, $this );	
		}
		
	}
	
	
}