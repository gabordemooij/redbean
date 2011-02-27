<?php
/**
 * Created by PhpStorm.
 * User: prive
 * Date: 3-feb-2011
 * Time: 20:31:20
 * To change this template use File | Settings | File Templates.
 */
 
class RedBean_Plugin_QueryLogger implements RedBean_Plugin,RedBean_Observer {

	protected $logs = array();

	public static function getInstanceAndAttach( RedBean_Observable $adapter ) {
		$queryLog = new RedBean_Plugin_QueryLogger;
		$adapter->addEventListener( "sql_exec", $queryLog );
		return $queryLog;
	}

	private function __construct(){

	}

	public function onEvent( $eventName, $adapter ) {
		if ($eventName=="sql_exec") {
			$sql = $adapter->getSQL();
			$this->logs[] = $sql;
			
		}
	}

	public function grep( $word ) {
		$found = array();
		foreach($this->logs as $log) {
			if (strpos($log,$word)!==false) {
				$found[] = $log;
			}

		}
		return $found;
	}

	public function getLogs() {
		return $this->logs;
	}



}
