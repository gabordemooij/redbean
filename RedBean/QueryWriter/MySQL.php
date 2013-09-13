<?php
/**
 * RedBean MySQLWriter
 *
 * @file    RedBean/QueryWriter/MySQL.php
 * @desc    Represents a MySQL Database to RedBean
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_QueryWriter_MySQL extends RedBean_QueryWriter_AQueryWriter implements RedBean_QueryWriter
{

	/**
	 * Data types
	 */
	const C_DATATYPE_BOOL             = 0;
	const C_DATATYPE_UINT8            = 1;
	const C_DATATYPE_UINT32           = 2;
	const C_DATATYPE_DOUBLE           = 3;
	const C_DATATYPE_TEXT8            = 4;
	const C_DATATYPE_TEXT16           = 5;
	const C_DATATYPE_TEXT32           = 6;
	const C_DATATYPE_SPECIAL_DATE     = 80;
	const C_DATATYPE_SPECIAL_DATETIME = 81;
	const C_DATATYPE_SPECIAL_POINT    = 90;
	const C_DATATYPE_SPECIFIED        = 99;

	/**
	 * @var RedBean_Adapter_DBAdapter
	 */
	protected $adapter;

	/**
	 * @var string
	 */
	protected $quoteCharacter = '`';

	/**
	 * Add the constraints for a specific database driver: MySQL.
	 *
	 * @todo Too many arguments; find a way to solve this in a neater way.
	 *
	 * @param string $table     table     table to add constrains to
	 * @param string $table1    table1    first reference table
	 * @param string $table2    table2    second reference table
	 * @param string $property1 property1 first column
	 * @param string $property2 property2 second column
	 *
	 * @return boolean $succes whether the constraint has been applied
	 */
	protected function constrain( $table, $table1, $table2, $property1, $property2 )
	{
		try {
			$db  = $this->adapter->getCell( 'SELECT database()' );

			$fks = $this->adapter->getCell(
				"SELECT count(*)
				FROM information_schema.KEY_COLUMN_USAGE
				WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND
				CONSTRAINT_NAME <>'PRIMARY' AND REFERENCED_TABLE_NAME IS NOT NULL",
				array( $db, $table )
			);

			// already foreign keys added in this association table
			if ( $fks > 0 ) {
				return FALSE;
			}

			$columns = $this->getColumns( $table );

			if ( $this->code( $columns[$property1] ) !== RedBean_QueryWriter_MySQL::C_DATATYPE_UINT32 ) {
				$this->widenColumn( $table, $property1, RedBean_QueryWriter_MySQL::C_DATATYPE_UINT32 );
			}

			if ( $this->code( $columns[$property2] ) !== RedBean_QueryWriter_MySQL::C_DATATYPE_UINT32 ) {
				$this->widenColumn( $table, $property2, RedBean_QueryWriter_MySQL::C_DATATYPE_UINT32 );
			}

			$sql = "
				ALTER TABLE " . $this->esc( $table ) . "
				ADD FOREIGN KEY($property1) references `$table1`(id) ON DELETE CASCADE;
			";

			$this->adapter->exec( $sql );

			$sql = "
				ALTER TABLE " . $this->esc( $table ) . "
				ADD FOREIGN KEY($property2) references `$table2`(id) ON DELETE CASCADE
			";

			$this->adapter->exec( $sql );

			return TRUE;
		} catch ( Exception $e ) {
			return FALSE;
		}
	}

	/**
	 * Constructor
	 *
	 * @param RedBean_Adapter $adapter Database Adapter
	 */
	public function __construct( RedBean_Adapter $adapter )
	{
		$this->typeno_sqltype = array(
			RedBean_QueryWriter_MySQL::C_DATATYPE_BOOL             => ' TINYINT(1) UNSIGNED ',
			RedBean_QueryWriter_MySQL::C_DATATYPE_UINT8            => ' TINYINT(3) UNSIGNED ',
			RedBean_QueryWriter_MySQL::C_DATATYPE_UINT32           => ' INT(11) UNSIGNED ',
			RedBean_QueryWriter_MySQL::C_DATATYPE_DOUBLE           => ' DOUBLE ',
			RedBean_QueryWriter_MySQL::C_DATATYPE_TEXT8            => ' VARCHAR(255) ',
			RedBean_QueryWriter_MySQL::C_DATATYPE_TEXT16           => ' TEXT ',
			RedBean_QueryWriter_MySQL::C_DATATYPE_TEXT32           => ' LONGTEXT ',
			RedBean_QueryWriter_MySQL::C_DATATYPE_SPECIAL_DATE     => ' DATE ',
			RedBean_QueryWriter_MySQL::C_DATATYPE_SPECIAL_DATETIME => ' DATETIME ',
			RedBean_QueryWriter_MySQL::C_DATATYPE_SPECIAL_POINT    => ' POINT ',
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
	 * @return integer $const data type to be used for IDS.
	 */
	public function getTypeForID()
	{
		return self::C_DATATYPE_UINT32;
	}

	/**
	 * @see RedBean_QueryWriter::getTables
	 */
	public function getTables()
	{
		return $this->adapter->getCol( 'show tables' );
	}

	/**
	 * @see RedBean_QueryWriter::createTable
	 */
	public function createTable( $table )
	{
		$table = $this->esc( $table );

		$encoding = $this->adapter->getDatabase()->getMysqlEncoding();
		$sql   = "CREATE TABLE $table (id INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT, PRIMARY KEY ( id )) ENGINE = InnoDB DEFAULT CHARSET={$encoding} COLLATE={$encoding}_unicode_ci ";

		$this->adapter->exec( $sql );
	}

	/**
	 * @see RedBean_QueryWriter::getColumns
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
	 * @see RedBean_QueryWriter::scanType
	 */
	public function scanType( $value, $flagSpecial = FALSE )
	{
		$this->svalue = $value;

		if ( is_null( $value ) ) return RedBean_QueryWriter_MySQL::C_DATATYPE_BOOL;

		if ( $flagSpecial ) {
			if ( preg_match( '/^\d{4}\-\d\d-\d\d$/', $value ) ) {
				return RedBean_QueryWriter_MySQL::C_DATATYPE_SPECIAL_DATE;
			}
			if ( preg_match( '/^\d{4}\-\d\d-\d\d\s\d\d:\d\d:\d\d$/', $value ) ) {
				return RedBean_QueryWriter_MySQL::C_DATATYPE_SPECIAL_DATETIME;
			}
		}

		$value = strval( $value );

		if ( !$this->startsWithZeros( $value ) ) {
			if ( $value === TRUE || $value === FALSE || $value === '1' || $value === '' ) {
				return RedBean_QueryWriter_MySQL::C_DATATYPE_BOOL;
			}

			if ( is_numeric( $value ) && ( floor( $value ) == $value ) && $value >= 0 && $value <= 255 ) {
				return RedBean_QueryWriter_MySQL::C_DATATYPE_UINT8;
			}

			if ( is_numeric( $value ) && ( floor( $value ) == $value ) && $value >= 0 && $value <= 4294967295 ) {
				return RedBean_QueryWriter_MySQL::C_DATATYPE_UINT32;
			}

			if ( is_numeric( $value ) ) {
				return RedBean_QueryWriter_MySQL::C_DATATYPE_DOUBLE;
			}
		}

		if ( mb_strlen( $value, 'UTF-8' ) <= 255 ) {
			return RedBean_QueryWriter_MySQL::C_DATATYPE_TEXT8;
		}

		if ( mb_strlen( $value, 'UTF-8' ) <= 65535 ) {
			return RedBean_QueryWriter_MySQL::C_DATATYPE_TEXT16;
		}

		return RedBean_QueryWriter_MySQL::C_DATATYPE_TEXT32;
	}

	/**
	 * @see RedBean_QueryWriter::code
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

		if ( $r >= RedBean_QueryWriter::C_DATATYPE_RANGE_SPECIAL ) {
			return self::C_DATATYPE_SPECIFIED;
		}

		return $r;
	}

	/**
	 * @see RedBean_QueryWriter::addUniqueIndex
	 */
	public function addUniqueIndex( $table, $columns )
	{
		$table = $this->esc( $table );

		sort( $columns ); // Else we get multiple indexes due to order-effects

		foreach ( $columns as $k => $v ) {
			$columns[$k] = $this->esc( $v );
		}

		$r    = $this->adapter->get( "SHOW INDEX FROM $table" );

		$name = 'UQ_' . sha1( implode( ',', $columns ) );

		if ( $r ) {
			foreach ( $r as $i ) {
				if ( $i['Key_name'] == $name ) {
					return;
				}
			}
		}

		$sql = "ALTER IGNORE TABLE $table
                ADD UNIQUE INDEX $name (" . implode( ',', $columns ) . ")";

		$this->adapter->exec( $sql );
	}

	/**
	 * @see RedBean_QueryWriter::addIndex
	 */
	public function addIndex( $type, $name, $column )
	{
		$table  = $type;
		$table  = $this->esc( $table );

		$name   = preg_replace( '/\W/', '', $name );

		$column = $this->esc( $column );

		foreach ( $this->adapter->get( "SHOW INDEX FROM $table " ) as $ind ) if ( $ind['Key_name'] === $name ) return;

		try {
			$this->adapter->exec( "CREATE INDEX $name ON $table ($column) " );
		} catch ( Exception $e ) {
		}
	}

	/**
	 * @see RedBean_QueryWriter::sqlStateIn
	 */
	public function sqlStateIn( $state, $list )
	{
		$stateMap = array(
			'42S02' => RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_TABLE,
			'42S22' => RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN,
			'23000' => RedBean_QueryWriter::C_SQLSTATE_INTEGRITY_CONSTRAINT_VIOLATION
		);

		return in_array( ( isset( $stateMap[$state] ) ? $stateMap[$state] : '0' ), $list );
	}

	/**
	 * @see RedBean_QueryWriter::wipeAll
	 */
	public function wipeAll()
	{
		$this->adapter->exec( 'SET FOREIGN_KEY_CHECKS = 0;' );

		foreach ( $this->getTables() as $t ) {
			try {
				$this->adapter->exec( "DROP TABLE IF EXISTS `$t`" );
			} catch ( Exception $e ) {
			}

			try {
				$this->adapter->exec( "DROP VIEW IF EXISTS `$t`" );
			} catch ( Exception $e ) {
			}
		}

		$this->adapter->exec( 'SET FOREIGN_KEY_CHECKS = 1;' );
	}
}
