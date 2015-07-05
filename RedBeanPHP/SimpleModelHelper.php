<?php

namespace RedBeanPHP;

use RedBeanPHP\Observer as Observer;
use RedBeanPHP\OODBBean as OODBBean;
use RedBeanPHP\Observable as Observable;

/**
 * RedBean Model Helper.
 *
 * Connects beans to models.
 * This is the core of so-called FUSE.
 *
 * @file    RedBeanPHP/ModelHelper.php
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class SimpleModelHelper implements Observer
{

	/**
	 * @see Observer::onEvent
	 */
	public function onEvent( $eventName, $bean )
	{
		$bean->$eventName();
	}

	/**
	 * Attaches the FUSE event listeners. Now the Model Helper will listen for
	 * CRUD events. If a CRUD event occurs it will send a signal to the model
	 * that belongs to the CRUD bean and this model will take over control from
	 * there.
	 *
	 * @param Observable $observable object to observe
	 *
	 * @return void
	 */
	public function attachEventListeners( Observable $observable )
	{
		foreach ( array( 'update', 'open', 'delete', 'after_delete', 'after_update', 'dispense' ) as $e ) {
			$observable->addEventListener( $e, $this );
		}
	}
}
