<?php
/**
 * 
 * @author gabordemooij
 *
 */
interface RedBean_Observer {
	
	/**
	 * 
	 * @param $eventname
	 * @param $o
	 * @return unknown_type
	 */
	public function onEvent( $eventname, RedBean_Observable $o );
}