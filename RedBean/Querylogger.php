<?php
class Redbean_Querylogger
{
 
	/**
	 * Logs a piece of SQL code
	 * @param $sql
	 * @return void
	 */
	public static function logSCQuery( $sql )
    {
		$sql = addslashes($sql);
		$db = Redbean_OODB::$db;
		$db->exec("INSERT INTO auditsql (id,`sql`) VALUES(null,\"$sql\")");
		return null;
	}
 
}