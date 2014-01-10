<?php
/**
 * RedBean SQLServerWriter
 *
 * @file    RedBean/QueryWriter/SQLServer.php
 * @desc    Represents a SQLServer Database to RedBean
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_QueryWriter_SQLServer extends RedBean_QueryWriter_AQueryWriter implements RedBean_QueryWriter
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
	protected $quoteCharacter = '';

	/**
	 * Add the constraints for a specific database driver: SQLServer.
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
			$db  = $this->adapter->getCell( 'SELECT DB_NAME()' );

			$fks = $this->adapter->getCell(
				"SELECT
                    COUNT(*)
                FROM
                    INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS C
                INNER JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS FK
                    ON C.CONSTRAINT_NAME = FK.CONSTRAINT_NAME
                INNER JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS PK
                    ON C.UNIQUE_CONSTRAINT_NAME = PK.CONSTRAINT_NAME
                INNER JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE CU
                    ON C.CONSTRAINT_NAME = CU.CONSTRAINT_NAME
                INNER JOIN (
                            SELECT
                                i1.TABLE_NAME,
                                i2.COLUMN_NAME
                            FROM
                                INFORMATION_SCHEMA.TABLE_CONSTRAINTS i1
                            INNER JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE i2
                                ON i1.CONSTRAINT_NAME = i2.CONSTRAINT_NAME
                            WHERE
                                i1.CONSTRAINT_TYPE = 'PRIMARY KEY'
                           ) PT
                    ON PT.TABLE_NAME = PK.TABLE_NAME
                WHERE C.CONSTRAINT_CATALOG = ?
                AND PK.TABLE_NAME = ?",
				array( $db, $table )
			);

			// already foreign keys added in this association table
			if ( $fks > 0 ) {
				return FALSE;
			}

			$columns = $this->getColumns( $table );

			if ( $this->code( $columns[$property1] ) !== RedBean_QueryWriter_SQLServer::C_DATATYPE_UINT32 ) {
				$this->widenColumn( $table, $property1, RedBean_QueryWriter_SQLServer::C_DATATYPE_UINT32 );
			}

			if ( $this->code( $columns[$property2] ) !== RedBean_QueryWriter_SQLServer::C_DATATYPE_UINT32 ) {
				$this->widenColumn( $table, $property2, RedBean_QueryWriter_SQLServer::C_DATATYPE_UINT32 );
			}

			$sql = "
				ALTER TABLE [" . $this->esc( $table ) . "]
				ADD FOREIGN KEY($property1) references $table1(id) ON DELETE CASCADE;
			";

			$this->adapter->exec( $sql );

			$sql = "
				ALTER TABLE " . $this->esc( $table ) . "
				ADD FOREIGN KEY($property2) references $table2(id) ON DELETE CASCADE
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
			RedBean_QueryWriter_SQLServer::C_DATATYPE_BOOL             => ' TINYINT ',
			RedBean_QueryWriter_SQLServer::C_DATATYPE_UINT8            => ' TINYINT ',
			RedBean_QueryWriter_SQLServer::C_DATATYPE_UINT32           => ' INT ',
			RedBean_QueryWriter_SQLServer::C_DATATYPE_DOUBLE           => ' DOUBLE ',
			RedBean_QueryWriter_SQLServer::C_DATATYPE_TEXT8            => ' VARCHAR(255) ',
			RedBean_QueryWriter_SQLServer::C_DATATYPE_TEXT16           => ' TEXT ',
			RedBean_QueryWriter_SQLServer::C_DATATYPE_TEXT32           => ' LONGTEXT ',
			RedBean_QueryWriter_SQLServer::C_DATATYPE_SPECIAL_DATE     => ' DATE ',
			RedBean_QueryWriter_SQLServer::C_DATATYPE_SPECIAL_DATETIME => ' DATETIME ',
			RedBean_QueryWriter_SQLServer::C_DATATYPE_SPECIAL_POINT    => ' POINT ',
		);

		$this->sqltype_typeno = array();

		foreach ( $this->typeno_sqltype as $k => $v ) {
			$this->sqltype_typeno[trim( strtolower( $v ) )] = $k;
		}

		$this->adapter = $adapter;

		//$this->encoding = $this->adapter->getDatabase()->getSQLServerEncoding();
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
		return $this->adapter->getCol( 'SELECT * FROM sys.Tables' );
	}

    /**
     * Inserts a record into the database using a series of insert columns
     * and corresponding insertvalues. Returns the insert id.
     *
     * @param string $table         table to perform query on
     * @param array  $insertcolumns columns to be inserted
     * @param array  $insertvalues  values to be inserted
     *
     * @return integer
     */
    protected function insertRecord( $type, $insertcolumns, $insertvalues )
    {
        $suffix  = $this->getInsertSuffix( $type );
        $table   = $this->esc( $type );

        if ( count( $insertvalues ) > 0 && is_array( $insertvalues[0] ) && count( $insertvalues[0] ) > 0 ) {
            foreach ( $insertcolumns as $k => $v ) {
                $insertcolumns[$k] = $this->esc( $v );
            }

            $insertSQL = "INSERT INTO $table ( " . implode( ',', $insertcolumns ) . " ) VALUES
			( " . implode( ',', array_fill( 0, count( $insertcolumns ), ' ? ' ) ) . " ) $suffix";

            $ids = array();
            foreach ( $insertvalues as $i => $insertvalue ) {
                $ids[] = $this->adapter->getCell( $insertSQL, $insertvalue, $i );
            }

            $result = count( $ids ) === 1 ? array_pop( $ids ) : $ids;
        }

        if ( $suffix ) return $result;

        $last_id = $this->adapter->getInsertID();

        return $last_id;
    }

	/**
	 * @see RedBean_QueryWriter::createTable
	 */
	public function createTable( $table )
	{
		$table = $this->esc( $table );

		$sql   = "CREATE TABLE [$table] ([id] [int] IDENTITY(1,1) NOT NULL, CONSTRAINT [PK_$table] PRIMARY KEY ( [id] ))";

		$this->adapter->exec( $sql );
	}

	/**
	 * @see RedBean_QueryWriter::getColumns
	 */
	public function getColumns( $table )
	{
		$columnsRaw = $this->adapter->get( "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '" . $this->esc( $table ) . "'" );

		$columns = array();
		foreach ( $columnsRaw as $r ) {
			$columns[$r['COLUMN_NAME']] = $r['DATA_TYPE'];
		}

		return $columns;
	}

	/**
	 * @see RedBean_QueryWriter::scanType
	 */
	public function scanType( $value, $flagSpecial = FALSE )
	{
		$this->svalue = $value;

		if ( is_null( $value ) ) return RedBean_QueryWriter_SQLServer::C_DATATYPE_BOOL;

		if ( $flagSpecial ) {
			if ( preg_match( '/^\d{4}\-\d\d-\d\d$/', $value ) ) {
				return RedBean_QueryWriter_SQLServer::C_DATATYPE_SPECIAL_DATE;
			}
			if ( preg_match( '/^\d{4}\-\d\d-\d\d\s\d\d:\d\d:\d\d$/', $value ) ) {
				return RedBean_QueryWriter_SQLServer::C_DATATYPE_SPECIAL_DATETIME;
			}
		}

		$value = strval( $value );

		if ( !$this->startsWithZeros( $value ) ) {
			if ( $value === TRUE || $value === FALSE || $value === '1' || $value === '' ) {
				return RedBean_QueryWriter_SQLServer::C_DATATYPE_BOOL;
			}

			if ( is_numeric( $value ) && ( floor( $value ) == $value ) && $value >= 0 && $value <= 255 ) {
				return RedBean_QueryWriter_SQLServer::C_DATATYPE_UINT8;
			}

			if ( is_numeric( $value ) && ( floor( $value ) == $value ) && $value >= 0 && $value <= 4294967295 ) {
				return RedBean_QueryWriter_SQLServer::C_DATATYPE_UINT32;
			}

			if ( is_numeric( $value ) ) {
				return RedBean_QueryWriter_SQLServer::C_DATATYPE_DOUBLE;
			}
		}

		if ( mb_strlen( $value, 'UTF-8' ) <= 255 ) {
			return RedBean_QueryWriter_SQLServer::C_DATATYPE_TEXT8;
		}

		if ( mb_strlen( $value, 'UTF-8' ) <= 65535 ) {
			return RedBean_QueryWriter_SQLServer::C_DATATYPE_TEXT16;
		}

		return RedBean_QueryWriter_SQLServer::C_DATATYPE_TEXT32;
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

		$r    = $this->adapter->get( "EXEC sys.sp_helpindex @objname = '$table'" );

		$name = 'UQ_' . sha1( implode( ',', $columns ) );

		if ( $r ) {
			foreach ( $r as $i ) {
				if ( $i['index_name'] == $name ) {
					return;
				}
			}
		}

		$sql = "CREATE UNIQUE INDEX $name
                    ON $table (" . implode( ',', $columns ) . ")";

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

		foreach ( $this->adapter->get( "EXEC sys.sp_helpindex @objname = '$table' " ) as $ind ) if ( $ind['index_name'] === $name ) return;

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
		foreach ( $this->getTables() as $t ) {
			try {
				$this->adapter->exec( "EXEC sp_msforeachtable 'ALTER TABLE ? NOCHECK CONSTRAINT all'; IF OBJECT_ID('[$t]', 'U') IS NOT NULL DROP TABLE [$t]; EXEC sp_msforeachtable 'ALTER TABLE ? WITH CHECK CHECK CONSTRAINT all';" );
			} catch ( Exception $e ) {
			}
		}
	}

    /**
     * @see RedBean_QueryWriter::addFK
     */
    public function addFK( $type, $targetType, $field, $targetField, $isDependent = FALSE )
    {
        $table           = $this->esc( $type );
        $tableNoQ        = $this->esc( $type, TRUE );

        $targetTable     = $this->esc( $targetType );

        $column          = $this->esc( $field );
        $columnNoQ       = $this->esc( $field, TRUE );

        $targetColumn    = $this->esc( $targetField );
        $targetColumnNoQ = $this->esc( $targetField, TRUE );

        $db = $this->adapter->getCell( 'SELECT DB_NAME()' );

        $fkName = 'fk_' . $tableNoQ . '_' . $columnNoQ . '_' . $targetColumnNoQ . ( $isDependent ? '_casc' : '' );
        $cName  = 'cons_' . $fkName;

        $cfks = $this->adapter->getCell( "
			SELECT CONSTRAINT_NAME
			FROM information_schema.KEY_COLUMN_USAGE
			WHERE TABLE_SCHEMA ='$db' AND TABLE_NAME = '$tableNoQ'  AND COLUMN_NAME = '$columnNoQ'
		" );

        $flagAddKey = FALSE;

        try {
            // No keys
            if ( !$cfks ) {
                $flagAddKey = TRUE; //go get a new key
            }

            // Has fk, but different setting, --remove
            if ( $cfks && $cfks != $cName ) {
                $this->adapter->exec( "ALTER TABLE [$table] DROP FOREIGN KEY [$cfks] " );
                $flagAddKey = TRUE; //go get a new key.
            }

            if ( $flagAddKey ) {
                $this->adapter->exec( "ALTER TABLE [$table]
				ADD CONSTRAINT [$cName] FOREIGN KEY ( [$column] ) REFERENCES [$targetTable] (
				[$targetColumn]) ON DELETE " . ( $isDependent ? 'CASCADE' : 'SET NULL' ) . ' ON UPDATE SET NULL ;' );
            }
        } catch ( Exception $e ) {
            // Failure of fk-constraints is not a problem
        }
    }

    /**
     * @see RedBean_QueryWriter::addColumn
     */
    public function addColumn( $type, $column, $field )
    {
        $table  = $type;
        $type   = $field;
        $table  = $this->esc( $table );
        $column = $this->esc( $column );

        $type = ( isset( $this->typeno_sqltype[$type] ) ) ? $this->typeno_sqltype[$type] : '';

        $this->adapter->exec( "ALTER TABLE [$table] ADD [$column] $type " );
    }
}
