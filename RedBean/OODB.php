<?php 
/**
 * RedBean OODB (object oriented database)
 * @package 		RedBean/OODB.php
 * @description		Core class for the RedBean ORM pack
 * @author			Gabor de Mooij
 * @license			BSD
 */
class RedBean_OODB {

	/**
	 *
	 * @var float
	 */
	private static $version = 0.6;

	/**
	 *
	 * @var string
	 */
	private static $versioninf = "
		RedBean Object Database layer 
		VERSION 0.6
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
		 * @var string $pkey - a fingerprint for locking
		 */
		public static $pkey = false;

		/**
		 * Indicates that a rollback is required
		 * @var unknown_type
		 */
		private static $rollback = false;
		
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
		 * @var QueryWriter
		 */
		private static $writer;
		
		/**
		 * Closes and unlocks the bean
		 * @return unknown_type
		 */
		public function __destruct() {

			self::$db->exec( 
				self::$writer->getQuery("destruct", array("engine"=>self::$engine,"rollback"=>self::$rollback))
			);
			
			
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
		 * Will perform a rollback at the end of the script
		 * @return unknown_type
		 */
		public static function rollback() {
			self::$rollback = true;
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

					$createtableSQL = self::$writer->getQuery("create_table", array(
						"engine"=>self::$engine,
						"table"=>$table
					));
				
					//get a table for our friend!
					$db->exec( $createtableSQL );
					//jupz, now he has its own table!
					self::addTable( $table );
				}

				//does the table fit?
				 $columnsRaw = self::$writer->getTableColumns($table, $db) ;
					
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
								$changecolumnSQL = self::$writer->getQuery( "widen_column", array(
									"table" => $table,
									"column" => $p,
									"newtype" => self::$writer->typeno_sqltype[$typeno]
								) ); 
								
								$db->exec( $changecolumnSQL );
							}
						}
						else {
							//no it is not
							$addcolumnSQL = self::$writer->getQuery("add_column",array(
								"table"=>$table,
								"column"=>$p,
								"type"=> self::$writer->typeno_sqltype[$typeno]
							));
							
							$db->exec( $addcolumnSQL );
						}
						//Okay, now we are sure that the property value will fit
						$insertvalues[] = $v;
						$insertcolumns[] = $p;
						$updatevalues[] = array( "property"=>$p, "value"=>$v );
					}
				}

			}
			else {
					
				foreach( $bean as $p=>$v) {
					if ($p!="type" && $p!="id") {
						$p = $db->escape($p);
						$v = $db->escape($v);
						$insertvalues[] = $v;
						$insertcolumns[] = $p;
						$updatevalues[] = array( "property"=>$p, "value"=>$v );
					}
				}
					
			}

			//Does the record exist already?
			if ($bean->id) {
				//echo "<hr>Now trying to open bean....";
				self::openBean($bean, true);
				//yes it exists, update it
				if (count($updatevalues)>0) {
					$updateSQL = self::$writer->getQuery("update", array(
						"table"=>$table,
						"updatevalues"=>$updatevalues,
						"id"=>$bean->id
					)); 
					
					//execute the previously build query
					$db->exec( $updateSQL );
				}
			}
			else {
				//no it does not exist, create it
				if (count($insertvalues)>0) {
					
					$insertSQL = self::$writer->getQuery("insert",array(
						"table"=>$table,
						"insertcolumns"=>$insertcolumns,
						"insertvalues"=>$insertvalues
					));
				
				}
				else {
					$insertSQL = self::$writer->getQuery("create", array("table"=>$table)); 
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
			
			$checktypeSQL = self::$writer->getQuery("infertype", array(
				"value"=> self::$db->escape(strval($v))
			));
			
			
			$db->exec( $checktypeSQL );
			$id = $db->getInsertID();
			
			$readtypeSQL = self::$writer->getQuery("readtype",array(
				"id"=>$id
			));
			
			$row=$db->getRow($readtypeSQL);
			
			
			$db->exec( self::$writer->getQuery("reset_dtyp") );
			
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

			if (in_array($sqlType,self::$writer->sqltype_typeno)) {
				$typeno = self::$writer->sqltype_typeno[$sqlType];
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
		public static function init( $querywriter, $dontclose = false ) {

			self::$me = new RedBean_OODB();
			self::$writer = $querywriter;
		

			//prepare database
			if (self::$engine === "innodb") {
				self::$db->exec(self::$writer->getQuery("prepare_innodb"));
				self::$db->exec(self::$writer->getQuery("starttransaction"));
			}
			else if (self::$engine === "myisam"){
				self::$db->exec(self::$writer->getQuery("prepare_myisam"));
			}


			//generate the basic redbean tables
			//Create the RedBean tables we need -- this should only happen once..
			if (!self::$frozen) {
				
				self::$db->exec(self::$writer->getQuery("clear_dtyp"));
					
				self::$db->exec(self::$writer->getQuery("setup_dtyp"));
						
				self::$db->exec(self::$writer->getQuery("setup_locking"));
						
				self::$db->exec(self::$writer->getQuery("setup_tables"));
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
				$alltables = $db->getCol(self::$writer->getQuery("show_tables"));
				return $alltables;
			}
			else {
				$alltables = $db->getCol(self::$writer->getQuery("show_rtables"));
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

			$db->exec(self::$writer->getQuery("register_table",array("table"=>$tablename)));

		}

		/**
		 * UNRegisters a table with RedBean
		 * @param $tablename
		 * @return void
		 */
		public static function dropTable( $tablename ) {

			$db = self::$db;

			$tablename = $db->escape( $tablename );

			$db->exec(self::$writer->getQuery("unregister_table",array("table"=>$tablename)));


		}

		/**
		 * Quick and dirty way to release all locks
		 * @return unknown_type
		 */
		public function releaseAllLocks() {

			self::$db->exec(self::$writer->getQuery("release",array("key"=>self::$pkey)));

		}


		/**
		 * Opens and locks a bean
		 * @param $bean
		 * @return unknown_type
		 */
		public static function openBean( $bean, $mustlock=false) {

			self::checkBean( $bean );
			
			//If locking is turned off, or the bean has no persistance yet (not shared) life is always a success!
			if (!self::$locking || $bean->id === 0) return true;

			$db = self::$db;

			//remove locks that have been expired...
			$removeExpiredSQL = self::$writer->getQuery("remove_expir_lock", array(
				"locktime"=>self::$locktime
			));
			
			$db->exec($removeExpiredSQL);

			$tbl = $db->escape( $bean->type );
			$id = intval( $bean->id );

			//Is the bean already opened for us?
			$checkopenSQL = self::$writer->getQuery("get_lock",array(
				"id"=>$id,
				"table"=>$tbl,
				"key"=>self::$pkey
			));
			
			$row = $db->getRow($checkopenSQL);
			if ($row && is_array($row) && count($row)>0) {
				$updateexpstamp = self::$writer->getQuery("update_expir_lock",array(
					"time"=>time(),
					"id"=>$row["id"]
				));
				$db->exec($updateexpstamp);
				return true; //bean is locked for us!
			}

			//If you must lock a bean then the bean must have been locked by a previous call.
			if ($mustlock) {
				throw new RedBean_Exception_FailedAccessBean("Could not acquire a lock for bean $tbl . $id ");
				return false;
			}

			//try to get acquire lock on the bean
			$openSQL = self::$writer->getQuery("aq_lock", array(
				"table"=>$tbl,
				"id"=>$id,
				"key"=>self::$pkey,
				"time"=>time()
			));
			
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
			$id = abs( intval( $id ) );
			$bean->id = $id;

			//try to open the bean
			self::openBean($bean);

			//load the bean using sql
			if (!$data) {
				
				$getSQL = self::$writer->getQuery("get_bean",array(
					"type"=>$type,
					"id"=>$id
				)); 
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
				$no = $db->getCell( self::$writer->getQuery("bean_exists",array(
					"type"=>$type,
					"id"=>$id
				)) );
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
				$no = $db->getCell( self::$writer->getQuery("count",array(
					"type"=>$type
				)));
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
				$ids = $db->getCol( self::$writer->getQuery("distinct",array(
					"type"=>$type,
					"field"=>$field
				)));
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
				$no = $db->getCell(self::$writer->getQuery("stat",array(
					"stat"=>$stat,
					"field"=>$field,
					"type"=>$type
				)));
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
			$sql = self::$writer->getQuery("releaseall");
			self::$db->exec( $sql );
			return true;
		}

		/**
		 * Fills slots in SQL query
		 * @param $sql
		 * @param $slots
		 * @return unknown_type
		 */
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
				$sql = str_replace( "{".$code.$key."}", self::$writer->getQuote().$db->escape( $value ).self::$writer->getQuote(),$sql ); 
			}
			
			return $sql;
		}
		
		/**
		 * Loads a collection of beans -fast-
		 * @param $type
		 * @param $ids
		 * @return unknown_type
		 */
		public static function fastLoader( $type, $ids ) {
			
			$db = self::$db;
			
			
			$sql = self::$writer->getQuery("fastload", array(
				"type"=>$type,
				"ids"=>$ids
			)); 
			
			return $db->get( $sql );
			
		}
		
		/**
		 * Allows you to fetch an array of beans using plain
		 * old SQL.
		 * @param $rawsql
		 * @param $slots
		 * @param $table
		 * @param $max
		 * @return array $beans
		 */
		public static function getBySQL( $rawsql, $slots, $table, $max=0 ) {
		
			$db = self::$db;
			$sql = $rawsql;
			
			if (is_array($slots)) {
				$sql = self::processQuerySlots( $sql, $slots );
			}
			
			$sql = str_replace('@ifexists:','', $sql);
			$rs = $db->getCol( self::$writer->getQuery("where",array(
				"table"=>$table
			)) . $sql );
			
			$err = $db->getErrorMsg();
			if (!self::$frozen && strpos($err,"Unknown column")!==false && $max<10) {
				$matches = array();
				if (preg_match("/Unknown\scolumn\s'(.*?)'/",$err,$matches)) {
					if (count($matches)==2 && strpos($rawsql,'@ifexists')!==false){
						$rawsql = str_replace('@ifexists:`'.$matches[1].'`','NULL', $rawsql);
						$rawsql = str_replace('@ifexists:'.$matches[1].'','NULL', $rawsql);
						return self::getBySQL( $rawsql, $slots, $table, ++$max);
					}
				}
				return array();
			}
			else {
				if (is_array($rs)) {
					return $rs;
				}
				else {
					return array();
				}
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
 
      $findSQL = self::$writer->getQuery("find",array(
      	"searchoperators"=>$searchoperators,
      	"bean"=>$bean,
      	"start"=>$start,
      	"end"=>$end,
      	"orderby"=>$orderby,
      	"extraSQL"=>$extraSQL,
      	"tbl"=>$tbl
      ));
      
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
 
			$listSQL = self::$writer->getQuery("list",array(
				"type"=>$type,
				"start"=>$start,
				"end"=>$end,
				"orderby"=>$orderby,
				"extraSQL"=>$extraSQL
			));
			
			
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

					$assoccreateSQL = self::$writer->getQuery("create_assoc",array(
						"assoctable"=> $assoctable,
						"t1" =>$t1,
						"t2" =>$t2,
						"engine"=>self::$engine
					));
					
					$db->exec( $assoccreateSQL );
					
					//add a unique constraint
					$db->exec( self::$writer->getQuery("add_assoc",array(
						"assoctable"=> $assoctable,
						"t1" =>$t1,
						"t2" =>$t2
					)) );
					
					self::addTable( $assoctable );
				}
			}
				
			//now insert the association record
			$assocSQL = self::$writer->getQuery("add_assoc_now", array(
				"id1"=>$id1,
				"id2"=>$id2,
				"assoctable"=>$assoctable
			));
			
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
					$unassocSQL = self::$writer->getQuery("unassoc",array(
					"assoctable"=>$assoctable,
					"t1"=>$t2,
					"t2"=>$t1,
					"id1"=>$id1,
					"id2"=>$id2
					));
					//$unassocSQL = "DELETE FROM `$assoctable` WHERE ".$t2."_id = $id1 AND ".$t1."_id = $id2 ";
					$db->exec($unassocSQL);
				}

				//$unassocSQL = "DELETE FROM `$assoctable` WHERE ".$t1."_id = $id1 AND ".$t2."_id = $id2 ";

				$unassocSQL = self::$writer->getQuery("unassoc",array(
					"assoctable"=>$assoctable,
					"t1"=>$t1,
					"t2"=>$t2,
					"id1"=>$id1,
					"id2"=>$id2
				));
				
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
					$unassocSQL = self::$writer->getQuery("untree", array(
						"assoctable2"=>$assoctable2,
						"idx1"=>$idx1,
						"idx2"=>$idx2
					));
					
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
				
				$getassocSQL = self::$writer->getQuery("get_assoc",array(
					"t1"=>$t1,
					"t2"=>$t2,
					"assoctable"=>$assoctable,
					"id"=>$id
				));
				
				
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
			$table = self::$db->escape($bean->type);
			$id = intval($bean->id);
			self::$db->exec( self::$writer->getQuery("trash",array(
				"table"=>$table,
				"id"=>$id
			)) );

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
				
					$db->exec( self::$writer->getQuery("deltree",array(
						"id"=>$id,
						"table"=>$table
					)) );
				}
				else {
					
					$db->exec( self::$writer->getQuery("unassoc_all_t1",array("table"=>$table,"t"=>$t,"id"=>$id)) );
					$db->exec( self::$writer->getQuery("unassoc_all_t2",array("table"=>$table,"t"=>$t,"id"=>$id)) );
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
				$db->exec( self::$writer->getQuery("deltreetype",array(
					"assoctable"=>$assoctable,
					"id"=>$id
				)) );
			}else{
				$db->exec( self::$writer->getQuery("unassoctype1",array(
					"assoctable"=>$assoctable,
					"t1"=>$t1,
					"id"=>$id
				)) );
				$db->exec( self::$writer->getQuery("unassoctype2",array(
					"assoctable"=>$assoctable,
					"t1"=>$t1,
					"id"=>$id
				)) );

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
					$assoccreateSQL = self::$writer->getQuery("create_tree",array(
						"engine"=>self::$engine,
						"assoctable"=>$assoctable
					));
					$db->exec( $assoccreateSQL );
					//add a unique constraint
					$db->exec( self::$writer->getQuery("unique", array(
						"assoctable"=>$assoctable
					)) );
					self::addTable( $assoctable );
				}
			}

			//now insert the association record
			$assocSQL = self::$writer->getQuery("add_child",array(
				"assoctable"=>$assoctable,
				"pid"=>$pid,
				"cid"=>$cid
			));
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
				$getassocSQL = self::$writer->getQuery("get_children", array(
					"assoctable"=>$assoctable,
					"pid"=>$pid
				));
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
				
				$getassocSQL = self::$writer->getQuery("get_parent", array(
					"assoctable"=>$assoctable,
					"cid"=>$cid
				));
					
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
				$unassocSQL = self::$writer->getQuery("remove_child", array(
					"assoctable"=>$assoctable,
					"pid"=>$pid,
					"cid"=>$cid
				));
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
					$sqlCountRelations = self::$writer->getQuery(
						"num_related", array(
							"assoctable"=>$assoctable,
							"t1"=>$t1,
							"id"=>$id
						)
					);
					
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
				$ns = '';
				$names = explode('\\', $c);
				$className = trim(end($names));
				if(count($names) > 1)
				{
					$ns = 'namespace ' . implode('\\', array_slice($names, 0, -1)) . ";\n";
				}
				if ($c!=="" && $c!=="null" && !class_exists($c) && 
								preg_match("/^\s*[A-Za-z_][A-Za-z0-9_]*\s*$/",$className)){ 
					try{
							$toeval = $ns . " class ".$className." extends RedBean_Decorator {
							private static \$__static_property_type = \"".strtolower($className)."\";
							
							public function __construct(\$id=0, \$lock=false) {
								parent::__construct('".strtolower($className)."',\$id,\$lock);
							}
							
							public static function where( \$sql, \$slots=array() ) {
								return new RedBean_Can( self::\$__static_property_type, RedBean_OODB::getBySQL( \$sql, \$slots, self::\$__static_property_type) );
							}
	
							public static function listAll(\$start=false,\$end=false,\$orderby=' id ASC ',\$sql=false) {
								return RedBean_OODB::listAll(self::\$__static_property_type,\$start,\$end,\$orderby,\$sql);
							}
							
						}";
						eval($toeval);	
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

			$tables = $db->getCol( self::$writer->getQuery("show_rtables") );

			foreach($tables as $key=>$table) {
				$tables[$key] = self::$writer->getEscape().$table.self::$writer->getEscape();
			}

			$sqlcleandatabase = self::$writer->getQuery("drop_tables",array(
				"tables"=>$tables
			));

			$db->exec( $sqlcleandatabase );

			$db->exec( self::$writer->getQuery("truncate_rtables") );
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
					$db->exec( self::$writer->getQuery("drop_tables",array("tables"=>array($table))) );
					$db->exec(self::$writer->getQuery("unregister_table",array("table"=>$table)));
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
			
			$db->exec( self::$writer->getQuery("drop_column", array(
				"table"=>$table,
				"property"=>$property
			)) );
			
		}

		/**
	     * Removes all beans of a particular type
	     * @param $type
	     * @return nothing
	     */
	    public static function trashAll($type) {
	        self::$db->exec( self::$writer->getQuery("drop_type",array("type"=>strtolower($type))));
	    }

	    /**
		 * Narrows columns to appropriate size if needed
		 * @return unknown_type
		 */
		public static function keepInShape( $gc = false ,$stdTable=false, $stdCol=false) {
			
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
			if ($stdTable) $table = $stdTable;

			$table = $db->escape( $table );
			//do not remove columns from association tables
			if (strpos($table,'_')!==false) return;
			//table is still in use? But are all columns in use as well?
			
			$cols = self::$writer->getTableColumns( $table, $db );
			
			//$cols = $db->get( self::$writer->getQuery("describe",array(
			//	"table"=>$table
			//)) );
			//pick a random column
			if (count($cols)<1) return;
				$colr = $cols[array_rand( $cols )];
				$col = $db->escape( $colr["Field"] ); //fetch the name and escape
		        if ($stdCol){
				$exists = false;	
				$col = $stdCol; 
				foreach($cols as $cl){
					if ($cl["Field"]==$col) {
						$exists = $cl;
					}
				}
				if (!$exists) {
					return; 
				}
				else {
					$colr = $exists;
				}
			}
			if ($col=="id" || strpos($col,"_id")!==false) {
				return; //special column, cant slim it down
			}
			
			
			//now we have a table and a column $table and $col
			if ($gc && !intval($db->getCell( self::$writer->getQuery("get_null",array(
				"table"=>$table,
				"col"=>$col
			)
			)))) {
				$db->exec( self::$writer->getQuery("drop_column",array("table"=>$table,"property"=>$col)));
				return;	
			}
			
			//okay so this column is still in use, but maybe its to wide
			//get the field type
			//print_r($colr);
			$currenttype =  self::$writer->sqltype_typeno[$colr["Type"]];
			if ($currenttype > 0) {
				$trytype = rand(0,$currenttype - 1); //try a little smaller
				//add a test column
				$db->exec(self::$writer->getQuery("test_column",array(
					"type"=>self::$writer->typeno_sqltype[$trytype],
					"table"=>$table
				)
				));
				//fill the tinier column with the same values of the original column
				$db->exec(self::$writer->getQuery("update_test",array(
					"table"=>$table,
					"col"=>$col
				)));
				//measure the difference
				$delta = $db->getCell(self::$writer->getQuery("measure",array(
					"table"=>$table,
					"col"=>$col
				)));
				if (intval($delta)===0) {
					//no difference? then change the column to save some space
					$sql = self::$writer->getQuery("remove_test",array(
						"table"=>$table,
						"col"=>$col,
						"type"=>self::$writer->typeno_sqltype[$trytype]
					));
					$db->exec($sql);
				}
				//get rid of the test column..
				$db->exec( self::$writer->getQuery("drop_test",array(
					"table"=>$table
				)) );
			}
		
			//Can we put an index on this column?
			//Is this column worth the trouble?
			if (
				strpos($colr["Type"],"TEXT")!==false ||
				strpos($colr["Type"],"LONGTEXT")!==false
			) {
				return;
			}
			
		
			$variance = $db->getCell(self::$writer->getQuery("variance",array(
				"col"=>$col,
				"table"=>$table
			)));
			$records = $db->getCell(self::$writer->getQuery("count",array("type"=>$table)));
			if ($records) {
				$relvar = intval($variance) / intval($records); //how useful would this index be?
				//if this column describes the table well enough it might be used to
				//improve overall performance.
				$indexname = "reddex_".$col;
				if ($records > 1 && $relvar > 0.85) {
					$sqladdindex=self::$writer->getQuery("index1",array(
						"table"=>$table,
						"indexname"=>$indexname,
						"col"=>$col
					));
					$db->exec( $sqladdindex );
				}
				else {
					$sqldropindex = self::$writer->getQuery("index2",array("table"=>$table,"indexname"=>$indexname));
					$db->exec( $sqldropindex );
				}
			}
			
			return true;
		}
	
}


