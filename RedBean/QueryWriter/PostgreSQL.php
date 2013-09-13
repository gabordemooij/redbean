<?php
/**
 * RedBean PostgreSQL Query Writer
 *
 * @file    RedBean/QueryWriter/PostgreSQL.php
 * @desc    QueryWriter for the PostgreSQL database system.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license BSD/GPLv2
 *
 * (c) copyright G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_QueryWriter_PostgreSQL extends RedBean_QueryWriter_AQueryWriter implements RedBean_QueryWriter
{
	/**
	 * Data types
	 */
	const C_DATATYPE_INTEGER          = 0;
	const C_DATATYPE_DOUBLE           = 1;
	const C_DATATYPE_TEXT             = 3;
	const C_DATATYPE_SPECIAL_DATE     = 80;
	const C_DATATYPE_SPECIAL_DATETIME = 81;
	const C_DATATYPE_SPECIAL_POINT    = 90;
	const C_DATATYPE_SPECIAL_LSEG     = 91;
	const C_DATATYPE_SPECIAL_CIRCLE   = 92;
	const C_DATATYPE_SPECIAL_MONEY    = 93;
	const C_DATATYPE_SPECIFIED        = 99;

	/**
	 * @var RedBean_Adapter_DBAdapter
	 */
	protected $adapter;

	/**
	 * @var string
	 */
	protected $quoteCharacter = '"';

	/**
	 * @var string
	 */
	protected $defaultValue = 'DEFAULT';

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
	 * Add the constraints for a specific database driver: PostgreSQL.
	 *
	 * @param string $table     table to add fk constraints to
	 * @param string $table1    first reference table
	 * @param string $table2    second reference table
	 * @param string $property1 first reference column
	 * @param string $property2 second reference column
	 *
	 * @return boolean
	 */
	protected function constrain( $table, $table1, $table2, $property1, $property2 )
	{
		try {
			$adapter = $this->adapter;

			$fkCode  = 'fk' . md5( $table . $property1 . $property2 );

			$sql = "SELECT c.oid, n.nspname, c.relname,
				n2.nspname, c2.relname, cons.conname
				FROM pg_class c
				JOIN pg_namespace n ON n.oid = c.relnamespace
				LEFT OUTER JOIN pg_constraint cons ON cons.conrelid = c.oid
				LEFT OUTER JOIN pg_class c2 ON cons.confrelid = c2.oid
				LEFT OUTER JOIN pg_namespace n2 ON n2.oid = c2.relnamespace
				WHERE c.relkind = 'r'
					AND n.nspname IN ('public')
					AND (cons.contype = 'f' OR cons.contype IS NULL)
					AND (  cons.conname = '{$fkCode}a'	OR  cons.conname = '{$fkCode}b' )
			";

			$rows    = $adapter->get( $sql );
			if ( !count( $rows ) ) {
				$sql1 = "ALTER TABLE \"$table\" ADD CONSTRAINT
					{$fkCode}a FOREIGN KEY ($property1)
					REFERENCES \"$table1\" (id) ON DELETE CASCADE ";

				$sql2 = "ALTER TABLE \"$table\" ADD CONSTRAINT
					{$fkCode}b FOREIGN KEY ($property2)
					REFERENCES \"$table2\" (id) ON DELETE CASCADE ";

				$adapter->exec( $sql1 );

				$adapter->exec( $sql2 );
			}

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
			self::C_DATATYPE_INTEGER          => ' integer ',
			self::C_DATATYPE_DOUBLE           => ' double precision ',
			self::C_DATATYPE_TEXT             => ' text ',
			self::C_DATATYPE_SPECIAL_DATE     => ' date ',
			self::C_DATATYPE_SPECIAL_DATETIME => ' timestamp without time zone ',
			self::C_DATATYPE_SPECIAL_POINT    => ' point ',
			self::C_DATATYPE_SPECIAL_LSEG     => ' lseg ',
			self::C_DATATYPE_SPECIAL_CIRCLE   => ' circle ',
			self::C_DATATYPE_SPECIAL_MONEY    => ' money ',
		);

		$this->sqltype_typeno = array();

		foreach ( $this->typeno_sqltype as $k => $v ) {
			$this->sqltype_typeno[trim( strtolower( $v ) )] = $k;
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
	 * @see RedBean_QueryWriter::getTables
	 */
	public function getTables()
	{
		return $this->adapter->getCol( "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'" );
	}

	/**
	 * @see RedBean_QueryWriter::createTable
	 */
	public function createTable( $table )
	{
		$table = $this->esc( $table );

		$this->adapter->exec( " CREATE TABLE $table (id SERIAL PRIMARY KEY); " );
	}

	/**
	 * @see RedBean_QueryWriter::getColumns
	 */
	public function getColumns( $table )
	{
		$table      = $this->esc( $table, TRUE );

		$columnsRaw = $this->adapter->get( "SELECT column_name, data_type FROM information_schema.columns WHERE table_name='$table'" );

		$columns = array();
		foreach ( $columnsRaw as $r ) {
			$columns[$r['column_name']] = $r['data_type'];
		}

		return $columns;
	}

	/**
	 * @see RedBean_QueryWriter::scanType
	 */
	public function scanType( $value, $flagSpecial = FALSE )
	{
		$this->svalue = $value;

		if ( $flagSpecial && $value ) {
			if ( preg_match( '/^\d{4}\-\d\d-\d\d$/', $value ) ) {
				return RedBean_QueryWriter_PostgreSQL::C_DATATYPE_SPECIAL_DATE;
			}

			if ( preg_match( '/^\d{4}\-\d\d-\d\d\s\d\d:\d\d:\d\d(\.\d{1,6})?$/', $value ) ) {
				return RedBean_QueryWriter_PostgreSQL::C_DATATYPE_SPECIAL_DATETIME;
			}

			if ( preg_match( '/^\([\d\.]+,[\d\.]+\)$/', $value ) ) {
				return RedBean_QueryWriter_PostgreSQL::C_DATATYPE_SPECIAL_POINT;
			}

			if ( preg_match( '/^\[\([\d\.]+,[\d\.]+\),\([\d\.]+,[\d\.]+\)\]$/', $value ) ) {
				return RedBean_QueryWriter_PostgreSQL::C_DATATYPE_SPECIAL_LSEG;
			}

			if ( preg_match( '/^\<\([\d\.]+,[\d\.]+\),[\d\.]+\>$/', $value ) ) {
				return RedBean_QueryWriter_PostgreSQL::C_DATATYPE_SPECIAL_CIRCLE;
			}

			if ( preg_match( '/^\-?\$\d+/', $value ) ) {
				return RedBean_QueryWriter_PostgreSQL::C_DATATYPE_SPECIAL_MONEY;
			}
		}

		$sz = ( $this->startsWithZeros( $value ) );

		if ( $sz ) {
			return self::C_DATATYPE_TEXT;
		}

		if ( $value === NULL || ( $value instanceof RedBean_Driver_PDO_NULL ) || ( is_numeric( $value )
				&& floor( $value ) == $value
				&& $value < 2147483648
				&& $value > -2147483648 )
		) {
			return self::C_DATATYPE_INTEGER;
		} elseif ( is_numeric( $value ) ) {
			return self::C_DATATYPE_DOUBLE;
		} else {
			return self::C_DATATYPE_TEXT;
		}
	}

	/**
	 * @see RedBean_QueryWriter::code
	 */
	public function code( $typedescription, $includeSpecials = FALSE )
	{
		$r = ( isset( $this->sqltype_typeno[$typedescription] ) ) ? $this->sqltype_typeno[$typedescription] : 99;

		if ( $includeSpecials ) return $r;

		if ( $r >= RedBean_QueryWriter::C_DATATYPE_RANGE_SPECIAL ) {
			return self::C_DATATYPE_SPECIFIED;
		}

		return $r;
	}

	/**
	 * @see RedBean_QueryWriter::widenColumn
	 */
	public function widenColumn( $type, $column, $datatype )
	{
		$table   = $type;
		$type    = $datatype;

		$table   = $this->esc( $table );
		$column  = $this->esc( $column );

		$newtype = $this->typeno_sqltype[$type];

		$this->adapter->exec( "ALTER TABLE $table \n\t ALTER COLUMN $column TYPE $newtype " );
	}

	/**
	 * @see RedBean_QueryWriter::addUniqueIndex
	 */
	public function addUniqueIndex( $table, $columns )
	{
		$table = $this->esc( $table, TRUE );

		sort( $columns ); //else we get multiple indexes due to order-effects

		foreach ( $columns as $k => $v ) {
			$columns[$k] = $this->esc( $v );
		}

		$r = $this->adapter->get( "SELECT i.relname AS index_name
			FROM pg_class t,pg_class i,pg_index ix,pg_attribute a
			WHERE t.oid = ix.indrelid
				AND i.oid = ix.indexrelid
				AND a.attrelid = t.oid
				AND a.attnum = ANY(ix.indkey)
				AND t.relkind = 'r'
				AND t.relname = '$table'
			ORDER BY t.relname, i.relname;" );

		$name = "UQ_" . sha1( $table . implode( ',', $columns ) );

		if ( $r ) {
			foreach ( $r as $i ) {
				if ( strtolower( $i['index_name'] ) == strtolower( $name ) ) {
					return;
				}
			}
		}

		$sql = "ALTER TABLE \"$table\"
                ADD CONSTRAINT $name UNIQUE (" . implode( ',', $columns ) . ")";

		$this->adapter->exec( $sql );
	}

	/**
	 * @see RedBean_QueryWriter::sqlStateIn
	 */
	public function sqlStateIn( $state, $list )
	{
		$stateMap = array(
			'42P01' => RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_TABLE,
			'42703' => RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN,
			'23505' => RedBean_QueryWriter::C_SQLSTATE_INTEGRITY_CONSTRAINT_VIOLATION
		);

		return in_array( ( isset( $stateMap[$state] ) ? $stateMap[$state] : '0' ), $list );
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

		if ( $this->adapter->getCell( "SELECT COUNT(*) FROM pg_class WHERE relname = '$name'" ) ) {
			return;
		}

		try {
			$this->adapter->exec( "CREATE INDEX $name ON $table ($column) " );
		} catch ( Exception $e ) {
		}
	}

	/**
	 * @see RedBean_QueryWriter::addFK
	 */
	public function addFK( $type, $targetType, $field, $targetField, $isDep = FALSE )
	{
		try {
			$table           = $this->esc( $type );
			$column          = $this->esc( $field );

			$tableNoQ        = $this->esc( $type, TRUE );
			$columnNoQ       = $this->esc( $field, TRUE );

			$targetTable     = $this->esc( $targetType );
			$targetTableNoQ  = $this->esc( $targetType, TRUE );

			$targetColumn    = $this->esc( $targetField );
			$targetColumnNoQ = $this->esc( $targetField, TRUE );

			$sql = "SELECT
				tc.constraint_name, tc.table_name,
				kcu.column_name, ccu.table_name AS foreign_table_name,
				ccu.column_name AS foreign_column_name,rc.delete_rule
				FROM information_schema.table_constraints AS tc
				JOIN information_schema.key_column_usage AS kcu ON tc.constraint_name = kcu.constraint_name
				JOIN information_schema.constraint_column_usage AS ccu ON ccu.constraint_name = tc.constraint_name
				JOIN information_schema.referential_constraints AS rc ON ccu.constraint_name = rc.constraint_name
				WHERE constraint_type = 'FOREIGN KEY' AND tc.table_catalog=current_database()
					AND tc.table_name = '$tableNoQ'
					AND ccu.table_name = '$targetTableNoQ'
					AND kcu.column_name = '$columnNoQ'
					AND ccu.column_name = '$targetColumnNoQ'
			";

			$row             = $this->adapter->getRow( $sql );

			$flagAddKey      = FALSE;

			if ( !$row ) $flagAddKey = TRUE;

			if ( $row ) {
				if ( ( $row['delete_rule'] == 'SET NULL' && $isDep ) ||
					( $row['delete_rule'] != 'SET NULL' && !$isDep )
				) {
					// Delete old key and order a new one
					$flagAddKey = TRUE;
					$cName      = $row['constraint_name'];
					$sql        = "ALTER TABLE $table DROP CONSTRAINT $cName ";
					$this->adapter->exec( $sql );
				}
			}
			if ( $flagAddKey ) {
				$delRule = ( $isDep ? 'CASCADE' : 'SET NULL' );

				$this->adapter->exec( "ALTER TABLE  $table
					ADD FOREIGN KEY (  $column ) REFERENCES  $targetTable (
					$targetColumn) ON DELETE $delRule ON UPDATE SET NULL DEFERRABLE ;" );

				return TRUE;
			}

			return FALSE;
		} catch ( Exception $e ) {
			return FALSE;
		}
	}

	/**
	 * @see RedBean_QueryWriter::wipeAll
	 */
	public function wipeAll()
	{
		$this->adapter->exec( 'SET CONSTRAINTS ALL DEFERRED' );

		foreach ( $this->getTables() as $t ) {
			$t = $this->esc( $t );

			$this->adapter->exec( "DROP TABLE IF EXISTS $t CASCADE " );
		}

		$this->adapter->exec( 'SET CONSTRAINTS ALL IMMEDIATE' );
	}

}
