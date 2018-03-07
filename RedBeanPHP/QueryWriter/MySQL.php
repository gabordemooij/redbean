<?php

namespace RedBeanPHP\QueryWriter;

use RedBeanPHP\QueryWriter\AQueryWriter as AQueryWriter;
use RedBeanPHP\QueryWriter as QueryWriter;
use RedBeanPHP\Adapter\DBAdapter as DBAdapter;
use RedBeanPHP\Adapter as Adapter;
use RedBeanPHP\RedException\SQL as SQLException;

/**
 * RedBeanPHP MySQLWriter.
 * This is a QueryWriter class for RedBeanPHP.
 * This QueryWriter provides support for the MySQL/MariaDB database platform.
 *
 * @file    RedBeanPHP/QueryWriter/MySQL.php
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * @copyright
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class MySQL extends AQueryWriter implements QueryWriter
{
	/**
	 * Data types
	 */
	const C_DATATYPE_BOOL             = 0;
	const C_DATATYPE_UINT32           = 2;
	const C_DATATYPE_DOUBLE           = 3;
	const C_DATATYPE_TEXT7            = 4; //InnoDB cant index varchar(255) utf8mb4 - so keep 191 as long as possible
	const C_DATATYPE_TEXT8            = 5;
	const C_DATATYPE_TEXT16           = 6;
	const C_DATATYPE_TEXT32           = 7;
	const C_DATATYPE_SPECIAL_DATE     = 80;
	const C_DATATYPE_SPECIAL_DATETIME = 81;
	const C_DATATYPE_SPECIAL_POINT    = 90;
	const C_DATATYPE_SPECIAL_LINESTRING = 91;
	const C_DATATYPE_SPECIAL_POLYGON    = 92;
	const C_DATATYPE_SPECIAL_MONEY      = 93;
	const C_DATATYPE_SPECIAL_JSON       = 94;  //JSON support (only manual)

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
	 * @see AQueryWriter::getKeyMapForType
	 */
	protected function getKeyMapForType( $type )
	{
		$databaseName = $this->adapter->getCell('SELECT DATABASE()');
		$table = $this->esc( $type, TRUE );
		$keys = $this->adapter->get('
			SELECT
				information_schema.key_column_usage.constraint_name AS `name`,
				information_schema.key_column_usage.referenced_table_name AS `table`,
				information_schema.key_column_usage.column_name AS `from`,
				information_schema.key_column_usage.referenced_column_name AS `to`,
				information_schema.referential_constraints.update_rule AS `on_update`,
				information_schema.referential_constraints.delete_rule AS `on_delete`
				FROM information_schema.key_column_usage
				INNER JOIN information_schema.referential_constraints
				ON information_schema.referential_constraints.constraint_name = information_schema.key_column_usage.constraint_name
			WHERE
				information_schema.key_column_usage.table_schema = :database
				AND information_schema.referential_constraints.constraint_schema  = :database
				AND information_schema.key_column_usage.constraint_schema  = :database
				AND information_schema.key_column_usage.table_name = :table
				AND information_schema.key_column_usage.constraint_name != \'PRIMARY\'
				AND information_schema.key_column_usage.referenced_table_name IS NOT NULL
		', array( ':database' => $databaseName, ':table' => $table ) );
		$keyInfoList = array();
		foreach ( $keys as $k ) {
			$label = $this->makeFKLabel( $k['from'], $k['table'], $k['to'] );
			$keyInfoList[$label] = array(
				'name'          => $k['name'],
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
	 *
	 * @param Adapter $adapter Database Adapter
	 */
	public function __construct( Adapter $adapter )
	{
		$this->typeno_sqltype = array(
			MySQL::C_DATATYPE_BOOL             => ' TINYINT(1) UNSIGNED ',
			MySQL::C_DATATYPE_UINT32           => ' INT(11) UNSIGNED ',
			MySQL::C_DATATYPE_DOUBLE           => ' DOUBLE ',
			MySQL::C_DATATYPE_TEXT7            => ' VARCHAR(191) ',
			MYSQL::C_DATATYPE_TEXT8	           => ' VARCHAR(255) ',
			MySQL::C_DATATYPE_TEXT16           => ' TEXT ',
			MySQL::C_DATATYPE_TEXT32           => ' LONGTEXT ',
			MySQL::C_DATATYPE_SPECIAL_DATE     => ' DATE ',
			MySQL::C_DATATYPE_SPECIAL_DATETIME => ' DATETIME ',
			MySQL::C_DATATYPE_SPECIAL_POINT    => ' POINT ',
			MySQL::C_DATATYPE_SPECIAL_LINESTRING => ' LINESTRING ',
			MySQL::C_DATATYPE_SPECIAL_POLYGON => ' POLYGON ',
			MySQL::C_DATATYPE_SPECIAL_MONEY    => ' DECIMAL(10,2) ',
			MYSQL::C_DATATYPE_SPECIAL_JSON     => ' JSON '
		);

		$this->sqltype_typeno = array();

		foreach ( $this->typeno_sqltype as $k => $v ) {
			$this->sqltype_typeno[trim( strtolower( $v ) )] = $k;
		}

		$this->adapter = $adapter;

		$this->encoding = $this->adapter->getDatabase()->getMysqlEncoding();
	}

	/**
	 * This method returns the datatype to be used for primary key IDS and
	 * foreign keys. Returns one if the data type constants.
	 *
	 * @return integer
	 */
	public function getTypeForID()
	{
		return self::C_DATATYPE_UINT32;
	}

	/**
	 * @see QueryWriter::getTables
	 */
	public function getTables()
	{
		return $this->adapter->getCol( 'show tables' );
	}

	/**
	 * @see QueryWriter::createTable
	 */
	public function createTable( $table )
	{
		$table = $this->esc( $table );

		$charset_collate = $this->adapter->getDatabase()->getMysqlEncoding( TRUE );
		$charset = $charset_collate['charset'];
		$collate = $charset_collate['collate'];
		
		$sql   = "CREATE TABLE $table (id INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT, PRIMARY KEY ( id )) ENGINE = InnoDB DEFAULT CHARSET={$charset} COLLATE={$collate} ";

		$this->adapter->exec( $sql );
	}

	/**
	 * @see QueryWriter::getColumns
	 */
	public function getColumns( $table )
	{
		$columnsRaw = $this->adapter->get( "DESCRIBE " . $this->esc( $table ) );

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

		if ( is_null( $value ) ) return MySQL::C_DATATYPE_BOOL;
		if ( $value === INF ) return MySQL::C_DATATYPE_TEXT7;

		if ( $flagSpecial ) {
			if ( preg_match( '/^-?\d+\.\d{2}$/', $value ) ) {
				return MySQL::C_DATATYPE_SPECIAL_MONEY;
			}
			if ( preg_match( '/^\d{4}\-\d\d-\d\d$/', $value ) ) {
				return MySQL::C_DATATYPE_SPECIAL_DATE;
			}
			if ( preg_match( '/^\d{4}\-\d\d-\d\d\s\d\d:\d\d:\d\d$/', $value ) ) {
				return MySQL::C_DATATYPE_SPECIAL_DATETIME;
			}
			if ( preg_match( '/^POINT\(/', $value ) ) {
				return MySQL::C_DATATYPE_SPECIAL_POINT;
			}
			if ( preg_match( '/^LINESTRING\(/', $value ) ) {
				return MySQL::C_DATATYPE_SPECIAL_LINESTRING;
			}
			if ( preg_match( '/^POLYGON\(/', $value ) ) {
				return MySQL::C_DATATYPE_SPECIAL_POLYGON;
			}
			if ( self::$flagUseJSONColumns && $this->isJSON( $value ) ) {
				return self::C_DATATYPE_SPECIAL_JSON;
			}
		}

		//setter turns TRUE FALSE into 0 and 1 because database has no real bools (TRUE and FALSE only for test?).
		if ( $value === FALSE || $value === TRUE || $value === '0' || $value === '1' ) {
			return MySQL::C_DATATYPE_BOOL;
		}

		if ( is_float( $value ) ) return self::C_DATATYPE_DOUBLE;

		if ( !$this->startsWithZeros( $value ) ) {

			if ( is_numeric( $value ) && ( floor( $value ) == $value ) && $value >= 0 && $value <= 4294967295 ) {
				return MySQL::C_DATATYPE_UINT32;
			}

			if ( is_numeric( $value ) ) {
				return MySQL::C_DATATYPE_DOUBLE;
			}
		}

		if ( mb_strlen( $value, 'UTF-8' ) <= 191 ) {
			return MySQL::C_DATATYPE_TEXT7;
		}

		if ( mb_strlen( $value, 'UTF-8' ) <= 255 ) {
			return MySQL::C_DATATYPE_TEXT8;
		}

		if ( mb_strlen( $value, 'UTF-8' ) <= 65535 ) {
			return MySQL::C_DATATYPE_TEXT16;
		}

		return MySQL::C_DATATYPE_TEXT32;
	}

	/**
	 * @see QueryWriter::code
	 */
	public function code( $typedescription, $includeSpecials = FALSE )
	{
		if ( isset( $this->sqltype_typeno[$typedescription] ) ) {
			$r = $this->sqltype_typeno[$typedescription];
		} else {
			$r = self::C_DATATYPE_SPECIFIED;
		}

		if ( $includeSpecials ) {
			return $r;
		}

		if ( $r >= QueryWriter::C_DATATYPE_RANGE_SPECIAL ) {
			return self::C_DATATYPE_SPECIFIED;
		}

		return $r;
	}

	/**
	 * @see QueryWriter::addUniqueIndex
	 */
	public function addUniqueConstraint( $type, $properties )
	{
		$tableNoQ = $this->esc( $type, TRUE );
		$columns = array();
		foreach( $properties as $key => $column ) $columns[$key] = $this->esc( $column );
		$table = $this->esc( $type );
		sort( $columns ); // Else we get multiple indexes due to order-effects
		$name = 'UQ_' . sha1( implode( ',', $columns ) );
		try {
			$sql = "ALTER TABLE $table
						 ADD UNIQUE INDEX $name (" . implode( ',', $columns ) . ")";
			$this->adapter->exec( $sql );
		} catch ( SQLException $e ) {
			//do nothing, dont use alter table ignore, this will delete duplicate records in 3-ways!
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * @see QueryWriter::addIndex
	 */
	public function addIndex( $type, $name, $property )
	{
		try {
			$table  = $this->esc( $type );
			$name   = preg_replace( '/\W/', '', $name );
			$column = $this->esc( $property );
			$this->adapter->exec( "CREATE INDEX $name ON $table ($column) " );
			return TRUE;
		} catch ( SQLException $e ) {
			return FALSE;
		}
	}

	/**
	 * @see QueryWriter::addFK
	 * @return bool
	 */
	public function addFK( $type, $targetType, $property, $targetProperty, $isDependent = FALSE )
	{
		$table = $this->esc( $type );
		$targetTable = $this->esc( $targetType );
		$targetTableNoQ = $this->esc( $targetType, TRUE );
		$field = $this->esc( $property );
		$fieldNoQ = $this->esc( $property, TRUE );
		$targetField = $this->esc( $targetProperty );
		$targetFieldNoQ = $this->esc( $targetProperty, TRUE );
		$tableNoQ = $this->esc( $type, TRUE );
		$fieldNoQ = $this->esc( $property, TRUE );
		if ( !is_null( $this->getForeignKeyForTypeProperty( $tableNoQ, $fieldNoQ ) ) ) return FALSE;

		//Widen the column if it's incapable of representing a foreign key (at least INT).
		$columns = $this->getColumns( $tableNoQ );
		$idType = $this->getTypeForID();
		if ( $this->code( $columns[$fieldNoQ] ) !==  $idType ) {
			$this->widenColumn( $type, $property, $idType );
		}

		$fkName = 'fk_'.($tableNoQ.'_'.$fieldNoQ);
		$cName = 'c_'.$fkName;
		try {
			$this->adapter->exec( "
				ALTER TABLE {$table}
				ADD CONSTRAINT $cName
				FOREIGN KEY $fkName ( `{$fieldNoQ}` ) REFERENCES `{$targetTableNoQ}`
				(`{$targetFieldNoQ}`) ON DELETE " . ( $isDependent ? 'CASCADE' : 'SET NULL' ) . ' ON UPDATE '.( $isDependent ? 'CASCADE' : 'SET NULL' ).';');
		} catch ( SQLException $e ) {
			// Failure of fk-constraints is not a problem
		}
		return true;
	}

	/**
	 * @see QueryWriter::sqlStateIn
	 */
	public function sqlStateIn( $state, $list, $extraDriverDetails = array() )
	{
		$stateMap = array(
			'42S02' => QueryWriter::C_SQLSTATE_NO_SUCH_TABLE,
			'42S22' => QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN,
			'23000' => QueryWriter::C_SQLSTATE_INTEGRITY_CONSTRAINT_VIOLATION,
		);

		if ( $state == 'HY000' && !empty( $extraDriverDetails[1] ) ) {
			$driverCode = $extraDriverDetails[1];

			if ( $driverCode == '1205' && in_array( QueryWriter::C_SQLSTATE_LOCK_TIMEOUT, $list ) ) {
				return true;
			}
		}

		return in_array( ( isset( $stateMap[$state] ) ? $stateMap[$state] : '0' ), $list );
	}

	/**
	 * @see QueryWriter::wipeAll
	 */
	public function wipeAll()
	{
		$this->adapter->exec( 'SET FOREIGN_KEY_CHECKS = 0;' );

		foreach ( $this->getTables() as $t ) {
			try { $this->adapter->exec( "DROP TABLE IF EXISTS `$t`" ); } catch ( SQLException $e ) { ; }
			try { $this->adapter->exec( "DROP VIEW IF EXISTS `$t`" ); } catch ( SQLException $e ) { ; }
		}

		$this->adapter->exec( 'SET FOREIGN_KEY_CHECKS = 1;' );
	}
}
