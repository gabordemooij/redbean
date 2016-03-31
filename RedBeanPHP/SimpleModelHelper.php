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
	 * Gets notified by an observable.
	 * This method decouples the FUSE system from the actual beans.
	 * If a FUSE event happens 'update', this method will attempt to
	 * invoke the corresponding method on the bean.
	 *
	 * @param string  $eventName i.e. 'delete', 'after_delete'
	 * @param OODBean $bean      affected bean
	 *
	 * @return void
	 */
	public function onEvent( $eventName, $bean )
	{
		$bean->$eventName();
	}

	/**
	 * Attaches the FUSE event listeners. Now the Model Helper will listen for
	 * CRUD events. If a CRUD event occurs it will send a signal to the model
	 * that belongs to the CRUD bean and this model will take over control from
	 * there. This method will attach the following event listeners to the observable:
	 *
	 * - 'update'       (gets called by R::store, before the records gets inserted / updated)
	 * - 'after_update' (gets called by R::store, after the records have been inserted / updated)
	 * - 'open'         (gets called by R::load, after the record has been retrieved)
	 * - 'delete'       (gets called by R::trash, before deletion of record)
	 * - 'after_delete' (gets called by R::trash, after deletion)
	 * - 'dispense'     (gets called by R::dispense)
	 *
	 * For every event type, this method will register this helper as a listener.
	 * The observable will notify the listener (this object) with the event ID and the
	 * affected bean. This helper will then process the event (onEvent) by invoking
	 * the event on the bean. If a bean offers a method with the same name as the
	 * event ID, this method will be invoked.
	 *
	 * @param Observable $observable object to observe
	 *
	 * @return void
	 */
	public function attachEventListeners( Observable $observable )
	{
		foreach ( array( 'update', 'open', 'delete', 'after_delete', 'after_update', 'dispense' ) as $eventID ) {
			$observable->addEventListener( $eventID, $this );
		}
	}
}
