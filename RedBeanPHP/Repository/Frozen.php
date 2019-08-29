<?php

namespace RedBeanPHP\Repository;

use RedBeanPHP\OODBBean as OODBBean;
use RedBeanPHP\QueryWriter as QueryWriter;
use RedBeanPHP\RedException as RedException;
use RedBeanPHP\BeanHelper as BeanHelper;
use RedBeanPHP\RedException\SQL as SQLException;
use RedBeanPHP\Repository as Repository;

/**
 * Frozen Repository.
 * OODB manages two repositories, a fluid one that
 * adjust the database schema on-the-fly to accomodate for
 * new bean types (tables) and new properties (columns) and
 * a frozen one for use in a production environment. OODB
 * allows you to swap the repository instances using the freeze()
 * method.
 *
 * @file    RedBeanPHP/Repository/Frozen.php
 * @author  Gabor de Mooij and the RedBeanPHP community
 * @license BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Frozen extends Repository
{
	/**
	 * Exception handler.
	 * Fluid and Frozen mode have different ways of handling
	 * exceptions. Fluid mode (using the fluid repository) ignores
	 * exceptions caused by the following:
	 *
	 * - missing tables
	 * - missing column
	 *
	 * In these situations, the repository will behave as if
	 * no beans could be found. This is because in fluid mode
	 * it might happen to query a table or column that has not been
	 * created yet. In frozen mode, this is not supposed to happen
	 * and the corresponding exceptions will be thrown.
	 *
	 * @param \Exception $exception exception
	 *
	 * @return void
	 */
	protected function handleException( \Exception $exception )
	{
		throw $exception;
	}

	/**
	 * Stores a cleaned bean; i.e. only scalar values. This is the core of the store()
	 * method. When all lists and embedded beans (parent objects) have been processed and
	 * removed from the original bean the bean is passed to this method to be stored
	 * in the database.
	 *
	 * @param OODBBean $bean the clean bean
	 *
	 * @return void
	 */
	protected function storeBean( OODBBean $bean )
	{
		if ( $bean->getMeta( 'changed' ) ) {

			list( $properties, $table ) = $bean->getPropertiesAndType();
			$id = $properties['id'];
			unset($properties['id']);
			$updateValues = array();
			$k1 = 'property';
			$k2 = 'value';

			$partial = ( $this->partialBeans === TRUE || ( is_array( $this->partialBeans ) && in_array( $table, $this->partialBeans ) ) );
			if ( $partial ) {
				$mask = $bean->getMeta( 'changelist' );
				$bean->setMeta( 'changelist', array() );
			}

			foreach( $properties as $key => $value ) {
				if ( $partial && !in_array( $key, $mask ) ) continue;
				$updateValues[] = array( $k1 => $key, $k2 => $value );
			}
			$bean->id = $this->writer->updateRecord( $table, $updateValues, $id );
			$bean->setMeta( 'changed', FALSE );
		}
		$bean->setMeta( 'tainted', FALSE );
	}

	/**
	 * Part of the store() functionality.
	 * Handles all new additions after the bean has been saved.
	 * Stores addition bean in own-list, extracts the id and
	 * adds a foreign key. Also adds a constraint in case the type is
	 * in the dependent list.
	 *
	 * Note that this method raises a custom exception if the bean
	 * is not an instance of OODBBean. Therefore it does not use
	 * a type hint. This allows the user to take action in case
	 * invalid objects are passed in the list.
	 *
	 * @param OODBBean $bean         bean to process
	 * @param array    $ownAdditions list of addition beans in own-list
	 *
	 * @return void
	 * @throws RedException
	 */
	protected function processAdditions( $bean, $ownAdditions )
	{
		$beanType = $bean->getMeta( 'type' );

		$cachedIndex = array();
		foreach ( $ownAdditions as $addition ) {
			if ( $addition instanceof OODBBean ) {

				$myFieldLink = $beanType . '_id';
				$alias = $bean->getMeta( 'sys.alias.' . $addition->getMeta( 'type' ) );
				if ( $alias ) $myFieldLink = $alias . '_id';

				$addition->$myFieldLink = $bean->id;
				$addition->setMeta( 'cast.' . $myFieldLink, 'id' );
				$this->store( $addition );

			} else {
				throw new RedException( 'Array may only contain OODBBeans' );
			}
		}
	}

	/**
	 * Loads a bean from the object database.
	 * It searches for a OODBBean Bean Object in the
	 * database. It does not matter how this bean has been stored.
	 * RedBean uses the primary key ID $id and the string $type
	 * to find the bean. The $type specifies what kind of bean you
	 * are looking for; this is the same type as used with the
	 * dispense() function. If RedBean finds the bean it will return
	 * the OODB Bean object; if it cannot find the bean
	 * RedBean will return a new bean of type $type and with
	 * primary key ID 0. In the latter case it acts basically the
	 * same as dispense().
	 *
	 * Important note:
	 * If the bean cannot be found in the database a new bean of
	 * the specified type will be generated and returned.
	 *
	 * @param string  $type type of bean you want to load
	 * @param integer $id   ID of the bean you want to load
	 *
	 * @return OODBBean
	 * @throws SQLException
	 */
	public function load( $type, $id )
	{
		$rows = array();
		$bean = $this->dispense( $type );
		if ( isset( $this->stash[$this->nesting][$id] ) ) {
			$row = $this->stash[$this->nesting][$id];
		} else {
			$rows = $this->writer->queryRecord( $type, array( 'id' => array( $id ) ) );
			if ( !count( $rows ) ) {
				return $bean;
			}
			$row = array_pop( $rows );
		}
		$bean->importRow( $row );
		$this->nesting++;
		$this->oodb->signal( 'open', $bean );
		$this->nesting--;

		return $bean->setMeta( 'tainted', FALSE );
	}
}
