<?php
/**
 * Created by JetBrains PhpStorm.
 * User: prive
 * Date: 06-04-11
 * Time: 11:57
 * To change this template use File | Settings | File Templates.
 */
 
abstract class RedBean_AIceWriter implements RedBean_IceWriter {

	/**
	 *
	 * @var RedBean_IBeanFormatter
	 * Holds the bean formatter to be used for applying
	 * table schema.
	 */
	public $tableFormatter;


	/**
	 * @var array
	 * Supported Column Types.
	 */
	public $typeno_sqltype = array();

	/**
	 *
	 * @var RedBean_Adapter_DBAdapter
	 * Holds a reference to the database adapter to be used.
	 */
	protected $adapter;

	/**
	 * @var string
	 * Indicates the field name to be used for primary keys;
	 * default is 'id'.
	 */
  	protected $idfield = "id";

	/**
	 * @var string
	 * default value to for blank field (passed to PK for auto-increment)
	 */
 	 protected $defaultValue = 'NULL';

	/**
	 * @var string
	 * character to escape keyword table/column names
	 */
  	protected $quoteCharacter = '';

	/**
	 * Do everything that needs to be done to format a table name.
	 *
	 * @param string $name of table
	 *
	 * @return string table name
	 */
	public function safeTable($name, $noQuotes = false) {
		$name = $this->getFormattedTableName($name);
		$name = $this->check($name);
		if (!$noQuotes) $name = $this->noKW($name);
		return $name;
	}

	/**
	 * Do everything that needs to be done to format a column name.
	 *
	 * @param string $name of column
	 *
	 * @return string $column name
	 */
	public function safeColumn($name, $noQuotes = false) {
		$name = $this->check($name);
		if (!$noQuotes) $name = $this->noKW($name);
		return $name;
	}

	/**
	 * Returns the sql that should follow an insert statement.
	 *
	 * @param string $table name
	 *
	 * @return string sql
	 */
  	protected function getInsertSuffix ($table) {
    	return "";
  	}

	/**
	 * Returns the string identifying a table for a given type.
	 *
	 * @param string $type
	 *
	 * @return string $table
	 */
	public function getFormattedTableName($type) {
		if ($this->tableFormatter) return $this->tableFormatter->formatBeanTable($type);
		return $type;
	}

	/**
	 * Sets the Bean Formatter to be used to handle
	 * custom/advanced DB<->Bean
	 * Mappings. This method has no return value.
	 *
	 * @param RedBean_IBeanFormatter $beanFormatter the bean formatter
	 *
	 * @return void
	 */
	public function setBeanFormatter( RedBean_IBeanFormatter $beanFormatter ) {
		$this->tableFormatter = $beanFormatter;
	}


	/**
	 * Returns the column name that should be used
	 * to store and retrieve the primary key ID.
	 *
	 * @param string $type type of bean to get ID Field for
	 *
	 * @return string $idfieldtobeused ID field to be used for this type of bean
	 */
	public function getIDField( $type ) {
		$nArgs = func_num_args();
		if ($nArgs>1) $safe = func_get_arg(1); else $safe = false;
		if ($this->tableFormatter) return $this->tableFormatter->formatBeanID($type);
		return $safe ? $this->safeColumn($this->idfield) : $this->idfield;
	}

	/**
	 * Checks table name or column name.
	 *
	 * @param string $table table string
	 *
	 * @return string $table escaped string
	 */
	public function check($table) {
		// if (strpos($table, '`')!==false || strpos($table, '"')!==false) { // maybe this?
		if ($this->quoteCharacter && strpos($table, $this->quoteCharacter)!==false) {
		  throw new Redbean_Exception_Security("Illegal chars in table name");
    }
		return $this->adapter->escape($table);
	}

	/**
	 * Puts keyword escaping symbols around string.
	 *
	 * @param string $str keyword
	 *
	 * @return string $keywordSafeString escaped keyword
	 */
	public function noKW($str) {
		$q = $this->quoteCharacter;
		return $q.$str.$q;
	}





}
