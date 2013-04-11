<?php
/**
 * RedBean Abstract Query Writer
 *
 * @file 			RedBean/QueryWriter/AQueryWriter.php
 * @description		Quert Writer
 *					Represents an abstract Database to RedBean
 *					To write a driver for a different database for RedBean
 *					Contains a number of functions all implementors can
 *					inherit or override.
 * @author			Gabor de Mooij and the RedBeanPHP Community
 * @license			BSD/GPLv2
 *
 *
 * (c) copyright G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
abstract class RedBean_QueryWriter_AQueryWriter {
	/**
	 * @var array
	 */
	public $typeno_sqltype = array();
	/**
	 * @var RedBean_Adapter_DBAdapter
	 */
	protected $adapter;
	/**
	 * @var string
	 */
	protected $defaultValue = 'NULL';
	/**
	 * @var string
	 */
	protected $quoteCharacter = '';
	/**
	 * @var boolean
	 */
	protected $flagUseCache = false;
	/**
	 * @var array 
	 */
	protected $cache = array();
	/**
	 * @var array
	 */
	protected static $renames = array();
	/**
	 * Returns the sql that should follow an insert statement.
	 *
	 * @param string $table name
	 *
	 * @return string sql
	 */
  	protected function getInsertSuffix ($table) {
    	return '';
  	}
	/**
	 * @see RedBean_QueryWriter::esc
	 */
	public function esc($dbStructure, $dontQuote = false) {
		$this->check($dbStructure);
		return ($dontQuote) ? $dbStructure : $this->quoteCharacter.$dbStructure.$this->quoteCharacter;
	}
	/**
	 * Checks table name or column name.
	 *
	 * @param string $table table string
	 *
	 * @return string $table escaped string
	 */
	protected function check($struct) {
		if (!preg_match('/^[a-zA-Z0-9_]+$/', $struct)) {
		  throw new RedBean_Exception_Security('Identifier does not conform to RedBeanPHP security policies.');
	    }
		return $struct;
	}
	/**
	 * @see RedBean_QueryWriter::addColumn
	 */
	public function addColumn($type, $column, $field) {
		$table = $type;
		$type = $field;
		$table = $this->esc($table);
		$column = $this->esc($column);
		$type = (isset($this->typeno_sqltype[$type])) ? $this->typeno_sqltype[$type] : '';
		$sql = "ALTER TABLE $table ADD $column $type ";
		$this->adapter->exec($sql);
	}
	/**
	 * @see RedBean_QueryWriter::updateRecord
	 */
	public function updateRecord($type, $updatevalues, $id = null) {
		$table = $type;
		if (!$id) {
			$insertcolumns =  $insertvalues = array();
			foreach($updatevalues as $pair) {
				$insertcolumns[] = $pair['property'];
				$insertvalues[] = $pair['value'];
			}
			return $this->insertRecord($table, $insertcolumns, array($insertvalues));
		}
		if ($id && !count($updatevalues)) return $id;	
		$table = $this->esc($table);
		$sql = "UPDATE $table SET ";
		$p = $v = array();
		foreach($updatevalues as $uv) {
			$p[] = " {$this->esc($uv["property"])} = ? ";
			$v[] = $uv['value'];
		}
		$sql .= implode(',', $p).' WHERE id = '.intval($id);
		$this->adapter->exec($sql, $v);
		return $id;
	}
	/**
	 * Inserts a record into the database using a series of insert columns
	 * and corresponding insertvalues. Returns the insert id.
	 *
	 * @param string $table			  table to perform query on
	 * @param array  $insertcolumns columns to be inserted
	 * @param array  $insertvalues  values to be inserted
	 *
	 * @return integer $insertid	  insert id from driver, new record id
	 */
	protected function insertRecord($table, $insertcolumns, $insertvalues) {
		$default = $this->defaultValue;
		$suffix = $this->getInsertSuffix($table);
		$table = $this->esc($table);
		if (count($insertvalues)>0 && is_array($insertvalues[0]) && count($insertvalues[0])>0) {
			foreach($insertcolumns as $k => $v) {
				$insertcolumns[$k] = $this->esc($v);
			}
			$insertSQL = "INSERT INTO $table ( id, ".implode(',', $insertcolumns)." ) VALUES 
			( $default, ". implode(',', array_fill(0, count($insertcolumns), ' ? '))." ) $suffix";

			foreach($insertvalues as $i => $insertvalue) {
				$ids[] = $this->adapter->getCell($insertSQL, $insertvalue, $i);
			}
			$result = count($ids) === 1 ? array_pop($ids) : $ids;
		} else {
			$result = $this->adapter->getCell("INSERT INTO $table (id) VALUES($default) $suffix");
		}
		if ($suffix) return $result;
		$last_id = $this->adapter->getInsertID();
		return $last_id;
	}
	/**
	 * @see RedBean_QueryWriter::selectRecord
	 */
	public function selectRecord($type, $conditions, $addSql = null, $delete = null, $inverse = false, $all = false) { 
		if (!is_array($conditions)) throw new Exception('Conditions must be an array');
		if (!$delete && $this->flagUseCache) {
			$key = serialize(array($type, $conditions, $addSql, $inverse, $all));
			$sql = $this->adapter->getSQL();
			if (strpos($sql, '-- keep-cache') !== strlen($sql)-13) {
				//If SQL has been taken place outside of this method then something else then
				//a select query might have happened! (or instruct to keep cache)
				$this->cache = array();
			} else {
				if (isset($this->cache[$key])) return $this->cache[$key];
			}
		}
		$table = $this->esc($type);
		$sqlConditions = array();
		$bindings = array();
		foreach($conditions as $column => $values) {
			if (!count($values)) continue;
			$sql = $this->esc($column);
			$sql .= ' '.($inverse ? ' NOT ':'').' IN ( ';
			//If its safe to not use bindings please do... (fixes SQLite PDO issue limit 256 bindings)
			if (is_array($conditions)
				&& count($conditions) === 1 
				&& isset($conditions['id']) 
				&& is_array($values) 
				&& preg_match('/^\d+$/', implode('', $values))) {
				$sql .= implode(',', $values).') ';
				$sqlConditions[] = $sql;
			} else {
				$sql .= implode(',', array_fill(0, count($values), '?')).') ';
				$sqlConditions[] = $sql;
				if (!is_array($values)) $values = array($values);
				foreach($values as $k => $v) {
					$values[$k] = strval($v);
				}
				$bindings = array_merge($bindings, $values);
			}
		}
		if (is_array($addSql)) {
			if (count($addSql)>1) {
				$bindings = array_merge($bindings, $addSql[1]);
			} else {
				$bindings = array();
			}
			$addSql = $addSql[0];
		}
		$sql = '';
		if (is_array($sqlConditions) && count($sqlConditions)>0) {
			$sql = implode(' AND ', $sqlConditions);
			$sql = " WHERE ( $sql ) ";
			if ($addSql) $sql .= ($all ? '': ' AND ') . " $addSql ";
		}
		elseif ($addSql) {
			if ($all) {
				$sql = " $addSql ";
			} else {
				$sql = " WHERE $addSql ";
			}
		}
		$sql = (($delete) ? 'DELETE FROM ' : 'SELECT * FROM ').$table.$sql;
		$rows = $this->adapter->get($sql.(($delete) ? '' : ' -- keep-cache'), $bindings);
		if (!$delete && $this->flagUseCache) {
			$this->cache[$key] = $rows;
		}
		return $rows;
	}
	/**
	 * @see RedBean_QueryWriter::wipe
	 */
	public function wipe($type) {
		$table = $this->esc($type);
		$this->adapter->exec("TRUNCATE $table ");
	}
	/**
	 * @see RedBean_QueryWriter::count
	 */
	public function count($beanType, $addSQL = '', $params = array()) {
		$sql = "SELECT count(*) FROM {$this->esc($beanType)} ";
		if ($addSQL != '') $addSQL = ' WHERE '.$addSQL; 
		return (int) $this->adapter->getCell($sql.$addSQL, $params);
	}
	/**
	 * Checks whether a number can be treated like an int.
	 *
	 * @param  string $value string representation of a certain value
	 *
	 * @return boolean $value boolean result of analysis
	 */
	public static function canBeTreatedAsInt($value) {
		return (boolean) (ctype_digit(strval($value)) && strval($value) === strval(intval($value)));
	}
	/**
	 * @see RedBean_QueryWriter::addFK
	 */
	public function addFK($type, $targetType, $field, $targetField, $isDependent = false) {
		$table = $this->esc($type);
		$tableNoQ = $this->esc($type, true);
		$targetTable = $this->esc($targetType);
		$column = $this->esc($field);
		$columnNoQ = $this->esc($field, true);
		$targetColumn  = $this->esc($targetField);
		$targetColumnNoQ  = $this->esc($targetField, true);
		$db = $this->adapter->getCell('select database()');
		$fkName = 'fk_'.$tableNoQ.'_'.$columnNoQ.'_'.$targetColumnNoQ.($isDependent ? '_casc':'');
		$cName = 'cons_'.$fkName;
		$cfks =  $this->adapter->getCell("
			SELECT CONSTRAINT_NAME
			FROM information_schema.KEY_COLUMN_USAGE
			WHERE TABLE_SCHEMA ='$db' AND TABLE_NAME = '$tableNoQ'  AND COLUMN_NAME = '$columnNoQ' AND
			CONSTRAINT_NAME <>'PRIMARY' AND REFERENCED_TABLE_NAME is not null
		");
		$flagAddKey = false;
		try{
			//No keys
			if (!$cfks) {
				$flagAddKey = true; //go get a new key
			}
			//has fk, but different setting, --remove
			if ($cfks && $cfks != $cName) {
				$this->adapter->exec("ALTER TABLE $table DROP FOREIGN KEY $cfks ");
				$flagAddKey = true; //go get a new key.
			}
			if ($flagAddKey) { 
				$this->adapter->exec("ALTER TABLE  $table
				ADD CONSTRAINT $cName FOREIGN KEY $fkName (  $column ) REFERENCES  $targetTable (
				$targetColumn) ON DELETE ".($isDependent ? 'CASCADE':'SET NULL').' ON UPDATE SET NULL ;');
			}
		} catch(Exception $e) {} //Failure of fk-constraints is not a problem
	}
	/**
	 * Renames an association. For instance if you would like to refer to
	 * album_song as: track you can specify this by calling this method like:
	 * 
	 * renameAssociation('album_song','track')
	 * 
	 * This allows:
	 * 
	 * $album->sharedSong 
	 * 
	 * to add/retrieve beans from track instead of album_song.
	 * Also works for exportAll().
	 * 
	 * This method also accepts a single associative array as
	 * its first argument.
	 * 
	 * @param string|array $from
	 * @param string $to (optional)
	 * 
	 * @return void 
	 */
	public static function renameAssociation($from, $to = null) {
		if (is_array($from)) {
			foreach($from as $key => $value) self::$renames[$key] = $value;
			return;
		}
		self::$renames[$from] = $to;
	}
	/**
	 * Returns the format for link tables.
	 * Given an array containing two type names this method returns the
	 * name of the link table to be used to store and retrieve
	 * association records.
	 *
	 * @param  array $types two types array($type1, $type2)
	 *
	 * @return string $linktable name of the link table
	 */
	public static function getAssocTableFormat($types) {
		sort($types);
		$assoc = (implode('_', $types));
		return (isset(self::$renames[$assoc])) ? self::$renames[$assoc] : $assoc;
	}
	/**
	 * @see RedBean_QueryWriter::addConstraint
	 */
	public function addConstraint(RedBean_OODBBean $bean1, RedBean_OODBBean $bean2) {
		$table1 = $bean1->getMeta('type');
		$table2 = $bean2->getMeta('type');
		$writer = $this;
		$adapter = $this->adapter;
		$table = RedBean_QueryWriter_AQueryWriter::getAssocTableFormat(array($table1, $table2));
		$property1 = $bean1->getMeta('type') . '_id';
		$property2 = $bean2->getMeta('type') . '_id';
		if ($property1 == $property2) $property2 = $bean2->getMeta('type').'2_id';
		$table = $this->esc($table, true);
		$table1 = $this->esc($table1, true);
		$table2 = $this->esc($table2, true);
		$property1 = $this->esc($property1, true);
		$property2 = $this->esc($property2, true);
		//Dispatch to right method
		return $this->constrain($table, $table1, $table2, $property1, $property2);
	}
	/**
	 * Checks whether a value starts with zeros. In this case
	 * the value should probably be stored using a text datatype instead of a
	 * numerical type in order to preserve the zeros.
	 * 
	 * @param string $value value to be checked.
	 */
	protected function startsWithZeros($value) {
		$value = strval($value);
		if (strlen($value)>1 && strpos($value, '0') === 0 && strpos($value, '0.') !==0) {
			return true;
		} else {
			return false;
		}
	}
	/**
	 * Turns caching on or off. Default: off.
	 * If caching is turned on retrieval queries fired after eachother will
	 * use a result row cache.
	 * 
	 * @param boolean $yesNo 
	 */
	public function setUseCache($yesNo) {
		$this->flushCache();
		$this->flagUseCache = (boolean) $yesNo;
	}
	/**
	 * Flushes the Query Writer Cache.
	 */
	public function flushCache() {
		$this->cache = array();
	}
	/**
	 * @deprecated Use esc() instead.
	 */
	public function safeColumn($a, $b = false) { return $this->esc($a, $b); }
	public function safeTable($a, $b = false) { return $this->esc($a, $b); }
}