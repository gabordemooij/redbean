<?php
/**
 * Observer
 *
 * @file    RedBean/Observer.php
 * @desc    Part of the observer pattern in RedBean
 * @author  Gabor de Mooijand the RedBeanPHP community
 * @license BSD/GPLv2
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
interface RedBean_Observer
{

	/**
	 * @param string $eventname
	 * @param        $bean
	 *
	 * @return void
	 */
	public function onEvent( $eventname, $bean );
}
