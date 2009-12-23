<?php 

/**
 * RedBean Setup
 * Helper class to quickly setup RedBean for you
 * @file 		RedBean/Setup.php
 * @description		Helper class to quickly setup RedBean for you
 * @author			Gabor de Mooij
 * @license			BSD
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
			if (strpos($dsn, "mysql:")!==0) {
				throw new RedBean_Exception_NotImplemented("
					Support for this DSN has not been implemented yet. \n
					Begin your DSN with: 'mysql:'
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
		 * @param  string $dsn
		 * @param  string $username
		 * @param  string $password
		 * @return RedBean_ToolBox $toolbox
		 */
        public static function kickstart( $dsn, $username, $password, $frozen=false ) {

			self::checkDSN($dsn);
            $pdo = new RedBean_Driver_PDO( $dsn,$username,$password );
            $adapter = new RedBean_Adapter_DBAdapter( $pdo );

			$writer = new RedBean_QueryWriter_MySQL( $adapter, $frozen );
			
			$redbean = new RedBean_OODB( $writer );
			$toolbox = new RedBean_ToolBox( $redbean, $adapter, $writer );
            
            //deliver everything back in a neat toolbox
			self::$toolbox = $toolbox;
            return self::$toolbox;

        }

		/**
		 * Kickstart for development phase.
		 * Use this method to quickly setup RedBean for use during development phase.
		 * This Kickstart establishes a database connection
		 * using the $dsn, the $username and the $password you provide.
		 * It will start RedBean in fluid mode; meaning the database will
		 * be altered if required to store your beans.
		 * This method returns a RedBean_Toolbox $toolbox filled with a
		 * RedBean_Adapter, a RedBean_QueryWriter and most importantly a
		 * RedBean_OODB; the object database. To start storing beans in the database
		 * simply say: $redbean = $toolbox->getRedBean(); Now you have a reference
		 * to the RedBean object.
		 * @param  string $dsn
		 * @param  string $username
		 * @param  string $password
		 * @return RedBean_ToolBox $toolbox
		 */
		public static function kickstartDev( $dsn, $username="root", $password="" ) {
			$toolbox = self::kickstart($dsn, $username, $password);
			return $toolbox;
		}

		/**
		 * Kickstart for development phase (strict mode).
		 * Use this method to quickly setup RedBean for use during development phase.
		 * This Kickstart establishes a database connection
		 * using the $dsn, the $username and the $password you provide.
		 * It will start RedBean in fluid mode; meaning the database will
		 * be altered if required to store your beans.
		 * This method returns a RedBean_Toolbox $toolbox filled with a
		 * RedBean_Adapter, a RedBean_QueryWriter and most importantly a
		 * RedBean_OODB; the object database. To start storing beans in the database
		 * simply say: $redbean = $toolbox->getRedBean(); Now you have a reference
		 * to the RedBean object.
		 * @param  string $dsn
		 * @param  string $username
		 * @param  string $password
		 * @return RedBean_ToolBox $toolbox
		 */
		public static function kickstartDevS( $dsn, $username="root", $password="" ) {
			$frozen = false;
			self::checkDSN($dsn);
            $pdo = new RedBean_Driver_PDO( $dsn,$username,$password );
            $adapter = new RedBean_Adapter_DBAdapter( $pdo );
            $writer = new RedBean_QueryWriter_MySQLS( $adapter, $frozen );
            $redbean = new RedBean_OODB( $writer );
			$toolbox = new RedBean_ToolBox( $redbean, $adapter, $writer );
            //deliver everything back in a neat toolbox
			self::$toolbox = $toolbox;
            return self::$toolbox;
		}


		/**
		 * Almost the same as Dev, but adds the journaling plugin by default for you.
		 * This Kickstart establishes a database connection
		 * using the $dsn, the $username and the $password you provide.
		 * The Journaling plugin detects Race Conditions, for more information please
		 * consult the RedBean_Plugin_ChangeLogger Documentation.
		 * This method returns a RedBean_Toolbox $toolbox filled with a
		 * RedBean_Adapter, a RedBean_QueryWriter and most importantly a
		 * RedBean_OODB; the object database. To start storing beans in the database
		 * simply say: $redbean = $toolbox->getRedBean(); Now you have a reference
		 * to the RedBean object.
		 * @param  string $dsn
		 * @param  string $username
		 * @param  string $password
		 * @return RedBean_ToolBox $toolbox
		 */
		public static function KickStartDevWithJournal($dsn, $username="root", $password="") {
			$toolbox = self::kickstart($dsn, $username, $password);
			$redbean = $toolbox->getRedBean();
			$logger = new RedBean_Plugin_ChangeLogger( $toolbox );
			self::$observers["logger"] = $logger;
			$redbean->addEventListener( "open", $logger );
			$redbean->addEventListener( "update", $logger);
			$redbean->addEventListener( "delete", $logger);
			return $toolbox;
		}


		/**
		 * Kickstart method for production environment.
		 * This Kickstart establishes a database connection
		 * using the $dsn, the $username and the $password you provide.
		 * This method will start RedBean in frozen mode which is
		 * the preferred mode of operation for a production environment.
		 * In frozen mode, RedBean will not alter the schema of the database;
		 * which improves performance and security.
		 * This method returns a RedBean_Toolbox $toolbox filled with a
		 * RedBean_Adapter, a RedBean_QueryWriter and most importantly a
		 * RedBean_OODB; the object database. To start storing beans in the database
		 * simply say: $redbean = $toolbox->getRedBean(); Now you have a reference
		 * to the RedBean object.
		 * @param  string $dsn
		 * @param  string $username
		 * @param  string $password
		 * @return RedBean_ToolBox $toolbox
		 */
		public static function kickstartFrozen( $dsn, $username, $password ) {
			$toolbox = self::kickstart($dsn, $username, $password, true);
			$toolbox->getRedBean()->freeze(true);
			return $toolbox;
		}

		/**
		 * Kickstart Method for debugging.
		 * This method returns a RedBean_Toolbox $toolbox filled with a
		 * RedBean_Adapter, a RedBean_QueryWriter and most importantly a
		 * RedBean_OODB; the object database. To start storing beans in the database
		 * simply say: $redbean = $toolbox->getRedBean(); Now you have a reference
		 * to the RedBean object.
		 * @param  string $dsn
		 * @param  string $username
		 * @param  string $password
		 * @return RedBean_ToolBox $toolbox
		 */
		public static function kickstartDebug( $dsn, $username="root", $password="" ) {
			$toolbox = self::kickstart($dsn, $username, $password);
			$toolbox->getDatabaseAdapter()->getDatabase()->setDebugMode( true );
			return $toolbox;
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

		/**
		 * This is a convenience method. By default a kickstart method
		 * returns the RedBean_ToolBox $toolbox for you with all necessary
		 * objects inside. If for some reason you need to have access to the
		 * latest toolbox that Setup has assembled you can use this function
		 * to retrieve it.
		 * Returns the most recently assembled toolbox
		 * @return RedBean_ToolBox $toolbox
		 */
		public static function getToolBox() {
			return self::$toolbox;
		}

}
