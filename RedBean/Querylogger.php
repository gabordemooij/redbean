<?php
class Redbean_Querylogger implements RedBean_Observer
{
 
	/**
	 * 
	 * @var string
	 */
	private $path = "";
	
	/**
	 * 
	 * @var integer
	 */
	private $userid = 0;
	
	private function getFilename() {
		return $this->path . "audit_".date("m_d_y").".log";
	}
	
	/**
	 * Logs a piece of SQL code
	 * @param $sql
	 * @return void
	 */
	public function logSCQuery( $sql, $db )
    {
		$sql = addslashes($sql);
		$line = "\n".date("H:i:s")."|".$_SERVER["REMOTE_ADDR"]."|UID=".$this->userid."|".$sql;  
		file_put_contents( $this->getFilename(), $line, FILE_APPEND );
		return null;
	}
	
	/**
	 * Inits the logger
	 * @param $path
	 * @param $userid
	 * @return unknown_type
	 */
	public static function init($path="",$userid=0) {
		
		$logger = new self;
		$logger->userid = $userid;
		$logger->path = $path;
		if (!file_exists($logger->getFilename())) {
			file_put_contents($logger->getFilename(),"begin logging");	
		}
		
		RedBean_OODB::$db->addEventListener( "sql_exec", $logger );
	
	}
	
	/**
	 * (non-PHPdoc)
	 * @see RedBean/RedBean_Observer#onEvent()
	 */
	public function onEvent( $event, RedBean_Observable $db ) {
		
		$this->logSCQuery( $db->getSQL(), $db );
	}
	
 
}