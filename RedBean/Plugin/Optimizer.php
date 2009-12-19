<?php
/**
 * Optimizer
 * @file 		RedBean/Optimizer.php
 * @author			Gabor de Mooij
 * @license			BSD
 */
class RedBean_Plugin_Optimizer implements RedBean_Plugin,RedBean_Observer {

	/**
	 * @var RedBean_Adapter_DBAdapter
	 */
	private $adapter;

	/**
	 * @var RedBean_OODB
	 */
	private $oodb;

	/**
	 * @var RedBean_QueryWriter_MySQL
	 */
	private $writer;

	/**
	 * Constructor
	 * Handles the toolbox
	 * @param RedBean_ToolBox $toolbox
	 */
	public function __construct( RedBean_ToolBox $toolbox ) {
		$this->oodb = $toolbox->getRedBean();
		$this->adapter = $toolbox->getDatabaseAdapter();
		$this->writer = $toolbox->getWriter(); 
	}

	/**
	 * Does an optimization cycle for each UPDATE event.
	 * @param string $event
	 * @param RedBean_OODBBean $bean
	 */
	public function onEvent( $event , $bean ) {
		try{
		if ($event=="update") {
			$arr = $bean->export();
			unset($arr["id"]);
			if (count($arr)==0) return;
			$table = $this->adapter->escape($bean->getMeta("type"));
			$columns = array_keys($arr);
			//Select a random column for optimization.
			$column = $this->adapter->escape($columns[ array_rand($columns) ]);
			$value = $arr[$column];
			$type = $this->writer->scanType($value);
			$fields = $this->writer->getColumns($table);
			if (!in_array($column,array_keys($fields))) return;
			$typeInField = $this->writer->code($fields[$column]);
			//Is the type too wide?
			if ($type < $typeInField) {
				try{@$this->adapter->exec("alter table `$table` drop __test");}catch(Exception $e){}
				//Try to re-fit the entire column; by testing it.
				$type = $this->writer->typeno_sqltype[$type];
				//Add a test column.
				@$this->adapter->exec("alter table `$table` add __test ".$type);
				//Copy the values and see if there are differences.
				@$this->adapter->exec("update `$table` set __test=`$column`");
				$diff = $this->adapter->getCell("select
							count(*) as df from `$table` where
							strcmp(`$column`,__test) != 0");
				if (!$diff) {
					//No differences; shrink column.
					@$this->adapter->exec("alter table `$table` change `$column` `$column` ".$type);
				}
				//Throw away test column; we don't need it anymore!
				@$this->adapter->exec("alter table `$table` drop __test");
			}
			else {
				$this->MySQLSpecificColumns($table, $column, $fields[$column], $value);
			}

		}
		}catch(RedBean_Exception_SQL $e){
			//optimizer might make mistakes, don't care.
			//echo $e->getMessage()."<br>";
		}
	}


	/**
	 * Tries to convert columns to MySQL specific types like:
	 * datetime, ENUM etc. This method is called automatically for you and
	 * works completely in the background. You can however if you like trigger
	 * this method by invoking it directly.
	 * @param string $table
	 * @param string $column
	 * @param string $columnType
	 * @param string $value
	 */
	public function MySQLSpecificColumns( $table, $column, $columnType, $value ) {
		//$this->adapter->getDatabase()->setDebugMode(1);
		$table = $this->adapter->escape($table);
		$column = $this->adapter->escape($column);
		//Is column already datetime?
		if ($columnType!="datetime") {
			$pattern = "/^([0-9]{2,4})-([0-1][0-9])-([0-3][0-9]) (?:([0-2][0-9]):([0-5][0-9]):([0-5][0-9]))?$/";
			if (preg_match($pattern, $value)){
				//Ok, value is datetime, can we convert the column to support this?
				$cnt = (int) $this->adapter->getCell("select count(*) as n from $table where
					$column regexp '[0-9]{4}-[0-1][0-9]-[0-3][0-9] [0-2][0-9]:[0-5][0-9]:[0-5][0-9]'
				");
				$total = (int) $this->adapter->getCell("SELECT count(*) FROM $table");
				//Is it safe to convert: ie are all values compatible?
				if ($total===$cnt) { //yes
					$this->adapter->exec("ALTER TABLE `$table` change `$column` `$column` datetime ");
				}
			}
		}

	}

}