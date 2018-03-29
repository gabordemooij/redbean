<?php

namespace RedBeanPHP\Repository;

use RedBeanPHP\OODBBean as OODBBean;
use RedBeanPHP\QueryWriter as QueryWriter;
use RedBeanPHP\RedException as RedException;
use RedBeanPHP\BeanHelper as BeanHelper;
use RedBeanPHP\RedException\SQL as SQLException;
use RedBeanPHP\Repository as Repository;

/**
 * Fluid Repository.
 * OODB manages two repositories, a fluid one that
 * adjust the database schema on-the-fly to accomodate for
 * new bean types (tables) and new properties (columns) and
 * a frozen one for use in a production environment. OODB
 * allows you to swap the repository instances using the freeze()
 * method.
 *
 * @file    RedBeanPHP/Repository/Fluid.php
 * @author  Gabor de Mooij and the RedBeanPHP community
 * @license BSD/GPLv2
 *
 * @copyright
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Fluid extends Repository
{
	/**
	 * Figures out the desired type given the cast string ID.
	 * Given a cast ID, this method will return the associated
	 * type (INT(10) or VARCHAR for instance). The returned type
	 * can be processed by the Query Writer to build the specified
	 * column for you in the database. The Cast ID is actually just
	 * a superset of the QueryWriter types. In addition to default
	 * Query Writer column types you can pass the following 'cast types':
	 * 'id' and 'string'. These will map to Query Writer specific
	 * column types (probably INT and VARCHAR).
	 *
	 * @param string $cast cast identifier
	 *
	 * @return integer
	 */
	private function getTypeFromCast( $cast )
	{
		if ( $cast == 'string' ) {
			$typeno = $this->writer->scanType( 'STRING' );
		} elseif ( $cast == 'id' ) {
			$typeno = $this->writer->getTypeForID();
		} elseif ( isset( $this->writer->sqltype_typeno[$cast] ) ) {
			$typeno = $this->writer->sqltype_typeno[$cast];
		} else {
			throw new RedException( 'Invalid Cast' );
		}

		return $typeno;
	}

	/**
	 * Orders the Query Writer to create a table if it does not exist already and
	 * adds a note in the build report about the creation.
	 *
	 * @param OODBBean $bean bean to update report of
	 * @param string         $table table to check and create if not exists
	 *
	 * @return void
	 */
	private function createTableIfNotExists( OODBBean $bean, $table )
	{
		//Does table exist? If not, create
		if ( !$this->tableExists( $this->writer->esc( $table, TRUE ) ) ) {
			$this->writer->createTable( $table );
			$bean->setMeta( 'buildreport.flags.created', TRUE );
		}
	}

	/**
	 * Modifies the table to fit the bean data.
	 * Given a property and a value and the bean, this method will
	 * adjust the table structure to fit the requirements of the property and value.
	 * This may include adding a new column or widening an existing column to hold a larger
	 * or different kind of value. This method employs the writer to adjust the table
	 * structure in the database. Schema updates are recorded in meta properties of the bean.
	 *
	 * This method will also apply indexes, unique constraints and foreign keys.
	 *
	 * @param OODBBean $bean     bean to get cast data from and store meta in
	 * @param string   $property property to store
	 * @param mixed    $value    value to store
	 *
	 * @return void
	 */
	private function modifySchema( OODBBean $bean, $property, $value, &$columns = NULL )
	{
		$doFKStuff = FALSE;
		$table   = $bean->getMeta( 'type' );
		if ($columns === NULL) {
			$columns = $this->writer->getColumns( $table );
		}
		$columnNoQ = $this->writer->esc( $property, TRUE );
		if ( !$this->oodb->isChilled( $bean->getMeta( 'type' ) ) ) {
			if ( $bean->getMeta( "cast.$property", -1 ) !== -1 ) { //check for explicitly specified types
				$cast   = $bean->getMeta( "cast.$property" );
				$typeno = $this->getTypeFromCast( $cast );
			} else {
				$cast   = FALSE;
				$typeno = $this->writer->scanType( $value, TRUE );
			}
			if ( isset( $columns[$this->writer->esc( $property, TRUE )] ) ) { //Is this property represented in the table ?
				if ( !$cast ) { //rescan without taking into account special types >80
					$typeno = $this->writer->scanType( $value, FALSE );
				}
				$sqlt = $this->writer->code( $columns[$this->writer->esc( $property, TRUE )] );
				if ( $typeno > $sqlt ) { //no, we have to widen the database column type
					$this->writer->widenColumn( $table, $property, $typeno );
					$bean->setMeta( 'buildreport.flags.widen', TRUE );
					$doFKStuff = TRUE;
				}
			} else {
				$this->writer->addColumn( $table, $property, $typeno );
				$bean->setMeta( 'buildreport.flags.addcolumn', TRUE );
				$doFKStuff = TRUE;
			}
			if ($doFKStuff) {
				if (strrpos($columnNoQ, '_id')===(strlen($columnNoQ)-3)) {
					$destinationColumnNoQ = substr($columnNoQ, 0, strlen($columnNoQ)-3);
					$indexName = "index_foreignkey_{$table}_{$destinationColumnNoQ}";
					$this->writer->addIndex($table, $indexName, $columnNoQ);
					$typeof = $bean->getMeta("sys.typeof.{$destinationColumnNoQ}", $destinationColumnNoQ);
					$isLink = $bean->getMeta( 'sys.buildcommand.unique', FALSE );
					//Make FK CASCADING if part of exclusive list (dependson=typeof) or if link bean
					$isDep = ( $bean->moveMeta( 'sys.buildcommand.fkdependson' ) === $typeof || is_array( $isLink ) );
					$result = $this->writer->addFK( $table, $typeof, $columnNoQ, 'id', $isDep );
					//If this is a link bean and all unique columns have been added already, then apply unique constraint
					if ( is_array( $isLink ) && !count( array_diff( $isLink, array_keys( $this->writer->getColumns( $table ) ) ) ) ) {
						$this->writer->addUniqueConstraint( $table, $bean->moveMeta('sys.buildcommand.unique') );
						$bean->setMeta("sys.typeof.{$destinationColumnNoQ}", NULL);
					}
				}
			}
		}
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
	 */
	protected function processAdditions( $bean, $ownAdditions )
	{
		$beanType = $bean->getMeta( 'type' );

		foreach ( $ownAdditions as $addition ) {
			if ( $addition instanceof OODBBean ) {

				$myFieldLink = $beanType . '_id';
				$alias = $bean->getMeta( 'sys.alias.' . $addition->getMeta( 'type' ) );
				if ( $alias ) $myFieldLink = $alias . '_id';

				$addition->$myFieldLink = $bean->id;
				$addition->setMeta( 'cast.' . $myFieldLink, 'id' );

				if ($alias) {
					$addition->setMeta( "sys.typeof.{$alias}", $beanType );
				} else {
					$addition->setMeta( "sys.typeof.{$beanType}", $beanType );
				}

				$this->store( $addition );
			} else {
				throw new RedException( 'Array may only contain OODBBeans' );
			}
		}
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
			$this->check( $bean );
			$table = $bean->getMeta( 'type' );
			$this->createTableIfNotExists( $bean, $table );

			$updateValues = array();

			$partial = ( $this->partialBeans === TRUE || ( is_array( $this->partialBeans ) && in_array( $table, $this->partialBeans ) ) );
			if ( $partial ) {
				$mask = $bean->getMeta( 'changelist' );
				$bean->setMeta( 'changelist', array() );
			}

			$columnCache = NULL;
			foreach ( $bean as $property => $value ) {
				if ( $partial && !in_array( $property, $mask ) ) continue;
				if ( $property !== 'id' ) {
					$this->modifySchema( $bean, $property, $value, $columnCache );
				}
				if ( $property !== 'id' ) {
					$updateValues[] = array( 'property' => $property, 'value' => $value );
				}
			}

			$bean->id = $this->writer->updateRecord( $table, $updateValues, $bean->id );
			$bean->setMeta( 'changed', FALSE );
		}
		$bean->setMeta( 'tainted', FALSE );
	}

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
		if ( !$this->writer->sqlStateIn( $exception->getSQLState(),
			array(
				QueryWriter::C_SQLSTATE_NO_SUCH_TABLE,
				QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN ),
				$exception->getDriverDetails() )
		) {
			throw $exception;
		}
	}

	/**
	 * Dispenses a new bean (a OODBBean Bean Object)
	 * of the specified type. Always
	 * use this function to get an empty bean object. Never
	 * instantiate a OODBBean yourself because it needs
	 * to be configured before you can use it with RedBean. This
	 * function applies the appropriate initialization /
	 * configuration for you.
	 *
	 * @param string  $type              type of bean you want to dispense
	 * @param string  $number            number of beans you would like to get
	 * @param boolean $alwaysReturnArray if TRUE always returns the result as an array
	 *
	 * @return OODBBean
	 */
	public function dispense( $type, $number = 1, $alwaysReturnArray = FALSE )
	{
		$OODBBEAN = defined( 'REDBEAN_OODBBEAN_CLASS' ) ? REDBEAN_OODBBEAN_CLASS : '\RedBeanPHP\OODBBean';
		$beans = array();
		for ( $i = 0; $i < $number; $i++ ) {
			$bean = new $OODBBEAN;
			$bean->initializeForDispense( $type, $this->oodb->getBeanHelper() );
			$this->check( $bean );
			$this->oodb->signal( 'dispense', $bean );
			$beans[] = $bean;
		}

		return ( count( $beans ) === 1 && !$alwaysReturnArray ) ? array_pop( $beans ) : $beans;
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
	 */
	public function load( $type, $id )
	{
		$rows = array();
		$bean = $this->dispense( $type );
		if ( isset( $this->stash[$this->nesting][$id] ) ) {
			$row = $this->stash[$this->nesting][$id];
		} else {
			try {
				$rows = $this->writer->queryRecord( $type, array( 'id' => array( $id ) ) );
			} catch ( SQLException $exception ) {
				if (
					$this->writer->sqlStateIn(
						$exception->getSQLState(),
						array(
							QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN,
							QueryWriter::C_SQLSTATE_NO_SUCH_TABLE
						),
						$exception->getDriverDetails()
					)
				) {
					$rows = array();
				} else {
					throw $exception;
				}
			}
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
