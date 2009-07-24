<?php 


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
					throw new RedBean_Exception_Security("Invalid Characters in property");
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
				throw new RedBean_Exception_FailedAccessBean("Could not acquire a lock for bean $tbl . $id ");
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
				throw new RedBean_Exception_FailedAccessBean("bean not found");
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
				throw new RedBean_Exception_InvalidParentChildCombination();
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
				throw new RedBean_Exception_InvalidParentChildCombination();
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
						eval("final class ".$c." extends RedBean_Decorator {
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
				throw new RedBean_Exception_InvalidArgument( "time must be integer >= 0" );
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


