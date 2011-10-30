<?php

/**
 * RedBean Setup
 * Helper class to quickly setup RedBean for you
 * @file 		RedBean/Setup.php
 * @description		Helper class to quickly setup RedBean for you
 * @author			Gabor de Mooij
 * @license			BSD
 *
 *
 * (c) G.J.G.T. (Gabor) de Mooij
 * This source file is subject to the BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedBean_Setup {

	/**
	 *
	 * @var array
	 * Keeps track of the observers
	 */
	private static $observers = array();


	/**
	 *
	 * @var RedBean_ToolBox $toolbox
	 */
	private static $toolbox = NULL;


	/**
	 * This method checks the DSN string. If the DSN string contains a
	 * database name that is not supported by RedBean yet then it will
	 * throw an exception RedBean_Exception_NotImplemented. In any other
	 * case this method will just return boolean TRUE.
	 * @throws RedBean_Exception_NotImplemented
	 * @param string $dsn
	 * @return boolean $true
	 */
	private static function checkDSN($dsn) {
		$dsn = trim($dsn);
		$dsn = strtolower($dsn);
		if (
		strpos($dsn, "mysql:")!==0
				  && strpos($dsn,"sqlite:")!==0
				  && strpos($dsn,"pgsql:")!==0
		) {

			trigger_error("
					Support for this DSN has not been implemented yet. \n
					Begin your DSN with: 'mysql:' or 'sqlite:'
				");
		}
		else {
			return true;
		}
	}


	/**
	 * Generic Kickstart method.
	 * This is the generic kickstarter. It will establish a database connection
	 * using the $dsn, the $username and the $password you provide.
	 * If $frozen is boolean TRUE it will start RedBean in frozen mode, meaning
	 * that the database cannot be altered. If RedBean is started in fluid mode
	 * it will adjust the schema of the database if it detects an
	 * incompatible bean.
	 * This method returns a RedBean_Toolbox $toolbox filled with a
	 * RedBean_Adapter, a RedBean_QueryWriter and most importantly a
	 * RedBean_OODB; the object database. To start storing beans in the database
	 * simply say: $redbean = $toolbox->getRedBean(); Now you have a reference
	 * to the RedBean object.
	 * Optionally instead of using $dsn you may use an existing PDO connection.
	 * Example: RedBean_Setup::kickstart($existingConnection, true);
	 *
	 * @param  string|PDO $dsn      Database Connection String
	 * @param  string     $username Username for database
	 * @param  string     $password Password for database
	 * @param  boolean    $frozen   Start in frozen mode?
	 *
	 * @return RedBean_ToolBox $toolbox
	 */
	public static function kickstart( $dsn, $username=NULL, $password=NULL, $frozen=false ) {
		if ($dsn instanceof PDO) {
			$pdo = new RedBean_Driver_PDO($dsn);
			$dsn = $pdo->getDatabaseType() ;
		}
		else {
			self::checkDSN($dsn);
			$pdo = new RedBean_Driver_PDO( $dsn,$username,$password );
		}

		$adapter = new RedBean_Adapter_DBAdapter( $pdo );

		if (strpos($dsn,"pgsql")===0) {
			$writer = new RedBean_QueryWriter_PostgreSQL( $adapter );
		}
		else if (strpos($dsn,"sqlite")===0) {
			$writer = new RedBean_QueryWriter_SQLiteT( $adapter );
		}
		else {
			$writer = new RedBean_QueryWriter_MySQL( $adapter );
		}

		$redbean = new RedBean_OODB( $writer );
		if ($frozen) $redbean->freeze(true);
		$toolbox = new RedBean_ToolBox( $redbean, $adapter, $writer );

		//deliver everything back in a neat toolbox
		self::$toolbox = $toolbox;
		return self::$toolbox;

	}



	/**
	 * During a kickstart method observers may be attached to the RedBean_OODB object.
	 * Setup keeps track of the observers that are connected to RedBean.
	 * Returns the observers that have been attached by Setup.
	 * @return array $observers
	 */
	public static function getAttachedObservers() {
		return self::$observers;
	}



}
