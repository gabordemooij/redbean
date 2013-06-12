<?php
/**
 * DBAdapter		(Database Adapter)
 * @file			RedBean/Adapter/DBAdapter.php
 * @desc			An adapter class to connect various database systems to RedBean
 * @author			Gabor de Mooij and the RedBeanPHP Community. 
 * @license			BSD/GPLv2
 *
 * (c) copyright G.J.G.T. (Gabor) de Mooij and the RedBeanPHP community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_Adapter_DBAdapter extends RedBean_Observable implements RedBean_Adapter {
	private $db = null;
	private $sql = '';
	/**
	 * Constructor.
	 * Creates an instance of the RedBean Adapter Class.
	 * This class provides an interface for RedBean to work
	 * with ADO compatible DB instances.
	 *
	 * @param RedBean_Driver $database ADO Compatible DB Instance
	 */
	public function __construct($database) {
		$this->db = $database;
	}
	/**
	 * @see RedBean_Adapter::getSQL
	 */
	public function getSQL() {
		return $this->sql;
	}
	/**
	 * @see RedBean_Adapter::exec
	 */
	public function exec($sql, $aValues = array(), $noevent = false) {
		if (!$noevent) {
			$this->sql = $sql;
			$this->signal('sql_exec', $this);
		}
		return $this->db->Execute($sql, $aValues);
	}
	/**
	 * @see RedBean_Adapter::get
	 */
	public function get($sql, $aValues = array()) {
		$this->sql = $sql;
		$this->signal('sql_exec', $this);
		return $this->db->GetAll($sql, $aValues);
	}
	/**
	 * @see RedBean_Adapter::getRow
	 */
	public function getRow($sql, $aValues = array()) {
		$this->sql = $sql;
		$this->signal('sql_exec', $this);
		return $this->db->GetRow($sql, $aValues);
	}
	/**
	 * @see RedBean_Adapter::getCol
	 */
	public function getCol($sql, $aValues = array()) {
		$this->sql = $sql;
		$this->signal('sql_exec', $this);
		return $this->db->GetCol($sql, $aValues);
	}
	/**
	 * @see RedBean_Adapter::getAssoc
	 */
	public function getAssoc($sql, $aValues = array()) {
		$this->sql = $sql;
		$this->signal('sql_exec', $this);
		$rows = $this->db->GetAll($sql, $aValues);
		$assoc = array();
		if ($rows) {
			foreach($rows as $row) {
				if (is_array($row) && count($row)>0) {
					if (count($row)>1) {
						$key = array_shift($row);
						$value = array_shift($row);
					}
					elseif (count($row) == 1) {
						$key = array_shift($row);
						$value = $key;
					}
					$assoc[$key] = $value;
				}
			}
		}
		return $assoc;
	}
	/**
	 * @see RedBean_Adapter::getCell
	 */
	public function getCell($sql, $aValues = array(), $noSignal = null) {
		$this->sql = $sql;
		if (!$noSignal) $this->signal('sql_exec', $this);
		$arr = $this->db->getCol($sql, $aValues);
		if ($arr && is_array($arr) && isset($arr[0])) return ($arr[0]); else return null;
	}
	/**
	 * @see RedBean_Adapter::getInsertID
	 */
	public function getInsertID() {
		return $this->db->getInsertID();
	}
	/**
	 * @see RedBean_Adapter::getAffectedRows
	 */
	public function getAffectedRows() {
		return $this->db->Affected_Rows();
	}
	/**
	 * @see RedBean_Adapter::getDatabase
	 */
	public function getDatabase() {
		return $this->db;
	}
	/**
	 * @see RedBean_Adapter::startTransaction
	 */
	public function startTransaction() {
		return $this->db->StartTrans();
	}
	/**
	 * @see RedBean_Adapter::commit
	 */
	public function commit() {
		return $this->db->CommitTrans();
	}
	/**
	 * @see RedBean_Adapter::rollback
	 */
	public function rollback() {
		return $this->db->FailTrans();
	}
	/**
	 * @see RedBean_Adapter::close.
	 */
	public function close() {
		$this->db->close();
	}
}