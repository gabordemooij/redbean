<?php
/**
 * Recursive Bean Export
 *
 * @file 			RedBean/Plugin/BeanExport.php
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */

class RedBean_Plugin_BeanExport {

	/**
	 * @var null|\RedBean_Toolbox
	 */
	protected $toolbox = null;

	/**
	 * @var array
	 * Array used to check for recursion. This avoids infinite loops.
	 */
	protected $recurCheck = array();

	/**
	 * Constructor
	 * @param RedBean_Toolbox $toolbox
	 */
	public function __construct( RedBean_Toolbox $toolbox ) {
		$this->toolbox = $toolbox;
	}

	/**
	 * Loads Schema
	 * @return void
	 */
	public function loadSchema() {
		$tables = array_flip($this->toolbox->getWriter()->getTables());
		foreach($tables as $table=>$columns) {
			try{
				$tables[$table] = $this->toolbox->getWriter()->getColumns($table);
			}
			catch(RedBean_Exception_SQL $e) {
				$tables[$table] = array();
			}
		}
		$this->tables = $tables;
	}

	/**
	 *Returs a serialized representation of the schema
	 *
	 *@return string $serialized serialized representation
	 */
	public function getSchema() {
		return serialize($this->tables);
	}

	/**
	 * Loads a schema from a string (containing serialized export of schema)
	 *
	 * @param string $schema
	 */
	public function loadSchemaFromString($schema) {
		$this->tables = unserialize($schema);
	}


	/**
	 * Exports a collection of beans
	 *
	 * @param	mixed $beans 	  Either array or RedBean_OODBBean
	 * @param	bool  $resetRecur Whether we need to reset the recursion check array (first time only)
	 *
	 * @return	array $export Exported beans
	 */
	public function export( $beans, $resetRecur=true ) {
		if ($resetRecur) {
			$this->recurCheck = array();
		}
		if (!is_array($beans)) {
			$beans = array($beans);
		}
		$export = array();
		foreach($beans as $bean) {
			$export[$bean->getID()] = $this->exportBean( $bean );
		}
		return $export;
	}

	/**
	 * Exports a single bean
	 *
	 * @param RedBean_OODBBean $bean Bean to be exported
	 *
	 * @return array|null $array Array export of bean
	 */
	public function exportBean(RedBean_OODBBean $bean) {
		$bid = $bean->getMeta('type').'-'.$bean->getID();
		if (isset($this->recurCheck[$bid])) return null;
		$this->recurCheck[$bid]=$bid;
		$export = $bean->export();
		foreach($export as $key=>$value) {
			if (strpos($key,'_id')!==false) {
				$sub = str_replace('_id','',$key);
				$subBean = $bean->$sub;
				if ($subBean) {
					$export[$sub] = $this->export($subBean, false);
				}
			}
		}
		$type = $bean->getMeta('type');
		$linkField = $type . '_id';
		//get all ownProperties
		foreach($this->tables as $table=>$cols) {
			if (strpos($table,'_')===false) {
				if (in_array($linkField,array_keys($cols))) {
					$field = 'own'.ucfirst($table);
					$export[$field] = self::export($bean->$field, false);
				}
			}
		}
		//get all sharedProperties
		foreach($this->tables as $table=>$cols) {
			if (strpos($table,'_')!==false) {
				$parts = explode('_', $table);
				if (is_array($parts) && in_array($type,$parts)) {
					$other = $parts[0];
					if ($other==$type) $other=$parts[1];
					$field = 'shared'.ucfirst($other);
					$export[$field] = self::export($bean->$field, false);
				}
			}
		}
		return $export;
	}
}