<?php

class Optimizer implements RedBean_Observer {


	private $adapter;
	private $oodb;

	/**
	 *
	 * @var RedBean_QueryWriter_MySQL
	 */
	private $writer;

	public function __construct( RedBean_ToolBox $toolbox ) {
		$this->oodb = $toolbox->getRedBean();
		$this->adapter = $toolbox->getDatabaseAdapter();
		$this->writer = $toolbox->getWriter(); 
	}


	public function onEvent( $event , $bean ) { return;
		if ($event=="update") {
			$arr = $bean->export();
			unset($arr["id"]);
			if (count($arr)==0) return;
			$table = $this->adapter->escape($bean->getMeta("type"));
			$columns = array_keys($arr);
			$column = $this->adapter->escape($columns[ array_rand($columns) ]);
			$column = "body";
			$value = $arr[$column];
			$type = $this->writer->scanType($value);
			$fields = $this->writer->getColumns($table);
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
	}

}