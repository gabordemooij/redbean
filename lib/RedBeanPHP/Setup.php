<?php
namespace RedBeanPHP;
use \RedBeanPHP\ToolBox as ToolBox;
use \RedBeanPHP\Driver\RPDO as RPDO;
use \RedBeanPHP\Driver\OCI as OCI;
use \RedBeanPHP\Adapter\DBAdapter as DBAdapter;
use \RedBeanPHP\QueryWriter\PostgreSQL as PostgreSQL;
use \RedBeanPHP\QueryWriter\SQLiteT as SQLiteT;
use \RedBeanPHP\QueryWriter\CUBRID as CUBRID;
use \RedBeanPHP\QueryWriter\Oracle as Oracle;
use \RedBeanPHP\QueryWriter\MySQL as MySQL;
use \RedBeanPHP\OODB as OODB;
/**
 * RedBean Setup
 * Helper class to quickly setup RedBean for you.
 *
 * @file    RedBean/Setup.php
 * @desc    Helper class to quickly setup RedBean for you
 * @author  Gabor de Mooij and the RedBeanPHP community
 * @license BSD/GPLv2
 *
 * copyright (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class Setup
{

	/**
	 * This method checks the DSN string.
	 * Checks the validity of the DSN string.
	 * If the DSN contains an invalid database identifier this method
	 * will trigger an error.
	 *
	 * @param string $dsn
	 *
	 * @return boolean
	 */
	private static function checkDSN( $dsn )
	{
		if ( !preg_match( '/^(mysql|sqlite|pgsql|cubrid|oracle):/', strtolower( trim( $dsn ) ) ) ) {
			trigger_error( 'Unsupported DSN' );
		}

		return TRUE;
	}

	/**
	 * Initializes the database and prepares a toolbox.
	 * The kickstart method assembles a toolbox based on your DSN and
	 * credentials and returns it.
	 * The toolbox contains all the necessary core components for
	 * RedBeanPHP to start working with your database. Most RedBeanPHP
	 * components are stand-alone and require a toolbox to work.
	 *
	 * @param  string|PDO $dsn      Database Connection String (or \PDO instance)
	 * @param  string     $username Username for database
	 * @param  string     $password Password for database
	 * @param  boolean    $frozen   Start in frozen mode?
	 *
	 * @return ToolBox
	 */
	public static function kickstart( $dsn, $username = NULL, $password = NULL, $frozen = FALSE, $autoSetEncoding = TRUE )
	{
		if ( $dsn instanceof \PDO ) {
			$db  = new RPDO( $dsn );
			$dsn = $db->getDatabaseType();
		} else {
			self::checkDSN( $dsn );

			if ( strpos( $dsn, 'oracle' ) === 0 ) {
				$db = new OCI( $dsn, $username, $password);
			} else {
				$db = new RPDO( $dsn, $username, $password, $autoSetEncoding );
			}
		}

		$adapter = new DBAdapter( $db );

		if ( strpos( $dsn, 'pgsql' ) === 0 ) {
			$writer = new PostgreSQL( $adapter );
		} else if ( strpos( $dsn, 'sqlite' ) === 0 ) {
			$writer = new SQLiteT( $adapter );
		} else if ( strpos( $dsn, 'cubrid' ) === 0 ) {
			$writer = new CUBRID( $adapter );
		} else if ( strpos( $dsn, 'oracle' ) === 0 ) {
			$writer = new Oracle( $adapter );
		} else {
			$writer = new MySQL( $adapter );
		}

		$redbean = new OODB( $writer );

		if ( $frozen ) {
			$redbean->freeze( TRUE );
		}

		$toolbox = new ToolBox( $redbean, $adapter, $writer );

		return $toolbox;
	}
}
