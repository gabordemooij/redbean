<?php

/**
 * Adapter Interface
 * @package 		RedBean/Adapter.php
 * @description		Describes the API for a RedBean Database Adapter
 * @author			Gabor de Mooij
 * @license			BSD
 */
interface RedBean_Adapter {

	public function getSQL();
	public function escape( $sqlvalue );
	public function exec( $sql , $aValues=array(), $noevent=false);
	public function get( $sql, $aValues = array() );
	public function getRow( $sql, $aValues = array() );
	public function getCol( $sql, $aValues = array() );
	public function getCell( $sql, $aValues = array() );
	public function getInsertID();
	public function getAffectedRows();
	public function getDatabase();
	public function getErrorMsg();
	public function startTransaction();
	public function commit();
	public function rollback();

}