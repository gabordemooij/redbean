<?php
/**
 * RedBean_ObjectDatabase
 * @package 		RedBean/RedBean_ObjectDatabase.php
 * @description		RedBean simulates an object oriented database. This interface
 *					describes the API for the object database. It is the
 *					abstract core of RedBean describing its main functionality.
 * @author			Gabor de Mooij
 * @license			BSD
 */
interface RedBean_ObjectDatabase {

	/**
	 * Loads a bean from the object database.
	 * If the bean cannot be found in the database a new bean of
	 * the specified type will be generated and returned.
	 * @param string $type
	 * @param integer $id
	 * @return RedBean_OODBBean $bean
	 */
	public function load( $type, $id );

	/**
	 * Stores a bean in the database.
	 * @param RedBean_OODBBean $bean
	 */
	public function store( RedBean_OODBBean $bean );

	/**
	 * Removes a bean from the database.
	 * @param RedBean_OODBBean $bean
	 */
	public function trash( RedBean_OODBBean $bean );

	/**
	 * Loads a series of beans all at once.
	 * The beans are retrieved using their primary key IDs
	 * specified in the second argument.
	 * @param string $type
	 * @param array $ids
	 */
	public function batch( $type, $ids );

	/**
	 * Dispenses a new bean of the specified type. Always
	 * use this function to get an empty bean object. Never
	 * instantiate a RedBean_OODBBean yourself because it needs
	 * to be configured before you can use it with RedBean. This
	 * function applies the appropriate initialization /
	 * configuration for you.
	 * @param string $type
	 */
	public function dispense( $type );

}