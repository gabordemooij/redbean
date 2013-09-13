<?php
/**
 * OCI Driver
 *
 * @file                   RedBean/Driver/OCI.php
 * @desc                   OCI Driver for RedBeanPHP
 * @author                 Stephane Gerber
 * @license                BSD/GPLv2
 *
 * The OCI driver is required to facilitate a connection to
 * an Oracle database.
 *
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_Driver_OCI implements RedBean_Driver
{
	/**
	 * @var string
	 */
	private $dsn;
	/**
	 * @var RedBean_Driver_OCI
	 */
	private static $instance;
	/**
	 * @var boolean
	 */
	private $debug = FALSE;
	/**
	 * @var RedBean_Logger
	 */
	protected $logger = NULL;
	/**
	 * @var integer
	 */
	private $affected_rows;
	/**
	 * @var mixed
	 */
	private $rs;
	/**
	 * @var boolean
	 */
	private $autocommit = TRUE;
	/**
	 * @var mixed
	 */
	private $statement;
	/**
	 * @var mixed
	 */
	private $lastInsertedId;
	/**
	 * Whether we are currently connected or not.
	 * This flag is being used to delay the connection until necessary.
	 * Delaying connections is a good practice to speed up scripts that
	 * don't need database connectivity but for some reason want to
	 * init RedbeanPHP.
	 *
	 * @var boolean
	 */
	protected $isConnected = FALSE;
	/**
	 * OCI NLS date format.
	 *
	 * @var string
	 */
	private $nlsDateFormat = 'YYYY-MM-DD HH24:MI:SS';
	/**
	 * OCI NLS date format.
	 *
	 * @var string
	 */
	private $nlsTimeStampFormat = 'YYYY-MM-DD HH24:MI:SS.FF';
	/**
	 * OCI constants
	 */
	const OCI_NO_SUCH_TABLE                  = '942';
	const OCI_NO_SUCH_COLUMN                 = '904';
	const OCI_INTEGRITY_CONSTRAINT_VIOLATION = '2292';
	const OCI_UNIQUE_CONSTRAINT_VIOLATION    = '1';

	/**
	 * Returns an instance of the OCI Driver.
	 *
	 * @param $dsn
	 * @param $user
	 * @param $pass
	 * @param $dbname
	 *
	 * @return RedBean_Driver_OCI
	 */
	public static function getInstance( $dsn, $user, $pass )
	{
		if ( is_null( self::$instance ) ) {
			self::$instance = new RedBean_Driver_OCI( $dsn, $user, $pass );
		}

		return self::$instance;
	}

	/**
	 * Constructor. You may either specify dsn, user and password or
	 * just give an existing OCI connection.
	 * Examples:
	 *    $driver = new RedBean_Driver_OCI($dsn, $user, $password);
	 *    $driver = new RedBean_Driver_OCI($existingConnection);
	 *
	 * @param string|resource $dsn     database connection string
	 * @param string          $user    optional
	 * @param string          $pass    optional
	 *
	 * @return void
	 */
	public function __construct( $dsn, $user = NULL, $pass = NULL )
	{
		if ( is_resource($dsn) ) {
			$this->connection  = $dsn;
			$this->isConnected = TRUE;

			// make sure that the dsn at least contains the type
			$this->dsn         = $this->getDatabaseType();
		} else {
			$this->dsn         = substr( $dsn, 7 ); // remove 'oracle:'
			$this->connectInfo = array( 'pass' => $pass, 'user' => $user );
		}
	}

	/**
	 * @todo add Documentation
	 *
	 * @return string
	 */
	public function getNlsDateFormat()
	{
		return $this->nlsDateFormat;
	}

	/**
	 * @todo add Documentation
	 *
	 * @return void
	 */
	public function setNlsDateFormat( $nlsDateFormat )
	{
		$this->nlsDateFormat = $nlsDateFormat;
	}

	/**
	 * @todo add Documentation
	 *
	 * @return string
	 */
	public function getNlsTimestampFormat()
	{
		return $this->nlsTimeStampFormat;
	}

	/**
	 * @todo add Documentation
	 *
	 * @return void
	 */
	public function setNlsTimestampFormat( $nlsTimestampFormat )
	{
		$this->nlsTimeStampFormat = $nlsTimestampFormat;
	}

	/**
	 * Gets RedBean_Logger object.
	 *
	 * @return RedBean_Logger
	 */
	public function setLogger( RedBean_Logger $logger )
	{
		$this->logger = $logger;
	}

	/**
	 * Gets RedBean_Logger object.
	 *
	 * @return RedBean_Logger
	 */
	public function getLogger()
	{
		return $this->logger;
	}

	/**
	 * Toggles auto-commit.
	 *
	 * @param boolean $toggle
	 */
	public function setAutoCommit( $toggle )
	{
		$this->autocommit = (bool) $toggle;
	}

	/**
	 * Establishes a connection to the database using PHP PDO
	 * functionality. If a connection has already been established this
	 * method will simply return directly. This method also turns on
	 * UTF8 for the database and PDO-ERRMODE-EXCEPTION as well as
	 * PDO-FETCH-ASSOC.
	 *
	 * @return void
	 */
	public function connect()
	{
		if ( $this->isConnected )
			return;
		$user = $this->connectInfo['user'];
		$pass = $this->connectInfo['pass'];

		$this->connection = oci_connect( $user, $pass, $this->dsn, 'utf8' );
		if ( !$this->connection ) {
			$e = oci_error();

			print_r( $e );

			$this->isConnected = FALSE;
		} else {
			$s = oci_parse( $this->connection, "alter session set nls_date_format = '$this->nlsDateFormat'" );

			oci_execute( $s );

			$s = oci_parse( $this->connection, "alter session set nls_timestamp_format = '$this->nlsTimeStampFormat'" );

			oci_execute( $s );

			$this->isConnected = TRUE;
		}
	}

	/**
	 * Runs a query. Internal function, available for subclasses. This method
	 * runs the actual SQL query and binds a list of parameters to the query.
	 * slots. The result of the query will be stored in the protected property
	 * $rs (always array). The number of rows affected (result of rowcount, if supported by database)
	 * is stored in protected property $affected_rows. If the debug flag is set
	 * this function will send debugging output to screen buffer.
	 *
	 * @throws RedBean_Exception_SQL
	 *
	 * @param string $sql     the SQL string to be send to database server
	 * @param array  $aValues the values that need to get bound to the query slots
	 */
	protected function runQuery( $sql, $aValues )
	{
		$this->connect();

		if ( $this->debug && $this->logger ) {
			$this->logger->log( $sql, $aValues );
		}

		try {
			$this->doBinding( $sql, $aValues );
			$this->affected_rows = oci_num_rows( $this->statement );

			if ( oci_num_fields( $this->statement ) ) {
				$rows = array();

				oci_fetch_all( $this->statement, $rows, 0, -1, OCI_FETCHSTATEMENT_BY_ROW );

				// This rewrite all the php properties in lowercase
				foreach ( $rows as $key => $row ) {
					foreach ( $row as $field => $value ) {
						unset( $rows[$key][$field] );

						$new_key = strtolower( $field );

						$rows[$key][$new_key] = $value;
					}
				}

				$this->rs = $rows;

				if ( $this->debug && $this->logger )
					$this->logger->log( 'resultset: ' . count( $this->rs ) . ' rows' );
			} else {
				$this->rs = array();
			}
		} catch ( PDOException $pdoException ) {
			// Unfortunately the code field is supposed to be int by default (php)
			// So we need a property to convey the SQL State code.
			$transformedException = new RedBean_Exception_SQL( $pdoException->getMessage(), 0 );
			$transformedException->setSQLState( $pdoException->getCode() );

			throw $transformedException;
		}
	}

	/**
	 * Runs a query and fetches results as a multi dimensional array.
	 *
	 * @param  string $sql SQL to be executed
	 *
	 * @return array $results result
	 */
	public function GetAll( $sql, $aValues = array() )
	{
		$this->runQuery( $sql, $aValues );

		return $this->rs;
	}

	/**
	 * Executes SQL code and allows key-value binding.
	 * This function allows you to provide an array with values to bind
	 * to query parameters. For instance you can bind values to question
	 * marks in the query. Each value in the array corresponds to the
	 * question mark in the query that matches the position of the value in the
	 * array. You can also bind values using explicit keys, for instance
	 * array(":key"=>123) will bind the integer 123 to the key :key in the
	 * SQL. This method has no return value.
	 *
	 * @param string $sql      SQL Code to execute
	 * @param array  $aValues  Values to bind to SQL query
	 *
	 * @return array Affected Rows
	 */
	public function Execute( $sql, $aValues = array() )
	{
		$this->runQuery( $sql, $aValues );

		return $this->affected_rows;
	}

	/**
	 * Runs a query and fetches results as a column.
	 *
	 * @param  string $sql SQL Code to execute
	 *
	 * @return array $results Resultset
	 */
	public function GetCol( $sql, $aValues = array() )
	{
		$rows = $this->GetAll( $sql, $aValues );

		$cols = array();
		if ( $rows && is_array( $rows ) && count( $rows ) > 0 ) {
			foreach ( $rows as $row ) {
				$cols[] = array_shift( $row );
			}
		}

		return $cols;
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see RedBean/RedBean_Driver#GetCell()
	 */
	public function GetCell( $sql, $aValues = array() )
	{
		$arr = $this->GetAll( $sql, $aValues );

		$row1 = array_shift( $arr );
		$col1 = array_shift( $row1 );

		return $col1;
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see RedBean/RedBean_Driver#GetRow()
	 */
	public function GetRow( $sql, $aValues = array() )
	{
		$arr = $this->GetAll( $sql, $aValues );

		return array_shift( $arr );
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see RedBean/RedBean_Driver#ErrorNo()
	 */
	public function ErrorNo()
	{
		$error = oci_error( $this->statement );

		if ( is_array( $error ) ) {
			return $error['code'];
		} else {
			return NULL;
		}
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see RedBean/RedBean_Driver#Errormsg()
	 */
	public function Errormsg()
	{
		$error = oci_error( $this->statement );

		if ( is_array( $error ) ) {
			return $error['message'];
		} else {
			return NULL;
		}
	}

	/**
	 * Use oci binding to execute the binding and execute the query
	 *
	 *
	 * @param string $sql      SQL Code to execute
	 * @param array  $aValues  Values to bind to SQL query
	 *
	 * @throws RedBean_Exception_SQL
	 *
	 * @return void
	 */
	private function doBinding( $sql, $aValues = array() )
	{
		foreach ( $aValues as $key => $value ) {
			$sql = preg_replace( '/\?/', ' :SLOT' . $key . ' ', $sql, 1 );
		}

		//if we insert we fetch the inserted id
		$isInsert = preg_match( '/^insert/i', $sql );

		if ( $isInsert ) {
			$sql .= ' RETURN ID INTO :ID';
		}

		$this->statement = oci_parse( $this->connection, $sql );

		foreach ( $aValues as $key => $value ) {
			if ( !is_int( $key ) ) {
				$keyv = str_replace( ':', '', $key );

				${'SLOT' . $keyv} = $value;

				oci_bind_by_name( $this->statement, $key, ${'SLOT' . $keyv} );
			} else {
				${'SLOT' . $key} = $value;

				oci_bind_by_name( $this->statement, ':SLOT' . $key, ${'SLOT' . $key} );
			}
		}

		if ( $isInsert ) {
			oci_bind_by_name( $this->statement, ':ID', $this->lastInsertedId, 20, SQLT_INT );
		}

		if ( $this->debug ) {
			if ( !$this->autocommit ) {
				$result = oci_execute( $this->statement, OCI_NO_AUTO_COMMIT ); // data not committed
			} else {
				$result = oci_execute( $this->statement );
			}
		} else { // no supression of warning
			if ( !$this->autocommit ) {
				$result = @oci_execute( $this->statement, OCI_NO_AUTO_COMMIT ); // data not committed
			} else {
				$result = @oci_execute( $this->statement );
			}
		}

		if ( !$result ) {
			$error = oci_error( $this->statement );

			$x = new RedBean_Exception_SQL( $error['message'] . ':' . $error['sqltext'], 0 );
			$x->setSQLState( $this->mergeErrors( $error['code'] ) );
			throw $x;
		}
	}

	/**
	 * Returns the underlying PHP OCI instance.
	 *
	 * @return resource
	 */
	public function getOCI()
	{
		$this->connect();

		return $this->connection;
	}

	// This function is used to be compatible with the Redbean actual behaviour. Oracle makes a difference between the
	// two errors belows, Redbean doesn't
	private function mergeErrors( $code )
	{
		if ( $code == self::OCI_UNIQUE_CONSTRAINT_VIOLATION )
			return self::OCI_INTEGRITY_CONSTRAINT_VIOLATION;
		else
			return $code;
	}

	/**
	 * Returns the latest insert ID if driver does support this
	 * feature.
	 *
	 * @return integer $id primary key ID
	 */
	public function GetInsertID()
	{
		return $this->lastInsertedId;
	}

	/**
	 * Returns the number of rows affected by the most recent query
	 * if the currently selected PDO driver supports this feature.
	 *
	 * @return integer $numOfRows number of rows affected
	 */
	public function Affected_Rows()
	{
		$this->connect();

		return (int) $this->affected_rows;
	}

	/**
	 * Toggles debug mode. In debug mode the driver will print all
	 * SQL to the screen together with some information about the
	 * results. All SQL code that passes through the driver will be
	 * passes on to the screen for inspection.
	 * This method has no return value.
	 *
	 * Additionally you can inject RedBean_Logger implementation
	 * where you can define your own log() method
	 *
	 * @param boolean        $trueFalse turn on/off
	 * @param RedBean_Logger $logger
	 *
	 * @return void
	 */
	public function setDebugMode( $tf, $logger = NULL )
	{
		$this->connect();
		$this->debug = (bool) $tf;
		if ( $this->debug and !$logger ) $logger = new RedBean_Logger_Default();
		$this->setLogger( $logger );
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see RedBean/RedBean_Driver#GetRaw()
	 */
	public function GetRaw()
	{
		return NULL;
	}

	/**
	 * Returns TRUE if the current PDO instance is connected.
	 *
	 * @return boolean $yesNO
	 */
	public function isConnected()
	{
		if ( !$this->isConnected && !$this->connection ) return FALSE;

		return TRUE;
	}

	/**
	 * Closes database connection by destructing PDO.
	 */
	public function close()
	{
		$this->connection  = NULL;
		$this->isConnected = FALSE;
	}

	/**
	 * Starts a transaction.
	 */
	public function StartTrans()
	{
		$this->autocommit = FALSE;
	}

	/**
	 * Commits a transaction.
	 */
	public function CommitTrans()
	{
		oci_commit( $this->connection );
	}

	/**
	 * Rolls back a transaction.
	 */
	public function FailTrans()
	{
		oci_rollback( $this->connection );
	}

	/**
	 * Returns the name of the database type/brand: i.e. mysql, db2 etc.
	 *
	 * @return string $typeName
	 */
	public function getDatabaseType()
	{
		return "OCI";
	}

	/**
	 * Returns the version number of the database.
	 *
	 * @return mixed $version
	 */
	public function getDatabaseVersion()
	{
		$this->connect();

		$output = array();

		$s = oci_parse( $this->connection, 'select * from v$version where banner like ' . "'Oracle%'" );

		oci_execute( $s );

		oci_fetch_all( $s, $output );

		return $output['BANNER'][0];
	}
}
