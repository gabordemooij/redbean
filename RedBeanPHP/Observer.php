<?php

namespace RedBeanPHP;

/**
 * Observer.
 *
 * Interface for Observer object. Implementation of the
 * observer pattern.
 *
 * @file    RedBeanPHP/Observer.php
 * @author  Gabor de Mooij and the RedBeanPHP community
 * @license BSD/GPLv2
 * @desc    Part of the observer pattern in RedBean
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
interface Observer
{
	/**
	 * An observer object needs to be capable of receiving
	 * notifications. Therefore the observer needs to implement the
	 * onEvent method with two parameters: the event identifier specifying the
	 * current event and a message object (in RedBeanPHP this can also be a bean).
	 *
	 * @param string $eventname event identifier
	 * @param mixed  $bean      a message sent along with the notification
	 *
	 * @return void
	 */
	public function onEvent( $eventname, $bean );
}
