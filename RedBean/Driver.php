<?php 
/**
 * Generic interface for Databases
 */
interface RedBean_Driver {

	public static function getInstance( $host, $user, $pass, $dbname );

	public function GetAll( $sql );

	public function GetCol( $sql );

	public function GetCell( $sql );

	public function GetRow( $sql );

	public function ErrorNo();

	public function Errormsg();

	public function Execute( $sql );

	public function Escape( $str );

	public function GetInsertID();

	public function Affected_Rows();

	public function setDebugMode( $tf );

	public function GetRaw();

}
