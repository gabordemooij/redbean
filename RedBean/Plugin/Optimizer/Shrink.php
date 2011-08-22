<?php
/**
 * RedBean Optimizer Shrink
 * @file				RedBean/Plugin/Optimizer/Shrink.php
 * @description			An Optimizer Plugin for RedBean.
 *						This optimizer tries to narrow columns on the fly.
 *						If the values in a column can be stored in a smaller
 *						column type this plugin will try to adjust the column to the
 *						smaller type.
 *
 * @author			Gabor de Mooij
 * @license			BSD
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_Plugin_Optimizer_Shrink implements RedBean_Plugin_IOptimizer {


	/**
	 * An optimizer takes three arguments; a table, column and value.
	 * The table is the table that is being used for an update event at the moment,
	 * the Object Database will inform you about this because it might be an
	 * opportunity to perform optimization.
	 * Table to optimize.
	 *
	 * @var string $table name of the table to optimize
	 */
	protected $table;

	/**
	 * An optimizer takes three arguments; a table, column and value.
	 * The column is the column currently being updated, the Object Database
	 * will inform you about this because it might be an
	 * opportunity to perform optimization.
	 * Column to optimize.
	 *
	 * @var string $column column name
	 */
	protected $column;

	/**
	 * An optimizer takes three arguments; a table, column and value.
	 * The value is the piece of data that is being inserted in the column
	 * at this moment. The job of the optimizer is to check whether the column
	 * could be optimized based on the current contents and the value currently
	 * being inserted.
	 *
	 * @var string $value Value currently inserted in the column
	 */
	protected $value;

	/**
	 * Toolbox, contains everyting required for this instance to
	 * perform database operations within the RedBean framework.
	 *
	 * @var RedBean_Toolbox $toolbox a toolbox
	 */
	protected $toolbox;

	/**
	 * This is a convenience property so you don't have to
	 * ask the toolbox for this object every time you need it.
	 *
	 * @var RedBean_QueryWriter $writer query writer
	 */
	protected $writer;

	/**
	 * This is a convenience property so you don't have to
	 * ask the toolbox for this object every time you need it.
	 *
	 * @var RedBean_DatabaseAdapter $adapter database adapter
	 */
	protected $adapter;


	/**
	 * Constructor.
	 * This Object requires a toolbox.
	 *
	 * @param RedBean_ToolBox $toolbox toolbox for DB operations.
	 */
	public function __construct( RedBean_ToolBox $toolbox ) {
		$this->writer = $toolbox->getWriter();
		$this->adapter = $toolbox->getDatabaseAdapter();
	}

	/**
	 * An optimizer takes three arguments; a table, column and value.
	 * The table is the table that is being used for an update event at the moment,
	 * the Object Database will inform you about this because it might be an
	 * opportunity to perform optimization.
	 * Table to optimize.
	 *
	 * @param string $table name of the table to optimize
	 */

	public function setTable( $table ) {
		$this->table = $table;
	}

	/**
	 * An optimizer takes three arguments; a table, column and value.
	 * The column is the column currently being updated, the Object Database
	 * will inform you about this because it might be an
	 * opportunity to perform optimization.
	 * Column to optimize.
	 *
	 * @param string $column column name
	 */
	public function setColumn( $column ) {
		$this->column = $column;
	}

	/**
	 * An optimizer takes three arguments; a table, column and value.
	 * The value is the piece of data that is being inserted in the column
	 * at this moment. The job of the optimizer is to check whether the column
	 * could be optimized based on the current contents and the value currently
	 * being inserted.
	 *
	 * @param string $value Value currently inserted in the column
	 */
	public function setValue( $value ) {
		$this->value = $value;
	}

	/**
	 * Performs the actual optimization. In this case the optimizer looks
	 * at the size of the column and the size of the value. If the value size is
	 * smaller than the column size it tries to convert the column to a smaller
	 * size. Next, it counts if there is any different between the smaller column
	 * and the original column. If no differences are found the original column
	 * gets replaced.
	 * Like the other optimizers, this optimizer returns TRUE if it thinks
	 * further optimizations can happen, FALSE otherwise.
	 *
	 * @return boolean $yesNo advance to next optimizer
	 */
	public function optimize() {
		//get the type of the current value
		$type = $this->writer->scanType($this->value);
		//get all the fields in the table
		$fields = $this->writer->getColumns($this->table);
		//If the column for some reason does not occur in fields, return
		if (!in_array($this->column,array_keys($fields))) return false;
		//get the type we got in the field of the table
		$typeInField = $this->writer->code($fields[$this->column]);
		//Is the type too wide?
		if ($type < $typeInField) {
			try {
				@$this->adapter->exec("alter table ".$this->writer->safeTable($this->table)." drop __test");
			}catch(Exception $e) {}
			//Try to re-fit the entire column; by testing it.
			$type = $this->writer->typeno_sqltype[$type];
			//Add a test column.
			@$this->adapter->exec("alter table ".$this->writer->safeTable($this->table)." add __test ".$type);
			//Copy the values and see if there are differences.
			@$this->adapter->exec("update ".$this->writer->safeTable($this->table)." set __test=".$this->writer->safeColumn($this->column)."");
			$rows = $this->adapter->get("select ".$this->writer->safeColumn($this->column)." as a, __test as b from ".$this->writer->safeTable($this->table));
			$diff = 0;
			foreach($rows as $row) {
				$diff += ($row["a"]!=$row["b"]);
			}
			if (!$diff) {
				//No differences; shrink column.
				@$this->adapter->exec("alter table ".$this->writer->safeTable($this->table)." change ".$this->writer->safeColumn($this->column)." ".$this->writer->safeColumn($this->column)." ".$type);
			}
			//Throw away test column; we don't need it anymore!
			@$this->adapter->exec("alter table ".$this->writer->safeTable($this->table)." drop __test");
		}
		return false;
	}

}