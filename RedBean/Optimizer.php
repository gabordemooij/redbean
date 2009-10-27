<?php
/**
 * Optimizer
 * @package 		RedBean/Optimizer.php
 * @author			Gabor de Mooij
 * @license			BSD
 */
class Optimizer implements RedBean_Observer {

	/**
	 *
	 * @var RedBean_DBAdapter
	 */
	private $adapter;

	/**
	 *
	 * @var RedBean_OODB
	 */
	private $oodb;

	/**
	 *
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
	 * Does an optimization cycle for each UPDATE event
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
			$column = $this->adapter->escape($columns[ array_rand($columns) ]);
			$value = $arr[$column];
			$type = $this->writer->scanType($value);
			$fields = $this->writer->getColumns($table);
			if (!in_array($column,array_keys($fields))) return;
			$typeInField = $this->writer->code($fields[$column]);
			if ($type < $typeInField) { 
				$type = $this->writer->typeno_sqltype[$type];
				$this->adapter->exec("alter table `$table` add __test ".$type);
				$this->adapter->exec("update `$table` set __test=`$column`");
				$diff = $this->adapter->getCell("select
							count(*) as df from `$table` where
							strcmp(`$column`,__test) != 0");
				if (!$diff) {
					$this->adapter->exec("alter table `$table` change `$column` `$column` ".$type);
				}
				$this->adapter->exec("alter table `$table` drop __test");
			}

		}
		}catch(RedBean_Exception_SQL $e){ //optimizer might make mistakes, dont care..
		
		}
	}

}