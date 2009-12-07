<?php
/**
 * Observer
 * @file 		RedBean/Observer.php
 * @description		Part of the observer pattern in RedBean
 * @author			Gabor de Mooij
 * @license			BSD
 */
interface RedBean_Observer {
	
	/**
	 *
	 * @param string $eventname
	 * @param RedBean_OODBBean $bean
	 */
	public function onEvent( $eventname, $bean );
}