<?php
/**
 * RedBean Abstract Query Writer
 *
 * @file 			RedBean/QueryWriter/AQueryWriter.php
 * @description	Query Writer
 *						Represents an abstract Database to RedBean
 *						To write a driver for a different database for RedBean
 *						Contains a number of functions all implementors can
 *						inherit or override.
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
	 * Query writer mode
	 */
	const C_MODE_SELECT = 0;
	const C_MODE_DELETE = 1;
	const C_MODE_COUNT = 2;
	
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
	 * Sets the ID SQL Snippet to use.
	 * This can be used to use something different than NULL for the ID value,
	 * for instance an UUID SQL function.
	 * Returns the old value. So you can restore it later.
	 * 
	 * @param string $sql pure SQL (don't use this for user input)
	 * 
	 * @return string  
	 */
	public function setNewIDSQL($sql) {
		$old = $this->defaultValue;
		$this->defaultValue = $sql;
		return $old;
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
		$sql .= implode(',', $p).' WHERE id = ? ';
		$v[] = $id;
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
	 * @see RedBean_QueryWriter::queryRecord
	 */
	public function queryRecord($type, $conditions, $addSql = null, $all = false) {
		return $this->writeStandardQuery($type, $conditions, $addSql, self::C_MODE_SELECT, false, $all);
	}
	/**
	 * @see RedBean_QueryWriter::queryRecordCount
	 */
	public function queryRecordCount($type, $conditions, $addSql = null) {
		$rows = $this->writeStandardQuery($type, $conditions, $addSql, self::C_MODE_COUNT, false, false);
		if (is_array($rows) && is_array($rows[0])) return (integer) reset($rows[0]);
		return 0;
	}
	/**
	 * @see RedBean_QueryWriter::deleteRecord
	 */
	public function deleteRecord($type, $conditions, $addSql = null) {
		return $this->writeStandardQuery($type, $conditions, $addSql, self::C_MODE_DELETE, false, false);
	}
	/**
	 * @see RedBean_QueryWriter::queryRecordInverse
	 */
	public function queryRecordInverse($type, $conditions, $addSql = null) {
		return $this->writeStandardQuery($type, $conditions, $addSql, self::C_MODE_SELECT, true, false);
	}
	/**
	 * @deprecated
	 * @see RedBean_QueryWriter::selectRecord
	 */
	public function selectRecord($type, $conditions, $addSql = null, $delete = null, $inverse = false, $all = false) { 
		return $this->writeStandardQuery($type, $conditions, $addSql, ($delete) ? self::C_MODE_DELETE : self::C_MODE_SELECT, $inverse, $all);
	}
	
	/**
	 * Returns a cache key for the cache values passed.
	 * This method returns a fingerprint string to be used as a key to store
	 * data in the writer cache.
	 * 
	 * @param array $keyValues
	 * @return string
	 */
	private function getCacheKey($keyValues) {
		return serialize($keyValues);
	}
	
	/**
	 * Returns the values associated with the provided cache tag and key.
	 * 
	 * @param string $cacheTag
	 * @param string $key
	 * 
	 * @return mixed
	 */
	private function getCached($cacheTag, $key) {
		$sql = $this->adapter->getSQL();
		if (strpos($sql, '-- keep-cache') !== strlen($sql)-13) {
			//If SQL has been taken place outside of this method then something else then
			//a select query might have happened! (or instruct to keep cache)
			$this->cache = array();
		} else {
			if (isset($this->cache[$cacheTag][$key])) {
				return $this->cache[$cacheTag][$key];
			}
		}
		return null;
	}
	
	/**
	 * Stores data from the writer in the cache under a specific key and cache tag.
	 * A cache tag is used to make sure the cache remains consistent. In most cases the cache tag
	 * will be the bean type, this makes sure queries associated with a certain reference type will
	 * never contain conflicting data.
	 * You can only store one item under a cache tag. Why not use the cache tag as a key? Well
	 * we need to make sure the cache contents fits the key (and key is based on the cache values).
	 * Otherwise it would be possible to store two different result sets under the same key (the cache tag).
	 * 
	 * @param string $cacheTag cache tag (secondary key)
	 * @param string $key      key
	 * @param array  $values   content to be stored
	 */
	private function putResultInCache($cacheTag, $key, $values) {
		$this->cache[$cacheTag] = array(
			 $key => $values
		);
	}
	
	/**
	 * Internal method to build query.
	 * 
	 * @param string       $type       name of the table you want to query
	 * @param array        $conditions criteria ( $column => array( $values ) )
	 * @param string|array $addSql     additional SQL snippet, either a string or: array($SQL, $bindings)
	 * @param boolean      $mode		  selects query mode: 1 is DELETE, 0 is SELECT, 2 is COUNT(*)
	 * @param boolean      $inverse    if TRUE uses 'NOT IN'-clause for conditions
	 * @param boolean      $all        if FALSE and $addSQL is SET prefixes $addSQL with ' WHERE ' or ' AND ' 
	 */		
	private function writeStandardQuery($type, $conditions, $addSql = null, $mode = null, $inverse = false, $all = false) {	
		if (!is_array($conditions)) throw new Exception('Conditions must be an array');
		if (!($mode===self::C_MODE_DELETE) && $this->flagUseCache) {
			$key = $this->getCacheKey(array($conditions, $addSql, $mode, $inverse, $all));
			if ($cached = $this->getCached($type, $key)) return $cached;
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
				&& preg_match('/^[\d\w\-]+$/', implode('', $values))) {
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
		if ($mode === self::C_MODE_DELETE) $sqlBegin = 'DELETE FROM ';
		elseif($mode === self::C_MODE_COUNT) $sqlBegin = 'SELECT COUNT(*) FROM ';
		else $sqlBegin = 'SELECT * FROM ';
		$sql = $sqlBegin . $table . $sql;
		$rows = $this->adapter->get($sql.(($mode === 1) ? '' : ' -- keep-cache'), $bindings);
		if (!($mode === 1) && $this->flagUseCache && $key) {
			$this->putResultInCache($type, $key, $rows);
		}
		return $rows;
	}
	
	
	public function queryRecordRelated($sourceType, $destType, $linkID, $addSql = '', $params = array()) {
		return $this->writeRelationalQuery($sourceType, $destType, $linkID, $addSql, $params, self::C_MODE_SELECT);
	}
	
	/**
	 * @see RedBean_QueryWriter::queryRecordCountRelated
	 */
	public function queryRecordCountRelated($sourceType, $destType, $linkID, $addSql = '', $params = array()) {
		return $this->writeRelationalQuery($sourceType, $destType, $linkID, $addSql, $params, self::C_MODE_COUNT);
	}
	
	/**
	 * Writes a relational query.
	 * 
	 * @param type $sourceType
	 * @param type $destType
	 * @param type $linkID
	 * @param type $addSql
	 * @param type $params
	 * @param type $mode
	 * @return type
	 * @throws RedBean_Exception_SQL
	 */
	private function writeRelationalQuery($sourceType, $destType, $linkID, $addSql = '', $params = array(), $mode = self::C_MODE_SELECT) {
		$key = $this->getCacheKey(array($sourceType, $destType, $linkID, $addSql, $params, $mode));
		if ($this->flagUseCache && $mode === self::C_MODE_SELECT && $cached = $this->getCached($destType, $key)) {
			return $cached;
		}
		$sourceTable = $this->esc($sourceType);
		$destTable = $this->esc($destType);
		$linkTable = $this->getAssocTable(array($sourceType, $destType));
		$sourceCol = $this->esc($sourceType.'_id');
		$destCol = $this->esc($destType.'_id');
		$selector = ($mode === self::C_MODE_SELECT) ? "{$destTable}.*" : "count(*)";
		if ($sourceType === $destType) {
			$destCol = $this->esc($sourceType.'2_id');
			$sql = "
			SELECT {$selector} FROM {$linkTable}
			INNER JOIN {$destTable} ON 
			( {$destTable}.id = {$linkTable}.{$destCol} AND {$linkTable}.{$sourceCol} = ? ) OR
			( {$destTable}.id = {$linkTable}.{$sourceCol} AND {$linkTable}.{$destCol} = ? )
			$addSql
			-- keep-cache";
			array_unshift($params, $linkID);
		} else {
			$sql = "
			SELECT {$selector} FROM {$linkTable}
			INNER JOIN {$destTable} ON 
			( {$destTable}.id = {$linkTable}.{$destCol} AND {$linkTable}.{$sourceCol} = ? )
			$addSql	
			-- keep-cache";
		}
		array_unshift($params, $linkID);	
		$rows = ($mode === self::C_MODE_COUNT) ? 0 : array();
		try {
			if ($mode === self::C_MODE_COUNT) {
				return (int) $this->adapter->getCell($sql, $params);
			} else {
				$rows = $this->adapter->get($sql, $params);
				$this->putResultInCache($destType, $key, $rows);
				return $rows;
			}
		} catch(RedBean_Exception_SQL $e) {
			if (!$this->sqlStateIn($e->getSQLState(),
			array(
				RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_TABLE,
				RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN)
			)) throw $e;
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
		$db = $this->adapter->getCell('SELECT DATABASE()');
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
	 * @see RedBean_QueryWriter::renameAssociation
	 */
	public static function renameAssociation($from, $to = null) {
		if (is_array($from)) {
			foreach($from as $key => $value) self::$renames[$key] = $value;
			return;
		}
		self::$renames[$from] = $to;
	}
	
	/**
	 * @see RedBean_QueryWriter::renameAssocTable
	 */
	public function renameAssocTable($from, $to = null) {
		return self::renameAssociation($from, $to);
	}
	
	/**
	 * @see RedBean_QueryWriter::getAssocTableFormat
	 */
	public static function getAssocTableFormat($types) {
		sort($types);
		$assoc = (implode('_', $types));
		return (isset(self::$renames[$assoc])) ? self::$renames[$assoc] : $assoc;
	}
	
	/**
	 * @see RedBean_QueryWriter::getAssocTable
	 */
	public function getAssocTable($types) {
		return self::getAssocTableFormat($types);
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
