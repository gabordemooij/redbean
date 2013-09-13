<?php
/**
 * RedBean Oracle Driver
 *
 * @file               RedBean/QueryWriter/Oracle.php
 * @description        Query Writer for Oracle databases.
 *
 * @author             Stephane Gerber
 * @license            BSD/GPLv2
 *
 *
 * RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_QueryWriter_Oracle extends RedBean_QueryWriter_AQueryWriter implements RedBean_QueryWriter
{
	/**
	 * Adapter
	 *
	 * @var RedBean_Adapter
	 */
	protected $adapter;

	/**
	 * character to escape keyword table/column names
	 *
	 * @var string
	 */
	protected $quoteCharacter = '"';

	/**
	 * Boolean Data type
	 *
	 * @var integer
	 */
	const C_DATATYPE_BOOL = 0;

	/**
	 * Unsigned 8BIT Integer
	 *
	 * @var integer
	 */
	const C_DATATYPE_UINT8 = 1;

	/**
	 * Unsigned 32BIT Integer
	 *
	 * @var integer
	 */
	const C_DATATYPE_UINT32 = 2;

	/**
	 * Double precision floating point number and
	 * negative numbers.
	 *
	 * @var integer
	 */
	const C_DATATYPE_DOUBLE = 3;

	/**
	 * Standard Text column (like varchar255)
	 * At least 8BIT character support.
	 *
	 * @var integer
	 */
	const C_DATATYPE_TEXT8 = 4;

	/**
	 * Long text column (16BIT)
	 *
	 * @var integer
	 */
	const C_DATATYPE_TEXT16 = 5;

	/**
	 * 32BIT long textfield (number of characters can be as high as 32BIT) Data type
	 * This is the biggest column that RedBean supports. If possible you may write
	 * an implementation that stores even bigger values.
	 *
	 * @var integer
	 */
	const C_DATATYPE_TEXT32 = 6;

	/**
	 * Special type date for storing date values: YYYY-MM-DD or YYYY-MM-DD HH:MM(:SS)
	 *
	 * @var integer
	 */
	const C_DATATYPE_SPECIAL_DATE = 80;

	/**
	 * Special type date for storing date values: YYYY-MM-DD HH:MM:SS.FF
	 *
	 * @var integer
	 */
	const C_DATATYPE_SPECIAL_TIMESTAMP = 81;

	/**
	 * Specified. This means the developer or DBA
	 * has altered the column to a different type not
	 * recognized by RedBean. This high number makes sure
	 * it will not be converted back to another type by accident.
	 *
	 * @var integer
	 */
	const C_DATATYPE_SPECIFIED = 99;

	/**
	 * Spatial types
	 *
	 * @var integer
	 */
	const C_DATATYPE_SPECIAL_POINT              = 100;
	const C_DATATYPE_SPECIAL_LINESTRING         = 101;
	const C_DATATYPE_SPECIAL_GEOMETRY           = 102;
	const C_DATATYPE_SPECIAL_POLYGON            = 103;
	const C_DATATYPE_SPECIAL_MULTIPOINT         = 104;
	const C_DATATYPE_SPECIAL_MULTIPOLYGON       = 105;
	const C_DATATYPE_SPECIAL_GEOMETRYCOLLECTION = 106;

	/**
	 * Do everything that needs to be done to format a column name.
	 *
	 * @param string $name of column
	 *
	 * @return string $column name
	 */
	public function esc( $c, $q = FALSE )
	{
		return parent::esc( ( !$q ) ? strtoupper( $c ) : $c, $q );
	}

	/**
	 * Do everything that needs to be done to format a table name.
	 *
	 * @param string $name of table
	 *
	 * @return string table name
	 */
	public function __construct( RedBean_Adapter $a )
	{
		$this->adapter        = $a;
		$this->typeno_sqltype = array(
			RedBean_QueryWriter_Oracle::C_DATATYPE_BOOL              => 'NUMBER(1,0)',
			RedBean_QueryWriter_Oracle::C_DATATYPE_UINT8             => 'NUMBER(3,0)',
			RedBean_QueryWriter_Oracle::C_DATATYPE_UINT32            => 'NUMBER(11,0)',
			RedBean_QueryWriter_Oracle::C_DATATYPE_DOUBLE            => 'FLOAT',
			RedBean_QueryWriter_Oracle::C_DATATYPE_TEXT8             => 'NVARCHAR2(255)',
			RedBean_QueryWriter_Oracle::C_DATATYPE_TEXT16            => 'NVARCHAR2(2000)',
			RedBean_QueryWriter_Oracle::C_DATATYPE_TEXT32            => 'CLOB',
			RedBean_QueryWriter_Oracle::C_DATATYPE_SPECIAL_DATE      => 'DATE',
			RedBean_QueryWriter_Oracle::C_DATATYPE_SPECIAL_TIMESTAMP => 'TIMESTAMP(6)' );

		$this->sqltype_typeno = array();

		foreach ( $this->typeno_sqltype as $k => $v ) {
			$this->sqltype_typeno[$v] = $k;
		}
	}

	/**
	 * Adds a Unique index constrain to the table.
	 *
	 * @param string $table table
	 * @param string $col1  column
	 * @param string $col2  column
	 *
	 * @return void
	 */
	public function addUniqueIndex( $table, $columns )
	{
		$tableNoQuote   = strtoupper( $this->esc( $table, TRUE ) );
		$tableWithQuote = strtoupper( $this->esc( $table ) );

		sort( $columns ); //else we get multiple indexes due to order-effects

		foreach ( $columns as $k => $v ) {
			$columns[$k] = strtoupper( $this->esc( $v, TRUE ) );
		}

		$r = $this->adapter->get( "SELECT INDEX_NAME FROM USER_INDEXES WHERE TABLE_NAME='$tableNoQuote' AND UNIQUENESS='UNIQUE'" );

		$name = strtoupper( 'UQ_' . substr( sha1( implode( ',', $columns ) ), 0, 20 ) );

		if ( $r ) {
			foreach ( $r as $i ) {
				if ( $i['index_name'] == $name ) return;
			}
		}

		$sql = "ALTER TABLE $tableWithQuote
                ADD CONSTRAINT  $name UNIQUE (" . implode( ',', $columns ) . ")";

		$this->adapter->exec( $sql );
	}

	/**
	 * Add the constraints for a specific database driver: Oracle.
	 *
	 * @todo Too many arguments; find a way to solve this in a neater way.
	 *
	 * @param string $table     table
	 * @param string $table1    table1
	 * @param string $table2    table2
	 * @param string $property1 property1
	 * @param string $property2 property2
	 *
	 * @return boolean $success whether the constraint has been applied
	 */
	protected function constrain( $table, $table1, $table2, $property1, $property2 )
	{
		try {
			$table     = strtoupper( $this->esc( $table ) );

			$table1    = strtoupper( $this->esc( $table1 ) );
			$table2    = strtoupper( $this->esc( $table2 ) );

			$property1 = strtoupper( $this->esc( $property1 ) );
			$property2 = strtoupper( $this->esc( $property2 ) );

			$fks       = $this->adapter->getCell( "
				SELECT COUNT(*)
		        FROM ALL_CONS_COLUMNS A JOIN ALL_CONSTRAINTS C  ON A.CONSTRAINT_NAME = C.CONSTRAINT_NAME
			    WHERE LOWER(C.TABLE_NAME) = ? AND C.CONSTRAINT_TYPE = 'R'
					  ", array( $table ) );

			// Already foreign keys added in this association table
			if ( $fks > 0 ) return FALSE;

			$columns = $this->getColumns( $table );

			if ( $this->code( $columns[$property1] ) !== RedBean_QueryWriter_Oracle::C_DATATYPE_UINT32 ) {
				$this->widenColumn( $table, $property1, RedBean_QueryWriter_Oracle::C_DATATYPE_UINT32 );
			}

			if ( $this->code( $columns[$property2] ) !== RedBean_QueryWriter_Oracle::C_DATATYPE_UINT32 ) {
				$this->widenColumn( $table, $property2, RedBean_QueryWriter_Oracle::C_DATATYPE_UINT32 );
			}

			$sql = "
				ALTER TABLE " . $table . "
				ADD FOREIGN KEY($property1) references $table1(id) ON DELETE CASCADE";

			$this->adapter->exec( $sql );

			$sql = "
				ALTER TABLE " . $table . "
				ADD FOREIGN KEY($property2) references $table2(id) ON DELETE CASCADE";

			$this->adapter->exec( $sql );

			return TRUE;
		} catch ( Exception $e ) {
			return FALSE;
		}
	}

	/**
	 * Counts rows in a table.
	 * Overridden because OCI want upper cased table name.
	 *
	 * @param string $beanType type of bean you want to count
	 * @param string $assSQL   additional SQL snippet for filtering
	 * @param array  $params   parameters to bind to SQL snippet
	 *
	 * @return integer $numRowsFound
	 */
	public function count( $beanType, $addSQL = '', $params = array() )
	{
		return parent::count( strtoupper( $beanType ), $addSQL, $params );
	}

	/**
	 * Returns all tables in the database.
	 *
	 * @return array $tables tables
	 */
	public function getTables()
	{
		return $this->adapter->getCol( 'SELECT LOWER(table_name) FROM user_tables' );
	}

	/**
	 * This method should add an index to a type and field with name
	 * $name.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param string $type   type to add index to
	 * @param string $name   name of the new index
	 * @param string $column field to index
	 *
	 * @return void
	 */
	public function addIndex( $type, $name, $column )
	{
		$table  = $type;
		$table  = strtoupper( $this->esc( $table ) );

		$name   = $this->limitOracleIdentifierLength( preg_replace( '/\W/', '', $name ) );
		$column = strtoupper( $this->esc( $column ) );

		try {
			$this->adapter->exec( "CREATE INDEX $name ON $table ($column) " );
		} catch ( Exception $e ) {
		}
	}

	/**
	 * Creates an empty, column-less table for a bean based on it's type.
	 * This function creates an empty table for a bean. It uses the
	 * safeTable() function to convert the type name to a table name.
	 * For oracle we have to create a sequence and a trigger to get
	 * the autoincrement feature.
	 *
	 * @param string $table type of bean you want to create a table for
	 *
	 * @throws Exception
	 *
	 * @return void
	 */
	public function createTable( $table )
	{
		if ( strtolower( $table ) != $table ) {
			throw new Exception( $table . ' is not lowercase. With ORACLE you MUST only use lowercase table in PHP, sorry!' );
		}

		$table_with_quotes         = strtoupper( $this->esc( $table ) );
		$safe_table_without_quotes = strtoupper( $this->esc( $table, TRUE ) );

		$sql = "CREATE TABLE $table_with_quotes(
                ID NUMBER(11) NOT NULL,
                CONSTRAINT " . $safe_table_without_quotes . "_PK PRIMARY KEY (ID)
                )";

		$this->adapter->exec( $sql );

		$sql =
			"CREATE SEQUENCE " . $safe_table_without_quotes . "_SEQ
            START WITH 1
            INCREMENT BY 1
            NOCACHE";

		$this->adapter->exec( $sql );

		$sql =
			"CREATE OR REPLACE TRIGGER " . $safe_table_without_quotes . "_SEQ_TRI
            BEFORE INSERT ON $table_with_quotes
            FOR EACH ROW
            BEGIN
            SELECT " . $safe_table_without_quotes . "_SEQ.NEXTVAL
            INTO   :NEW.ID
            FROM   DUAL;
            END " . $safe_table_without_quotes . "_SEQ_TRI;";

		$this->adapter->exec( $sql );
	}

	/**
	 * This method adds a column to a table.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param string  $type   name of the table
	 * @param string  $column name of the column
	 * @param integer $field  data type for field
	 *
	 * @throws Exception
	 *
	 * @return void
	 */
	public function addColumn( $type, $column, $field )
	{
		$columnTested = preg_replace( '/^((own)|(shared))./', '', $column );

		if ( strtolower( $columnTested ) != $columnTested ) {
			throw new Exception( $column . ' is not lowercase. With ORACLE you MUST only use lowercase properties in PHP, sorry!' );
		}

		parent::addColumn( strtoupper( $type ), strtoupper( $column ), $field );
	}

	/**
	 * Inserts a record into the database using a series of insert columns
	 * and corresponding insertvalues. Returns the insert id.
	 *
	 * @param string $table              table to perform query on
	 * @param array  $insertcolumns      columns to be inserted
	 * @param array  $insertvalues       values to be inserted
	 *
	 * @return integer $insertid      insert id from driver, new record id
	 */
	protected function insertRecord( $table, $insertcolumns, $insertvalues )
	{
		foreach ( $insertcolumns as &$col ) {
			$col = strtoupper( $col );
		}

		return parent::insertRecord( strtoupper( $table ), $insertcolumns, $insertvalues );
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
	 * Returns an array containing the column names of the specified table.
	 *
	 * @param string $table table
	 *
	 * @return array $columns columns
	 */
	public function getColumns( $table )
	{
		$table      = $this->esc( $table, TRUE );
		$columnsRaw = $this->adapter->get( "SELECT LOWER(COLUMN_NAME) COLUMN_NAME, DATA_TYPE, DATA_LENGTH, DATA_PRECISION FROM ALL_TAB_COLUMNS WHERE TABLE_NAME = UPPER('$table')" );

		$columns = array();
		foreach ( $columnsRaw as $r ) {
			$field = $r['column_name'];

			switch ( $r['data_type'] ) {
				case 'NUMBER':
					$columns[$field] = $r['data_type'] . '(' . ( (int) $r['data_precision'] ) . ',0)';
					break;
				case 'NVARCHAR2':
					$columns[$field] = $r['data_type'] . '(' . ( $r['data_length'] / 2 ) . ')';
					break;
				case 'FLOAT':
				case 'TIMESTAMP(6)':
				case 'CLOB':
				case 'DATE':
					$columns[$field] = $r['data_type'];
					break;
			}
		}

		return $columns;
	}

	/**
	 * Returns the Type Code for a Column Description.
	 * Given an SQL column description this method will return the corresponding
	 * code for the writer. If the include specials flag is set it will also
	 * return codes for special columns. Otherwise special columns will be identified
	 * as specified columns.
	 *
	 * @param string  $typedescription description
	 * @param boolean $includeSpecials whether you want to get codes for special columns as well
	 *
	 * @return integer $typecode code
	 */
	public function code( $typedescription, $includeSpecials = FALSE )
	{
		$r = ( ( isset( $this->sqltype_typeno[$typedescription] ) ) ? $this->sqltype_typeno[$typedescription] : self::C_DATATYPE_SPECIFIED );

		if ( $includeSpecials ) return $r;

		if ( $r > self::C_DATATYPE_SPECIFIED ) return self::C_DATATYPE_SPECIFIED;

		return $r;
	}

	/**
	 * This method upgrades the column to the specified data type.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param string  $type       type / table that needs to be adjusted
	 * @param string  $column     column that needs to be altered
	 * @param integer $datatype   target data type
	 *
	 * @return void
	 */
	public function widenColumn( $type, $column, $datatype )
	{
		$table   = $type;
		$type    = $datatype;

		$table   = strtoupper( $this->esc( $table ) );
		$column  = strtoupper( $this->esc( $column ) );

		$newtype = array_key_exists( $type, $this->typeno_sqltype ) ? $this->typeno_sqltype[$type] : '';

		$addTempColumn = "ALTER TABLE $table ADD (HOPEFULLYNOTEXIST $newtype)";

		$this->adapter->exec( $addTempColumn );

		$updateTempColumn = "UPDATE $table SET HOPEFULLYNOTEXIST = $column";

		$this->adapter->exec( $updateTempColumn );

		$this->adapter->exec( "ALTER TABLE $table DROP COLUMN $column" );

		$this->adapter->exec( "ALTER TABLE $table RENAME COLUMN HOPEFULLYNOTEXIST TO $column" );
	}

	/**
	 * Tests whether a given SQL state is in the list of states.
	 *
	 * @param string $state code
	 * @param array  $list  array of sql states
	 *
	 * @return boolean $yesno occurs in list
	 */
	public function sqlStateIn( $state, $list )
	{
		$stateMap = array(
			RedBean_Driver_OCI::OCI_NO_SUCH_TABLE                  => RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_TABLE,
			RedBean_Driver_OCI::OCI_NO_SUCH_COLUMN                 => RedBean_QueryWriter::C_SQLSTATE_NO_SUCH_COLUMN,
			RedBean_Driver_OCI::OCI_INTEGRITY_CONSTRAINT_VIOLATION => RedBean_QueryWriter::C_SQLSTATE_INTEGRITY_CONSTRAINT_VIOLATION
		);

		return in_array( ( isset( $stateMap[$state] ) ? $stateMap[$state] : '0' ), $list );
	}

	/**
	 * @todo Add documentation
	 *
	 * @param integer $id
	 *
	 * @return mixed $returnValue
	 */
	private function limitOracleIdentifierLength( $id )
	{
		return substr( $id, 0, 30 );
	}

	/**
	 * This method updates (or inserts) a record, it takes
	 * a table name, a list of update values ( $field => $value ) and an
	 * primary key ID (optional). If no primary key ID is provided, an
	 * INSERT will take place.
	 * Returns the new ID.
	 * This methods accepts a type and infers the corresponding table name.
	 *
	 * @param string  $type          name of the table to update
	 * @param array   $updatevalues  list of update values
	 * @param integer $id            optional primary key ID value
	 *
	 * @return integer $id the primary key ID value of the new record
	 */
	public function updateRecord( $type, $updatevalues, $id = NULL )
	{
		foreach ( $updatevalues as &$updatevalue ) {
			$updatevalue['property'] = strtoupper( $updatevalue['property'] );
		}

		return parent::updateRecord( strtoupper( $type ), $updatevalues, $id );
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
	public function addFK( $type, $targetType, $field, $targetField, $isDependent = FALSE )
	{
		$table           = strtoupper( $this->esc( $type ) );
		$tableNoQ        = strtoupper( $this->esc( $type, TRUE ) );

		$targetTable     = strtoupper( $this->esc( $targetType ) );

		$column          = strtoupper( $this->esc( $field ) );
		$columnNoQ       = strtoupper( $this->esc( $field, TRUE ) );

		$targetColumn    = strtoupper( $this->esc( $targetField ) );
		$targetColumnNoQ = strtoupper( $this->esc( $targetField, TRUE ) );

		$fkName          = 'FK_' . ( $isDependent ? 'C_' : '' ) . $tableNoQ . '_' . $columnNoQ . '_' . $targetColumnNoQ;
		$fkName          = $this->limitOracleIdentifierLength( $fkName );

		$cfks = $this->adapter->getCell( "
			SELECT A.CONSTRAINT_NAME
		    FROM ALL_CONS_COLUMNS A JOIN ALL_CONSTRAINTS C  ON A.CONSTRAINT_NAME = C.CONSTRAINT_NAME
			WHERE C.TABLE_NAME = '$tableNoQ' AND C.CONSTRAINT_TYPE = 'R'	AND COLUMN_NAME='$columnNoQ'" );

		$flagAddKey = FALSE;
		try {
			// No keys
			if ( !$cfks ) {
				$flagAddKey = TRUE; //go get a new key
			}

			// Has fk, but different setting, --remove
			if ( $cfks && $cfks != $fkName ) {
				$this->adapter->exec( "ALTER TABLE $table DROP CONSTRAINT $cfks " );
				$flagAddKey = TRUE; //go get a new key.
			}

			if ( $flagAddKey ) {
				$sql = "ALTER TABLE  $table
				ADD CONSTRAINT $fkName FOREIGN KEY (  $column ) REFERENCES  $targetTable (
				$targetColumn) ON DELETE " . ( $isDependent ? 'CASCADE' : 'SET NULL' );

				$this->adapter->exec( $sql );
			}
		} catch ( Exception $e ) {
			// Failure of fk-constraints is not a problem
		}
	}

	/**
	 * @see RedBean_QueryWriter::queryRecord
	 */
	public function queryRecord( $type, $conditions = array(), $addSql = NULL, $bindings = array() )
	{
		return parent::queryRecord( $type, $this->filterConditions( $conditions ), $addSql, $bindings );
	}

	/**
	 * @see RedBean_QueryWriter::deleteRecord
	 */
	public function deleteRecord( $type, $conditions = array(), $addSql = NULL, $bindings = array() )
	{
		parent::deleteRecord( $type, $this->filterConditions( $conditions ), $addSql, $bindings );
	}

	/**
	 * Uppercase the conditions.
	 *
	 * @param array $conditions conditions
	 *
	 * @return array
	 */
	private function filterConditions( $conditions )
	{
		$upperCaseConditions = array();
		foreach ( $conditions as $column => $value ) {
			$upperCaseConditions[strtoupper( $column )] = $value;
		}

		return $upperCaseConditions;
	}

	/**
	 * Returns the Column Type Code (integer) that corresponds
	 * to the given value type. This method is used to determine the minimum
	 * column type required to represent the given value.
	 *
	 * @param string $value value
	 *
	 * @return integer $type type
	 */
	public function scanType( $value, $flagSpecial = FALSE )
	{
		$this->svalue = $value;

		if ( is_null( $value ) ) {
			return RedBean_QueryWriter_Oracle::C_DATATYPE_BOOL;
		}

		if ( $flagSpecial ) {
			if ( preg_match( '/^\d{4}\-\d\d-\d\d(\s\d\d:\d\d(:\d\d)?)?$/', $value ) ) {
				return RedBean_QueryWriter_Oracle::C_DATATYPE_SPECIAL_DATE;
			}
			if ( preg_match( '/^\d{4}\-\d\d-\d\d\s\d\d:\d\d:\d\d.\d\d$/', $value ) ) {
				return RedBean_QueryWriter_Oracle::C_DATATYPE_SPECIAL_TIMESTAMP;
			}
		}

		$value = strval( $value );

		if ( !$this->startsWithZeros( $value ) ) {
			if ( $value == '1' || $value == '' ) {
				return RedBean_QueryWriter_Oracle::C_DATATYPE_BOOL;
			}

			if ( is_numeric( $value ) && ( floor( $value ) == $value ) && $value >= 0 && $value <= 255 ) {
				return RedBean_QueryWriter_Oracle::C_DATATYPE_UINT8;
			}

			if ( is_numeric( $value ) && ( floor( $value ) == $value ) && $value >= 0 && $value <= 4294967295 ) {
				return RedBean_QueryWriter_Oracle::C_DATATYPE_UINT32;
			}

			if ( is_numeric( $value ) ) {
				return RedBean_QueryWriter_Oracle::C_DATATYPE_DOUBLE;
			}
		}

		if ( strlen( $value ) <= 255 ) {
			return RedBean_QueryWriter_Oracle::C_DATATYPE_TEXT8;
		}

		if ( strlen( $value ) <= 2000 ) {
			return RedBean_QueryWriter_Oracle::C_DATATYPE_TEXT16;
		}

		return RedBean_QueryWriter_Oracle::C_DATATYPE_TEXT32;
	}

	/**
	 * Drops all tables in database
	 */
	public function wipeAll()
	{
		$this->adapter->exec( "
			BEGIN

			--Bye Sequences!
			FOR i IN (SELECT us.sequence_name
						FROM USER_SEQUENCES us) LOOP
				EXECUTE IMMEDIATE 'drop sequence \"'|| i.sequence_name ||'\"';
			END LOOP;

			--Bye Tables!
			FOR i IN (SELECT ut.table_name
						FROM USER_TABLES ut) LOOP
				EXECUTE IMMEDIATE 'drop table \"'|| i.table_name ||'\" CASCADE CONSTRAINTS ';
			END LOOP;

			END;" );
	}
}
