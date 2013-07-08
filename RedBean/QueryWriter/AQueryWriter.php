<?php
/**
 * RedBean Abstract Query Writer
 *
 * @file    RedBean/QueryWriter/AQueryWriter.php
 * @desc    Query Writer
 *	         Represents an abstract Database to RedBean
 *          To write a driver for a different database for RedBean
 *          Contains a number of functions all implementors can
 *          inherit or override.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
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
	 * Glues an SQL snippet to the beginning of a WHERE clause.
	 * If the snippet begins with a condition glue (OR/AND) or a non-condition
	 * keyword then no glue is required.
	 * 
	 * @staticvar array $snippetCache
	 * 
	 * @param string $sql SQL Snippet
	 * 
	 * @return array|string
	 */
	public function glueSQLCondition($sql) {
		static $snippetCache = array();
		if (isset($snippetCache[$sql])) {
			return $snippetCache[$sql];
		}
		if (trim($sql) === '') {
			return $sql;
		}
		if (preg_match('/^(AND|OR|WHERE|ORDER|GROUP|HAVING|LIMIT|OFFSET)\s+/i', ltrim($sql))) {
			$snippetCache[$sql] = $sql;
		} else {
			$snippetCache[$sql] = ' WHERE '.$sql;
		}
		return $snippetCache[$sql];
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
		if ($id && !count($updatevalues)) {
			return $id;
		}
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
	protected function insertRecord($type, $insertcolumns, $insertvalues) {
		$default = $this->defaultValue;
		$suffix = $this->getInsertSuffix($type);
		$table = $this->esc($type);
		if (count($insertvalues) > 0 && is_array($insertvalues[0]) && count($insertvalues[0]) > 0) {
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
	public function queryRecord($type, $conditions = array(), $addSql = null, $params = array()) {
		
		$addSql = $this->glueSQLCondition($addSql);
		if ($this->flagUseCache) {
			$key = $this->getCacheKey(array($conditions, $addSql, $params, 'select'));
			if ($cached = $this->getCached($type, $key)) {
				return $cached;
			}
		}
		$table = $this->esc($type);
		$sql = $this->makeSQLFromConditions($conditions, $params, $addSql);
		$sql = "SELECT * FROM {$table} {$sql} -- keep-cache";
		$rows = $this->adapter->get($sql, $params);
		if ($this->flagUseCache && $key) {
			$this->putResultInCache($type, $key, $rows);
		}
		return $rows;
	}
	
	/**
	 * @see RedBean_QueryWriter::queryRecordCount
	 */
	public function queryRecordCount($type, $conditions = array(), $addSql = null, $params = array()) {
		$addSql = $this->glueSQLCondition($addSql);
		$table = $this->esc($type);
		$sql = $this->makeSQLFromConditions($conditions, $params, $addSql);
		$sql = "SELECT COUNT(*) FROM {$table} {$sql} -- keep-cache";
		return $this->adapter->getCell($sql, $params);
	}
	
	/**
	 * @see RedBean_QueryWriter::deleteRecord
	 */
	public function deleteRecord($type, $conditions = array(), $addSql = null, $params = array()) {
		$addSql = $this->glueSQLCondition($addSql);
		$table = $this->esc($type);
		$sql = $this->makeSQLFromConditions($conditions, $params, $addSql);
		$sql = "DELETE FROM {$table} {$sql}";
		$this->adapter->exec($sql, $params);
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
	 * Creates an SQL snippet from a list of conditions of format:
	 * 
	 * array(
	 *    key => array( 
	 *		   value1, value2, value3 ....
	 *		)
	 * )
	 * @param array   $conditions
	 * @param array   $params
	 * @param string  $addSql
	 * 
	 * @return string
	 */
	private function makeSQLFromConditions($conditions, &$params, $addSql = '') {
		$sqlConditions = array();
		foreach($conditions as $column => $values) {
			if (!count($values)) continue;
			$sql = $this->esc($column);
			$sql .= ' IN ( ';
			if (!is_array($values)) {
				$values = array($values);
			}
			//if it's safe to skip bindings, do so...
			if (preg_match('/^\d+$/', implode('', $values))) {
				$sql .= implode(',', $values).' ) ';
				//only numeric, cant do much harm.
				$sqlConditions[] = $sql;
			} else { 
				$sql .= implode(',', array_fill(0, count($values), '?')).' ) ';
				$sqlConditions[] = $sql;
				foreach($values as $k => $v) {
					$values[$k] = strval($v);
					array_unshift($params, $v);
				}
			}
		}
		$sql = '';
		if (is_array($sqlConditions) && count($sqlConditions)>0) {
			$sql = implode(' AND ', $sqlConditions);
			$sql = " WHERE ( $sql ) ";
			if ($addSql) $sql .= $addSql;
		} elseif ($addSql) {
			$sql = $addSql;
		}
		return $sql;
	}
	
	
	
	/**
	 * @see RedBean_QueryWriter::queryRecordRelated
	 */
	public function queryRecordRelated($sourceType, $destType, $linkIDs, $addSql = '', $params = array()) {
		list($sourceTable, $destTable, $linkTable, $sourceCol, $destCol) = $this->getRelationalTablesAndColumns($sourceType, $destType);
		$key = $this->getCacheKey(array($sourceType, $destType, implode(',', $linkIDs), $addSql, $params));
		if ($this->flagUseCache && $cached = $this->getCached($destType, $key)) {
			return $cached;
		}
		$inClause = implode(',', array_fill(0, count($linkIDs), '?'));
		if ($sourceType === $destType) {
			$sql = "
			SELECT 
				{$destTable}.*,
				COALESCE(
				NULLIF({$linkTable}.{$sourceCol}, {$destTable}.id), 
				NULLIF({$linkTable}.{$destCol}, {$destTable}.id)) AS __linked_by
			FROM {$linkTable}
			INNER JOIN {$destTable} ON 
			( {$destTable}.id = {$linkTable}.{$destCol} AND {$linkTable}.{$sourceCol} IN ($inClause) ) OR
			( {$destTable}.id = {$linkTable}.{$sourceCol} AND {$linkTable}.{$destCol} IN ($inClause) )
			{$addSql}
			-- keep-cache";
			$linkIDs = array_merge($linkIDs,$linkIDs);
		} else {
			$sql = "
			SELECT 
				{$destTable}.*,
				{$linkTable}.{$sourceCol} AS __linked_by	
			FROM {$linkTable}
			INNER JOIN {$destTable} ON 
			( {$destTable}.id = {$linkTable}.{$destCol} AND {$linkTable}.{$sourceCol} IN ($inClause) )
			{$addSql}	
			-- keep-cache";
		}
		$params = array_merge($linkIDs, $params);	
		$rows = array();
		$rows = $this->adapter->get($sql, $params);
		$this->putResultInCache($destType, $key, $rows);
		return $rows;
	}
	
	/**
	 * @see RedBean_QueryWriter::queryRecordLinks
	 */
	public function queryRecordLinks($sourceType, $destType, $linkIDs, $addSql = '', $params = array()) {
		list($sourceTable, $destTable, $linkTable, $sourceCol, $destCol) = $this->getRelationalTablesAndColumns($sourceType, $destType);
		$key = $this->getCacheKey(array($sourceType, $destType, implode(',',$linkIDs), $addSql, $params));
		if ($this->flagUseCache && $cached = $this->getCached($linkTable, $key)) {
			return $cached;
		}
		$inClause = implode(',', array_fill(0, count($linkIDs), '?'));
		$selector = "{$linkTable}.*"; 
		if ($sourceType === $destType) {
			$sql = "
			SELECT {$selector} FROM {$linkTable}
			INNER JOIN {$destTable} ON 
			( {$destTable}.id = {$linkTable}.{$destCol} AND {$linkTable}.{$sourceCol} IN ($inClause) ) OR
			( {$destTable}.id = {$linkTable}.{$sourceCol} AND {$linkTable}.{$destCol} IN ($inClause) )
			{$addSql}
			-- keep-cache";
			$linkIDs = array_merge($linkIDs,$linkIDs);
		} else {
			$sql = "
			SELECT {$selector} FROM {$linkTable}
			INNER JOIN {$destTable} ON 
			( {$destTable}.id = {$linkTable}.{$destCol} AND {$linkTable}.{$sourceCol} IN ($inClause) )
			{$addSql}	
			-- keep-cache";
		}
		$params = array_merge($linkIDs, $params);	
		$rows = array();
		$rows = $this->adapter->get($sql, $params);
		$this->putResultInCache($linkTable, $key, $rows);
		return $rows;
	}
	
	/**
	 * @see RedBean_QueryWriter::queryRecordCountRelated
	 */
	public function queryRecordCountRelated($sourceType, $destType, $linkID, $addSql = '', $params = array()) {
		list($sourceTable, $destTable, $linkTable, $sourceCol, $destCol) = $this->getRelationalTablesAndColumns($sourceType, $destType);
		if ($sourceType === $destType) {
			$sql = "
			SELECT COUNT(*) FROM {$linkTable}
			INNER JOIN {$destTable} ON 
			( {$destTable}.id = {$linkTable}.{$destCol} AND {$linkTable}.{$sourceCol} = ? ) OR
			( {$destTable}.id = {$linkTable}.{$sourceCol} AND {$linkTable}.{$destCol} = ? )
			{$addSql}
			-- keep-cache";
			$params = array_merge(array($linkID, $linkID), $params);	
		} else {
			$sql = "
			SELECT COUNT(*) FROM {$linkTable}
			INNER JOIN {$destTable} ON 
			( {$destTable}.id = {$linkTable}.{$destCol} AND {$linkTable}.{$sourceCol} = ? )
			{$addSql}	
			-- keep-cache";
			$params = array_merge(array($linkID), $params);	
		}
		return (int) $this->adapter->getCell($sql, $params);
	}
	
	/**
	 * Returns the table names and column names for a relational query.
	 *  
	 * @param string $sourceType
	 * @param string $destType
	 * @param boolean $noQuote
	 * 
	 * @return array
	 */
	private function getRelationalTablesAndColumns($sourceType, $destType, $noQuote = false) {
		$linkTable = $this->esc($this->getAssocTable(array($sourceType, $destType)), $noQuote);
		$sourceCol = $this->esc($sourceType.'_id', $noQuote);
		$destCol = ($sourceType === $destType) ? $this->esc($destType.'2_id', $noQuote) : $this->esc($destType.'_id', $noQuote);
		$sourceTable = $this->esc($sourceType, $noQuote);
		$destTable = $this->esc($destType, $noQuote);
		return array($sourceTable, $destTable, $linkTable, $sourceCol, $destCol);
	}
	
	/**
	 * @see RedBean_QueryWriter::deleteRelations
	 */
	public function deleteRelations($sourceType, $destType, $sourceID) {
		list($sourceTable, $destTable, $linkTable, $sourceCol, $destCol) = $this->getRelationalTablesAndColumns($sourceType, $destType);
		if ($sourceTable === $destTable) {
			$sql = "DELETE FROM {$linkTable} 
				WHERE ( {$sourceCol} = ? ) OR 
				( {$destCol} = ?  )
			";
			$this->adapter->exec($sql, array($sourceID, $sourceID));	
		} else {
			$sql = "DELETE FROM {$linkTable} 
				WHERE {$sourceCol} = ? ";
			$this->adapter->exec($sql, array($sourceID));
		}
	}
	
	/**
	 * @see RedBean_QueryWriter::queryRecordLink
	 */
	public function queryRecordLink($sourceType, $destType, $sourceID, $destID) {
		list($sourceTable, $destTable, $linkTable, $sourceCol, $destCol) = $this->getRelationalTablesAndColumns($sourceType, $destType);
		$key = $this->getCacheKey(array($sourceType, $destType, $sourceID, $destID));
		if ($this->flagUseCache && $cached = $this->getCached($linkTable, $key)) {
			return $cached;
		}
		if ($sourceTable === $destTable) {
			$sql = "SELECT {$linkTable}.* FROM {$linkTable} 
				WHERE ( {$sourceCol} = ? AND {$destCol} = ? ) OR 
				 ( {$destCol} = ? AND {$sourceCol} = ? ) 
				";
			$row = $this->adapter->getRow($sql, array($sourceID, $destID, $sourceID, $destID));
		} else {
			$sql = "SELECT {$linkTable}.* FROM {$linkTable} 
				WHERE {$sourceCol} = ? AND {$destCol} = ? ";
			$row = $this->adapter->getRow($sql, array($sourceID, $destID));	
		}
		$this->putResultInCache($linkTable, $key, $row);
		return $row;
	}
	
	
	/**
	 * @see RedBean_QueryWriter::wipe
	 */
	public function wipe($type) {
		$table = $this->esc($type);
		$this->adapter->exec("TRUNCATE $table ");
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
	 * @see RedBean_QueryWriter::addConstraintForTypes
	 */
	public function addConstraintForTypes($sourceType, $destType) {
		list($sourceTable, $destTable, $linkTable, $sourceCol, $destCol) = $this->getRelationalTablesAndColumns($sourceType, $destType, true);
		return $this->constrain($linkTable, $sourceTable, $destTable, $sourceCol, $destCol);
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
	 * 
	 * @param string  $a column to be escaped
	 * @param boolean $b omit quotes
	 * 
	 * @return string
	 */
	public function safeColumn($a, $b = false) { 
		return $this->esc($a, $b); 
	}
	
	/**
	 * @deprecated Use esc() instead.
	 *
	 * @param string  $a column to be escaped
	 * @param boolean $b omit quotes
	 * 
	 * @return string

	 */
	public function safeTable($a, $b = false) { 
		return $this->esc($a, $b); 
	}
	
	/**
	 * @deprecated Use addContraintForTypes instead.
	 * 
	 * @param RedBean_Bean $bean1 bean
	 * @param RedBean_Bean $bean2 bean
	 */
	public function addConstraint(RedBean_OODBBean $bean1, RedBean_OODBBean $bean2) { 
		return $this->addConstraintForTypes($bean1->getMeta('type'), $bean2->getMeta('type')); 
	}
}