<?php

namespace RedBeanPHP\QueryWriter; 
use RedBeanPHP\QueryWriter\AQueryWriter as AQueryWriter;
use RedBeanPHP\QueryWriter as QueryWriter;
use RedBeanPHP\Adapter\DBAdapter as DBAdapter;
use RedBeanPHP\Adapter as Adapter; 

/**
 * RedBean CUBRID Writer
 *
 * @file    RedBean/QueryWriter/CUBRID.php
 * @desc    Represents a CUBRID Database to RedBean
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * (c) copyright G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class CUBRID extends AQueryWriter implements QueryWriter
{
	/**
	 * Data types
	 */
	const C_DATATYPE_INTEGER          = 0;
	const C_DATATYPE_DOUBLE           = 1;
	const C_DATATYPE_STRING           = 2;
	const C_DATATYPE_SPECIAL_DATE     = 80;
	const C_DATATYPE_SPECIAL_DATETIME = 81;
	const C_DATATYPE_SPECIFIED        = 99;

	/**
	 * @var DBAdapter
	 */
	protected $adapter;

	/**
	 * @var string
	 */
	protected $quoteCharacter = '`';

	/**
	 * Obtains the keys of a table using the\PDO schema function.
	 *
	 * @param string $table
	 *
	 * @return array
	 */
	protected function getKeys( $table, $table2 = NULL )
	{
		$pdo  = $this->adapter->getDatabase()->getPDO();

		$keys = $pdo->cubrid_schema(\PDO::CUBRID_SCH_EXPORTED_KEYS, $table );

		if ( $table2 ) {
			$keys = array_merge( $keys, $pdo->cubrid_schema(\PDO::CUBRID_SCH_IMPORTED_KEYS, $table2 ) );
		}

		return $keys;
	}

	/**
	 * Add the constraints for a specific database driver: CUBRID
	 *
	 * @param string $table     table
	 * @param string $table1    table1
	 * @param string $table2    table2
	 * @param string $property1 property1
	 * @param string $property2 property2
	 *
	 * @return boolean
	 */
	protected function constrain( $table, $table1, $table2, $property1, $property2 )
	{
		$firstState  = $this->buildFK( $table, $table1, $property1, 'id', TRUE );
		$secondState = $this->buildFK( $table, $table2, $property2, 'id', TRUE );
		
		return ( $firstState && $secondState );
	}

	/**
	 * This method adds a foreign key from type and field to
	 * target type and target field.
	 * The foreign key is created without an action. On delete/update
	 * no action will be triggered. The FK is only used to allow database
	 * tools to generate pretty diagrams and to make it easy to add actions
	 * later on.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 *
	 * @param  string $type           type that will have a foreign key field
	 * @param  string $targetType     points to this type
	 * @param  string $field          field that contains the foreign key value
	 * @param  string $targetField    field where the fk points to
	 *
	 * @return void
	 */
	protected function buildFK( $type, $targetType, $field, $targetField, $isDep = FALSE )
	{
		$table           = $this->esc( $type );
		$tableNoQ        = $this->esc( $type, TRUE );

		$targetTable     = $this->esc( $targetType );
		$targetTableNoQ  = $this->esc( $targetType, TRUE );

		$column          = $this->esc( $field );
		$columnNoQ       = $this->esc( $field, TRUE );

		$targetColumn    = $this->esc( $targetField );

		$keys            = $this->getKeys( $targetTableNoQ, $tableNoQ );

		$needsToDropFK   = FALSE;

		foreach ( $keys as $key ) {
			if ( $key['FKTABLE_NAME'] == $tableNoQ && $key['FKCOLUMN_NAME'] == $columnNoQ ) {
				// Already has an FK
				return FALSE;
			}
		}


		$casc = ( $isDep ? 'CASCADE' : 'SET NULL' );
		$sql  = "ALTER TABLE $table ADD CONSTRAINT FOREIGN KEY($column) REFERENCES $targetTable($targetColumn) ON DELETE $casc ";
		$this->adapter->exec( $sql );
		return TRUE;
	}

	/**
	 * Constructor
	 *
	 * @param Adapter $adapter Database Adapter
	 */
	public function __construct( Adapter $adapter )
	{
		$this->typeno_sqltype = array(
			CUBRID::C_DATATYPE_INTEGER          => ' INTEGER ',
			CUBRID::C_DATATYPE_DOUBLE           => ' DOUBLE ',
			CUBRID::C_DATATYPE_STRING           => ' STRING ',
			CUBRID::C_DATATYPE_SPECIAL_DATE     => ' DATE ',
			CUBRID::C_DATATYPE_SPECIAL_DATETIME => ' DATETIME ',
		);

		$this->sqltype_typeno = array();

		foreach ( $this->typeno_sqltype as $k => $v ) {
			$this->sqltype_typeno[trim( ( $v ) )] = $k;
		}

		$this->sqltype_typeno['STRING(1073741823)'] = self::C_DATATYPE_STRING;

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
	 * @see QueryWriter::getTables
	 */
	public function getTables()
	{
		$rows = $this->adapter->getCol( "SELECT class_name FROM db_class WHERE is_system_class = 'NO';" );

		return $rows;
	}

	/**
	 * @see QueryWriter::createTable
	 */
	public function createTable( $table )
	{
		$sql  = 'CREATE TABLE '
			. $this->esc( $table )
			. ' ("id" integer AUTO_INCREMENT, CONSTRAINT "pk_'
			. $this->esc( $table, TRUE )
			. '_id" PRIMARY KEY("id"))';

		$this->adapter->exec( $sql );
	}

	/**
	 * @see QueryWriter::getColumns
	 */
	public function getColumns( $table )
	{
		$table = $this->esc( $table );

		$columnsRaw = $this->adapter->get( "SHOW COLUMNS FROM $table" );

		$columns = array();
		foreach ( $columnsRaw as $r ) {
			$columns[$r['Field']] = $r['Type'];
		}

		return $columns;
	}

	/**
	 * @see QueryWriter::scanType
	 */
	public function scanType( $value, $flagSpecial = FALSE )
	{
		$this->svalue = $value;

		if ( is_null( $value ) ) {
			return self::C_DATATYPE_INTEGER;
		}

		if ( $flagSpecial ) {
			if ( preg_match( '/^\d{4}\-\d\d-\d\d$/', $value ) ) {
				return self::C_DATATYPE_SPECIAL_DATE;
			}
			if ( preg_match( '/^\d{4}\-\d\d-\d\d\s\d\d:\d\d:\d\d$/', $value ) ) {
				return self::C_DATATYPE_SPECIAL_DATETIME;
			}
		}

		$value = strval( $value );

		if ( !$this->startsWithZeros( $value ) ) {
			if ( is_numeric( $value ) && ( floor( $value ) == $value ) && $value >= -2147483647 && $value <= 2147483647 ) {
				return self::C_DATATYPE_INTEGER;
			}
			if ( is_numeric( $value ) ) {
				return self::C_DATATYPE_DOUBLE;
			}
		}

		return self::C_DATATYPE_STRING;
	}

	/**
	 * @see QueryWriter::code
	 */
	public function code( $typedescription, $includeSpecials = FALSE )
	{
		$r = ( ( isset( $this->sqltype_typeno[$typedescription] ) ) ? $this->sqltype_typeno[$typedescription] : self::C_DATATYPE_SPECIFIED );

		if ( $includeSpecials ) {
			return $r;
		}

		if ( $r >= QueryWriter::C_DATATYPE_RANGE_SPECIAL ) {
			return self::C_DATATYPE_SPECIFIED;
		}

		return $r;
	}

	/**
	 * @see QueryWriter::addColumn
	 */
	public function addColumn( $type, $column, $field )
	{
		$table  = $type;
		$type   = $field;

		$table  = $this->esc( $table );
		$column = $this->esc( $column );

		$type   = array_key_exists( $type, $this->typeno_sqltype ) ? $this->typeno_sqltype[$type] : '';

		$this->adapter->exec( "ALTER TABLE $table ADD COLUMN $column $type " );
	}

	/**
	 * @see QueryWriter::addUniqueIndex
	 */
	public function addUniqueIndex( $table, $columns )
	{
		$table = $this->esc( $table );

		sort( $columns ); // else we get multiple indexes due to order-effects

		foreach ( $columns as $k => $v ) {
			$columns[$k] = $this->esc( $v );
		}

		$r = $this->adapter->get( "SHOW INDEX FROM $table" );

		$name = 'UQ_' . sha1( implode( ',', $columns ) );

		if ( $r ) {
			foreach ( $r as $i ) {
				if ( strtoupper( $i['Key_name'] ) == strtoupper( $name ) ) {
					return;
				}
			}
		}

		$sql = "ALTER TABLE $table ADD CONSTRAINT UNIQUE $name (" . implode( ',', $columns ) . ")";

		$this->adapter->exec( $sql );
	}

	/**
	 * @see QueryWriter::sqlStateIn
	 */
	public function sqlStateIn( $state, $list )
	{
		return ( $state == 'HY000' ) ? ( count( array_diff( array(
				QueryWriter::C_SQLSTATE_INTEGRITY_CONSTRAINT_VIOLATION,
				QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN,
				QueryWriter::C_SQLSTATE_NO_SUCH_TABLE
			), $list ) ) !== 3 ) : FALSE;
	}

	/**
	 * @see QueryWriter::addIndex
	 */
	public function addIndex( $type, $name, $column )
	{
		$table  = $type;
		$table  = $this->esc( $table );

		$name   = preg_replace( '/\W/', '', $name );

		$column = $this->esc( $column );

		$index  = $this->adapter->getRow( "SELECT 1 as `exists` FROM db_index WHERE index_name = ? ", array( $name ) );

		if ( $index && $index['exists'] ) {
			return; // positive number will return, 0 will continue.
		}

		try {
			$this->adapter->exec( "CREATE INDEX $name ON $table ($column) " );
		} catch (\Exception $e ) {
		}
	}

	/**
	 * @see QueryWriter::addFK
	 */
	public function addFK( $type, $targetType, $field, $targetField, $isDependent = FALSE )
	{
		$this->buildFK( $type, $targetType, $field, $targetField, $isDependent );
	}

	/**
	 * @see QueryWriter::wipeAll
	 */
	public function wipeAll()
	{
		foreach ( $this->getTables() as $t ) {
			foreach ( $this->getKeys( $t ) as $k ) {
				$this->adapter->exec( "ALTER TABLE \"{$k['FKTABLE_NAME']}\" DROP FOREIGN KEY \"{$k['FK_NAME']}\"" );
			}

			$this->adapter->exec( "DROP TABLE \"$t\"" );
		}
	}

	/**
	 * @see QueryWriter::esc
	 */
	public function esc( $dbStructure, $noQuotes = FALSE )
	{
		return parent::esc( strtolower( $dbStructure ), $noQuotes );
	}
}
