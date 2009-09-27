<?php 

//For framework intergration if you can specify a class prefix for models
if (!defined("RedBean_Setup_Namespace_PRFX")) define("RedBean_Setup_Namespace_PRFX","");
if (!defined("RedBean_Setup_Namespace_SFFX")) define("RedBean_Setup_Namespace_SFFX","");

/**
 * RedBean Setup
 * Helper class to quickly setup RedBean for you
 * @package 		RedBean/Setup.php
 * @description		Helper class to quickly setup RedBean for you
 * @author			Gabor de Mooij
 * @license			BSD
 */
class RedBean_Setup { 
	
	/**
	 * Kickstarts RedBean :)
	 * @param $dsn
	 * @param $username
	 * @param $password
	 * @param $freeze
	 * @param $engine
	 * @param $debugmode
	 * @param $unlockall
	 * @return unknown_type
	 */
	public static function kickstart( $dsn="mysql:host=localhost;dbname=oodb", 
									  $username='root', 
									  $password='', 
									  $freeze=false, 
  									  $engine="innodb", 
									  $debugmode=false, 
									  $unlockall=false) {
		
		//This is no longer configurable							  		
		if (!class_exists("R")) {
			eval("
				class R extends RedBean_OODB { }
			");
			
			eval("
				class RD extends RedBean_Decorator { }
			");
		}
		
		
		//get an instance of the MySQL database
		if (strpos($dsn,"embmysql")===0) {
			//try to parse emb string
			$dsn .= ';';
			$matches = array();
			preg_match('/host=(.+?);/',$dsn,$matches);
			$matches2 = array();
			preg_match('/dbname=(.+?);/',$dsn,$matches2);
			if (count($matches)==2 && count($matches2)==2) {
				$db = RedBean_Driver_MySQL::getInstance( $matches[1], $username, $password, $matches2[1] );
			}
			else {
				throw new Exception("Could not parse MySQL DSN");
			}
		}
		else{
			$db = new Redbean_Driver_PDO( $dsn, $username, $password, null );
		}
		
		if ($debugmode) {
			$db->setDebugMode(1);
		}
	
		$oldconn = RedBean_OODB::getInstance()->getInstance()->getDatabase();
		$conn = new RedBean_DBAdapter($db);//Wrap ADO in RedBean's adapter
		RedBean_OODB::getInstance()->setDatabase( $conn ); 
		
		
		RedBean_OODB::getInstance()->setEngine($engine); //select a database driver
		RedBean_OODB::getInstance()->init( new QueryWriter_MySQL() ); //Init RedBean
	
		if ($unlockall) {
			
	 
			RedBean_OODB::getInstance()->resetAll(); //Release all locks
		}
	
		if ($freeze) {
			RedBean_OODB::getInstance()->freeze(); //Decide whether to freeze the database
		}
	
		return $oldconn;
	}
	
	/**
	 * Kickstarter for development phase
	 * @param $gen
	 * @param $dsn
	 * @param $username
	 * @param $password
	 * @param $debug
	 * @return unknown_type
	 */
	public static function kickstartDev( $gen, $dsn, $username="root", $password="", $debug=false ) {
		
		//kickstart for development
		self::kickstart( $dsn, $username, $password, false, "innodb", $debug, false);
		
		//generate classes
		RedBean_OODB::getInstance()->gen( $gen );
	}
	
	/**
	 * Kickstarter for deployment phase and testing
	 * @param $dsn
	 * @param $username
	 * @param $password
	 * @param $gen
	 * @return unknown_type
	 */
	public static function kickstartFrozen( $gen, $dsn, $username="root", $password="" ) {
		
		//kickstart for development
		self::kickstart( $dsn, $username, $password, true, "innodb", false, false);
		
		//generate classes
		RedBean_OODB::getInstance()->gen( $gen );
	}
	
	
	public static function reconnect( RedBean_DBAdapter $new ) {
		$old = RedBean_OODB::getInstance()->getInstance()->getDatabase();
		RedBean_OODB::getInstance()->getInstance()->setDatabase( $new );
		return $old;
	}
	
}
