<?php
/**
 * RedBean Optimizer DateTime
 * @file				RedBean/Plugin/Optimizer/DateTime.php
 * @description			An Optimizer Plugin for RedBean.
 *						Tries to convert columns to MySQL datetime
 *						if possible.
 *
 * @author			Gabor de Mooij
 * @license			BSD
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_Plugin_Optimizer_Datetime  implements RedBean_Plugin_IOptimizer {

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
	 * Performs the actual optimization. In this case the optimizer first
	 * scans the value. If the value if of type 'datetime' and the column
	 * is not it tries to make the column datetime. If the column is 'datetime'
	 * and the value 'datetime' it blocks further optimization. If the value
	 * is NOT 'datetime' then it immediately returns true, thus allowing further
	 * optimization.
	 *
	 * @return boolean $yesNo advance to next optimizer
	 */
	public function optimize() {
		if (!$this->matchesDateTime($this->value)) return true;
		//get the type of the current value
		$type = $this->writer->scanType($this->value);
		//get all the fields in the table
		$fields = $this->writer->getColumns($this->table);
		//If the column for some reason does not occur in fields, return
		//print_r($fields);
		if (!in_array($this->column,array_keys($fields))) return false;
		//get the type we got in the field of the table
		$typeInField = $this->writer->code($fields[$this->column]);

		//Is column already datetime?
		if ($typeInField!="datetime") {
			if ($this->matchesDateTime($this->value)) {
				//Ok, value is datetime, can we convert the column to support this?
				$cnt = (int) $this->adapter->getCell("select count(*) as n from ".$this->writer->safeTable($this->table)." where
						  {$this->column} regexp '[0-9]{4}-[0-1][0-9]-[0-3][0-9] [0-2][0-9]:[0-5][0-9]:[0-5][0-9]'
						  OR {$this->column} IS NULL");
				$total = (int) $this->adapter->getCell("SELECT count(*) FROM ".$this->writer->safeTable($this->table));
				//Is it safe to convert: ie are all values compatible?
				if ($total===$cnt) { //yes
					$this->adapter->exec("ALTER TABLE ".$this->writer->safeTable($this->table)." change ".$this->writer->safeColumn($this->column)." ".$this->writer->safeColumn($this->column)." datetime ");
				}
				//No further optimization required.
				return false;
			}
			//Further optimization could be useful.
			return true;
		}
		else {
			//yes column is datetime, if value is stop further optimizing
			return false;
		}

	}

	/**
	 * MatchesDateTime matches a value to determine whether it matches the
	 * MySQL datetime type.
	 *
	 * @param string $value Value to match
	 *
	 * @return boolean $yesNo Whether it is a datetime value
	 */
	public function matchesDateTime($value) {
		$pattern = "/^([0-9]{2,4})-([0-1][0-9])-([0-3][0-9]) (?:([0-2][0-9]):([0-5][0-9]):([0-5][0-9]))?$/";
		return (boolean) (preg_match($pattern, $value));
	}



}