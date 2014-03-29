<?php 

namespace RedBeanPHP\QueryWriter;

use RedBeanPHP\QueryWriter\AQueryWriter as AQueryWriter;
use RedBeanPHP\QueryWriter as QueryWriter;
use RedBeanPHP\Adapter\DBAdapter as DBAdapter;
use RedBeanPHP\Adapter as Adapter;

/**
 * RedBean SQLiteWriter with support for SQLite types
 *
 * @file    RedBean/QueryWriter/SQLiteT.php
 * @desc    Represents a SQLite Database to RedBean
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * (c) copyright G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class SQLiteT extends AQueryWriter implements QueryWriter
{
	/**
	 * @var DBAdapter
	 */

	protected $adapter;

	/**
	 * @var string
	 */
	protected $quoteCharacter = '`';

	/**
	 * Data types
	 */
	const C_DATATYPE_INTEGER   = 0;
	const C_DATATYPE_NUMERIC   = 1;
	const C_DATATYPE_TEXT      = 2;
	const C_DATATYPE_SPECIFIED = 99;

	/**
	 * Gets all information about a table (from a type).
	 *
	 * Format:
	 * array(
	 *    name => name of the table
	 *    columns => array( name => datatype )
	 *    indexes => array() raw index information rows from PRAGMA query
	 *    keys => array() raw key information rows from PRAGMA query
	 * )
	 *
	 * @param string $type type you want to get info of
	 *
	 * @return array $info
	 */
	protected function getTable( $type )
	{
		$tableName = $this->esc( $type, TRUE );
		$columns   = $this->getColumns( $type );
		$indexes   = $this->getIndexes( $type );
		$keys      = $this->getKeys( $type );

		$table = array(
			'columns' => $columns,
			'indexes' => $indexes,
			'keys' => $keys,
			'name' => $tableName
		);

		$this->tableArchive[$tableName] = $table;

		return $table;
	}

	/**
	 * Puts a table. Updates the table structure.
	 * In SQLite we can't change columns, drop columns, change or add foreign keys so we
	 * have a table-rebuild function. You simply load your table with getTable(), modify it and
	 * then store it with putTable()...
	 *
	 * @param array $tableMap information array
	 */
	protected function putTable( $tableMap )
	{
		$table = $tableMap['name'];
		$q     = array();
		$q[]   = "DROP TABLE IF EXISTS tmp_backup;";

		$oldColumnNames = array_keys( $this->getColumns( $table ) );

		foreach ( $oldColumnNames as $k => $v ) $oldColumnNames[$k] = "`$v`";

		$q[] = "CREATE TEMPORARY TABLE tmp_backup(" . implode( ",", $oldColumnNames ) . ");";
		$q[] = "INSERT INTO tmp_backup SELECT * FROM `$table`;";
		$q[] = "PRAGMA foreign_keys = 0 ";
		$q[] = "DROP TABLE `$table`;";

		$newTableDefStr = '';
		foreach ( $tableMap['columns'] as $column => $type ) {
			if ( $column != 'id' ) {
				$newTableDefStr .= ",`$column` $type";
			}
		}

		$fkDef = '';
		foreach ( $tableMap['keys'] as $key ) {
			$fkDef .= ", FOREIGN KEY(`{$key['from']}`)
						 REFERENCES `{$key['table']}`(`{$key['to']}`)
						 ON DELETE {$key['on_delete']} ON UPDATE {$key['on_update']}";
		}

		$q[] = "CREATE TABLE `$table` ( `id` INTEGER PRIMARY KEY AUTOINCREMENT  $newTableDefStr  $fkDef );";

		foreach ( $tableMap['indexes'] as $name => $index ) {
			if ( strpos( $name, 'UQ_' ) === 0 ) {
				$cols = explode( '__', substr( $name, strlen( 'UQ_' . $table ) ) );
				foreach ( $cols as $k => $v ) $cols[$k] = "`$v`";
				$q[] = "CREATE UNIQUE INDEX $name ON `$table` (" . implode( ',', $cols ) . ")";
			} else $q[] = "CREATE INDEX $name ON `$table` ({$index['name']}) ";
		}

		$q[] = "INSERT INTO `$table` SELECT * FROM tmp_backup;";
		$q[] = "DROP TABLE tmp_backup;";
		$q[] = "PRAGMA foreign_keys = 1 ";

		foreach ( $q as $sq ) $this->adapter->exec( $sq );
	}

	/**
	 * Returns the indexes for type $type.
	 *
	 * @param string $type
	 *
	 * @return array $indexInfo index information
	 */
	protected function getIndexes( $type )
	{
		$table   = $this->esc( $type, TRUE );
		$indexes = $this->adapter->get( "PRAGMA index_list('$table')" );

		$indexInfoList = array();
		foreach ( $indexes as $i ) {
			$indexInfoList[$i['name']] = $this->adapter->getRow( "PRAGMA index_info('{$i['name']}') " );

			$indexInfoList[$i['name']]['unique'] = $i['unique'];
		}

		return $indexInfoList;
	}

	/**
	 * Returns the keys for type $type.
	 *
	 * @param string $type
	 *
	 * @return array $keysInfo keys information
	 */
	protected function getKeys( $type )
	{
		$table = $this->esc( $type, TRUE );
		$keys  = $this->adapter->get( "PRAGMA foreign_key_list('$table')" );

		$keyInfoList = array();
		foreach ( $keys as $k ) {
			$keyInfoList['from_' . $k['from'] . '_to_table_' . $k['table'] . '_col_' . $k['to']] = $k;
		}

		return $keyInfoList;
	}

	/**
	 * Adds a foreign key to a type
	 *
	 * @param  string  $type        type you want to modify table of
	 * @param  string  $targetType  target type
	 * @param  string  $field       field of the type that needs to get the fk
	 * @param  string  $targetField field where the fk needs to point to
	 * @param  integer $buildopt    0 = NO ACTION, 1 = ON DELETE CASCADE
	 *
	 * @return boolean $didIt
	 *
	 * @note: cant put this in try-catch because that can hide the fact
	 *      that database has been damaged.
	 */
	protected function buildFK( $type, $targetType, $field, $targetField, $constraint = FALSE )
	{
		$consSQL = ( $constraint ? 'CASCADE' : 'SET NULL' );

		$t       = $this->getTable( $type );
		$label   = 'from_' . $field . '_to_table_' . $targetType . '_col_' . $targetField;

		foreach($t['keys'] as $key) {
			if ($key['from'] === $field) return FALSE;
		}

		$t['keys'][$label] = array(
			'table'     => $targetType,
			'from'      => $field,
			'to'        => $targetField,
			'on_update' => $consSQL,
			'on_delete' => $consSQL
		);

		$this->putTable( $t );

		return TRUE;
	}

	/**
	 * Add the constraints for a specific database driver: SQLite.
	 *
	 * @param string $table     table to add fk constrains to
	 * @param string $table1    first reference table
	 * @param string $table2    second reference table
	 * @param string $property1 first reference column
	 * @param string $property2 second reference column
	 *
	 * @return boolean $success whether the constraint has been applied
	 */
	protected function constrain( $table, $table1, $table2, $property1, $property2 )
	{
		$firstState  = $this->buildFK( $table, $table1, $property1, 'id', TRUE );
		$secondState = $this->buildFK( $table, $table2, $property2, 'id', TRUE );

		return ( $firstState && $secondState );
	}

	/**
	 * Constructor
	 *
	 * @param Adapter $adapter Database Adapter
	 */
	public function __construct( Adapter $adapter )
	{
		$this->typeno_sqltype = array(
			SQLiteT::C_DATATYPE_INTEGER => 'INTEGER',
			SQLiteT::C_DATATYPE_NUMERIC => 'NUMERIC',
			SQLiteT::C_DATATYPE_TEXT    => 'TEXT',
		);

		$this->sqltype_typeno = array();

		foreach ( $this->typeno_sqltype as $k => $v ) {
			$this->sqltype_typeno[$v] = $k;
		}

		$this->adapter = $adapter;
	}

	/**
	 * This method returns the datatype to be used for primary key IDS and
	 * foreign keys. Returns one if the data type constants.
	 *
	 * @return integer $const data type to be used for IDS.
	 */
	public function getTypeForID()
	{
		return self::C_DATATYPE_INTEGER;
	}

	/**
	 * @see QueryWriter::scanType
	 */
	public function scanType( $value, $flagSpecial = FALSE )
	{
		$this->svalue = $value;

		if ( $value === FALSE ) return self::C_DATATYPE_INTEGER;

		if ( $value === NULL ) return self::C_DATATYPE_INTEGER;

		if ( $this->startsWithZeros( $value ) ) return self::C_DATATYPE_TEXT;

		if ( is_numeric( $value ) && ( intval( $value ) == $value ) && $value < 2147483648 ) return self::C_DATATYPE_INTEGER;

		if ( ( is_numeric( $value ) && $value < 2147483648 )
			|| preg_match( '/\d{4}\-\d\d\-\d\d/', $value )
			|| preg_match( '/\d{4}\-\d\d\-\d\d\s\d\d:\d\d:\d\d/', $value )
		) {
			return self::C_DATATYPE_NUMERIC;
		}

		return self::C_DATATYPE_TEXT;
	}

	/**
	 * @see QueryWriter::addColumn
	 */
	public function addColumn( $table, $column, $type )
	{
		$column = $this->check( $column );
		$table  = $this->check( $table );
		$type   = $this->typeno_sqltype[$type];

		$this->adapter->exec( "ALTER TABLE `$table` ADD `$column` $type " );
	}

	/**
	 * @see QueryWriter::code
	 */
	public function code( $typedescription, $includeSpecials = FALSE )
	{
		$r = ( ( isset( $this->sqltype_typeno[$typedescription] ) ) ? $this->sqltype_typeno[$typedescription] : 99 );
		
		return $r;
	}

	/**
	 * @see QueryWriter::widenColumn
	 */
	public function widenColumn( $type, $column, $datatype )
	{
		$t = $this->getTable( $type );

		$t['columns'][$column] = $this->typeno_sqltype[$datatype];

		$this->putTable( $t );
	}

	/**
	 * @see QueryWriter::getTables();
	 */
	public function getTables()
	{
		return $this->adapter->getCol( "SELECT name FROM sqlite_master
			WHERE type='table' AND name!='sqlite_sequence';" );
	}

	/**
	 * @see QueryWriter::createTable
	 */
	public function createTable( $table )
	{
		$table = $this->esc( $table );

		$sql   = "CREATE TABLE $table ( id INTEGER PRIMARY KEY AUTOINCREMENT ) ";

		$this->adapter->exec( $sql );
	}

	/**
	 * @see QueryWriter::getColumns
	 */
	public function getColumns( $table )
	{
		$table      = $this->esc( $table, TRUE );

		$columnsRaw = $this->adapter->get( "PRAGMA table_info('$table')" );

		$columns    = array();
		foreach ( $columnsRaw as $r ) $columns[$r['name']] = $r['type'];

		return $columns;
	}

	/**
	 * @see QueryWriter::addUniqueIndex
	 */
	public function addUniqueIndex( $type, $columns )
	{
		$name  = 'UQ_' . $this->esc( $type, TRUE ) . implode( '__', $columns );

		$t     = $this->getTable( $type );

		if ( isset( $t['indexes'][$name] ) ) return;

		$t['indexes'][$name] = array( 'name' => $name );

		$this->putTable( $t );
	}

	/**
	 * @see QueryWriter::sqlStateIn
	 */
	public function sqlStateIn( $state, $list )
	{
		$stateMap = array(
			'HY000' => QueryWriter::C_SQLSTATE_NO_SUCH_TABLE,
			'23000' => QueryWriter::C_SQLSTATE_INTEGRITY_CONSTRAINT_VIOLATION
		);

		return in_array( ( isset( $stateMap[$state] ) ? $stateMap[$state] : '0' ), $list );
	}

	/**
	 * @see QueryWriter::addIndex
	 */
	public function addIndex( $type, $name, $column )
	{
		$table  = $type;
		$table  = $this->esc( $table );

		$name   = preg_replace( '/\W/', '', $name );
		$column = $this->esc( $column, TRUE );

		try {
			
			foreach ( $this->adapter->get( "PRAGMA INDEX_LIST($table) " ) as $ind ) {
				if ( $ind['name'] === $name ) return;
			}

			$t = $this->getTable( $type );
			$t['indexes'][$name] = array( 'name' => $column );

			$this->putTable( $t );
		} catch( \Exception $exception ) {
			//do nothing
		}
	}

	/**
	 * @see QueryWriter::wipe
	 */
	public function wipe( $type )
	{
		$table = $this->esc( $type );
		
		$this->adapter->exec( "DELETE FROM $table " );
	}

	/**
	 * @see QueryWriter::addFK
	 */
	public function addFK( $type, $targetType, $field, $targetField, $isDep = FALSE )
	{
		return $this->buildFK( $type, $targetType, $field, $targetField, $isDep );
	}

	/**
	 * @see QueryWriter::wipeAll
	 */
	public function wipeAll()
	{
		$this->adapter->exec( 'PRAGMA foreign_keys = 0 ' );

		foreach ( $this->getTables() as $t ) {
			try {
				$this->adapter->exec( "DROP TABLE IF EXISTS `$t`" );
			} catch (\Exception $e ) {
			}

			try {
				$this->adapter->exec( "DROP TABLE IF EXISTS `$t`" );
			} catch (\Exception $e ) {
			}
		}

		$this->adapter->exec( 'PRAGMA foreign_keys = 1 ' );
	}
}
