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

        public function kickstartDev( $dsn, $username, $password ) {

            $pdo = new Redbean_Driver_PDO( "mysql:host=localhost;dbname=oodb","root","" );
            $adapter = new RedBean_DBAdapter( $pdo );
            $writer = new RedBean_QueryWriter_MySQL( $adapter );
            $redbean = new RedBean_OODB( $writer );

            //add concurrency shield
            $redbean->addEventListener( "open", new RedBean_ChangeLogger( new RedBean_QueryWriter_MySQL( $adapter ) ));
            $redbean->addEventListener( "update", new RedBean_ChangeLogger( new RedBean_QueryWriter_MySQL( $adapter ) ));

            //deliver everything back in a neat toolbox
            return new RedBean_ToolBox( $redbean, $adapter, $writer );

        }

	
	
}
