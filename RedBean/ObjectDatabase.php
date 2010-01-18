<?php
/**
 * RedBean_ObjectDatabase
 * @file 		RedBean/RedBean_ObjectDatabase.php
 * @description		RedBean simulates an object oriented database. This interface
 *					describes the API for the object database. It is the
 *					abstract core of RedBean describing its main functionality.
 * @author			Gabor de Mooij
 * @license			BSD
 */
interface RedBean_ObjectDatabase {

	/**
	 * This interface describes how ANY Object Database should
	 * behave.For detailed descriptions of RedBean specific implementation
	 * see: RedBean_OODB.
	 * An Object Database should be able to load a bean using a $type and $id.
	 * The $type argument indicated what kind of bean you are looking for.
	 * The $id argument specifies the primary key ID; which links the bean to
	 * a (series) of record(s) in the database.
	 * @param string $type
	 * @param integer $id
	 * @return RedBean_OODBBean $bean
	 */
	public function load( $type, $id );

	/**
	 * This interface describes how ANY Object Database should
	 * behave.For detailed descriptions of RedBean specific implementation
	 * see: RedBean_OODB.
	 * An Object Database should be able to store a RedBean_OODBBean $bean.
	 * @param RedBean_OODBBean $bean
	 * @return integer $newid
	 */
	public function store( RedBean_OODBBean $bean );

	/**
	 * This interface describes how ANY Object Database should
	 * behave.For detailed descriptions of RedBean specific implementation
	 * see: RedBean_OODB.
	 * @param RedBean_OODBBean $bean
	 */
	public function trash( RedBean_OODBBean $bean );

	/**
	 * This interface describes how ANY Object Database should
	 * behave.For detailed descriptions of RedBean specific implementation
	 * see: RedBean_OODB.
	 * @param string $type
	 * @param array $ids
	 * @return array $beans
	 */
	public function batch( $type, $ids );

	/**
	 * This interface describes how ANY Object Database should
	 * behave.For detailed descriptions of RedBean specific implementation
	 * see: RedBean_OODB.
	 *  @param string $type
	 * @return RedBean_OODBBean $bean
	 */
	public function dispense( $type );

}