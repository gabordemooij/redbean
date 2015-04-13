<?php

namespace RedBeanPHP\Adapter;

use RedBeanPHP\Observable as Observable;
use RedBeanPHP\AdaptorInterface as AdaptorInterface;
use RedBeanPHP\DriverInterface as DriverInterface;

/**
 * DBAdapter (Database Adapter)
 *
 * An adapter class to connect various database systems to RedBean
 * Database Adapter Class. The task of the database adapter class is to
 * communicate with the database driver. You can use all sorts of database
 * drivers with RedBeanPHP. The default database drivers that ships with
 * the RedBeanPHP library is the RPDO driver ( which uses the PHP Data Objects
 * Architecture aka PDO ).
 *
 * @file    RedBeanPHP/Adapter/DBAdapter.php
 * @author  Gabor de Mooij and the RedBeanPHP Community.
 * @license BSD/GPLv2
 *
 * @copyright
 * (c) copyright G.J.G.T. (Gabor) de Mooij and the RedBeanPHP community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class DBAdapter extends Observable implements AdaptorInterface
{
	/**
	 * @var DriverInterface
	 */
	private $db = NULL;

	/**
	 * @var string
	 */
	private $sql = '';

	/**
	 * Constructor.
	 *
	 * Creates an instance of the RedBean Adapter Class.
	 * This class provides an interface for RedBean to work
	 * with ADO compatible DB instances.
	 *
	 * @param DriverInterface $database ADO Compatible DB Instance
	 */
	public function __construct( $database )
	{
		$this->db = $database;
	}

	/**
	 * @see AdaptorInterface::getSQL
	 */
	public function getSQL()
	{
		return $this->sql;
	}

	/**
	 * @see AdaptorInterface::exec
	 */
	public function exec( $sql, $bindings = array(), $noevent = FALSE )
	{
		if ( !$noevent ) {
			$this->sql = $sql;
			$this->signal( 'sql_exec', $this );
		}

		return $this->db->Execute( $sql, $bindings );
	}

	/**
	 * @see AdaptorInterface::get
	 */
	public function get( $sql, $bindings = array() )
	{
		$this->sql = $sql;
		$this->signal( 'sql_exec', $this );

		return $this->db->GetAll( $sql, $bindings );
	}

	/**
	 * @see AdaptorInterface::getRow
	 */
	public function getRow( $sql, $bindings = array() )
	{
		$this->sql = $sql;
		$this->signal( 'sql_exec', $this );

		return $this->db->GetRow( $sql, $bindings );
	}

	/**
	 * @see AdaptorInterface::getCol
	 */
	public function getCol( $sql, $bindings = array() )
	{
		$this->sql = $sql;
		$this->signal( 'sql_exec', $this );

		return $this->db->GetCol( $sql, $bindings );
	}

	/**
	 * @see AdaptorInterface::getAssoc
	 */
	public function getAssoc( $sql, $bindings = array() )
	{
		$this->sql = $sql;

		$this->signal( 'sql_exec', $this );

		$rows  = $this->db->GetAll( $sql, $bindings );

		$assoc = array();
		if ( !$rows ) {
			return $assoc;
		}

		foreach ( $rows as $row ) {
			if ( empty( $row ) ) continue;

			if ( count( $row ) > 2 ) {
            $key   = array_shift( $row );
            $value = $row;
        } elseif ( count( $row ) > 1 ) {
				$key   = array_shift( $row );
				$value = array_shift( $row );
			} else {
				$key   = array_shift( $row );
				$value = $key;
			}

			$assoc[$key] = $value;
		}

		return $assoc;
	}
	
	/**
	 * @see AdaptorInterface::getAssocRow
	 */
	public function getAssocRow($sql, $bindings = array())
	{
		$this->sql = $sql;
		$this->signal( 'sql_exec', $this );

		return $this->db->GetAssocRow( $sql, $bindings );
	}

	/**
	 * @see AdaptorInterface::getCell
	 */
	public function getCell( $sql, $bindings = array(), $noSignal = NULL )
	{
		$this->sql = $sql;

		if ( !$noSignal ) $this->signal( 'sql_exec', $this );

		return $this->db->GetOne( $sql, $bindings );
	}

	/**
	 * @see AdaptorInterface::getCursor
	 */
	public function getCursor( $sql, $bindings = array() )
	{
		return $this->db->GetCursor( $sql, $bindings );
	}

	/**
	 * @see AdaptorInterface::getInsertID
	 */
	public function getInsertID()
	{
		return $this->db->getInsertID();
	}

	/**
	 * @see AdaptorInterface::getAffectedRows
	 */
	public function getAffectedRows()
	{
		return $this->db->Affected_Rows();
	}

	/**
	 * @see AdaptorInterface::getDatabase
	 */
	public function getDatabase()
	{
		return $this->db;
	}

	/**
	 * @see AdaptorInterface::startTransaction
	 */
	public function startTransaction()
	{
		$this->db->StartTrans();
	}

	/**
	 * @see AdaptorInterface::commit
	 */
	public function commit()
	{
		$this->db->CommitTrans();
	}

	/**
	 * @see AdaptorInterface::rollback
	 */
	public function rollback()
	{
		$this->db->FailTrans();
	}

	/**
	 * @see AdaptorInterface::close.
	 */
	public function close()
	{
		$this->db->close();
	}
}
