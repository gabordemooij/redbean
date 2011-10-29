<?php
/**
 * Optimizer
 * @file 			RedBean/Optimizer.php
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_Plugin_Optimizer implements RedBean_Observer {

	/**
	 * @var RedBean_Adapter_DBAdapter
	 * Contains a reference to the database adapter.
	 */
	private $adapter;

	/**
	 * @var RedBean_OODB
	 * Contains a reference to the RedBean OODB object.
	 */
	private $oodb;

	/**
	 * @var RedBean_QueryWriter_MySQL
	 * Contains a reference to the query writer.
	 */
	private $writer;


	/**
	 * Contains an array filled with optimizers.
	 * @var RedBean_Plugin_IOptimizer $optimizers
	 */
	protected $optimizers = array();

	/**
	 * Constructor
	 * Handles the toolbox
	 *
	 * @param RedBean_ToolBox $toolbox
	 */
	public function __construct( RedBean_ToolBox $toolbox ) {
		$this->oodb = $toolbox->getRedBean();
		$this->adapter = $toolbox->getDatabaseAdapter();
		$this->writer = $toolbox->getWriter();

	}

	/**
	 * Runs optimization Queue.
	 *
	 * @param string $table  table to optimize
	 * @param string $column column to optimize
	 * @param string $value  value to scan
	 *
	 */
	protected function optimize($table,$column,$value) {
		foreach($this->optimizers as $optimizer) {
			$optimizer->setTable($table);
			$optimizer->setColumn($column);
			$optimizer->setValue($value);
			if (!$optimizer->optimize()) break;
		}
	}


	/**
	 * Does an optimization cycle for each UPDATE event.
	 *
	 * @param string				$event event
	 * @param RedBean_OODBBean $bean	 bean
	 *
	 * @return void
	 */
	public function onEvent( $event , $bean ) {
		try {
			if ($event=="update") {
				//export the bean as an array
				$arr = $bean->export(); //print_r($arr);
				//remove the id property
				unset($arr["id"]);
				//If we are left with an empty array we might as well return
				if (count($arr)==0) return;
				//fetch table name for this bean
				//get the column names for this table
				$table = $bean->getMeta("type");
				$columns = array_keys($arr);
				//Select a random column for optimization.
				$column = $columns[ array_rand($columns) ];
				//get the value to be optimized
				$value = $arr[$column];
				$this->optimize($table,$column,$value);
			}
		}catch(RedBean_Exception_SQL $e) { }
	}

	/**
	 * Adds an optimizer to the optimizer collection.
	 *
	 * @param RedBean_Plugin_IOptimizer $optimizer
	 */
	public function addOptimizer(RedBean_Plugin_IOptimizer $optimizer) {
		$this->optimizers[] = $optimizer;
	}

}