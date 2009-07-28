<?php 

//For framework intergration if you define $db you can specify a class prefix for models
if (!isset($db)) define("PRFX","");
if (!isset($db)) define("SFFX","");


/**
 * RedBean Setup
 * Helper class to quickly setup RedBean for you
 * @author gabordemooij
 *
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
									  $unlockall=false ) {
		
		//This is no longer configurable							  		
		eval("
			class R extends RedBean_OODB { }
		");
		
		eval("
			class RD extends RedBean_Decorator { }
		");
		

		//get an instance of the MySQL database
		$db = Redbean_Driver_PDO::getInstance( $dsn, $username, $password, null ); 
		
			
	
		if ($debugmode) {
			$db->setDebugMode(1);
		}
	
		RedBean_OODB::$db = new RedBean_DBAdapter($db); //Wrap ADO in RedBean's adapter
		RedBean_OODB::setEngine($engine); //select a database driver
		RedBean_OODB::init( new QueryWriter_MySQL() ); //Init RedBean
	
		if ($unlockall) {
			RedBean_OODB::resetAll(); //Release all locks
		}
	
		if ($freeze) {
			RedBean_OODB::freeze(); //Decide whether to freeze the database
		}
	}
	
}
