<?php 

/**
 * RedBean Setup
 * Helper class to quickly setup RedBean for you
 * @package 		RedBean/Setup.php
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
		 * @param  string $dsn
		 * @param  string $username
		 * @param  string $password
		 * @return RedBean_ToolBox $toolbox
		 */
        public static function kickstart( $dsn, $username, $password, $frozen=false ) {

            $pdo = new Redbean_Driver_PDO( $dsn,$username,$password );
            $adapter = new RedBean_DBAdapter( $pdo );
            $writer = new RedBean_QueryWriter_MySQL( $adapter, $frozen );
            $redbean = new RedBean_OODB( $writer );

			$toolbox = new RedBean_ToolBox( $redbean, $adapter, $writer );
            
            //deliver everything back in a neat toolbox
			self::$toolbox = $toolbox;
            return self::$toolbox;

        }

		/**
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
		 * Almost the same as Dev, but adds the journaling plugin by default for you.
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
		 * Kickstart Method
		 * Sets up RedBean for Zend Framework.
		 * @param Zend_Db_Adapter_Abstract $adapter
		 * @param boolean $frozen
		 * @return RedBean_ToolBox $toolbox
		 */
		public static function kickstartZendDev( Zend_Db_Adapter_Abstract $adapter, $frozen = false ) {
			$adapter = new RedBean_Adapter_Zend( $adapter );
            $writer = new RedBean_QueryWriter_MySQL( $adapter, $frozen );
            $redbean = new RedBean_OODB( $writer );
			$toolbox = new RedBean_ToolBox( $redbean, $adapter, $writer );
            //deliver everything back in a neat toolbox
			self::$toolbox = $toolbox;
			Zend_Registry::set("redbean_toolbox", self::$toolbox);
			return self::$toolbox;
		}



		/**
		 * Returns the observers that have been attached by Setup
		 * @return array $observers
		 */
		public static function getAttachedObservers() {
			return self::$observers;
		}

		/**
		 * Returns the most recently assembled toolbox
		 * @return RedBean_ToolBox $toolbox
		 */
		public static function getToolBox() {
			return self::$toolbox;
		}

}
