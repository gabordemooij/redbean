<?php

namespace RedBeanPHP\QueryWriter;

/* Experimental */

/**
 * This driver has been created in 2015 but it has never been distributed
 * because it was never finished. However, in the true spirit of open source
 * it is now available in the source of RedBeanPHP.
 *
 * Consider this driver experimental or help me finish it to
 * support the Firebird/Interbase database series.
 *
 */

use RedBeanPHP\QueryWriter\AQueryWriter as AQueryWriter;
use RedBeanPHP\QueryWriter as QueryWriter;
use RedBeanPHP\Adapter\DBAdapter as DBAdapter;
use RedBeanPHP\Adapter as Adapter;
use RedBeanPHP\RedException\SQL as SQLException;

/**
 * RedBeanPHP Firebird/Interbase QueryWriter.
 * This is a QueryWriter class for RedBeanPHP.
 * This QueryWriter provides support for the Firebird/Interbase database platform.
 * 
 * *** Warning - Experimental Software | Not ready for Production ****
 *
 * @file    RedBeanPHP/QueryWriter/Firebird.php
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * @copyright
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Firebird extends AQueryWriter implements QueryWriter
{
	/**
	 * Data types
	 */
	const C_DATATYPE_INTEGER           = 2;
	const C_DATATYPE_FLOAT             = 3;
	const C_DATATYPE_TEXT              = 5;
   
	/**
	 * @var DBAdapter
	 */
	protected $adapter;

	/**
	 * @var string
	 */
	protected $quoteCharacter = '"';

	/**
	 * Returns the insert suffix SQL Snippet
	 *
	 * @param string $table table
	 *
	 * @return  string $sql SQL Snippet
	 */
	protected function getInsertSuffix( $table )
	{
		return 'RETURNING id ';
	}

	/**
	 * @see AQueryWriter::getKeyMapForType
	 */
	protected function getKeyMapForType( $type )
	{
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
					ON (
						information_schema.referential_constraints.constraint_name = information_schema.key_column_usage.constraint_name
						AND information_schema.referential_constraints.constraint_schema = information_schema.key_column_usage.constraint_schema
						AND information_schema.referential_constraints.constraint_catalog = information_schema.key_column_usage.constraint_catalog
					)
			WHERE
				information_schema.key_column_usage.table_schema IN ( SELECT DATABASE() )
				AND information_schema.key_column_usage.table_name = ?
				AND information_schema.key_column_usage.constraint_name != \'PRIMARY\'
				AND information_schema.key_column_usage.referenced_table_name IS NOT NULL
		', array($table));
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
			Firebird::C_DATATYPE_INTEGER    => 'INTEGER',
			Firebird::C_DATATYPE_FLOAT   => 'FLOAT',
			Firebird::C_DATATYPE_TEXT     => 'VARCHAR(8190)',
		);

		$this->sqltype_typeno = array();

		foreach ( $this->typeno_sqltype as $k => $v ) {
			$this->sqltype_typeno[trim( strtoupper( $v ) )] = $k;
		}
	   
		print_r($this->sqltype_typeno);

		$this->adapter = $adapter;
	   
		$this->encoding = $this->adapter->getDatabase()->getMysqlEncoding();
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
		return $this->adapter->getCol( 'SELECT RDB$RELATION_NAME FROM RDB$RELATIONS
			WHERE RDB$VIEW_BLR IS NULL AND
			(RDB$SYSTEM_FLAG IS NULL OR RDB$SYSTEM_FLAG = 0)');
	}

	/**
	 * @see QueryWriter::createTable
	 */
	public function createTable( $table )
	{
		$tableNoQ         = $this->esc( $table );
		$tableSQL         = "CREATE TABLE \"{$table}\" ( id INT )";
		$dropGeneratorSQL = "DROP GENERATOR gen{$table}";
		$generatorSQL     = "CREATE GENERATOR gen{$table}";
		$generatorSQL2    = "SET GENERATOR gen{$table} TO 0";
		$triggerSQL       = "
			CREATE TRIGGER ai{$table} FOR \"{$table}\"
			ACTIVE BEFORE INSERT POSITION 0
			AS
			BEGIN
			if (NEW.id is NULL) then NEW.id = GEN_ID(gen{$table}, 1);
			END
		";

		try { $this->adapter->exec( $dropGeneratorSQL ); }catch( SQLException $e ) {};
		$this->adapter->exec( $tableSQL );
		$this->adapter->exec( $generatorSQL );
		$this->adapter->exec( $generatorSQL2 );
		$this->adapter->exec( $triggerSQL );
	}
   
	/**
	 * @see QueryWriter::widenColumn
	 */
	public function widenColumn( $type, $property, $dataType )
	{
		if ( !isset($this->typeno_sqltype[$dataType]) ) return FALSE;

		$table   = $this->esc( $type );
		$column  = $this->esc( $property );

		$newType = $this->typeno_sqltype[$dataType];

		$this->adapter->exec( "ALTER TABLE $table ALTER COLUMN $column TYPE $newType " );

		return TRUE;
	}

	/**
	 * @see QueryWriter::getColumns
	 */
	public function getColumns( $table )
	{
		$columnsRaw = $this->adapter->getAssoc( '
		SELECT
			RDB$RELATION_FIELDS.RDB$FIELD_NAME,
			CASE RDB$FIELDS.RDB$FIELD_TYPE
				WHEN 10 THEN \'FLOAT\'
				WHEN 8 THEN \'INTEGER\'
				WHEN 37 THEN \'VARCHAR\'
				ELSE RDB$FIELDS.RDB$FIELD_TYPE
			END AS FTYPE
			FROM RDB$RELATION_FIELDS
			LEFT JOIN RDB$FIELDS ON RDB$RELATION_FIELDS.RDB$FIELD_SOURCE = RDB$FIELDS.RDB$FIELD_NAME
			WHERE RDB$RELATION_FIELDS.RDB$RELATION_NAME = \''.($this->esc($table, true)).'\'
			ORDER BY RDB$RELATION_FIELDS.RDB$FIELD_POSITION
		');
		$columns = array();
		foreach( $columnsRaw as $rawKey => $columnRaw ) {
			$columns[ trim( $rawKey ) ] = trim( $columnRaw );
		}
		return $columns;
	}

	/**
	 * @see QueryWriter::scanType
	 */
	public function scanType( $value, $flagSpecial = FALSE )
	{
		if ( AQueryWriter::canBeTreatedAsInt( $value ) ) {
			return FireBird::C_DATATYPE_INTEGER;
		}
	   
		if ( !$this->startsWithZeros( $value ) && is_numeric( $value ) ) {
			return FireBird::C_DATATYPE_DOUBLE;
		}
	   
		return FireBird::C_DATATYPE_TEXT;
	}

	/**
	 * @see QueryWriter::code
	 */
	public function code( $typedescription, $includeSpecials = FALSE )
	{
		if ( isset( $this->sqltype_typeno[$typedescription] ) ) {
			return $this->sqltype_typeno[$typedescription];
		} else {
			return self::C_DATATYPE_SPECIFIED;
		}
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
		$name = 'UQ_'.substr( sha1( implode( ',', $columns ) ), 0, 28);
		try {
			$sql = "ALTER TABLE $table
						 ADD CONSTRAINT $name UNIQUE (" . implode( ',', $columns ) . ")";
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
				FOREIGN KEY $fkName ( {$fieldNoQ} ) REFERENCES {$targetTableNoQ}
				({$targetFieldNoQ}) ON DELETE " . ( $isDependent ? 'CASCADE' : 'SET NULL' ) . ' ON UPDATE '.( $isDependent ? 'CASCADE' : 'SET NULL' ).';');
		} catch ( SQLException $e ) {
			// Failure of fk-constraints is not a problem
		}
	}

	/**
	 * @see QueryWriter::sqlStateIn
	 */
	public function sqlStateIn( $state, $list, $extraDriverDetails = array() )
	{
		$stateMap = array(
			'42S02' => QueryWriter::C_SQLSTATE_NO_SUCH_TABLE,
			'42S22' => QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN,
			'23000' => QueryWriter::C_SQLSTATE_INTEGRITY_CONSTRAINT_VIOLATION
		);

		return in_array( ( isset( $stateMap[$state] ) ? $stateMap[$state] : '0' ), $list );
	}

	/**
	 * @see QueryWriter::wipeAll
	 */
	public function wipeAll()
	{
		if (AQueryWriter::$noNuke) throw new \Exception('The nuke() command has been disabled using noNuke() or R::feature(novice/...).');
		$tables = $this->getTables();
		foreach( $tables as $table ) {
			$table = trim( $table );
			$this->adapter->exec( "DROP TABLE \"{$table}\" " );
		}
	}
}

