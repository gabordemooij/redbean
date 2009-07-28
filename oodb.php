<?php
//BRNRDPROJECT-REDBEAN - SOURCE CODE

/**

--- welcome to

                   .______.                         
_______   ____   __| _/\_ |__   ____ _____    ____  
\_  __ \_/ __ \ / __ |  | __ \_/ __ \\__  \  /    \ 
 |  | \/\  ___// /_/ |  | \_\ \  ___/ / __ \|   |  \
 |__|    \___  >____ |  |___  /\___  >____  /___|  /
             \/     \/      \/     \/     \/     \/ 



|RedBean Database Objects -
|Written by Gabor de Mooij (c) copyright 2009


|List of Contributors:
|Sean Hess 
|Alan Hogan
|Desfrenes

======================================================
|						       RedBean is Licensed BSD
------------------------------------------------------
|RedBean is a OOP Database Simulation Middleware layer
|for php.
------------------------------------------------------
|Loosely based on an idea by Erik Roelofs - thanks man

VERSION 0.5

======================================================
Official GIT HUB:
git://github.com/buurtnerd/redbean.git
http://github.com/buurtnerd/redbean/tree/master
======================================================



Copyright (c) 2009, G.J.G.T (Gabor) de Mooij
All rights reserved.

a Buurtnerd project


Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:
* Redistributions of source code must retain the above copyright
notice, this list of conditions and the following disclaimer.
* Redistributions in binary form must reproduce the above copyright
notice, this list of conditions and the following disclaimer in the
documentation and/or other materials provided with the distribution.
* Neither the name of the <organization> nor the
names of its contributors may be used to endorse or promote products
derived from this software without specific prior written permission.

All advertising materials mentioning features or use of this software
are encouraged to display the following acknowledgement:
This product is powered by RedBean written by Gabor de Mooij (http://www.redbeanphp.com)


----




THIS SOFTWARE IS PROVIDED BY GABOR DE MOOIJ ''AS IS'' AND ANY
EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL GABOR DE MOOIJ BE LIABLE FOR ANY
DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.



WARNING
THIS IS AN PRE-BETA VERSION, DONT USE THIS CODE ON PRODUCTION SERVERS

*/

/*
@todo: configure your database


=== CONFIGURE YOUR REDBEAN LAYER HERE ===
Put your database configuration here
*/

//Standard configurations, you may override them in your own code before inclusion if
//you don't want to alter these values here.


//For framework intergration if you define $db you can specify a class prefix for models
if (!isset($db)) define("PRFX","");
if (!isset($db)) define("SFFX","");

/**
 * Generic interface for Databases
 */
interface IGenericDatabaseDriver {

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

/**
 * PDO support driver
 * the PDO driver has been written by Desfrenes.
 */
class PDODriver implements IGenericDatabaseDriver {
    private static $instance;
    
    private $debug = false;
    private $pdo;
    private $affected_rows;
    private $rs;
    
    public static function getInstance($dsn, $user, $pass, $dbname)
    {
        if(is_null(self::$instance))
        {
            self::$instance = new PDODriver($dsn, $user, $pass);
        }
        return self::$instance;
    }
    
    public function __construct($dsn, $user, $pass)
    {
        $this->pdo = new PDO(
                $dsn,
                $user,
                $pass,
                array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
                      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC)
            );
    }
    
    public function GetAll( $sql )
    {
    	try{ 
	        if ($this->debug)
	        {
	            echo "<HR>" . $sql;
	        }
	        $rs = $this->pdo->query($sql);
	        $this->rs = $rs;
	        $rows = $rs->fetchAll();
	        if(!$rows)
	        {
	            $rows = array();
	        }
	        
	        if ($this->debug)
	        {
	            if (count($rows) > 0)
	            {
	                echo "<br><b style='color:green'>resultset: " . count($rows) . " rows</b>";
	            }
	            
	            $str = $this->Errormsg();
	            if ($str != "")
	            {
	                echo "<br><b style='color:red'>" . $str . "</b>";
	            }
	        }
    	}
    	catch(Exception $e){ return array(); }
        return $rows;
    }
    
    public function GetCol($sql)
    {
    	try{
	        $rows = $this->GetAll($sql);
	        $cols = array();
	 
	        if ($rows && is_array($rows) && count($rows)>0){
		        foreach ($rows as $row)
		        {
		            $cols[] = array_shift($row);
		        }
	        }
	    	
    	}
    	catch(Exception $e){ return array(); }
        return $cols;
    }
 
    public function GetCell($sql)
    {
    	try{
	        $arr = $this->GetAll($sql);
	        $row1 = array_shift($arr);
	        $col1 = array_shift($row1);
    	}
    	catch(Exception $e){}
        return $col1;
    }
    
    public function GetRow($sql)
    {
    	try{
        	$arr = $this->GetAll($sql);
    	}
       	catch(Exception $e){ return array(); }
        return array_shift($arr);
    }
    
    public function ErrorNo()
    {
    	$infos = $this->pdo->errorInfo();
        return $infos[1];
    }
    public function Errormsg()
    {
        $infos = $this->pdo->errorInfo();
        return $infos[2];
    }
    public function Execute( $sql )
    {
    	try{
	        if ($this->debug)
	        {
	            echo "<HR>" . $sql;
	        }
	        $this->affected_rows = $this->pdo->exec($sql);
	        if ($this->debug)
	        {
	            $str = $this->Errormsg();
	            if ($str != "")
	            {
	                echo "<br><b style='color:red'>" . $str . "</b>";
	            }
	        }
    	}
    	catch(Exception $e){ return 0; }
        return $this->affected_rows;
    }
    public function Escape( $str )
    {
        return substr(substr($this->pdo->quote($str), 1), 0, -1);
    }
    public function GetInsertID()
    {
        return (int) $this->pdo->lastInsertId();
    }
    public function Affected_Rows()
    {
        return (int) $this->affected_rows;
    }
    public function setDebugMode( $tf )
    {
        $this->debug = (bool)$tf;
    }
    public function GetRaw()
    {
        return $this->rs;
    }
}


//Exception for Database problems -- not in use yet...
class ExceptionSQL extends Exception {};

/**
 * Adapter for ADODB database layer AND RedBean
 * @author gabordemooij
 *
 */
class RedBean_DBAdapter {

	/**
	 *
	 * @var ADODB
	 */
	private $db = null;

	public static $log = array();

	/**
	 *
	 * @param $database
	 * @return unknown_type
	 */
	public function __construct($database) {
		$this->db = $database;
	}

	/**
	 * Escapes a string for use in a Query
	 * @param $sqlvalue
	 * @return unknown_type
	 */
	public function escape( $sqlvalue ) {
		return $this->db->Escape($sqlvalue);
	}

	/**
	 * Executes SQL code
	 * @param $sql
	 * @return unknown_type
	 */
	public function exec( $sql ) {
		self::$log[] = $sql;
		return $this->db->Execute( $sql );
	}

	/**
	 * Multi array SQL fetch
	 * @param $sql
	 * @return unknown_type
	 */
	public function get( $sql ) {
		self::$log[] = $sql;
		return $this->db->GetAll( $sql );
	}

	/**
	 * SQL row fetch
	 * @param $sql
	 * @return unknown_type
	 */
	public function getRow( $sql ) {
		self::$log[] = $sql;
		return $this->db->GetRow( $sql );
	}

	/**
	 * SQL column fetch
	 * @param $sql
	 * @return unknown_type
	 */
	public function getCol( $sql ) {
		self::$log[] = $sql;
		return $this->db->GetCol( $sql );
	}

	/**
	 * Retrieves a single cell
	 * @param $sql
	 * @return unknown_type
	 */
	public function getCell( $sql ) {
		self::$log[] = $sql;
		$arr = $this->db->GetCol( $sql );
		if ($arr && is_array($arr))	return ($arr[0]); else return false;
	}

	/**
	 * Returns last inserted id
	 * @return unknown_type
	 */
	public function getInsertID() {
		// self::$log[] = $sql;
		return $this->db->getInsertID();
	}

	/**
	 * Returns number of affected rows
	 * @return unknown_type
	 */
	public function getAffectedRows() {
		// self::$log[] = $sql;
		return $this->db->Affected_Rows();
	}
	
	/**
	 * Unwrap the original database object
	 * @return $database
	 */
	public function getDatabase() {
		return $this->db;
	}
	
	/**
	 * Return latest error message
	 * @return string $message
	 */
	public function getErrorMsg() {
		return $this->db->Errormsg();
	}

}

/**
 * RedBean OODB (object oriented database) Core class for the RedBean ORM pack
 * @author gabordemooij
 *
 */
class RedBean_OODB {

	/**
	 *
	 * @var float
	 */
	private static $version = 0.5;

	/**
	 *
	 * @var string
	 */
	private static $versioninf = "
		RedBean Object Database layer 
		VERSION 0.5
		BY G.J.G.T DE MOOIJ
		LICENSE BSD
		COPYRIGHT 2009
	";

	/**
	 * Indicates how long one can lock an item,
	 * defaults to ten minutes
	 * If a user opens a bean and he or she does not
	 * perform any actions on it others cannot modify the
	 * bean during this time interval.
	 * @var unknown_type
	 */
	private static $locktime = 10;

	/**
	 * a standard adapter for use with RedBean's MYSQL Database wrapper or
	 * ADO library
	 * @var RedBean_DBAdapter
	 */
	public static $db;

	/**
	 * 
	 * @var boolean
	 */
	private static $locking = true;

	/**
	 *
	 * @var array all allowed sql types
	 */
	public static $typeno_sqltype = array(
		" TINYINT(3) UNSIGNED ",
		" INT(11) UNSIGNED ",
		" BIGINT(20) SIGNED ",
		" VARCHAR(255) ",
		" TEXT ",
		" LONGTEXT "
		);

		/**
		 *
		 * @var array all allowed sql types
		 */
		public static $sqltype_typeno = array(
		"tinyint(3) unsigned"=>0,
		"int(11) unsigned"=>1,
		"bigint(20) signed"=>2,
		"varchar(255)"=>3,
		"text"=>4,
		"longtext"=>5
		);

		/**
		 * @var array all dtype types
		 */
		public static $dtypes = array(
		"tintyintus","intus","ints","varchar255","text","ltext"
		);

		/**
		 *
		 * @var string $pkey - a fingerprint for locking
		 */
		public static $pkey = false;

		/**
		 * 
		 * @var RedBean_OODB
		 */
		private static $me = null;

		/**
		 * 
		 * Indicates the current engine
		 * @var string
		 */
		private static $engine = "myisam";

		/**
		 * @var boolean $frozen - indicates whether the db may be adjusted or not
		 */
		private static $frozen = false;

		
		/**
		 * Closes and unlocks the bean
		 * @return unknown_type
		 */
		public function __destruct() {


			//prepare database
			if (self::$engine === "innodb") {
				self::$db->exec("COMMIT");
			}
			else if (self::$engine === "myisam"){
				//nope
			}
			RedBean_OODB::releaseAllLocks();
			
		}

		/**
		 * Returns the version information of this RedBean instance
		 * @return float
		 */
		public static function getVersionInfo() {
			return self::$versioninf;
		}

		/**
		 * Returns the version number of this RedBean instance
		 * @return unknown_type
		 */
		public static function getVersionNumber() {
			return self::$version;
		}

		/**
		 * Toggles Forward Locking
		 * @param $tf
		 * @return unknown_type
		 */
		public static function setLocking( $tf ) {
			self::$locking = $tf;
		}


		/**
		 * Gets the current locking mode (on or off)
		 * @return unknown_type
		 */
		public static function getLocking() {
			return self::$locking;
		}
	
		
		/**
		 * Toggles optimizer
		 * @param $bool
		 * @return unknown_type
		 */
		public static function setOptimizerActive( $bool ) {
			self::$optimizer = (boolean) $bool;
		}
		
		/**
		 * Returns state of the optimizer
		 * @param $bool
		 * @return unknown_type
		 */
		public static function getOptimizerActive() {
			return self::$optimizer;
		}
		
		/**
		 * Checks whether a bean is valid
		 * @param $bean
		 * @return unknown_type
		 */
		public static function checkBean(OODBBean $bean) {

			foreach($bean as $prop=>$value) {
				$prop = preg_replace('/[^abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_]/',"",$prop);
				if (strlen(trim($prop))===0) {
					throw new ExceptionRedBeanSecurity("Invalid Characters in property");
				}
				else {
					
					$bean->$prop = $value;
				}
			}			
			
			//has redBean already been initialized?
			if (!self::$pkey) self::init();

			//Is the bean valid? does the bean have an id?
			if (!isset($bean->id)) {
				throw new Exception("Invalid bean, no id");
			}

			//is the id numeric?
			if (!is_numeric($bean->id)) {
				throw new Exception("Invalid bean, id not numeric");
			}

			//does the bean have a type?
			if (!isset($bean->type)) {
				throw new Exception("Invalid bean, no type");
			}

			//is the beantype correct and valid?
			if (!is_string($bean->type) || is_numeric($bean->type) || strlen($bean->type)<3) {
				throw new Exception("Invalid bean, wrong type");
			}

			//is the beantype legal?
			if ($bean->type==="locking" || $bean->type==="dtyp") {
				throw new Exception("Beantype is reserved table");
			}

			//is the beantype allowed?
			if (strpos($bean->type,"_")!==false && ctype_alnum($bean->type)) {
				throw new Exception("Beantype contains illegal characters");
			}


		}

		/**
		 * same as check bean, but does additional checks for associations
		 * @param $bean
		 * @return unknown_type
		 */
		public static function checkBeanForAssoc( $bean ) {

			//check the bean
			self::checkBean($bean);

			//make sure it has already been saved to the database, else we have no id.
			if (intval($bean->id) < 1) {
				//if it's not saved, save it
				$bean->id = self::set( $bean );
			}

			return $bean;

		}

		/**
		 * Returns the current engine
		 * @return unknown_type
		 */
		public static function getEngine() {
			return self::$engine;
		}

		/**
		 * Sets the current engine
		 * @param $engine
		 * @return unknown_type
		 */
		public static function setEngine( $engine ) {

			if ($engine=="myisam" || $engine=="innodb") {
				self::$engine = $engine;
			}
			else {
				throw new Exception("Unsupported database engine");
			}

			return self::$engine;

		}

		/**
		 * Inserts a bean into the database
		 * @param $bean
		 * @return $id
		 */
		public static function set( OODBBean $bean ) {

			self::checkBean($bean);


			$db = self::$db; //I am lazy, I dont want to waste characters...

		
			$table = $db->escape($bean->type); //what table does it want

			//may we adjust the database?
			if (!self::$frozen) {

				//does this table exist?
				$tables = self::showTables();
					
				if (!in_array($table, $tables)) {

					if (self::$engine=="myisam") {
						//this fellow has no table yet to put his beer on!
						$createtableSQL = "
					 CREATE TABLE `$table` (
					`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
					 PRIMARY KEY ( `id` )
					 ) ENGINE = MYISAM 
					";
					}
					else {
						$createtableSQL = "
					 CREATE TABLE `$table` (
					`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
					 PRIMARY KEY ( `id` )
					 ) ENGINE = InnoDB 
					";
					}
					//get a table for our friend!
					$db->exec( $createtableSQL );
					
					
					//jupz, now he has its own table!
					self::addTable( $table );
				}

				//does the table fit?
				$columnsRaw = $db->get("describe `$table` ");
					
				$columns = array();
				foreach($columnsRaw as $r) {
					$columns[$r["Field"]]=$r["Type"];
				}
					
				$insertvalues = array();
				$insertcolumns = array();
				$updatevalues = array();
					
				foreach( $bean as $p=>$v) {
					if ($p!="type" && $p!="id") {
						$p = $db->escape($p);
						$v = $db->escape($v);
						//What kind of property are we dealing with?
						$typeno = self::inferType($v);
						//Is this property represented in the table?
						if (isset($columns[$p])) {
							//yes it is, does it still fit?
							$sqlt = self::getType($columns[$p]);
							//echo "TYPE = $sqlt .... $typeno ";
							if ($typeno > $sqlt) {
								//no, we have to widen the database column type
								$changecolumnSQL="ALTER TABLE `$table` CHANGE `$p` `$p` ".self::$typeno_sqltype[$typeno];
								$db->exec( $changecolumnSQL );
							}
						}
						else {
							//no it is not
							$addcolumnSQL = "ALTER TABLE `$table` ADD `$p` ".self::$typeno_sqltype[$typeno];
							$db->exec( $addcolumnSQL );
						}
						//Okay, now we are sure that the property value will fit
						$insertvalues[] = "\"".$v."\"";
						$insertcolumns[] = "`".$p."`";
						$updatevalues[] = " `$p`=\"$v\" ";
					}
				}

			}
			else {
					
				foreach( $bean as $p=>$v) {
					if ($p!="type" && $p!="id") {
						$p = $db->escape($p);
						$v = $db->escape($v);
							
						$insertvalues[] = "\"".$v."\"";
						$insertcolumns[] = "`".$p."`";
						$updatevalues[] = " `$p`=\"$v\" ";
					}
				}
					
			}

			//Does the record exist already?
			if ($bean->id) {
				//echo "<hr>Now trying to open bean....";
				self::openBean($bean, true);
				//yes it exists, update it
				if (count($updatevalues)>0) {
					$updateSQL = "UPDATE `$table` SET ".implode(",",$updatevalues)."WHERE id = ".$bean->id;
					//execute the previously build query
					$db->exec( $updateSQL );
				}
			}
			else {
				//no it does not exist, create it
				if (count($insertvalues)>0) {
					$insertSQL = "INSERT INTO `$table` ";
					$insertSQL .= " ( id, ".implode(",",$insertcolumns)." ) ";
					$insertSQL .= " VALUES( null, ".implode(",",$insertvalues)." ) ";
				}
				else {
					$insertSQL = "INSERT INTO `$table` VALUES(null) ";
				}
				//execute the previously build query
				$db->exec( $insertSQL );
				$bean->id = $db->getInsertID();
				self::openBean($bean);
			}

			return $bean->id;
				
		}


		/**
		 * Infers the SQL type of a bean
		 * @param $v
		 * @return $type the SQL type number constant
		 */
		public static function inferType( $v ) {
			$db = self::$db;
			$rawv = $v;
			$v = "'".$db->escape(strval($v))."'";
			$checktypeSQL = "insert into dtyp VALUES(null,$v,$v,$v,$v,$v )";
			$db->exec( $checktypeSQL );
			$id = $db->getInsertID();
			$readtypeSQL = "select tinyintus,intus,ints,varchar255,`text` from dtyp where id=$id";
			$row=$db->getRow($readtypeSQL);
			$db->exec("truncate table dtyp");
			$tp = 0;
			foreach($row as $t=>$tv) {
				if (strval($tv) === strval($rawv)) {
					return $tp;
				}
				$tp++;
			}
			return $tp;
		}

		/**
		 * Returns the RedBean type const for an SQL type
		 * @param $sqlType
		 * @return $typeno
		 */
		public static function getType( $sqlType ) {

			if (in_array($sqlType,self::$sqltype_typeno)) {
				$typeno = self::$sqltype_typeno[$sqlType];
			}
			else {
				$typeno = -1;
			}

			return $typeno;
		}

		/**
		 * Initializes RedBean
		 * @return bool $true
		 */
		public static function init( $dontclose = false) {

			self::$me = new RedBean_OODB();


			//prepare database
			if (self::$engine === "innodb") {
				self::$db->exec("SET autocommit=0");
				self::$db->exec("START TRANSACTION");
			}
			else if (self::$engine === "myisam"){
				self::$db->exec("SET autocommit=1");
			}


			//generate the basic redbean tables
			//Create the RedBean tables we need -- this should only happen once..
			if (!self::$frozen) {
				
				self::$db->exec("drop tables dtyp");
					
				self::$db->exec("
				CREATE TABLE IF NOT EXISTS `dtyp` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `tinyintus` tinyint(3) unsigned NOT NULL,
				  `intus` int(11) unsigned NOT NULL,
				  `ints` bigint(20) NOT NULL,
				  
				  `varchar255` varchar(255) NOT NULL,
				  `text` text NOT NULL,
				  PRIMARY KEY  (`id`)
				) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
				");
						
					self::$db->exec("
				CREATE TABLE IF NOT EXISTS `locking` (
				  `tbl` varchar(255) NOT NULL,
				  `id` bigint(20) NOT NULL,
				  `fingerprint` varchar(255) NOT NULL,
				  `expire` int(11) NOT NULL,
				  UNIQUE KEY `tbl` (`tbl`,`id`)
				) ENGINE=MyISAM DEFAULT CHARSET=latin1;
				");
						
					//rbt
					self::$db->exec("
				 CREATE TABLE IF NOT EXISTS `redbeantables` (
				 `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
				 `tablename` VARCHAR( 255 ) NOT NULL ,
				 PRIMARY KEY ( `id` ),
				 UNIQUE KEY `tablename` (`tablename`)
				 ) ENGINE = MYISAM 
				");
						
					self::$db->exec("
				 CREATE TABLE IF NOT EXISTS `searchindex` (
				`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
				`ind` VARCHAR( 255 ) NOT NULL ,
				`cnt` INT( 11 ) NOT NULL ,
				PRIMARY KEY ( `id` ),
				UNIQUE KEY `ind` (`ind`)
				) ENGINE = MYISAM ");
				
	
			
			
			}
			
			//generate a key
			if (!self::$pkey) {
				self::$pkey = str_replace(".","",microtime(true)."".mt_rand());
			}

			return true;
		}

		/**
		 * Freezes the database so it won't be changed anymore
		 * @return unknown_type
		 */
		public static function freeze() {
			self::$frozen = true;
		}

		/**
		 * UNFreezes the database so it won't be changed anymore
		 * @return unknown_type
		 */
		public static function unfreeze() {
			self::$frozen = false;
		}

		/**
		 * Returns all redbean tables or all tables in the database
		 * @param $all if set to true this function returns all tables instead of just all rb tables
		 * @return array $listoftables
		 */
		public static function showTables( $all=false ) {

			$db = self::$db;

			if ($all && self::$frozen) {
				$alltables = $db->getCol("show tables");
				return $alltables;
			}
			else {
				$alltables = $db->getCol("select tablename from redbeantables");
				return $alltables;
			}

		}

		/**
		 * Registers a table with RedBean
		 * @param $tablename
		 * @return void
		 */
		public static function addTable( $tablename ) {

			$db = self::$db;

			$tablename = $db->escape( $tablename );

			$db->exec("replace into redbeantables values (null, \"$tablename\") ");

		}

		/**
		 * UNRegisters a table with RedBean
		 * @param $tablename
		 * @return void
		 */
		public static function dropTable( $tablename ) {

			$db = self::$db;

			$tablename = $db->escape( $tablename );

			$db->exec("delete from redbeantables where tablename = \"$tablename\" ");


		}

		/**
		 * Quick and dirty way to release all locks
		 * @return unknown_type
		 */
		public function releaseAllLocks() {

			self::$db->exec("DELETE FROM locking WHERE fingerprint=\"".self::$pkey."\" ");

		}


		/**
		 * Opens and locks a bean
		 * @param $bean
		 * @return unknown_type
		 */
		public static function openBean( $bean, $mustlock=false) {

			self::checkBean( $bean );

			//echo "trying to open bean... ".print_r($bean,1);

			//If locking is turned off, or the bean has no persistance yet (not shared) life is always a success!
			if (!self::$locking || $bean->id === 0) return true;

			$db = self::$db;

			//remove locks that have been expired...
			$removeExpiredSQL = "DELETE FROM locking WHERE expire < ".(time()-self::$locktime);
			$db->exec($removeExpiredSQL);

			$tbl = $db->escape( $bean->type );
			$id = intval( $bean->id );

			//Is the bean already opened for us?
			$checkopenSQL = "SELECT id FROM locking WHERE id=$id AND  tbl=\"$tbl\" AND fingerprint=\"".self::$pkey."\" ";
			$row = $db->getRow($checkopenSQL);
			if ($row && is_array($row) && count($row)>0) {
				$updateexpstamp = "UPDATE locking SET expire=".time()." WHERE id =".$row["id"];
				return true; //bean is locked for us!
			}

			//If you must lock a bean then the bean must have been locked by a previous call.
			if ($mustlock) {
				throw new ExceptionFailedAccessBean("Could not acquire a lock for bean $tbl . $id ");
				return false;
			}

			//try to get acquire lock on the bean
			$openSQL = "INSERT INTO locking VALUES(\"$tbl\",$id,\"".self::$pkey."\",\"".time()."\") ";
			$trials = 0;
			$aff = 0;
			while( $aff < 1 && $trials < 5 ) {
				$db->exec($openSQL);
				$aff = $db->getAffectedRows();
				$trials++;
				if ($aff < 1) usleep(500000); //half a sec
			}

			if ($trials > 4) {
				return false;
			}
			else {
				return true;
			}
		}

		/**
		 * For internal use, synchronizes a block of code
		 * @param $toggle
		 * @return unknown_type
		 */
		private static function sync( $toggle ) {

			$bean = RedBean_OODB::dispense("_syncmethod");
			$bean->id = 0;

			if ($toggle) {
				self::openBean( $bean );
			}
			else {
				self::closeBean( $bean );
			}
		}

		/**
		 * Gets a bean by its primary ID
		 * @param $type
		 * @param $id
		 * @return OODBBean $bean
		 */
		public static function getById($type, $id, $data=false) {

			$bean = self::dispense( $type );
			$db = self::$db;
			$table = $db->escape( $type );
			$id = intval( $id );
			$bean->id = $id;

			//try to open the bean
			self::openBean($bean);

			//load the bean using sql
			if (!$data) {
				$getSQL = "SELECT * FROM `$type` WHERE id = $id ";
				$row = $db->getRow( $getSQL );
			}
			else {
				$row = $data;
			}
			
			if ($row && is_array($row) && count($row)>0) {
				foreach($row as $p=>$v) {
					//populate the bean with the database row
					$bean->$p = $v;
				}
			}
			else {
				throw new ExceptionFailedAccessBean("bean not found");
			}

			return $bean;

		}

		/**
		 * Checks whether a type-id combination exists
		 * @param $type
		 * @param $id
		 * @return unknown_type
		 */
		public static function exists($type,$id) {

			$db = self::$db;
			$id = intval( $id );
			$type = $db->escape( $type );

			//$alltables = $db->getCol("show tables");
			$alltables = self::showTables();

			if (!in_array($type, $alltables)) {
				return false;
			}
			else {
				$no = $db->getCell("select count(*) from `$type` where id=$id");
				if (intval($no)) {
					return true;
				}
				else {
					return false;
				}
			}
		}

		/**
		 * Counts occurences of  a bean
		 * @param $type
		 * @return integer $i
		 */
		public static function numberof($type) {

			$db = self::$db;
			$type = strtolower( $db->escape( $type ) );

			$alltables = self::showTables();

			if (!in_array($type, $alltables)) {
				return 0;
			}
			else {
				$no = $db->getCell("select count(*) from `$type`");
				return $no;
			}
		}
		
		/**
		 * Gets all beans of $type, grouped by $field.
		 *
		 * @param String Object type e.g. "user" (lowercase!)
		 * @param String Field/parameter e.g. "zip"
		 * @return Array list of beans with distinct values of $field. Uses GROUP BY
		 * @author Alan J. Hogan
		 **/
		static function distinct($type, $field)
		{
			//TODO: Consider if GROUP BY (equivalent meaning) is more portable 
			//across DB types?
			$db = self::$db;
			$type = strtolower( $db->escape( $type ) );
			$field = $db->escape( $field );
		
			$alltables = self::showTables();

			if (!in_array($type, $alltables)) {
				return array();
			}
			else {
				$ids = $db->getCol("SELECT id FROM `$type` GROUP BY $field");
				$beans = array();
				if (is_array($ids) && count($ids)>0) {
					foreach( $ids as $id ) {
						$beans[ $id ] = self::getById( $type, $id , false);
					}
				}
				return $beans;
			}
		}

		/**
		 * Simple statistic
		 * @param $type
		 * @param $field
		 * @return integer $i
		 */
		private static function stat($type,$field,$stat="sum") {

			$db = self::$db;
			$type = strtolower( $db->escape( $type ) );
			$field = strtolower( $db->escape( $field ) );
			$stat = $db->escape( $stat );

			$alltables = self::showTables();

			if (!in_array($type, $alltables)) {
				return 0;
			}
			else {
				$no = $db->getCell("select $stat(`$field`) from `$type`");
				return $no;
			}
		}

		/**
		 * Sum
		 * @param $type
		 * @param $field
		 * @return float $i
		 */
		public static function sumof($type,$field) {
			return self::stat( $type, $field, "sum");
		}

		/**
		 * AVG
		 * @param $type
		 * @param $field
		 * @return float $i
		 */
		public static function avgof($type,$field) {
			return self::stat( $type, $field, "avg");
		}

		/**
		 * minimum
		 * @param $type
		 * @param $field
		 * @return float $i
		 */
		public static function minof($type,$field) {
			return self::stat( $type, $field, "min");
		}

		/**
		 * maximum
		 * @param $type
		 * @param $field
		 * @return float $i
		 */
		public static function maxof($type,$field) {
			return self::stat( $type, $field, "max");
		}


		/**
		 * Unlocks everything
		 * @return unknown_type
		 */
		public static function resetAll() {
			$sql = "TRUNCATE locking";
			self::$db->exec( $sql );
			return true;
		}

	
		public static function processQuerySlots($sql, $slots) {
			
			$db = self::$db;
			
			//Just a funny code to identify slots based on randomness
			$code = sha1(rand(1,1000)*time());
			
			//This ensures no one can hack our queries via SQL template injection
			foreach( $slots as $key=>$value ) {
				$sql = str_replace( "{".$key."}", "{".$code.$key."}" ,$sql ); 
			}
			
			//replace the slots inside the SQL template
			foreach( $slots as $key=>$value ) {
				$sql = str_replace( "{".$code.$key."}", "\"".$db->escape( $value )."\"",$sql ); 
			}
			
			return $sql;
		}
		
		public static function fastLoader( $type, $ids ) {
			
			$db = self::$db;
			$sql = "SELECT * FROM `$type` WHERE id IN ( ".implode(",", $ids)." ) ORDER BY FIELD(id,".implode(",", $ids).") ASC
			";
			return $db->get( $sql );
			
		}
		
		public static function getBySQL( $rawsql, $slots, $table ) {
		
			$db = self::$db;
			$sql = $rawsql;
			
			if (is_array($slots)) {
				$sql = self::processQuerySlots( $sql, $slots );
			}
			
			$rs = $db->getCol( "select `$table`.id from $table where " . $sql );
			
			if (is_array($rs)) {
				return $rs;
			}
			else {
				return array();
			}
		}
		
		
     /** 
     * Finds a bean using search parameters
     * @param $bean
     * @param $searchoperators
     * @param $start
     * @param $end
     * @param $orderby
     * @return unknown_type
     */
    public static function find(OODBBean $bean, $searchoperators = array(), $start=0, $end=100, $orderby="id ASC", $extraSQL=false) {
 
      self::checkBean( $bean );
      $db = self::$db;
      $tbl = $db->escape( $bean->type );
 
      $findSQL = "SELECT id FROM `$tbl` WHERE ";
      
      
      foreach($bean as $p=>$v) {
        if ($p === "type" || $p === "id") continue;
        $p = $db->escape($p);
        $v = $db->escape($v);
        if (isset($searchoperators[$p])) {
 
          if ($searchoperators[$p]==="LIKE") {
            $part[] = " `$p`LIKE \"%$v%\" ";
          }
          else {
            $part[] = " `$p` ".$searchoperators[$p]." \"$v\" ";
          }
        }
        else {
 
        }
      }
 
      if ($extraSQL) {
        $findSQL .= @implode(" AND ",$part) . $extraSQL;
      }
      else {
        $findSQL .= @implode(" AND ",$part) . " ORDER BY $orderby LIMIT $start, $end ";
      }
 
      
      $ids = $db->getCol( $findSQL );
      $beans = array();
 
      if (is_array($ids) && count($ids)>0) {
          foreach( $ids as $id ) {
            $beans[ $id ] = self::getById( $bean->type, $id , false);
        }
      }
      
      return $beans;
      
    }
		
    
		/**
		 * Returns a plain and simple array filled with record data
		 * @param $type
		 * @param $start
		 * @param $end
		 * @param $orderby
		 * @return unknown_type
		 */
		public static function listAll($type, $start=false, $end=false, $orderby="id ASC", $extraSQL = false) {
 
			$db = self::$db;
 
			if ($extraSQL) {
 
				$listSQL = "SELECT * FROM ".$db->escape($type)." ".$extraSQL;
 
			}
			else {
 
				$listSQL = "SELECT * FROM ".$db->escape($type)."
					ORDER BY ".$orderby;
 
					if ($end !== false && $start===false) {
						$listSQL .= " LIMIT ".intval($end);
					}
 
					if ($start !== false && $end !== false) {
						$listSQL .= " LIMIT ".intval($start).", ".intval($end);
					}
 
					if ($start !== false && $end===false) {
						$listSQL .= " LIMIT ".intval($start).", 18446744073709551615 ";
					}
 
 
 
			}
 
			return $db->get( $listSQL );
 
		}
		

		/**
		 * Associates two beans
		 * @param $bean1
		 * @param $bean2
		 * @return unknown_type
		 */
		public static function associate( OODBBean $bean1, OODBBean $bean2 ) { //@associate

			//get a database
			$db = self::$db;

			//first we check the beans whether they are valid
			$bean1 = self::checkBeanForAssoc($bean1);
			$bean2 = self::checkBeanForAssoc($bean2);

			self::openBean( $bean1, true );
			self::openBean( $bean2, true );

			//sort the beans
			$tp1 = $bean1->type;
			$tp2 = $bean2->type;
			if ($tp1==$tp2){
				$arr = array( 0=>$bean1, 1 =>$bean2 );
			}
			else {
				$arr = array( $tp1=>$bean1, $tp2 =>$bean2 );
			}
			ksort($arr);
			$bean1 = array_shift( $arr );
			$bean2 = array_shift( $arr );

			$id1 = intval($bean1->id);
			$id2 = intval($bean2->id);

			//infer the association table
			$tables = array();
			array_push( $tables, $db->escape( $bean1->type ) );
			array_push( $tables, $db->escape( $bean2->type ) );
			//sort the table names to make sure we only get one assoc table
			sort($tables);
			$assoctable = $db->escape( implode("_",$tables) );

			//check whether this assoctable already exists
			if (!self::$frozen) {
				$alltables = self::showTables();
				if (!in_array($assoctable, $alltables)) {
					//no assoc table does not exist, create it..
					$t1 = $tables[0];
					$t2 = $tables[1];

					if ($t1==$t2) {
						$t2.="2";
					}

					$assoccreateSQL = "
				 CREATE TABLE `$assoctable` (
				`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
				`".$t1."_id` INT( 11 ) UNSIGNED NOT NULL,
				`".$t2."_id` INT( 11 ) UNSIGNED NOT NULL,
				 PRIMARY KEY ( `id` )
				 ) ENGINE = ".self::$engine."; 
				";
					$db->exec( $assoccreateSQL );
					//add a unique constraint
					$db->exec( "ALTER TABLE `$assoctable` ADD UNIQUE INDEX `u_$assoctable` (`".$t1."_id`, `".$t2."_id` ) " );
					self::addTable( $assoctable );
				}
			}
				
			//now insert the association record
			$assocSQL = "REPLACE INTO `$assoctable` VALUES(null,$id1,$id2) ";
			$db->exec( $assocSQL );
				

		}

		/**
		 * Breaks the association between a pair of beans
		 * @param $bean1
		 * @param $bean2
		 * @return unknown_type
		 */
		public static function unassociate(OODBBean $bean1, OODBBean $bean2) {

			//get a database
			$db = self::$db;

			//first we check the beans whether they are valid
			$bean1 = self::checkBeanForAssoc($bean1);
			$bean2 = self::checkBeanForAssoc($bean2);


			self::openBean( $bean1, true );
			self::openBean( $bean2, true );


			$idx1 = intval($bean1->id);
			$idx2 = intval($bean2->id);

			//sort the beans
			$tp1 = $bean1->type;
			$tp2 = $bean2->type;

			if ($tp1==$tp2){
				$arr = array( 0=>$bean1, 1 =>$bean2 );
			}
			else {
				$arr = array( $tp1=>$bean1, $tp2 =>$bean2 );
			}
				
			ksort($arr);
			$bean1 = array_shift( $arr );
			$bean2 = array_shift( $arr );
				
			$id1 = intval($bean1->id);
			$id2 = intval($bean2->id);
				
			//infer the association table
			$tables = array();
			array_push( $tables, $db->escape( $bean1->type ) );
			array_push( $tables, $db->escape( $bean2->type ) );
			//sort the table names to make sure we only get one assoc table
			sort($tables);
				
				
			$assoctable = $db->escape( implode("_",$tables) );
				
			//check whether this assoctable already exists
			$alltables = self::showTables();
				
			if (in_array($assoctable, $alltables)) {
				$t1 = $tables[0];
				$t2 = $tables[1];
				if ($t1==$t2) {
					$t2.="2";
					$unassocSQL = "DELETE FROM `$assoctable` WHERE ".$t2."_id = $id1 AND ".$t1."_id = $id2 ";
					$db->exec($unassocSQL);
				}

				$unassocSQL = "DELETE FROM `$assoctable` WHERE ".$t1."_id = $id1 AND ".$t2."_id = $id2 ";

				$db->exec($unassocSQL);
			}
			if ($tp1==$tp2) {
				$assoctable2 = "pc_".$db->escape( $bean1->type )."_".$db->escape( $bean1->type );
				//echo $assoctable2;
				//check whether this assoctable already exists
				$alltables = self::showTables();
				if (in_array($assoctable2, $alltables)) {

					//$id1 = intval($bean1->id);
					//$id2 = intval($bean2->id);
					$unassocSQL = "DELETE FROM `$assoctable2` WHERE
				(parent_id = $idx1 AND child_id = $idx2) OR
				(parent_id = $idx2 AND child_id = $idx1) ";
					$db->exec($unassocSQL);
				}
			}
		}

		/**
		 * Fetches all beans of type $targettype assoiciated with $bean
		 * @param $bean
		 * @param $targettype
		 * @return array $beans
		 */
		public static function getAssoc(OODBBean $bean, $targettype) {
			//get a database
			$db = self::$db;
			//first we check the beans whether they are valid
			$bean = self::checkBeanForAssoc($bean);

			$id = intval($bean->id);


			//obtain the table names
			$t1 = $db->escape( strtolower($bean->type) );
			$t2 = $db->escape( $targettype );

			//infer the association table
			$tables = array();
			array_push( $tables, $t1 );
			array_push( $tables, $t2 );
			//sort the table names to make sure we only get one assoc table
			sort($tables);
			$assoctable = $db->escape( implode("_",$tables) );

			//check whether this assoctable exists
			$alltables = self::showTables();
				
			if (!in_array($assoctable, $alltables)) {
				return array(); //nope, so no associations...!
			}
			else {
				if ($t1==$t2) {
					$t2.="2";
				}
				$getassocSQL = "SELECT `".$t2."_id` FROM `$assoctable` WHERE `".$t1."_id` = $id ";
				$rows = $db->getCol( $getassocSQL );
				$beans = array();
				if ($rows && is_array($rows) && count($rows)>0) {
					foreach($rows as $i) {
						$beans[$i] = self::getById( $targettype, $i, false);
					}
				}
				return $beans;
			}


		}


		/**
		 * Removes a bean from the database and breaks associations if required
		 * @param $bean
		 * @return unknown_type
		 */
		public static function trash( OODBBean $bean ) {

			self::checkBean( $bean );
			if (intval($bean->id)===0) return;
			self::deleteAllAssoc( $bean );
			self::openBean($bean);
			self::$db->exec( "DELETE FROM ".self::$db->escape($bean->type)." WHERE id = ".intval($bean->id) );

		}
			
		/**
		 * Breaks all associations of a perticular bean $bean
		 * @param $bean
		 * @return unknown_type
		 */
		public static function deleteAllAssoc( $bean ) {

			$db = self::$db;
			$bean = self::checkBeanForAssoc($bean);

			self::openBean( $bean, true );


			$id = intval( $bean->id );

			//get all tables
			$alltables = self::showTables();

			//are there any possible associations?
			$t = $db->escape($bean->type);
			$checktables = array();
			foreach( $alltables as $table ) {
				if (strpos($table,$t."_")!==false || strpos($table,"_".$t)!==false){
					$checktables[] = $table;
				}
			}

			//remove every possible association
			foreach($checktables as $table) {
				if (strpos($table,"pc_")===0){
					$db->exec("DELETE FROM $table WHERE parent_id = $id OR child_id = $id ");
				}
				else {
					$db->exec("DELETE FROM $table WHERE ".$t."_id = $id ");
					$db->exec("DELETE FROM $table WHERE ".$t."2_id = $id ");
				}
					
					
			}
			return true;
		}

		/**
		 * Breaks all associations of a perticular bean $bean
		 * @param $bean
		 * @return unknown_type
		 */
		public static function deleteAllAssocType( $targettype, $bean ) {

			$db = self::$db;
			$bean = self::checkBeanForAssoc($bean);
			self::openBean( $bean, true );

			$id = intval( $bean->id );

			//obtain the table names
			$t1 = $db->escape( strtolower($bean->type) );
			$t2 = $db->escape( $targettype );

			//infer the association table
			$tables = array();
			array_push( $tables, $t1 );
			array_push( $tables, $t2 );
			//sort the table names to make sure we only get one assoc table
			sort($tables);
			$assoctable = $db->escape( implode("_",$tables) );

			if (strpos($assoctable,"pc_")===0){
				$db->exec("DELETE FROM $assoctable WHERE parent_id = $id  OR child_id = $id ");
			}else{
				$db->exec("DELETE FROM $assoctable WHERE ".$t1."_id = $id ");
				$db->exec("DELETE FROM $assoctable WHERE ".$t1."2_id = $id ");

			}

			return true;
		}


		/**
		 * Dispenses; creates a new OODB bean of type $type
		 * @param $type
		 * @return OODBBean $bean
		 */
		public static function dispense( $type="StandardBean" ) {

			$oBean = new OODBBean();
			$oBean->type = $type;
			$oBean->id = 0;
			return $oBean;
		}


		/**
		 * Adds a child bean to a parent bean
		 * @param $parent
		 * @param $child
		 * @return unknown_type
		 */
		public static function addChild( OODBBean $parent, OODBBean $child ) {

			//get a database
			$db = self::$db;

			//first we check the beans whether they are valid
			$parent = self::checkBeanForAssoc($parent);
			$child = self::checkBeanForAssoc($child);

			self::openBean( $parent, true );
			self::openBean( $child, true );


			//are parent and child of the same type?
			if ($parent->type !== $child->type) {
				throw new ExceptionInvalidParentChildCombination();
			}

			$pid = intval($parent->id);
			$cid = intval($child->id);

			//infer the association table
			$assoctable = "pc_".$db->escape($parent->type."_".$parent->type);

			//check whether this assoctable already exists
			if (!self::$frozen) {
				$alltables = self::showTables();
				if (!in_array($assoctable, $alltables)) {
					//no assoc table does not exist, create it..
					$assoccreateSQL = "
				 CREATE TABLE `$assoctable` (
				`id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
				`parent_id` INT( 11 ) UNSIGNED NOT NULL,
				`child_id` INT( 11 ) UNSIGNED NOT NULL,
				 PRIMARY KEY ( `id` )
				 ) ENGINE = ".self::$engine."; 
				";
					$db->exec( $assoccreateSQL );
					//add a unique constraint
					$db->exec( "ALTER TABLE `$assoctable` ADD UNIQUE INDEX `u_$assoctable` (`parent_id`, `child_id` ) " );
					self::addTable( $assoctable );
				}
			}

			//now insert the association record
			$assocSQL = "REPLACE INTO `$assoctable` VALUES(null,$pid,$cid) ";
			$db->exec( $assocSQL );

		}

		/**
		 * Returns all child beans of parent bean $parent
		 * @param $parent
		 * @return array $beans
		 */
		public static function getChildren( OODBBean $parent ) {

			//get a database
			$db = self::$db;

			//first we check the beans whether they are valid
			$parent = self::checkBeanForAssoc($parent);

			$pid = intval($parent->id);

			//infer the association table
			$assoctable = "pc_".$db->escape( $parent->type . "_" . $parent->type );

			//check whether this assoctable exists
			$alltables = self::showTables();
			if (!in_array($assoctable, $alltables)) {
				return array(); //nope, so no children...!
			}
			else {
				$targettype = $parent->type;
				$getassocSQL = "SELECT `child_id` FROM `$assoctable` WHERE `parent_id` = $pid ";
				$rows = $db->getCol( $getassocSQL );
				$beans = array();
				if ($rows && is_array($rows) && count($rows)>0) {
					foreach($rows as $i) {
						$beans[$i] = self::getById( $targettype, $i, false);
					}
				}
				return $beans;
			}

		}

		/**
		 * Fetches the parent bean of child bean $child
		 * @param $child
		 * @return OODBBean $parent
		 */
		public static function getParent( OODBBean $child ) {

				
			//get a database
			$db = self::$db;

			//first we check the beans whether they are valid
			$child = self::checkBeanForAssoc($child);

			$cid = intval($child->id);

			//infer the association table
			$assoctable = "pc_".$db->escape( $child->type . "_" . $child->type );
			//check whether this assoctable exists
			$alltables = self::showTables();
			if (!in_array($assoctable, $alltables)) {
				return array(); //nope, so no children...!
			}
			else {
				$targettype = $child->type;
				$getassocSQL = "SELECT `parent_id` FROM `$assoctable` WHERE `child_id` = $cid ";
					
				$rows = $db->getCol( $getassocSQL );
				$beans = array();
				if ($rows && is_array($rows) && count($rows)>0) {
					foreach($rows as $i) {
						$beans[$i] = self::getById( $targettype, $i, false);
					}
				}
					
				return $beans;
			}

		}

		/**
		 * Removes a child bean from a parent-child association
		 * @param $parent
		 * @param $child
		 * @return unknown_type
		 */
		public static function removeChild(OODBBean $parent, OODBBean $child) {

			//get a database
			$db = self::$db;

			//first we check the beans whether they are valid
			$parent = self::checkBeanForAssoc($parent);
			$child = self::checkBeanForAssoc($child);

			self::openBean( $parent, true );
			self::openBean( $child, true );


			//are parent and child of the same type?
			if ($parent->type !== $child->type) {
				throw new ExceptionInvalidParentChildCombination();
			}

			//infer the association table
			$assoctable = "pc_".$db->escape( $parent->type . "_" . $parent->type );

			//check whether this assoctable already exists
			$alltables = self::showTables();
			if (!in_array($assoctable, $alltables)) {
				return true; //no association? then nothing to do!
			}
			else {
				$pid = intval($parent->id);
				$cid = intval($child->id);
				$unassocSQL = "DELETE FROM `$assoctable` WHERE
				( parent_id = $pid AND child_id = $cid ) ";
				$db->exec($unassocSQL);
			}
		}
		
		/**
		 * Counts the associations between a type and a bean
		 * @param $type
		 * @param $bean
		 * @return integer $numberOfRelations
		 */
		public static function numofRelated( $type, OODBBean $bean ) {
			
			//get a database
			$db = self::$db;
			
			$t2 = strtolower( $db->escape( $type ) );
						
			//is this bean valid?
			self::checkBean( $bean );
			$t1 = strtolower( $bean->type  );
			$tref = strtolower( $db->escape( $bean->type ) );
			$id = intval( $bean->id );
						
			//infer the association table
			$tables = array();
			array_push( $tables, $t1 );
			array_push( $tables, $t2 );
			
			//sort the table names to make sure we only get one assoc table
			sort($tables);
			$assoctable = $db->escape( implode("_",$tables) );
			
			//get all tables
			$tables = self::showTables();
			
			if ($tables && is_array($tables) && count($tables) > 0) {
				if (in_array( $t1, $tables ) && in_array($t2, $tables)){
					$sqlCountRelations = "
						SELECT COUNT(1) 
						FROM `$assoctable` WHERE 
						".$t1."_id = $id
					";
					
					return (int) $db->getCell( $sqlCountRelations );
				}
			}
			else {
				return 0;
			}
		}
		
		/**
		 * Accepts a comma separated list of class names and
		 * creates a default model for each classname mentioned in
		 * this list. Note that you should not gen() classes
		 * for which you already created a model (by inheriting
		 * from ReadBean_Decorator).
		 * @param string $classes
		 * @return unknown_type
		 */
		public static function gen( $classes ) {
			$classes = explode(",",$classes);
			foreach($classes as $c) {
				if ($c!=="" && $c!=="null" && !class_exists($c) && preg_match("/^\s*[A-Za-z_][A-Za-z0-9_]*\s*$/",$c)){
					try{
						eval("class ".$c." extends RedBean_Decorator {
							private static \$__static_property_type = \"".strtolower($c)."\";
							
							public function __construct(\$id=0, \$lock=false) {
								parent::__construct('".strtolower($c)."',\$id,\$lock);
							}
							
							//no late static binding... great..
							public static function where( \$sql, \$slots=array() ) {
								return new RedBean_Can( self::\$__static_property_type, RedBean_OODB::getBySQL( \$sql, \$slots, self::\$__static_property_type) );
							}
	
							public static function listAll(\$start=false,\$end=false,\$orderby=' id ASC ',\$sql=false) {
								return RedBean_OODB::listAll(self::\$__static_property_type,\$start,\$end,\$orderby,\$sql);
							}
							
					}");
							
						if (!class_exists($c)) return false;
					}
					catch(Exception $e){
						return false;
					}
				}
				else {
					return false;
				}
			}
			return true;
		}


		/**
		 * Changes the locktime, this time indicated how long
		 * a user can lock a bean in the database.
		 * @param $timeInSecs
		 * @return unknown_type
		 */
		public static function setLockingTime( $timeInSecs ) {

			if (is_int($timeInSecs) && $timeInSecs >= 0) {
				self::$locktime = $timeInSecs;
			}
			else {
				throw new ExceptionInvalidArgument( "time must be integer >= 0" );
			}
		}


		
		/**
		 * Cleans the entire redbean database, this will not affect
		 * tables that are not managed by redbean.
		 * @return unknown_type
		 */
		public static function clean() {

			if (self::$frozen) {
				return false;
			}

			$db = self::$db;

			$tables = $db->getCol("select tablename from redbeantables");

			foreach($tables as $key=>$table) {
				$tables[$key] = "`".$table."`";
			}

			$sqlcleandatabase = "drop tables ".implode(",",$tables);

			$db->exec( $sqlcleandatabase );

			$db->exec( "truncate redbeantables" );
			self::resetAll();
			return true;

		}
		
	
		/**
		 * Removes all tables from redbean that have
		 * no classes
		 * @return unknown_type
		 */
		public static function removeUnused( ) {

			//oops, we are frozen, so no change..
			if (self::$frozen) {
				return false;
			}

			//get a database
			$db = self::$db;

			//get all tables
			$tables = self::showTables();
			
			foreach($tables as $table) {
				
				//does the class exist?
				$classname = PRFX . $table . SFFX;
				if(!class_exists( $classname , true)) {
					$db->exec("DROP TABLE `$table`;");
					$db->exec("DELETE FROM redbeantables WHERE tablename=\"$table\"");
				} 
				
			}
			
		}
		/**
		 * Drops a specific column
		 * @param $table
		 * @param $property
		 * @return unknown_type
		 */
		public static function dropColumn( $table, $property ) {
			
			//oops, we are frozen, so no change..
			if (self::$frozen) {
				return false;
			}

			//get a database
			$db = self::$db;
			
			$db->exec("ALTER TABLE `$table` DROP `$property`");
			
		}
		
	
		
		/**
		 * Narrows columns to appropriate size if needed
		 * @return unknown_type
		 */
		public static function keepInShape( $gc = false) {
			
			//oops, we are frozen, so no change..
			if (self::$frozen) {
				return false;
			}

			//get a database
			$db = self::$db;

			//get all tables
			$tables = self::showTables();

			//pick a random table
			if ($tables && is_array($tables) && count($tables) > 0) {
				if ($gc) self::removeUnused( $tables );
				$table = $tables[array_rand( $tables, 1 )];
			}
			else {
				return; //or return if there are no tables (yet)
			}

			$table = $db->escape( $table );
			//do not remove columns from association tables
			if (strpos($table,'_')!==false) return;
			//table is still in use? But are all columns in use as well?
			$cols = $db->get("describe `$table`");
			//pick a random column
			$colr = $cols[array_rand( $cols )];
			$col = $db->escape( $colr["Field"] ); //fetch the name and escape
			if ($col=="id" || strpos($col,"_id")!==false) {
				return; //special column, cant slim it down
			}
			
			//now we have a table and a column $table and $col
			
			//okay so this column is still in use, but maybe its to wide
			//get the field type
			$currenttype =  self::$sqltype_typeno[$colr["Type"]];
			if ($currenttype > 0) {
				$trytype = rand(0,$currenttype - 1); //try a little smaller
				//add a test column
				$db->exec("alter table `$table` add __test  ".self::$typeno_sqltype[$trytype]);
				//fill the tinier column with the same values of the original column
				$db->exec("update `$table` set __test=`$col`");
				//measure the difference
				$delta = $db->getCell("select count(*) as df from `$table` where
				strcmp(`$col`,__test) != 0 AND `$col` IS NOT NULL");
				if (intval($delta)===0) {
					//no difference? then change the column to save some space
					$sql = "alter table `$table` change `$col` `$col` ".self::$typeno_sqltype[$trytype];
					$db->exec($sql);
				}
				//get rid of the test column..
				$db->exec("alter table `$table` drop __test");
			}
		
			//Can we put an index on this column?
			//Is this column worth the trouble?
			if (
				strpos($colr["Type"],"TEXT")!==false ||
				strpos($colr["Type"],"LONGTEXT")!==false
			) {
				return;
			}
			
		
			$variance = $db->getCell("select count( distinct $col ) from $table");
			$records = $db->getCell("select count(*) from $table");
			if ($records) {
				$relvar = intval($variance) / intval($records); //how useful would this index be?
				//if this column describes the table well enough it might be used to
				//improve overall performance.
				$indexname = "reddex_".$col;
				if ($records > 1 && $relvar > 0.85) {
					$sqladdindex="ALTER IGNORE TABLE `$table` ADD INDEX $indexname (`$col`)";
					$db->exec( $sqladdindex );
				}
				else {
					$sqldropindex = "ALTER IGNORE TABLE `$table` DROP INDEX $indexname";
					$db->exec( $sqldropindex );
				}
			}
			
			return true;
		}
	
}






/**
 * RedBean decorator class
 * @desc   this class provides additional ORM functionality and defauly accessors
 * @author gabordemooij
 */
class RedBean_Decorator implements IteratorAggregate {

	/**
	 *
	 * @var OODBBean
	 */
	protected $data = null;

	/**
	 *
	 * @var string
	 */
	protected $type = "";

	/**
	 * @var array
	 */

	protected $problems = array();



	/**
	 * Constructor, loads directly from main table
	 * @param $type
	 * @param $id
	 * @return unknown_type
	 */
	public function __construct( $type=false, $id=0, $lock=false ) {

		$id = floatval( $id );
		if (!$type) {
			throw new Exception("Undefined bean type");
		}
		else {
			$this->type = preg_replace( "[\W_]","", strtolower($type));
			//echo $this->type;
			if ($id > 0) { //if the id is higher than 0 load data
				$this->data = RedBean_OODB::getById( $this->type, $id, $lock );
			}
			else { //otherwise, dispense a regular empty OODBBean
				$this->data = RedBean_OODB::dispense( $this->type );
			}
		}
	}
	
	public function getIterator()
	{
		$o = new ArrayObject($this->data);
		return $o->getIterator();
	}

	/**
	 * Free memory of a class, drop column in db
	 * @param $property
	 * @return unknown_type
	 */
	public function free( $property ) {
		RedBean_OODB::dropColumn( $this->type, $property );
	}
	
	/**

	* Quick service to copy post values to properties
	* @param $selection
	* @return unknown_type
	*/
	public function importFromPost( $selection=null ) {
		
		if (!$selection) {
			$selection = array_keys($_POST);
		}
		
		if (is_string($selection)) {
			$selection = explode(",",$selection);
		}
		
		if ($selection && is_array($selection) && count($selection) > 0) {
			foreach( $selection as $field ) { 
				$setter = "set".ucfirst( $field );  
				if (isset( $_POST[$field] )) {
					$resp = $this->$setter( $_POST[ $field ]  );
					if ($resp !== true) {
						$this->problems[$field] = $resp;
					}
				}
	
			}
	
			if (count($this->problems)===0) {
				return true;
			}
			else {
				return false;
			}
		}
		

	}

	/**
	 * Imports an array or object
	 * If this function returns boolean true, no problems
	 * have occurred during the import and all values have been copies
	 * succesfully. 
	 * @param $arr or $obj
	 * @return boolean $anyproblems
	 */
	public function import( $arr ) {
		
		foreach( $arr as $key=>$val ) {
			$setter = "set".ucfirst( $key );
			$resp = $this->$setter( $val );
			if ($resp !== true) {
				$this->problems[$key] = $resp;
			}
		}


		if (count($this->problems)===0) {
			return true;
		}
		else {
			return false;
		}

	}

	/**
	 * Returns a list filled with possible problems
	 * that occurred while populating the model
	 * @return unknown_type
	 */
	public function problems() {
		return $this->problems;
	}

	/**
	 * Magic method call, this provides basic accessor functionalities
	 */
	public function __call( $method, $arguments ) {
		return $this->command( $method, $arguments );
	}
	
	/**
	 * Magic getter. Another way to handle accessors
	 */
	public function __get( $name ) {
		$name = strtolower( $name );
		return isset($this->data->$name) ? $this->data->$name : null;
	}
	
	/**
	 * Magic setter. Another way to handle accessors
	 */
	public function __set( $name, $value ) {
		$name = strtolower( $name );
		$this->data->$name = $value;
	}

	/**
	 * To perform a command normally handles by the magic method __call
	 * use this one. This makes it easy to overwrite a method like set
	 * and then route its result to the original method
	 * @param $method
	 * @param $arguments
	 * @return unknown_type
	 */
	public function command( $method, $arguments ) {
		if (strpos( $method,"set" ) === 0) {
			$prop = substr( $method, 3 );
			$this->$prop = $arguments[0];
			return false;
				
		}
		elseif (strpos($method,"getRelated")===0)	{
			$prop = strtolower( substr( $method, 10 ) );
			$beans = RedBean_OODB::getAssoc( $this->data, $prop );
			$decos = array();
			$dclass = PRFX.$prop.SFFX;
				
			if ($beans && is_array($beans)) {
				foreach($beans as $b) {
					$d = new $dclass();
					$d->setData( $b );
					$decos[] = $d;
				}
			}
			return $decos;
		}
		elseif (strpos( $method, "get" ) === 0) {
			$prop = substr( $method, 3 );
			return $this->$prop;
		}
		elseif (strpos( $method, "is" ) === 0) {
			$prop = strtolower( substr( $method, 2 ) );
			return ($this->data->$prop ? TRUE : FALSE);
		}
		else if (strpos($method,"add") === 0) { //@add
			$deco = $arguments[0];
			$bean = $deco->getData();
			RedBean_OODB::associate($this->data, $bean);
			return $this;
		}
		else if (strpos($method,"remove")===0) {
			$deco = $arguments[0];
			$bean = $deco->getData();
			RedBean_OODB::unassociate($this->data, $bean);
			return $this;
		}
		else if (strpos($method,"attach")===0) {
			$deco = $arguments[0];
			$bean = $deco->getData();
			RedBean_OODB::addChild($this->data, $bean);
			return $this;
		}
		else if (strpos($method,"clearRelated")===0) {
			$type = strtolower( substr( $method, 12 ) );
			RedBean_OODB::deleteAllAssocType($type, $this->data);
			return $this;
		}
		else if (strpos($method,"numof")===0) {
			$type = strtolower( substr( $method, 5 ) );
			return RedBean_OODB::numOfRelated($type, $this->data);
			
		}
	}
	
	/**
	 * Enforces an n-to-1 relationship
	 * @param $deco
	 * @return unknown_type
	 */
	public function belongsTo( $deco ) {
		RedBean_OODB::deleteAllAssocType($deco->getType(), $this->data);
		RedBean_OODB::associate($this->data, $deco->getData());
	}
	
	/**
	 * Enforces an 1-to-n relationship
	 * @param $deco
	 * @return unknown_type
	 */
	public function exclusiveAdd( $deco ) {
		RedBean_OODB::deleteAllAssocType($this->type,$deco->getData());
		RedBean_OODB::associate($deco->getData(), $this->data);
	}
	
	/**
	 * Returns the parent object of the current object if any
	 * @return RedBean_Decorator $oBean
	 */
	public function parent() {
		$beans = RedBean_OODB::getParent( $this->data );
		if (count($beans) > 0 ) $bean = array_pop($beans); else return null;
		$dclass = PRFX.$this->type.SFFX;
		$deco = new $dclass();
		$deco->setData( $bean );
		return $deco;
	}
	
	/**
	 * Returns array of siblings (objects with the same parent except the object itself)
	 * @return array $aObjects
	 */
	public function siblings() { 
		$beans = RedBean_OODB::getParent( $this->data );
		if (count($beans) > 0 ) {
			$bean = array_pop($beans);	
		} 
		else { 
			return null;	
		}
		$beans = RedBean_OODB::getChildren( $bean );
		$decos = array();
		$dclass = PRFX.$this->type.SFFX;
		if ($beans && is_array($beans)) {
			foreach($beans as $b) {
				if ($b->id != $this->data->id) {
					$d = new $dclass();
					$d->setData( $b );
					$decos[] = $d;
				}
			}
		}
		return $decos;
	}
	
	/**
	 * Returns array of child objects 
	 * @return array $aObjects
	 */
	public function children() {
		$beans = RedBean_OODB::getChildren( $this->data );
		$decos = array();
		$dclass = PRFX.$this->type.SFFX;
		if ($beans && is_array($beans)) {
			foreach($beans as $b) {
				$d = new $dclass();
				$d->setData( $b );
				$decos[] = $d;
			}
		}
		return $decos;
	}
	
	/**
	 * Returns whether a node has a certain parent in its ancestry
	 * @param $deco
	 * @return boolean $found
	 */
	public function hasParent( $deco ) {
		$me = $this;
		while( $parent = $me->parent() ) {
			if ($deco->getID() == $parent->getID()) {
				return true;
			}
			else {
				$me = $parent;
			}
		}
		return false;
	}
	
	/**
	 * Searches children of a specific tree node for the target child
	 * @param $deco
	 * @return boolean $found
	 */
	public function hasChild( $deco ) {
		
		$nodes = array($this);
		while($node = array_shift($nodes)) {
			//echo "<br>checking ".$node->getID();
			//echo "<br>equals?... ".$deco->getID();
			if ($node->getID() == $deco->getID() && 
				($node->getID() != $this->getID())) {
					return true;	
				}
			//echo "<br> no.. get children.. ";
			if ($children = $node->children()) {
				$nodes = array_merge($nodes, $children);
				//echo "<br>new array: ".count($nodes);
			}
		}
		return false;
		
	}
	
	/**
	 * Searches if a node has the specified sibling
	 * @param $deco
	 * @return boolean $found
	 */
	public function hasSibling( $deco ) {
		$siblings = $this->siblings();
		foreach( $siblings as $sibling ) {
			if ($sibling->getID() == $deco->getID()) { 
				return true;	
			}
		}
		return false;
	}
	
	/**
	 * This function simply copies the model and returns it
	 * @return RedBean_Decorator $oRD
	 */
	public function copy() {
		$clone = new self( $this->type, 0 );
		$clone->setData( $this->getData() );
		return $clone;
	}
	
	/**
	 * Clears all associations
	 * @return unknown_type
	 */
	public function clearAllRelations() {
		RedBean_OODB::deleteAllAssoc( $this->getData() );
	}

	/**
	 * Gets data directly
	 * @return OODBBean
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * Sets data directly
	 * @param $data
	 * @return void
	 */
	public function setData( $data ) {
		$this->data = $data;
	}

	/**
	 * Inserts or updates the bean
	 * Returns the ID
	 * @return unknown_type
	 */
	public function save() {
		return RedBean_OODB::set( $this->data );
	}

	/**
	 * Deletes the bean
	 * @param $deco
	 * @return unknown_type
	 */
	public static function delete( $deco ) {
		RedBean_OODB::trash( $deco->getData() );
	}


	/**
	 * Explicitly forward-locks a decorated bean
	 * @return unknown_type
	 */
	public function lock() {
		RedBean_OODB::openBean($this->getData());
	}

	/**
	 * Explicitly unlocks a decorated bean
	 * @return unknown_type
	 */
	public function unlock() {
		RedBean_OODB::closeBean( $this->getData());
	}

	
	

	/**
	 * Closes and unlocks the bean
	 * @param $deco
	 * @return unknown_type
	 */
	public static function close( $deco ) {
		RedBean_OODB::closeBean( $deco->getData() );
	}

	/**
	 * Creates a redbean decorator for a specified type
	 * @param $type
	 * @param $id
	 * @return unknown_type
	 */
	public static function make( $type="", $id ){
		return new RedBean_Decorator( $type, $id );
	}


	/**
	 * Exports a bean to a view
	 * @param $bean
	 * @return unknown_type
	 */
	public function exportTo( &$bean, $overridebean=false ) {


		foreach($this->data as $prop=>$value) {
				
			//what value should we use?
			if (is_object($overridebean) && isset($overridebean->$prop)) {
				$value = $overridebean->$prop;
			}
			elseif (is_array($overridebean) && isset($overridebean[$prop])) {
				$value = $overridebean[$prop];
			}
				
			if (is_object($value)){
				$value = $value->getID();
			}
				
			if (is_object($bean)) {
				$bean->$prop = $value;
			}
			elseif (is_array($bean)) {
				$bean[$prop] = $value;
			}
		}
		
		return $bean;
	}


	/**
	 * Exports a bean as an array
	 * @param $bean
	 * @return array $arr
	 */
	public function exportAsArr() {
		$arr = array();
		foreach($this->data as $prop=>$value) {
			if ($value instanceof RedBean_Decorator){
				$value = $value->getID();
			}
			$arr[ $prop ] = $value;
		
		}
		return  $arr;
	}
	
  /** 
   * Finds another decorator
   * @param $deco
   * @param $filter
   * @return array $decorators
   */
  public static function find( $deco, $filter, $start=0, $end=100, $orderby=" id ASC ", $extraSQL=false ) {
 
    if (!is_array($filter)) {
      return array();
    }
 
    if (count($filter)<1) {
      return array();
    }
 
    //make all keys of the filter lowercase
    $filters = array();
    foreach($filter as $key=>$f) {
      $filters[strtolower($key)] =$f;
        
      if (!in_array($f,array("=","!=","<",">","<=",">=","like","LIKE"))) {
        throw new ExceptionInvalidFindOperator();
      }
        
    }
 
    $beans = RedBean_OODB::find( $deco->getData(), $filters, $start, $end, $orderby, $extraSQL );
    
    
    
    $decos = array();
    $dclass = PRFX.$deco->type.SFFX;
    foreach( $beans as $bean ) {
      $decos[ $bean->id ] = new $dclass( floatval( $bean->id ) );
      $decos[ $bean->id ]->setData( $bean );
    }
    return $decos;
  }
	
	
}


/**
 * RedBean Can
 * @desc   a Can contains beans and acts as n iterator, it also enables you to work with
 * 		   large collections while remaining light-weight
 * @author gabordemooij
 */
class RedBean_Can implements Iterator ,  ArrayAccess , SeekableIterator , Countable {
	
	/**
	 * 
	 * @var array
	 */
	private $collectionIDs = null;
	
	/**
	 * 
	 * @var string
	 */
	private $type = null;
	
	/**
	 * 
	 * @var int
	 */
	private $pointer = 0;
	
	/**
	 * 
	 * @var int
	 */
	private $num = 0;
	
	/**
	 * Constructor
	 * @param $type
	 * @param $collection
	 * @return RedBean_Can $instance
	 */
	public function __construct( $type="", $collection = array() ) {
		
		$this->collectionIDs = $collection;
		$this->type = $type;
		$this->num = count( $this->collectionIDs );
	}
	
	/**
	 * Wraps an OODBBean in a RedBean_Decorator
	 * @param OODBBean $bean
	 * @return RedBean_Decorator $deco
	 */
	public function wrap( $bean ) {

		$dclass = PRFX.$this->type.SFFX;
		$deco = new $dclass( floatval( $bean->id ) );
		$deco->setData( $bean );
		return $deco;
		
	}
	
	/**
	 * Returns the number of beans in this can
	 * @return int $num
	 */
	public function count() {
		
		return $this->num;
	
	}
	
	/**
	 * Returns all the beans inside this can
	 * @return array $beans
	 */
	public function getBeans() {

		$rows = RedBean_OODB::fastloader( $this->type, $this->collectionIDs );
		
		$beans = array();
		
		if (is_array($rows)) {
			foreach( $rows as $row ) {
				//Use the fastloader for optimal performance (takes row as data)
				$beans[] = $this->wrap( RedBean_OODB::getById( $this->type, $id , $row) );
			}
		}

		return $beans;
	}
	
	public function slice( $begin=0, $end=0 ) {
		$this->collectionIDs = array_slice( $this->collectionIDs, $begin, $end);
		$this->num = count( $this->collectionIDs );
	} 
	
	/**
	 * Returns the current bean
	 * @return RedBean_Decorator $bean
	 */
	public function current() {
		if (isset($this->collectionIDs[$this->pointer])) {
			$id = $this->collectionIDs[$this->pointer];
			return $this->wrap( RedBean_OODB::getById( $this->type, $id ) );
		}
		else {
			return null;
		}
	}
	
	/**
	 * Returns the key of the current bean
	 * @return int $key
	 */
	public function key() {
		return $this->pointer;
	}
	
	
	/**
	 * Advances the internal pointer to the next bean in the can
	 * @return int $pointer
	 */
	public function next() {
		return ++$this->pointer;	
	}
	
	/**
	 * Sets the internal pointer to the previous bean in the can
	 * @return int $pointer
	 */
	public function prev() {
		if ($this->pointer > 0) {
			return ++$this->pointer;
		}else {
			return 0;
		}
	}
	
	/**
	 * Resets the internal pointer to the first bean in the can
	 * @return void
	 */
	public function rewind() {
		$this->pointer=0;
		return 0;	
	}
	
	/**
	 * Sets the internal pointer to the specified key in the can
	 * @param int $seek
	 * @return void
	 */
	public function seek( $seek ) {
		$this->pointer = (int) $seek;
	}
	
	/**
	 * Checks there are any more beans in this can
	 * @return boolean $morebeans
	 */
	public function valid() {
		return ($this->num > ($this->pointer+1));
	}
	
	/**
	 * Checks there are any more beans in this can
	 * Same as valid() but this method has a more descriptive name
	 * @return boolean $morebeans
	 */
	public function hasMoreBeans() {
		return $this->valid();
	}
	
	/**
	 * Makes it possible to use this object as an array
	 * Sets the offset
	 * @param $offset
	 * @param $value
	 * @return void
	 */
	public function offsetSet($offset, $value) {
        $this->collectionIDs[$offset] = $value;
    }
    
    /**
	 * Makes it possible to use this object as an array
	 * Checks the offset
	 * @param $offset
	 * @return boolean $isset
	 */
	public function offsetExists($offset) {
        return isset($this->collectionIDs[$offset]);
    }
    
    /**
	 * Makes it possible to use this object as an array
	 * Unsets the value at offset
	 * @param $offset
	 * @return void
	 */
    public function offsetUnset($offset) {
        unset($this->collectionIDs[$offset]);
    }
    
    /**
	 * Makes it possible to use this object as an array
	 * Gets the bean at a given offset
	 * @param $offset
	 * @return RedBean_Decorator $bean
	 */
	public function offsetGet($offset) {
    	
    	if (isset($this->collectionIDs[$offset])) {
			$id = $this->collectionIDs[$offset];
			return $this->wrap( RedBean_OODB::getById( $this->type, $id ) );
		}
		else {
			return null;
		}
      
    }
	
	
}


/**
 * Object Oriented Database Bean class
 * Empty Type definition for bean processing
 *
 */
class OODBBean {
}


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
		$db = PDODriver::getInstance( $dsn, $username, $password, null ); 
		
			
	
		if ($debugmode) {
			$db->setDebugMode(1);
		}
	
		RedBean_OODB::$db = new RedBean_DBAdapter($db); //Wrap ADO in RedBean's adapter
		RedBean_OODB::setEngine($engine); //select a database driver
		RedBean_OODB::init(); //Init RedBean
	
		if ($unlockall) {
			RedBean_OODB::resetAll(); //Release all locks
		}
	
		if ($freeze) {
			RedBean_OODB::freeze(); //Decide whether to freeze the database
		}
	}
}

//Define some handy exceptions

//Exception for locking mechanism
class ExceptionFailedAccessBean extends Exception{}

//Exception for invalid parent-child associations (type mismatch)
class ExceptionInvalidParentChildCombination extends Exception{}

//Exception for invalid argument
class ExceptionInvalidArgument extends Exception {}

//Exception for security issues 
class ExceptionRedBeanSecurity extends Exception {}
