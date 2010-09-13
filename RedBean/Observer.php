<?php
/**
 * Observer
 * @file 		RedBean/Observer.php
 * @description		Part of the observer pattern in RedBean
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
interface RedBean_Observer {

	/**
	 * Part of the RedBean Observer Infrastructure.
	 * @param string $eventname
	 * @param RedBean_OODBBean $bean
	 */
	public function onEvent( $eventname, $bean );
}