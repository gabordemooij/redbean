<?php

namespace RedBeanPHP\QueryWriter;

use RedBeanPHP\QueryWriter\AQueryWriter as AQueryWriter;
use RedBeanPHP\QueryWriter as QueryWriter;
use RedBeanPHP\Adapter\DBAdapter as DBAdapter;
use RedBeanPHP\Adapter as Adapter;
use RedBeanPHP\RedException\SQL as SQLException;

/**
 * RedBeanPHP SQLiteWriter with support for SQLite types
 * This is a QueryWriter class for RedBeanPHP.
 * This QueryWriter provides support for the SQLite database platform.
 *
 * @file    RedBeanPHP/QueryWriter/SQLiteT.php
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * @copyright
 * (c) copyright G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class SQLiteT extends AQueryWriter implements QueryWriter
{
	/**
	 * Data types
	 */
	const C_DATATYPE_INTEGER   = 0;
	const C_DATATYPE_NUMERIC   = 1;
	const C_DATATYPE_TEXT      = 2;
	const C_DATATYPE_SPECIFIED = 99;

	/**
	 * @var DBAdapter
	 */
	protected $adapter;

	/**
	 * @var string
	 */
	protected $quoteCharacter = '`';

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
	 * @return array
	 */
	protected function getTable( $type )
	{
		$tableName = $this->esc( $type, TRUE );
		$columns   = $this->getColumns( $type );
		$indexes   = $this->getIndexes( $type );
		$keys      = $this->getKeyMapForType( $type );

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
	 *
	 * @return void
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
	 * Returns the an array describing the indexes for type $type.
	 *
	 * @param string $type type to describe indexes of
	 *
	 * @return array
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
	 * Adds a foreign key to a type.
	 * Note: cant put this in try-catch because that can hide the fact
	 * that database has been damaged.
	 *
	 * @param  string  $type        type you want to modify table of
	 * @param  string  $targetType  target type
	 * @param  string  $field       field of the type that needs to get the fk
	 * @param  string  $targetField field where the fk needs to point to
	 * @param  integer $buildopt    0 = NO ACTION, 1 = ON DELETE CASCADE
	 *
	 * @return boolean
	 */
	protected function buildFK( $type, $targetType, $property, $targetProperty, $constraint = FALSE )
	{
		$table           = $this->esc( $type, TRUE );
		$targetTable     = $this->esc( $targetType, TRUE );
		$column          = $this->esc( $property, TRUE );
		$targetColumn    = $this->esc( $targetProperty, TRUE );

		$tables = $this->getTables();
		if ( !in_array( $targetTable, $tables ) ) return FALSE;

		if ( !is_null( $this->getForeignKeyForTypeProperty( $table, $column ) ) ) return FALSE;
		$t = $this->getTable( $table );
		$consSQL = ( $constraint ? 'CASCADE' : 'SET NULL' );
		$label   = 'from_' . $column . '_to_table_' . $targetTable . '_col_' . $targetColumn;
		$t['keys'][$label] = array(
			'table'     => $targetTable,
			'from'      => $column,
			'to'        => $targetColumn,
			'on_update' => $consSQL,
			'on_delete' => $consSQL
		);
		$this->putTable( $t );
		return TRUE;
	}

	/**
	 * @see AQueryWriter::getKeyMapForType
	 */
	protected function getKeyMapForType( $type )
	{
		$table = $this->esc( $type, TRUE );
		$keys  = $this->adapter->get( "PRAGMA foreign_key_list('$table')" );
		$keyInfoList = array();
		foreach ( $keys as $k ) {
			$label = $this->makeFKLabel( $k['from'], $k['table'], $k['to'] );
			$keyInfoList[$label] = array(
				'name'          => $label,
				'from'          => $k['from'],
				'table'         => $k['table'],
				'to'            => $k['to'],
				'on_update'     => $k['on_update'],
				'on_delete'     => $k['on_delete']
			);
		}
		return $keyInfoList;
	}

	/**
	 * Constructor
	 * Most of the time, you do not need to use this constructor,
	 * since the facade takes care of constructing and wiring the
	 * RedBeanPHP core objects. However if you would like to
	 * assemble an OODB instance yourself, this is how it works:
	 *
	 * Usage:
	 *
	 * <code>
	 * $database = new RPDO( $dsn, $user, $pass );
	 * $adapter = new DBAdapter( $database );
	 * $writer = new PostgresWriter( $adapter );
	 * $oodb = new OODB( $writer, FALSE );
	 * $bean = $oodb->dispense( 'bean' );
	 * $bean->name = 'coffeeBean';
	 * $id = $oodb->store( $bean );
	 * $bean = $oodb->load( 'bean', $id );
	 * </code>
	 *
	 * The example above creates the 3 RedBeanPHP core objects:
	 * the Adapter, the Query Writer and the OODB instance and
	 * wires them together. The example also demonstrates some of
	 * the methods that can be used with OODB, as you see, they
	 * closely resemble their facade counterparts.
	 *
	 * The wiring process: create an RPDO instance using your database
	 * connection parameters. Create a database adapter from the RPDO
	 * object and pass that to the constructor of the writer. Next,
	 * create an OODB instance from the writer. Now you have an OODB
	 * object.
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
		$this->adapter->setOption( 'setInitQuery', ' PRAGMA foreign_keys = 1 ' );
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

		if ( $value === NULL ) return self::C_DATATYPE_INTEGER;
		if ( $value === INF ) return self::C_DATATYPE_TEXT;

		if ( $this->startsWithZeros( $value ) ) return self::C_DATATYPE_TEXT;

		if ( $value === TRUE || $value === FALSE )  return self::C_DATATYPE_INTEGER;

		if ( is_numeric( $value ) && ( intval( $value ) == $value ) && $value < 2147483648 && $value > -2147483648 ) return self::C_DATATYPE_INTEGER;

		if ( ( is_numeric( $value ) && $value < 2147483648 && $value > -2147483648)
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
	public function addUniqueConstraint( $type, $properties )
	{
		$tableNoQ = $this->esc( $type, TRUE );
		$name  = 'UQ_' . $this->esc( $type, TRUE ) . implode( '__', $properties );
		$t     = $this->getTable( $type );
		$t['indexes'][$name] = array( 'name' => $name );
		try {
			$this->putTable( $t );
		} catch( SQLException $e ) {
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * @see QueryWriter::sqlStateIn
	 */
	public function sqlStateIn( $state, $list, $extraDriverDetails = array() )
	{
		$stateMap = array(
			'23000' => QueryWriter::C_SQLSTATE_INTEGRITY_CONSTRAINT_VIOLATION
		);
		if ( $state == 'HY000'
		&& isset($extraDriverDetails[1])
		&& $extraDriverDetails[1] == 1
		&& ( in_array( QueryWriter::C_SQLSTATE_NO_SUCH_TABLE, $list )
			|| in_array( QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN, $list )
		)) {
			return TRUE;
		}
		return in_array( ( isset( $stateMap[$state] ) ? $stateMap[$state] : '0' ), $list );
	}

	/**
	 * @see QueryWriter::addIndex
	 */
	public function addIndex( $type, $name, $column )
	{
		$columns = $this->getColumns( $type );
		if ( !isset( $columns[$column] ) ) return FALSE;

		$table  = $this->esc( $type );
		$name   = preg_replace( '/\W/', '', $name );
		$column = $this->esc( $column, TRUE );

		try {
			$t = $this->getTable( $type );
			$t['indexes'][$name] = array( 'name' => $column );
			$this->putTable( $t );
			return TRUE;
		} catch( SQLException $exception ) {
			return FALSE;
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
	public function addFK( $type, $targetType, $property, $targetProperty, $isDep = FALSE )
	{
		return $this->buildFK( $type, $targetType, $property, $targetProperty, $isDep );
	}

	/**
	 * @see QueryWriter::wipeAll
	 */
	public function wipeAll()
	{
		if (AQueryWriter::$noNuke) throw new \Exception('The nuke() command has been disabled using noNuke() or R::feature(novice/...).');
		$this->adapter->exec( 'PRAGMA foreign_keys = 0 ' );

		foreach ( $this->getTables() as $t ) {
			try { $this->adapter->exec( "DROP TABLE IF EXISTS `$t`" ); } catch ( SQLException $e ) { ; }
			try { $this->adapter->exec( "DROP TABLE IF EXISTS `$t`" ); } catch ( SQLException $e ) { ; }
		}

		$this->adapter->exec( 'PRAGMA foreign_keys = 1 ' );
	}
}
