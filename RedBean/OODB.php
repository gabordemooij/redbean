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
	 * Indicates how long one can lock an item,
	 * defaults to ten minutes
	 * If a user opens a bean and he or she does not
	 * perform any actions on it others cannot modify the
	 * bean during this time interval.
	 * @var unknown_type
	 */
	private $locktime = 10;

	/**
	 * a standard adapter for use with RedBean's MYSQL Database wrapper or
	 * ADO library
	 * @var RedBean_DBAdapter
	 */
	private $db;

	/**
	 * 
	 * @var boolean
	 */
	private $locking = true;



		/**
		 *
		 * @var string $pkey - a fingerprint for locking
		 */
		public $pkey = false;

		/**
		 * Indicates that a rollback is required
		 * @var unknown_type
		 */
		private $rollback = false;
		
		/**
		 * 
		 * @var $this
		 */
		private $me = null;

		/**
		 * 
		 * Indicates the current engine
		 * @var string
		 */
		private $engine = "myisam";

		/**
		 * @var boolean $frozen - indicates whether the db may be adjusted or not
		 */
		private $frozen = false;

		/**
		 * @var QueryWriter
		 */
		private $writer;


                private $beanchecker;
                private $gc;
                private $classGenerator;
                private $filter;

                public function __construct( $filter = false ) {
                    if ($filter) $this->filter=$filter; else  $this->filter = new RedBean_Mod_Filter_Strict();
                    $this->beanchecker = new RedBean_Mod_BeanChecker();
                    $this->gc = new RedBean_Mod_GarbageCollector();
                    $this->classGenerator = new RedBean_Mod_ClassGenerator( $this->filter );
                }

                public function getFilter() {
                    return $this->filter;
                }
               

		/**
		 * Closes and unlocks the bean
		 * @return unknown_type
		 */
		public function __destruct() {

			$this->releaseAllLocks();
			
			$this->db->exec( 
				$this->writer->getQuery("destruct", array("engine"=>$this->engine,"rollback"=>$this->rollback))
			);
			
		}



		
		/**
		 * Toggles Forward Locking
		 * @param $tf
		 * @return unknown_type
		 */
		public function setLocking( $tf ) {
			$this->locking = $tf;
		}

		public function getDatabase() {
			return $this->db;
		}
		
		public function setDatabase( RedBean_DBAdapter $db ) {
			$this->db = $db;
		} 
		
		/**
		 * Gets the current locking mode (on or off)
		 * @return unknown_type
		 */
		public function getLocking() {
			return $this->locking;
		}
	
		
		/**
		 * Toggles optimizer
		 * @param $bool
		 * @return unknown_type
		 */
		public function setOptimizerActive( $bool ) {
			$this->optimizer = (boolean) $bool;
		}
		
		/**
		 * Returns state of the optimizer
		 * @param $bool
		 * @return unknown_type
		 */
		public function getOptimizerActive() {
			return $this->optimizer;
		}
		
		/**
		 * keeps the current instance
		 * @var RedBean_OODB
		 */
		private static $instance = null;
		
		/**
		 * Singleton
		 * @return unknown_type
		 */
		public function getInstance() {
			if (self::$instance === null) {
				self::$instance = new RedBean_OODB;
			}
			return self::$instance;
		}
		
		/**
		 * Checks whether a bean is valid
		 * @param $bean
		 * @return unknown_type
		 */
		public function checkBean(RedBean_OODBBean $bean) {
                    if (!$this->db) {
                        throw new RedBean_Exception_Security("No database object. Have you used kickstart to initialize RedBean?");
                    }
                    return $this->beanchecker->check( $bean );
		}

		/**
		 * same as check bean, but does additional checks for associations
		 * @param $bean
		 * @return unknown_type
		 */
		public function checkBeanForAssoc( $bean ) {

			//check the bean
			$this->checkBean($bean);

			//make sure it has already been saved to the database, else we have no id.
			if (intval($bean->id) < 1) {
				//if it's not saved, save it
				$bean->id = $this->set( $bean );
			}

			return $bean;

		}

		/**
		 * Returns the current engine
		 * @return unknown_type
		 */
		public function getEngine() {
			return $this->engine;
		}

		/**
		 * Sets the current engine
		 * @param $engine
		 * @return unknown_type
		 */
		public function setEngine( $engine ) {

			if ($engine=="myisam" || $engine=="innodb") {
				$this->engine = $engine;
			}
			else {
				throw new Exception("Unsupported database engine");
			}

			return $this->engine;

		}

		/**
		 * Will perform a rollback at the end of the script
		 * @return unknown_type
		 */
		public function rollback() {
			$this->rollback = true;
		}
		
		/**
		 * Inserts a bean into the database
		 * @param $bean
		 * @return $id
		 */
		public function set( RedBean_OODBBean $bean ) {

			$this->checkBean($bean);


			$db = $this->db; //I am lazy, I dont want to waste characters...

		
			$table = $db->escape($bean->type); //what table does it want

			//may we adjust the database?
			if (!$this->frozen) {

				//does this table exist?
				$tables = $this->showTables();
					
				if (!in_array($table, $tables)) {

					$createtableSQL = $this->writer->getQuery("create_table", array(
						"engine"=>$this->engine,
						"table"=>$table
					));
				
					//get a table for our friend!
					$db->exec( $createtableSQL );
					//jupz, now he has its own table!
					$this->addTable( $table );
				}

				//does the table fit?
				 $columnsRaw = $this->writer->getTableColumns($table, $db) ;
					
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
						$typeno = $this->inferType($v);
						//Is this property represented in the table?
						if (isset($columns[$p])) {
							//yes it is, does it still fit?
							$sqlt = $this->getType($columns[$p]);
							//echo "TYPE = $sqlt .... $typeno ";
							if ($typeno > $sqlt) {
								//no, we have to widen the database column type
								$changecolumnSQL = $this->writer->getQuery( "widen_column", array(
									"table" => $table,
									"column" => $p,
									"newtype" => $this->writer->typeno_sqltype[$typeno]
								) ); 
								
								$db->exec( $changecolumnSQL );
							}
						}
						else {
							//no it is not
							$addcolumnSQL = $this->writer->getQuery("add_column",array(
								"table"=>$table,
								"column"=>$p,
								"type"=> $this->writer->typeno_sqltype[$typeno]
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
				$this->openBean($bean, true);
				//yes it exists, update it
				if (count($updatevalues)>0) {
					$updateSQL = $this->writer->getQuery("update", array(
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
					
					$insertSQL = $this->writer->getQuery("insert",array(
						"table"=>$table,
						"insertcolumns"=>$insertcolumns,
						"insertvalues"=>$insertvalues
					));
				
				}
				else {
					$insertSQL = $this->writer->getQuery("create", array("table"=>$table)); 
				}
				//execute the previously build query
				$db->exec( $insertSQL );
				$bean->id = $db->getInsertID();
                    		$this->openBean($bean);
			}

			return $bean->id;
				
		}


		/**
		 * Infers the SQL type of a bean
		 * @param $v
		 * @return $type the SQL type number constant
		 */
		public function inferType( $v ) {
			
			$db = $this->db;
			$rawv = $v;
			
			$checktypeSQL = $this->writer->getQuery("infertype", array(
				"value"=> $this->db->escape(strval($v))
			));
			
			
			$db->exec( $checktypeSQL );
			$id = $db->getInsertID();
			
			$readtypeSQL = $this->writer->getQuery("readtype",array(
				"id"=>$id
			));
			
			$row=$db->getRow($readtypeSQL);
			
			
			$db->exec( $this->writer->getQuery("reset_dtyp") );
			
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
		public function getType( $sqlType ) {

			if (in_array($sqlType,$this->writer->sqltype_typeno)) {
				$typeno = $this->writer->sqltype_typeno[$sqlType];
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
		public function init( RedBean_QueryWriter $querywriter, $dontclose = false ) {

			$this->writer = $querywriter;
		

			//prepare database
			if ($this->engine === "innodb") {
				$this->db->exec($this->writer->getQuery("prepare_innodb"));
				$this->db->exec($this->writer->getQuery("starttransaction"));
			}
			else if ($this->engine === "myisam"){
				$this->db->exec($this->writer->getQuery("prepare_myisam"));
			}


			//generate the basic redbean tables
			//Create the RedBean tables we need -- this should only happen once..
			if (!$this->frozen) {
				
				$this->db->exec($this->writer->getQuery("clear_dtyp"));
					
				$this->db->exec($this->writer->getQuery("setup_dtyp"));
						
				$this->db->exec($this->writer->getQuery("setup_locking"));
						
				$this->db->exec($this->writer->getQuery("setup_tables"));
			}
			
			//generate a key
			if (!$this->pkey) {
				$this->pkey = str_replace(".","",microtime(true)."".mt_rand());
			}

			return true;
		}

		/**
		 * Freezes the database so it won't be changed anymore
		 * @return unknown_type
		 */
		public function freeze() {
			$this->frozen = true;
		}

		/**
		 * UNFreezes the database so it won't be changed anymore
		 * @return unknown_type
		 */
		public function unfreeze() {
			$this->frozen = false;
		}

		/**
		 * Returns all redbean tables or all tables in the database
		 * @param $all if set to true this function returns all tables instead of just all rb tables
		 * @return array $listoftables
		 */
		public function showTables( $all=false ) {

			$db = $this->db;

			if ($all && $this->frozen) {
				$alltables = $db->getCol($this->writer->getQuery("show_tables"));
				return $alltables;
			}
			else {
				$alltables = $db->getCol($this->writer->getQuery("show_rtables"));
				return $alltables;
			}

		}

		/**
		 * Registers a table with RedBean
		 * @param $tablename
		 * @return void
		 */
		public function addTable( $tablename ) {

			$db = $this->db;

			$tablename = $db->escape( $tablename );

			$db->exec($this->writer->getQuery("register_table",array("table"=>$tablename)));

		}

		/**
		 * UNRegisters a table with RedBean
		 * @param $tablename
		 * @return void
		 */
		public function dropTable( $tablename ) {

			$db = $this->db;

			$tablename = $db->escape( $tablename );

			$db->exec($this->writer->getQuery("unregister_table",array("table"=>$tablename)));


		}

		/**
		 * Quick and dirty way to release all locks
		 * @return unknown_type
		 */
		public function releaseAllLocks() {
			
			$this->db->exec($this->writer->getQuery("release",array("key"=>$this->pkey)));

		}


		/**
		 * Opens and locks a bean
		 * @param $bean
		 * @return unknown_type
		 */
		public function openBean( $bean, $mustlock=false) {

			$this->checkBean( $bean );
			
			//If locking is turned off, or the bean has no persistance yet (not shared) life is always a success!
			if (!$this->locking || $bean->id === 0) return true;
                        $db = $this->db;

			//remove locks that have been expired...
			$removeExpiredSQL = $this->writer->getQuery("remove_expir_lock", array(
				"locktime"=>$this->locktime
			));
			
			$db->exec($removeExpiredSQL);

			$tbl = $db->escape( $bean->type );
			$id = intval( $bean->id );

			//Is the bean already opened for us?
			$checkopenSQL = $this->writer->getQuery("get_lock",array(
				"id"=>$id,
				"table"=>$tbl,
				"key"=>$this->pkey
			));
			
			$row = $db->getRow($checkopenSQL);
			if ($row && is_array($row) && count($row)>0) {
				$updateexpstamp = $this->writer->getQuery("update_expir_lock",array(
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
			$openSQL = $this->writer->getQuery("aq_lock", array(
				"table"=>$tbl,
				"id"=>$id,
				"key"=>$this->pkey,
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
		private function sync( $toggle ) {

			$bean = $this->dispense("_syncmethod");
			$bean->id = 0;

			if ($toggle) {
				$this->openBean( $bean );
			}
			else {
				$this->closeBean( $bean );
			}
		}

		/**
		 * Gets a bean by its primary ID
		 * @param $type
		 * @param $id
		 * @return RedBean_OODBBean $bean
		 */
		public function getById($type, $id, $data=false) {

			$bean = $this->dispense( $type );
			$db = $this->db;
			$table = $db->escape( $type );
			$id = abs( intval( $id ) );
			$bean->id = $id;

			//try to open the bean
			$this->openBean($bean);

			//load the bean using sql
			if (!$data) {
				
				$getSQL = $this->writer->getQuery("get_bean",array(
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
		public function exists($type,$id) {

			$db = $this->db;
			$id = intval( $id );
			$type = $db->escape( $type );

			//$alltables = $db->getCol("show tables");
			$alltables = $this->showTables();

			if (!in_array($type, $alltables)) {
				return false;
			}
			else {
				$no = $db->getCell( $this->writer->getQuery("bean_exists",array(
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
		public function numberof($type) {

			$db = $this->db;
			$type = $this->filter->table( $db->escape( $type ) );

			$alltables = $this->showTables();

			if (!in_array($type, $alltables)) {
				return 0;
			}
			else {
				$no = $db->getCell( $this->writer->getQuery("count",array(
					"type"=>$type
				)));
				return intval( $no );
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
		function distinct($type, $field)
		{
			//TODO: Consider if GROUP BY (equivalent meaning) is more portable 
			//across DB types?
			$db = $this->db;
			$type = $this->filter->table( $db->escape( $type ) );
			$field = $db->escape( $field );
		
			$alltables = $this->showTables();

			if (!in_array($type, $alltables)) {
				return array();
			}
			else {
				$ids = $db->getCol( $this->writer->getQuery("distinct",array(
					"type"=>$type,
					"field"=>$field
				)));
				$beans = array();
				if (is_array($ids) && count($ids)>0) {
					foreach( $ids as $id ) {
						$beans[ $id ] = $this->getById( $type, $id , false);
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
		private function stat($type,$field,$stat="sum") {

			$db = $this->db;
			$type = $this->filter->table( $db->escape( $type ) );
			$field = $this->filter->property( $db->escape( $field ) );
			$stat = $db->escape( $stat );

			$alltables = $this->showTables();

			if (!in_array($type, $alltables)) {
				return 0;
			}
			else {
				$no = $db->getCell($this->writer->getQuery("stat",array(
					"stat"=>$stat,
					"field"=>$field,
					"type"=>$type
				)));
				return floatval( $no );
			}
		}

		/**
		 * Sum
		 * @param $type
		 * @param $field
		 * @return float $i
		 */
		public function sumof($type,$field) {
			return $this->stat( $type, $field, "sum");
		}

		/**
		 * AVG
		 * @param $type
		 * @param $field
		 * @return float $i
		 */
		public function avgof($type,$field) {
			return $this->stat( $type, $field, "avg");
		}

		/**
		 * minimum
		 * @param $type
		 * @param $field
		 * @return float $i
		 */
		public function minof($type,$field) {
			return $this->stat( $type, $field, "min");
		}

		/**
		 * maximum
		 * @param $type
		 * @param $field
		 * @return float $i
		 */
		public function maxof($type,$field) {
			return $this->stat( $type, $field, "max");
		}


		/**
		 * Unlocks everything
		 * @return unknown_type
		 */
		public function resetAll() {
			$sql = $this->writer->getQuery("releaseall");
			$this->db->exec( $sql );
			return true;
		}

		/**
		 * Fills slots in SQL query
		 * @param $sql
		 * @param $slots
		 * @return unknown_type
		 */
		public function processQuerySlots($sql, $slots) {
			
			$db = $this->db;
			
			//Just a funny code to identify slots based on randomness
			$code = sha1(rand(1,1000)*time());
			
			//This ensures no one can hack our queries via SQL template injection
			foreach( $slots as $key=>$value ) {
				$sql = str_replace( "{".$key."}", "{".$code.$key."}" ,$sql ); 
			}
			
			//replace the slots inside the SQL template
			foreach( $slots as $key=>$value ) {
				$sql = str_replace( "{".$code.$key."}", $this->writer->getQuote().$db->escape( $value ).$this->writer->getQuote(),$sql ); 
			}
			
			return $sql;
		}
		
		/**
		 * Loads a collection of beans -fast-
		 * @param $type
		 * @param $ids
		 * @return unknown_type
		 */
		public function fastLoader( $type, $ids ) {
			
			$db = $this->db;
			
			
			$sql = $this->writer->getQuery("fastload", array(
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
		public function getBySQL( $rawsql, $slots, $table, $max=0 ) {
		
			$db = $this->db;
			$sql = $rawsql;
			
			if (is_array($slots)) {
				$sql = $this->processQuerySlots( $sql, $slots );
			}
			
			$sql = str_replace('@ifexists:','', $sql);
			$rs = $db->getCol( $this->writer->getQuery("where",array(
				"table"=>$table
			)) . $sql );
			
			$err = $db->getErrorMsg();
			if (!$this->frozen && strpos($err,"Unknown column")!==false && $max<10) {
				$matches = array();
				if (preg_match("/Unknown\scolumn\s'(.*?)'/",$err,$matches)) {
					if (count($matches)==2 && strpos($rawsql,'@ifexists')!==false){
						$rawsql = str_replace('@ifexists:`'.$matches[1].'`','NULL', $rawsql);
						$rawsql = str_replace('@ifexists:'.$matches[1].'','NULL', $rawsql);
						return $this->getBySQL( $rawsql, $slots, $table, ++$max);
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
    public function find(RedBean_OODBBean $bean, $searchoperators = array(), $start=0, $end=100, $orderby="id ASC", $extraSQL=false) {
 
      $this->checkBean( $bean );
      $db = $this->db;
      $tbl = $db->escape( $bean->type );
 
      $findSQL = $this->writer->getQuery("find",array(
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
            $beans[ $id ] = $this->getById( $bean->type, $id , false);
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
		public function listAll($type, $start=false, $end=false, $orderby="id ASC", $extraSQL = false) {
 
			$db = $this->db;
 
			$listSQL = $this->writer->getQuery("list",array(
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
		public function associate( RedBean_OODBBean $bean1, RedBean_OODBBean $bean2 ) { //@associate

			//get a database
			$db = $this->db;

			//first we check the beans whether they are valid
			$bean1 = $this->checkBeanForAssoc($bean1);
			$bean2 = $this->checkBeanForAssoc($bean2);

			$this->openBean( $bean1, true );
			$this->openBean( $bean2, true );

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
			if (!$this->frozen) {
				$alltables = $this->showTables();
				if (!in_array($assoctable, $alltables)) {
					//no assoc table does not exist, create it..
					$t1 = $tables[0];
					$t2 = $tables[1];

					if ($t1==$t2) {
						$t2.="2";
					}

					$assoccreateSQL = $this->writer->getQuery("create_assoc",array(
						"assoctable"=> $assoctable,
						"t1" =>$t1,
						"t2" =>$t2,
						"engine"=>$this->engine
					));
					
					$db->exec( $assoccreateSQL );
					
					//add a unique constraint
					$db->exec( $this->writer->getQuery("add_assoc",array(
						"assoctable"=> $assoctable,
						"t1" =>$t1,
						"t2" =>$t2
					)) );
					
					$this->addTable( $assoctable );
				}
			}
				
			//now insert the association record
			$assocSQL = $this->writer->getQuery("add_assoc_now", array(
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
		public function unassociate(RedBean_OODBBean $bean1, RedBean_OODBBean $bean2) {

			//get a database
			$db = $this->db;

			//first we check the beans whether they are valid
			$bean1 = $this->checkBeanForAssoc($bean1);
			$bean2 = $this->checkBeanForAssoc($bean2);


			$this->openBean( $bean1, true );
			$this->openBean( $bean2, true );


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
			$alltables = $this->showTables();
				
			if (in_array($assoctable, $alltables)) {
				$t1 = $tables[0];
				$t2 = $tables[1];
				if ($t1==$t2) {
					$t2.="2";
					$unassocSQL = $this->writer->getQuery("unassoc",array(
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

				$unassocSQL = $this->writer->getQuery("unassoc",array(
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
				$alltables = $this->showTables();
				if (in_array($assoctable2, $alltables)) {

					//$id1 = intval($bean1->id);
					//$id2 = intval($bean2->id);
					$unassocSQL = $this->writer->getQuery("untree", array(
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
		public function getAssoc(RedBean_OODBBean $bean, $targettype) {
			//get a database
			$db = $this->db;
			//first we check the beans whether they are valid
			$bean = $this->checkBeanForAssoc($bean);

			$id = intval($bean->id);


			//obtain the table names
			$t1 = $db->escape( $this->filter->table($bean->type) );
			$t2 = $db->escape( $targettype );

			//infer the association table
			$tables = array();
			array_push( $tables, $t1 );
			array_push( $tables, $t2 );
			//sort the table names to make sure we only get one assoc table
			sort($tables);
			$assoctable = $db->escape( implode("_",$tables) );

			//check whether this assoctable exists
			$alltables = $this->showTables();
				
			if (!in_array($assoctable, $alltables)) {
				return array(); //nope, so no associations...!
			}
			else {
				if ($t1==$t2) {
					$t2.="2";
				}
				
				$getassocSQL = $this->writer->getQuery("get_assoc",array(
					"t1"=>$t1,
					"t2"=>$t2,
					"assoctable"=>$assoctable,
					"id"=>$id
				));
				
				
				$rows = $db->getCol( $getassocSQL );
				$beans = array();
				if ($rows && is_array($rows) && count($rows)>0) {
					foreach($rows as $i) {
						$beans[$i] = $this->getById( $targettype, $i, false);
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
		public function trash( RedBean_OODBBean $bean ) {

			$this->checkBean( $bean );
			if (intval($bean->id)===0) return;
			$this->deleteAllAssoc( $bean );
			$this->openBean($bean);
			$table = $this->db->escape($bean->type);
			$id = intval($bean->id);
			$this->db->exec( $this->writer->getQuery("trash",array(
				"table"=>$table,
				"id"=>$id
			)) );

		}
			
		/**
		 * Breaks all associations of a perticular bean $bean
		 * @param $bean
		 * @return unknown_type
		 */
		public function deleteAllAssoc( $bean ) {

			$db = $this->db;
			$bean = $this->checkBeanForAssoc($bean);

			$this->openBean( $bean, true );


			$id = intval( $bean->id );

			//get all tables
			$alltables = $this->showTables();

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
				
					$db->exec( $this->writer->getQuery("deltree",array(
						"id"=>$id,
						"table"=>$table
					)) );
				}
				else {
					
					$db->exec( $this->writer->getQuery("unassoc_all_t1",array("table"=>$table,"t"=>$t,"id"=>$id)) );
					$db->exec( $this->writer->getQuery("unassoc_all_t2",array("table"=>$table,"t"=>$t,"id"=>$id)) );
				}
					
					
			}
			return true;
		}

		/**
		 * Breaks all associations of a perticular bean $bean
		 * @param $bean
		 * @return unknown_type
		 */
		public function deleteAllAssocType( $targettype, $bean ) {

			$db = $this->db;
			$bean = $this->checkBeanForAssoc($bean);
			$this->openBean( $bean, true );

			$id = intval( $bean->id );

			//obtain the table names
			$t1 = $db->escape( $this->filter->table($bean->type) );
			$t2 = $db->escape( $targettype );

			//infer the association table
			$tables = array();
			array_push( $tables, $t1 );
			array_push( $tables, $t2 );
			//sort the table names to make sure we only get one assoc table
			sort($tables);
			$assoctable = $db->escape( implode("_",$tables) );
			
			$availabletables = $this->showTables();
			
			
			if (in_array('pc_'.$assoctable,$availabletables)){
				$db->exec( $this->writer->getQuery("deltreetype",array(
					"assoctable"=>'pc_'.$assoctable,
					"id"=>$id
				)) );
			}
			if (in_array($assoctable,$availabletables)) {
				$db->exec( $this->writer->getQuery("unassoctype1",array(
					"assoctable"=>$assoctable,
					"t1"=>$t1,
					"id"=>$id
				)) );
				$db->exec( $this->writer->getQuery("unassoctype2",array(
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
		 * @return RedBean_OODBBean $bean
		 */
		public function dispense( $type="StandardBean" ) {

			$oBean = new RedBean_OODBBean();
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
		public function addChild( RedBean_OODBBean $parent, RedBean_OODBBean $child ) {

			//get a database
			$db = $this->db;

			//first we check the beans whether they are valid
			$parent = $this->checkBeanForAssoc($parent);
			$child = $this->checkBeanForAssoc($child);

			$this->openBean( $parent, true );
			$this->openBean( $child, true );


			//are parent and child of the same type?
			if ($parent->type !== $child->type) {
				throw new RedBean_Exception_InvalidParentChildCombination();
			}

			$pid = intval($parent->id);
			$cid = intval($child->id);

			//infer the association table
			$assoctable = "pc_".$db->escape($parent->type."_".$parent->type);

			//check whether this assoctable already exists
			if (!$this->frozen) {
				$alltables = $this->showTables();
				if (!in_array($assoctable, $alltables)) {
					//no assoc table does not exist, create it..
					$assoccreateSQL = $this->writer->getQuery("create_tree",array(
						"engine"=>$this->engine,
						"assoctable"=>$assoctable
					));
					$db->exec( $assoccreateSQL );
					//add a unique constraint
					$db->exec( $this->writer->getQuery("unique", array(
						"assoctable"=>$assoctable
					)) );
					$this->addTable( $assoctable );
				}
			}

			//now insert the association record
			$assocSQL = $this->writer->getQuery("add_child",array(
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
		public function getChildren( RedBean_OODBBean $parent ) {

			//get a database
			$db = $this->db;

			//first we check the beans whether they are valid
			$parent = $this->checkBeanForAssoc($parent);

			$pid = intval($parent->id);

			//infer the association table
			$assoctable = "pc_".$db->escape( $parent->type . "_" . $parent->type );

			//check whether this assoctable exists
			$alltables = $this->showTables();
			if (!in_array($assoctable, $alltables)) {
				return array(); //nope, so no children...!
			}
			else {
				$targettype = $parent->type;
				$getassocSQL = $this->writer->getQuery("get_children", array(
					"assoctable"=>$assoctable,
					"pid"=>$pid
				));
				$rows = $db->getCol( $getassocSQL );
				$beans = array();
				if ($rows && is_array($rows) && count($rows)>0) {
					foreach($rows as $i) {
						$beans[$i] = $this->getById( $targettype, $i, false);
					}
				}
				return $beans;
			}

		}

		/**
		 * Fetches the parent bean of child bean $child
		 * @param $child
		 * @return RedBean_OODBBean $parent
		 */
		public function getParent( RedBean_OODBBean $child ) {

				
			//get a database
			$db = $this->db;

			//first we check the beans whether they are valid
			$child = $this->checkBeanForAssoc($child);

			$cid = intval($child->id);

			//infer the association table
			$assoctable = "pc_".$db->escape( $child->type . "_" . $child->type );
			//check whether this assoctable exists
			$alltables = $this->showTables();
			if (!in_array($assoctable, $alltables)) {
				return array(); //nope, so no children...!
			}
			else {
				$targettype = $child->type;
				
				$getassocSQL = $this->writer->getQuery("get_parent", array(
					"assoctable"=>$assoctable,
					"cid"=>$cid
				));
					
				$rows = $db->getCol( $getassocSQL );
				$beans = array();
				if ($rows && is_array($rows) && count($rows)>0) {
					foreach($rows as $i) {
						$beans[$i] = $this->getById( $targettype, $i, false);
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
		public function removeChild(RedBean_OODBBean $parent, RedBean_OODBBean $child) {

			//get a database
			$db = $this->db;

			//first we check the beans whether they are valid
			$parent = $this->checkBeanForAssoc($parent);
			$child = $this->checkBeanForAssoc($child);

			$this->openBean( $parent, true );
			$this->openBean( $child, true );


			//are parent and child of the same type?
			if ($parent->type !== $child->type) {
				throw new RedBean_Exception_InvalidParentChildCombination();
			}

			//infer the association table
			$assoctable = "pc_".$db->escape( $parent->type . "_" . $parent->type );

			//check whether this assoctable already exists
			$alltables = $this->showTables();
			if (!in_array($assoctable, $alltables)) {
				return true; //no association? then nothing to do!
			}
			else {
				$pid = intval($parent->id);
				$cid = intval($child->id);
				$unassocSQL = $this->writer->getQuery("remove_child", array(
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
		public function numofRelated( $type, RedBean_OODBBean $bean ) {
			
			//get a database
			$db = $this->db;
			
			$t2 = $this->filter->table( $db->escape( $type ) );
						
			//is this bean valid?
			$this->checkBean( $bean );
			$t1 = $this->filter->table( $bean->type  );
			$tref = $this->filter->table( $db->escape( $bean->type ) );
			$id = intval( $bean->id );
						
			//infer the association table
			$tables = array();
			array_push( $tables, $t1 );
			array_push( $tables, $t2 );
			
			//sort the table names to make sure we only get one assoc table
			sort($tables);
			$assoctable = $db->escape( implode("_",$tables) );
			
			//get all tables
			$tables = $this->showTables();
			
			if ($tables && is_array($tables) && count($tables) > 0) {
				if (in_array( $t1, $tables ) && in_array($t2, $tables)){
					$sqlCountRelations = $this->writer->getQuery(
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
		 * @param string $prefix prefix for framework integration (optional, constant is used otherwise)
		 * @param string $suffix suffix for framework integration (optional, constant is used otherwise)
		 * @return unknown_type
		 */
		
		public function generate( $classes, $prefix = false, $suffix = false ) {
			return $this->classGenerator->generate($classes,$prefix,$suffix);
                }
			
		


		/**
		 * Changes the locktime, this time indicated how long
		 * a user can lock a bean in the database.
		 * @param $timeInSecs
		 * @return unknown_type
		 */
		public function setLockingTime( $timeInSecs ) {

			if (is_int($timeInSecs) && $timeInSecs >= 0) {
				$this->locktime = $timeInSecs;
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
		public function clean() {

			if ($this->frozen) {
				return false;
			}

			$db = $this->db;

			$tables = $db->getCol( $this->writer->getQuery("show_rtables") );

			foreach($tables as $key=>$table) {
				$tables[$key] = $this->writer->getEscape().$table.$this->writer->getEscape();
			}

			$sqlcleandatabase = $this->writer->getQuery("drop_tables",array(
				"tables"=>$tables
			));

			$db->exec( $sqlcleandatabase );

			$db->exec( $this->writer->getQuery("truncate_rtables") );
			$this->resetAll();
			return true;

		}
		
	
		/**
		 * Removes all tables from redbean that have
		 * no classes
		 * @return unknown_type
		 */
		public function removeUnused( ) {

			//oops, we are frozen, so no change..
			if ($this->frozen) {
				return false;
			}

                        return $this->gc->removeUnused( $this, $this->db, $this->writer );

			
		}
		/**
		 * Drops a specific column
		 * @param $table
		 * @param $property
		 * @return unknown_type
		 */
		public function dropColumn( $table, $property ) {
			
			//oops, we are frozen, so no change..
			if ($this->frozen) {
				return false;
			}

			//get a database
			$db = $this->db;
			
			$db->exec( $this->writer->getQuery("drop_column", array(
				"table"=>$table,
				"property"=>$property
			)) );
			
		}

		/**
	     * Removes all beans of a particular type
	     * @param $type
	     * @return nothing
	     */
	    public function trashAll($type) {
	        $this->db->exec( $this->writer->getQuery("drop_type",array("type"=>$this->filter->table($type))));
	    }

	    /**
		 * Narrows columns to appropriate size if needed
		 * @return unknown_type
		 */
		public function keepInShapeNS( $gc = false ,$stdTable=false, $stdCol=false) {
			
			//oops, we are frozen, so no change..
			if ($this->frozen) {
				return false;
			}

			//get a database
			$db = $this->db;

			//get all tables
			$tables = $this->showTables();
			
				//pick a random table
				if ($tables && is_array($tables) && count($tables) > 0) {
					if ($gc) $this->removeUnused( $tables );
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
			
			$cols = $this->writer->getTableColumns( $table, $db );
			
			//$cols = $db->get( $this->writer->getQuery("describe",array(
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
			if ($gc && !intval($db->getCell( $this->writer->getQuery("get_null",array(
				"table"=>$table,
				"col"=>$col
			)
			)))) {
				$db->exec( $this->writer->getQuery("drop_column",array("table"=>$table,"property"=>$col)));
				return;	
			}
			
			//okay so this column is still in use, but maybe its to wide
			//get the field type
			//print_r($colr);
			$currenttype =  $this->writer->sqltype_typeno[$colr["Type"]];
			if ($currenttype > 0) {
				$trytype = rand(0,$currenttype - 1); //try a little smaller
				//add a test column
				$db->exec($this->writer->getQuery("test_column",array(
					"type"=>$this->writer->typeno_sqltype[$trytype],
					"table"=>$table
				)
				));
				//fill the tinier column with the same values of the original column
				$db->exec($this->writer->getQuery("update_test",array(
					"table"=>$table,
					"col"=>$col
				)));
				//measure the difference
				$delta = $db->getCell($this->writer->getQuery("measure",array(
					"table"=>$table,
					"col"=>$col
				)));
				if (intval($delta)===0) {
					//no difference? then change the column to save some space
					$sql = $this->writer->getQuery("remove_test",array(
						"table"=>$table,
						"col"=>$col,
						"type"=>$this->writer->typeno_sqltype[$trytype]
					));
					$db->exec($sql);
				}
				//get rid of the test column..
				$db->exec( $this->writer->getQuery("drop_test",array(
					"table"=>$table
				)) );
			}
		
			//@todo -> querywriter!
			//Can we put an index on this column?
			//Is this column worth the trouble?
			if (
				strpos($colr["Type"],"TEXT")!==false ||
				strpos($colr["Type"],"LONGTEXT")!==false
			) {
				return;
			}
			
		
			$variance = $db->getCell($this->writer->getQuery("variance",array(
				"col"=>$col,
				"table"=>$table
			)));
			$records = $db->getCell($this->writer->getQuery("count",array("type"=>$table)));
			if ($records) {
				$relvar = intval($variance) / intval($records); //how useful would this index be?
				//if this column describes the table well enough it might be used to
				//improve overall performance.
				$indexname = "reddex_".$col;
				if ($records > 1 && $relvar > 0.85) {
					$sqladdindex=$this->writer->getQuery("index1",array(
						"table"=>$table,
						"indexname"=>$indexname,
						"col"=>$col
					));
					$db->exec( $sqladdindex );
				}
				else {
					$sqldropindex = $this->writer->getQuery("index2",array("table"=>$table,"indexname"=>$indexname));
					$db->exec( $sqldropindex );
				}
			}
			
			return true;
		}
		
		public static function gen($arg, $prefix = false, $suffix = false) {
			return self::getInstance()->generate($arg, $prefix, $suffix);
		}
	
		public static function keepInShape($gc = false ,$stdTable=false, $stdCol=false) {
			return self::getInstance()->keepInShapeNS($gc, $stdTable, $stdCol);
		}

                public function getInstOf( $className, $id=0 ) {
                    if (!class_exists($className)) throw new Exception("Class does not Exist");
                    $object = new $className($id);
                    return $object;
                }
}


