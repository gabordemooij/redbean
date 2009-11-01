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
        public static function kickstart( $dsn, $username, $password ) {

            $pdo = new Redbean_Driver_PDO( $dsn,$username,$password );
            $adapter = new RedBean_DBAdapter( $pdo );
            $writer = new RedBean_QueryWriter_MySQL( $adapter );
            $redbean = new RedBean_OODB( $writer );

            //add concurrency shield
			$logger = new RedBean_ChangeLogger( $writer );
			self::$observers["logger"] = $logger;
            $redbean->addEventListener( "open", $logger );
            $redbean->addEventListener( "update", $logger);
			$redbean->addEventListener( "delete", $logger);

            //deliver everything back in a neat toolbox
			self::$toolbox = new RedBean_ToolBox( $redbean, $adapter, $writer );
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
		 * @param  string $dsn
		 * @param  string $username
		 * @param  string $password
		 * @return RedBean_ToolBox $toolbox
		 */
		public static function kickstartFrozen( $dsn, $username, $password ) {
			$toolbox = self::kickstart($dsn, $username, $password);
			$toolbox->getRedBean()->freeze(true);
			return $toolbox;
		}

		/**
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
