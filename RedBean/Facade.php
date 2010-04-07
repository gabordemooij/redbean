<?php
/**
 * RedBean Facade
 * @file			RedBean/Facade.php
 * @description		Facade Class for people who want to:
 *					- focus on prototyping
 *					- are not interested in OO architecture (yet)
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD license that is bundled
 * with this source code in the file license.txt.
 *
 */

class R {
	
	/**
	 *
	 * Constains an instance of the RedBean Toolbox
	 * @var RedBean_ToolBox
	 *
	 */
	public static $toolbox;

	/**
	 * Constains an instance of RedBean OODB
	 * @var RedBean_OODB
	 */
	public static $redbean;

	/**
	 * Contains an instance of the Query Writer
	 * @var RedBean_QueryWriter
	 */
	public static $writer;

	/**
	 * Contains an instance of the Database
	 * Adapter.
	 * @var RedBean_DBAdapter
	 */
	public static $adapter;

	/**
	 * Contains an instance of the Tree Manager
	 * @var RedBean_TreeManager
	 */
	public static $treeManager;

	/**
	 * Contains an instance of the Association Manager
	 * @var RedBean_AssociationManager
	 */
	public static $associationManager;

	/**
	 * Kickstarts redbean for you.
	 * @param string $dsn
	 * @param string $username
	 * @param string $password
	 */
	public static function setup( $dsn, $username=NULL, $password=NULL ) {
		RedBean_Setup::kickstart( $dsn, $username, $password );
		self::$toolbox = RedBean_Setup::getToolBox();
		self::$writer = self::$toolbox->getWriter();
		self::$adapter = self::$toolbox->getDatabaseAdapter();
		self::$redbean = self::$toolbox->getRedBean();
		self::$associationManager = new RedBean_AssociationManager( self::$toolbox );
		self::$treeManager = new RedBean_TreeManager( self::$toolbox );
	}

	/**
	 * Stores a RedBean OODB Bean and returns the ID.
	 * @param RedBean_OODBBean $bean
	 * @return integer $id
	 */
	public static function store( RedBean_OODBBean $bean ) {
		return self::$redbean->store( $bean );
	}

	/**
	 * Loads the bean with the given type and id and returns it.
	 * @param string $type
	 * @param integer $id
	 * @return RedBean_OODBBean $bean
	 */
	public static function load( $type, $id ) {
		return self::$redbean->load( $type, $id );
	}

	/**
	 * Deletes the specified bean.
	 * @param RedBean_OODBBean $bean
	 * @return mixed
	 */
	public static function trash( RedBean_OODBBean $bean ) {
		return self::$redbean->trash( $bean );
	}

	/**
	 * Dispenses a new RedBean OODB Bean for use with
	 * the rest of the methods.
	 * @param string $type
	 * @return RedBean_OODBBean $bean
	 */
	public static function dispense( $type ) {
		return self::$redbean->dispense( $type );
	}

	/**
	 * Associates two Beans.
	 * @param RedBean_OODBBean $bean1
	 * @param RedBean_OODBBean $bean2
	 * @return mixed
	 */
	public static function associate( RedBean_OODBBean $bean1, RedBean_OODBBean $bean2 ) {
		return self::$associationManager->associate( $bean1, $bean2 );
	}

	/**
	 * Breaks the association between two beans.
	 * @param RedBean_OODBBean $bean1
	 * @param RedBean_OODBBean $bean2
	 * @return mixed
	 */
	public static function unassociate( RedBean_OODBBean $bean1, RedBean_OODBBean $bean2 ) {
		return self::$associationManager->unassociate( $bean1, $bean2 );
	}

	/**
	 * Returns all the beans associated with $bean.
	 * @param RedBean_OODBBean $bean
	 * @param string $type
	 * @return array $beans
	 */
	public static function related( RedBean_OODBBean $bean, $type ) {
		return self::$redbean->batch( $type, self::$associationManager->related( $bean, $type ));
	}

	/**
	 * Clears all associated beans.
	 * @param RedBean_OODBBean $bean
	 * @param string $type
	 * @return mixed
	 */
	public static function clearRelations( RedBean_OODBBean $bean, $type ) {
		return self::$associationManager->clearRelations( $bean, $type );
	}

	/**
	 * Attaches $child bean to $parent bean.
	 * @param RedBean_OODBBean $parent
	 * @param RedBean_OODBBean $child
	 * @return mixed
	 */
	public static function attach( RedBean_OODBBean $parent, RedBean_OODBBean $child ) {
		return self::$treeManager->attach( $parent, $child );
	}

	/**
	 * Returns all children beans under parent bean $parent
	 * @param RedBean_OODBBean $parent
	 * @return array $childBeans
	 */
	public static function children( RedBean_OODBBean $parent ) {
		return self::$treeManager->children( $parent );
	}
	
	
}

