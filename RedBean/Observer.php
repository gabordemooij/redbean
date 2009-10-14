<?php
/**
 * Observer
 * @package 		RedBean/Observer.php
 * @description		Part of the observer pattern in RedBean
 * @author			Gabor de Mooij
 * @license			BSD
 */
interface RedBean_Observer {
	
	/**
	 * Handles the event send by a RedBean Observable
	 * @param string $eventname
	 * @param RedBean_Observable $observable
	 * @return unknown_type
	 */
	public function onEvent( $eventname, $info );
}