<?php
/**
 * RedBean SQL Helper
 *
 * @file				RedBean/SQLHelper.php
 * @description			Allows you to mix PHP and SQL as if they were
 * 						a unified language
 *					
 *						Simplest case:
 *
 *						$r->now(); //returns SQL time
 *
 *
 *						Another Example:
 *
 *						$f->begin()
 * 						->select('*')
 * 						->from('island')->where('id = ? ')->put(1)->get();
 *
 *						Another example:
 *			
 *						$f->begin()->show('tables')->get('col');
 *
 *	
 * @author				Gabor de Mooij and the RedBeanPHP community
 * @license				BSD/GPLv2
 *
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
 class RedBean_SQLHelper {

	/**
	 * Holds the database adapter for executing SQL queries.
	 * @var RedBean_Adapter 
	 */
	protected $adapter;

	/**
	 * Holds current mode
	 * @var boolean
	 */
	protected $capture = false;

	/**
	 * Holds SQL until now
	 * @var string
	 */
	protected $sql = '';
	
	/**
	 * Holds list of parameters for SQL Query
	 * @var array
	 */
	protected $params = array();

	/**
	 * Constructor
	 * 
	 * @param RedBean_DBAdapter $adapter database adapter for querying
	 */
	public function __construct(RedBean_Adapter $adapter) {
		$this->adapter = $adapter;
	}

	/**
	 * Magic method to construct SQL query
	 * 
	 * @param string $funcName name of the next SQL statement/keyword
	 * @param array  $args     list of statements to be seperated by commas
	 * 
	 * @return mixed $result   either self or result depending on mode 
	 */
	public function __call($funcName,$args=array()) {
		$funcName = str_replace('_',' ',$funcName);
		if ($this->capture) {
			$this->sql .= ' '.$funcName . ' '.implode(',', $args);
			return $this;
		}
		else {
			return $this->adapter->getCell('SELECT '.$funcName.'('.implode(',',$args).')');	
		}	
	}

	/**
	 * Begins SQL query
	 * 
	 * @return RedBean_SQLHelper $this chainable
	 */
	public function begin() {
		$this->capture = true;
		return $this;
	}
	
	/**
	 * Adds a value to the parameter list
	 * 
	 * @param mixed $param parameter to be added
	 * 
	 * @return RedBean_SQLHelper $this chainable
	 */
	public function put($param) {
		$this->params[] = $param;
		return $this;
	}
	
	/**
	 * Executes query and returns result
	 * 
	 * @return mixed $result
	 */
	public function get($what='') {
		$what = 'get'.ucfirst($what);
		$rs = $this->adapter->$what($this->sql,$this->params);
		$this->clear();
		return $rs;
	}
	
	/**
	 * Clears the parameter list as well as the SQL query string.
	 * 
	 * @return RedBean_SQLHelper $this chainable
	 */
	public function clear() {
		$this->sql = '';
		$this->params = array();
		$this->capture = false; //turn off capture mode (issue #142)
		return $this;
	}
	
	/**
	 * To explicitly add a piece of SQL.
	 * 
	 * @param string $sql sql
	 * 
	 * @return RedBean_SQLHelper 
	 */
	public function addSQL($sql) {
		if ($this->capture) {
			$this->sql .= ' '.$sql . ' ';
			return $this;
		}
	}
	
	
	/**
	 * Returns query parts.
	 * 
	 * @return array $queryParts query parts. 
	 */
	public function getQuery() {
		$list = array($this->sql,$this->params);
		$this->clear();
		return $list;
	}

	/**
	 * Writes a '(' to the sql query.
	 */
	public function open() {
		if ($this->capture) {
			$this->sql .= ' ( ';
			return $this;
		}
	}
	
	/**
	 * Writes a ')' to the sql query.
	 */
	public function close() {
		if ($this->capture) {
			$this->sql .= ' ) ';
			return $this;
		}
	}
	
}